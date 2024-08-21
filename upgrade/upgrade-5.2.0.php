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

function upgrade_module_5_2_0($module)
{
    $wkQueries = [
        'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'wk_preorder_completion_data` (
            `id_shop` int(11) unsigned NOT NULL,
            `product_id` int(11) unsigned NOT NULL DEFAULT 0,
            `attribute_id` int(11) unsigned NOT NULL DEFAULT 0,
            `customer_id` int(11) unsigned NOT NULL DEFAULT 0,
            `old_order_id` int(11) unsigned NOT NULL DEFAULT 0
        ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8',
        'DROP TABLE IF EXISTS
            `' . _DB_PREFIX_ . 'wk_preorder_product_map`,
            `' . _DB_PREFIX_ . 'wk_preorder_product_map_shop`',
    ];

    $wkDatabaseInstance = Db::getInstance();
    foreach ($wkQueries as $wkQuery) {
        $wkDatabaseInstance->execute(trim($wkQuery));
    }

    return $module->uninstallOverrides()
        && $module->installOverrides()
        && $module->registerHook('actionObjectCombinationDeleteAfter');
}
