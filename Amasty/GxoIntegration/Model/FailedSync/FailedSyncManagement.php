<?php

/**
 * Copyright © 2009-2026 Amasty. All Rights Reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace Amasty\GxoIntegration\Model\FailedSync;

use Amasty\GxoIntegration\Api\FailedSyncManagementInterface;
use Amasty\GxoIntegration\Model\FailedSyncFactory;
use Amasty\GxoIntegration\Model\ResourceModel\FailedSync as FailedSyncResource;

class FailedSyncManagement implements FailedSyncManagementInterface
{
    /**
     * @var FailedSyncResource
     */
    private $failedSyncResource;

    /**
     * @var FailedSyncFactory
     */
    private $failedSyncFactory;

    public function __construct(
        FailedSyncResource $failedSyncResource,
        FailedSyncFactory $failedSyncFactory
    ) {
        $this->failedSyncResource = $failedSyncResource;
        $this->failedSyncFactory = $failedSyncFactory;
    }

    /**
     * @param int $entityId
     * @return int
     */
    public function getOrderIdByEntityId(int $entityId): int
    {
        return $this->failedSyncResource->getOrderIdByEntityId($entityId);
    }

    /**
     * @param int $orderId
     * @return int
     */
    public function getAttemptsByOrderId(int $orderId): int
    {
        return $this->failedSyncResource->getAttemptsByOrderId($orderId);
    }

    /**
     * @param int $orderId
     * @return \Amasty\GxoIntegration\Api\Data\FailedSyncInterface|null
     */
    public function getByOrderId(int $orderId): ?\Amasty\GxoIntegration\Api\Data\FailedSyncInterface
    {
        /** @var \Amasty\GxoIntegration\Model\FailedSync $model */
        $model = $this->failedSyncFactory->create();
        $this->failedSyncResource->load($model, $orderId, \Amasty\GxoIntegration\Api\Data\FailedSyncInterface::ORDER_ID);
        return $model->getId() ? $model : null;
    }

    /**
     * @param int $batchSize
     * @param int $maxAttempts
     * @return \Amasty\GxoIntegration\Api\Data\FailedSyncInterface[]
     */
    public function getRetryBatch(int $batchSize, int $maxAttempts): array
    {
        $items = [];
        foreach ($this->failedSyncResource->getRetryBatch($batchSize, $maxAttempts) as $row) {
            /** @var \Amasty\GxoIntegration\Model\FailedSync $model */
            $model = $this->failedSyncFactory->create();
            $model->setData($row);
            $items[] = $model;
        }

        return $items;
    }

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
    ): void {
        $this->failedSyncResource->upsertFailed($orderId, $attempts, $nextRetryAt, $error, $requestPayload, $responsePayload);
    }

    /**
     * @param int $orderId
     * @return void
     */
    public function deleteByOrderId(int $orderId): void
    {
        $this->failedSyncResource->deleteByOrderId($orderId);
    }
}
