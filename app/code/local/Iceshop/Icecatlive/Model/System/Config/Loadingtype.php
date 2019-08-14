<?php
class Iceshop_Icecatlive_Model_System_Config_Loadingtype
{
    public function toOptionArray()
    {
        return array(
            '0' => 'From cache',
            '1' => "Realtime (If cache doesn't exist)"
        );

    }
}

?>
