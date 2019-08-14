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

/**
 * Shipping data helper
 */
class Shipperhq_Shipper_Helper_Data extends Mage_Core_Helper_Abstract
{
    protected static $_debug;
    protected static $_showTransId;
    protected static $_dateFormat;
    protected static $_zendDateFormat;
    protected static $_datepickerFormat;
    protected static $_shortDateFormat;
    protected static $_wsTimeout;
    protected $_prodAttributes;
    protected $_baseCurrencyRate;

    /**
     * @var Shipperhq_Shipper_Model_Storage_Manager
     */
    protected $_storageManager;

    /**
     * @var Mage_Sales_Model_Quote
     */
    protected $_quote;

    public $magentoCarrierCodes =
        array( 'ups' => 'ups',
            'fedEx' => 'fedex',
            'usps' => 'usps',
            'dhl' => 'dhl',
            'dhlint' => 'dhlint'
        );

    CONST CALENDAR_DATE_OPTION = 'calendar';
    CONST DELIVERY_DATE_OPTION = 'delivery_date';
    CONST TIME_IN_TRANSIT = 'time_in_transit';
    CONST SHIPPERHQ_SHIPPER_CARRIERGROUP_DESC_PATH = 'carriers/shipper/carriergroup_describer';

    /**
     * Check is module exists and enabled in global config.
     *
     * @param $moduleName
     */
    public function isModuleEnabled($moduleName = null,$enabledLocation=null)
    {
        if ($moduleName === null) {
            $moduleName = $this->_getModuleName();
        }

        if (!Mage::getConfig()->getNode('modules/' . $moduleName)) {
            return false;
        }

        $isActive = Mage::getConfig()->getNode('modules/' . $moduleName . '/active');
        if (!$isActive || !in_array((string)$isActive, array('true', '1'))) {
            return false;
        }

        if ($enabledLocation === null) {
            return true;
        }

        if (!Mage::getStoreConfig($enabledLocation)) {
            return false;
        }

        return true;
    }

    /**
     * Retrieve debug configuration
     * @return boolean
     */
    public function isDebug()
    {
        if (self::$_debug == NULL) {
            self::$_debug = Mage::helper('wsalogger')->isDebug('Shipperhq_Shipper');
        }
        return self::$_debug;
    }


    /**
     * Retrieve debug configuration
     * @return boolean
     */
    public function isTransactionIdEnabled()
    {
        if (self::$_showTransId == NULL) {
            self::$_showTransId = Mage::getStoreConfigFlag('carriers/shipper/display_transaction');
        }
        return self::$_showTransId;

    }

    public function getTransactionId()
    {
        $id = Mage::registry('shipperhq_transaction');
        return $id;
    }

    /**
     * Returns an instance of storage manager
     * 
     * @return Shipperhq_Shipper_Model_Storage_Manager
     */
    public function storageManager()
    {
        if ($this->_storageManager === null) {
            $this->_storageManager = Mage::getModel('shipperhq_shipper/storage_manager');
        }
        
        return $this->_storageManager;
    }

    /**
     * Returns a storage for a quote
     * 
     * @param Mage_Sales_Model_Quote|null $quote
     * @return Shipperhq_Shipper_Model_Storage|bool
     */
    public function getQuoteStorage($quote = null)
    {
        if ($quote === null) {
            $quote = $this->getQuote();
        }
        
        return $this->storageManager()->findByQuote($quote);
    }

