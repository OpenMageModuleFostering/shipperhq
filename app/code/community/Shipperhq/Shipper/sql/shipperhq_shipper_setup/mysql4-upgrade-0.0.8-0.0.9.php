<?php
/* @var $installer Mage_Core_Model_Resource_Setup */
$installer = $this;
$installer->startSetup();

$installer->run("

select @entity_type_id:=entity_type_id from {$this->getTable('eav_entity_type')} where entity_type_code='catalog_product';

/*Ship Separately*/
insert ignore into {$this->getTable('eav_attribute')}
    set entity_type_id 	= @entity_type_id,
    	attribute_code 	= 'ship_separately',
    	backend_type	= 'int',
    	frontend_input	= 'boolean',
      	is_user_defined	= 1,
	   	is_required	= 0,
    	frontend_label	= 'Ship Separately';

select @attribute_id:=attribute_id from {$this->getTable('eav_attribute')} where attribute_code='ship_separately';

insert ignore into {$this->getTable('catalog_eav_attribute')}
    set attribute_id 	= @attribute_id,
    	is_visible 	= 1,
    	used_in_product_listing	= 0,
    	is_filterable_in_search	= 0;

/*Dimensional Group*/
insert ignore into {$this->getTable('eav_attribute')}
    set entity_type_id 	= @entity_type_id,
    	attribute_code 	= 'shipperhq_dim_group',
    	backend_type	= 'varchar',
    	frontend_input	= 'select',
    	backend_model = 'eav/entity_attribute_backend_array',
      	is_user_defined	= 1,
	   	is_required	= 0,
    	frontend_label	= 'ShipperHQ Dimensional Rule Group';

select @attribute_id:=attribute_id from {$this->getTable('eav_attribute')} where attribute_code='shipperhq_dim_group';

insert ignore into {$this->getTable('catalog_eav_attribute')}
    set attribute_id 	= @attribute_id,
    	is_visible 	= 1,
    	used_in_product_listing	= 0,
    	is_filterable_in_search	= 0;

/*Dimensional Ship Length*/
insert ignore into {$this->getTable('eav_attribute')}
	    set entity_type_id 	= @entity_type_id,
	    	attribute_code 	= 'ship_length',
	    	backend_type	= 'decimal',
	    	frontend_input	= 'text',
	    	is_required	= 0,
	    	is_user_defined	= 1,
	    	frontend_label	= 'Dimension Length';

select @attribute_id:=attribute_id from {$this->getTable('eav_attribute')} where attribute_code='ship_length';

insert ignore into {$this->getTable('catalog_eav_attribute')}
	    set attribute_id 	= @attribute_id,
	    	is_visible 	= 1,
	    	used_in_product_listing	= 0,
	    	is_filterable_in_search	= 0;


/*Dimensional Ship Width*/
insert ignore into {$this->getTable('eav_attribute')}
	    set entity_type_id 	= @entity_type_id,
			attribute_code 	= 'ship_width',
			backend_type	= 'decimal',
			frontend_input	= 'text',
			is_required	= 0,
			is_user_defined	= 1,
			frontend_label	= 'Dimension Width';

select @attribute_id:=attribute_id from {$this->getTable('eav_attribute')} where attribute_code='ship_width';

insert ignore into {$this->getTable('catalog_eav_attribute')}
    set attribute_id 	= @attribute_id,
        is_visible 	= 1,
        used_in_product_listing	= 0,
        is_filterable_in_search	= 0;

/*Dimensional Ship Height*/
insert ignore into {$this->getTable('eav_attribute')}
    set entity_type_id 	= @entity_type_id,
        attribute_code 	= 'ship_height',
        backend_type	= 'decimal',
        frontend_input	= 'text',
        is_required	= 0,
        is_user_defined	= 1,
        frontend_label	= 'Dimension Height';

select @attribute_id:=attribute_id from {$this->getTable('eav_attribute')} where attribute_code='ship_height';

insert ignore into {$this->getTable('catalog_eav_attribute')}
	    set attribute_id 	= @attribute_id,
	    	is_visible 	= 1,
	    	used_in_product_listing	= 0,
	    	is_filterable_in_search	= 0;

/*Dimensional Possible Ship Boxes*/
insert ignore into {$this->getTable('eav_attribute')}
	    set entity_type_id  = @entity_type_id,
  		attribute_code  = 'shipperhq_poss_boxes',
  		backend_type    = 'varchar',
  		backend_model   = 'eav/entity_attribute_backend_array',
  		frontend_input  = 'multiselect',
	  	is_required     = 0,
       	is_user_defined	= 1,
 	    frontend_label  = 'Possible Packing Boxes';

select @attribute_id:=attribute_id from {$this->getTable('eav_attribute')} where attribute_code='shipperhq_poss_boxes';

	insert ignore into {$this->getTable('catalog_eav_attribute')}
	    set attribute_id    = @attribute_id,
  		is_visible      = 1,
  		used_in_product_listing = 0,
  		is_filterable_in_search = 0;

");

$entityTypeId = $installer->getEntityTypeId('catalog_product');

$attributeSetArr = $installer->getConnection()->fetchAll("SELECT attribute_set_id FROM {$this->getTable('eav_attribute_set')} WHERE entity_type_id={$entityTypeId}");

$dimAttributeCodes = array('ship_separately' => '2',
    'shipperhq_dim_group' => '1',
    'ship_length' => '10',
    'ship_width' => '11',
    'ship_height' => '12',
    'shipperhq_poss_boxes' => '20',
    'shipperhq_declared_value' => '21'
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



