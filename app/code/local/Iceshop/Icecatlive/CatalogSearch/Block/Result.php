<?php
class Iceshop_Icecatlive_CatalogSearch_Block_Result extends Mage_CatalogSearch_Block_Result
{
     /**
     * Retrieve search result count
     *
     * @return string
     */
    public function getResultCount()
    {

        $ProductPriority = Mage::getStoreConfig('icecat_root/icecat/product_priority');
        if ($ProductPriority == 'Show') {
            return parent::getResultCount();
        }

        $products = $this->_getProductCollection();
        $toolbar = new Iceshop_Icecatlive_Block_Product_List_Toolbar();
        foreach ($products as $_product) {
            $icecat_prod = $toolbar->CheckIcecatData($_product);
            if ($icecat_prod === false) {
                $products->removeItemByKey($_product->getId());
            }
        }
        $size = count($products->getItems());
        $this->_getQuery()->setNumResults($size);
        $this->setResultCount($size);
        return $this->getData('result_count');
    }

}

?>
