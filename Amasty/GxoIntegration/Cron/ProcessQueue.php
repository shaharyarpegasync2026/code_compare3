<?php

/**
 * Copyright © 2009-2026 Amasty. All Rights Reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace Amasty\GxoIntegration\Cron;

use Amasty\GxoIntegration\Api\QueueManagementInterface;
use Amasty\GxoIntegration\Model\Config;
use Amasty\GxoIntegration\Model\Sync\OrderSyncService;
use Amasty\GxoIntegration\Logger\Logger;

class ProcessQueue
{
    /**
     * @var Config
     */
    private $config;

    /**
     * @var QueueManagementInterface
     */
    private $queueManagement;

    /**
     * @var OrderSyncService
     */
    private $orderSyncService;

    /**
     * @var Logger
     */
    private $logger;

    public function __construct(
        Config $config,
        QueueManagementInterface $queueManagement,
        OrderSyncService $orderSyncService,
        Logger $logger
    ) {
        $this->config = $config;
        $this->queueManagement = $queueManagement;
        $this->orderSyncService = $orderSyncService;
        $this->logger = $logger;
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
        $rows = $this->queueManagement->getPendingBatch($batchSize);

        foreach ($rows as $row) {
            $entityId = (int)$row->getId();
            $orderId = (int)$row->getOrderId();
            if (!$entityId || !$orderId) {
                continue;
            }

            if (!$this->queueManagement->markProcessing($entityId)) {
                continue;
            }

            try {
                $this->orderSyncService->sync($orderId);
            } catch (\Throwable $e) {
                $this->logger->error('GXO queue processing error', [
                    'order_id' => $orderId,
                    'error' => $e->getMessage()
                ]);
            }
        }
    }
}
