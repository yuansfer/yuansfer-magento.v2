<?php
namespace Pockyt\All\Included;

use Exception;
use Pockyt\All\Included\CurlClient;
use Pockyt\All\Included\MobileDetect;
use Pockyt\All\Included\Error\Error_Api;

class Requestor
{
    private $debug = false;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    protected $detect;

    public function __construct(
        \Psr\Log\LoggerInterface $logger
    )
    {
        $this->logger = $logger;
        $this->detect = new MobileDetect();
    }

    public function refund($merchantNo, $storeNo, $token, $payment, $amount)
    {
        $transactionId = $payment->getParentTransactionId();
        $order = $payment->getOrder();

        $httpClient = CurlClient::instance();
        $url = 'https://mapi.yuansfer.com/app-data-search/v3/refund';
        if ($this->debug) {
            $url = 'https://mapi.yuansfer.yunkeguan.com/app-data-search/v3/refund';
        }

        $params = array(
            'merchantNo' => $merchantNo,
            'storeNo' => $storeNo,
            'refundAmount' => $amount,
            'currency' => $order->getOrderCurrencyCode(),
            'settleCurrency' => $order->getData('pockyt_settle_currency')?$order->getData('pockyt_settle_currency') : 'USD',
            'reference' => $transactionId,
        );
        $params = array_filter($params);

        $params = $this->addSign($params, $token);

        $this->log('send to ' . $url . ' with params:' . print_r($params, true));

        [$rbody, $rcode, $rheaders] = $httpClient->request('post', $url, [], $params, false);

        $resp = $this->_interpretResponse($rbody, $rcode, $rheaders, $params);

        $this->log('response: ' . print_r($resp, true));

        if (
            !isset($resp['ret_code'])
        ) {
            throw new \ErrorException('System Error!');
        }

        if($resp['ret_code'] !== '000100') {
            $this->log(implode(', ', $resp));
            throw new \ErrorException('System Error:'. $resp['ret_code'] . ' - ' . $resp['ret_msg']);
        }

        return $resp;
    }

    private function _interpretResponse($rbody, $rcode, $rheaders, $params)
    {
        try {
            $resp = json_decode($rbody, true);
        } catch (Exception $e) {
            $msg = "Invalid response body from API: $rbody "
                . "(HTTP response code was $rcode)";
          //  throw new Error_Api($msg, $rcode, $rbody);
        }

        if ($rcode < 200 || $rcode >= 300) {
            $this->handleApiError($rbody, $rcode, $rheaders, $resp, $params);
        }

        return $resp;
    }

    public function handleApiError($rbody, $rcode, $rheaders, $resp, $param)
    {
        if (!is_array($resp) || !isset($resp['error'])) {
            $msg = "Invalid response object from API: $rbody "
                . "(HTTP response code was $rcode)";
        } else {
            $msg = isset($resp['message']) ? $resp['message'] : null;
        }

       // throw new Error_Api($msg, $param, $rcode, $rbody, $resp, $rheaders);

    }

    public function setDebug($debug)
    {
        $this->debug = $debug;
    }

    public function getDebug()
    {
        return $this->debug;
    }

    protected function log($msg)
    {
        $this->logger->debug('Requestor - ' . $msg);
    }

