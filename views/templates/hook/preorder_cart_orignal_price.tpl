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
{if isset($full_payment) && $full_payment == 0}
    {if isset($price_type)}
        {if $price_type == 2 || $price_type == 1}
            <div>
                <span class="text-info">{l s='Original Price: ' mod='preorder'}{$orginalPrice|escape:'html':'UTF-8'}<span>
            </div>
        {/if}
    {/if}
{/if}
{if !$secondOrder}
    <div class="mt-1">
        <span class="bg-primary text-white" style="padding: 0.2rem 0.5rem; border-radius:0.2rem; font-weight:bold">{l s='Preorder' mod='preorder'}</span>
    </div>
{/if}