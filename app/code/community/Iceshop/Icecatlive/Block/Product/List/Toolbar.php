<?php

class Iceshop_Icecatlive_Block_Product_List_Toolbar extends Mage_Catalog_Block_Product_List_Toolbar
{
    public static $_productCollection = null;
    public static $_totalRecords = null;

    public function getCollection()
    {

        $ProductPriority = Mage::getStoreConfig('icecat_root/icecat/product_priority');
        $_productCollection = parent::getCollection();


        if (!$_productCollection->count() || $ProductPriority == 'Show') {
            return $_productCollection;
        } else {
            foreach ($_productCollection as $_product) {
                $icecat_prod = $this->CheckIcecatData($_product);
                if ($icecat_prod === false) {
                    $_productCollection->removeItemByKey($_product->getId());
                }
            }

            self::$_productCollection = $_productCollection;

            return $_productCollection;
        }
    }

    public function getTotalNum()
    {
        if (self::$_productCollection === null) {
            return parent::getTotalNum();
        }
        self::$_totalRecords = count(self::$_productCollection->getItems());
        return intval(self::$_totalRecords);
    }

    public function CheckIcecatData($_product)
    {
        $tablePrefix = '';
        $tPrefix = (array)Mage::getConfig()->getTablePrefix();
        if (!empty($tPrefix)) {
            $tablePrefix = $tPrefix[0];
        }
        $db_res = Mage::getSingleton('core/resource')->getConnection('core_write');
        $query = "SELECT `entity_id` FROM `" . $tablePrefix . "catalog_product_entity` LEFT JOIN `"
                . $tablePrefix . "iceshop_icecatlive_products_titles` ON entity_id = prod_id WHERE prod_id IS NOT NULL";
        $entity_id = $db_res->fetchAll($query);
        return in_array(array('entity_id' => $_product->getID()), $entity_id);

    }

}

?>
