<?php
/**
 * Copyright © 2009-2025 Amasty. All Rights Reserved.
 * See LICENSE for license details.
 */
namespace BusinessCentral\Orders\Model;

use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use BusinessCentral\Orders\Api\CancelledOrderRepositoryInterface;
use BusinessCentral\Orders\Api\Data\CancelledOrderInterface;
use BusinessCentral\Orders\Model\ResourceModel\CancelledOrder as Resource;
use BusinessCentral\Orders\Model\ResourceModel\CancelledOrder\CollectionFactory;
use Magento\Framework\Api\SearchResultsInterfaceFactory;

class CancelledOrderRepository implements CancelledOrderRepositoryInterface
{
    protected $resource;
    protected $factory;
    protected $collectionFactory;
    protected $searchResultsFactory;

    public function __construct(
        Resource $resource,
        CancelledOrderFactory $factory,
        CollectionFactory $collectionFactory,
        SearchResultsInterfaceFactory $searchResultsFactory
    ) {
        $this->resource = $resource;
        $this->factory = $factory;
        $this->collectionFactory = $collectionFactory;
        $this->searchResultsFactory = $searchResultsFactory;
    }

    public function save(CancelledOrderInterface $cancelledOrder)
    {
        $this->resource->save($cancelledOrder);
        return $cancelledOrder;
    }

    public function getById($id)
    {
        $cancelledOrder = $this->factory->create();
        $this->resource->load($cancelledOrder, $id);
        if (!$cancelledOrder->getId()) {
            throw new NoSuchEntityException(__('Cancelled order with id "%1" does not exist.', $id));
        }
        return $cancelledOrder;
    }

    public function getByOrderId($orderId)
    {
        $cancelledOrder = $this->factory->create();
        $this->resource->load($cancelledOrder, $orderId, 'order_id');
        if (!$cancelledOrder->getId()) {
            return null;
        }
        return $cancelledOrder;
    }

    public function getList(SearchCriteriaInterface $criteria)
    {
        $collection = $this->collectionFactory->create();
        $searchResults = $this->searchResultsFactory->create();
        $searchResults->setSearchCriteria($criteria);
        $searchResults->setItems($collection->getItems());
        $searchResults->setTotalCount($collection->getSize());
        return $searchResults;
    }

    public function delete(CancelledOrderInterface $cancelledOrder)
    {
        $this->resource->delete($cancelledOrder);
        return true;
    }

    public function deleteById($id)
    {
        $cancelledOrder = $this->getById($id);
        return $this->delete($cancelledOrder);
    }
}










