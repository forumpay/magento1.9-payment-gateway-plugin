<?php
/**************************************************************
 * 
 * @Company Name: Forumpay
 * @Author: Forumpay Team
 * @Date: September 2020
 * @Description: Used for process forumpay payment
 *  
 *************************************************************/

class Limitlex_ForumPay_Model_Standard extends Mage_Payment_Model_Method_Abstract{
  protected $_code = 'forumpay';
  protected $_formBlockType = 'forumpay/form_forumpay';
  protected $_infoBlockType = 'forumpay/info_forumpay';

  const ACTION_ORDER = Mage_Sales_Model_Order_Payment_Transaction::TYPE_ORDER;
  const ACTION_AUTHORIZE = Mage_Sales_Model_Order_Payment_Transaction::TYPE_AUTH;
  const ACTION_CAPTURE = Mage_Sales_Model_Order_Payment_Transaction::TYPE_CAPTURE;
  const ACTION_VOID = Mage_Sales_Model_Order_Payment_Transaction::TYPE_VOID;
  const TRANSACTION_ADDITIONAL_INFO_KEY = Mage_Sales_Model_Order_Payment_Transaction::RAW_DETAILS;
	
  public function getPaymentConfig(){
    return [
      'redirect_url' => $this->getOrderPlaceRedirectUrl(),
      'success_url' => $this->getSuccessUrl(),
      'cancel_url' => $this->getCancelUrl(),
      'webhook_url' => $this->getWebhookUrl(),
      'process_payment_url' => $this->getProcessPaymentUrl(),
    ];
  }

  /**
	* Return Order place redirect url
	*
	* @return string
	*/

  public function getOrderPlaceRedirectUrl(){
      return Mage::getUrl('forumpay/index/redirect', array('_secure' => true));
  }

  /**
  * Return Order Success url
  * @return string
  */

  public function getSuccessUrl(){
    return Mage::getUrl('forumpay/index/response', array('_secure' => true));
  }

  /**
  * Return Order cancel url
  * @return string
  */
  public function getCancelUrl(){
    return Mage::getUrl('forumpay/index/cancel', array('_secure' => true));
  }

  /**
  * Return Order webhook url
  * @return string
  */
  public function getWebhookUrl(){
    return Mage::getUrl('forumpay/index/webhook', array('_secure' => true));
  }

  /**
  * Return Order Process Payment Url
  * @return string
  */
  public function getProcessPaymentUrl(){
    return Mage::getUrl('forumpay/index/processpayment', array('_secure' => true));
  }

  /**
  * Return Checkout Session array
  *
  * @return array
  */
  public function getCheckoutSession(){
    return Mage::getSingleton('checkout/session');
  }

  /**
  * Return Checkout Session array
  *
  * @return array
  */
  public function getSession(){
    return Mage::getSingleton('core/session');
  }

	/**
	* Return form field array
	*
	* @return array
	*/
	public function getHelper(){
		return Mage::helper('forumpay');
	}

   /**
   * Return url according to environment
   * @return string
   */

   public function apiEndPoint(){
      return $this->getHelper()->getApiRequestEndpoint();
  }

  /**
   * Return base64 Encoded apiKey 
   * @return string
   */
  public function getApiSecretKey(){
    return $this->getHelper()->getApiSecret();
  }
  public function getApiMerchant(){
    return $this->getHelper()->getApiMerchant();
  }

  /**
   * Return base64 Encoded apiKey 
   * @return string
   */
  public function getShopId(){
    return $this->getHelper()->getShopId();
  }


  private function getLastRealOrder(){
    $orderIncrementId = $this->getCheckoutSession()->getLastRealOrderId();
    if($orderIncrementId){
      return $order = Mage::getModel('sales/order')->loadByIncrementId($orderIncrementId);
    }
    return false;
  }

  private function getLastQuote(){
    $quoteId = $this->getCheckoutSession()->getQuoteId();
    if($quoteId){
      $quote = Mage::getModel( 'sales/quote' )->load($quoteId);
      return $quote;
    }
    return false;
  }
  
