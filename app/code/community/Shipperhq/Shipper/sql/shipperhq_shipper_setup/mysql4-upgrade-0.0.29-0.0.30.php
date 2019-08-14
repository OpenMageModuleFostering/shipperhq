<?php
/* @var $installer Mage_Core_Model_Resource_Setup */
$installer = $this;
$installer->startSetup();
$text = Mage::helper('wsalogger')->getNewVersion() > 10 ? Varien_Db_Ddl_Table::TYPE_TEXT : 'text';

if($installer->getConnection()->tableColumnExists($installer->getTable('sales/order'), 'delivery_date')){
    $installer->getConnection()->modifyColumn(
        $installer->getTable('sales/order'),
        'delivery_date',
        array(
            'type'    	=>  $text,
            'length'	=> 20,
            'comment' 	=> 'Expected Delivery',
            'nullable' 	=> 'true'
        )
    );
}


$installer->endSetup();
