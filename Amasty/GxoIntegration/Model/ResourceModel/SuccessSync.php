<?php

/**
 * Copyright © 2009-2026 Amasty. All Rights Reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace Amasty\GxoIntegration\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class SuccessSync extends AbstractDb
{
    protected function _construct()
    {
        $this->_init('amasty_gxo_success_sync', 'entity_id');
    }

    /**
     * @param int $orderId
     * @return bool
     */
    public function existsByOrderId(int $orderId): bool
    {
        $select = $this->getConnection()->select()
            ->from($this->getMainTable(), ['entity_id'])
            ->where('order_id = ?', $orderId)
            ->limit(1);

        return (bool)$this->getConnection()->fetchOne($select);
    }

    /**
     * @param int $orderId
     * @return array|null
     */
    public function getByOrderId(int $orderId): ?array
    {
        $select = $this->getConnection()->select()
            ->from($this->getMainTable(), ['gxo_reference', 'synced_at'])
            ->where('order_id = ?', $orderId)
            ->limit(1);

        $row = $this->getConnection()->fetchRow($select);
        return is_array($row) ? $row : null;
    }

    /**
     * @param int $orderId
     * @param string|null $reference
     * @param string|null $requestPayload
     * @param string|null $responsePayload
     * @param string $syncedAt
     * @return void
     */
    public function upsertSuccess(
        int $orderId,
        ?string $reference,
        ?string $requestPayload,
        ?string $responsePayload,
        string $syncedAt
    ): void {
        $this->getConnection()->insertOnDuplicate(
            $this->getMainTable(),
            [
                'order_id' => $orderId,
                'gxo_reference' => $reference,
                'request_payload' => $requestPayload,
                'response_payload' => $responsePayload,
                'synced_at' => $syncedAt
            ],
            ['gxo_reference', 'request_payload', 'response_payload', 'synced_at']
        );
    }

    /**
     * @param int $orderId
     * @return void
     */
    public function deleteByOrderId(int $orderId): void
    {
        $this->getConnection()->delete($this->getMainTable(), ['order_id = ?' => $orderId]);
    }
}
