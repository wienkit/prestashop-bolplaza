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
 *  @copyright 2013-2016 Wienk IT
 *  @license   LICENSE.txt
 */

$sql = array();
$sql[_DB_PREFIX_.'bolplaza_item'] = 'CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'bolplaza_item` (
              `id_bolplaza_item` int(11) NOT NULL AUTO_INCREMENT,
              `id_shop` INTEGER DEFAULT \'0\',
              `id_shop_group` INTEGER DEFAULT \'0\',
              `id_order` int(11) NOT NULL,
              `id_bol_order_item` varchar(32) NOT NULL,
              `ean` varchar(13),
              `title` varchar(255),
              `quantity` INTEGER DEFAULT \'1\',
              `status` varchar(12) NOT NULL,
              PRIMARY KEY (`id_bolplaza_item`)
            ) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8;';

$sql[_DB_PREFIX_.'bolplaza_product'] = 'CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'bolplaza_product` (
              `id_bolplaza_product` int(11) NOT NULL AUTO_INCREMENT,
              `id_product` int(10) unsigned NOT NULL,
              `id_product_attribute` int(10) unsigned NOT NULL,
              `id_shop` INT(11) UNSIGNED NOT NULL DEFAULT \'1\',
              `published` tinyint(1) NOT NULL DEFAULT \'0\',
              `price` DECIMAL(20, 6) NOT NULL DEFAULT \'0.000000\',
              PRIMARY KEY (`id_bolplaza_product`),
              UNIQUE KEY(`id_product`, `id_product_attribute`, `id_shop`)
            ) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8;';
