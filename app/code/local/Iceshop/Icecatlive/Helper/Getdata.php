<?php
/**
 *
 * Class achieves icecat description from Model by recieved SKU and manufacturer
 *
 */
class Iceshop_Icecatlive_Helper_Getdata extends Mage_Core_Helper_Abstract
{
    private $iceCatModel;
    private $error;
    private $systemError;

    /**
     * Gets product Data and delegates it to Model
     * @param Mage_Catalog_Model_Product $_product
     * @return Iceshop_Icecatlive_Helper_Getdata
     */

    public function getEntityTypeId()
    {
        $connection = Mage::getSingleton('core/resource')->getConnection('core_read');
        $query = $connection->select()
            ->from(Mage::getSingleton('core/resource')
                ->getTableName('eav_entity_type'), 'entity_type_id')
            ->where('entity_type_code = ?', 'catalog_product')
            ->limit(1);
        $this->entityTypeId = $connection->fetchOne($query);
        return $this->entityTypeId;
    }

    public function getProductDescription($_product)
    {
        try {
            $entityId = $_product->getEntityId();
            $entity_type_id = $this->getEntityTypeId();
            $connection = Mage::getSingleton('core/resource')->getConnection('core_read');
            $mpn = $_product->getData(Mage::getStoreConfig('icecat_root/icecat/sku_field'));
            $ean_code = $_product->getData(Mage::getStoreConfig('icecat_root/icecat/ean_code'));
            $manufacturerId = $_product->getData(Mage::getStoreConfig('icecat_root/icecat/manufacturer'));
            $attributeInfo = Mage::getResourceModel('eav/entity_attribute_collection')
                ->setCodeFilter(Mage::getStoreConfig('icecat_root/icecat/manufacturer'))
                ->setEntityTypeFilter($entity_type_id)
                ->getFirstItem();
            if ($attributeInfo->getData('backend_type') == 'int' ||
                ($attributeInfo->getData('frontend_input') == 'select' && $attributeInfo->getData('backend_type') == 'static')
            ) {
                $attribute = $attributeInfo->setEntity($_product->getResource());
                $manufacturer = $attribute->getSource()->getOptionText($manufacturerId);
            } else {
                $manufacturer = $manufacturerId;
            }

            $locale = Mage::getStoreConfig('icecat_root/icecat/language');
            if ($locale == '0') {
                $systemLocale = explode("_", Mage::app()->getLocale()->getLocaleCode());
                $locale = $systemLocale[0];
            }
            $userLogin = Mage::getStoreConfig('icecat_root/icecat/login');
            $userPass = Mage::getStoreConfig('icecat_root/icecat/password');
            $this->iceCatModel = Mage::getSingleton('icecatlive/import');
            if (!$this->iceCatModel->getProductDescription($mpn, $manufacturer, $locale, $userLogin, $userPass, $entityId, $ean_code)) {
                $this->error = $this->iceCatModel->getErrorMessage();
                $this->systemError = $this->iceCatModel->getSystemError();
                return $this;
            }
        } catch (Exception $e) {
            Mage::log('Icecat getProductDescription error' . $e);
        }
        return $this;
    }

    /**
     * returns true if error during data fetch occured else false
     */
    public function hasError()
    {
        if ($this->error || $this->systemError) {
            return true;
        }
        return false;
    }

    /**
     * return error message
     */
    public function getError()
    {
    }

    /**
     * return system error
     */
    public function hasSystemError()
    {
        if ($this->systemError) {
            return $this->systemError;
        }
        return false;
    }

    public function getProductDescriptionList()
    {
        return $this->iceCatModel->getProductDescriptionList();
    }

    public function getShortProductDescription()
    {
        return $this->iceCatModel->getShortProductDescription();
    }

    public function getLowPicUrl()
    {
        return $this->iceCatModel->getLowPicUrl();
    }

    public function getThumbPicture()
    {
        return $this->iceCatModel->getThumbPicture();
    }

    public function getGalleryPhotos()
    {
        return $this->iceCatModel->getGalleryPhotos();
    }

    public function getProductName()
    {
        return $this->iceCatModel->getProductName();
    }

    public function getVendor()
    {
        return $this->iceCatModel->getVendor();
    }

    public function getFullProductDescription()
    {
        return $this->iceCatModel->getFullProductDescription();
    }

    public function getMPN()
    {
        return $this->iceCatModel->getMPN();
    }

    public function getEAN()
    {
        return $this->iceCatModel->getEAN();
    }

    /**
     * Form related products list according to store products
     */
    public function getRelatedProducts()
    {
        if (!$this->iceCatModel) {
            $this->iceCatModel = Mage::getSingleton('icecatlive/import');
        }
        $relatedProducts = $this->iceCatModel->getRelatedProducts();
        if (empty($relatedProducts)) {
            return array();
        }
        $sku = Mage::getStoreConfig('icecat_root/icecat/sku_field');
        $collection = Mage::getModel('catalog/product')->getCollection();
        $filterArray = array();
        foreach ($relatedProducts as $mpn => $valueArray) {
            array_push($filterArray, array('attribute' => $sku, 'eq' => $mpn));
        }
        $collection->addFieldToFilter($filterArray);
        $collection->addAttributeToSelect($sku);
        $collection->addAttributeToSelect('category_ids');
        $relatedProductsList = array();
        foreach ($collection as $product) {
            $categoryIds = $product->getCategoryIds();
            if (!empty($categoryIds)) {
                if (is_array($categoryIds)) {
                    $catogoriesArray = $categoryIds;
                }
                if (is_string($categoryIds)) {
                    $catogoriesArray = explode(",", $product->getCategoryIds());
                }
                foreach ($catogoriesArray as $categoryId) {
                    if (!array_key_exists($product->getData($sku), $relatedProducts)) {
                        continue;
                    }
                    $relatedProductInfo = $relatedProducts[$product->getData($sku)];
                    $relatedProductInfo['mpn'] = $product->getData($sku);
                    $relatedProductInfo['url'] = preg_replace('/\/\d+\/$/', "/" . $categoryId . "/", $product->getProductUrl());;
                    if (!array_key_exists($categoryId, $relatedProductsList)) {
                        $relatedProductsList[$categoryId] = array();
                    }
                    array_push($relatedProductsList[$categoryId], $relatedProductInfo);
                }
            } else {
                if (!array_key_exists($product->getData($sku), $relatedProducts)) {
                    continue;
                }
                $relatedProductInfo = $relatedProducts[$product->getData($sku)];
                $relatedProductInfo['mpn'] = $product->getData($sku);
                $relatedProductInfo['url'] = preg_replace('/category\/\d+\/$/', '', $product->getProductUrl());;
                if (!array_key_exists('a', $relatedProductsList)) {
                    $relatedProductsList['a'] = array();
                }
                array_push($relatedProductsList['a'], $relatedProductInfo);
            }
        }
        return $relatedProductsList;
    }
}

?>
