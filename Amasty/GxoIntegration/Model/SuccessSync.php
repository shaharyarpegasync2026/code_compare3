<?php

/**
 * Copyright © 2009-2026 Amasty. All Rights Reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace Amasty\GxoIntegration\Model;

use Amasty\GxoIntegration\Api\Data\SuccessSyncInterface;
use Magento\Framework\Model\AbstractExtensibleModel;

class SuccessSync extends AbstractExtensibleModel implements SuccessSyncInterface
{
    protected function _construct()
    {
        $this->_init(\Amasty\GxoIntegration\Model\ResourceModel\SuccessSync::class);
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
     * @return string|null
     */
    public function getGxoReference(): ?string
    {
        $value = $this->getData(self::GXO_REFERENCE);
        return $value !== null ? (string)$value : null;
    }

    /**
     * @param string|null $reference
     * @return $this
     */
    public function setGxoReference(?string $reference)
    {
        return $this->setData(self::GXO_REFERENCE, $reference);
    }

    /**
     * @return string|null
     */
    public function getSyncedAt(): ?string
    {
        $value = $this->getData(self::SYNCED_AT);
        return $value !== null ? (string)$value : null;
    }

    /**
     * @param string|null $syncedAt
     * @return $this
     */
    public function setSyncedAt(?string $syncedAt)
    {
        return $this->setData(self::SYNCED_AT, $syncedAt);
    }

    /**
     * @return \Amasty\GxoIntegration\Api\Data\SuccessSyncExtensionInterface|null
     */
    public function getExtensionAttributes()
    {
        return $this->_getExtensionAttributes();
    }

    /**
     * @param \Amasty\GxoIntegration\Api\Data\SuccessSyncExtensionInterface $extensionAttributes
     * @return $this
     */
    public function setExtensionAttributes(\Amasty\GxoIntegration\Api\Data\SuccessSyncExtensionInterface $extensionAttributes)
    {
        return $this->_setExtensionAttributes($extensionAttributes);
    }
}
