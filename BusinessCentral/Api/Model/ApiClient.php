<?php

/**
 * Copyright © 2009-2025 Amasty. All Rights Reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace BusinessCentral\Api\Model;

use BusinessCentral\Api\Api\ApiInterface;
use Magento\Framework\HTTP\ClientInterface;
use Psr\Log\LoggerInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\LocalizedException;

/**
 * Class ApiClient
 *
 * @package BusinessCentral\Api\Model
 */
class ApiClient implements ApiInterface
{
    /**
     * @var ClientInterface
     */
    private $client;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var ScopeConfigInterface
     */
    private $config;

    /**
     * @var TokenProvider
     */
    private $tokenProvider;

    public function __construct(
        ClientInterface $client,
        LoggerInterface $logger,
        ScopeConfigInterface $config,
        TokenProvider $tokenProvider
    ) {
        $this->client = $client;
        $this->logger = $logger;
        $this->config = $config;
        $this->tokenProvider = $tokenProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function sendRequest(string $method, string $endpoint, array $data = [], array $headers = []): array
    {
        $apiUrl = (string)$this->config->getValue('businesscentral_api/general/api_url');
        if (!$apiUrl) {
            throw new LocalizedException(__('API base URL is missing.'));
        }

        $url = rtrim($apiUrl, '/') . '/' . ltrim($endpoint, '/');

        if ($method === 'POST' && strpos($endpoint, '/SalesOrderHeadersV2') !== false) {
            $url .= strpos($url, '?') === false ? '?' : '&';
            $url .= '$format=application/json;odata.metadata=minimal;IEEE754Compatible=false';
        }

        $httpMethod = strtoupper($method);
        if ($httpMethod === 'GET' && $data) {
            $url .= strpos($url, '?') === false ? '?' : '&';
            $url .= http_build_query($data, '', '&', PHP_QUERY_RFC3986);
        }

        $accessToken = $this->tokenProvider->getAccessToken();
        if (method_exists($this->client, 'setHeaders')) {
            $this->client->setHeaders([]);
        }
        $this->client->addHeader('Authorization', 'Bearer ' . $accessToken);
        $this->client->addHeader('Accept', 'application/json');
        $this->client->addHeader('OData-Version', '4.0');
        $this->client->addHeader('OData-MaxVersion', '4.0');
        $this->client->addHeader('Prefer', 'odata.maxpagesize=100');

        if ($httpMethod !== 'GET') {
            $this->client->addHeader('Content-Type', 'application/json');
        }

        foreach ($headers as $hName => $hValue) {
            $this->client->addHeader($hName, (string)$hValue);
        }

        $attempt = 0;
        $maxAttempts = 3;
        $retryableStatuses = [429, 502, 503, 504];
        $lastBody = '';
        $lastStatus = 0;

        while ($attempt < $maxAttempts) {
            try {
                $this->client->setOption(CURLOPT_CUSTOMREQUEST, null);
                $this->logger->info('D365 request', [
                    'method' => $httpMethod,
                    'url' => $url,
                    'headers' => $headers,
                    'payload' => $httpMethod === 'GET' ? null : $data
                ]);
                switch (strtoupper($method)) {
                    case 'GET':
                        $this->client->get($url);
                        break;
                    case 'POST':
                        $this->client->post($url, json_encode($data));
                        break;
                    case 'PATCH':
                        $this->client->setOption(CURLOPT_CUSTOMREQUEST, 'PATCH');
                        $this->client->post($url, json_encode($data));
                        break;
                    case 'PUT':
                        $this->client->setOption(CURLOPT_CUSTOMREQUEST, 'PUT');
                        $this->client->post($url, json_encode($data));
                        break;
                    case 'DELETE':
                        $this->client->setOption(CURLOPT_CUSTOMREQUEST, 'DELETE');
                        $this->client->post($url, '');
                        break;
                    default:
                        throw new LocalizedException(__('Unsupported method: %1', $method));
                }

                $response = $this->client->getBody();
                $status = $this->client->getStatus();
                $this->logger->info('D365 response', [
                    'method' => $httpMethod,
                    'url' => $url,
                    'status' => $status,
                    'body' => $response
                ]);

                if (in_array($status, $retryableStatuses, true)) {
                    $attempt++;
                    $delayMs = (int)(500 * (2 ** ($attempt - 1))); // 500ms, 1000ms
                    usleep($delayMs * 1000);
                    $lastBody = $response;
                    $lastStatus = $status;
                    continue;
                }

                if ($status >= 400) {
                    $this->logger->error('API request failed', ['status' => $status, 'response' => $response, 'url' => $url, 'method' => $method]);
                    throw new LocalizedException(__('API request failed with status %1', $status));
                }

                $decoded = json_decode($response, true);
                return is_array($decoded) ? $decoded : [];
            } catch (\Exception $e) {
                $attempt++;
                if ($attempt >= $maxAttempts) {
                    $this->logger->error('API request exception', ['exception' => $e->getMessage(), 'url' => $url, 'method' => $method, 'last_status' => $lastStatus, 'last_body' => $lastBody]);
                    throw new LocalizedException(__('API request error: %1', $e->getMessage()));
                }
                $delayMs = (int)(500 * (2 ** ($attempt - 1)));
                usleep($delayMs * 1000);
            }
        }

        throw new LocalizedException(__('API request failed after retries.'));
    }

    /**
     * {@inheritdoc}
     */
    public function put(string $endpoint, array $data = []): array
    {
        return $this->sendRequest('PUT', $endpoint, $data);
    }

    /**
     * {@inheritdoc}
     */
    public function delete(string $endpoint): array
    {
        return $this->sendRequest('DELETE', $endpoint);
    }

    /**
     * {@inheritdoc}
     */
    public function patch(string $endpoint, array $data = []): array
    {
        return $this->sendRequest('PATCH', $endpoint, $data);
    }

    /**
     * {@inheritdoc}
     */
    public function sendBatch(array $operations): array
    {
        $apiUrl = (string)$this->config->getValue('businesscentral_api/general/api_url');
        if (!$apiUrl) {
            throw new LocalizedException(__('API base URL is missing.'));
        }

        $boundary = 'batch_' . bin2hex(random_bytes(8));
        $changeset = 'changeset_' . bin2hex(random_bytes(8));

        $accessToken = $this->tokenProvider->getAccessToken();
        $this->client->addHeader('Authorization', 'Bearer ' . $accessToken);
        $this->client->addHeader('Content-Type', 'multipart/mixed; boundary=' . $boundary);
        $this->client->addHeader('Accept', 'application/json');
        $this->client->addHeader('OData-Version', '4.0');
        $this->client->addHeader('OData-MaxVersion', '4.0');

        $body = "--$boundary\r\n";
        $body .= "Content-Type: multipart/mixed; boundary=$changeset\r\n\r\n";

        $contentId = 1;
        foreach ($operations as $op) {
            $method = strtoupper($op['method'] ?? 'POST');
            $endpoint = ltrim((string)($op['endpoint'] ?? ''), '/');
            $data = $op['data'] ?? [];

            $body .= "--$changeset\r\n";
            $body .= "Content-Type: application/http\r\n";
            $body .= "Content-Transfer-Encoding: binary\r\n";
            $body .= "Content-ID: $contentId\r\n\r\n";
            $body .= "$method $endpoint HTTP/1.1\r\n";
            $body .= "Content-Type: application/json; charset=utf-8\r\n";
            $body .= "Accept: application/json\r\n\r\n";
            $body .= json_encode($data) . "\r\n\r\n";
            $contentId++;
        }

        $body .= "--$changeset--\r\n";
        $body .= "--$boundary--\r\n";

        $this->client->post(rtrim($apiUrl, '/') . '/$batch', $body);
        $status = $this->client->getStatus();
        $response = $this->client->getBody();
        if ($status >= 400) {
            $this->logger->error('API batch request failed', ['status' => $status, 'response' => $response]);
            throw new LocalizedException(__('API batch request failed with status %1', $status));
        }

        return ['status' => $status, 'raw' => $response];
    }

    /**
     * Get all products from Dynamics 365
     *
     * @return array
     * @throws LocalizedException
     */
    public function getAllProducts(): array
    {
        $allProducts = [];
        $skip = 0;
        $top = 100; // Page size
        $hasMore = true;

        while ($hasMore) {
            $endpoint = "ReleasedProductsV2?\$top=$top&\$skip=$skip";
            $response = $this->sendRequest('GET', $endpoint);
            
            if (isset($response['value']) && is_array($response['value'])) {
                $products = $response['value'];
                $allProducts = array_merge($allProducts, $products);
                
                // Check if there are more products
                if (count($products) < $top) {
                    $hasMore = false;
                } else {
                    $skip += $top;
                }
                
                $this->logger->info('Fetched products batch', [
                    'count' => count($products),
                    'total' => count($allProducts),
                    'skip' => $skip
                ]);
            } else {
                $hasMore = false;
            }
        }

        $this->logger->info('Finished fetching all products', [
            'total_count' => count($allProducts)
        ]);

        return $allProducts;
    }
}
