<?php
/**
 * class overrides category getProductCollection function to provide products with needed attributes
 *
 */
class Iceshop_Icecatlive_Model_Catalog_Category extends Mage_Catalog_Model_Category
{
    /**
     * add product manufacturer attribute to category collection
     */
    public function getProductCollection()
    {
        $collection = parent::getProductCollection();

        $collection->addAttributeToSelect(Mage::getStoreConfig('icecat_root/icecat/manufacturer'));
        return $collection;
    }
}

?>