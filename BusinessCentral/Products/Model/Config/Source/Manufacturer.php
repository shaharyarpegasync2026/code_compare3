<?php

/**
 * Copyright © 2009-2025 Amasty. All Rights Reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace BusinessCentral\Products\Model\Config\Source;

use Magento\Eav\Api\AttributeRepositoryInterface;
use Magento\Framework\Option\ArrayInterface;
use \Exception;

class Manufacturer implements ArrayInterface
{
    /**
     * @var AttributeRepositoryInterface
     */
    private $attributeRepository;

    public function __construct(AttributeRepositoryInterface $attributeRepository)
    {
        $this->attributeRepository = $attributeRepository;
    }

    /**
     * @return array
     */
    public function toOptionArray(): array
    {
        $options = [];
        try {
            $attribute = $this->attributeRepository->get('catalog_product', 'manufacturer');
            foreach ($attribute->getOptions() as $option) {
                $value = (string)$option->getValue();
                $label = (string)$option->getLabel();
                if ($value === '' || $label === '') {
                    continue;
                }
                $options[] = ['value' => $value, 'label' => $label];
            }
        } catch (Exception $exception) {
            
        }
        
        return $options;
    }
}
