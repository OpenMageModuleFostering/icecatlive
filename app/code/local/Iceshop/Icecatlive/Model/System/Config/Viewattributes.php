<?php
/**
 * Class Provides product Attributes for BO menu
 *
 */
class Iceshop_Icecatlive_Model_System_Config_Viewattributes
{
    public function toOptionArray()
    {
        $attributesArray = Mage::getResourceModel('eav/entity_attribute_collection')
            ->setAttributeSetFilter(Mage::getResourceSingleton('catalog/product')->getEntityType()->getDefaultAttributeSetId());
        $outputAttributeArray = array();
        $i = 1;
        $outputAttributeArray[0] = array('value' => '', 'label' => '');
        foreach ($attributesArray as $attribute) {
            $outputAttributeArray[$i] = array('value' => $attribute['attribute_code'], 'label' => $attribute['attribute_code']);
            $i++;
        }
        ksort($outputAttributeArray);
        return $outputAttributeArray;
    }
}

?><?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
?>
