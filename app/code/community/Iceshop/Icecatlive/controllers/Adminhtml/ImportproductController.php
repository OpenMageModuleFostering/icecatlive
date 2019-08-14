<?php

class Iceshop_Icecatlive_Adminhtml_ImportproductController extends Mage_Adminhtml_Controller_Action
{

    public function getGridTable()
    {
        return $this->getResponse()->setBody(
            $this->getLayout()->createBlock('icecatlive/adminhtml_product_list_grid')->toHtml()
        );
    }

    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('system/config/icecat_root');
    }
}