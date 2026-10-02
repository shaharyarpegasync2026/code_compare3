<?php

/**
 * Copyright © 2009-2026 Amasty. All Rights Reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace Amasty\GxoIntegration\Model\ResourceModel\Queue;

use Amasty\GxoIntegration\Model\Queue as Model;
use Amasty\GxoIntegration\Model\ResourceModel\Queue as ResourceModel;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    protected function _construct()
    {
        $this->_init(Model::class, ResourceModel::class);
        $this->_idFieldName = 'entity_id';
    }
}
