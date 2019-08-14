<?php
/**
 * Overloaded catalog helper to substitute magento images
 *  @author Sergey Gozhedrianov <info@bintime.com>
 *
 */
class Bintime_Icecatimport_Helper_Catalog_Image extends Mage_Catalog_Helper_Image
{

    /**
     * Overriden method provides product with images from icecatimport data table
     * @param $product Mage_Catalog_Model_Product
     * @param $attributeName string
     * @param $imageFile string
     */
    public function init(Mage_Catalog_Model_Product $product, $attributeName, $imageFile=null)
    {   
        $current_page = Mage::app()->getFrontController()->getRequest()->getControllerName();
        $url = Mage::helper('core/url')->getCurrentUrl();
        $is_gallery = (int) strpos($url,'catalog/product/gallery');
        $Imagepriority = Mage::getStoreConfig('icecat_root/icecat/image_priority');
        if ($Imagepriority == 'Db' || (($attributeName == 'thumbnail' && !empty($imageFile)
                                          && $current_page == 'product' ) || ($is_gallery > 0)) ) {
          return parent::init($product, $attributeName, $imageFile);
        }
        
        if ($attributeName == 'image' && $imageFile == null ) {
          $imageFile = mage::getsingleton('icecatimport/import')->getLowPicUrl();
        } else if ($attributeName == 'thumbnail' && $imageFile == null ) {
          $imageFile =  Mage::helper('icecatimport/image')->getImage($product);
        } else if ($attributeName == 'small_image' && $imageFile == null) {
          $imageFile = Mage::helper('icecatimport/image')->getImage($product);
          if (!$imageFile) {
            $iceImport = new  Bintime_Icecatimport_Model_Import();
            $imageFile = $iceImport->getImg($product->getId());
          }
        } else if (!empty($imageFile)) {
          $iceCatModel = Mage::getSingleton('icecatimport/import');
          if (!strpos($imageFile,'ttp')) {
            $imageFile = $iceCatModel->saveImg($product->getEntityId(),Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA).'catalog/product/'.$imageFile,$attributeName); 
          } else {
            $imageFile = $iceCatModel->saveImg($product->getEntityId(),$imageFile,$attributeName); 
          } 
        }

        return parent::init($product, $attributeName, $imageFile);
    }

    /**
     * Return icecat image URL if set
     */
     
    public function __toString()
    {   
        $url = parent::__toString();
		
        if ( $this->getImageFile() && strpos( $this->getImageFile(), 'icecat.biz') && strpos($url, 'placeholder') ) {
            $url = $this->getImageFile();
        }
        
        return $url;
    }
    
}
?>
