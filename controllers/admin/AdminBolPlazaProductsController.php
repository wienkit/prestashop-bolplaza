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

require_once _PS_MODULE_DIR_.'bolplaza/libraries/autoload.php';
require_once _PS_MODULE_DIR_.'bolplaza/bolplaza.php';
require_once _PS_MODULE_DIR_.'bolplaza/classes/BolPlazaProduct.php';

class AdminBolPlazaProductsController extends ModuleAdminController
{

    protected $statuses_array;

    public function __construct()
    {
        $this->bootstrap = true;
        $this->table = 'bolplaza_product';
        $this->className = 'BolPlazaProduct';

        $this->identifier = 'id_bolplaza_product';

        $this->_join .= ' INNER JOIN `'._DB_PREFIX_.'product_lang` pl
                            ON (pl.`id_product` = a.`id_product` AND pl.`id_shop` = a.`id_shop`) 
                          INNER JOIN `'._DB_PREFIX_.'lang` lang
                            ON (pl.`id_lang` = lang.`id_lang` AND lang.`iso_code` = \'nl\')
                          LEFT JOIN `'._DB_PREFIX_.'bolplaza_ownoffers` bo
                            ON (a.`id_bolplaza_product` = bo.`id_bolplaza_product`) ';
        $this->_select .= ' pl.`name` as `product_name`,
                            IF(status = 0, 1, 0) as badge_success,
                            IF(status > 0, 1, 0) as badge_danger,
                            bo.`published` as `bol_published`';

        $this->statuses_array = array(
            BolPlazaProduct::STATUS_OK => $this->l('OK'),
            BolPlazaProduct::STATUS_NEW => $this->l('New'),
            BolPlazaProduct::STATUS_STOCK_UPDATE => $this->l('Stock updated'),
            BolPlazaProduct::STATUS_INFO_UPDATE => $this->l('Info updated')
        );

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
                'title' => $this->l('Bol.com price addition'),
                'type' => 'price',
                'filter' => false,
                'search' => false,
                'align' => 'text-right'
            ),
            'published' => array(
                'title' => $this->l('Published'),
                'type' => 'bool',
                'active' => 'published',
                'align' => 'text-center',
                'filter_key' => 'a!published',
                'class' => 'fixed-width-sm'
            ),
            'bol_published' => array(
                'title' => $this->l('Published on Bol'),
                'type' => 'bool',
                'active' => 'bol_published',
                'filter_key' => 'bo!published',
                'align' => 'text-center',
                'class' => 'fixed-width-sm'
            ),
            'status' => array(
                'title' => $this->l('Synchronized'),
                'type' => 'select',
                'callback' => 'getSynchronizedState',
                'badge_danger' => true,
                'badge_success' => true,
                'align' => 'text-center',
                'class' => 'fixed-width-sm',
                'list' => $this->statuses_array,
                'filter_key' => 'status',
                'filter_type' => 'int'
            )
        );

        $this->shopLinkType = 'shop';

