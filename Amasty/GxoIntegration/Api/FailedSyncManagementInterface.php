<?php

/**
 * Copyright © 2009-2026 Amasty. All Rights Reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace Amasty\GxoIntegration\Api;

use Amasty\GxoIntegration\Api\Data\FailedSyncInterface;

interface FailedSyncManagementInterface
{
    /**
     * @param int $entityId
     * @return int
     */
    public function getOrderIdByEntityId(int $entityId): int;

    /**
     * @param int $orderId
     * @return int
     */
    public function getAttemptsByOrderId(int $orderId): int;

    /**
     * @param int $orderId
     * @return FailedSyncInterface|null
     */
    public function getByOrderId(int $orderId): ?FailedSyncInterface;

    /**
     * @param int $batchSize
     * @param int $maxAttempts
     * @return FailedSyncInterface[]
     */
    public function getRetryBatch(int $batchSize, int $maxAttempts): array;

    /**
     * @param int $orderId
     * @param int $attempts
     * @param string|null $nextRetryAt
     * @param string|null $error
     * @param string|null $requestPayload
     * @param string|null $responsePayload
     * @return void
     */
    public function upsertFailed(
        int $orderId,
        int $attempts,
        ?string $nextRetryAt,
        ?string $error,
        ?string $requestPayload,
        ?string $responsePayload
    ): void;

    /**
     * @param int $orderId
     * @return void
     */
    public function deleteByOrderId(int $orderId): void;
}

