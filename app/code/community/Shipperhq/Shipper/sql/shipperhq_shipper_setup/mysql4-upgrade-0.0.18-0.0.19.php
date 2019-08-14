<?php
/* @var $this Mage_Core_Model_Resource_Setup */
$this->startSetup();
$installer = $this;
$columnOptions = array(
    'TYPE' => Varien_Db_Ddl_Table::TYPE_TEXT,
    'LENGTH' => 50,
    'COMMENT' => 'ShipperHQ Package name',
);
if($this->getConnection()->isTableExists($this->getTable('shipperhq_shipper/quote_packages'))) {

    $quotetable = $this->getTable('shipperhq_shipper/quote_packages');

    if(!$installer->getConnection()->tableColumnExists($quotetable, 'package_name')) {
        $installer->getConnection()->addColumn($quotetable, 'package_name', $columnOptions);
    }
}

if($this->getConnection()->isTableExists($this->getTable('shipperhq_shipper/order_packages'))) {
    $orderTable = $this->getTable('shipperhq_shipper/order_packages');
    if(!$installer->getConnection()->tableColumnExists($orderTable, 'package_name')){
        $installer->getConnection()->addColumn($orderTable, 'package_name', $columnOptions);
    }
}
$this->endSetup();