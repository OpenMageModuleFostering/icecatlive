<?php
/**
 * Class provides category page with images, cron processing
 *
 *
 */
class Iceshop_Icecatlive_Model_Observer
{
    /**
     * Our process ID.
     */
    private $process_id = 'iceshop_icecat';
    private $indexProcess;


    private $errorMessage;
    private $connection;
    private $freeExportURLs = 'http://data.icecat.biz/export/freeurls/export_urls_rich.txt.gz';
    private $fullExportURLs = 'http://data.icecat.biz/export/export_urls_rich.txt.gz';
    private $productinfoUrL = 'http://data.icecat.biz/prodid/prodid_d.txt.gz';
    protected $_supplierMappingUrl = 'http://data.icecat.biz/export/freeurls/supplier_mapping.xml';
    protected $_connectorDir = '/iceshop/icecatlive/';
    protected $_productFile;
    protected $_supplierFile;

    protected function _construct()
    {
        $this->_init('icecatlive/observer');
    }

    /**
     * root method for uploading images to DB
     */
    public function load()
    {


        $loadUrl = $this->getLoadURL();
        ini_set('max_execution_time', 0);
        try {
            $this->indexProcess = new Mage_Index_Model_Process();
            $this->indexProcess->setId($this->process_id);

            if ($this->indexProcess->isLocked()) {
                throw new Exception('Error! Another icecat module cron process is running!');
            }

            $this->indexProcess->lockAndBlock();

            $this->_productFile = $this->_prepareFile(basename($loadUrl));
            $this->_supplierFile = $this->_prepareFile(basename($this->_supplierMappingUrl));
            $importlogFile =  Mage::getBaseDir('var') . $this->_connectorDir . 'import.log';
            $importlogHandler = fopen($importlogFile, "w");

            fwrite($importlogHandler, "Downloading product data file (export_urls_rich.txt.gz)\n");
            $this->downloadFile($this->_productFile, $loadUrl);

            fwrite($importlogHandler, "Downloading supplier mapping file (supplier_mapping.xml)\n");
            $this->downloadFile($this->_supplierFile, $this->_supplierMappingUrl);
            $this->XMLfile = Mage::getBaseDir('var') . $this->_connectorDir . basename($loadUrl, ".gz");

            fwrite($importlogHandler, "Unzipping files\n");
            $this->unzipFile();

            fwrite($importlogHandler, "Importing supplier mapping (supplier_mapping.xml) to database\n");
            $this->_loadSupplierListToDb();

            fwrite($importlogHandler, "Importing products data (export_urls_rich.txt) to database\n");
            $this->loadFileToDb();

            //Start load product data file
            $loadUrl = $this->productinfoUrL;
            $this->_productFile = $this->_prepareFile(basename($loadUrl));

            fwrite($importlogHandler, "Downloading additional product data file (prodid_d.txt.gz)\n");
            $this->downloadFile($this->_productFile, $loadUrl);

            fwrite($importlogHandler, "Unzipping additional product data file\n");
            $this->unzipFile();

            fwrite($importlogHandler, "Importing additional product data (prodid_d.txt.gz) to database\n");
            $this->loadInfoFileToDb();
            fwrite($importlogHandler, "Import process complete.\n");

            $this->indexProcess->unlock();
            fclose ($importlogHandler);
        } catch (Exception $e) {
            echo $e->getMessage();
            Mage::log($e->getMessage());
        }
    }

    /**
     * parse given XML to SIMPLE XML
     * @param string $stringXml
     */
    protected function _parseXml($stringXml)
    {
        libxml_use_internal_errors(true);
        $simpleDoc = simplexml_load_string($stringXml);
        if ($simpleDoc) {
            return $simpleDoc;
        }
        $simpleDoc = simplexml_load_string(utf8_encode($stringXml));
        if ($simpleDoc) {
            return $simpleDoc;
        }
        return false;
    }

