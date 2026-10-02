<?php

/**
 * Copyright © 2009-2026 Amasty. All Rights Reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace Amasty\GxoIntegration\Controller\Adminhtml\Order;

use Amasty\GxoIntegration\Model\Config;
use Amasty\GxoIntegration\Model\Queue\Enqueuer;
use Magento\Backend\App\Action;
use Magento\Framework\Controller\Result\Redirect;

class Sync extends Action
{
    public const ADMIN_RESOURCE = 'Amasty_GxoIntegration::manual_sync';

    /**
     * @var Enqueuer
     */
    private $enqueuer;

    /**
     * @var Config
     */
    private $config;

    public function __construct(
        Action\Context $context,
        Enqueuer $enqueuer,
        Config $config
    ) {
        parent::__construct($context);
        $this->enqueuer = $enqueuer;
        $this->config = $config;
    }

    /**
     * @return Redirect
     */
    public function execute()
    {
        /** @var Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();
        $orderId = (int)$this->getRequest()->getParam('order_id');
        $resultRedirect->setPath('sales/order/view', ['order_id' => $orderId]);

        if (!$orderId) {
            $this->messageManager->addErrorMessage(__('Order ID is missing.'));
            return $resultRedirect;
        }

        if (!$this->config->isEnabled()) {
            $this->messageManager->addErrorMessage(__('GXO Integration is disabled.'));
            return $resultRedirect;
        }

        $this->enqueuer->enqueueManual($orderId);
        $this->messageManager->addSuccessMessage(__('The order was queued for GXO synchronization.'));

        return $resultRedirect;
    }
}
