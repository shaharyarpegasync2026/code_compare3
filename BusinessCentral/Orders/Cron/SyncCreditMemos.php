<?php

/**
 * Copyright © 2009-2025 Amasty. All Rights Reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace BusinessCentral\Orders\Cron;

use BusinessCentral\Orders\Model\Adapter;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Psr\Log\LoggerInterface;

class SyncCreditMemos
{
    private $adapter;
    private $config;
    private $logger;

    public function __construct(
        Adapter $adapter,
        ScopeConfigInterface $config,
        LoggerInterface $logger
    ) {
        $this->adapter = $adapter;
        $this->config = $config;
        $this->logger = $logger;
    }

    public function execute(): void
    {
        if (!(bool)$this->config->getValue('orders/sync_credit_memos/enabled')) {
            return;
        }

        try {
            $this->adapter->syncCreditMemos();
        } catch (\Throwable $e) {
            $this->logger->error('Credit memo sync failed', [
                'error' => $e->getMessage(),
            ]);
        }
    }
}


