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

class PreorderInstall
{
    /**
     * create tables in database
     *
     * @return bool
     */
    public function createTables()
    {
        if ($sql = $this->getModuleSql()) {
            foreach ($sql as $query) {
                if ($query) {
                    if (!Db::getInstance()->execute(trim($query))) {
                        return false;
                    }
                }
            }
        }

        return true;
    }

    /**
     * This function is for writting the query
     *
     * @return array
     */
    public function getModuleSql()
    {
        return [
            'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . "wk_preorder_product` (
                `id_wk_preorder_product` int(11) unsigned NOT NULL AUTO_INCREMENT,
                `id_shop_default` int(11) unsigned NOT NULL DEFAULT 0,
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
                `recreation_date` datetime DEFAULT NULL,
                `date_add` datetime NOT NULL,
                `date_upd` datetime NOT NULL,
                PRIMARY KEY (`id_wk_preorder_product`)
            ) ENGINE=" . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8',

            // shop table of preorder product
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
                `recreation_date` datetime DEFAULT NULL,
                PRIMARY KEY (`id_wk_preorder_product`, `id_shop`)
            ) ENGINE=" . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8',
            // end

            'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . "wk_preorder_product_customer` (
                `id_wk_preorder_product_customer` int(11) unsigned NOT NULL AUTO_INCREMENT,
                `id_shop_default` int(11) unsigned NOT NULL DEFAULT 0,
                `product_id` int(11) unsigned NOT NULL DEFAULT 0,
                `attribute_id` int(11) unsigned NOT NULL DEFAULT 0,
                `customer_id` int(11) unsigned NOT NULL DEFAULT 0,
                `order_id` int(11) unsigned NOT NULL DEFAULT 0,
                `quantity` int(11) unsigned NOT NULL DEFAULT 0,
                `payment_type` tinyint(1) unsigned NOT NULL DEFAULT '1',
                `paid_amt` decimal(20,6) unsigned NOT NULL DEFAULT 0,
                `remaining_amt` decimal(20,6) unsigned NOT NULL DEFAULT 0,
                `original_price` decimal(20,6) unsigned NOT NULL DEFAULT 0,
                `tax_amt` decimal(20,6) unsigned NOT NULL DEFAULT 0,
                `shipping_amt` decimal(20,6) unsigned NOT NULL DEFAULT 0,
                `booked_date` datetime DEFAULT NULL,
                `preorder_complete` tinyint(1) unsigned NOT NULL DEFAULT '0',
                `disallow_order` tinyint(1) unsigned NOT NULL DEFAULT '0',
                `complete_qty` int(11) unsigned NOT NULL DEFAULT 0,
                `country` int(11) unsigned NOT NULL DEFAULT 0,
                `state` int(11) unsigned NOT NULL DEFAULT 0,
                `limited_time` tinyint(1) unsigned NOT NULL DEFAULT '0',
                `allowed_days` int(11) unsigned NOT NULL DEFAULT 0,
                `stock_rollback` tinyint(1) unsigned NOT NULL DEFAULT '0',
                `date_add` datetime DEFAULT NULL,
                `date_upd` datetime DEFAULT NULL,
                PRIMARY KEY (`id_wk_preorder_product_customer`)
            ) ENGINE=" . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8',

            'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'wk_preorder_product_customer_shop` (
                `id_wk_preorder_product_customer` int(11) unsigned NOT NULL AUTO_INCREMENT,
                `id_shop` int(11) unsigned NOT NULL,
                PRIMARY KEY (`id_wk_preorder_product_customer`, `id_shop`)
            ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8',

            'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'wk_preorder_order_map` (
                `id_wk_preorder_product_customer` int(11) unsigned NOT NULL,
                `order_id` int(11) unsigned NOT NULL DEFAULT 0,
                PRIMARY KEY (`id_wk_preorder_product_customer`, `order_id`)
            ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8',

            'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'wk_preorder_custom_price` (
                `id_wk_preorder_custom_price` int(11) unsigned NOT NULL AUTO_INCREMENT,
                `id_shop` int(11) unsigned NOT NULL,
                `product_id` int(11) unsigned NOT NULL DEFAULT 0,
                `attribute_id` int(11) unsigned NOT NULL DEFAULT 0,
                `customer_id` int(11) unsigned NOT NULL DEFAULT 0,
                `custom_price` decimal(20,6) unsigned NOT NULL DEFAULT 0,
                PRIMARY KEY (`id_wk_preorder_custom_price`)
            ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8',

            'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'wk_preorder_completion_data` (
                `id_shop` int(11) unsigned NOT NULL,
                `product_id` int(11) unsigned NOT NULL DEFAULT 0,
                `attribute_id` int(11) unsigned NOT NULL DEFAULT 0,
                `customer_id` int(11) unsigned NOT NULL DEFAULT 0,
                `old_order_id` int(11) unsigned NOT NULL DEFAULT 0
            ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8',
        ];
    }

    /**
     * Delete module tables
     *
     * @return bool
     */
    public function deletePreorderTable()
    {
        return Db::getInstance()->execute('
            DROP TABLE IF EXISTS
            `' . _DB_PREFIX_ . 'wk_preorder_order_map`,
            `' . _DB_PREFIX_ . 'wk_preorder_custom_price`,
            `' . _DB_PREFIX_ . 'wk_preorder_product_customer`,
            `' . _DB_PREFIX_ . 'wk_preorder_product_customer_shop`,
            `' . _DB_PREFIX_ . 'wk_preorder_completion_data`,
            `' . _DB_PREFIX_ . 'wk_preorder_product`,
            `' . _DB_PREFIX_ . 'wk_preorder_product_shop`
        ');
    }
}
