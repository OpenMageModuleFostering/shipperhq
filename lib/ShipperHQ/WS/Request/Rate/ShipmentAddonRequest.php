<?php

namespace ShipperHQ\WS\Request\Rate;
use ShipperHQ\Shipping\Address;

include_once 'ShipperHQ/WS/Request/AbstractWebServiceRequest.php';
include_once 'ShipperHQ/WS/Request/WebServiceRequest.php';

/**
 * Class RateRequest
 *
 * @package ShipperHQ\WS\Request\Rate
 */
class ShipmentAddonRequest extends \ShipperHQ\WS\Request\AbstractWebServiceRequest implements \ShipperHQ\WS\Request\WebServiceRequest
{
    public $carrierCode;
    public $orderNo;
    public $reserveOrderNo;
    public $trackingCarrier;
    public $trackingNo;
    public $trackingMethodCode;
    public $shipment;
    public $originName;

    /**
     * @param null $shipmentList
     * @param null $carrierId
     * @param null $orderId
     */
    function __construct($shipmentList = null, $carrierCode = null, $trackingCarrier = null, $trackingMethodCode = null, $orderId = null, $originName = null,
                         $reserveOrderNo = null, $trackinNo = null)
    {
        $this->shipment = $shipmentList;
        $this->carrierCode = $carrierCode;
        $this->trackingCarrier = $trackingCarrier;
        $this->trackingMethodCode = $trackingMethodCode;
        $this->orderNo = $orderId;
        $this->originName = $originName;
        $this->reserveOrderNo = $reserveOrderNo;
        $this->trackingNo = $trackinNo;
    }

    /**
     * @param null $carrierId
     */
    public function setCarrierCode($carrierCode)
    {
        $this->carrierCode = $carrierCode;
    }

    /**
     * @return null
     */
    public function getCarrierCode()
    {
        return $this->carrierCode;
    }

    /**
     * @param String $trackingCarrier
     */
    public function setTrackingCarrier($trackingCarrier)
    {
        $this->trackingCarrier = $trackingCarrier;
    }

    /**
     * @return null
     */
    public function getTrackingCarrier()
    {
        return $this->trackingCarrier;
    }


    /**
     * @param null $orderId
     */
    public function setOrderNo($orderId)
    {
        $this->orderNo = $orderId;
    }

    /**
     * @return null
     */
    public function getOrderNo()
    {
        return $this->orderNo;
    }

    /**
     * @param null $shipmentList
     */
    public function setShipment($shipmentList)
    {
        $this->shipment = $shipmentList;
    }

    /**
     * @return null
     */
    public function getShipment()
    {
        return $this->shipment;
    }

    /**
     * @return mixed
     */
    public function getReserveOrderNo()
    {
        return $this->reserveOrderNo;
    }

    /**
     * @param mixed $reserveOrderNo
     */
    public function setReserveOrderNo($reserveOrderNo)
    {
        $this->reserveOrderNo = $reserveOrderNo;
    }

    /**
     * @param mixed $trackingNo
     */
    public function setTrackingNo($trackingNo)
    {
        $this->trackingNo = $trackingNo;
    }

    /**
     * @return mixed
     */
    public function getTrackingNo()
    {
        return $this->trackingNo;
    }

    /**
     * @param mixed $methodCode
     */
    public function setTrackingMethodCode($trackingMethodCode)
    {
        $this->trackingMethodCode = $trackingMethodCode;
    }

    /**
     * @return mixed
     */
    public function getTrackingMethodCode()
    {
        return $this->trackingMethodCode;
    }

    /**
     * @param null $originName
     */
    public function setOriginName($originName)
    {
        $this->originName = $originName;
    }

    /**
     * @return null
     */
    public function getOriginName()
    {
        return $this->originName;
    }

}
