{*
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
*
*}
<style>
    .clickable {
        cursor: pointer;
    }
    .collapsed.clickable i {
        transform: rotate(180deg);
    }
</style>
<input type="hidden" name="bolplaza_loaded" value="1">
{if isset($product->id)}
    <div class="panel product-tab" id="product-ModuleBolplaza">
        <input type="hidden" name="submitted_tabs[]" value="Bolplaza" />
        <h3 class="tab">{l s='Bol.com settings' mod='bolplaza'}</h3>
        <div class="row">
            <div class="alert alert-info" style="display:block; position:'auto';">
                <p>{l s='This interface allows you to edit the Bol.com data.' mod='bolplaza'}</p>
                <p>{l s='You can also specify product/product combinations.' mod='bolplaza'}</p>
            </div>
        </div>
        <div class="row">
            <div class="col-lg-12">
                <table class="table">
                    <thead>
                    <tr>
                        <th class="width: 10%; min-width: 50px;" align="center"><span class="title_box">{l s='Published' mod='bolplaza'}</span></th>
                        <th style="width: 30%"><span class="title_box">{l s='Product' mod='bolplaza'}</span></th>
                        <th style="width: 10%"><span class="title_box">{l s='Base price' mod='bolplaza'}</span></th>
                        <th style="width: 20%"><span class="title_box">{l s='Price change' mod='bolplaza'}</span></th>
                        <th style="width: 10%"><span class="title_box">{l s='Proposed price' mod='bolplaza'}</span></th>
                        <th style="width: 20%"><span class="title_box">{l s='Final price' mod='bolplaza'}</span></th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr class="alt_row">
                        <td class="fixed-width-xs" align="center"><input type="checkbox" id="toggle_bolplaza_check"  /> </td>
                        <td colspan="2">-- {l s='All products' mod='bolplaza'} -- </td>
                        <td>
                            <div class="input-group">
                                <span class="input-group-addon">+ &euro;</span>
                                <input id="toggle_bolplaza_price" type="text" onchange="noComma('toggle_bolplaza_price');" maxlength="27" />
                            </div>
                        </td>
                        <td>
                            <a class="use_calculated_prices" title="{l s='Select each proposed price' mod='bolplaza'}">-- {l s='Select' mod='bolplaza'} --</a></td>
                        <td>
                            <div class="input-group">
                                <span class="input-group-addon"> &euro;</span>
                                <input id="toggle_bolplaza_final_price" type="text" onchange="noComma('toggle_bolplaza_final_price');" maxlength="27" />
                            </div>
                        </td>
                    </tr>
                    {foreach $attributes AS $index => $attribute}
                        {assign var=price value=''}
                        {assign var=selected value=''}
                        {if array_key_exists($attribute['id_product_attribute'], $bol_products)}
                            {assign var=price value=$bol_products[$attribute['id_product_attribute']]['price']}
                            {assign var=selected value=$bol_products[$attribute['id_product_attribute']]['published']}
                            {assign var=delivery_time value=$bol_products[$attribute['id_product_attribute']]['delivery_time']}
                            {assign var=ean value=$bol_products[$attribute['id_product_attribute']]['ean']}
                        {/if}
                        <tr class="bol-plaza-item">
                            <td class="fixed-width-xs" align="center"><input type="checkbox"
                                                                             name="bolplaza_published_{$attribute['id_product']|escape:'htmlall':'UTF-8'}_{$attribute['id_product_attribute']|escape:'htmlall':'UTF-8'}"
                                                                             {if $selected == true}checked="checked"{/if}
                                                                             value="1" />
                            </td>
                            <td class="clickable collapsed" data-toggle="collapse" data-target=".{$index|escape:'htmlall':'UTF-8'}collapsed">
                                {$product_designation[$attribute['id_product_attribute']]|escape:'htmlall':'UTF-8'}
                                <i class="icon-caret-up pull-right"></i>
                            </td>
                            <td>
                                € {$base_price[$attribute['id_product_attribute']]|escape:'htmlall':'UTF-8'|string_format:"%.2f"}
                            </td>
                            <td>
                                <div class="input-group">
                                    <span class="input-group-addon">+ &euro;</span>
                                    <input name="bolplaza_price_{$attribute['id_product']|escape:'htmlall':'UTF-8'}_{$attribute['id_product_attribute']|escape:'htmlall':'UTF-8'}"
                                           id="bolplaza_price_{$attribute['id_product']|escape:'htmlall':'UTF-8'}_{$attribute['id_product_attribute']|escape:'htmlall':'UTF-8'}"
                                           type="text"
                                           class="bolplaza-price"
                                           value="{if $price}{$price|escape:'html':'UTF-8'|string_format:"%.2f"}{/if}"
                                           maxlength="14">
                                </div>
                            </td>
                            <td>
                                <a class="use_calculated_price"
                                   data-val="{$calculated_price[$attribute['id_product_attribute']]|escape:'htmlall':'UTF-8'|string_format:"%.2f"}"
                                   title="{l s='Select this proposed price for each row' mod='bolplaza'}"
                                >&euro; {$calculated_price[$attribute['id_product_attribute']]|escape:'htmlall':'UTF-8'|string_format:"%.2f"}</a>
                            </td>
                            <td>
                                <div class="input-group">
                                    <span class="input-group-addon"> &euro;</span>
                                    <input name="bolplaza_final_price_{$attribute['id_product']|escape:'htmlall':'UTF-8'}_{$attribute['id_product_attribute']|escape:'htmlall':'UTF-8'}"
                                           id="bolplaza_final_price_{$attribute['id_product']|escape:'htmlall':'UTF-8'}_{$attribute['id_product_attribute']|escape:'htmlall':'UTF-8'}"
                                           type="text"
                                           data-base-price="{$base_price[$attribute['id_product_attribute']]|escape:'html':'UTF-8'|string_format:"%.2f"}"
                                           class="bolplaza-price-final"
                                           value="{($price + $base_price[$attribute['id_product_attribute']])|escape:'html':'UTF-8'|string_format:"%.2f"}"
                                           maxlength="14">
                                </div>
                            </td>
                        </tr>
                        <tr class="collapse out {$index|escape:'htmlall':'UTF-8'}collapsed">
                            <td>&nbsp;</td>
                            <td colspan="2">
                                {l s='Custom EAN (optional)' mod='bolplaza'}
                            </td>
                            <td>
                                <input name="bolplaza_ean_{$attribute['id_product']|escape:'htmlall':'UTF-8'}_{$attribute['id_product_attribute']|escape:'htmlall':'UTF-8'}" id="bolplaza_ean_{$attribute['id_product']|escape:'htmlall':'UTF-8'}_{$attribute['id_product_attribute']|escape:'htmlall':'UTF-8'}" type="text" value="{if isset($ean)}{$ean|escape:'html':'UTF-8'}{/if}" maxlength="27">
                            </td>
                        </tr>
                        <tr class="collapse out {$index|escape:'htmlall':'UTF-8'}collapsed">
                            <td>&nbsp;</td>
                            <td colspan="2">
                                {l s='Custom Delivery time (optional)' mod='bolplaza'}
                            </td>
                            <td>
                                <select name="bolplaza_delivery_time_{$attribute['id_product']|escape:'htmlall':'UTF-8'}_{$attribute['id_product_attribute']|escape:'htmlall':'UTF-8'}" id="bolplaza_delivery_time_{$attribute['id_product']|escape:'htmlall':'UTF-8'}_{$attribute['id_product_attribute']|escape:'htmlall':'UTF-8'}">
                                    <option value=""{if !isset($delivery_time) || $delivery_time == '' } selected="selected"{/if}>-- {l s='Use default' mod='bolplaza'} --</option>
                                    {foreach $delivery_codes AS $code}
                                        <option value="{$code['deliverycode']|escape:'htmlall':'UTF-8'}"{if isset($delivery_time) && $delivery_time == $code['deliverycode']} selected="selected"{/if}>{$code['description']|escape:'htmlall':'UTF-8'}</option>
                                    {/foreach}
                                </select>
                            </td>
                        </tr>
                    {/foreach}
                    </tbody>
                </table>
            </div>
        </div>
        <div class="panel-footer">
            <a href="{$link->getAdminLink('AdminProducts')|escape:'htmlall':'UTF-8'}" class="btn btn-default"><i class="process-icon-cancel"></i> {l s='Cancel' mod='bolplaza'}</a>
            <button type="submit" name="submitAddproduct" class="btn btn-default pull-right"><i class="process-icon-save"></i> {l s='Save' mod='bolplaza'}</button>
            <button type="submit" name="submitAddproductAndStay" class="btn btn-default pull-right"><i class="process-icon-save"></i> {l s='Save and stay' mod='bolplaza'}</button>
        </div>
    </div>
    <script>
        var BolPlaza = {
            updateFinalPrice: function($row) {
                $row.find("input[name^=bolplaza_final_price_]").val(
                    BolPlaza.calculateFinalPrice(
                        BolPlaza.getBasePrice($row),
                        BolPlaza.getPrice($row)
                    )
                );
            },

            updatePrice: function($row) {
                $row.find("input[name^=bolplaza_price_]").val(
                    BolPlaza.calculatePrice(
                        BolPlaza.getBasePrice($row),
                        BolPlaza.getFinalPrice($row)
                    )
                );
            },

            getPrice: function($row) {
                var value = $row.find("input[name^=bolplaza_price_]").val();
                return parseFloat(value);
            },

            getBasePrice: function($row) {
                var value = $row.find("input[name^=bolplaza_final_price_]").data('base-price');
                return parseFloat(value);
            },

            setFinalPrice: function($row, price) {
                $row.find("input[name^=bolplaza_final_price_]").val(price);
            },

            getFinalPrice: function($row) {
                var value = $row.find("input[name^=bolplaza_final_price_]").val();
                return parseFloat(value);
            },

            calculatePrice: function(basePrice, finalPrice) {
                return (parseFloat(finalPrice) - parseFloat(basePrice)).toFixed(2);
            },

            calculateFinalPrice: function(basePrice, price) {
                return (parseFloat(basePrice) + parseFloat(price)).toFixed(2);
            }
        };
        $('#toggle_bolplaza_check').click(function() {
            var value = $('#toggle_bolplaza_check').prop('checked');
            var checkBoxes = $("input[name^=bolplaza_published_]");
            checkBoxes.prop("checked", value);
        });

        $('.bolplaza-price').change(function() {
            BolPlaza.updateFinalPrice($(this).closest('.bol-plaza-item'));
        });

        $('.bolplaza-price-final').change(function() {
            BolPlaza.updatePrice($(this).closest('.bol-plaza-item'));
        });

        $('#toggle_bolplaza_price').change(function() {
            var value = $(this).val();
            var prices = $("input[name^=bolplaza_price_]");
            prices.val(value);
            $(".bol-plaza-item").each(function() {
                BolPlaza.updateFinalPrice($(this));
            });
        });

        $('#toggle_bolplaza_final_price').change(function() {
            var value = $(this).val();
            var prices = $("input[name^=bolplaza_final_price_]");
            prices.val(value);
            $(".bol-plaza-item").each(function() {
                BolPlaza.updatePrice($(this));
            });
        });

        $('.use_calculated_price').click(function() {
            var value = $(this).data('val');
            $(".bol-plaza-item").each(function() {
                var $row = $(this);
                BolPlaza.setFinalPrice($row, value);
                BolPlaza.updatePrice($row);
            });
        });

        $('.use_calculated_prices').click(function() {
            $(".bol-plaza-item").each(function() {
                var $row = $(this);
                var value = $row.find('.use_calculated_price').data('val');
                BolPlaza.setFinalPrice($row, value);
                BolPlaza.updatePrice($row);
            });
        });
    </script>
{/if}
