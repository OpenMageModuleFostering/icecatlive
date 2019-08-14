<?php

class Iceshop_Icecatlive_Model_System_Config_Checksystem
{
    public function toOptionArray()
    {
        return array(
            0 => base64_encode(Mage::getSingleton('adminhtml/url')->getUrl("adminhtml/icecatlive/system/"))
        );
    }
}