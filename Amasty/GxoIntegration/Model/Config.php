<?php

/**
 * Copyright © 2009-2026 Amasty. All Rights Reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace Amasty\GxoIntegration\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Encryption\EncryptorInterface;

class Config
{
    public const XML_PATH_ENABLED = 'amasty_gxo/general/enabled';
    public const XML_PATH_TRIGGER_STATUSES = 'amasty_gxo/general/trigger_statuses';
    public const XML_PATH_API_BASE_URL = 'amasty_gxo/api/base_url';
    public const XML_PATH_API_USERNAME = 'amasty_gxo/api/username';
    public const XML_PATH_API_PASSWORD = 'amasty_gxo/api/password';
    public const XML_PATH_API_ENDPOINT_PATH = 'amasty_gxo/api/endpoint_path';
    public const XML_PATH_RETRY_MAX_ATTEMPTS = 'amasty_gxo/retry/max_attempts';
    public const XML_PATH_RETRY_DELAY_SECONDS = 'amasty_gxo/retry/retry_delay_seconds';
    public const XML_PATH_RETRY_BATCH_SIZE = 'amasty_gxo/retry/batch_size';

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var EncryptorInterface
     */
    private $encryptor;

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        EncryptorInterface $encryptor
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->encryptor = $encryptor;
    }

    /**
     * @return bool
     */
    public function isEnabled(): bool
    {
        return (bool)$this->scopeConfig->isSetFlag(self::XML_PATH_ENABLED);
    }

    /**
     * @return string[]
     */
    public function getTriggerStatuses(): array
    {
        $value = (string)$this->scopeConfig->getValue(self::XML_PATH_TRIGGER_STATUSES);
        if ($value === '') {
            return [];
        }

        $parts = array_map('trim', explode(',', $value));
        return array_values(array_filter($parts, static function (string $v): bool {
            return $v !== '';
        }));
    }

    /**
     * @return string
     */
    public function getApiBaseUrl(): string
    {
        return rtrim((string)$this->scopeConfig->getValue(self::XML_PATH_API_BASE_URL), '/');
    }

    /**
     * @return string
     */
    public function getApiEndpointPath(): string
    {
        $path = (string)$this->scopeConfig->getValue(self::XML_PATH_API_ENDPOINT_PATH);
        if ($path === '') {
            return '';
        }

        return '/' . ltrim($path, '/');
    }

    /**
     * @return string
     */
    public function getApiUsername(): string
    {
        return (string)$this->scopeConfig->getValue(self::XML_PATH_API_USERNAME);
    }

    /**
     * @return string
     */
    public function getApiPassword(): string
    {
        $value = (string)$this->scopeConfig->getValue(self::XML_PATH_API_PASSWORD);
        if ($value === '') {
            return '';
        }

        return (string)$this->encryptor->decrypt($value);
    }

    /**
     * @return int
     */
    public function getMaxAttempts(): int
    {
        return max(1, (int)$this->scopeConfig->getValue(self::XML_PATH_RETRY_MAX_ATTEMPTS));
    }

    /**
     * @return int
     */
    public function getRetryDelaySeconds(): int
    {
        return max(0, (int)$this->scopeConfig->getValue(self::XML_PATH_RETRY_DELAY_SECONDS));
    }

    /**
     * @return int
     */
    public function getBatchSize(): int
    {
        return max(1, (int)$this->scopeConfig->getValue(self::XML_PATH_RETRY_BATCH_SIZE));
    }
}
