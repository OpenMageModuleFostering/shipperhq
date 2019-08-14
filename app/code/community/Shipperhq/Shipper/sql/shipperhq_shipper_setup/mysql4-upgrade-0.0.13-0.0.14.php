<?php
/* @var $installer Mage_Core_Model_Resource_Setup */
$installer = $this;
$installer->startSetup();

$installer->run("
select @attribute_id:=attribute_id from {$this->getTable('eav_attribute')} where attribute_code='shipperhq_free_shipping';

DELETE FROM {$this->getTable('catalog_eav_attribute')} WHERE attribute_id = @attribute_id;
DELETE FROM {$this->getTable('eav_attribute')} WHERE attribute_code = 'shipperhq_free_shipping';
");


$installer->endSetup();

