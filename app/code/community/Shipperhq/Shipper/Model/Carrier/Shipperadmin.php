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

class Shipperhq_Shipper_Model_Carrier_Shipperadmin
    extends Mage_Shipping_Model_Carrier_Abstract
{

    /**
     * Identifies this shipping carrier
     * @var string
     */
    protected $_code = 'shipperadmin';

    /**
     * Code for Wsalogger to pickup
     *
     * @var string
     */
    protected $_modName = 'Shipperhq_Shipper';

    /**
     * Collect and get rates
     *
     * @param Mage_Shipping_Model_Rate_Request $request
     * @return Mage_Shipping_Model_Rate_Result|bool|null
     */
    public function collectRates(Mage_Shipping_Model_Rate_Request $request)
    {
        if ($shipData = Mage::registry('shqadminship_data')) {
            $result = Mage::getModel('shipping/rate_result');
            foreach($shipData->getData() as $carrierGroupId => $rateInfo) {
                $carrierGroupShippingDetail = array(
                    "checkoutDescription"=>$rateInfo['carriergroup'],
                    "name"=>$rateInfo['carriergroup'],
                    "carrierGroupId"=>$carrierGroupId,
                    "carrierType"=>"custom_admin",
                    "carrierTitle"=> $this->getConfigData('title'),
                    "carrier_code"=> $this->_code,
                    "carrierName"=> Mage::helper('shipperhq_shipper')->__('Custom Shipping'),
                     "methodTitle"=> $rateInfo['customCarrier'],
                    "price" => $rateInfo['customPrice'],
                    "cost" => $rateInfo['customPrice'],
                    "code"=> 'adminshipping',
                );
                $method = Mage::getModel('shipping/rate_result_method');
                $method->setCarrier($this->_code);
                $method->setPrice($rateInfo['customPrice']);
                $method->setCarrierTitle($this->getConfigData('title'));
                $method->setMethod('adminshipping');
                $method->setMethodTitle($rateInfo['customCarrier']);
                $method->setCarriergroupId($carrierGroupId);
                $method->setCarriergroupShippingDetails(Mage::helper('shipperhq_shipper')->encodeShippingDetails($carrierGroupShippingDetail));
                $result->append($method);
            }
            if (Mage::helper('shipperhq_shipper')->isDebug()) {
                Mage::helper('wsalogger/log')->postDebug('Shipperhq_Shipper', 'ShipperHQ Admin - created custom shipping rate ',
                    $shipData);
            }
            return $result;
        } else {
            return Mage::getModel('shipping/rate_result');
        }
    }

    public function getAllowedMethods()
    {
        return array('adminshipping'=>'adminshipping');
    }


}
