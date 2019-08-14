<?php
/* @var $installer Mage_Core_Model_Resource_Setup */
$installer = $this;
$installer->startSetup();

$text = Mage::helper('wsalogger')->getNewVersion() > 10 ? Varien_Db_Ddl_Table::TYPE_TEXT : 'text';

$customDescription = array(
    'type' => $text,
    'comment' => 'Shipperhq Custom Description',
    'nullable' => 'true',
);


if(!$installer->getConnection()->tableColumnExists($installer->getTable('sales/quote_address_shipping_rate'), 'custom_description')){
    $installer->getConnection()->addColumn($installer->getTable('sales/quote_address_shipping_rate'), 'custom_description', $customDescription);
}

$installer->endSetup();
