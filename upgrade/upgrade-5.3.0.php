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

function upgrade_module_5_3_0($module)
{
    $wkQueries = [
        'ALTER TABLE `' . _DB_PREFIX_ . 'wk_preorder_product_customer` ADD `limited_time` TINYINT(1) UNSIGNED NOT NULL DEFAULT "0" AFTER `state`;',
        'ALTER TABLE `' . _DB_PREFIX_ . 'wk_preorder_product_customer` ADD `allowed_days` int(11) UNSIGNED NOT NULL DEFAULT "0" AFTER `limited_time`;',
        'ALTER TABLE `' . _DB_PREFIX_ . 'wk_preorder_product_customer` ADD `stock_rollback` TINYINT(1) UNSIGNED NOT NULL DEFAULT "0" AFTER `allowed_days`;',
    ];

    $wkDatabaseInstance = Db::getInstance();
    $wkSuccess = true;
    foreach ($wkQueries as $wkQuery) {
        $wkSuccess &= $wkDatabaseInstance->execute(trim($wkQuery));
    }

    $groups = Group::getGroups((int) Context::getContext()->language->id);
    $groupBox = [];
    foreach ($groups as $group) {
        $groupBox[] = $group['id_group'];
    }

    if ($wkSuccess
        && Configuration::updateValue('WK_LIMITED_TIME', 0)
        && Configuration::updateValue('WK_PREORDER_GROUP', json_encode($groupBox))
        && Configuration::updateValue('WK_PREORDER_COUNTRY', json_encode(Configuration::get('PS_COUNTRY_DEFAULT'))
        && Configuration::updateValue('WK_ALLOW_GEOLOCATION', 0)
        && $module->uninstallOverrides()
        && $module->installOverrides()
        && $module->registerHook('displayAdminOrderMain'))) {
        return true;
    }

    return false;
}
