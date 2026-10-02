<?php

/**
 * Copyright © 2009-2025 Amasty. All Rights Reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace BusinessCentral\Products\Model\Export;

use BusinessCentral\Api\Api\ApiInterface;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Psr\Log\LoggerInterface;
use Magento\InventoryApi\Api\GetSourceItemsBySkuInterface;

class InventoryExporter
{
    private const DEFAULT_ENDPOINT = 'InventOnHand';

    private $api;
    private $stockRegistry;
    private $productCollectionFactory;
    private $config;
    private $logger;
    private $getSourceItemsBySku;

    public function __construct(
        ApiInterface $api,
        StockRegistryInterface $stockRegistry,
        ProductCollectionFactory $productCollectionFactory,
        ScopeConfigInterface $config,
        LoggerInterface $logger,
        GetSourceItemsBySkuInterface $getSourceItemsBySku
    ) {
        $this->api = $api;
        $this->stockRegistry = $stockRegistry;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->config = $config;
        $this->logger = $logger;
        $this->getSourceItemsBySku = $getSourceItemsBySku;
    }

    public function execute(): void
    {
        if (!$this->config->getValue('businesscentral_products/export_inventory/enabled')) {

            return;
        }

        $company = (string)$this->config->getValue('businesscentral_products/export_inventory/data_area');
        $journalName = (string)$this->config->getValue('businesscentral_products/export_inventory/journal_name');
        $warehouse = (string)($this->config->getValue('businesscentral_orders/sync/default_warehouse') ?? 'GB900');

        if ($journalName === '') {
            $this->logger->error('Inventory export failed', [
                'error' => 'Missing businesscentral_products/export_inventory/journal_name'
            ]);
            return;
        }

        $collection = $this->productCollectionFactory->create();
        $collection->addAttributeToSelect('is_in_dynamics', 'sku')
            ->addAttributeToFilter('is_in_dynamics', 1);
        try {
            $headerPayload = [
                'dataAreaId' => $company,
                'JournalNameId' => $journalName,
                'DefaultWarehouseId' => $warehouse,
                'Description' => 'Magento inventory sync'
            ];
            $headerResponse = $this->api->sendRequest('POST', '/InventoryCountingJournalHeaders', $headerPayload);
            $journalNumber = isset($headerResponse['JournalNumber']) ? (string)$headerResponse['JournalNumber'] : '';
            if ($journalNumber === '') {
                $this->logger->error('Inventory export failed', [
                    'error' => 'Missing JournalNumber in header response'
                ]);
                return;
            }

            foreach ($collection as $product) {
                try {
                    $qty = 0.0;
                    $sourceItems = $this->getSourceItemsBySku->execute((string)$product->getSku());
                    foreach ($sourceItems as $sourceItem) {
                        if ($sourceItem->getSourceCode() === 'default') {
                            $qty = (float)$sourceItem->getQuantity();
                            break;
                        }
                    }

                    $linePayload = [
                        'dataAreaId' => $company,
                        'JournalNumber' => $journalNumber,
                        'ItemNumber' => (string)$product->getSku(),
                        'CountedQuantity' => $qty,
                        'CountingDate' => (new \DateTime('now', new \DateTimeZone('UTC')))->format('Y-m-d\\TH:i:s\\Z')
                    ];

                    $this->api->sendRequest('POST', '/InventoryCountingJournalLines', $linePayload);
                } catch (\Throwable $e) {
                    $this->logger->error('Inventory export failed', [
                        'sku' => $product->getSku(),
                        'exception' => $e->getMessage(),
                    ]);
                }
            }

            if ((bool)$this->config->getValue('businesscentral_products/export_inventory/post_after_sync')) {
                if ($company !== '') {
                    try {
                        $this->postCountingJournal($company, $journalNumber);
                    } catch (\Throwable $e) {
                        $this->logger->error('Inventory journal post failed', [
                            'company' => $company,
                            'journal' => $journalNumber,
                            'error' => $e->getMessage()
                        ]);
                    }
                }
            }
        } catch (\Throwable $e) {
            $this->logger->error('Inventory export failed', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function postCountingJournal(string $company, string $journalNumber): void
    {
        $path = "/InventoryCountingJournalHeaders(dataAreaId='" . rawurlencode($company) . "',JournalNumber='" . rawurlencode($journalNumber) . "')/Microsoft.Dynamics.DataEntities.Post";
        try {
            $this->api->sendRequest('POST', $path, []);
        } catch (\Throwable $e) {
            try {
                $fallback = "/InventoryCountingJournalHeaders(dataAreaId='" . rawurlencode($company) . "',JournalNumber='" . rawurlencode($journalNumber) . "')/Post";
                $this->api->sendRequest('POST', $fallback, []);
            } catch (\Throwable $ee) {
                $this->logger->error('Inventory journal post failed', [
                    'company' => $company,
                    'journal' => $journalNumber,
                    'error' => $ee->getMessage()
                ]);
            }
        }
    }
}


