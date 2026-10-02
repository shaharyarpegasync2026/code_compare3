<?php

/**
 * Copyright © 2009-2025 Amasty. All Rights Reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace BusinessCentral\Customers\Plugin;

class AdminCustomerDynamicsCodeMetaPlugin
{
    /**
     * @param \Magento\Customer\Model\Customer\DataProvider $subject
     * @param array $meta
     * @return array
     */
    public function afterGetMeta(
        \Magento\Customer\Model\Customer\DataProviderWithDefaultAddresses $subject,
        array $meta
    ): array {
        $groupKey = 'customer';

        $fieldConfig = [
            'arguments' => [
                'data' => [
                    'config' => [
                        'label' => __('Dynamics 365 Code'),
                        'source' => 'customer',
                        'dataScope' => 'dynamics365_code',
                        'formElement' => 'input',
                        'componentType' => 'field',
                        'dataType' => 'text',
                        'visible' => true,
                        'disabled' => true,
                        'sortOrder' => 850
                    ]
                ]
            ]
        ];

        if (!isset($meta[$groupKey]['children'])) {
            $meta[$groupKey]['children'] = [];
        }
        $meta[$groupKey]['children']['dynamics365_code'] = $fieldConfig;

        return $meta;
    }
}


