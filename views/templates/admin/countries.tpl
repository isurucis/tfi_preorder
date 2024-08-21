{**
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License version 3.0
* that is bundled with this package in the file LICENSE.txt
* It is also available through the world-wide-web at this URL:
* https://opensource.org/licenses/AFL-3.0
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade this module to a newer
* versions in the future. If you wish to customize this module for your needs
* please refer to CustomizationPolicy.txt file inside our module for more information.
*
* @author Webkul IN
* @copyright Since 2010 Webkul
* @license https://opensource.org/licenses/AFL-3.0 Academic Free License version 3.0
*}
<div class="row">
    <div style="height: 250px; overflow-y: auto;" class="col-lg-6">
        <table style="border-spacing : 0; border-collapse : collapse;" class="table table-bordered">
            <thead>
                <tr>
                    <th>
                        <input type="checkbox" id="checkAll" name="checkAll">
                    </th>
                    <th>{l s='Select all' mod='preorder'}</th>
                </tr>
            </thead>
            <tbody>
            {if isset($country)}
                {foreach $country as $countrydetail}
                    <tr>
                        <td><input type="checkbox" class="country_checkbox" value="{$countrydetail.id_country|escape:'html':'UTF-8'}"
                            name="WK_PREORDER_COUNTRY[]"
                            {if isset($allowed_countries)}{foreach $allowed_countries as $country}{if $country eq $countrydetail.id_country}checked{/if}{/foreach}{/if}
                                id=""></td>
                        <td>{$countrydetail.name|escape:'html':'UTF-8'}</td>
                        </tr>
                    {/foreach}
                {/if}
                </tbody>
        </table>
    </div>
</div>

<script>
    $("#checkAll").click(function() {
        $('.country_checkbox').not(this).prop('checked', this.checked);
    });
</script>
