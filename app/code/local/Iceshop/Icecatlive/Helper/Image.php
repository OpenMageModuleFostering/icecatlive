<?php
/**
 * Class retrieves images from icecatlive data table
 *
 */
class Iceshop_Icecatlive_Helper_Image extends Mage_Core_Helper_Abstract
{
    /**
     * Fetch Image URL from DB
     * @param Mage_Catalog_Model_Product $_product
     * @return string image URL
     */
    public function getImage($_product)
    {

        $_product = $_product->load($_product->getId());
        $sku = $_product->getData(Mage::getStoreConfig('icecat_root/icecat/sku_field'));
        $manufacturerId = $_product->getData(Mage::getStoreConfig('icecat_root/icecat/manufacturer'));

        $connection = Mage::getSingleton('core/resource')->getConnection('core_read');
        $query = $connection->select()
            ->from(Mage::getSingleton('core/resource')->getTableName('eav_entity_type'), 'entity_type_id')
            ->where('entity_type_code = ?', 'catalog_product')
            ->limit(1);
        $entityTypeId = $connection->fetchOne($query);
        $attributeInfo = Mage::getResourceModel('eav/entity_attribute_collection')
            ->setCodeFilter(Mage::getStoreConfig('icecat_root/icecat/manufacturer'))
            ->setEntityTypeFilter($_product->getResource()->getTypeId())
            ->getFirstItem();
        switch ($attributeInfo->getData('backend_type')) {
            case 'int':
                $attribute = $attributeInfo->setEntity($_product->getResource());
                $manufacturer = $attribute->getSource()->getOptionText($manufacturerId);
                break;
            default:
                $manufacturer = $manufacturerId;
                break;
        }
        $this->observer = Mage::getSingleton('icecatlive/observer');

        $url = $this->observer->getImageURL($sku, $manufacturer, $_product->getId());
        return $url;
    }

    public function getGallery()
    {
        return Mage::getSingleton('icecatlive/import')->getGalleryPhotos();
    }
}
