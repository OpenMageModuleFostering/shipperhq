<?php
/* @var $installer Mage_Core_Model_Resource_Setup */
$installer = $this;
$installer->startSetup();

$installer->run("

select @entity_type_id:=entity_type_id from {$this->getTable('eav_entity_type')} where entity_type_code='catalog_product';

/*shipping group as scope store */
select @attribute_id:=attribute_id from {$this->getTable('eav_attribute')} where attribute_code='shipperhq_shipping_group';

UPDATE {$this->getTable('catalog_eav_attribute')} SET
        is_global = 0
WHERE {$this->getTable('catalog_eav_attribute')}.attribute_id = @attribute_id;

/*shipping fee as scope store */
select @attribute_id:=attribute_id from {$this->getTable('eav_attribute')} where attribute_code='shipperhq_shipping_fee';

UPDATE {$this->getTable('catalog_eav_attribute')} SET
        is_global = 0
WHERE {$this->getTable('catalog_eav_attribute')}.attribute_id = @attribute_id;

/*origin as scope store */
select @attribute_id:=attribute_id from {$this->getTable('eav_attribute')} where attribute_code='shipperhq_warehouse';

UPDATE {$this->getTable('catalog_eav_attribute')} SET
        is_global = 0
WHERE {$this->getTable('catalog_eav_attribute')}.attribute_id = @attribute_id;


/*Dimensional Ship Box Tolerance */
insert ignore into {$this->getTable('eav_attribute')}
    set entity_type_id 	= @entity_type_id,
    	attribute_code 	= 'ship_box_tolerance',
    	backend_type	= 'int',
    	frontend_input	= 'text',
      	is_user_defined	= 1,
	   	is_required	= 0,
        note           =  'Note: Ignore if unsure',
   	    frontend_label	= 'Box Tolerance';

select @attribute_id:=attribute_id from {$this->getTable('eav_attribute')} where attribute_code='ship_box_tolerance';

insert ignore into {$this->getTable('catalog_eav_attribute')}
    set attribute_id 	= @attribute_id,
    	is_visible 	= 1,
    	used_in_product_listing	= 0,
    	is_filterable_in_search	= 0;

");

$entityTypeId = $installer->getEntityTypeId('catalog_product');

$attributeSetArr = $installer->getConnection()->fetchAll("SELECT attribute_set_id FROM {$this->getTable('eav_attribute_set')} WHERE entity_type_id={$entityTypeId}");

$dimAttributeCodes = array('ship_separately' => '2',
    'shipperhq_dim_group' => '1',
    'ship_length' => '10',
    'ship_width' => '11',
    'ship_height' => '12',
    'shipperhq_poss_boxes' => '20',
    'ship_box_tolerance' => '22',
    'shipperhq_declared_value' => '25'
);

foreach ($attributeSetArr as $attr) {
    $attributeSetId = $attr['attribute_set_id'];

    $installer->addAttributeGroup($entityTypeId, $attributeSetId, 'Dimensional Shipping', '100');

    $dimAttributeGroupId = $installer->getAttributeGroupId($entityTypeId, $attributeSetId, 'Dimensional Shipping');

    foreach($dimAttributeCodes as $code => $sort) {
        $attributeId = $installer->getAttributeId($entityTypeId, $code);
        $installer->addAttributeToGroup($entityTypeId, $attributeSetId, $dimAttributeGroupId, $attributeId, $sort);
    }
};

$installer->endSetup();
