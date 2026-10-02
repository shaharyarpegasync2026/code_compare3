<?php
/**
 * Copyright © 2009-2025 Amasty. All Rights Reserved.
 * See LICENSE for license details.
 */
namespace BusinessCentral\Orders\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class CancelledOrder extends AbstractDb
{
    protected function _construct()
    {
        $this->_init('businesscentral_cancelled_orders', 'entity_id');
    }
}










