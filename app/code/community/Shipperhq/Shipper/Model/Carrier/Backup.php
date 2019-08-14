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

class Shipperhq_Shipper_Model_Carrier_Backup extends Mage_Core_Model_Abstract
{

    protected $storeId;
    protected $availabilityConfigField = 'active';

    public function getBackupCarrierRates($request)
    {
        $this->storeId = $request->getStoreId();
        $carrierCode = $this->getBackupCarrierDetails();
        if(!$carrierCode) {
            return false;
        }

        $tempEnabledCarrier = $this->tempSetCarrierEnabled($carrierCode,true);
        $carrier = $this->getCarrierByCode($carrierCode, $request->getStoreId());

        if (!$carrier) {
            $this->tempSetCarrierEnabled($carrierCode,false);
            if (Mage::helper('shipperhq_shipper')->isDebug()) {
                Mage::helper('wsalogger/log')->postInfo('Shipperhq_Shipper', 'Unable to activate backup carrier',
                    $carrierCode);
            }
            return false;
        }

        $result = $carrier->collectRates($request);
        if (Mage::helper('shipperhq_shipper')->isDebug()) {
            Mage::helper('wsalogger/log')->postInfo('Shipperhq_Shipper', 'Backup carrier result: ',
                $result);
        }

        if ($tempEnabledCarrier) {
            $this->tempSetCarrierEnabled($carrierCode,false);
        }
        return $result;

    }

    /**
     * Enable or disable carrier
     * @return boolean
     */
    protected function tempSetCarrierEnabled ($carrierCode,$enabled)
    {
        $carrierPath='carriers/'.$carrierCode.'/'.$this->availabilityConfigField;
        return $this->setEnabledConfig($carrierPath, $enabled);
    }

    protected function setEnabledConfig($path, $enabled)
    {
        $store = Mage::app()->getStore();
        $tempEnabled = false;

        if (!Mage::getStoreConfigFlag($path) || !$enabled) { // if $enabled set to false was previously enabled!
            $store->setConfig($path,$enabled);
            $tempEnabled = true;
        }

        return $tempEnabled;
    }

    /**
     * Get backup carrier if configured
     * @return mixed
     */
    protected function getBackupCarrierDetails() {
        $carrierDetails = Mage::getStoreConfig('carriers/shipper/backup_carrier', $this->storeId);
        if (Mage::helper('shipperhq_shipper')->isDebug()) {
            Mage::helper('wsalogger/log')->postInfo('Shipperhq_Shipper', 'Unable to establish connection with ShipperHQ',
                'Attempting to use backup carrier: ' .$carrierDetails);
        }
        if(!$carrierDetails) {
            if (Mage::helper('shipperhq_shipper')->isDebug()) {
                Mage::helper('wsalogger/log')->postDebug('Shipperhq_Shipper', 'Backup carrier: ',
                    'No backup carrier is configured');
            }
            return false;
        }
        return $carrierDetails;
    }

    /**
     * Get carrier by its code
     *
     * @param string $carrierCode
     * @param null|int $storeId
     * @return bool|Mage_Core_Model_Abstract
     */
    public function getCarrierByCode($carrierCode, $storeId = null)
    {
        if (!Mage::getStoreConfigFlag('carriers/'.$carrierCode.'/'.$this->availabilityConfigField, $storeId)) {
            return false;
        }
        $className = Mage::getStoreConfig('carriers/'.$carrierCode.'/model', $storeId);
        if (!$className) {
            return false;
        }
        $obj = Mage::getModel($className);
        if ($storeId) {
            $obj->setStore($storeId);
        }
        return $obj;
    }
}