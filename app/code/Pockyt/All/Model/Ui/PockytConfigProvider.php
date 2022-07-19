<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Pockyt\All\Model\Ui;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Framework\Session\SessionManagerInterface;
use Pockyt\All\Model\MethodAbstract;
/**
 * Class PockytConfigProvider
 */
class PockytConfigProvider implements ConfigProviderInterface
{
    const CODE = 'pockyt';

    /**
     * @var Config
     */
    private $config;
    /**
     * @var $session
     */
    private $session;
    /**
     * @var $storeManager
     */
    private $storeManager;
    /**
     * @var $repository
     */
    private $assetRepo;

    protected $store;

    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        // \Magento\Framework\Session\SessionManagerInterface $session,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\View\Asset\Repository $assetRepo,
        \Magento\Store\Api\Data\StoreInterface $store
    ) {
        $this->config = $scopeConfig;
        // $this->session = $session;
        $this->storeManager = $storeManager;
        $this->assetRepo = $assetRepo;
    }

    /**
     * Retrieve assoc array of checkout configuration
     *
     * @return array
     */
    public function getConfig()
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $store = $objectManager->get('Magento\Store\Api\Data\StoreInterface');
        $localeCode = $store->getLocaleCode();
        if($localeCode != "zh_CN")
            $localeCode = "en_US";

        $alipayImage = $this->assetRepo->getUrl("Pockyt_All::images/pockyt_alipay/logo_".$localeCode.".svg");
        $alipayWidth = "165";

        $unionpayImage = $this->assetRepo->getUrl("Pockyt_All::images/pockyt_unionpay/logo_".$localeCode.".svg");
        $unionpayWidth = "89";

        $wechatpayImage = $this->assetRepo->getUrl("Pockyt_All::images/pockyt_wechatpay/logo_".$localeCode.".png");
        $wechatpayWidth = "200";

        $creditcardImage = $this->assetRepo->getUrl("Pockyt_All::images/pockyt_creditcard/logo_".$localeCode.".png");
        $creditcardWidth = "128";

        $paypalImage = $this->assetRepo->getUrl("Pockyt_All::images/pockyt_paypal/logo_".$localeCode.".svg");
        $paypalWidth = "165";

        $venmoImage = $this->assetRepo->getUrl("Pockyt_All::images/pockyt_venmo/logo_".$localeCode.".svg");
        $venmoWidth = "165";

        $message = 'You will be taken to the payment website when you click Place Order';

        return [
            'payment' => [
                self::CODE => [
                    'pockyt_merchant_no' => $this->config->getValue('payment/pockyt/pockyt_merchant_no', \Magento\Store\Model\ScopeInterface::SCOPE_STORE),
                    'pockyt_store_no' => $this->config->getValue('payment/pockyt/pockyt_store_no', \Magento\Store\Model\ScopeInterface::SCOPE_STORE),
                    'pockyt_mode' => $this->config->getValue('payment/pockyt/pockyt_mode', \Magento\Store\Model\ScopeInterface::SCOPE_STORE),
                    'pockyt_test_apikey' => $this->config->getValue('payment/pockyt/pockyt_test_apikey', \Magento\Store\Model\ScopeInterface::SCOPE_STORE),
                    'pockyt_live_apikey' => $this->config->getValue('payment/pockyt/pockyt_live_apikey', \Magento\Store\Model\ScopeInterface::SCOPE_STORE),
                ],
                MethodAbstract::CODE_UNIONPAY => [
                    'isActive' => $this->config->getValue('payment/pockyt/unionpay_active', \Magento\Store\Model\ScopeInterface::SCOPE_STORE),
                    'title' => $this->config->getValue('payment/pockyt/unionpay_title', \Magento\Store\Model\ScopeInterface::SCOPE_STORE),
                    'imageUrl' => $unionpayImage,
                    'imageWidth' => $unionpayWidth
                ],
                MethodAbstract::CODE_ALIPAY => [
                    'isActive' => $this->config->getValue('payment/pockyt/alipay_active', \Magento\Store\Model\ScopeInterface::SCOPE_STORE),
                    'title' => $this->config->getValue('payment/pockyt/alipay_title', \Magento\Store\Model\ScopeInterface::SCOPE_STORE),
                    'imageUrl' => $alipayImage,
                    'imageWidth' => $alipayWidth,
                    'settleCurrencyForCNY' => $this->config->getValue('payment/pockyt/alipay_settle_currency_for_cny', \Magento\Store\Model\ScopeInterface::SCOPE_STORE)
                ],
                MethodAbstract::CODE_WECHATPAY => [
                    'isActive' => $this->config->getValue('payment/pockyt/wechatpay_active', \Magento\Store\Model\ScopeInterface::SCOPE_STORE),
                    'title' => $this->config->getValue('payment/pockyt/wechatpay_title', \Magento\Store\Model\ScopeInterface::SCOPE_STORE),
                    'imageUrl' => $wechatpayImage,
                    'imageWidth' => $wechatpayWidth
                ],
                MethodAbstract::CODE_CREDITCARD => [
                    'isActive' => $this->config->getValue('payment/pockyt/creditcard_active', \Magento\Store\Model\ScopeInterface::SCOPE_STORE),
                    'title' => $this->config->getValue('payment/pockyt/creditcard_title', \Magento\Store\Model\ScopeInterface::SCOPE_STORE),
                    'createAccount' => $this->config->getValue('payment/pockyt/creditcard_create_account', \Magento\Store\Model\ScopeInterface::SCOPE_STORE),
                    'imageUrl' => $creditcardImage,
                    'imageWidth' => $creditcardWidth
                ],
                MethodAbstract::CODE_PAYPAL => [
                    'isActive' => $this->config->getValue('payment/pockyt/paypal_active', \Magento\Store\Model\ScopeInterface::SCOPE_STORE),
                    'title' => $this->config->getValue('payment/pockyt/paypal_title', \Magento\Store\Model\ScopeInterface::SCOPE_STORE),
                    'imageUrl' => $paypalImage,
                    'imageWidth' => $paypalWidth,
                ],
                MethodAbstract::CODE_VENMO => [
                    'isActive' => $this->config->getValue('payment/pockyt/venmo_active', \Magento\Store\Model\ScopeInterface::SCOPE_STORE),
                    'title' => $this->config->getValue('payment/pockyt/venmo_title', \Magento\Store\Model\ScopeInterface::SCOPE_STORE),
                    'imageUrl' => $venmoImage,
                    'imageWidth' => $venmoWidth,
                ],
            ]
        ];
    }
}
