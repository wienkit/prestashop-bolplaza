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

require_once _PS_MODULE_DIR_.'bolplaza/vendor/autoload.php';
require_once _PS_MODULE_DIR_.'bolplaza/classes/BolPlazaOrderItem.php';
require_once _PS_MODULE_DIR_.'bolplaza/classes/BolPlazaProduct.php';

class BolPlaza extends Module
{
    public function __construct()
    {
        $this->name = 'bolplaza';
        $this->tab = 'market_place';
        $this->version = '1.0.0';
        $this->author = 'Wienk IT';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = array('min' => '1.6', 'max' => _PS_VERSION_);
        $this->bootstrap = true;
        $this->module_key = 'f774cfaf352c30b37c7bfce545861c1e';

        $this->display = 'view';

        parent::__construct();

        $this->displayName = $this->l('Bol.com Plaza API connector');
        $this->description = $this->l('Connect to Bol.com Plaza to synchronize your Bol.com orders and products with your Prestashop website.');

        $this->confirmUninstall = $this->l('Are you sure you want to uninstall?');

    }

    public function install()
    {
        if (parent::install()) {
            return $this->installDb()
                && $this->installOrderState()
                && $this->installTab()
                && $this->registerHook('actionProductUpdate')
                && $this->registerHook('actionUpdateQuantity')
                && $this->registerHook('displayAdminProductsExtra')
                && $this->registerHook('ActionObjectOrderCarrierUpdateAfter');
        }
        return false;
    }

    public function uninstall()
    {
        return $this->uninstallTab()
          && $this->uninstallOrderState()
          && $this->uninstallDb()
          && $this->unregisterHook('actionProductUpdate')
          && $this->unregisterHook('actionUpdateQuantity')
          && $this->unregisterHook('displayAdminProductsExtra')
          && $this->unregisterHook('ActionObjectOrderCarrierUpdateAfter')
          && parent::uninstall();
    }

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

    public function uninstallDb()
    {
        $sql = array();
        include(dirname(__FILE__).'/sql_install.php');
        foreach ($sql as $name => $v) {
            Db::getInstance()->execute('DROP TABLE IF EXISTS '.pSQL($name));
        }
        return true;
    }

