<?php
/**************************************************************
 * 
 * @Company Name: Forumpay
 * @Author: Forumpay Team
 * @Date: September 2020
 * @Description: Used for process forumpay payment
 *  
 *************************************************************/
class Limitlex_ForumPay_Block_Standard_Redirect extends Mage_Core_Block_Abstract{
    protected function _toHtml(){
        $standard = Mage::getModel('forumpay/standard');
        $redirectUrl = $standard->setupSession();
        if($redirectUrl){
			Mage::app()->getFrontController()->getResponse()->setRedirect($redirectUrl);
        }
		else{
			Mage::getSingleton("core/session")->addError(__('Something went wrong, Please try agian later.'));
			$checkout_link = Mage::helper('checkout/url')->getCheckoutUrl();
            Mage::app()->getFrontController()->getResponse()->setRedirect($checkout_link);
		}
    }
}
