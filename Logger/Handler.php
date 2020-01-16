<?php

namespace Emakina\Cobai\Logger;

use Magento\Framework\Logger\Handler\Base;
use Monolog\Logger;

/**
 * Class Handler
 */
class Handler extends Base
{
    /**
     * Logging level
     * @var int
     */
    protected $loggerType = Logger::ERROR;

    /**
     * File name
     * @var string
     */
    protected $fileName = '/var/log/cobai.log';
}
