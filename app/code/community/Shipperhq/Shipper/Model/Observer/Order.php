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

class Shipperhq_Shipper_Model_Observer_Order extends Mage_Core_Model_Abstract
{

    protected $_shipperWSInstance = null;

    /*
     * Process shipping method and save and perform reserve Order if required
     *
     */
    public function saveShippingMethod($observer)
    {
        $request = $observer->getEvent()->getRequest();
        if(!$shippingMethod = $request->getPost('shipping_method', '')) {
            return;
        }
        if(!Mage::helper('shipperhq_shipper')->isModuleEnabled('Shipperhq_Shipper', 'carriers/shipper/active')) {
            return;
        }
        $quote = $observer->getEvent()->getQuote();
        $helper = Mage::getSingleton('shipperhq_shipper/checkout_helper');
        $helper->saveSingleShippingMethod($quote->getShippingAddress(), $shippingMethod);

        $rate = $quote->getShippingAddress()->getShippingRateByCode($shippingMethod);
        if(!$rate) {
            if (Mage::helper('shipperhq_shipper')->isDebug()) {
                Mage::helper('wsalogger/log')->postDebug('Shipperhq_Shipper',
                    'save Shipping Method', "Can't find rate for selected shipping method of " .$shippingMethod);
            }
            return;
        }

        if(Mage::helper('shipperhq_shipper')->isModuleEnabled('Shipperhq_Pbint') &&
            Mage::helper('shipperhq_shipper')->isModuleEnabled('Shipperhq_Shipper', 'carriers/shipper/active')) {
            $quote = $observer->getQuote();
            $address = $quote->getShippingAddress();
            $pbHelper = Mage::getModel('shipperhq_pbint/helper');
            $pbHelper->cleanDownSession();

            if (Mage::helper('shipperhq_pbint')->isPbOrder($address->getCarrierType())) {

                $result = $this->reserveOrder($pbHelper, $address, $rate->getCarrier(), $rate->getCarriergroupId());
            }
            else {
                if (Mage::helper('shipperhq_shipper')->isDebug()) {
                    Mage::helper('wsalogger/log')->postDebug('Shipperhq_Shipper',
                        '', "Selected shipping method is NOT ShipperHQ Pitney");
                }
                return;
            }
        }

    }

    /*
     * Process shipping method and save and perform reserve Order if required
     *
     */
    public function saveShippingMethodMulti($observer)
    {
        if(!Mage::helper('shipperhq_shipper')->isModuleEnabled('Shipperhq_Shipper', 'carriers/shipper/active')) {
            return;
        }
        try {
            $processPitney = false;

            if(Mage::helper('shipperhq_shipper')->isModuleEnabled('Shipperhq_Pbint') &&
                Mage::helper('shipperhq_shipper')->isModuleEnabled('Shipperhq_Shipper', 'carriers/shipper/active')) {
                $processPitney = true;
                $pbHelper = Mage::getModel('shipperhq_pbint/helper');
            }
            $request = $observer->getEvent()->getRequest();
            $shippingMethods = $request->getPost('shipping_method', '');
            if(!is_array($shippingMethods)) {
                return;
            }
            foreach($shippingMethods as $addressId => $shippingMethod) {
                if (empty($shippingMethod)) {
                    return;
                }
                $quote = $observer->getEvent()->getQuote();
                $addresses = $quote->getAllShippingAddresses();
                $shippingAddress = false;
                foreach($addresses as $address) {
                    if($address->getId() == $addressId) {
                        $shippingAddress = $address;
                        break;
                    }

                }
                $rate = $shippingAddress->getShippingRateByCode($shippingMethod);
                if (!$rate) {
                    continue;
                }

                $helper = Mage::getSingleton('shipperhq_shipper/checkout_helper');
                $helper->saveSingleShippingMethod($shippingAddress, $shippingMethod);

                $shippingAddress
                    ->setCarrierType($rate->getCarrierType())
                    ->setCarrierId($rate->getCarrierId())
                    ->save();

                if ($processPitney) {
                    if(Mage::helper('shipperhq_pbint')->isPbOrder($address->getCarrierType())) {
                    $result = $this->reserveOrder($pbHelper, $address, $rate->getCarrier(), $rate->getCarriergroupId());
                    }
                    else {
                        if (Mage::helper('shipperhq_shipper')->isDebug()) {
                            Mage::helper('wsalogger/log')->postDebug('Shipperhq_Shipper',
                                '', "Selected shipping method is NOT ShipperHQ Pitney");
                        }
                        //TODO handle when one is and one isn't Pitney - it will wipe here ?
                        $pbHelper->cleanDownSession();
                    }
                }

            }
        }catch (Exception $e) {
            Mage::logException($e);
        }
    }

