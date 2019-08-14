<?php

/* @var $installer Mage_Core_Model_Resource_Setup */
$installer = $this;

$installer->startSetup();

/**
 * We use this for some of the EE layout updates
 */
$isEnterprise = Mage::helper('wsalogger')->isEnterpriseEdition();

if($isEnterprise) {
    $config = new Mage_Core_Model_Config();
    $config->saveConfig('carriers/shipper/is_enterprise', "1", 'default', 0);
}

$installer->endSetup();