  /**
  * Send Payment details to API
  * Return paymentGateway Redirect Url
  * @return string
  */

  public function setupSession(){
      $order = $this->getLastRealOrder();
      if($order && $order->getId()){
        $quote = $this->getLastQuote();
        if(!$quote) {
          return false;
        }
        if($quoteId = $quote->getId()){
          $this->getCheckoutSession()->setForumPayQuoteId();
          $quote->setIsActive(true)->save();
        }
        $created_at_time_str = strtotime($order->getCreatedAt());
        $p_code = $order->getProtectCode();
        $e_id = $order->getEntityId();
        $orderIncrementId = $order->getIncrementId();
        $paymentUrl = Mage::getUrl('forumpay/index/payment/', array('_secure' => true, 'id'=>$orderIncrementId, 'pcode'=>$p_code, 'eid'=>$e_id, 'cat'=>$created_at_time_str));
        return $paymentUrl;
      }
      return false;
  }

  public function getCryptoCurrencyList(){
    $action = 'GetCurrencyList';
    $response = $this->makeCurlRequest($action);
    if($response == false) {
      return false;
    }
    $response = json_decode($response);
    return $response;
  }

  public function getRate($params=null){
    $pos_id = $this->getShopId();
    $locale = '';
    $cryptocurrency = 'BCH';
    //$quote = $this->getCheckoutSession->getQuote();
    $quote = Mage::getSingleton('checkout/session')->getQuote();
    $orderCurrency = $quote->getQuoteCurrencyCode();
    $total = $quote->getGrandTotal();
    if($params && is_array($params) && array_key_exists('payment_cryptocurrency', $params) ){
      $cryptocurrency = $params['payment_cryptocurrency'];
    }
    $action = 'GetRate';
    $req_params = [
      'pos_id' => $pos_id,
      'invoice_currency' => $orderCurrency,
      'invoice_amount' => $total,
      'currency' => $cryptocurrency,
      'locale'=>$locale
    ];

    $response = $this->makeCurlRequest($action, $req_params);
    if($response == false) {
      return false;
    }
    $response = json_decode($response);
    //$response->print_string = '';
    if($response && !isset($response->err)){
      $response->pos_id = $pos_id;
      $response->locale = $locale;
      //$this->savePaymentDataToOrder($order, $response);
    }
    return $response;
  }

  public function startPayment($params){
    if(!$params){
      return false;
    }

    $order = $this->getLastRealOrder();
    if(!$order) {
      return false;
    }
    $transactionAmount = $order->getGrandTotal();
    $orderNo = $order->getIncrementId();
    $orderCurrency = $order->getOrderCurrencyCode();
    $payment = $order->getPayment();
    $cryptoCurrency = $payment->getCryptoCurrency();
    if(!$cryptoCurrency){
      $cryptoCurrency = 'BCH';
    }
    $locale = '';
    $shopId = $this->getShopId();

    $action = 'StartPayment';
    $request_data = [
      'pos_id' => $shopId,
      'invoice_currency' => $orderCurrency,
      'invoice_amount' => $transactionAmount,
      'currency' => $cryptoCurrency,
      //'accept_zero_confirmations' => 'true',
      'reference_no' => $orderNo,
      'locale'=>$locale
    ];
      
    $response = $this->makeCurlRequest($action, $request_data);
    if($response == false) {
      return false;
    }
    $response = json_decode($response);
    if($response && !isset($response->err)){
      $response->pos_id = $shopId;
      $this->savePaymentDataToOrder($order, $response);
    }
    return $response;
  }

