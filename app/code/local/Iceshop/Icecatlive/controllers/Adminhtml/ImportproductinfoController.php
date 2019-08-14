<?php
class Iceshop_Icecatlive_Adminhtml_ImportproductinfoController extends Mage_Adminhtml_Controller_Action
{
    /**
     * Return some checking result
     *
     * @return void
     */
    public function checkAction()
    {

        $result = Mage::getModel('icecatlive/observer')->loadProductInfoIntoCache();

        Mage::app()->getResponse()->setBody($result);
    }
}