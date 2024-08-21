{*
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
<div class="card" id="view_preorder_details">
    <div class="card-header">
    {if $performed_orders eq 1}
        <h3 class="card-header-title">{l s='Preorder details' mod='preorder'}</h3>
    {elseif $performed_orders eq 2}
        <h3 class="card-header-title">{l s='Preorder partial payment details' mod='preorder'}</h3>
    {/if}
    </div>
    <div class="cart-body mb-3 ml-2">
        <table class="table mb-0" data-role="preorder-details-table">
            <thead>
                <tr>
                    <th>{l s='Product name' mod='preorder'}</th>
                    <th>{l s='Quantity' mod='preorder'}</th>
                    {if $performed_orders eq 1}
                        <th>{l s='Paid amount' mod='preorder'}</th>
                        <th>{l s='Remaining amount' mod='preorder'}</th>
                        <th>{l s='Status' mod='preorder'}</th>
                    {elseif $performed_orders eq 2}
                        <th>{l s='Preorder price' mod='preorder'}</th>
                        <th>{l s='Order date' mod='preorder'}</th>
                    {/if}
                    <th>{l s='Order reference' mod='preorder'}</th>
                </tr>
            </thead>
            <tbody>
                {foreach from=$product_details item=$detail}
                    <tr>
                        <td>{$detail.product_name|escape:'html':'UTF-8'}</td>
                        <td>{$detail.quantity|escape:'html':'UTF-8'}</td>
                        {if $performed_orders eq 1}
                            <td>{$detail.paid_amt|escape:'html':'UTF-8'}</td>
                            <td>{$detail.rem_amt|escape:'html':'UTF-8'}</td>
                            <td>{$detail.status|escape:'html':'UTF-8'}</td>
                        {elseif $performed_orders eq 2}
                            <td>{$detail.paid_amt|escape:'html':'UTF-8'}</td>
                            <td>{$detail.order_date|escape:'html':'UTF-8'}</td>
                        {/if}
                        <td>
                            {$detail.order_reference|escape:'html':'UTF-8'}
                            {if $detail.order_link}
                                <a href="{$detail.order_link|escape:'html':'UTF-8'}"><i class="material-icons text-muted">visibility</i></a>
                            {/if}
                        </td>
                    </tr>
                {/foreach}
            </tbody>
        </table>
    </div>
    <div class="cart-footer"></div>
</div>