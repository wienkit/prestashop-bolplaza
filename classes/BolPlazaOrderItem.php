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

class BolPlazaOrderItem extends ObjectModel
{

    /** @var int */
    public $id_bolplaza_item;

    /** @var int */
    public $id_shop;

    /** @var int */
    public $id_shop_group;

    /** @var int */
    public $id_order;

    /** @var string */
    public $id_bol_order_item;

    /** @var string */
    public $status = 'init';

    /** @var string */
    public $ean;

    /** @var string */
    public $title;

    /** @var int */
    public $quantity;

    /**
     * @see ObjectModel::$definition
     */
    public static $definition = array(
        'table' => 'bolplaza_item',
        'primary' => 'id_bolplaza_item',
        'fields' => array(
            'id_shop' => array(
                'type' => self::TYPE_INT,
                'validate' => 'isUnsignedId'
            ),
            'id_shop_group' => array(
                'type' => self::TYPE_INT,
                'validate' =>
                'isUnsignedId'
            ),
            'id_order' => array(
                'type' => self::TYPE_INT,
                'validate' =>
                'isUnsignedId',
                'required' => true
            ),
            'id_bol_order_item' => array(
                'type' => self::TYPE_STRING,
                'validate' =>
                'isGenericName',
                'required' => true
            ),
            'status' => array(
                'type' => self::TYPE_STRING,
                'validate' => 'isGenericName',
                'size' => 12
            ),
            'ean' => array(
                'type' => self::TYPE_STRING,
                'validate' => 'isGenericName',
                'size' => 13
            ),
            'title' => array(
                'type' => self::TYPE_STRING,
                'validate' => 'isGenericName',
                'size' => 255
            ),
            'quantity' => array(
                'type' => self::TYPE_INT,
                'validate' => 'isUnsignedId'
            )
        ),
    );

    /**
     * Set the shipped status
     */
    public function setShipped()
    {
        $this->status = 'shipped';
        $this->save();
    }

    /**
     * Returns the BolPlazaOrderItem data for an order ID
     * @param string $id_order
     * @return array
     */
    public static function getByOrderId($id_order)
    {
        return ObjectModel::hydrateCollection(
            'BolPlazaOrderItem',
            Db::getInstance()->executeS('
                SELECT *
                FROM `'._DB_PREFIX_.'bolplaza_item`
                WHERE `id_order` = \''.(int)pSQL($id_order).'\'')
        );
    }
}
