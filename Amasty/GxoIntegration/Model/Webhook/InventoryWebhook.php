<?php

/**
 * Copyright © 2009-2026 Amasty. All Rights Reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace Amasty\GxoIntegration\Model\Webhook;

use Amasty\GxoIntegration\Api\InventoryWebhookInterface;
use Amasty\GxoIntegration\Logger\InventoryLogger;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Magento\InventoryApi\Api\Data\SourceItemInterfaceFactory;
use Magento\InventoryApi\Api\SourceItemsSaveInterface;

class InventoryWebhook implements InventoryWebhookInterface
{
    private const BATCH_SIZE = 200;
    private const SOURCE_CODE = 'default';

    /**
     * @var SourceItemsSaveInterface
     */
    private $sourceItemsSave;

    /**
     * @var SourceItemInterfaceFactory
     */
    private $sourceItemFactory;

    /**
     * @var InventoryLogger
     */
    private $logger;

    /**
     * @var ProductCollectionFactory
     */
    private $productCollectionFactory;

    /**
     * @param SourceItemsSaveInterface $sourceItemsSave
     * @param SourceItemInterfaceFactory $sourceItemFactory
     * @param InventoryLogger $logger
     * @param ProductCollectionFactory $productCollectionFactory
     */
    public function __construct(
        SourceItemsSaveInterface $sourceItemsSave,
        SourceItemInterfaceFactory $sourceItemFactory,
        InventoryLogger $logger,
        ProductCollectionFactory $productCollectionFactory
    ) {
        $this->sourceItemsSave = $sourceItemsSave;
        $this->sourceItemFactory = $sourceItemFactory;
        $this->logger = $logger;
        $this->productCollectionFactory = $productCollectionFactory;
    }

    /**
     * @param string $COMPANY_CODE
     * @param mixed $ROWS
     * @return array
     * @throws LocalizedException
     */
    public function syncInventory(
        string $COMPANY_CODE = '',
        mixed $ROWS = []
    ): array {
        if (!is_array($ROWS)) {
            throw new LocalizedException(__('ROWS must be an array.'));
        }

        if (!$ROWS) {
            throw new LocalizedException(__('ROWS is required.'));
        }

        $companyCode = trim($COMPANY_CODE);
        $skuTotals = [];
        $skippedNotAvailable = 0;
        $skippedInvalidRows = 0;

        foreach ($ROWS as $row) {
            if (!is_array($row)) {
                $skippedInvalidRows++;
                continue;
            }

            $sku = trim((string)($row['SKU_ID'] ?? ''));
            if ($sku === '') {
                $skippedInvalidRows++;
                continue;
            }

            $condition = strtoupper(trim((string)($row['CONDITION_ID'] ?? 'AVAILABLE')));
            if ($condition !== 'AVAILABLE') {
                $skippedNotAvailable++;
                continue;
            }

            $qty = (float)($row['QTY_ON_HAND'] ?? 0);
            if (!isset($skuTotals[$sku])) {
                $skuTotals[$sku] = 0.0;
            }
            $skuTotals[$sku] += $qty;
        }

        if (!$skuTotals) {
            $this->logger->info('GXO inventory snapshot processed with no updates', [
                'company_code' => $companyCode,
                'rows_total' => count($ROWS),
                'skipped_invalid_rows' => $skippedInvalidRows,
                'skipped_not_available' => $skippedNotAvailable
            ]);

            return [
                'success' => true,
                'updated_count' => 0,
                'message' => 'No inventory updates.'
            ];
        }

        $skuMap = $this->resolveSkusByDynamicsItemNumber(array_keys($skuTotals));

        $notFound = 0;
        $sourceItems = [];
        $pendingSkus = [];

        foreach ($skuTotals as $dynamicsNumber => $qty) {
            if (!isset($skuMap[$dynamicsNumber])) {
                $notFound++;
                $this->logger->warning('GXO inventory product not found by dynamics365_item_number', [
                    'company_code' => $companyCode,
                    'dynamics365_item_number' => $dynamicsNumber
                ]);
                continue;
            }

            $sku = $skuMap[$dynamicsNumber];
            $normalizedQty = max(0.0, (float)$qty);

            /** @var SourceItemInterface $sourceItem */
            $sourceItem = $this->sourceItemFactory->create();
            $sourceItem->setSku($sku);
            $sourceItem->setSourceCode(self::SOURCE_CODE);
            $sourceItem->setQuantity($normalizedQty);
            $sourceItem->setStatus(
                $normalizedQty > 0
                    ? SourceItemInterface::STATUS_IN_STOCK
                    : SourceItemInterface::STATUS_OUT_OF_STOCK
            );

            $sourceItems[] = $sourceItem;
            $pendingSkus[] = $sku;
        }

        $updated = 0;
        $errors = 0;
        $updatedSkus = [];

        foreach (array_chunk($sourceItems, self::BATCH_SIZE) as $batchIndex => $batch) {
            $batchSkus = array_slice($pendingSkus, $batchIndex * self::BATCH_SIZE, count($batch));

            try {
                $this->sourceItemsSave->execute($batch);
                $updated += count($batch);
                array_push($updatedSkus, ...$batchSkus);
            } catch (\Throwable $e) {
                $errors += count($batch);
                $this->logger->error('GXO inventory batch update failed', [
                    'company_code' => $companyCode,
                    'batch_index' => $batchIndex,
                    'batch_size' => count($batch),
                    'skus' => $batchSkus,
                    'message' => $e->getMessage()
                ]);
            }
        }

        $this->logger->info('GXO inventory snapshot processed', [
            'company_code' => $companyCode,
            'rows_total' => count($ROWS),
            'skipped_invalid_rows' => $skippedInvalidRows,
            'skipped_not_available' => $skippedNotAvailable,
            'sku_total' => count($skuTotals),
            'updated' => $updated,
            'not_found' => $notFound,
            'errors' => $errors
        ]);

        return [
            'success' => true,
            'updated_count' => $updated,
            'not_found_count' => $notFound,
            'error_count' => $errors,
            'skipped_invalid_rows' => $skippedInvalidRows,
            'skipped_not_available' => $skippedNotAvailable,
            'updated_skus' => $updatedSkus
        ];
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
            $map[(string)$product->getData('dynamics365_item_number')] = (string)$product->getSku();
        }

        return $map;
    }
}
