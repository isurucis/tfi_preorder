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

{extends file=$wkListingLayout}
{capture assign="productClasses"}col-xs-6 col-lg-4 col-xl-3{/capture}
{block name='product_price_and_shipping'}
    {if $product.show_price}
        <div class="product-price-and-shipping">
            {assign var="wkpreOrder" value=PreorderHelper::checkPreorderproduct($product->id_product, $product->id_product_attribute)}
            {if $product.has_discount}
                {hook h='displayProductPriceBlock' product=$product type="old_price"}
                <span class="sr-only">{l s='Regular price' d='Shop.Theme.Catalog'}</span>
                <span class="regular-price">
                    {if isset($wkpreOrder.isPreOrderProduct) && $wkpreOrder.isPreOrderProduct}
                        {$wkpreOrder.preorderOriginalPriceWithoutReduction|escape:'html':'UTF-8'}
                    {else}
                        {$product.regular_price|escape:'html':'UTF-8'}
                    {/if}
                </span>
            {/if}

            {hook h='displayProductPriceBlock' product=$product type="before_price"}

            <span class="sr-only">{l s='Price' d='Shop.Theme.Catalog'}</span>
            <span class="price{if $product.has_discount} current-price-discount{/if}">
                {if isset($wkpreOrder.isPreOrderProduct) && $wkpreOrder.isPreOrderProduct == 1}
                    {if isset($price_type) && $price_type =='1'}
                        {$wkpreOrder.preorderOriginalPrice|escape:'html':'UTF-8'}
                    {else}
                        {$wkpreOrder.preorderOriginalPrice|escape:'html':'UTF-8'}
                        <span class="wk_preorder_price" style="font-size:14px;">{l s='Preorder price: ' mod='preorder'}{$product.price|escape:'html':'UTF-8'}</span>
                    {/if}
                {else}
                    {$product.price|escape:'html':'UTF-8'}
                {/if}
            </span>

            {hook h='displayProductPriceBlock' product=$product type='unit_price'}

            {hook h='displayProductPriceBlock' product=$product type='weight'}
        </div>
    {/if}
{/block}