<?php
namespace Yuansfer\All\Model;

// require_once(__DIR__ .'/Requestor.php');
// require_once(__DIR__ .'/Error/Base.php');
use Magento\Framework\App\ObjectManager;
use Magento\Framework\DataObject;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Gateway\Command\CommandManagerInterface;
use Magento\Payment\Gateway\Command\CommandPoolInterface;
use Magento\Payment\Gateway\Config\ValueHandlerPoolInterface;
use Magento\Payment\Gateway\Data\PaymentDataObjectFactory;
use Magento\Payment\Gateway\Validator\ValidatorPoolInterface;
use Magento\Payment\Model\InfoInterface;
use Magento\Payment\Model\MethodInterface;
use Magento\Payment\Observer\AbstractDataAssignObserver;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Psr\Log\LoggerInterface;

//class MethodAbstract extends \Magento\Payment\Model\Method\Adapter
class MethodAbstract implements \Magento\Payment\Model\MethodInterface
{
    protected $_canRefund = true;
    protected $_canRefundInvoicePartial = true;
    const CODE_UNIONPAY = 'yuansfer_unionpay';
    const CODE_ALIPAY = 'yuansfer_alipay';
    const CODE_WECHATPAY = 'yuansfer_wechatpay';
    const CODE_CREDITCARD = 'yuansfer_creditcard';
    const CODE_PAYPAL = 'yuansfer_paypal';
    const CODE_VENMO = 'yuansfer_venmo';

    protected $_store;
    protected $_urlBuilder;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

        /**
     * @var ValueHandlerPoolInterface
     */
    private $valueHandlerPool;

    /**
     * @var ValidatorPoolInterface
     */
    private $validatorPool;

    /**
     * @var CommandPoolInterface
     */
    private $commandPool;

    /**
     * @var int
     */
    private $storeId;

    /**
     * @var string
     */
    private $formBlockType;

    /**
     * @var string
     */
    private $infoBlockType;

    /**
     * @var InfoInterface
     */
    private $infoInstance;

    /**
     * @var string
     */
    private $code;

    /**
     * @var ManagerInterface
     */
    private $eventManager;

    /**
     * @var PaymentDataObjectFactory
     */
    private $paymentDataObjectFactory;

    /**
     * @var \Magento\Payment\Gateway\Command\CommandManagerInterface
     */
    private $commandExecutor;

    /**
     * Logger for exception details
     *
     * @var LoggerInterface
     */
    private $logger;


    // public function __construct(
    //     ManagerInterface $eventManager,
    //     ValueHandlerPoolInterface $valueHandlerPool,
    //     PaymentDataObjectFactory $paymentDataObjectFactory,
    //     $code,
    //     $formBlockType,
    //     $infoBlockType,
    //     CommandPoolInterface $commandPool,
    //     ValidatorPoolInterface $validatorPool,
    //     CommandManagerInterface $commandExecutor,
    //     LoggerInterface $logger = null,
    //     \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
    //     \Magento\Framework\UrlInterface $urlBuilder
    // ) {
    //     $this->logger = $logger;
    //     $this->scopeConfig = $scopeConfig;
    //     $this->_urlBuilder = $urlBuilder;
    //     parent::__construct(
    //         $eventManager,
    //         $valueHandlerPool,
    //         $paymentDataObjectFactory,
    //         $code,
    //         $formBlockType,
    //         $infoBlockType,
    //         $commandPool,
    //         $validatorPool,
    //         $commandExecutor,
    //         $logger
    //     );
    // }

    public function __construct(
        ManagerInterface $eventManager,
        ValueHandlerPoolInterface $valueHandlerPool,
        PaymentDataObjectFactory $paymentDataObjectFactory,
        $code,
        $formBlockType,
        $infoBlockType,
        CommandPoolInterface $commandPool = null,
        ValidatorPoolInterface $validatorPool = null,
        CommandManagerInterface $commandExecutor = null,
        LoggerInterface $logger = null
    ) {
        $this->valueHandlerPool = $valueHandlerPool;
        $this->validatorPool = $validatorPool;
        $this->commandPool = $commandPool;
        $this->code = $code;
        $this->infoBlockType = $infoBlockType;
        $this->formBlockType = $formBlockType;
        $this->eventManager = $eventManager;
        $this->paymentDataObjectFactory = $paymentDataObjectFactory;
        $this->commandExecutor = $commandExecutor;
        $this->logger = $logger ?: ObjectManager::getInstance()->get(LoggerInterface::class);
        $this->scopeConfig = ObjectManager::getInstance()->get('Magento\Framework\App\Config\ScopeConfigInterface');
        $this->_urlBuilder = ObjectManager::getInstance()->get('Magento\Framework\UrlInterface');;
    }

