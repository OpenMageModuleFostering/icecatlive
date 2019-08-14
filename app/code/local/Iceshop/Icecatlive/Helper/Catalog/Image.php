<?php
/**
 * Overloaded catalog helper to substitute magento images
 *
 */
class Iceshop_Icecatlive_Helper_Catalog_Image extends Mage_Catalog_Helper_Image
{

    /**
     * Overriden method provides product with images from icecatlive data table
     * @param $product Mage_Catalog_Model_Product
     * @param $attributeName string
     * @param $imageFile string
     */
    public function init(Mage_Catalog_Model_Product $product, $attributeName, $imageFile = null)
    {

        $current_page = Mage::app()->getFrontController()->getRequest()->getControllerName();
        $url = Mage::helper('core/url')->getCurrentUrl();
        $is_gallery = (int)strpos($url, 'catalog/product/gallery');
        $Imagepriority = Mage::getStoreConfig('icecat_root/icecat/image_priority');
        if ($Imagepriority == 'Db' || (($attributeName == 'thumbnail' && !empty($imageFile)
                    && $current_page == 'product') || ($is_gallery > 0))
        ) {
            return parent::init($product, $attributeName, $imageFile);
        }

        if ($attributeName == 'image' && $imageFile == null) {
            $imageFile = mage::getsingleton('icecatlive/import')->getLowPicUrl();

        } else if ($attributeName == 'thumbnail' && $imageFile == null) {
            $imageFile = Mage::helper('icecatlive/image')->getImage($product);

        } else if ($attributeName == 'small_image' && $imageFile == null) {
            $imageFile = Mage::helper('icecatlive/image')->getImage($product);
        }

        return parent::init($product, $attributeName, $imageFile);
    }

    /**
     * Return icecat image URL if set
     */

    public function __toString()
    {
        $url = parent::__toString();

        if ($this->getImageFile() && strpos($this->getImageFile(), 'icecat.biz') && strpos($url, 'placeholder')) {
            $url = $this->getImageFile();
        }

        return $url;
    }

}

?>