    public function chooseCarrierAndProcess($carrierRate, $carrierGroupId = null, $carrierGroupDetail = null, $isSplit = false)
    {
        $carrierCode = $carrierRate->carrierCode;
        $sort = isset($carrierRate->sortOrder) ? $carrierRate->sortOrder : false;
        $this->_dynamicCarrierConfig($carrierCode, $carrierRate->carrierType, $carrierRate->carrierTitle, $sort);

        $isCalendar = false;
        $calendarDetails = (array)$carrierRate->calendarDetails;
        if(isset($carrierRate->dateOption) && $carrierRate->dateOption == self::CALENDAR_DATE_OPTION) {
            $isCalendar = true;
        }
        elseif (!empty($calendarDetails) && !isset($carrierRate->dateOption)) {
            //backwards compatibility
            $isCalendar  = true;
        }

        $this->populateCarrierLevelDetails((array)$carrierRate, $carrierGroupDetail);
        if ($this->isModuleEnabled('Shipperhq_Pickup') && Mage::helper('shipperhq_pickup')->isPickupEnabledCarrier($carrierRate->carrierType)) {
            if (!Mage::registry('pickup_carrier')) {
                $model = Mage::getModel('shipperhq_pickup/carrier_storepickup');
                Mage::register('pickup_carrier', $model);
            }
            return Mage::registry('pickup_carrier')->extractShipperhqRates($carrierRate, $carrierGroupId, $carrierGroupDetail, $isSplit);
        }
        else if ($this->isModuleEnabled('Shipperhq_Calendar') && $isCalendar) {
            if (!Mage::registry('calendar_carrier')) {
                $model = Mage::getModel('shipperhq_calendar/carrier_calendar');
                Mage::register('calendar_carrier', $model);
            }
            return Mage::registry('calendar_carrier')->extractShipperhqRates($carrierRate, $carrierGroupId, $carrierGroupDetail, $isSplit);
        }
        //Always process rates with standard if not already done
        if (!Mage::registry('shipper_carrier')) {
            $model = Mage::getModel('shipperhq_shipper/carrier_shipper');
            Mage::register('shipper_carrier', $model);
        }
        return Mage::registry('shipper_carrier')->extractShipperhqRates($carrierRate, $carrierGroupId, $carrierGroupDetail, $isSplit);
    }

    public function populateCarrierLevelDetails($carrierRate, &$carrierGroupDetail)
    {

        $carrierGroupDetail['carrierType'] = $carrierRate['carrierType'];
        $carrierGroupDetail['carrierTitle'] = $carrierRate['carrierTitle'];
        $carrierGroupDetail['carrier_code'] = $carrierRate['carrierCode'];
        $carrierGroupDetail['carrierName'] = $carrierRate['carrierName'];
        $shippingAddress = $this->getQuote()->getShippingAddress();

        //store packages
        if(array_key_exists('shipments', $carrierRate) && $carrierRate['shipments'] != null) {
            if(!$this->getQuote()->getShippingAddress()->getAddressId()) {
                return;
            }
            $cgId = array_key_exists('carrierGroupId', $carrierGroupDetail) ? $carrierGroupDetail['carrierGroupId'] : null;
            $this->cleanUpPackages($shippingAddress->getAddressId(),$cgId, $carrierRate['carrierCode']);
            $mapping = $this->getPackagesMapping();
            $standardData = array('address_id' => $shippingAddress->getAddressId(),
                                'carrier_group_id' => $cgId,
                                'carrier_code' =>  $carrierRate['carrierCode']);
            foreach($carrierRate['shipments'] as $shipment) {
                $data = array_merge($standardData, Mage::helper('shipperhq_shipper/mapper')->map($mapping,(array)$shipment));
                $package = Mage::getModel('shipperhq_shipper/quote_packages');
                $package->setData($data);
                $package->save();
            }
        }

        if(array_key_exists('rates', $carrierRate) && $carrierRate['rates'] != null) {
            foreach($carrierRate['rates'] as $rate) {
                $cgId = array_key_exists('carrierGroupId', $carrierGroupDetail) ? $carrierGroupDetail['carrierGroupId'] : null;
                $this->cleanUpPackages($shippingAddress->getAddressId(),$cgId, $carrierRate['carrierCode'].'_'.$rate->code);
                $mapping = $this->getPackagesMapping();
                $standardData = array('address_id' => $shippingAddress->getAddressId(),
                    'carrier_group_id' => $cgId,
                    'carrier_code' =>  $carrierRate['carrierCode'].'_'.$rate->code);
                if(!isset($rate->shipments)) {
                    continue;
                }
                foreach($rate->shipments as $shipment) {
                    $data = array_merge($standardData, Mage::helper('shipperhq_shipper/mapper')->map($mapping,(array)$shipment));
                    $package = Mage::getModel('shipperhq_shipper/quote_packages');
                    $package->setData($data);
                    $package->save();
                }
            }
        }
    }

