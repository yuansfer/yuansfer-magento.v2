<?php
namespace Yuansfer\All\Controller\SecurePay;

class Callback extends \Magento\Framework\App\Action\Action
{

    /**
     * @var \Magento\Checkout\Helper\Data
     */
    protected $checkoutHelper;

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
        \Magento\Sales\Model\OrderFactory $salesOrderFactory,
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->checkoutHelper = $checkoutHelper;
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
            if ($data['status'] !== 'init') {
                $message = __('The order not paid!');
            } else {
                $message = __('Unable to place the order!');
            }

            $this->checkoutHelper->getCheckout()->restoreQuote();
            $this->messageManager->addError(__('Something has gone wrong with your payment. Please contact us.'));
            $this->log($message);

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
        $this->logger->debug("Yuansfer Callback controller - " . $msg);
    }
}
