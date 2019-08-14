<?php
class Iceshop_Icecatlive_Block_Adminhtml_System_Config_Form_Button extends Mage_Adminhtml_Block_System_Config_Form_Field
{
    /*
     * Set templated
     */
    protected function _construct()
    {
        parent::_construct();

        $this->setTemplate('iceshop/icecatlive/ajaxstatusimport.phtml');
    }

    /**
     * Return element html
     *
     * @param  Varien_Data_Form_Element_Abstract $element
     * @return string
     */
    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        return $this->_toHtml();
    }

    /**
     * Return ajax url for button
     *
     * @return string
     */
    public function getAjaxCheckUrl()
    {
        return Mage::helper('adminhtml')->getUrl('adminhtml/adminhtml_importdata/check');
    }

    /**
     * Generate button html
     *
     * @return string
     */
    public function getButtonHtml()
    {
        $userLogin = Mage::getStoreConfig('icecat_root/icecat/login');
        $userPass = Mage::getStoreConfig('icecat_root/icecat/password');
        if (!empty($userLogin) && !empty($userPass)) {
            $prod_button = $this->getLayout()->createBlock('adminhtml/widget_button')
                ->setData(array(
                    'id' => 'icecatlive_button',
                    'label' => $this->helper('adminhtml')->__('Import product information'),
                    'onclick' => 'javascript:import_prod_info(0, 1); return false;'
                ));
            $buttons = $prod_button->toHtml();
            return $buttons;
        } else {
            return 'Please type your login and pass from IceCat and press Save Config button. After that you can see Import button, for start import data from IceCat to you DB.';
        }
    }
}