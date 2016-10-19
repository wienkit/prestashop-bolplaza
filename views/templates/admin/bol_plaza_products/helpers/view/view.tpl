{*
* 2007-2016 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author PrestaShop SA <contact@prestashop.com>
*  @copyright  2007-2016 PrestaShop SA
*  @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
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
                {else}
                    <div class="row">
                        <p class="alert alert-danger">{l s='No Bol.com product found' mod='bolplaza'}</p>
                        <p>{l s='There is no product found in the Bol.com environment. You can retrieve the Bol.com updates via the Bol.com Products overview page. Try to submit the product again if it still isn\'t visible. You can do this at the same page, by setting the product status to \'new\'.'  mod='bolplaza'}</p>
                    </div>
                {/if}
            </div>

            {if $ownoffer}
                <div class="panel">
                    <h3>{l s='Bol.com publication status' mod='bolplaza'}</h3>
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
                </div>
            {/if}
        </div>
    </div>
{/block}

