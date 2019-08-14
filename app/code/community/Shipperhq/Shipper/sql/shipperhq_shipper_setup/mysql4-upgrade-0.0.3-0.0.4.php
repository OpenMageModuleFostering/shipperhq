<?php
/* @var $installer Mage_Core_Model_Resource_Setup */
$installer = $this;
$installer->startSetup();


$installer->updateAttribute('catalog_product', 'shipperhq_shipping_price', array('frontend_label' => 'Shipping Fee', 'attribute_code' => 'shipperhq_shipping_fee'));


$installer->endSetup();
