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

class PreorderSpecificProcessModuleFrontController extends ModuleFrontController
{
    public function initContent()
    {
        parent::initContent();
        // For dynamic price add on product details page
        if ($this->context->customer->id && Tools::getValue('addpreorder')) {
            $qty = (int) Tools::getValue('qty');
            $idProduct = (int) Tools::getValue('id_product');
            $ipa = (int) Tools::getValue('ipa');
            $context = Context::getContext();
            if ($context->cart->id == null) {
                $idCustomer = $context->cookie->id_customer;
                $this->context->cart->add();
                if ($this->context->cart->id) {
                    $this->context->cookie->id_cart = (int) $this->context->cart->id;
                }
                $idCart = $context->cart->id;
                if ($idCart) {
                    $cart = new Cart((int) $idCart);
                } else {
                    // Initialize a new cart object
                    $cart = new Cart();
                    $cart->id_customer = (int) $idCustomer;
                    $cart->id_currency = $context->currency->id;
                    $cart->id_lang = $context->language->id;
                    $cart->id_shop = $context->shop->id;
                    $cart->id_shop_group = $context->shop->id_shop_group;
                    $cart->add();
                }
                $context->cart = $cart;
                if (Validate::isLoadedObject($this->context->cart)) {
                    $this->context->cookie->id_cart = (int) $cart->id;
                }
            }
            $context->cart->updateQty(
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

        // Add custom price (dynamic price)
        $idProduct = Tools::getValue('id_product');
        $idCust = Tools::getValue('id_cust');
        if ($idProduct && $idCust) {
            $idAttr = Tools::getValue('id_attr');
            $customPrice = (float) Tools::getValue('custom_price');
            $objPreorder = new PreorderProduct();
            // check complete preorder
            if (Tools::getValue('custom_price_add')) {
                $objPreorderCustomer = new PreorderProductCustomer();
                $checkCookie = $objPreorderCustomer->checkEntryExistsWithoutOrder(
                    $idProduct,
                    $idAttr,
                    $this->context->customer->id,
                    $this->context->shop->id
                );
                if ($checkCookie) {
                    $preorderCustomerObj = new PreorderProductCustomer();
                    $existingCustomerPreorder = $preorderCustomerObj->getCustomerPreOrderByIdPIdCIdO(
                        (int) $idCust,
                        $idProduct,
                        $idAttr,
                        $checkCookie['old_order_id']
                    );
                    if ($existingCustomerPreorder) {
                        if (!$existingCustomerPreorder['preorder_complete']) {
                            $objPreorderCustomer->deteleTempCompletionEntry(
                                $idProduct,
                                $idAttr,
                                $this->context->customer->id,
                                $this->context->shop->id
                            );
                            exit;
                        }
                    }
                }
            }
            if (!Tools::getValue('custom_price_add')) {
                if (!Validate::isPrice($customPrice)) {
                    exit('2'); // Price is not valid
                }
            }
            $isPreorderExist = $objPreorder->getExistingActivePreOrderProduct($idProduct, $idAttr);
            if ($isPreorderExist && $isPreorderExist['payment_type'] == '3') {
                if ($isPreorderExist['payment_method'] == 1) {
                    $originalPrice = PreorderProduct::getPriceStatic(
                        (int) $idProduct,
                        false,
                        $idAttr,
                        6,
                        null,
                        false,
                        true,
                        1,
                        null,
                        null,
                        null,
                        null,
                        true,
                        true,
                        null,
                        true,
                        null,
                        0
                    );
                    // $originalPrice = Tools::ps_round(
                    //     $isPreorderExist['original_price'] + $isPreorderExist['impact_price'],
                    //     2
                    // );
                    $preorderPrice = Tools::ps_round(
                        ($originalPrice * (float) $isPreorderExist['preorder_price']) / 100,
                        2
                    );
                } else {
                    $currentCurrency = $this->context->currency->id;
                    $preorderCurrency = $isPreorderExist['id_default_currency'];
                    if ($currentCurrency != $preorderCurrency) {
                        $currency = new Currency($preorderCurrency);
                        $oldCurrency = new Currency($this->context->currency->id);
                        $isPreorderExist['preorder_price'] = Tools::convertPriceFull(
                            $isPreorderExist['preorder_price'],
                            $currency,
                            $oldCurrency
                        );
                    }
                    $preorderPrice = $isPreorderExist['preorder_price'];
                }

                $priceDisplay = Group::getPriceDisplayMethod(Group::getCurrent()->id);
                if (!$priceDisplay || $priceDisplay == 2) {
                    $priceTax = true;
                } elseif ($priceDisplay == 1) {
                    $priceTax = false;
                }

                if ($priceTax) {
                    $objProduct = new Product((int) $idProduct, false, $this->context->language->id);
                    $taxRate = $objProduct->getTaxesRate();
                    $preorderPrice += ((float) $preorderPrice * $taxRate) / 100;
                    $productPrice = PreorderProduct::getPriceStatic(
                        (int) $idProduct,
                        true,
                        $idAttr,
                        6,
                        null,
                        false,
                        true,
                        1,
                        null,
                        null,
                        null,
                        null,
                        true,
                        true,
                        null,
                        true,
                        null,
                        0
                    );
                } else {
                    $productPrice = PreorderProduct::getPriceStatic(
                        (int) $idProduct,
                        false,
                        $idAttr,
                        6,
                        null,
                        false,
                        true,
                        1,
                        null,
                        null,
                        null,
                        null,
                        true,
                        true,
                        null,
                        true,
                        null,
                        0
                    );
                }
                if (!Tools::getValue('custom_price_add')) {
                    if (Tools::ps_round($customPrice, 6) < Tools::ps_round($preorderPrice, 6)) {
                        exit('5'); // custom price is lower than minimum price.
                    } elseif ($customPrice >= $productPrice) {
                        exit('7'); // custom price must be lower than product price.
                    }
                    if ($priceTax) {
                        $objProduct = new Product((int) $idProduct, false, $this->context->language->id);
                        $taxRate = $objProduct->getTaxesRate();
                        $customPrice = ($customPrice / ($taxRate + 100)) * 100;
                    }
                } else {
                    $customPrice = $preorderPrice;
                    $objCustomPrice = new PreorderCustomPrice();
                    if ($objCustomPrice->checkCustomPriceExist(
                        $idProduct,
                        $idAttr,
                        $idCust,
                        $this->context->shop->id
                    )) {
                        exit;
                    }
                }
                $objCustomPrice = new PreorderCustomPrice();
                if ($id = $objCustomPrice->checkCustomPriceExist(
                    $idProduct,
                    $idAttr,
                    $idCust,
                    $this->context->shop->id
                )) {
                    $objCustomPrice = new PreorderCustomPrice($id);
                } else {
                    $objCustomPrice->product_id = (int) $idProduct;
                    $objCustomPrice->attribute_id = (int) $idAttr;
                    $objCustomPrice->customer_id = (int) $idCust;
                    $objCustomPrice->id_shop = $this->context->shop->id;
                }
                $objCustomPrice->custom_price = (float) Tools::ps_round($customPrice, 6);

                if ($objCustomPrice->save()) {
                    exit('1'); // success
                } else {
                    exit('3'); // An error occurred while updating the specific price.
                }
            } else {
                exit('4');    // preorder is not exist on this product.
            }
        } else {
            exit('0'); // preorder can not be proceed due to missing parameters
        }
    }
}
