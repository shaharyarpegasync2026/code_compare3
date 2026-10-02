<?php

/**
 * Copyright © 2009-2026 Amasty. All Rights Reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace Amasty\GxoIntegration\Model;

use Amasty\GxoIntegration\Api\Data\QueueInterface;
use Magento\Framework\Model\AbstractExtensibleModel;

class Queue extends AbstractExtensibleModel implements QueueInterface
{
    protected function _construct()
    {
        $this->_init(\Amasty\GxoIntegration\Model\ResourceModel\Queue::class);
    }

    /**
     * @return int
     */
    public function getOrderId(): int
    {
        return (int)$this->getData(self::ORDER_ID);
    }

    /**
     * @param int $orderId
     * @return $this
     */
    public function setOrderId(int $orderId)
    {
        return $this->setData(self::ORDER_ID, $orderId);
    }

    /**
     * @return string
     */
    public function getStatus(): string
    {
        return (string)$this->getData(self::STATUS);
    }

    /**
     * @param string $status
     * @return $this
     */
    public function setStatus(string $status)
    {
        return $this->setData(self::STATUS, $status);
    }

    /**
     * @return int
     */
    public function getAttempts(): int
    {
        return (int)$this->getData(self::ATTEMPTS);
    }

    /**
     * @param int $attempts
     * @return $this
     */
    public function setAttempts(int $attempts)
    {
        return $this->setData(self::ATTEMPTS, $attempts);
    }

    /**
     * @return string|null
     */
    public function getNextRunAt(): ?string
    {
        $value = $this->getData(self::NEXT_RUN_AT);
        return $value !== null ? (string)$value : null;
    }

    /**
     * @param string|null $nextRunAt
     * @return $this
     */
    public function setNextRunAt(?string $nextRunAt)
    {
        return $this->setData(self::NEXT_RUN_AT, $nextRunAt);
    }

    /**
     * @return string|null
     */
    public function getLockedAt(): ?string
    {
        $value = $this->getData(self::LOCKED_AT);
        return $value !== null ? (string)$value : null;
    }

    /**
     * @param string|null $lockedAt
     * @return $this
     */
    public function setLockedAt(?string $lockedAt)
    {
        return $this->setData(self::LOCKED_AT, $lockedAt);
    }

    /**
     * @return string|null
     */
    public function getLastError(): ?string
    {
        $value = $this->getData(self::LAST_ERROR);
        return $value !== null ? (string)$value : null;
    }

    /**
     * @param string|null $lastError
     * @return $this
     */
    public function setLastError(?string $lastError)
    {
        return $this->setData(self::LAST_ERROR, $lastError);
    }

    /**
     * @return string|null
     */
    public function getRequestPayload(): ?string
    {
        $value = $this->getData(self::REQUEST_PAYLOAD);
        return $value !== null ? (string)$value : null;
    }

    /**
     * @param string|null $requestPayload
     * @return $this
     */
    public function setRequestPayload(?string $requestPayload)
    {
        return $this->setData(self::REQUEST_PAYLOAD, $requestPayload);
    }

    /**
     * @return \Amasty\GxoIntegration\Api\Data\QueueExtensionInterface|null
     */
    public function getExtensionAttributes()
    {
        return $this->_getExtensionAttributes();
    }

    /**
     * @param \Amasty\GxoIntegration\Api\Data\QueueExtensionInterface $extensionAttributes
     * @return $this
     */
    public function setExtensionAttributes(\Amasty\GxoIntegration\Api\Data\QueueExtensionInterface $extensionAttributes)
    {
        return $this->_setExtensionAttributes($extensionAttributes);
    }
}
