<?php
/* @var $installer Mage_Core_Model_Resource_Setup */
$installer = $this;
$installer->startSetup();


$installer->updateAttribute('catalog_product', 'shipperhq_handling_price', array('frontend_label' => 'Handling Fee', 'attribute_code' => 'shipperhq_handling_fee'));


$installer->endSetup();
