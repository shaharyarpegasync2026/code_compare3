<?php

/**
 * Copyright © 2009-2026 Amasty. All Rights Reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace Amasty\GxoIntegration\Api\Data;

use Magento\Framework\Api\SearchResultsInterface;

interface SuccessSyncSearchResultsInterface extends SearchResultsInterface
{
    /**
     * @return \Amasty\GxoIntegration\Api\Data\SuccessSyncInterface[]
     */
    public function getItems();

    /**
     * @param \Amasty\GxoIntegration\Api\Data\SuccessSyncInterface[] $items
     * @return $this
     */
    public function setItems(array $items);
}
