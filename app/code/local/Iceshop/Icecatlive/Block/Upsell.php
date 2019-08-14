<?php
class Iceshop_Icecatlive_Block_Upsell extends Mage_Catalog_Block_Product_List_Upsell
{

    protected function _prepareData()
    {
        $product = Mage::registry('product');
        /* @var $product Mage_Catalog_Model_Product */
        $this->_itemCollection = $product->getUpSellProductCollection()
            ->addAttributeToSort('position', 'asc')
            ->addStoreFilter();
        $skuField = Mage::getStoreConfig('icecat_root/icecat/sku_field');
        $this->_itemCollection->addAttributeToSelect($skuField);

        $manufacturerId = Mage::getStoreConfig('icecat_root/icecat/manufacturer');
        $this->_itemCollection->addAttributeToSelect($manufacturerId);

        Mage::getResourceSingleton('checkout/cart')->addExcludeProductFilter($this->_itemCollection,
            Mage::getSingleton('checkout/session')->getQuoteId()
        );


        $this->_addProductAttributesAndPrices($this->_itemCollection);

//        Mage::getSingleton('catalog/product_status')->addSaleableFilterToCollection($this->_itemCollection);
        Mage::getSingleton('catalog/product_visibility')->addVisibleInCatalogFilterToCollection($this->_itemCollection);

        if ($this->getItemLimit('upsell') > 0) {
            $this->_itemCollection->setPageSize($this->getItemLimit('upsell'));
        }

        $this->_itemCollection->load();
        /**
         * Updating collection with desired items
         */
        Mage::dispatchEvent('catalog_product_upsell', array(
            'product' => $product,
            'collection' => $this->_itemCollection,
            'limit' => $this->getItemLimit()
        ));

        foreach ($this->_itemCollection as $product) {
            $product->setDoNotUseCategoryId(true);
        }

        return $this;
    }


}
