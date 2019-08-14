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
 * Shipper shipping model
 *
 * @category ShipperHQ
 * @package ShipperHQ_Shipper
 */

include_once 'ShipperHQ/WS/Client/WebServiceClient.php';
include_once 'ShipperHQ/WS/Response/ErrorMessages.php';

class Shipperhq_Shipper_Model_Carrier_Shipper
    extends Mage_Shipping_Model_Carrier_Abstract
    implements Mage_Shipping_Model_Carrier_Interface
{

    /**
     * Flag for check carriers for activity
     *
     * @var string
     */
    protected $_activeFlag = 'active';

    /**
     * Identifies this shipping carrier
     * @var string
     */
    protected $_code = 'shipper';

    /**
     * Raw rate request data
     *
     * @var Varien_Object|null
     */
    protected $_rawRequest = null;

    /*
     * Rate request object
     */
    protected $_shipperRequest = null;

    /**
     * Shipper Web Service instance
     *
     * @var Shipper_Shipper|null
     */
    protected $_shipperWSInstance = null;

    /**
     * Error Message Lookup Object
     *
     */
    protected $_errorMessageLookup = null;

    /**
     * Rate result data
     *
     * @var Mage_Shipping_Model_Rate_Result|null
     */
    protected $_result = null;

    /*
     * Cache of rate results
     */
    protected static $_quotesCache = array();

    /*
     * Cache setting
     */
    protected $_cacheEnabled;


    /**
     * Part of carrier xml config path
     *
     * @var string
     */
    protected $_availabilityConfigField = 'active';

    /**
     * Code for Wsalogger to pickup
     *
     * @var string
     */
    protected $_modName = 'Shipperhq_Shipper';


    /**
     *  Retrieve sort order of current carrier
     *
     * @return mixed
     */
    public function getSortOrder()
    {
        $path = 'carriers/'.$this->getId().'/sort_order';
        return Mage::getStoreConfig($path, $this->getStore());
    }

    /**
     * Collect and get rates
     *
     * @param Mage_Shipping_Model_Rate_Request $request
     * @return Mage_Shipping_Model_Rate_Result|bool|null
     */
    public function collectRates(Mage_Shipping_Model_Rate_Request $request)
    {
        if (!$this->getConfigFlag($this->_activeFlag)) {

            return false;
        }
        $initVal = microtime(true);

        $this->_cacheEnabled = Mage::app()->useCache('collections');
        $this->setRequest($request);

        $this->_result = $this->_getQuotes();
        $elapsed = microtime(true) - $initVal;
        if (Mage::helper('shipperhq_shipper')->isDebug()) {
            Mage::helper('wsalogger/log')->postDebug('Shipperhq_Shipper', 'Long lapse',$elapsed );
        }
        return $this->getResult();

    }

    /**
     * Prepare and set request to this instance
     *
     * @param Mage_Shipping_Model_Rate_Request $request
     * @return Shipperhq_Shipper_Model_Carrier_Shipper
     */
    public function setRequest(Mage_Shipping_Model_Rate_Request $request)
    {

        if (is_array($request->getAllItems())) {
            $item = current($request->getAllItems());
            if ($item instanceof Mage_Sales_Model_Quote_Item_Abstract) {
                $request->setQuote($item->getQuote());
            }
        }

        if(Mage::helper('shipperhq_shipper')->isModuleEnabled('Shipperhq_Pickup')) {
            Mage::helper('shipperhq_pickup')->addPickupToRequest($request);
        }

        if(Mage::helper('shipperhq_shipper')->isModuleEnabled('Shipperhq_Calendar')) {
            Mage::helper('shipperhq_calendar')->addSelectedDatesToRequest($request);
        }

        if(Mage::helper('shipperhq_shipper')->isModuleEnabled('Shipperhq_Freight')) {
            Mage::helper('shipperhq_freight')->addSelectedFreightOptionsToRequest($request);
        }

        $isCheckout = Mage::helper('shipperhq_shipper')->isCheckout();
        $cartType = (!is_null($isCheckout) && $isCheckout != 1) ? "CART" : "STD";
        if(Mage::helper('shipperhq_shipper')->isMultiAddressCheckout()) {
            $cartType = 'MAC';
            if(Mage::helper('shipperhq_shipper')->isModuleEnabled('Shipperhq_Pickup')&&
                Mage::helper('shipperhq_pickup')->pickupPreselected($request)) {
                    $cartType = 'MAC_PICKUP';
            }
        }
        $request->setCartType($cartType);
        $request->setStore($this->getStore());
        $this->_shipperRequest = Mage::getSingleton('Shipperhq_Shipper_Model_Carrier_Convert_ShipperMapper')->
            getShipperTranslation($request);
        $this->_rawRequest = $request;
        return $this;

    }

    /**
     * Get result of request
     *
     * @return mixed
     */
    public function getResult()
    {
        return $this->_result;
    }


    public function refreshCarriers()
    {
        $allowedMethods =  $this->getAllowedMethods();
        if(count($allowedMethods) == 0 ) {
            if (Mage::helper('shipperhq_shipper')->isDebug()) {
                Mage::helper('wsalogger/log')->postInfo('Shipperhq_Shipper', 'refresh carriers',
                    'Allowed methods web service did not contain any shipping methods for carriers');
            }
            $result['result'] = false;
            $result['error'] = 'ShipperHQ Error: No shipping methods for carrier setup in your ShipperHQ account';
            return $result;
        }
        return $allowedMethods;

    }

    /**
     * Get allowed shipping methods
     *
     * @return array
     */
    public function getAllowedMethods()
    {
        $ourCarrierCode = $this->getId();
        $result = array();
        $allowedMethods = array();
        $allowedMethodUrl = Mage::helper('shipperhq_shipper')->getAllowedMethodGatewayUrl();
        $timeout = Mage::helper('shipperhq_shipper')->getWebserviceTimeout();
        $shipperMapper = Mage::getSingleton('Shipperhq_Shipper_Model_Carrier_Convert_ShipperMapper');
        $resultSet = $this->_getShipperInstance()->sendAndReceive(
            $shipperMapper->getCredentialsTranslation(), $allowedMethodUrl, $timeout);

        $allowedMethodResponse = $resultSet['result'];

        if (Mage::helper('shipperhq_shipper')->isDebug()) {
            Mage::helper('wsalogger/log')->postDebug('Shipperhq_Shipper', 'Allowed methods response:',
                $resultSet['debug']);
        }
        if (!is_object($allowedMethodResponse)) {
            if (Mage::helper('shipperhq_shipper')->isDebug()) {
                Mage::helper('wsalogger/log')->postInfo('Shipperhq_Shipper',
                    'Allowed Methods: No or invalid response received from Shipper HQ',
                    $allowedMethodResponse);
            }

            $shipperHQ = "<a href=https://shipperhq.com/ratesmgr/websites>ShipperHQ</a> ";
            $result['result'] = false;
            $result['error'] = 'ShipperHQ is not contactable, verify the details from the website configuration in ' .$shipperHQ;
            return $result;
        }
        else if (count($allowedMethodResponse->errors)){
            if (Mage::helper('shipperhq_shipper')->isDebug()) {
                Mage::helper('wsalogger/log')->postInfo('Shipperhq_Shipper', 'Allowed methods: response contained following errors',
                    $allowedMethodResponse);

            }
            $error = 'ShipperHQ Error: ';
            foreach($allowedMethodResponse->errors as $anError) {
                if(isset($anError->internalErrorMessage)) {
                    $error .=  ' ' .$anError->internalErrorMessage;
                }
                elseif(isset($anError->externalErrorMessage) && $anError->externalErrorMessage != '') {
                    $error .=  ' ' .$anError->externalErrorMessage;
                }
            }
            $result['result'] = false;
            $result['error'] = $error;
            return $result;
        }
        else if ( !count($allowedMethodResponse->carrierMethods)) {
            if (Mage::helper('shipperhq_shipper')->isDebug()) {
                Mage::helper('wsalogger/log')->postInfo('Shipperhq_Shipper',
                    'Allowed methods web service did not return any carriers or shipping methods',
                    $allowedMethodResponse);
            }
            $result['result'] = false;
            $result['warning'] = 'ShipperHQ Warning: No carriers setup, log in to ShipperHQ Dashboard and create carriers';
            return $result;
        }

        $returnedMethods = $allowedMethodResponse->carrierMethods;

        $carrierConfig = array();

        foreach ($returnedMethods as $carrierMethod) {

            $rateMethods = $carrierMethod->methods;

            foreach ($rateMethods as $method) {
                if(!is_null($ourCarrierCode) && $carrierMethod->carrierCode != $ourCarrierCode) {
                    continue;
                }

                $allowedMethodCode = /*$carrierMethod->carrierCode . '_' .*/ $method->methodCode;
                $allowedMethodCode = preg_replace('/&|;| /', "_", $allowedMethodCode);

                if (!array_key_exists($allowedMethodCode, $allowedMethods)) {
                    $allowedMethods[$allowedMethodCode] = $carrierMethod->title . '(' . $method->name . ')';
                }
            }

            $carrierConfig[$carrierMethod->carrierCode]['title'] = $carrierMethod->title;
            if(isset($carrierMethod->sortOrder)) {
                $carrierConfig[$carrierMethod->carrierCode]['sortOrder'] = $carrierMethod->sortOrder;
            }
        }

        if (Mage::helper('shipperhq_shipper')->isDebug()) {
            Mage::helper('wsalogger/log')->postDebug('Shipperhq_Shipper', 'Allowed methods parsed result: ',  $allowedMethods);
        }
        // go set carrier titles
        $this->setCarrierConfig($carrierConfig);

        return $allowedMethods;
    }

    public function createMergedRate($ratesToAdd)
    {
        $result = Mage::getModel('shipping/rate_result');
        foreach ($ratesToAdd as $rateToAdd) {
            $method = Mage::getModel('shipping/rate_result_method');
            $method->setPrice((float)$rateToAdd['price']);
            $method->setCost((float)$rateToAdd['price']);
            $method->setCarrier($this->_code);
            $method->setCarrierTitle($rateToAdd['mergedTitle']);
            $method->setMethod($rateToAdd['title']);
            $method->setMethodTitle($rateToAdd['mergedDescription']);
            $method->setFreightQuoteId($rateToAdd['freight_quote_id']);
            $method->setMethodDescription($rateToAdd['mergedDescription']);
            $method->setCarrierType(Mage::helper('shipperhq_shipper')->__('multiple_shipments'));
         //   $method->setExpectedDelivery($rateToAdd['expected_delivery']);
         //   $method->setDispatchDate($rateToAdd['dispatch_date']);
            $result->append($method);
        }
        return $result;
    }

    public function extractShipperhqRates($carrierRate, $carrierGroupId, $carrierGroupDetail, $isSplit)
    {

        $carrierResultWithRates = array(
            'code'  => $carrierRate->carrierCode,
            'title' => $carrierRate->carrierTitle);

        if(Mage::helper('shipperhq_shipper')->isModuleEnabled('Shipperhq_Calendar')) {
            Mage::helper('shipperhq_calendar')->cleanUpCalendarsInSession($carrierRate->carrierCode, $carrierGroupId);
        }
        if(isset($carrierRate->error)) {
            $carrierResultWithRates['error'] = (array)$carrierRate->error;
            $carrierResultWithRates['carriergroup_detail']['carrierGroupId'] = $carrierGroupId;
        }

        if(isset($carrierRate->rates) && !array_key_exists('error', $carrierResultWithRates)) {
            $thisCarriersRates = $this->populateRates($carrierRate, $carrierGroupDetail, $carrierGroupId, true);
            $carrierResultWithRates['rates'] = $thisCarriersRates;
        }

        return $carrierResultWithRates;
    }

    protected function populateRates($carrierRate, &$carrierGroupDetail, $carrierGroupId)
    {
        $thisCarriersRates = array();
        $hideNotify = Mage::getStoreConfig('carriers/shipper/hide_notify');
        $locale = $this->getLocaleInGlobals();
        $dateOption = $carrierRate->dateOption;
        $deliveryMessage = isset($carrierRate->deliveryDateMessage) ?
            Mage::helper('shipperhq_shipper')->__($carrierRate->deliveryDateMessage) : '';
        if(is_null($deliveryMessage) || $deliveryMessage == '') {
            $deliveryMessage = $dateOption == Shipperhq_Shipper_Helper_Data::TIME_IN_TRANSIT ? 'business day(s)' :
                Mage::helper('shipperhq_shipper')->__('Delivers :');
        }
        $customDescription = isset($carrierRate->customDescription) ?
            Mage::helper('shipperhq_shipper')->__($carrierRate->customDescription) : false;
        $freightRate = isset($carrierRate->availableOptions) && !empty($carrierRate->availableOptions);
        $baseRate = 1;
        $baseCurrencyCode = Mage::app()->getStore()->getBaseCurrency()->getCode();
        $dateFormat = isset($carrierRate->deliveryDateFormat) ?
           $this->getCldrDateFormat($locale, $carrierRate->deliveryDateFormat) : Mage::helper('shipperhq_shipper')->getDateFormat();

        foreach($carrierRate->rates as $oneRate) {
            $methodDescription = false;
            $title = Mage::helper('shipperhq_shipper')->isTransactionIdEnabled() ?
                Mage::helper('shipperhq_shipper')->__($oneRate->name).' (' .$carrierGroupDetail['transaction'] .')'
                : Mage::helper('shipperhq_shipper')->__($oneRate->name);
            //currency conversion required
            if(isset($oneRate->currency)) {
                if($oneRate->currency != $baseCurrencyCode || $baseRate != 1) {
                    $baseRate = Mage::helper('shipperhq_shipper')->getBaseCurrencyRate($oneRate->currency);
                    if(!$baseRate) {
                        $carrierResultWithRates['error'] =  Mage::helper('directory')
                            ->__('Can\'t convert rate from "%s".',
                                $oneRate->currency);
                        $carrierResultWithRates['carriergroup_detail']['carrierGroupId'] = $carrierGroupId;
                        if (Mage::helper('shipperhq_shipper')->isDebug()) {
                            Mage::helper('wsalogger/log')->postWarning('Shipperhq_Shipper', 'Currency rate missing ',
                                'Currency code in shipping rate is ' .$oneRate->currency
                                .' but there is no currency conversion rate configured so we cannot display this shipping rate');
                        }
                        continue;
                    }

                }
            }
            Mage::helper('shipperhq_shipper')->populateRateLevelDetails((array)$oneRate, $carrierGroupDetail, $baseRate);
            if($oneRate->deliveryDate && is_numeric($oneRate->deliveryDate)) {
                $deliveryDate = Mage::app()->getLocale()->date($oneRate->deliveryDate/1000, null, null, true)->toString($dateFormat);
                if($dateOption == Shipperhq_Shipper_Helper_Data::DELIVERY_DATE_OPTION && isset($oneRate->deliveryDate)) {
                    $methodDescription = Mage::helper('shipperhq_shipper')->__(' %s %s',$deliveryMessage, $deliveryDate);
                    if($oneRate->latestDeliveryDate && is_numeric($oneRate->latestDeliveryDate)) {
                        $latestDeliveryDate = Mage::app()->getLocale()->date($oneRate->latestDeliveryDate/1000, null, null, true)->toString($dateFormat);
                        $methodDescription.= ' - ' .$latestDeliveryDate;
                    }
                }
                else if($dateOption == Shipperhq_Shipper_Helper_Data::TIME_IN_TRANSIT
                    && isset($oneRate->dispatchDate)) {
                    $deliveryMessage = Mage::helper('shipperhq_shipper')->__('business days');
                    $numDays = floor(abs($oneRate->deliveryDate/1000 - $oneRate->dispatchDate/1000)/60/60/24);
                    if($oneRate->latestDeliveryDate && is_numeric($oneRate->latestDeliveryDate)) {
                        $maxNumDays = floor(abs($oneRate->latestDeliveryDate/1000 - $oneRate->dispatchDate/1000)/60/60/24);
                        $methodDescription = Mage::helper('shipperhq_shipper')->__(' (%s - %s %s)',$numDays, $maxNumDays, $deliveryMessage);
                    }
                    else {
                        $methodDescription = Mage::helper('shipperhq_shipper')->__(' (%s %s)',$numDays, $deliveryMessage);
                    }
                }
            }
            if($oneRate->dispatchDate && is_numeric($oneRate->dispatchDate)) {
                $dispatchDate = Mage::app()->getLocale()->date($oneRate->dispatchDate/1000, null, null, true)->toString($dateFormat);
            }

            if($methodDescription) {
                $title.= ' ' .$methodDescription;
            }
            if($carrierRate->carrierType == 'shqshared') {
                $carrierType = $carrierRate->carrierType .'_' .$oneRate->carrierType;
            }
            else {
                $carrierType = $oneRate->carrierType;
            }
            $rateToAdd = array(
                'method_code'   => $oneRate->code,
                'method_title'  => $title,
                'cost'          => (float)$oneRate->shippingPrice * $baseRate,
                'price'         => (float)$oneRate->totalCharges * $baseRate,
                'carrier_type'  => $carrierType,
                'carrier_id'    => $carrierRate->carrierId,
                'freight_rate'  => $freightRate
            );

            if($oneRate->customDuties) {
                $rateToAdd['custom_duties'] = $oneRate->customDuties;
            }

            if($oneRate->deliveryDate && is_numeric($oneRate->deliveryDate)) {
                $carrierGroupDetail['delivery_date'] = $deliveryDate;
                $rateToAdd['delivery_date'] = $deliveryDate;
                $carrierGroupDetail['dateFormat'] = $dateFormat;
            }

            if($oneRate->dispatchDate && is_numeric($oneRate->dispatchDate)) {
                $carrierGroupDetail['dispatch_date'] = $dispatchDate;
                $rateToAdd['dispatch_date'] = $dispatchDate;
            }
            if($methodDescription) {
                $rateToAdd['method_description'] = $methodDescription;
            }
            $rateToAdd['carriergroup_detail'] = $carrierGroupDetail;

            if(!$hideNotify && isset($carrierRate->notices)) {
                foreach($carrierRate->notices as $notice) {
                    if(array_key_exists('carrier_notice', $rateToAdd)) {
                        $rateToAdd['carrier_notice'] .=  ' ' .(string)$notice ;
                    } else {
                        $rateToAdd['carrier_notice'] =  (string)$notice ;
                    }
                }
            }

            if($customDescription) {
                $rateToAdd['custom_description'] = $customDescription;
            }

            $thisCarriersRates[] = $rateToAdd;
        }
        return $thisCarriersRates;
    }

    protected function getLocaleInGlobals()
    {
        $globals = Mage::helper('shipperhq_shipper')->getQuoteStorage()->getShipperGlobal();
        if(isset($globals['preferredLocale'])) {
            return $globals['preferredLocale'];
        }
        return 'en-US';
    }

    /**
     * Get configuration data of carrier
     *
     * @param string $type
     * @param string $code
     * @return array|bool
     */
    public function getCode($type, $code = '')
    {
        $codes = array(
            'date_format'   =>array(
                'dd-mm-yyyy'    	    => 'd-m-Y',
                'mm/dd/yyyy'    	    => 'm/d/Y',
                'EEE dd-MM-yyyy'        => 'D d-m-Y'
            ),
            'short_date_format'   =>array(
                'dd-mm-yyyy'   	    => 'd-m-Y',
                'mm/dd/yyyy'    	    => 'm/d/Y',
                'EEE dd-MM-yyyy'        => 'D d-m-Y'
            ),
            'datepicker_format' => array(
                'dd-mm-yyyy'         => 'dd-mm-yy',
                'mm/dd/yyyy'         => 'mm/dd/yy',
                'EEE dd-MM-yyyy'        => 'DD d-MM-yy'

            ),
            'zend_date_format'     => array(
                'dd-mm-yyyy'         => 'dd-MM-y',
                'mm/dd/yyyy'         => 'MM/dd/y',
                'EEE dd-MM-yyyy'        => 'E d-M-y'
            ),
            'cldr_date_format'      => array(
                'en-US'            => array(
                    'yMd'           => 'MM/dd/Y',
                    'yMMMd'         => 'MMM d, Y',
                    'yMMMEd'        => 'EEE, MMM d, Y',
                    'yMEd'          => 'EEE, M/d/Y',
                    'MMMd'          => 'MMM d',
                    'MMMEd'         => 'EEE, MMM d',
                    'MEd'           => 'EEE, M/d',
                    'Md'            => 'M/d',
                    'yM'            => 'M/Y',
                    'yMMM'          => 'MMM Y',
                    'MMM'          => 'MMM',
                    'E'             => 'EEE',
                    'Ed'            => 'd EEE',
                ),
                'en-GB'            => array(
                    'yMd'           => 'dd/MM/Y',
                    'yMMMd'         => 'd MMM Y',
                    'yMMMEd'        => 'EEE, d MMM Y',
                    'yMEd'          => 'EEE, d/M/Y',
                    'MMMd'          => 'd MMM',
                    'MMMEd'         => 'EEE, d MMM',
                    'MEd'           => 'EEE, d/M',
                    'Md'            => 'd/M',
                    'yM'            => 'M/Y',
                    'yMMM'          => 'MMM Y',
                    'MMM'          =>  'MMM',
                    'E'             => 'EEE',
                    'Ed'            => 'EEE d',
                )
            )



        );
        $codes['error'] = $this->_getErrorMessageLookup()->getErrors();

        if (!isset($codes[$type])) {
            return false;
        } elseif ('' === $code) {
            return $codes[$type];
        }

        if (!isset($codes[$type][$code])) {
            return false;
        } else {
            return $codes[$type][$code];
        }
    }

    protected function getCldrDateFormat($locale, $code)
    {
        $dateFormatArray = $this->getCode('cldr_date_format', $locale);
        $dateFormat = is_array($dateFormatArray) && array_key_exists($code, $dateFormatArray) ? $dateFormatArray[$code]:
            Mage::helper('shipperhq_shipper')->getDateFormat();
        return $dateFormat;
    }

    /**
     * Do remote request for and handle errors
     *
     * @return Mage_Shipping_Model_Rate_Result
     */
    protected function _getQuotes()
    {
        $requestString = serialize($this->_shipperRequest);
        $resultSet = $this->_getCachedQuotes($requestString);
        $timeout = Mage::helper('shipperhq_shipper')->getWebserviceTimeout();
        if (!$resultSet) {
            $initVal =  microtime(true);
            $resultSet = $this->_getShipperInstance()->sendAndReceive($this->_shipperRequest,
                Mage::helper('shipperhq_shipper')->getRateGatewayUrl(), $timeout);
            $elapsed = microtime(true) - $initVal;
            if (Mage::helper('shipperhq_shipper')->isDebug()) {
                Mage::helper('wsalogger/log')->postDebug('Shipperhq_Shipper', 'Short lapse',$elapsed );
            }
            if(!$resultSet['result']){
                $backupRates = $this->_getBackupCarrierRates();
                if ($backupRates) {
                    return $backupRates;
                }
            }
            if(is_object($resultSet['result'])) {
                $this->_setCachedQuotes($requestString, $resultSet);
            }
        }

        /**
         *
         * This holds the raw json
         */
        /**
         * if (Mage::helper('shipperhq_shipper')->isDebug()) {
            Mage::helper('wsalogger/log')->postInfo('Shipperhq_Shipper', 'Request/Response',
                $resultSet);
        }
        **/
        return $this->_parseShipperResponse($resultSet['result']);

    }


    /**
     * @param $shipperResponse
     * @return Mage_Shipping_Model_Rate_Result
     */
    protected function _parseShipperResponse($shipperResponse)
    {
        $debugRequest = $this->_shipperRequest;


        $debugRequest->credentials = null;
        $debugData = array('request' => $debugRequest, 'response' => $shipperResponse);

        //first check and save globals for display purposes
        if(is_object($shipperResponse) && isset($shipperResponse->globalSettings)) {
            $globals = (array)$shipperResponse->globalSettings;
            Mage::helper('shipperhq_shipper')->getQuoteStorage()->setShipperGlobal($globals);
        }

        if(Mage::helper('shipperhq_shipper')->isSortOnPrice()) {
            $result = Mage::getModel('shipping/rate_result');
        }
        else {
            $result = Mage::getModel('shipperhq_shipper/rate_result');
        }
        // If no rates are found return error message
        if (!is_object($shipperResponse)) {
            if (Mage::helper('shipperhq_shipper')->isDebug()) {
                Mage::helper('wsalogger/log')->postInfo('Shipperhq_Shipper', 'Shipper HQ did not return a response',
                    $debugData);
            }
            $message = $this->getCode('error', 1550);

            return $this->returnGeneralError($message);
        }
        elseif(!empty($shipperResponse->errors)) {
            if (Mage::helper('shipperhq_shipper')->isDebug()) {
                Mage::helper('wsalogger/log')->postInfo('Shipperhq_Shipper', 'Shipper HQ returned an error',
                    $debugData);
            }
            if(isset($shipperResponse->errors)) {
                foreach($shipperResponse->errors as $error) {
                    $this->appendError($result, $error, $this->_code, $this->getConfigData('title'));
                }
            }
            return $result;
        }
        elseif(!isset($shipperResponse->carrierGroups)) {

        }

        if(isset($shipperResponse->carrierGroups)) {
            if(count($shipperResponse->carrierGroups) > 1 && !isset($shipperResponse->mergedRateResponse )) {
                    if (Mage::helper('shipperhq_shipper')->isDebug()) {
                        Mage::helper('wsalogger/log')->postInfo('Shipperhq_Shipper',
                            'Shipper HQ returned multi origin/group rates without any merged rate details',$debugData);
                    }
            }
            $carrierRates = $this->_processRatesResponse($shipperResponse);
        }
        else {
            $carrierRates = array();
        }
        if(count($carrierRates) == 0) {
            if (Mage::helper('shipperhq_shipper')->isDebug()) {
                Mage::helper('wsalogger/log')->postInfo('Shipperhq_Shipper', 'Shipper HQ did not return any carrier rates',$debugData);
            }
            return $result;
        }

        foreach ($carrierRates as $carrierRate) {
            if (isset($carrierRate['error'])) {
                $carriergroupId = null;
                $carrierGroupDetail = null;
                if(array_key_exists('carriergroup_detail', $carrierRate)
                    && !is_null($carrierRate['carriergroup_detail'])) {
                    if(array_key_exists('carrierGroupId', $carrierRate['carriergroup_detail'])) {
                        $carriergroupId = $carrierRate['carriergroup_detail']['carrierGroupId'];
                    }
                    $carrierGroupDetail = $carrierRate['carriergroup_detail'];
                }
                $this->appendError($result, $carrierRate['error'], $carrierRate['code'], $carrierRate['title'],
                    $carriergroupId,$carrierGroupDetail);
                continue;
            }

            if (!array_key_exists('rates', $carrierRate)) {
                if (Mage::helper('shipperhq_shipper')->isDebug()) {
                    Mage::helper('wsalogger/log')->postInfo('Shipperhq_Shipper',
                        'Shipper HQ did not return any rates for '. $carrierRate['code'] .' ' .$carrierRate['title']
                        ,$debugData);
                }
            } else {

                foreach ($carrierRate['rates'] as $rateDetails) {
                    $rate = Mage::getModel('shipping/rate_result_method');
                    $rate->setCarrier($carrierRate['code']);
                    $rate->setCarrierTitle($carrierRate['title']);
                    $methodCombineCode = preg_replace('/&|;| /', "_", $rateDetails['method_code']);
                    $rate->setMethod($methodCombineCode);
                    $rate->setMethodTitle(Mage::helper('shipperhq_shipper')->__($rateDetails['method_title']));
                    if(array_key_exists('method_description', $rateDetails)) {
                        $rate->setMethodDescription(Mage::helper('shipperhq_shipper')->__($rateDetails['method_description']));
                    }
                    $rate->setCost($rateDetails['cost']);
                    $rate->setPrice($rateDetails['price']);
                    if(array_key_exists('custom_duties', $rateDetails)) {
                        $rate->setCustomDuties($rateDetails['custom_duties']);
                    }

                    if(array_key_exists('carrier_type', $rateDetails)) {
                        $rate->setCarrierType($rateDetails['carrier_type']);
                    }

                    if(array_key_exists('carrier_id', $rateDetails)) {
                        $rate->setCarrierId($rateDetails['carrier_id']);
                    }

                    if(array_key_exists('dispatch_date', $rateDetails)) {
                        $rate->setDispatchDate($rateDetails['dispatch_date']);
                    }

                    if(array_key_exists('delivery_date', $rateDetails)) {
                        $rate->setDeliveryDate($rateDetails['delivery_date']);
                    }

                    if(array_key_exists('carriergroup_detail', $rateDetails)
                        && !is_null($rateDetails['carriergroup_detail'])) {
                        $rate->setCarriergroupShippingDetails(
                            Mage::helper('shipperhq_shipper')->encodeShippingDetails($rateDetails['carriergroup_detail']));
                        if(array_key_exists('carrierGroupId', $rateDetails['carriergroup_detail'])) {
                            $rate->setCarriergroupId($rateDetails['carriergroup_detail']['carrierGroupId']);
                        }
                        if(array_key_exists('checkoutDescription', $rateDetails['carriergroup_detail'])) {
                            $rate->setCarriergroup($rateDetails['carriergroup_detail']['checkoutDescription']);
                        }
                    }

                    if(array_key_exists('carrier_notice', $rateDetails)) {
                        $rate->setCarrierNotice($rateDetails['carrier_notice']);
                    }

                    if(array_key_exists('freight_rate', $rateDetails)) {
                        $rate->setFreightRate($rateDetails['freight_rate']);
                    }

                    if(array_key_exists('custom_description', $rateDetails)) {
                        $rate->setCustomDescription($rateDetails['custom_description']);
                    }

                    $result->append($rate);
                }
            }
        }

        if (Mage::helper('shipperhq_shipper')->isDebug()) {
            Mage::helper('wsalogger/log')->postDebug('Shipperhq_Shipper', 'Rate request and result', $debugData);
        }
        return $result;

    }

    /*
     *
     * Build array of rates based on split or merged rates display
     */
    protected function _processRatesResponse($shipperResponse)
    {
        if(Mage::helper('shipperhq_shipper')->isModuleEnabled('Shipperhq_Freight')) {
            Mage::helper('shipperhq_freight')->parseFreightDetails($shipperResponse, $this->getQuote()->getShippingAddress()->getIsCheckout());
        }

        //Use multi-origin/group processing
        if(Mage::helper('shipperhq_shipper')->isModuleEnabled('Shipperhq_Splitrates')
            && isset($shipperResponse->mergedRateResponse) && count($shipperResponse->carrierGroups) > 1) {
            return Mage::helper('shipperhq_splitrates')->parseCarrierGroupRates($shipperResponse, $this->_rawRequest);
        }

        Mage::helper('shipperhq_shipper')->setStandardShipperResponseType();

        $carrierGroups = $shipperResponse->carrierGroups;
        $ratesArray = array();
        $globals = (array)$shipperResponse->globalSettings;
        $responseSummary = (array)$shipperResponse->responseSummary;
        foreach($carrierGroups as $carrierGroup)
        {
            $carrierGroupDetail = (array)$carrierGroup->carrierGroupDetail;
            $carriergroupId = array_key_exists('carrierGroupId', $carrierGroupDetail) ? $carrierGroupDetail['carrierGroupId'] : 0;

            Mage::unregister('shipperhq_transaction');
            Mage::register('shipperhq_transaction', $responseSummary['transactionId']);
            $carrierGroupDetail['transaction'] = $responseSummary['transactionId'];

            $this->_setCarriergroupOnItems($carrierGroupDetail, $carrierGroup->products);
            $globals = array_merge($globals,$carrierGroupDetail);
            //Pass off each carrier group to helper to decide best fit to process it.
            //Push result back into our array
            foreach($carrierGroup->carrierRates as $carrierRate) {
                $carrierResultWithRates = Mage::helper('shipperhq_shipper')->chooseCarrierAndProcess($carrierRate, $carriergroupId, $carrierGroupDetail, false);
                $ratesArray[] = $carrierResultWithRates;
            }
        }
        Mage::helper('shipperhq_shipper')->getQuoteStorage()->setShipperGlobal($globals);

        $carriergroupDescriber = $shipperResponse->globalSettings->carrierGroupDescription;
        if($carriergroupDescriber != '') {
            Mage::helper('shipperhq_shipper')->saveConfig(Shipperhq_Shipper_Helper_Data::SHIPPERHQ_SHIPPER_CARRIERGROUP_DESC_PATH,
                $carriergroupDescriber);
        }

        Mage::helper('shipperhq_shipper')->refreshConfig();

        return $ratesArray;
    }

    protected function _setCarriergroupOnItems($carriergroupDetails, $productInRateResponse)
    {
        $quoteItems = $this->getQuote()->getAllItems();
        foreach($productInRateResponse as $item) {
            $item = (array)$item;
            $sku = $item['sku'];
            $itemId = array_key_exists('id', $item) ? $item['id'] : false;
            foreach($quoteItems as $item)
            {
                if($item->getSku() == $sku && (!$itemId || $item->getId() == $itemId)) {
                    $item->setCarriergroupId($carriergroupDetails['carrierGroupId']);
                    $item->setCarriergroup($carriergroupDetails['name']);
                    if($parentItem = $item->getParentItem()) {
                        $parentItem->setCarriergroupId($carriergroupDetails['carrierGroupId']);
                        $parentItem->setCarriergroup($carriergroupDetails['name']);

                    }
                }
            }

            foreach($this->_rawRequest->getAllItems() as $quoteItem)
            {
                if($quoteItem->getSku() == $sku && (!$itemId || $quoteItem->getQuoteItemId() == $itemId)) {
                    $quoteItem->setCarriergroupId($carriergroupDetails['carrierGroupId']);
                    $quoteItem->setCarriergroup($carriergroupDetails['name']);
                    if($parentItem = $quoteItem->getParentItem()) {
                        $parentItem->setCarriergroupId($carriergroupDetails['carrierGroupId']);
                        $parentItem->setCarriergroup($carriergroupDetails['name']);
                    }
                }
            }
        }
    }

    /**
     * Retrieve checkout quote model object
     *
     * @return Mage_Sales_Model_Quote
     */
    public function getQuote()
    {
        return Mage::helper('shipperhq_shipper')->getQuote();
    }

    /**
     *
     * Build up an error message when no carrier rates returned
     * @return Mage_Shipping_Model_Rate_Result
     */
    protected function returnGeneralError($message = null)
    {
        $result = Mage::getModel('shipping/rate_result');
        $error = Mage::getModel('shipping/rate_result_error');
        $error->setCarrier($this->_code);
        $error->setCarrierTitle($this->getConfigData('title'));
        $error->setCarriergroupId('');
        if($message && Mage::helper('shipperhq_shipper')->isDebug()) {
            $error->setErrorMessage($message);
        }
        else {
            $error->setErrorMessage($this->getConfigData('specificerrmsg'));
        }
        $result->append($error);
        return $result;
    }

    /**
     *
     * Generate error message from ShipperHQ response.
     * Display of error messages per carrier is managed in SHQ configuration
     *
     * @param $result
     * @param $errorDetails
     * @return Mage_Shipping_Model_Rate_Result
     */
    protected function appendError($result, $errorDetails, $carrierCode, $carrierTitle, $carrierGroupId = null, $carrierGroupDetail = null)
    {
        if(is_object($errorDetails)) {
            $errorDetails = get_object_vars($errorDetails);
        }
        if ((array_key_exists('internalErrorMessage', $errorDetails) && $errorDetails['internalErrorMessage'] != '')
        || (array_key_exists('externalErrorMessage', $errorDetails) && $errorDetails['externalErrorMessage'] != ''))
        {
            $errorMessage = false;
            if (Mage::helper('wsalogger')->isDebugError() && array_key_exists('internalErrorMessage', $errorDetails)
                && $errorDetails['internalErrorMessage'] != '') {
                $errorMessage = $errorDetails['internalErrorMessage'];
            }
            else if(array_key_exists('externalErrorMessage', $errorDetails)
                && $errorDetails['externalErrorMessage'] != '') {
                $errorMessage = $errorDetails['externalErrorMessage'];
            }
            if($errorMessage) {
                $error = Mage::getModel('shipping/rate_result_error');
                $error->setCarrier($carrierCode);
                $error->setCarrierTitle($carrierTitle);
                $error->setErrorMessage(Mage::helper('shipperhq_shipper')->__($errorMessage));
                if(!is_null($carrierGroupId)) {
                   $error->setCarriergroupId($carrierGroupId);
                }
                if(is_array($carrierGroupDetail) && array_key_exists('checkoutDescription', $carrierGroupDetail)) {
                    $error->setCarriergroup($carrierGroupDetail['checkoutDescription']);
                }

                $result->append($error);
                if(Mage::helper('shipperhq_shipper')->isDebug()) {
                    Mage::helper('wsalogger/log')->postInfo('Shipperhq_Shipper', 'Shipper HQ returned error', $errorDetails);
                }
            }

        }
        return $result;
    }

    /*
     * This dynamically updates the carrier titles from ShipperHQ
     * Is required as don't want to set these on every quote request
     */
    protected function setCarrierConfig($carrierConfig)
    {
        foreach ($carrierConfig as $carrierCode=>$config) {
            Mage::helper('shipperhq_shipper')->saveCarrierTitle($carrierCode, $config['title']);
            if(array_key_exists('sortOrder', $config)) {
                Mage::helper('shipperhq_shipper')->saveConfig('carriers/'.$carrierCode.'/sort_order', $config['sortOrder']);
            }
        }

    }

    protected function _getBackupCarrierRates()
    {
        $backupHandler = Mage::getModel('shipperhq_shipper/carrier_backup');
        return $backupHandler->getBackupCarrierRates($this->_rawRequest);
    }

    /**
     * Initialise shipper library class
     *
     * @return null|Shipper_Shipper
     */
    protected function _getShipperInstance()
    {
        if (empty($this->_shipperWSInstance)) {
            $this->_shipperWSInstance = new \ShipperHQ\WS\Client\WebServiceClient();
        }
        return $this->_shipperWSInstance;
    }

    /**
     * Initialise shipper library class
     *
     * @return null|Shipper_Shipper
     */
    protected function _getErrorMessageLookup()
    {
        if (empty($this->_errorMessageLookup)) {
            $this->_errorMessageLookup = new \ShipperHQ\WS\Response\ErrorMessages();
        }
        return $this->_errorMessageLookup;
    }

    /**
     * Returns cache key for some request to carrier quotes service
     *
     * @param string|array $requestParams
     * @return string
     */
    protected function _getQuotesCacheKey($requestParams)
    {

        if (is_array($requestParams)) {
            $requestParams = implode(',', array_merge(
                    array($this->getCarrierCode()),
                    array_keys($requestParams),
                    $requestParams)
            );

        }
        else {
            $unSerialized = unserialize($requestParams);
            if(isset($unSerialized->cart)) {
                $cart = $unSerialized->cart;
                $cartItems = $cart['items'];
                foreach($cartItems as $key => $cartItem) {
                    $cartItem['id'] = '';
                    $cartItems[$key] = $cartItem;
                }
                $cart['items'] = $cartItems;
                $unSerialized->cart = $cart;
                $requestParams = serialize($unSerialized);
            }
        }
        return crc32($requestParams);
    }


    /**
     * Checks whether some request to rates have already been done, so we have cache for it
     * Used to reduce number of same requests done to carrier service during one session
     *
     * Returns cached response or null
     *
     * @param string|array $requestParams
     * @return null|string
     */
    protected function _getCachedQuotes($requestParams)
    {
        $result = false;
        $key = $this->_getQuotesCacheKey($requestParams);
        if($this->_cacheEnabled) {
            $cache = Mage::app()->getCache();
            $result = $cache->load($key);
            if($result) {
                $result = unserialize($result);
            }
        }
        else {
            $result = isset(self::$_quotesCache[$key]) ? self::$_quotesCache[$key] : false;
        }
        return $result;

    }

    /**
     * Sets received carrier quotes to cache
     *
     * @param string|array $requestParams
     * @param string $response
     * @return Mage_Usa_Model_Shipping_Carrier_Abstract
     */
    protected function _setCachedQuotes($requestParams, $response)
    {
        $key = $this->_getQuotesCacheKey($requestParams);
        if($this->_cacheEnabled) {
            $cache = Mage::app()->getCache();
            $cache->save(serialize($response), $key, array("shipperhq_shipper"), 5*60);
        }
        else {
            self::$_quotesCache[$key] = $response;
        }
        return $this;
    }

}
