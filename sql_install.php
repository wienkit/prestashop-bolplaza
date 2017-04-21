<?php
/**
 * NOTICE OF LICENSE
 *
 * This file is licenced under the Software License Agreement.
 * With the purchase or the installation of the software in your application
 * you accept the licence agreement.
 *
 * You must not modify, adapt or create derivative works of this source code
 *
 *  @author    Mark Wienk
 *  @copyright 2013-2017 Wienk IT
 *  @license   LICENSE.txt
 */

$sql = array();
$sql[_DB_PREFIX_.'bolplaza_item'] = 'CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'bolplaza_item` (
              `id_bolplaza_item` INT(11) NOT NULL AUTO_INCREMENT,
              `id_shop` INTEGER DEFAULT \'0\',
              `id_shop_group` INTEGER DEFAULT \'0\',
              `id_order` INT(11) NOT NULL,
              `id_bol_order_item` VARCHAR(32) NOT NULL,
              `ean` VARCHAR(13),
              `title` VARCHAR(255),
              `quantity` INTEGER DEFAULT \'1\',
              `status` varchar(12) NOT NULL,
              PRIMARY KEY (`id_bolplaza_item`)
            ) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8;';

$sql[_DB_PREFIX_.'bolplaza_product'] = 'CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'bolplaza_product` (
              `id_bolplaza_product` INT(11) NOT NULL AUTO_INCREMENT,
              `id_product` INT(10) unsigned NOT NULL,
              `id_product_attribute` INT(10) unsigned NOT NULL,
              `id_shop` INT(11) UNSIGNED NOT NULL DEFAULT \'1\',
              `ean` VARCHAR(13),
              `condition` tinyint(1) NOT NULL DEFAULT \'0\',
              `delivery_time` VARCHAR(10),
              `published` tinyint(1) NOT NULL DEFAULT \'0\',
              `price` DECIMAL(20, 6) NOT NULL DEFAULT \'0.000000\',
              `status` tinyint(1) NOT NULL DEFAULT \'1\',
              PRIMARY KEY (`id_bolplaza_product`),
              UNIQUE KEY(`id_product`, `id_product_attribute`, `id_shop`)
            ) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8;';

$sql[_DB_PREFIX_.'bolplaza_ownoffers'] = 'CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'bolplaza_ownoffers` (
              `id_bolplaza_product` INT(11) NOT NULL,
              `ean` VARCHAR(13),
              `condition` VARCHAR(10),
              `stock` INT(10),
              `price` DECIMAL(20, 6) NOT NULL DEFAULT \'0.000000\',
              `description` VARCHAR(255),
              `title` VARCHAR(255),
              `fulfillment` VARCHAR(10),
              `delivery_code` VARCHAR(10),
              `publish` tinyint(1) NOT NULL DEFAULT \'0\',
              `published` tinyint(1) NOT NULL DEFAULT \'0\',
              `reasoncode` VARCHAR(20),
              `reason` VARCHAR(255),
              PRIMARY KEY (`id_bolplaza_product`)
            ) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8;';
