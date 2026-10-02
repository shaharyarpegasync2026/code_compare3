<?php

/**
 * Copyright © 2009-2026 Amasty. All Rights Reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace Amasty\GxoIntegration\Model\Api;

use Amasty\GxoIntegration\Model\Config;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\HTTP\ClientInterface;

class Client
{
    /**
     * @var ClientInterface
     */
    private $httpClient;

    /**
     * @var Config
     */
    private $config;

    public function __construct(
        ClientInterface $httpClient,
        Config $config
    ) {
        $this->httpClient = $httpClient;
        $this->config = $config;
    }

    /**
     * @param array $payload
     * @return array
     */
    public function sendOrders(array $payload): array
    {
        $baseUrl = $this->config->getApiBaseUrl();
        $endpointPath = $this->config->getApiEndpointPath();
        if ($baseUrl === '' || $endpointPath === '') {
            throw new LocalizedException(__('GXO API configuration is incomplete.'));
        }

        $url = $baseUrl . $endpointPath;

        $username = $this->config->getApiUsername();
        $password = $this->config->getApiPassword();
        if ($username === '' || $password === '') {
            throw new LocalizedException(__('GXO API credentials are missing.'));
        }

        if (method_exists($this->httpClient, 'setHeaders')) {
            $this->httpClient->setHeaders([]);
        }

        $this->httpClient->addHeader('Accept', 'application/json');
        $this->httpClient->addHeader('Content-Type', 'application/json');
        $this->httpClient->addHeader(
            'Authorization',
            'Basic ' . base64_encode($username . ':' . $password)
        );

        $this->httpClient->post($url, json_encode($payload));
        $status = (int)$this->httpClient->getStatus();
        $body = (string)$this->httpClient->getBody();

        if ($status < 200 || $status >= 300) {
            throw new LocalizedException(__('GXO API request failed with status %1: %2', $status, $body));
        }

        $decoded = json_decode($body, true);
        return is_array($decoded) ? $decoded : [];
    }
}
