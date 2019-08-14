<?php
/**
 * Class overrides base Product Model to provide products icecat data
 *  @author Sergey Gozhedrianov <info@bintime.com>
 *
 */
class Bintime_Icecatimport_Model_Catalog_Product extends Mage_Catalog_Model_Product 
{
	
  public function getName(){
      
   $productNamePriority = Mage::getStoreConfig('icecat_root/icecat/name_priority');
   if ($productNamePriority=='Db') {
     return parent::getName(); 
   }
   
   try {
      self::$_product_source = '';
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

      $tableName = Mage::getSingleton('core/resource')->getTableName('icecatimport/data').'_products';
      $selectCondition = $connection->select()
        ->from(array('connector' => $tableName), new Zend_Db_Expr('connector.prod_title'))
        ->where('connector.prod_id = ? ', $mpn);
      $icecatName = $connection->fetchOne($selectCondition);
	  
    } catch (Exception $e) {
      Mage::log('Icecat getName error'.$e);
    }

    $product_name = !empty($icecatName) ? str_replace("|"," ",$icecatName) : $this->getData('name');
    return $product_name; 
  }


  public function getImage(){
    if(!parent::getImage()||parent::getImage() == 'no_selection'){
      return "true";
    }else {
      return parent::getImage();
    } 
  }

  public function getShortDescription() {
    
    if( !isset(self::$_product_source) ) {
      $this->checkIcecatProdDescription();
    }
    
    $source = self::$_product_source;
    
    if ('Icecat' == Mage::getStoreConfig('icecat_root/icecat/descript_priority') and $source != 'DB') {
      return true;
    } else {
      return parent::getShortDescription();
    }
  } 

  public function getDescription() {

    if( !isset(self::$_product_source) ) {
       $this->checkIcecatProdDescription();
    }
    
    $source = self::$_product_source;
    
    
    if ('Icecat' == Mage::getStoreConfig('icecat_root/icecat/descript_priority') and  $source != 'DB' ) {
      return true;
    } else {
      return parent::getDescription();
    }
  }
  
  public function checkIcecatProdDescription($productId = '',$attributeName = '') {
       
      $iceImport = new  Bintime_Icecatimport_Model_Import();
      
      if (empty($productId)) {
        $productId = Mage::registry('current_product')->getId();   
      }
      
      //$productId = Mage::registry('current_product')->getId();
      $model = Mage::getModel('catalog/product');
      $_product = $model->load($productId);
      
      $mpn          = $_product->getData(Mage::getStoreConfig('icecat_root/icecat/sku_field'));
      $ean_code     = $_product->getData(Mage::getStoreConfig('icecat_root/icecat/ean_code')); 
      $manufacturer = $_product->getData(Mage::getStoreConfig('icecat_root/icecat/manufacturer'));
      $locale       = Mage::getStoreConfig('icecat_root/icecat/language');
      if ($locale == '0'){
        $systemLocale = explode("_", Mage::app()->getLocale()->getLocaleCode());
        $locale = $systemLocale[0];
      }
      $userLogin    = Mage::getStoreConfig('icecat_root/icecat/login');
      $userPass     = Mage::getStoreConfig('icecat_root/icecat/password');
      $entityId     = $_product->getEntityId();

      $descr[1] = false;
      
      $descr[0] = $iceImport->getProductDescription($mpn, $manufacturer, $locale, $userLogin, $userPass, $entityId, $ean_code);
     
      if (!empty($iceImport->simpleDoc)){
	  
        $productTag = $iceImport->simpleDoc->Product;
        $descr[1] = (string) $productTag->ProductDescription['ShortDesc'];   
      } else if(!empty($descr[0]) && $attributeName == 'short_description') {
        $descr[1] = $iceImport->getShortProductDescription();  
      }
      //$productTag = $iceImport->simpleDoc->Product;
     // $descr[1] = (string) $productTag->ProductDescription['ShortDesc'];     
      if($descr[0] == false and $descr[1] == false) {
        self::$_product_source = 'DB';
      } else if ($attributeName == 'short_description') {
        self::$_product_source = ''; 
        return $descr[1];
      } else if ($attributeName == 'name') {
	    self::$_product_source = ''; 
        return $iceImport->getProductName();
      } else {
	    self::$_product_source = ''; 
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
  
  /**
   * Source of product data
   *
   * @var string
   */
  public static $_product_source = '';

}
?>
