<?php

/**
 * Copyright © 2009-2026 Amasty. All Rights Reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace Amasty\GxoIntegration\Model\Config\Backend;

use Magento\Framework\Exception\LocalizedException;

class BaseUrl extends AbstractRequiredWhenEnabled
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

        $value = rtrim(trim((string)$this->getValue()), '/');
        if ($value === '') {
            return;
        }

        if (!filter_var($value, FILTER_VALIDATE_URL)) {
            throw new LocalizedException(__('Invalid URL.'));
        }

        $this->setValue($value);
    }
}
