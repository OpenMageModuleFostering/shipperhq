<?php

/* @var $installer Mage_Core_Model_Resource_Setup */
$installer = $this;

$installer->startSetup();

$destinationTypeAttr =  array(
    'type'    	=> $version > 10 ? Varien_Db_Ddl_Table::TYPE_TEXT : 'text',
    'comment' 	=> 'ShipperHQ Address Type',
    'nullable' 	=> 'true',
);

if(!$installer->getConnection()->tableColumnExists($installer->getTable('sales/quote_address'), 'destination_type')){
    $installer->getConnection()->addColumn($installer->getTable('sales/quote_address'),'destination_type',$destinationTypeAttr);
}

if(!$installer->getConnection()->tableColumnExists($installer->getTable('sales/order'), 'destination_type')){
    $installer->getConnection()->addColumn($installer->getTable('sales/order'),'destination_type',$destinationTypeAttr);
}

$installer->endSetup();