<?php

/**
 * Copyright © 2009-2026 Amasty. All Rights Reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace Amasty\GxoIntegration\Model\Config\Backend;

use Magento\Framework\App\Config\Value;
use Magento\Framework\Exception\LocalizedException;

abstract class AbstractRequiredWhenEnabled extends Value
{
    /**
     * @return void
     */
    public function beforeSave()
    {
        parent::beforeSave();

        $enabled = (bool)$this->getFieldsetDataValue('enabled');
        if (!$enabled) {
            return;
        }

        $value = trim((string)$this->getValue());
        if ($value === '') {
            throw new LocalizedException(__('This field is required.'));
        }
    }
}
