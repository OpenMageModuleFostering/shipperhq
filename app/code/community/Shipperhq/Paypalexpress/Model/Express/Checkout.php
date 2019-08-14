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
 *
 * ST_Core_Model_Resource_Iterator_Batched : Kalen Jordan
 */
class Shipperhq_Paypalexpress_Model_Express_Checkout extends Mage_Paypal_Model_Express_Checkout
{

    /*
     * Resolve bug where PayPal displays the code instead of the name
     */
    protected function _prepareShippingOptions(
        Mage_Sales_Model_Quote_Address $address,
        $mayReturnEmpty = false, $calculateTax = false
    ) {
        $options = array(); $i = 0; $iMin = false; $min = false;
        $userSelectedOption = null;

        foreach ($address->getGroupedAllShippingRates() as $group) {
            foreach ($group as $rate) {
                $amount = (float)$rate->getPrice();
                if ($rate->getErrorMessage()) {
                    continue;
                }
                $isDefault = $address->getShippingMethod() === $rate->getCode();
                $amountExclTax = Mage::helper('tax')->getShippingPrice($amount, false, $address);
                $amountInclTax = Mage::helper('tax')->getShippingPrice($amount, true, $address);

                $options[$i] = new Varien_Object(array(
                    'is_default' => $isDefault,
                    'name'       => $rate->getCode(),
                    'code'       => trim("{$rate->getCarrierTitle()} - {$rate->getMethodTitle()}", ' -'),
                    'amount'     => $amountExclTax,
                ));
                if ($calculateTax) {
                    $options[$i]->setTaxAmount(
                        $amountInclTax - $amountExclTax
                        + $address->getTaxAmount() - $address->getShippingTaxAmount()
                    );
                }
                if ($isDefault) {
                    $userSelectedOption = $options[$i];
                }
                if (false === $min || $amountInclTax < $min) {
                    $min = $amountInclTax;
                    $iMin = $i;
                }
                $i++;
            }
        }

        if ($mayReturnEmpty && is_null($userSelectedOption)) {
            $options[] = new Varien_Object(array(
                'is_default' => true,
                'code'       => Mage::helper('paypal')->__('N/A'),
                'name'       => 'no_rate',
                'amount'     => 0.00,
            ));
            if ($calculateTax) {
                $options[$i]->setTaxAmount($address->getTaxAmount());
            }
        } elseif (is_null($userSelectedOption) && isset($options[$iMin])) {
            $options[$iMin]->setIsDefault(true);
        }

        // Magento will transfer only first 10 cheapest shipping options if there are more than 10 available.
        if (count($options) > 10) {
            usort($options, array(get_class($this),'cmpShippingOptions'));
            array_splice($options, 10);
            // User selected option will be always included in options list
            if (!is_null($userSelectedOption) && !in_array($userSelectedOption, $options)) {
                $options[9] = $userSelectedOption;
            }
        }

        return $options;
    }


    /*
     * Reverse code and name in option matching to encompass fix above
     *
     */
    protected function _matchShippingMethodCode(Mage_Sales_Model_Quote_Address $address, $selectedCode)
    {
        $options = $this->_prepareShippingOptions($address, false);
        foreach ($options as $option) {
            if ($selectedCode === $option['code'] // the proper case as outlined in documentation
                || $selectedCode === $option['name'] // workaround: PayPal may return name instead of the code
                // workaround: PayPal may concatenate code and name, and return it instead of the code:
                || $selectedCode === "{$option['code']} {$option['name']}"
            ) {
                return $option['name'];
            }
        }
        return '';
    }


}