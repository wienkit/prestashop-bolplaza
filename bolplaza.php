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
require_once _PS_MODULE_DIR_.'bolplaza/classes/BolPlazaOrderItem.php';
require_once _PS_MODULE_DIR_.'bolplaza/classes/BolPlazaProduct.php';
require_once _PS_MODULE_DIR_.'bolplaza/controllers/admin/AdminBolPlazaProductsController.php';

class BolPlaza extends Module
{
    const PREFIX_SECONDARY_ACCOUNT = "2_";
    const DB_SUFFIX_SECONDARY_ACCOUNT = "_2";

    public function __construct()
    {
        $this->name = 'bolplaza';
        $this->tab = 'market_place';
        $this->version = '1.4.2';
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
     * @throws PrestaShopException
     */
    public function install()
    {
        if (parent::install()) {
            return $this->installDb()
                && $this->installOrderState()
                && $this->installOrdersTab()
                && $this->installProductsTab()
                && $this->registerHook('actionProductUpdate')
                && $this->registerHook('actionProductDelete')
                && $this->registerHook('actionProductAttributeDelete')
                && $this->registerHook('actionUpdateQuantity')
                && $this->registerHook('displayAdminProductsExtra')
                && $this->registerHook('actionObjectOrderCarrierUpdateAfter')
                && $this->registerHook('actionObjectBolPlazaProductAddAfter')
                && $this->registerHook('actionObjectBolPlazaProductDeleteAfter')
                && $this->registerHook('actionObjectBolPlazaProductUpdateAfter')
                && $this->registerHook('actionObjectSpecificPriceDeleteAfter')
                && $this->registerHook('actionObjectSpecificPriceUpdateAfter')
                && $this->registerHook('actionObjectSpecificPriceAddAfter');
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
          && $this->unregisterHook('actionObjectSpecificPriceDeleteAfter')
          && $this->unregisterHook('actionObjectSpecificPriceUpdateAfter')
          && $this->unregisterHook('actionObjectSpecificPriceAddAfter')
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
        foreach (array_keys($sql) as $name) {
            Db::getInstance()->execute('DROP TABLE IF EXISTS '.pSQL($name));
        }
        return true;
    }

    /**
     * Install a new order state for bol.com orders
     * @return bool success
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function installOrderState()
    {
        $orderStateName = 'Bol.com order imported';
        foreach (Language::getLanguages(true) as $lang) {
            $order_states = OrderState::getOrderStates($lang['id_lang']);
            foreach ($order_states as $state) {
                if ($state['name'] == $orderStateName) {
                    Configuration::updateValue(
                        'BOL_PLAZA_ORDERS_INITIALSTATE',
                        $state['id_order_state'],
                        false,
                        null,
                        null
                    );
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
        Configuration::updateValue(
            'BOL_PLAZA_ORDERS_INITIALSTATE',
            $order_state->id,
            false,
            null,
            null
        );
        return true;
    }

    /**
     * Remove the Bol.com order state
     * @return bool success
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function uninstallOrderState()
    {
        $order_state = new OrderState(Configuration::get('BOL_PLAZA_ORDERS_INITIALSTATE'));
        $order_state->hidden = true;
        $order_state->save();
        return true;
    }

    /**
     * Install menu items
     * @return bool success
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
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
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
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
     * @return string $output the rendered page
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function getContent()
    {
        $cron_url = Tools::getShopDomainSsl(true, true).__PS_BASE_URI__.basename(_PS_MODULE_DIR_);
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

        if (PHP_VERSION_ID < 70200 && !extension_loaded('mcrypt')) {
            $errors[] = $this->l('You don\'t have the mcrypt php extension enabled, please enable it first.');
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
            $useAddress2 = (bool) Tools::getValue('bolplaza_orders_use_address2');
            $useSplitted = (bool) Tools::getValue('bolplaza_orders_enable_splitted');
            $languageId = (int) Tools::getValue('bolplaza_orders_language_id');

            Configuration::updateValue('BOL_PLAZA_ORDERS_ENABLED', $enabled);
            Configuration::updateValue('BOL_PLAZA_ORDERS_TESTMODE', $testmode);
            Configuration::updateValue('BOL_PLAZA_ORDERS_USE_ADDRESS2', $useAddress2);
            Configuration::updateValue('BOL_PLAZA_ORDERS_ENABLE_SPLITTED', $useSplitted);
            Configuration::updateValue('BOL_PLAZA_ORDERS_LANGUAGE_ID', $languageId);

            // Primary account settings
            $privkey = (string) Tools::getValue('bolplaza_orders_privkey');
            $pubkey = (string) Tools::getValue('bolplaza_orders_pubkey');
            $carrier = (int) Tools::getValue('bolplaza_orders_carrier');
            $carrierCode = (string) Tools::getValue('bolplaza_orders_carrier_code');
            $deliveryCode = (string) Tools::getValue('bolplaza_orders_delivery_code');
            $customerGroup = (int) Tools::getValue('bolplaza_orders_customer_group');
            $freeShipping = (bool) Tools::getValue('bolplaza_orders_free_shipping');
            $updatePrices = (bool) Tools::getValue('bolplaza_orders_update_prices');

            if (!$privkey
                || ! $pubkey
                || empty($privkey)
                || empty($pubkey)
                || empty($carrier)
                || empty($deliveryCode)
                || empty($carrierCode)
                || empty($customerGroup)) {
                $output .= $this->displayError(
                    $this->l('Invalid Configuration value in primary account settings')
                );
            } else {
                Configuration::updateValue('BOL_PLAZA_ORDERS_PRIVKEY', $privkey);
                Configuration::updateValue('BOL_PLAZA_ORDERS_PUBKEY', $pubkey);
                Configuration::updateValue('BOL_PLAZA_ORDERS_CARRIER', $carrier);
                Configuration::updateValue('BOL_PLAZA_ORDERS_CARRIER_CODE', $carrierCode);
                Configuration::updateValue('BOL_PLAZA_ORDERS_DELIVERY_CODE', $deliveryCode);
                Configuration::updateValue('BOL_PLAZA_ORDERS_CUSTOMER_GROUP', $customerGroup);
                Configuration::updateValue('BOL_PLAZA_ORDERS_FREE_SHIPPING', $freeShipping);
                $output .= $this->displayConfirmation($this->l('Settings updated'));
            }

            // Secondary account settings
            $privkey2 = (string) Tools::getValue(self::PREFIX_SECONDARY_ACCOUNT . 'bolplaza_orders_privkey');
            $pubkey2 = (string) Tools::getValue(self::PREFIX_SECONDARY_ACCOUNT . 'bolplaza_orders_pubkey');
            $carrier2 = (int) Tools::getValue(self::PREFIX_SECONDARY_ACCOUNT . 'bolplaza_orders_carrier');
            $carrierCode2 = (string) Tools::getValue(self::PREFIX_SECONDARY_ACCOUNT . 'bolplaza_orders_carrier_code');
            $delivCode2 = (string) Tools::getValue(self::PREFIX_SECONDARY_ACCOUNT . 'bolplaza_orders_delivery_code');
            $customerGroup2 = (int) Tools::getValue(self::PREFIX_SECONDARY_ACCOUNT . 'bolplaza_orders_customer_group');
            $freeShipping2 = (bool) Tools::getValue(self::PREFIX_SECONDARY_ACCOUNT . 'bolplaza_orders_free_shipping');

            if ($useSplitted) {
                if (!$privkey2
                    || !$pubkey2
                    || empty($privkey2)
                    || empty($pubkey2)
                    || empty($carrier2)
                    || empty($delivCode2)
                    || empty($carrierCode2)
                    || empty($customerGroup2)) {
                    $output .= $this->displayError(
                        $this->l('Invalid Configuration value in secondary account settings')
                    );
                } else {
                    Configuration::updateValue(
                        self::PREFIX_SECONDARY_ACCOUNT . 'BOL_PLAZA_ORDERS_PRIVKEY',
                        $privkey2
                    );
                    Configuration::updateValue(
                        self::PREFIX_SECONDARY_ACCOUNT . 'BOL_PLAZA_ORDERS_PUBKEY',
                        $pubkey2
                    );
                    Configuration::updateValue(
                        self::PREFIX_SECONDARY_ACCOUNT . 'BOL_PLAZA_ORDERS_CARRIER',
                        $carrier2
                    );
                    Configuration::updateValue(
                        self::PREFIX_SECONDARY_ACCOUNT . 'BOL_PLAZA_ORDERS_CARRIER_CODE',
                        $carrierCode2
                    );
                    Configuration::updateValue(
                        self::PREFIX_SECONDARY_ACCOUNT . 'BOL_PLAZA_ORDERS_DELIVERY_CODE',
                        $delivCode2
                    );
                    Configuration::updateValue(
                        self::PREFIX_SECONDARY_ACCOUNT . 'BOL_PLAZA_ORDERS_CUSTOMER_GROUP',
                        $customerGroup2
                    );
                    Configuration::updateValue(
                        self::PREFIX_SECONDARY_ACCOUNT . 'BOL_PLAZA_ORDERS_FREE_SHIPPING',
                        $freeShipping2
                    );
                }
            }

            // Pricing rules settings
            $multiplication = (double) Tools::getValue('bolplaza_price_multiplication');
            if (!empty($multiplication)) {
                Configuration::updateValue('BOL_PLAZA_PRICE_MULTIPLICATION', $multiplication);
            }

            $addition = (double) Tools::getValue('bolplaza_price_addition');
            if (!empty($addition)) {
                Configuration::updateValue('BOL_PLAZA_PRICE_ADDITION', $addition);
            }

            $roundup = (double) Tools::getValue('bolplaza_price_roundup');
            if (!empty($roundup)) {
                Configuration::updateValue('BOL_PLAZA_PRICE_ROUNDUP', $roundup);
            }

            if ($updatePrices) {
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
                        if (isset($id_product_attribute) && $id_product_attribute > 0) {
                            $combination = new Combination($id_product_attribute);
                        } else {
                            $combination = new Product($id_product);
                        }
                        $product->ean = $combination->ean13;
                        $changed = true;
                    }
                    if ($changed) {
                        $product->save();
                    }
                }
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


        $languages = Language::getLanguages();

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
                    'type' => 'switch',
                    'label' => $this->l('Housenumber in address2'),
                    'name' => 'bolplaza_orders_use_address2',
                    'is_bool' => true,
                    'values' => array(
                        array(
                            'id' => 'bolplaza_orders_use_address2_1',
                            'value' => 1,
                            'label' => $this->l('Yes'),
                        ),
                        array(
                            'id' => 'bolplaza_orders_use_address2_0',
                            'value' => 0,
                            'label' => $this->l('No')
                        )
                    ),
                    'desc' => $this->l('Won\'t append housenumber to street but uses separate field for housenumber')
                ),
                array(
                    'type' => 'switch',
                    'label' => $this->l('Use splitted account (for Belgium/Netherlands)'),
                    'name' => 'bolplaza_orders_enable_splitted',
                    'is_bool' => true,
                    'values' => array(
                        array(
                            'id' => 'bolplaza_orders_enable_splitted_1',
                            'value' => 1,
                            'label' => $this->l('Yes'),
                        ),
                        array(
                            'id' => 'bolplaza_orders_enable_splitted_0',
                            'value' => 0,
                            'label' => $this->l('No')
                        )
                    ),
                    'desc' => $this->l('You can enable the splitted account functionality so you can handle Belgium and Netherlands separately.')
                ),
                array(
                    'type' => 'select',
                    'label' => $this->l('Language'),
                    'desc' => $this->l('Choose which language should be used for your Bol.com products'),
                    'name' => 'bolplaza_orders_language_id',
                    'options' => array(
                        'query' => $languages,
                        'id' => 'id_lang',
                        'name' => 'name'
                    )
                ),
            ),
            'submit' => array(
                'title' => $this->l('Save'),
                'class' => 'btn btn-default pull-right'
            )
        );


        $fields_form[1]['form'] = array(
            'legend' => array(
                'title' => $this->l('Settings primary account'),
            ),
            'input' => $this->getAccountFields(),
            'submit' => array(
                'title' => $this->l('Save'),
                'class' => 'btn btn-default pull-right'
            )
        );

        $fields_form[2]['form'] = array(
            'legend' => array(
                'title' => $this->l('Settings secondary account'),
            ),
            'input' => $this->getAccountFields(self::PREFIX_SECONDARY_ACCOUNT),
            'submit' => array(
                'title' => $this->l('Save'),
                'class' => 'btn btn-default pull-right'
            )
        );


        $fields_form[3]['form'] = array(
            'legend' => array(
                'title' => $this->l('Pricing settings'),
            ),
            'description' => $this->l(
                'These settings are used to generate default pricing settings per product,
                you can always override the price per product.'
            ),
            'input' => array(
                array(
                    'type' => 'text',
                    'label' => $this->l('Addition amount'),
                    'desc' => $this->l('Adds the amount to the normal price (incl. VAT), for example 1 for € 1,00'),
                    'name' => 'bolplaza_price_addition',
                    'size' => 20
                ),
                array(
                    'type' => 'text',
                    'label' => $this->l('Multiplication factor'),
                    'desc' => $this->l(
                        'Multiply the normal price (incl. VAT and addition amount) with this factor, for example 1.20 for 20 percent'
                    ),
                    'name' => 'bolplaza_price_multiplication',
                    'size' => 20
                ),
                array(
                    'type' => 'text',
                    'label' => $this->l('Round up amount'),
                    'desc' => $this->l('Round the amount up to a specific unit. For example, use 0.10 to round from 1.52 to 1.60'),
                    'name' => 'bolplaza_price_roundup',
                    'size' => 20
                ),
                array(
                    'type' => 'switch',
                    'is_bool' => true,
                    'values' => array(
                        array(
                            'id' => 'bolplaza_orders_update_prices_1',
                            'value' => 1,
                            'label' => $this->l('Yes'),
                        ),
                        array(
                            'id' => 'bolplaza_orders_update_prices_0',
                            'value' => 0,
                            'label' => $this->l('No')
                        )
                    ),
                    'label' => $this->l('Update pricing'),
                    'desc' => $this->l('Use this option to update your pricing after release 1.3.5,' .
                        ' which makes use of the difference instead of the value of the price.'),
                    'name' => 'bolplaza_orders_update_prices',
                )
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
        $helper->fields_value['bolplaza_orders_use_address2'] = Configuration::get('BOL_PLAZA_ORDERS_USE_ADDRESS2');
        $helper->fields_value['bolplaza_orders_enable_splitted'] =
            Configuration::get('BOL_PLAZA_ORDERS_ENABLE_SPLITTED');
        $helper->fields_value['bolplaza_orders_language_id'] = Configuration::get('BOL_PLAZA_ORDERS_LANGUAGE_ID', $this->context->language->id);

        // Primary account values
        $helper->fields_value['bolplaza_orders_privkey'] = Configuration::get('BOL_PLAZA_ORDERS_PRIVKEY');
        $helper->fields_value['bolplaza_orders_pubkey'] = Configuration::get('BOL_PLAZA_ORDERS_PUBKEY');
        $helper->fields_value['bolplaza_orders_carrier'] = Configuration::get('BOL_PLAZA_ORDERS_CARRIER');
        $helper->fields_value['bolplaza_orders_carrier_code'] = Configuration::get('BOL_PLAZA_ORDERS_CARRIER_CODE');
        $helper->fields_value['bolplaza_orders_delivery_code'] = Configuration::get('BOL_PLAZA_ORDERS_DELIVERY_CODE');
        $customerGroup = Configuration::get('BOL_PLAZA_ORDERS_CUSTOMER_GROUP');
        if (empty($customerGroup)) {
            $customerGroup = Configuration::get('PS_CUSTOMER_GROUP');
        }
        $helper->fields_value['bolplaza_orders_customer_group'] = $customerGroup;
        $freeShipping = Configuration::get('BOL_PLAZA_ORDERS_FREE_SHIPPING');
        if (empty($freeShipping)) {
            $freeShipping = true;
        }
        $helper->fields_value['bolplaza_orders_free_shipping'] = $freeShipping;

        // Secondary account values
        $helper->fields_value[self::PREFIX_SECONDARY_ACCOUNT . 'bolplaza_orders_privkey'] =
            Configuration::get(self::PREFIX_SECONDARY_ACCOUNT . 'BOL_PLAZA_ORDERS_PRIVKEY');
        $helper->fields_value[self::PREFIX_SECONDARY_ACCOUNT . 'bolplaza_orders_pubkey'] =
            Configuration::get(self::PREFIX_SECONDARY_ACCOUNT . 'BOL_PLAZA_ORDERS_PUBKEY');
        $helper->fields_value[self::PREFIX_SECONDARY_ACCOUNT . 'bolplaza_orders_carrier'] =
            Configuration::get(self::PREFIX_SECONDARY_ACCOUNT . 'BOL_PLAZA_ORDERS_CARRIER');
        $helper->fields_value[self::PREFIX_SECONDARY_ACCOUNT . 'bolplaza_orders_carrier_code'] =
            Configuration::get(self::PREFIX_SECONDARY_ACCOUNT . 'BOL_PLAZA_ORDERS_CARRIER_CODE');
        $helper->fields_value[self::PREFIX_SECONDARY_ACCOUNT . 'bolplaza_orders_delivery_code'] =
            Configuration::get(self::PREFIX_SECONDARY_ACCOUNT . 'BOL_PLAZA_ORDERS_DELIVERY_CODE');
        $customerGroup2 = Configuration::get(self::PREFIX_SECONDARY_ACCOUNT . 'BOL_PLAZA_ORDERS_CUSTOMER_GROUP');
        if (empty($customerGroup2)) {
            $customerGroup2 = Configuration::get('PS_CUSTOMER_GROUP');
        }
        $helper->fields_value[self::PREFIX_SECONDARY_ACCOUNT . 'bolplaza_orders_customer_group'] = $customerGroup2;
        $freeShipping2 = Configuration::get(self::PREFIX_SECONDARY_ACCOUNT . 'BOL_PLAZA_ORDERS_FREE_SHIPPING');
        if (empty($freeShipping2)) {
            $freeShipping2 = true;
        }
        $helper->fields_value[self::PREFIX_SECONDARY_ACCOUNT . 'bolplaza_orders_free_shipping'] = $freeShipping2;

        $helper->fields_value['bolplaza_price_addition'] = Configuration::get('BOL_PLAZA_PRICE_ADDITION');
        $helper->fields_value['bolplaza_price_multiplication'] = Configuration::get('BOL_PLAZA_PRICE_MULTIPLICATION');
        $helper->fields_value['bolplaza_price_roundup'] = Configuration::get('BOL_PLAZA_PRICE_ROUNDUP');
        $helper->fields_value['bolplaza_orders_update_prices'] = 0;

        return $helper->generateForm($fields_form);
    }

    public function getAccountFields($account_prefix = "")
    {
        $carriers = Carrier::getCarriers(
            Context::getContext()->language->id,
            false,
            false,
            false,
            null,
            Carrier::ALL_CARRIERS
        );

        $delivery_codes = BolPlazaProduct::getDeliveryCodes();
        $customer_groups = Group::getGroups(Context::getContext()->language->id);

        return array(
            array(
                'type' => 'textarea',
                'label' => $this->l('Bol.com Plaza API Public key'),
                'name' => $account_prefix . 'bolplaza_orders_pubkey',
                'size' => 20
            ),
            array(
                'type' => 'textarea',
                'label' => $this->l('Bol.com Plaza API Private key'),
                'name' => $account_prefix . 'bolplaza_orders_privkey',
                'size' => 20
            ),
            array(
                'type' => 'select',
                'label' => $this->l('Carrier'),
                'desc' => $this->l('Choose a carrier for your Bol.com orders'),
                'name' => $account_prefix . 'bolplaza_orders_carrier',
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
                'name' => $account_prefix . 'bolplaza_orders_carrier_code'
            ),
            array(
                'type' => 'select',
                'label' => $this->l('Delivery code'),
                'desc' => $this->l('Choose a delivery code for your Bol.com products'),
                'name' => $account_prefix . 'bolplaza_orders_delivery_code',
                'options' => array(
                    'query' => $delivery_codes,
                    'id' => 'deliverycode',
                    'name' => 'description'
                )
            ),
            array(
                'type' => 'select',
                'label' => $this->l('Customer group'),
                'desc' => $this->l('Choose a customer group for your Bol.com customers'),
                'name' => $account_prefix . 'bolplaza_orders_customer_group',
                'options' => array(
                    'query' => $customer_groups,
                    'id' => 'id_group',
                    'name' => 'name'
                )
            ),
            array(
                'type' => 'switch',
                'label' => $this->l('Use free shipping'),
                'name' => $account_prefix . 'bolplaza_orders_free_shipping',
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
            )
        );
    }

    /**
     * Retrieve the BolPlaza client
     * @return Wienkit\BolPlazaClient\BolPlazaClient
     */
    public static function getClient($prefix = '')
    {
        $publickey = Configuration::get($prefix . 'BOL_PLAZA_ORDERS_PUBKEY');
        $privatekey = Configuration::get($prefix . 'BOL_PLAZA_ORDERS_PRIVKEY');

        $client = new Wienkit\BolPlazaClient\BolPlazaClient($publickey, $privatekey);
        if ((bool)Configuration::get('BOL_PLAZA_ORDERS_TESTMODE')) {
            $client->setTestMode(true);
        }
        return $client;
    }

    /**
     * Returns an array of BolPlaza clients, indexed by id
     * @return \Wienkit\BolPlazaClient\BolPlazaClient[]
     */
    public static function getClients()
    {
        $clients = array(0 => self::getClient());
        if (Configuration::get('BOL_PLAZA_ORDERS_ENABLE_SPLITTED')) {
            $clients[1] = self::getClient(self::PREFIX_SECONDARY_ACCOUNT);
        }
        return $clients;
    }

    /**
     * Calculate the delivery date of a shipment
     * @return false|string
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
     * @param array $params
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function hookActionObjectOrderCarrierUpdateAfter($params)
    {
        $orderCarrier = $params['object'];
        if ($orderCarrier->tracking_number) {
            $order = new Order($orderCarrier->id_order);
            if ($order->module == 'bolplaza_payment' || $order->module == 'bolplazatest') {
                $clients = self::getClients();
                $itemsShipped = array();
                $items = BolPlazaOrderItem::getByOrderId($order->id);
                foreach ($items as $item) {
                    $shipment = new Wienkit\BolPlazaClient\Entities\BolPlazaShipmentRequest();
                    $shipment->OrderItemId = $item->id_bol_order_item;
                    $shipment->ShipmentReference = $order->reference . '-' . $orderCarrier->id;
                    $shipment->DateTime = date('Y-m-d\TH:i:s');
                    $shipment->ExpectedDeliveryDate = $this->getDeliveryDate();
                    $transport = new Wienkit\BolPlazaClient\Entities\BolPlazaTransport();
                    $transport->TransporterCode = Configuration::get('BOL_PLAZA_ORDERS_CARRIER_CODE');
                    $transport->TrackAndTrace = $orderCarrier->tracking_number;
                    $shipment->Transport = $transport;
                    $itemsShipped[] = $item;
                    $client = $clients[$item->id_client];
                    if (isset($client)) {
                        $client->processShipment($shipment);
                    }
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
     * @param $params
     * @return string
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function hookDisplayAdminProductsExtra($params)
    {
        if (!Configuration::get('BOL_PLAZA_ORDERS_ENABLED')) {
            return $this->display(__FILE__, 'views/templates/admin/disabled.tpl');
        }
        $id_product = isset($params['id_product']) ? $params['id_product'] : false;
        if ($id_product = (int)Tools::getValue('id_product', $id_product)) {
            $product = new Product($id_product, true, $this->context->language->id, $this->context->shop->id);
        }
        if (!Validate::isLoadedObject($product)) {
            return "";
        }

        $attributes = $product->getAttributesResume($this->context->language->id);

        if (empty($attributes)) {
            $attributes[] = array(
                'id_product' => $product->id,
                'id_product_attribute' => 0,
                'attribute_designation' => $product->name,
                'ean13' => $product->ean13
            );
        }

        $product_designation = array();
        $product_calculatedprice = array();
        $product_baseprice = array();

        $addition = (double) Configuration::get('BOL_PLAZA_PRICE_ADDITION');
        $multiplication = (double) Configuration::get('BOL_PLAZA_PRICE_MULTIPLICATION');
        $roundup = (double) Configuration::get('BOL_PLAZA_PRICE_ROUNDUP');

        foreach ($attributes as $attribute) {
            $product_designation[$attribute['id_product_attribute']] = $attribute['attribute_designation'];

            $price = BolPlazaProduct::getPriceStatic($product->id, $attribute['id_product_attribute']);
            $product_baseprice[$attribute['id_product_attribute']] = $price;

            if ($addition > 0) {
                $price += $addition;
            }
            if ($multiplication > 0) {
                $price = $price * $multiplication;
            }
            if ($roundup > 0) {
                $price =  ceil($price / $roundup) * $roundup;
            }

            $product_calculatedprice[$attribute['id_product_attribute']] = $price;
        }

        $bolProducts = BolPlazaProduct::getByProductId($id_product);
        $indexedBolProducts = array();
        foreach ($bolProducts as $bolProduct) {
            $indexedBolProducts[$bolProduct['id_product_attribute']] = $bolProduct;
        }
        $this->context->smarty->assign(array(
            'attributes' => $attributes,
            'product_designation' => $product_designation,
            'calculated_price' => $product_calculatedprice,
            'base_price' => $product_baseprice,
            'product' => $product,
            'bol_products' => $indexedBolProducts,
            'token' => Tools::getAdminTokenLite('AdminBolPlazaProducts'),
            'delivery_codes' => BolPlazaProduct::getDeliveryCodes(),
            'conditions' => BolPlazaProduct::getConditions(),
            'splitted' => Configuration::get('BOL_PLAZA_ORDERS_ENABLE_SPLITTED')
        ));


        if (version_compare(_PS_VERSION_, '1.7', '>=')) {
            return $this->display(__FILE__, 'views/templates/admin/bolproduct-panel.tpl');
        }
        return $this->display(__FILE__, 'views/templates/admin/bolproduct-tab.tpl');
    }

    /**
     * Process BolProduct entities added on the product page
     * Executes hook: actionProductUpdate
     * @param array $params
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
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
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    private function processBolProductEntities($product)
    {
        // Get all id_product_attribute
        $attributes = $product->getAttributesResume($this->context->language->id);
        if (empty($attributes)) {
            $attributes[] = array(
                'id_product_attribute' => 0,
                'attribute_designation' => '',
                'ean13' => $product->ean13
            );
        }

        $bolProducts = BolPlazaProduct::getByProductId($product->id);
        $indexedBolProducts = array();
        foreach ($bolProducts as $bolProduct) {
            $indexedBolProducts[$bolProduct['id_product_attribute']] = $bolProduct;
        }

        // get form information
        foreach ($attributes as $attribute) {
            $key = $product->id.'_'.$attribute['id_product_attribute'];

            // get elements to manage
            $published = Tools::getValue('bolplaza_published_'.$key);
            $price = Tools::getValue('bolplaza_price_'.$key, 0);
            $ean = Tools::getValue('bolplaza_ean_'.$key);
            $delivery_time = Tools::getValue('bolplaza_delivery_time_'.$key);
            if ($delivery_time == 'default') {
                $delivery_time = null;
            }

            $delivery_time_2 = null;
            if (Configuration::get('BOL_PLAZA_ORDERS_ENABLE_SPLITTED')) {
                $delivery_time_2 = Tools::getValue('bolplaza_delivery_time_2_'.$key);
                if ($delivery_time_2 == 'default') {
                    $delivery_time_2 = null;
                }
            }

            $condition = Tools::getValue('bolplaza_condition_'.$key);

            if (array_key_exists($attribute['id_product_attribute'], $indexedBolProducts)) {
                $bolProduct = new BolPlazaProduct(
                    $indexedBolProducts[$attribute['id_product_attribute']]['id_bolplaza_product']
                );

                if ($bolProduct->price == $price &&
                    $bolProduct->published == $published &&
                    $bolProduct->condition == $condition &&
                    $bolProduct->ean === $ean &&
                    $bolProduct->delivery_time == $delivery_time &&
                    $bolProduct->delivery_time_2 == $delivery_time_2 &&
                    (float) $bolProduct->getPrice() === (float) Tools::getValue('bolplaza_baseprice_'.$key)
                ) {
                    continue;
                } elseif ($ean != $bolProduct->ean || $condition != $bolProduct->condition) {
                    // New identifying info, so remove the old product and add as a new one
                    AdminBolPlazaProductsController::processBolProductDelete($bolProduct, $this->context);
                    $bolProduct->status = BolPlazaProduct::STATUS_NEW;
                } else {
                    $bolProduct->status = BolPlazaProduct::STATUS_INFO_UPDATE;
                }
            } elseif (!$published &&
                $price == 0 &&
                $condition == 0 &&
                ($ean == $attribute['ean13'] || empty($ean)) &&
                $delivery_time == null &&
                $delivery_time_2 == null
            ) {
                continue;
            } else {
                $bolProduct = new BolPlazaProduct();
            }

            $bolProduct->id_product = $product->id;
            $bolProduct->id_product_attribute = $attribute['id_product_attribute'];
            $bolProduct->price = $price;
            $bolProduct->published = $published;
            $bolProduct->condition = $condition;
            $bolProduct->ean = $ean;
            $bolProduct->delivery_time = $delivery_time;
            $bolProduct->delivery_time_2 = $delivery_time_2;

            if (($existingProducts = BolPlazaProduct::getByEan13($ean)) !== null
                && $existingProducts[0]['id_bolplaza_product'] !== $bolProduct->id_bolplaza_product) {
                header("HTTP/1.0 400 Bad Request");
                $existingProduct = $existingProducts[0];
                $error = sprintf(
                    $this->l('The EAN %s is already in use (Bol.com ID: %s, Product ID: %s).'),
                    $existingProduct['ean'],
                    $existingProduct['id_bolplaza_product'],
                    $existingProduct['id_product']
                );
                die(json_encode(array('bolplaza_products' => array($error))));
            }

            if (!$published &&
                $price == 0 &&
                $condition == BolPlazaProduct::CONDITION_NEW &&
                $ean == $attribute['ean13'] &&
                $delivery_time == ''
            ) {
                $bolProduct->delete();
            } else {
                $bolProduct->save();
            }
        }
    }

    /**
     * Send an update to Bol.com if the product has Bol.com data.
     * @param $param
     */
    public function hookActionObjectSpecificPriceAddAfter($param)
    {
        $id_product = $param['object']->id_product;
        $bolProducts = BolPlazaProduct::getByProductId($id_product);
        foreach ($bolProducts as $bolProduct) {
            AdminBolPlazaProductsController::setProductStatusByBolPlazaId(
                $bolProduct['id_bolplaza_product'],
                BolPlazaProduct::STATUS_INFO_UPDATE
            );
        }
    }

    /**
     * Send an update to Bol.com if the product has Bol.com data.
     * @param $param
     */
    public function hookActionObjectSpecificPriceUpdateAfter($param)
    {
        $id_product = $param['object']->id_product;
        $bolProducts = BolPlazaProduct::getByProductId($id_product);
        foreach ($bolProducts as $bolProduct) {
            AdminBolPlazaProductsController::setProductStatusByBolPlazaId(
                $bolProduct['id_bolplaza_product'],
                BolPlazaProduct::STATUS_INFO_UPDATE
            );
        }
    }

    /**
     * Send an update to Bol.com if the product has Bol.com data.
     * @param $param
     */
    public function hookActionObjectSpecificPriceDeleteAfter($param)
    {
        $id_product = $param['object']->id_product;
        $bolProducts = BolPlazaProduct::getByProductId($id_product);
        foreach ($bolProducts as $bolProduct) {
            AdminBolPlazaProductsController::setProductStatusByBolPlazaId(
                $bolProduct['id_bolplaza_product'],
                BolPlazaProduct::STATUS_INFO_UPDATE
            );
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
            AdminBolPlazaProductsController::processBolProductUpdate($param['object'], $this->context);
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
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
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
            AdminBolPlazaProductsController::processBolProductUpdate($bolProduct, $this->context);
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
     * On delete product, delete Bol.com Product
     * @param $param
     * @throws PrestaShopException
     */
    public function hookActionProductDelete($param)
    {
        if (!empty($param['id_product'])) {
            $bolProducts = BolPlazaProduct::getHydratedByProductId($param['id_product']);
            foreach ($bolProducts as $bolProduct) {
                $bolProduct->delete();
            }
        }
    }

    /**
     * On delete attribute, delete Bol.com Product
     * @param $param
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function hookActionProductAttributeDelete($param)
    {
        $bolProductId = BolPlazaProduct::getIdByProductAndAttributeId(
            $param['id_product'],
            $param['id_product_attribute']
        );
        if (!empty($bolProductId)) {
            $bolProduct = new BolPlazaProduct($bolProductId);
            $bolProduct->delete();
        }
    }

    /**
     * Synchronize the orders
     */
    public static function synchronize()
    {
        $context = Context::getContext();
        AdminBolPlazaOrdersController::synchronize();
        AdminBolPlazaProductsController::fillEans();
        AdminBolPlazaProductsController::synchronizeFromBol($context);
        AdminBolPlazaProductsController::synchronize($context);
    }
}
