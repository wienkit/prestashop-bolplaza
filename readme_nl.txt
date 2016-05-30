Bol.com Plaza API connector
===========================

1. Introductie
==============
De Bol.com Plaza API connector laat je jouw Bol.com orders afhandelen in jouw backoffice. Dit betekent dat je je normale proces kunt volgen, alsof het een order via je gewone shop betreft.

2. Installatie
==============
  1. Installeer de module door het zip bestand te uploaden
  2. Activeer de module voor de shop(s) waarin je de Bol.com orders wilt hebben. Vul de gegevens in op de module configuratiepagina.
  		- Het wordt aangeraden om eerst de Test API te gebruiken, als alles werkt, kun je de productiegegevens invullen.
  		- Als je de test api gebruikt, zorg er dan voor dat er een product is met EAN code 9789062387410.

3. Gebruik
==========

3.1 Orders
----------
Ga naar Orders -> Bol.com orders. Je kunt daar klikken op 'Sync orders', waarna de orders worden geïmporteerd.
Wanneer je nu je order afhandeld (via de normale Orders pagina), kun je zien welke data er gebruikt is.
Het Bol.com Order ID wordt geïmporteerd als het transactie ID van de betaling.

Wanneer je een track & trace nummer aan je order toevoegt, wordt deze naar Bol.com gemeld en wordt je order aangemerkt als verstuurd.

Als je in de test modus zit, kun je de 'Delete test data' knop gebruiken om de testorders te verwijderen.

3.2 Producten
-------------
Vanaf versie 1.1.0 is het ook mogelijk om je producten te synchroniseren met je Bol.com verkoopaccount.
Je kunt de data zoals deze op Bol.com wordt getoond beheren op de productpagina (via de Bol.com tab).
Daar kun je per product of combinatie aangeven hoe deze op Bol.com getoond moet worden.
Je kunt instellen of het artikel gepubliceerd moet worden, en je kunt optioneel een specifieke prijs voor Bol.com aangeven.

Wanneer je je product aanpast, wordt er een bericht naar Bol.com gestuurd met de nieuwe informatie.
Als je je EAN code of je product conditie (nieuw, gebruikt etc) aanpast, moet het product eerst verwijderd worden.
Dit kun je doen door voor het product publiceren uit te zetten, en de prijs op 0 te zetten.

Er is ook een nieuw menuitem toegevoegd aan het Catalogus menu. Daarin zie je een overzicht van alle producten die naar Bol.com zijn gemeld.
Wanneer er een foutieve melding is gedaan, staat dat in de statusbalk aangegeven.
Je kunt dan handmatig de melding opnieuw doen door op de knop 'Synchronize' te drukken.

Voordat je de Bol.com producten kunt gebruiken, dien je in de instellingen van de module je levertijd in te stellen.

4. Veelgestelde vragen
=============================
1. Kan ik een order annuleren via de connector?
- Helaas is dit momenteel nog niet mogelijk, je moet dit op je Bol.com account zelf doen.

2. Hoe kan ik mijn producten synchroniseren naar Bol.com?
- Dit kun je doen vanaf de productpagina in de backoffice

3. Kunnen de orders ook automatisch worden geïmporteerd?
- Ja, maar daar moet je een crontaak voor opzetten.

4. Kan ik orders importeren voor producten die niet op voorraad zijn?
- Ja, maar dan moeten de producten wel bestelbaar zijn (dus ook op je website). Je kunt deze functionaliteit veranderen door een override class aan te maken voor Product, en daarin de volgende code te zetten:

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

De code voegt een check toe om te kijken of een medewerker de order heeft geïmporteerd.

5. Ik krijg een waarschuwing wanneer ik meerdere orders tegelijk importeer
- De waarschuwing komt door een bug in Prestashop core, het probleem kan geen kwaad, maar er is een bug report gemaakt: http://forge.prestashop.com/browse/PSCSX-7858.
Je kunt de waarschuwing verwijderen door handmatig een override class te maken voor de Cart class:

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
