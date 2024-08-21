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

{extends file=$layout}
{block name='content'}
	<section class="container" style="overflow: auto;" id="main">
		<header class="page-header">
			<h1>{l s='Preorder orders' mod='preorder'}</h1>
		</header>
		<div class="table-responsive card card-block">
			<table class="table table-striped" id="preorder_list">
				<thead>
					<tr>
						<th>{l s='Order reference' mod='preorder'}</th>
						<th>{l s='Product' mod='preorder'}</th>
						<th>{l s='Date' mod='preorder'}</th>
						<th>{l s='Total paid' mod='preorder'}</th>
						<th>{l s='Remaining' mod='preorder'}</th>
						<th>{l s='Status' mod='preorder'}</th>
						<th>{l s='Action' mod='preorder'}</th>
					</tr>
				</thead>
				<tbody>
					{if isset($orderDetails) && $orderDetails}
						{foreach $orderDetails as $key => $orders}
							<tr>
								<td>{$orders.reference|escape:'html':'UTF-8'}</td>
								<td style="text-align:left;">{$orders.product_name|escape:'html':'UTF-8'}</td>
								<td>{$orders.date_add|escape:'html':'UTF-8'}</td>
								<td>{$orders.priceWithCurrency|escape:'html':'UTF-8'}</td>
								{if $orders.preorder_status == 2}
									<td>{$orders.priceRemWithCurrencyComp|escape:'html':'UTF-8'}</td>
								{elseif condition}
									<td>{$orders.priceRemWithCurrency|escape:'html':'UTF-8'}</td>
								{/if}
								<td>
									{if $orders.preorder_status == 1}
										<a class="btn1 btn-primary1" href="{url entity='module' name='preorder' controller='process' params=['add' => 1, 'id_order' => $orders.order_id, 'qty' => $orders.quantity, 'id_product' => $orders.product_id, 'ipa' => $orders.attribute_id, 'token' => $static_token]}">
											{l s='Complete preorder' mod='preorder'}
										</a>
									{elseif $orders.preorder_status == 2}
										{l s='Preorder completed' mod='preorder'}
									{elseif $orders.preorder_status == 0}
										{l s='Not available' mod='preorder'}
									{elseif $orders.preorder_status == 4}
										<span class="text-danger">{l s='Preorder cancelled' mod='preorder'}</span>
									{else}
										--
									{/if}
								</td>
								<td class="text-sm-center order-actions">
									<a href="{$detailsOrder|escape:'html':'UTF-8'}{$orders.order_id|escape:'html':'UTF-8'}" data-link-action="view-order-details">
										{l s='Details' mod='preorder'}
									</a>
								</td>
							</tr>
						{/foreach}
					{else}
						<tr>
							<td colspan="7"><center>{l s='No order yet' mod='preorder'}</center></td>
						</tr>
					{/if}
				</tbody>
			</table>
		</div>
	</div>
{/block}
