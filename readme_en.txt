Bol.com Plaza API connector
===========================

1. Introduction
===============
The Bol.com Plaza API connector allows you to import your orders from the Bol.com Plaza account. That means that you can handle all logic you would normally follow, from your own backoffice.

2. Installation
===============
  1. Install the module by uploading the zip file.
  2. Enable it in a Shop context and fill out the settings on the Configure page of the Module (in the module list).
  		- It is recommended to use the Test API first, if everything works correctly, you can change it to the production values.
  		- If you want to use the test api, be sure to have a product with EAN 9789062387410.

3. Usage
========
Go to your Orders -> Bol.com orders page. You can now click on 'Sync orders' to retrieve the orders from Bol.com.
If you now handle your order (via the Order details page), you can see the data that was used.
The Bol.com Order ID is imported as the transaction ID of the payment.

When you add a track & trace code to your order, the API connector notify Bol.com of that shipment.

If you are in testing mode, a button to clean the test data will appear on the Bol.com orders page.


4. Frequently asked questions
=============================
1. Can I cancel an order via the connector?
- No this is currently not possible, you need to login to your Bol.com account.

2. How can I synchronize my products to Bol.com?
- You need to upload an MS Excel file to Bol.com via the seller interface, or use another addon for this functionality.

3. Can the order be imported automatically?
- Yes, however, you need to setup a cron task for this.

4. Can I also import orders with products that aren't in stock?
- Yes, but the products have to be orderable (on your own website). You can also override this functionality by adding the following code to an override class for Product:

public static function isAvailableWhenOutOfStock($out_of_stock)
{
    // @TODO 1.5.0 Update of STOCK_MANAGEMENT & ORDER_OUT_OF_STOCK
    static $ps_stock_management = null;
    if ($ps_stock_management === null) {
        $ps_stock_management = Configuration::get('PS_STOCK_MANAGEMENT');
    }

    if (!$ps_stock_management || isset(Context::getContext()->employee)) {
        return true;
    } else {
        static $ps_order_out_of_stock = null;
        if ($ps_order_out_of_stock === null) {
            $ps_order_out_of_stock = Configuration::get('PS_ORDER_OUT_OF_STOCK');
        }

        return (int)$out_of_stock == 2 ? (int)$ps_order_out_of_stock : (int)$out_of_stock;
    }
}

The code checks if an employee is logged creating the order, and if so, it skips the stock checks.

5. I get an error when I import multiple orders
- The error is caused by a bug in Prestashop core, it's not a problem (just a warning), but a bug report has been created http://forge.prestashop.com/browse/PSCSX-7858.
You can manually fix this by adding the following code in an override class for Cart:

/**
 * Get the delivery option selected, or if no delivery option was selected,
 * the cheapest option for each address
 *
 * @param Country|null $default_country
 * @param bool         $dontAutoSelectOptions
 * @param bool         $use_cache
 *
 * @return array|bool|mixed Delivery option
 */
public function getDeliveryOption($default_country = null, $dontAutoSelectOptions = false, $use_cache = true)
{
    static $cache = array();
    $cache_id = (int)(is_object($default_country) ? $default_country->id : 0).'-'.(int)$dontAutoSelectOptions.'-'.$this->id;
    if (isset($cache[$cache_id]) && $use_cache) {
        return $cache[$cache_id];
    }

    $delivery_option_list = $this->getDeliveryOptionList($default_country);

    // The delivery option was selected
    if (isset($this->delivery_option) && $this->delivery_option != '') {
        $delivery_option = Tools::unSerialize($this->delivery_option);
        $validated = true;
        foreach ($delivery_option as $id_address => $key) {
            if (!isset($delivery_option_list[$id_address][$key])) {
                $validated = false;
                break;
            }
        }

        if ($validated) {
            $cache[$cache_id] = $delivery_option;
            return $delivery_option;
        }
    }

    if ($dontAutoSelectOptions) {
        return false;
    }

    // No delivery option selected or delivery option selected is not valid, get the better for all options
    $delivery_option = array();
    foreach ($delivery_option_list as $id_address => $options) {
        foreach ($options as $key => $option) {
            if (Configuration::get('PS_CARRIER_DEFAULT') == -1 && $option['is_best_price']) {
                $delivery_option[$id_address] = $key;
                break;
            } elseif (Configuration::get('PS_CARRIER_DEFAULT') == -2 && $option['is_best_grade']) {
                $delivery_option[$id_address] = $key;
                break;
            } elseif ($option['unique_carrier'] && in_array(Configuration::get('PS_CARRIER_DEFAULT'), array_keys($option['carrier_list']))) {
                $delivery_option[$id_address] = $key;
                break;
            }
        }

        reset($options);
        if (!isset($delivery_option[$id_address])) {
            $delivery_option[$id_address] = key($options);
        }
    }

    $cache[$cache_id] = $delivery_option;

    return $delivery_option;
}