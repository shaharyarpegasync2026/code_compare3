<?php

/**
 * Copyright © 2009-2025 Amasty. All Rights Reserved.
 * See LICENSE for license details.
 */

namespace BusinessCentral\Customers\Model;

use BusinessCentral\Customers\Api\CustomerRepositoryInterface;
use BusinessCentral\Customers\Api\Data\CustomerInterface;
use BusinessCentral\Customers\Api\Data\CustomerInterfaceFactory;
use BusinessCentral\Customers\Model\ResourceModel\Customer as CustomerResource;
use BusinessCentral\Customers\Model\ResourceModel\Customer\CollectionFactory;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SearchResultsInterface;
use Magento\Framework\Api\SearchResultsInterfaceFactory;
use Magento\Framework\Api\SortOrder;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;

class CustomerRepository implements CustomerRepositoryInterface
{
    /**
     * @var CustomerResource
     */
    private CustomerResource $customerResource;

    /**
     * @var CustomerInterfaceFactory
     */
    private CustomerInterfaceFactory $customerFactory;

    /**
     * @var CollectionFactory
     */
    private CollectionFactory $collectionFactory;

    /**
     * @var SearchResultsInterfaceFactory
     */
    private SearchResultsInterfaceFactory $searchResultsFactory;

    public function __construct(
        CustomerResource $customerResource,
        CustomerInterfaceFactory $customerFactory,
        CollectionFactory $collectionFactory,
        SearchResultsInterfaceFactory $searchResultsFactory
    ) {
        $this->customerResource = $customerResource;
        $this->customerFactory = $customerFactory;
        $this->collectionFactory = $collectionFactory;
        $this->searchResultsFactory = $searchResultsFactory;
    }

    public function save(CustomerInterface $customer)
    {
        try {
            $this->customerResource->save($customer);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(__('Could not save customer: %1', $exception->getMessage()));
        }
        return $customer;
    }

    public function getById($entityId)
    {
        $customer = $this->customerFactory->create();
        $this->customerResource->load($customer, $entityId);
        if (!$customer->getEntityId()) {
            throw new NoSuchEntityException(__('Customer with id "%1" does not exist.', $entityId));
        }
        return $customer;
    }

    public function getByEmail($email)
    {
        $customer = $this->customerFactory->create();
        $this->customerResource->load($customer, $email, CustomerInterface::EMAIL);
        if (!$customer->getEntityId()) {
            throw new NoSuchEntityException(__('Customer with email "%1" does not exist.', $email));
        }
        return $customer;
    }

    public function getByDynamics365Code($dynamics365Code)
    {
        $customer = $this->customerFactory->create();
        $this->customerResource->load($customer, $dynamics365Code, CustomerInterface::DYNAMICS365_CODE);
        if (!$customer->getEntityId()) {
            throw new NoSuchEntityException(__('Customer with Dynamics 365 code "%1" does not exist.', $dynamics365Code));
        }
        return $customer;
    }

    public function getList(SearchCriteriaInterface $searchCriteria)
    {
        $collection = $this->collectionFactory->create();
        
        $this->addFiltersToCollection($searchCriteria, $collection);
        $this->addSortOrdersToCollection($searchCriteria, $collection);
        $this->addPagingToCollection($searchCriteria, $collection);
        
        $collection->load();
        
        return $this->buildSearchResult($searchCriteria, $collection);
    }

    public function delete(CustomerInterface $customer)
    {
        try {
            $this->customerResource->delete($customer);
        } catch (\Exception $exception) {
            throw new CouldNotDeleteException(__('Could not delete customer: %1', $exception->getMessage()));
        }
        return true;
    }

    public function deleteById($entityId)
    {
        return $this->delete($this->getById($entityId));
    }

    private function addFiltersToCollection(SearchCriteriaInterface $searchCriteria, $collection)
    {
        foreach ($searchCriteria->getFilterGroups() as $filterGroup) {
            $fields = [];
            $conditions = [];
            foreach ($filterGroup->getFilters() as $filter) {
                $fields[] = $filter->getField();
                $conditions[] = [$filter->getConditionType() => $filter->getValue()];
            }
            $collection->addFieldToFilter($fields, $conditions);
        }
    }

    private function addSortOrdersToCollection(SearchCriteriaInterface $searchCriteria, $collection)
    {
        foreach ((array) $searchCriteria->getSortOrders() as $sortOrder) {
            $collection->addOrder(
                $sortOrder->getField(),
                ($sortOrder->getDirection() == SortOrder::SORT_ASC) ? 'ASC' : 'DESC'
            );
        }
    }

    private function addPagingToCollection(SearchCriteriaInterface $searchCriteria, $collection)
    {
        $collection->setPageSize($searchCriteria->getPageSize());
        $collection->setCurPage($searchCriteria->getCurrentPage());
    }

    private function buildSearchResult(SearchCriteriaInterface $searchCriteria, $collection)
    {
        $searchResults = $this->searchResultsFactory->create();
        $searchResults->setSearchCriteria($searchCriteria);
        $searchResults->setItems($collection->getItems());
        $searchResults->setTotalCount($collection->getSize());
        return $searchResults;
    }
}
