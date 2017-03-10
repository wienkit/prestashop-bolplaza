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
  #bolplaza_combinations {
    padding: 0;
  }
  select {
    max-width: 200px;
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
          <th align="center">{l s='Published' mod='bolplaza'}</th>
          <th>{l s='Product' mod='bolplaza'}</th>
          <th>{l s='Calculated price' mod='bolplaza'}</th>
          <th>{l s='Price' mod='bolplaza'} ({l s='optional' mod='bolplaza'})</th>
          <th>{l s='EAN' mod='bolplaza'} ({l s='optional' mod='bolplaza'})</th>
          <th>{l s='Delivery time' mod='bolplaza'} ({l s='optional' mod='bolplaza'})</th>
        </tr>
        </thead>
        <tbody>
        <tr>
          <td class="fixed-width-xs" align="center"><input type="checkbox" id="toggle_bolplaza_check"  /> </td>
          <td colspan="2">-- {l s='All products' mod='bolplaza'} -- </td>
          <td>
            <div class="input-group money-type">
              <span class="input-group-addon">€ </span>
              <input type="text" id="toggle_bolplaza_price" onchange="noComma('toggle_bolplaza_price');" class="form-control">
            </div>
          </td>
          <td></td>
          <td></td>
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
          <tr>
            <td class="fixed-width-xs" align="center"><input type="checkbox"
                                                             name="bolplaza_published_{$attribute['id_product']}_{$attribute['id_product_attribute']}"
                                                             {if $selected == true}checked="checked"{/if}
                                                             value="1" />
            </td>
            <td class="clickable collapsed" data-toggle="collapse" data-target=".{$index}collapsed">
                {$product_designation[$attribute['id_product_attribute']]}
              <i class="icon-caret-up pull-right"></i>
            </td>
            <td>
              <a class="use_calculated_price" data-val="{$calculated_price[$attribute['id_product_attribute']]|string_format:"%.2f"}">&euro; {$calculated_price[$attribute['id_product_attribute']]|string_format:"%.2f"}</a>
            </td>
            <td>
              <div class="input-group money-type">
                <span class="input-group-addon">€ </span>
                <input name="bolplaza_price_{$attribute['id_product']}_{$attribute['id_product_attribute']}"
                       id="bolplaza_price_{$attribute['id_product']}_{$attribute['id_product_attribute']}"
                       type="text"
                       value="{if $price}{$price|string_format:"%.2f"}{/if}"
                       onchange="noComma('bolplaza_price_{$attribute['id_product']}_{$attribute['id_product_attribute']}');"
                       class="form-control">
              </div>
            </td>
            <td>
              <input name="bolplaza_ean_{$attribute['id_product']}_{$attribute['id_product_attribute']}" id="bolplaza_ean_{$attribute['id_product']}_{$attribute['id_product_attribute']}" type="text" class="form-control" value="{if isset($ean)}{$ean}{/if}" maxlength="27">
            </td>
            <td>
              <select name="bolplaza_delivery_time_{$attribute['id_product']}_{$attribute['id_product_attribute']}" id="bolplaza_delivery_time_{$attribute['id_product']}_{$attribute['id_product_attribute']}" data-toggle="select2">
                <option value=""{if !isset($delivery_time) || $delivery_time == '' } selected="selected"{/if}>-- {l s='Use default' mod='bolplaza'} --</option>
                  {foreach $delivery_codes AS $code}
                    <option value="{$code['deliverycode']}"{if isset($delivery_time) && $delivery_time == $code['deliverycode']} selected="selected"{/if}>{$code['deliverycode']}</option>
                  {/foreach}
              </select>
            </td>
          </tr>
        {/foreach}
        </tbody>
      </table>
    </div>
  </div>
</div>
<script>
  $('#toggle_bolplaza_check').click(function() {
    var value = $('#toggle_bolplaza_check').prop('checked');
    var checkBoxes = $("input[name^=bolplaza_published_]");
    checkBoxes.prop("checked", value);
  });
  $('#toggle_bolplaza_price').change(function() {
    var value = $(this).val();
    var prices = $("input[name^=bolplaza_price_]");
    prices.val(value);
  });
  $('.use_calculated_price').click(function(e) {
    var value = $(this).data('val');
    var prices = $("input[name^=bolplaza_price_]");
    prices.val(value);
  });
</script>
{/if}
