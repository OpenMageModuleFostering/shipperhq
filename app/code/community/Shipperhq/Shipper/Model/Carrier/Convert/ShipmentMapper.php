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
include_once 'ShipperHQ/WS/Request/Rate/ShipmentAddonRequest.php';


class Shipperhq_Shipper_Model_Carrier_Convert_ShipmentMapper extends Shipperhq_Shipper_Model_Carrier_Convert_ShipperMapper
{

    protected static $ecommerceType = 'magento';

    /**
     * Set up values for ShipperHQ Shipment Request
     *
     * @param $order
     * @return string
     */
    public static function getShipmentTranslation($order, $shipment, $package, $customOrderId = null)
    {

        $shipperHQRequest = new \ShipperHQ\WS\Request\Rate\ShipmentAddonRequest(
            self::getShipmentItems($package),
            self::getCarrierCode($order),
            self::getDomesticShipper($order),
            self::getMethodCode($order),
            self::getMagentoOrderNumber($order),
            self::getOriginName($order),
            $customOrderId,
            self::getTrackingNo($shipment)
        );
        $storeId = $order->getStoreId();
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
    public static function getShipmentItems($package)
    {
        $shipmentItems = false;
        //TODO we need to support CarrierGroupId here
        if($package) {
            $mapping = self::getPackagesMapping();
            $data = Mage::helper('shipperhq_shipper/mapper')->map($mapping,(array)$package->getData());
            $data['boxedItems'] = self::appendAdditionalAttributes($data['boxedItems']);
            $shipmentItems = $data;
        }
        return $shipmentItems;
    }

    public static function appendAdditionalAttributes($itemData)
    {
        $newItemData = array();
        foreach($itemData as $item)
        {
            $product = Mage::getModel('catalog/product')->loadByAttribute('sku', $item['sku']);
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

    public static function getCarrierCode($order)
    {
        $carrierCode = null;

        $shipMethod = $order->getShippingMethod();
        $shipArray = explode('_', $shipMethod);
        if(is_array($shipArray)) {
            $carrierCode = $shipArray[0];
        }
        return $carrierCode;
    }

    public static function getMethodCode($order)
    {
        $methodCode = null;
        $shipMethod = $order->getShippingMethod();
        $shipArray = explode('_', $shipMethod);
        if(is_array($shipArray)) {
            $methodCode = $shipArray[1];
        }
        return $methodCode;
    }

    /**
     * Domestic tracking number
     * @param $shipment
     * @return string
     */
    public static function getTrackingNo($shipment)
    {
        foreach($shipment->getTracksCollection() as $track) {
            return $track->getNumber();
        }
        return 'Not Set';
    }

    /**
     * Domestic carrier
     *
     * @param $shipment
     * @return string
     */
    public static function getDomesticShipper($shipment)
    {
        foreach($shipment->getTracksCollection() as $track) {
            return $track->getTitle();
        }
        return 'Not Set';
    }

    public static function getOriginName($order)
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

}
