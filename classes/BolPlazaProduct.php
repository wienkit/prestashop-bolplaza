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

class BolPlazaProduct extends ObjectModel
{
    const STATUS_OK = 0;
    const STATUS_NEW = 1;
    const STATUS_STOCK_UPDATE = 2;
    const STATUS_INFO_UPDATE = 3;

    const CONDITION_NEW = 0;
    const CONDITION_AS_NEW = 1;
    const CONDITION_GOOD = 2;
    const CONDITION_REASONABLE = 3;
    const CONDITION_MODERATE = 4;

    /** @var int */
    public $id_bolplaza_product;

    /** @var int */
    public $id_product;

    /** @var int */
    public $id_product_attribute;

    /** @var string */
    public $ean;

    /** @var int */
    public $condition = 0;

    /** @var string */
    public $delivery_time;

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
            'condition' => array(
                'type' => self::TYPE_INT,
                'validate' => 'isUnsignedId'
            ),
            'delivery_time' => array(
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

//    /**
//     * Returns the EAN13 (default or overridden)
//     * @return string
//     */
//    public function getEan13()
//    {
//        if (isset($this->ean) && $this->ean != "" && $this->ean != "0") {
//            return $this->ean;
//        } else {
//            if ($this->id_product_attribute) {
//                $combination = new Combination($this->id_product_attribute);
//                return $combination->ean13;
//            } else {
//                $product = new Product($this->id_product);
//                return $product->ean13;
//            }
//        }
//    }

    /**
     * Returns the condition for the product
     * @return array
     */
    public function getCondition()
    {
        $conditions = self::getConditions();
        return $conditions[$this->condition]['code'];
    }

    /**
     * Parse the Product to a Bol processable entity
     * @return \Wienkit\BolPlazaClient\Entities\BolPlazaRetailerOffer
     */
    public function toRetailerOffer()
    {
        $id_product_attribute = $this->id_product_attribute ? $this->id_product_attribute : null;
        $offer = new \Wienkit\BolPlazaClient\Entities\BolPlazaRetailerOffer();
        $offer->EAN = $this->ean;
        $offer->Condition = $this->getCondition();
        $price = Product::getPriceStatic($this->id_product, true, $id_product_attribute);
        $offer->Price = $price + $this->price;
        if ($this->delivery_time != null) {
            $offer->DeliveryCode = $this->delivery_time;
        } else {
            $offer->DeliveryCode = Configuration::get('BOL_PLAZA_ORDERS_DELIVERY_CODE');
        }
        $stock = StockAvailable::getQuantityAvailableByProduct(
            $this->id_product,
            $id_product_attribute
        );
        if ($stock < 0) {
            $stock = 0;
        } elseif ($stock > 999) {
            $stock = 999;
        }
        if ($this->status === self::STATUS_NEW) {
            $product = new Product($this->id_product, false, Language::getIdByIso('NL'));
            $offer->Title = $product->name;
        }
        $offer->QuantityInStock = $stock;
        $offer->Publish = $this->published == 1 ? 'true' : 'false';
        $offer->ReferenceCode = $this->id_bolplaza_product;
        return $offer;
    }

    /**
     * Returns the BolProduct data for a product ID
     * @param int $id_product
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
     * Returns the own offer
     * @param int $id_bolplaza_product
     * @return array|false|mysqli_result|null|PDOStatement|resource
     */
    public static function getOwnOfferResult($id_bolplaza_product)
    {
        return Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS('
            SELECT *
            FROM `'._DB_PREFIX_.'bolplaza_ownoffers`
            WHERE `id_bolplaza_product` = ' . (int)$id_bolplaza_product);
    }

    /**
     * Returns the BolProduct data for a product ID and attribute ID
     * @param int $id_product
     * @param int $id_product_attribute
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
     * Returns the bolproduct data for an ean
     * @param $ean13
     * @return false|null|string
     */
    public static function getByEan13($ean13)
    {
        return Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue('
			SELECT `id_bolplaza_product`
			FROM `'._DB_PREFIX_.'bolplaza_product`
			WHERE `ean` = '.(int)$ean13);
    }

    /**
     * Returns a list of BolProduct objects that need an update
     * @return BolPlazaProduct[]
     */
    public static function getAll()
    {
        return ObjectModel::hydrateCollection(
            'BolPlazaProduct',
            Db::getInstance()->executeS('
                SELECT *
                FROM `'._DB_PREFIX_.'bolplaza_product`')
        );
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
                WHERE `status` > 0
                LIMIT 1000')
        );
    }

    /**
     * Returns a list of the possible conditions
     * @return array
     */
    public static function getConditions()
    {
        return array(
            self::CONDITION_NEW => array(
                'value' => self::CONDITION_NEW,
                'code' => 'NEW',
                'description' => 'New'
            ),
            self::CONDITION_AS_NEW => array(
                'value' => self::CONDITION_AS_NEW,
                'code' => 'AS_NEW',
                'description' => 'As new'
            ),
            self::CONDITION_GOOD => array(
                'value' => self::CONDITION_GOOD,
                'code' => 'GOOD',
                'description' => 'Good'
            ),
            self::CONDITION_REASONABLE => array(
                'value' => self::CONDITION_REASONABLE,
                'code' => 'REASONABLE',
                'description' => 'Reasonable'
            ),
            self::CONDITION_MODERATE => array(
                'value' => self::CONDITION_MODERATE,
                'code' => 'MODERATE',
                'description' => 'Moderate'
            ),
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
