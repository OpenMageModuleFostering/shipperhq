<?php

/* @var $installer Mage_Core_Model_Resource_Setup */
$installer = $this;

$installer->startSetup();
$version = Mage::helper('wsalogger')->getNewVersion();

$carriergroupAttr = array(
    'type' => $version > 10 ? Varien_Db_Ddl_Table::TYPE_TEXT : Varien_Db_Ddl_Table::TYPE_VARCHAR,
    'comment' => 'Carrier Group',
    'nullable' => 'true',
);

$carriergroupID  = array(
    'type' => $version > 10 ? Varien_Db_Ddl_Table::TYPE_TEXT : Varien_Db_Ddl_Table::TYPE_VARCHAR,
    'comment' => 'Carrier Group ID',
    'nullable' => 'true',
);

$carriergroupDetails = array(
    'type' => $version > 10 ? Varien_Db_Ddl_Table::TYPE_TEXT : Varien_Db_Ddl_Table::TYPE_VARCHAR,
    'comment' => 'Carrier Group Details',
    'nullable' => 'true',
);

$carriergroupHtml = array(
    'type' => $version > 10 ? Varien_Db_Ddl_Table::TYPE_TEXT : Varien_Db_Ddl_Table::TYPE_VARCHAR,
    'comment' => 'Carrier Group Html',
    'nullable' => 'true',
);

$displayMerged = array(
    'type'  => Varien_Db_Ddl_Table::TYPE_SMALLINT,
    'comment' => 'Checkout display type',
    'nullable' => 'false',
    'default'  => '1'
);

$splitRates = array(
    'type'  => Varien_Db_Ddl_Table::TYPE_SMALLINT,
    'comment' => 'Shipperhq Split Rates',
    'nullable' => 'false',
    'default'  => '0'
);

$carriergroupShipping = array(
    'type' => $version > 10 ? Varien_Db_Ddl_Table::TYPE_TEXT : Varien_Db_Ddl_Table::TYPE_VARCHAR,
    'comment' => 'Shipping Description',
    'nullable' => 'true',
);

if(!$installer->getConnection()->tableColumnExists($installer->getTable('sales/quote_address'), 'carriergroup_shipping_details')){
    $installer->getConnection()->addColumn($installer->getTable('sales/quote_address'), 'carriergroup_shipping_details', $carriergroupDetails);
}
if(!$installer->getConnection()->tableColumnExists($installer->getTable('sales/quote_address'), 'carriergroup_shipping_html')){
    $installer->getConnection()->addColumn($installer->getTable('sales/quote_address'), 'carriergroup_shipping_html', $carriergroupHtml);
}
if(!$installer->getConnection()->tableColumnExists($installer->getTable('sales/quote_address'), 'checkout_display_merged')){
    $installer->getConnection()->addColumn($installer->getTable('sales/quote_address'), 'checkout_display_merged', $displayMerged);
}
if(!$installer->getConnection()->tableColumnExists($installer->getTable('sales/quote_address'), 'split_rates')){
    $installer->getConnection()->addColumn($installer->getTable('sales/quote_address'), 'split_rates', $splitRates);
}

if(!$installer->getConnection()->tableColumnExists($installer->getTable('sales/quote_address_shipping_rate'), 'carriergroup_id')){
    $installer->getConnection()->addColumn($installer->getTable('sales/quote_address_shipping_rate'), 'carriergroup_id', $carriergroupID);
}
if(!$installer->getConnection()->tableColumnExists($installer->getTable('sales/quote_address_shipping_rate'), 'carriergroup')){
    $installer->getConnection()->addColumn($installer->getTable('sales/quote_address_shipping_rate'), 'carriergroup', $carriergroupAttr);
}
if(!$installer->getConnection()->tableColumnExists($installer->getTable('sales/quote_address_shipping_rate'), 'carriergroup_shipping_details')){
    $installer->getConnection()->addColumn($installer->getTable('sales/quote_address_shipping_rate'), 'carriergroup_shipping_details', $carriergroupDetails);
}

