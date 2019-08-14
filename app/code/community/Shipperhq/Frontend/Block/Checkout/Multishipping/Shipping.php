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

class Shipperhq_Frontend_Block_Checkout_Multishipping_Shipping extends Mage_Checkout_Block_Multishipping_Shipping
{
    private $_currentAddress;

    public function setCurrentAddress($address){
        $this->_currentAddress = $address;
    }

    public function getAddress()
    {
        return $this->_currentAddress;
    }

    public function getHelperBlock()
    {
        if ($this->_helperBlock === null) {
            $this->_helperBlock = $this->getLayout()->createBlock(
                'shipperhq_frontend/checkout_helper',
                sprintf('%s.helper', $this->getNameInLayout())
            );

            $this->_helperBlock->init(array(
                'calendar_block' => 'shipperhq_calendar/checkout_onepage_shipping_method_calendar',
                'calendar_template' => 'shipperhq/calendar/checkout/onepage/shipping_method/calendar.phtml',
                'pickup_block' => 'shipperhq_pickup/checkout_onepage_shipping_method_storepickup',
                'pickup_template' => 'shipperhq/pickup/checkout/onepage/shipping_method/storepickup.phtml',
                'quote' => $this->getCheckout()->getQuote(),
                'address' => $this->getAddress()
            ));
        }


        return $this->_helperBlock;
    }

    public function getCalendarHtml($carriergroup, $code)
    {
        return $this->getHelperBlock()->getCalendarHtml($carriergroup, $code);
    }

    public function getStorepickupHtml($carrierCode, $carrierType, $carriergroup = null)
    {
        return $this->getHelperBlock()->getStorepickupHtml($carrierCode, $carrierType, $carriergroup);
    }

    public function getCarrierGroupRates($address)
    {
        $this->getHelperBlock()->setAddress($address);
        return $this->getHelperBlock()->getCarrierGroupRates();
    }

    public function getAddressItemsGrouped($address, $cg_items, $carrierGroupId)
    {
        $items = array();
        foreach ($address->getAllItems() as $item) {
            if ($item->getParentItemId()) {
                continue;
            }
            $item->setQuoteItem($this->getCheckout()->getQuote()->getItemById($item->getQuoteItemId()));
            if((int)$carrierGroupId == 0 ) {
                    $items[] = $item;
                }
            else {
                    foreach($cg_items as $cgitem) {
                            if($cgitem->getQuoteItemId() == $item->getQuoteItemId()) {
                                    $items[] = $item;
                                }
                }
            }
        }
        $itemsFilter = new Varien_Filter_Object_Grid();
        $itemsFilter->addFilter(new Varien_Filter_Sprintf('%d'), 'qty');
        return $itemsFilter->filter($items);
   }

    public function getPickupAddress()
    {
       return Mage::helper('shipperhq_pickup')->__('In Store Pickup');
    }

    public function isPickupCarrier($rate)
    {
        return $this->getHelperBlock()->isPickupCarrier($rate);
    }

    public function getMethodTitle($methodTitle, $methodDescription, $includeContainer)
    {
        return $this->getHelperBlock()->getMethodTitle($methodTitle, $methodDescription, $includeContainer);
    }

}