    public function isPackageBreakdownDisplayEnabled()
    {
        return Mage::helper('wsalogger')->isDebugError();
    }

    protected function cleanUpPackages($addressId, $carrierGroupId, $carrier_code)
    {
        try{
            $packages  = Mage::getModel('shipperhq_shipper/quote_packages')->loadByCarrier($addressId, $carrierGroupId, $carrier_code);
            foreach($packages as $old)
            {
                $old->delete();
            }
        }
        catch (Exception $e) {
            $result = false;
            if (Mage::helper('shipperhq_shipper')->isDebug()) {
                Mage::helper('wsalogger/log')->postWarning('Shipperhq_Shipper',
                    'Unable to remove existing packages', $e->getMessage());
            }
        }

    }
    protected function getPackagesMapping()
    {
        return array(
            'package_name' => 'name',
            'length' => 'length',
            'width' => 'width',
            'height' => 'height',
            'weight' => 'weight',
            'surcharge_price' => 'surchargePrice',
            'declared_value' => 'declaredValue',
            'items'         => 'boxedItems'
        );
    }

    public function getPackageBreakdown($carrierGroupId, $carrier_code)
    {
        if($this->isPackageBreakdownDisplayEnabled()) {
             $packages  = Mage::getModel('shipperhq_shipper/quote_packages')->loadByCarrier(
                 $this->getQuote()->getShippingAddress()->getAddressId(),$carrierGroupId, $carrier_code);
            return $this->getPackageBreakdownText($packages);
        }

    }

    public function getPackageBreakdownText($packages) {
        $boxText = '';
        $count = 1;
        foreach ($packages as $key=>$box)
        {
            $boxText .= Mage::helper('shipperhq_shipper')->__('Package').' #'.($count++);

            if ($box!=null) {
                $boxText .= ' Box name: ' .$box['package_name'];
                $boxText .= ' : ' . $box['length'];
                $boxText .= 'x' . $box['width'] ;
                $boxText .= 'x'. $box['height'] ;
                $boxText .= ': W='.$box['weight'] . ':' ;
                $boxText .= ' Value='.$box['declaredValue']. ':';
                $boxText .= $this->getProductBreakdownText($box);
            }
            $boxText .= '</br>';
        }
        $boxText .= 'Transaction ID: ' .$this->getTransactionId();
        return $boxText;
    }

    public function getProductBreakdownText($box) {
        $productText = '';
        $weightUnit = $this->getGlobalSetting('weightUnit');
        if(!$weightUnit) {
            $weightUnit = '';
        }
        if (array_key_exists('items',$box)  || (is_object($box) && !is_null($box->getItems()))) {
            if (is_array($box['items'])) {
                foreach ($box['items'] as $item) {
                    $productText .= ' SKU=' .$item['qty_packed'] .' * '.$item['sku'] .' ' .$item['weight_packed'] .$weightUnit .';  ';
                }
            } else {
                $productText = $box['items'];
            }
        }
        return $productText;
    }


    public function populateRateLevelDetails($rate, &$carrierGroupDetail, $currencyConversionRate)
    {
        $carrierGroupDetail['methodTitle'] = $rate['name'];
        $carrierGroupDetail['price'] = (float)$rate['totalCharges']*$currencyConversionRate;
        $carrierGroupDetail['cost'] = (float)$rate['shippingPrice']*$currencyConversionRate;
        $carrierGroupDetail['code'] = $rate['code'];


    }

    public function getBaseCurrencyRate($code)
    {
        $allowedCurrencies = Mage::getModel('directory/currency')->getConfigAllowCurrencies();
        if (!in_array($code, $allowedCurrencies)) {
            return false;
        }
        $baseCurrencyCode = Mage::app()->getStore()->getBaseCurrency()->getCode();
        if (!$this->_baseCurrencyRate) {
            $this->_baseCurrencyRate = Mage::getModel('directory/currency')
                ->load($code)
                ->getAnyRate($baseCurrencyCode);
        }

        return $this->_baseCurrencyRate > 0 ? $this->_baseCurrencyRate : false;

    }
    public function setStandardShipperResponseType()
    {
        $shipping = $this->getQuote()->getShippingAddress();
        if($shipping->getSplitRates()) {
            $shipping->setSplitRates(0);
        }
    }

