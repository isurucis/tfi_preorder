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


<input type="hidden" value="{$id_order|intval}" name="preorder_id_order" id="preorder_id_order">
{if $preorder_order}
	{foreach $preorder_order as $key => $details}
		{if $details['is_preorder'] == 1}
			<input type="hidden" value="{$details['product_id']|intval}" name="pre_id_product" id="pre_id_product_{$key|intval}" pre-quantity="{$details['product_quantity']|intval}" pre-id-attr="{$details['product_attribute_id']|intval}">
		{else if $details['is_preorder'] == 0}
			<input type="hidden" value="0" name="pre_id_product" id="pre_id_product_{$key|intval}" pre-quantity="{$details['product_quantity']|intval}" pre-id-attr="{$details['product_attribute_id']|intval}">
		{else if $details['is_preorder'] == 2}
			<input type="hidden" value="complete" name="pre_id_product" id="pre_id_product_{$key|intval}" pre-quantity="{$details['product_quantity']|intval}" pre-id-attr="{$details['product_attribute_id']|intval}">
		{/if}
	{/foreach}
{/if}
