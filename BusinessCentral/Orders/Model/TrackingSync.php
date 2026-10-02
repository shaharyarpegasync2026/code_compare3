<?php

/**
 * Copyright © 2009-2025 Amasty. All Rights Reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace BusinessCentral\Orders\Model;

use BusinessCentral\Api\Api\ApiInterface;
use Magento\Sales\Api\ShipmentRepositoryInterface;
use Magento\Sales\Api\ShipmentTrackRepositoryInterface;
use Magento\Sales\Model\Order\Shipment\TrackFactory;
use Psr\Log\LoggerInterface;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Class TrackingSync
 *
 * @package BusinessCentral\Orders\Model
 */
class TrackingSync
{
    /**
     * @var ApiInterface
     */
    private $api;

    /**
     * @var ShipmentRepositoryInterface
     */
    private $shipmentRepository;

    /**
     * @var ShipmentTrackRepositoryInterface
     */
    private $trackRepository;

    /**
     * @var TrackFactory
     */
    private $trackFactory;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        ApiInterface $api,
        ShipmentRepositoryInterface $shipmentRepository,
        ShipmentTrackRepositoryInterface $trackRepository,
        TrackFactory $trackFactory,
        LoggerInterface $logger
    ) {
        $this->api = $api;
        $this->shipmentRepository = $shipmentRepository;
        $this->trackRepository = $trackRepository;
        $this->trackFactory = $trackFactory;
        $this->logger = $logger;
    }

    /**
     * Sync tracking numbers from Dynamics
     *
     * @return void
     */
    public function sync()
    {
        try {
            $response = $this->api->sendRequest('GET', '/trackings');

            foreach ($response['trackings'] ?? [] as $tracking) {
                $shipmentId = $tracking['shipment_id'];
                $trackNumber = $tracking['track_number'];
                $carrier = $tracking['carrier'] ?? 'custom';

                try {
                    $shipment = $this->shipmentRepository->get($shipmentId);
                } catch (NoSuchEntityException $e) {
                    $this->logger->error('Shipment not found', ['shipment_id' => $shipmentId]);
                    continue;
                }

                $track = $this->trackFactory->create()
                    ->setCarrierCode($carrier)
                    ->setTitle($carrier)
                    ->setTrackNumber($trackNumber);

                $shipment->addTrack($track);
                $this->shipmentRepository->save($shipment);
            }
        } catch (\Exception $e) {
            $this->logger->error('Tracking sync error', ['exception' => $e->getMessage()]);
        }
    }
}
