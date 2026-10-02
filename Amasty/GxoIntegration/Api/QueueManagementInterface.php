<?php

/**
 * Copyright © 2009-2026 Amasty. All Rights Reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace Amasty\GxoIntegration\Api;

use Amasty\GxoIntegration\Api\Data\QueueInterface;

interface QueueManagementInterface
{
    /**
     * @param int $orderId
     * @param int $attempts
     * @return void
     */
    public function enqueue(int $orderId, int $attempts = 0): void;

    /**
     * @param int $batchSize
     * @return QueueInterface[]
     */
    public function getPendingBatch(int $batchSize): array;

    /**
     * @param int $entityId
     * @return bool
     */
    public function markProcessing(int $entityId): bool;

    /**
     * @param int $orderId
     * @return QueueInterface|null
     */
    public function getByOrderId(int $orderId): ?QueueInterface;

    /**
     * @param int $orderId
     * @return void
     */
    public function deleteByOrderId(int $orderId): void;
}
