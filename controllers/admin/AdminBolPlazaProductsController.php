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

require_once _PS_MODULE_DIR_.'bolplaza/libraries/autoload.php';
require_once _PS_MODULE_DIR_.'bolplaza/bolplaza.php';
require_once _PS_MODULE_DIR_.'bolplaza/classes/BolPlazaProduct.php';

class AdminBolPlazaProductsController extends AdminController
{
    public function __construct()
    {

        if ($id_product = Tools::getValue('id_product')) {
            Tools::redirectAdmin(
                Context::getContext()
                    ->link
                    ->getAdminLink('AdminProducts').'&updateproduct&id_product='.(int)$id_product
            );
        }

        $this->bootstrap = true;
        $this->table = 'bolplaza_product';
        $this->className = 'BolPlazaProduct';

        $this->addRowAction('view');

        $this->identifier = 'id_product';

        $this->_join .= ' INNER JOIN `'._DB_PREFIX_.'product_lang` pl
                            ON (pl.`id_product` = a.`id_product` AND pl.`id_shop` = a.`id_shop`) ';
        $this->_select .= ' pl.`name` as `product_name`,
                            IF(status = 0, 1, 0) as badge_success,
                            IF(status > 0, 1, 0) as badge_danger ';

        $this->fields_list = array(
            'id_bolplaza_product' => array(
                'title' => $this->l('Offer ID'),
                'align' => 'text-left',
                'class' => 'fixed-width-xs'
            ),
            'product_name' => array(
                'title' => $this->l('Product'),
                'align' => 'text-left',
                'filter_key' => 'pl!name'
            ),
            'id_product_attribute' => array(
                'title' => $this->l('Product combination'),
                'align' => 'text-left',
            ),
            'price' => array(
                'title' => $this->l('Bol specific price'),
                'type' => 'price',
                'align' => 'text-right',
            ),
            'published' => array(
                'title' => $this->l('Published'),
                'type' => 'bool',
                'active' => 'published',
                'align' => 'text-center',
                'class' => 'fixed-width-sm'
            ),
            'status' => array(
                'title' => $this->l('Synchronized'),
                'callback' => 'getSychronizedState',
                'badge_danger' => true,
                'badge_success' => true,
                'align' => 'text-center',
                'class' => 'fixed-width-sm'
            )
        );

        $this->shopLinkType = 'shop';

        parent::__construct();
    }

    /**
     * Callback for the static column in the list
     * @param int $status the status
     * @return string the status
     */
    public function getSychronizedState($status)
    {
        switch($status) {
            case BolPlazaProduct::STATUS_OK:
                return $this->l('OK');
            case BolPlazaProduct::STATUS_STOCK_UPDATE:
                return $this->l('Stock updated');
            case BolPlazaProduct::STATUS_INFO_UPDATE:
                return $this->l('Info updated');
            case BolPlazaProduct::STATUS_NEW:
                return $this->l('New');
            default:
                return $this->l('Unknown');
        }
    }

    /**
     * Overrides parent::displayViewLink
     */
    public function displayViewLink($token = null, $id = 0, $name = null)
    {
        if ($this->tabAccess['view'] == 1) {
            $tpl = $this->createTemplate('helpers/list/list_action_view.tpl');
            if (!array_key_exists('View', self::$cache_lang)) {
                self::$cache_lang['View'] = $this->l('View', 'Helper');
            }

            $tpl->assign(array(
                'href' => $this->context->link->getAdminLink('AdminProducts').'&updateproduct&id_product='.(int)$id,
                'action' => self::$cache_lang['View'],
                'id' => $id
            ));

            return $tpl->fetch();
        } else {
            return false;
        }
    }

    /**
     * Overrides parent::initPageHeaderToolbar
     */
    public function initPageHeaderToolbar()
    {
        parent::initPageHeaderToolbar();
        if (!Configuration::get('BOL_PLAZA_ORDERS_ENABLED')) {
            return;
        }
        $this->page_header_toolbar_btn['sync_products'] = array(
            'href' => self::$currentIndex.'&token='.$this->token.'&sync_products=1',
            'desc' => $this->l('Sync products'),
            'icon' => 'process-icon-update'
        );
    }

