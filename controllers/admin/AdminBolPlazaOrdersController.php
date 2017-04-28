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
require_once _PS_MODULE_DIR_.'bolplaza/classes/BolPlazaPayment.php';
require_once _PS_MODULE_DIR_.'bolplaza/classes/BolPlazaTestPayment.php';
require_once _PS_MODULE_DIR_.'bolplaza/classes/BolPlazaOrderItem.php';

class AdminBolPlazaOrdersController extends AdminController
{
    public function __construct()
    {

        if ($id_order = Tools::getValue('id_order')) {
            Tools::redirectAdmin(
                Context::getContext()->link->getAdminLink('AdminOrders').'&vieworder&id_order='.(int)$id_order
            );
        }

        $this->bootstrap = true;
        $this->table = 'bolplaza_item';
        $this->className = 'BolPlazaOrderItem';
        $this->context = Context::getContext();
        $this->explicitSelect = true;
        $this->allow_export = true;
        $this->deleted = false;

        $this->addRowAction('view');

        $this->identifier = 'id_order';

        $this->_join .= ' INNER JOIN `'._DB_PREFIX_.'orders` ord
                            ON (ord.`id_order` = a.`id_order`)
                          INNER JOIN `'._DB_PREFIX_.'order_payment` op
                            ON (op.`order_reference` = ord.`reference`) ';
        $this->_select = 'op.transaction_id,
                          IF(STRCMP(status,\'shipped\'), 1, 0) as badge_danger,
                          IF (STRCMP(status,\'shipped\'), 0, 1) as badge_success';

        parent::__construct();

        $this->fields_list = array(
            'id_order' => array(
                'title' => $this->l('Order ID'),
                'align' => 'text-center',
                'class' => 'fixed-width-xs',
                'filter_key' => 'a!id_order'
            ),
            'transaction_id' => array(
                'title' => $this->l('Transaction ID'),
                'align' => 'text-center',
                'class' => 'fixed-width-xs',
                'filter_key' => 'op!transaction_id'
            ),
            'quantity' => array(
                'title' => $this->l('Quantity'),
                'align' => 'text-left',
                'class' => 'fixed-width-xs'
            ),
            'title' => array(
                'title' => $this->l('Title'),
                'align' => 'text-left',
            ),
            'ean' => array(
                'title' => $this->l('EAN'),
                'align' => 'text-left',
            ),
            'status' => array(
                'title' => $this->l('Status'),
                'align' => 'text-left',
                'badge_danger' => true,
                'badge_success' => true,
                'havingFilter' => true,
                'class' => 'fixed-width-lg'
            ),
        );

        $this->shopLinkType = 'shop';
    }

    /**
     * {@inheritdoc}
     */
    public function displayViewLink($token = null, $id = 0, $name = null)
    {
        if ($this->tabAccess['view'] == 1) {
            $tpl = $this->createTemplate('helpers/list/list_action_view.tpl');
            if (!array_key_exists('View', self::$cache_lang)) {
                self::$cache_lang['View'] = $this->l('View', 'Helper');
            }

            $tpl->assign(array(
                'href' => $this->context->link->getAdminLink('AdminOrders').'&vieworder&id_order='.(int)$id,
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

        $this->page_header_toolbar_btn['sync_orders'] = array(
            'href' => self::$currentIndex.'&token='.$this->token.'&sync_orders=1',
            'desc' => $this->l('Sync orders'),
            'icon' => 'process-icon-download'
        );

        if (Configuration::get('BOL_PLAZA_ORDERS_TESTMODE')) {
            $this->page_header_toolbar_btn['delete_testdata'] = array(
                'href' => self::$currentIndex.'&token='.$this->token.'&delete_testdata=1',
                'desc' => $this->l('Delete test data'),
                'icon' => 'process-icon-eraser'
            );
        }
    }

    /**
     * Processes the request
     * @return bool
     */
    public function postProcess()
    {
        /* PrestaShop demo mode */
        if (_PS_MODE_DEMO_) {
            $this->errors[] = Tools::displayError('This functionality has been disabled.');
            return false;
        }

        if ((bool)Tools::getValue('sync_orders')) {
            self::synchronize();
            $this->confirmations[] = $this->l('Bol.com order sync completed.');
        } elseif ((bool)Tools::getValue('delete_testdata')) {
            $orders = new PrestaShopCollection('Order');
            $orders->where('module', '=', 'bolplazatest');
            foreach ($orders->getResults() as $order) {
                $customer = $order->getCustomer();
                $addresses = $customer->getAddresses($customer->id_lang);
                foreach ($addresses as $addressArr) {
                    $address = new Address($addressArr['id_address']);
                    $address->delete();
                }
                $details = $order->getOrderDetailList();
                foreach ($details as $detail) {
                    (new OrderDetail($detail['id_order_detail']))->delete();
                }
                (new Cart($order->id_cart))->delete();
                $payments = OrderPayment::getByOrderReference($order->reference);
                foreach ($payments as $payment) {
                    $payment->delete();
                }
                Db::getInstance()->execute('DELETE FROM `'._DB_PREFIX_.'bolplaza_item`
                                              WHERE `id_order` = '.(int)pSQL($order->id));
                Db::getInstance()->execute('DELETE FROM `'._DB_PREFIX_.'order_history`
                                              WHERE `id_order` = '.(int)pSQL($order->id));
                $order->delete();
                $customer->delete();
            }
        }

        return parent::postProcess();
    }

    /**
     * Synchronize the orders from Bol.com to the PrestaShop shop
     */
    public static function synchronize()
    {
        $context = Context::getContext();
        $module = Module::getInstanceByName('bolplaza');
        if (!Configuration::get('BOL_PLAZA_ORDERS_ENABLED') || !$module->isEnabledForShopContext()) {
            $context->controller->errors[] = Tools::displayError('Bol Plaza API isn\'t enabled for the current store.');
            return;
        }
        $Plaza = BolPlaza::getClient();
        $payment_module = new BolPlazaPayment();
        if ((bool)Configuration::get('BOL_PLAZA_ORDERS_TESTMODE')) {
            $payment_module = new BolPlazaTestPayment();
        }

        foreach ($Plaza->getOrders() as $order) {
            if (!self::getTransactionExists($order->OrderId)) {
                $cart = self::parse($order);

                if (!$cart) {
                    $context->controller->errors[] = Translate::getAdminTranslation(
                        'Couldn\'t create a cart for order ',
                        'AdminBolPlazaOrders'
                    ) .$order->OrderId;
                    continue;
                }

                Context::getContext()->cart = $cart;
                Context::getContext()->currency = new Currency((int)$cart->id_currency);
                Context::getContext()->customer = new Customer((int)$cart->id_customer);

                $id_order_state = Configuration::get('BOL_PLAZA_ORDERS_INITIALSTATE');
                $amount_paid = self::getBolPaymentTotal($order);
                $verified = $payment_module->validateOrder(
                    (int)$cart->id,
                    (int)$id_order_state,
                    $amount_paid,
                    $payment_module->displayName,
                    null,
                    array(
                        'transaction_id' => $order->OrderId
                    ),
                    null,
                    false,
                    $cart->secure_key
                );
                if ($verified) {
                    self::persistBolItems($payment_module->currentOrder, $order);
                }
            }
        }
    }

    /**
     * Get OrderID for a Transaction ID
     * @param $transaction_id
     * @return bool
     */
    public static function getTransactionExists($transaction_id)
    {
        $sql = new DbQuery();
        $sql->select('order_reference');
        $sql->from('order_payment', 'op');
        $sql->where('op.transaction_id = '. pSQL($transaction_id));
        return (bool)Db::getInstance()->executeS($sql);
    }

    /**
     * Parse a Bol.com order to a fully prepared Cart object
     * @param Wienkit\BolPlazaClient\Entities\BolPlazaOrder $order
     * @return Cart
     */
    public static function parse(Wienkit\BolPlazaClient\Entities\BolPlazaOrder $order)
    {
        $customer = self::parseCustomer($order);
        Context::getContext()->customer = $customer;
        $shipping = self::parseAddress($order->CustomerDetails->ShipmentDetails, $customer, 'Shipping');
        $billing  = self::parseAddress($order->CustomerDetails->BillingDetails, $customer, 'Billing');
        $cart     = self::parseCart($order, $customer, $billing, $shipping);
        return $cart;
    }

    /**
     * Parse a customer for the order
     * @param Wienkit\BolPlazaClient\Entities\BolPlazaOrder $order
     * @return Customer
     */
    public static function parseCustomer(Wienkit\BolPlazaClient\Entities\BolPlazaOrder $order)
    {
        $customers = Customer::getCustomersByEmail($order->CustomerDetails->BillingDetails->Email);
        if (count($customers) > 0) {
            $customer = $customers[0];
            return new Customer($customer['id_customer']);
        }
        $customer = new Customer();
        $customer->firstname = preg_replace(
            "/[0-9!<>,;?=+()@#\"°{}_$%:]*/",
            '',
            $order->CustomerDetails->BillingDetails->Firstname
        );
        $customer->lastname = preg_replace(
            "/[0-9!<>,;?=+()@#\"°{}_$%:]*/",
            '',
            $order->CustomerDetails->BillingDetails->Surname
        );
        $customer->email = $order->CustomerDetails->BillingDetails->Email;
        $customer->passwd = Tools::passwdGen(8, 'RANDOM');
        $customer->id_default_group = Configuration::get('BOL_PLAZA_ORDERS_CUSTOMER_GROUP');
        $customer->newsletter = false;
        $customer->add();
        return $customer;
    }

    /**
     * Parse an address for the order
     * @param Wienkit\BolPlazaClient\Entities\BolPlazaShipmentDetails $details
     * @param Customer $customer
     * @param string $alias a name for the address
     * @return Address
     */
    public static function parseAddress(
        Wienkit\BolPlazaClient\Entities\BolPlazaShipmentDetails $details,
        Customer $customer,
        $alias
    ) {
        $address = new Address();
        $address->id_customer = $customer->id;
        if ($details->Company != '') {
            $address->company = preg_replace(
                "/[<>={}]*/",
                '',
                $details->Company
            );
        }
        $address->firstname = preg_replace(
            "/[0-9!<>,;?=+()@#\"°{}_$%:]*/",
            '',
            $details->Firstname
        );
        $address->lastname = preg_replace(
            "/[0-9!<>,;?=+()@#\"°{}_$%:]*/",
            '',
            $details->Surname
        );
        $address->address1 = $details->Streetname;

        $houseNumber = $details->Housenumber;
        $address2 = "";
        if ($details->HousenumberExtended != '') {
            $houseNumber .= ' ' . $details->HousenumberExtended;
        }
        if (Configuration::get('BOL_PLAZA_ORDERS_USE_ADDRESS2')) {
            $address2 = $houseNumber;
        } else {
            $address->address1 .= ' ' . $houseNumber;
        }

        if ($details->AddressSupplement) {
            $address2 .= ' ' . $details->AddressSupplement;
        }
        if ($details->ExtraAddressInformation != '') {
            $address2 .= ' (' . $details->ExtraAddressInformation . ')';
        }
        $address->address2 = trim($address2);
        $address->postcode = $details->ZipCode;
        $address->city = $details->City;
        $address->id_country = Country::getByIso($details->CountryCode);
        $address->alias = $alias;
        $address->add();
        return $address;
    }

    /**
     * Parse the cart for the order
     * @param Wienkit\BolPlazaClient\Entities\BolPlazaOrder $order
     * @param Customer $customer
     * @param Address $billing
     * @param Address $shipping
     * @return Cart|bool
     */
    public static function parseCart(
        Wienkit\BolPlazaClient\Entities\BolPlazaOrder $order,
        Customer $customer,
        Address $billing,
        Address $shipping
    ) {
        $context = Context::getContext();
        $cart = new Cart();
        $cart->id_customer = $customer->id;
        $cart->id_address_delivery = $shipping->id;
        $cart->id_address_invoice = $billing->id;
        $cart->id_shop = (int)Context::getContext()->shop->id;
        $cart->id_shop_group = (int)Context::getContext()->shop->id_shop_group;
        $cart->id_lang = $context->language->id;
        $cart->id_currency = (int)Currency::getIdByIsoCode('EUR');
        $cart->id_carrier = (int)Configuration::get('BOL_PLAZA_ORDERS_CARRIER');
        $cart->recyclable = 0;
        $cart->gift = 0;
        $cart->secure_key = md5(uniqid(rand(), true));
        $cart->add();
        $items = $order->OrderItems;
        $hasProducts = false;
        if (!empty($items)) {
            foreach ($items as $item) {
                $productIds = self::getProductIdFromReference($item->OfferReference);
                if (!array_key_exists('id_product', $productIds) || empty($productIds['id_product'])) {
                    $productIds = self::getProductIdByEan($item->EAN);
                }
                if (empty($productIds) || !array_key_exists('id_product', $productIds)) {
                    $context->controller->errors[] = Translate::getAdminTranslation(
                        'Couldn\'t find product for EAN: ',
                        'AdminBolPlazaOrders'
                    ) . $item->EAN;
                    continue;
                }
                $product = new Product($productIds['id_product']);
                if (!Validate::isLoadedObject($product)) {
                    $context->controller->errors[] = Translate::getAdminTranslation(
                        'Couldn\'t load product for EAN: ',
                        'AdminBolPlazaOrders'
                    ) . $item->EAN;
                    continue;
                }
                $hasProducts = true;
                self::addSpecificPrice(
                    $cart,
                    $shipping,
                    $customer,
                    $product,
                    $productIds['id_product_attribute'],
                    round(self::getTaxExclusive($product, $item->OfferPrice / $item->Quantity), 6)
                );
                $cartResult = $cart->updateQty($item->Quantity, $product->id, $productIds['id_product_attribute']);
                if (!$cartResult) {
                    $context->controller->errors[] = Tools::displayError(
                        'Couldn\'t add product to cart. The product cannot
                         be sold because it\'s unavailable or out of stock'
                    );
                    return false;
                }
            }
        }

        if (Configuration::get('BOL_PLAZA_ORDERS_FREE_SHIPPING')) {
            self::addFreeShippingCartRule($cart);
        }

        $cart->update();
        if (!$hasProducts) {
            return false;
        }
        return $cart;
    }

    /**
     * Persist the BolItems to the database
     * @param string $orderId
     * @param Wienkit\BolPlazaClient\Entities\BolPlazaOrder $order
     */
    public static function persistBolItems($orderId, Wienkit\BolPlazaClient\Entities\BolPlazaOrder $order)
    {
        $items = $order->OrderItems;
        if (!empty($items)) {
            foreach ($items as $orderItem) {
                $item = new BolPlazaOrderItem();
                $item->id_shop = (int)Context::getContext()->shop->id;
                $item->id_shop_group = (int)Context::getContext()->shop->id_shop_group;
                $item->id_order = $orderId;
                $item->id_bol_order_item = $orderItem->OrderItemId;
                $item->ean = $orderItem->EAN;
                $item->title = $orderItem->Title;
                $item->quantity = $orderItem->Quantity;
                $item->add();
            }
        }
    }

    /**
     * Adds a specific price for a product
     * @param Cart $cart
     * @param Address $shipping
     * @param Customer $customer
     * @param Product $product
     * @param string $id_product_attribute
     * @param float $price
     */
    private static function addSpecificPrice(
        Cart $cart,
        Address $shipping,
        Customer $customer,
        Product $product,
        $id_product_attribute,
        $price
    ) {
        $specific_price = new SpecificPrice();
        $specific_price->id_cart = (int)$cart->id;
        $specific_price->id_shop = $cart->id_shop;
        $specific_price->id_shop_group = $cart->id_shop_group;
        $specific_price->id_currency = $cart->id_currency;
        $specific_price->id_country = $shipping->id_country;
        $specific_price->id_group = (int)$customer->id_default_group;
        $specific_price->id_customer = (int)$customer->id;
        $specific_price->id_product = $product->id;
        $specific_price->id_product_attribute = $id_product_attribute;
        $specific_price->price = $price;
        $specific_price->from_quantity = 1;
        $specific_price->reduction = 0;
        $specific_price->reduction_type = 'amount';
        $specific_price->from = '0000-00-00 00:00:00';
        $specific_price->to = '0000-00-00 00:00:00';
        $specific_price->add();
    }

    /**
     * Adds a cart rule for free shipping
     * @param Cart $cart
     */
    private static function addFreeShippingCartRule(Cart $cart)
    {
        $cart_rule = new CartRule();
        $cart_rule->code = BolPlazaPayment::CARTRULE_CODE_PREFIX.(int)$cart->id;
        $cart_rule->name = array(
            Configuration::get('PS_LANG_DEFAULT') => Translate::getAdminTranslation(
                'Free Shipping',
                'AdminTab',
                false,
                false
            )
        );
        $cart_rule->id_customer = (int)$cart->id_customer;
        $cart_rule->free_shipping = true;
        $cart_rule->quantity = 1;
        $cart_rule->quantity_per_user = 1;
        $cart_rule->minimum_amount_currency = (int)$cart->id_currency;
        $cart_rule->reduction_currency = (int)$cart->id_currency;
        $cart_rule->date_from = date('Y-m-d H:i:s', time());
        $cart_rule->date_to = date('Y-m-d H:i:s', time() + 60);
        $cart_rule->active = 1;
        $cart_rule->add();
        $cart->addCartRule((int)$cart_rule->id);
    }

    /**
     * Return the tax exclusive price
     * @param Product $product
     * @param float $price
     * @return float the price wihout tax
     */
    public static function getTaxExclusive(Product $product, $price)
    {
        $address = Address::initialize();
        $tax_manager = TaxManagerFactory::getManager($address, $product->id_tax_rules_group);
        $tax_calculator = $tax_manager->getTaxCalculator();
        return $tax_calculator->removeTaxes($price);
    }

    /**
     * Split the saved reference and check if a product exists
     *
     * @param $reference
     * @return array|bool
     */
    public static function getProductIdFromReference($reference)
    {
        $bolProduct = new BolPlazaProduct($reference);
        if ($bolProduct != null) {
            $result = array();
            $result['id_product'] = $bolProduct->id_product;
            if ($bolProduct->id_product_attribute) {
                $result['id_product_attribute'] = $bolProduct->id_product_attribute;
            }
            return $result;
        }
        return false;
    }

    /**
     * Return the product ID for an EAN number
     * @param string $ean
     * @return array the product (and attribute)
     */
    public static function getProductIdByEan($ean)
    {
        $data = BolPlazaProduct::getByEan13($ean);
        if (count($data) > 0) {
            return array(
                'id_product' => $data[0]['id_product'],
                'id_product_attribute' => $data[0]['id_product_attribute']
            );
        }
        $attribute = self::getAttributeByEan($ean);
        if (count($attribute) == 1) {
            return $attribute[0];
        }

        $product = Product::getIdByEan13($ean);
        if ($product) {
            return array('id_product' => $product, 'id_product_attribute' => 0);
        }

        if ($ean !== (int) $ean) {
            return self::getProductIdByEan((int) $ean);
        }
        return null;
    }

    /**
     * Return the attribute for an ean
     * @param string $ean
     * @return array|false|int|mysqli_result|null|PDOStatement|resource
     */
    private static function getAttributeByEan($ean)
    {
        if (empty($ean)) {
            return 0;
        }

        if (!Validate::isEan13($ean)) {
            return 0;
        }

        $query = new DbQuery();
        $query->select('pa.id_product, pa.id_product_attribute');
        $query->from('product_attribute', 'pa');
        $query->where('pa.ean13 = \''.pSQL($ean).'\'');
        return Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($query);
    }

    /**
     * Get the Payment total of the Bol.com order
     * @param Wienkit\BolPlazaClient\Entities\BolPlazaOrder $order
     * @return float the total
     */
    private static function getBolPaymentTotal(Wienkit\BolPlazaClient\Entities\BolPlazaOrder $order)
    {
        $items = $order->OrderItems;
        $total = 0;
        if (!empty($items)) {
            foreach ($items as $orderItem) {
                $total += $orderItem->OfferPrice;
            }
        }
        return $total;
    }
}
