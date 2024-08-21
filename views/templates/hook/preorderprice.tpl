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


{* {if isset($product) && $product}
    <script type="text/javascript">
        var id_product_attribute = {$product.id_product_attribute|intval};
    </script>
{/if} *}

<div id='preorderprice_timer'>
{if isset($var)}
    {if $var == 1}
        <div class="preordercontent">
        {if isset($preorder_product) && $preorder_product['is_auto_available'] == '1' && $time_left_time_stamp != '0'}
            <div class="expected-date"></div>
        {/if}
        {if Configuration::get('WK_SHOW_PRODUCT_AVAILABLE_ON')}
        <p class="date_available" >{l s='Available on:' mod='preorder'} {$expected_date|date_format:"%A, %e %B, %Y"|escape:'htmlall':'UTF-8'}</p>
        {/if}
            {if isset($preorder_product)}
                <div class="preorder_info" {if $preorder_product['payment_type'] != '3' && Configuration::get('WK_SHOW_PAYMENT_TYPE') && (empty(strip_tags($partialPaymentContent)) || empty(strip_tags($fullpaymentContent))) }style="margin-bottom: 0px;" {/if}>
                    <div id="wk_pre_price">{if $preorder_product['payment_type'] == '2' || $preorder_product['payment_type'] == '3'}
                            {if Configuration::get('WK_SHOW_PAYMENT_TYPE') && strip_tags($partialPaymentContent)}
                                <div style="margin-bottom: 0.625rem;">
                                    {$partialPaymentContent nofilter}
                                </div>
                            {/if}
                            {if $preorder_product['payment_type'] == '3'}
                                <div class="wkcustom" style="text-align: center;">
                                    <h5>{l s='Enter custom price' mod='preorder'} {$taxTxt|escape:'html':'UTF-8'}</h5>
                                    <div class="row">
                                        <div class="col-md-4 col-xs-6" style="margin-right:0.5rem;padding-right:0;">
                                            <input type="text"
                                            name="wk_custom_price"
                                            id="wk_custom_price"
                                            value=""
                                            class="form-control" />
                                        </div>
                                        <div class="col-md-7 col-xs-6" style="text-align:left; margin-left:0; margin-right:0;padding-left:0;">
                                            <button data-id-customer="{$id_customer|intval}"
                                            data-id-product="{$id_product|intval}"
                                            data-id-product-attr="{$attr_id|intval}"
                                            id="preordercustom"
                                            class="btn btn-primary">
                                                {l s='Add custom price' mod='preorder'}
                                            </button>
                                            <img id="{$id_product|intval}-{$attr_id|intval}-loader" class="customloader" src="{$ps_module_dir|escape:'htmlall':'UTF-8'}preorder/views/img/p_loading.gif" width="25">
                                        </div>
                                    </div>
                                </div>
                            {/if}
                        {else}
                            {if Configuration::get('WK_SHOW_PAYMENT_TYPE') && strip_tags($fullpaymentContent)}
                                {$fullpaymentContent nofilter}
                            {/if}
                        {/if}</div>
                </div>
            {/if}
        </div>
    {/if}
    <script type="text/javascript">
        var clockTime = "{$time_left_time_stamp|intval}";
        var id_product = "{$id_product|intval}";
        var remaining_qty = "{if isset($remaining_qty)}{$remaining_qty|intval}{else}1{/if}";
        var prodoutofstock = notAvailable;
        var outofstock = "{l s='Out-of-stock' mod='preorder'}";
        var not_allowed = "{$not_allowed|escape:'html':'UTF-8'}";
        var add_to_cart = "{l s='Add to cart' mod='preorder'}";
    </script>
    {if (isset($call_ajax) && ($call_ajax == 'quickview' || $call_ajax == 'refresh'))
        || (isset($not_allowed) && ($not_allowed == true))}
        <script type="text/javascript" src="{$ps_module_dir|escape:'html':'UTF-8'}preorder/views/js/preorder_timer.js"></script>
        <script type="text/javascript" src="{$ps_module_dir|escape:'html':'UTF-8'}preorder/views/js/preordercustomprice.js"></script>
    {/if}
{/if}
</div>