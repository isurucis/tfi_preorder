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


<style type="text/css">
p.alert-warning{
	display: none;
}
</style>
{if $status == 'ok'}
<div class="box">
	<p>{l s='Your preorder on %s is complete.' sprintf=$shop_name mod='preorder'}
		<br />{l s='Amount' mod='preorder'} <span class="price"><strong>{$total_to_pay|escape:'htmlall':'UTF-8'}</strong>
		</span>
		<br />{l s='An email has been sent with this information.' mod='preorder'}
		<br />{l s='If you have questions, comments or concerns, please contact our' mod='preorder'} <a href="{$link->getPageLink('contact', true)|escape:'htmlall':'UTF-8'}">{l s='expert customer preorder team' mod='preorder'}</a>.
	</p>
</div>
{else}
	<p class="warning">
		{l s='We noticed a problem with your order. If you think this is an error, feel free to contact our' mod='preorder'}
		<a href="{$link->getPageLink('contact', true)|escape:'htmlall':'UTF-8'}">{l s='Expert customer support team' mod='preorder'}</a>.
	</p>
{/if}
