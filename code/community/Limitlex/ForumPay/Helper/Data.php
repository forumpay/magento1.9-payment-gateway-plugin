<?php
/**************************************************************
 * 
 * @Company Name: Forumpay
 * @Author: Forumpay Team
 * @Date: September 2020
 * @Description: Used for process forumpay payment
 *  
 *************************************************************/
class Limitlex_ForumPay_Helper_Data extends Mage_Core_Helper_Abstract
{
    const API_REQUEST_URL = 'https://forumpay.com/api/v2/'; // Trailing Slash(/) is required

	public function getConfig($field=null){
		if($field==null || !$field){
			return false;
		}
		return Mage::getStoreConfig('payment/forumpay/'.$field, Mage::app()->getStore()->getStoreId());
	}

	public function getApiRequestEndpoint(){
		return self::API_REQUEST_URL;
	}

	public function getPaymentMethodImage(){
		$logoImage = $this->getConfig('logo');
		if($logoImage){
			$mediaUrl = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA);
			return $mediaUrl.'forumpay/'.$logoImage;
		}
		return false;
	}

	public function getShopId(){
        $shopId = $this->getConfig('shop_id');
        if($shopId){
            $shopId = str_replace(' ', '-', $shopId);
            $shopId = preg_replace('/[^A-Za-z0-9\-]/', '', $shopId);
        }
        if(!$shopId){
            $shopId = 'magento-1';
        }
        return $shopId;
    }

    public function getApiSecret(){
        $api_key = $this->getConfig('api_key');
        return $api_key;
    }
    
    public function getApiMerchant(){
        $api_user = $this->getConfig('api_user');
        return $api_user;
    }

    public function getOrderStatusAfterPayment(){
        return $this->getConfig('order_status_after_payment');
    }

    public function getBasicAuthorization(){
        return $this->generateBasicAuthorization();
    }

    private function generateBasicAuthorization(){
        $user = $this->getApiMerchant();
        $pass = $this->getApiSecret();
        $token = $user.':'.$pass;
        $hash = base64_encode($token);
        return "Basic ".$hash;
    }

}