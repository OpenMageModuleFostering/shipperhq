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
 *
 */
class Shipperhq_Shipper_Model_Quote_Packages
    extends Mage_Core_Model_Abstract
{
    /**
     * Packages stored for quote
     *
     */
    protected function _construct()
    {
        $this->_init('shipperhq_shipper/quote_packages');
    }

    /**
     * Loads data by quote object
     *
     * @param Mage_Sales_Model_Quote $quote
     * @return $this
     */
    public function loadByCarrier($addressId, $carrierGroupId, $carrierCode)
    {
         $collection = $this->getResourceCollection()
            ->addAddressToFilter($addressId)
            ->addCarrierCodeToFilter($carrierCode);
        if(!is_null($carrierGroupId)) {
            $collection->addCarrierGroupToFilter($carrierGroupId);
        }

        foreach($collection as $package)
        {
            $package->load($package->getId());
        }
        return $collection;
    }


    /**
     *
     *
     * @return Mage_Core_Model_Abstract
     */
    protected function _beforeSave()
    {
        return parent::_beforeSave();
    }



}
