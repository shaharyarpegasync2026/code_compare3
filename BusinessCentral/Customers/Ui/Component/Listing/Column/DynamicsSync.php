<?php

/**
 * Copyright © 2009-2025 Amasty. All Rights Reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace BusinessCentral\Customers\Ui\Component\Listing\Column;

use Magento\Framework\View\Asset\Repository as AssetRepository;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;

class DynamicsSync extends Column
{
    /**
     * @var AssetRepository
     */
    private $assetRepository;

    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        AssetRepository $assetRepository,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);
        $this->assetRepository = $assetRepository;
    }

    /**
     * @param array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource): array
    {
        if (!isset($dataSource['data']['items'])) {
            return $dataSource;
        }

        $name = $this->getData('name');
        foreach ($dataSource['data']['items'] as &$item) {
            $hasSync = isset($item['bc_dynamics_sync'])
                ? (bool)$item['bc_dynamics_sync']
                : (bool)($item['bc_dynamics365_code'] ?? null);
            $icon = $hasSync ? 'yes.svg' : 'no.svg';
            $label = $hasSync ? 'Yes' : 'No';
            $url = $this->assetRepository->getUrlWithParams('BusinessCentral_Orders::images/' . $icon, []);
            $item[$name] = '<span class="bc-dyn-flag">'
                . '<img src="' . $url . '" alt="' . $label . '" width="16" height="16" /> '
                . $label
                . '</span>';
        }
        unset($item);

        return $dataSource;
    }
}
