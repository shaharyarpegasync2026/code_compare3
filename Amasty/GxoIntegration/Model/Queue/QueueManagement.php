<?php

/**
 * Copyright © 2009-2026 Amasty. All Rights Reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace Amasty\GxoIntegration\Model\Queue;

use Amasty\GxoIntegration\Api\QueueManagementInterface;
use Amasty\GxoIntegration\Model\QueueFactory;
use Amasty\GxoIntegration\Model\ResourceModel\Queue as QueueResource;

class QueueManagement implements QueueManagementInterface
{
    /**
     * @var QueueResource
     */
    private $queueResource;

    /**
     * @var QueueFactory
     */
    private $queueFactory;

    public function __construct(
        QueueResource $queueResource,
        QueueFactory $queueFactory
    ) {
        $this->queueResource = $queueResource;
        $this->queueFactory = $queueFactory;
    }

    /**
     * @param int $orderId
     * @param int $attempts
     * @return void
     */
    public function enqueue(int $orderId, int $attempts = 0): void
    {
        $this->queueResource->enqueue($orderId, $attempts);
    }

    /**
     * @param int $batchSize
     * @return \Amasty\GxoIntegration\Api\Data\QueueInterface[]
     */
    public function getPendingBatch(int $batchSize): array
    {
        $items = [];
        foreach ($this->queueResource->getPendingBatch($batchSize) as $row) {
            /** @var \Amasty\GxoIntegration\Model\Queue $model */
            $model = $this->queueFactory->create();
            $model->setData($row);
            $items[] = $model;
        }

        return $items;
    }

    /**
     * @param int $entityId
     * @return bool
     */
    public function markProcessing(int $entityId): bool
    {
        return $this->queueResource->markProcessing($entityId);
    }

    /**
     * @param int $orderId
     * @return \Amasty\GxoIntegration\Api\Data\QueueInterface|null
     */
    public function getByOrderId(int $orderId): ?\Amasty\GxoIntegration\Api\Data\QueueInterface
    {
        /** @var \Amasty\GxoIntegration\Model\Queue $model */
        $model = $this->queueFactory->create();
        $this->queueResource->load($model, $orderId, \Amasty\GxoIntegration\Api\Data\QueueInterface::ORDER_ID);
        return $model->getId() ? $model : null;
    }

    /**
     * @param int $orderId
     * @return void
     */
    public function deleteByOrderId(int $orderId): void
    {
        $this->queueResource->deleteByOrderId($orderId);
    }
}
