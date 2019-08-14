<?php
/**
 * Class Provides product Attributes for BO menu
 *  @author Sergey Gozhedrianov <info@bintime.com>
 *
 */
class Bintime_Icecatimport_Model_System_Config_Attributes
{
    public function toOptionArray()
    {    
    	$attributesArray = Mage::getResourceModel('eav/entity_attribute_collection')
		->setAttributeSetFilter(Mage::getResourceSingleton('catalog/product')->getEntityType()->getDefaultAttributeSetId());
		$outputAttributeArray = array();
    	foreach($attributesArray as $attribute){
    		$outputAttributeArray[$attribute['attribute_code']]=$attribute['attribute_code'];
    	}
    	ksort($outputAttributeArray);
        return $outputAttributeArray;
    }
}
?>