<?php

/**
 * Copyright © 2009-2025 Amasty. All Rights Reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace BusinessCentral\Orders\Plugin\Sales\Order\Grid;

use Magento\Sales\Model\ResourceModel\Order\Grid\Collection as OrderGridCollection;

class CollectionPlugin
{
    /**
     * @param OrderGridCollection $subject
     * @return void
     */
    public function beforeLoad(OrderGridCollection $subject): void
    {
        $select = $subject->getSelect();

        $from = $select->getPart('from');
        if (!isset($from['main_table'])) {
            return;
        }

        $fromParts = array_change_key_case($from, CASE_LOWER);
        if (!isset($fromParts['so'])) {
            $salesOrderTable = $subject->getTable('sales_order');
            $select->joinLeft(
                ['so' => $salesOrderTable],
                'so.entity_id = main_table.entity_id',
                ['dynamics365_code' => 'so.dynamics365_code']
            );
            $select->columns([
                'dyn_has_code' => new \Zend_Db_Expr("IF(so.dynamics365_code IS NULL OR so.dynamics365_code = '', 0, 1)")
            ]);
            $subject->addFilterToMap('dynamics365_code', 'so.dynamics365_code');
            $subject->addFilterToMap('dyn_has_code', 'dyn_has_code');
        }
    }

    public function aroundAddFieldToFilter(
        OrderGridCollection $subject,
        callable $proceed,
        $field,
        $condition = null
    ) {
        if ($field === 'dynamics365_code' && is_array($condition) && array_key_exists('eq', $condition)) {
            $value = (int)$condition['eq'];
            if ($value === 1) {
                $subject->getSelect()->where("COALESCE(so.dynamics365_code, '') <> ''");
            } else {
                $subject->getSelect()->where("COALESCE(so.dynamics365_code, '') = ''");
            }
            return $subject;
        }

        return $proceed($field, $condition);
    }
}
