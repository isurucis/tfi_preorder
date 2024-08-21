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

class PreorderHelper
{
    public function createNewOrderStatus()
    {
        $orderStatus = new OrderState();
        $orderStatus->invoice = 0;
        $orderStatus->send_email = 0;
        $orderStatus->module_name = 'preorder';
        $orderStatus->color = '#6ad4ff';
        $orderStatus->unremovable = 0;
        foreach (Language::getLanguages(false) as $lang) {
            $orderStatus->name[$lang['id_lang']] = 'Pre-Order Product';
            $orderStatus->template[$lang['id_lang']] = 'preorder_reserved';
        }
        if ($orderStatus->save()) {
            $source = _PS_MODULE_DIR_ . 'preorder/views/img/1.gif';
            $destination = _PS_ORDER_STATE_IMG_DIR_ . $orderStatus->id . '.gif';
            Tools::copy($source, $destination);
            Configuration::updateValue('PS_OS_PREORDER', $orderStatus->id);

            return true;
        }

        return false;
    }

    public static function calculatePreorderOriginalPrice(
        $idProduct,
        $existingPreorder,
        $idCustomerGroup = false,
        $deliveryAddress = false,
        $withoutReduction = 0,
        $withCurrency = 1,
        $taxincl = 0
    ) {
        $originalPrice = $existingPreorder['original_price'] + $existingPreorder['impact_price'];
        $originalPriceWithTax = Tools::ps_round($originalPrice, 2);
        if (!$idCustomerGroup) {
            $idCustomerGroup = Group::getCurrent()->id;
        }
        $priceDisplay = Group::getPriceDisplayMethod((int) $idCustomerGroup);
        if (!$priceDisplay || $priceDisplay == 2) {
            $priceTax = true;
        } elseif ($priceDisplay == 1) {
            $priceTax = false;
        }

        $objProduct = new Product((int) $idProduct, false, Context::getContext()->language->id);
        if ($priceTax || $taxincl) {
            if ($deliveryAddress) {
                $taxRate = $objProduct->getTaxesRate($deliveryAddress);
            } else {
                $taxRate = $objProduct->getTaxesRate();
            }
            $originalPriceWithTax = (float) $originalPriceWithTax + ((float) $originalPriceWithTax * $taxRate) / 100;
        }
        $currency = new Currency(Context::getContext()->currency->id);
        $defaultCurrency = new Currency((int) Configuration::get('PS_CURRENCY_DEFAULT'));
        $originalPriceWithTax = Tools::ps_round(
            Tools::convertPriceFull($originalPriceWithTax, $defaultCurrency, $currency),
            2
        );
        if ($withoutReduction) {
            return self::displayPrice($originalPriceWithTax, $currency);
        } else {
            // product has discount
            //  add specific price
            $context = Context::getContext();
            $wkCustomerId = (isset($context->customer) ? (int) $context->customer->id : 0);
            $wkCountryId = $wkCustomerId ? (int) Customer::getCurrentCountry($wkCustomerId) :
                (int) Configuration::get('PS_COUNTRY_DEFAULT');

            $specificPriceData = SpecificPrice::getSpecificPrice(
                $idProduct,
                Context::getContext()->shop->id,
                Context::getContext()->cookie->id_currency,
                $wkCountryId,
                (int) Group::getCurrent()->id,
                $objProduct->minimal_quantity,
                $existingPreorder['attribute_id'],
                $wkCustomerId,
                $context->cart->id
            );
            if ($specificPriceData
                && isset($specificPriceData['reduction'])
                && ($specificPriceData['reduction'] > 0)
            ) {
                $wkTaxRate = (float) $objProduct->getTaxesRate(new Address(
                    (int) $context->cart->{Configuration::get('PS_TAX_ADDRESS_TYPE')}
                ));

                if ($specificPriceData['reduction_type'] == 'amount') {
                    if (Product::$_taxCalculationMethod == PS_TAX_INC) {
                        $discountAmount = $specificPriceData['reduction_tax'] == 1 ?
                            $specificPriceData['reduction'] : $specificPriceData['reduction'] * (1 + $wkTaxRate / 100);
                    } else {
                        $discountAmount = $specificPriceData['reduction_tax'] == 0 ?
                            $specificPriceData['reduction'] : $specificPriceData['reduction'] / (1 + $wkTaxRate / 100);
                    }
                    $originalPriceWithTax = $originalPriceWithTax - $discountAmount;
                } else {
                    $red_price = $specificPriceData['reduction'] * $originalPriceWithTax;

                    $originalPriceWithTax = $originalPriceWithTax - $red_price;
                }
            }

            if ($withCurrency) {
                return PreorderHelper::displayPrice($originalPriceWithTax, $currency);
            } else {
                return Tools::ps_round($originalPriceWithTax, 6);
            }
        }
    }

