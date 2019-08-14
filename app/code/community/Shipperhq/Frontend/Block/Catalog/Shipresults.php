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
 * Provides proxy methods for helper block
 *
 * Supplies helper block with render information
 */
class Shipperhq_Frontend_Block_Catalog_Shipresults extends Mage_Catalog_Block_Product_Abstract
{
    /**
     * Returns a helper block, that is used to retrieve all data
     *
     * @return Shipperhq_Frontend_Block_Checkout_Helper
     */
    public function getHelperBlock()
    {
        if ($this->_helperBlock === null) {
            $this->_helperBlock = $this->getLayout()->createBlock(
                'shipperhq_frontend/catalog_helper',
                sprintf('%s.helper', $this->getNameInLayout())
            );

            //TODO change these
            $this->_helperBlock->init(array(
                'calendar_block' => 'shipperhq_calendar/catalog_calendar',
                'calendar_template' => 'shipperhq/calendar/catalog/calendar.phtml',
//                'pickup_block' => 'shipperhq_pickup/checkout_onepage_shipping_method_storepickup',
//                'pickup_template' => 'shipperhq/pickup/checkout/onepage/shipping_method/storepickup.phtml',
//
            ));
        }


        return $this->_helperBlock;
    }

    public function isCalendarRate($carrierGroupId, $carrierCode, $carrierType)
    {
        return $this->getHelperBlock()->isCalendarRate($carrierGroupId, $carrierCode, $carrierType);
    }

    public function getCalendarHtml($carriergroup, $code, $soleMethod = false)
    {
        return $this->getHelperBlock()->getCalendarHtml($carriergroup, $code);
    }

    public function getStorepickupHtml($carrierCode, $carrierType, $carriergroup = null )
    {
        return $this->getHelperBlock()->getStorepickupHtml($carrierCode, $carrierType, $carriergroup);
    }

    public function isPickupCarrier($rate)
    {
        return $this->getHelperBlock()->isPickupCarrier($rate);
    }

    public function isFreightCarrier($rate)
    {
        return $this->getHelperBlock()->isFreightCarrier($rate);
    }

    public function getFreightAccessorialsHtml($carrierCode, $carrierType, $carrerigroup = '')
    {
        return $this->getHelperBlock()->getFreightAccessorialsHtml($carrerigroup, $carrierCode, $carrierType);
    }
}