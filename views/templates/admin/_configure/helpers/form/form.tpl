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
{extends file="helpers/form/form.tpl"}

{block name="script"}
	$(document).ready(function() {
        var showSplitted = function (value) {
            $('#fieldset_2_2').css('display', (value == 1) ? 'block' : 'none');
        }
        $('input[name="bolplaza_orders_enable_splitted"]').change(function() {
            showSplitted($(this).val());
        });
        showSplitted($('input[name="bolplaza_orders_enable_splitted"]:checked').val());
	});
{/block}