    /**
     * Save shipping breakdown per carrier group
     * @param $observer
     */
    public function saveShippingMethodAdmin($observer)
    {
        if(!Mage::helper('shipperhq_shipper')->isModuleEnabled('Shipperhq_Shipper', 'carriers/shipper/active')) {
            return;
        }
        $requestData = $observer->getRequestModel()->getPost();
        $orderData = array();
        if (isset($requestData['order'])) {
            $orderData = $requestData['order'];
        }
        if(!empty($requestData['shipping_method_flag'])) {
            $orderData = $requestData;
        }
        $quote = $observer->getOrderCreateModel()->getQuote();
        Mage::helper('shipperhq_shipper')->setQuote($quote);

        if (!empty($orderData['shipping_method_flag'])) {
            if (!empty($orderData['shipping_method'])) {
                $shippingMethod = $orderData['shipping_method'];
                $helper = Mage::getSingleton('shipperhq_shipper/checkout_helper');
                $helper->saveSingleShippingMethod($quote->getShippingAddress(), $shippingMethod);

                $rate = $quote->getShippingAddress()->getShippingRateByCode($shippingMethod);
                if(!$rate) {
                    if (Mage::helper('shipperhq_shipper')->isDebug()) {
                        Mage::helper('wsalogger/log')->postDebug('Shipperhq_Shipper',
                            'save Shipping Method', "Can't find rate for selected shipping method of " .$shippingMethod);
                    }
                    return;
                }

                if(Mage::helper('shipperhq_shipper')->isModuleEnabled('Shipperhq_Pbint') &&
                    Mage::helper('shipperhq_shipper')->isModuleEnabled('Shipperhq_Shipper', 'carriers/shipper/active')) {
                    $address = $quote->getShippingAddress();
                    $pbHelper = Mage::getModel('shipperhq_pbint/helper');
                    $pbHelper->cleanDownSession();

                    if (Mage::helper('shipperhq_pbint')->isPbOrder($address->getCarrierType())) {

                        $result = $this->reserveOrder($pbHelper, $address, $rate->getCarrier(), $rate->getCarriergroupId());
                    }
                    else {
                        if (Mage::helper('shipperhq_shipper')->isDebug()) {
                            Mage::helper('wsalogger/log')->postDebug('Shipperhq_Shipper',
                                '', "Selected shipping method is NOT ShipperHQ Pitney");
                        }
                    }
                }
                $requestData['order']['shipping_method'] = $orderData['shipping_method'];
            }
            $observer->getRequestModel()->setPost($requestData);
        }
    }

