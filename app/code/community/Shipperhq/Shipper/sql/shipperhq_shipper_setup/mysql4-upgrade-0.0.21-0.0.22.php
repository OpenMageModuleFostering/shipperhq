<?php
/* @var $installer Mage_Core_Model_Resource_Setup */
$installer = $this;
$installer->startSetup();


if($installer->getAttribute('catalog_product', 'shipperhq_dim_group')) {
    $installer->updateAttribute('catalog_product', 'shipperhq_dim_group', array('is_configurable' => false));
}

if($installer->getAttribute('catalog_product', 'shipperhq_poss_boxes')) {
    $installer->updateAttribute('catalog_product', 'shipperhq_poss_boxes', array('is_configurable' => false));
}

if($installer->getAttribute('catalog_product', 'shipperhq_warehouse')) {
    $installer->updateAttribute('catalog_product', 'shipperhq_warehouse', array('is_configurable' => false));
}

if($installer->getAttribute('catalog_product', 'shipperhq_shipping_group')) {
    $installer->updateAttribute('catalog_product', 'shipperhq_shipping_group', array('is_configurable' => false));
}

$installer->endSetup();
