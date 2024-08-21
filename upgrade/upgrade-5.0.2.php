<?php
/**
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
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_5_0_2($module)
{
    $WK_FULL_PAYMENT = [];
    $WK_PARTIAL_PAYMENT = [];

    foreach (Language::getLanguages(false) as $language) {
        $WK_FULL_PAYMENT[$language['id_lang']] =
        $module->l('This is a pre-order product. Once it is available in stock, your order will be dispatched.');

        $WK_PARTIAL_PAYMENT[$language['id_lang']] =
        $module->l('Pay Preorder Price {preorderPrice} (tax included) instead of paying full {originalPrice}');
    }

    return $module->uninstallOverrides()
        && $module->installOverrides()
        && Configuration::updateValue('WK_SHOW_PRODUCT_AVAILABLE_ON', '1')
        && Configuration::updateValue('WK_SHOW_PAYMENT_TYPE', '1')
        && Configuration::updateValue('WK_FULL_PAYMENT', $WK_FULL_PAYMENT, true)
        && Configuration::updateValue('WK_PARTIAL_PAYMENT', $WK_PARTIAL_PAYMENT, true)
    ;
}
