<?php

/**
 *
 * Webshopapps Shipping Module
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 *
 * Shipper HQ Pitney Bowes International
 *
 * @category ShipperHQ
 * @package ShipperHQ_Shipping_Carrier
 * @copyright Copyright (c) 2014 Zowta LLC (http://www.ShipperHQ.com)
 * @license http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @author ShipperHQ Team sales@shipperhq.com
 */
class Shipperhq_Pbint_Model_Helper extends Mage_Core_Model_Abstract
{

    public function processReserveOrderResponse($response)
    {
        if($response) {
            $errors = (array)$response->errors;
            $responseSummary = (array)$response->responseSummary;
            if(($errors && count($errors) > 0) || $responseSummary['status'] != 1) {
                if(Mage::helper('shipperhq_shipper')->isDebug()) {
                    Mage::helper('wsalogger/log')->postInfo('Shipperhq_PbInt', 'Shipper HQ could not reserve the order',
                        $errors, $responseSummary);
                }
                //Mage::throwException("Unable to create Pb order.");
                return false;
            }

            $orderId = $response->orderId;
            $shipToHub = (array)$response->shipToHub;
            $rateInformation = (array)$response->carrierRate;
            if(empty($shipToHub) || empty($rateInformation) || $orderId == 'Not Set') {
                if(Mage::helper('shipperhq_shipper')->isDebug()) {
                    Mage::helper('wsalogger/log')->postInfo('Shipperhq_PbInt', 'Shipper HQ did not return Pitney International duties information for order',
                        $response);
                }
                return false;
            }
            if($rateInformation && isset($rateInformation['rates']) && isset($rateInformation['rates'][0])) {
                $rate = $rateInformation['rates'][0];
                Mage::getSingleton("customer/session")->setPbDutyAndTax($rate->customDuties);
            }
            $orderNumber = Mage::getModel("shipperhq_pbint/ordernumber");
            $hubAddress = $shipToHub['address'];
            $orderNumber->setCpOrderNumber($orderId);
            $orderNumber->setHubId($shipToHub['hubId']);
            $orderNumber->setHubStreet1($hubAddress->street);
            $orderNumber->setHubStreet2($hubAddress->street2);
            $orderNumber->setHubProvinceOrState($hubAddress->region);
            $orderNumber->setHubCity($hubAddress->city);
            $orderNumber->setHubPostcode($hubAddress->zipcode);
            $orderNumber->setHubCountry($hubAddress->country);

            Mage::getSingleton("customer/session")->setPbOrderNumber($orderNumber);
            if(Mage::helper('shipperhq_shipper')->isDebug()) {
                Mage::helper('wsalogger/log')->postInfo('Shipperhq_PbInt',
                    'Shipper HQ Pitney International reserve order successful',
                    "Order ID: " .$orderId);
            }
            return true;
        }

    }
    /*
     * Determine whether this shipment requires processing to generate ASN
     */
    public function analyzeProcessShipment($shipment)
    {
        $processRequired = false;

        $order = $shipment->getOrder();
        /* @var $order Mage_Sales_Model_Order */

        if(Mage::helper('shipperhq_pbint')->isPbOrder($order->getCarrierType())) {
            $processRequired = true;
        }
        $parcel = Mage::getModel("shipperhq_pbint/inboundparcel")-> getCollection();
        $parcel->addFieldToFilter('mage_order_number', $order->getRealOrderId());
        if(count($parcel) > 0) {
            if (Mage::helper('shipperhq_pbint')->isDebug()) {
                Mage::helper('wsalogger/log')->postDebug('Shipperhq_Pbint',
                    'Processing shipment', 'Inbound parcel already created for this order');
            }
            $processRequired = false;
        }

        return $processRequired;

    }

