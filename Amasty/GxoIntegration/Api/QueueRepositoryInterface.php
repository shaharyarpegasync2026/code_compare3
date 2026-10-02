<?php

/**
 * Copyright © 2009-2026 Amasty. All Rights Reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace Amasty\GxoIntegration\Api;

use Amasty\GxoIntegration\Api\Data\QueueInterface;
use Amasty\GxoIntegration\Api\Data\QueueSearchResultsInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Exception\NoSuchEntityException;

interface QueueRepositoryInterface
{
    /**
     * @param QueueInterface $entity
     * @return QueueInterface
     */
    public function save(QueueInterface $entity): QueueInterface;

    /**
     * @param int $id
     * @return QueueInterface
     * @throws NoSuchEntityException
     */
    public function getById(int $id): QueueInterface;

    /**
     * @param int $orderId
     * @return QueueInterface
     * @throws NoSuchEntityException
     */
    public function getByOrderId(int $orderId): QueueInterface;

    /**
     * @param SearchCriteriaInterface $searchCriteria
     * @return QueueSearchResultsInterface
     */
    public function getList(SearchCriteriaInterface $searchCriteria): QueueSearchResultsInterface;

    /**
     * @param QueueInterface $entity
     * @return bool
     */
    public function delete(QueueInterface $entity): bool;

    /**
     * @param int $id
     * @return bool
     */
    public function deleteById(int $id): bool;
}
