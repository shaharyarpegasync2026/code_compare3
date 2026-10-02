<?php

/**
 * Copyright © 2009-2026 Amasty. All Rights Reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace Amasty\GxoIntegration\Model\Sync;

use Amasty\GxoIntegration\Api\FailedSyncManagementInterface;
use Amasty\GxoIntegration\Api\QueueManagementInterface;
use Amasty\GxoIntegration\Api\SuccessSyncManagementInterface;
use Amasty\GxoIntegration\Model\Api\Client as ApiClient;
use Amasty\GxoIntegration\Model\Config;
use Amasty\GxoIntegration\Model\Payload\OrderPayloadBuilder;
use Amasty\GxoIntegration\Logger\Logger;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\OrderRepositoryInterface;

class OrderSyncService
{
    /**
     * @var Config
     */
    private $config;

    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var OrderPayloadBuilder
     */
    private $payloadBuilder;

    /**
     * @var ApiClient
     */
    private $apiClient;

    /**
     * @var QueueManagementInterface
     */
    private $queueManagement;

    /**
     * @var SuccessSyncManagementInterface
     */
    private $successSyncManagement;

    /**
     * @var FailedSyncManagementInterface
     */
    private $failedSyncManagement;

    /**
     * @var Logger
     */
    private $logger;

    public function __construct(
        Config $config,
        OrderRepositoryInterface $orderRepository,
        OrderPayloadBuilder $payloadBuilder,
        ApiClient $apiClient,
        QueueManagementInterface $queueManagement,
        SuccessSyncManagementInterface $successSyncManagement,
        FailedSyncManagementInterface $failedSyncManagement,
        Logger $logger
    ) {
        $this->config = $config;
        $this->orderRepository = $orderRepository;
        $this->payloadBuilder = $payloadBuilder;
        $this->apiClient = $apiClient;
        $this->queueManagement = $queueManagement;
        $this->successSyncManagement = $successSyncManagement;
        $this->failedSyncManagement = $failedSyncManagement;
        $this->logger = $logger;
    }

    /**
     * @param int $orderId
     * @param bool $force
     * @return void
     * @throws LocalizedException
     */
    public function sync(int $orderId, bool $force = false): void
    {
        if (!$force && !$this->config->isEnabled()) {
            return;
        }

        if (!$force && $this->successSyncManagement->existsByOrderId($orderId)) {
            $this->queueManagement->deleteByOrderId($orderId);
            return;
        }

        $order = $this->orderRepository->get($orderId);
        $payload = $this->payloadBuilder->build($order);

        $requestPayloadJson = json_encode($payload);
        $responsePayloadJson = null;

        try {
            $this->logger->info('GXO sync request', [
                'order_id' => $orderId,
                'payload' => $requestPayloadJson
            ]);
            $response = $this->apiClient->sendOrders($payload);
            $responsePayloadJson = json_encode($response);
            $this->logger->info('GXO sync response', [
                'order_id' => $orderId,
                'response' => $responsePayloadJson
            ]);

            $gxoReference = $this->extractReference($response);
            $this->successSyncManagement->upsertSuccess(
                $orderId,
                $gxoReference,
                $requestPayloadJson,
                $responsePayloadJson,
                (new \DateTimeImmutable('now', new \DateTimeZone('UTC')))->format('Y-m-d H:i:s')
            );
            $this->failedSyncManagement->deleteByOrderId($orderId);
            $this->queueManagement->deleteByOrderId($orderId);
        } catch (\Exception $e) {
            $responsePayloadJson = $responsePayloadJson ?? null;
            $this->logger->error('GXO sync failed', [
                'order_id' => $orderId,
                'error' => $e->getMessage()
            ]);
            $this->saveFailed($orderId, $e->getMessage(), $requestPayloadJson, $responsePayloadJson);
            $this->queueManagement->deleteByOrderId($orderId);
            if ($e instanceof LocalizedException) {
                throw $e;
            }
            throw new LocalizedException(__('GXO sync failed: %1', $e->getMessage()));
        }
    }

    /**
     * @param int $orderId
     * @param string $error
     * @param string|null $requestPayload
     * @param string|null $responsePayload
     * @return void
     */
    private function saveFailed(
        int $orderId,
        string $error,
        ?string $requestPayload,
        ?string $responsePayload
    ): void {
        $attempts = $this->failedSyncManagement->getAttemptsByOrderId($orderId) + 1;
        $nextRetryAt = null;
        $delay = $this->config->getRetryDelaySeconds();
        if ($delay > 0) {
            $nextRetryAt = (new \DateTimeImmutable('now', new \DateTimeZone('UTC')))
                ->modify('+' . $delay . ' seconds')
                ->format('Y-m-d H:i:s');
        }

        $this->failedSyncManagement->upsertFailed(
            $orderId,
            $attempts,
            $nextRetryAt,
            $this->truncate($error, 2048),
            $requestPayload,
            $responsePayload
        );
    }

    /**
     * @param array $response
     * @return string|null
     */
    private function extractReference(array $response): ?string
    {
        if (isset($response['reference']) && is_scalar($response['reference'])) {
            $value = trim((string)$response['reference']);
            return $value !== '' ? $value : null;
        }

        if (isset($response['Order_Ref']) && is_scalar($response['Order_Ref'])) {
            $value = trim((string)$response['Order_Ref']);
            return $value !== '' ? $value : null;
        }

        if (isset($response['orderReference']) && is_scalar($response['orderReference'])) {
            $value = trim((string)$response['orderReference']);
            return $value !== '' ? $value : null;
        }

        return null;
    }

    /**
     * @param string $value
     * @param int $maxLength
     * @return string
     */
    private function truncate(string $value, int $maxLength): string
    {
        $value = trim($value);
        if (strlen($value) <= $maxLength) {
            return $value;
        }

        return substr($value, 0, $maxLength);
    }

}
