<?php

/**
 * Copyright © 2009-2026 Amasty. All Rights Reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace Amasty\GxoIntegration\Model;

use Amasty\GxoIntegration\Api\Data\SuccessSyncInterface;
use Amasty\GxoIntegration\Api\SuccessSyncRepositoryInterface;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Exception\NoSuchEntityException;

class SuccessSyncRepository implements SuccessSyncRepositoryInterface
{
    /**
     * @var ResourceModel\SuccessSync
     */
    private $resource;

    /**
     * @var SuccessSyncFactory
     */
    private $entityFactory;

    /**
     * @var ResourceModel\SuccessSync\CollectionFactory
     */
    private $collectionFactory;

    /**
     * @var SuccessSyncSearchResultsFactory
     */
    private $searchResultsFactory;

    /**
     * @var CollectionProcessorInterface
     */
    private $collectionProcessor;

    public function __construct(
        \Amasty\GxoIntegration\Model\ResourceModel\SuccessSync $resource,
        \Amasty\GxoIntegration\Model\SuccessSyncFactory $entityFactory,
        \Amasty\GxoIntegration\Model\ResourceModel\SuccessSync\CollectionFactory $collectionFactory,
        \Amasty\GxoIntegration\Model\SuccessSyncSearchResultsFactory $searchResultsFactory,
        CollectionProcessorInterface $collectionProcessor
    ) {
        $this->resource = $resource;
        $this->entityFactory = $entityFactory;
        $this->collectionFactory = $collectionFactory;
        $this->searchResultsFactory = $searchResultsFactory;
        $this->collectionProcessor = $collectionProcessor;
    }

    /**
     * @param SuccessSyncInterface $entity
     * @return SuccessSyncInterface
     */
    public function save(SuccessSyncInterface $entity): SuccessSyncInterface
    {
        $this->resource->save($entity);
        return $entity;
    }

    /**
     * @param int $id
     * @return SuccessSyncInterface
     * @throws NoSuchEntityException
     */
    public function getById(int $id): SuccessSyncInterface
    {
        /** @var SuccessSync $model */
        $model = $this->entityFactory->create();
        $this->resource->load($model, $id);
        if (!$model->getId()) {
            throw new NoSuchEntityException(__('Success record does not exist.'));
        }
        return $model;
    }

    /**
     * @param int $orderId
     * @return SuccessSyncInterface
     * @throws NoSuchEntityException
     */
    public function getByOrderId(int $orderId): SuccessSyncInterface
    {
        /** @var SuccessSync $model */
        $model = $this->entityFactory->create();
        $this->resource->load($model, $orderId, SuccessSyncInterface::ORDER_ID);
        if (!$model->getId()) {
            throw new NoSuchEntityException(__('Success record does not exist.'));
        }
        return $model;
    }

    /**
     * @param SearchCriteriaInterface $searchCriteria
     * @return \Amasty\GxoIntegration\Api\Data\SuccessSyncSearchResultsInterface
     */
    public function getList(SearchCriteriaInterface $searchCriteria): \Amasty\GxoIntegration\Api\Data\SuccessSyncSearchResultsInterface
    {
        $collection = $this->collectionFactory->create();
        $this->collectionProcessor->process($searchCriteria, $collection);

        /** @var \Amasty\GxoIntegration\Api\Data\SuccessSyncSearchResultsInterface $searchResults */
        $searchResults = $this->searchResultsFactory->create();
        $searchResults->setSearchCriteria($searchCriteria);
        $searchResults->setTotalCount((int)$collection->getSize());
        $searchResults->setItems($collection->getItems());

        return $searchResults;
    }

    /**
     * @param SuccessSyncInterface $entity
     * @return bool
     */
    public function delete(SuccessSyncInterface $entity): bool
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
