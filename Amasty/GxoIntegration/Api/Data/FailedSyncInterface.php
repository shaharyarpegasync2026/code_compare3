<?php

/**
 * Copyright © 2009-2026 Amasty. All Rights Reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace Amasty\GxoIntegration\Api\Data;

use Magento\Framework\Api\ExtensibleDataInterface;

interface FailedSyncInterface extends ExtensibleDataInterface
{
    public const ENTITY_ID = 'entity_id';
    public const ORDER_ID = 'order_id';
    public const ATTEMPTS = 'attempts';
    public const NEXT_RETRY_AT = 'next_retry_at';
    public const LAST_ERROR = 'last_error';
    public const REQUEST_PAYLOAD = 'request_payload';
    public const RESPONSE_PAYLOAD = 'response_payload';
    public const CREATED_AT = 'created_at';
    public const UPDATED_AT = 'updated_at';

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
     * @return int
     */
    public function getAttempts(): int;

    /**
     * @param int $attempts
     * @return $this
     */
    public function setAttempts(int $attempts);

    /**
     * @return string|null
     */
    public function getNextRetryAt(): ?string;

    /**
     * @param string|null $nextRetryAt
     * @return $this
     */
    public function setNextRetryAt(?string $nextRetryAt);

    /**
     * @return string|null
     */
    public function getLastError(): ?string;

    /**
     * @param string|null $lastError
     * @return $this
     */
    public function setLastError(?string $lastError);

    /**
     * @return \Amasty\GxoIntegration\Api\Data\FailedSyncExtensionInterface|null
     */
    public function getExtensionAttributes();

    /**
     * @param \Amasty\GxoIntegration\Api\Data\FailedSyncExtensionInterface $extensionAttributes
     * @return $this
     */
    public function setExtensionAttributes(\Amasty\GxoIntegration\Api\Data\FailedSyncExtensionInterface $extensionAttributes);
}
