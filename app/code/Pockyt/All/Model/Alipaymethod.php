<?php
namespace Pockyt\All\Model;


class Alipaymethod extends MethodAbstract {
  	protected $_code  = MethodAbstract::CODE_ALIPAY;
    protected $_formBlockType = \Pockyt\All\Block\Securepay\Form;
    protected $_infoBlockType = \Magento\Payment\Block\ConfigurableInfo;
    protected $_isInitializeNeeded      = true;
    protected $_canUseForMultishipping  = false;
    //protected $_isGateway               = true;
    protected $_canUseInternal          = false;
    //protected $_canUseCheckout          = true;


    public function isAvailable(\Magento\Quote\Api\Data\CartInterface $quote = null)
    {
    	//does not work for wechar browser
		if(preg_match('/(micromessenger)/i', strtolower($_SERVER['HTTP_USER_AGENT']))) {
			return false;
		}

		return parent::isAvailable($quote);
	}
}
