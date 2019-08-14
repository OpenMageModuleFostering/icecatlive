<?php
/**
 * Class provides data for Magento BO
 *  @author Sergey Gozhedrianov <info@bintime.com>
 *
 */
class Bintime_Icecatimport_Model_System_Config_Subscription
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