    /**
     * Processes the request
     */
    public function postProcess()
    {
        if ((bool)Tools::getValue('sync_products')) {
            if (!Configuration::get('BOL_PLAZA_ORDERS_ENABLED')) {
                $this->errors[] = Tools::displayError('Bol Plaza API isn\'t enabled for the current store.');
                return false;
            }
            self::synchronize($this->context);
            $this->confirmations[] = $this->l('Bol products fully synchronized.');
        }
        return parent::postProcess();
    }

    /**
     * Synchronize changed products
     */
    public static function synchronize($context)
    {
        $bolProducts = BolPlazaProduct::getUpdatedProducts();
        foreach ($bolProducts as $bolProduct) {
            switch($bolProduct->status) {
                case BolPlazaProduct::STATUS_NEW:
                    self::processBolProductCreate($bolProduct, $context);
                    break;
                case BolPlazaProduct::STATUS_INFO_UPDATE:
                    self::processBolProductUpdate($bolProduct, $context);
                    break;
                case BolPlazaProduct::STATUS_STOCK_UPDATE:
                    self::processBolStockUpdate($bolProduct, $context);
                    break;
            }
        }
    }

    /**
     * Set the plaza synchronization status of a product
     * @param BolPlazaProduct $bolProduct
     * @param int $status
     */
    public static function setProductStatus($bolProduct, $status)
    {
        DB::getInstance()->update('bolplaza_product', array(
            'status' => (int)$status
        ), 'id_bolplaza_product = ' . (int)$bolProduct->id);
    }

    /**
     * Delete a product from Bol.combination
     * @param BolPlazaProduct $bolProduct
     * @param Context $context
     */
    public static function processBolProductDelete($bolProduct, $context)
    {
        $Plaza = BolPlaza::getClient();
        try {
            $Plaza->deleteOffer($bolProduct->id);
        } catch (Exception $e) {
            $context->controller->errors[] = Tools::displayError(
                'Couldn\'t send update to Bol.com, error: ' . $e->getMessage() . 'You have to correct this manually.'
            );
        }
    }

    /**
     * Update the stock on Bol.com
     * @param BolPlazaProduct $bolProduct
     * @param Context $context
     */
    public static function processBolStockUpdate($bolProduct, $context)
    {
        $product = new Product($bolProduct->id_product, false, $context->language->id, $context->shop->id);
        $quantity = StockAvailable::getQuantityAvailableByProduct(
            $product->id,
            $bolProduct->id_product_attribute
        );
        self::processBolQuantityUpdate($bolProduct, $quantity, $context);
    }

    /**
     * Update the stock on Bol.com
     * @param BolPlazaProduct $bolProduct
     * @param int $quantity
     * @param Context $context
     */
    public static function processBolQuantityUpdate($bolProduct, $quantity, $context)
    {
        $Plaza = BolPlaza::getClient();
        $stockUpdate = new Picqer\BolPlazaClient\Entities\BolPlazaStockUpdate();
        $stockUpdate->QuantityInStock = $quantity;
        try {
            $Plaza->updateOfferStock($bolProduct->id, $stockUpdate);
            self::setProductStatus($bolProduct, (int)BolPlazaProduct::STATUS_OK);
        } catch (Exception $e) {
            $context->controller->errors[] = Tools::displayError(
                '[bolplaza] Couldn\'t send update to Bol.com, error: ' . $e->getMessage()
            );
        }
    }