if(!$installer->getConnection()->tableColumnExists($installer->getTable('sales/quote_item'), 'carriergroup')){
    $installer->getConnection()->addColumn($installer->getTable('sales/quote_item'), 'carriergroup', $carriergroupAttr);
}
if(!$installer->getConnection()->tableColumnExists($installer->getTable('sales/order_item'), 'carriergroup')){
    $installer->getConnection()->addColumn($installer->getTable('sales/order_item'), 'carriergroup', $carriergroupAttr);
}
if(!$installer->getConnection()->tableColumnExists($installer->getTable('sales/quote_item'), 'carriergroup_id')){
    $installer->getConnection()->addColumn($installer->getTable('sales/quote_item'), 'carriergroup_id', $carriergroupID);
}
if(!$installer->getConnection()->tableColumnExists($installer->getTable('sales/order_item'), 'carriergroup_id')){
    $installer->getConnection()->addColumn($installer->getTable('sales/order_item'), 'carriergroup_id', $carriergroupID);
}
if(!$installer->getConnection()->tableColumnExists($installer->getTable('sales/quote_item'), 'carriergroup_shipping')){
    $installer->getConnection()->addColumn($installer->getTable('sales/quote_item'), 'carriergroup_shipping', $carriergroupShipping);
}
if(!$installer->getConnection()->tableColumnExists($installer->getTable('sales/order_item'), 'carriergroup_shipping')){
    $installer->getConnection()->addColumn($installer->getTable('sales/order_item'), 'carriergroup_shipping', $carriergroupShipping);
}
if(!$installer->getConnection()->tableColumnExists($installer->getTable('sales/quote_address_item'), 'carriergroup_shipping')){
    $installer->getConnection()->addColumn($installer->getTable('sales/quote_address_item'), 'carriergroup_shipping', $carriergroupShipping);
}
if(!$installer->getConnection()->tableColumnExists($installer->getTable('sales/quote_address_item'), 'carriergroup')){
    $installer->getConnection()->addColumn($installer->getTable('sales/quote_address_item'), 'carriergroup', $carriergroupAttr);
}
if(!$installer->getConnection()->tableColumnExists($installer->getTable('sales/quote_address_item'), 'carriergroup_id')){
    $installer->getConnection()->addColumn($installer->getTable('sales/quote_address_item'), 'carriergroup_id', $carriergroupID);
}


if (Mage::helper('wsalogger')->getNewVersion() >= 8) {
    if(!$installer->getConnection()->tableColumnExists($installer->getTable('sales/shipment_grid'), 'carriergroup')){
        $installer->getConnection()->addColumn($installer->getTable('sales/shipment_grid'),'carriergroup',$carriergroupAttr);
    }
    if(!$installer->getConnection()->tableColumnExists($installer->getTable('sales/shipment'), 'carriergroup')){
        $installer->getConnection()->addColumn($installer->getTable('sales/shipment'),'carriergroup',$carriergroupAttr);
    }
    if(!$installer->getConnection()->tableColumnExists($installer->getTable('sales/shipment'), 'shipping_description')){
        $installer->getConnection()->addColumn($installer->getTable('sales/shipment'),'shipping_description',array(
            'type'    	=> Varien_Db_Ddl_Table::TYPE_TEXT,
            'length'	=> 255,
            'comment' 	=> 'Shipping Description',
            'nullable' 	=> 'true',));
    }
    if(!$installer->getConnection()->tableColumnExists($installer->getTable('sales/order'), 'carriergroup_shipping_html')){
        $installer->getConnection()->addColumn($installer->getTable('sales/order'), 'carriergroup_shipping_html', $carriergroupHtml);
    }
    if(!$installer->getConnection()->tableColumnExists($installer->getTable('sales/order'), 'carriergroup_shipping_details')){
        $installer->getConnection()->addColumn($installer->getTable('sales/order'), 'carriergroup_shipping_details', $carriergroupDetails);
    }
    if  (Mage::helper('wsalogger')->isEnterpriseEdition() && $installer->tableExists('enterprise_sales_shipment_grid_archive')) {
        if(!$installer->getConnection()->tableColumnExists($installer->getTable('enterprise_sales_shipment_grid_archive'), 'carriergroup')){
            $installer->getConnection()->addColumn($installer->getTable('enterprise_sales_shipment_grid_archive'),'carriergroup',$carriergroupAttr);
        }
    }
}

if($installer->getAttribute('catalog_product', 'shipperhq_warehouse')) {
    $installer->updateAttribute('catalog_product', 'shipperhq_warehouse', array('is_user_defined' => false));
}
if($installer->getAttribute('catalog_product', 'shipperhq_shipping_group')) {
    $installer->updateAttribute('catalog_product', 'shipperhq_shipping_group', array('is_user_defined' => false));
}

$installer->endSetup();