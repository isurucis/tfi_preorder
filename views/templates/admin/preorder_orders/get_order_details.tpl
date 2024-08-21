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

{if $other_order_ids}
<div class="btn-group-action">
	<div class="btn-group pull-right">
		<a class="btn btn-default" target="_blank" href="{$orderlink|escape:'html':'UTF-8'}" title="{l s='View orders' mod='preorder'}"><i class="icon-eye"></i>{l s=' View' mod='preorder'}</a>
		<button class="btn btn-default dropdown-toggle" data-toggle="dropdown">
			<i class="icon-caret-down"></i>
		</button>
		<ul class="dropdown-menu">
			{foreach $other_order_ids as $key => $order}
			<li>
				<a target="_blank" href="{$order['order_page_link']|escape:'html':'UTF-8'}"><i class="icon-eye"></i>{$order['reference']|escape:'html':'UTF-8'}</a>
			</li>
			{if ($key+1) != count($other_order_ids)}
				<li class="divider"></li>
			{/if}
			{/foreach}
		</ul>
	</div>
</div>
{else}
<span style="width:20px; margin-right:5px;">
	<a class=" btn btn-default" target="_blank" href="{$orderlink|escape:'html':'UTF-8'}" title="{l s='Details' mod='preorder'}"><i class="icon-eye-open"></i>{l s=' View' mod='preorder'}</a>
</span>
{/if}
