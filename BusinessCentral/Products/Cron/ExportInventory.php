<?php

/**
 * Copyright © 2009-2025 Amasty. All Rights Reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace BusinessCentral\Products\Cron;

use BusinessCentral\Products\Model\Export\InventoryExporter;

class ExportInventory
{
    /**
     * @var InventoryExporter
     */
    private $inventoryExporter;

    public function __construct(InventoryExporter $inventoryExporter)
    {
        $this->inventoryExporter = $inventoryExporter;
    }

    /**
     * @return void
     */
    public function execute(): void
    {
        $this->inventoryExporter->execute();
    }
}




