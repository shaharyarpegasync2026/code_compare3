<?php

/**
 * Copyright © 2009-2025 Amasty. All Rights Reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace BusinessCentral\Products\Setup\Patch\Data;

use Magento\Catalog\Model\Product;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

class UpdateDynamics365ItemNumberAttribute implements DataPatchInterface
{
    /**
     * @var ModuleDataSetupInterface
     */
    private $moduleDataSetup;

    /**
     * @var EavSetupFactory
     */
    private $eavSetupFactory;

    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        EavSetupFactory $eavSetupFactory
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->eavSetupFactory = $eavSetupFactory;
    }

    public function apply()
    {
        $this->moduleDataSetup->getConnection()->startSetup();

        $eavSetup = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);
        $attributeCode = 'dynamics365_item_number';

        $attributeId = $eavSetup->getAttributeId(Product::ENTITY, $attributeCode);
        if ($attributeId) {
            $eavSetup->updateAttribute(Product::ENTITY, $attributeCode, 'frontend_input', 'text');
            $eavSetup->updateAttribute(Product::ENTITY, $attributeCode, 'is_visible', 1);
            $eavSetup->updateAttribute(Product::ENTITY, $attributeCode, 'visible', 1);
            $eavSetup->updateAttribute(Product::ENTITY, $attributeCode, 'is_required', 0);
            $eavSetup->updateAttribute(Product::ENTITY, $attributeCode, 'default_value', null);
            $eavSetup->updateAttribute(Product::ENTITY, $attributeCode, 'source_model', null);
        }

        $this->moduleDataSetup->getConnection()->endSetup();
    }

    public static function getDependencies(): array
    {
        return [];
    }

    public function getAliases(): array
    {
        return [];
    }
}

