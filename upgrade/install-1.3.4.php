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
        if (isset($product->price) && $product->price > 0) {
            $price = Product::getPriceStatic(
                $product->id_product,
                true,
                $product->id_product_attribute
            );
            if ($product->price > $price) {
                $product->price = $product->price - $price;
                $product->save();
            }
        }
    };
    return true;
}
