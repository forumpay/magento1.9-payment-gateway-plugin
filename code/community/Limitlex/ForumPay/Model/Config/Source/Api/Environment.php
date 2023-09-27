<?php

class Limitlex_ForumPay_Model_Config_Source_Api_Environment
{
    public function toOptionArray()
    {
        return array(
            array('value'=>'https://api.forumpay.com/pay/v2/', 'label'=>'Production'),
            array('value'=>'https://sandbox.api.forumpay.com/pay/v2/', 'label'=>'Sandbox'),
        );
    }
}