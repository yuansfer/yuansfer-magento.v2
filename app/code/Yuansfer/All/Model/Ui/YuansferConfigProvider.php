<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Yuansfer\All\Model\Ui;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Framework\Session\SessionManagerInterface;
use Yuansfer\All\Model\MethodAbstract;
/**
 * Class YuansferConfigProvider
 */
class YuansferConfigProvider implements ConfigProviderInterface
{
    const CODE = 'yuansfer';

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

        $alipayImage = $this->assetRepo->getUrl("Yuansfer_All::images/yuansfer_alipay/logo_".$localeCode.".svg");
        $alipayWidth = "165";

        $unionpayImage = $this->assetRepo->getUrl("Yuansfer_All::images/yuansfer_unionpay/logo_".$localeCode.".svg");
        $unionpayWidth = "89";

        $wechatpayImage = $this->assetRepo->getUrl("Yuansfer_All::images/yuansfer_wechatpay/logo_".$localeCode.".png");
        $wechatpayWidth = "200";

        $creditcardImage = $this->assetRepo->getUrl("Yuansfer_All::images/yuansfer_creditcard/logo_".$localeCode.".png");
        $creditcardWidth = "128";

        $message = 'You will be taken to the payment website when you click Place Order';

        return [
            'payment' => [
                self::CODE => [
                    'yuansfer_merchant_no' => $this->config->getValue('payment/yuansfer/yuansfer_merchant_no', \Magento\Store\Model\ScopeInterface::SCOPE_STORE),
                    'yuansfer_store_no' => $this->config->getValue('payment/yuansfer/yuansfer_store_no', \Magento\Store\Model\ScopeInterface::SCOPE_STORE),
                    'yuansfer_mode' => $this->config->getValue('payment/yuansfer/yuansfer_mode', \Magento\Store\Model\ScopeInterface::SCOPE_STORE),
                    'yuansfer_test_apikey' => $this->config->getValue('payment/yuansfer/yuansfer_test_apikey', \Magento\Store\Model\ScopeInterface::SCOPE_STORE),
                    'yuansfer_live_apikey' => $this->config->getValue('payment/yuansfer/yuansfer_live_apikey', \Magento\Store\Model\ScopeInterface::SCOPE_STORE),
                ],
                MethodAbstract::CODE_UNIONPAY => [
                    'isActive' => $this->config->getValue('payment/yuansfer/unionpay_active', \Magento\Store\Model\ScopeInterface::SCOPE_STORE),
                    'title' => $this->config->getValue('payment/yuansfer/unionpay_title', \Magento\Store\Model\ScopeInterface::SCOPE_STORE),
                    'imageUrl' => $unionpayImage,
                    'imageWidth' => $unionpayWidth
                ],
                MethodAbstract::CODE_ALIPAY => [
                    'isActive' => $this->config->getValue('payment/yuansfer/alipay_active', \Magento\Store\Model\ScopeInterface::SCOPE_STORE),
                    'title' => $this->config->getValue('payment/yuansfer/alipay_title', \Magento\Store\Model\ScopeInterface::SCOPE_STORE),
                    'imageUrl' => $alipayImage,
                    'imageWidth' => $alipayWidth
                ],
                MethodAbstract::CODE_WECHATPAY => [
                    'isActive' => $this->config->getValue('payment/yuansfer/wechatpay_active', \Magento\Store\Model\ScopeInterface::SCOPE_STORE),
                    'title' => $this->config->getValue('payment/yuansfer/wechatpay_title', \Magento\Store\Model\ScopeInterface::SCOPE_STORE),
                    'imageUrl' => $wechatpayImage,
                    'imageWidth' => $wechatpayWidth
                ],
                MethodAbstract::CODE_CREDITCARD => [
                    'isActive' => $this->config->getValue('payment/yuansfer/creditcard_active', \Magento\Store\Model\ScopeInterface::SCOPE_STORE),
                    'title' => $this->config->getValue('payment/yuansfer/creditcard_title', \Magento\Store\Model\ScopeInterface::SCOPE_STORE),
                    'createAccount' => $this->config->getValue('payment/yuansfer/creditcard_create_account', \Magento\Store\Model\ScopeInterface::SCOPE_STORE),
                    'imageUrl' => $creditcardImage,
                    'imageWidth' => $creditcardWidth
                ]
            ]
        ];
    }
}
