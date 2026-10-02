<?php
/**
 * Copyright © 2009-2025 Amasty. All Rights Reserved.
 * See LICENSE for license details.
 */
namespace BusinessCentral\Orders\Model\ResourceModel\CancelledOrder;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    protected function _construct()
    {
        $this->_init(
            \BusinessCentral\Orders\Model\CancelledOrder::class,
            \BusinessCentral\Orders\Model\ResourceModel\CancelledOrder::class
        );
    }
}










