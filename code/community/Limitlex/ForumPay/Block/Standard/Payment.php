<?php
/**************************************************************
 * 
 * @Company Name: Forumpay
 * @Author: Forumpay Team
 * @Date: September 2020
 * @Description: Used for process forumpay payment
 *  
 *************************************************************/
class Limitlex_ForumPay_Block_Standard_Payment extends Mage_Core_Block_Template{
    public function _prepareLayout(){
        $title = 'Payment Processing';
        if($this->getLayout()->getBlock("head")){
            $this->getLayout()->getBlock("head")->setTitle($this->__($title));
        }
    }
    /**
    * Return Helper
    *
    */
    public function getHelper(){
        return Mage::helper('forumpay');
    }

    /**
    * Return Model
    *
    */
    public function getModel(){
        return Mage::getModel('forumpay/standard');
    }

    public function getPaymentLogo(){
        return $this->getHelper()->getPaymentMethodImage();
    }

    public function getParams(){
        $params = json_encode(Mage::app()->getRequest()->getParams());
        return $params;
    }

    public function getPaymentConfig(){
        $paymentConfig = Mage::helper('core')->jsonEncode($this->getModel()->getPaymentConfig());
        return $paymentConfig;
    }
}
