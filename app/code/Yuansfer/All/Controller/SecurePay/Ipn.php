<?php
namespace Yuansfer\All\Controller\SecurePay;

class Ipn extends \Magento\Framework\App\Action\Action
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
    ) {
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
        $data = $this->getRequest()->getParams();

        $this->log('Get request to IPN');
        $this->log(print_r($data, true));

        $this->processPayment($data);
    }

    protected function verifySig($data)
    {
        $debug = $this->scopeConfig->getValue('payment/yuansfer/yuansfer_mode', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        if ($debug) {
            $token = $this->scopeConfig->getValue('payment/yuansfer/yuansfer_test_apikey', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        } else {
            $token = $this->scopeConfig->getValue('payment/yuansfer/yuansfer_live_apikey', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        }
        $this->log('get ' . ($debug ? 'test' : 'live') . ' token: ' . $token);

        if (!isset($data['verifySign'])) {
            return false;
        }
        $verifySign = $data['verifySign'];

        unset($data['verifySign']);

        ksort($data, SORT_STRING);
        $str = '';
        foreach ($data as $k => $v) {
            $str .= $k . '=' . $v . '&';
        }
        $sig = md5($str . md5($token));

        $this->log('sig: ' . $sig . '; verify: ' . $verifySign);

        return $sig === $verifySign;
    }

    protected function findOrder($data)
    {
        $refs = explode('at', $data['reference']);
        //first item is order id
        if ($refs !== null && is_array($refs)) {
            $order_id = $refs[0];
        } else {
            $this->log('reference code invalid:' . $data['reference']);

            return null;
        }

        $order = $this->salesOrderFactory->create()->loadByIncrementId($order_id);
        if (!$order->getId()) {
            $this->getResponse()
                ->setStatusCode(404)
                ->setContent('Order not found')
                ->sendResponse();
            exit;
        }

        $this->log('Find order id=' . $order->getId());

        return $order;
    }

    protected function processPayment($data)
    {
         if (!isset($data['status'], $data['reference']) || !$this->verifySig($data)) {
             $this->getResponse()
                 ->setStatusCode(400)
                 ->setContent('Params invalid')
                 ->sendResponse();
             exit;
         }

        $order = $this->findOrder($data);

        if ($data['status'] === 'success') {
            $this->successIPN($order, $data);

            $this->getResponse()
                ->setBody('success');
        } else {
            $this->failIPN($order, $data);
        }
    }

    protected function successIPN($order, $data)
    {
        $state = \Magento\Sales\Model\Order::STATE_PROCESSING;
        $order->setState($state)
            ->setStatus($order->getConfig()->getStateDefaultStatus($state));

        $payment = $order->getPayment();
        $amount = !empty($data['rmbAmount']) ? $data['rmbAmount'] : $data['amount'];
        $payment->setTransactionId($data['reference'])
            ->setCurrencyCode($order->getOrderCurrencyCode())
            ->setPreparedMessage('')
            ->setIsTransactionClosed(1)
            ->registerCaptureNotification($amount);
        $order->save();

        // notify customer
        $invoice = $payment->getCreatedInvoice();
        if ($invoice && !$order->getEmailSent()) {
            try{
                $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
                $emailSender = $objectManager->create('\Magento\Sales\Model\Order\Email\Sender\OrderSender');
                $emailSender->send($order);

                $order->addStatusHistoryComment(
                    $this->__('Notified customer about invoice #%s.', $invoice->getIncrementId())
                )
                    ->setIsCustomerNotified(true)
                    ->save();
            } catch(\Exception $e) {
            }
        }
    }

    protected function failIPN($order, $data)
    {
        $payment = $order->getPayment();

        $payment->setTransactionId($data['reference'])
            ->setNotificationResult(true)
            ->setIsTransactionClosed(true);
        if (!$order->isCanceled()) {
            $payment->registerPaymentReviewAction(\Magento\Sales\Model\Order\Payment::REVIEW_ACTION_DENY, false);
        } else {
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            $emailSender = $objectManager->create('\Magento\Sales\Model\Order\Email\Sender\OrderSender');
            $emailSender->send($order);

            $comment = $this->__('Transaction ID: "%s"', $data['reference']);
            $order->addStatusHistoryComment($comment, false);
        }
        $order->save();
    }

    protected function log($msg)
    {
        $this->logger->debug("Yuansfer Ipn controller - " . $msg);
    }
}
