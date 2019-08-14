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
class Shipperhq_Pbint_Model_Credentials {
	
	public static function decrypt($string) {
		if (!empty($string)) {
			return Mage::helper('core')->decrypt($string);
		}
		return "";
	}

    public static function getMaxRecordsCount() {
        return Mage::getStoreConfig('shipperhqpitney/shqpbint/catalog_size');
    }
    public static function getAdminEmail() {
        return Mage::getStoreConfig('shipperhqpitney/shqpbint/admin_email');
    }
    public static function getCatalogSenderID() {
        return Mage::getStoreConfig('shipperhqpitney/shqpbint/catalog_sender_id');
    }
	
	public static function getMerchantCode() {
		return self::decrypt(Mage::getStoreConfig('shipperhqpitney/shqpbint/merchantcode'));
	}
	
	public static function getSftpUsername() {
		return self::decrypt(Mage::getStoreConfig('shipperhqpitney/shqpbint/ftpuser'));
	}
	public static function getSftpPassword() {
		return self::decrypt(Mage::getStoreConfig('shipperhqpitney/shqpbint/ftppass'));
	}
	public static function getSftpHostname() {
		return Mage::getStoreConfig('shipperhqpitney/shqpbint/ftphost');
	}
	public static function getSftpPort() {
		return Mage::getStoreConfig('shipperhqpitney/shqpbint/ftpport');
	}
	public static function getSftpCatalogDirectory() {
		return self::decrypt(Mage::getStoreConfig('shipperhqpitney/shqpbint/ftpdir'));
	}
    public static function isEncryptionEnabled() {
        return Mage::getStoreConfig('shipperhqpitney/shqpbint/catalog_encryption_enabled');
    }
    public static function getPublicKey() {
        return Mage::getStoreConfig('shipperhqpitney/shqpbint/encryption_public_key');
    }
    public static function getPBID() {
        return "16061";
    }
}
?>
