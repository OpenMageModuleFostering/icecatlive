<?php
class Iceshop_Icecatlive_Adminhtml_ImportdataController extends Mage_Adminhtml_Controller_Action
{
    /**
     * Return some checking result
     *
     * @return void
     */
    public function checkAction()
    {
        session_write_close();
        $result = Mage::getModel('icecatlive/observer')->load();
        sleep(6);
        Mage::app()->getResponse()->setBody($result);
    }

    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('system/config/icecat_root');
    }
}