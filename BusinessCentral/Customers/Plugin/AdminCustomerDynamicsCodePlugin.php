<?php

/**
 * Copyright © 2009-2025 Amasty. All Rights Reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace BusinessCentral\Customers\Plugin;

use BusinessCentral\Customers\Api\CustomerRepositoryInterface as BusinessCentralCustomerRepositoryInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;

class AdminCustomerDynamicsCodePlugin
{
    /**
     * @var BusinessCentralCustomerRepositoryInterface
     */
    private $businessCentralCustomerRepository;

    /**
     * @var CustomerRepositoryInterface
     */
    private $magentoCustomerRepository;

    public function __construct(
        BusinessCentralCustomerRepositoryInterface $businessCentralCustomerRepository,
        CustomerRepositoryInterface $magentoCustomerRepository
    ) {
        $this->businessCentralCustomerRepository = $businessCentralCustomerRepository;
        $this->magentoCustomerRepository = $magentoCustomerRepository;
    }

    /**
     * @param \Magento\Ui\DataProvider\AbstractDataProvider $subject
     * @param array $result
     * @return array
     */
    public function afterGetData(
        \Magento\Ui\DataProvider\AbstractDataProvider $subject,
        array $result
    ): array {
        foreach ($result as $entityId => $data) {
            $email = null;
            if (isset($data['customer']['email'])) {
                $email = (string)$data['customer']['email'];
            } elseif (isset($data['email'])) {
                $email = (string)$data['email'];
            }

            if (!$email) {
                $id = null;
                if (isset($data['customer']['entity_id'])) {
                    $id = (int)$data['customer']['entity_id'];
                } elseif (isset($data['entity_id'])) {
                    $id = (int)$data['entity_id'];
                }
                if ($id) {
                    try {
                        $customer = $this->magentoCustomerRepository->getById($id);
                        $email = (string)$customer->getEmail();
                    } catch (\Throwable $e) {
                    }
                }
            }

            if (!$email) {
                continue;
            }

            $existingValue = null;
            if (isset($data['customer']['custom_attributes']['dynamics365_code'])) {
                $existingValue = $data['customer']['custom_attributes']['dynamics365_code'];
            } elseif (isset($data['customer']['dynamics365_code'])) {
                $existingValue = $data['customer']['dynamics365_code'];
            }

            if ($existingValue !== null && $existingValue !== '') {
                continue;
            }

            try {
                $bcCustomer = $this->businessCentralCustomerRepository->getByEmail($email);
                $code = (string)$bcCustomer->getDynamics365Code();
                if ($code !== '') {
                    if (isset($result[$entityId]['customer']['custom_attributes']) && is_array($result[$entityId]['customer']['custom_attributes'])) {
                        $result[$entityId]['customer']['custom_attributes']['dynamics365_code'] = $code;
                    } else {
                        $result[$entityId]['customer']['dynamics365_code'] = $code;
                    }
                }
            } catch (\Throwable $e) {
            }
        }

        return $result;
    }
}


