<?php

/**
 * Copyright © 2009-2025 Amasty. All Rights Reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace Amasty\GxoIntegration\Controller\Adminhtml\Failed;

use Amasty\GxoIntegration\Model\Queue\Enqueuer;
use Amasty\GxoIntegration\Model\ResourceModel\FailedSync\CollectionFactory;
use Magento\Backend\App\Action;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Ui\Component\MassAction\Filter;

class MassRetry extends Action
{
    public const ADMIN_RESOURCE = 'Amasty_GxoIntegration::failed_sync';

    /**
     * @var Filter
     */
    private $filter;

    /**
     * @var CollectionFactory
     */
    private $collectionFactory;

    /**
     * @var Enqueuer
     */
    private $enqueuer;

    public function __construct(
        Action\Context $context,
        Filter $filter,
        CollectionFactory $collectionFactory,
        Enqueuer $enqueuer
    ) {
        parent::__construct($context);
        $this->filter = $filter;
        $this->collectionFactory = $collectionFactory;
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

        $collection = $this->filter->getCollection($this->collectionFactory->create());
        $queued = 0;

        foreach ($collection as $item) {
            $orderId = (int)$item->getData('order_id');
            if (!$orderId) {
                continue;
            }
            $this->enqueuer->enqueueManual($orderId);
            $queued++;
        }

        if ($queued) {
            $this->messageManager->addSuccessMessage(__('Queued %1 order(s) for GXO synchronization.', $queued));
        } else {
            $this->messageManager->addNoticeMessage(__('No records were queued.'));
        }

        return $resultRedirect;
    }
}
