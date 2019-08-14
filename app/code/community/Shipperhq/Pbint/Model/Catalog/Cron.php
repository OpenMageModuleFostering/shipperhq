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
class Shipperhq_Pbint_Model_Catalog_Cron
{

    private $lastDiff;
    private $lastFull;

    protected $_debug;

    public function catalogSync()
    {
        $this->_debug = Mage::helper('shipperhq_pbint')->isDebug();
        if ($this->_debug) {
            Mage::helper('wsalogger/log')->postDebug('Shipperhq_Pbint',
                'Catalog synch', 'Synchronization starting');
        }
        $diffPeriod = Mage::getStoreConfig('shipperhqpitney/shqpbint/catalog_diff');
        $fullPeriod = Mage::getStoreConfig('shipperhqpitney/shqpbint/catalog_full');

        $collection = Mage::getModel("shipperhq_pbint/variable")->getCollection();
        foreach ($collection as $variable) {
            if ($this->_debug) {
                Mage::helper('wsalogger/log')->postDebug('Shipperhq_Pbint',
                    'Processing: ', $variable->getName() . " -> " . $variable->getValue());
            }
            if ($variable->getName() == "lastFull") {
                $this->lastFull = $variable;
            }
            if ($variable->getName() == "lastDiff") {
                $this->lastDiff = $variable;
            }
        }


        $appRoot = Mage::getRoot();
        $mageRoot = dirname($appRoot);
        $configOptions = Mage::getModel('core/config_options');
        $configOptions->createDirIfNotExists($mageRoot . '/var/pbint');
        chmod($mageRoot . '/var/pbint/', 0777);

        if (!isset($this->lastFull) || $this->lastFull->getValue() < time() - $fullPeriod * 24 * 3600) {
            // Full catalog upload needed
            $this->uploadCatalog();
        } else if (!isset($this->lastDiff) && $this->lastFull->getValue() < time() - $diffPeriod * 3600) {
            // First catalog diff upload
            $this->uploadCatalog($this->lastFull);
        } else if (isset($this->lastDiff) && ($this->lastDiff->getValue() < time() - $diffPeriod * 3600 &&
                $this->lastFull->getValue() < time() - $diffPeriod * 3600)
        ) {
            // Catalog diff upload
            $this->uploadCatalog($this->lastDiff);
        } else {
            if ($this->_debug) {
                Mage::helper('wsalogger/log')->postDebug('Shipperhq_Pbint',
                    'PB Export cron.', 'Do not export catalog');
            }
        }

    }

    public function uploadCatalog($lastDiff = false)
    {
        $this->_debug = Mage::helper('shipperhq_pbint')->isDebug();
        if (!$lastDiff) {
            if ($this->_debug) {
                Mage::helper('wsalogger/log')->postDebug('Shipperhq_Pbint',
                    'PB Synch', 'Full catalog upload');
            }
            $file = new Shipperhq_Pbint_Model_Catalog_File();
            if (isset($this->lastFull)) {
                $this->lastFull->setValue(time());
            } else {
                $this->lastFull = Mage::getModel("shipperhq_pbint/variable");
                $this->lastFull->setName("lastFull");
                $this->lastFull->setValue(time());
            }
            $this->lastFull->save();
        } else {
            if ($this->_debug) {
                Mage::helper('wsalogger/log')->postDebug('Shipperhq_Pbint',
                    'PB Synch', 'Catalog diff');
            }
            $file = new Shipperhq_Pbint_Model_Catalog_File($lastDiff->getValue());
            if (isset($this->lastDiff)) {
                $this->lastDiff->setValue(time());
            } else {
                $this->lastDiff = Mage::getModel("shipperhq_pbint/variable");
                $this->lastDiff->setName("lastDiff");
                $this->lastDiff->setValue(time());
            }
            $this->lastDiff->save();
        }

        if ($this->_debug) {
            Mage::helper('wsalogger/log')->postDebug('Shipperhq_Pbint',
                'Catalog synch', 'Create function started');
        }
        $result = $file->createNew();
        if ($this->_debug) {
            Mage::helper('wsalogger/log')->postDebug('Shipperhq_Pbint',
                'Catalog synch', 'Create function complete');
        }

        $result = $file->upload();
        $file->logProdWithoutCategories();
        if ($this->_debug) {
            Mage::helper('wsalogger/log')->postDebug('Shipperhq_Pbint',
                'PB Synch', 'Upload Complete');
        }
        return $result;
    }

    public function processStatusNotifications()
    {
        $file = new Shipperhq_Pbint_Model_Catalog_File();
        $file->processStatusNotifications();
    }

}


?>
