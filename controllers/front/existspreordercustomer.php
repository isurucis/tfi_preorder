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

class PreorderExistsPreorderCustomerModuleFrontController extends ModuleFrontController
{
    public function initContent()
    {
        parent::initContent();
        $idProduct = Tools::getValue('id_product');
        $idAttr = Tools::getValue('attr_id');
        $idCustomer = Tools::getValue('id_customer');
        $idOrder = Tools::getValue('id_order');
        $reload = Tools::getValue('reload');
        $reorder = Tools::getValue('reorder');
        $changetpl = Tools::getValue('changetpl');
        $checkmaxquantity = Tools::getValue('checkmaxquantity');
        $checkAddressChange = Tools::getValue('checkAddressChange');
        if (isset($this->context->cart->id)) {
            $idCart = $this->context->cart->id;
            $cart = new Cart($idCart);
        }

        $preorderObj = new PreOrderProduct();
        $objpreordercust = new PreorderProductCustomer();

        if ($idProduct && $idCustomer && !$reload) {
            $existPreorderCust = $objpreordercust->getCustomerPreOrderByIdPIdC($idCustomer, $idProduct, $idAttr);
            $existingPreorder = $preorderObj->getExistingPreOrderProduct($idProduct, $idAttr);
            if ($existingPreorder && $existPreorderCust['product_id'] != $idProduct) {
                exit(json_encode('2'));
            } elseif ($existingPreorder['is_preorder'] == '1' && $existPreorderCust['product_id'] == $idProduct) {
                $delete = DB::getInstance()->delete('cart_product', 'id_product = ' . (int) $idProduct);
                if ($delete) {
                    exit(json_encode('1'));
                }
            }
        } elseif ($reorder && $idOrder) {
            // checking preorder product quantity when user reorder any order

            if (!$this->context->customer->id) {
                exit(json_encode('2')); // customer must be login to buy preorder product
            }
            $objOrder = new Order($idOrder);
            $cart = new Cart($objOrder->id_cart);
            $products = $cart->getProducts();
            $reorder = false;
            foreach ($products as $prodDetails) {
                $idProduct = $prodDetails['id_product'];
                $idProductAttribute = $prodDetails['id_product_attribute'];
                $existingPreorderProduct = $preorderObj->getExistingPreOrderProduct($idProduct, $idProductAttribute);
                if ($existingPreorderProduct && $existingPreorderProduct['is_preorder'] == 1) {
                    $maxQuantity = $existingPreorderProduct['maxquantity'];
                    $prebookedQuantity = $existingPreorderProduct['prebooked_quantity'];
                    $remainingQty = $maxQuantity - $prebookedQuantity;
                    if ($existingPreorderProduct['payment_type'] == 3) {
                        $isSpecificExist = SpecificPrice::getSpecificPrice(
                            $idProduct,
                            0,
                            0,
                            0,
                            0,
                            1,
                            $idProductAttribute,
                            $this->context->customer->id,
                            0,
                            0
                        );
                        if (!$isSpecificExist) {
                            // You can not buy preorder product by paying full amount of the product.
                            exit(json_encode(3));
                        }
                    }

                    if ($prodDetails['quantity'] <= $remainingQty) {  // during the reorder quantity is enough
                        $reorder = true;
                    } else {      // during the reorder quantity is not enough
                        $reorder = false;
                        exit(json_encode(0));
                    }
                } else {
                    $reorder = true;
                    // die(json_encode(1));
                }
            }
            if ($reorder) {
                exit(json_encode(1));
            } else {
                exit(json_encode(0));
            }
        } elseif ($idProduct && $changetpl && !$reload) {
            $existingPreorder = $preorderObj->getExistingPreOrderProduct($idProduct, $idAttr);
            if (!empty($existingPreorder) && $existingPreorder['is_preorder'] == '1') {
                $objProduct = new Product((int) $idProduct, false, $this->context->language->id);
                $originalPrice = $existingPreorder['original_price'] + $existingPreorder['impact_price'];
                $preorderPaymentType = $existingPreorder['payment_type'];
                if ($preorderPaymentType == '1') {
                    $preorderProductPrice = $existingPreorder['original_price'];
                } elseif ($preorderPaymentType == '2' || $preorderPaymentType == '3') {
                    if ($existingPreorder['payment_method'] == 1) {
                        $preorderProductPrice = ($originalPrice * $existingPreorder['preorder_price']) / 100;
                    } elseif ($existingPreorder['payment_method'] == 2) {
                        $preorderProductPrice = $existingPreorder['preorder_price'];
                    }
                }
                $remainingQty = $existingPreorder['maxquantity'] - $existingPreorder['prebooked_quantity'];
                $preorderProductPrice = $preorderProductPrice;
                $preorderProductPrice = Tools::ps_round($preorderProductPrice, 2);
                $originalPriceWithTax = Tools::ps_round($originalPrice, 2);
                $availableTimeStamp = strtotime($existingPreorder['expected_date']);
                $currentTimeStamp = strtotime(date('Y-m-d H:i:s'));
                if ($availableTimeStamp >= $currentTimeStamp) {
                    $timeLeftTimeStamp = $availableTimeStamp - $currentTimeStamp;
                } else {
                    $timeLeftTimeStamp = '0';
                }
                $priceDisplay = Group::getPriceDisplayMethod(Group::getCurrent()->id);
                if (!$priceDisplay || $priceDisplay == 2) {
                    $priceTax = true;
                } elseif ($priceDisplay == 1) {
                    $priceTax = false;
                }
                if ($priceTax) {
                    // $preorderProductPrice = $objProduct->getPrice(true, $idAttr);
                    $taxRate = $objProduct->getTaxesRate();
                    $preorderProductPrice += ((float) $preorderProductPrice * $taxRate) / 100;
                    $originalPriceWithTax = (float) $originalPriceWithTax + ((float) $originalPriceWithTax * $taxRate) / 100;
                }

                $currency = new Currency((int) $this->context->cookie->id_currency);
                $defaultCurrency = new Currency((int) Configuration::get('PS_CURRENCY_DEFAULT'));
                $priceProduct = Product::getPriceStatic((int) $idProduct, $priceTax, (int) $idAttr);
                $prebookPriceWithTax = Tools::ps_round(
                    Tools::convertPriceFull($preorderProductPrice, $defaultCurrency, $currency),
                    2
                );
                $originalPriceWithTax = Tools::ps_round(
                    Tools::convertPriceFull($originalPriceWithTax, $defaultCurrency, $currency),
                    2
                );
                $partialPaymentContent = '';
                if ($existingPreorder['payment_type'] != 1) {
                    $idLang = $this->context->language->id;
                    $partialPayment = [];
                    foreach (Language::getLanguages(true) as $lang) {
                        $partialPayment[$lang['id_lang']] = Configuration::get(
                            'WK_PARTIAL_PAYMENT',
                            $lang['id_lang']
                        );
                    }

                    if (array_key_exists($idLang, $partialPayment) && $partialPayment[$idLang]) {
                        $from = [
                            '{preorderPrice}',
                            '{originalPrice}',
                        ];
                        if ($existingPreorder['payment_type'] == 2) {
                            $to = [
                                '<span style="color:#232323;">' . $priceProduct . $this->module->l(' (tax excl)', 'existspreordercustomer') . '</span>',
                                '<span style="color:#232323;">' . PreorderHelper::calculatePreorderOriginalPrice(
                                    $idProduct,
                                    $existingPreorder
                                ) . '</span>',
                            ];
                            $partialPaymentContent = str_replace($from, $to, $partialPayment[$idLang]);
                        } elseif ($existingPreorder['payment_type'] == 3) {
                            $to = [
                                '<span style="color:#232323;">' . PreorderHelper::displayPrice(
                                    Tools::ps_round(Product::getPriceStatic((int) $idProduct, false, $idAttr), 2),
                                    $currency
                                ) . $this->module->l(' (tax excl)', 'existspreordercustomer') . '</span>',
                                '<span style="color:#232323;">' . PreorderHelper::calculatePreorderOriginalPrice(
                                    $idProduct,
                                    $existingPreorder
                                ) . '</span>',
                            ];
                            $partialPaymentContent = str_replace($from, $to, $partialPayment[$idLang]);
                        }
                    }
                }

                $this->context->smarty->assign([
                    'var' => 1,
                    'attr_id' => $idAttr,
                    'ps_module_dir' => _MODULE_DIR_,
                    'id_product' => $idProduct,
                    'remaining_qty' => $remainingQty,
                    'preorder_product' => $existingPreorder,
                    'id_customer' => $this->context->customer->id,
                    'time_left_time_stamp' => $timeLeftTimeStamp,
                    'expected_date' => $existingPreorder['expected_date'],
                    'original_price_with_tax' => PreorderHelper::displayPrice($originalPriceWithTax, $currency),
                    'prebook_price_with_tax' => PreorderHelper::displayPrice($prebookPriceWithTax, $currency),
                    'prebook_price' => PreorderHelper::displayPrice(Tools::ps_round($preorderProductPrice, 2), $currency),
                    'priceDisplay' => $priceTax,
                    'price_with_tax' => $priceProduct,
                    'fullpaymentContent' => Configuration::get('WK_FULL_PAYMENT', $this->context->language->id),
                    'partialPaymentContent' => $partialPaymentContent,
                ]);
                $this->setTemplate('module:preorder/views/templates/hook/preorderprice.tpl');
            } else {
                exit(false);
            }
        } elseif ($idProduct && $idCustomer && $reload) {
            // checking is product preorder or not if yes then page will reload in order to set voucher on product

            $reload = 1;
            $existingPreorder = $preorderObj->getExistingPreOrderProduct($idProduct, $idAttr);
            if (!empty($existingPreorder)
            && $existingPreorder['is_preorder'] == '0'
            && $existingPreorder['payment_type'] != 1) {
                $existPreorderCust = $objpreordercust->getCustomerPreOrderProductByIdProduct(
                    $idCustomer,
                    $idProduct,
                    $idAttr
                );
                if (!empty($existPreorderCust)) {
                    foreach ($existPreorderCust as $exist_order) {
                        if ($exist_order['preorder_complete'] == 1) {
                        } else {
                            $reload = 0;
                        }
                    }
                    echo $reload;
                    exit;
                }
            } else {
                echo '1';
                exit;
            }
        } elseif ($idCustomer && $checkmaxquantity && $idCart) {
            if (!$this->context->customer->id) {
                exit(json_encode('2'));        // customer must be login to buy preorder product
            }
            $products = $cart->getProducts();
            $reorder = false;
            foreach ($products as $prodDetails) {
                $idProduct = $prodDetails['id_product'];
                $idProductAttribute = $prodDetails['id_product_attribute'];
                $existingPreorderProduct = $preorderObj->getExistingPreOrderProduct($idProduct, $idProductAttribute);
                if ($existingPreorderProduct && $existingPreorderProduct['is_preorder'] == 1) {
                    $existingMaxQty = $existingPreorderProduct['maxquantity'];
                    $existingPrebookedQty = $existingPreorderProduct['prebooked_quantity'];
                    $remainingQty = $existingMaxQty - $existingPrebookedQty;
                    if ($prodDetails['quantity'] <= $remainingQty) {  // during the reorder quantity is enough
                        // die(json_encode(1));
                        $reorder = true;
                    } else {      // during the reorder quantity is not enough
                        $reorder = false;
                        $this->context->cart->deleteProduct(
                            $prodDetails['id_product'],
                            (int) $prodDetails['id_product_attribute'],
                            false
                        );
                        exit(json_encode(0));
                    }
                } else {
                    $allOrders = $objpreordercust->getCustomerPreOrderProductByIdProduct(
                        $idCustomer,
                        $idProduct,
                        $idProductAttribute
                    );
                    if (!empty($allOrders)) {
                        $address = true;
                        foreach ($allOrders as $order) {
                            $idOrder = $order['order_id'];
                            $order = new Order((int) $idOrder);
                            $preorderAddressDelivery = $order->id_address_delivery;
                            $currentAddressDelivery = $this->context->cart->id_address_delivery;
                            if ($preorderAddressDelivery != $currentAddressDelivery) {
                                $address = false;
                                break;
                            }
                        }
                        if ($address) {
                            exit(json_encode(3)); // address is ok for completing preorder
                        } else {
                            exit(json_encode(4)); // address is changed for completing preorder
                        }
                        $reorder = false;
                    } else {
                        $reorder = true;
                    }
                }
            }
            if ($reorder) {
                exit(json_encode(1));
            } else {
                exit(json_encode(0));
            }
        } elseif ($idCustomer && $checkAddressChange && $idCart) {
            if (!$this->context->customer->id) {
                exit(json_encode('2'));        // customer must be login to buy preorder product
            }
            $products = $cart->getProducts();
            foreach ($products as $prodDetails) {
                $idProduct = $prodDetails['id_product'];
                $idProductAttribute = $prodDetails['id_product_attribute'];
                $existingPreorderProduct = $objpreordercust->getCustomerPreOrderProductByIdProduct(
                    $idCustomer,
                    $idProduct,
                    $idProductAttribute
                );
                if (!empty($existingPreorderProduct)) {
                    exit(json_encode('0'));
                } else {
                    $noPreorder = false;
                }
            }
            if (!$noPreorder) {
                exit(json_encode('1'));
            }
        }
    }
}
