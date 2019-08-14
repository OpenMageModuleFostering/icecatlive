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
            if(!empty($icePhotos)){
                return Mage::getSingleton('Bintime_Icecatimport_Model_Imagescollection',array(
					'product' => $this->getProduct()
                ));
            }
            else{
                return $collection;
            }
    }



   public function getGalleryUrl($image=null)
    {
	$iceCatModel = Mage::getSingleton('icecatimport/import');
	$icePhotos = $iceCatModel->getGalleryPhotos();
    $collection = $this->getProduct()->getMediaGalleryImages();
    $items = $collection->getItems();
	if(!empty($icePhotos))
		return $image['file'];
	else
		return 	parent::getGalleryUrl($image);
    }


}
