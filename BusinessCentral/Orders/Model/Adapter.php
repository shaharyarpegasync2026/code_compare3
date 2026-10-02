<?php

/**
 * Copyright © 2009-2025 Amasty. All Rights Reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace BusinessCentral\Orders\Model;

use BusinessCentral\Api\Api\ApiInterface;
use BusinessCentral\Customers\Model\Adapter as CustomerAdapter;
use BusinessCentral\Customers\Api\CustomerRepositoryInterface as BusinessCentralCustomerRepositoryInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Psr\Log\LoggerInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Model\Order;
use Magento\Customer\Model\CustomerFactory;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Sales\Model\ResourceModel\Order\Creditmemo\CollectionFactory as CreditmemoCollectionFactory;
use Magento\Sales\Api\CreditmemoRepositoryInterface;
use BusinessCentral\Orders\Model\CancelledOrderFactory;
use BusinessCentral\Orders\Api\CancelledOrderRepositoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Class Adapter
 *
 * @package BusinessCentral\Orders\Model
 */
class Adapter
{
    /**
     * @var ApiInterface
     */
    private $api;

    /**
     * @var CustomerAdapter
     */
    private $customerAdapter;

    /**
     * @var CollectionFactory
     */
    private $orderCollectionFactory;

    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var ScopeConfigInterface
     */
    private $config;

    /**
     * @var LoggerInterface
     */
    private $logger;

    private $customerFactory;

    private $customerRepository;

    /**
     * @var BusinessCentralCustomerRepositoryInterface
     */
    private $businessCentralCustomerRepository;

    /**
     * @var CreditmemoCollectionFactory
     */
    private $creditmemoCollectionFactory;

    /**
     * @var CreditmemoRepositoryInterface
     */
    private $creditmemoRepository;

    /**
     * @var CancelledOrderFactory
     */
    private $cancelledOrderFactory;

    /**
     * @var CancelledOrderRepositoryInterface
     */
    private $cancelledOrderRepository;

    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var array
     */
    private $dynamicsItemNumberCache = [];

    public function __construct(
        ApiInterface $api,
        CustomerAdapter $customerAdapter,
        CollectionFactory $orderCollectionFactory,
        OrderRepositoryInterface $orderRepository,
        ScopeConfigInterface $config,
        LoggerInterface $logger,
        CustomerFactory $customerFactory,
        CustomerRepositoryInterface $customerRepository,
        BusinessCentralCustomerRepositoryInterface $businessCentralCustomerRepository,
        CreditmemoCollectionFactory $creditmemoCollectionFactory,
        CreditmemoRepositoryInterface $creditmemoRepository,
        CancelledOrderFactory $cancelledOrderFactory,
        CancelledOrderRepositoryInterface $cancelledOrderRepository,
        ProductRepositoryInterface $productRepository
    ) {
        $this->api = $api;
        $this->customerAdapter = $customerAdapter;
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->orderRepository = $orderRepository;
        $this->config = $config;
        $this->logger = $logger;
        $this->customerFactory = $customerFactory;
        $this->customerRepository = $customerRepository;
        $this->businessCentralCustomerRepository = $businessCentralCustomerRepository;
        $this->creditmemoCollectionFactory = $creditmemoCollectionFactory;
        $this->creditmemoRepository = $creditmemoRepository;
        $this->cancelledOrderFactory = $cancelledOrderFactory;
        $this->cancelledOrderRepository = $cancelledOrderRepository;
        $this->productRepository = $productRepository;
    }

