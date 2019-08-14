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
 * Shipper HQ Pitney Bowes International
 *
 * @category ShipperHQ
 * @package ShipperHQ_Shipping_Carrier
 * @copyright Copyright (c) 2014 Zowta LLC (http://www.ShipperHQ.com)
 * @license http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @author ShipperHQ Team sales@shipperhq.com
 */
class Shipperhq_Pbint_Model_Quote_Duty extends Mage_Sales_Model_Quote_Address_Total_Abstract
{
    /**
     * Discount calculation object
     *
     * @var Mage_SalesRule_Model_Validator
     */
    protected $dutyCalculated = false;
    protected $dutyDisplayed = false;

    public function __construct()
    {
	   // parent::__construct();
        $this->setCode('shq_pb_duty');

    }
    
    public function getDutyAndTax() {
	    return Mage::getSingleton("customer/session")->getPbDutyAndTax();
    }

    /**
     * Collect address discount amount
     *
     * @param   Mage_Sales_Model_Quote_Address $address
     * @return  Mage_SalesRule_Model_Quote_Discount
     */
    public function collect(Mage_Sales_Model_Quote_Address $address)
    {
        parent::collect($address);

		if ($amount = $this->getDutyAndTax()) {
            $items = $this->_getAddressItems($address);
            if (!count($items)) {
                return $this;
            }

		    $this->_setAmount($address->getQuote()->getStore()->convertPrice($amount, false));
            $this->_setBaseAmount($amount);
		}
        return $this;
    }


    /**
     * Add discount total information to address
     *
     * @param   Mage_Sales_Model_Quote_Address $address
     * @return  Mage_SalesRule_Model_Quote_Discount
     */
    public function fetch(Mage_Sales_Model_Quote_Address $address)
    {
        if ($address->getShqPbDutyAmount() != 0) {
            $address->addTotal(array(
                'code' => $this->getCode(),
                'title' => 'International Duty',
                'value' => $address->getShqPbDutyAmount()
            ));
        }
    }
}
