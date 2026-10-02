<?php

/**
 * Copyright © 2009-2026 Amasty. All Rights Reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace Amasty\GxoIntegration\Model\ResourceModel\SuccessSync;

use Amasty\GxoIntegration\Model\ResourceModel\SuccessSync as ResourceModel;
use Amasty\GxoIntegration\Model\SuccessSync as Model;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    protected function _construct()
    {
        $this->_init(Model::class, ResourceModel::class);
        $this->_idFieldName = 'entity_id';
    }
}
