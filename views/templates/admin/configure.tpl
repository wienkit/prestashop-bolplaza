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
*  @author    PrestaShop SA <contact@prestashop.com>
*  @copyright 2007-2016 PrestaShop SA
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*}

<div class="panel">
    <h3>{l s='How do I use this module?' mod='bolplaza'}</h3>
    <div class="row">
        <div class="col-md-2 text-center"><img src="{$module_dir|escape}/logo.png" id="bolplaza-logo" /></div>
        <div class="col-md-10">
            <p class="lead">
                {l s='This module uses the Bol.com seller account functionality. You can apply for an account at Bol.com.' mod='bolplaza'}
            </p>
            <p>
                {l s='Find help online at ' mod='bolplaza'}<a href="http://www.werkaandewebshop.com/bolplaza-docs/">{l s='the online documentation (dutch)' mod='bolplaza'}</a>.
            </p>
            <p><a data-toggle="collapse" href="#collapseAdvanced" aria-expanded="false" aria-controls="collapseAdvanced">
                {l s='Show advanced options' mod='bolplaza'}
            </a></p>
            <div class="collapse" id="collapseAdvanced">
                <div class="well">
                    <strong>Cron URL:</strong> {$cron_url|escape}
                </div>
            </div>
        </div>
    </div>
</div>
