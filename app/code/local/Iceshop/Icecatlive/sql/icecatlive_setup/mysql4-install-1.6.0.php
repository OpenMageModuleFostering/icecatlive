<?php
include_once 'uninstall-old-version.php';
$unistaller_old_version = new Uninstall_Bintime_Icecatlive();
$unistaller_old_version->uninstall();
$installer = $this;
/* @var $installer Mage_Catalog_Model_Resource_Eav_Mysql4_Setup */

$installer->startSetup();
$installer->run("
    DROP TABLE IF EXISTS `bintime_connector_data`;
	DROP TABLE IF EXISTS `bintime_connector_data_old`;
	DROP TABLE IF EXISTS `bintime_connector_data_products`;
	DROP TABLE IF EXISTS `bintime_supplier_mapping`;
	DROP TABLE IF EXISTS `bintime_supplier_mapping_old`;
	DROP TABLE IF EXISTS {$this->getTable('icecatlive/data')};
	CREATE TABLE {$this->getTable('icecatlive/data')} (
		`prod_id` varchar(255) NOT NULL,
        `supplier_id` int(11) DEFAULT NULL,
        `prod_name` varchar(255) DEFAULT NULL,
        `prod_img` varchar(255) DEFAULT NULL,
        KEY `PRODUCT_MPN` (`prod_id`),
        KEY `supplier_id` (`supplier_id`)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Iceshop Connector product image table';

	DROP TABLE IF EXISTS {$this->getTable('icecatlive/supplier_mapping')};
	CREATE TABLE {$this->getTable('icecatlive/supplier_mapping')} (
		`supplier_id` int(11) NOT NULL,
		`supplier_symbol` VARCHAR(255),
		KEY `supplier_id` (`supplier_id`)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Iceshop Connector supplier mapping table';

	DROP TABLE IF EXISTS {$this->getTable('icecatlive/data_products')};
	CREATE TABLE {$this->getTable('icecatlive/data_products')} (
		`prod_id` VARCHAR(255) NOT NULL,
		`supplier_symbol` varchar(255) DEFAULT NULL,
        `prod_title` VARCHAR(255) NULL DEFAULT NULL,
        `prod_ean` VARCHAR(255) NOT NULL,
        KEY `prod_id`     (`prod_id`),
        KEY `PRODUCT_EAN` (`prod_ean`),
        INDEX `mpn_brand` (`prod_id`, `supplier_symbol`)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Iceshop Connector product ean table';
");

$installer->endSetup();
