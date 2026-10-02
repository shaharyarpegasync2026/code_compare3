<?php

/**
 * Copyright © 2009-2026 Amasty. All Rights Reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace Amasty\GxoIntegration\Api\Data;

use Magento\Framework\Api\ExtensibleDataInterface;

interface SuccessSyncInterface extends ExtensibleDataInterface
{
    public const ENTITY_ID = 'entity_id';
    public const ORDER_ID = 'order_id';
    public const GXO_REFERENCE = 'gxo_reference';
    public const REQUEST_PAYLOAD = 'request_payload';
    public const RESPONSE_PAYLOAD = 'response_payload';
    public const SYNCED_AT = 'synced_at';

    /**
     * @return int|null
     */
    public function getId();

    /**
     * @param int $id
     * @return $this
     */
    public function setId($id);

    /**
     * @return int
     */
    public function getOrderId(): int;

    /**
     * @param int $orderId
     * @return $this
     */
    public function setOrderId(int $orderId);

    /**
     * @return string|null
     */
    public function getGxoReference(): ?string;

    /**
     * @param string|null $reference
     * @return $this
     */
    public function setGxoReference(?string $reference);

    /**
     * @return string|null
     */
    public function getSyncedAt(): ?string;

    /**
     * @param string|null $syncedAt
     * @return $this
     */
    public function setSyncedAt(?string $syncedAt);

    /**
     * @return \Amasty\GxoIntegration\Api\Data\SuccessSyncExtensionInterface|null
     */
    public function getExtensionAttributes();

    /**
     * @param \Amasty\GxoIntegration\Api\Data\SuccessSyncExtensionInterface $extensionAttributes
     * @return $this
     */
    public function setExtensionAttributes(\Amasty\GxoIntegration\Api\Data\SuccessSyncExtensionInterface $extensionAttributes);
}
