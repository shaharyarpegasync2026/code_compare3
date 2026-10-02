<?php

/**
 * Copyright © 2009-2025 Amasty. All Rights Reserved.
 * See LICENSE for license details.
 */

namespace BusinessCentral\Customers\Api;

use BusinessCentral\Customers\Api\Data\CustomerInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SearchResultsInterface;

interface CustomerRepositoryInterface
{
    /**
     * @param CustomerInterface $customer
     * @return CustomerInterface
     */
    public function save(CustomerInterface $customer);

    /**
     * @param int $entityId
     * @return CustomerInterface
     */
    public function getById($entityId);

    /**
     * @param string $email
     * @return CustomerInterface
     */
    public function getByEmail($email);

    /**
     * @param string $dynamics365Code
     * @return CustomerInterface
     */
    public function getByDynamics365Code($dynamics365Code);

    /**
     * @param SearchCriteriaInterface $searchCriteria
     * @return SearchResultsInterface
     */
    public function getList(SearchCriteriaInterface $searchCriteria);

    /**
     * @param CustomerInterface $customer
     * @return bool
     */
    public function delete(CustomerInterface $customer);

    /**
     * @param int $entityId
     * @return bool
     */
    public function deleteById($entityId);
}
