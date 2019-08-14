<?php
/* @var $installer Mage_Core_Model_Resource_Setup */
$installer = $this;
$installer->startSetup();

if (Mage::helper('wsalogger')->getNewVersion() > 10) {

    $carrierNotice = array(
        'type' => Varien_Db_Ddl_Table::TYPE_TEXT,
        'comment' => 'Shipperhq Carrier Notice',
        'nullable' => 'true',
    );
    if(!$installer->getConnection()->tableColumnExists($installer->getTable('sales/quote_address_shipping_rate'), 'carrier_notice')){
        $installer->getConnection()->addColumn($installer->getTable('sales/quote_address_shipping_rate'), 'carrier_notice', $carrierNotice);
    }


} else  {

    $quoteRateTable = $installer->getTable('sales/quote_address_shipping_rate');

   if(!$installer->getConnection()->tableColumnExists($quoteRateTable,  'carrier_notice')){
        $installer->run("
            ALTER IGNORE TABLE {$quoteRateTable} ADD carrier_notice text COMMENT 'Shipperhq Carrier Notice';
        ");
    }

}

$installer->endSetup();



