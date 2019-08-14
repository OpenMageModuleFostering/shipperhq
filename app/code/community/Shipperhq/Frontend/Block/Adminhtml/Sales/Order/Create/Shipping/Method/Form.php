<?php

class Shipperhq_Frontend_Block_Adminhtml_Sales_Order_Create_Shipping_Method_Form
    extends Mage_Adminhtml_Block_Sales_Order_Create_Shipping_Method_Form
{
    /**
     * Returns a helper block, that is used to retrieve all data
     *
     * @return Shipperhq_Frontend_Block_Checkout_Helper
     */
    public function getHelperBlock()
    {
        if ($this->_helperBlock === null) {
            $this->_helperBlock = $this->getLayout()->createBlock(
                'shipperhq_frontend/checkout_helper',
                sprintf('%s.helper', $this->getNameInLayout())
            );

            $this->_helperBlock->init(array(
                'calendar_block' => 'shipperhq_calendar/adminhtml_sales_order_create_shipping_method_calendar',
                'calendar_template' => 'shipperhq/calendar/sales/order/create/shipping_method/calendar.phtml',
                'pickup_block' => 'shipperhq_pickup/adminhtml_sales_order_create_shipping_method_storepickup',
                'pickup_template' => 'shipperhq/pickup/sales/order/create/shipping_method/storepickup.phtml',
                'accessorials_block' => 'shipperhq_freight/adminhtml_sales_order_create_shipping_method_accessorials',
                'accessorials_template' => 'shipperhq/freight/sales/order/create/shipping_method/accessorials.phtml',
                'quote' => $this->getQuote(),
                'address' => $this->getAddress()
            ));
        }


        return $this->_helperBlock;
    }

    public function getCarrierGroupRates()
    {
        return $this->getHelperBlock()->getCarrierGroupRates();
    }

    public function getProcessedShippingRates()
    {
        return $this->getHelperBlock()->getProcessedShippingRates($this->getShippingRates());
    }

    public function adminShippingEnabled()
    {
        return Mage::getStoreConfigFlag('carriers/shipper/custom_admin');
    }

    /*
     * Retrieves the items details ready for printing to checkout when < 2 carrier groups are in checkout
     */
    public function getSingleItemDetails($shippingRates)
    {
        return $this->getHelperBlock()->getSingleItemDetails($shippingRates);
    }

    /**
     * Populates the location select dropdown
     * @return string
     */
    public function getShippingMethodsSelect($name, $id, $value, $rates, $sole)
    {
        return $this->getHelperBlock()->getShippingMethodsSelect($name, $id, $value, $rates, $sole);
    }

    public function getAddressShippingMethod()
    {
        return $this->getHelperBlock()->getAddressShippingMethod();
    }

    public function isShippingMethodSelected($rates)
    {
        return $this->getHelperBlock()->isShippingMethodSelected($rates);
    }

    public function getShippingPrice($price, $flag, $includeContainer = true)
    {
        return $this->getHelperBlock()->getShippingPrice($price, $flag, $includeContainer);
    }

    public function showToolTips() {
        return $this->getHelperBlock()->showToolTips();
    }

    public function getToolTipText()
    {
        return $this->getHelperBlock()->getToolTipText();
    }

    public function getMethodTitle($methodTitle, $methodDescription, $includeContainer)
    {
        return $this->getHelperBlock()->getMethodTitle($methodTitle, $methodDescription, $includeContainer);
    }

    protected function getFormattedItemList($items)
    {
        return $this->getHelperBlock()->getFormattedItemList($items);
    }

    public function getCarrierGroupAddressShippingMethod($carrierGroup)
    {
        return $this->getHelperBlock()->getCarrierGroupAddressShippingMethod($carrierGroup);
    }

    public function showItemDescription()
    {
        return $this->getHelperBlock()->showItemDescription();
    }

    public function getStorepickupHtml($carrierCode, $carrierType, $carriergroup = null )
    {
        return $this->getHelperBlock()->getStorepickupHtml($carrierCode, $carrierType, $carriergroup);
    }

    public function getCalendarHtml($carriergroup, $code, $soleMethod = false)
    {
        return $this->getHelperBlock()->getCalendarHtml($carriergroup, $code, $soleMethod);
    }

    public function isPickupCarrier($rate)
    {
        return $this->getHelperBlock()->isPickupCarrier($rate);
    }

    public function isFreightCarrier($rate)
    {
        return $this->getHelperBlock()->isFreightCarrier($rate);
    }

    public function getFreightAccessorialsHtml($carrierCode, $carrierType, $carrerigroup = '')
    {
        return $this->getHelperBlock()->getFreightAccessorialsHtml($carrerigroup, $carrierCode, $carrierType);
    }
}