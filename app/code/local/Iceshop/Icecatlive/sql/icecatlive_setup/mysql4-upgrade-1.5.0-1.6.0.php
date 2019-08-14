<?php
include_once 'uninstall-old-version.php';
$unistaller_old_version = new Uninstall_Bintime_Icecatlive();
$unistaller_old_version->uninstall();
$installer = $this;
/* @var $installer Mage_Catalog_Model_Resource_Eav_Mysql4_Setup */

$installer->startSetup();

$installer->run("
	DROP TABLE IF EXISTS {$this->getTable('bintime_connector_data')};
	DROP TABLE IF EXISTS {$this->getTable('bintime_connector_data_old')};
	DROP TABLE IF EXISTS {$this->getTable('bintime_connector_data_products')};
	DROP TABLE IF EXISTS {$this->getTable('bintime_supplier_mapping')};
	DROP TABLE IF EXISTS {$this->getTable('bintime_supplier_mapping_old')};
	DROP TABLE IF EXISTS {$this->getTable('iceshop_icecatlive_connector_data')};
	DROP TABLE IF EXISTS {$this->getTable('iceshop_icecatlive_supplier_mapping')};
	DROP TABLE IF EXISTS {$this->getTable('iceshop_icecatlive_connector_data_products')} ;

	CREATE TABLE IF NOT EXISTS {$this->getTable('icecatlive/products_titles')} (
		`prod_id` VARCHAR(255) NOT NULL,
        `prod_title` VARCHAR(255) NULL DEFAULT NULL,
        UNIQUE KEY (`prod_id`)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Iceshop Connector product titles';

	CREATE TABLE IF NOT EXISTS {$this->getTable('iceshop_extensions_logs')} (
	`log_key` VARCHAR(255) NOT NULL,
	`log_value` varchar(255) DEFAULT NULL,
	UNIQUE KEY (`log_key`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Iceshop Connector logs';
");

$installer->endSetup();