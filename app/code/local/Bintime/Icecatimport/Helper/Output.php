<?php
class Bintime_Icecatimport_Helper_Output extends Mage_Catalog_Helper_Output
{

  private $iceCatModel;
  private $error = false;
  private $systemError;

  /**
   * @var isFirstTime spike for getProductDescription that is called many times from template
   */
  private $isFirstTime = true;

  /**
   * Prepare product attribute html output
   *
   * @param   Mage_Catalog_Model_Product $product
   * @param   string $attributeHtml
   * @param   string $attributeName
   * @return  string
   */
  public function productAttribute($product, $attributeHtml, $attributeName){		
  
    $productId =  $product->getId();

    if (!mage::registry('product')) {
        Mage::register('product', $product);
        Mage::register('current_product', $product);
     // return parent::productAttribute($product, $attributeHtml, $attributeName);
    } 
	
	if ($attributeName == 'image') {
	  return parent::productAttribute($product, $attributeHtml, $attributeName);
	}

    $productDescriptionPriority = Mage::getStoreConfig('icecat_root/icecat/descript_priority');
    $dbDescriptionPriority = false;
    $current_page = Mage::app()->getFrontController()->getRequest()->getControllerName();
    if ($productDescriptionPriority == 'Db' &&
         ( $attributeName == 'description' || $attributeName == 'short_description')) {
       $dbDescriptionPriority = true;
    }
	
    if ($current_page == 'product') {
      $bin_prod = new Bintime_Icecatimport_Model_Catalog_Product(); 
	  if ($attributeName == 'description' || $attributeName == 'short_description') {
        $descr = $bin_prod->checkIcecatProdDescription($productId,$attributeName);
	  }
	}
    $prod_source = Bintime_Icecatimport_Model_Catalog_Product::$_product_source;
	  
    if( $prod_source == 'DB' && empty($descr) ) {
        $dbDescriptionPriority = true;
    }
     
    if ($dbDescriptionPriority || ($current_page != 'product' 
                                   && $prod_source != 'DB' && $attributeName != 'name') ) {
       $attributeHtml = $product->getData('short_description');
       return parent::productAttribute($product, $attributeHtml, $attributeName);     
    }
    $this->iceCatModel = Mage::getSingleton('icecatimport/import');
    if ($this->isFirstTime){ 
      $helper = Mage::helper('icecatimport/getdata');
      $helper->getProductDescription($product);

      if ($helper->hasError() && !$dbDescriptionPriority) {
        $this->error = true;
      }
      $this->isFirstTime = false;
    }

    if ($this->error){
      if ($attributeName != 'description' && $attributeName != 'short_description') {
        return parent::productAttribute($product, $attributeHtml, $attributeName); 
      } else {
        return '';
      }
      
    } 
    $id = $product->getData('entity_id');
    if ($attributeName == 'name'){ 
      //if we on product page then mage::registry('product') exist
      if ($product->getId() == $this->iceCatModel->entityId && $name = $this->iceCatModel->getProductName()) {
        return $name;
      } else if(!empty($descr)) {
	    return $descr;  
      }
      $manufacturerId = Mage::getStoreConfig('icecat_root/icecat/manufacturer');
      $mpn = Mage::getStoreConfig('icecat_root/icecat/sku_field');
      $collection = Mage::getResourceModel('catalog/product_collection');
      $collection->addAttributeToSelect($manufacturerId)->addAttributeToSelect($mpn)
      ->addAttributeToSelect('name')
      ->addAttributeToFilter('entity_id', array('eq' => $id));
      $product = $collection->getFirstItem() ;
      return $product->getName();
    }

    if ($attributeName == 'short_description') {
	  $icecat_descr = $this->iceCatModel->getShortProductDescription();
      if (!empty($descr)) {
        return $descr;
      } else if(!empty($icecat_descr)) {
        return $icecat_descr;
      } else {
        $attributeHtml = $product->getData('short_description');
        return parent::productAttribute($product, $attributeHtml, $attributeName);
      }
    }
    if ($attributeName == 'description') {
      $icecat_full_descr = $this->iceCatModel->getFullProductDescription();
      if (!empty($icecat_full_descr)) {
        return str_replace("\\n", "<br>",$icecat_full_descr);
      } else {
        $attributeHtml = $product->getData('description');
      }
    }
    return parent::productAttribute($product, $attributeHtml, $attributeName);
  } 


  public function getWarrantyInfo(){
    return $this->iceCatModel->getWarrantyInfo();
  }

  public function getShortSummaryDescription(){
    return $this->iceCatModel->getShortSummaryDescription();
  }

  public function getLongSummaryDescription(){
    return $this->iceCatModel->getLongSummaryDescription();
  }

  public function getManualPDF(){
    return $this->iceCatModel->getManualPDF();
  }

  public function getPDF(){
    return $this->iceCatModel->getPDF();
  }

  public function getIceCatMedia(){
    $media = (array)$this->iceCatModel->getIceCatMedia();
    return (array_key_exists('@attributes', $media)) ? $media['@attributes'] : array();
  }
}
?>