  public function checkPayment($params){
    if(!$params){
      return false;
    }
    $locale = '';
    $action = 'CheckPayment';
    $paymentId = $params['invoice_no']; //Magento Transaction Id
    
    $order = $this->getOrderByTransactionId($paymentId);
    if(!$order) {
      return false;
    }
    $request_data = $this->generateApiRequestByOrder($order,$paymentId);

    $response = $this->makeCurlRequest($action, $request_data);
    if($response == false) {
      return false;
    }
    $response = json_decode($response);
    //$response->print_string = '';
    if($response && !isset($response->err)){
      $response->payment_id = $paymentId;
      if(isset($response->status)){
        if(strtolower($response->status) == 'cancelled' || strtolower($response->status) == 'confirmed'){
          $this->savePaymentDataToOrder($order, $response);
        }
      }
    }
    return $response;
  }

  public function cancelPayment($params){
    if(!$params){
      return false;
    }
    $locale = '';
    $action = 'CancelPayment';
    $paymentId = $params['invoice_no']; //Magento Transaction Id
    $order = $this->getOrderByTransactionId($paymentId);
    if(!$order) {
      return false;
    }
    $request_data = $this->generateApiRequestByOrder($order, $paymentId);

    $response = $this->makeCurlRequest($action, $request_data);
    if($response == false) {
      return false;
    }
    $response = json_decode($response);
    //$response->print_string = '';
    if(!isset($response->err)){
      $response->payment_id = $paymentId;
      if(isset($response->status)){
        if(strtolower($response->status) == 'cancelled'){
          $order = $this->getOrderByTransactionId($paymentId);
          if(!$order) {
            return false;
          }
          $this->savePaymentDataToOrder($order, $response);
        }
      }
    }
    return $response;
  }

  private function generateApiRequestByOrder($order=null, $paymentId=null){
    $request_data = [];
    if($order && $order->getId() && $paymentId!=null){
      $payment = $order->getPayment();
      $transaction = $payment->getTransaction($paymentId);
      if($transaction && $transaction->getId()){
        $additionalInfoKey = self::TRANSACTION_ADDITIONAL_INFO_KEY;
        $additionaldata = $transaction->getAdditionalInformation($additionalInfoKey);
        if($additionaldata && is_array($additionaldata)){
          $request_data = [
            'pos_id' => $additionaldata['pos_id'],
            'currency' => $additionaldata['currency'],
            'address' => $additionaldata['address'],
            'payment_id' => $additionaldata['payment_id']
          ];
        }
      }
    }
    return $request_data;
  }

  public function makeCurlRequest($action = null, $params=null){
    if($action==null){
      return false;
    }
    $url = $this->apiEndPoint().$action.'/';
    $api_basic_authorization = $this->getHelper()->getBasicAuthorization();
    try {
        $curl = curl_init();
        curl_setopt_array($curl, array(
          CURLOPT_URL => $url,
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING => "",
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 0,
          CURLOPT_FOLLOWLOCATION => true,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => "POST",
          CURLOPT_POSTFIELDS => $params,
          CURLOPT_HTTPHEADER => array(
            "Authorization: ".$api_basic_authorization,
          ),
        ));
        $response = curl_exec($curl);
        curl_close($curl);
        return $response;
    } catch (Exception $e) {
      Mage::logException($e);
      return false;
    }
  }

  private function getOrderByTransactionId($transactionId = null){
    if($transactionId==null){
      return false;
    }
    $transaction = Mage::getModel('sales/order_payment_transaction')->load($transactionId, 'txn_id');
    if($transaction->getId()){
      $orderId = $transaction->getOrderId();
      $order = Mage::getModel('sales/order')->load($orderId);
      return $order;
    }
    return false;
  }

  private function getOrderStatusAfterPayment(){
    $status = Mage_Sales_Model_Order::STATE_PROCESSING;
    $status = ($this->getHelper()->getOrderStatusAfterPayment()?$this->getHelper()->getOrderStatusAfterPayment():$status);
    return $status;
  }