    public function installOrderState()
    {
        $orderStateName = 'Bol.com order imported';
        foreach (Language::getLanguages(true) as $lang) {
            $order_states = OrderState::getOrderStates($lang['id_lang']);
            foreach ($order_states as $state) {
                if($state['name'] == $orderStateName) {
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

    public function uninstallOrderState()
    {
        Configuration::deleteByName('BOL_PLAZA_ORDERS_INITIALSTATE');
        return true;
    }

    public function installTab()
    {
        $tab = new Tab();
        $tab->active = 1;
        $tab->name = array();
        $tab->class_name = 'AdminBolPlazaOrders';

        foreach (Language::getLanguages(true) as $lang) {
            $tab->name[$lang['id_lang']] = 'Bol.com orders';
        }

        $tab->id_parent = 10;
        $tab->module = $this->name;

        return $tab->add();
    }

    public function uninstallTab()
    {
        $id_tab = (int)Tab::getIdFromClassName('AdminBolPlazaOrders');
        if ($id_tab) {
            $tab = new Tab($id_tab);
            return $tab->delete();
        }
        return true;
    }

    public function getContent()
    {
        $output = null;

        if (Tools::isSubmit('submit'.$this->name)) {
            $enabled = (bool) Tools::getValue('bolplaza_orders_enabled');
            $testmode = (bool) Tools::getValue('bolplaza_orders_testmode');
            $privkey = (string) Tools::getValue('bolplaza_orders_privkey');
            $pubkey = (string) Tools::getValue('bolplaza_orders_pubkey');
            $carrier = (int) Tools::getValue('bolplaza_orders_carrier');
            $carrierCode = (string) Tools::getValue('bolplaza_orders_carrier_code');
            $freeShipping = (bool) Tools::getValue('bolplaza_orders_free_shipping');

            if (!$privkey
                || ! $pubkey
                || empty($privkey)
                || empty($pubkey)
                || empty($carrier)
                || empty($carrierCode)) {
                $output .= $this->displayError($this->l('Invalid Configuration value'));
            } else {
                Configuration::updateValue('BOL_PLAZA_ORDERS_ENABLED', $enabled);
                Configuration::updateValue('BOL_PLAZA_ORDERS_TESTMODE', $testmode);
                Configuration::updateValue('BOL_PLAZA_ORDERS_PRIVKEY', $privkey);
                Configuration::updateValue('BOL_PLAZA_ORDERS_PUBKEY', $pubkey);
                Configuration::updateValue('BOL_PLAZA_ORDERS_CARRIER', $carrier);
                Configuration::updateValue('BOL_PLAZA_ORDERS_CARRIER_CODE', $carrierCode);
                Configuration::updateValue('BOL_PLAZA_ORDERS_FREE_SHIPPING', $freeShipping);
                $output .= $this->displayConfirmation($this->l('Settings updated'));
            }
        }
        return $output.$this->displayForm();
    }

    public function displayForm()
    {
        // Get default language
        $default_lang = (int)Configuration::get('PS_LANG_DEFAULT');

        $carriers = Carrier::getCarriers(Context::getContext()->language->id);

        // Init Fields form array
        $fields_form = array();
        $fields_form[0]['form'] = array(
            'legend' => array(
                'title' => $this->l('Settings'),
                ),
            'input' => array(
                array(
                    'type' => 'switch',
                    'label' => $this->l('Enable Bol.com orders connector'),
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
                    'hint' => $this->l("Don't calculate shipping costs.")
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
        $helper->fields_value['bolplaza_orders_free_shipping'] = Configuration::get('BOL_PLAZA_ORDERS_FREE_SHIPPING');

        return $helper->generateForm($fields_form);
    }

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

    public function hookActionObjectOrderCarrierUpdateAfter($params)
    {
        $orderCarrier = $params['object'];
        if ($orderCarrier->tracking_number) {

            $order = new Order($orderCarrier->id_order);
            if ($order->module == 'bolplaza' || $order->module == 'bolplazatest') {
                $shipments = array();
                $Plaza = self::getClient();
                $itemsShipped = array();
                $orderPayments = OrderPayment::getByOrderReference($order->reference);
                foreach ($orderPayments as $orderPayment) {
                    $items = BolPlazaOrderItem::getByOrderId($order->id);
                    foreach ($items as $item) {
                        $shipment = new Picqer\BolPlazaClient\Entities\BolPlazaShipmentRequest();
                        $shipment->OrderItemId = $item->id_bol_order_item;
                        $shipment->ShipmentReference = 'bolplazatest123'; // TODO REFERENCE?
                        $shipment->DateTime = date('Y-m-d\TH:i:s');
                        $shipment->ExpectedDeliveryDate = date('Y-m-d\TH:i:s');  // TODO IMPLEMENT?
                        $transport = new Picqer\BolPlazaClient\Entities\BolPlazaTransport();
                        $transport->TransporterCode = Configuration::get('BOL_PLAZA_ORDERS_CARRIER_CODE');
                        $transport->TrackAndTrace = $orderCarrier->tracking_number;
                        $shipment->Transport = $transport;
                        $itemsShipped[] = $item;
                        $Plaza->processShipment($shipment);
                    }
                }
                foreach ($itemsShipped as $item) {
                    $item->setShipped();
                }
            }
        }
    }

    public function hookDisplayAdminProductsExtra($params)
    {
        if ($id_product = (int)Tools::getValue('id_product'))
        $product = new Product($id_product, true, $this->context->language->id, $this->context->shop->id);
        if (!Validate:: isLoadedObject($product))
          return;

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
            'bol_products' => $indexedBolProducts
        ));

        return $this->display(__FILE__, 'views/templates/admin/bolproduct.tpl');
    }

    public function hookActionProductUpdate($params)
    {
        if ((int)Tools::getValue('bolplaza_loaded') === 1 && Validate::isLoadedObject($product = new Product((int)$params['id_product']))) {
            $this->processBolProductEntities($product);
        }
    }

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
            $price = Tools::getValue('bolplaza_price_'.$key, '');

            if($indexedBolProducts[$attribute['id_product_attribute']]) {
                $bolProduct = new BolPlazaProduct($indexedBolProducts[$attribute['id_product_attribute']]['id_bolplaza_product']);
            } else {
                $bolProduct = new BolPlazaProduct();
            }

            if(!$published && $price == 0) {
                $bolProduct->delete();
            } else {
                $bolProduct->id_product = $product->id;
                $bolProduct->id_product_attribute = $attribute['id_product_attribute'];
                $bolProduct->price = $price;
                $bolProduct->published = $published;
                $bolProduct->info_update = true;
                $bolProduct->save();
                $this->processBolProductUpdate($bolProduct);
            }
        }
    }

    private function processBolProductCreate($bolProduct)
    {
        $Plaza = self::getClient();
        $offerCreate = new Picqer\BolPlazaClient\Entities\BolPlazaOfferCreate();
        $offerCreate->EAN = '12354'; // TODO use product ean
        $offerCreate->Condition = 'NEW'; // TODO get from product
        $offerCreate->Price = $bolProduct->price;
        $offerCreate->DeliveryCode = '24uurs-16'; // TODO as default config and bolProduct field
        $offerCreate->QuantityInStock = 12; // TODO get from stock
        $offerCreate->Publish = $bolProduct->published;
        $offerCreate->ReferenceCode = $bolProduct->id; // TODO use product sku
        $offerCreate->Description = 'Test product';  // TODO get from product description
        try {
            $Plaza->createOffer($bolProduct->id, $offerCreate);
            $bolProduct->info_update = false;
            $bolProduct->save();
        } catch (Exception $e) {
            $this->context->controller->errors[] = "[bolplaza] " . $this->l("Couldn't send update to Bol.com, error: ") . "<br />" . $e->getMessage();
        }
    }

    private function processBolProductUpdate($bolProduct)
    {
        $Plaza = self::getClient();
        $offerUpdate = new Picqer\BolPlazaClient\Entities\BolPlazaOfferUpdate();
        $offerUpdate->Price = $bolProduct->price;
        $offerUpdate->DeliveryCode = '24uurs-16'; // TODO as default config and bolProduct field
        $offerUpdate->Publish = $bolProduct->published;
        $offerUpdate->ReferenceCode = $bolProduct->id; // TODO use product sku
        $offerUpdate->Description = 'Test product';  // TODO get from product description
        try {
            $Plaza->updateOffer($bolProduct->id, $offerUpdate);
            $bolProduct->info_update = false;
            $bolProduct->save();
        } catch (Exception $e) {
            $this->context->controller->errors[] = "[bolplaza] " . $this->l("Couldn't send update to Bol.com, error: ") . "<br />" . $e->getMessage();
        }
    }

    public function hookActionUpdateQuantity($param)
    {
        $bolProductId = BolPlazaProduct::getIdByProductAndAttributeId($param['id_product'], $param['id_product_attribute']);
        if(!empty($bolProductId)) {
            $bolProduct = new BolPlazaProduct($bolProductId);
            $bolProduct->stock_update = true;
            $bolProduct->save();
            $this->processBolStockUpdate($bolProduct, $param['quantity']);
        }
    }

    private function processBolStockUpdate($bolProduct, $quantity)
    {
        $Plaza = self::getClient();
        $stockUpdate = new Picqer\BolPlazaClient\Entities\BolPlazaStockUpdate();
        $stockUpdate->QuantityInStock = $quantity;
        try {
            $result = $Plaza->updateOfferStock($bolProduct->id, $stockUpdate);
            $bolProduct->stock_update = false;
            $bolProduct->save();
        } catch (Exception $e) {
            $this->context->controller->errors[] = "[bolplaza] " . $this->l("Couldn't send update to Bol.com, error: ") . "<br />" . $e->getMessage();
        }
    }
}
