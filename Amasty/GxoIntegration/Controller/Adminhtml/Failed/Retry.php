<?php

/**
 * Copyright © 2009-2025 Amasty. All Rights Reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace Amasty\GxoIntegration\Controller\Adminhtml\Failed;

use Amasty\GxoIntegration\Api\FailedSyncManagementInterface;
use Amasty\GxoIntegration\Model\Queue\Enqueuer;
use Magento\Backend\App\Action;
use Magento\Framework\Controller\Result\Redirect;

class Retry extends Action
{
    public const ADMIN_RESOURCE = 'Amasty_GxoIntegration::failed_sync';

    /**
     * @var FailedSyncManagementInterface
     */
    private $failedSyncManagement;

    /**
     * @var Enqueuer
     */
    private $enqueuer;

    public function __construct(
        Action\Context $context,
        FailedSyncManagementInterface $failedSyncManagement,
        Enqueuer $enqueuer
    ) {
        parent::__construct($context);
        $this->failedSyncManagement = $failedSyncManagement;
        $this->enqueuer = $enqueuer;
    }

    /**
     * @return Redirect
     */
    public function execute()
    {
        /** @var Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();
        $resultRedirect->setPath('amasty_gxo/failed/index');

        $entityId = (int)$this->getRequest()->getParam('entity_id');
        if (!$entityId) {
            $this->messageManager->addErrorMessage(__('Record ID is missing.'));
            return $resultRedirect;
        }

        $orderId = $this->failedSyncManagement->getOrderIdByEntityId($entityId);
        if (!$orderId) {
            $this->messageManager->addErrorMessage(__('Order ID was not found for this record.'));
            return $resultRedirect;
        }

        $this->enqueuer->enqueueManual($orderId);
        $this->messageManager->addSuccessMessage(__('The order was queued for GXO synchronization.'));
        return $resultRedirect;
    }
}