    public function isCheckout()
    {

        $shipping = $this->getQuote()->getShippingAddress();
        $isCheckout =  $shipping->getIsCheckout();
        if($this->getQuote()->getIsMultiShipping()) {
            return true;
        }
        return $isCheckout;
    }

    public function isMultiAddressCheckout()
    {
        return $this->getQuote()->getIsMultiShipping();
    }

    /**
     * Determine template to use based on cart attributes
     *
     * @return boolean
     */
    public function getAvailableTemplate()
    {

        $splitRates = $this->getQuote()->getShippingAddress()->getSplitRates();
        if(Mage::helper('shipperhq_shipper')->isModuleEnabled('Shipperhq_Splitrates')
            &&  $splitRates == 1) {
            return 'shipperhq/checkout/onepage/shipping_method/available.phtml';
        }
        elseif($this->isModuleEnabled('Shipperhq_Pickup')) {
            return 'shipperhq/checkout/onepage/shipping_method/available.phtml';
        }
        return 'checkout/onepage/shipping_method/available.phtml';
    }

    /**
     * Set template for multiaddress checkout if enabled
     *
     * @return string
     */
    public function getMultiAddressTemplate()
    {
        if(Mage::getStoreConfig('carriers/shipper/active')) {
            return 'shipperhq/checkout/multishipping/shipping.phtml';
        }
        return 'checkout/multishipping/shipping.phtml';
    }

    public function getMultiAddressOverviewTemplate()
    {
        if(Mage::getStoreConfig('carriers/shipper/active')) {
            return 'shipperhq/checkout/multishipping/overview.phtml';
        }
        return "checkout/multishipping/overview.phtml";
    }
    /*
     * Set template for onestepcheckout if enabled
     *
     * @return string
     */
    public function getOnestepcheckoutShippingTemplate()
    {
        if(Mage::getStoreConfig('carriers/shipper/active')) {
            if(Mage::getStoreConfig('onestepcheckout/general/condense_shipping_methods')) {
                return 'shipperhq/checkout/onestepcheckout/shipping_method_osc_shq.phtml';
            }
            else {
                return 'shipperhq/checkout/onestepcheckout/shipping_method_osc_shq_radio.phtml';
            }
        }
        return 'onestepcheckout/shipping_method.phtml';
    }

    /**
     * Retrieve url for getting allowed methods
     * @return string
     */
    public function getAllowedMethodGatewayUrl()
    {
        return $this->_getGatewayUrl().'allowed_methods';
    }

    /**
     * Retrieve url for getting shipping rates
     * @return string
     */
    public function getRateGatewayUrl()
    {
        return  $this->_getGatewayUrl().'rates';

    }

    /**
     * Retrieve url for getting shipping rates
     * @return string
     */
    public function getValidationGatewayUrl()
    {
        return  $this->_getGatewayUrl().'address/validation';

    }

    /*
     * *Retrieve url for retrieving attributes
     */
    public function getAttributeGatewayUrl()
    {
        return $this->_getGatewayUrl().'attributes/get';
    }

    /*
     * *Retrieve url for retrieving attributes
     */
    public function getCheckSynchronizedUrl()
    {
        return $this->_getGatewayUrl().'attributes/check';
    }

    /*
     * *Retrieve url for retrieving attributes
     */
    public function getSetSynchronizedUrl()
    {
        return $this->_getGatewayUrl().'attributes/set/updated';
    }

    /*
     * *Retrieve url for reserving order details
     */
    public function getReserveOrderGatewayUrl()
    {
        return $this->_getGatewayUrl().'order/reserve';
    }

    /*
     * *Retrieve url for confirming order
     */
    public function getConfirmOrderGatewayUrl()
    {
        return $this->_getGatewayUrl().'order/confirm';
    }

    /*
     * *Retrieve url for creating shipment
     */
    public function getCreateShipmentGatewayUrl()
    {
        return $this->_getGatewayUrl().'shipment/create';
    }

