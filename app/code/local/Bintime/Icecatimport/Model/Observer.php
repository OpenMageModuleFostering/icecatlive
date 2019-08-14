<?php
/**
 * Class provides category page with images, cron processing
 * 
 *  @author Sergey Gozhedrianov <info@bintime.com>
 *
 */
class Bintime_Icecatimport_Model_Observer 
{
  private   $errorMessage;
  private   $connection;

  private   $freeExportURLs = 'http://data.icecat.biz/export/freeurls/export_urls_rich.txt.gz';
  private   $fullExportURLs = 'http://data.icecat.biz/export/export_urls_rich.txt.gz';
  private   $productinfoUrL = 'http://data.icecat.biz/prodid/prodid_d.txt.gz';
  protected $_supplierMappingUrl = 'http://data.icecat.biz/export/freeurls/supplier_mapping.xml';
  protected $_connectorDir = '/bintime/icecatimport/';
  protected $_productFile;
  protected $_supplierFile;

  protected function _construct(){
    $this->_init('icecatimport/observer');
  }

  /**
   * root method for uploading images to DB
   */
  public function load(){
$testfile = Mage::getBaseDir('var') . $this->_connectorDir . 'newt.txt';
file_put_contents($testfile, 'here obs',FILE_APPEND);
    $loadUrl = $this->getLoadURL();
    ini_set('max_execution_time', 0);
    try {
      $this->_productFile = $this->_prepareFile(basename($loadUrl));
      $this->_supplierFile = $this->_prepareFile(basename($this->_supplierMappingUrl));
      echo "Data file downloading started <br>";
      $this->downloadFile($this->_productFile, $loadUrl);

      echo "Start of supplier mapping file download<br>";
      $this->downloadFile($this->_supplierFile, $this->_supplierMappingUrl);
      $this->XMLfile = Mage::getBaseDir('var') . $this->_connectorDir . basename($loadUrl, ".gz");
  
      echo "Start Unzipping Data File<br>";
      $this->unzipFile();
      echo "Start File Processing<br>";
      
      $this->_loadSupplierListToDb();
      $this->loadFileToDb();
      echo "File Processed Succesfully<br>";
    
      //Start load product data file
      $loadUrl = $this->productinfoUrL;
      $this->_productFile = $this->_prepareFile(basename($loadUrl));
      echo " Product Data file downloading started <br>";
      $this->downloadFile($this->_productFile, $loadUrl);
      echo "Start Unzipping Data File<br>";
      $this->unzipFile();
      echo "Start File Processing<br>";
      $this->loadFileToDb();
      
      echo " Product Data File Processed Succesfully<br>";
    } catch( Exception $e) {
      echo $e->getMessage();
      Mage::log($e->getMessage());
    }
  }

  /**
   * parse given XML to SIMPLE XML
   * @param string $stringXml
   */
  protected function _parseXml($stringXml){
    libxml_use_internal_errors(true);
    $simpleDoc = simplexml_load_string($stringXml);
    if ($simpleDoc){
      return $simpleDoc;
    }
    $simpleDoc = simplexml_load_string(utf8_encode($stringXml));
    if ($simpleDoc){
      return $simpleDoc;
    }  
    return false;
  }

