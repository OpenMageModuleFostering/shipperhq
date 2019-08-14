<?php
/* @var $installer Mage_Core_Model_Resource_Setup */
$installer = $this;
$installer->startSetup();


if($installer->getAttribute('catalog_product', 'shipperhq_dim_group')) {
    $installer->updateAttribute('catalog_product', 'shipperhq_dim_group', array('is_user_defined' => false));
}

if($installer->getAttribute('catalog_product', 'shipperhq_poss_boxes')) {
    $installer->updateAttribute('catalog_product', 'shipperhq_poss_boxes', array('is_user_defined' => false));
}

if($installer->getAttribute('catalog_product', 'shipperhq_dim_group')) {
    $installer->updateAttribute('catalog_product', 'shipperhq_dim_group', array('is_user_defined' => false));
}


$installer->endSetup();
