<?php

/* @var $installer Mage_Core_Model_Resource_Setup */
$installer = $this;

$installer->startSetup();

/* ------ shipperhq_malleable_product -------- */
$this->addAttribute('catalog_product', 'shipperhq_malleable_product', array(
    'type'                     => 'int',
    'input'                    => 'boolean',
    'label'                    => 'Malleable Product',
    'global'                   => false,
    'visible'                  => 1,
    'required'                 => 0,
    'visible_on_front'         => 0,
    'is_html_allowed_on_front' => 0,
    'searchable'               => 0,
    'filterable'               => 0,
    'comparable'               => 0,
    'unique'                   => false,
    'user_defined'			   => true,
    'note'                     => 'Ignore if unsure. Indicates the product dimensions can be adjusted to fit box',
    'used_in_product_listing'  => false
));


$installer->endSetup();