    protected function log($msg)
    {
        $this->logger->debug("Yuansfer Payments - " . $this->code . ' - ' . $msg);
    }

    /**
     * Instantiate state and set it to state object
     *
     * @param string $paymentAction
     * @param        \Magento\Framework\DataObject
     */
    public function initialize($paymentAction, $stateObject)
    {
        $state = \Magento\Sales\Model\Order::STATE_PENDING_PAYMENT;
        $stateObject->setState($state);
        $stateObject->setStatus('pending_payment');
        $stateObject->setIsNotified(false);
        if ($this->_store = $this->getStore()) {
            $this->_store = is_object($this->_store) ? $this->_store->getId() : $this->_store;
        }
    }

    public function getOrderPlaceRedirectUrl()
    {
        return $this->_urlBuilder->getUrl('yuansfer/securePay/redirect', array('_secure' => true));
    }


    public function getTitle()
    {
        // if ($this->code == self::CODE_ALIPAY) {
        //     return $this->scopeConfig->getValue('payment/yuansfer/alipay_title', \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $this->_store);
        // }

        // if ($this->code == self::CODE_UNIONPAY) {
        //     return $this->scopeConfig->getValue('payment/yuansfer/unionpay_title', \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $this->_store);
        // }

        // if ($this->code == self::CODE_WECHATPAY) {
        //     return $this->scopeConfig->getValue('payment/yuansfer/wechatpay_title', \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $this->_store);
        // }

        // return parent::getTitle();
        return $this->getConfiguredValue('title');
    }
    public function isAvailable(CartInterface $quote = null)
    {
        if (!$this->isActive($quote ? $quote->getStoreId() : null)) {
            return false;
        }

        $checkResult = new DataObject();
        $checkResult->setData('is_available', true);
        try {
            $infoInstance = $this->getInfoInstance();
            if ($infoInstance !== null) {
                $validator = $this->getValidatorPool()->get('availability');
                $result = $validator->validate(
                    [
                        'payment' => $this->paymentDataObjectFactory->create($infoInstance)
                    ]
                );

                $checkResult->setData('is_available', $result->isValid());
            }
        } catch (\Exception $e) {
            // pass
        }

        // for future use in observers
        $this->eventManager->dispatch(
            'payment_method_is_active',
            [
                'result' => $checkResult,
                'method_instance' => $this,
                'quote' => $quote
            ]
        );

        return $checkResult->getData('is_available');
    }
    // public function isAvailable(\Magento\Quote\Api\Data\CartInterface $quote = null)
    // {

    //     // if (parent::isAvailable($quote)) {
    //     if($this->code) {
    //         if ($this->code == self::CODE_ALIPAY) {
    //             // return $this->scopeConfig->getValue('payment/yuansfer/alipay_active', \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $this->_store);
    //             return $this->scopeConfig->getValue('payment/yuansfer/alipay_active', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    //         }

    //         if ($this->code == self::CODE_UNIONPAY) {
    //             return $this->scopeConfig->getValue('payment/yuansfer/unionpay_active', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    //         }

    //         if ($this->code == self::CODE_WECHATPAY) {
    //             return $this->scopeConfig->getValue('payment/yuansfer/wechatpay_active', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    //         }
    //     }

    //     return false;
    // }


