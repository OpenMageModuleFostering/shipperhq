<?php
/* @var $this Mage_Core_Model_Resource_Setup */ 
$this->startSetup();
if(!$this->getConnection()->isTableExists($this->getTable('shipperhq_shipper/storage'))) {

    $table = $this->getConnection()->newTable($this->getTable('shipperhq_shipper/storage'));

    $table
        ->addColumn('quote_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
            'primary' => true,
            'nullable' => false,
            'unsigned' => true
        ))
        ->addColumn('data', Varien_Db_Ddl_Table::TYPE_TEXT, '512k', array('nullable' => false))
        ->addForeignKey(
            $this->getFkName('shipperhq_shipper/storage', 'quote_id', 'sales/quote', 'entity_id'),
            'quote_id',
            $this->getTable('sales/quote'),
            'entity_id',
            Varien_Db_Adapter_Interface::FK_ACTION_CASCADE
        )
    ;
    $this->getConnection()->createTable($table);
}
$this->endSetup();