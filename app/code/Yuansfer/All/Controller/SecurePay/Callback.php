<?php
namespace Yuansfer\All\Controller\SecurePay;

class Callback extends \Magento\Framework\App\Action\Action
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
     * @var \Magento\Framework\App\Response\Http
     */
    protected $responseHttp;

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

        $this->log('Get request to callback');
        $this->log(print_r($data, true));

        $this->findOrder($data);

        if (isset($data['status']) && $data['status'] === 'success') {
            $this->_redirect('checkout/onepage/success');
        } else {
            $this->checkoutHelper->sendPaymentFailedEmail(
                $this->checkoutSession->getQuote(),
                $this->__('Unable to place the order.')
            );
            $this->checkoutSession->addError($this->__('Unable to place the order.'));
            $this->log('place order error');
            $this->_redirect('checkout/cart');
        }
    }

    protected function findOrder($data)
    {
        if (!isset($data['reference'])) {
            return null;
        }

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
                ->setHeader('HTTP/1.1', '503 Service Unavailable')
                ->sendResponse();
            exit;
        }

        $this->log('Find order id=' . $order->getId());

        return $order;
    }

    protected function log($msg)
    {
        $this->logger->debug("Yuansfer SecurePay controller - " . $msg);
    }
}
