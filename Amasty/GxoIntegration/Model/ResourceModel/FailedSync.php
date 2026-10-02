<?php

/**
 * Copyright © 2009-2025 Amasty. All Rights Reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace Amasty\GxoIntegration\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class FailedSync extends AbstractDb
{
    protected function _construct()
    {
        $this->_init('amasty_gxo_failed_sync', 'entity_id');
    }

    /**
     * @param int $entityId
     * @return int
     */
    public function getOrderIdByEntityId(int $entityId): int
    {
        $select = $this->getConnection()->select()
            ->from($this->getMainTable(), ['order_id'])
            ->where('entity_id = ?', $entityId)
            ->limit(1);

        return (int)$this->getConnection()->fetchOne($select);
    }

    /**
     * @param int $orderId
     * @return int
     */
    public function getAttemptsByOrderId(int $orderId): int
    {
        $select = $this->getConnection()->select()
            ->from($this->getMainTable(), ['attempts'])
            ->where('order_id = ?', $orderId)
            ->limit(1);

        return (int)$this->getConnection()->fetchOne($select);
    }

    /**
     * @param int $orderId
     * @return array|null
     */
    public function getByOrderId(int $orderId): ?array
    {
        $select = $this->getConnection()->select()
            ->from($this->getMainTable(), ['attempts', 'next_retry_at', 'last_error'])
            ->where('order_id = ?', $orderId)
            ->limit(1);

        $row = $this->getConnection()->fetchRow($select);
        return is_array($row) ? $row : null;
    }

    /**
     * @param int $batchSize
     * @param int $maxAttempts
     * @return array<int, array<string, mixed>>
     */
    public function getRetryBatch(int $batchSize, int $maxAttempts): array
    {
        $select = $this->getConnection()->select()
            ->from($this->getMainTable(), ['order_id', 'attempts'])
            ->where('attempts < ?', $maxAttempts)
            ->where('(next_retry_at IS NULL OR next_retry_at <= UTC_TIMESTAMP())')
            ->order('updated_at ASC')
            ->limit($batchSize);

        return $this->getConnection()->fetchAll($select);
    }

    /**
     * @param int $orderId
     * @param int $attempts
     * @param string|null $nextRetryAt
     * @param string|null $error
     * @param string|null $requestPayload
     * @param string|null $responsePayload
     * @return void
     */
    public function upsertFailed(
        int $orderId,
        int $attempts,
        ?string $nextRetryAt,
        ?string $error,
        ?string $requestPayload,
        ?string $responsePayload
    ): void {
        $this->getConnection()->insertOnDuplicate(
            $this->getMainTable(),
            [
                'order_id' => $orderId,
                'attempts' => $attempts,
                'next_retry_at' => $nextRetryAt,
                'last_error' => $error,
                'request_payload' => $requestPayload,
                'response_payload' => $responsePayload
            ],
            ['attempts', 'next_retry_at', 'last_error', 'request_payload', 'response_payload', 'updated_at']
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
