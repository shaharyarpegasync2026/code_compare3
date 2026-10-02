<?php

/**
 * Copyright © 2009-2025 Amasty. All Rights Reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace BusinessCentral\Products\Ui\Component\Listing\Column;

use Magento\Framework\View\Asset\Repository as AssetRepository;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;

class IsInDynamics extends Column
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

    public function prepareDataSource(array $dataSource): array
    {
        if (!isset($dataSource['data']['items'])) {
            return $dataSource;
        }

        $name = $this->getData('name');
        foreach ($dataSource['data']['items'] as &$item) {
            $value = isset($item['is_in_dynamics']) ? (int)$item['is_in_dynamics'] : 0;
            $icon = $value ? 'yes.svg' : 'no.svg';
            $label = $value ? 'Yes' : 'No';
            $url = $this->assetRepository->getUrlWithParams('BusinessCentral_Products::images/' . $icon, []);
            $item[$name] = '<span class="bc-dyn-flag">'
                . '<img src="' . $url . '" alt="' . $label . '" width="16" height="16" /> '
                . $label
                . '</span>';
        }
        unset($item);

        return $dataSource;
    }
}


