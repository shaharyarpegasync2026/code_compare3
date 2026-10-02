<?php

/**
 * Copyright © 2009-2026 Amasty. All Rights Reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace Amasty\GxoIntegration\Model;

use Amasty\GxoIntegration\Api\Data\QueueInterface;
use Amasty\GxoIntegration\Api\QueueRepositoryInterface;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Exception\NoSuchEntityException;

class QueueRepository implements QueueRepositoryInterface
{
    /**
     * @var ResourceModel\Queue
     */
    private $resource;

    /**
     * @var QueueFactory
     */
    private $entityFactory;

    /**
     * @var ResourceModel\Queue\CollectionFactory
     */
    private $collectionFactory;

    /**
     * @var QueueSearchResultsFactory
     */
    private $searchResultsFactory;

    /**
     * @var CollectionProcessorInterface
     */
    private $collectionProcessor;

    public function __construct(
        \Amasty\GxoIntegration\Model\ResourceModel\Queue $resource,
        \Amasty\GxoIntegration\Model\QueueFactory $entityFactory,
        \Amasty\GxoIntegration\Model\ResourceModel\Queue\CollectionFactory $collectionFactory,
        \Amasty\GxoIntegration\Model\QueueSearchResultsFactory $searchResultsFactory,
        CollectionProcessorInterface $collectionProcessor
    ) {
        $this->resource = $resource;
        $this->entityFactory = $entityFactory;
        $this->collectionFactory = $collectionFactory;
        $this->searchResultsFactory = $searchResultsFactory;
        $this->collectionProcessor = $collectionProcessor;
    }

    /**
     * @param QueueInterface $entity
     * @return QueueInterface
     */
    public function save(QueueInterface $entity): QueueInterface
    {
        $this->resource->save($entity);
        return $entity;
    }

    /**
     * @param int $id
     * @return QueueInterface
     * @throws NoSuchEntityException
     */
    public function getById(int $id): QueueInterface
    {
        /** @var Queue $model */
        $model = $this->entityFactory->create();
        $this->resource->load($model, $id);
        if (!$model->getId()) {
            throw new NoSuchEntityException(__('Queue record does not exist.'));
        }
        return $model;
    }

    /**
     * @param int $orderId
     * @return QueueInterface
     * @throws NoSuchEntityException
     */
    public function getByOrderId(int $orderId): QueueInterface
    {
        /** @var Queue $model */
        $model = $this->entityFactory->create();
        $this->resource->load($model, $orderId, QueueInterface::ORDER_ID);
        if (!$model->getId()) {
            throw new NoSuchEntityException(__('Queue record does not exist.'));
        }
        return $model;
    }

    /**
     * @param SearchCriteriaInterface $searchCriteria
     * @return \Amasty\GxoIntegration\Api\Data\QueueSearchResultsInterface
     */
    public function getList(SearchCriteriaInterface $searchCriteria): \Amasty\GxoIntegration\Api\Data\QueueSearchResultsInterface
    {
        $collection = $this->collectionFactory->create();
        $this->collectionProcessor->process($searchCriteria, $collection);

        /** @var \Amasty\GxoIntegration\Api\Data\QueueSearchResultsInterface $searchResults */
        $searchResults = $this->searchResultsFactory->create();
        $searchResults->setSearchCriteria($searchCriteria);
        $searchResults->setTotalCount((int)$collection->getSize());
        $searchResults->setItems($collection->getItems());

        return $searchResults;
    }

    /**
     * @param QueueInterface $entity
     * @return bool
     */
    public function delete(QueueInterface $entity): bool
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
