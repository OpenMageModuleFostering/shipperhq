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

    $dispatchDate =  array(
        'type'    	=> Varien_Db_Ddl_Table::TYPE_TEXT,
        'length'	=> 20,
        'comment' 	=> 'Dispatch Date',
        'nullable' 	=> 'true');

    $deliveryDate =  array(
        'type'    	=>  Varien_Db_Ddl_Table::TYPE_TEXT,
        'length'	=> 20,
        'comment' 	=> 'Expected Delivery',
        'nullable' 	=> 'true');

    if(!$installer->getConnection()->tableColumnExists($installer->getTable('sales/quote_address'), 'dispatch_date')){
        $installer->getConnection()->addColumn($installer->getTable('sales/quote_address'),'dispatch_date', $dispatchDate );
    }
    if(!$installer->getConnection()->tableColumnExists($installer->getTable('sales/quote_address'), 'delivery_date')){
        $installer->getConnection()->addColumn($installer->getTable('sales/quote_address'),'delivery_date', $deliveryDate );
    }
    if(!$installer->getConnection()->tableColumnExists($installer->getTable('sales/quote_address_shipping_rate'), 'dispatch_date')){
        $installer->getConnection()->addColumn($installer->getTable('sales/quote_address_shipping_rate'),'dispatch_date', $dispatchDate );
    }
    if(!$installer->getConnection()->tableColumnExists($installer->getTable('sales/quote_address_shipping_rate'), 'delivery_date')){
        $installer->getConnection()->addColumn($installer->getTable('sales/quote_address_shipping_rate'),'delivery_date', $deliveryDate );
    }
    if(!$installer->getConnection()->tableColumnExists($installer->getTable('sales/order'), 'carrier_type')){
        $installer->getConnection()->addColumn($installer->getTable('sales/order'),'carrier_type', $carrierType );
    }
    if(!$installer->getConnection()->tableColumnExists($installer->getTable('sales/order'), 'dispatch_date')){
        $installer->getConnection()->addColumn($installer->getTable('sales/order'),'dispatch_date', $dispatchDate );
    }
    if(!$installer->getConnection()->tableColumnExists($installer->getTable('sales/order'), 'delivery_date')){
        $installer->getConnection()->addColumn($installer->getTable('sales/order'),'delivery_date', $deliveryDate );
    }

} else  {

    $quoteAddressTable = $installer->getTable('sales/quote_address');
    $quoteRateTable = $installer->getTable('sales/quote_address_shipping_rate');
    $orderTable = $installer->getTable('sales/order');


    if(!$installer->getConnection()->tableColumnExists($quoteAddressTable, 'dispatch_date')){
        $installer->run("
            ALTER IGNORE TABLE {$quoteAddressTable} ADD dispatch_date  text COMMENT 'Shipperhq Dispatch Date';
        ");
    }
    if(!$installer->getConnection()->tableColumnExists($quoteAddressTable, 'delivery_date')){
        $installer->run("
            ALTER IGNORE TABLE {$quoteAddressTable} ADD delivery_date  text COMMENT 'Shipperhq Expected Delivery Date';
        ");
    }

    if(!$installer->getConnection()->tableColumnExists($quoteRateTable,  'dispatch_date')){
        $installer->run("
            ALTER IGNORE TABLE {$quoteRateTable} ADD dispatch_date text COMMENT 'Shipperhq Dispatch Date';
        ");
    }
    if(!$installer->getConnection()->tableColumnExists($quoteRateTable,  'delivery_date')){
        $installer->run("
            ALTER IGNORE TABLE {$quoteRateTable} ADD delivery_date text COMMENT 'Shipperhq Delivery Date';
        ");
    }

    if(!$installer->getConnection()->tableColumnExists($orderTable,  'carrier_type')){
        $installer->run("
            ALTER IGNORE TABLE {$orderTable} ADD carrier_type text COMMENT 'Shipperhq Carrier Type';
        ");
    }
    if(!$installer->getConnection()->tableColumnExists($orderTable,  'dispatch_date')){
        $installer->run("
            ALTER IGNORE TABLE {$orderTable} ADD dispatch_date text COMMENT 'Shipperhq Dispatch Date';
        ");
    }
    if(!$installer->getConnection()->tableColumnExists($orderTable,  'delivery_date')){
        $installer->run("
            ALTER IGNORE TABLE {$orderTable} ADD delivery_date text COMMENT 'Shipperhq Delivery Date';
        ");
    }



}

$installer->endSetup();



