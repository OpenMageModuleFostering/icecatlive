<?php
/**
 * Class provides category page with images, cron processing
 * 
 *  @author Sergey Gozhedrianov <info@bintime.com>
 *
 */
class Bintime_Icecatimport_Model_Observer
{
	private $errorMessage;
	private $connection;
	
	private $freeExportURLs = 'http://data.icecat.biz/export/freeurls/export_urls_rich.txt.gz';
	private $fullExportURLs = 'http://data.icecat.biz/export/export_urls_rich.txt.gz';
	protected $_supplierMappingUrl = 'http://data.icecat.biz/export/freeurls/supplier_mapping.xml';
	protected $_connectorDir = '/bintime/icecatimport/';
	protected $_productFile;
	protected $_supplierFile;
	
	protected function _construct()
	{
		$this->_init('icecatimport/observer');
	}
	
	/**
	 * root method for uploading images to DB
	 */
	public function load(){
		
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
	protected function _loadSupplierListToDb()
	{
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
					$supplierId = $supplier['supplier_id'];
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
	public function getImageURL($productSku, $productManufacturer){

		$connection = $this->getDbConnection();
		try {

			$tableName = Mage::getSingleton('core/resource')->getTableName('icecatimport/data');
			$mappingTable = Mage::getSingleton('core/resource')->getTableName('icecatimport/supplier_mapping');
	
			$selectCondition = $connection->select()
						->from(array('connector' => $tableName), new Zend_Db_Expr('connector.prod_img'))
						->joinInner(array('supplier' => $mappingTable), "connector.supplier_id = supplier.supplier_id AND supplier.supplier_symbol = {$this->connection->quote($productManufacturer)}")
						->where('connector.prod_id = ? ', $productSku);

			$imageURL = $connection->fetchOne($selectCondition);
			if (empty($imageURL)){
				$this->errorMessage = "Given product id is not present in database";
				return false;
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

		$tableName = Mage::getSingleton('core/resource')->getTableName('icecatimport/data');
		try {
			$connection->beginTransaction();
			$fileHandler = fopen($this->XMLfile, "r");
			if ($fileHandler) {
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
				$csvFile = Mage::getBaseDir('var') . $this->_connectorDir . 'ice_cat_temp.csv';
				$csvFileRes = fopen($csvFile, "w+");

				while (!feof($fileHandler)) {
					$row = fgets($fileHandler);
					$oneLine = explode("\t", $row);
					if ($oneLine[0]!= 'product_id' && $oneLine[0]!= ''){
						try{
							//	$connection->insert($tableName."_temp", array('prod_id' => $oneLine[1], 'prod_img' => $oneLine[6], 'prod_name' => $oneLine[12], 'supplier_id' => $oneLine[13]));
						
							$prod_id      = (!empty($oneLine[1]))  ? $oneLine[1]  : '';
							$prod_img     = (!empty($oneLine[6]))  ? $oneLine[6]  : '';
							$prod_name    = (!empty($oneLine[12])) ? $oneLine[12] : '';
							$supplier_id  = (!empty($oneLine[4])) ? $oneLine[4] : '';

							$line = "$prod_id\t$supplier_id\t$prod_name\t$prod_img\n";
            
							fwrite($csvFileRes, $line);

						}catch(Exception $e){
							Mage::log("connector issue: {$e->getMessage()}");
						}
					}
				}

				// write csv file into temp table
				$connection->query('LOAD DATA LOCAL INFILE "'.$csvFile.'" INTO TABLE '.$tableName.'_temp ( prod_id, supplier_id, prod_name, prod_img )');
				$connection->query("DROP TABLE IF EXISTS `".$tableName."_old`");
				$connection->query("rename table `".$tableName."` to `".$tableName."_old`, `".$tableName."_temp` to ".$tableName);
        
				$connection->commit();
				
				fclose($fileHandler);
				unlink($csvFile);
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
