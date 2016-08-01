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
