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
* @package ShipperHQ
* @copyright Copyright (c) 2014 Zowta LLC (http://www.ShipperHQ.com)
* @license http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
* @author ShipperHQ Team sales@shipperhq.com
*/

class Shipperhq_Pbint_Helper_Data extends Mage_Core_Helper_Abstract
{
    protected static $_debug;
    protected static $code = 'pitney';
    /**
     * Retrieve debug configuration
     * @return boolean
     */
    public function isDebug()
    {
        if (self::$_debug == NULL) {
            self::$_debug = Mage::helper('wsalogger')->isDebug('Shipperhq_Pbint');
        }
        return self::$_debug;
    }

    public function isPbOrder($carrierType)
    {
        if($carrierType == self::$code) {
            if ($this->isDebug()) {
                Mage::helper('wsalogger/log')->postDebug('Shipperhq_Pbint',
                    'Carrier type is Pitney Bowes International via ShipperHQ', $carrierType);
            }
            return true;
        }
        if ($this->isDebug()) {
            Mage::helper('wsalogger/log')->postDebug('Shipperhq_Pbint',
                'Carrier type is not Pitney Bowes International via ShipperHQ', $carrierType);

        }
        return false;

    }
}
