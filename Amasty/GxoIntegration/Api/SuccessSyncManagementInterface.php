<?php

/**
 * Copyright © 2009-2026 Amasty. All Rights Reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace Amasty\GxoIntegration\Api;

use Amasty\GxoIntegration\Api\Data\SuccessSyncInterface;

interface SuccessSyncManagementInterface
{
    /**
     * @param int $orderId
     * @return bool
     */
    public function existsByOrderId(int $orderId): bool;

    /**
     * @param int $orderId
     * @return SuccessSyncInterface|null
     */
    public function getByOrderId(int $orderId): ?SuccessSyncInterface;

    /**
     * @param int $orderId
     * @param string|null $reference
     * @param string|null $requestPayload
     * @param string|null $responsePayload
     * @param string $syncedAt
     * @return void
     */
    public function upsertSuccess(
        int $orderId,
        ?string $reference,
        ?string $requestPayload,
        ?string $responsePayload,
        string $syncedAt
    ): void;
}
