<?php

/**
 * Copyright © 2009-2026 Amasty. All Rights Reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace Amasty\GxoIntegration\Plugin\Sales\Api;

use Amasty\GxoIntegration\Model\Queue\Enqueuer;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;

class OrderRepositoryInterfacePlugin
{
    /**
     * @var Enqueuer
     */
    private $enqueuer;

    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    public function __construct(
        Enqueuer $enqueuer,
        OrderRepositoryInterface $orderRepository
    ) {
        $this->enqueuer = $enqueuer;
        $this->orderRepository = $orderRepository;
    }

    /**
     * @param OrderRepositoryInterface $subject
     * @param \Closure $proceed
     * @param OrderInterface $entity
     * @return OrderInterface
     */
    public function aroundSave(
        OrderRepositoryInterface $subject,
        \Closure $proceed,
        OrderInterface $entity
    ): OrderInterface {
        $orderId = (int)$entity->getEntityId();
        $oldStatus = null;

        if ($orderId) {
            try {
                $existing = $this->orderRepository->get($orderId);
                $oldStatus = $existing->getStatus();
            } catch (NoSuchEntityException $e) {
                $oldStatus = null;
            }
        }

        $saved = $proceed($entity);

        $savedId = (int)$saved->getEntityId();
        if ($savedId) {
            $this->enqueuer->enqueueIfNeeded($savedId, (string)$saved->getStatus(), $oldStatus);
        }

        return $saved;
    }
}

