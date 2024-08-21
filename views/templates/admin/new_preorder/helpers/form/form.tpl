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

<style>
	.wksearchresult:hover{
		cursor: pointer;
	}
</style>
<div class="panel">
	<div class="panel-heading">
		{if isset($obj_preorder)}
			{l s='Update preorder' mod='preorder'}
		{else}
			{l s='Create preorder' mod='preorder'}
		{/if}
	</div>
    <form class="form-horizontal" id="{$table|escape:'htmlall':'UTF-8'}_form" class="defaultForm {$name_controller|escape:'htmlall':'UTF-8'} form-horizontal" action="{$current|escape:'htmlall':'UTF-8'}&{if !empty($submit_action)}{$submit_action|escape:'htmlall':'UTF-8'}{/if}&token={$token|escape:'htmlall':'UTF-8'}" method="post" enctype="multipart/form-data">
		{if empty($obj_preorder)}
		<div class="form-group">
			<label class="col-lg-3 control-label required">
				<span class="label-tooltip" data-toggle="tooltip" data-html="true">{l s='Search product' mod='preorder'}</span>
			</label>
			<input type="hidden" id="status_img_ps_dir" value="{$img_ps_dir|escape:'html':'UTF-8'}" />

			<div class="col-lg-9">
				<div class="col-lg-6">
					<input type="text" name="pre_product" id="pre_product" autocomplete="off">
					<input type="hidden" name="preorder_enable" id="pre_enable" value="0">
					<input type="hidden" id="suggestpageurl" value="{$link->getAdminLink('AdminNewPreorder')|escape:'html':'UTF-8'}" />
					<input type="hidden" name="pre_product_id" id="pre_product_id" value="">
					<input type="hidden" id="getproduct_name" name="getproduct_name" value="" class="account_input form-control" autocomplete="off"/>
					<div id="products_ul"></div>
					<div id="get_product_name_div"></div>
					<div class="help-block">{l s='To search product(s), type at least three characters, you can search active products as well as out of stock products too.' mod='preorder'}</div>
				</div>
			</div>
		</div>
		{else}
		<input type="hidden" name="id" value="{$obj_preorder->id|intval}">
		<input type="hidden" name="id_wk_preorder_product" value="{$obj_preorder->id|intval}">
		<div class="form-group">
			<label class="col-lg-3 control-label required">{l s='Product' mod='preorder'}</label>
			<div class="col-lg-4">
				<input type="hidden" name="pre_product_id" id="pre_product_id" value="{$obj_preorder->product_id|intval}">
				<div class="clearfix">
					<select name="pre-attr" id="getproduct_name" class="id_product_attribute">
						<option selected="selected" value="{$obj_preorder->attribute_id|intval}">{$prod_name|escape:'htmlall':'UTF-8'}</option>
						{if isset($attr_name) && $attr_name == 'Default'}

						{else}
							<option value="all">{l s='All combinations' mod='preorder'}</option>
						{/if}
					</select>
				</div>
			</div>
		</div>
		{/if}
		<div class="clearfix" id="preorder_enabled" {if empty($obj_preorder)} style="display:none;" {/if}>
			{if !isset($obj_preorder)}
			<div class="form-group">
				<label class="control-label col-lg-3">{l s='Combination' mod='preorder'}</label>
				<div class="col-lg-4">
					<select name="pre-attr" id="pre_product_comb" class="id_product_attribute"></select>
				</div>
			</div>
			{/if}
			<div class="form-group">
				<label class="col-lg-3 control-label required">
				<span title="" data-toggle="tooltip" class="label-tooltip">{l s='Payment type' mod='preorder'}</span></label>
				<div class="col-lg-4">
					<select id="payment-method" name="preorder_payment_type" class="form-control">
						<option {if isset($smarty.post.preorder_payment_type) && $smarty.post.preorder_payment_type == '1'}selected="selected"{else if isset($obj_preorder) && ($obj_preorder->payment_type == '1')}selected="selected"{/if} value="1">{l s='Full payment' mod='preorder'}</option>
						<option {if isset($smarty.post.preorder_payment_type) && $smarty.post.preorder_payment_type == '2'}selected="selected"{else if isset($obj_preorder) && ($obj_preorder->payment_type == '2' || $obj_preorder->payment_type == '3')}selected="selected"{/if} value="2">{l s='Partial payment' mod='preorder'}</option>
						<option {if isset($smarty.post.preorder_payment_type) && $smarty.post.preorder_payment_type == '3'}selected="selected"{else if isset($obj_preorder) && ($obj_preorder->payment_type == '3')}selected="selected"{/if} value="3">{l s='Dynamic payment' mod='preorder'}</option>
					</select>
				</div>
			</div>
			<div class="form-group">
				<label class="control-label col-lg-3 required">
					<span title="" data-toggle="tooltip" class="label-tooltip" data-original-title="{l s='Original price of the product excluding tax and shipping' mod='preorder'}">
						{l s='Product original price' mod='preorder'}
					</span>
				</label>
				<div class="col-lg-4">
					<div class="input-group">
						<span class="input-group-addon">{$currency->sign|escape:'htmlall':'UTF-8'}</span>
						<input id="original_price" type="text" value="{if isset($obj_preorder->original_price)}{Tools::ps_round($original_price_with_impact,2)|floatval}{/if}" name="preorder_originalprice" maxlength="27" size="11" readonly="readonly">
					</div>
				</div>
			</div>
			<div class="form-group" id="price_col">
				<label for="" class="control-label col-lg-3 required">
					<span title="" data-toggle="tooltip" class="label-tooltip" data-original-title="{l s='Preorder price excluding tax and shipping. This price will appear on product page.' mod='preorder'}">
						{l s='Preorder price' mod='preorder'}
					</span>
				</label>
				<div class="col-lg-4">
					<div class="row">
						<div class="col-lg-12" id="preorder_price_box">
							<div class="input-group">
								<span style="display:none;" id="currency_symbol" class="input-group-addon">{$currency->sign|escape:'htmlall':'UTF-8'}</span>
								<span id="percentage_symbol" class="input-group-addon">%</span>
								<input id="preorder_price" type="text" value="{if isset($smarty.post.preorder_price)}{$smarty.post.preorder_price|floatval}{else if isset($obj_preorder->preorder_price)}{$obj_preorder->preorder_price|floatval}{/if}" name="preorder_price" maxlength="27" size="11" readonly="readonly">
							</div>
						</div>
						<div class="col-lg-6" id="partial-method" style="display:none;">
							<select id="payment_type" name="preorder_partial_type" class="form-control">
								<option {if isset($smarty.post.preorder_partial_type) && $smarty.post.preorder_partial_type =='1'}selected="selected"{else if isset($obj_preorder) && ($obj_preorder->payment_method == '1')}selected="selected"{/if} value="1">{l s='Percentage' mod='preorder'}</option>
								<option {if isset($smarty.post.preorder_partial_type) && $smarty.post.preorder_partial_type == '2'}selected="selected"{else if isset($obj_preorder) && ($obj_preorder->payment_method == '2')}selected="selected"{/if} value="2">{l s='Amount' mod='preorder'}</option>
							</select>
						</div>
					</div>
					<p class="help-block">
                        <span {if isset($smarty.post.preorder_partial_type) && $smarty.post.preorder_partial_type =='1'}{else if isset($obj_preorder) && ($obj_preorder->payment_method == '1')}{else}style="display:none;"{/if} id="wk_precentage_preorder">
							{l s='Preorder price will be calculated according to the original price.' mod='preorder'}
						</span>
						<span  {if isset($smarty.post.preorder_partial_type) && $smarty.post.preorder_partial_type == '2'}selected="selected"{else if isset($obj_preorder) && ($obj_preorder->payment_method == '2')}{else}style="display:none;"{/if} id="wk_amount_preorder">
							{l s='Entered price will be the preorder price.' mod='preorder'}
						</span>
                    </p>
				</div>
			</div>
			<div class="form-group">
				<label class="control-label col-lg-3 required">
					<span title="" data-toggle="tooltip" class="label-tooltip">
						{l s='Show preorder timer' mod='preorder'}
					</span>
				</label>
				<div class="col-lg-9">
					<span class="switch prestashop-switch fixed-width-lg enable_auto-avail">
					<input type="radio" id="auto_on" value="1" name="auto_on"
					{if isset($obj_preorder) && $obj_preorder->is_auto_available=='1'}checked="checked"{else if isset($smarty.post.auto_on) && $smarty.post.auto_on == '1'}checked="checked"{/if}>
						<label for="auto_on">{l s='Yes' mod='preorder'}</label>
					{if isset($obj_preorder)}
					<input type="radio" id="auto_off" value="0" name="auto_on"
					{if $obj_preorder->is_auto_available == '0'}checked="checked"{else if isset($smarty.post.auto_on) && $smarty.post.auto_on == '0'}checked="checked"{/if}>
					{else}
					<input type="radio" id="auto_off" value="0" name="auto_on"
					{if isset($obj_preorder->is_auto_available) && $obj_preorder->is_auto_available == '0'}checked="checked"{else if isset($smarty.post.auto_on) && $smarty.post.auto_on == '0'}checked="checked"{else if !isset($smarty.post.auto_on)}checked="checked"{/if}>
					{/if}
						<label for="auto_off">{l s='No' mod='preorder'}</label>
						<a class="slide-button btn"></a>
					</span>
				</div>
			</div>
			<div class="form-group" id="set_quant">
				<label class="control-label col-lg-3 required">
					<span title="" data-toggle="tooltip" class="label-tooltip" data-original-title="{l s='This quantity will added in product when product becomes a normal product.' mod='preorder'}">
						{l s='Quantity for this product' mod='preorder'}
					</span>
				</label>
				<div class="col-lg-4">
					<input class="form-control" id="pre-quantity" type="number" name="quantity" value="{if isset($smarty.post.quantity)}{$smarty.post.quantity|intval}{else if isset($obj_preorder)}{$obj_preorder->quantity|intval}{/if}">
				</div>
			</div>
			<div class="form-group" id="set_quant">
				<label class="control-label col-lg-3 required">
					<span title="" data-toggle="tooltip" class="label-tooltip">
						{l s='Maximum quantity for preorder' mod='preorder'}
					</span>
				</label>
				<div class="col-lg-4">
					<input class="form-control" id="pre-maxquantity" type="number" name="maxquantity" value="{if isset($smarty.post.maxquantity)}{$smarty.post.maxquantity|intval}{else if isset($obj_preorder)}{$obj_preorder->maxquantity|intval}{/if}">
				</div>
			</div>
			<div class="form-group">
				<label class="control-label col-lg-3 required">
					<span title="" data-toggle="tooltip" class="label-tooltip">
						{l s='Expected date of product availability' mod='preorder'}
					</span>
				</label>
				<div class="col-lg-4">
					<input id="preorder_date" class="datetimepicker" type="text" name="expected_date" value="{if isset($obj_preorder)}{$obj_preorder->expected_date|escape:'htmlall':'UTF-8'}{/if}">
				</div>
			</div>
			{if !empty($obj_preorder)}
			{* complete preorder before expected date *}
				{if isset($expectedTimeStamp) && isset($currentTimeStamp) && ($expectedTimeStamp > $currentTimeStamp)}
					<div class="form-group">
						<label class="control-label col-lg-3 required">
							<span title="" data-toggle="tooltip" class="label-tooltip" data-original-title="{l s='You can make preorder product available before expected date' mod='preorder'}">
								{l s='Make preorder available before expected date' mod='preorder'}
							</span>
						</label>
						<div class="col-lg-9">
							<span class="switch prestashop-switch fixed-width-lg complete_preorder">
								<input type="radio" id="complete_preorder_on" value="1" name="complete_preorder">
									<label for="complete_preorder_on">{l s='Yes' mod='preorder'}</label>
								<input type="radio" id="complete_preorder_off" value="0" name="complete_preorder" checked="checked">
									<label for="complete_preorder_off">{l s='No' mod='preorder'}</label>
									<a class="slide-button btn"></a>
							</span>
						</div>
					</div>
				{/if}
			{* end *}
			<div class="form-group" id="wk_enable_preorder">
				<label for="simple_product" class="control-label col-lg-3">
				<span title="" data-toggle="tooltip" class="label-tooltip">{l s='Enable preorder' mod='preorder'}</span></label>
				<div class="col-lg-9">
					<span class="switch prestashop-switch fixed-width-lg enable_preorder">
						<input type="radio" id="preorder_on" value="1" name="preorder_enable"
						{if isset($obj_preorder->is_preorder) && $obj_preorder->is_preorder == '1'}checked="checked"{else if isset($smarty.post.preorder_enable) && $smarty.post.preorder_enable == '1'}checked="checked"{/if}>
							<label for="preorder_on">{l s='Yes' mod='preorder'}</label>
						{if isset($obj_preorder->is_preorder)}
						<input type="radio" id="preorder_off" value="0" name="preorder_enable"
						{if isset($smarty.post.preorder_enable) && $smarty.post.preorder_enable == '0'}checked="checked"}{else if $obj_preorder->is_preorder == '0' && !isset($smarty.post.preorder_enable)}checked="checked"{/if}>
						{else}
						<input type="radio" id="preorder_off" value="0" name="preorder_enable"
						{if isset($obj_preorder->is_preorder) && $obj_preorder->is_preorder == '0'}checked="checked"{else if isset($smarty.post.preorder_enable) && $smarty.post.preorder_enable == '0'}checked="checked"{else if !isset($smarty.post.preorder_enable)}checked="checked"{/if}>
						{/if}
						<label for="preorder_off">{l s='No' mod='preorder'}</label>
						<a class="slide-button btn"></a>
					</span>
				</div>
			</div>

			{/if}
			<div class="panel-footer">
				<a href="{$link->getAdminLink('AdminNewPreorder')|escape:'html':'UTF-8'}" class="btn btn-default"><i class="process-icon-cancel"></i>{l s='Cancel' mod='preorder'}
				</a>
				<button type="submit" name="submitAdd{$table|escape:'html':'UTF-8'}" class="btn btn-default pull-right"><i class="process-icon-save"></i>{l s='Save' mod='preorder'}
				</button>
				{if !empty($obj_preorder)}
					<button type="submit" name="submitAdd{$table|escape:'html':'UTF-8'}AndStay" class="btn btn-default pull-right"><i class="process-icon-save"></i>{l s='Save and stay' mod='preorder'}
					</button>
				{/if}
			</div>
		</div>
		</div>
	</form>
