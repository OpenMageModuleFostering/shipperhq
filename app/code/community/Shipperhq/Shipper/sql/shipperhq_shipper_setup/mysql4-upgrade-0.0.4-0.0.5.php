<?php
/* @var $installer Mage_Core_Model_Resource_Setup */
$installer = $this;
$installer->startSetup();


$installer->run("
select @entity_type_id:=entity_type_id from {$this->getTable('eav_entity_type')} where entity_type_code='catalog_product';


INSERT ignore into {$installer->getTable('eav_attribute')}
    set entity_type_id 	= @entity_type_id,
	attribute_code 	= 'shipperhq_declared_value',
	backend_type	= 'decimal',
	frontend_input	= 'price',
	is_required	= 0,
	is_user_defined	= 1,
	frontend_label	= 'Declared Value',
	note        = 'The deemed cost of this product for customs & insurance purposes';

select @attribute_id:=attribute_id from {$this->getTable('eav_attribute')} where attribute_code='shipperhq_declared_value';

insert ignore into {$this->getTable('catalog_eav_attribute')}
    set attribute_id 	= @attribute_id,
    	is_visible 	= 1,
    	used_in_product_listing	= 0,
    	is_filterable_in_search	= 0;
");
$installer->endSetup();