    /**
     * Upload supplier mapping list to Database
     */
    protected function _loadSupplierListToDb()
    {
        $connection = $this->getDbConnection();
        $mappingTable = Mage::getSingleton('core/resource')->getTableName('icecatlive/supplier_mapping');
        try {
            $connection->beginTransaction();
            $xmlString = file_get_contents($this->_supplierFile);
            $xmlDoc = $this->_parseXml($xmlString);
            if ($xmlDoc) {
                $connection->query("DROP TABLE IF EXISTS `" . $mappingTable . "_temp`");
                $connection->query("
            CREATE TABLE `" . $mappingTable . "_temp` (
              `supplier_id` int(11) NOT NULL,
              `supplier_symbol` varchar(255) DEFAULT NULL,
              KEY `supplier_id` (`supplier_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 
            ");
                $sql = '';
                $counter = 1;
                $max_counter = 1000;
                $supplierList = $xmlDoc->SupplierMappings->SupplierMapping;
                foreach ($supplierList as $supplier) {
                    $supplierSymbolList = $supplier->Symbol;
                    $supplierId = $supplier['supplier_id'];
                    $supplierName = addslashes((string)$supplier['name']);
                    if ($counter == 1) {
                        $sql = " INSERT INTO  " . $mappingTable . "_temp ( supplier_id, supplier_symbol ) VALUES('" . $supplierId . "', '" . $supplierName . "') ";
                    } else if ($counter % $max_counter == 0) {
                        $connection->query($sql);
                        $sql = " INSERT INTO  " . $mappingTable . "_temp ( supplier_id, supplier_symbol ) VALUES('" . $supplierId . "', '" . $supplierName . "')  ";
                    } else {
                        $sql .= " , ('" . $supplierId . "', '" . $supplierName . "') ";
                    }
                    foreach ($supplierSymbolList as $symbol) {
                        $symbolName = addslashes((string)$symbol);
                        if ($counter == 1) {
                            $sql = " INSERT INTO  " . $mappingTable . "_temp ( supplier_id, supplier_symbol ) VALUES('" . $supplierId . "', '" . $symbolName . "') ";
                        } else if ($counter % $max_counter == 0) {
                            $connection->query($sql);
                            $sql = " INSERT INTO  " . $mappingTable . "_temp ( supplier_id, supplier_symbol ) VALUES('" . $supplierId . "', '" . $symbolName . "')  ";
                        } else {
                            $sql .= " , ('" . $supplierId . "', '" . $symbolName . "') ";
                        }
                        $counter++;
                    }
                    $counter++;
                }
                $connection->query($sql);
                $connection->query("DROP TABLE IF EXISTS `" . $mappingTable . "_old`");
                $connection->query("rename table `" . $mappingTable . "` to `" . $mappingTable . "_old`, `" . $mappingTable . "_temp` to " . $mappingTable);
                $connection->query("DROP TABLE IF EXISTS `" . $mappingTable . "_old`");
                $connection->commit();
            } else {
                throw new Exception('Unable to process supplier file');
            }
        } catch (Exception $e) {
            $connection->rollBack();
            throw new Exception("Icecat Import Terminated: {$e->getMessage()}");
        }
    }

    /**
     * retrieve URL of data file that corresponds ICEcat account
     */
    private function getLoadURL()
    {
        $subscripionLevel = Mage::getStoreConfig('icecat_root/icecat/icecat_type');

        if ($subscripionLevel === 'full') {
            return $this->fullExportURLs;
        } else {
            return $this->freeExportURLs;
        }
    }

    /**
     * return error messages
     */
    public function getErrorMessage()
    {
        return $this->errorMessage;
    }

    /**
     * getImage URL from DB
     * @param string $productSku
     * @param string $productManufacturer
     */
    public function getImageURL($productSku, $productManufacturer, $productId = '')
    {

        $connection = $this->getDbConnection();
        try {

            $dataTable = Mage::getSingleton('core/resource')->getTableName('icecatlive/data');
            $data_productsTable = Mage::getSingleton('core/resource')->getTableName('icecatlive/data_products');
            $mappingTable = Mage::getSingleton('core/resource')->getTableName('icecatlive/supplier_mapping');
            $model = Mage::getModel('catalog/product');
            $_product = $model->load($productId);
            $ean_code = $_product->getData(Mage::getStoreConfig('icecat_root/icecat/ean_code'));
            $imageURL = '';

            if (isset($productManufacturer) && !empty($productManufacturer)) {
                $selectCondition = $connection->select()
                    ->from(array('connector' => $dataTable), new Zend_Db_Expr('connector.prod_img'))
                    ->joinInner(array('supplier' => $mappingTable), "connector.supplier_id = supplier.supplier_id AND supplier.supplier_symbol = {$this->connection->quote($productManufacturer)}")
                    ->where('connector.prod_id = ?', $productSku);
                $imageURL = $connection->fetchOne($selectCondition);
            }
            if (empty($imageURL) && !empty($ean_code)) {
                $selectCondition = $connection->select()
                    ->from(array('connector' => $dataTable), new Zend_Db_Expr('connector.prod_img'))
                    ->joinLeft(array('products' => $data_productsTable), "connector.prod_id = products.prod_id")
                    ->where('products.prod_ean = ?', trim($ean_code));
                $imageURL = $connection->fetchOne($selectCondition);
            }

            if (!empty($imageURL)) {
                $iceCatModel = Mage::getSingleton('icecatlive/import');

                if (isset($productId) && !empty($productId)) {
                    $imageURL = $iceCatModel->saveImg($productId, $imageURL, 'image');
                }
            }

            if (empty($imageURL)) {
                $this->errorMessage = "Given product id is not present in database";
                return $imageURL;
            }
            return $imageURL;
        } catch (Exception $e) {
            $this->errorMessage = "DB ERROR: {$e->getMessage()}";
            return false;
        }
    }

    /**
     * Singletong for DB connection
     */
    private function getDbConnection()
    {
        if ($this->connection) {
            return $this->connection;
        }
        $this->connection = Mage::getSingleton('core/resource')->getConnection('core_read');

        return $this->connection;
    }

    /**
     * Upload Data file to DP
     */
    private function loadFileToDb()
    {
        $connection = $this->getDbConnection();
        $dataTable = Mage::getSingleton('core/resource')->getTableName('icecatlive/data');

        $max_counter = 1000;
        try {
            $connection->beginTransaction();
            $fileHandler = fopen($this->XMLfile, "r");
            if ($fileHandler) {
                $connection->query("DROP TABLE IF EXISTS `" . $dataTable . "_temp`");
                $connection->query("
                    CREATE TABLE `" . $dataTable . "_temp` (
                   `prod_id` varchar(255) NOT NULL,
                   `supplier_id` int(11) DEFAULT NULL,
                   `prod_name` varchar(255) DEFAULT NULL,
                   `prod_img` varchar(255) DEFAULT NULL,
                   KEY `PRODUCT_MPN` (`prod_id`),
                   KEY `supplier_id` (`supplier_id`)
                   ) ENGINE=InnoDB DEFAULT CHARSET=utf8
                ");

                $counter = 0;
                $sql = "";
                while (!feof($fileHandler)) {
                    $row = fgets($fileHandler);
                    $oneLine = explode("\t", $row);

                    if ($oneLine[0] != 'product_id' && $oneLine[0] != '') {

                        try {

                            $prod_id = (!empty($oneLine[1])) ? addslashes($oneLine[1]) : '';
                            if(!empty($oneLine[5])){
                                $prod_img = addslashes($oneLine[5]);
                            }elseif(!empty($oneLine[6])){
                                $prod_img = addslashes($oneLine[6]);
                            }elseif(!empty($oneLine[7])){
                                $prod_img = addslashes($oneLine[7]);
                            }else{
                                $prod_img = '';
                            }
                            $prod_name = (!empty($oneLine[12])) ? addslashes($oneLine[12]) : '';
                            $supplier_id = (!empty($oneLine[4])) ? addslashes($oneLine[4]) : '';

                            if ($counter == 1) {
                                $sql = " INSERT INTO  " . $dataTable . "_temp ( prod_id, supplier_id, prod_name, prod_img ) VALUES('" . $prod_id . "', '" . $supplier_id . "', '" . $prod_name . "', '" . $prod_img . "') ";
                            } else if ($counter % $max_counter == 0) {
                                $connection->query($sql);
                                $sql = " INSERT INTO  " . $dataTable . "_temp ( prod_id, supplier_id, prod_name, prod_img ) VALUES('" . $prod_id . "', '" . $supplier_id . "', '" . $prod_name . "', '" . $prod_img . "') ";
                            } else {
                                $sql .= " , ('" . $prod_id . "', '" . $supplier_id . "', '" . $prod_name . "', '" . $prod_img . "') ";
                            }

                        } catch (Exception $e) {
                            Mage::log("connector issue: {$e->getMessage()}");
                        }
                    }
                    $counter++;
                }
                $connection->query($sql);
                $connection->query("DROP TABLE IF EXISTS `" . $dataTable . "_old`");
                $connection->query("RENAME TABLE `" . $dataTable . "` TO `" . $dataTable . "_old`, `" . $dataTable . "_temp` TO " . $dataTable);
                $connection->query("DROP TABLE IF EXISTS `" . $dataTable . "_old`");
                $connection->commit();
                fclose($fileHandler);
            }
        } catch (Exception $e) {
            $connection->rollBack();
            throw new Exception("Icecat Import Terminated: {$e->getMessage()}");
        }
    }

    /**
     * Upload Data file to DP
     */
    private function loadInfoFileToDb()
    {
        $connection = $this->getDbConnection();
        $data_productsTable = Mage::getSingleton('core/resource')->getTableName('icecatlive/data_products');
        $max_counter = 1000;
        try {
            $connection->beginTransaction();
            $fileHandler = fopen($this->XMLfile, "r");
            if ($fileHandler) {

                $connection->query("DROP TABLE IF EXISTS `" . $data_productsTable . "`");
                $connection->query("
                    CREATE TABLE `" . $data_productsTable . "` (
                    `prod_id` varchar(255) NOT NULL,
                    `supplier_symbol` varchar(255) DEFAULT NULL,
                    `prod_title`  varchar(255) DEFAULT NULL,
                    `prod_ean` varchar(255) NOT NULL,
                    KEY `prod_id`     (`prod_id`),
                    KEY `PRODUCT_EAN` (`prod_ean`),
                    INDEX `mpn_brand` (`prod_id`, `supplier_symbol`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8
                ");

                $counter = 1;
                $sql = "";
                while (!feof($fileHandler)) {
                    $row = fgets($fileHandler);
                    $oneLine = explode("\t", $row);

                    if ($oneLine[0] != 'Part number') {
                        try {
                            $oneLine3 = trim($oneLine[3]);
                            $oneLine15 = trim($oneLine[15]);
                            $oneLine24 = trim($oneLine[24]);
                            if (!empty($oneLine15)) {
                                $eans = explode(';', $oneLine15);
                            }
                            $prod_id = (!empty($oneLine[0])) ? addslashes(str_replace("\t", '', $oneLine[0])) : '';
                            $brand = (!empty($oneLine3)) ? addslashes(str_replace("\t", '', $oneLine3)) : '';
                            $title = (!empty($oneLine24)) ? addslashes(str_replace("\t", '', $oneLine24)) : '';
                            if (is_array($eans) && !empty($eans)){
                                foreach($eans as $prod_ean){
                                    if ($counter == 1) {
                                        $sql .= " INSERT INTO  " . $data_productsTable . " ( prod_id, supplier_symbol, prod_title, prod_ean ) VALUES('" . $prod_id . "', '" . $brand . "', '" . $title. "', '" . $prod_ean . "') ";
                                    } else if ($counter % $max_counter == 0) {
                                        $connection->query($sql);
                                        $sql = " INSERT INTO  " . $data_productsTable . " ( prod_id, supplier_symbol, prod_title, prod_ean ) VALUES('" . $prod_id . "', '" . $brand . "', '" . $title . "', '" . $prod_ean . "') ";
                                    } else {
                                        $sql .= " , ('" . $prod_id . "', '" . $brand . "', '" . $title . "', '" . $prod_ean . "') ";
                                    }
                                    $counter++;
                                }
                            }else{
                                $prod_ean = !empty($eans) ? $eans : '';
                                if ($counter == 1) {
                                    $sql .= " INSERT INTO  " . $data_productsTable . " ( prod_id, supplier_symbol, prod_title, prod_ean ) VALUES('" . $prod_id . "', '" . $brand . "', '" . $title. "', '" . $prod_ean . "') ";
                                } else if ($counter % $max_counter == 0) {
                                    $connection->query($sql);
                                    $sql = " INSERT INTO  " . $data_productsTable . " ( prod_id, supplier_symbol, prod_title, prod_ean ) VALUES('" . $prod_id . "', '" . $brand . "', '" . $title . "', '" . $prod_ean . "') ";
                                } else {
                                    $sql .= " , ('" . $prod_id . "', '" . $brand . "', '" . $title . "', '" . $prod_ean . "') ";
                                }
                                $counter++;
                            }
                        } catch (Exception $e) {
                            Mage::log("connector issue: {$e->getMessage()}");
                        }
                    }
                }
                $connection->query($sql);
                $connection->commit();
                fclose($fileHandler);
            }
        } catch (Exception $e) {
            $connection->rollBack();
            throw new Exception("Icecat Import Terminated: {$e->getMessage()}");
        }
    }

    /**
     * unzip Uploaded file
     */
    private function unzipFile()
    {
        $gz = gzopen($this->_productFile, 'rb');
        if (file_exists($this->XMLfile)) {
            unlink($this->XMLfile);
        }
        $fileToWrite = @fopen($this->XMLfile, 'w+');
        if (!$fileToWrite) {
            $this->errorMessage = 'Unable to open output txt file. Please remove all *.txt files from ' .
                Mage::getBaseDir('var') . $this->_connectorDir . 'folder';
            return false;
        }
        while (!gzeof($gz)) {
            $buffer = gzgets($gz, 100000);
            fputs($fileToWrite, $buffer);
        }
        gzclose($gz);
        fclose($fileToWrite);
    }

    /**
     * Process downloading files
     * @param string $destinationFile
     * @param string $loadUrl
     */
    private function downloadFile($destinationFile, $loadUrl)
    {
        $userName = Mage::getStoreConfig('icecat_root/icecat/login');
        $userPass = Mage::getStoreConfig('icecat_root/icecat/password');
        $fileToWrite = @fopen($destinationFile, 'w+');

        try {
            $webClient = new Zend_Http_Client();
            $webClient->setUri($loadUrl);
            $webClient->setConfig(array('maxredirects' => 0, 'timeout' => 60));
            $webClient->setMethod(Zend_Http_Client::GET);
            $webClient->setHeaders('Content-Type: text/xml; charset=UTF-8');
            $webClient->setAuth($userName, $userPass, Zend_Http_CLient::AUTH_BASIC);
            $response = $webClient->request('GET');
            if ($response->isError()) {
                throw new Exception("\nERROR Occured.\nResponse Status: " . $response->getStatus() . "\nResponse Message: " . $response->getMessage() ."\nResponse Body: " . $response->getBody() .
                "\nPlease, make sure that you added correct icecat username and password to Icecat Live! configuration and you have your webshop's correct IP in Allowed IP addresses field in your icecat account.");
            }
        } catch (Exception $e) {
            throw new Exception("Warning: cannot connect to ICEcat. {$e->getMessage()}");
        }
        $resultString = $response->getBody();
        fwrite($fileToWrite, $resultString);
        fclose($fileToWrite);
    }

    /**
     * Prepares file and folder for futur download
     * @param string $fileName
     */
    protected function _prepareFile($fileName)
    {
        $varDir = Mage::getBaseDir('var') . $this->_connectorDir;
        $filePath = $varDir . $fileName;
        if (!is_dir($varDir)) {
            mkdir($varDir, 0777, true);
        }
        if (file_exists($filePath)) {
            unlink($filePath);
        }
        return $filePath;
    }
}

?>
