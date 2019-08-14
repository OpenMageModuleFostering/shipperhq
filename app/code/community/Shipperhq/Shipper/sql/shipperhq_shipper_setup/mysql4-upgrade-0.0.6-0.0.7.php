<?php
/* @var $installer Mage_Core_Model_Resource_Setup */
$installer = $this;
$installer->startSetup();
if (Mage::helper('wsalogger')->getNewVersion() > 10) {


    $isCheckout = array(
        'type'  => Varien_Db_Ddl_Table::TYPE_SMALLINT,
        'comment' => 'Shipperhq Shipper',
        'nullable' => 'false',
        'default'  => '0'
    );
    if(!$installer->getConnection()->tableColumnExists($installer->getTable('sales/quote_address'), 'is_checkout')){
        $installer->getConnection()->addColumn($installer->getTable('sales/quote_address'), 'is_checkout', $isCheckout);
    }

} else  {

    $quoteAddressTable = $installer->getTable('sales/quote_address');

    if(!$installer->getConnection()->tableColumnExists($quoteAddressTable, 'is_checkout')){
        $installer->run("
            ALTER IGNORE TABLE {$quoteAddressTable} ADD is_checkout  smallint(1) unsigned NOT NULL default '0' COMMENT 'Shipperhq Shipper';
        ");
    }

}

$installer->endSetup();