    public static function calculateRemainingAmount($idProduct, $idProductAttribute, $existingPreorder, $quantity)
    {
        $quantity = (int) $quantity;
        $originalPrice = $existingPreorder['original_price'] + $existingPreorder['impact_price'];
        $originalPriceWithTax = Tools::ps_round($originalPrice, 2);
        $priceDisplay = Group::getPriceDisplayMethod(Group::getCurrent()->id);
        if (!$priceDisplay || $priceDisplay == 2) {
            $priceTax = true;
        } elseif ($priceDisplay == 1) {
            $priceTax = false;
        }
        if ($priceTax) {
            $objProduct = new Product((int) $idProduct, false, Context::getContext()->language->id);
            $taxRate = $objProduct->getTaxesRate();
            $originalPriceWithTax = (float) $originalPriceWithTax + ((float) $originalPriceWithTax * $taxRate) / 100;
        }
        $currency = new Currency(Context::getContext()->currency->id);
        $defaultCurrency = new Currency((int) Configuration::get('PS_CURRENCY_DEFAULT'));

        $originalPriceWithTax = Tools::ps_round(
            Tools::convertPriceFull($originalPriceWithTax, $defaultCurrency, $currency),
            2
        );

        $priceProduct = Product::getPriceStatic($idProduct, $priceTax, $idProductAttribute);

        $wkRemaningAmount = Tools::ps_round($originalPriceWithTax - $priceProduct, 2);
        if ($wkRemaningAmount > 0) {
            $wkRemaningAmount = $wkRemaningAmount * $quantity;

            return Tools::ps_round($wkRemaningAmount, 2);
        }

        return false;
    }

    /**
     * Called on product data global-list override tpl
     */
    public static function checkPreorderproduct($idProduct, $idProductAttribute)
    {
        $preorderObj = new PreOrderProduct();
        $existingPreorder = $preorderObj->getExistingPreOrderProduct($idProduct, $idProductAttribute);
        if ($existingPreorder) {
            if ($existingPreorder['is_preorder']
            && ($existingPreorder['payment_type'] == 2 || $existingPreorder['payment_type'] == 3)) {
                return [
                    'isPreOrderProduct' => 1,
                    'preorderOriginalPrice' => self::calculatePreorderOriginalPrice($idProduct, $existingPreorder),
                    'preorderOriginalPriceWithoutReduction' => self::calculatePreorderOriginalPrice(
                        $idProduct,
                        $existingPreorder,
                        false,
                        false,
                        1
                    ),
                    'price_type' => Configuration::get('price_type'),
                ];
            }
        }

        return [];
    }

    public static function deletePreorderShippingData()
    {
        $idPsCarriers = Configuration::get('WK_PREORDER_SHIPPING');
        Db::getInstance()->delete('range_price', 'id_carrier=' . (int) $idPsCarriers);
        Db::getInstance()->delete('range_weight', 'id_carrier=' . (int) $idPsCarriers);
        Db::getInstance()->delete('carrier_group', 'id_carrier=' . (int) $idPsCarriers);
        Db::getInstance()->delete('carrier_tax_rules_group_shop', 'id_carrier=' . (int) $idPsCarriers);
        Db::getInstance()->delete('carrier_zone', 'id_carrier=' . (int) $idPsCarriers);
        Db::getInstance()->delete('carrier', 'id_carrier=' . (int) $idPsCarriers);
        Db::getInstance()->delete('carrier_lang', 'id_carrier=' . (int) $idPsCarriers);
        Db::getInstance()->delete('carrier_shop', 'id_carrier=' . (int) $idPsCarriers);

        return true;
    }

    public static function insertIntolinksmenutop($newWindow, $idShop)
    {
        Db::getInstance()->insert(
            'linksmenutop',
            [
                'new_window' => (int) $newWindow,
                'id_shop' => (int) $idShop,
            ]
        );
    }

    public static function getLastInsertedId()
    {
        return Db::getInstance()->Insert_ID();
    }

    public static function insertIntoLinkMenuTopLang($idLinksMenutop, $idLang, $idShop, $label, $linkIdlang)
    {
        Db::getInstance()->insert(
            'linksmenutop_lang',
            [
                'id_linksmenutop' => (int) $idLinksMenutop,
                'id_lang' => (int) $idLang,
                'id_shop' => (int) $idShop,
                'label' => pSQL($label),
                'link' => pSQL($linkIdlang),
            ]
        );
    }

    public static function insertIntoCarrierZone($idCarrier, $idZone)
    {
        Db::getInstance()->insert(
            'carrier_zone',
            [
                'id_carrier' => (int) $idCarrier,
                'id_zone' => (int) $idZone,
            ]
        );
    }

