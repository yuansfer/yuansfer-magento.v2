<?php

namespace Pockyt\All\Model;

class Venmomethod extends MethodAbstract {
  	protected $_code  = MethodAbstract::CODE_VENMO;
    protected $_formBlockType = \Pockyt\All\Block\Securepay\Form;
    protected $_infoBlockType = \Magento\Payment\Block\ConfigurableInfo;
    protected $_isInitializeNeeded      = true;
    protected $_canUseForMultishipping  = false;
    //protected $_isGateway               = true;
    protected $_canUseInternal          = false;
    //protected $_canUseCheckout          = true;
}
