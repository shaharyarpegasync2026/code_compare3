<?php

/**
 * Copyright © 2009-2025 Amasty. All Rights Reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace BusinessCentral\Customers\Plugin\Customer\Grid;

use Magento\Customer\Model\ResourceModel\Grid\Collection as CustomerGridCollection;

class CollectionPlugin
{
    /**
     * @param CustomerGridCollection $subject
     * @return void
     */
    public function beforeLoad(CustomerGridCollection $subject): void
    {
        $select = $subject->getSelect();

        $from = $select->getPart('from');
        if (!isset($from['main_table'])) {
            return;
        }

        $fromParts = array_change_key_case($from, CASE_LOWER);
        if (!isset($fromParts['bcc'])) {
            $bcTable = $subject->getTable('businesscentral_customers');
            $select->joinLeft(
                ['bcc' => $bcTable],
                'bcc.email = main_table.email',
                [
                    'bc_dynamics365_code' => 'bcc.dynamics365_code',
                    'bc_dynamics_sync' => new \Zend_Db_Expr("IF(bcc.dynamics365_code IS NULL OR bcc.dynamics365_code = '', 0, 1)")
                ]
            );
        }

        $subject->addFilterToMap('bc_dynamics_sync', 'bc_dynamics_sync');
        $subject->addFilterToMap('bc_dynamics365_code', 'bcc.dynamics365_code');
    }
}