    /**
     * Sync orders to Dynamics
     *
     * @return void
     */
    public function syncOrders()
    {
        $startDate = (string)($this->config->getValue('businesscentral_orders/sync/start_date') ?? '1970-01-01');
        $company = (string)($this->config->getValue('businesscentral_orders/sync/default_company') ?? 'GB01');
        $syncStatuses = explode(',', (string)($this->config->getValue('businesscentral_orders/sync/statuses') ?? 'pending,processing'));

        $collection = $this->orderCollectionFactory->create()
            ->addFieldToFilter('dynamics365_code', ['null' => true])
            ->addFieldToFilter('status', ['in' => $syncStatuses])
            ->addFieldToFilter('created_at', ['gteq' => $startDate]);

        foreach ($collection as $order) {
            try {
                $incrementId = $order->getIncrementId();
                $customerId = $order->getCustomerId();

                $customerEmail = $order->getCustomerEmail();
                if ($customerEmail) {
                    try {
                        $businessCentralCustomer = $this->businessCentralCustomerRepository->getByEmail($customerEmail);
                        $dynamicsCode = $businessCentralCustomer->getDynamics365Code();
                        if (!$dynamicsCode) {
                            $customerSynced = $this->syncCustomerIfNeeded($customerEmail);
                            if (!$customerSynced) {
                                $this->logger->error('Failed to sync customer, skipping order', [
                                    'order_id' => $order->getId(),
                                    'customer_email' => $customerEmail
                                ]);
                                continue;
                            }
                        }
                    } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
                        $customerSynced = $this->syncCustomerIfNeeded($customerEmail);
                        if (!$customerSynced) {
                            $this->logger->error('Failed to sync customer, skipping order', [
                                'order_id' => $order->getId(),
                                'customer_email' => $customerEmail
                            ]);
                            continue;
                        }
                    }
                }

                $existingOrder = $this->findOrderByIncrementId($incrementId);
                if ($existingOrder) {
                    if (isset($existingOrder['SalesOrderNumber'])) {
                        $order->setData('dynamics365_code', $existingOrder['SalesOrderNumber']);
                        $order->save();
                    }
                    continue;
                }

                $data = $this->prepareOrderData($order, $company);

                try {
                    $response = $this->api->sendRequest('POST', '/SalesOrderHeadersV2', $data);
                    
                    if (isset($response['SalesOrderNumber'])) {
                        $order->setData('dynamics365_code', $response['SalesOrderNumber']);
                        $order->save();
                    } else {
                        $this->logger->error('Order creation failed - no SalesOrderNumber in response', [
                            'order_id' => $order->getId(),
                            'increment_id' => $incrementId,
                            'response' => $response
                        ]);
                    }
                } catch (LocalizedException $e) {
                    if (strpos($e->getMessage(), 'already exists') !== false) {
                        $existingOrder = $this->findOrderByIncrementId($incrementId);
                        if ($existingOrder && isset($existingOrder['SalesOrderNumber'])) {
                            $order->setData('dynamics365_code', $existingOrder['SalesOrderNumber']);
                            $order->save();
                        } else {
                            $this->logger->error('Failed to find existing order after creation error', [
                                'order_id' => $order->getId(),
                                'increment_id' => $incrementId
                            ]);
                        }
                    } else {
                        $this->logger->error('Order creation failed with unexpected error', [
                            'order_id' => $order->getId(),
                            'increment_id' => $incrementId,
                            'error' => $e->getMessage()
                        ]);
                        throw $e;
                    }
                }
            } catch (LocalizedException $e) {
                $this->logger->error('Order sync exception', [
                    'order_id' => $order->getId(),
                    'increment_id' => $order->getIncrementId(),
                    'exception' => $e->getMessage()
                ]);
            }
        }
    }

    /**
     * Prepare order data for API request
     *
     * @param Order $order
     * @param string $company
     * @return array
     */
    private function prepareOrderData(Order $order, string $company): array
    {
        $data = [
            'dataAreaId' => $company,
            'RequestedReceiptDate' => $this->formatDateTimeForDynamics($this->getTomorrowDate()),
            'CurrencyCode' => $order->getOrderCurrencyCode(),
            'OrderingCustomerAccountNumber' => $this->getCustomerNumber($order)
        ];

        return $data;
    }

    /**
     * Format datetime for Dynamics 365
     *
     * @param string $dateTime
     * @return string
     */
    private function formatDateTimeForDynamics(string $dateTime): string
    {
        $date = new \DateTime($dateTime);
        $date->setTimezone(new \DateTimeZone('UTC'));
        return $date->format('Y-m-d\TH:i:s\Z');
    }

    /**
     * Get tomorrow's date for RequestedReceiptDate
     *
     * @return string
     */
    private function getTomorrowDate(): string
    {
        $date = new \DateTime();
        $date->add(new \DateInterval('P1D')); // Add 1 day to current date
        return $date->format('Y-m-d H:i:s');
    }


    /**
     * Prepare order lines data
     *
     * @param Order $order
     * @return array
     */
    private function prepareOrderLines(Order $order, string $defaultWarehouse, string $defaultSiteId, string $defaultSalesUnit, string $salesOrderNumber, string $dataAreaId): array
    {
        $lines = [];
        
        foreach ($order->getAllVisibleItems() as $item) {
            $itemNumber = $this->resolveDynamicsItemNumber((string)$item->getSku(), $dataAreaId);
            $lines[] = [
                'dataAreaId' => $dataAreaId,
                'SalesOrderNumber' => $salesOrderNumber,
                'LineNumber' => (int)$item->getItemId(),
                'ItemNumber' => $itemNumber,
                'LineDescription' => $item->getName(),
                'ShippingWarehouseId' => $defaultWarehouse,
                'ShippingSiteId' => $defaultSiteId,
                'SalesUnitSymbol' => $defaultSalesUnit,
                'OrderedSalesQuantity' => (float)$item->getQtyOrdered(),
                'SalesPrice' => (float)$item->getPrice(),
                'LineAmount' => (float)($item->getQtyOrdered() * $item->getPrice())
            ];
        }

        return $lines;
    }

    /**
     * @param string $barcode
     * @param string $dataAreaId
     * @return string
     */
    private function resolveDynamicsItemNumber(string $barcode, string $dataAreaId): string
    {
        $barcode = trim($barcode);
        if ($barcode === '') {
            return $barcode;
        }

        if (isset($this->dynamicsItemNumberCache[$dataAreaId][$barcode])) {
            return $this->dynamicsItemNumberCache[$dataAreaId][$barcode];
        }

        try {
            $product = $this->productRepository->get($barcode);
            $cached = (string)$product->getData('dynamics365_item_number');
            if ($cached !== '') {
                $this->dynamicsItemNumberCache[$dataAreaId][$barcode] = $cached;
                return $cached;
            }
        } catch (NoSuchEntityException $e) {
            $product = null;
        } catch (\Exception $e) {
            $this->logger->error('Failed to load product for dynamics item number', [
                'sku' => $barcode,
                'error' => $e->getMessage()
            ]);
            $product = null;
        }

        $resolved = $this->fetchDynamicsItemNumberByBarcode($barcode, $dataAreaId);

        if ($resolved !== null) {
            if ($product) {
                try {
                    $product->setData('dynamics365_item_number', $resolved);
                    $this->productRepository->save($product);
                } catch (\Exception $e) {
                    $this->logger->error('Failed to save dynamics item number to product', [
                        'sku' => $barcode,
                        'value' => $resolved,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            $this->dynamicsItemNumberCache[$dataAreaId][$barcode] = $resolved;
            return $resolved;
        }

        $this->dynamicsItemNumberCache[$dataAreaId][$barcode] = $barcode;
        return $barcode;
    }

    /**
     * @param string $barcode
     * @param string $dataAreaId
     * @return string|null
     */
    private function fetchDynamicsItemNumberByBarcode(string $barcode, string $dataAreaId): ?string
    {
        try {
            $escapedBarcode = $this->escapeODataString($barcode);
            $escapedDataAreaId = $this->escapeODataString($dataAreaId);

            $response = $this->api->sendRequest('GET', '/ProductBarcodesV3', [
                '$select' => 'ItemNumber,Barcode,dataAreaId',
                '$filter' => "dataAreaId eq '{$escapedDataAreaId}' and Barcode eq '{$escapedBarcode}'",
                '$top' => 1
            ]);

            if (!isset($response['value']) || !is_array($response['value']) || count($response['value']) === 0) {
                return null;
            }

            $row = $response['value'][0];
            if (!is_array($row) || !isset($row['ItemNumber'])) {
                return null;
            }

            $itemNumber = trim((string)$row['ItemNumber']);
            return $itemNumber !== '' ? $itemNumber : null;
        } catch (LocalizedException $e) {
            $this->logger->error('Failed to resolve dynamics item number by barcode', [
                'barcode' => $barcode,
                'dataAreaId' => $dataAreaId,
                'error' => $e->getMessage()
            ]);
            return null;
        } catch (\Exception $e) {
            $this->logger->error('Unexpected error while resolving dynamics item number by barcode', [
                'barcode' => $barcode,
                'dataAreaId' => $dataAreaId,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * @param string $value
     * @return string
     */
    private function escapeODataString(string $value): string
    {
        return str_replace("'", "''", $value);
    }

    /**
     * Get customer number for order
     *
     * @param Order $order
     * @return string
     */
    private function getCustomerNumber(Order $order): string
    {
        $customerEmail = $order->getCustomerEmail();
        
        if ($customerEmail) {
            try {
                $businessCentralCustomer = $this->businessCentralCustomerRepository->getByEmail($customerEmail);
                $dynamicsCode = $businessCentralCustomer->getDynamics365Code();
                if ($dynamicsCode) {
                    return $dynamicsCode;
                }
            } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
            }
        }
        
        return 'GUEST';
    }

    /**
     * Find order by increment ID in Dynamics 365
     *
     * @param string $incrementId
     * @return array|null
     */
    private function findOrderByIncrementId(string $incrementId): ?array
    {
        try {
            $response = $this->api->sendRequest('GET', '/SalesOrderHeadersV2', [
                '$filter' => "SalesOrderNumber eq '{$incrementId}'"
            ]);
            
            if (isset($response['value']) && is_array($response['value']) && count($response['value']) > 0) {
                $order = $response['value'][0];

                if (isset($order['SalesOrderNumber']) && $order['SalesOrderNumber'] == $incrementId) {
                    return $order;
                }
            }
        } catch (LocalizedException $e) {
            $this->logger->error('Failed to search for order by increment ID', [
                'increment_id' => $incrementId, 
                'error' => $e->getMessage()
            ]);
        }
        
        return null;
    }

    /**
     * Sync customer if needed
     *
     * @param \Magento\Customer\Model\Customer $customer
     * @return bool
     */
    private function syncCustomerIfNeeded(string $customerEmail): bool
    {
        try {
            $success = $this->customerAdapter->syncSingleCustomer($customerEmail);

            if ($success) {
                return true;
            } else {
                $this->logger->error('Customer sync failed', [
                    'email' => $customerEmail
                ]);
                return false;
            }

        } catch (\Exception $e) {
            $this->logger->error('Failed to sync customer', [
                'email' => $customerEmail,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Sync single order to Dynamics
     *
     * @param Order $order
     * @return bool
     */
    public function syncSingleOrder(Order $order): bool
    {
        try {
            $incrementId = $order->getIncrementId();
            $customerId = $order->getCustomerId();
            $company = (string)($this->config->getValue('businesscentral_orders/sync/default_company') ?? 'GB01');

            $customerEmail = $order->getCustomerEmail();
            if ($customerEmail) {
                try {
                    $businessCentralCustomer = $this->businessCentralCustomerRepository->getByEmail($customerEmail);
                    $dynamicsCode = $businessCentralCustomer->getDynamics365Code();
                    if (!$dynamicsCode) {
                        $customerSynced = $this->syncCustomerIfNeeded($customerEmail);
                        if (!$customerSynced) {
                            $this->logger->error('Failed to sync customer, skipping order', [
                                'order_id' => $order->getId(),
                                'customer_email' => $customerEmail
                            ]);
                            return false;
                        }
                    }
                } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
                    $customerSynced = $this->syncCustomerIfNeeded($customerEmail);
                    if (!$customerSynced) {
                        $this->logger->error('Failed to sync customer, skipping order', [
                            'order_id' => $order->getId(),
                            'customer_email' => $customerEmail
                        ]);
                        return false;
                    }
                }
            }

            $existingOrder = $this->findOrderByIncrementId($incrementId);
            if ($existingOrder) {
                if (isset($existingOrder['SalesOrderNumber'])) {
                    $order->setData('dynamics365_code', $existingOrder['SalesOrderNumber']);
                    $order->save();
                    return true;
                }
                return false;
            }

            $headerData = $this->prepareOrderData($order, $company);

            $headerResponse = $this->api->sendRequest('POST', '/SalesOrderHeadersV2', $headerData);
            
            if (isset($headerResponse['SalesOrderNumber'])) {
                $salesOrderNumber = $headerResponse['SalesOrderNumber'];
                $order->setData('dynamics365_code', $salesOrderNumber);
                $order->save();

                $linesCreated = $this->createOrderLines($salesOrderNumber, $company, $order);
                if ($linesCreated) {
                    return true;
                } else {
                    $this->logger->error('Failed to create order lines', ['order_id' => $order->getId()]);
                    return false;
                }
            } else {
                $this->logger->error('Order header creation failed - no SalesOrderNumber', [
                    'order_id' => $order->getId(),
                    'response' => $headerResponse
                ]);
                return false;
            }
        } catch (\Exception $e) {
            $this->logger->error('Single order sync failed', [
                'order_id' => $order->getId(),
                'increment_id' => $incrementId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Create order lines separately
     *
     * @param string $salesOrderNumber
     * @param string $dataAreaId
     * @param Order $order
     * @return bool
     */
    private function createOrderLines(string $salesOrderNumber, string $dataAreaId, Order $order): bool
    {
        $defaultWarehouse = (string)($this->config->getValue('businesscentral_orders/sync/default_warehouse') ?? 'GB900');
        $defaultSiteId = (string)($this->config->getValue('businesscentral_orders/sync/default_site_id') ?? 'GB900');
        $defaultSalesUnit = (string)($this->config->getValue('businesscentral_orders/sync/default_sales_unit') ?? 'SET');

        $sequence = 1;
        $lines = $this->prepareOrderLines($order, $defaultWarehouse, $defaultSiteId, $defaultSalesUnit, $salesOrderNumber, $dataAreaId);

        foreach ($lines as $line) {
            try {
                $line['LineCreationSequenceNumber'] = $sequence++;
                $this->api->sendRequest('POST', '/SalesOrderLines', $line);
            } catch (\Exception $e) {
                $this->logger->error('Failed to create order line', [
                    'sales_order_number' => $salesOrderNumber,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                return false;
            }
        }

        return true;
    }

    private function validateSalesUnit(string $unitSymbol): bool
    {
        $endpoints = ['/unitOfMeasures', '/UnitsOfMeasure'];
        
        foreach ($endpoints as $endpoint) {
            try {
                $response = $this->api->sendRequest('GET', $endpoint, [
                    '$filter' => "Code eq '{$unitSymbol}'"
                ]);
                
                if (isset($response['value']) && count($response['value']) > 0) {
                    return true;
                }
                
                $allUnits = $this->api->sendRequest('GET', $endpoint, ['$top' => 10]);
                $availableCodes = array_map(function($unit) { return $unit['Code'] ?? $unit['Symbol'] ?? 'N/A'; }, $allUnits['value'] ?? []);
                
                $this->logger->error('Invalid sales unit symbol', ['unit' => $unitSymbol, 'endpoint' => $endpoint]);
            } catch (\Exception $e) {
                $errorMessage = $e->getMessage();
                if (strpos($errorMessage, 'status 404') === false) {
                    $this->logger->error('Sales unit validation failed unexpectedly', [
                        'endpoint' => $endpoint,
                        'error' => $errorMessage,
                        'trace' => $e->getTraceAsString()
                    ]);
                    return false;
                }
            }
        }
        
        if (true) {
            try {
                $productsResponse = $this->api->sendRequest('GET', '/ReleasedProductsV2', [
                    '$select' => 'SalesUnitOfMeasureSymbol',
                    '$top' => 100
                ]);
                
                $symbols = array_unique(array_map(function($product) {
                    return $product['SalesUnitOfMeasureSymbol'] ?? 'N/A';
                }, $productsResponse['value'] ?? []));
                
            } catch (\Exception $e) {
            }
        }

        $this->logger->error('All unit endpoints failed validation', ['unit' => $unitSymbol]);
        return false;
    }

    public function syncCreditMemos(): void
    {
        $company = (string)($this->config->getValue('businesscentral_orders/sync_credit_memos/data_area') ?? 'GB01');
        $collection = $this->creditmemoCollectionFactory->create()
            ->addFieldToFilter('entity_id', '6485');

        foreach ($collection as $creditmemo) {
            try {
                $this->exportCreditMemo($creditmemo, $company);
            } catch (\Throwable $e) {
                $this->logger->error('Credit memo export failed', [
                    'creditmemo' => $creditmemo->getIncrementId(),
                    'error' => $e->getMessage()
                ]);
            }
        }
    }

    private function exportCreditMemo(\Magento\Sales\Model\Order\Creditmemo $creditmemo, string $company): void
    {
        $payload = [
            'dataAreaId' => $company,
            'SalesOrderNumber' => 'RET-' . (string)$creditmemo->getIncrementId(),
            'SalesOrderType' => 'ReturnOrder',
            'CustomerAccountNumber' => $this->getCustomerNumber($creditmemo->getOrder()),
            'OrderTotalAmount' => -(float)$creditmemo->getGrandTotal(),
            'CurrencyCode' => (string)$creditmemo->getOrderCurrencyCode(),
            'RequestedDeliveryDate' => (new \DateTime('now', new \DateTimeZone('UTC')))->format('Y-m-d\\TH:i:s\\Z'),
            'OriginalSalesOrderNumber' => (string)$creditmemo->getOrder()->getIncrementId()
        ];

        try {
            $this->api->sendRequest('POST', '/SalesOrderHeadersV2', $payload);
        } catch (\Throwable $e) {
            throw $e;
        }
    }

    public function syncCancelledOrders(): void
    {
        $collection = $this->orderCollectionFactory->create()
            ->addFieldToFilter('state', 'canceled')
            ->addFieldToFilter('entity_id', '31161')
            ->addFieldToFilter('dynamics365_code', ['notnull' => true]);

        foreach ($collection as $order) {
            try {
                $dynamicsCode = $order->getData('dynamics365_code');
                if (strpos($dynamicsCode, '-') === false) {
                    $this->logger->error('Invalid dynamics365_code format', ['order' => $order->getIncrementId(), 'code' => $dynamicsCode]);
                    continue;
                }
                list($dataAreaIdRaw) = explode('-', $dynamicsCode, 2);
                $dataAreaIdUpper = strtoupper($dataAreaIdRaw);
                $dataAreaIdLower = strtolower($dataAreaIdRaw);
                $salesOrderNumber = $dynamicsCode;

                $endpointUpper = "/SalesOrderHeadersV2(dataAreaId='{$dataAreaIdUpper}',SalesOrderNumber='{$salesOrderNumber}')";
                $endpointLower = "/SalesOrderHeadersV2(dataAreaId='{$dataAreaIdLower}',SalesOrderNumber='{$salesOrderNumber}')";
                $dataAreaId = $dataAreaIdUpper;
                $endpoint = $endpointUpper;
                try {
                    $response = $this->api->sendRequest('GET', $endpoint);
                } catch (\Throwable $e) {
                    if (strpos($e->getMessage(), 'status 404') !== false) {
                        try {
                            $response = $this->api->sendRequest('GET', $endpointLower);
                            $dataAreaId = $dataAreaIdLower;
                            $endpoint = $endpointLower;
                        } catch (\Throwable $eLower) {
                            if (strpos($eLower->getMessage(), 'status 404') !== false) {
                                try {
                                    $hdrUrlUpper = "/SalesOrderHeadersV2?\$select=dataAreaId,SalesOrderNumber,SalesOrderProcessingStatus&\$filter=dataAreaId%20eq%20'{$dataAreaIdUpper}'%20and%20SalesOrderNumber%20eq%20'{$salesOrderNumber}'&\$top=1";
                                    $list = $this->api->sendRequest('GET', $hdrUrlUpper);
                                    $first = $list['value'][0] ?? null;
                                    if (!$first) {
                                        $hdrUrlLower = "/SalesOrderHeadersV2?\$select=dataAreaId,SalesOrderNumber,SalesOrderProcessingStatus&\$filter=dataAreaId%20eq%20'{$dataAreaIdLower}'%20and%20SalesOrderNumber%20eq%20'{$salesOrderNumber}'&\$top=1";
                                        $list2 = $this->api->sendRequest('GET', $hdrUrlLower);
                                        $first = $list2['value'][0] ?? null;
                                    }
                                    if (!$first) {
                                        $this->logger->info('Order not found in D365 via list, skipping cancellation', ['order' => $order->getIncrementId()]);
                                        continue;
                                    }
                                    $dataAreaId = (string)($first['dataAreaId'] ?? $dataAreaIdUpper);
                                    $salesOrderNumber = (string)($first['SalesOrderNumber'] ?? $salesOrderNumber);
                                    $endpoint = "/SalesOrderHeadersV2(dataAreaId='{$dataAreaId}',SalesOrderNumber='{$salesOrderNumber}')";
                                    $response = $first;
                                } catch (\Throwable $e2) {
                                    $this->logger->info('Order not found in D365 via list, skipping cancellation', ['order' => $order->getIncrementId()]);
                                    continue;
                                }
                            } else {
                                throw $eLower;
                            }
                        }
                    } else {
                        throw $e;
                    }
                }

                if (!empty($response['SalesOrderProcessingStatus']) && $response['SalesOrderProcessingStatus'] === 'Canceled') {
                    continue;
                }

                $headers = ['If-Match' => '*'];
                $updatedAnyLine = false;
                try {
                    $linesUrl = "/CDSSalesOrderLinesV2?\$select=dataAreaId,SalesOrderNumber,LineCreationSequenceNumber&\$filter=dataAreaId%20eq%20'{$dataAreaId}'%20and%20SalesOrderNumber%20eq%20'{$salesOrderNumber}'&\$top=2000";
                    $linesResponse = $this->api->sendRequest('GET', $linesUrl);
                    $lines = $linesResponse['value'] ?? [];
                    foreach ($lines as $line) {
                        $lcsn = $line['LineCreationSequenceNumber'] ?? null;
                        if ($lcsn === null) {
                            continue;
                        }
                        $lineEndpoint = "/CDSSalesOrderLinesV2(dataAreaId='{$dataAreaId}',SalesOrderNumber='{$salesOrderNumber}',LineCreationSequenceNumber={$lcsn})";
                        try {
                            $this->api->sendRequest('PATCH', $lineEndpoint, ['SalesDeliverNow' => 0], $headers);
                            $updatedAnyLine = true;
                        } catch (\Throwable $eLine) {
                            if (strpos($eLine->getMessage(), "property named 'SalesDeliverNow'") !== false) {
                                try {
                                    $this->api->sendRequest('PATCH', $lineEndpoint, ['InventDeliverNow' => 0], $headers);
                                    $updatedAnyLine = true;
                                } catch (\Throwable $eLine2) {
                                    $this->logger->error('Failed to zero deliver quantity on line', [
                                        'order' => $order->getIncrementId(),
                                        'line' => $lcsn,
                                        'error' => $eLine2->getMessage()
                                    ]);
                                }
                            } else {
                                $this->logger->error('Failed to zero deliver quantity on line', [
                                    'order' => $order->getIncrementId(),
                                    'line' => $lcsn,
                                    'error' => $eLine->getMessage()
                                ]);
                            }
                        }
                    }
                } catch (\Throwable $eLines) {
                    $this->logger->error('Failed to fetch or update sales order lines', [
                        'order' => $order->getIncrementId(),
                        'error' => $eLines->getMessage()
                    ]);
                }

                if ($updatedAnyLine) {
                    $this->logger->info('D365 line remainders set to zero for cancellation', [
                        'order' => $order->getIncrementId(),
                        'sales_order_number' => $salesOrderNumber,
                        'data_area_id' => $dataAreaId
                    ]);
                } else {
                    $this->logger->warning('No D365 line updates performed for cancellation', [
                        'order' => $order->getIncrementId(),
                        'sales_order_number' => $salesOrderNumber,
                        'data_area_id' => $dataAreaId
                    ]);
                }
                
                if ($updatedAnyLine) {
                    try {
                        $existing = null;
                        try {
                            $existing = $this->cancelledOrderRepository->getByOrderId((int)$order->getId());
                        } catch (\Throwable $ignored) {}
                        if (!$existing || !(int)$existing->getId()) {
                            $cancelled = $this->cancelledOrderFactory->create();
                            $cancelled->setOrderId((int)$order->getId());
                            $cancelled->setDynamicsOrderNumber($dynamicsCode);
                            $cancelled->setCancelledAt(date('Y-m-d H:i:s'));
                            $this->cancelledOrderRepository->save($cancelled);
                        }
                    } catch (\Throwable $e) {
                        $this->logger->error('Failed to save cancelled order locally', [
                            'order' => $order->getIncrementId(),
                            'error' => $e->getMessage()
                        ]);
                    }
                }
            } catch (\Throwable $e) {
                if (strpos($e->getMessage(), 'status 404') !== false) {
                    $this->logger->info('Order not found in D365, skipping cancellation', ['order' => $order->getIncrementId()]);
                    continue;
                }
                $this->logger->error('Order cancellation failed', ['order' => $order->getIncrementId(), 'error' => $e->getMessage()]);
            }
        }
    }
}
