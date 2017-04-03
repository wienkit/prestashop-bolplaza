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

include(dirname(__FILE__).'/../../config/config.inc.php');
require_once(dirname(__FILE__).'/bolplaza.php');
require_once(dirname(__FILE__).'/controllers/admin/AdminBolPlazaOrdersController.php');


if (Tools::getIsset('secure_key')) {
    $secureKey = md5(_COOKIE_KEY_.Configuration::get('PS_SHOP_NAME').'BOLPLAZA');
    if (!empty($secureKey) && $secureKey === Tools::getValue('secure_key')) {
        BolPlaza::synchronize();
    }
}
