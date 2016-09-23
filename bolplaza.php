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
require_once _PS_MODULE_DIR_.'bolplaza/classes/BolPlazaOrderItem.php';
require_once _PS_MODULE_DIR_.'bolplaza/classes/BolPlazaProduct.php';
require_once _PS_MODULE_DIR_.'bolplaza/controllers/admin/AdminBolPlazaProductsController.php';

class BolPlaza extends Module
{
    public function __construct()
    {
        $this->name = 'bolplaza';
        $this->tab = 'market_place';
        $this->version = '1.2.3';
        $this->author = 'Wienk IT';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = array('min' => '1.6', 'max' => _PS_VERSION_);
        $this->bootstrap = true;
        $this->module_key = 'f774cfaf352c30b37c7bfce545861c1e';

        $this->display = 'view';

        parent::__construct();

        $this->displayName = $this->l('Bol.com Plaza API connector');
        $this->description = $this->l('Connect to Bol.com Plaza to synchronize your Bol.com
                                       orders and products with your Prestashop website.');

        $this->confirmUninstall = $this->l('Are you sure you want to uninstall?');

    }

    /**
     * Overrides parent::install()
     */
    public function install()
    {
        if (parent::install()) {
            return $this->installDb()
                && $this->installOrderState()
                && $this->installOrdersTab()
                && $this->installProductsTab()
                && $this->registerHook('actionProductUpdate')
                && $this->registerHook('actionUpdateQuantity')
                && $this->registerHook('displayAdminProductsExtra')
                && $this->registerHook('actionObjectOrderCarrierUpdateAfter')
                && $this->registerHook('actionObjectBolPlazaProductAddAfter')
                && $this->registerHook('actionObjectBolPlazaProductDeleteAfter')
                && $this->registerHook('actionObjectBolPlazaProductUpdateAfter');
        }
        return false;
    }

    /**
     * Overrides parent::uninstall()
     */
    public function uninstall()
    {
        return $this->uninstallTabs()
          && $this->uninstallOrderState()
          && $this->uninstallDb()
          && $this->unregisterHook('actionProductUpdate')
          && $this->unregisterHook('actionUpdateQuantity')
          && $this->unregisterHook('displayAdminProductsExtra')
          && $this->unregisterHook('actionObjectOrderCarrierUpdateAfter')
          && $this->unregisterHook('actionObjectBolPlazaProductAddAfter')
          && $this->unregisterHook('actionObjectBolPlazaProductDeleteAfter')
          && $this->unregisterHook('actionObjectBolPlazaProductUpdateAfter')
          && parent::uninstall();
    }

    /**
     * Install the database tables
     * @return bool success
     */
    public function installDb()
    {
        $sql = array();
        $return = true;
        include(dirname(__FILE__).'/sql_install.php');
        foreach ($sql as $s) {
            $return &= Db::getInstance()->execute($s);
        }
        return $return;

    }

    /**
     * Remove the database tables
     * @return bool success
     */
    public function uninstallDb()
    {
        $sql = array();
        include(dirname(__FILE__).'/sql_install.php');
        foreach ($sql as $name => $v) {
            Db::getInstance()->execute('DROP TABLE IF EXISTS '.pSQL($name));
        }
        return true;
    }

    /**
     * Install a new order state for bol.com orders
     * @return bool success
     */
    public function installOrderState()
    {
        $orderStateName = 'Bol.com order imported';
        foreach (Language::getLanguages(true) as $lang) {
            $order_states = OrderState::getOrderStates($lang['id_lang']);
            foreach ($order_states as $state) {
                if ($state['name'] == $orderStateName) {
                    Configuration::updateValue('BOL_PLAZA_ORDERS_INITIALSTATE', $state['id_order_state']);
                    return true;
                }
            }
        }

        $order_state = new OrderState();
        $order_state->name = array();
        foreach (Language::getLanguages(true) as $lang) {
            $order_state->name[$lang['id_lang']] = $orderStateName;
        }

        $order_state->send_email = false;
        $order_state->module_name = $this->name;
        $order_state->invoice = false;
        $order_state->logable = true;
        $order_state->shipped = false;
        $order_state->unremovable = true;
        $order_state->delivery = false;
        $order_state->paid = true;
        $order_state->pdf_invoice = false;
        $order_state->pdf_delivery = false;
        $order_state->color = '#32CD32';
        $order_state->hidden = false;
        $order_state->deleted = false;
        $order_state->add();
        Configuration::updateValue('BOL_PLAZA_ORDERS_INITIALSTATE', $order_state->id);
        return true;
    }

    /**
     * Remove the Bol.com order state
     * @return bool success
     */
    public function uninstallOrderState()
    {
        Configuration::deleteByName('BOL_PLAZA_ORDERS_INITIALSTATE');
        return true;
    }

    /**
     * Install menu items
     * @return bool success
     */
    public function installOrdersTab()
    {
        $ordersTab = new Tab();
        $ordersTab->active = 1;
        $ordersTab->name = array();
        $ordersTab->class_name = 'AdminBolPlazaOrders';

        foreach (Language::getLanguages(true) as $lang) {
            $ordersTab->name[$lang['id_lang']] = 'Bol.com orders';
        }

        $ordersTab->id_parent = (int)Tab::getIdFromClassName('AdminParentOrders');
        $ordersTab->module = $this->name;

        return $ordersTab->add();
    }

    public function installProductsTab()
    {
        $productsTab = new Tab();
        $productsTab->active = 1;
        $productsTab->name = array();
        $productsTab->class_name = 'AdminBolPlazaProducts';

        foreach (Language::getLanguages(true) as $lang) {
            $productsTab->name[$lang['id_lang']] = 'Bol.com products';
        }

        $productsTab->id_parent = (int)Tab::getIdFromClassName('AdminCatalog');
        $productsTab->module = $this->name;

        return $productsTab->add();
    }

    /**
     * Remove menu items
     * @return bool success
     */
    public function uninstallTabs()
    {
        $id_tab = (int)Tab::getIdFromClassName('AdminBolPlazaOrders');
        if ($id_tab) {
            $tab = new Tab($id_tab);
            if (!$tab->delete()) {
                return false;
            }
        }
        $id_tab = (int)Tab::getIdFromClassName('AdminBolPlazaProducts');
        if ($id_tab) {
            $tab = new Tab($id_tab);
            if (!$tab->delete()) {
                return false;
            }
        }
        return true;
    }

    /**
     * Render the module configuration page
     * @return $output the rendered page
     */
    public function getContent()
    {
        $cron_url = Tools::getShopDomain(true, true).__PS_BASE_URI__.basename(_PS_MODULE_DIR_);
        $cron_url.= '/bolplaza/cron.php?secure_key='.md5(_COOKIE_KEY_.Configuration::get('PS_SHOP_NAME').'BOLPLAZA');

        $errors = array();
        if (!defined('PHP_VERSION_ID')) {
            $version = explode('.', PHP_VERSION);
            define('PHP_VERSION_ID', ($version[0] * 10000 + $version[1] * 100 + $version[2]));
        }

        if (PHP_VERSION_ID < 50500) {
            $errors[] = $this->l('Your PHP version is lower than 5.5, please update your PHP version first.');
        }

        if (!extension_loaded('curl')) {
            $errors[] = $this->l('You don\'t have the cURL php extension enabled, please enable it first.');
        }

        if (!extension_loaded('simplexml')) {
            $errors[] = $this->l('You don\'t have the simplexml php extension enabled, please enable it first.');
        }

        if (!extension_loaded('mbstring')) {
            $errors[] = $this->l('You don\'t have the mbstring php extension enabled, please enable it first.');
        }

        if (!extension_loaded('mcrypt')) {
            $errors[] = $this->l('You don\'t have the mcrypt php extension enabled, please enable it first.');
        }

        if (!extension_loaded('xsl')) {
            $errors[] = $this->l('You don\'t have the xsl php extension enabled, please enable it first.');
        }

        $version = explode('.', _PS_VERSION_);
        if (($version[0] * 10000 + $version[1] * 100 + $version[2]) < 10601) {
            $errors[] = $this->l(
                'Your Prestashop version is too low, please use 1.6.1.x or higher, 
                you can apply for a refund at the addons store.'
            );
        }

        $this->context->smarty->assign(array(
            'cron_url' => $cron_url,
            'module_dir' => $this->_path,
            'module_local_dir' => $this->local_path,
            'errors' => $errors
        ));
        $output = $this->context->smarty->fetch($this->local_path.'views/templates/admin/configure.tpl');

        if (Tools::isSubmit('submit'.$this->name)) {
            $enabled = (bool) Tools::getValue('bolplaza_orders_enabled');
            $testmode = (bool) Tools::getValue('bolplaza_orders_testmode');
            $privkey = (string) Tools::getValue('bolplaza_orders_privkey');
            $pubkey = (string) Tools::getValue('bolplaza_orders_pubkey');
            $carrier = (int) Tools::getValue('bolplaza_orders_carrier');
            $carrierCode = (string) Tools::getValue('bolplaza_orders_carrier_code');
            $deliveryCode = (string) Tools::getValue('bolplaza_orders_delivery_code');
            $freeShipping = (bool) Tools::getValue('bolplaza_orders_free_shipping');

            if (!$privkey
                || ! $pubkey
                || empty($privkey)
                || empty($pubkey)
                || empty($carrier)
                || empty($deliveryCode)
                || empty($carrierCode)) {
                $output .= $this->displayError($this->l('Invalid Configuration value'));
            } else {
                Configuration::updateValue('BOL_PLAZA_ORDERS_ENABLED', $enabled);
                Configuration::updateValue('BOL_PLAZA_ORDERS_TESTMODE', $testmode);
                Configuration::updateValue('BOL_PLAZA_ORDERS_PRIVKEY', $privkey);
                Configuration::updateValue('BOL_PLAZA_ORDERS_PUBKEY', $pubkey);
                Configuration::updateValue('BOL_PLAZA_ORDERS_CARRIER', $carrier);
                Configuration::updateValue('BOL_PLAZA_ORDERS_CARRIER_CODE', $carrierCode);
                Configuration::updateValue('BOL_PLAZA_ORDERS_DELIVERY_CODE', $deliveryCode);
                Configuration::updateValue('BOL_PLAZA_ORDERS_FREE_SHIPPING', $freeShipping);
                $output .= $this->displayConfirmation($this->l('Settings updated'));
            }
        }

        return $output.$this->displayForm();
    }

    /**
     * Render a form on the module configuration page
     * @return the form
     */
    public function displayForm()
    {
        // Get default language
        $default_lang = (int)Configuration::get('PS_LANG_DEFAULT');

        $carriers = Carrier::getCarriers(Context::getContext()->language->id);
        $delivery_codes = BolPlazaProduct::getDeliveryCodes();

        // Init Fields form array
        $fields_form = array();
        $fields_form[0]['form'] = array(
            'legend' => array(
                'title' => $this->l('Settings'),
                ),
            'input' => array(
                array(
                    'type' => 'switch',
                    'label' => $this->l('Enable Bol.com connector'),
                    'name' => 'bolplaza_orders_enabled',
                    'is_bool' => true,
                    'values' => array(
                        array(
                            'id' => 'bolplaza_orders_enabled_1',
                            'value' => 1,
                            'label' => $this->l('Yes'),
                        ),
                        array(
                            'id' => 'bolplaza_orders_enabled_0',
                            'value' => 0,
                            'label' => $this->l('No')
                        )
                    ),
                    'hint' => $this->l('You can enable the connector per shop.')
                ),
                array(
                    'type' => 'switch',
                    'label' => $this->l('Use test connection'),
                    'name' => 'bolplaza_orders_testmode',
                    'is_bool' => true,
                    'values' => array(
                        array(
                            'id' => 'bolplaza_orders_testmode_1',
                            'value' => 1,
                            'label' => $this->l('Yes'),
                        ),
                        array(
                            'id' => 'bolplaza_orders_testmode_0',
                            'value' => 0,
                            'label' => $this->l('No')
                        )
                    ),
                    'hint' => $this->l('Enables the testing connection.')
                ),
                array(
                    'type' => 'textarea',
                    'label' => $this->l('Bol.com Plaza API Public key'),
                    'name' => 'bolplaza_orders_pubkey',
                    'size' => 20
                ),
                array(
                    'type' => 'textarea',
                    'label' => $this->l('Bol.com Plaza API Private key'),
                    'name' => 'bolplaza_orders_privkey',
                    'size' => 20
                ),
                array(
                    'type' => 'select',
                    'label' => $this->l('Carrier'),
                    'desc' => $this->l('Choose a carrier for your Bol.com orders'),
                    'name' => 'bolplaza_orders_carrier',
                    'options' => array(
                        'query' => $carriers,
                        'id' => 'id_carrier',
                        'name' => 'name'
                    )
                ),
                array(
                    'type' => 'text',
                    'label' => $this->l('Carrier code'),
                    'desc' => $this->l('Bol.com code for the carrier'),
                    'name' => 'bolplaza_orders_carrier_code'
                ),
                array(
                    'type' => 'select',
                    'label' => $this->l('Delivery code'),
                    'desc' => $this->l('Choose a delivery code for your Bol.com products'),
                    'name' => 'bolplaza_orders_delivery_code',
                    'options' => array(
                        'query' => $delivery_codes,
                        'id' => 'deliverycode',
                        'name' => 'description'
                    )
                ),
                array(
                    'type' => 'switch',
                    'label' => $this->l('Use free shipping'),
                    'name' => 'bolplaza_orders_free_shipping',
                    'is_bool' => true,
                    'values' => array(
                        array(
                            'id' => 'bolplaza_orders_free_shipping_1',
                            'value' => 1,
                            'label' => $this->l('Yes'),
                        ),
                        array(
                            'id' => 'bolplaza_orders_free_shipping_0',
                            'value' => 0,
                            'label' => $this->l('No')
                        )
                    ),
                    'hint' => $this->l('Don\'t calculate shipping costs.')
                ),
            ),
            'submit' => array(
                'title' => $this->l('Save'),
                'class' => 'btn btn-default pull-right'
                )
            );

        $helper = new HelperForm();

        // Module, token and currentIndex
        $helper->module = $this;
        $helper->name_controller = $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex.'&configure='.$this->name;

        // Language
        $helper->default_form_language = $default_lang;
        $helper->allow_employee_form_lang = $default_lang;

        // Title and toolbar
        $helper->title = $this->displayName;
        $helper->show_toolbar = true;
        $helper->toolbar_scroll = true;
        $helper->submit_action = 'submit'.$this->name;
        $helper->toolbar_btn = array(
            'save' =>
            array(
                'desc' => $this->l('Save'),
                'href' => AdminController::$currentIndex.'&configure='.$this->name.'&save'.$this->name.
                '&token='.Tools::getAdminTokenLite('AdminModules'),
                ),
            'back' => array(
                'href' => AdminController::$currentIndex.'&token='.Tools::getAdminTokenLite('AdminModules'),
                'desc' => $this->l('Back to list')
                )
            );

        // Load current value
        $helper->fields_value['bolplaza_orders_enabled'] = Configuration::get('BOL_PLAZA_ORDERS_ENABLED');
        $helper->fields_value['bolplaza_orders_testmode'] = Configuration::get('BOL_PLAZA_ORDERS_TESTMODE');
        $helper->fields_value['bolplaza_orders_privkey'] = Configuration::get('BOL_PLAZA_ORDERS_PRIVKEY');
        $helper->fields_value['bolplaza_orders_pubkey'] = Configuration::get('BOL_PLAZA_ORDERS_PUBKEY');
        $helper->fields_value['bolplaza_orders_carrier'] = Configuration::get('BOL_PLAZA_ORDERS_CARRIER');
        $helper->fields_value['bolplaza_orders_carrier_code'] = Configuration::get('BOL_PLAZA_ORDERS_CARRIER_CODE');
        $helper->fields_value['bolplaza_orders_delivery_code'] = Configuration::get('BOL_PLAZA_ORDERS_DELIVERY_CODE');
        $helper->fields_value['bolplaza_orders_free_shipping'] = Configuration::get('BOL_PLAZA_ORDERS_FREE_SHIPPING');

        return $helper->generateForm($fields_form);
    }

    /**
     * Retrieve the BolPlaza client
     * @return Picqer\BolPlazaClient\BolPlazaClient
     */
    public static function getClient()
    {
        $publickey = Configuration::get('BOL_PLAZA_ORDERS_PUBKEY');
        $privatekey = Configuration::get('BOL_PLAZA_ORDERS_PRIVKEY');

        $client = new Picqer\BolPlazaClient\BolPlazaClient($publickey, $privatekey);
        if ((bool)Configuration::get('BOL_PLAZA_ORDERS_TESTMODE')) {
            $client->setTestMode(true);
        }
        return $client;
    }

    /**
     * Calculate the delivery date of a shipment
     * @return Date
     */
    private function getDeliveryDate()
    {
        $codes = BolPlazaProduct::getDeliveryCodes();
        $deliverycode = end($codes);
        foreach ($codes as $code) {
            if ($code['deliverycode'] == Configuration::get('BOL_PLAZA_ORDERS_DELIVERY_CODE')) {
                $deliverycode = $code;
            }
        }
        $addedWeekdays = $deliverycode['addtime'];
        if (date('H') >= $deliverycode['shipsuntil']) {
            $addedWeekdays++;
        }
        return date('Y-m-d\T18:00:00', strtotime('+ ' . $addedWeekdays . ' Weekdays'));
    }

    /**
     * Update a shipment to Bol.com
     * Executes hook: actionObjectOrderCarrierUpdateAfter
     * @param array $param
     */
    public function hookActionObjectOrderCarrierUpdateAfter($params)
    {
        $orderCarrier = $params['object'];
        if ($orderCarrier->tracking_number) {
            $order = new Order($orderCarrier->id_order);
            if ($order->module == 'bolplaza' || $order->module == 'bolplazatest') {
                $Plaza = self::getClient();
                $itemsShipped = array();
                $items = BolPlazaOrderItem::getByOrderId($order->id);
                foreach ($items as $item) {
                    $shipment = new Picqer\BolPlazaClient\Entities\BolPlazaShipmentRequest();
                    $shipment->OrderItemId = $item->id_bol_order_item;
                    $shipment->ShipmentReference = $order->reference . '-' . $orderCarrier->id;
                    $shipment->DateTime = date('Y-m-d\TH:i:s');
                    $shipment->ExpectedDeliveryDate = $this->getDeliveryDate();
                    $transport = new Picqer\BolPlazaClient\Entities\BolPlazaTransport();
                    $transport->TransporterCode = Configuration::get('BOL_PLAZA_ORDERS_CARRIER_CODE');
                    $transport->TrackAndTrace = $orderCarrier->tracking_number;
                    $shipment->Transport = $transport;
                    $itemsShipped[] = $item;
                    $Plaza->processShipment($shipment);
                }
                foreach ($itemsShipped as $item) {
                    $item->setShipped();
                }
            }
        }
    }

    /**
     * Add a new tab to the product page
     * Executes hook: displayAdminProductsExtra
     * @param array $param
     */
    public function hookDisplayAdminProductsExtra($params)
    {
        if (!Configuration::get('BOL_PLAZA_ORDERS_ENABLED')) {
            return $this->display(__FILE__, 'views/templates/admin/disabled.tpl');
        }
        if ($id_product = (int)Tools::getValue('id_product')) {
            $product = new Product($id_product, true, $this->context->language->id, $this->context->shop->id);
        }
        if (!Validate:: isLoadedObject($product)) {
            return;
        }

        $attributes = $product->getAttributesResume($this->context->language->id);

        if (empty($attributes)) {
            $attributes[] = array(
                'id_product' => $product->id,
                'id_product_attribute' => 0,
                'attribute_designation' => ''
            );
        }

        $product_designation = array();

        foreach ($attributes as $attribute) {
            $product_designation[$attribute['id_product_attribute']] = rtrim(
                $product->name .' - ' . $attribute['attribute_designation'],
                ' - '
            );
        }

        $bolProducts = BolPlazaProduct::getByProductId($id_product);
        $indexedBolProducts = array();
        foreach ($bolProducts as $bolProduct) {
            $indexedBolProducts[$bolProduct['id_product_attribute']] = $bolProduct;
        }

        $this->context->smarty->assign(array(
            'attributes' => $attributes,
            'product_designation' => $product_designation,
            'product' => $product,
            'bol_products' => $indexedBolProducts,
            'delivery_codes' => BolPlazaProduct::getDeliveryCodes()
        ));

        return $this->display(__FILE__, 'views/templates/admin/bolproduct.tpl');
    }

    /**
     * Process BolProduct entities added on the product page
     * Executes hook: actionProductUpdate
     * @param array $param
     */
    public function hookActionProductUpdate($params)
    {
        if ((int)Tools::getValue('bolplaza_loaded') === 1
             && Validate::isLoadedObject($product = new Product((int)$params['id_product']))) {
            $this->processBolProductEntities($product);
        }
    }

    /**
     * Process the Bol.com products for a product
     * @param Product $product
     */
    private function processBolProductEntities($product)
    {
        // Get all id_product_attribute
        $attributes = $product->getAttributesResume($this->context->language->id);
        if (empty($attributes)) {
            $attributes[] = array(
                'id_product_attribute' => 0,
                'attribute_designation' => ''
            );
        }

        $bolProducts = BolPlazaProduct::getByProductId($product->id);
        $indexedBolProducts = array();
        foreach ($bolProducts as $bolProduct) {
            $indexedBolProducts[$bolProduct['id_product_attribute']] = $bolProduct;
        }

        // get form inforamtion
        foreach ($attributes as $attribute) {
            $key = $product->id.'_'.$attribute['id_product_attribute'];

            // get elements to manage
            $published = Tools::getValue('bolplaza_published_'.$key);
            $price = Tools::getValue('bolplaza_price_'.$key, 0);
            $ean = Tools::getValue('bolplaza_ean_'.$key);
            $delivery_time = Tools::getValue('bolplaza_delivery_time_'.$key);

            if (array_key_exists($attribute['id_product_attribute'], $indexedBolProducts)) {
                $bolProduct = new BolPlazaProduct(
                    $indexedBolProducts[$attribute['id_product_attribute']]['id_bolplaza_product']
                );
                if (
                    $bolProduct->price == $price &&
                    $bolProduct->published == $published &&
                    $bolProduct->ean == $ean &&
                    $bolProduct->delivery_time == $delivery_time
                ) {
                    continue;
                }
                $bolProduct->status = BolPlazaProduct::STATUS_INFO_UPDATE;
            } elseif (!$published && $price == 0) {
                continue;
            } else {
                $bolProduct = new BolPlazaProduct();
            }

            $bolProduct->id_product = $product->id;
            $bolProduct->id_product_attribute = $attribute['id_product_attribute'];
            $bolProduct->price = $price;
            $bolProduct->published = $published;
            $bolProduct->ean = $ean;
            $bolProduct->delivery_time = $delivery_time;

            if (!$bolProduct->published && $price == 0) {
                $bolProduct->delete();
            } else {
                $bolProduct->save();
            }
        }
    }

    /**
     * Send a creation request to Bol.com
     * Executes hook: actionObjectBolPlazaProductAddAfter
     * @param array $param
     */
    public function hookActionObjectBolPlazaProductAddAfter($param)
    {
        if (!empty($param['object'])) {
            AdminBolPlazaProductsController::processBolProductCreate($param['object'], $this->context);
        }
    }

    /**
     * Send an update request to Bol.com
     * Executes hook: actionObjectBolPlazaProductUpdateAfter
     * @param array $param
     */
    public function hookActionObjectBolPlazaProductUpdateAfter($param)
    {
        if (!empty($param['object'])) {
            AdminBolPlazaProductsController::setProductStatus(
                $param['object'],
                (int)BolPlazaProduct::STATUS_INFO_UPDATE
            );
            AdminBolPlazaProductsController::processBolProductUpdate($param['object'], $this->context);
        }
    }

    /**
     * Send stock updates to Bol.com
     * Executes hook: actionUpdateQuantity
     * @param array $param
     */
    public function hookActionUpdateQuantity($param)
    {
        $bolProductId = BolPlazaProduct::getIdByProductAndAttributeId(
            $param['id_product'],
            $param['id_product_attribute']
        );
        if (!empty($bolProductId)) {
            $bolProduct = new BolPlazaProduct($bolProductId);
            AdminBolPlazaProductsController::setProductStatus($bolProduct, (int)BolPlazaProduct::STATUS_STOCK_UPDATE);
            AdminBolPlazaProductsController::processBolQuantityUpdate($bolProduct, $param['quantity'], $this->context);
        }
    }

    /**
     * Send a product deletion request to Bol.com
     * Executes hook: actionObjectBolPlazaProductDeleteAfter
     * @param array $param
     */
    public function hookActionObjectBolPlazaProductDeleteAfter($param)
    {
        if (!empty($param['object'])) {
            AdminBolPlazaProductsController::processBolProductDelete($param['object'], $this->context);
        }
    }

    /**
     * Synchronize the orders
     */
    public static function synchronize()
    {
        AdminBolPlazaOrdersController::synchronize();
    }
}
