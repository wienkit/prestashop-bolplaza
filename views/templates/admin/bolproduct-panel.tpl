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
    .input-group-addon.btn {
        color: #fff;
    }
</style>
<input type="hidden" name="bolplaza_loaded" value="1">
{if isset($product->id)}
    <div class="row">
        <div class="col-md-12">
            <div class="alert alert-info" role="alert">
                <i class="material-icons">help</i>
                <p>{l s='This interface allows you to edit the Bol.com data.' mod='bolplaza'}</p>
                <p>{l s='You can also specify product/product combinations.' mod='bolplaza'}</p>
            </div>
        </div>
    </div>
    <div class="panel panel-default">
        <div class="panel-heading"><strong>{l s='Bol.com settings' mod='bolplaza'}</strong></div>
        <div class="panel-body" id="bolplaza_combinations">
            <div>
                <table class="table table-striped table-no-bordered">
                    <thead>
                    <tr class="text-uppercase">
                        <th class="width: 10%; min-width: 50px;" align="center"><span class="title_box">{l s='Published' mod='bolplaza'}</span></th>
                        <th style="width: 25%"><span class="title_box">{l s='Product' mod='bolplaza'}</span></th>
                        <th style="width: 10%"><span class="title_box">{l s='Base price' mod='bolplaza'}</span></th>
                        <th style="width: 20%"><span class="title_box">{l s='Price change' mod='bolplaza'}</span></th>
                        <th style="width: 10%"><span class="title_box">{l s='Proposed price' mod='bolplaza'}</span></th>
                        <th style="width: 20%"><span class="title_box">{l s='Final price' mod='bolplaza'}</span></th>
                        <th style="width: 5%"><span class="title_box">{l s='Edit' mod='bolplaza'}</span></th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr>
                        <td class="fixed-width-xs" align="center"><input type="checkbox" id="toggle_bolplaza_check"  /> </td>
                        <td colspan="2">-- {l s='All products' mod='bolplaza'} -- </td>
                        <td>
                            <div class="input-group money-type">
                                <span class="input-group-addon">+ &euro;</span>
                                <input id="toggle_bolplaza_price" class="form-control" type="text" onchange="noComma('toggle_bolplaza_price');" maxlength="27" />
                            </div>
                        </td>
                        <td>
                            <a class="use_calculated_prices" title="{l s='Select each proposed price' mod='bolplaza'}">-- {l s='Select' mod='bolplaza'} --</a></td>
                        <td>
                            <div class="input-group money-type">
                                <span class="input-group-addon"> &euro;</span>
                                <input id="toggle_bolplaza_final_price" class="form-control" type="text" onchange="noComma('toggle_bolplaza_final_price');" maxlength="27" />
                            </div>
                        </td>
                        <td></td>
                    </tr>
                    {foreach $attributes AS $index => $attribute}
                        {assign var=price value=''}
                        {assign var=selected value=''}
                        {assign var=delivery_time value='default'}
                        {assign var=delivery_time_2 value='default'}
                        {assign var=prod_condition value='0'}
                        {assign var=ean value=$attribute['ean13']}
                        {assign var=key value=$attribute['id_product']|cat:'_'|cat:$attribute['id_product_attribute']}
                        {if array_key_exists($attribute['id_product_attribute'], $bol_products)}
                            {assign var=price value=$bol_products[$attribute['id_product_attribute']]['price']}
                            {assign var=selected value=$bol_products[$attribute['id_product_attribute']]['published']}
                            {assign var=delivery_time value=$bol_products[$attribute['id_product_attribute']]['delivery_time']}
                            {assign var=delivery_time_2 value=$bol_products[$attribute['id_product_attribute']]['delivery_time_2']}
                            {assign var=prod_condition value=$bol_products[$attribute['id_product_attribute']]['condition']}
                            {assign var=ean value=$bol_products[$attribute['id_product_attribute']]['ean']}
                        {/if}
                        <tr class="bol-plaza-item" data-key="{$key}">
                            <td class="fixed-width-xs" align="center"><input type="checkbox"
                                                                             name="bolplaza_published_{$key}"
                                                                             {if $selected == true}checked="checked"{/if}
                                                                             value="1" />
                            </td>
                            <td class="clickable collapsed" data-toggle="collapse" data-target=".{$index}collapsed">
                                {$product_designation[$attribute['id_product_attribute']]}
                                <i class="icon-caret-up pull-right"></i>
                            </td>
                            <td>
                                € {$base_price[$attribute['id_product_attribute']]|escape:'htmlall':'UTF-8'|string_format:"%.2f"}
                            </td>
                            <td>
                                <div class="input-group money-type">
                                    <span class="input-group-addon">+ &euro;</span>
                                    <input name="bolplaza_price_{$key}"
                                           id="bolplaza_price_{$key}"
                                           type="text"
                                           class="bolplaza-price form-control"
                                           value="{if $price}{$price|escape:'htmlall':'UTF-8'|string_format:"%.2f"}{/if}"
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
                                <div class="input-group money-type">
                                    <span class="input-group-addon">€ </span>
                                    <input name="bolplaza_final_price_{$key}"
                                           id="bolplaza_final_price_{$key}"
                                           type="text"
                                           data-base-price="{$base_price[$attribute['id_product_attribute']]|escape:'htmlall':'UTF-8'|string_format:"%.2f"}"
                                           class="form-control bolplaza-price-final"
                                           value="{($price + $base_price[$attribute['id_product_attribute']])|escape:'htmlall':'UTF-8'|string_format:"%.2f"}"
                                           maxlength="14">
                                </div>
                            </td>
                            <td>
                                <div>
                                    <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#bol-modal-{$index}">
                                        <i class="material-icons">edit</i>
                                    </button>
                                    <div class="modal fade" id="bol-modal-{$index}" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
                                        <div class="modal-dialog" role="document">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                        <span aria-hidden="true">&times;</span>
                                                    </button>
                                                    <h4 class="modal-title" id="myModalLabel">{$product_designation[$attribute['id_product_attribute']]}</h4>
                                                </div>
                                                <div class="modal-body">
                                                    <div class="row form-group">
                                                        <div class="col-sm-4">{l s='Condition' mod='bolplaza'}</div>
                                                        <div class="col-sm-8">
                                                            <select name="bolplaza_condition_{$key}" id="bolplaza_condition_{$key}" data-toggle="select2">
                                                                {foreach $conditions AS $condition}
                                                                    <option value="{$condition['value']}"{if $prod_condition == $condition['value']} selected="selected"{/if} data-code="{$condition['code']}">
                                                                        {$condition['description']}
                                                                    </option>
                                                                {/foreach}
                                                            </select>
                                                        </div>
                                                    </div>
                                                    <div class="row form-group">
                                                        <div class="col-sm-4">{l s='EAN' mod='bolplaza'}</div>
                                                        <div class="col-sm-8">
                                                            <input name="bolplaza_ean_{$key}" id="bolplaza_ean_{$key}" type="text" value="{if isset($ean)}{$ean}{/if}" maxlength="27" class="form-control">
                                                        </div>
                                                    </div>
                                                    <div class="row form-group">
                                                        <div class="col-sm-4">{l s='Custom Delivery time (optional)' mod='bolplaza'}</div>
                                                        <div class="col-sm-8">
                                                            <select name="bolplaza_delivery_time_{$key}" id="bolplaza_delivery_time_{$key}" data-toggle="select2">
                                                                <option value="default" {if $delivery_time == 'default'} selected="selected"{/if}>-- {l s='Use default' mod='bolplaza'} --</option>
                                                                {foreach $delivery_codes AS $code}
                                                                    <option value="{$code['deliverycode']}"{if isset($delivery_time) && $delivery_time == $code['deliverycode']} selected="selected"{/if}>{$code['description']}</option>
                                                                {/foreach}
                                                            </select>
                                                        </div>
                                                    </div>
                                                    {if $splitted}
                                                        <div class="row form-group">
                                                            <div class="col-sm-4">{l s='Custom Delivery time (optional, secondary account)' mod='bolplaza'}</div>
                                                            <div class="col-sm-8">
                                                                <select name="bolplaza_delivery_time_2_{$key}" id="bolplaza_delivery_time_2_{$key}" data-toggle="select2">
                                                                    <option value="default" {if $delivery_time_2 == 'default'} selected="selected"{/if}>-- {l s='Use default' mod='bolplaza'} --</option>
                                                                    {foreach $delivery_codes AS $code}
                                                                        <option value="{$code['deliverycode']}"{if isset($delivery_time_2) && $delivery_time_2 == $code['deliverycode']} selected="selected"{/if}>{$code['description']}</option>
                                                                    {/foreach}
                                                                </select>
                                                            </div>
                                                        </div>
                                                    {/if}
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-primary btn-lg" data-dismiss="modal">{l s='Close' mod='bolplaza'}</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    {/foreach}
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="panel panel-default">
        <div class="panel-heading"><strong>{l s='Bol.com commission calculator' mod='bolplaza'}</strong></div>
        <div class="panel-body" id="bolplaza_combinations">
            <div class="row">
                <div class="col-lg-1"></div>
                <div class="col-lg-4">
                    <div class="row form-group">
                        <label class="control-label col-lg-6" for="calculator_selected_ean">
                            {l s='Currently selected EAN' mod='bolplaza'}
                        </label>
                        <div class="col-lg-6">
                            <input type="text" disabled class="form-control" name="calculator_selected_ean" id="calculator_selected_ean" />
                        </div>
                    </div>
                    <div class="row form-group">
                        <label class="control-label col-lg-6" for="calculator_selected_condition">
                            {l s='Currently selected condition' mod='bolplaza'}
                        </label>
                        <div class="col-lg-6">
                            <input type="text" disabled class="form-control" name="calculator_selected_condition" id="calculator_selected_condition" />
                        </div>
                    </div>
                    <div class="row form-group">
                        <label class="control-label col-lg-6" for="calculator_price">
                            {l s='Final price' mod='bolplaza'}
                        </label>
                        <div class="col-lg-6">
                            <div class="input-group">
                                <input type="text" class="form-control" name="calculator_price" id="calculator_price" />
                                <div class="input-group-addon btn btn-primary" id="calculator_price_btn"><i class="material-icons">refresh</i></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="row form-group">
                        <label class="control-label col-lg-6" for="calculator_fixed_amount">
                            {l s='Fixed amount' mod='bolplaza'}
                        </label>
                        <div class="col-lg-6">
                            <input type="text" disabled class="form-control" name="calculator_fixed_amount" id="calculator_fixed_amount" />
                        </div>
                    </div>
                    <div class="row form-group">
                        <label class="control-label col-lg-6" for="calculator_percentage">
                            {l s='Percentage' mod='bolplaza'}
                        </label>
                        <div class="col-lg-6">
                            <input type="text" disabled class="form-control" name="calculator_percentage" id="calculator_percentage" />
                        </div>
                    </div>
                    <div class="row form-group">
                        <label class="control-label col-lg-6" for="calculator_total">
                            {l s='Total' mod='bolplaza'}
                        </label>
                        <div class="col-lg-6">
                            <input type="text" disabled class="form-control" name="calculator_total" id="calculator_total" />
                        </div>
                    </div>
                    <div class="row form-group">
                        <label class="control-label col-lg-6" for="calculator_total_without_reduction">
                            {l s='Total (without reductions)' mod='bolplaza'}
                        </label>
                        <div class="col-lg-6">
                            <input type="text" disabled class="form-control" name="calculator_total_without_reduction" id="calculator_total_without_reduction" />
                        </div>
                    </div>
                </div>
            </div>
            <div class="row form-group">
                <div class="col-lg-1"></div>
                <label class="control-label col-lg-2">
                    {l s='Reductions' mod='bolplaza'}
                </label>
                <div class="col-lg-6">
                    <table class="table" id="calculator_reductions">
                        <thead>
                        <tr>
                            <th>{l s='MaximumPrice' mod='bolplaza'}</th>
                            <th>{l s='CostReduction' mod='bolplaza'}</th>
                            <th>{l s='StartDate' mod='bolplaza'}</th>
                            <th>{l s='EndDate' mod='bolplaza'}</th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr>
                            <td colspan="4">{l s='None' mod='bolplaza'}</td>
                        </tr>
                        </tbody>
                    </table>
                </div>
            </div>
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
                BolPlaza.initCalculator($row);
            },

            updatePrice: function($row) {
                $row.find("input[name^=bolplaza_price_]").val(
                    BolPlaza.calculatePrice(
                        BolPlaza.getBasePrice($row),
                        BolPlaza.getFinalPrice($row)
                    )
                );
                BolPlaza.initCalculator($row);
            },

            getPrice: function($row) {
                var value = $row.find('input[name^=bolplaza_price_]').val();
                return parseFloat(value);
            },

            getBasePrice: function($row) {
                var value = $row.find('input[name^=bolplaza_final_price_]').data('base-price');
                return parseFloat(value);
            },

            setFinalPrice: function($row, price) {
                $row.find('input[name^=bolplaza_final_price_]').val(price);
            },

            getFinalPrice: function($row) {
                var value = $row.find('input[name^=bolplaza_final_price_]').val();
                return parseFloat(value);
            },

            getEan: function(key) {
                return $('input[name^=bolplaza_ean_' + key +']').val();
            },

            getConditionCode: function(key) {
                return $('select[name^=bolplaza_condition_' + key +'] option:selected').attr('data-code');
            },

            calculatePrice: function(basePrice, finalPrice) {
                return (parseFloat(finalPrice) - parseFloat(basePrice)).toFixed(2);
            },

            calculateFinalPrice: function(basePrice, price) {
                return (parseFloat(basePrice) + parseFloat(price)).toFixed(2);
            },

            initCalculator: function($row) {
                var key = $row.attr("data-key"),
                    price = BolPlaza.getFinalPrice($row),
                    ean = BolPlaza.getEan(key),
                    condition = BolPlaza.getConditionCode(key);
                $('#calculator_selected_ean').val(ean);
                $('#calculator_selected_condition').val(condition);
                $('#calculator_price').val(price);
                BolPlaza.calculateCommission(ean, condition, price);
            },

            updateCalculator: function() {
                var ean = $('#calculator_selected_ean').val(),
                    condition = $('#calculator_selected_condition').val(),
                    price = $('#calculator_price').val();
                BolPlaza.calculateCommission(ean, condition, price);
            },

            calculateCommission: function(ean, condition, price) {
                $.post(
                    baseAdminDir + "ajax-tab.php",
                    {
                        controller:'AdminBolPlazaProducts',
                        token:'{$token}',
                        action:'calculateCommission',
                        ean: ean,
                        price: price,
                        condition: condition
                    }
                ).done(function(content) {
                    content = JSON.parse(content);
                    if(content.failed) {
                        $.growl.error({ title: "", message: content.message});
                    } else {
                        $('#calculator_fixed_amount').val(content.cost.fixed);
                        $('#calculator_percentage').val(content.cost.percentage);
                        $('#calculator_total').val(content.cost.total);
                        $('#calculator_total_without_reduction').val(content.cost.totalWithoutReduction);
                        $('#calculator_reductions tbody').empty();
                        if(content.reductions.length === 0) {
                            $('#calculator_reductions tbody').append('<tr><td colspan="4">{l s='None' mod='bolplaza'}</td></tr>');
                        } else {
                            for (var i = 0; i < content.reductions.length; i++) {
                                $('#calculator_reductions tbody').append(
                                    $('<tr><td>' + content.reductions[i].max + '</td><td>' + content.reductions[i].reduction + '</td><td>' + content.reductions[i].start + '</td><td>' + content.reductions[i].end + '</td></tr>')
                                );
                            }
                        }
                    }
                }).fail(function(xhr, status, error) {
                    $.growl.error({ title: "", message:error});
                });
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

        $(".bol-plaza-item").click(function() {
            var $row = $(this);
            BolPlaza.initCalculator($row);
        });

        $("#calculator_price").change(function() {
            BolPlaza.updateCalculator();
        });
        $("#calculator_price_btn").change(function() {
            BolPlaza.updateCalculator();
        });
    </script>
{/if}