    public function processShipmentAddonResponse($shipment, $response, $pitneyOrderId)
    {
        try {

            if($response) {
                $errors = (array)$response->errors;
                $responseSummary = (array)$response->responseSummary;
                if(($errors && count($errors) > 0) || $responseSummary['status'] != 1) {
                    if(Mage::helper('shipperhq_shipper')->isDebug()) {
                        Mage::helper('wsalogger/log')->postInfo('Shipperhq_PbInt', 'Shipper HQ could create ASN for Pitney order',
                            $errors, $responseSummary);
                    }
                    return false;
                }
                $orderID = $shipment->getOrder()->getRealOrderId();
                if(!isset($response->parcelId)) {
                    if(Mage::helper('shipperhq_shipper')->isDebug()) {
                        Mage::helper('wsalogger/log')->postWarning('Shipperhq_PbInt', 'Shipper HQ inbound parcel response.',
                            'No parcel ID returned for orderID' .$orderID . ', Pitney Order ID: ' .$pitneyOrderId .' with response: ' .$response);
                    }
                    return;
                }
                $pitneyParcel = Mage::getModel('shipperhq_pbint/inboundparcel');
                    $pitneyParcel->setInboundParcel($response->parcelId);
                    $pitneyParcel->setMageOrderNumber($orderID);
                    $pitneyParcel->setPbOrderNumber( $pitneyOrderId);
                    $pitneyParcel->save();
                if(Mage::helper('shipperhq_shipper')->isDebug()) {
                    Mage::helper('wsalogger/log')->postInfo('Shipperhq_PbInt', 'Shipper HQ created inbound parcel ID.',
                    'Magento Order ID: ' .$orderID . ', Pitney Order ID: ' .$pitneyOrderId .' with ASN: ' .$response->parcelId);
                }


            }
        }
        catch(Exception $e) {
            if (Mage::helper('shipperhq_pbint')->isDebug()) {
                Mage::helper('wsalogger/log')->postWarning('Shipperhq_Pbint',
                    'Error creating inbound parcel.', $e->getMessage());
            }
            Mage::logException($e);
        }
    }

    public function confirmOrderRequired($magentoOrderNumber)
    {
        Mage::getSingleton("customer/session")->setPbDutyAndTax(0);
        $orderNumber = Mage::getSingleton("customer/session")->getPbOrderNumber();

        if($orderNumber) {
            $orderNumber->setMageOrderNumber($magentoOrderNumber);
            $orderNumber->setConfirmed(false);
            $orderNumber->setReferenced(false);
            $orderNumber->save();
            if (Mage::helper('shipperhq_pbint')->isDebug()) {
                Mage::helper('wsalogger/log')->postDebug('Shipperhq_Pbint',
                    'Confirm order required with ShipperHQ Pitney for',  $magentoOrderNumber.'. Hub ID: '
                    .$orderNumber->getHubId() . ', Hub country: ' . $orderNumber->getHubCountry());
            }
            Mage::getSingleton("customer/session")->setPbOrderNumber($orderNumber);
            return $orderNumber->getData('cp_order_number');
        }
        return false;
    }

    public function processConfirmOrderResponse($response)
    {
        $pbOrder = Mage::getSingleton("customer/session")->getPbOrderNumber();

        if(!is_object($response)) {
            if (Mage::helper('shipperhq_pbint')->isDebug()) {
                Mage::helper('wsalogger/log')->postWarning('Shipperhq_Pbint',
                    'Confirm Order did not return a response', $response);
            }
            return;
        }

        $errors = (array)$response->errors;
        $responseSummary = (array)$response->responseSummary;
        $result = isset($response->result) ? (array)$response->result : false;
        if(($errors && count($errors) > 0) || $responseSummary['status'] != 1) {
            if (Mage::helper('shipperhq_pbint')->isDebug()) {
                Mage::helper('wsalogger/log')->postWarning('Shipperhq_Pbint',
                    'Error confirming order', $response);
            }
        }
        elseif(in_array('SUCCCESS', $result)) {
            try {
                $pbOrder->setConfirmed(true);
                $pbOrder->setReferenced(true);
                $pbOrder->save();
                if (Mage::helper('shipperhq_pbint')->isDebug()) {
                    Mage::helper('wsalogger/log')->postDebug('Shipperhq_Pbint',
                    'Confirmed order for ShipperHQ Pitney Bowes', $pbOrder->getData('mage_order_number'));
                }
            }
            catch(Exception $e) {
                if (Mage::helper('shipperhq_pbint')->isDebug()) {
                    Mage::helper('wsalogger/log')->postWarning('Shipperhq_Pbint',
                        'Confirm order response processing, error', $e->getMessage());
                }
            }
        }
    }

    /*
     * reset session saved variables
     */
    public function cleanDownSession()
    {
        Mage::getSingleton("customer/session")->setPbDutyAndTax(false);
        Mage::getSingleton("customer/session")->setPbOrderNumber(false);

    }
}