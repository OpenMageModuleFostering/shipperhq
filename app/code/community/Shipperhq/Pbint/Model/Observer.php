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
 * @package ShipperHQ
 * @copyright Copyright (c) 2014 Zowta LLC (http://www.ShipperHQ.com)
 * @license http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @author ShipperHQ Team sales@shipperhq.com
 */
class Shipperhq_Pbint_Model_Observer extends Mage_Core_Model_Abstract
{
    const MODULE_NAME = 'Shipperhq_Pbint';

    public function addDutiesOnEstimation($observer)
    {
        Mage::getSingleton("customer/session")->setPbDutyAndTax(false);

        $quote = Mage::getSingleton('checkout/session')->getQuote();
        $selectedMethod = null;
        if ($quote->getShippingAddress()) {
            $selectedMethod = $quote->getShippingAddress()->getShippingMethod();
        }

        if ($selectedMethod) {
            $rate = $quote->getShippingAddress()->getShippingRateByCode($selectedMethod);
            if($rate) {
                if (Mage::helper('shipperhq_pbint')->isDebug()) {
                    Mage::helper('wsalogger/log')->postDebug('Shipperhq_Pbint',
                        'Add duties on estimation', $rate->getMethod() . ' and tax' . $rate->getCustomDuties());
                }
                Mage::getSingleton("customer/session")->setPbDutyAndTax($rate->getCustomDuties());
            }

        }
    }

    public function modifyOrderView($observer = NULL)
    {
        if (!$observer) {
            return;
        }
        if (Mage::getStoreConfig('advanced/modules_disable_output/' . self::MODULE_NAME))
            return;
        $transport = $observer->getEvent()->getTransport();
        $layoutName = $observer->getEvent()->getBlock()->getNameInLayout();
        if ('order_info' == $layoutName) {

            $pitneyOrderNumber = $this->_getCpOrderNumber($observer->getEvent()->getBlock()->getOrder());
            if ($pitneyOrderNumber) {
                $html = "<div class='entry-edit'>
                    <div class='entry-edit-head'>
                        <h4 class='icon-head '>Pitney Bowes Shipments</h4>
                    </div>
                    <fieldset>
                       <strong>PB Order Number</strong>
                         <span>" . $pitneyOrderNumber->getCpOrderNumber() . "</span><br/>
                          <strong>Hub ID</strong>
                          <span>" . $pitneyOrderNumber->getHubId() . "</span><br/>
                          <strong>Hub Street 1</strong>
                          <span>" . $pitneyOrderNumber->getHubStreet1() . "</span><br/>
                          <strong>Hub Street 2</strong>
                          <span>" . $pitneyOrderNumber->getHubStreet2() . "</span><br/>
                           <strong>Hub City</strong>
                          <span>" . $pitneyOrderNumber->getHubCity() . "</span><br/>
                           <strong>Hub Province/State</strong>
                          <span>" . $pitneyOrderNumber->getHubProvinceOrState() . "</span><br/>
                           <strong>Hub Zip</strong>
                          <span>" . $pitneyOrderNumber->getHubPostcode() . "</span><br/>
                           <strong>Hub Country</strong>
                          <span>" . $pitneyOrderNumber->getHubCountry() . "</span><br/>
                        </fieldset>
                    </div>";
                $transport['html'] = $transport['html'] . $html;
            }



        } else if ('email/order/shipment/track.phtml' == $observer->getEvent()->getBlock()->getTemplate()) {

            $cpord = $this->_getCPORD($observer->getEvent()->getBlock()->getOrder());
            if ($cpord) {
                $staging = 0;
               //TODO offer staging
                $transport['html'] = "<a href='http://tracking.ecommerce.pb.com/track/$cpord?staging=$staging'>Track your order</a>";
            }

        } else if ('shipping.tracking.popup' == $layoutName) {

            $helper = Mage::helper('shipping');
            $data = $helper->decodeTrackingHash($observer->getEvent()->getBlock()->getRequest()->getParam('hash'));

            $orderId = null;
            if ($data['key'] == 'order_id')
                $orderId = $data['id'];
            else if ($data['key'] == 'ship_id') {
                /* @var $model Mage_Sales_Model_Order_Shipment */
                $model = Mage::getModel('sales/order_shipment');
                $ship = $model->load($data['id']);
                $orderId = $model->getOrderId();
            } else if ($data['key'] == 'track_id') {
                $track = Mage::getModel('sales/order_shipment_track')->load($data['id']);
                $orderId = $track->getOrderId();
            }
            if (!$orderId)
                return;
            $cpord = $this->_getCPORD(Mage::getModel('sales/order')->load($orderId));
            if ($cpord) {
                $staging = 0;
              //TODO offer staging
                $script = "<script lang='javascript'>
                                window.location = 'http://tracking.ecommerce.pb.com/track/$cpord?staging=$staging';
                           </script>
                            ";
                $transport['html'] = $script;
            }

        }
        return $this;
    }

    private function _getCPORD($order)
    {
        if ($order) {
            $cpOrder = $this->_getCpOrderNumber($order);
            if ($cpOrder) {
                return $cpOrder->getCpOrderNumber();
            }

        }

        return false;
    }

    private function _getCpOrderNumber($order)
    {
        if ($order) {
            $clearPathOrders = Mage::getModel("shipperhq_pbint/ordernumber")->getCollection();

            $clearPathOrders->addFieldToFilter('mage_order_number', $order->getRealOrderId());
            foreach ($clearPathOrders as $cpOrder) {
                return $cpOrder;

            }
        }

        return false;
    }
}

?>
