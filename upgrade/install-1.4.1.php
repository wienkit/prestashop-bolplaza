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

if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_1_4_1()
{
    return Db::getInstance()->execute(
            'ALTER TABLE `'._DB_PREFIX_.'bolplaza_item` ADD `delivery_time_2` VARCHAR(10) AFTER `delivery_time`;'
        ) && Db::getInstance()->execute(
            'CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'bolplaza_ownoffers_2` (
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
              `reasoncode` VARCHAR(50),
              `reason` VARCHAR(255),
              PRIMARY KEY (`id_bolplaza_product`)
            ) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8;'
        );
}
