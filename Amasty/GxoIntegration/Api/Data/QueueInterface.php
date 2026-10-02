<?php

/**
 * Copyright © 2009-2026 Amasty. All Rights Reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace Amasty\GxoIntegration\Api\Data;

use Magento\Framework\Api\ExtensibleDataInterface;

interface QueueInterface extends ExtensibleDataInterface
{
    public const ENTITY_ID = 'entity_id';
    public const ORDER_ID = 'order_id';
    public const STATUS = 'status';
    public const ATTEMPTS = 'attempts';
    public const NEXT_RUN_AT = 'next_run_at';
    public const LOCKED_AT = 'locked_at';
    public const LAST_ERROR = 'last_error';
    public const REQUEST_PAYLOAD = 'request_payload';
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
     * @return string
     */
    public function getStatus(): string;

    /**
     * @param string $status
     * @return $this
     */
    public function setStatus(string $status);

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
    public function getNextRunAt(): ?string;

    /**
     * @param string|null $nextRunAt
     * @return $this
     */
    public function setNextRunAt(?string $nextRunAt);

    /**
     * @return string|null
     */
    public function getLockedAt(): ?string;

    /**
     * @param string|null $lockedAt
     * @return $this
     */
    public function setLockedAt(?string $lockedAt);

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
     * @return string|null
     */
    public function getRequestPayload(): ?string;

    /**
     * @param string|null $requestPayload
     * @return $this
     */
    public function setRequestPayload(?string $requestPayload);

    /**
     * @return \Amasty\GxoIntegration\Api\Data\QueueExtensionInterface|null
     */
    public function getExtensionAttributes();

    /**
     * @param \Amasty\GxoIntegration\Api\Data\QueueExtensionInterface $extensionAttributes
     * @return $this
     */
    public function setExtensionAttributes(\Amasty\GxoIntegration\Api\Data\QueueExtensionInterface $extensionAttributes);
}
