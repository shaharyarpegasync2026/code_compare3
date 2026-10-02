<?php

/**
 * Copyright © 2009-2026 Amasty. All Rights Reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace Amasty\GxoIntegration\Block\Adminhtml\Order\View;

use Amasty\GxoIntegration\Api\FailedSyncManagementInterface;
use Amasty\GxoIntegration\Api\QueueManagementInterface;
use Amasty\GxoIntegration\Api\SuccessSyncManagementInterface;
use Amasty\GxoIntegration\Model\Config;
use Magento\Backend\Block\Template;
use Magento\Framework\Registry;
use Magento\Sales\Api\Data\OrderInterface;

class GxoInfo extends Template
{
    /**
     * @var Registry
     */
    private $registry;

    /**
     * @var SuccessSyncManagementInterface
     */
    private $successSyncManagement;

    /**
     * @var QueueManagementInterface
     */
    private $queueManagement;

    /**
     * @var FailedSyncManagementInterface
     */
    private $failedSyncManagement;

    /**
     * @var Config
     */
    private $config;

    public function __construct(
        Template\Context $context,
        Registry $registry,
        SuccessSyncManagementInterface $successSyncManagement,
        QueueManagementInterface $queueManagement,
        FailedSyncManagementInterface $failedSyncManagement,
        Config $config,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->registry = $registry;
        $this->successSyncManagement = $successSyncManagement;
        $this->queueManagement = $queueManagement;
        $this->failedSyncManagement = $failedSyncManagement;
        $this->config = $config;
    }

    /**
     * @return OrderInterface|null
     */
    public function getOrder(): ?OrderInterface
    {
        $order = $this->registry->registry('current_order');
        return $order instanceof OrderInterface ? $order : null;
    }

    /**
     * @return array
     */
    public function getGxoState(): array
    {
        $order = $this->getOrder();
        if (!$order || !(int)$order->getEntityId()) {
            return ['status' => 'unknown'];
        }

        $orderId = (int)$order->getEntityId();

        $success = $this->successSyncManagement->getByOrderId($orderId);
        if ($success) {
            return [
                'status' => 'success',
                'reference' => $success->getGxoReference(),
                'synced_at' => $success->getSyncedAt()
            ];
        }

        $queue = $this->queueManagement->getByOrderId($orderId);
        if ($queue) {
            return [
                'status' => $queue->getStatus(),
                'last_error' => $queue->getLastError()
            ];
        }

        $failed = $this->failedSyncManagement->getByOrderId($orderId);
        if ($failed) {
            return [
                'status' => 'failed',
                'attempts' => $failed->getAttempts(),
                'next_retry_at' => $failed->getNextRetryAt(),
                'last_error' => $failed->getLastError()
            ];
        }

        return ['status' => 'not_queued'];
    }

    /**
     * @return bool
     */
    public function isEnabled(): bool
    {
        return $this->config->isEnabled();
    }

    /**
     * @return string
     */
    public function getSyncUrl(): string
    {
        $order = $this->getOrder();
        $orderId = $order ? (int)$order->getEntityId() : 0;
        return $this->getUrl('amasty_gxo/order/sync', ['order_id' => $orderId]);
    }
}
