<?php

/**
 * Copyright © 2009-2026 Amasty. All Rights Reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace Amasty\GxoIntegration\Model\Payload;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Directory\Model\Currency;
use Magento\Framework\Locale\CurrencyInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderItemInterface;

class OrderPayloadBuilder
{
    /**
     * @var CurrencyInterface
     */
    private $currency;

    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    public function __construct(
        CurrencyInterface $currency,
        ProductRepositoryInterface $productRepository
    ) {
        $this->currency = $currency;
        $this->productRepository = $productRepository;
    }

    /**
     * @param OrderInterface $order
     * @return array
     */
    public function build(OrderInterface $order): array
    {
        $incrementId = (string) $order->getIncrementId();
        $customerId = $order->getCustomerId();
        $customerIdValue = $customerId ? (string) $customerId : 'GUEST';

        $orderDate = $this->formatDate((string) $order->getCreatedAt());

        $shipping = $order->getShippingAddress() ?: $order->getBillingAddress();
        $billing = $order->getBillingAddress() ?: $shipping;

        $street = $shipping ? (array) $shipping->getStreet() : [];
        $street1 = (string) ($street[0] ?? '');
        $street2 = (string) ($street[1] ?? '');
        if (strlen($street1) > 60) {
            $street2 = substr($street1, 60) . ($street2 !== '' ? ' ' . $street2 : '');
            $street1 = substr($street1, 0, 60);
        }
        if (strlen($street2) > 60) {
            $street2 = substr($street2, 0, 60);
        }

        $contactFirstName = $shipping ? (string) $shipping->getFirstname() : '';
        $contactTelephone = $shipping ? (string) $shipping->getTelephone() : '';
        $contactEmail = (string) $order->getCustomerEmail();
        $recipientName = $shipping ? trim((string) $shipping->getName()) : '';
        if (strlen($recipientName) > 25) {
            $recipientName = substr($recipientName, 0, 25);
        }

        $dispatchMethod = (string) $order->getShippingMethod();
        $dispatchMethod = $order->getShippingMethod() == 'tpn_tpn' ? 'TPN' : 'TPS';

        $currencyCode = (string) $order->getOrderCurrencyCode();
        $currencySymbol = $this->getCurrencySymbol($currencyCode);

        $lines = $this->buildLines($order, $currencySymbol, $currencyCode, $incrementId);

        return [
            [
                'header' => [
                    'Order_Id' => $incrementId,
                    'Customer_Id' => $customerIdValue,
                    'Order_Date' => $orderDate,
                    'Contact' => $contactFirstName,
                    'Contact_Phone' => $contactTelephone,
                    'Contact_Email' => $contactEmail,
                    'Name' => $recipientName,
                    'Address1' => $street1,
                    'Address2' => $street2,
                    'Town' => $shipping ? (string) $shipping->getCity() : '',
                    'Postcode' => $shipping ? (string) $shipping->getPostcode() : '',
                    'County' => $shipping ? (string) $shipping->getRegion() : '',
                    'Country' => $shipping ? (string) $shipping->getCountryId() : '',
                    'Dispatch_Method' => $dispatchMethod,
                    'Vat_Number' => $billing ? (string) $billing->getVatId() : ''
                ],
                'lineGrp' => [
                    'lines' => $lines
                ]
            ]
        ];
    }

    /**
     * @param OrderInterface $order
     * @param string $currencySymbol
     * @param string $currencyCode
     * @param string $incrementId
     * @return array
     */
    private function buildLines(
        OrderInterface $order,
        string $currencySymbol,
        string $currencyCode,
        string $incrementId
    ): array {
        $lines = [];
        $lineId = 1;

        foreach ($order->getAllVisibleItems() as $item) {
            if (!$item instanceof OrderItemInterface) {
                continue;
            }

            $product = $this->productRepository->get($item->getSku());

            $lines[] = [
                'line' => [
                    'Order_Id' => $incrementId,
                    'Line_Id' => (string) $lineId,
                    'Sku_Id' => (string) $product->getData('dynamics365_item_number'),
                    'Qty_Ordered' => (string) (int) $item->getQtyOrdered(),
                    'Product_Price' => (string) $this->formatPrice((float) $item->getPrice()),
                    'Product_Currency' => $currencySymbol !== '' ? $currencySymbol : $currencyCode
                ]
            ];

            $lineId++;
        }

        return $lines;
    }

    /**
     * @param string $createdAt
     * @return string
     */
    private function formatDate(string $createdAt): string
    {
        try {
            $date = new \DateTimeImmutable($createdAt, new \DateTimeZone('UTC'));
        } catch (\Exception $e) {
            $date = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
        }

        return $date->format('Y-m-d\TH:i:sP');
    }

    /**
     * @param float $value
     * @return string
     */
    private function formatPrice(float $value): string
    {
        return number_format($value, 2, '.', '');
    }

    /**
     * @param string $currencyCode
     * @return string
     */
    private function getCurrencySymbol(string $currencyCode): string
    {
        try {
            $currency = $this->currency->getCurrency($currencyCode);
            if ($currency instanceof Currency) {
                return (string) $currency->getCurrencySymbol();
            }
        } catch (\Exception $e) {
        }

        return '';
    }
}
