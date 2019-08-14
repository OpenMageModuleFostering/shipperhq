<?php
/* @var $installer Mage_Core_Model_Resource_Setup */
$installer = $this;
$installer->startSetup();


    $pbOrderTable = $installer->getTable('shipperhq_pbint/ordernumber');

    if(!$installer->getConnection()->tableColumnExists($pbOrderTable, 'hub_city')){
        $text = Mage::helper('wsalogger')->getNewVersion() > 10 ? Varien_Db_Ddl_Table::TYPE_TEXT : 'text';

        $hubCity = array(
            'type' => $text,
            'length'	=> 50,
            'comment' => 'Hub city',
            'nullable' => 'true',
        );
        $installer->getConnection()
            ->addColumn(
                $pbOrderTable,
                'hub_city',
                $hubCity
            );
    }

$installer->endSetup();



