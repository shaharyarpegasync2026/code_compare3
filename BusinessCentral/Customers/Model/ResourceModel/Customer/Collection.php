<?php

/**
 * Copyright © 2009-2025 Amasty. All Rights Reserved.
 * See LICENSE for license details.
 */

namespace BusinessCentral\Customers\Model\ResourceModel\Customer;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    protected function _construct()
    {
        $this->_init(
            \BusinessCentral\Customers\Model\Customer::class,
            \BusinessCentral\Customers\Model\ResourceModel\Customer::class
        );
    }
}
