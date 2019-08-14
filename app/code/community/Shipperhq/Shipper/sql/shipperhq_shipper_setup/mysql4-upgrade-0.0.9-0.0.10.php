<?php
/* @var $installer Mage_Core_Model_Resource_Setup */
$installer = $this;
$installer->startSetup();

$entityTypeId = $installer->getEntityTypeId('catalog_product');

$attributeSetArr = $installer->getConnection()->fetchAll("SELECT attribute_set_id FROM {$this->getTable('eav_attribute_set')} WHERE entity_type_id={$entityTypeId}");

$dimAttributeCodes = array('ship_separately' => '2',
    'shipperhq_dim_group' => '1',
    'ship_length' => '10',
    'ship_width' => '11',
    'ship_height' => '12',
    'shipperhq_poss_boxes' => '20',
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



