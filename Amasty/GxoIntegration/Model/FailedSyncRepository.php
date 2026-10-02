<?php

/**
 * Copyright © 2009-2026 Amasty. All Rights Reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace Amasty\GxoIntegration\Model;

use Amasty\GxoIntegration\Api\Data\FailedSyncInterface;
use Amasty\GxoIntegration\Api\FailedSyncRepositoryInterface;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Exception\NoSuchEntityException;

class FailedSyncRepository implements FailedSyncRepositoryInterface
{
    /**
     * @var ResourceModel\FailedSync
     */
    private $resource;

    /**
     * @var FailedSyncFactory
     */
    private $entityFactory;

    /**
     * @var ResourceModel\FailedSync\CollectionFactory
     */
    private $collectionFactory;

    /**
     * @var FailedSyncSearchResultsFactory
     */
    private $searchResultsFactory;

    /**
     * @var CollectionProcessorInterface
     */
    private $collectionProcessor;

    public function __construct(
        \Amasty\GxoIntegration\Model\ResourceModel\FailedSync $resource,
        \Amasty\GxoIntegration\Model\FailedSyncFactory $entityFactory,
        \Amasty\GxoIntegration\Model\ResourceModel\FailedSync\CollectionFactory $collectionFactory,
        \Amasty\GxoIntegration\Model\FailedSyncSearchResultsFactory $searchResultsFactory,
        CollectionProcessorInterface $collectionProcessor
    ) {
        $this->resource = $resource;
        $this->entityFactory = $entityFactory;
        $this->collectionFactory = $collectionFactory;
        $this->searchResultsFactory = $searchResultsFactory;
        $this->collectionProcessor = $collectionProcessor;
    }

    /**
     * @param FailedSyncInterface $entity
     * @return FailedSyncInterface
     */
    public function save(FailedSyncInterface $entity): FailedSyncInterface
    {
        $this->resource->save($entity);
        return $entity;
    }

    /**
     * @param int $id
     * @return FailedSyncInterface
     * @throws NoSuchEntityException
     */
    public function getById(int $id): FailedSyncInterface
    {
        /** @var FailedSync $model */
        $model = $this->entityFactory->create();
        $this->resource->load($model, $id);
        if (!$model->getId()) {
            throw new NoSuchEntityException(__('Failed record does not exist.'));
        }
        return $model;
    }

    /**
     * @param int $orderId
     * @return FailedSyncInterface
     * @throws NoSuchEntityException
     */
    public function getByOrderId(int $orderId): FailedSyncInterface
    {
        /** @var FailedSync $model */
        $model = $this->entityFactory->create();
        $this->resource->load($model, $orderId, FailedSyncInterface::ORDER_ID);
        if (!$model->getId()) {
            throw new NoSuchEntityException(__('Failed record does not exist.'));
        }
        return $model;
    }

    /**
     * @param SearchCriteriaInterface $searchCriteria
     * @return \Amasty\GxoIntegration\Api\Data\FailedSyncSearchResultsInterface
     */
    public function getList(SearchCriteriaInterface $searchCriteria): \Amasty\GxoIntegration\Api\Data\FailedSyncSearchResultsInterface
    {
        $collection = $this->collectionFactory->create();
        $this->collectionProcessor->process($searchCriteria, $collection);

        /** @var \Amasty\GxoIntegration\Api\Data\FailedSyncSearchResultsInterface $searchResults */
        $searchResults = $this->searchResultsFactory->create();
        $searchResults->setSearchCriteria($searchCriteria);
        $searchResults->setTotalCount((int)$collection->getSize());
        $searchResults->setItems($collection->getItems());

        return $searchResults;
    }

    /**
     * @param FailedSyncInterface $entity
     * @return bool
     */
    public function delete(FailedSyncInterface $entity): bool
    {
        $this->resource->delete($entity);
        return true;
    }

    /**
     * @param int $id
     * @return bool
     */
    public function deleteById(int $id): bool
    {
        return $this->delete($this->getById($id));
    }
}
