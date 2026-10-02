<?php

/**
 * Copyright © 2009-2026 Amasty. All Rights Reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace Amasty\GxoIntegration\Api;

use Amasty\GxoIntegration\Api\Data\SuccessSyncInterface;
use Amasty\GxoIntegration\Api\Data\SuccessSyncSearchResultsInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Exception\NoSuchEntityException;

interface SuccessSyncRepositoryInterface
{
    /**
     * @param SuccessSyncInterface $entity
     * @return SuccessSyncInterface
     */
    public function save(SuccessSyncInterface $entity): SuccessSyncInterface;

    /**
     * @param int $id
     * @return SuccessSyncInterface
     * @throws NoSuchEntityException
     */
    public function getById(int $id): SuccessSyncInterface;

    /**
     * @param int $orderId
     * @return SuccessSyncInterface
     * @throws NoSuchEntityException
     */
    public function getByOrderId(int $orderId): SuccessSyncInterface;

    /**
     * @param SearchCriteriaInterface $searchCriteria
     * @return SuccessSyncSearchResultsInterface
     */
    public function getList(SearchCriteriaInterface $searchCriteria): SuccessSyncSearchResultsInterface;

    /**
     * @param SuccessSyncInterface $entity
     * @return bool
     */
    public function delete(SuccessSyncInterface $entity): bool;

    /**
     * @param int $id
     * @return bool
     */
    public function deleteById(int $id): bool;
}
