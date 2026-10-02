<?php

/**
 * Copyright © 2009-2026 Amasty. All Rights Reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace Amasty\GxoIntegration\Model\ResourceModel;

use Amasty\GxoIntegration\Model\Queue\QueueStatus;
use Magento\Framework\DB\Sql\Expression;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Queue extends AbstractDb
{
    protected function _construct()
    {
        $this->_init('amasty_gxo_queue', 'entity_id');
    }

    /**
     * @param int $orderId
     * @param int $attempts
     * @return void
     */
    public function enqueue(int $orderId, int $attempts = 0): void
    {
        $this->getConnection()->insertOnDuplicate(
            $this->getMainTable(),
            [
                'order_id' => $orderId,
                'status' => QueueStatus::PENDING,
                'attempts' => $attempts,
                'next_run_at' => null,
                'locked_at' => null,
                'last_error' => null
            ],
            ['status', 'attempts', 'next_run_at', 'locked_at', 'last_error', 'updated_at']
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

    /**
     * @param int $orderId
     * @return array|null
     */
    public function getByOrderId(int $orderId): ?array
    {
        $select = $this->getConnection()->select()
            ->from($this->getMainTable(), ['status', 'last_error'])
            ->where('order_id = ?', $orderId)
            ->limit(1);

        $row = $this->getConnection()->fetchRow($select);
        return is_array($row) ? $row : null;
    }

    /**
     * @param int $batchSize
     * @return array<int, array<string, mixed>>
     */
    public function getPendingBatch(int $batchSize): array
    {
        $select = $this->getConnection()->select()
            ->from($this->getMainTable(), ['entity_id', 'order_id'])
            ->where('status = ?', QueueStatus::PENDING)
            ->where('(next_run_at IS NULL OR next_run_at <= UTC_TIMESTAMP())')
            ->where('(locked_at IS NULL OR locked_at <= DATE_SUB(UTC_TIMESTAMP(), INTERVAL 5 MINUTE))')
            ->order('entity_id ASC')
            ->limit($batchSize);

        return $this->getConnection()->fetchAll($select);
    }

    /**
     * @param int $entityId
     * @return bool
     */
    public function markProcessing(int $entityId): bool
    {
        $affected = $this->getConnection()->update(
            $this->getMainTable(),
            [
                'status' => QueueStatus::PROCESSING,
                'locked_at' => new Expression('UTC_TIMESTAMP()')
            ],
            ['entity_id = ?' => $entityId, 'status = ?' => QueueStatus::PENDING]
        );

        return $affected > 0;
    }
}
