<?php
/**
 * Class Provides locales list for Magento BO
 *  @author Sergey Gozhedrianov <info@bintime.com>
 *
 */
class Bintime_Icecatimport_Model_System_Config_Locales
{
	private $domDoc;
    public function toOptionArray()
    {
    	$pathToFile = Mage::getRoot().'/code/local/Bintime/Icecatimport/Model/System/Config/';
    	$fileContent = file_get_contents($pathToFile.'LanguageList.xml');
	if(!$this->parseXml(utf8_encode($fileContent))){
				return false;
		}
		
		$values = $this->parseLocaleValues();
        return $values;
    }
    
    private function parseLocaleValues(){
    	$languageArray = $this->domDoc->getElementsByTagName('Language');
    	$resultArray = array();
    	foreach($languageArray as $language){
    		$languageShortCode = strtolower($language->getAttribute('ShortCode'));
    		$languageCode = ucfirst($language->getAttribute('Code'));
    		$resultArray[$languageShortCode]=$languageCode;
    	}
    	ksort($resultArray);
    	array_unshift($resultArray, 'Use Store Locale');
    	return $resultArray;
    }
    
	private function parseXml($stringXml){
		$this->domDoc = new DOMDocument();
		$result = $this->domDoc->loadXML($stringXml);
		if (!$this->domDoc->validate()){
			echo "Document is not Valid<br>";
			return false;
		}
		return true;
	}
}
?>
