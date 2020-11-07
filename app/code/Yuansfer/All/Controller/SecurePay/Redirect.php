<?php

namespace Yuansfer\All\Controller\SecurePay;

use Yuansfer\All\Model\MethodAbstract;

class Redirect extends \Magento\Framework\App\Action\Action
{

    /**
     * @var \Magento\Checkout\Helper\Data
     */
    protected $checkoutHelper;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $checkoutSession;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var \Magento\Sales\Model\OrderFactory
     */
    protected $salesOrderFactory;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Checkout\Helper\Data $checkoutHelper,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Sales\Model\OrderFactory $salesOrderFactory,
        \Psr\Log\LoggerInterface $logger
    )
    {
        $this->checkoutHelper = $checkoutHelper;
        $this->checkoutSession = $checkoutSession;
        $this->scopeConfig = $scopeConfig;
        $this->salesOrderFactory = $salesOrderFactory;
        $this->logger = $logger;
        parent::__construct(
            $context
        );
    }

    public function execute()
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

        $ipn = $this->_url->getUrl('yuansfer/securePay/ipn');
        $callback = $this->_url->getUrl('yuansfer/securePay/callback', [
            '_query' => 'status={status}&amount={amount}&reference={reference}&note={note}',
        ]);

        $methodCode = "";
        if ($oOrder->getPayment()) {
            $methodCode = $oOrder->getPayment()->getMethod();
        }

        $createAccount = false;

        $this->log('current method=' . $methodCode);
        $vendor = '';
        if ($methodCode == \Yuansfer\All\Model\MethodAbstract::CODE_ALIPAY) {
            $vendor = 'alipay';
        } elseif ($methodCode == \Yuansfer\All\Model\MethodAbstract::CODE_UNIONPAY) {
            $vendor = 'unionpay';
        } elseif ($methodCode == \Yuansfer\All\Model\MethodAbstract::CODE_WECHATPAY) {
            $vendor = 'wechatpay';
        } elseif ($methodCode == \Yuansfer\All\Model\MethodAbstract::CODE_CREDITCARD) {
            $vendor = 'creditcard';

            $createAccount = $this->scopeConfig->getValue('payment/yuansfer/creditcard_create_account', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        }

        $requestor = new \Requestor($this->logger);

        $customerId = null;
        if ($createAccount) {
            $customerId = $requestor->customer($merchantNo, $storeNo, $token, $oOrder);
        }

        $alipaySettleCurrencyFroCNY = $this->scopeConfig->getValue('payment/yuansfer/alipay_settle_currency_for_cny', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);

        $requestor->setDebug($debug);
        $result = $requestor->securePay(
            $merchantNo,
            $storeNo,
            $token,
            $vendor,
            $oOrder,
            $ipn,
            $callback,
            $customerId,
            $alipaySettleCurrencyFroCNY
        );

        $oOrder->setData('yuansfer_settle_currency', $result['settleCurrency']);
        $oOrder->save();

        $this->log('yuansfer payment redirect to:' . $result['url']);

        $this->getResponse()->setRedirect($result['url']);
    }

    protected function log($msg)
    {
        $this->logger->debug("Yuansfer Redirect controller - " . $msg);
    }
}