</div>
{strip}
{addJsDefL name=all_combs_text}{l s='Product all combinations' js=1 mod='preorder'}{/addJsDefL}
{addJsDefL name=no_combs_text}{l s='No combination available' js=1 mod='preorder'}{/addJsDefL}
{addJsDefL name=no_image_text}{l s='No image available' js=1 mod='preorder'}{/addJsDefL}
{addJsDefL name=productid}{l s='Please search a product' js=1 mod='preorder'}{/addJsDefL}
{addJsDefL name=prieceempty}{l s='Preorder price can not be empty.' js=1 mod='preorder'}{/addJsDefL}
{addJsDefL name=priceoriginal}{l s='Preorder price must be equal to original price.' js=1 mod='preorder'}{/addJsDefL}
{addJsDefL name=percentageup}{l s='Preorder price percentage must be less than 100.' js=1 mod='preorder'}{/addJsDefL}
{addJsDefL name=zeroPriceProduct}{l s='Product with price 0 can not be added as preorder product.' js=1 mod='preorder'}{/addJsDefL}
{addJsDefL name=percentagedown}{l s='Preorder price must be a positive numeric value and greater than zero.' js=1 mod='preorder'}{/addJsDefL}
{addJsDefL name=priceoriginalgreater}{l s='Preorder price must be less than original price.' js=1 mod='preorder'}{/addJsDefL}
{addJsDefL name=quantityempty}{l s='Please set quantity for preorder product.' js=1 mod='preorder'}{/addJsDefL}
{addJsDefL name=maxquantityempty}{l s='Please set maximum quantity for preorder.' js=1 mod='preorder'}{/addJsDefL}
{addJsDefL name=quantitylow}{l s='Quantity must be a positive numeric value and greater than Zero.' js=1 mod='preorder'}{/addJsDefL}
{addJsDefL name=maxquantitylow}{l s='Max quantity must be a positive numeric value and greater than Zero.' js=1 mod='preorder'}{/addJsDefL}
{addJsDefL name=quantityerror}{l s='Invalid input for quantity.' js=1 mod='preorder'}{/addJsDefL}
{addJsDefL name=dateempty}{l s='Expected date can not be empty.' js=1 mod='preorder'}{/addJsDefL}
{addJsDefL name=dateerror}{l s='Expected date must be greater than current date and time.' js=1 mod='preorder'}{/addJsDefL}
{addJsDefL name=minprice}{l s='Set minimum preorder price for dynamic preorder.' js=1 mod='preorder'}{/addJsDefL}
{addJsDefL name=max_pre_quantityerror}{l s='Maximum quantity for preorder must be less than quantity for this product.' js=1 mod='preorder'}{/addJsDefL}
{addJsDefL name=error_title}{l s='Error!' js=1 mod='preorder'}{/addJsDefL}
{/strip}
