<?php
namespace Pockyt\All\Included\Error;

use Pockyt\All\Included\Error\ErrorBase;

class Error_Api extends \Pockyt\All\Included\Error\ErrorBase
{
        /**
     * @var \Psr\Log\LoggerInterface
     */
    // protected $logger;

    public function __construct(
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->logger = $logger;
    }
}
