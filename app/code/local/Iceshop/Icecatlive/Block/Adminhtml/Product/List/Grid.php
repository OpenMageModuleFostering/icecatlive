<?php

class Iceshop_Icecatlive_Block_Adminhtml_Product_List_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
    public function __construct()
    {
        parent::__construct();
        $this->setId('icecatliveGrid');
        $this->setSaveParametersInSession(true);
        $this->setUseAjax(true);
        $this->setVarNameFilter('icecatlive_filter');
        $this->_prepareCollection;
    }

    protected function _getStore()
    {
        $storeId = (int) $this->getRequest()->getParam('store', 0);
        return Mage::app()->getStore($storeId);
    }

    protected function _prepareCollection()
    {
        $store = $this->_getStore();
        $collection = Mage::getModel('catalog/product')->getCollection()->addAttributeToSort('entity_id', 'DESC');

        $collection->getSelect()->joinLeft( array('pt'=>Mage::getSingleton('core/resource')->getTableName('icecatlive/products_titles')),
                'entity_id = pt.prod_id', array('*'))->where("pt.prod_id IS NULL");

        $mpn = Mage::getStoreConfig('icecat_root/icecat/sku_field');
        $ean_code = Mage::getStoreConfig('icecat_root/icecat/ean_code');

        if($this->checkExistingAttribute('catalog_product', 'sku')){
            $collection->addAttributeToSelect('sku');
        }
        if($this->checkExistingAttribute('catalog_product', 'name')){
            $collection->addAttributeToSelect('name');
        }
        if($this->checkExistingAttribute('catalog_product', 'attribute_set_id')){
            $collection->addAttributeToSelect('attribute_set_id');
        }
        if($this->checkExistingAttribute('catalog_product', 'type_id')){
            $collection->addAttributeToSelect('type_id');
        }

        if ($store->getId()) {
            $adminStore = Mage_Core_Model_App::ADMIN_STORE_ID;
            $collection->addStoreFilter($store);
            if($this->checkExistingAttribute('catalog_product', 'name')){
                $collection->joinAttribute(
                    'name',
                    'catalog_product/name',
                    'entity_id',
                    null,
                    'inner',
                    $adminStore
                );
            }
            if($this->checkExistingAttribute('catalog_product', $mpn)){
                $collection->joinAttribute(
                    $mpn,
                    "catalog_product/{$mpn}",
                    'entity_id',
                    null,
                    'inner',
                    $adminStore
                );
            }

            if($this->checkExistingAttribute('catalog_product', $ean_code)){
                $collection->joinAttribute(
                    $ean_code,
                    "catalog_product/{$ean_code}",
                    'entity_id',
                    null,
                    'inner',
                    $adminStore
                );
            }

            $collection->joinAttribute(
                'custom_name',
                'catalog_product/name',
                'entity_id',
                null,
                'inner',
                $store->getId()
            );
        }
        else {
          if($this->checkExistingAttribute('catalog_product', 'price')){
              $collection->addAttributeToSelect('price');
          }

          if($this->checkExistingAttribute('catalog_product', $ean_code)){
              $collection->joinAttribute($ean_code, "catalog_product/{$ean_code}", 'entity_id', null, 'inner');
          }
          if($this->checkExistingAttribute('catalog_product', $mpn)){
              $collection->joinAttribute($mpn, "catalog_product/{$mpn}", 'entity_id', null, 'inner');
          }
          if($this->checkExistingAttribute('catalog_product', 'status')){
              $collection->joinAttribute('status', 'catalog_product/status', 'entity_id', null, 'inner');
          }
          if($this->checkExistingAttribute('catalog_product', 'status')){
              $collection->joinAttribute('visibility', 'catalog_product/visibility', 'entity_id', null, 'inner');
          }
        }

        $this->setCollection($collection);

        parent::_prepareCollection();
        $this->getCollection()->addWebsiteNamesToResult();
        return $this;
    }

    public function checkExistingAttribute($group, $attribut){
        $eav       = Mage::getModel('eav/config');
        $attribute = $eav->getAttribute($group, $attribut);
        return $attribute->getId();
    }


    protected function _prepareColumns()
    {

      $mpn = Mage::getStoreConfig('icecat_root/icecat/sku_field');
      $ean_code = Mage::getStoreConfig('icecat_root/icecat/ean_code');

       $this->addColumn('entity_id',
            array(
                'header'=> Mage::helper('catalog')->__('ID'),
                'width' => '50px',
                'type'  => 'number',
                'index' => 'entity_id',
        ));
       if($this->checkExistingAttribute('catalog_product', 'name')){
           $this->addColumn('name',
                array(
                    'header'=> Mage::helper('catalog')->__('Name'),
                    'index' => 'name',
            ));

       }

        $store = $this->_getStore();
        if ($store->getId()) {
            $this->addColumn('custom_name',
                array(
                    'header'=> Mage::helper('catalog')->__('Name in %s', $store->getName()),
                    'index' => 'custom_name',
            ));
        }

        if($this->checkExistingAttribute('catalog_product', 'sku')){
            $this->addColumn('sku',
                array(
                    'header'=> Mage::helper('catalog')->__('SKU'),
                    'width' => '80px',
                    'index' => 'sku',
            ));
        }

        if($this->checkExistingAttribute('catalog_product', $mpn)){
            $this->addColumn($mpn,
              array(
                  'header'=> Mage::helper('catalog')->__('MPN'),
                  'width' => '80px',
                  'index' => $mpn,
          ));
        }

        if($this->checkExistingAttribute('catalog_product', $ean_code)){
            $this->addColumn($ean_code,
                array(
                    'header'=> Mage::helper('catalog')->__('EAN'),
                    'width' => '80px',
                    'index' => $ean_code,
            ));
        }


        $this->addExportType('*/*/exportIcecatliveCsv', Mage::helper('icecatlive')->__('CSV'));
        $this->addExportType('*/*/exportIcecatliveExcel', Mage::helper('icecatlive')->__('Excel XML'));

        return parent::_prepareColumns();
    }

    public function getGridUrl()
    {
        return $this->getUrl('*/*/grid', array('_current'=>true));
    }

    public function getRowUrl($row)
    {
        return $this->getUrl('*/catalog_product/edit', array(
            'store'=>$this->getRequest()->getParam('store'),
            'id'=>$row->getId())
        );
    }
}