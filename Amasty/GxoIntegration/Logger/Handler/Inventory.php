<?php

/**
 * Copyright © 2009-2026 Amasty. All Rights Reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace Amasty\GxoIntegration\Logger\Handler;

use Magento\Framework\Logger\Handler\Base;
use Monolog\Logger;

class Inventory extends Base
{
    protected $loggerType = Logger::INFO;

    protected $fileName = '/var/log/gxo-integration/inventory.log';
}
