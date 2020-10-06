<?php
class Limitlex_ForumPay_Block_Info_Forumpay extends Mage_Payment_Block_Info
{
  protected function _prepareSpecificInformation($transport = null)
  {
    if (null !== $this->_paymentSpecificInformation) 
    {
      return $this->_paymentSpecificInformation;
    }
     
    $data = array();

    if ($this->getInfo()->getCryptoCurrency()) 
    {
      $data[Mage::helper('payment')->__('Payment Crypto Currency')] = $this->getInfo()->getCryptoCurrency();
    }
 
    $transport = parent::_prepareSpecificInformation($transport);
     
    return $transport->setData(array_merge($data, $transport->getData()));
  }
}