    /*
     *
     */
    public function preDispatchSetMethodsSeparate($observer)
    {
        if(!Mage::helper('shipperhq_shipper')->isModuleEnabled('Shipperhq_Shipper', 'carriers/shipper/active')) {
            return;
        }
        $controller = $observer->getControllerAction();
        $quote = Mage::getSingleton('checkout/session')->getQuote();
        $quoteStorage = Mage::helper('shipperhq_shipper')->getQuoteStorage($quote);

        if ($controller->getRequest()->isPost()) {
            $shippingMethod = $controller->getRequest()->getPost('shipping_method', '');
            if (empty($shippingMethod)) {
                return;
            }

            $helper = Mage::getSingleton('shipperhq_shipper/checkout_helper');
            $helper->saveSingleShippingMethod($quote->getShippingAddress(), $shippingMethod);

            $rate = $quote->getShippingAddress()->getShippingRateByCode($shippingMethod);
            if(!$rate) {
                if (Mage::helper('shipperhq_shipper')->isDebug()) {
                    Mage::helper('wsalogger/log')->postDebug('Shipperhq_Shipper',
                        'setMethodsSeparate', "Can't find rate for selected shipping method of " .$shippingMethod);
                }
                return;
            }

            if(Mage::helper('shipperhq_shipper')->isModuleEnabled('Shipperhq_Pbint') &&
                Mage::helper('shipperhq_shipper')->isModuleEnabled('Shipperhq_Shipper', 'carriers/shipper/active')) {
                $address = $quote->getShippingAddress();
                $pbHelper = Mage::getModel('shipperhq_pbint/helper');
                $pbHelper->cleanDownSession();

                if (Mage::helper('shipperhq_pbint')->isPbOrder($address->getCarrierType())) {

                    $result = $this->reserveOrder($pbHelper, $address, $rate->getCarrier(), $rate->getCarriergroupId());
                }
                else {
                    if (Mage::helper('shipperhq_shipper')->isDebug()) {
                        Mage::helper('wsalogger/log')->postDebug('Shipperhq_Shipper',
                            '', "Selected shipping method is NOT ShipperHQ Pitney");
                    }
                }
            }

        }
    }

    /*
    * Confirm Order action
    *
    */
    public function checkoutOnepageSuccess($observer)
    {
        if(!Mage::helper('shipperhq_shipper')->isModuleEnabled('Shipperhq_Shipper', 'carriers/shipper/active')) {
            return;
        }
        $mageOrderNumber = Mage::getSingleton('checkout/session')->getLastRealOrderId();
        $order = Mage::getModel('sales/order')->loadByIncrementId($mageOrderNumber);
        if($order->getIncrementId()) {
            $this->confirmOrder($order);
        }
    }

    public function checkoutMulitaddressSuccess($observer)
    {
        if(!Mage::helper('shipperhq_shipper')->isModuleEnabled('Shipperhq_Shipper', 'carriers/shipper/active')) {
            return;
        }
        $orderIds = $observer->getEvent()->getOrderIds();
        if (empty($orderIds) || !is_array($orderIds)) {
            return;
        }
        foreach($orderIds as $orderId) {
            $order = Mage::getModel('sales/order')->load($orderId);
            if($order->getIncrementId()) {
                $this->confirmOrder($order);
            }
        }
    }

    public function adminOrderSaveAfter($observer)
    {
        if(!Mage::helper('shipperhq_shipper')->isModuleEnabled('Shipperhq_Shipper', 'carriers/shipper/active')) {
            return;
        }
        $order = $observer->getEvent()->getOrder();
        if($order->getIncrementId()) {
            $this->confirmOrder($order);
        }

    }

    protected function confirmOrder($order)
    {
        $confirm = false;
        $customOrderId = null;
        $pitney = false;
        $orderId = $order->getIncrementId();
        if(Mage::helper('shipperhq_shipper')->isModuleEnabled('Shipperhq_Pbint') &&
            Mage::helper('shipperhq_pbint')->isPbOrder($order->getCarrierType())) {
            $helper = Mage::getModel('shipperhq_pbint/helper');
            $pitney = true;
            if ($customOrderId = $helper->confirmOrderRequired($orderId)) {
                $confirm = true;
            } else {
                if (Mage::helper('shipperhq_pbint')->isDebug()) {
                    Mage::helper('wsalogger/log')->postDebug('Shipperhq_Pbint',
                        'Confirm order observer', $orderId . ' is not a Pitney Order Number');

                }
                $helper->cleanDownSession();
            }

        }

        if(Mage::helper('shipperhq_shipper')->isConfirmOrderRequired($order->getCarrierType())) {
            $confirm = true;

        }
        if($confirm) {
            $quoteId = $order->getQuoteId();
            if($quoteId && $quote = Mage::getModel('sales/quote')->load($quoteId)) {

                $request = Mage::getSingleton('Shipperhq_Shipper_Model_Carrier_Convert_OrderMapper')->
                    getOrderTranslation($order, $quote, $customOrderId);
                $timeout = Mage::helper('shipperhq_shipper')->getWebserviceTimeout();
                $response = $this->_getShipperInstance()->sendAndReceive($request,
                    Mage::helper('shipperhq_shipper')->getConfirmOrderGatewayUrl(), $timeout);
                if (Mage::helper('shipperhq_shipper')->isDebug()) {
                    $request = json_decode($response['debug']['json_request']);
                    Mage::helper('wsalogger/log')->postDebug('Shipperhq_Shipper',
                        'Confirm order request',$request);
                    Mage::helper('wsalogger/log')->postDebug('Shipperhq_Pbint',
                        'Confirm order response', $response['result']);
                }
                if($response && isset($response['result'])) {
                    $result = $this->processConfirmOrderResponse($response['result'], $order);
                    if($pitney) {
                        $result = $helper->processConfirmOrderResponse($response['result']);
                    }

                }
            }

        }
        if(strstr($order->getCarrierType(), 'shqshared_')) {
            $carrierTypeArray = explode('_', $order->getCarrierType());
            if(is_array($carrierTypeArray)) {
                $order->setCarrierType($carrierTypeArray[1]);
                $order->save();
                if (Mage::helper('shipperhq_shipper')->isDebug()) {
                    Mage::helper('wsalogger/log')->postDebug('Shipperhq_Shipper', 'Rates displayed as single carrier',
                        'Resetting carrier type on order to be ' .$carrierTypeArray[1]);
                }
            }
        }
    }

