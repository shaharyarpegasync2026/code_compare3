<?php
/**
 * Copyright © 2009-2025 Amasty. All Rights Reserved.
 * See LICENSE for license details.
 */
namespace BusinessCentral\Orders\Api;

use Magento\Framework\Api\SearchCriteriaInterface;
use BusinessCentral\Orders\Api\Data\CancelledOrderInterface;

interface CancelledOrderRepositoryInterface
{
    public function save(CancelledOrderInterface $cancelledOrder);
    public function getById($id);
    public function getByOrderId($orderId);
    public function getList(SearchCriteriaInterface $criteria);
    public function delete(CancelledOrderInterface $cancelledOrder);
    public function deleteById($id);
}










