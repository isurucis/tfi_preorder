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

function upgrade_module_5_0_0($module)
{
    $wkQueries = [
        'ALTER TABLE `' . _DB_PREFIX_ . 'wk_preorder_product`
        CHANGE `id` id_wk_preorder_product int(11) unsigned NOT NULL AUTO_INCREMENT',
        'ALTER TABLE `' . _DB_PREFIX_ . 'wk_preorder_product_customer`
        CHANGE `id` id_wk_preorder_product_customer int(11) unsigned NOT NULL AUTO_INCREMENT',
        'ALTER TABLE `' . _DB_PREFIX_ . 'wk_preorder_cartrule_map`
        CHANGE `id` id_wk_preorder_cartrule_map int(11) unsigned NOT NULL AUTO_INCREMENT,
        ADD COLUMN `id_wk_preorder` int(11) unsigned NOT NULL DEFAULT 0',
        'ALTER TABLE `' . _DB_PREFIX_ . 'wk_preorder_product_map`
        CHANGE `id` id_wk_preorder_product_map int(11) unsigned NOT NULL AUTO_INCREMENT',
        'ALTER TABLE `' . _DB_PREFIX_ . 'wk_preorder_free_shipping`
        CHANGE `id` id_wk_preorder_free_shipping int(11) unsigned NOT NULL AUTO_INCREMENT',
        'ALTER TABLE `' . _DB_PREFIX_ . 'wk_preorder_specific_price`
        CHANGE `id` id_wk_preorder_specific_price int(11) unsigned NOT NULL AUTO_INCREMENT',

        'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . "wk_preorder_product_shop` (
            `id_wk_preorder_product` int(11) unsigned NOT NULL AUTO_INCREMENT,
            `id_shop` int(11) unsigned NOT NULL,
            `product_id` int(11) unsigned NOT NULL DEFAULT 0,
            `attribute_id` int(11) unsigned NOT NULL DEFAULT 0,
            `original_price` decimal(20,6) unsigned NOT NULL DEFAULT 0,
            `impact_price` decimal(20,6) DEFAULT '0.000000',
            `payment_type` tinyint(1) unsigned NOT NULL DEFAULT '1',
            `payment_method` tinyint(1) unsigned NOT NULL DEFAULT '0',
            `preorder_price` decimal(20,6) unsigned NOT NULL DEFAULT 0,
            `expected_date` datetime DEFAULT NULL,
            `is_preorder` tinyint(1) unsigned NOT NULL DEFAULT '0',
            `is_auto_available` tinyint(1) unsigned NOT NULL DEFAULT '0',
            `quantity` int(11) unsigned NOT NULL DEFAULT 0,
            `maxquantity` int(11) unsigned NOT NULL DEFAULT 0,
            `prebooked_quantity` int(11) unsigned NOT NULL DEFAULT 0,
            `id_tax_rules_group` int(11) unsigned DEFAULT 0,
            `id_applied_shipping` text,
            `id_default_currency` int(11) unsigned DEFAULT 0,
            PRIMARY KEY (`id_wk_preorder_product`, `id_shop`)
        ) ENGINE=" . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8',
        'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'wk_preorder_product_customer_shop` (
            `id_wk_preorder_product_customer` int(11) unsigned NOT NULL AUTO_INCREMENT,
            `id_shop` int(11) unsigned NOT NULL,
            PRIMARY KEY (`id_wk_preorder_product_customer`, `id_shop`)
        ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8',
        'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'wk_preorder_product_map_shop` (
            `id_wk_preorder_product_map` int(11) unsigned NOT NULL AUTO_INCREMENT,
            `id_shop` int(11) unsigned NOT NULL,
            `product_id` int(11) unsigned NOT NULL DEFAULT 0,
            `attribute_id` int(11) unsigned NOT NULL DEFAULT 0,
            `customer_id` int(11) unsigned NOT NULL DEFAULT 0,
            `quantity` int(11) unsigned NOT NULL DEFAULT 0,
            `used_voucher` int(11) unsigned NOT NULL DEFAULT 0,
            PRIMARY KEY (`id_wk_preorder_product_map`, `id_shop`)
        ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8',
        'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . "wk_preorder_specific_price_shop` (
            `id_wk_preorder_specific_price` int(11) unsigned NOT NULL AUTO_INCREMENT,
            `id_shop` int(11) unsigned NOT NULL,
            `id_specific` int(11) unsigned NOT NULL,
            `is_deleted` tinyint(1) unsigned NOT NULL DEFAULT '0',
            PRIMARY KEY (`id_wk_preorder_specific_price`, `id_shop`)
        ) ENGINE=" . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8',
    ];
    $wkDatabaseInstance = Db::getInstance();
    $wkSuccess = true;
    foreach ($wkQueries as $wkQuery) {
        $wkSuccess &= $wkDatabaseInstance->execute(trim($wkQuery));
    }
    if ($wkSuccess) {
        return $module->registerHook('displayCustomerAccount')
            && $module->uninstallOverrides()
            && PreorderProduct::changeQuantity()
            && $module->callPreorderMenu()
            && $module->installOverrides()
            && PreorderHelper::addDataInTables()
        ;
    }

    return true;
}
