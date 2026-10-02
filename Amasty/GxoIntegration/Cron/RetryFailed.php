<?php

/**
 * Copyright © 2009-2026 Amasty. All Rights Reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace Amasty\GxoIntegration\Cron;

use Amasty\GxoIntegration\Api\FailedSyncManagementInterface;
use Amasty\GxoIntegration\Api\QueueManagementInterface;
use Amasty\GxoIntegration\Model\Config;

class RetryFailed
{
    /**
     * @var Config
     */
    private $config;

    /**
     * @var FailedSyncManagementInterface
     */
    private $failedSyncManagement;

    /**
     * @var QueueManagementInterface
     */
    private $queueManagement;

    public function __construct(
        Config $config,
        FailedSyncManagementInterface $failedSyncManagement,
        QueueManagementInterface $queueManagement
    ) {
        $this->config = $config;
        $this->failedSyncManagement = $failedSyncManagement;
        $this->queueManagement = $queueManagement;
    }

    /**
     * @return void
     */
    public function execute(): void
    {
        if (!$this->config->isEnabled()) {
            return;
        }

        $batchSize = $this->config->getBatchSize();
        $maxAttempts = $this->config->getMaxAttempts();

        $rows = $this->failedSyncManagement->getRetryBatch($batchSize, $maxAttempts);

        foreach ($rows as $row) {
            $orderId = (int)$row->getOrderId();
            if (!$orderId) {
                continue;
            }

            $this->queueManagement->enqueue($orderId, (int)$row->getAttempts());
        }
    }
}