  /**
   * Upload supplier mapping list to Database
   */
  protected function _loadSupplierListToDb(){
    $connection = $this->getDbConnection();
    $mappingTable = Mage::getSingleton('core/resource')->getTableName('icecatimport/supplier_mapping');
    try {
      $connection->beginTransaction();
      $xmlString = file_get_contents($this->_supplierFile);
      $xmlDoc = $this->_parseXml($xmlString);
      if ($xmlDoc) {
        $connection->query("DROP TABLE IF EXISTS `".$mappingTable."_temp`");
        $connection->query("
            CREATE TABLE `".$mappingTable."_temp` (
              `supplier_id` int(11) NOT NULL,
              `supplier_symbol` varchar(255) DEFAULT NULL,
              KEY `supplier_id` (`supplier_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 
            ");

        $supplierList = $xmlDoc->SupplierMappings->SupplierMapping;
        foreach ($supplierList as $supplier) {
          $supplierSymbolList = $supplier->Symbol;
          $supplierId         = $supplier['supplier_id'];
          $connection->insert($mappingTable."_temp", array('supplier_id' => $supplierId, 'supplier_symbol' => (string)$supplier['name']));
          foreach($supplierSymbolList as $symbol) {
            $symbolName = (string)$symbol;
            $connection->insert($mappingTable."_temp", array('supplier_id' => $supplierId, 'supplier_symbol' => $symbolName));
          }
        }
        
        $connection->query("DROP TABLE IF EXISTS `".$mappingTable."_old`");
        $connection->query("rename table `".$mappingTable."` to `".$mappingTable."_old`, `".$mappingTable."_temp` to ".$mappingTable);
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
  private function getLoadURL(){
    $subscripionLevel = Mage::getStoreConfig('icecat_root/icecat/icecat_type');

    if ($subscripionLevel === 'full'){
      return $this->fullExportURLs;
    }
    else {
      return $this->freeExportURLs;
    }
  }

  /**
   * return error messages
   */
  public function getErrorMessage(){
    return $this->errorMessage;
  }

  /**
   * getImage URL from DB
   * @param string $productSku
   * @param string $productManufacturer
   */
  public function getImageURL($productSku, $productManufacturer, $productId = ''){
    $connection = $this->getDbConnection();
    $testfile = Mage::getBaseDir('var') . $this->_connectorDir . 'test_img2.txt';
    try {
  
      $tableName = Mage::getSingleton('core/resource')->getTableName('icecatimport/data');
      $mappingTable = Mage::getSingleton('core/resource')->getTableName('icecatimport/supplier_mapping');
      $model = Mage::getModel('catalog/product');
      $_product = $model->load($productId);  
      $ean_code     = $_product->getData(Mage::getStoreConfig('icecat_root/icecat/ean_code'));
      $imageURL = '';

      if (isset($productManufacturer) && !empty($productManufacturer)) {
        $selectCondition = $connection->select()
           ->from(array('connector' => $tableName), new Zend_Db_Expr('connector.prod_img'))
           ->joinInner(array('supplier' => $mappingTable), "connector.supplier_id = supplier.supplier_id AND supplier.supplier_symbol = {$this->connection->quote($productManufacturer)}")
           ->where('connector.prod_id = ?', $productSku);
        $imageURL = $connection->fetchOne($selectCondition);
      } 
      if (empty($imageURL) && !empty($ean_code)) {
        $selectCondition = $connection->select()
           ->from(array('connector' => $tableName), new Zend_Db_Expr('connector.prod_img'))
           ->joinLeft(array('products' => $tableName.'_products'), "connector.prod_id = products.prod_id")
           ->where('products.prod_ean = ?', trim($ean_code)); 
        $imageURL = $connection->fetchOne($selectCondition);
      }

      if (!empty($imageURL)) {
        $iceCatModel = Mage::getSingleton('icecatimport/import'); 

        if(isset($productId) && !empty($productId)) {
          $imageURL = $iceCatModel->saveImg($productId,$imageURL);
        }
      }

      if (empty($imageURL)){
        $imageURL = Mage::getStoreConfig('web/unsecure/base_url').'/skin/frontend/base/default/images/catalog/product/placeholder/image.jpg';
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
  private function getDbConnection(){
    if ($this->connection){
      return $this->connection;
    }
    $this->connection = Mage::getSingleton('core/resource')->getConnection('core_read');
    return $this->connection;
  }
  
  /**
   * Upload Data file to DP
   */
  private function loadFileToDb(){
    $connection = $this->getDbConnection();
    $testfile = Mage::getBaseDir('var') . $this->_connectorDir . 'newt.txt';
    $tableName = Mage::getSingleton('core/resource')->getTableName('icecatimport/data');
    $is_info_file = strpos($this->_productFile,'prodid_d.txt');
    try {
      $connection->beginTransaction();
      $fileHandler = fopen($this->XMLfile, "r");
      if ($fileHandler) {
        if (!$is_info_file) {
          $connection->query("DROP TABLE IF EXISTS `".$tableName."_temp`");
          $connection->query("
              CREATE TABLE `".$tableName."_temp` (
                `prod_id` varchar(255) NOT NULL,
                `supplier_id` int(11) DEFAULT NULL,
                `prod_name` varchar(255) DEFAULT NULL,
                `prod_img` varchar(255) DEFAULT NULL,
                KEY `PRODUCT_MPN` (`prod_id`),
                KEY `supplier_id` (`supplier_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8
          ");
        } else {
          $connection->query("DROP TABLE IF EXISTS `".$tableName."_products`");
          $connection->query("
              CREATE TABLE `".$tableName."_products` (
                `prod_id` varchar(255) NOT NULL,
                `prod_title`  varchar(255) DEFAULT NULL,
                `prod_ean` varchar(255) NOT NULL,
                KEY `prod_id`     (`prod_id`),
                KEY `PRODUCT_EAN` (`prod_ean`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8
          ");  
        }
	if (!$is_info_file) {
          $csvFile = Mage::getBaseDir('var') . $this->_connectorDir . 'ice_cat_temp.csv';
        } else {
	  $csvFile = Mage::getBaseDir('var') . $this->_connectorDir . 'ice_cat_temp_prod.csv';  
        }
		
	$csvFile = str_replace("\\", "\\\\", $csvFile);
        $csvFileRes = fopen($csvFile, "w+");
		
        while (!feof($fileHandler)) {
          $row = fgets($fileHandler);
          $oneLine = explode("\t", $row);
          
          if ($oneLine[0]!= 'product_id' && $oneLine[0]!= '' && !$is_info_file) {
            try{
              
              $prod_id      = (!empty($oneLine[1]))  ? $oneLine[1]  : '';
              $prod_img     = (!empty($oneLine[6]))  ? $oneLine[6]  : $oneLine[5];
              $prod_name    = (!empty($oneLine[12])) ? $oneLine[12] : '';
              $supplier_id  = (!empty($oneLine[4])) ? $oneLine[4]   : '';
              $line = "$prod_id\t$supplier_id\t$prod_name\t$prod_img\n";
             
              fwrite($csvFileRes, $line);
            }catch(Exception $e){
              Mage::log("connector issue: {$e->getMessage()}");
            }
          } else if ($is_info_file && $oneLine[0]!= 'Part number') {
            try{   
              $oneLine3  = trim($oneLine[3]);
              $oneLine12 = trim($oneLine[12]);
              $oneLine21 = trim($oneLine[21]);
              $oneLine15 = trim($oneLine[15]);
              if (!empty($oneLine15)) {
                $prod_ean = $oneLine15;
                $eans = explode(';',$oneLine15);
                if (is_array($eans) && count($eans) > 0 && array_key_exists(0,$eans)) {
                  $prod_ean = !empty($eans[0]) ? $eans[0] : '';    
                }  
              }
              $prod_id    = (!empty($oneLine[0])) ? str_replace("\t",'',$oneLine[0])           : '';  
              $brand      = (!empty($oneLine3))   ? str_replace("\t",'',$oneLine[3]. '|')      : '';
              $model      = (!empty($oneLine12))  ? str_replace("\t",'',$oneLine[12].'|')      : '';
              $family     = (!empty($oneLine21))  ? preg_replace("/\s+/", "",$oneLine[21].'|') : ''; 
              $line = "$prod_id\t$brand$family$model\t$prod_ean\n";
              fwrite($csvFileRes, $line);
            }catch(Exception $e){
              Mage::log("connector issue: {$e->getMessage()}");
            }  
          }
        }
		
		$connection->commit();
        $config  = Mage::getConfig()->getResourceConnectionConfig(Mage_Core_Model_Resource::DEFAULT_WRITE_RESOURCE);
        $sql_file = Mage::getBaseDir('var') . $this->_connectorDir . 'test.sql';

	$hostname = $config->host;
        $user     = $config->username;
        $password = $config->password;
        $dbname   = $config->dbname;
		  
        // write csv file into temp table
        if (!$is_info_file) {
		  $sql = 'LOAD DATA LOCAL INFILE "'.$csvFile.'" INTO TABLE '.$tableName.'_temp ( prod_id, supplier_id, prod_name, prod_img );';
		  file_put_contents($sql_file,$sql);
		  $res = system("mysql -u$user -p$password -h$hostname $dbname < \"$sql_file\" ");
		  file_put_contents($testfile, "mysql -u$user -p$password -h$hostname $dbname < $sql_file",FILE_APPEND);
          //$connection->query('LOAD DATA LOCAL INFILE "'.$csvFile.'" INTO TABLE '.$tableName.'_temp ( prod_id, supplier_id, prod_name, prod_img )');
          $connection->query("DROP TABLE IF EXISTS `".$tableName."_old`");
          $connection->query("rename table `".$tableName."` to `".$tableName."_old`, `".$tableName."_temp` to ".$tableName);
          $connection->commit();
          fclose($fileHandler);
          unlink($csvFile);
        } else {   
          $sql = 'LOAD DATA LOCAL INFILE "'.$csvFile.'" INTO TABLE '.$tableName.'_products ( prod_id, prod_title,prod_ean );';   
		  file_put_contents($sql_file,$sql);
          $res = system("mysql -u$user -p$password -h$hostname $dbname < \"$sql_file\" ");
		  file_put_contents($testfile, "mysql -u$user -p$password -h$hostname $dbname < $sql_file", FILE_APPEND);
          $connection->commit();
          fclose($fileHandler);
          unlink($csvFile);  
        }
	  }
    } catch (Exception $e) {
      $connection->rollBack();
      throw new Exception("Icecat Import Terminated: {$e->getMessage()}");
    }
  }
  
  /**
   * unzip Uploaded file
   */
  private function unzipFile(){
    $gz = gzopen ( $this->_productFile, 'rb' );
    if (file_exists($this->XMLfile)){
      unlink($this->XMLfile);
    }
    $fileToWrite = @fopen($this->XMLfile, 'w+');
    if (!$fileToWrite){
      $this->errorMessage = 'Unable to open output txt file. Please remove all *.txt files from '.
      Mage::getBaseDir('var'). $this->_connectorDir .'folder';
      return false;
    }
    while (!gzeof($gz)) {
      $buffer = gzgets($gz, 100000);
      fputs($fileToWrite, $buffer) ;
    }
    gzclose ($gz);
    fclose($fileToWrite);
  }
  
  /**
   * Process downloading files
   * @param string $destinationFile
   * @param string $loadUrl
   */
  private function downloadFile($destinationFile, $loadUrl){
    $userName = Mage::getStoreConfig('icecat_root/icecat/login');
    $userPass = Mage::getStoreConfig('icecat_root/icecat/password');
    $fileToWrite = @fopen($destinationFile, 'w+');
    
    try{
      $webClient = new Zend_Http_Client();
      $webClient->setUri($loadUrl);
      $webClient->setConfig(array('maxredirects' => 0,  'timeout'      => 60));
      $webClient->setMethod(Zend_Http_Client::GET);
      $webClient->setHeaders('Content-Type: text/xml; charset=UTF-8');
      $webClient->setAuth($userName, $userPass, Zend_Http_CLient::AUTH_BASIC);
      $response = $webClient->request('GET');
      if ($response->isError()){
        throw new Exception('<br>ERROR Occured.<br>Response Status: '.$response->getStatus()."<br>Response Message: ".$response->getMessage());
      }
    }
    catch (Exception $e) {
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
  protected function _prepareFile($fileName){
    $varDir =  Mage::getBaseDir('var') . $this->_connectorDir;
    $filePath = $varDir . $fileName;
    if (!is_dir($varDir)){
      mkdir($varDir, 0777, true);
    }
    if (file_exists($filePath)){
      unlink($filePath);
    }
    return $filePath;
  }
}
?>
