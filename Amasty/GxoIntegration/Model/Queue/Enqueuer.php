<?php

/**
 * Copyright © 2009-2026 Amasty. All Rights Reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace Amasty\GxoIntegration\Model\Queue;

use Amasty\GxoIntegration\Api\QueueManagementInterface;
use Amasty\GxoIntegration\Api\SuccessSyncManagementInterface;
use Amasty\GxoIntegration\Model\Config;

class Enqueuer
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
     * @var SuccessSyncManagementInterface
     */
    private $successSyncManagement;

    public function __construct(
        Config $config,
        QueueManagementInterface $queueManagement,
        SuccessSyncManagementInterface $successSyncManagement
    ) {
        $this->config = $config;
        $this->queueManagement = $queueManagement;
        $this->successSyncManagement = $successSyncManagement;
    }

    /**
     * @param int $orderId
     * @param string $newStatus
     * @param string|null $oldStatus
     * @return void
     */
    public function enqueueIfNeeded(int $orderId, string $newStatus, ?string $oldStatus): void
    {
        if (!$this->config->isEnabled()) {
            return;
        }

        $triggerStatuses = $this->config->getTriggerStatuses();
        if (!$triggerStatuses || !in_array($newStatus, $triggerStatuses, true)) {
            return;
        }

        if ($oldStatus !== null && $oldStatus === $newStatus) {
            return;
        }

        if ($this->successSyncManagement->existsByOrderId($orderId)) {
            return;
        }

        $this->queueManagement->enqueue($orderId, 0);
    }

    /**
     * @param int $orderId
     * @return void
     */
    public function enqueueManual(int $orderId): void
    {
        $this->queueManagement->enqueue($orderId, 0);
    }
}
