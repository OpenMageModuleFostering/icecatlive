<?php
/**
 * Class overrides base Product Model to provide products icecat data
 *  @author Sergey Gozhedrianov <info@bintime.com>
 *
 */
class Bintime_Icecatimport_Model_Catalog_Product extends Mage_Catalog_Model_Product 
{
	
  public function getName(){ 

    try {
      $connection = Mage::getSingleton('core/resource')->getConnection('core_read');
      $manufacturerId = $this->getData(Mage::getStoreConfig('icecat_root/icecat/manufacturer'));
      $mpn = $this->getData(Mage::getStoreConfig('icecat_root/icecat/sku_field'));
      $attributeInfo = Mage::getResourceModel('eav/entity_attribute_collection')
        ->setCodeFilter(Mage::getStoreConfig('icecat_root/icecat/manufacturer'))
        ->setEntityTypeFilter($this->getResource()->getTypeId())
        ->getFirstItem();              
      switch ($attributeInfo->getData('backend_type')) {
        case 'int':
          $attribute = $attributeInfo->setEntity($this->getResource());
          $manufacturer = $attribute->getSource()->getOptionText($manufacturerId);
        break;
        default:
          $manufacturer = $manufacturerId;
        break;
      }

      $tableName = Mage::getSingleton('core/resource')->getTableName('icecatimport/data');
      $mappingTable = Mage::getSingleton('core/resource')->getTableName('icecatimport/supplier_mapping');
      $selectCondition = $connection->select()
        ->from(array('connector' => $tableName), new Zend_Db_Expr('connector.prod_name'))
        ->joinInner(array('supplier' => $mappingTable), "connector.supplier_id = supplier.supplier_id AND supplier.supplier_symbol = {$connection->quote($manufacturer)}")
        ->where('connector.prod_id = ? ', $mpn);
      $icecatName = $connection->fetchOne($selectCondition);
    } catch (Exception $e) {
      Mage::log('Icecat getName error'.$e);
    }

    $product_name = $icecatName ? $icecatName : $this->getData('name');
    return $this->getData('brand_name') . ' ' . $product_name; 
  }


  public function getImage(){
    if(!parent::getImage()||parent::getImage() == 'no_selection'){
      return "true";
    }else {
      return parent::getImage();
    } 
  }

  public function getShortDescription() {
    if ('Icecat' == Mage::getStoreConfig('icecat_root/icecat/descript_priority')) {
      return true;
    } else {
      return parent::getShortDescription();
    }
  } 

  public function getDescription() {
    if ('Icecat' == Mage::getStoreConfig('icecat_root/icecat/descript_priority')) {
      return true;
    } else {
      return parent::getDescription();
    }
  } 


  /**
   * Entity code.
   * Can be used as part of method name for entity processing
   */
  const ENTITY                 = 'catalog_product';

  const CACHE_TAG              = 'catalog_product';
  protected $_cacheTag         = 'catalog_product';
  protected $_eventPrefix      = 'catalog_product';
  protected $_eventObject      = 'product';
  protected $_canAffectOptions = false;

  /**
   * Product type instance
   *
   * @var Mage_Catalog_Model_Product_Type_Abstract
   */
  protected $_typeInstance            = null;

  /**
   * Product type instance as singleton
   */
  protected $_typeInstanceSingleton   = null;

  /**
   * Product link instance
   *
   * @var Mage_Catalog_Model_Product_Link
   */
  protected $_linkInstance;

  /**
   * Product object customization (not stored in DB)
   *
   * @var array
   */
  protected $_customOptions = array();

  /**
   * Product Url Instance
   *
   * @var Mage_Catalog_Model_Product_Url
   */
  protected $_urlModel = null;
  protected static $_url;
  protected static $_urlRewrite;
  protected $_errors = array();
  protected $_optionInstance;
  protected $_options = array();

  /**
   * Product reserved attribute codes
   */
  protected $_reservedAttributes;

  /**
   * Flag for available duplicate function
   *
   * @var boolean
   */
  protected $_isDuplicable = true;

}
?>
