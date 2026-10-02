<?php
/**
 * Copyright © 2009-2025 Amasty. All Rights Reserved.
 * See LICENSE for license details.
 */
namespace BusinessCentral\Orders\Model;

use Magento\Framework\Model\AbstractModel;
use BusinessCentral\Orders\Api\Data\CancelledOrderInterface;

class CancelledOrder extends AbstractModel implements CancelledOrderInterface
{
    protected function _construct()
    {
        $this->_init(\BusinessCentral\Orders\Model\ResourceModel\CancelledOrder::class);
    }

    public function getEntityId()
    {
        return $this->getData(self::ENTITY_ID);
    }

    public function setEntityId($entityId)
    {
        return $this->setData(self::ENTITY_ID, $entityId);
    }

    public function getOrderId()
    {
        return $this->getData(self::ORDER_ID);
    }

    public function setOrderId($orderId)
    {
        return $this->setData(self::ORDER_ID, $orderId);
    }

    public function getDynamicsOrderNumber()
    {
        return $this->getData(self::DYNAMICS_ORDER_NUMBER);
    }

    public function setDynamicsOrderNumber($dynamicsOrderNumber)
    {
        return $this->setData(self::DYNAMICS_ORDER_NUMBER, $dynamicsOrderNumber);
    }

    public function getCancelledAt()
    {
        return $this->getData(self::CANCELLED_AT);
    }

    public function setCancelledAt($cancelledAt)
    {
        return $this->setData(self::CANCELLED_AT, $cancelledAt);
    }
}










