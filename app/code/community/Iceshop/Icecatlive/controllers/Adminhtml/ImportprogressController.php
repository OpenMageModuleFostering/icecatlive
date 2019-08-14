<?php
class Iceshop_Icecatlive_Adminhtml_ImportprogressController extends Mage_Adminhtml_Controller_Action
{
    /**
     * Return some checking result
     *
     * @return void
     */
    public function checkAction()
    {
        $importlogFile =  Mage::getBaseDir('var') . '/iceshop/icecatlive/import.log';
        $importlogContent = file_get_contents($importlogFile);
        $importlogArray = explode("\n", $importlogContent);
        array_pop($importlogArray);
        $importlogJson = json_encode($importlogArray);
        Mage::app()->getResponse()->setHeader('Content-type', 'application/json');
        Mage::app()->getResponse()->setBody($importlogJson);
    }

    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('system/config/icecat_root');
    }
}