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


<div class="row preorder-preview-content mt-2" style="padding: 10px;">
    <div class="col-md-3">
        <img class="img-responsive img-thumbnail" src="{$productImage|escape:'html':'UTF-8'}" alt="" style="width: 130px;">
    </div>
    <div class="col-md-4">
        <h4 class="product-name">{$productName|escape:'html':'UTF-8'}</h4>
        <div style="margin-left:9px;">
            {if $preorder_product_list['productRef'] != '-'}
            <div class="form-group" style="margin-bottom: 0px;">
                <label>{l s='Reference: ' mod='preorder'}</label> {$preorder_product_list['productRef']|escape:'html':'UTF-8'}
            </div>
            {/if}
            <div class="form-group" style="margin-bottom: 0px;">
                <label>{l s='Original price: ' mod='preorder'}</label> {$preorder_product_list['original_price']|escape:'html':'UTF-8'}
            </div>
            <div class="form-group quantity-block" style="margin-bottom: 0px;">
                <label for="wk_subs_quantity">{l s='Maximum quantity: ' mod='preorder'}</label> {$preorder_product_list['maxquantity']|escape:'html':'UTF-8'}
            </div>
        </div>
    </div>
    <div class="col-md-5">
        <div style="margin-top: 32px;">
            <div class="form-group" style="margin-bottom: 0px;">
                <label>{l s='Preorder price: ' mod='preorder'}</label> {$preorder_product_list['preorder_price']|escape:'html':'UTF-8'}
            </div>
            <div class="form-group quantity-block" style="margin-bottom: 0px;">
                <label for="wk_subs_quantity">{l s='Available quantity: ' mod='preorder'}</label> {$preorder_product_list['aval_quantity']|escape:'html':'UTF-8'}
            </div>
            <div class="form-group" style="margin-bottom: 0px;">
                <label>{l s='Payment type: ' mod='preorder'}</label> {$preorder_product_list['payment_type']|escape:'html':'UTF-8'}
            </div>
            {if $preorder_product_list['payment_method']}
            <div class="form-group" style="margin-bottom: 0px;">
                <label>{l s='Payment method: ' mod='preorder'}</label> {$preorder_product_list['payment_method']|escape:'html':'UTF-8'}
            </div>
            {/if}
            <div class="form-group" style="margin-bottom: 0px;">
                <label>{l s='Available date: ' mod='preorder'}</label> {$preorder_product_list['expected_date']|escape:'html':'UTF-8'}
            </div>
        </div>
    </div>
</div>