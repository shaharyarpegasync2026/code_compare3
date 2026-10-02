<?php

/**
 * Copyright © 2009-2025 Amasty. All Rights Reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace BusinessCentral\Api\Model;

use Magento\Framework\App\CacheInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\HTTP\Client\Curl;

/**
 * Class TokenProvider
 *
 * @package BusinessCentral\Api\Model
 */
class TokenProvider
{
    /**
     * @var ScopeConfigInterface
     */
    private $config;

    /**
     * @var Curl
     */
    private $curl;

    /**
     * @var CacheInterface
     */
    private $cache;

    /**
     * @var EncryptorInterface
     */
    private $encryptor;

    private const CACHE_KEY = 'businesscentral_oauth_token';
    private const CACHE_TTL = 3000;

    public function __construct(
        ScopeConfigInterface $config,
        Curl $curl,
        CacheInterface $cache,
        EncryptorInterface $encryptor
    ) {
        $this->config = $config;
        $this->curl = $curl;
        $this->cache = $cache;
        $this->encryptor = $encryptor;
    }

    /**
     * @return string
     */
    public function getAccessToken(): string
    {
        $cached = $this->cache->load(self::CACHE_KEY);
        if ($cached) {
            return $cached;
        }

        $tenantId = trim((string)$this->config->getValue('businesscentral_api/general/tenant_id'));
        $clientId = trim((string)$this->config->getValue('businesscentral_api/general/client_id'));
        $clientSecretEncrypted = (string)$this->config->getValue('businesscentral_api/general/client_secret');
        $scope = trim((string)$this->config->getValue('businesscentral_api/general/scope'));

        if ($clientSecretEncrypted !== '') {
            try {
                $clientSecret = $this->encryptor->decrypt($clientSecretEncrypted);
            } catch (\Throwable $e) {
                $clientSecret = $clientSecretEncrypted;
            }
        } else {
            $clientSecret = '';
        }

        if ($tenantId === '' || $clientId === '' || $clientSecret === '' || $scope === '') {
            throw new LocalizedException(__('OAuth2 configuration is missing.'));
        }

        $tokenUrl = sprintf('https://login.microsoftonline.com/%s/oauth2/v2.0/token', $tenantId);
        $post = http_build_query([
            'grant_type' => 'client_credentials',
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'scope' => $scope,
        ], '', '&', PHP_QUERY_RFC3986);


        $this->curl->addHeader('Content-Type', 'application/x-www-form-urlencoded');
        $this->curl->post($tokenUrl, $post);

        $status = $this->curl->getStatus();
        $body = $this->curl->getBody();
        if ($status >= 400) {
            throw new LocalizedException(__('OAuth2 token request failed: %1', $body));
        }

        $json = json_decode($body, true);
        if (!isset($json['access_token'])) {
            throw new LocalizedException(__('OAuth2 token not present in response.'));
        }

        $token = (string)$json['access_token'];
        $expiresIn = (int)($json['expires_in'] ?? self::CACHE_TTL);
        $ttl = max(60, $expiresIn - 60);
        $this->cache->save($token, self::CACHE_KEY, [], $ttl);
        
        return $token;
    }
}
