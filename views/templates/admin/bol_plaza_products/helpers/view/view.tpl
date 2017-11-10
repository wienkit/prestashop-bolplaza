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

{extends file="helpers/view/view.tpl"}

{block name="override_tpl"}
    <div class="row">
        <div class="col-lg-6">
            <div class="panel">
                <h1>{$title|escape:'htmlall':'UTF-8'}</h1>
                <hr />
                <div class="row">
                    <div class="col-lg-3">
                        {l s='ID' mod='bolplaza'}
                    </div>
                    <div class="col-lg-9">
                        {$bolproduct->id|escape:'htmlall':'UTF-8'}
                    </div>
                </div>
                <div class="row">
                    <div class="col-lg-3">
                        {l s='Published' mod='bolplaza'}
                    </div>
                    <div class="col-lg-9">
                        {$bolproduct->published|escape:'htmlall':'UTF-8'}
                    </div>
                </div>
                <div class="row">
                    <div class="col-lg-3">
                        {l s='Price' mod='bolplaza'}
                    </div>
                    <div class="col-lg-9">
                        {$bolproduct->price|escape:'htmlall':'UTF-8'}
                    </div>
                </div>
                <div class="row">
                    <div class="col-lg-3">
                        {l s='Stock' mod='bolplaza'}
                    </div>
                    <div class="col-lg-9">
                        {$stock|escape:'htmlall':'UTF-8'}
                    </div>
                </div>
                <div class="row">
                    <div class="col-lg-3">
                        {l s='Delivery code' mod='bolplaza'}
                    </div>
                    <div class="col-lg-9">
                        {$delivery_code|escape:'htmlall':'UTF-8'}
                    </div>
                </div>
                <div class="row">
                    <div class="col-lg-3">
                        {l s='Delivery code 2' mod='bolplaza'}
                    </div>
                    <div class="col-lg-9">
                        {$delivery_code_2|escape:'htmlall':'UTF-8'}
                    </div>
                </div>
            </div>

            <div class="panel">
                <div class="row">
                    {foreach $links as $link}
                        <a href="{$link.link|escape:'htmlall':'UTF-8'}" class="btn btn-primary">{$link.title|escape:'htmlall':'UTF-8'}</a>
                    {/foreach}
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="panel">
                <h1>{l s='Bol.com stored data' mod='bolplaza'}</h1>
                <hr />
                {if $ownoffer}
                    <div class="row">
                        <div class="col-lg-3">
                            {l s='ID' mod='bolplaza'}
                        </div>
                        <div class="col-lg-9">
                            {$ownoffer['id_bolplaza_product']|escape:'htmlall':'UTF-8'}
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-lg-3">
                            {l s='Published' mod='bolplaza'}
                        </div>
                        <div class="col-lg-9">
                            {$ownoffer['published']|escape:'htmlall':'UTF-8'}
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-lg-3">
                            {l s='Price' mod='bolplaza'}
                        </div>
                        <div class="col-lg-9">
                            {$ownoffer['price']|escape:'htmlall':'UTF-8'}
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-lg-3">
                            {l s='Stock' mod='bolplaza'}
                        </div>
                        <div class="col-lg-9">
                            {$ownoffer['stock']|escape:'htmlall':'UTF-8'}
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-lg-3">
                            {l s='Delivery code' mod='bolplaza'}
                        </div>
                        <div class="col-lg-9">
                            {$ownoffer['delivery_code']|escape:'htmlall':'UTF-8'}
                        </div>
                    </div>
                    <div class="row">
                        <h3>{l s='Bol.com publication status' mod='bolplaza'}</h3>
                    </div>
                    <div class="row">
                        <div class="col-lg-3">
                            {l s='Published' mod='bolplaza'}
                        </div>
                        <div class="col-lg-9">
                            {$ownoffer['published']|escape:'htmlall':'UTF-8'}
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-lg-3">
                            {l s='Reasoncode' mod='bolplaza'}
                        </div>
                        <div class="col-lg-9">
                            {$ownoffer['reasoncode']|escape:'htmlall':'UTF-8'}
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-lg-3">
                            {l s='Reason' mod='bolplaza'}
                        </div>
                        <div class="col-lg-9">
                            {$ownoffer['reason']|escape:'htmlall':'UTF-8'}
                        </div>
                    </div>
                {else}
                    <div class="row">
                        <p class="alert alert-danger">{l s='No Bol.com product found' mod='bolplaza'}</p>
                        <p>{l s='There is no product found in the Bol.com environment. You can retrieve the Bol.com updates via the Bol.com Products overview page. Try to submit the product again if it still isn\'t visible. You can do this at the same page, by setting the product status to \'new\'.'  mod='bolplaza'}</p>
                    </div>
                {/if}
            </div>

            {if $ownoffer_2}
            <div class="panel">
                <h1>{l s='Bol.com stored data (secondary account)' mod='bolplaza'}</h1>
                <hr />
                <div class="row">
                    <div class="col-lg-3">
                        {l s='ID' mod='bolplaza'}
                    </div>
                    <div class="col-lg-9">
                        {$ownoffer_2['id_bolplaza_product']|escape:'htmlall':'UTF-8'}
                    </div>
                </div>
                <div class="row">
                    <div class="col-lg-3">
                        {l s='Published' mod='bolplaza'}
                    </div>
                    <div class="col-lg-9">
                        {$ownoffer_2['published']|escape:'htmlall':'UTF-8'}
                    </div>
                </div>
                <div class="row">
                    <div class="col-lg-3">
                        {l s='Price' mod='bolplaza'}
                    </div>
                    <div class="col-lg-9">
                        {$ownoffer_2['price']|escape:'htmlall':'UTF-8'}
                    </div>
                </div>
                <div class="row">
                    <div class="col-lg-3">
                        {l s='Stock' mod='bolplaza'}
                    </div>
                    <div class="col-lg-9">
                        {$ownoffer_2['stock']|escape:'htmlall':'UTF-8'}
                    </div>
                </div>
                <div class="row">
                    <div class="col-lg-3">
                        {l s='Delivery code' mod='bolplaza'}
                    </div>
                    <div class="col-lg-9">
                        {$ownoffer_2['delivery_code']|escape:'htmlall':'UTF-8'}
                    </div>
                </div>
                <div class="row">
                    <h3>{l s='Bol.com publication status' mod='bolplaza'}</h3>
                </div>
                <div class="row">
                    <div class="col-lg-3">
                        {l s='Published' mod='bolplaza'}
                    </div>
                    <div class="col-lg-9">
                        {$ownoffer_2['published']|escape:'htmlall':'UTF-8'}
                    </div>
                </div>
                <div class="row">
                    <div class="col-lg-3">
                        {l s='Reasoncode' mod='bolplaza'}
                    </div>
                    <div class="col-lg-9">
                        {$ownoffer_2['reasoncode']|escape:'htmlall':'UTF-8'}
                    </div>
                </div>
                <div class="row">
                    <div class="col-lg-3">
                        {l s='Reason' mod='bolplaza'}
                    </div>
                    <div class="col-lg-9">
                        {$ownoffer_2['reason']|escape:'htmlall':'UTF-8'}
                    </div>
                </div>
                <div class="row">
                    <p class="alert alert-danger">{l s='No Bol.com product found' mod='bolplaza'}</p>
                    <p>{l s='There is no product found in the Bol.com environment. You can retrieve the Bol.com updates via the Bol.com Products overview page. Try to submit the product again if it still isn\'t visible. You can do this at the same page, by setting the product status to \'new\'.'  mod='bolplaza'}</p>
                </div>
            </div>
            {/if}
        </div>
    </div>
{/block}

