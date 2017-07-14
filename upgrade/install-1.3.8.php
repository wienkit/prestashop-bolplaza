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

function upgrade_module_1_3_8($object)
{
    return $object->registerHook('actionProductDelete')
        && $object->registerHook('actionProductAttributeDelete')
        && Db::getInstance()->execute(
            'UPDATE `'._DB_PREFIX_.'orders` 
            SET `module` = \'bolplaza_payment\' WHERE `module` = \'bolplaza\''
        ) && Db::getInstance()->execute(
            'ALTER TABLE  `'._DB_PREFIX_.'bolplaza_ownoffers` CHANGE `reasoncode` `reasoncode` VARCHAR( 50 );'
        );
}