    /*
     * *Retrieve url for retrieving shipment add-on details
     */
    public function getShipmentAddonGatewayUrl()
    {
        return $this->_getGatewayUrl().'shipment/addon';
    }

    /*
     * Retrieve configured timeout for webservice
     */
    public function getWebserviceTimeout()
    {

        if (self::$_wsTimeout==NULL) {
            $timeout =  Mage::getStoreConfig('carriers/shipper/ws_timeout');
            if(!is_numeric($timeout) || $timeout < 120) {
                $timeout = 120;
            }
            self::$_wsTimeout = $timeout;
        }
        return self::$_wsTimeout;
    }

    /**
     * Saves the carrier title to core_config_data
     * Need to do this as doesnt read from the shipping rate quote table!
     * @param $carrierCode
     * @param $carrierTitle
     */
    public function saveCarrierTitle($carrierCode,$carrierTitle)
    {
        $this->saveConfig('carriers/'.$carrierCode.'/title',$carrierTitle);
    }

    /**
     * Save config value to db
     * @param $path
     * @param $value
     * @param string $scope
     * @param int $scopeId
     * @return $this
     */
    public function saveConfig($path, $value, $scope = 'default', $scopeId = 0)
    {
        if (Mage::getStoreConfig($path) != $value) {
            Mage::getConfig()->saveConfig(rtrim($path, '/'), $value, $scope, $scopeId);
            Mage::helper('shipperhq_shipper')->getQuoteStorage()->setConfigUpdated(true);
        }
    }

    public function encodeShippingDetails($shippingDetails)
    {
        return Zend_Json::encode($shippingDetails);
    }

    public function decodeShippingDetails($shippingDetailsEnc)
    {
        return Zend_Json::decode($shippingDetailsEnc);
    }

    /**
     * Retrieve checkout quote model object
     *
     * @return Mage_Sales_Model_Quote
     */
    public function getQuote()
    {
        if ($this->_quote !== null) {
            return $this->_quote;
        }
        $quote = Mage::getSingleton('checkout/session')->getQuote();
        if(!$quote->getId()) {
            $quote = Mage::getSingleton('adminhtml/session_quote')->getQuote();
        }
        return $quote;

    }

    /**
     * Overrides quote model
     * 
     * @param Mage_Sales_Model_Quote|null $quote
     * @return $this
     */
    public function setQuote($quote)
    {
        $this->_quote = $quote;
        return $this;
    }

    public function isSortOnPrice()
    {
        return $this->getGlobalSetting('sortBasedPrice');
    }

    public function getGlobalSetting($code)
    {
        $globals = Mage::helper('shipperhq_shipper')->getGlobalSettings();
        if(!is_null($globals) && array_key_exists($code, $globals)
            && $globals[$code] != '') {
            return $globals[$code];
        }
        return false;
    }

    /*
     * Retrieve global settings saved to session
     *
     * @return array
     */
    public function getGlobalSettings()
    {
        return $this->getQuoteStorage($this->getQuote())->getShipperGlobal();
    }

    /**
     * Get a date format from global settings
     *
     * @param string $date
     * @return bool|string
     */
    public static function getDateFormat()
    {
        if (self::$_dateFormat==NULL) {
            $globals = Mage::helper('shipperhq_shipper')->getGlobalSettings();
            if( !is_null($globals) && array_key_exists('preferredLocale', $globals) && $globals['preferredLocale'] != '') {
                if($globals['preferredLocale'] == 'en-US') {
                    $rawDateFormat = 'mm/dd/yyyy';
                }
                else {
                    $rawDateFormat = 'dd-mm-yyyy';
                }
            }
            else {
                $rawDateFormat = 'mm/dd/yyyy';
            }
            self::$_dateFormat =
                Mage::getModel('shipperhq_shipper/carrier_shipper')->getCode('date_format', $rawDateFormat);
        }
        return self::$_dateFormat;
    }