  private function savePaymentDataToOrder($order, $response){
    $ifUpdateOrderStatus = false;
    $closed = 0;
    $txnType = self::ACTION_ORDER;
    $paymentResponse = json_decode(json_encode($response), true);
    
    $grandTotal = $order->getGrandTotal();
    $formatedPrice = Mage::helper('core')->currency($grandTotal,true,false);

    $isClosed = false;
    $message = __('Initialize Payment amount is %1.', $formatedPrice);

    if(array_key_exists('status', $paymentResponse)){
      if(strtolower($paymentResponse['status']) == 'cancelled' && array_key_exists('cancelled', $paymentResponse)){
        $txnType = self::ACTION_VOID;
        $message = __('Payment Failed amount is %1.', $formatedPrice);
        $isClosed = true;
        $order->cancel();
      }elseif (strtolower($paymentResponse['status']) == 'confirmed' && array_key_exists('confirmed', $paymentResponse)) {
        $closed = 1;
        $ifUpdateOrderStatus = true;
        
        $orderStatus = $this->getOrderStatusAfterPayment();
        $txnType = self::ACTION_CAPTURE;
        $message = __('The Captured Payment amount is %1.', $formatedPrice);
        $isClosed = true;
        $order->sendNewOrderEmail();
      }
    }

    if($ifUpdateOrderStatus){
      $order->setStatus($orderStatus);
    }
    $this->createTransaction($order,$paymentResponse,$txnType, $message, $closed);
    $order->save();
    return;
  }

   /**
   * Creates Transactions for directlink activities
   *
   * @param Mage_Sales_Model_Order $order
   * @param int $transactionID - persistent transaction id
   * @param int $subPayID - identifier for each transaction
   * @param array $arrInformation - add dynamic data
   * @param string $typename - name for the transaction exp.: refund
   * @param string $comment - order comment
   * 
   * @return Cashu_Helper_DirectLink $this
   */
  public function createTransaction($order, $paymentResponse = [], $txnType=null, $comment=null, $closed = 0){
    $additionaldata = [];
    $additionalInfoKey = self::TRANSACTION_ADDITIONAL_INFO_KEY;
    $tra_id = $paymentResponse['payment_id'];
    $payment = $order->getPayment();

    $transaction = $payment->getTransaction($tra_id);
    if($transaction && $transaction->getId()){
      $additionaldata = $transaction->getAdditionalInformation($additionalInfoKey);
    }
    $paymentResponse = array_merge($additionaldata,$paymentResponse);
    $payment->setTransactionId($tra_id);
    $transaction = $payment->addTransaction($txnType, null, false, $comment);
    $transaction->setParentTxnId($tra_id);
    $transaction->setIsClosed($closed);
    $transaction->setAdditionalInformation($additionalInfoKey, (array) $paymentResponse);
    $transaction->save();
    return $this;
  }

  /**
  * Send Payment details to API
  * Return paymentGateway Redirect Url
  * @return string
  */
  public function verifyTransaction($params = []){
    if(!empty($params)){
      $tra_id = $params['id'];
      $order = $this->getOrderByTransactionId($tra_id);
      if($order && $order->getId() && $order->getStatus() == $this->getOrderStatusAfterPayment()){
        $this->orderSuccess();
        return true;
      }
    }
    return false;
  }

  /**
  * Update order Status Processing
  * @return string
  */
  private function orderSuccess(){
    Mage::getSingleton('checkout/session')->getQuote()->setIsActive(false)->save();
    return $this;
  }





  public function assignData($data){
    $info = $this->getInfoInstance();
    if ($data->getCryptoCurrency()){
      $info->setCryptoCurrency($data->getCryptoCurrency());
    }
    return $this;
  }

  public function validate(){
    $errorMsg = null;
    parent::validate();
    $info = $this->getInfoInstance();
    if (!$info->getCryptoCurrency()){
      $errorCode = 'invalid_data';
      $errorMsg = $this->_getHelper()->__("Pease Select Payment Crypto Currency.");
    }

    if ($errorMsg){
      Mage::throwException($errorMsg);
    }
    return $this;
  }

}
