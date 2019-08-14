<?php
/**
 * class overrides search getProductCollection function to provide products with needed attributes
 *  @author Sergey Gozhedrianov <info@bintime.com>
 *
 */
class Bintime_Icecatimport_Model_Catalog_Search extends Mage_CatalogSearch_Model_Mysql4_Fulltext_Collection
{
	public function _beforeLoad() 
	{
		$this->addAttributeToSelect(Mage::getStoreConfig('icecat_root/icecat/manufacturer'));
		return parent::_beforeLoad();
	}
}
?>