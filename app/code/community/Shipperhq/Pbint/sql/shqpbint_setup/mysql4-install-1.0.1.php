<?php

$installer = $this;

$installer->startSetup();

$installer->run("

					DROP TABLE IF EXISTS {$this->getTable('shipperhq_pbint/variable')};
					CREATE TABLE {$this->getTable('shipperhq_pbint/variable')} (
					`variable_id` int(11) unsigned NOT NULL auto_increment,
					`name` varchar(255) NOT NULL default '',
					`value` varchar(255) NOT NULL default '',
					PRIMARY KEY (`variable_id`)
					) ENGINE=InnoDB DEFAULT CHARSET=utf8;

					DROP TABLE IF EXISTS {$this->getTable('shipperhq_pbint/ordernumber')};
					CREATE TABLE {$this->getTable('shipperhq_pbint/ordernumber')} (
					`ordernumber_id` int(11) unsigned NOT NULL auto_increment,
					`cp_order_number` varchar(255) NOT NULL default '',
					`mage_order_number` varchar(255) NOT NULL default '',
					`confirmed` tinyint NOT NULL default 0,
					`referenced` tinyint NOT NULL default 0,
					`hub_id` varchar(50) null,
					`hub_street1` varchar(50) null,
					`hub_street2` varchar(50) null,
				    `hub_city` varchar(50) null,
					`hub_province_or_state` varchar(50) null,
					`hub_postcode` varchar(50) null,
					`hub_country` varchar(50) null,
					PRIMARY KEY (`ordernumber_id`)
					) ENGINE=InnoDB DEFAULT CHARSET=utf8;

					DROP TABLE IF EXISTS {$this->getTable('shipperhq_pbint/inboundparcel')};
					CREATE TABLE {$this->getTable('shipperhq_pbint/inboundparcel')} (
					`inbound_parcel_id` int(11) unsigned NOT NULL auto_increment,
					`inbound_parcel` varchar(255) NOT NULL default '',
					`mage_order_number` varchar(255) NOT NULL default '',
					`pb_order_number` varchar(255) NOT NULL default '',
					PRIMARY KEY (`inbound_parcel_id`)
					) ENGINE=InnoDB DEFAULT CHARSET=utf8;

					");

$eav = Mage::getResourceModel('catalog/setup', 'catalog_setup');//new Mage_Eav_Model_Entity_Setup('catalog_setup');
//$eav->removeAttribute('catalog_product','shipperhq_pbint_upload');
$eav->addAttribute('catalog_product', 'shipperhq_pbint_upload', array(
    'type' => 'int',
    'input' => 'text',
    'label' => 'Last PBGSP upload timestampt',
    'global' => 2,
    'user_defined' => 0,
    'required' => 0,
    'visible' => 1,
    'default' => 0

));


$installer->addAttribute('quote_address', 'shq_pb_duty_amount', array('type'=>'decimal'));
$installer->addAttribute('quote_address', 'base_shq_pb_duty_amount', array('type'=>'decimal'));

$installer->addAttribute('order', 'shq_pb_duty_amount', array('type'=>'decimal'));
$installer->addAttribute('order', 'base_shq_pb_duty_amount', array('type'=>'decimal'));

$installer->addAttribute('invoice', 'shq_pb_duty_amount', array('type'=>'decimal'));
$installer->addAttribute('invoice', 'base_shq_pb_duty_amount', array('type'=>'decimal'));

$installer->addAttribute('creditmemo', 'shq_pb_duty_amount', array('type'=>'decimal'));
$installer->addAttribute('creditmemo', 'base_shq_pb_duty_amount', array('type'=>'decimal'));

$installer->endSetup();
?>
