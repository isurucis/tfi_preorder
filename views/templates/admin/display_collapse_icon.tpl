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

{$preorder_product_list['id_wk_preorder_product']|escape:'html':'UTF-8'}
<span class="preview-toggle" style="cursor: pointer; visibility: hidden;" data-url="{$preorder_controller|escape:'html':'UTF-8'}" data-id="{$preorder_product_list['id_wk_preorder_product']|escape:'html':'UTF-8'}">
        <i class="text-primary material-icons js-expand ">keyboard_arrow_down</i>
        <i class="text-primary material-icons js-collapse " style="display: none;">keyboard_arrow_up</i>
</span>