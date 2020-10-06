<?php
/**************************************************************
 * 
 * @Company Name: Forumpay
 * @Author: Forumpay Team
 * @Date: September 2020
 * @Description: Used for process forumpay payment
 *  
 *************************************************************/

class Limitlex_ForumPay_IndexController extends Mage_Core_Controller_Front_Action
{
    private function _forumPayModel(){
        return Mage::getModel('forumpay/standard');
    }

    public function indexAction(){
		$this->loadLayout();     
		$this->renderLayout();
    }

    /*
    *
    * Render Payment Page
    *
    */

    public function paymentAction(){
        $this->loadLayout();     
        $this->renderLayout();
    }
    
    public function errorAction(){
		$this->loadLayout();     
		$this->renderLayout();
    }
    public function webhookAction(){
        $params = json_decode(file_get_contents('php://input'), true);
        if($params && is_array($params) && array_key_exists('payment_id', $params)){
            $params['invoice_no'] = $params['payment_id'];
            $response = $this->_forumPayModel()->checkPayment($params);
            echo 'success';
            die();
        }else{
            header("HTTP/1.0 404 Not Found");
            die();
        }
    }

    public function redirectAction(){
        $this->getResponse()->setBody($this->getLayout()->createBlock('forumpay/standard_redirect')->toHtml());
    }


    public function processpaymentAction(){
        $params = Mage::app()->getRequest()->getParams();
        $html = '';
        $generateHtml = false;
        $response = [];

        if($params && is_array($params)){
            if(array_key_exists('action', $params) && $params['action'] == 'forumpay_start_payment'){
                $template = 'forumpay-ajax-start-payment-window.phtml';
                $generateHtml = true;
                $response = $this->_forumPayModel()->startPayment($params); //return array
            }

            if(array_key_exists('action', $params) && $params['action'] == 'forumpay_check_payment'){
                $template = 'forumpay-ajax-check-payment-window.phtml';
                $generateHtml = true;
                $response = $this->_forumPayModel()->checkPayment($params); //return array
            }

            if(array_key_exists('action', $params) && $params['action'] == 'forumpay_get_rate'){
                $template = 'forumpay-ajax-get-rate-payment-window.phtml';
                $generateHtml = true;
                $response = $this->_forumPayModel()->getRate($params); //return array
            }
        }
        $data = [
            'payment_response' => $response,
            'action' => $params['action']
        ];
        $data = json_encode($data);

        if($generateHtml){
            $html = $this->getLayout()->createBlock('forumpay/standard_payment')
                ->setData('data',$data)
                ->setTemplate('forumpay/'.$template)
                ->toHtml();
        }

        $response = ['html' => $html,'status'=> true,'data'=> $data];
        return $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($response));
    }

    public function responseAction(){
        $params = Mage::app()->getRequest()->getParams();
        $model = $this->_forumPayModel();
        $verifyTransaction = $model->verifyTransaction($params);
        if($verifyTransaction){
            $this->_redirect('checkout/onepage/success', array('_secure'=>true));
        }else{
            Mage::getSingleton("core/session")->addError(__('We are not able to verify Transaction.'));
            $checkout_link = Mage::helper('checkout/url')->getCheckoutUrl();
            Mage::app()->getFrontController()->getResponse()->setRedirect($checkout_link);
        }
	}

	public function cancelAction(){
        $cancelMsg = __('Payment not authorized. Please try again later or choose a different payment method');
        $params = Mage::app()->getRequest()->getParams();
        if($params && is_array($params) && array_key_exists('action', $params) && $params['action'] == 'manual'){
            $response = $this->_forumPayModel()->cancelPayment($params);
            $response = $this->_forumPayModel()->checkPayment($params);
            if(isset($response->status) && strtolower($response->status) == 'cancelled' && isset($response->cancelled)){
                $cancelMsg = __('Payment request cancelled successfully.');
            }
        }
        Mage::getSingleton("core/session")->addError($cancelMsg);
        $checkout_link = Mage::helper('checkout/url')->getCheckoutUrl();
        Mage::app()->getFrontController()->getResponse()->setRedirect($checkout_link);
	}
}
