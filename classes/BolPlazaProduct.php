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
    const STATUS_OK = 0;
    const STATUS_NEW = 1;
    const STATUS_STOCK_UPDATE = 2;
    const STATUS_INFO_UPDATE = 3;

    /** @var int */
    public $id_bolplaza_product;

    /** @var int */
    public $id_product;

    /** @var int */
    public $id_product_attribute;

    /** @var string */
    public $ean;

    /** @var string */
    public $delivery_time_nostock;

    /** @var bool */
    public $published = false;

    /** @var float */
    public $price;

    /** @var int */
    public $status = self::STATUS_NEW;

    /**
     * @see ObjectModel::$definition
     */
    public static $definition = array(
        'table' => 'bolplaza_product',
        'primary' => 'id_bolplaza_product',
        'multishop' => true,
        'fields' => array(
            'id_product' => array(
                'type' => self::TYPE_INT,
                'validate' => 'isUnsignedId',
                'required' => true
            ),
            'id_product_attribute' => array(
                'type' => self::TYPE_INT,
                'validate' => 'isUnsignedId',
                'required' => true
            ),
            'ean' => array(
                'type' => self::TYPE_STRING,
                'shop' => true,
                'validate' => 'isEan13'
            ),
            'delivery_time_nostock' => array(
                'type' => self::TYPE_STRING,
                'shop' => true,
                'validate' => 'isString'
            ),
            'published' => array(
                'type' => self::TYPE_BOOL,
                'shop' => true,
                'validate' => 'isBool'
            ),
            'price' => array(
                'type' => self::TYPE_FLOAT,
                'shop' => true,
                'validate' => 'isPrice'
            ),
            'status' => array(
                'type' => self::TYPE_INT,
                'shop' => true,
                'validate' => 'isInt'
            )
        )
    );

    /**
     * Returns the BolProduct data for a product ID
     * @param string $id_product
     * @return array the BolPlazaProduct data
     */
    public static function getByProductId($id_product)
    {
        return Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS('
			SELECT *
			FROM `'._DB_PREFIX_.'bolplaza_product`
			WHERE `id_product` = '.(int)$id_product);
    }

    /**
     * Returns the BolProduct data for a product ID and attribute ID
     * @param string $id_product
     * @param string $id_product_attribute
     * @return array the BolPlazaProduct data
     */
    public static function getIdByProductAndAttributeId($id_product, $id_product_attribute)
    {
        return Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue('
			SELECT `id_bolplaza_product`
			FROM `'._DB_PREFIX_.'bolplaza_product`
			WHERE `id_product` = '.(int)$id_product.'
      AND `id_product_attribute` = '.(int)$id_product_attribute);
    }

    /**
     * Returns a list of BolProduct objects that need an update
     * @return array
     */
    public static function getUpdatedProducts()
    {
        return ObjectModel::hydrateCollection(
            'BolPlazaProduct',
            Db::getInstance()->executeS('
                SELECT *
                FROM `'._DB_PREFIX_.'bolplaza_product`
                WHERE `status` > 0')
        );
    }

    /**
     * Returns a list of delivery codes
     * @return array
     */
    public static function getDeliveryCodes()
    {
          return array(
            array(
                'deliverycode' => '24uurs-23',
                'description' => 'Ordered before 23:00 on working days, delivered the next working day.',
                'shipsuntil' => 23,
                'addtime' => 1
            ),
            array(
                'deliverycode' => '24uurs-22',
                'description' => 'Ordered before 22:00 on working days, delivered the next working day.',
                'shipsuntil' => 22,
                'addtime' => 1
            ),
            array(
                'deliverycode' => '24uurs-21',
                'description' => 'Ordered before 21:00 on working days, delivered the next working day.',
                'shipsuntil' => 21,
                'addtime' => 1
            ),
            array(
                'deliverycode' => '24uurs-20',
                'description' => 'Ordered before 20:00 on working days, delivered the next working day.',
                'shipsuntil' => 20,
                'addtime' => 1
            ),
            array(
                'deliverycode' => '24uurs-19',
                'description' => 'Ordered before 19:00 on working days, delivered the next working day.',
                'shipsuntil' => 19,
                'addtime' => 1
            ),
            array(
                'deliverycode' => '24uurs-18',
                'description' => 'Ordered before 18:00 on working days, delivered the next working day.',
                'shipsuntil' => 18,
                'addtime' => 1
            ),
            array(
                'deliverycode' => '24uurs-17',
                'description' => 'Ordered before 17:00 on working days, delivered the next working day.',
                'shipsuntil' => 17,
                'addtime' => 1
            ),
            array(
                'deliverycode' => '24uurs-16',
                'description' => 'Ordered before 16:00 on working days, delivered the next working day.',
                'shipsuntil' => 16,
                'addtime' => 1
            ),
            array(
                'deliverycode' => '24uurs-15',
                'description' => 'Ordered before 15:00 on working days, delivered the next working day.',
                'shipsuntil' => 15,
                'addtime' => 1
            ),
            array(
                'deliverycode' => '24uurs-14',
                'description' => 'Ordered before 14:00 on working days, delivered the next working day.',
                'shipsuntil' => 14,
                'addtime' => 1
            ),
            array(
                'deliverycode' => '24uurs-13',
                'description' => 'Ordered before 13:00 on working days, delivered the next working day.',
                'shipsuntil' => 13,
                'addtime' => 1
            ),
            array(
                'deliverycode' => '24uurs-12',
                'description' => 'Ordered before 12:00 on working days, delivered the next working day.',
                'shipsuntil' => 12,
                'addtime' => 1
            ),
            array(
                'deliverycode' => '1-2d',
                'description' => '1-2 working days.',
                'shipsuntil' => 12,
                'addtime' => 2
            ),
            array(
                'deliverycode' => '2-3d',
                'description' => '2-3 working days.',
                'shipsuntil' => 12,
                'addtime' => 3
            ),
            array(
                'deliverycode' => '3-5d',
                'description' => '3-5 working days.',
                'shipsuntil' => 12,
                'addtime' => 5
            ),
            array(
                'deliverycode' => '4-8d',
                'description' => '4-8 working days.',
                'shipsuntil' => 12,
                'addtime' => 8
            ),
            array(
                'deliverycode' => '1-8d',
                'description' => '1-8 working days.',
                'shipsuntil' => 12,
                'addtime' => 8
            )
        );
    }
}
