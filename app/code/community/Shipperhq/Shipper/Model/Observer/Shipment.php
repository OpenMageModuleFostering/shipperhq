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
 * Shipper HQ Shipping
 *
 * @category ShipperHQ
 * @package ShipperHQ_Shipping_Carrier
 * @copyright Copyright (c) 2014 Zowta LLC (http://www.ShipperHQ.com)
 * @license http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @author ShipperHQ Team sales@shipperhq.com
 */

include_once 'ShipperHQ/WS/Client/WebServiceClient.php';
include_once 'ShipperHQ/WS/Response/ErrorMessages.php';

class Shipperhq_Shipper_Model_Observer_Shipment extends Mage_Core_Model_Abstract
{
    protected $_shipperWSInstance = null;

    /*
     * Process shipment additional information
     *
     */
    public function processShipmentAddon($observer)
    {
        if(Mage::helper('shipperhq_shipper')->isModuleEnabled('Shipperhq_Pbint') &&
            Mage::helper('shipperhq_shipper')->isModuleEnabled('Shipperhq_Shipper', 'carriers/shipper/active')) {
            //if pitney order required
            $helper = Mage::getModel('shipperhq_pbint/helper');
            $shipment = $observer->getShipment();
             if ($helper->analyzeProcessShipment($shipment)) {
                 $shipment = $observer->getShipment();
                 $order = $shipment->getOrder();
                 if($order) {
                    //this should be keyed off the shipment ID not the order id I believe, so we can have more than one shipment per order
                    $pbOrders = Mage::getModel("shipperhq_pbint/ordernumber")->getCollection();
                    $pbOrders->addFieldToFilter('mage_order_number', $order->getRealOrderId());
                     $result = false;

                    foreach ($pbOrders as $oneOrder) {
                        $pitneyOrderId = $oneOrder->getCpOrderNumber();
                        $tracks = array();
                        foreach($shipment->getTracksCollection() as $track) {
                            $tracks[] = $track;
                        }
                        $packagesColl= Mage::getModel('shipperhq_shipper/order_packages')
                            ->loadByOrderId($order->getId());
                        foreach($packagesColl as $package) {
                            $request = Mage::getSingleton('Shipperhq_Shipper_Model_Carrier_Convert_ShipmentMapper')->
                                getShipmentTranslation($order, $shipment, $package, $pitneyOrderId);
                            $timeout = Mage::helper('shipperhq_shipper')->getWebserviceTimeout();
                            $response = $this->_getShipperInstance()->sendAndReceive($request,
                                Mage::helper('shipperhq_shipper')->getShipmentAddonGatewayUrl(), $timeout);
                            if (Mage::helper('shipperhq_shipper')->isDebug()) {
                                $request = json_decode($response['debug']['json_request']);
                                Mage::helper('wsalogger/log')->postDebug('Shipperhq_Shipper',
                                    'Process Shipment Add On request',$request);
                                Mage::helper('wsalogger/log')->postDebug('Shipperhq_Pbint',
                                    'Process Shipment Add On response', $response['result']);
                            }
                            if($response && isset($response['result'])) {
                                $result = $helper->processShipmentAddonResponse($shipment, $response['result'], $pitneyOrderId);
                            }
                            else {
                                $result = false;
                            }
                        }

                    }
                     return $result;

                 }
             }

            return false;

        }
    }


    protected function _getShipperInstance()
    {
        if (empty($this->_shipperWSInstance)) {
            $this->_shipperWSInstance = new \ShipperHQ\WS\Client\WebServiceClient();
        }
        return $this->_shipperWSInstance;
    }

}