    protected function reserveOrder($helper, $address, $carrierCode, $carriergroupId)
    {
        $request = Mage::getSingleton('Shipperhq_Shipper_Model_Carrier_Convert_AddressMapper')->
            getAddressTranslation($address, $carrierCode, $carriergroupId);
        $timeout = Mage::helper('shipperhq_shipper')->getWebserviceTimeout();
        $response = $this->_getShipperInstance()->sendAndReceive($request,
            Mage::helper('shipperhq_shipper')->getReserveOrderGatewayUrl(), $timeout);
        if (Mage::helper('shipperhq_shipper')->isDebug()) {
            $request = $response['debug']['json_request'];
            $request =  json_decode($request);
            Mage::helper('wsalogger/log')->postDebug('Shipperhq_Pbint',
                'Reserve order request', $request);
            Mage::helper('wsalogger/log')->postDebug('Shipperhq_Pbint',
                'Reserve order response', $response['result']);
        }
        if($response && isset($response['result'])) {
            $result = $helper->processReserveOrderResponse($response['result']);
        }
        else {
            $result = false;
        }
        return $result;
    }


    protected function _getShipperInstance()
    {
        if (empty($this->_shipperWSInstance)) {
            $this->_shipperWSInstance = new \ShipperHQ\WS\Client\WebServiceClient();
        }
        return $this->_shipperWSInstance;
    }

    protected function processConfirmOrderResponse($response, $order)
    {
        if(!is_object($response)) {
            if (Mage::helper('shipperhq_shipper')->isDebug()) {
                Mage::helper('wsalogger/log')->postWarning('Shipperhq_Shipper',
                    'Process Confirm Order did not return a response', $response);
            }
            return;
        }
        $errors = (array)$response->errors;
        $responseSummary = (array)$response->responseSummary;
        $result = isset($response->result) ? (array)$response->result : false;
        if(($errors && count($errors) > 0) || $responseSummary['status'] != 1) {
            if (Mage::helper('shipperhq_shipper')->isDebug()) {
                Mage::helper('wsalogger/log')->postWarning('Shipperhq_Shipper',
                    'Error confirming order', $response);
            }
        }
        elseif(in_array('SUCCCESS', $result)) {
            $confirmationNumber = $response->confirmationNo;
            try {
                $order->setConfirmationNumber($confirmationNumber);
                $order->save();
                if (Mage::helper('shipperhq_shipper')->isDebug()) {
                    Mage::helper('wsalogger/log')->postDebug('Shipperhq_Shipper',
                        'Confirmed order with Shipperhq', $confirmationNumber);
                }
            }
            catch(Exception $e) {
                if (Mage::helper('shipperhq_shipper')->isDebug()) {
                    Mage::helper('wsalogger/log')->postWarning('Shipperhq_Shipper',
                        'Confirm order response processing, error', $e->getMessage());
                }
            }
        }
    }

}