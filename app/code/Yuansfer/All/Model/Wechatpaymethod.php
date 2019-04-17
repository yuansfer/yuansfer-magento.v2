<?php
namespace Yuansfer\All\Model;

class Wechatpaymethod extends \Yuansfer\All\Model\MethodAbstract {
  	protected $_code  = \Yuansfer\All\Model\MethodAbstract::CODE_WECHATPAY;
    protected $_formBlockType = \Yuansfer\All\Block\Securepay\Form;
    protected $_infoBlockType = \Magento\Payment\Block\ConfigurableInfo;
    protected $_isInitializeNeeded      = true;
    protected $_canUseForMultishipping  = false;
    //protected $_isGateway               = true;
    protected $_canUseInternal          = false;
    //protected $_canUseCheckout          = true;
}