<?php
/**
 * Copyright © 2009-2025 Amasty. All Rights Reserved.
 * See LICENSE for license details.
 */
namespace BusinessCentral\Orders\Api\Data;

interface CancelledOrderInterface
{
    const ENTITY_ID = 'entity_id';
    const ORDER_ID = 'order_id';
    const DYNAMICS_ORDER_NUMBER = 'dynamics_order_number';
    const CANCELLED_AT = 'cancelled_at';

    public function getEntityId();
    public function setEntityId($entityId);
    public function getOrderId();
    public function setOrderId($orderId);
    public function getDynamicsOrderNumber();
    public function setDynamicsOrderNumber($dynamicsOrderNumber);
    public function getCancelledAt();
    public function setCancelledAt($cancelledAt);
}










