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
     
    public function productAttribute($product, $attributeHtml, $attributeName)
    {		
		if (!mage::registry('product')) {
            return parent::productAttribute($product, $attributeHtml, $attributeName);
        }

	$this->iceCatModel = Mage::getSingleton('icecatimport/import');
	if($this->isFirstTime){ 
		$helper = Mage::helper('icecatimport/getdata');
		$helper->getProductDescription($product);
		
		if($helper->hasError())
		{
			$this->error = true;
		}
		$this->isFirstTime = false;
        
	}

    if($this->error)
    {
        return parent::productAttribute($product, $attributeHtml, $attributeName); 
    } 
	$id = $product->getData('entity_id');

    
       if($attributeName == 'name')
       { 

        //if we on product page then mage::registry('product' exist
        if ($product->getId() == $this->iceCatModel->entityId && $name = $product->getData('brand_name') . ' ' . $this->iceCatModel->getProductName()) {
            return $name;
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
	    
	   if($attributeName == 'short_description')
	   {
		   return $this->iceCatModel->getShortProductDescription();
	   }
	   if($attributeName == 'description')
	   {
		   return str_replace("\\n", "<br>",$this->iceCatModel->getFullProductDescription());
	   }
	   	     else return parent::productAttribute($product, $attributeHtml, $attributeName);
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