    public function securePay($merchantNo, $storeNo, $token, $vendor, $order, $ipn, $callback, $customerId = null, $alipaySettleCurrencyFroCNY = null)
    {
        $httpClient = CurlClient::instance();
        $url = 'https://mapi.yuansfer.com/online/v3/secure-pay';
        if ($this->debug) {
            $url = 'https://mapi.yuansfer.yunkeguan.com/online/v3/secure-pay';
        }
        
        $product = '';
        foreach ($order->getAllItems() as $item) {
            $product .= $item->getName() . '...';
            break;
        }

        $amount = $order->getGrandTotal();

        $currency = $order->getOrderCurrencyCode();
        $settleCurrency = 'USD';
        $terminal = $this->detect->isMobile() ? 'WAP' : 'ONLINE';

        $osType = null;
        if ($terminal === 'WAP') {
            $osType = 'ANDROID';
            if ($this->detect->is('iOS') || $this->detect->is('iPadOS')) {
                $osType = 'IOS';
            }
        }

        $creditType = null;
        if ($vendor === 'creditcard') {
            if ($currency !== 'USD') {
                throw new \ErrorException('Credit Card only support "USD" for currency');
            }
            $creditType = 'normal';
        } elseif ($vendor === 'unionpay') {
            if (!in_array($currency, array('USD', 'CNY'), true)) {
                throw new \ErrorException('Union Pay only support "USD", "CNY" for currency');
            }
        } elseif ($vendor === 'wechatpay') {
            if (!in_array($currency, array('USD', 'CNY'), true)) {
                throw new \ErrorException('WeChat Pay only support "USD", "CNY" for currency');
            }

            if ($terminal === 'WAP' && !$this->detect->is('WeChat')) {
                $terminal = 'MWEB';
            }
        } elseif ($vendor === 'alipay') {
            if (!in_array($currency, array('USD', 'CNY', 'PHP', 'IDR', 'KRW', 'HKD', 'GBP'), true)) {
                throw new \ErrorException('Alipay only support “USD“, “CNY“, “PHP“, “IDR“, “KRW“, “HKD“, “GBP“ for currency');
            }

            switch ($currency) {
                case 'PHP':
                    if ($amount < 1) {
                        throw new \ErrorException('The minimum value is 1PHP');
                    }
                    break;

                case 'IDR':
                    if ($amount < 300) {
                        throw new \ErrorException('The minimum value is 300IDR');
                    }
                    break;

                case 'KRW':
                    if ($amount < 50) {
                        throw new \ErrorException('The minimum value is 50KRW');
                    }
                    break;

                case 'HKD':
                    if ($amount < 0.1) {
                        throw new \ErrorException('The minimum value is 0.1HKD');
                    }
                    break;

                case 'GBP':
                    $settleCurrency = 'GBP';
                    break;

                case 'CNY':
                    if (in_array($alipaySettleCurrencyFroCNY, array('USD', 'GBP'), true)) {
                        $settleCurrency = $alipaySettleCurrencyFroCNY;
                    }
                    break;
            }
        } elseif (in_array($vendor, array('paypal', 'venmo'), true)) {
            if ($currency !== 'USD') {
                throw new \ErrorException(ucfirst($vendor) . ' only support "USD" for currency');
            }
        }

        $params = array(
            'merchantNo' => $merchantNo,
            'storeNo' => $storeNo,
            'amount' => $amount,
            'currency' => $currency,
            'settleCurrency' => $settleCurrency,
            'vendor' => $vendor,
            'ipnUrl' => $ipn,
            'callbackUrl' => $callback,
            'reference' => $this->getReferenceCode($order->getIncrementId()),
            'terminal' => $terminal,
            'description' => $product,
            'note' => sprintf('#%s(%s)', $order->getRealOrderId(), $order->getCustomerEmail()),
        );

        if($osType !== null) {
            $params['osType'] = $osType;
        }

        if ($creditType !== null) {
            $params['creditType'] = $creditType;
        }

        if ($customerId !== null) {
            $params['customerNo'] = $customerId;
        }

        $params = $this->addSign($params, $token);

        $this->log('send to ' . $url . ' with params:' . print_r($params, true));

        [$rbody, $rcode, $rheaders] = $httpClient->request('post', $url, [], $params, false);

        $this->log($rbody);

        $resp = $this->_interpretResponse($rbody, $rcode, $rheaders, $params);

        if (
            !isset($resp['ret_code'])
        ) {
            throw new \ErrorException('System Error!');
        }

        if($resp['ret_code'] !== '000100') {
            $this->log(implode(', ', $resp));
            throw new \ErrorException('System Error:'. $resp['ret_code'] . ' - ' . $resp['ret_msg']);
        }

        return [
            'url' => $resp['result']['cashierUrl'],
            'settleCurrency' => $resp['result']['settleCurrency'],
        ];
    }

    protected function getReferenceCode($order_id)
    {
        return $order_id . 'at' . time();
    }

    /**
     * @param array $params
     * @param string $token
     *
     * @return mixed
     */
    protected function addSign($params, $token)
    {
        unset($params['verifySign']);

        ksort($params, SORT_STRING);
        $str = '';
        foreach ($params as $k => $v) {
            if(is_array($v)){
                $v = implode(" ", $v);
            }
            $str .= $k . '=' . $v . '&';
        }
       // $sig = sha256($str . sha256($token));
        $sig = hash('md5', $str . hash('md5', $token));

        $params['verifySign'] = $sig;

        return $params;
    }

    public function customer($merchantNo, $storeNo, $token, $order)
    {
        $info = $this->customerInfo($order);
        $old = $this->getCustomer($merchantNo, $storeNo, $token, $order->getCustomerId());
        if ($old === null) {
            return $this->createCustomer($merchantNo, $storeNo, $token, $info);
        }

        $update = false;
        foreach ($info as $k => $v) {
            if ($v != $old[$k]) {
                $update = true;
                break;
            }
        }

        if ($update) {
            return $this->updateCustomer($merchantNo, $storeNo, $token, $info);
        }

        return $old['customerNo'];
    }

