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
    <section class="featured-products">
        {if empty($products)}
            <div class="alert alert-info">{l s='No product added as Preorder Product.' mod='preorder'}</div>
        {else}
            <div class="products" style="display: flex; flex-wrap: wrap;">
                {block name='preorder-product'}
                    {foreach from=$products item="product"}
                        {if isset($price_type) && $price_type}
                            {include file='module:preorder/views/templates/hook/global-listing.tpl' product=$product wkListingLayout='catalog/_partials/miniatures/product.tpl'}
                        {else}
                            {include file="modules/preorder/views/templates/front/_partials/product.tpl" product=$product  productClasses="col-xs-6 col-lg-4 col-xl-3"}
                        {/if}
                    {/foreach}
                {/block}
            </div>
        {/if}
    </section>
{/block}