        $this->addRowAction('view');
        $this->addRowAction('viewProduct');
        $this->addRowAction('resetNew');
        $this->addRowAction('resetUpdated');
        $this->addRowAction('resetStock');
        $this->addRowAction('resetOk');
        parent::__construct();
    }

    public function setMedia()
    {
        parent::setMedia();
        $this->addJS(__PS_BASE_URI__ . 'modules/bolplaza/views/js/bolplaza.js');
    }

    /**
     * Callback for the static column in the list
     * @param int $status the status
     * @return string the status
     */
    public function getSynchronizedState($status)
    {
        return $this->statuses_array[$status];
    }

    /**
     * Show a link to view the linked product
     * @param null $token
     * @param $id
     * @return mixed
     */
    public function displayViewProductLink($token = null, $id = 0)
    {
        if (!array_key_exists('Go to product', self::$cache_lang)) {
            self::$cache_lang['Go to product'] = $this->l('Go to product');
        }

        $tpl = $this->createTemplate('helpers/list/list_action_view.tpl');
        $tpl->assign(array(
            'href' => self::$currentIndex . '&' . $this->identifier . '=' . $id . '&gotoproduct=1&token='
                . ($token != null ? $token : $this->token),
            'action' => self::$cache_lang['Go to product']
        ));
        return $tpl->fetch();
    }

    /**
     * Show a reset link to the new state
     * @param null $token
     * @param $id
     * @return mixed
     */
    public function displayResetNewLink($token = null, $id = 0)
    {
        if (!array_key_exists('Reset to new', self::$cache_lang)) {
            self::$cache_lang['Reset to new'] = $this->l('Reset to new');
        }

        $tpl = $this->createTemplate('helpers/list/list_action_transferstock.tpl');
        $tpl->assign(array(
            'href' => self::$currentIndex . '&' . $this->identifier . '=' . $id . '&reset=1&state=new&token='
                . ($token != null ? $token : $this->token),
            'action' => self::$cache_lang['Reset to new']
        ));
        return $tpl->fetch();
    }

    /**
     * Show a reset link to the updated state
     * @param null $token
     * @param $id
     * @return mixed
     */
    public function displayResetUpdatedLink($token = null, $id = 0)
    {
        if (!array_key_exists('Reset to updated', self::$cache_lang)) {
            self::$cache_lang['Reset to updated'] = $this->l('Reset to updated');
        }

        $tpl = $this->createTemplate('helpers/list/list_action_transferstock.tpl');
        $tpl->assign(array(
            'href' => self::$currentIndex . '&' . $this->identifier . '=' . $id . '&reset=1&state=updated&token='
                . ($token != null ? $token : $this->token),
            'action' => self::$cache_lang['Reset to updated']
        ));
        return $tpl->fetch();
    }

    /**
     * Show a reset link to the stock updated state
     * @param null $token
     * @param $id
     * @return mixed
     */
    public function displayResetStockLink($token = null, $id = 0)
    {
        if (!array_key_exists('Reset to stock updated', self::$cache_lang)) {
            self::$cache_lang['Reset to stock updated'] = $this->l('Reset to stock updated');
        }

        $tpl = $this->createTemplate('helpers/list/list_action_transferstock.tpl');
        $tpl->assign(array(
            'href' => self::$currentIndex . '&' . $this->identifier . '=' . $id . '&reset=1&state=stock&token='
                . ($token != null ? $token : $this->token),
            'action' => self::$cache_lang['Reset to stock updated']
        ));
        return $tpl->fetch();
    }


    /**
     * Show a reset link to the stock updated state
     * @param null $token
     * @param $id
     * @return mixed
     */
    public function displayResetOkLink($token = null, $id = 0)
    {
        if (!array_key_exists('Reset to ok', self::$cache_lang)) {
            self::$cache_lang['Reset to ok'] = $this->l('Reset to ok');
        }

        $tpl = $this->createTemplate('helpers/list/list_action_transferstock.tpl');
        $tpl->assign(array(
            'href' => self::$currentIndex . '&' . $this->identifier . '=' . $id . '&reset=1&state=ok&token='
                . ($token != null ? $token : $this->token),
            'action' => self::$cache_lang['Reset to ok']
        ));
        return $tpl->fetch();
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

        if (Configuration::get('BOL_PLAZA_ORDERS_OWNOFFERS') != '') {
            $this->page_header_toolbar_btn['reset_sync'] = array(
                'href' => self::$currentIndex.'&token='.$this->token.'&reset_sync=1',
                'desc' => $this->l('Reset sync'),
                'icon' => 'process-icon-update'
            );
        }
        $this->page_header_toolbar_btn['sync_products'] = array(
            'href' => self::$currentIndex.'&token='.$this->token.'&sync_products=1',
            'desc' => $this->l('Sync products'),
            'icon' => 'process-icon-update'
        );
        $this->page_header_toolbar_btn['update_products'] = array(
            'href' => self::$currentIndex.'&token='.$this->token.'&update_products=1',
            'desc' => $this->l('Update products'),
            'icon' => 'process-icon-export'
        );
    }

    public function initProcess()
    {
        if (Tools::getIsset('published'.$this->table)) {
            $this->action = 'published';
        }
        if (!$this->action) {
            parent::initProcess();
        }
    }

    /**
     * Process the price updater widget
     */
    public function ajaxProcessUpdateBolPrice()
    {
        $id_bolplaza_product = Tools::getValue("id_bolplaza_product");
        $price = Tools::getValue("price");
        if ($id_bolplaza_product && $price) {
            $price = str_replace(',', '.', $price);
            $bolProduct = new BolPlazaProduct($id_bolplaza_product);
            $bolProduct->price = $price;
            $bolProduct->save();
            return die(Tools::jsonEncode(array(
                'error' => false,
                'message' => sprintf($this->l('Updated Bol.com ID: %s'), $bolProduct->id_bolplaza_product),
                'price' => $bolProduct->price
            )));
        } else {
            return die(Tools::jsonEncode(array('error' => $this->l('You did not send the right parameters'))));
        }
    }

    /**
     * Process the commission calculation
     */
    public function ajaxProcessCalculateCommission()
    {
        $ean = Tools::getValue("ean");
        $condition = Tools::getValue("condition");
        $price = Tools::getValue("price");

        try {
            $Plaza = BolPlaza::getClient();
            $commission = $Plaza->getCommission($ean, $condition, $price);
            $reductions = array();
            foreach ($commission->Reductions as $reduction) {
                $reduction[] = array(
                    'max' => $reduction->MaximumPrice,
                    'reduction' => $reduction->CostReduction,
                    'start' => $reduction->StartDate,
                    'end' => $reduction->EndDate
                );
            }
            return die(Tools::jsonEncode(array(
                'error' => false,
                'cost' => array(
                    'fixed' => $commission->FixedAmount,
                    'percentage' => $commission->Percentage,
                    'total' => $commission->TotalCost,
                    'totalWithoutReduction' => $commission->TotalCostWithoutReduction,
                ),
                'reductions' => $reductions
            )));
        } catch (Exception $e) {
            return die(Tools::jsonEncode(array(
                'error' => true,
                'message' => sprintf($e->getMessage())
            )));
        }
    }

    /**
     * Processes the request
     */
    public function postProcess()
    {
        if (!Configuration::get('BOL_PLAZA_ORDERS_ENABLED')) {
            $this->errors[] = Tools::displayError('Bol Plaza API isn\'t enabled for the current store.');
            return false;
        }

        if ((bool)Tools::getValue('reset_sync')) {
            Configuration::deleteByName('BOL_PLAZA_ORDERS_OWNOFFERS');
            $this->confirmations[] = $this->l(
                'The sync wil request a new file on the next run.'
            );
        } elseif ((bool)Tools::getValue('sync_products')) {
            self::synchronizeFromBol($this->context);
        } elseif ((bool)Tools::getValue('update_products')) {
            self::synchronize($this->context);
            $this->confirmations[] = $this->l('Bol products fully synchronized.');
        } elseif ((bool)Tools::getValue('reset') && (int)Tools::getValue('id_bolplaza_product')) {
            $id_bolplaza_product = (int)Tools::getValue('id_bolplaza_product');
            $bolProduct = new BolPlazaProduct($id_bolplaza_product);
            switch ((string)Tools::getValue('state')) {
                case 'new':
                    self::setProductStatus($bolProduct, BolPlazaProduct::STATUS_NEW);
                    break;
                case 'updated':
                    self::setProductStatus($bolProduct, BolPlazaProduct::STATUS_INFO_UPDATE);
                    break;
                case 'stock':
                    self::setProductStatus($bolProduct, BolPlazaProduct::STATUS_STOCK_UPDATE);
                    break;
                default:
                    self::setProductStatus($bolProduct, BolPlazaProduct::STATUS_OK);
                    break;
            }
        } elseif ((bool)Tools::getValue('gotoproduct')) {
            $id_bolplaza_product = (int)Tools::getValue('id_bolplaza_product');
            $bolProduct = new BolPlazaProduct($id_bolplaza_product);
            Tools::redirectAdmin(
                Context::getContext()
                    ->link
                    ->getAdminLink('AdminProducts').'&updateproduct&id_product='.(int)$bolProduct->id_product
            );
        }
        return parent::postProcess();
    }

    public function processPublished()
    {
        /** @var BolPlazaProduct $bolProduct */
        if (Validate::isLoadedObject($bolProduct = $this->loadObject())) {
            $bolProduct->published = $bolProduct->published ? 0 : 1;
            $bolProduct->save();
        }
    }

    /**
     * Synchronize changed products
     * @param Context $context
     */
    public static function synchronize($context)
    {

        $bolProducts = BolPlazaProduct::getUpdatedProducts();
        foreach ($bolProducts as $bolProduct) {
            switch ($bolProduct->status) {
                case BolPlazaProduct::STATUS_NEW:
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
     * Handle the own offers returned result from Bol.com
     * @param string $ownOffers
     */
    public static function handleOwnOffers($ownOffers)
    {
        DB::getInstance()->delete('bolplaza_ownoffers');
        $keys = array(
            'OfferId' => 'id_bolplaza_product',
            'Reference' => 'reference',
            'EAN' => 'ean',
            'Condition' => 'condition',
            'Stock' => 'stock',
            'Price' => 'price',
            'Description' => 'description',
            'Deliverycode' => 'delivery_code',
            'Publish' => 'publish',
            'Published' => 'published',
            'ReasonCode' => 'reasoncode',
            'Reason' => 'reason',
            'ReasonMessage' => 'reason'
        );

        $data = str_getcsv($ownOffers, "\n");
        $data = array_map("str_getcsv", $data);
        $header = array_shift($data);
        array_walk($data, 'AdminBolPlazaProductsController::parseCsvRow', array('header' => $header, 'keys' => $keys));
        $data = array_filter($data, 'AdminBolPlazaProductsController::filterCsvRow');
        DB::getInstance()->insert('bolplaza_ownoffers', $data);
    }


    /**
     * @param $row
     * @param $key
     * @param $settings
     * Reindex the data for the database
     */
    public static function parseCsvRow(&$row, $key, $settings)
    {
        $row = array_combine($settings['header'], $row);
        $row = array_intersect_key($row, $settings['keys']);
        foreach ($settings['keys'] as $bolKey => $dbKey) {
            if (array_key_exists($bolKey, $row)) {
                if ($dbKey == 'publish' || $dbKey == 'published') {
                    $row[$dbKey] = $row[$bolKey] == 'TRUE';
                } else {
                    $row[$dbKey] = pSQL($row[$bolKey]);
                }
                unset($row[$bolKey]);
            }
        }
    }

    /**
     * @param $row
     * @return bool
     */
    public static function filterCsvRow($row)
    {
        return array_key_exists('id_bolplaza_product', $row) && (((int) $row['id_bolplaza_product']) != 0);
    }

    /**
     * Updates the BolPlazaProduct status with the stock status from Bol.com
     */
    private static function updateOwnOffersStock()
    {
        // Update stock status
        $sql = "SELECT bo.id_bolplaza_product
                FROM "._DB_PREFIX_."bolplaza_ownoffers bo 
                INNER JOIN "._DB_PREFIX_."bolplaza_product bp 
                    ON bo.id_bolplaza_product = bp.id_bolplaza_product
                INNER JOIN "._DB_PREFIX_."stock_available sa 
                    ON sa.id_product = bp.id_product
                    AND sa.id_product_attribute = bp.id_product_attribute
                WHERE sa.quantity <> bo.stock";
        $results = Db::getInstance()->executeS($sql);
        $ids = array();
        foreach ($results as $row) {
            $ids[] = (int) $row['id_bolplaza_product'];
        }
        if (count($ids) > 0) {
            Db::getInstance()->update(
                'bolplaza_product',
                array(
                    'status' => BolPlazaProduct::STATUS_STOCK_UPDATE
                ),
                'id_bolplaza_product IN (' . implode(',', $ids) . ')'
            );
        }
    }

    /**
     * Updates the BolPlazaProduct status to new if there is changed data
     */
    private static function updateOwnOffersInfo()
    {
        // Update stock status
        $sql = "SELECT bo.id_bolplaza_product
                FROM "._DB_PREFIX_."bolplaza_ownoffers bo 
                INNER JOIN "._DB_PREFIX_."bolplaza_product bp 
                    ON bo.id_bolplaza_product = bp.id_bolplaza_product
                WHERE bo.publish <> bp.published";
        $results = Db::getInstance()->executeS($sql);
        $ids = array();
        foreach ($results as $row) {
            $ids[] = (int) $row['id_bolplaza_product'];
        }
        if (count($ids) > 0) {
            Db::getInstance()->update(
                'bolplaza_product',
                array(
                    'status' => BolPlazaProduct::STATUS_INFO_UPDATE
                ),
                'id_bolplaza_product IN (' . implode(',', $ids) . ')'
            );
        }
    }

    /**
     * Updates the BolPlazaProduct status if the product isn't available at Bol.com
     */
    private static function updateOwnOffersNew()
    {
        // Update stock status
        $sql = "SELECT bp.id_bolplaza_product
                FROM "._DB_PREFIX_."bolplaza_product bp
                LEFT JOIN "._DB_PREFIX_."bolplaza_ownoffers bo
                    ON bp.id_bolplaza_product = bo.id_bolplaza_product
                WHERE bo.id_bolplaza_product IS NULL";

        $results = Db::getInstance()->executeS($sql);
        $ids = array();
        foreach ($results as $row) {
            $ids[] = (int) $row['id_bolplaza_product'];
        }
        if (count($ids) > 0) {
            Db::getInstance()->update(
                'bolplaza_product',
                array(
                    'status' => BolPlazaProduct::STATUS_NEW
                ),
                'id_bolplaza_product IN (' . implode(',', $ids) . ')'
            );
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
            $Plaza->deleteOffer($bolProduct->ean, $bolProduct->getCondition());
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
        $offer = $bolProduct->toRetailerOffer();
        $offer->QuantityInStock = $quantity;
        $request = new Wienkit\BolPlazaClient\Requests\BolPlazaUpsertRequest();
        $request->RetailerOffer = $offer;
        try {
            $Plaza->updateOfferStock($request);
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
        $offerUpdate = $bolProduct->toRetailerOffer();
        $product = new Product($bolProduct->id_product, false, $context->language->id, $context->shop->id);
        if (!empty($product->description)) {
            $offerUpdate->Description = html_entity_decode($product->description);
        } else {
            $offerUpdate->Description = html_entity_decode($product->name);
        }

        $Plaza = BolPlaza::getClient();
        try {
            $request = new Wienkit\BolPlazaClient\Requests\BolPlazaUpsertRequest();
            $request->RetailerOffer = $offerUpdate;
            $Plaza->updateOffer($request);
            self::setProductStatus($bolProduct, (int)BolPlazaProduct::STATUS_OK);
        } catch (Exception $e) {
            $context->controller->errors[] = Tools::displayError(
                '[bolplaza] Couldn\'t send update to Bol.com, error: ' . $e->getMessage()
            );
        }
    }

    /**
     * Overrides parent::renderView();
     * @return string
     */
    public function renderView()
    {
        $product = new Product($this->object->id_product, $this->context->language->id);
        $ownOffer = BolPlazaProduct::getOwnOfferResult($this->object->id);
        if (count($ownOffer) == 1) {
            $ownOffer = $ownOffer[0];
        }

        $stock = StockAvailable::getQuantityAvailableByProduct(
            $this->object->id_product,
            $this->object->id_product_attribute
        );
        $delivery_code = $this->object->delivery_time;
        if ($delivery_code == '') {
            $delivery_code = Configuration::get('BOL_PLAZA_ORDERS_DELIVERY_CODE');
        }

        $this->tpl_view_vars = array(
            'title' => $product->name[$this->context->language->id],
            'bolproduct' => $this->object,
            'ownoffer' => $ownOffer,
            'stock' => $stock,
            'delivery_code' => $delivery_code,
            'links' => array(
                array(
                    'title' => 'Go to product',
                    'link' => Context::getContext()
                            ->link
                            ->getAdminLink('AdminProducts').'&updateproduct&id_product='.(int)$product->id
                )
            )
        );
        return parent::renderView();
    }

    /**
     * @param Context $context
     */
    public static function synchronizeFromBol($context)
    {
        $bolplaza = BolPlaza::getClient();
        $url = Configuration::get('BOL_PLAZA_ORDERS_OWNOFFERS');
        if (!$url) {
            $ownOffers = $bolplaza->getOwnOffers();
            $url = $ownOffers->Url;
        }
        try {
            $ownOffersResult = $bolplaza->getOwnOffersResult($url);
            Configuration::deleteByName('BOL_PLAZA_ORDERS_OWNOFFERS');
            self::handleOwnOffers($ownOffersResult);
            self::updateOwnOffersStock();
            self::updateOwnOffersInfo();
            self::updateOwnOffersNew();
            $context->controller->confirmations[] = 'This file has been processed: ' . $url;
        } catch (Wienkit\BolPlazaClient\Exceptions\BolPlazaClientException $e) {
            Configuration::set('BOL_PLAZA_ORDERS_OWNOFFERS', $url);
            $context->controller->confirmations[] =  'The file will be generated by Bol.com. Click this' .
                ' button again in a few minutes.';
        }
    }
}
