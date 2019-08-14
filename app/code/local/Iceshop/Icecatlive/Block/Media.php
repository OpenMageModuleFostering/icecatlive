<?php

class Iceshop_Icecatlive_Block_Media extends Mage_Catalog_Block_Product_View_Media
{
    protected $_isGalleryDisabled;

    public function getGalleryImages()
    {
        if ($this->_isGalleryDisabled) {
            return array();
        }
        $iceCatModel = Mage::getSingleton('icecatlive/import');
        $icePhotos = $iceCatModel->getGalleryPhotos();
        $collection = $this->getProduct()->getMediaGalleryImages();
        $items = $collection->getItems();
        $Imagepriority = Mage::getStoreConfig('icecat_root/icecat/image_priority');
        if (!empty($icePhotos) && $Imagepriority != 'Db') {
            return Mage::getSingleton('Iceshop_Icecatlive_Model_Imagescollection', array(
                'product' => $this->getProduct()
            ));
        } else if (count($items) == 1) {
            return array();
        } else {
            return $collection;
        }
    }


    public function getGalleryUrl($image = null)
    {
        $iceCatModel = Mage::getSingleton('icecatlive/import');
        $icePhotos = $iceCatModel->getGalleryPhotos();
        $Imagepriority = Mage::getStoreConfig('icecat_root/icecat/image_priority');

        if (!empty($icePhotos) && $Imagepriority != 'Db') {
            return Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA) . 'catalog/product/' . $image['file'];
        } else {
            return parent::getGalleryUrl($image);
        }
    }
}