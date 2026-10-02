<?php

/**
 * Copyright © 2009-2026 Amasty. All Rights Reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace Amasty\GxoIntegration\Api;

use Amasty\GxoIntegration\Api\Data\FailedSyncInterface;
use Amasty\GxoIntegration\Api\Data\FailedSyncSearchResultsInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Exception\NoSuchEntityException;

interface FailedSyncRepositoryInterface
{
    /**
     * @param FailedSyncInterface $entity
     * @return FailedSyncInterface
     */
    public function save(FailedSyncInterface $entity): FailedSyncInterface;

    /**
     * @param int $id
     * @return FailedSyncInterface
     * @throws NoSuchEntityException
     */
    public function getById(int $id): FailedSyncInterface;

    /**
     * @param int $orderId
     * @return FailedSyncInterface
     * @throws NoSuchEntityException
     */
    public function getByOrderId(int $orderId): FailedSyncInterface;

    /**
     * @param SearchCriteriaInterface $searchCriteria
     * @return FailedSyncSearchResultsInterface
     */
    public function getList(SearchCriteriaInterface $searchCriteria): FailedSyncSearchResultsInterface;

    /**
     * @param FailedSyncInterface $entity
     * @return bool
     */
    public function delete(FailedSyncInterface $entity): bool;

    /**
     * @param int $id
     * @return bool
     */
    public function deleteById(int $id): bool;
}
