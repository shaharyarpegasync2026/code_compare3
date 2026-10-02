<?php

/**
 * Copyright © 2009-2025 Amasty. All Rights Reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace BusinessCentral\Orders\Cron;

use BusinessCentral\Api\Api\ApiInterface;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Psr\Log\LoggerInterface;
use Magento\Sales\Model\Order;

/**
 * Class SyncOrders
 *
 * @package BusinessCentral\Orders\Cron
 */
class SyncOrders
{
    /**
     * @var ApiInterface
     */
    private $api;

    /**
     * @var CollectionFactory
     */
    private $orderCollectionFactory;

    /**
     * @var ScopeConfigInterface
     */
    private $config;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        ApiInterface $api,
        CollectionFactory $orderCollectionFactory,
        ScopeConfigInterface $config,
        LoggerInterface $logger
    ) {
        $this->api = $api;
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->config = $config;
        $this->logger = $logger;
    }

    /**
     * Execute the cron job for order synchronization
     *
     * @return void
     */
    public function execute()
    {
        if (!$this->config->getValue('orders/sync/enabled')) {
            return;
        }

        $startDate = $this->config->getValue('orders/sync/start_date') ?? '1970-01-01';

        $collection = $this->orderCollectionFactory->create()
            ->addFieldToFilter('status', ['in' => ['pending', 'processing']])
            ->addFieldToFilter('dynamics_synced', ['null' => true])
            ->addFieldToFilter('created_at', ['gteq' => $startDate]);

        foreach ($collection as $order) {
            try {
                $data = $this->prepareOrderData($order);
                $response = $this->api->sendRequest('POST', '/orders', $data);

                if (isset($response['success'])) {
                    $order->setData('dynamics_synced', 1)->save();
                } else {
                    $this->logger->error('Order sync failed', ['order_id' => $order->getId(), 'response' => $response]);
                }
            } catch (\Exception $e) {
                $this->logger->error('Order sync exception', ['order_id' => $order->getId(), 'exception' => $e->getMessage()]);
            }
        }
    }

    /**
     * Prepare order data for API request
     *
     * @param Order $order
     * @return array
     */
    private function prepareOrderData(Order $order): array
    {
            $data = [
                'order_id' => $order->getIncrementId(),
                'total' => $order->getGrandTotal(),
                'items' => [],
            ];

            if ($order->getCustomerIsGuest()) {
                $data['customer'] = [
                    'name' => $order->getBillingAddress()->getName(),
                    'email' => $order->getBillingAddress()->getEmail(),
                ];
        } else {
            $data['customer_id'] = $order->getCustomerId();
        }

            foreach ($order->getAllItems() as $item) {
                $data['items'][] = [
                    'sku' => $item->getSku(),
                    'qty' => $item->getQtyOrdered(),
                ];
        }

        return $data;
    }
}
