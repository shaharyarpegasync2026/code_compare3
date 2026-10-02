<?php

/**
 * Copyright © 2009-2026 Amasty. All Rights Reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace Amasty\GxoIntegration\Model;

use Amasty\GxoIntegration\Api\Data\SuccessSyncSearchResultsInterface;
use Magento\Framework\Api\SearchResults;

class SuccessSyncSearchResults extends SearchResults implements SuccessSyncSearchResultsInterface
{
}
