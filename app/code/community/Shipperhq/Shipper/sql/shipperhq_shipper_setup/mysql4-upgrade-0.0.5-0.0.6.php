<?php
/* @var $installer Mage_Core_Model_Resource_Setup */
$installer = $this;
$installer->startSetup();

if (Mage::helper('wsalogger')->getNewVersion() > 10) {

    $carrierType = array(
        'type' => Varien_Db_Ddl_Table::TYPE_TEXT,
        'comment' => 'Shipperhq Carrier Type',
        'nullable' => 'true',
    );
    if(!$installer->getConnection()->tableColumnExists($installer->getTable('sales/quote_address'), 'carrier_type')){
        $installer->getConnection()->addColumn($installer->getTable('sales/quote_address'), 'carrier_type', $carrierType);
    }
    if(!$installer->getConnection()->tableColumnExists($installer->getTable('sales/quote_address_shipping_rate'), 'carrier_type')){
        $installer->getConnection()->addColumn($installer->getTable('sales/quote_address_shipping_rate'), 'carrier_type', $carrierType);
    }


} else  {

    $quoteAddressTable = $installer->getTable('sales/quote_address');
    $quoteRateTable = $installer->getTable('sales/quote_address_shipping_rate');

    if(!$installer->getConnection()->tableColumnExists($quoteAddressTable, 'carrier_type')){
        $installer->run("
            ALTER IGNORE TABLE {$quoteAddressTable} ADD carrier_type  text COMMENT 'Shipperhq Carrier Type';
        ");
    }

    if(!$installer->getConnection()->tableColumnExists($quoteRateTable,  'carrier_type')){
        $installer->run("
            ALTER IGNORE TABLE {$quoteRateTable} ADD carrier_type text COMMENT 'Shipperhq Carrier Type';
        ");
    }

}

$installer->endSetup();



