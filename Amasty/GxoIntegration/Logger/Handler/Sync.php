<?php

/**
 * Copyright © 2009-2025 Amasty. All Rights Reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace Amasty\GxoIntegration\Logger\Handler;

use Magento\Framework\Logger\Handler\Base;
use Monolog\Logger;

class Sync extends Base
{
    protected $loggerType = Logger::INFO;

    protected $fileName = '/var/log/gxo-integration/sync.log';
}
