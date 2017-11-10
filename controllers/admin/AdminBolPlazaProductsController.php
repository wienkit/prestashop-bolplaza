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
                            ON (a.`id_bolplaza_product` = bo.`id_bolplaza_product`) 
                          LEFT JOIN `'._DB_PREFIX_.'bolplaza_ownoffers_2` bo2
                            ON (a.`id_bolplaza_product` = bo2.`id_bolplaza_product`)';
        $this->_select .= ' pl.`name` as `product_name`,
                            IF(status = 0, 1, 0) as badge_success,
                            IF(status > 0, 1, 0) as badge_danger,
                            bo.`published` as `bol_published`,
                            bo2.`published` as `bol_published_2`';

        parent::__construct();

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
            'bol_published_2' => array(
                'title' => $this->l('Published on Bol 2'),
                'type' => 'bool',
                'active' => 'bol_published_2',
                'filter_key' => 'bo2!published',
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

    public function initToolbar()
    {
        $this->allow_export = true;
        parent::initToolbar();
        unset($this->toolbar_btn['new']);
    }

    public function initProcess()
    {
        if (Tools::getIsset('published'.$this->table)) {
            $this->action = 'published';
        }
        if (Tools::getIsset('bol_published'.$this->table)) {
            $this->display = 'view';
            $this->action = 'view';
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
            if ($commission->Reductions != null) {
                foreach ($commission->Reductions as $reduction) {
                    $reductions[] = array(
                        'max' => $reduction->MaximumPrice,
                        'reduction' => $reduction->CostReduction,
                        'start' => $reduction->StartDate,
                        'end' => $reduction->EndDate
                    );
                }
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
            self::processBolProductUpdate($bolProduct, $context);
        }
    }

    /**
     * Handle the own offers returned result from Bol.com
     * @param string $ownOffers
     * @param $dbSuffix string
     */
    public static function handleOwnOffers($ownOffers, $dbSuffix = '')
    {
        $table = 'bolplaza_ownoffers' . $dbSuffix;
        Db::getInstance()->delete($table);
        $keys = array(
            'EAN' => 'ean',
            'Condition' => 'condition',
            'Price' => 'price',
            'Deliverycode' => 'delivery_code',
            'Stock' => 'stock',
            'Publish' => 'publish',
            'Reference' => 'id_bolplaza_product',
            'Description' => 'description',
            'Title' => 'title',
            'FulfillmentMethod' => 'fulfillment',
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
        Db::getInstance()->insert($table, $data, false, false, Db::REPLACE);
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
     * @param $dbSuffix
     */
    private static function updateOwnOffersStock($dbSuffix = '')
    {
        // Update stock status
        $sql = "SELECT bo.id_bolplaza_product
                FROM "._DB_PREFIX_."bolplaza_ownoffers".pSQL($dbSuffix)." bo 
                INNER JOIN "._DB_PREFIX_."bolplaza_product bp 
                    ON bo.id_bolplaza_product = bp.id_bolplaza_product
                INNER JOIN "._DB_PREFIX_."stock_available sa 
                    ON sa.id_product = bp.id_product
                    AND sa.id_product_attribute = bp.id_product_attribute
                WHERE (sa.quantity <> bo.stock)
                    AND (bo.stock <> 999 OR (sa.quantity > 0 AND sa.quantity < 1000))";
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
     * @param string $dbSuffix
     */
    private static function updateOwnOffersInfo($dbSuffix = '')
    {
        // Update stock status
        $sql = "SELECT bo.id_bolplaza_product
                FROM "._DB_PREFIX_."bolplaza_ownoffers".pSQL($dbSuffix)." bo 
                INNER JOIN "._DB_PREFIX_."bolplaza_product bp 
                    ON bo.id_bolplaza_product = bp.id_bolplaza_product
                INNER JOIN "._DB_PREFIX_."product_shop ps
                    ON ps.id_product = bp.id_product
                    AND ps.id_shop = bp.id_shop
                WHERE 
                    bo.publish <> bp.published
                AND
                    (bp.published = 0 OR ps.active = 1)";
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
     * @param string $dbSuffix
     */
    private static function updateOwnOffersNew($dbSuffix = '')
    {
        // Update stock status
        $sql = "SELECT bp.id_bolplaza_product
                FROM "._DB_PREFIX_."bolplaza_product bp
                LEFT JOIN "._DB_PREFIX_."bolplaza_ownoffers".pSQL($dbSuffix)." bo
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
        Db::getInstance()->update('bolplaza_product', array(
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
        $clients = BolPlaza::getClients();
        foreach ($clients as $client) {
            try {
                $client->deleteOffer($bolProduct->ean, $bolProduct->getCondition());
            } catch (Exception $e) {
                $context->controller->errors[] = Tools::displayError(
                    'Couldn\'t send update to Bol.com, error: ' . $e->getMessage() .
                    'You have to correct this manually.'
                );
            }
        }
    }

    /**
     * Update a product on Bol.com
     * @param BolPlazaProduct $bolProduct
     * @param Context $context
     */
    public static function processBolProductUpdate($bolProduct, $context)
    {
        $clients = BolPlaza::getClients();
        $hasErrors = false;
        foreach ($clients as $clientID => $client) {
            $prefix = $clientID == 1 ? BolPlaza::PREFIX_SECONDARY_ACCOUNT : '';
            $offerUpdate = $bolProduct->toRetailerOffer($context, $prefix);
            try {
                $request = new Wienkit\BolPlazaClient\Requests\BolPlazaUpsertRequest();
                $request->RetailerOffer = $offerUpdate;
                $client->updateOffer($request);
            } catch (Exception $e) {
                $hasErrors = true;
                $context->controller->errors[] = Tools::displayError(
                    '[bolplaza] Couldn\'t send update to Bol.com, error: ' . $e->getMessage()
                );
            }
        }
        if (!$hasErrors) {
            self::setProductStatus($bolProduct, (int)BolPlazaProduct::STATUS_OK);
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
        if (count($ownOffer) > 0) {
            $ownOffer = $ownOffer[0];
        }

        $ownOffer_2 = null;
        if (Configuration::get('BOL_PLAZA_ORDERS_ENABLE_SPLITTED')) {
            $ownOffer_2 = BolPlazaProduct::getOwnOfferSecondaryResult($this->object->id);
            if (count($ownOffer_2) > 0) {
                $ownOffer_2 = $ownOffer_2[0];
            }
        }

        $stock = StockAvailable::getQuantityAvailableByProduct(
            $this->object->id_product,
            $this->object->id_product_attribute
        );

        $delivery_code = $this->object->delivery_time;
        if ($delivery_code == '') {
            $delivery_code = Configuration::get('BOL_PLAZA_ORDERS_DELIVERY_CODE');
        }

        $delivery_code_2 = $this->object->delivery_time_2;
        if ($delivery_code_2 == '') {
            $delivery_code_2 = Configuration::get(
                BolPlaza::PREFIX_SECONDARY_ACCOUNT . 'BOL_PLAZA_ORDERS_DELIVERY_CODE'
            );
        }

        $this->tpl_view_vars = array(
            'title' => $product->name[$this->context->language->id],
            'bolproduct' => $this->object,
            'ownoffer' => $ownOffer,
            'ownoffer_2' => $ownOffer_2,
            'stock' => $stock,
            'delivery_code' => $delivery_code,
            'delivery_code_2' => $delivery_code_2,
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
        $clients = BolPlaza::getClients();
        foreach ($clients as $clientID => $client) {
            $prefix = $clientID == 1 ? BolPlaza::PREFIX_SECONDARY_ACCOUNT : '';
            $suffix = $clientID == 1 ? BolPlaza::DB_SUFFIX_SECONDARY_ACCOUNT : '';
            $url = Configuration::get($prefix . 'BOL_PLAZA_ORDERS_OWNOFFERS');
            if (!$url) {
                $ownOffers = $client->getOwnOffers();
                $url = $ownOffers->Url;
            }
            try {
                $ownOffersResult = $client->getOwnOffersResult($url);
                Configuration::deleteByName($prefix . 'BOL_PLAZA_ORDERS_OWNOFFERS');
                self::handleOwnOffers($ownOffersResult, $suffix);
                self::updateOwnOffersStock($suffix);
                self::updateOwnOffersInfo($suffix);
                self::updateOwnOffersNew($suffix);
                $context->controller->confirmations[] = 'This file has been processed: ' . $url;
            } catch (Wienkit\BolPlazaClient\Exceptions\BolPlazaClientException $e) {
                Configuration::set($prefix . 'BOL_PLAZA_ORDERS_OWNOFFERS', $url);
                $context->controller->confirmations[] =  'The file will be generated by Bol.com. Click this' .
                    ' button again in a few minutes.';
            }
        }
    }
}
