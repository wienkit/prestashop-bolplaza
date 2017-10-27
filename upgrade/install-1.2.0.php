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

function upgrade_module_1_2_0()
{
    // ALTER TABLE `PREFIX_product` ADD quantity_discount BOOL NULL DEFAULT 0 AFTER out_of_stock;
    return (
        Db::getInstance()->execute(
            'ALTER TABLE `'._DB_PREFIX_.'bolplaza_product` ADD `ean` VARCHAR(13) AFTER `id_shop`;'
        ) &&
        Db::getInstance()->execute(
            'ALTER TABLE `'._DB_PREFIX_.'bolplaza_product` ADD `delivery_time` VARCHAR(10) AFTER `ean`'
        )
    );
}
