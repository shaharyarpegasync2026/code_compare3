<?php

/**
 * Copyright © 2009-2025 Amasty. All Rights Reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace BusinessCentral\Products\Cron;

use BusinessCentral\Products\Model\Export\ProductsExporter;

class ExportProducts
{
    /**
     * @var ProductsExporter
     */
    private $productsExporter;

    public function __construct(ProductsExporter $productsExporter)
    {
        $this->productsExporter = $productsExporter;
    }

    /**
     * @return void
     */
    public function execute(): void
    {
        $this->productsExporter->execute();
    }
}


