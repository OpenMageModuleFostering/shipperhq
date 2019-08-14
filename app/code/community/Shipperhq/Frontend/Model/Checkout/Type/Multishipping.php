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

class Shipperhq_Frontend_Model_Checkout_Type_Multishipping extends Mage_Checkout_Model_Type_Multishipping
{

    /**
     * Set payment method info to quote payment
     *
     * @param array $payment
     * @return Mage_Checkout_Model_Type_Multishipping
     */
    public function setPaymentMethod($payment)
    {
        if (!isset($payment['method'])) {
            Mage::throwException(Mage::helper('checkout')->__('Payment method is not defined'));
        }
        $quote = $this->getQuote();
        $quote->getPayment()->importData($payment);

        // shipping totals may be affected by payment method
        if (!$quote->isVirtual() && $quote->getShippingAddress()) {
            //     $quote->getShippingAddress()->setCollectShippingRates(true);
            $quote->setTotalsCollectedFlag(false)->collectTotals();
        }
        $quote->save();
        return $this;
    }

    /**
     * Prepare order based on quote address
     *
     * @param   Mage_Sales_Model_Quote_Address $address
     * @return  Mage_Sales_Model_Order
     * @throws  Mage_Checkout_Exception
     */
    protected function _prepareOrder(Mage_Sales_Model_Quote_Address $address)
    {
        $quote = $this->getQuote();
        $quote->unsReservedOrderId();
        $quote->reserveOrderId();
        $quote->collectTotals();

        $convertQuote = Mage::getSingleton('sales/convert_quote');
        $order = $convertQuote->addressToOrder($address);
        $order->setQuote($quote);
        $order->setBillingAddress(
            $convertQuote->addressToOrderAddress($quote->getBillingAddress())
        );

        if ($address->getAddressType() == 'billing') {
            $order->setIsVirtual(1);
        } else {
            $order->setShippingAddress($convertQuote->addressToOrderAddress($address));
        }

        $order->setPayment($convertQuote->paymentToOrderPayment($quote->getPayment()));
        if (Mage::app()->getStore()->roundPrice($address->getGrandTotal()) == 0) {
            $order->getPayment()->setMethod('free');
        }

        foreach ($address->getAllItems() as $item) {
            $_quoteItem = $item->getQuoteItem();
            if (!$_quoteItem) {
                throw new Mage_Checkout_Exception(Mage::helper('checkout')->__('Item not found or already ordered'));
            }
            $item->setProductType($_quoteItem->getProductType())
                ->setProductOptions(
                    $_quoteItem->getProduct()->getTypeInstance(true)->getOrderOptions($_quoteItem->getProduct())
                );
            //   $item->setCarriergroupId($_quoteItem->getCarriergroupId());
            //   $item->setCarriergroup($_quoteItem->getCarriergroup());
            $orderItem = $convertQuote->itemToOrderItem($item);
            if ($item->getParentItem()) {
                $orderItem->setParentItem($order->getItemByQuoteItemId($item->getParentItem()->getId()));
            }
            $order->addItem($orderItem);
        }

        return $order;
    }

    /**
     * Add quote item to specific shipping address based on customer address id
     *
     * @param int $quoteItemId
     * @param array $data array('qty'=>$qty, 'address'=>$customerAddressId)
     * @return Mage_Checkout_Model_Type_Multishipping
     */
    protected function _addShippingItem($quoteItemId, $data)
    {
        if(!Mage::helper('shipperhq_pickup')->preselectPickupEnabled()) {
            return parent::_addShippingItem($quoteItemId, $data);
        }

        $qty       = isset($data['qty']) ? (int) $data['qty'] : 1;
        //$qty       = $qty > 0 ? $qty : 1;
        $addressId = isset($data['address']) ? $data['address'] : false;
        $quoteItem = $this->getQuote()->getItemById($quoteItemId);

        if ($addressId && $quoteItem) {
            /**
             * Skip item processing if qty 0
             */
            if ($qty === 0) {
                return $this;
            }
            $quoteItem->setMultishippingQty((int)$quoteItem->getMultishippingQty()+$qty);
            $quoteItem->setQty($quoteItem->getMultishippingQty());
            $preselectedPickupAddressId = Mage::helper('shipperhq_pickup')->getPreselectPickupAddressId();
            if(strstr($addressId, $preselectedPickupAddressId)) {
                $address = $this->getCustomer()->getDefaultBillingAddress();
            } else {
                $address = $this->getCustomer()->getAddressById($addressId);
            }
            if ($address->getId()) {
                if(strstr($addressId, $preselectedPickupAddressId)) {
                    if($this->_storePickupAddressId == false) {
                        $quoteAddress = Mage::getModel('sales/quote_address')->importCustomerAddress($address);
                        $quoteAddress->setCustomerAddressId($addressId);
                        $this->_storePickupAddressId = true;
                        $this->getQuote()->addShippingAddress($quoteAddress);
                    }
                }
                elseif (!$quoteAddress = $this->getQuote()->getShippingAddressByCustomerAddressId($address->getId())) {
                    $quoteAddress = Mage::getModel('sales/quote_address')->importCustomerAddress($address);
                    $this->getQuote()->addShippingAddress($quoteAddress);
                }
                if(strstr($addressId, $preselectedPickupAddressId)) {
                    if (Mage::helper('shipperhq_shipper')->isDebug()) {
                        Mage::helper('wsalogger/log')->postInfo('ShipperHQ Pickup', 'Creating separate address for store pickup item',
                            $quoteItem->getSku());
                    }
                    $quoteAddress = $this->getQuote()->getShippingAddressByCustomerAddressId($addressId);
                    $quoteAddress->setPickupPreselected(true);
                }
                else {
                    $quoteAddress = $this->getQuote()->getShippingAddressByCustomerAddressId($address->getId());
                }
                if ($quoteAddressItem = $quoteAddress->getItemByQuoteItemId($quoteItemId)) {
                    $quoteAddressItem->setQty((int)($quoteAddressItem->getQty()+$qty));
                } else {
                    $quoteAddress->addItem($quoteItem, $qty);
                }

                /**
                 * Require shiping rate recollect
                 */
                $quoteAddress->setCollectShippingRates((boolean) $this->getCollectRatesFlag());
            }
        }
        return $this;
    }
}