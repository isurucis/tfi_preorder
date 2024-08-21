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

class PreorderProcessModuleFrontController extends ModuleFrontController
{
    public function initContent()
    {
        parent::initContent();
        $qty = (int) Tools::getValue('qty');
        $idProduct = (int) Tools::getValue('id_product');
        $ipa = (int) Tools::getValue('ipa');
        if ($this->context->customer->id) {
            // Add cart if no cart found
            if (!$this->context->cart->id) {
                if (Context::getContext()->cookie->id_guest) {
                    $guest = new Guest(Context::getContext()->cookie->id_guest);
                    $this->context->cart->mobile_theme = $guest->mobile_theme;
                }
                $this->context->cart->add();
                if ($this->context->cart->id) {
                    $this->context->cookie->id_cart = (int) $this->context->cart->id;
                }
            }
            $checkCookie = [];
            $objPreorderCustomer = new PreorderProductCustomer();
            $checkCookie[$idProduct . '_' . $ipa] = (int) Tools::getValue('id_order');
            $objPreorderCustomer->addEntryInCompletePreorderTable(
                $idProduct,
                $ipa,
                (int) Tools::getValue('id_order'),
                $this->context->customer->id,
                $this->context->shop->id
            );
            if ($this->context->cart->containsProduct($idProduct, $ipa)) {
                $this->context->cart->deleteProduct($idProduct, $ipa);
            }
            $this->context->cart->updateQty(
                $qty,
                $idProduct,
                $ipa,
                null,
                'up',
                0
            );
            Tools::clearSmartyCache();
            Tools::redirect($this->context->link->getPageLink('cart') . '?action=show');
        }
    }
}
