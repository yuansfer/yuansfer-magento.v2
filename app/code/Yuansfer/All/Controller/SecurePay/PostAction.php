<?php
namespace Yuansfer\All\Controller\SecurePay;

if (interface_exists('\Magento\Framework\App\CsrfAwareActionInterface')) {
    abstract class PostAction extends \Magento\Framework\App\Action\Action implements \Magento\Framework\App\CsrfAwareActionInterface
    {
        public function createCsrfValidationException($request)
        {
            return null;
        }

        public function validateForCsrf($request)
        {
            return true;
        }
    }
} else {
    abstract class PostAction extends \Magento\Framework\App\Action\Action
    {

    }
}