    /**
     * Get a date format in Zend format
     *
     * @param string $date
     * @return bool|string
     */
    public static function getZendDateFormat()
    {
        if (self::$_zendDateFormat==NULL) {
            $globals = Mage::helper('shipperhq_shipper')->getGlobalSettings();
            if( !is_null($globals) && array_key_exists('preferredLocale', $globals) && $globals['preferredLocale'] != '') {
                if($globals['preferredLocale'] == 'en-US') {
                    $rawDateFormat = 'mm/dd/yyyy';
                }
                else {
                    $rawDateFormat = 'dd-mm-yyyy';
                }
            }
            else {
                $rawDateFormat = 'mm/dd/yyyy';
            }
            self::$_zendDateFormat =
                Mage::getModel('shipperhq_shipper/carrier_shipper')->getCode('zend_date_format', $rawDateFormat);
        }
        return self::$_zendDateFormat;
    }

    /**
     * Get a date format for calendar widget display
     *
     * @param string $date
     * @return bool|string
     */
    public static function getDatepickerFormat()
    {
        if (self::$_datepickerFormat==NULL) {
            $globals = Mage::helper('shipperhq_shipper')->getGlobalSettings();
            if( !is_null($globals) && array_key_exists('preferredLocale', $globals) && $globals['preferredLocale'] != '') {
                if($globals['preferredLocale'] == 'en-US') {
                    $rawDateFormat = 'mm/dd/yyyy';
                }
                else {
                    $rawDateFormat = 'dd-mm-yyyy';
                }
            }
            else {
                $rawDateFormat = 'mm/dd/yyyy';
            }
            self::$_datepickerFormat =
                Mage::getModel('shipperhq_shipper/carrier_shipper')->getCode('datepicker_format', $rawDateFormat);
        }
        return self::$_datepickerFormat;
    }

    /**
     * Get a date format in short format
     *
     * @param string $date
     * @return bool|string
     */
    public static function getShortDateFormat()
    {
        if (self::$_shortDateFormat==NULL) {
            $globals = Mage::helper('shipperhq_shipper')->getGlobalSettings();
            if(!is_null($globals) && array_key_exists('dateFormat', $globals) && $globals['dateFormat'] != '') {
                $rawDateFormat = $globals['dateFormat'];
            }
            else {
                $rawDateFormat = 'mm/dd/yyyy';
            }
            self::$_shortDateFormat =
                Mage::getModel('shipperhq_shipper/carrier_shipper')->getCode('short_date_format', $rawDateFormat);
        }
        return self::$_shortDateFormat;
    }

    public function showPickupConfirm()
    {
        return $this->getGlobalSetting('calendarConfirm');
    }

    public function isPickupRate($address, $shippingMethodChosen)
    {
        if(!$this->isModuleEnabled('Shipperhq_Pickup')) {
            return false;
        }
        $rate = $address->getShippingRateByCode($shippingMethodChosen);
        if($rate && Mage::helper('shipperhq_pickup')->isPickupEnabledCarrier($rate->getCarrierType())) {
            return $rate->getCarrier();
        }
        return false;
    }

    /*
    *
    */
    public function getOurCarrierCode()
    {
        return Mage::getModel('shipperhq_shipper/carrier_shipper')->getCarrierCode();
    }

    public function mapToMagentoCarrierCode($carrierType, $carrierCode)
    {
        if(array_key_exists($carrierType, $this->magentoCarrierCodes)) {
            return $this->magentoCarrierCodes[$carrierType];
        }
        return $carrierCode;
    }

    public function refreshConfig()
    {
        if(Mage::helper('shipperhq_shipper')->getQuoteStorage()->getConfigUpdated()) {
            Mage::app()->getStore()->resetConfig();
            Mage::helper('shipperhq_shipper')->getQuoteStorage()->setConfigUpdated(false);
        }
    }

    public function isCityEnabled()
    {
        if($this->isModuleEnabled('Shipperhq_Lookup') && Mage::helper('shipperhq_lookup')->isPostcodeAutocompleteEnabled()) {
            return true;
        }
        $cityRequired = $this->getGlobalSetting('cityRequired');
        if(!$cityRequired) {
            $cityRequired = Mage::getStoreConfig('carriers/shipper/CARRIER_CITY_REQUIRED_DEFAULT');
        }
        return $cityRequired;
    }

