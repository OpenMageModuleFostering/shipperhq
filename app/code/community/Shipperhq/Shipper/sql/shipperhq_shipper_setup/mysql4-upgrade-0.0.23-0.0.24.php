<?php
/* @var $installer Mage_Core_Model_Resource_Setup */
$installer = $this;
$installer->startSetup();

$text = Mage::helper('wsalogger')->getNewVersion() > 10 ? Varien_Db_Ddl_Table::TYPE_TEXT : 'text';

$carrierId = array(
    'type' => $text,
    'length'	=> 20,
    'comment' => 'Shipperhq Carrier ID',
    'nullable' => 'true',
);

$confirmationNo = array(
    'type' => $text,
    'length'	=> 20,
    'comment' => 'Shipperhq Confirmation Number',
    'nullable' => 'true',
);

if(!$installer->getConnection()->tableColumnExists($installer->getTable('sales/order'), 'carrier_id')){
    $installer->getConnection()->addColumn($installer->getTable('sales/order'),'carrier_id', $carrierId );
}

if(!$installer->getConnection()->tableColumnExists($installer->getTable('sales/quote_address'), 'carrier_id')){
    $installer->getConnection()->addColumn($installer->getTable('sales/quote_address'),'carrier_id', $carrierId );
}

if(!$installer->getConnection()->tableColumnExists($installer->getTable('sales/order'), 'confirmation_number')){
    $installer->getConnection()->addColumn($installer->getTable('sales/order'),'confirmation_number', $confirmationNo );
}

$installer->endSetup();
