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

function upgrade_module_1_1_0($object)
{
    return ($object->registerHook('actionProductUpdate')
        && $object->registerHook('actionUpdateQuantity')
        && $object->registerHook('displayAdminProductsExtra')
        && $object->registerHook('actionObjectBolPlazaProductAddAfter')
        && $object->registerHook('actionObjectBolPlazaProductDeleteAfter')
        && $object->registerHook('actionObjectBolPlazaProductUpdateAfter')
        && $object->installProductsTab()
        && Db::getInstance()->execute(
            'CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'bolplaza_product` (
                `id_bolplaza_product` int(11) NOT NULL AUTO_INCREMENT,
                `id_product` int(10) unsigned NOT NULL,
                `id_product_attribute` int(10) unsigned NOT NULL,
                `id_shop` INT(11) UNSIGNED NOT NULL DEFAULT \'1\',
                `published` tinyint(1) NOT NULL DEFAULT \'0\',
                `price` DECIMAL(20, 6) NOT NULL DEFAULT \'0.000000\',
                `status` tinyint(1) NOT NULL DEFAULT \'1\',
                PRIMARY KEY (`id_bolplaza_product`),
                UNIQUE KEY(`id_product`, `id_product_attribute`, `id_shop`)
            ) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8;'
        )
    );
}