    private function updateCustomer($merchantNo, $storeNo, $token, $info)
    {
        $httpClient = CurlClient::instance();
        $url = 'https://mapi.yuansfer.com/creditpay/v2/customer/edit';
        if ($this->debug) {
            $url = 'https://mapi.yuansfer.yunkeguan.com/creditpay/v2/customer/edit';
        }

        $params = array(
            'merchantNo' => $merchantNo,
            'storeNo' => $storeNo,
        );
        $params += $info;
        $params['street'] = $params['street'][0];

        $params = $this->addSign($params, $token);

        $this->log('send to ' . $url . ' with params:' . print_r($params, true));

        [$rbody, $rcode, $rheaders] = $httpClient->request('post', $url, [], array_filter($params), false);

        $this->log($rbody);

        $resp = $this->_interpretResponse($rbody, $rcode, $rheaders, $params);

        if (
            !isset($resp['ret_code'])
        ) {
            throw new \ErrorException('System Error!');
        }

        if($resp['ret_code'] !== '000100') {
            $this->log(implode(', ', $resp));
            throw new \ErrorException('System Error:'. $resp['ret_code'] . ' - ' . $resp['ret_msg']);
        }

        return $resp['customerInfo']['customerNo'];
    }

    private function getCustomer($merchantNo, $storeNo, $token, $id)
    {
        $httpClient = CurlClient::instance();
        $url = 'https://mapi.yuansfer.com/creditpay/v2/customer/detail';
        if ($this->debug) {
            $url = 'https://mapi.yuansfer.yunkeguan.com/creditpay/v2/customer/detail';
        }

        $params = array(
            'merchantNo' => $merchantNo,
            'storeNo' => $storeNo,
            'customerCode' => $id
        );

        $params = $this->addSign($params, $token);

        $this->log('send to ' . $url . ' with params:' . print_r($params, true));

        [$rbody, $rcode, $rheaders] = $httpClient->request('post', $url, [], $params, false);

        $this->log($rbody);

        $resp = $this->_interpretResponse($rbody, $rcode, $rheaders, $params);

        if (
            !isset($resp['ret_code']) ||
            $resp['ret_code'] !== '000100' ||
            empty($resp['customerInfo'])
        ) {
            return null;
        }

        return $resp['customerInfo'];
    }

    private function createCustomer($merchantNo, $storeNo, $token, $info)
    {
        $httpClient = CurlClient::instance();
        $url = 'https://mapi.yuansfer.com/creditpay/v2/customer/add';
        if ($this->debug) {
            $url = 'https://mapi.yuansfer.yunkeguan.com/creditpay/v2/customer/add';
        }

        $params = array(
            'merchantNo' => $merchantNo,
            'storeNo' => $storeNo,
            'groupCode' => 'HPP',
        );
        $params += $info;
        $params['street'] = $params['street'][0];

        $params = $this->addSign($params, $token);

        $this->log('send to ' . $url . ' with params:' . print_r($params, true));

        [$rbody, $rcode, $rheaders] = $httpClient->request('post', $url, [], array_filter($params), false);

        $this->log($rbody);

        $resp = $this->_interpretResponse($rbody, $rcode, $rheaders, $params);

        if (
            !isset($resp['ret_code'])
        ) {
            throw new \ErrorException('System Error!');
        }

        if($resp['ret_code'] !== '000100') {
            $this->log(implode(', ', $resp));
            throw new \ErrorException('System Error:'. $resp['ret_code'] . ' - ' . $resp['ret_msg']);
        }

        return $resp['customerInfo']['customerNo'];
    }

    private function customerInfo($order)
    {
        $address = $order->getBillingAddress();

        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $country = $objectManager->create('\Magento\Directory\Model\Country')->load(
            $address->getCountryId()
        )->getName();

        return array(
            'firstName' => $address->getFirstname(),
            'lastName' => $address->getLastname(),
            'customerCode' => $address->getCustomerId(),
            'street' => $address->getStreet(),
            'city' => $address->getCity(),
            'state' => $address->getRegion(),
            'country' => $country,
            'zip' => $address->getPostcode(),
            'email' => $address->getEmail(),
            'phone' => $address->getTelephone(),
            'company' => $address->getCompany(),
        );
    }
}
