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

class BolPlazaProduct extends ObjectModel
{
    /** @var int */
    public $id_bolplaza_product;

    /** @var int */
    public $id_product;

    /** @var int */
    public $id_product_attribute;

    /** @var bool */
    public $published = false;

    /** @var float */
    public $price;

    /** @var bool */
    public $stock_update;

    /** @var bool */
    public $info_update;

    /**
     * @see ObjectModel::$definition
     */
    public static $definition = array(
        'table' => 'bolplaza_product',
        'primary' => 'id_bolplaza_product',
        'multishop' => true,
        'fields' => array(
            'id_product' =>              array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true),
            'id_product_attribute' =>    array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true),
            'published' =>               array('type' => self::TYPE_BOOL, 'shop' => true, 'validate' => 'isBool'),
            'price' =>                   array('type' => self::TYPE_FLOAT, 'shop' => true, 'validate' => 'isPrice'),
            'stock_update' =>            array('type' => self::TYPE_BOOL, 'shop' => true, 'validate' => 'isBool'),
            'info_update' =>             array('type' => self::TYPE_BOOL, 'shop' => true, 'validate' => 'isBool')
        )
    );

    public static function getByProductId($id_product)
    {
        return Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS('
			SELECT *
			FROM `'._DB_PREFIX_.'bolplaza_product`
			WHERE `id_product` = '.(int)$id_product);
    }

    public static function getIdByProductAndAttributeId($id_product, $id_product_attribute)
    {
        return Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue('
			SELECT `id_bolplaza_product`
			FROM `'._DB_PREFIX_.'bolplaza_product`
			WHERE `id_product` = '.(int)$id_product.'
      AND `id_product_attribute` = '.(int)$id_product_attribute);
    }
}