    public function refund(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        // parent::refund($payment, $amount);
        $this->executeCommand(
            'refund',
            ['payment' => $payment, 'amount' => $amount]
        );
        $debug = $this->scopeConfig->getValue('payment/yuansfer/yuansfer_mode', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        if ($debug) {
            $token = $this->scopeConfig->getValue('payment/yuansfer/yuansfer_test_apikey', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        } else {
            $token = $this->scopeConfig->getValue('payment/yuansfer/yuansfer_live_apikey', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        }

        $merchantNo = $this->scopeConfig->getValue('payment/yuansfer/yuansfer_merchant_no', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        $storeNo = $this->scopeConfig->getValue('payment/yuansfer/yuansfer_store_no', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);

        $transactionId = $payment->getParentTransactionId();

        $this->log('debug mode = ' . $debug . PHP_EOL . 'api key=' . $token . 'trasactionId=' . PHP_EOL . $transactionId);


        try {
            $requestor = new Requestor();
            $requestor->setDebug($debug);
            $ret = $requestor->refund($merchantNo, $storeNo, $token, $payment, $amount);

        } catch (Exception $e) {
            $this->log('exception = ' . $e->getMessage());
            //$this->_logger->info(__('Payment refunding error.'));
            throw new \Magento\Framework\Exception\LocalizedException($e->getMessage());
        }

        $payment
            ->setTransactionId($transactionId . '-' . \Magento\Sales\Model\Order\Payment\Transaction::TYPE_REFUND)
            ->setParentTransactionId($transactionId)
            ->setIsTransactionClosed(1)
            ->setShouldCloseParentTransaction(1);

        return $this;

    }

    /**
     * Returns Validator pool
     *
     * @return ValidatorPoolInterface
     * @throws \DomainException
     */
    public function getValidatorPool()
    {
        if ($this->validatorPool === null) {
            throw new \DomainException('Validator pool is not configured for use.');
        }
        return $this->validatorPool;
    }

    /**
     * @inheritdoc
     */
    public function canOrder()
    {
        return $this->canPerformCommand('order');
    }

    /**
     * @inheritdoc
     */
    public function canAuthorize()
    {
        return $this->canPerformCommand('authorize');
    }

    /**
     * @inheritdoc
     */
    public function canCapture()
    {
        return $this->canPerformCommand('capture');
    }

    /**
     * @inheritdoc
     */
    public function canCapturePartial()
    {
        return $this->canPerformCommand('capture_partial');
    }

    /**
     * @inheritdoc
     */
    public function canCaptureOnce()
    {
        return $this->canPerformCommand('capture_once');
    }

    /**
     * @inheritdoc
     */
    public function canRefund()
    {
        return $this->canPerformCommand('refund');
    }

    /**
     * @inheritdoc
     */
    public function canRefundPartialPerInvoice()
    {
        return $this->canPerformCommand('refund_partial_per_invoice');
    }

    /**
     * @inheritdoc
     */
    public function canVoid()
    {
        return $this->canPerformCommand('void');
    }

    /**
     * @inheritdoc
     */
    public function canUseInternal()
    {
        return (bool)$this->getConfiguredValue('can_use_internal');
    }

    /**
     * @inheritdoc
     */
    public function canUseCheckout()
    {
        return (bool)$this->getConfiguredValue('can_use_checkout');
    }

    /**
     * @inheritdoc
     */
    public function canEdit()
    {
        return (bool)$this->getConfiguredValue('can_edit');
    }

    /**
     * @inheritdoc
     */
    public function canFetchTransactionInfo()
    {
        return $this->canPerformCommand('fetch_transaction_info');
    }

    /**
     * @inheritdoc
     */
    public function canReviewPayment()
    {
        return $this->canPerformCommand('review_payment');
    }

    /**
     * @inheritdoc
     */
    public function isGateway()
    {
        return (bool)$this->getConfiguredValue('is_gateway');
    }

    /**
     * @inheritdoc
     */
    public function isOffline()
    {
        return (bool)$this->getConfiguredValue('is_offline');
    }

    /**
     * @inheritdoc
     */
    public function isInitializeNeeded()
    {
        return (bool)(int)$this->getConfiguredValue('can_initialize');
    }

    /**
     * @inheritdoc
     */
    public function isActive($storeId = null)
    {
        return (bool)$this->getConfiguredValue('active', $storeId);
    }

    /**
     * @inheritdoc
     */
    public function canUseForCountry($country)
    {
        try {
            $validator = $this->getValidatorPool()->get('country');
        } catch (\Exception $e) {
            return true;
        }

        $result = $validator->validate(['country' => $country, 'storeId' => $this->getStore()]);
        return $result->isValid();
    }

    /**
     * @inheritdoc
     */
    public function canUseForCurrency($currencyCode)
    {
        try {
            $validator = $this->getValidatorPool()->get('currency');
        } catch (\Exception $e) {
            return true;
        }

        $result = $validator->validate(['currency' => $currencyCode, 'storeId' => $this->getStore()]);
        return $result->isValid();
    }

    /**
     * Whether payment command is supported and can be executed
     *
     * @param string $commandCode
     * @return bool
     */
    private function canPerformCommand($commandCode)
    {
        return (bool)$this->getConfiguredValue('can_' . $commandCode);
    }

    /**
     * Unifies configured value handling logic
     *
     * @param string $field
     * @param null $storeId
     * @return mixed
     */
    private function getConfiguredValue($field, $storeId = null)
    {
        $handler = $this->valueHandlerPool->get($field);
        $subject = [
            'field' => $field
        ];

        if ($this->getInfoInstance()) {
            $subject['payment'] = $this->paymentDataObjectFactory->create($this->getInfoInstance());
        }

        return $handler->handle($subject, $storeId ?: $this->getStore());
    }

    /**
     * @inheritdoc
     */
    public function getConfigData($field, $storeId = null)
    {
        return $this->getConfiguredValue($field, $storeId);
    }

    /**
     * @inheritdoc
     */
    public function validate()
    {
        try {
            $validator = $this->getValidatorPool()->get('global');
        } catch (\Exception $e) {
            return $this;
        }

        $result = $validator->validate(
            ['payment' => $this->getInfoInstance(), 'storeId' => $this->getStore()]
        );

        if (!$result->isValid()) {
            throw new LocalizedException(
                __(implode("\n", $result->getFailsDescription()))
            );
        }

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function fetchTransactionInfo(InfoInterface $payment, $transactionId)
    {
        return $this->executeCommand(
            'fetch_transaction_information',
            ['payment' => $payment, 'transactionId' => $transactionId]
        );
    }

    /**
     * @inheritdoc
     */
    public function order(InfoInterface $payment, $amount)
    {
        $this->executeCommand(
            'order',
            ['payment' => $payment, 'amount' => $amount]
        );

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function authorize(InfoInterface $payment, $amount)
    {
        $this->executeCommand(
            'authorize',
            ['payment' => $payment, 'amount' => $amount]
        );

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function capture(InfoInterface $payment, $amount)
    {
        $this->executeCommand(
            'capture',
            ['payment' => $payment, 'amount' => $amount]
        );

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function cancel(InfoInterface $payment)
    {
        $this->executeCommand('cancel', ['payment' => $payment]);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function void(InfoInterface $payment)
    {
        $this->executeCommand('void', ['payment' => $payment]);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function acceptPayment(InfoInterface $payment)
    {
        $this->executeCommand('accept_payment', ['payment' => $payment]);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function denyPayment(InfoInterface $payment)
    {
        $this->executeCommand('deny_payment', ['payment' => $payment]);

        return $this;
    }

    /**
     * @inheritdoc
     */
    private function executeCommand($commandCode, array $arguments = [])
    {
        if (!$this->canPerformCommand($commandCode)) {
            return null;
        }

        /** @var InfoInterface|null $payment */
        $payment = null;
        if (isset($arguments['payment']) && $arguments['payment'] instanceof InfoInterface) {
            $payment = $arguments['payment'];
            $arguments['payment'] = $this->paymentDataObjectFactory->create($arguments['payment']);
        }

        if ($this->commandExecutor !== null) {
            return $this->commandExecutor->executeByCode($commandCode, $payment, $arguments);
        }

        if ($this->commandPool === null) {
            throw new \DomainException('Command pool is not configured for use.');
        }

        $command = $this->commandPool->get($commandCode);

        return $command->execute($arguments);
    }

    /**
     * @inheritdoc
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * @inheritdoc
     */
    public function setStore($storeId)
    {
        $this->storeId = (int)$storeId;
    }

    /**
     * @inheritdoc
     */
    public function getStore()
    {
        return $this->storeId;
    }

    /**
     * @inheritdoc
     */
    public function getFormBlockType()
    {
        return $this->formBlockType;
    }

    /**
     * @inheritdoc
     */
    public function getInfoBlockType()
    {
        return $this->infoBlockType;
    }

    /**
     * @inheritdoc
     */
    public function getInfoInstance()
    {
        return $this->infoInstance;
    }

    /**
     * @inheritdoc
     */
    public function setInfoInstance(InfoInterface $info)
    {
        $this->infoInstance = $info;
    }

    /**
     * @inheritdoc
     * @param DataObject $data
     * @return $this
     */
    public function assignData(\Magento\Framework\DataObject $data)
    {
        $this->eventManager->dispatch(
            'payment_method_assign_data_' . $this->getCode(),
            [
                AbstractDataAssignObserver::METHOD_CODE => $this,
                AbstractDataAssignObserver::MODEL_CODE => $this->getInfoInstance(),
                AbstractDataAssignObserver::DATA_CODE => $data
            ]
        );

        $this->eventManager->dispatch(
            'payment_method_assign_data',
            [
                AbstractDataAssignObserver::METHOD_CODE => $this,
                AbstractDataAssignObserver::MODEL_CODE => $this->getInfoInstance(),
                AbstractDataAssignObserver::DATA_CODE => $data
            ]
        );

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getConfigPaymentAction()
    {
        return $this->getConfiguredValue('payment_action');
    }
}
