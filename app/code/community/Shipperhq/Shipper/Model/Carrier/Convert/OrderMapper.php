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
 * Class Shipperhq_Shipper_Model_Carrier_Convert_ShipperMapper
 *
 * This class converts the Magento Request into a format that
 * is usable by the ShipperHQ webservice
 */

include_once 'ShipperHQ/User/Credentials.php';
include_once 'ShipperHQ/User/SiteDetails.php';
include_once 'ShipperHQ/WS/Request/Rate/ShipmentCustomerDetails.php';
include_once 'ShipperHQ/WS/Request/Rate/ShipmentRequest.php';
include_once 'ShipperHQ/Shipping/Address.php';


class Shipperhq_Shipper_Model_Carrier_Convert_OrderMapper extends Shipperhq_Shipper_Model_Carrier_Convert_ShipperMapper
{

    protected static $ecommerceType = 'magento';

    /**
     * Set up values for ShipperHQ Shipment Request
     *
     * @param $order
     * @return string
     */
    public static function getOrderTranslation($order, $quote, $customOrderId = null)
    {

        $shipperHQRequest = new \ShipperHQ\WS\Request\Rate\ShipmentRequest(
            self::getShipmentItems($order),
            self::getShippingAddress($order),
            self::getBillingAddress($order),
            self::getCustomerDetails($order, $quote),
            self::getCarrierCode($quote, $order),
            self::getMagentoOrderNumber($order),
            $customOrderId,
            self::getOriginName($order),
            self::getMethodCode($order)
        );
        $shippingAddress = $quote->getShippingAddress();
        $storeId = $quote->getStoreId();
        $shipperHQRequest->setSiteDetails(self::getSiteDetails($storeId));
        $shipperHQRequest->setCredentials(self::getCredentials($storeId));

        return $shipperHQRequest;
    }

    /**
     * Format cart for from shipper for Magento
     *
     * @param $request
     * @return array
     */
    public static function getShipmentItems($order)
    {
        $shipmentItems = false;
        //TODO we need to support CarrierGroupId here
        // loadByCarriergroup($orderId, $carrierGroupId)
        $packagesColl= Mage::getModel('shipperhq_shipper/order_packages')
            ->loadByOrderId($order->getId());
        $mapping = self::getPackagesMapping();
        if($packagesColl) {
            $shipmentItems = array();
            foreach($packagesColl as $package) {
                $data = Mage::helper('shipperhq_shipper/mapper')->map($mapping,(array)$package->getData());
                $data['boxedItems'] = self::appendAdditionalAttributes($data['boxedItems']);
                $shipmentItems[] = $data;
            }
        }
        return $shipmentItems;
    }

    public static function appendAdditionalAttributes($itemData)
    {
        $newItemData = array();
        foreach($itemData as $item)
        {
            $product = Mage::getModel('catalog/product')->loadByAttribute('sku', $item['sku']);
            // dont want package_id
            $data['sku'] = $item['sku'];
            $data['weightPacked'] = $item['weight_packed'];
            $data['qtyPacked'] = $item['qty_packed'];
            if($product) {
                $countryArray = array('name' => 'country_of_manufacture',
                                    'value' => $product->getData('country_of_manufacture'));
                $data['shippingAttributes'] = array($countryArray);
            }
            $newItemData[] = $data;
        }
        return $newItemData;
    }
    /*
     * Return customer group details
     *
     */
    public static function getCustomerDetails($order, $quote)
    {
        $code = self::getCustomerGroupId($quote);
        $group = Mage::getModel('customer/group')->load($code);
        $billingAddress = $order->getBillingAddress();
        $custGroupDetails = new \ShipperHQ\WS\Request\Rate\ShipmentCustomerDetails(
            $group->getCustomerGroupCode(), $billingAddress->getCompany(),  $order->getCustomerEmail(),  $order->getCustomerFirstname(),
            $order->getCustomerLastname(), $billingAddress->getTelephone()
        );

        return $custGroupDetails;
    }


    /*
     * Return selected carrierGroup id
     */
    public static function getCarrierGroupId($request)
    {
        $carrierGroupId = $request->getCarriergroupId();
        return $carrierGroupId;
    }
    /*
   * Return selected carrier id
   *
   */
    public static function getCarrierCode($quote, $order)
    {
        $carrierCode = null;
        $shippingMethod = $order->getShippingMethod();

        $rate = $quote->getShippingAddress()->getShippingRateByCode($shippingMethod);
        if($rate) {
            $carrierCode = $rate->getCarrier();
        }
        else{
            $carrierArray = explode('_', $shippingMethod);
            $carrierCode = $carrierArray[0];
        }

        return $carrierCode;
    }

    protected static function getPackagesMapping()
    {
        return array(
            'name' => 'package_name',
            'length' => 'length',
            'width' => 'width',
            'height' => 'height',
            'weight' => 'weight',
            'surchargePrice' => 'surcharge_price',
            'declaredValue' => 'declared_value',
            'boxedItems'         => 'items'
        );
    }

    protected static function getCustomerGroupId($quote)
    {
        if($quote) {
            return $quote->getCustomerGroupId();
        }
        return null;
    }

    /**
     * Get values for destination
     *
     * @param $request
     * @return array
     */
    private static function getShippingAddress($order)
    {
        $shippingAddress = $order->getShippingAddress();

        $streetArray =  $shippingAddress->getStreet();
        $street2 = isset($streetArray[1]) ? $streetArray[1] : null;
        $destination = new \ShipperHQ\Shipping\Address(
            null,
            $shippingAddress->getCity(),
            $shippingAddress->getCountryId(),
            $shippingAddress->getRegionCode(),
            $streetArray[0],
            $street2,
            $shippingAddress->getPostcode(),
            $shippingAddress->getEmail(),
            $shippingAddress->getFirstname(),
            $shippingAddress->getLastname(),
            $shippingAddress->getCompany(),
            $shippingAddress->getTelephone(),
            null
        );
        return $destination;
    }

    /**
     * Get values for destination
     *
     * @param $request
     * @return array
     */
    private static function getBillingAddress($order)
    {
        $billingAddress = $order->getBillingAddress();

        $streetArray =  $billingAddress->getStreet();
        $street2 = isset($streetArray[1]) ? $streetArray[1] : null;
        $address = new \ShipperHQ\Shipping\Address(
            null,
            $billingAddress->getCity(),
            $billingAddress->getCountryId(),
            $billingAddress->getRegionCode(),
            $streetArray[0],
            $street2,
            $billingAddress->getPostcode(),
            $billingAddress->getEmail(),
            $billingAddress->getFirstname(),
            $billingAddress->getLastname(),
            $billingAddress->getCompany(),
            $billingAddress->getTelephone(),
            null
        );
        return $address;
    }

    protected static function getOriginName($order)
    {
        $carrierGroupDetail = $order->getCarriergroupShippingDetails();
        if($carrierGroupDetail) {
            $cgDetail = json_decode($carrierGroupDetail);
            foreach($cgDetail as $carrierGroup) {
                if(!is_array($carrierGroup)) {
                    $carrierGroup = (array)$carrierGroup;
                }
                return $carrierGroup['name'];
            }
        }
        return null;
    }

    protected static function getMethodCode($order)
    {
        $shipmethod =  explode('_', $order->getShippingMethod());
        if(is_array($shipmethod)) {
            return $shipmethod[1];
        }
        return null;
    }
}
