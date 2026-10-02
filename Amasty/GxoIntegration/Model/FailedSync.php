<?php

/**
 * Copyright © 2009-2025 Amasty. All Rights Reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace Amasty\GxoIntegration\Model;

use Amasty\GxoIntegration\Api\Data\FailedSyncInterface;
use Magento\Framework\Model\AbstractExtensibleModel;

class FailedSync extends AbstractExtensibleModel implements FailedSyncInterface
{
    protected function _construct()
    {
        $this->_init(\Amasty\GxoIntegration\Model\ResourceModel\FailedSync::class);
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
    public function getNextRetryAt(): ?string
    {
        $value = $this->getData(self::NEXT_RETRY_AT);
        return $value !== null ? (string)$value : null;
    }

    /**
     * @param string|null $nextRetryAt
     * @return $this
     */
    public function setNextRetryAt(?string $nextRetryAt)
    {
        return $this->setData(self::NEXT_RETRY_AT, $nextRetryAt);
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
     * @return \Amasty\GxoIntegration\Api\Data\FailedSyncExtensionInterface|null
     */
    public function getExtensionAttributes()
    {
        return $this->_getExtensionAttributes();
    }

    /**
     * @param \Amasty\GxoIntegration\Api\Data\FailedSyncExtensionInterface $extensionAttributes
     * @return $this
     */
    public function setExtensionAttributes(\Amasty\GxoIntegration\Api\Data\FailedSyncExtensionInterface $extensionAttributes)
    {
        return $this->_setExtensionAttributes($extensionAttributes);
    }
}
