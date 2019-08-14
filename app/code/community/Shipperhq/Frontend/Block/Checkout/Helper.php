<?php

class Shipperhq_Frontend_Block_Checkout_Helper
    extends Shipperhq_Frontend_Block_Checkout_AbstractBlock
{

    private static $_debug;

    protected $_storepickup;
    protected $_calendar;
    protected $_rates;
    protected $_accessorial;


    /**
     * Calendar block name
     * 
     * @var string
     */
    protected $_calendarBlockType;

    /**
     * Calendar block template
     *
     * @var string
     */
    protected $_calendarBlockTemplate;

    /**
     * Store pickup block name
     * 
     * @var string
     */
    protected $_storePickupBlockType;

    /**
     * Store pickup block template
     *
     * @var string
     */
    protected $_storePickupBlockTemplate;

    /**
     * Freight accessorials block name
     *
     * @var string
     */
    protected $_freightAccessorialsBlockType;

    /*
     * Freight accessorials block template
     *
     * @var string
     */
    protected $_freightAccessorialsBlockTemplate;

    /**
     * Sets debug flag
     */
    protected function _construct() {
        self::$_debug = Mage::helper('wsalogger')->isDebug('Shipperhq_Splitrates');

        parent::_construct();

    }

    /**
     * Sets up block properties
     * 
     * @param array $options
     * @return $this
     */
    public function init($options)
    {
        if (isset($options['calendar_block'])) {
            $this->setCalendarBlockType($options['calendar_block']);
        }
        if (isset($options['calendar_template'])) {
            $this->setCalendarBlockTemplate($options['calendar_template']);
        }
        if (isset($options['pickup_block'])) {
            $this->setStorePickUpBlockType($options['pickup_block']);
        }
        if (isset($options['pickup_template'])) {
            $this->setStorePickUpBlockTemplate($options['pickup_template']);
        }
        if (isset($options['accessorials_block'])) {
            $this->setFreightAccessorialsBlockType($options['accessorials_block']);
        }
        if (isset($options['accessorials_template'])) {
            $this->setFreightAccessorialsBlockTemplate($options['accessorials_template']);
        }
        if (isset($options['quote'])) {
            $this->setQuote($options['quote']);
        } 
        if (isset($options['address'])) {
            $this->setAddress($options['address']);
        }
        
        return $this;
    }

    public function getCarrierGroupRates()
    {
        self::$_debug = true;
        if (self::$_debug) {
            Mage::helper('wsalogger/log')->postInfo('ShipperHQ','Getting Carrier Group Rates','');
        }
        $shippingRateGroups = $this->getShippingRates();
        if (count($shippingRateGroups)==0) {
            return;
        }

        $carrierGroupRates=array();

        $address = $this->getAddress();
        $address->unsetData('cached_items_all');
        $address->unsetData('cached_items_nonnominal');

        $itemsGrouped = Mage::helper('shipperhq_shipper')->getItemsGroupedByCarrierGroup($address->getAllItems());
        $carrierGroupDescriber = Mage::getStoreConfig(Shipperhq_Shipper_Helper_Data::SHIPPERHQ_SHIPPER_CARRIERGROUP_DESC_PATH)?
            Mage::getStoreConfig(Shipperhq_Shipper_Helper_Data::SHIPPERHQ_SHIPPER_CARRIERGROUP_DESC_PATH) : 'Vendor' ;
        $displayMerged = $address->getCheckoutDisplayMerged() == 1? true : false;

        $carrierGroupText = array();
        $carrierGroupErrors = array();
        foreach ($shippingRateGroups as $code=>$rates) {
            $tempGroupRates=array();
            foreach ($rates as $rate) {

                $carriergroup=$rate->getCarriergroupId();
                if(!$displayMerged && is_null($carriergroup)) {
                    continue;
                }
                $checkoutText = $rate->getCarriergroup();
                if (array_key_exists($carriergroup,$tempGroupRates)) {
                    $tempGroupRates[$carriergroup][]=$rate;
                } else {
                    $tempGroupRates[$carriergroup]= array($rate);
                }
                if($rate->getErrorMessage()) {
                    $carrierGroupErrors[$carriergroup][$code] = true;
                }
                if(isset($carriergroup) && $checkoutText != '') {
                    $carrierGroupText[$carriergroup] = $checkoutText;
                }
            }

            foreach ($tempGroupRates as $carriergroup=>$rates) {
                if (array_key_exists($carriergroup,$carrierGroupRates)) {
                    $carrierGroupRates[$carriergroup]['shipping'][$code] = $rates;
                } else {
                    $carrierGroupRates[$carriergroup] = array (
                        'item_list'	=> false,
                        'shipping'	=> array(
                            $code => $rates
                        )
                    );
                }
            }
        }

        foreach ($carrierGroupRates as $carriergroup=>$value) {
            if (array_key_exists($carriergroup,$itemsGrouped)) {
                $carrierGroupRates[$carriergroup]['item_list']= $this->getFormattedItemList(
                    $itemsGrouped[$carriergroup]);
                $carrierGroupRates[$carriergroup]['plain_item_list']= $itemsGrouped[$carriergroup];
            }
            if(array_key_exists($carriergroup, $carrierGroupErrors)) {
                $carrierGroupRates[$carriergroup]['error']= $carrierGroupErrors[$carriergroup];
            }
            if(array_key_exists($carriergroup, $carrierGroupText)) {
                $carrierGroupRates[$carriergroup]['checkout_text'] = $carrierGroupText[$carriergroup];
            }
            else {
                $carrierGroupRates[$carriergroup]['checkout_text'] = '';
            }
            $carrierGroupRates[$carriergroup]['carriergroup_text'] = $carrierGroupDescriber;
        }

        if (self::$_debug) {
            Mage::helper('wsalogger/log')->postInfo('ShipperHQ','Rates display information',$carrierGroupText);
        }

        return $carrierGroupRates;
    }

    public function getShippingRates()
    {

      //  if (empty($this->_rates)) {
            $this->getAddress()->collectShippingRates()->save();
            $groups = $this->getAddress()->getGroupedAllShippingRates();
        //    return $this->_rates = $groups;
       // }

        return $groups;//$this->_rates;
    }

    public function getProcessedShippingRates($shippingRateGroups)
    {
        $processedRates = array();
        $errorArray = array();
        if(is_array($shippingRateGroups)) {
            foreach($shippingRateGroups as $code => $rates)
            {
                foreach($rates as $rate)
                {
                    if($rate->getErrorMessage())
                    {
                        $errorArray[$code] = true;
                    }
                }
            }
        }
        $processedRates['shipping'] = $shippingRateGroups;
        if(count($errorArray) > 0 ) {
            $processedRates['error'] = $errorArray;
        }

        return $processedRates;
    }

    /*
     * Retrieves the items details ready for printing to checkout when < 2 carrier groups are in checkout
     */
    public function getSingleItemDetails($shippingRates)
    {
        $address = $this->getAddress();
        $formattedItems = array();

        $itemsGrouped = Mage::helper('shipperhq_shipper')->getItemsGroupedByCarrierGroup($address->getAllItems());

        if (count($itemsGrouped)>1) {
            if (self::$_debug) {
                Mage::helper('wsalogger/log')->postDebug('ShipperHQ','Single Warehouse','Found more than 1 carrier group so returning');
            }
            return array();
        }
        $checkoutText = '';

        foreach ($shippingRates as $code=>$rates) {
            foreach ($rates as $rate) {
                $carriergroupShippingDetails = Mage::helper('shipperhq_shipper')->decodeShippingDetails($rate->getCarriergroupShippingDetails());
                foreach($carriergroupShippingDetails as $detail) {
                    if(array_key_exists('carriergroup', $detail)) {
                        $checkoutText = $detail['carriergroup'];
                    }
                }
            }
        }

        // now format these
        foreach ($itemsGrouped as $carriergroupId=>$items) {
            $formattedItems[] = array (
                'carriergroupId' => $carriergroupId,
                'checkout_text' => $checkoutText,
                'item_list'  => $this->getFormattedItemList($items));
        }
        return $formattedItems;

    }

    /**
     * Populates the location select dropdown
     * @return string
     */
    public function getShippingMethodsSelect($name, $id, $value, $rates, $sole)
    {
        $options = array();
        $error = false;
        $matchedToSelected = false;
        foreach($rates as $_rate) {

            if($_rate->getErrorMessage()) {
                $options = '<dt><ul class="messages"><li class="error-msg"><ul><li>'.$_rate->getErrorMessage() .'</li></ul></li></ul></dt>';

                $error = true;
                break;
            }
            $title = $_rate->getMethodTitle();
            $_excl = $this->getShippingPrice($_rate->getPrice(), $this->helper('tax')->displayShippingPriceIncludingTax(), false);
            $_incl = $this->getShippingPrice($_rate->getPrice(), true, false);
            $label =  $title .' ' .$_excl;
            if ($this->helper('tax')->displayShippingBothPrices() && $_incl != $_excl)
            {
                $label .= ' (' .$this->__('Incl. Tax') .' ' .$_incl .')';
            }
            $options[] = array(
                'value' => $_rate->getCode(),
                'label' => $label,
            );
            if($_rate->getCode() == $value) {
                $matchedToSelected = true;
            }
        }

        if($error) {
            return $options;
        }

        $select = $this->getLayout()->createBlock('core/html_select')
            ->setName($name)
            ->setId($id)
            ->setClass('shipping-method-select')
            ->setValue($value)
            ->setOptions($options);
        if(!$sole) {
            $select->setExtraParams('hidden="true"');
        }
        if($matchedToSelected) {
            $select->setValue($value);
        }
        return $select->getHtml();
    }

    public function isShippingMethodSelected($rates)
    {
        $selectedShippingMethod = $this->getAddressShippingMethod();
        foreach($rates as $rate)
        {
            if($rate->getCode() == $selectedShippingMethod) {
                return true;
            }
        }
        return false;
    }

    public function getShippingPrice($price, $flag, $includeContainer = true)
    {
        return $this->getQuote()->getStore()->convertPrice(Mage::helper('tax')->getShippingPrice($price, $flag, $this->getAddress()), true, $includeContainer);
    }

    public function getMethodTitle($methodTitle, $methodDescription, $includeContainer)
    {
        $title = $methodTitle;
        if($includeContainer) {
            $truncatedTitle = str_replace($methodDescription, '', $methodTitle);
            $title = '<span class="method-title">'.$truncatedTitle.'</span> <span class="method-extra">'.$methodDescription.'</span>';
        }
        return $title;
    }

    public function showToolTips() {
        $globals = Mage::helper('shipperhq_shipper')->getGlobalSettings();
        if (!is_array($globals) || !array_key_exists('shippingTooltipText', $globals)
            || $globals['shippingTooltipText'] == '') {
            return false;
        }
        return true;
    }

    public function getToolTipText()
    {
        $globals = Mage::helper('shipperhq_shipper')->getGlobalSettings();
        if (is_null($globals) || !array_key_exists('shippingTooltipText', $globals)
            || $globals['shippingTooltipText'] == '') {
            return false;
        }
        return addslashes($globals['shippingTooltipText']);
    }

    protected function getFormattedItemList($items)
    {
        $globals = Mage::helper('shipperhq_shipper')->getGlobalSettings();
        if (is_null($globals)) {
            return array();
        }
        $useParent = true;
        $showAllItems = true;
        $formattedItemList=array();

        $weightUnit =  'none';
        $showWeight = array_key_exists('dropshipShowWeight', $globals) ? $globals['dropshipShowWeight']: false;
        if($showWeight) {
            $weightUnit = array_key_exists('weightUnit', $globals) ? $globals['weightUnit']: 'none';
        }

        foreach ($items as $item) {
            if ($item->getParentItem() && ( ($item->getParentItem()->getProductType() == Mage_Catalog_Model_Product_Type::TYPE_BUNDLE && $useParent && !$showAllItems)
                    || $item->getParentItem()->getProductType() == Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE  )) {
                continue;
            }

            if (!$useParent && $item->getHasChildren() && $item->getProductType() == Mage_Catalog_Model_Product_Type::TYPE_BUNDLE ) {
                continue;
            }

            if ($item->getHasChildren() && $item->isShipSeparately() && !$useParent) {
                foreach ($item->getChildren() as $child) {
                    $formattedItemList[]= self::getStyledHtmlItem(
                        'bundle_child',$child->getQty(),$child->getProduct()->getName(),$child->getWeight(),$weightUnit);
                }
            } else {
                $formattedItemList[] = self::getFormattedItem($item ,$weightUnit, $showWeight);
            }
        }
        if (self::$_debug) {
            Mage::helper('wsalogger/log')->postDebug('ShipperHQ','Formatted Item List',$formattedItemList);
        }

        return $formattedItemList;
    }


    protected static function getFormattedItem($item, $weightUnit, $showWeight) {

        $class = 'non_bundle';

        if ($item->getHasChildren()) {
            $class = 'bundle_parent';
        } elseif ($item->getParentItemId()) {
            $class = 'bundle_child';
        }

        $styleHtmlItem = self::getStyledHtmlItem(
            $class,$item->getQty(),$item->getName(),$item->getWeight(), $weightUnit);

        return $styleHtmlItem;
    }

    protected static function getStyledHtmlItem($itemType, $qty, $name,$weight,$weightUnit)
    {
        $weightHtmlDesc = $weightUnit == 'none' ? '' : '
           <span class="'.$itemType.'_weight">'.round($weight).$weightUnit.'</span>';

        $qtyHtmlDesc =  '<span class="'.$itemType.'_qty">'.$qty.' x '.'</span>';

        $nameHtmlDesc =  '<span class="'.$itemType.'_name">'.$name.'</span>';

        return $qtyHtmlDesc.$nameHtmlDesc.$weightHtmlDesc;

    }

    public function getCarrierGroupAddressShippingMethod($carrierGroup)
    {
        $shippingDetails = $this->getQuote()->getShippingAddress()->getCarriergroupShippingDetails();
        if (empty($shippingDetails) || $shippingDetails=='') {
            return;
        }
        $_cargrps = Mage::helper('shipperhq_shipper')->decodeShippingDetails($shippingDetails);
        foreach ($_cargrps as $_cargrp) {
            if (is_array($_cargrp) && $_cargrp['carrierGroupId']==$carrierGroup) {
                return $_cargrp['code'];
            }
        }

    }

    public function showItemDescription()
    {
        $globals = Mage::helper('shipperhq_shipper')->getGlobalSettings();
        $showDesc =  $globals['dropshipShowItemDesc'];
        return $showDesc;
    }

    public function getStorepickupHtml($carrierCode, $carrierType, $carriergroup = null )
    {
        return $this->_getStorePickup('storepickup_'.$carrierCode)
            ->setCarriergroupId($carriergroup)
            ->setCarrierCode($carrierCode)
            ->setIsAccessPoints(Mage::helper('shipperhq_pickup')->isUpsAccessPointCarrier($carrierType))
            ->setCarrierType($carrierType)
            ->setName('storepickup_'.$carrierCode)
            ->setTemplate($this->getStorePickUpBlockTemplate())
            ->toHtml();
    }

    public function getCalendarHtml($carriergroup, $code, $soleMethod = false)
    {

        return $this->_getCalendar()
            ->setCarriergroupId($carriergroup)
            ->setCarrierCode($code)
            ->setSoleMethod($soleMethod)
            ->setName('calendar')
            ->setTemplate($this->getCalendarBlockTemplate())
            ->toHtml();
    }

    public function isPickupCarrier($rate)
    {
        if(!Mage::helper('shipperhq_shipper')->isModuleEnabled('Shipperhq_Pickup')) {
            return false;
        }
        if($rate && Mage::helper('shipperhq_pickup')->isPickupEnabledCarrier($rate->getCarrierType())) {
            if (self::$_debug) {
                Mage::helper('wsalogger/log')->postDebug('ShipperHQ Pickup','Following carrier has pickup enabled: ',
                   'Carrier: ' .$rate->getCode() .', Type : ' .$rate->getCarrierType() );
            }
            return true;
        }
        if (self::$_debug) {
            Mage::helper('wsalogger/log')->postDebug('ShipperHQ Pickup','This carrier is not pickup enabled ',
                'Carrier: ' .$rate->getCode() .', Type : ' .$rate->getCarrierType());
        }
        return false;
    }

    public function isFreightCarrier($rate)
    {
        if(!Mage::helper('shipperhq_shipper')->isModuleEnabled('Shipperhq_Freight')) {
            return false;
        }
        if($rate && $rate->getFreightRate() == 1) {
            if (self::$_debug) {
                Mage::helper('wsalogger/log')->postDebug('ShipperHQ Freight','Carrier is freight enabled',
                    'Carrier : ' . $rate->getCode() .', Is freight rate : ' .$rate->getFreightRate()
                   );
            }
            return true;
        }
        if (self::$_debug) {
            Mage::helper('wsalogger/log')->postDebug('ShipperHQ Freight','Carrier is NOT freight enabled',
                'Carrier : ' . $rate->getCode() .', Is freight rate : ' .$rate->getFreightRate()
            );
        }
        return false;
    }

    public function getFreightAccessorialsHtml($carrierGroup, $carrierCode, $carrierType)
    {
        //go and get the freight accessorials and set them here for this one carrier

        return $this->_getAccessorial('freight_'.$carrierCode)
            ->setCarriergroupId($carrierGroup)
            ->setCarrierCode($carrierCode)
            ->setName('accessorial_'.$carrierCode)
            ->setTemplate($this->getFreightAccessorialsBlockTemplate())
            ->toHtml();
    }

    protected function _getStorePickup($name)
    {
        $this->_storepickup = $this->getLayout()->createBlock($this->getStorePickUpBlockType(), $name);
        $this->_storepickup->setQuote($this->getQuote());
        $this->_storepickup->setAddress($this->getAddress());
        return $this->_storepickup;
    }

    protected function _getCalendar($name = '')
    {
        if (!$this->_calendar) {
            $this->_calendar = $this->getLayout()->createBlock($this->getCalendarBlockType(), $name);
            $this->_calendar->setQuote($this->getQuote());
            $this->_calendar->setAddress($this->getAddress());
        }

        return $this->_calendar;
    }

    protected function _getAccessorial($name = '')
    {
        if (!$this->_accessorial) {
            $this->_accessorial = $this->getLayout()->createBlock($this->getFreightAccessorialsBlockType(), $name);
            $this->_accessorial->setQuote($this->getQuote());
            $this->_accessorial->setAddress($this->getAddress());
        }

        return $this->_accessorial;
    }

    /**
     * Returns a block type, that should be used for a calendar
     * 
     * @return string
     */
    public function getStorePickUpBlockType()
    {
        return $this->_storePickupBlockType;
    }

    /**
     * Sets store pickup block type
     * 
     * @param $blockType
     * @return $this
     */
    public function setStorePickUpBlockType($blockType)
    {
        $this->_storePickupBlockType = $blockType;
        return $this;
    }

    /**
     * Returns calendar block type
     * 
     * @return string
     */
    public function getCalendarBlockType()
    {
        return $this->_calendarBlockType;
    }

    /**
     * Sets calendar block type
     * 
     * @param string $blockType
     * @return $this
     */
    public function setCalendarBlockType($blockType)
    {
        $this->_calendarBlockType = $blockType;
        return $this;
    }

    /**
     * Returns a block type, that should be used for a calendar
     *
     * @return string
     */
    public function getStorePickUpBlockTemplate()
    {
        return $this->_storePickupBlockTemplate;
    }

    /**
     * Sets store pickup block type
     *
     * @param string $template
     * @return $this
     */
    public function setStorePickUpBlockTemplate($template)
    {
        $this->_storePickupBlockTemplate = $template;
        return $this;
    }

    /**
     * Returns calendar block type
     *
     * @return string
     */
    public function getCalendarBlockTemplate()
    {
        return $this->_calendarBlockTemplate;
    }

    /**
     * Sets calendar block type
     *
     * @param string $blockType
     * @return $this
     */
    public function setCalendarBlockTemplate($blockType)
    {
        $this->_calendarBlockTemplate = $blockType;
        return $this;
    }

    /**
     * @param mixed $freightAccessorialsBlockTemplate
     */
    public function setFreightAccessorialsBlockTemplate($freightAccessorialsBlockTemplate)
    {
        $this->_freightAccessorialsBlockTemplate = $freightAccessorialsBlockTemplate;
    }

    /**
     * @return mixed
     */
    public function getFreightAccessorialsBlockTemplate()
    {
        return $this->_freightAccessorialsBlockTemplate;
    }

    /**
     * @param string $freightAccessorialsBlockType
     */
    public function setFreightAccessorialsBlockType($freightAccessorialsBlockType)
    {
        $this->_freightAccessorialsBlockType = $freightAccessorialsBlockType;
    }

    /**
     * @return string
     */
    public function getFreightAccessorialsBlockType()
    {
        return $this->_freightAccessorialsBlockType;
    }
    
    

    public function getCarrierName($carrierCode)
    {
        if ($name = Mage::getStoreConfig('carriers/'.$carrierCode.'/title', $this->getQuote()->getStoreId())) {
            return $name;
        }
        
        return $carrierCode;
    }

    public function getAddressShippingMethod()
    {
        return $this->getAddress()->getShippingMethod();
    }
}