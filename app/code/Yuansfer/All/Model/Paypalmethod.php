<?php

namespace Yuansfer\All\Model;

class Paypalmethod extends MethodAbstract {
  	protected $_code  = MethodAbstract::CODE_PAYPAL;
    protected $_formBlockType = \Yuansfer\All\Block\Securepay\Form;
    protected $_infoBlockType = \Magento\Payment\Block\ConfigurableInfo;
    protected $_isInitializeNeeded      = true;
    protected $_canUseForMultishipping  = false;
    //protected $_isGateway               = true;
    protected $_canUseInternal          = false;
    //protected $_canUseCheckout          = true;
}
