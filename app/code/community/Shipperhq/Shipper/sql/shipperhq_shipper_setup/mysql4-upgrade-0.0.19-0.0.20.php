<?php
/* @var $installer Mage_Core_Model_Resource_Setup */
$installer = $this;
$installer->startSetup();

$entityTypeId = $installer->getEntityTypeId('catalog_product');

$attributeSetArr = $installer->getAllAttributeSetIds($entityTypeId);

if(Mage::helper('shipperhq_shipper')->isModuleEnabled('Shipperhq_Splitrates')) {
    $stdAttributeCodes = array('shipperhq_shipping_group' => '1', 'shipperhq_warehouse' => '10');
}
else {
    $stdAttributeCodes = array('shipperhq_shipping_group' => '1');
}
foreach ($attributeSetArr as $attributeSetId) {
    $installer->addAttributeGroup($entityTypeId, $attributeSetId, 'Shipping', '99');

    $attributeGroupId = $installer->getAttributeGroupId($entityTypeId, $attributeSetId, 'Shipping');

    foreach($stdAttributeCodes as $code => $sort) {
        $attributeId = $installer->getAttributeId($entityTypeId, $code);
        $installer->addAttributeToGroup($entityTypeId, $attributeSetId, $attributeGroupId, $attributeId, $sort);
    }
};

$installer->endSetup();
