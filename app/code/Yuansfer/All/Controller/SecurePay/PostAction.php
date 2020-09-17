<?php
namespace Yuansfer\All\Controller\SecurePay;

if (interface_exists('\Magento\Framework\App\CsrfAwareActionInterface')) {
    require __DIR__ . '/PostAction.23.php';
} else {
    abstract class PostAction extends \Magento\Framework\App\Action\Action
    {

    }
}
