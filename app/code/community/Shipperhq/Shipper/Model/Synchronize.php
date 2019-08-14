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

class Shipperhq_Shipper_Model_Synchronize extends Mage_Core_Model_Abstract
{

    const ADD_ATTRIBUTE_OPTION = 'Add';
    const REMOVE_ATTRIBUTE_OPTION = 'Manual delete required';
    const AUTO_REMOVE_ATTRIBUTE_OPTION = 'Delete';
    protected $_shipperWSInstance;
    protected $_shipperMapperInstance;
    protected $_prodAttributes;

    /*
     *Review latest attribute data and save changes required to database
     */
    public function updateSynchronizeData()
    {
        $latestAttributes = $this->_getLatestAttributeData();
        $result = array();
        if( $latestAttributes && array_key_exists('error', $latestAttributes)) {
            $result['error'] = $latestAttributes['error'];
        }
        elseif($latestAttributes && !empty($latestAttributes)) {
            $updateData = $this->_compareAttributeData($latestAttributes);
            $result['result'] = $this->_saveSynchData($updateData);
        }
        else{
            $result['error']
                = Mage::getModel('shipperhq_shipper/carrier_shipper')->getCode('error', 1700);
        }

        return $result;

    }

    /*
     *Get latest attribute data and perform changes required
     */
    public function synchronizeData()
    {
        $latestAttributes = $this->_getLatestAttributeData();
        $result = array();
        if( $latestAttributes && array_key_exists('error', $latestAttributes)) {
            $result['error'] = $latestAttributes['error'];
        }
        elseif($latestAttributes && !empty($latestAttributes)) {
            $updateData = $this->_compareAttributeData($latestAttributes);
            $updateResult = $this->_updateAll($updateData);
            $result['result'] = $updateResult;
        }
        else{
            $result['error']
                 = Mage::getModel('shipperhq_shipper/carrier_shipper')->getCode('error', 1700);
        }

        return $result;
    }

