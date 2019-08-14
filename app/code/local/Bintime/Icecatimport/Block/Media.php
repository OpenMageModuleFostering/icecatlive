<?php

class Bintime_Icecatimport_Block_Media extends Mage_Catalog_Block_Product_View_Media
{
    protected $_isGalleryDisabled;

    public function getGalleryImages()
    {   
        if ($this->_isGalleryDisabled) {
            return array();
        }
        $iceCatModel = Mage::getSingleton('icecatimport/import');
        $icePhotos = $iceCatModel->getGalleryPhotos();

            $collection = $this->getProduct()->getMediaGalleryImages();
            $items = $collection->getItems();
            $Imagepriority = Mage::getStoreConfig('icecat_root/icecat/image_priority');
            if (!empty($icePhotos) && $Imagepriority != 'Db'){
              return Mage::getSingleton('Bintime_Icecatimport_Model_Imagescollection',array(
                     'product' => $this->getProduct()
              ));
            } else if (count($items) == 1) {
              return array();
            } else{
              return $collection;
            }
    }



   public function getGalleryUrl($image=null)
    {
      $iceCatModel = Mage::getSingleton('icecatimport/import');
      $icePhotos = $iceCatModel->getGalleryPhotos();
      $collection = $this->getProduct()->getMediaGalleryImages();
      $items = $collection->getItems();
      $Imagepriority = Mage::getStoreConfig('icecat_root/icecat/image_priority');

      if (!empty($icePhotos) && $Imagepriority != 'Db') {
        $product_id = $this->getProduct()->getEntityId();
        $image_saved = $iceCatModel->saveImg($product_id,$image['file'],'thumb');  
        return Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA).'catalog/product/'.$image_saved;
      } else {
        return parent::getGalleryUrl($image);
      }
    }


}
