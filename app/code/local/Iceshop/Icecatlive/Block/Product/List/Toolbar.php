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
        $connection = Mage::getSingleton('core/resource')->getConnection('core_read');
        $mpn = Mage::getModel('catalog/product')->load($_product->getId())->getData(Mage::getStoreConfig('icecat_root/icecat/sku_field'));
        $ean = Mage::getModel('catalog/product')->load($_product->getId())->getData(Mage::getStoreConfig('icecat_root/icecat/ean_code'));
        $tableName = Mage::getSingleton('core/resource')->getTableName('icecatlive/data_products');
        if(!empty($mpn)){
            $selectCondition = $connection->select()
                ->from(array('connector' => $tableName), new Zend_Db_Expr('connector.prod_title'))
                ->where('connector.prod_id = ? ', $mpn);
            $icecatName = $connection->fetchOne($selectCondition);
        }
        if(empty($icecatName) && !empty($ean)){
            $selectCondition = $connection->select()
                ->from(array('connector' => $tableName), new Zend_Db_Expr('connector.prod_title'))
                ->where('connector.prod_ean = ? ', $ean);
            $icecatName = $connection->fetchOne($selectCondition);
        }
        if (!empty($icecatName)) {
            return true;
        } else {
            $tableName = Mage::getSingleton('core/resource')->getTableName('icecatlive/data');
            $mappingTable = Mage::getSingleton('core/resource')->getTableName('icecatlive/supplier_mapping');
            $manufacturer = Mage::getModel('catalog/product')->load($_product->getId())->getData(Mage::getStoreConfig('icecat_root/icecat/manufacturer'));
            if (isset($manufacturer) && !empty($manufacturer)) {
                $selectCondition = $connection->select()
                    ->from(array('connector' => $tableName), new Zend_Db_Expr('connector.prod_img'))
                    ->joinInner(array('supplier' => $mappingTable), "connector.supplier_id = supplier.supplier_id AND supplier.supplier_symbol = {$connection->quote($manufacturer)}")
                    ->where('connector.prod_id = ?', $mpn);
                $imageURL = $connection->fetchOne($selectCondition);
            }
            if (empty($imageURL) && !empty($ean)) {
                $selectCondition = $connection->select()
                    ->from(array('connector' => $tableName), new Zend_Db_Expr('connector.prod_img'))
                    ->joinLeft(array('products' => $tableName . '_products'), "connector.prod_id = products.prod_id")
                    ->where('products.prod_ean = ?', trim($ean));
                $imageURL = $connection->fetchOne($selectCondition);
            }

            $icecat_prod = !empty($imageURL) ? true : false;
            return $icecat_prod;
        }
    }

}

?>
