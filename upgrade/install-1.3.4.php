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

function upgrade_module_1_3_4()
{
    $products = BolPlazaProduct::getAll();
    foreach ($products as $product) {
        $id_product = $product->id_product;
        $id_product_attribute = $product->id_product_attribute;
        $changed = false;
        if (isset($product->price) && $product->price > 0) {
            $price = Product::getPriceStatic($id_product, true, $id_product_attribute);
            if ($product->price > $price) {
                $product->price = round($product->price - $price, 6);
                $changed = true;
            }
        }
        if (!isset($product->ean) || $product->ean == '' || $product->ean == '0') {
            $combination = $id_product_attribute ? new Combination($id_product_attribute) : new Product($id_product);
            $product->ean = $combination->ean13;
            $changed = true;
        }
        if ($changed) {
            $product->save();
        }
    }
    Db::getInstance()->execute(
        'ALTER TABLE `'._DB_PREFIX_.'bolplaza_product` ADD `condition` tinyint(1) NOT NULL DEFAULT \'0\' AFTER `ean`;'
    );
    return true;
}
