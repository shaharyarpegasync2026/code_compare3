<?php

/**
 * Copyright © 2009-2026 Amasty. All Rights Reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace Amasty\GxoIntegration\Api;

use Magento\Framework\Exception\LocalizedException;

interface InventoryWebhookInterface
{
    /**
     * @param string $COMPANY_CODE
     * @param mixed $ROWS
     * @return array
     * @throws LocalizedException
     */
    public function syncInventory(
        string $COMPANY_CODE = '',
        mixed $ROWS = []
    ): array;
}
