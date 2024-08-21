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

class PreorderCronModuleFrontController extends ModuleFrontController
{
    public function initContent()
    {
        $this->display_header = false;
        $this->display_footer = false;

        $objPreorder = Module::getInstanceByName('preorder');
        if (Tools::getValue('token') != $objPreorder->secure_key) {
            // Set error if token mismatched
            if ($errorLog = fopen(_PS_MODULE_DIR_ . '/preorder/error_log', 'a+')) {
                $now = new DateTime();
                $txt = '[' . $now->format('Y-m-d H:i:s') . '] : ';
                $txt .= 'Failed to call:  Token Invalid';
                fwrite($errorLog, $txt . "\n");
            }
            fclose($errorLog);
            exit($this->l('Something went wrong.'));
        }

        if (Module::isEnabled('preorder')) {
            PreorderProduct::autoUpdateAllPreorder();
            exit($this->l('Cron is executed successfully.'));
        }

        exit;
    }
}
