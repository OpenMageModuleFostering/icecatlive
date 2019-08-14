<?php
/**
 * Class provides data for Magento BO
 *
 */
class Iceshop_Icecatlive_Model_System_Config_Subscription
{
    public function toOptionArray()
    {
        $paramsArray = array(
            'free' => 'OpenIcecat XML',
            'full' => 'FullIcecat XML'
        );
        return $paramsArray;
    }
}

?>
