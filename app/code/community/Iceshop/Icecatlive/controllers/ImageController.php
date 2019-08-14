<?php
/**
 * Class provides controller for import image debug
 *
 */
class Iceshop_Icecatlive_ImageController extends Mage_Adminhtml_Controller_Action
{

    /**
     * Action calls load method which uploads data to iceshop connector data table
     */
    public function viewAction()
    {
        $result = Mage::getModel('icecatlive/observer')->load();
    }

    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('system/config/icecat_root');
    }
}

?>