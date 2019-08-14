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
        

        if ($attributeName == 'image' && $imageFile == null ) {
            $imageFile = mage::getsingleton('icecatimport/import')->getLowPicUrl();
        }
        if ($attributeName == 'thumbnail' && $imageFile == null ) {
              //  $imageFile = $product->getData($attributeName);        
           $imageFile =  Mage::helper('icecatimport/image')->getImage($product);
        }
        
        if ($attributeName == 'small_image' && $imageFile == null) {
            $imageFile = Mage::helper('icecatimport/image')->getImage($product);
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