    public static function insertIntoDelivery($idCarrier, $idPriceRange, $idZone)
    {
        Db::getInstance()->insert(
            'delivery',
            [
                'id_carrier' => (int) $idCarrier,
                'id_range_price' => (int) $idPriceRange,
                'id_range_weight' => null,
                'id_zone' => (int) $idZone,
                'price' => '0',
            ]
        );
    }

    public static function insertIntpCarrierGroup($idCarrier, $idGroup)
    {
        Db::getInstance()->insert(
            'carrier_group',
            [
                'id_carrier' => (int) $idCarrier,
                'id_group' => (int) $idGroup,
            ]
        );
    }

    public static function deleteFromlinksmenutopTable($preorderMenuId)
    {
        Db::getInstance()->delete('linksmenutop', 'id_linksmenutop = ' . (int) $preorderMenuId);
    }

    public static function deleteFromlinksmenutoplangTable($preorderMenuId)
    {
        Db::getInstance()->delete('linksmenutop_lang', 'id_linksmenutop = ' . (int) $preorderMenuId);
    }

    public static function updateOrderHistory($idOrderState, $idOrder, $oldOrderStatus)
    {
        Db::getInstance()->update(
            'order_history',
            [
                'id_order_state' => (int) $idOrderState,
            ],
            'id_order =' . (int) $idOrder . ' AND id_order_state = ' . (int) $oldOrderStatus
        );
    }

    public static function addDataInTables()
    {
        $id_shop = (int) Configuration::get('PS_SHOP_DEFAULT');
        $sql = 'SELECT * FROM `' . _DB_PREFIX_ . 'wk_preorder_product`';
        $allData = Db::getInstance()->executeS($sql);
        if ($allData) {
            foreach ($allData as $row) {
                Db::getInstance()->insert('wk_preorder_product_shop', [
                    'id_wk_preorder_product' => $row['id_wk_preorder_product'],
                    'product_id' => $row['product_id'],
                    'id_shop' => $id_shop,
                    'attribute_id' => $row['attribute_id'],
                    'original_price' => $row['original_price'],
                    'impact_price' => $row['impact_price'],
                    'payment_type' => $row['payment_type'],
                    'payment_method' => $row['payment_method'],
                    'preorder_price' => $row['preorder_price'],
                    'expected_date' => $row['expected_date'],
                    'is_preorder' => $row['is_preorder'],
                    'is_auto_available' => $row['is_auto_available'],
                    'quantity' => $row['quantity'],
                    'maxquantity' => $row['maxquantity'],
                    'prebooked_quantity' => $row['prebooked_quantity'],
                    'id_tax_rules_group' => $row['id_tax_rules_group'],
                    'id_applied_shipping' => $row['id_applied_shipping'],
                    'id_default_currency' => $row['id_default_currency'],
                ]);
            }
        }

        $sql = 'SELECT * FROM `' . _DB_PREFIX_ . 'wk_preorder_product_customer`';
        $allData = Db::getInstance()->executeS($sql);
        if ($allData) {
            foreach ($allData as $row) {
                Db::getInstance()->insert('wk_preorder_product_customer_shop', [
                    'id_shop' => $id_shop,
                ]);
            }
        }

        $sql = 'SELECT * FROM `' . _DB_PREFIX_ . 'wk_preorder_product_map`';
        $allData = Db::getInstance()->executeS($sql);
        if ($allData) {
            foreach ($allData as $row) {
                Db::getInstance()->insert('wk_preorder_product_map_shop', [
                    'id_shop' => $id_shop,
                    'product_id' => $row['product_id'],
                    'attribute_id' => $row['attribute_id'],
                    'customer_id' => $row['customer_id'],
                    'quantity' => $row['quantity'],
                    'used_voucher' => $row['used_voucher'],
                ]);
            }
        }

        $sql = 'SELECT * FROM `' . _DB_PREFIX_ . 'wk_preorder_specific_price`';
        $allData = Db::getInstance()->executeS($sql);
        if ($allData) {
            foreach ($allData as $row) {
                Db::getInstance()->insert('wk_preorder_specific_price_shop', [
                    'id_shop' => $id_shop,
                    'id_specific' => $row['id_specific'],
                    'is_deleted' => $row['is_deleted'],
                ]);
            }
        }

        return true;
    }

    public function getAllCreatedVoucher()
    {
        $allVoucher = Db::getInstance()->executeS(
            'SELECT * FROM `' . _DB_PREFIX_ . 'wk_preorder_cartrule_map`
            WHERE 1'
        );
        if ($allVoucher) {
            return $allVoucher;
        } else {
            return false;
        }
    }

