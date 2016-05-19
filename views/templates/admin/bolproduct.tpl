<input type="hidden" name="bolplaza_loaded" value="1">
{if isset($product->id)}
<div class="panel product-tab" id="product-ModuleBolplaza">
  <input type="hidden" name="submitted_tabs[]" value="Bolplaza" />
  <h3 class="tab">{l s='Bol.com settings'}</h3>
  <div class="row">
		<div class="alert alert-info" style="display:block; position:'auto';">
			<p>{l s='This interface allows you to edit the Bol.com data.'}</p>
			<p>{l s='You can also specify product/product combinations. '}</p>
		</div>
	</div>
  <div class="row">
    <div class="col-lg-12">
      <table class="table">
        <thead>
          <tr>
            <th class="width: 10%; min-width: 50px;" align="center"><span class="title_box">{l s='Published'}</span></th>
            <th style="width: 50%"><span class="title_box">{l s='Product'}</span></th>
            <th style="width: 40%"><span class="title_box">{l s='Custom price (optional)'}</span></th>
          </tr>
        </thead>
        <tbody>
        {foreach $attributes AS $index => $attribute}
          {assign var=price value=''}
          {assign var=selected value=''}
          {if array_key_exists($attribute['id_product_attribute'], $bol_products)}
            {assign var=price value=$bol_products[$attribute['id_product_attribute']]['price']}
            {assign var=selected value=$bol_products[$attribute['id_product_attribute']]['published']}
          {/if}
          <tr {if $index is odd}class="alt_row"{/if}>
            <td class="fixed-width-xs" align="center"><input type="checkbox"
              name="bolplaza_published_{$attribute['id_product']}_{$attribute['id_product_attribute']}"
              {if $selected == true}checked="checked"{/if}
              value="1" />
            </td>
            <td>{$product_designation[$attribute['id_product_attribute']]}</td>
            <td>
              <div class="input-group">
          			<span class="input-group-addon"> &euro;</span>
          			<input name="bolplaza_price_{$attribute['id_product']}_{$attribute['id_product_attribute']}" type="text" value="{$price|escape:'html':'UTF-8'}" onchange="noComma('BOLPLAZA_PRICE');" maxlength="27">
          		</div>
            </td>
          </tr>
        {/foreach}
        </tbody>
      </table>
    </div>
  </div>
  <div class="panel-footer">
    <a href="{$link->getAdminLink('AdminProducts')}" class="btn btn-default"><i class="process-icon-cancel"></i> {l s='Cancel'}</a>
    <button type="submit" name="submitAddproduct" class="btn btn-default pull-right"><i class="process-icon-save"></i> {l s='Save'}</button>
    <button type="submit" name="submitAddproductAndStay" class="btn btn-default pull-right"><i class="process-icon-save"></i> {l s='Save and stay'}</button>
  </div>
</div>
{/if}
