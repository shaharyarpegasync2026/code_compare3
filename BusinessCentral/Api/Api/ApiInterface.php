<?php

/**
 * Copyright © 2009-2025 Amasty. All Rights Reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace BusinessCentral\Api\Api;

/**
 * Interface ApiInterface
 *
 * @package BusinessCentral\Api\Api
 */
interface ApiInterface
{
    /**
     * @param string $method
     * @param string $endpoint
     * @param array $data
     * @param array $headers
     * @return array
     */
    public function sendRequest(string $method, string $endpoint, array $data = [], array $headers = []): array;

    /**
     * @param string $endpoint
     * @param array $data
     * @return array
     */
    public function put(string $endpoint, array $data = []): array;

    /**
     * @param string $endpoint
     * @return array
     */
    public function delete(string $endpoint): array;

    /**
     * @param string $endpoint
     * @param array $data
     * @return array
     */
    public function patch(string $endpoint, array $data = []): array;

    /**
     * @param array $operations
     * @return array
     */
    public function sendBatch(array $operations): array;
}
