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
*  @copyright 2013-2016 Wienk IT
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
            <th style="width: 40%"><span class="title_box">{l s='Product' mod='bolplaza'}</span></th>
            <th style="width: 10%"><span class="title_box">{l s='Calculated price' mod='bolplaza'}</span></th>
            <th style="width: 40%"><span class="title_box">{l s='Custom price (optional)' mod='bolplaza'}</span></th>
          </tr>
        </thead>
        <tbody>
          <tr class="alt_row">
            <td class="fixed-width-xs" align="center"><input type="checkbox" id="toggle_bolplaza_check"  /> </td>
            <td colspan="2">-- {l s='All products' mod='bolplaza'} -- </td>
            <td>
              <div class="input-group">
                <span class="input-group-addon"> &euro;</span>
                <input id="toggle_bolplaza_price" type="text" onchange="noComma('toggle_bolplaza_price');" maxlength="27" />
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
            <tr{if $index is odd} class="alt_row"{/if}>
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
                <a class="use_calculated_price" data-val="{$calculated_price[$attribute['id_product_attribute']]|escape:'htmlall':'UTF-8'|string_format:"%.2f"}">&euro; {$calculated_price[$attribute['id_product_attribute']]|escape:'htmlall':'UTF-8'|string_format:"%.2f"}</a>
              </td>
              <td>
                <div class="input-group">
                      <span class="input-group-addon"> &euro;</span>
                      <input name="bolplaza_price_{$attribute['id_product']|escape:'htmlall':'UTF-8'}_{$attribute['id_product_attribute']|escape:'htmlall':'UTF-8'}"
                             id="bolplaza_price_{$attribute['id_product']|escape:'htmlall':'UTF-8'}_{$attribute['id_product_attribute']|escape:'htmlall':'UTF-8'}"
                             type="text"
                             value="{if $price}{$price|escape:'html':'UTF-8'|string_format:"%.2f"}{else}{$calculated_price[$attribute['id_product_attribute']]|escape:'htmlall':'UTF-8'|string_format:"%.2f"}{/if}"
                             onchange="noComma('bolplaza_price_{$attribute['id_product']|escape:'htmlall':'UTF-8'}_{$attribute['id_product_attribute']|escape:'htmlall':'UTF-8'}');"
                             maxlength="27">
                  </div>
              </td>
            </tr>
            <tr class="collapse out {$index|escape:'htmlall':'UTF-8'}collapsed{if $index is odd} alt_row{/if}">
              <td>&nbsp;</td>
              <td colspan="2">
                {l s='Custom EAN (optional)' mod='bolplaza'}
              </td>
              <td>
                <input name="bolplaza_ean_{$attribute['id_product']|escape:'htmlall':'UTF-8'}_{$attribute['id_product_attribute']|escape:'htmlall':'UTF-8'}" id="bolplaza_ean_{$attribute['id_product']|escape:'htmlall':'UTF-8'}_{$attribute['id_product_attribute']|escape:'htmlall':'UTF-8'}" type="text" value="{if isset($ean)}{$ean|escape:'html':'UTF-8'}{/if}" maxlength="27">
              </td>
            </tr>
            <tr class="collapse out {$index|escape:'htmlall':'UTF-8'}collapsed{if $index is odd} alt_row{/if}">
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