    public function getAllSpecificPreorder()
    {
        $result = Db::getInstance()->executeS('SELECT * FROM `' . _DB_PREFIX_ . 'wk_preorder_specific_price` wkpsp' .
        Shop::addSqlAssociation('wk_preorder_specific_price', 'wkpsp') . '
        WHERE 1');
        if ($result) {
            return $result;
        }

        return false;
    }

    public function getPreorderShipping()
    {
        $carriers = Db::getInstance()->getRow(
            'SELECT `id_shipping` FROM `' . _DB_PREFIX_ . 'wk_preorder_free_shipping` WHERE 1'
        );
        if ($carriers) {
            return $carriers;
        } else {
            return false;
        }
    }

    public static function validateConfigConditions()
    {
        $context = Context::getContext();

        $wkCountryId = '';
        if (isset($context->customer->id)
            && $context->customer->id && $context->cookie->logged) {
            $objCustomer = new Customer($context->customer->id);

            if (isset($context->cart->id)) {
                $objAddress = new Address($context->cart->id_address_delivery);
                if (Validate::isLoadedObject($objAddress)) {
                    $objCountry = new Country($objAddress->id_country);
                    if ($objCountry->id) {
                        $wkCountryId = $objCountry->id;
                    }
                } else {
                    // if cart exists but no address selected from checkout page
                    if ($customerAddress = $objCustomer->getAddresses($context->language->id)) {
                        if (isset($customerAddress[0]['id_country'])) {
                            $objCountry = new Country((int) $customerAddress[0]['id_country']);
                            if ($objCountry->id) {
                                // If customer has address then check by address country
                                $wkCountryId = $objCountry->id;
                            }
                        }
                    } else {
                        // If customer has no address then check by user IP address
                        $wkCountryId = self::getCustomerLocationByIPAddress();
                    }
                }
            } else {
                if ($customerAddress = $objCustomer->getAddresses($context->language->id)) {
                    if (isset($customerAddress[0]['id_country'])) {
                        $objCountry = new Country((int) $customerAddress[0]['id_country']);
                        if ($objCountry->id) {
                            // If customer has address then check by address country
                            $wkCountryId = $objCountry->id;
                        }
                    }
                } else {
                    // If customer has no address then check by user IP address
                    $wkCountryId = self::getCustomerLocationByIPAddress();
                }
            }
        } else {
            // If user is not logged in as customer then check by user IP address
            $wkCountryId = self::getCustomerLocationByIPAddress();
        }

        $allowedCountries = json_decode(Configuration::get('WK_PREORDER_COUNTRY'));
        if ($allowedCountries && $wkCountryId
            && is_array($allowedCountries)
            && !in_array($wkCountryId, $allowedCountries)) {
            return true;
        }

        $allowedGroups = json_decode(Configuration::get('WK_PREORDER_GROUP'));
        if (isset($context->customer->id)
        && $context->customer->id && $context->cookie->logged) {
            $objCustomer = new Customer($context->customer->id);
            $customerGroups = $objCustomer->getGroups();
            $allowed = true;
            foreach ($customerGroups as $group) {
                if (in_array($group, $allowedGroups)) {
                    $allowed = false;
                }
            }
            if ($allowed) {
                return true;
            }
        } else {
            $customerGroup = Configuration::get('PS_GUEST_GROUP');
            if ($allowedGroups && $customerGroup
            && is_array($allowedGroups)
            && !in_array($customerGroup, $allowedGroups)) {
                return true;
            }
        }

        return false;
    }

    public static function getCustomerLocationByIPAddress()
    {
        $result = false;
        $allowedIPLocation = Configuration::get('WK_ALLOW_GEOLOCATION');
        if ($allowedIPLocation) {
            if (@filemtime(_PS_GEOIP_DIR_ . _PS_GEOIP_CITY_FILE_)) {
                $reader = new GeoIp2\Database\Reader(_PS_GEOIP_DIR_ . _PS_GEOIP_CITY_FILE_);

                try {
                    $record = $reader->city(Tools::getRemoteAddr());
                } catch (GeoIp2\Exception\AddressNotFoundException $e) {
                    $record = null;
                }

                if ($record && isset($record->country->isoCode)) {
                    $result = Country::getByIso($record->country->isoCode);
                }
            }
        }

        return $result;
    }

    public static function displayPrice($price, $currency = null)
    {
        if (!is_numeric($price)) {
            return $price;
        }

        $context = Context::getContext();
        $currency = $currency ?: $context->currency;

        if (is_int($currency)) {
            $currency = Currency::getCurrencyInstance($currency);
        }

        $locale = Tools::getContextLocale($context);
        $currencyCode = is_array($currency) ? $currency['iso_code'] : $currency->iso_code;

        return $locale->formatPrice($price, $currencyCode);
    }
}
