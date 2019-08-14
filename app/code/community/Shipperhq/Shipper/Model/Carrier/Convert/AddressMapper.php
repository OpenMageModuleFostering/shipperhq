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
 * Class Shipperhq_Shipper_Model_Carrier_Convert_QuoteMapper
 *
 * This class converts the Magento Request into a format that
 * is usable by the ShipperHQ webservice
 */

include_once 'ShipperHQ/User/Credentials.php';
include_once 'ShipperHQ/User/SiteDetails.php';
include_once 'ShipperHQ/WS/Request/Rate/CustomerDetails.php';
include_once 'ShipperHQ/WS/Request/Rate/ShipDetails.php';
include_once 'ShipperHQ/WS/Request/Rate/RateRequest.php';
include_once 'ShipperHQ/WS/Request/Rate/InfoRequest.php';
include_once 'ShipperHQ/Shipping/Address.php';
include_once 'ShipperHQ/Shipping/Accessorials.php';


class Shipperhq_Shipper_Model_Carrier_Convert_AddressMapper extends Shipperhq_Shipper_Model_Carrier_Convert_ShipperMapper {


    function __construct() {
        self::$_prodAttributes = Mage::helper('shipperhq_shipper')->getProductAttributes();
    }
    /**
     * Set up values for ShipperHQ Rates Request
     *
     * @param $magentoRequest
     * @return string
     */
    public static function getAddressTranslation($address, $carrierCode = null, $carrierGroupId = null)
    {

        $shipperHQRequest = new \ShipperHQ\WS\Request\Rate\RateRequest(
            self::getCartDetails($address),
            self::getDestination($address),
            self::getCustomerGroupDetails($address),
            self::getCartType($address)
        );

        if($carrierGroupId) {
            $shipperHQRequest->setCarrierGroupId($carrierGroupId);
        }

        if($carrierCode) {
            $shipperHQRequest->setCarrierCode($carrierCode);
        }
        $storeId = $address->getQuote()->getStoreId();
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
    public static function getCartDetails($address)
    {
        $cart = array();
        $cart['declaredValue'] = (int)$address->getSubtotal();
        $cart['freeShipping'] = null;
        $cart['items'] = self::getFormattedItems($address->getAllItems());

        // $cart['additional_attributes'] = null; // not currently implemented

        return $cart;
    }

    /*
     * Return customer group details
     *
     */
    public static function getCustomerGroupDetails($address)
    {
        $code = $address->getQuote()->getCustomerGroupId();
        $group = Mage::getModel('customer/group')->load($code);

        $custGroupDetails = new \ShipperHQ\WS\Request\Rate\CustomerDetails(
            $group->getCustomerGroupCode()
        );

        return $custGroupDetails;
    }

    /*
    * Return cartType String default
    *
    */
    public static function getCartType($address)
    {
        return 'STD';
    }


    /**
     * Get values for items
     *
     * @param $request
     * @param $magentoItems
     * @param bool $childItems
     * @param null $taxPercentage
     * @return array
     */
    private static function getFormattedItems($magentoItems, $childItems = false, $taxPercentage = null)
    {
        $formattedItems = array();
        if (empty($magentoItems)) {
            return $formattedItems;
        }
        $selectedCarriergroupId = false;

        foreach ($magentoItems as $magentoItem) {
            if (!$childItems && $magentoItem->getParentItemId()) {
                continue;
            }

            $calculator = Mage::helper('tax')->getCalculator();
            $taxRequest = $calculator->getRateOriginRequest();
            $taxRequest->setProductClassId($magentoItem->getProduct()->getTaxClassId());
            $taxPercentage = $calculator->getRate($taxRequest);
            $fixedPrice = $magentoItem->getProduct()->getPriceType() == Mage_Bundle_Model_Product_Price::PRICE_TYPE_FIXED;
            $fixedWeight =  $magentoItem->getProduct()->getWeightType() == 1 ? true : false;
            $id = $magentoItem->getItemId()? $magentoItem->getItemId() : $magentoItem->getQuoteItemId();
            $productType = $magentoItem->getProductType()? $magentoItem->getProductType() : $magentoItem->getProduct()->getTypeId();
            $stdAttributes = array_merge(self::getDimensionalAttributes($magentoItem), self::$_stdAttributeNames);

            $formattedItem = array(
                'id'                          => $id,
                'sku'                         => $magentoItem->getSku(),
                'storePrice'                  => $magentoItem->getPrice(),
                'weight'                      => $magentoItem->getWeight(),
                'qty'                         => floatval($magentoItem->getQty()),
                'type'                        => $productType,
                'items'                       => array(), // child items
                'basePrice'                   => $magentoItem->getBasePrice(),
                'taxInclBasePrice'            => $magentoItem->getBasePriceInclTax()? $magentoItem->getBasePriceInclTax(): 0,
                'taxInclStorePrice'           => $magentoItem->getPriceInclTax() ? $magentoItem->getPriceInclTax() : 0,
                'rowTotal'                    => $magentoItem->getRowTotal(),
                'baseRowTotal'                => $magentoItem->getBaseRowTotal(),
                'discountPercent'             => $magentoItem->getDiscountPercent(),
                'discountedBasePrice'         => $magentoItem->getBasePrice() - ($magentoItem->getBaseDiscountAmount()/$magentoItem->getQty()),
                'discountedStorePrice'        => $magentoItem->getPrice() - ($magentoItem->getDiscountAmount()/$magentoItem->getQty()),
                'discountedTaxInclBasePrice'  => $magentoItem->getBasePrice() - ($magentoItem->getBaseDiscountAmount()/$magentoItem->getQty()) + ($magentoItem->getBaseTaxAmount()/$magentoItem->getQty()),
                'discountedTaxInclStorePrice' => $magentoItem->getPrice() - ($magentoItem->getDiscountAmount()/$magentoItem->getQty()) +  ($magentoItem->getTaxAmount()/$magentoItem->getQty()),
                'attributes'                  => self::populateAttributes($stdAttributes, $magentoItem),
                'legacyAttributes'            => self::populateAttributes(self::$_legacyAttributeNames, $magentoItem),
                'baseCurrency'                => Mage::app()->getBaseCurrencyCode(),//$request->getBaseCurrency()->getCurrencyCode(),
                'packageCurrency'             => Mage::app()->getBaseCurrencyCode(), //$request->getPackageCurrency()->getCurrencyCode(),
                'storeBaseCurrency'           => Mage::app()->getBaseCurrencyCode(),
                'storeCurrentCurrency'        => Mage::app()->getStore()->getCurrentCurrencyCode(),
                'taxPercentage'               => $taxPercentage,
                'freeShipping'                => (bool)$magentoItem->getFreeShipping(),
                'additionalAttributes'        => self::getCustomAttributes($magentoItem),
                'fixedPrice'                  => $fixedPrice,
                'fixedWeight'                 => $fixedWeight,
            );

            if (!$childItems) {
                $formattedItem['items'] = self::getFormattedItems(
                    $magentoItem->getChildren(), true, null );
            }

            $formattedItems[] = $formattedItem;

        }
        return $formattedItems;
    }

    /**
     * Get values for destination
     *
     * @param $request
     * @return array
     */
    private static function getDestination($shippingAddress)
    {
        $selectedOptions = self::getSelectedOptions($shippingAddress);
        $streetArray = $shippingAddress->getStreet();
        $street2 = isset($streetArray[1]) ? $streetArray[1] : '';
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
            $selectedOptions
        );

        return $destination;
    }

    /**
     * Set up additional attribute array
     * This takes the values from core_config_data
     *
     * Not currently implemented for v1 Magento2.
     *
     * @param $item
     * @return array
     */
    private static function getCustomAttributes($item)
    {
        $rawCustomAttributes = explode(',', Mage::getStoreConfig('carriers/shipper/item_attributes'));
        $customAttributes = array();
        foreach ($rawCustomAttributes as $attribute) {
            $attribute = str_replace(' ', '', $attribute);
            if(!in_array($attribute, self::$_stdAttributeNames) && !in_array($attribute, self::$_legacyAttributeNames) && $attribute != '') {
                $customAttributes[] = $attribute;
            }
        }

        return self::populateAttributes($customAttributes,$item);

    }

}
