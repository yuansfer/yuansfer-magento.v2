<?php
namespace Pockyt\All\Helper;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * @var \Magento\Framework\App\Request\Http
     */
    protected $request;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    protected $checkoutSession;

    protected $saleOrderFactory;

    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Framework\App\Request\Http $request,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Sales\Model\OrderFactory $salesOrderFactory
    ) {
        $this->request = $request;
        $this->storeManager = $storeManager;
        $this->checkoutSession = $checkoutSession;
        $this->salesOrderFactory = $salesOrderFactory;
        parent::__construct(
            $context
        );
    }

    public function getOrder() {
        $sOrderId = $this->checkoutSession->getLastRealOrderId();
        return $this->salesOrderFactory->create()->loadByIncrementId($sOrderId);
    }

    public function getPaymentMethod() {
        $order = $this->getOrder();
        return $order->getPayment()->getMethod();
    }
}