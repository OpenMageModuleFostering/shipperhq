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

class Shipperhq_Shipper_Model_Checkout_Helper
{
    /**
     * Save the carrier group shipping details for single carriergroup orders and then
     * return to standard Magento logic to save the method
     *
     * @param $shippingMethod
     * @return array
     */
    public function saveSingleShippingMethod(Mage_Sales_Model_Quote_Address $shippingAddress, $shippingMethod)
    {
        if (empty($shippingMethod) ) {
            $res = array(
                'error' => -1,
                'message' => Mage::helper('shipperhq_shipper')->__('Invalid Shipping Method.')
            );
            return $res;
        }
        $foundRate = $shippingAddress->getShippingRateByCode($shippingMethod);
        if($foundRate) {
            $shipDetails =  Mage::helper('shipperhq_shipper')->decodeShippingDetails($foundRate->getCarriergroupShippingDetails());
            if(array_key_exists('carrierGroupId', $shipDetails)) {
                $arrayofShipDetails = array();
                $arrayofShipDetails[] = $shipDetails;
                $shipDetails = $arrayofShipDetails;
                $encodedShipDetails = Mage::helper('shipperhq_shipper')->encodeShippingDetails($arrayofShipDetails);
            }
            else {
                $encodedShipDetails = Mage::helper('shipperhq_shipper')->encodeShippingDetails($shipDetails);
            }

            $shippingAddress
                ->setCarrierId($foundRate->getCarrierId())
                ->setCarrierType($foundRate->getCarrierType())
                ->setCarriergroupShippingDetails($encodedShipDetails)
                ->setCarriergroupShippingHtml(Mage::helper('shipperhq_shipper')->getCarriergroupShippingHtml(
                    $encodedShipDetails))
                ->setCarriergroupShippingHtml('something funny is going on')
                ->save();
            Mage::helper('shipperhq_shipper')->setShippingOnItems($shipDetails,  $shippingAddress);

        }
        return array();
    }

}