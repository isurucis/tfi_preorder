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

  {if $product.show_price}
    <div class="product-prices">
      {block name='product_discount'}
        {if $product.has_discount && !isset($wk_dynamic_payment)}
          <div class="product-discount">
            {hook h='displayProductPriceBlock' product=$product type="old_price"}
            <span class="regular-price">
            {if isset($preorderOriginalPriceWithoutReduction)}
              {$preorderOriginalPriceWithoutReduction|escape:'html':'UTF-8'}
            {else}
              {$product.regular_price|escape:'html':'UTF-8'}
            {/if}
            </span>
          </div>
        {/if}
      {/block}


{block name='product_price'}
<div
class="product-price h5 {if $product.has_discount && !isset($wk_dynamic_payment)}has-discount{/if}"
itemprop="offers"
itemscope
itemtype="https://schema.org/Offer"
>
<link itemprop="availability" href="https://schema.org/InStock"/>
<meta itemprop="priceCurrency" content="{$currency.iso_code|escape:'html':'UTF-8'}">
<meta itemprop="url" content="{$product.url|escape:'html':'UTF-8'}">
<div class="current-price">
  <span class="price" itemprop="price" content="{$product.price_amount|escape:'html':'UTF-8'}">
    {if isset($preorderOriginalPrice)}
      {$preorderOriginalPrice|escape:'html':'UTF-8'}
    {else}
      {$product.price|escape:'html':'UTF-8'}
    {/if}
    </span>
  {if $product.has_discount && !isset($wk_dynamic_payment)}
    {if $product.discount_type === 'percentage'}
      <span class="discount discount-percentage">{l s='-' d='Shop.Theme.Catalog'} {$product.discount_percentage_absolute|escape:'html':'UTF-8'}</span>
    {else}
      <span class="discount discount-amount">
          {l s='-' d='Shop.Theme.Catalog'} {$product.discount_to_display|escape:'html':'UTF-8'}
      </span>
    {/if}
  {/if}
    {if isset($preorderOriginalPrice)}
      {if isset($price_type) && $price_type == '1'}
      {else}
        <span class="wk_preorder_price" style="display: block; margin-top: 5px;">{l s='Preorder price: ' mod='preorder'}{$product.price|escape:'html':'UTF-8'}</span>
      {/if}
    {/if}

</div>

{block name='product_unit_price'}
  {if $displayUnitPrice}
    <p class="product-unit-price sub">{l s='(%unit_price%)' d='Shop.Theme.Catalog' sprintf=['%unit_price%' => $product.unit_price_full]}</p>
  {/if}
{/block}
</div>
{/block}

      {block name='product_without_taxes'}
        {if $priceDisplay == 2}
          <p class="product-without-taxes">{l s='%price% tax excl.' d='Shop.Theme.Catalog' sprintf=['%price%' => $product.price_tax_exc]}</p>
        {/if}
      {/block}

      {block name='product_pack_price'}
        {if $displayPackPrice}
          <p class="product-pack-price"><span>{l s='Instead of %price%' d='Shop.Theme.Catalog' sprintf=['%price%' => $noPackPrice]}</span></p>
        {/if}
      {/block}

      {block name='product_ecotax'}
        {if $product.ecotax.amount > 0}
          <p class="price-ecotax">{l s='Including %amount% for ecotax' d='Shop.Theme.Catalog' sprintf=['%amount%' => $product.ecotax.value]}
            {if $product.has_discount}
              {l s='(not impacted by the discount)' d='Shop.Theme.Catalog'}
            {/if}
          </p>
        {/if}
      {/block}

      {hook h='displayProductPriceBlock' product=$product type="weight" hook_origin='product_sheet'}

      <div class="tax-shipping-delivery-label">
        {if !$configuration.taxes_enabled}
          {*{l s='No tax' d='Shop.Theme.Catalog'}*}
        {elseif $configuration.display_taxes_label}
          {$product.labels.tax_long|escape:'html':'UTF-8'}
        {/if}
        {hook h='displayProductPriceBlock' product=$product type="price"}
        {hook h='displayProductPriceBlock' product=$product type="after_price"}
        {if $product.additional_delivery_times == 1}
          {if $product.delivery_information}
            <span class="delivery-information">{$product.delivery_information|escape:'html':'UTF-8'}</span>
          {/if}
        {elseif $product.additional_delivery_times == 2}
          {if $product.quantity > 0}
            <span class="delivery-information">{$product.delivery_in_stock|escape:'html':'UTF-8'}</span>
          {* Out of stock message should not be displayed if customer can't order the product. *}
          {elseif $product.quantity <= 0 && $product.add_to_cart_url}
            <span class="delivery-information">{$product.delivery_out_stock|escape:'html':'UTF-8'}</span>
          {/if}
        {/if}
      </div>
    </div>
  {/if}
