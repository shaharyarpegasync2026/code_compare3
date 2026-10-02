<?php
/**
 * Copyright © 2009-2025 Amasty. All Rights Reserved.
 * See LICENSE for license details.
 */
namespace BusinessCentral\Orders\Cron;

use BusinessCentral\Orders\Model\Adapter;

class SyncCancelledOrders
{
    protected $adapter;

    public function __construct(Adapter $adapter)
    {
        $this->adapter = $adapter;
    }

    public function execute()
    {
        $this->adapter->syncCancelledOrders();
    }
}










