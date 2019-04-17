<?php
namespace Yuansfer\All\Block\Securepay;

// require_once(__DIR__ .'/../../Model/Requestor.php');
// require_once(__DIR__ .'/../../Model/Error/Base.php');
// use Yuansfer\All\Model\Requestor;
// use Yuansfer\All\Model\Error\Base;

class Yuansferform extends \Magento\Framework\View\Element\AbstractBlock
{

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $checkoutSession;

    /**
     * @var \Magento\Sales\Model\OrderFactory
     */
    protected $salesOrderFactory;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;
    protected $_urlBuilder;
    protected $_helper;

    public function __construct(
        \Magento\Framework\View\Element\Context $context,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Sales\Model\OrderFactory $salesOrderFactory,
        \Magento\Framework\UrlInterface $urlBuilder,
        \Psr\Log\LoggerInterface $logger,   
        \Yuansfer\All\Helper\Data $helper,     
        array $data = []
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->checkoutSession = $checkoutSession;
        $this->salesOrderFactory = $salesOrderFactory;
        $this->logger = $logger;
        $this->_urlBuilder = $urlBuilder;
        $this->_helper = $helper;
        parent::__construct(
            $context,
            $data
        );
    }
    
    protected function _toHtml()
    {        
        $debug = $this->scopeConfig->getValue('payment/yuansfer/yuansfer_mode', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        if ($debug) {
            $token = $this->scopeConfig->getValue('payment/yuansfer/yuansfer_test_apikey', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        } else {
            $token = $this->scopeConfig->getValue('payment/yuansfer/yuansfer_live_apikey', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        }
        
        $merchantNo = $this->scopeConfig->getValue('payment/yuansfer/yuansfer_merchant_no', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        $storeNo = $this->scopeConfig->getValue('payment/yuansfer/yuansfer_store_no', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);

        $sOrderId = $this->checkoutSession->getLastRealOrderId();
        $oOrder = $this->salesOrderFactory->create()->loadByIncrementId($sOrderId);
        
        $ipn = $this->_urlBuilder->getUrl('yuansfer/securePay/ipn');
        // $callback = Mage::getUrl('yuansfer/securePay/callback', array(
        //     '_query' => array(
        //         'status' => '{status}',
        //         'amount' => '{amount}',
        //         'reference' => '{reference}',
        //         'note' => '{note}'
        //     )
        // ));
        $callback = $this->_urlBuilder->getUrl('yuansfer/securePay/callback', array(
            '_query' => 'status={status}&amount={amount}&reference={reference}&note={note}'            
        ));

        $methodCode = "";
        if($oOrder->getPayment())
            $methodCode = $oOrder->getPayment()->getMethod();
        
        $this->log('current method=' . $methodCode);
        $vendor = '';
        if ($methodCode == \Yuansfer\All\Model\MethodAbstract::CODE_ALIPAY) {
            $vendor = 'alipay';
        } elseif ($methodCode == \Yuansfer\All\Model\MethodAbstract::CODE_UNIONPAY) {
            $vendor = 'unionpay';
        } elseif ($methodCode == \Yuansfer\All\Model\MethodAbstract::CODE_WECHATPAY) {
            $vendor = 'wechatpay';
        }
        $requestor = new \Requestor($this->logger);
        $requestor->setDebug($debug);
        $ret = $requestor->getSecureForm(
            $merchantNo,
            $storeNo,
            $token,
            $vendor,
            $oOrder,
            $ipn,
            $callback
        );

        $this->log('return from yuansfer:' . print_r($ret,true));

        return $ret;
    }


    protected function log($msg)
    {
        $this->logger->debug("Yuansfer SecurePay form - " . $msg);
    }
}
