<?php
$installer = $this;
/* @var $installer Mage_Catalog_Model_Resource_Eav_Mysql4_Setup */

$installer->startSetup();
$installer->run("
	ALTER TABLE {$this->getTable('icecatimport/data')} ADD COLUMN `prod_name` VARCHAR(255) AFTER `prod_id`;
	ALTER TABLE {$this->getTable('icecatimport/data')} ADD COLUMN `supplier_id` int(11) AFTER `prod_id`;
	ALTER TABLE {$this->getTable('icecatimport/data')} ADD KEY `supplier_id` (`supplier_id`);
");
$installer->endSetup();
?>