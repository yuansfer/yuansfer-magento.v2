<?php
namespace Pockyt\All\Included;

use Exception;
use Pockyt\All\Included\Error\Error_Api;

class CurlClient
{
    private static $instance;

    public static $verifySslCerts = false;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    // protected $logger;

    // public function __construct(
    //     \Psr\Log\LoggerInterface $logger
    // ) {
    //     $this->logger = $logger;
    // }

    public static function instance()
    {
        if (!self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function request($method, $absUrl, $headers, $params, $hasFile)
    {
        $curl = curl_init();
        $method = strtolower($method);
        $opts = array();
        if ($method === 'get') {
            if ($hasFile) {
                throw new \ErrorException("Issuing a GET request with a file parameter");
            }
            $opts[CURLOPT_HTTPGET] = 1;
            if (count($params) > 0) {
                $encoded = self::encode($params);
                $absUrl = "$absUrl?$encoded";
            }
        } elseif ($method === 'post') {
            $opts[CURLOPT_POST] = 1;
            $opts[CURLOPT_POSTFIELDS] = $hasFile ? $params : self::encode($params);
        } elseif ($method === 'delete') {
            $opts[CURLOPT_CUSTOMREQUEST] = 'DELETE';
            if (count($params) > 0) {
                $encoded = self::encode($params);
                $absUrl = "$absUrl?$encoded";
            }
        } else {
            throw new \ErrorException("Unrecognized method $method");
        }

        // Create a callback to capture HTTP headers for the response
        $rheaders = array();
        $headerCallback = function ($curl, $header_line) use (&$rheaders) {
            // Ignore the HTTP request line (HTTP/1.1 200 OK)
            if (strpos($header_line, ":") === false) {
                return strlen($header_line);
            }
            list($key, $value) = explode(":", trim($header_line), 2);
            $rheaders[trim($key)] = trim($value);

            return strlen($header_line);
        };

        $absUrl = self::utf8($absUrl);
        $opts[CURLOPT_URL] = $absUrl;
        $opts[CURLOPT_RETURNTRANSFER] = true;
        $opts[CURLOPT_CONNECTTIMEOUT] = 30;
        $opts[CURLOPT_TIMEOUT] = 80;
        $opts[CURLOPT_RETURNTRANSFER] = true;
        $opts[CURLOPT_HEADERFUNCTION] = $headerCallback;
        $opts[CURLOPT_HTTPHEADER] = $headers;


        if (!self::$verifySslCerts) {
            $opts[CURLOPT_SSL_VERIFYPEER] = false;
        }


        curl_setopt_array($curl, $opts);


        //print_r($opts);

        $rbody = curl_exec($curl);


        if (!defined('CURLE_SSL_CACERT_BADFILE')) {
            define('CURLE_SSL_CACERT_BADFILE', 77);  // constant not defined in PHP
        }


        if ($rbody === false) {
            $errno = curl_errno($curl);
            $message = curl_error($curl);
            curl_close($curl);
            $this->handleCurlError($absUrl, $errno, $message);
        }

        $rcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        return array($rbody, $rcode, $rheaders);
    }


    private function handleCurlError($url, $errno, $message)
    {
        switch ($errno) {
            case CURLE_COULDNT_CONNECT:
            case CURLE_COULDNT_RESOLVE_HOST:
            case CURLE_OPERATION_TIMEOUTED:
                $msg = "Could not connect to Pockyt ($url).  Please check your "
                    . "internet connection and try again.  If this problem persists, "
                    . "you should check Pockyt's service status at "
                    . "https://www.pockyt.com, or";
                break;
            case CURLE_SSL_CACERT:
            case CURLE_SSL_PEER_CERTIFICATE:
                $msg = "Could not verify Pockyt's SSL certificate.  Please make sure "
                    . "that your network is not intercepting certificates.  "
                    . "(Try going to $url in your browser.)  "
                    . "If this problem persists,";
                break;
            default:
                $msg = "Unexpected error communicating with Pockyt.  "
                    . "If this problem persists,";
        }
        $msg .= " let us know at support@pockyt.com.";

        $msg .= "\n\n(Network error [errno $errno]: $message)";
        throw new Exception($msg);
    }

    public static function utf8($value)
    {
        if (is_string($value) && mb_detect_encoding($value, "UTF-8", true) !== "UTF-8") {
            return utf8_encode($value);
        }

        return $value;
    }

    public static function encode($arr, $prefix = null)
    {
        if (!is_array($arr)) {
            return $arr;
        }

        $r = array();
        foreach ($arr as $k => $v) {
            if ($v === null) {
                continue;
            }

            if ($prefix && $k && !is_int($k)) {
                $k = $prefix . "[" . $k . "]";
            } elseif ($prefix) {
                $k = $prefix . "[]";
            }

            if (is_array($v)) {
                $enc = self::encode($v, $k);
                if ($enc) {
                    $r[] = $enc;
                }
            } else {
                $r[] = urlencode($k) . "=" . urlencode($v);
            }
        }

        return implode("&", $r);
    }


}