    public function checkSynchStatus()
    {
        if(Mage::getStoreConfig('carriers/shipper/active')) {

            $synchCheckUrl = Mage::helper('shipperhq_shipper')->getCheckSynchronizedUrl();
            $timeout = Mage::helper('shipperhq_shipper')->getWebserviceTimeout();
            $shipperMapper = $this->_getShipperMapper();
            $request = $shipperMapper->getCredentialsTranslation();
            $result = $this->_getShipperInstance()->sendAndReceive($request, $synchCheckUrl, $timeout);
            $synchResult = $result['result'];
            $debugData = array(
                'result' => json_decode($result['debug']['response']),
                'url' => $result['debug']['url']
            );
            if (Mage::helper('shipperhq_shipper')->isDebug()) {
                Mage::helper('wsalogger/log')->postDebug('Shipperhq_Shipper', 'Check synchronized status: ',
                    $debugData);
            }
            if(!empty($synchResult->errors)) {
                if (Mage::helper('shipperhq_shipper')->isDebug()) {
                    Mage::helper('wsalogger/log')->postWarning('Shipperhq_Shipper', 'Check synchronized status failed. Error: ',
                        $synchResult->errors);
                }
                return false;
            }

            if(!isset($synchResult->responseSummary) || $synchResult->responseSummary->status != 1) {
                if (Mage::helper('shipperhq_shipper')->isDebug()) {
                    Mage::helper('wsalogger/log')->postWarning('Shipperhq_Shipper', 'Check Synchronized Status failed with no error. ',
                       $synchResult);
                }
                return false;
            }

            $result = $synchResult->synchronized == 1 ? '1' : "Required";
            return $result;
        }
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

    protected function _getShipperMapper()
    {
        if(empty($this->_shipperMapperInstance)) {
            $this->_shipperMapperInstance = Mage::getSingleton('Shipperhq_Shipper_Model_Carrier_Convert_ShipperMapper');
        }
        return $this->_shipperMapperInstance;
    }


    protected function _getLatestAttributeData()
    {
        $result = array();
        $synchronizeUrl = Mage::helper('shipperhq_shipper')->getAttributeGatewayUrl();
        $timeout = Mage::helper('shipperhq_shipper')->getWebserviceTimeout();
        $shipperMapper = $this->_getShipperMapper();
        $request = $shipperMapper->getCredentialsTranslation();
        if (Mage::helper('shipperhq_shipper')->isDebug()) {
            Mage::helper('wsalogger/log')->postDebug('Shipperhq_Shipper', 'Latest attributes request',
                $request->siteDetails);
        }
        $resultSet = $this->_getShipperInstance()->sendAndReceive($request,$synchronizeUrl, $timeout);
        $allAttributesResponse = $resultSet['result'];
        if (Mage::helper('shipperhq_shipper')->isDebug()) {
            Mage::helper('wsalogger/log')->postDebug('Shipperhq_Shipper', 'Latest attributes response',
                (array)$allAttributesResponse);
        }
        if(isset($allAttributesResponse->errors) && count($allAttributesResponse->errors) > 0) {
            foreach($allAttributesResponse->errors as $errorDetails) {
                $errorDetails = (array)$errorDetails;
                if(array_key_exists('internalErrorMessage', $errorDetails)
                    && $errorDetails['internalErrorMessage'] != '') {
                    $result['error'] = $errorDetails['internalErrorMessage'];
                }
                else if(array_key_exists('externalErrorMessage', $errorDetails)
                    && $errorDetails['externalErrorMessage'] != '') {
                    $result['error'] = $errorDetails['externalErrorMessage'];
                }
            }
            if(Mage::helper('shipperhq_shipper')->isDebug()) {
                Mage::helper('wsalogger/log')->postInfo('Shipperhq_Shipper', 'Shipper HQ returned error', $allAttributesResponse->errors);
            }
        }

        elseif (!$allAttributesResponse || !isset($allAttributesResponse->responseSummary) ||
              (string)$allAttributesResponse->responseSummary->status != 1 ||
              !$allAttributesResponse->attributeTypes ) {
                if (Mage::helper('shipperhq_shipper')->isDebug()) {
                    Mage::helper('wsalogger/log')->postInfo('Shipperhq_Shipper', 'Unable to parse latest attributes response',
                        ': ' .$allAttributesResponse);
                }
        }
        else {
            $result = $allAttributesResponse->attributeTypes;
        }
        return $result;
    }

    protected function _compareAttributeData($latestAttributes)
    {
        $result = array();
        $productAttributes = Mage::helper('shipperhq_shipper')->getProductAttributes();

        foreach($latestAttributes as $attribute)
        {
            switch ($attribute->type) {
                case 'product':
                    $productAttributeApi = Mage::getSingleton('catalog/product_attribute_api_v2');
                    try {
                        $existingAttributeOptions = array();
                        if(!in_array($attribute->code, $productAttributes)) {
                            if (Mage::helper('shipperhq_shipper')->isDebug()) {
                                Mage::helper('wsalogger/log')->postCritical('Shipperhq_Shipper',
                                    'Attribute '.$attribute->code .' does not exist.',
                                    'Verify you have latest version of ShipperHQ installed');
                            }
                            continue;
                        }
                        $existingAttributeInfo = $productAttributeApi->info($attribute->code);
                        if(is_array($existingAttributeInfo) && array_key_exists('options', $existingAttributeInfo)) {
                            $existingAttributeOptions = $existingAttributeInfo['options'];
                        }
                    }
                    catch (Exception $e) {
                        $e->getMessage();
                        if (Mage::helper('shipperhq_shipper')->isDebug()) {
                            Mage::helper('wsalogger/log')->postCritical('Shipperhq_Shipper',
                                'Stopped Processing Synchronisation',
                                'Unable to find attribute '.$attribute->code.' | Error: ' .$e->getMessage());
                        }
                        $result = false;
                        break;
                    }
                    $trackValues = $existingAttributeOptions;
                    foreach($attribute->attributes as $latestValue) {
                        $found = false;
                        foreach ($existingAttributeOptions as $key => $option) {
                            if ($option['label'] == $latestValue->name) {
                                $found = true;
                                unset($trackValues[$key]);
                                continue;
                            }
                        }

                        if(!$found) {
                            $result[] = array('attribute_type' => $attribute->type,
                                        'attribute_code' => $attribute->code,
                                        'value'         => $latestValue->name,
                                //      'label'         => $latestValue['description'];
                                        'status'        => self::ADD_ATTRIBUTE_OPTION,
                                        'date_added'    => date('Y-m-d H:i:s')

                            );
                        }

                    }
                    if(count($trackValues) > 0) {
                        //TODO add store selector in here
                        $storeId = '';
                            //AUTO_REMOVE_ATTRIBUTE_OPTION
                        foreach($trackValues as $key => $option) {
                            $numberAssigned = Mage::helper('shipperhq_shipper')->getProductsWithAttributeValue(
                                $attribute->code,  $option['value'], $storeId, true, true);
                            $deleteFlag = self::AUTO_REMOVE_ATTRIBUTE_OPTION;
                            if($numberAssigned > 0) {
                                $deleteFlag = self::REMOVE_ATTRIBUTE_OPTION;
                            }

                            $result[] = array('attribute_type' => $attribute->type,
                                'attribute_code' => $attribute->code,
                                'value'         => $option['label'],
                                'option_id'     => $option['value'],
                                'status'        => $deleteFlag,
                                'date_added'    => date('Y-m-d H:i:s')
                            );
                        }
                    }
                    break;
                case 'global':
                    if($attribute->code == 'global_settings') {
                        foreach($attribute->attributes as $globalSetting) {
                            $value = $globalSetting->value == 'true'? 1: 0;
                            if(Mage::getStoreConfig('carriers/shipper/'.$globalSetting->code) != $value) {
                                $result[] = array('attribute_type' => 'global_setting',
                                    'attribute_code' => $globalSetting->code,
                                    'value'         => $value,
                                    'option_id'     => '',
                                    'status'        => self::ADD_ATTRIBUTE_OPTION,
                                    'date_added'    => date('Y-m-d H:i:s')
                                );
                            }
                        }
                    }
                case 'customer':
                    //compare customer groups
                    break;
                default :
                    break;
            }

        }
        if (Mage::helper('shipperhq_shipper')->isDebug()) {
            Mage::helper('wsalogger/log')->postDebug('Shipperhq_Shipper', 'Compare attributes result: ', $result);
        }

        return $result;
    }

    /**
     * Checks to see if we have been sent extra info we want on the attribute
     *
     * @param $name
     * @param $value
     */
    protected function _extraInfoContains($extraInfoList, $keyToMatch, $valueToMatch) {
        foreach($extraInfoList as $extraInfo) {
            if ($extraInfo->key==$keyToMatch && $extraInfo->value==$valueToMatch) {
                return true;
            }
        }
        return false;
    }


    protected function _saveSynchData($data)
    {
        $result = 0;
        //remove all existing rows
        try{
            foreach($collection = Mage::getModel('shipperhq_shipper/attributeupdate')->getCollection() as $oldUpdate)
            {
                $oldUpdate->delete();
            }
        }
        catch (Exception $e) {
            $result = false;
            if (Mage::helper('shipperhq_shipper')->isDebug()) {
                Mage::helper('wsalogger/log')->postWarning('Shipperhq_Shipper',
                    'Unable to remove existing attribute update data', $e->getMessage());
            }
        }
        if(empty($data)) {
            if (Mage::helper('shipperhq_shipper')->isDebug()) {
                Mage::helper('wsalogger/log')->postDebug('Shipperhq_Shipper', 'Saving synch data',
                    'No attribute changes required, 0 rows saved');
            }
            return $result;
        }

        foreach($data as $update)
        {
            $newUpdate = Mage::getModel('shipperhq_shipper/attributeupdate');
            $newUpdate->setData($update);
            $newUpdate->save();
            $newUpdate->clearInstance();
            $result++;
        }
        return $result;

    }

    /*
     * Add new option values to attributes
     *
     */
    protected function _updateAll($updateData)
    {
        $result = 0;
        $productAttributeApi = Mage::getSingleton('catalog/product_attribute_api_v2');

        foreach($updateData as $attributeUpdate)
        {
            if($attributeUpdate['attribute_type'] == 'product') {
                if($attributeUpdate['status'] == self::ADD_ATTRIBUTE_OPTION) {
                    $optionToAdd = array(
                        'label' => array(
                            array (
                                'store_id' => 0,
                                'value'   => $attributeUpdate['value']
                            )

                        ),
                        'order'   => 0,
                        'is_default' => 0
                    );
                    try {
                        $productAttributeApi->addOption($attributeUpdate['attribute_code'], $optionToAdd);
                        $result++;
                    }
                    catch (Exception $e){
                        if (Mage::helper('shipperhq_shipper')->isDebug()) {
                            Mage::helper('wsalogger/log')->postInfo('Shipperhq_Shipper', 'Unable to add attribute option',
                                'Error: ' .$e->getMessage());
                        }
                        $result = false;
                    }
                }
                else if($attributeUpdate['status'] == self::AUTO_REMOVE_ATTRIBUTE_OPTION) {
                    try {
                            $productAttributeApi->removeOption($attributeUpdate['attribute_code'], $attributeUpdate['option_id']);
                            $result++;
                        }
                        catch (Exception $e) {
                            if (Mage::helper('shipperhq_shipper')->isDebug()) {
                                Mage::helper('wsalogger/log')->postInfo('Shipperhq_Shipper', 'Unable to remove attribute option',
                                    'Error: ' .$e->getMessage());
                            }
                            $result = false;
                        }
                }
            }
            elseif($attributeUpdate['attribute_type'] == 'global_setting') {
                Mage::helper('shipperhq_shipper')->saveConfig('carriers/shipper/'.$attributeUpdate['attribute_code'],
                    $attributeUpdate['value']);
            }
        }


        if ($result >= 0) {
            $synchSetUrl = Mage::helper('shipperhq_shipper')->getSetSynchronizedUrl();
            $timeout = Mage::helper('shipperhq_shipper')->getWebserviceTimeout();
            $shipperMapper = $this->_getShipperMapper();
            $request = $shipperMapper->getCredentialsTranslation();
            $setSynchResult = $this->_getShipperInstance()->sendAndReceive($request, $synchSetUrl, $timeout);
            if (Mage::helper('shipperhq_shipper')->isDebug()) {
                Mage::helper('wsalogger/log')->postInfo('Shipperhq_Shipper', 'Setting synchronized status: ',
                    $setSynchResult['result']);
            }
        }
      return $result;
    }
}