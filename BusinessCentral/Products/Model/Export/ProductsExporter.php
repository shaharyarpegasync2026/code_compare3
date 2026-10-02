<?php

/**
 * Copyright © 2009-2025 Amasty. All Rights Reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace BusinessCentral\Products\Model\Export;

use BusinessCentral\Api\Api\ApiInterface;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Psr\Log\LoggerInterface;

class ProductsExporter
{
    private const DEFAULT_ENDPOINT = 'ProductsV2';

    private $api;
    private $productCollectionFactory;
    private $config;
    private $logger;

    public function __construct(
        ApiInterface $api,
        ProductCollectionFactory $productCollectionFactory,
        ScopeConfigInterface $config,
        LoggerInterface $logger
    ) {
        $this->api = $api;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->config = $config;
        $this->logger = $logger;
    }

    public function execute(): void
    {
        if (!$this->config->getValue('businesscentral_products/export_products/enabled')) {
            return;
        }

        $endpoint = (string)$this->config->getValue('businesscentral_products/export_products/endpoint') ?: self::DEFAULT_ENDPOINT;
        $barcodeAttribute = (string)$this->config->getValue('businesscentral_products/export_products/barcode_attribute') ?: 'barcode';
        $company = (string)$this->config->getValue('businesscentral_products/export_products/data_area');
        $itemModelGroup = (string)$this->config->getValue('businesscentral_products/export_products/item_model_group') ?: 'WA';
        $storageDimGroup = (string)$this->config->getValue('businesscentral_products/export_products/storage_dim_group') ?: 'SWL';
        $trackingDimGroup = (string)$this->config->getValue('businesscentral_products/export_products/tracking_dim_group') ?: 'NONE';
        $productGroup = (string)$this->config->getValue('businesscentral_products/export_products/product_group') ?: 'Z004';
        $inventoryUom = (string)$this->config->getValue('businesscentral_products/export_products/inventory_uom') ?: 'PC';
        $barcodeSetupId = (string)$this->config->getValue('businesscentral_products/export_products/barcode_setup_id') ?: 'DEF';

        $collection = $this->productCollectionFactory->create();
        $collection->addAttributeToSelect(['name', 'price', $barcodeAttribute])
            ->setPageSize(50); // Increased batch size for better performance

        $totalProcessed = 0;
        $totalSuccess = 0;
        $totalErrors = 0;

        foreach ($collection as $product) {
            $totalProcessed++;
            $this->logger->info('Processing product', [
                'sku' => $product->getSku(),
                'progress' => "{$totalProcessed}/" . $collection->getSize()
            ]);

            try {
                $barcodeValue = (string)$product->getData($barcodeAttribute);
                if ($barcodeValue === '') {
                    $barcodeValue = (string)$product->getSku();
                }

                $payload = [
                    'ProductNumber' => (string)$product->getSku(),
                    'ProductName' => (string)$product->getName(),
                    'HSOBarcode' => $barcodeValue,
                ];

                $existingProduct = $this->findProductBySku($product->getSku(), $endpoint);
                $masterProductExists = $existingProduct !== null;

                if ($masterProductExists) {
                    $this->logger->info('Master product already exists in Dynamics 365', [
                        'sku' => $product->getSku(),
                        'endpoint' => $endpoint
                    ]);
                } else {
                    try {
                        $this->api->sendRequest('POST', 'ProductsV2', $payload);
                        $this->logger->info('Master product created successfully', [
                            'sku' => $product->getSku(),
                            'endpoint' => 'ProductsV2'
                        ]);
                    } catch (\Throwable $e) {
                        $errorMessage = $e->getMessage();
                        if (strpos($errorMessage, 'status 400') !== false || 
                            strpos($errorMessage, 'already exists') !== false || 
                            strpos($errorMessage, 'The record already exists') !== false ||
                            strpos($errorMessage, 'Cannot create a record') !== false) {
                            $this->logger->info('Master product already exists in Dynamics 365 (creation failed)', [
                                'sku' => $product->getSku(),
                                'endpoint' => 'ProductsV2'
                            ]);
                            $masterProductExists = true;
                        } else {
                            $this->logger->error('Master product creation failed', [
                                'sku' => $product->getSku(),
                                'endpoint' => 'ProductsV2',
                                'payload' => $payload,
                                'exception' => $errorMessage,
                            ]);
                            $totalErrors++;
                            continue; // Skip to next product if creation failed
                        }
                    }
                }

                $releaseSuccess = $this->releaseProductToCompany($product->getSku());
                if ($releaseSuccess) {
                    $totalSuccess++;
                } else {
                    $totalErrors++;
                }
            } catch (\Throwable $e) {
                $this->logger->error('Product export failed', [
                    'sku' => $product->getSku(),
                    'endpoint' => $endpoint,
                    'payload' => $payload,
                    'exception' => $e->getMessage(),
                ]);
                $totalErrors++;
            }
        }

        // Log summary
        $this->logger->info('Product export completed', [
            'total_processed' => $totalProcessed,
            'total_success' => $totalSuccess,
            'total_errors' => $totalErrors,
            'success_rate' => $totalProcessed > 0 ? round(($totalSuccess / $totalProcessed) * 100, 2) . '%' : '0%'
        ]);
    }

    /**
     * Find existing product in Dynamics 365 by SKU
     *
     * @param string $sku
     * @param string $endpoint
     * @return array|null
     */
    private function findProductBySku(string $sku, string $endpoint): ?array
    {
        try {
            $this->logger->debug('Searching for product by SKU in Dynamics 365', ['sku' => $sku]);
            
            // Use ReleasedProductsV2 for search as it's more reliable
            $searchEndpoint = 'ReleasedProductsV2';
            $response = $this->api->sendRequest('GET', $searchEndpoint, [
                '$filter' => "ProductNumber eq '{$sku}'"
            ]);
            
            if (isset($response['value']) && is_array($response['value']) && count($response['value']) > 0) {
                $product = $response['value'][0];

                if (isset($product['ProductNumber']) && $product['ProductNumber'] == $sku) {
                    $this->logger->debug('Product found in Dynamics 365', [
                        'sku' => $sku,
                        'product_number' => $product['ProductNumber'] ?? 'N/A',
                        'product_name' => $product['ProductName'] ?? 'N/A'
                    ]);
                    return $product;
                }
            }
        } catch (\Exception $e) {
            $this->logger->warning('Failed to search for product by SKU', [
                'sku' => $sku,
                'error' => 'API request error: ' . $e->getMessage()
            ]);
        }
        return null;
    }

    /**
     * Release product to company (Step 2)
     *
     * @param string $sku
     * @return bool
     */
    private function releaseProductToCompany(string $sku): bool
    {
        try {
            $this->logger->info('Releasing product to company', ['sku' => $sku]);
            
            $releasePayload = [
                'ItemNumber' => $sku,
                'dataAreaId' => 'GB01', // Company ID
            ];

            $this->api->sendRequest('POST', 'ReleasedProductsV2', $releasePayload);
            
            $this->logger->info('Product released to company successfully', [
                'sku' => $sku,
                'company' => 'GB01'
            ]);
            
            return true;
        } catch (\Throwable $e) {
            $errorMessage = $e->getMessage();
            
            // Check if this is an "already exists" error for release
            if (strpos($errorMessage, 'status 400') !== false || 
                strpos($errorMessage, 'already exists') !== false || 
                strpos($errorMessage, 'The record already exists') !== false ||
                strpos($errorMessage, 'Cannot create a record') !== false) {
                $this->logger->info('Product already released to company', [
                    'sku' => $sku,
                    'company' => 'GB01'
                ]);
                return true; // Consider this a success since the product is already released
            } else {
                $this->logger->error('Failed to release product to company', [
                    'sku' => $sku,
                    'company' => 'GB01',
                    'error' => $errorMessage
                ]);
                return false;
            }
        }
    }
}