    public function isCityRequired()
    {
        return $this->isCityEnabled();
    }

    public function isAccessorialsEnabled()
    {
        return Mage::helper('shipperhq_shipper')->isModuleEnabled('Shipperhq_Freight');
    }
    /**
     *
     * @return array
     */
    public function getProductAttributes()
    {
        if(is_null($this->_prodAttributes)) {
            /** @var $eavConfig Mage_Eav_Model_Config */
            $eavConfig = Mage::getSingleton('eav/config');

            $this->_prodAttributes = $eavConfig->getEntityAttributeCodes(Mage_Catalog_Model_Product::ENTITY);
        }

        return $this->_prodAttributes;
    }

    public function getProductsWithAttributeValue($attribute_code, $value, $storeId = null, $isSelect = false, $returnCount = true)
    {
        if($isSelect) {
            $value = $this->_getOptionId($this->getAttribute($attribute_code, $storeId), $value);
        }
        $collection = Mage::getModel('catalog/product')->setStoreId($storeId)->getCollection();
        if(!is_null($storeId) && $storeId != '') {
            $collection->addStoreFilter((int)$storeId);
        }

        $collection->addAttributeToSelect($attribute_code);
        $collection->addFieldToFilter(array(
            array('attribute'=>$attribute_code,'finset'=>$value),
        ));
        if($returnCount) {
            return count($collection);
        }

        return $collection;
    }

    public function extractAddressIdAndCarriergroupId(&$addressId, &$carrierGroupId)
    {
        if(strstr($carrierGroupId, 'ma')) {
            $addressId = str_replace('ma', '', $carrierGroupId);
            $carrierGroupId = '';
            if(strstr($addressId, 'ZZ')) {
                $idArray = explode('ZZ', $addressId);
                $addressId = $idArray[0];
                $carrierGroupId = $idArray[1];
            }
        }
    }

    public function isOscDropdowns()
    {
        if($this->isModuleEnabled('Idev_OneStepCheckout')
            && Mage::getStoreConfig('onestepcheckout/general/condense_shipping_methods')) {
            return true;
        }
        return false;
    }

    public function isConfirmOrderRequired($carrierType)
    {
        return $carrierType == 'gso';
    }

    public function getCarriergroupShippingHtml($encodedDetails)
    {
        $decodedDetails = Mage::helper('shipperhq_shipper')->decodeShippingDetails($encodedDetails);
        $htmlText='<br/>';
        foreach ($decodedDetails as $shipLine) {

            if(!is_array($shipLine) || !array_key_exists('name', $shipLine)) {
                continue;
            }
            $htmlText .= $shipLine['name'].
                ' : '.$shipLine['carrierTitle'].' - '. $shipLine['methodTitle'].' ';
            $htmlText .= " ". $this->getQuote()->getStore()->formatPrice($shipLine['price']).'<br/>';
            if(array_key_exists('pickup_date', $shipLine)) {
                $htmlText .= Mage::helper('shipperhq_shipper')->__('Location') .' : ' .$shipLine['location_name'];
                $htmlText .= Mage::helper('shipperhq_shipper')->__(' Date') .' : ' .$shipLine['pickup_date'];
                if(array_key_exists('pickup_slot', $shipLine)) {
                    $htmlText .= Mage::helper('shipperhq_shipper')->__(' Time : ') .str_replace('_', ' - ', $shipLine['pickup_slot']);
                }
                $htmlText.= '<br/>';
            }
            else if(array_key_exists('delivery_date', $shipLine)) {
                $htmlText .= Mage::helper('shipperhq_shipper')->__(' Delivery Date') .' : ' .$shipLine['delivery_date'];
                if(array_key_exists('del_slot', $shipLine)) {
                    $htmlText .= Mage::helper('shipperhq_shipper')->__(' Time : ') .str_replace('_', ' - ',$shipLine['del_slot']);
                }
                $htmlText.= '<br/>';
            }

        }
        return $htmlText;
    }

