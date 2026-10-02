<?php

/**
 * Copyright © 2009-2026 Amasty. All Rights Reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace Amasty\GxoIntegration\Model\SuccessSync;

use Amasty\GxoIntegration\Api\SuccessSyncManagementInterface;
use Amasty\GxoIntegration\Model\SuccessSyncFactory;
use Amasty\GxoIntegration\Model\ResourceModel\SuccessSync as SuccessSyncResource;

class SuccessSyncManagement implements SuccessSyncManagementInterface
{
    /**
     * @var SuccessSyncResource
     */
    private $successSyncResource;

    /**
     * @var SuccessSyncFactory
     */
    private $successSyncFactory;

    public function __construct(
        SuccessSyncResource $successSyncResource,
        SuccessSyncFactory $successSyncFactory
    ) {
        $this->successSyncResource = $successSyncResource;
        $this->successSyncFactory = $successSyncFactory;
    }

    /**
     * @param int $orderId
     * @return bool
     */
    public function existsByOrderId(int $orderId): bool
    {
        return $this->successSyncResource->existsByOrderId($orderId);
    }

    /**
     * @param int $orderId
     * @return \Amasty\GxoIntegration\Api\Data\SuccessSyncInterface|null
     */
    public function getByOrderId(int $orderId): ?\Amasty\GxoIntegration\Api\Data\SuccessSyncInterface
    {
        /** @var \Amasty\GxoIntegration\Model\SuccessSync $model */
        $model = $this->successSyncFactory->create();
        $this->successSyncResource->load($model, $orderId, \Amasty\GxoIntegration\Api\Data\SuccessSyncInterface::ORDER_ID);
        return $model->getId() ? $model : null;
    }

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
    ): void {
        $this->successSyncResource->upsertSuccess($orderId, $reference, $requestPayload, $responsePayload, $syncedAt);
    }
}