    /**
     * Update a product on Bol.com
     * @param BolPlazaProduct $bolProduct
     * @param Context $context
     */
    public static function processBolProductUpdate($bolProduct, $context)
    {
        $price_calculator    = Adapter_ServiceLocator::get('Adapter_ProductPriceCalculator');

        $offerUpdate = new Picqer\BolPlazaClient\Entities\BolPlazaOfferUpdate();
        if ($bolProduct->delivery_time != null) {
            $offerUpdate->DeliveryCode = $bolProduct->delivery_time;
        } else {
            $offerUpdate->DeliveryCode = Configuration::get('BOL_PLAZA_ORDERS_DELIVERY_CODE');
        }
        $offerUpdate->Publish = $bolProduct->published == 1 ? 'true' : 'false';

        $product = new Product($bolProduct->id_product, false, $context->language->id, $context->shop->id);
        if ($bolProduct->id_product_attribute) {
            $combination = new Combination($bolProduct->id_product_attribute);
            $offerUpdate->ReferenceCode = $combination->reference;
        } else {
            $offerUpdate->ReferenceCode = $product->reference;
        }
        $offerUpdate->Description = !empty($product->description) ? $product->description : $product->name;
        $price = $bolProduct->price;
        if ($price == 0) {
            $price = $price_calculator->getProductPrice(
                (int)$bolProduct->id_product,
                true,
                (int)$bolProduct->id_product_attribute
            );
        }
        $offerUpdate->Price = $price;

        $Plaza = BolPlaza::getClient();
        try {
            $Plaza->updateOffer($bolProduct->id, $offerUpdate);
            self::setProductStatus($bolProduct, (int)BolPlazaProduct::STATUS_OK);
        } catch (Exception $e) {
            $context->controller->errors[] = Tools::displayError(
                '[bolplaza] Couldn\'t send update to Bol.com, error: ' . $e->getMessage()
            );
        }
    }

    /**
     * Add a product from Bol.com
     * @param BolPlazaProduct $bolProduct
     * @param Context $context
     */
    public static function processBolProductCreate($bolProduct, $context)
    {
        $price_calculator    = Adapter_ServiceLocator::get('Adapter_ProductPriceCalculator');

        $offerCreate = new Picqer\BolPlazaClient\Entities\BolPlazaOfferCreate();
        if ($bolProduct->delivery_time != null) {
            $offerCreate->DeliveryCode = $bolProduct->delivery_time;
        } else {
            $offerCreate->DeliveryCode = Configuration::get('BOL_PLAZA_ORDERS_DELIVERY_CODE');
        }
        $offerCreate->Publish = $bolProduct->published == 1 ? 'true' : 'false';

        $product = new Product($bolProduct->id_product, false, $context->language->id, $context->shop->id);
        if ($bolProduct->id_product_attribute) {
            $combination = new Combination($bolProduct->id_product_attribute);
            $offerCreate->EAN = $bolProduct->ean != null? $bolProduct->ean : $combination->ean13;
            $offerCreate->QuantityInStock = StockAvailable::getQuantityAvailableByProduct(
                $product->id,
                $bolProduct->id_product_attribute
            );
            $offerCreate->ReferenceCode = $combination->reference;
        } else {
            $offerCreate->EAN = $bolProduct->ean != null ? $bolProduct->ean : $product->ean13;
            $offerCreate->QuantityInStock = StockAvailable::getQuantityAvailableByProduct($bolProduct->id_product);
            $offerCreate->ReferenceCode = $product->reference;
        }
        switch($product->condition) {
            case 'refurbished':
                $offerCreate->Condition = 'AS_NEW';
                break;
            case 'used':
                $offerCreate->Condition = 'GOOD';
                break;
            default:
                $offerCreate->Condition = 'NEW';
                break;
        }

        $offerCreate->Description = !empty($product->description) ? $product->description : $product->name;
        $price = $bolProduct->price;
        if ($price == 0) {
            $price = $price_calculator->getProductPrice(
                (int)$bolProduct->id_product,
                true,
                (int)$bolProduct->id_product_attribute
            );
        }
        $offerCreate->Price = $price;

        $Plaza = BolPlaza::getClient();
        try {
            $Plaza->createOffer($bolProduct->id, $offerCreate);
            self::setProductStatus($bolProduct, (int)BolPlazaProduct::STATUS_OK);
        } catch (Exception $e) {
            ddd($e);
            $context->controller->errors[] = Tools::displayError(
                '[bolplaza] Couldn\'t send update to Bol.com, error: ' . $e->getMessage()
            );
        }
    }
}
