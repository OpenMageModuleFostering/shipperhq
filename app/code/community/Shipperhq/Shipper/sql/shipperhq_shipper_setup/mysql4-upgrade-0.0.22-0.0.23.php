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

$customDuties = array(
    'type'    => Varien_Db_Ddl_Table::TYPE_DECIMAL,
    'scale'     => 4,
    'precision' => 12,
    'comment' => 'Shipperhq Custom Duties',
);

if(!$installer->getConnection()->tableColumnExists($installer->getTable('sales/quote_address_shipping_rate'), 'carrier_id')){
    $installer->getConnection()->addColumn($installer->getTable('sales/quote_address_shipping_rate'), 'carrier_id', $carrierId);
}

if(!$installer->getConnection()->tableColumnExists($installer->getTable('sales/quote_address_shipping_rate'), 'custom_duties')){
    $installer->getConnection()->addColumn($installer->getTable('sales/quote_address_shipping_rate'), 'custom_duties', $customDuties);
}

$installer->endSetup();
