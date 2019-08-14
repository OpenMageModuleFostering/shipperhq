<?php
/* @var $installer Mage_Core_Model_Resource_Setup */
$installer = $this;
$installer->startSetup();

$installer->run("

select @entity_type_id:=entity_type_id from {$this->getTable('eav_entity_type')} where entity_type_code='catalog_product';

/*Must Ship Freight*/
insert ignore into {$this->getTable('eav_attribute')}
    set entity_type_id 	= @entity_type_id,
	attribute_code 	= 'must_ship_freight',
	backend_type	= 'int',
	frontend_input	= 'boolean',
	is_required	= 0,
	is_user_defined	= 1,
	frontend_label	= 'Must ship freight';

select @attribute_id:=attribute_id from {$this->getTable('eav_attribute')} where attribute_code='must_ship_freight';

insert ignore into {$this->getTable('catalog_eav_attribute')}
	set attribute_id 	= @attribute_id,
	is_visible 	= 1,
	used_in_product_listing	= 0,
    is_filterable_in_search	= 0;

");
/*freight class attribute code change*/

if(!$installer->getAttribute('catalog_product', 'freight_class')) {
    $installer->updateAttribute('catalog_product', 'shipperhq_freight_class', array('attribute_code' => 'freight_class'));
}
else {
    $installer->removeAttribute('catalog_product', 'shipperhq_freight_class');
}

$entityTypeId = $installer->getEntityTypeId('catalog_product');

$attributeSetArr = $installer->getConnection()->fetchAll("SELECT attribute_set_id FROM {$this->getTable('eav_attribute_set')} WHERE entity_type_id={$entityTypeId}");

$freightAttributeCodes = array(
    'freight_class' => '1',
    'must_ship_freight' =>'10'
);

foreach ($attributeSetArr as $attr) {
    $attributeSetId = $attr['attribute_set_id'];

    $installer->addAttributeGroup($entityTypeId, $attributeSetId, 'Freight Shipping', '101');

    $freightAttGroupId = $installer->getAttributeGroupId($entityTypeId, $attributeSetId, 'Freight Shipping');

    foreach($freightAttributeCodes as $code => $sort) {
        $attributeId = $installer->getAttributeId($entityTypeId, $code);
        $installer->addAttributeToGroup($entityTypeId, $attributeSetId, $freightAttGroupId, $attributeId, $sort);
    }
};

$installer->endSetup();
