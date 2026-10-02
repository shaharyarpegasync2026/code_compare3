<?php

/**
 * Copyright © 2009-2026 Amasty. All Rights Reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace Amasty\GxoIntegration\Api;

use Magento\Framework\Exception\LocalizedException;

interface ShipmentWebhookInterface
{
    /**
     * @param mixed $payload
     * @return array
     * @throws LocalizedException
     */
    public function createShipment(mixed $payload = []): array;
}