    public function setShippingOnItems($shippingDetails, $shippingAddress)
    {
        $itemsGrouped = $this->getItemsGroupedByCarrierGroup($shippingAddress->getAllItems());
        foreach($shippingDetails as $carrierGroupDetail)
        {
            if(is_array($carrierGroupDetail) && array_key_exists('carrierTitle', $carrierGroupDetail)) {
                $carrierGroupId = $carrierGroupDetail['carrierGroupId'];
                $shippingText = $carrierGroupDetail['carrierTitle'] .' - ' .$carrierGroupDetail['methodTitle'];
                if(array_key_exists('delivery_date', $carrierGroupDetail)) {
                    $shippingText .= ' Delivery: ' .$carrierGroupDetail['delivery_date'];
                }
                if(array_key_exists('dispatch_date', $carrierGroupDetail)) {
                    $shippingText .= ' Dispatch: ' .$carrierGroupDetail['dispatch_date'];
                }
                // if(array_key_exists('time_slot'))
                if(array_key_exists($carrierGroupId, $itemsGrouped)) {
                    foreach($itemsGrouped[$carrierGroupId] as $item) {
                        $item->setCarriergroupShipping($shippingText);
                    }
                }


            }

        }
    }

    /**
     * Format items based on carrier group
     *
     * @return array
     */
    public function getItemsGroupedByCarrierGroup($cartItems)
    {
        $groupedItems = array();
        foreach($cartItems as $item)
        {
            if(array_key_exists($item->getCarriergroupId(), $groupedItems)) {
                $groupedItems[$item->getCarriergroupId()][] = $item;
            }
            else {
                $groupedItems[$item->getCarriergroupId()]= array($item);
            }
        }

        return $groupedItems;
    }

    public function cleanDownRatesCollection($address, $carrierCode, $carriergroupId)
    {
        $currentRates = $address->getGroupedAllShippingRates();
        foreach($currentRates as $code => $rates)
        {
            if($code == $carrierCode){
                foreach($rates as $rate) {
                    if($carriergroupId == '' || $rate->getCarriergroupId() == $carriergroupId) {
                        $rate->isDeleted(true);
                    }
                }
            }

        }
    }

    protected function _getOptionId($attribute, $value)
    {
        //get the source
        $source = $attribute->getSource();
        //get the id
        $id = $source->getOptionId($value);
        return $id;
    }

    protected function getAttribute($attribute_code, $store = null) {
        $attribute = Mage::getResourceModel('catalog/product')
            ->getAttribute($attribute_code);

        if(is_null($store) || $store == '') {
            $store = Mage_Core_Model_App::ADMIN_STORE_ID;
        }
        $attribute->setStoreId($store);

        return $attribute;
    }

    protected function _dynamicCarrierConfig($carrierCode, $carrierType, $carrierTitle, $sortOrder = false)
    {
        $modelPath = 'carriers/'.$carrierCode.'/model';
        if(!Mage::getStoreConfig($modelPath)) {
            if($this->isModuleEnabled('Shipperhq_Pickup') &&
                Mage::helper('shipperhq_pickup')->isPickupEnabledCarrier($carrierType)) {
                $model =  'shipperhq_pickup/carrier_storepickup';
            } else {
                $model = 'shipperhq_shipper/carrier_shipper';
            }
            $this->saveConfig($modelPath, $model);
            $this->saveConfig('carriers/'.$carrierCode.'/active', 0);
        }
        $this->saveCarrierTitle($carrierCode, $carrierTitle);

        if($sortOrder) {
            $this->saveConfig('carriers/'.$carrierCode.'/sort_order', $sortOrder);
        }


    }

    /**
     * Returns url to use - live if present, otherwise dev
     * @return array
     */
    protected function _getGatewayUrl()
    {
        $live = $this->_cleanUpUrl(Mage::getStoreConfig('carriers/shipper/live_url'));

        $test = $this->_cleanUpUrl(Mage::getStoreConfig('carriers/shipper/url'));
        return Mage::getStoreConfig('carriers/shipper/sandbox_mode') ? $test : $live;
    }

    protected function _cleanUpUrl($urlStart)
    {
        $url = trim($urlStart);
        $lastChar = substr("abcdef", -1);
        if($lastChar != '/') {
            $url .= '/';
        }
        return $url;
    }
}
