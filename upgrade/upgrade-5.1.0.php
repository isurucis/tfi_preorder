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

function upgrade_module_5_1_0($module)
{
    $wkQueries = [
        'ALTER TABLE `' . _DB_PREFIX_ . "wk_preorder_product_customer`
        ADD `disallow_order` tinyint(1) unsigned NOT NULL DEFAULT '0'",
        'ALTER TABLE `' . _DB_PREFIX_ . 'wk_preorder_product_shop`
        ADD `recreation_date` datetime DEFAULT NULL',
        'ALTER TABLE `' . _DB_PREFIX_ . 'wk_preorder_product`
        ADD `recreation_date` datetime DEFAULT NULL',

        'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'wk_preorder_custom_price` (
            `id_wk_preorder_custom_price` int(11) unsigned NOT NULL AUTO_INCREMENT,
            `id_shop` int(11) unsigned NOT NULL,
            `product_id` int(11) unsigned NOT NULL DEFAULT 0,
            `attribute_id` int(11) unsigned NOT NULL DEFAULT 0,
            `customer_id` int(11) unsigned NOT NULL DEFAULT 0,
            `custom_price` decimal(20,6) unsigned NOT NULL DEFAULT 0,
            PRIMARY KEY (`id_wk_preorder_custom_price`)
        ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8',
        'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'wk_preorder_order_map` (
            `id_wk_preorder_product_customer` int(11) unsigned NOT NULL,
            `order_id` int(11) unsigned NOT NULL DEFAULT 0,
            PRIMARY KEY (`id_wk_preorder_product_customer`, `order_id`)
        ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8',
    ];
    $wkDatabaseInstance = Db::getInstance();
    foreach ($wkQueries as $wkQuery) {
        $wkDatabaseInstance->execute(trim($wkQuery));
    }
    // delete all the vouchers
    $objHelper = new PreorderHelper();
    $allVoucher = $objHelper->getAllCreatedVoucher();
    if ($allVoucher) {
        foreach ($allVoucher as $voucher) {
            $objCart = new CartRule($voucher['cart_rule_id']);
            $objCart->delete();
        }
    }

    $WK_PARTIAL_PAYMENT = json_decode(Configuration::get('WK_PARTIAL_PAYMENT'), true);
    $WK_FULL_PAYMENT = json_decode(Configuration::get('WK_FULL_PAYMENT'), true);
    $wkFullPaymentText = [];
    $wkPartialPaymentText = [];

    foreach (Language::getLanguages(false) as $lang) {
        $wkPartialPaymentText[$lang['id_lang']] = $WK_PARTIAL_PAYMENT[$lang['id_lang']];
        $wkFullPaymentText[$lang['id_lang']] = $WK_FULL_PAYMENT[$lang['id_lang']];
    }
    Configuration::updateValue('WK_FULL_PAYMENT', $wkFullPaymentText, true);
    Configuration::updateValue('WK_PARTIAL_PAYMENT', $wkPartialPaymentText, true);
    // delete all preorder specififc price
    $allSpecific = $objHelper->getAllSpecificPreorder();
    if (!empty($allSpecific)) {
        foreach ($allSpecific as $specific) {
            $obj = new SpecificPrice($specific['id_specific']);
            $obj->delete();
        }
    }

    $preorderShipping = $objHelper->getPreorderShipping();
    Configuration::updateValue('WK_PREORDER_SHIPPING', (int) $preorderShipping['id_shipping']);
    Db::getInstance()->execute('
        DROP TABLE IF EXISTS
        `' . _DB_PREFIX_ . 'wk_preorder_free_shipping`,
        `' . _DB_PREFIX_ . 'wk_preorder_specific_price`,
        `' . _DB_PREFIX_ . 'wk_preorder_specific_price_shop`,
        `' . _DB_PREFIX_ . 'wk_preorder_cartrule_map`,
        `' . _DB_PREFIX_ . 'wk_preorder_cartrule_map_shop`
    ');

    return $module->uninstallOverrides()
        && $module->installOverrides()
        && $module->uninstallTab()
        && $module->callInstallTab()
        && Configuration::updateValue('price_type', '0')
        && Configuration::updateValue('WK_RESTRICT_CHECKOUT', '0')
        && Configuration::deleteByName('preorder_voucher_expiry')
        && $module->registerHook([
            'actionOrderHistoryAddAfter', 'displayOverrideTemplate', 'actionOrderStatusPostUpdate',
        ])
    ;
}
