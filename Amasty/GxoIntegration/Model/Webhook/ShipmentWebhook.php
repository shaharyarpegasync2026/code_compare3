<?php

/**
 * Copyright © 2009-2026 Amasty. All Rights Reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace Amasty\GxoIntegration\Model\Webhook;

use Amasty\GxoIntegration\Api\ShipmentWebhookInterface;
use Amasty\GxoIntegration\Logger\Logger;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\DB\TransactionFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Shipment\TrackFactory;
use Magento\Sales\Model\Order\ShipmentFactory;

class ShipmentWebhook implements ShipmentWebhookInterface
{
    private const CARRIER_CODE = 'custom';
    private const CARRIER_TITLE = 'Royal Mail';

    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var ShipmentFactory
     */
    private $shipmentFactory;

    /**
     * @var TransactionFactory
     */
    private $transactionFactory;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var ProductCollectionFactory
     */
    private $productCollectionFactory;

    /**
     * @var TrackFactory
     */
    private $trackFactory;

    public function __construct(
        OrderRepositoryInterface $orderRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        ShipmentFactory $shipmentFactory,
        TransactionFactory $transactionFactory,
        Logger $logger,
        ProductCollectionFactory $productCollectionFactory,
        TrackFactory $trackFactory
    ) {
        $this->orderRepository = $orderRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->shipmentFactory = $shipmentFactory;
        $this->transactionFactory = $transactionFactory;
        $this->logger = $logger;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->trackFactory = $trackFactory;
    }

    /**
     * @param mixed $payload
     * @return array
     * @throws LocalizedException
     */
    public function createShipment(mixed $payload = []): array
    {
        if (!is_array($payload) || !$payload) {
            throw new LocalizedException(__('Payload is required.'));
        }

        $shipConf = $this->extractShipConf($payload);
        $orderId = trim((string)($shipConf['ORDER_ID'] ?? ''));
        $customerId = trim((string)($shipConf['CUSTOMER_ID'] ?? ''));

        if ($orderId === '') {
            throw new LocalizedException(__('ORDER_ID is required.'));
        }

        $parcels = $shipConf['parcels'] ?? [];
        if (!is_array($parcels) || !$parcels) {
            throw new LocalizedException(__('parcels array is required.'));
        }

        $order = $this->getOrderByIncrementId($orderId);

        if (!$order->canShip()) {
            throw new LocalizedException(__('Order cannot be shipped.'));
        }

        $aggregated = $this->aggregateParcels($parcels);
        $itemsToShip = $this->buildItemsToShip($order, $aggregated['items']);

        if (!$itemsToShip) {
            return [
                'success' => true,
                'order_increment_id' => $order->getIncrementId(),
                'order_id' => (int)$order->getEntityId(),
                'message' => 'Nothing to ship.'
            ];
        }

        $shipment = $this->shipmentFactory->create($order, $itemsToShip);
        $shipment->register();
        $shipment->getOrder()->setIsInProcess(true);

        foreach ($aggregated['tracks'] as $trackData) {
            $track = $this->trackFactory->create();
            $track->setCarrierCode(self::CARRIER_CODE);
            $track->setTitle(self::CARRIER_TITLE);
            $track->setTrackNumber($trackData['tracking_number']);
            $shipment->addTrack($track);
        }

        $transaction = $this->transactionFactory->create();
        $transaction->addObject($shipment);
        $transaction->addObject($shipment->getOrder());
        $transaction->save();

        $trackingNumbers = array_column($aggregated['tracks'], 'tracking_number');

        $this->logger->info('GXO webhook shipment created', [
            'order_increment_id' => $orderId,
            'order_id' => (int)$order->getEntityId(),
            'customer_id' => $customerId,
            'parcels_count' => count($parcels),
            'tracking_numbers' => $trackingNumbers,
            'shipment_id' => (int)$shipment->getEntityId(),
            'shipment_increment_id' => (string)$shipment->getIncrementId()
        ]);

        return [
            'success' => true,
            'order_increment_id' => $order->getIncrementId(),
            'order_id' => (int)$order->getEntityId(),
            'shipment_id' => (int)$shipment->getEntityId(),
            'shipment_increment_id' => (string)$shipment->getIncrementId(),
            'tracking_numbers' => $trackingNumbers
        ];
    }

    /**
     * @param array $payload
     * @return array
     * @throws LocalizedException
     */
    private function extractShipConf(array $payload): array
    {
        if (isset($payload['shipConf'])) {
            return $payload['shipConf'];
        }

        if (isset($payload[0]['shipConf'])) {
            return $payload[0]['shipConf'];
        }

        if (isset($payload['ORDER_ID'])) {
            return $payload;
        }

        throw new LocalizedException(__('Invalid payload structure: shipConf not found.'));
    }

    /**
     * @param array $parcels
     * @return array
     * @throws LocalizedException
     */
    private function aggregateParcels(array $parcels): array
    {
        $itemsByDynamicsNumber = [];
        $tracks = [];

        foreach ($parcels as $parcel) {
            if (!is_array($parcel)) {
                continue;
            }

            $trackingNumber = trim((string)($parcel['carrierContainerId'] ?? ''));
            if ($trackingNumber !== '') {
                $tracks[] = [
                    'tracking_number' => $trackingNumber,
                    'tracking_url' => trim((string)($parcel['SHIPPING_STATUS'] ?? '')),
                    'container_id' => trim((string)($parcel['containerId'] ?? ''))
                ];
            }

            $lines = $parcel['lines'] ?? [];
            if (!is_array($lines)) {
                continue;
            }

            foreach ($lines as $line) {
                if (!is_array($line)) {
                    continue;
                }

                $dynamicsNumber = trim((string)($line['SKU_ID'] ?? ''));
                $qty = (float)($line['QTY'] ?? 0);

                if ($dynamicsNumber === '' || $qty <= 0) {
                    continue;
                }

                if (!isset($itemsByDynamicsNumber[$dynamicsNumber])) {
                    $itemsByDynamicsNumber[$dynamicsNumber] = 0.0;
                }
                $itemsByDynamicsNumber[$dynamicsNumber] += $qty;
            }
        }

        if (!$itemsByDynamicsNumber) {
            throw new LocalizedException(__('No shippable items found in parcels.'));
        }

        return [
            'items' => $itemsByDynamicsNumber,
            'tracks' => $tracks
        ];
    }

    /**
     * @param Order $order
     * @param array $itemsByDynamicsNumber
     * @return array
     * @throws LocalizedException
     */
    private function buildItemsToShip(Order $order, array $itemsByDynamicsNumber): array
    {
        $skuMap = $this->resolveSkusByDynamicsItemNumber(array_keys($itemsByDynamicsNumber));

        $itemsToShip = [];
        foreach ($itemsByDynamicsNumber as $dynamicsNumber => $requestQty) {
            if (!isset($skuMap[$dynamicsNumber])) {
                throw new LocalizedException(
                    __('Product not found by dynamics365_item_number: %1', $dynamicsNumber)
                );
            }

            $orderItem = $this->getOrderItemBySku($order, $skuMap[$dynamicsNumber]);
            $qtyToShip = (float)$orderItem->getQtyToShip();

            if ($qtyToShip <= 0) {
                continue;
            }

            if ($requestQty > $qtyToShip) {
                throw new LocalizedException(
                    __(
                        'Requested QTY exceeds qty_to_ship. SKU: %1, Requested: %2, Available: %3',
                        $orderItem->getSku(),
                        $requestQty,
                        $qtyToShip
                    )
                );
            }

            $itemId = (int)$orderItem->getItemId();
            $itemsToShip[$itemId] = ($itemsToShip[$itemId] ?? 0) + $requestQty;
        }

        return $itemsToShip;
    }

    /**
     * @param string $incrementId
     * @return Order
     * @throws LocalizedException
     */
    private function getOrderByIncrementId(string $incrementId): Order
    {
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter('increment_id', $incrementId)
            ->setPageSize(1)
            ->create();

        $result = $this->orderRepository->getList($searchCriteria);
        $items = $result->getItems();
        $order = reset($items);
        if (!$order instanceof Order) {
            throw new LocalizedException(__('Order was not found.'));
        }

        return $order;
    }

    /**
     * @param array $dynamicsNumbers
     * @return array
     */
    private function resolveSkusByDynamicsItemNumber(array $dynamicsNumbers): array
    {
        $collection = $this->productCollectionFactory->create();
        $collection->addAttributeToFilter('dynamics365_item_number', ['in' => $dynamicsNumbers]);
        $collection->addAttributeToSelect(['dynamics365_item_number']);

        $map = [];
        foreach ($collection as $product) {
            $number = (string)$product->getData('dynamics365_item_number');
            $map[trim($number)] = (string)$product->getSku();
        }

        return $map;
    }

    /**
     * @param Order $order
     * @param string $sku
     * @return \Magento\Sales\Api\Data\OrderItemInterface
     * @throws LocalizedException
     */
    private function getOrderItemBySku(Order $order, string $sku)
    {
        foreach ($order->getAllItems() as $item) {
            if ((string)$item->getSku() !== $sku) {
                continue;
            }

            if ($item->getIsVirtual()) {
                throw new LocalizedException(__('Cannot create shipment for virtual item.'));
            }

            return $item;
        }

        throw new LocalizedException(__('Order item was not found by SKU.'));
    }
}
