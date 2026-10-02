<?php

/**
 * Copyright © 2009-2026 Amasty. All Rights Reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace Amasty\GxoIntegration\Ui\Component\Listing\Column;

use Magento\Framework\UrlInterface;
use Magento\Ui\Component\Listing\Columns\Column;

class FailedSyncActions extends Column
{
    /**
     * @var UrlInterface
     */
    private $urlBuilder;

    public function __construct(
        \Magento\Framework\View\Element\UiComponent\ContextInterface $context,
        \Magento\Framework\View\Element\UiComponentFactory $uiComponentFactory,
        UrlInterface $urlBuilder,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);
        $this->urlBuilder = $urlBuilder;
    }

    /**
     * @param array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource)
    {
        if (!isset($dataSource['data']['items']) || !is_array($dataSource['data']['items'])) {
            return $dataSource;
        }

        foreach ($dataSource['data']['items'] as &$item) {
            $entityId = $item['entity_id'] ?? null;
            if (!$entityId) {
                continue;
            }

            $item[$this->getData('name')]['retry'] = [
                'href' => $this->urlBuilder->getUrl('amasty_gxo/failed/retry', ['entity_id' => $entityId]),
                'label' => __('Retry')
            ];
        }

        return $dataSource;
    }
}
