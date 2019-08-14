<?php
/* @var $installer Mage_Core_Model_Resource_Setup */
$installer = $this;
$installer->startSetup();

$entityTypeId = $installer->getEntityTypeId('catalog_product');

$attributeSetArr = $installer->getAllAttributeSetIds($entityTypeId);

$dimAttributeCodes = array('ship_separately' => '2',
    'shipperhq_dim_group' => '1',
    'ship_length' => '10',
    'ship_width' => '11',
    'ship_height' => '12',
    'shipperhq_poss_boxes' => '20',
);

foreach ($attributeSetArr as $attributeSetId) {
    $installer->addAttributeGroup($entityTypeId, $attributeSetId, 'Dimensional Shipping', '100');

    $dimAttributeGroupId = $installer->getAttributeGroupId($entityTypeId, $attributeSetId, 'Dimensional Shipping');

    foreach($dimAttributeCodes as $code => $sort) {
        $attributeId = $installer->getAttributeId($entityTypeId, $code);
        $installer->addAttributeToGroup($entityTypeId, $attributeSetId, $dimAttributeGroupId, $attributeId, $sort);
    }

};

if(!$installer->getConnection()->isTableExists($this->getTable('shipperhq_shipper/quote_packages'))) {
    $table = $this->getConnection()->newTable($this->getTable('shipperhq_shipper/quote_packages'));

    $table
        ->addColumn('package_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
            'primary' => true,
            'nullable' => false,
            'unsigned' => true,
            'auto_increment' => true
        ))
        ->addColumn('address_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
            'nullable' => false,
            'unsigned' => true
        ))
        ->addColumn('carrier_group_id', Varien_Db_Ddl_Table::TYPE_VARCHAR, null, array(
            'nullable' => false,
        ))
        ->addColumn('carrier_code', Varien_Db_Ddl_Table::TYPE_VARCHAR, null, array(
            'nullable' => false,
        ))
        ->addColumn('length', Varien_Db_Ddl_Table::TYPE_FLOAT , array(
            'nullable' => true
        ))
        ->addColumn('width', Varien_Db_Ddl_Table::TYPE_FLOAT , array(
            'nullable' => true
        ))
        ->addColumn('height', Varien_Db_Ddl_Table::TYPE_FLOAT , array(
            'nullable' => true
        ))
        ->addColumn('weight', Varien_Db_Ddl_Table::TYPE_FLOAT , array(
            'nullable' => true
        ))
        ->addColumn('declared_value', Varien_Db_Ddl_Table::TYPE_FLOAT , array(
            'nullable' => true
        ))
        ->addColumn('surcharge_price', Varien_Db_Ddl_Table::TYPE_FLOAT , array(
            'nullable' => true
        ))
        ->addForeignKey(
            $this->getFkName('shipperhq_shipper/quote_packages', 'address_id', 'sales/quote_address', 'address_id'),
            'address_id',
            $this->getTable('sales/quote_address'),
            'address_id',
            Varien_Db_Adapter_Interface::FK_ACTION_CASCADE
        )
    ;
    $this->getConnection()->createTable($table);
}

if(!$installer->getConnection()->isTableExists($this->getTable('shipperhq_shipper/quote_package_items'))) {
    $table = $this->getConnection()->newTable($this->getTable('shipperhq_shipper/quote_package_items'));

    $table
        ->addColumn('package_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
            'nullable' => false,
        ))
        ->addColumn('sku', Varien_Db_Ddl_Table::TYPE_VARCHAR, null, array(
            'nullable' => false,
        ))
        ->addColumn('qty_packed', Varien_Db_Ddl_Table::TYPE_FLOAT , array(
            'nullable' => true
        ))
        ->addColumn('weight_packed', Varien_Db_Ddl_Table::TYPE_FLOAT , array(
            'nullable' => true
        ))
        ->addForeignKey(
            $this->getFkName('shipperhq_shipper/quote_package_items', 'package_id', 'shipperhq_shipper/quote_packages', 'package_id'),
            'package_id',
            $this->getTable('shipperhq_shipper/quote_packages'),
            'package_id',
            Varien_Db_Adapter_Interface::FK_ACTION_CASCADE
        )
    ;
    $this->getConnection()->createTable($table);
}


if(!$installer->getConnection()->isTableExists($this->getTable('shipperhq_shipper/order_packages'))) {
    $table = $this->getConnection()->newTable($this->getTable('shipperhq_shipper/order_packages'));

    $table
        ->addColumn('package_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
            'primary' => true,
            'nullable' => false,
            'unsigned' => true,
            'auto_increment' => true
        ))
        ->addColumn('order_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
            'nullable' => false,
            'unsigned' => true
        ))
        ->addColumn('carrier_group_id', Varien_Db_Ddl_Table::TYPE_VARCHAR, null, array(
            'nullable' => false,
        ))
        ->addColumn('length', Varien_Db_Ddl_Table::TYPE_FLOAT , array(
            'nullable' => true
        ))
        ->addColumn('width', Varien_Db_Ddl_Table::TYPE_FLOAT , array(
            'nullable' => true
        ))
        ->addColumn('height', Varien_Db_Ddl_Table::TYPE_FLOAT , array(
            'nullable' => true
        ))
        ->addColumn('weight', Varien_Db_Ddl_Table::TYPE_FLOAT , array(
            'nullable' => true
        ))
        ->addColumn('declared_value', Varien_Db_Ddl_Table::TYPE_FLOAT , array(
            'nullable' => true
        ))
        ->addColumn('surcharge_price', Varien_Db_Ddl_Table::TYPE_FLOAT , array(
            'nullable' => true
        ))
        ->addForeignKey(
            $this->getFkName('shipperhq_shipper/order_packages', 'order_id', 'sales/order', 'entity_id'),
            'order_id',
            $this->getTable('sales/order'),
            'entity_id',
            Varien_Db_Adapter_Interface::FK_ACTION_CASCADE
        )
    ;
    $this->getConnection()->createTable($table);
}

if(!$installer->getConnection()->isTableExists($this->getTable('shipperhq_shipper/order_package_items'))) {
    $table = $this->getConnection()->newTable($this->getTable('shipperhq_shipper/order_package_items'));

    $table
        ->addColumn('package_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
            'nullable' => false,
        ))
        ->addColumn('sku', Varien_Db_Ddl_Table::TYPE_VARCHAR, null, array(
            'nullable' => false,
        ))
        ->addColumn('qty_packed', Varien_Db_Ddl_Table::TYPE_FLOAT , array(
            'nullable' => true
        ))
        ->addColumn('weight_packed', Varien_Db_Ddl_Table::TYPE_FLOAT , array(
            'nullable' => true
        ))
        ->addForeignKey(
            $this->getFkName('shipperhq_shipper/order_package_items', 'package_id', 'shipperhq_shipper/order_packages', 'package_id'),
            'package_id',
            $this->getTable('shipperhq_shipper/order_packages'),
            'package_id',
            Varien_Db_Adapter_Interface::FK_ACTION_CASCADE
        )
    ;
    $this->getConnection()->createTable($table);
}

$installer->endSetup();
