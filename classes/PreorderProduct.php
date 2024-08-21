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

class PreorderProduct extends ObjectModel
{
    public $id_wk_preorder_product;
    public $product_id;
    public $attribute_id;
    public $original_price;
    public $impact_price;
    public $payment_type;
    public $payment_method;
    public $preorder_price;
    public $expected_date;
    public $is_preorder;
    public $is_auto_available;
    public $quantity;
    public $maxquantity;
    public $prebooked_quantity;
    public $product_lang;
    public $id_tax_rules_group;
    public $id_applied_shipping;
    public $id_default_currency;
    public $recreation_date;
    public $date_add;
    public $date_upd;

    // Payment types
    public const WK_PREORDER_PAYMENT_FULL = 1;
    public const WK_PREORDER_PAYMENT_PARTIAL = 3;
    public const WK_PREORDER_PAYMENT_DYNAMIC = 3;

    // Payment partial methods
    public const WK_PREORDER_METHOD_PERCENTAGE = 1;
    public const WK_PREORDER_METHOD_FIXED = 2;

    public static $definition = [
        'table' => 'wk_preorder_product',
        'primary' => 'id_wk_preorder_product',
        'fields' => [
            'product_id' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt',
                'required' => true, 'shop' => true, ],
            'attribute_id' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt',
                'required' => true, 'shop' => true, ],
            'impact_price' => ['type' => self::TYPE_FLOAT, 'required' => true, 'shop' => true],
            'original_price' => ['type' => self::TYPE_FLOAT, 'validate' => 'isPrice', 'required' => true,
                'shop' => true, ],
            'payment_type' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt', 'shop' => true],
            'payment_method' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt', 'shop' => true],
            'preorder_price' => ['type' => self::TYPE_FLOAT, 'validate' => 'isPrice', 'required' => true,
                'shop' => true, ],
            'expected_date' => ['type' => self::TYPE_DATE, 'validate' => 'isDateFormat', 'required' => true,
                'shop' => true, ],
            'recreation_date' => ['type' => self::TYPE_DATE, 'validate' => 'isDateFormat',
                'shop' => true, ],
            'is_preorder' => ['type' => self::TYPE_BOOL, 'shop' => true],
            'is_auto_available' => ['type' => self::TYPE_BOOL, 'shop' => true],
            'quantity' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt', 'required' => true,
                'shop' => true, ],
            'maxquantity' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt', 'required' => true,
                'shop' => true, ],
            'prebooked_quantity' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt', 'required' => true,
                'shop' => true, ],
            'id_tax_rules_group' => ['type' => self::TYPE_INT, 'shop' => true],
            'id_applied_shipping' => ['type' => self::TYPE_STRING, 'shop' => true],
            'id_default_currency' => ['type' => self::TYPE_INT, 'shop' => true],
            'date_add' => ['type' => self::TYPE_DATE, 'validate' => 'isDateFormat', 'required' => false],
            'date_upd' => ['type' => self::TYPE_DATE, 'validate' => 'isDateFormat', 'required' => false],
        ],
    ];

    public function __construct($id = null, $idLang = null, $idShop = null)
    {
        parent::__construct($id, $idLang, $idShop);
        Shop::addTableAssociation(
            'wk_preorder_product',
            ['type' => 'shop', 'primary' => 'id_wk_preorder_product']
        );
    }

    public function delete()
    {
        $idProduct = $this->product_id;
        $idAttr = $this->attribute_id;
        $idShop = self::getPreorderProductIdShop($this->id_wk_preorder_product);

        if ($this->is_preorder > 0) {
            $newQuantity = 0;
            if ((int) $this->quantity >= (int) $this->prebooked_quantity) {
                $newQuantity = (int) $this->quantity - (int) $this->prebooked_quantity;
            } else {
                $newQuantity = $this->quantity;
            }
            if (Shop::CONTEXT_ALL == Shop::getContext()) {
                StockAvailable::setQuantity($idProduct, $idAttr, $newQuantity, $idShop, false);
            } else {
                StockAvailable::setQuantity($idProduct, $idAttr, $newQuantity, $idShop);
            }
            if ($this->payment_type == 1) {
            } else {
                if ($this->attribute_id == 0) {
                    $this->setOriginalProduct($idProduct, $this->original_price, $newQuantity, $idAttr);
                    $this->setCarriers($idProduct, $this->id_applied_shipping);
                } else {
                    $this->setImpactPrice($idProduct, $idAttr, $this->impact_price);
                    $this->setCarriers($idProduct, $this->id_applied_shipping);
                }
            }
            if ($idAttr) {
                if (Db::getInstance()->executeS(
                    'SELECT * FROM `' . _DB_PREFIX_ . 'wk_preorder_product` wkp' .
                    Shop::addSqlAssociation('wk_preorder_product', 'wkp') . '
                    WHERE wkp.`product_id` =' . (int) $idProduct . '
                    AND wkp.`attribute_id` !=' . (int) $idAttr
                )) {
                } else {
                    StockAvailable::setProductOutOfStock($idProduct, false, $idShop); // Set product deny order
                }
            } else {
                StockAvailable::setProductOutOfStock($idProduct, false, $idShop); // Set product deny order
            }
        }
        parent::delete();

        return true;
    }

    // changes product as allow order in v5.0.2
    public static function changeQuantity()
    {
        $sql = 'SELECT * FROM `' . _DB_PREFIX_ . 'wk_preorder_product` wkp' .
        Shop::addSqlAssociation('wk_preorder_product', 'wkp') . '
        WHERE wkp.`is_preorder` = 0';
        $inActivePreorderProducts = Db::getInstance()->executeS($sql);
        foreach ($inActivePreorderProducts as $product) {
            $idProduct = $product['product_id'];
            $idAttr = $product['attribute_id'];
            if ($product['payment_type'] != 1) {
                StockAvailable::setProductOutOfStock($idProduct, true, null, $idAttr);
            }
        }

        return true;
    }

    // get only when preorder status is active
    public function getExistingActivePreOrderProduct($id, $idAttr, $isPreorder = false)
    {
        $sql =
            'SELECT * FROM `' . _DB_PREFIX_ . 'wk_preorder_product` wkp' .
            Shop::addSqlAssociation('wk_preorder_product', 'wkp') . '
            WHERE wkp.`product_id` =' . (int) $id . '
            AND wkp.`attribute_id` =' . (int) $idAttr;

        if (!$isPreorder) {
            $sql .= ' AND wkp.`is_preorder` = 1';
        }
        $existingPreorder = Db::getInstance()->getRow($sql);
        if ($existingPreorder) {
            return $existingPreorder;
        } else {
            return false;
        }
    }

    public function getExistingPreOrderProduct($id, $idAttr)
    {
        $existingPreorder = Db::getInstance()->getRow(
            'SELECT * FROM `' . _DB_PREFIX_ . 'wk_preorder_product` wkp' .
            Shop::addSqlAssociation('wk_preorder_product', 'wkp') . '
            WHERE wkp.`product_id` = ' . (int) $id . '
            AND wkp.`attribute_id` = ' . (int) $idAttr
        );

        if ($existingPreorder) {
            return $existingPreorder;
        } else {
            return false;
        }
    }

    // not used
    public function getAllExistingPreOrderProduct($id, $idAttr)
    {
        $existingPreorder = Db::getInstance()->executeS(
            'SELECT * FROM `' . _DB_PREFIX_ . 'wk_preorder_product` wkp' .
            Shop::addSqlAssociation('wk_preorder_product', 'wkp') . '
            WHERE wkp.`product_id` =' . (int) $id . '
            AND wkp.`attribute_id` =' . (int) $idAttr
        );

        if ($existingPreorder) {
            return $existingPreorder;
        } else {
            return false;
        }
    }

    // for menu
    public function getAllPreorderProducts($idLang)
    {
        return Db::getInstance()->executeS(
            'SELECT * FROM `' . _DB_PREFIX_ . 'wk_preorder_product` wkp' .
            Shop::addSqlAssociation('wk_preorder_product', 'wkp') . '
            INNER JOIN `' . _DB_PREFIX_ . 'product_lang` AS pl ON
            wkp.`product_id` = pl.`id_product`
            INNER JOIN `' . _DB_PREFIX_ . 'product` AS p ON pl.`id_product` = p.`id_product` WHERE
            pl.`id_lang` = ' . (int) $idLang . ' AND wkp.`is_preorder` = 1 GROUP BY wkp.`product_id`'
        );
    }

    /**
     * [getAllPreOrderProduct -> fetching all preorder enabled product].
     *
     * @return [type] [array]
     */
    public function getAllPreOrderProduct($isPreorder = false)
    {
        $sql = 'SELECT * FROM `' . _DB_PREFIX_ . 'wk_preorder_product` wkp' .
        Shop::addSqlAssociation('wk_preorder_product', 'wkp') . '
        WHERE 1';
        if ($isPreorder) {
            $sql .= ' AND wkp.`is_preorder` = 1';
        }
        $getAllPreorderProduct = Db::getInstance()->executeS($sql);
        if ($getAllPreorderProduct) {
            return $getAllPreorderProduct;
        } else {
            return false;
        }
    }

    /**
     * [getAllAvailablePreOrderProductWithoutFull -> fetching all available preorder products].
     *
     * @return [type] [array]
     */
    public function getAllAvailablePreOrderProductWithoutFull()
    {
        $sql = 'SELECT * FROM `' . _DB_PREFIX_ . 'wk_preorder_product` wkp' .
        Shop::addSqlAssociation('wk_preorder_product', 'wkp') . '
        WHERE wkp.`payment_type` != 1 AND wkp.`is_preorder` = 0';

        $getAllPreorderProduct = Db::getInstance()->executeS($sql);
        if ($getAllPreorderProduct) {
            return $getAllPreorderProduct;
        } else {
            return false;
        }
    }

    /**
     * [getAllPreOrderProductAutoAvailable -> fetching all autoavailable preorder product WHERE is_auto_available = 1].
     *
     * @return [type] [array]
     */
    public function getAllPreOrderProductAutoAvailable()
    {
        $getAllPreorderProductAutoAvailable = Db::getInstance()->executeS(
            'SELECT * FROM `' . _DB_PREFIX_ . 'wk_preorder_product` wkp' .
            Shop::addSqlAssociation('wk_preorder_product', 'wkp') . ' WHERE wkp.`is_preorder` = 1'
        );
        if ($getAllPreorderProductAutoAvailable) {
            return $getAllPreorderProductAutoAvailable;
        } else {
            return false;
        }
    }

    public function getLangDisplayName($idProduct)
    {
        $productLang = Db::getInstance()->getRow(
            'SELECT `available_now` FROM `' . _DB_PREFIX_ . 'product_lang` pl' .
            Shop::addSqlAssociation('product_lang', 'pl') . '
            WHERE pl.`id_product`=' . (int) $idProduct
        );
        if ($productLang) {
            return $productLang;
        } else {
            return false;
        }
    }

    public function getImpactPrice($idProduct, $idAttr)
    {
        $impactPrice = Db::getInstance()->getRow(
            'SELECT * FROM `' . _DB_PREFIX_ . 'product_attribute` pa' .
            Shop::addSqlAssociation('product_attribute', 'pa') . '
            WHERE pa.`id_product` = ' . (int) $idProduct . '
            AND pa.`id_product_attribute` = ' . (int) $idAttr
        );
        if ($impactPrice) {
            return $impactPrice;
        } else {
            return false;
        }
    }

    public function setImpactPrice($idProduct, $idAttr, $price)
    {
        // allow/disallow order true in case of out of stock
        StockAvailable::setProductOutOfStock((int) $idProduct, true, null, $idAttr);
        Db::getInstance()->update(
            'product_attribute',
            [
                'price' => $price,
            ],
            'id_product = ' . (int) $idProduct . '
            AND `id_product_attribute` = ' . (int) $idAttr
        );
        if (!Shop::isFeatureActive() || Shop::getContext() !== Shop::CONTEXT_SHOP) {
            Db::getInstance()->update(
                'product_attribute_shop',
                [
                    'price' => $price,
                ],
                '`id_product_attribute` = ' . (int) $idAttr
            );
        } else {
            Db::getInstance()->update(
                'product_attribute_shop',
                [
                    'price' => $price,
                ],
                '`id_product_attribute` = ' . (int) $idAttr . ' AND
                `id_shop` = ' . (int) Context::getContext()->shop->id
            );
        }

        return true;
    }

    public function setPreorderPrice($idProduct, $attributeId, $price, $paymentType)
    {
        $context = Context::getContext();
        // allow order true in case of out of stock
        StockAvailable::setProductOutOfStock($idProduct, true);
        // set quantity zero
        if (Shop::CONTEXT_ALL == Shop::getContext()) {
            StockAvailable::setQuantity($idProduct, $attributeId, 0, null, false);
        } else {
            StockAvailable::setQuantity($idProduct, $attributeId, 0);
        }
        if ($paymentType == 2) {
            if ($attributeId == 0) {
                $objProduct = new Product((int) $idProduct, false, $context->language->id);
                $objProduct->price = (float) $price;
                $objProduct->update();
            }
        } elseif ($paymentType == 1) {
            $objProduct = new Product((int) $idProduct, false, $context->language->id);
            $objProduct->available_for_order = 1;
            $objProduct->update();
        }
    }

    public function setOriginalProductPrice($idProduct, $price)
    {
        Db::getInstance()->update(
            'product',
            [
                'price' => (float) $price,
            ],
            'id_product = ' . (int) $idProduct
        );
        Db::getInstance()->update(
            'product_shop',
            [
                'price' => (float) $price,
            ],
            'id_product = ' . (int) $idProduct
        );

        return true;
    }

    public function setOriginalProduct($idProduct, $price, $quantity, $attributeId)
    {
        if (Shop::CONTEXT_ALL == Shop::getContext()) {
            StockAvailable::setQuantity($idProduct, $attributeId, $quantity, null, false);
        } else {
            StockAvailable::setQuantity($idProduct, $attributeId, $quantity);
        }
        if ($attributeId) {
            if (Db::getInstance()->executeS(
                'SELECT * FROM `' . _DB_PREFIX_ . 'wk_preorder_product` wkp' .
                Shop::addSqlAssociation('wk_preorder_product', 'wkp') . '
                WHERE wkp.`product_id` =' . (int) $idProduct . '
                AND wkp.`attribute_id` !=' . (int) $attributeId
            )) {
            } else {
                StockAvailable::setProductOutOfStock($idProduct, false); // Set product deny order
            }
        } else {
            StockAvailable::setProductOutOfStock($idProduct, false);
        }
        // disallow order true in case of out of stock
        Db::getInstance()->update(
            'product',
            [
                'price' => (float) $price,
            ],
            'id_product = ' . (int) $idProduct
        );
        Db::getInstance()->update(
            'product_shop',
            [
                'price' => (float) $price,
            ],
            'id_product = ' . (int) $idProduct
        );

        return true;
    }

    public function getAttributeCombinationsById($idAttribute)
    {
        $attribute = Db::getInstance()->executeS(
            'SELECT * FROM `' . _DB_PREFIX_ . 'product_attribute_combination` pac' .
            Shop::addSqlAssociation('product_attribute_combination', 'pac') . '
            WHERE pac.`id_product_attribute`=' . (int) $idAttribute
        );
        if ($attribute) {
            return $attribute;
        } else {
            return false;
        }
    }

    public function getAttributesById($idAttribute)
    {
        $attr = [];
        $attribute = Db::getInstance()->executeS(
            'SELECT `id_attribute` FROM `' . _DB_PREFIX_ . 'product_attribute_combination` pac' .
            Shop::addSqlAssociation('product_attribute_combination', 'pac') . '
            WHERE pac.`id_product_attribute`=' . (int) $idAttribute
        );
        if (!empty($attribute)) {
            foreach ($attribute as $idAttr) {
                $attr[] = $idAttr['id_attribute'];
            }

            return $attr;
        } else {
            return false;
        }
    }

    public function getPreorderShipping()
    {
        if (Configuration::get('WK_PREORDER_SHIPPING')) {
            return [
                'id_shipping' => Configuration::get('WK_PREORDER_SHIPPING'),
            ];
        } else {
            return false;
        }
    }

    public function removeCarriers($idProduct)
    {
        Db::getInstance()->delete('product_carrier', 'id_product=' . (int) $idProduct);

        return true;
    }

    public function setCarriers($idProduct, $carriers)
    {
        $objProduct = new Product((int) $idProduct);
        $allCarrier = [];
        if ($idProduct) {
            $this->removeCarriers($idProduct);
            if ($carriers == '0') {
                $objProduct->setCarriers($allCarrier);
            } elseif ($carriers) {
                if (!is_array($carriers)) {
                    $carriers = json_decode($carriers);
                }
                $objProduct->setCarriers($carriers);
            }
        }
    }

    public function getExistingPreOrderByProductId($id)
    {
        $existingPreorder = Db::getInstance()->executeS(
            'SELECT * FROM ' . _DB_PREFIX_ . 'wk_preorder_product wkp' .
            Shop::addSqlAssociation('wk_preorder_product', 'wkp') . '
            WHERE wkp.product_id = ' . (int) $id
        );

        if ($existingPreorder) {
            return $existingPreorder;
        } else {
            return false;
        }
    }

    public function getAllPreorderAttributesByProductId($idProduct)
    {
        $preorderAttrs = Db::getInstance()->executeS(
            'SELECT wkp.`id_wk_preorder_product`, wkp.`attribute_id` FROM `' . _DB_PREFIX_ . 'wk_preorder_product` wkp' .
            Shop::addSqlAssociation('wk_preorder_product', 'wkp') . '
            WHERE wkp.`product_id` =' . (int) $idProduct
        );

        return $preorderAttrs;
    }

    public static function getPreorderTaxRulesByGroupId($idLang, $idGroup, $idCountry)
    {
        return Db::getInstance()->getRow(
            '
            SELECT g.`id_tax_rule`,
                c.`name` AS country_name,
                s.`name` AS state_name,
                t.`rate`,
                g.`zipcode_from`, g.`zipcode_to`,
                g.`description`,
                g.`behavior`,
                g.`id_country`,
                g.`id_state`
            FROM `' . _DB_PREFIX_ . 'tax_rule` g
            LEFT JOIN `' . _DB_PREFIX_ . 'country_lang` c
            ON (g.`id_country` = c.`id_country` AND `id_lang` =' . (int) $idLang . ')
            LEFT JOIN `' . _DB_PREFIX_ . 'state` s ON (g.`id_state` = s.`id_state`)
            LEFT JOIN `' . _DB_PREFIX_ . 'tax` t ON (g.`id_tax` = t.`id_tax`)
            WHERE `id_tax_rules_group` = ' . (int) $idGroup . ' AND g.`id_country` = ' . (int) $idCountry . '
            ORDER BY `country_name` ASC, `state_name` ASC, `zipcode_from` ASC, `zipcode_to` ASC'
        );
    }

    /**
     * [checkExsitingMpPreorder - checking in prestashop preorder for same product].
     *
     * @param [type] $id [ps product id]
     *
     * @return [type] [description]
     */
    public function checkExsitingPsPreorder($id)
    {
        $existingPreorder = Db::getInstance()->executeS(
            'SELECT * FROM `' . _DB_PREFIX_ . 'wk_preorder_product` wkp' .
            Shop::addSqlAssociation('wk_preorder_product', 'wkp') . '
            WHERE wkp.`product_id` =' . (int) $id
        );

        if ($existingPreorder) {
            return $existingPreorder;
        }

        return false;
    }

    public function checkExsitingPsActivePreorder($id)
    {
        $existingPreorder = Db::getInstance()->getRow(
            'SELECT * FROM `' . _DB_PREFIX_ . 'wk_preorder_product` wkp' .
            Shop::addSqlAssociation('wk_preorder_product', 'wkp') . '
            WHERE wkp.`product_id` =' . (int) $id . ' AND wkp.`is_preorder`=1'
        );

        if ($existingPreorder) {
            return $existingPreorder;
        }

        return false;
    }

    public static function autoUpdateAllPreorder()
    {
        $moduleInstance = new Preorder();
        $context = Context::getContext();
        $idLang = $context->language->id;
        $preorderObj = new self();
        $objpreordercust = new PreorderProductCustomer();
        $existingAutoAvailablePreorder = $preorderObj->getAllPreOrderProductAutoAvailable();
        if (!empty($existingAutoAvailablePreorder)) {
            foreach ($existingAutoAvailablePreorder as $availablePreorder) {
                $availableDateStamp = strtotime($availablePreorder['expected_date']);
                $currentDateStamp = strtotime(date('Y-m-d H:i:s'));

                if ($currentDateStamp >= $availableDateStamp) {
                    $objPreorder = new self(
                        $availablePreorder['id_wk_preorder_product'],
                        null,
                        $availablePreorder['id_shop']
                    );
                    // $objPreorder->prebooked_quantity = '0';
                    $objPreorder->is_preorder = '0';
                    if ($objPreorder->update()) {
                        $newQuantity = 0;
                        if ((int) $availablePreorder['quantity'] >= (int) $availablePreorder['prebooked_quantity']) {
                            $preorderQuantity = (int) $availablePreorder['quantity'];
                            $prebookedQuantity = (int) $availablePreorder['prebooked_quantity'];
                            $newQuantity = $preorderQuantity - $prebookedQuantity;
                        } else {
                            $newQuantity = $availablePreorder['quantity'];
                        }
                        $idProduct = $availablePreorder['product_id'];
                        $idAttr = $availablePreorder['attribute_id'];
                        $quantity = $newQuantity;
                        $originalPrice = $availablePreorder['original_price'];
                        $impactPrice = $availablePreorder['impact_price'];
                        if (Shop::CONTEXT_ALL == Shop::getContext()) {
                            StockAvailable::setQuantity($idProduct, $idAttr, $quantity, null, false);
                        } else {
                            StockAvailable::setQuantity($idProduct, $idAttr, $quantity, null);
                        }
                        if ($availablePreorder['payment_type'] != 1) {
                            if ($idAttr == 0) {
                                $preorderObj->setOriginalProduct($idProduct, $originalPrice, $quantity, $idAttr);
                            } else {
                                $preorderObj->setImpactPrice($idProduct, $idAttr, $impactPrice);
                            }
                            $preorderObj->removeCarriers($idProduct);
                            $appliedShipping = $availablePreorder['id_applied_shipping'];

                            if ($appliedShipping == 0) {
                                $idAppliedShipping = $appliedShipping;
                            } else {
                                $idAppliedShipping = json_decode($appliedShipping);
                            }
                            $preorderObj->setCarriers($idProduct, $idAppliedShipping);
                        }
                        StockAvailable::setProductOutOfStock($idProduct, true);

                        $allCust = $objpreordercust->getAllCustomerPreOrderByIdProduct($idProduct, $idAttr);
                        if ($allCust) {
                            foreach ($allCust as $customer) {
                                $idCustomer = $customer['customer_id'];

                                if ($customer['preorder_complete'] == 1) {
                                    continue;
                                }
                                $objCustomer = new Customer($idCustomer);
                                if ($objCustomer) {
                                    $firstname = $objCustomer->firstname;
                                    $lastname = $objCustomer->lastname;
                                    $email = $objCustomer->email;
                                    $idLang = $objCustomer->id_lang;
                                    // $currency = $params['currency'];
                                    $startdate = $availablePreorder['expected_date'];
                                    $enddate = date(
                                        'Y-m-d H:i:s',
                                        strtotime(date(
                                            'Y-m-d',
                                            strtotime($availablePreorder['expected_date'])
                                        ) . ' + 365 day')
                                    );

                                    $objOrder = new Order($customer['order_id']);
                                    $currency = new Currency($objOrder->id_currency);
                                    $productName = self::getPsProductName($idProduct, $idAttr, $idLang);

                                    $allowedDays = '';
                                    if ($customer['limited_time']) {
                                        $allowedDays = sprintf($moduleInstance->l('You have %s days to complete your order.'), $customer['allowed_days']);
                                    }

                                    $customerVars = [
                                        '{firstname}' => $firstname,
                                        '{customer_name}' => $firstname . ' ' . $lastname,
                                        '{lastname}' => $lastname,
                                        '{email}' => $email,
                                        '{startdate}' => $startdate,
                                        '{enddate}' => $enddate,
                                        '{paid_amount}' => PreorderHelper::displayPrice(
                                            Tools::ps_round($customer['paid_amt'], 2),
                                            $currency
                                        ),
                                        '{product_name}' => $productName,
                                        '{allowed_days}' => $allowedDays,
                                    ];

                                    $templatePath = _PS_MODULE_DIR_ . 'preorder/mails/';
                                    Mail::Send(
                                        (int) $idLang,
                                        'available_mail_to_customer',
                                        Mail::l('Your Preorder Product now available.', (int) $idLang),
                                        $customerVars,
                                        $email,
                                        null,
                                        null,
                                        null,
                                        null,
                                        null,
                                        $templatePath,
                                        false,
                                        null,
                                        null
                                    );

                                    unset($objOrder);
                                }
                            }
                        }

                        Tools::clearSmartyCache();
                    }
                    if ($availablePreorder['payment_type'] == 1) {
                        if ($availablePreorder['product_id']) {
                            if ($idAttr) {
                                if (Db::getInstance()->executeS(
                                    'SELECT * FROM `' . _DB_PREFIX_ . 'wk_preorder_product` wkp' .
                                    Shop::addSqlAssociation('wk_preorder_product', 'wkp') . '
                                    WHERE wkp.`product_id` =' . (int) $availablePreorder['product_id'] . '
                                    AND wkp.`attribute_id` !=' . (int) $idAttr
                                )) {
                                } else {
                                    StockAvailable::setProductOutOfStock($availablePreorder['product_id'], false);
                                    // Set product deny order
                                }
                            } else {
                                StockAvailable::setProductOutOfStock($availablePreorder['product_id'], false);
                            }
                        }
                    }
                }
            }
        }

        // Cancelled order if limited time is over and customer didn't complete preorder. Also rollback stock
        $allPreorderProducts = $preorderObj->getAllAvailablePreOrderProductWithoutFull();
        if ($allPreorderProducts && is_array($allPreorderProducts)) {
            foreach ($allPreorderProducts as $product) {
                $currentDateStamp = strtotime(date('Y-m-d H:i:s'));
                if ($product['is_preorder'] == 0) {
                    $idProduct = $product['product_id'];
                    $idAttr = $product['attribute_id'];
                    $allCust = $objpreordercust->getAllCustomerPreOrderByIdProduct($idProduct, $idAttr);
                    if ($allCust && is_array($allCust)) {
                        $lastUpdateDateStamp = strtotime($product['date_upd']);
                        $timeDiff = $currentDateStamp - $lastUpdateDateStamp;
                        foreach ($allCust as $customer) {
                            if ($customer['limited_time'] && $customer['preorder_complete'] != 1) {
                                $allowedTime = $customer['allowed_days'] * 24 * 60 * 60;
                                if ($timeDiff > $allowedTime) {
                                    $order = new Order($customer['order_id']);
                                    $order->setCurrentState(Configuration::get('PS_OS_CANCELED'));

                                    $preorderCustomerObj = new PreorderProductCustomer($customer['id_wk_preorder_product_customer']);
                                    $preorderCustomerObj->disallow_order = 1;
                                    $preorderCustomerObj->update();

                                    // Rollback stock
                                    if ($customer['stock_rollback'] == 0) {
                                        StockAvailable::updateQuantity($idProduct, $idAttr, -$customer['quantity'], null);
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    public static function getPriceStatic(
        $id_product,
        $usetax = true,
        $id_product_attribute = null,
        $decimals = 6,
        $divisor = null,
        $only_reduc = false,
        $usereduc = true,
        $quantity = 1,
        $id_customer = null,
        $id_cart = null,
        $id_address = null,
        $specific_price_output = null,
        $with_ecotax = true,
        $use_group_reduction = true,
        Context $context = null,
        $use_customer_price = true,
        $id_customization = null,
        $preorderoriginalPrice = 1
    ) {
        if (!$context) {
            $context = Context::getContext();
        }

        $cur_cart = $context->cart;

        if ($divisor !== null) {
            Tools::displayParameterAsDeprecated('divisor');
        }

        if (!Validate::isBool($usetax) || !Validate::isUnsignedId($id_product)) {
            exit(Tools::displayError());
        }

        // Initializations
        $id_group = null;
        if ($id_customer) {
            $id_group = Customer::getDefaultGroupId((int) $id_customer);
        }
        if (!$id_group) {
            $id_group = (int) Group::getCurrent()->id;
        }

        // If there is cart in context or if the specified id_cart is different from the context cart id
        if (!is_object($cur_cart) || (Validate::isUnsignedInt($id_cart) && $id_cart && $cur_cart->id != $id_cart)) {
            /*
            * When a user (e.g., guest, customer, Google...) is on PrestaShop,
            he has already its cart as the global (see /init.php)
            * When a non-user calls directly this method (e.g., payment module...) is on PrestaShop,
            he does not have already it BUT knows the cart ID
            * When called from the back office, cart ID can be inexistant
            */
            if (!$id_cart && !isset($context->employee)) {
                exit(Tools::displayError());
            }
            $cur_cart = new Cart($id_cart);
            // Store cart in context to avoid multiple instantiations in BO
            if (!Validate::isLoadedObject($context->cart)) {
                $context->cart = $cur_cart;
            }
        }

        $cart_quantity = 0;
        if ((int) $id_cart) {
            $cache_id = 'Product::getPriceStatic_' . (int) $id_product . '-' . (int) $id_cart;
            if (!Cache::isStored($cache_id) || ($cart_quantity = Cache::retrieve($cache_id) != (int) $quantity)) {
                $sql = 'SELECT SUM(`quantity`)
                FROM `' . _DB_PREFIX_ . 'cart_product`
                WHERE `id_product` = ' . (int) $id_product . '
                AND `id_cart` = ' . (int) $id_cart;
                $cart_quantity = (int) Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue($sql);
                Cache::store($cache_id, $cart_quantity);
            } else {
                $cart_quantity = Cache::retrieve($cache_id);
            }
        }

        $id_currency = Validate::isLoadedObject(
            $context->currency
        ) ? (int) $context->currency->id : (int) Configuration::get('PS_CURRENCY_DEFAULT');

        if (!$id_address && Validate::isLoadedObject($cur_cart)) {
            $id_address = $cur_cart->{Configuration::get('PS_TAX_ADDRESS_TYPE')};
        }

        // retrieve address informations
        $address = Address::initialize($id_address, true);
        $id_country = (int) $address->id_country;
        $id_state = (int) $address->id_state;
        $zipcode = $address->postcode;

        if (Tax::excludeTaxeOption()) {
            $usetax = false;
        }

        if ($usetax != false
            && !empty($address->vat_number)
            && $address->id_country != Configuration::get('VATNUMBER_COUNTRY')
            && Configuration::get('VATNUMBER_MANAGEMENT')) {
            $usetax = false;
        }

        if (null === $id_customer && Validate::isLoadedObject($context->customer)) {
            $id_customer = $context->customer->id;
        }

        $return = Product::priceCalculation(
            $context->shop->id,
            $id_product,
            $id_product_attribute,
            $id_country,
            $id_state,
            $zipcode,
            $id_currency,
            $id_group,
            $quantity,
            $usetax,
            $decimals,
            $only_reduc,
            $usereduc,
            $with_ecotax,
            $specific_price_output,
            $use_group_reduction,
            $id_customer,
            $use_customer_price,
            $id_cart,
            $cart_quantity,
            $id_customization,
            $preorderoriginalPrice
        );

        return $return;
    }

    // check for other combinations before deactivate or delete preorder product
    public static function changeAvailabilityPreference($idProduct, $idAttr)
    {
        if ($idAttr) {
            if (Db::getInstance()->executeS(
                'SELECT * FROM `' . _DB_PREFIX_ . 'wk_preorder_product` wkp' .
                Shop::addSqlAssociation('wk_preorder_product', 'wkp') . '
                WHERE wkp.`product_id` =' . (int) $idProduct . '
                AND wkp.`attribute_id` !=' . (int) $idAttr
            )) {
            } else {
                StockAvailable::setProductOutOfStock($idProduct, false); // Set product deny order
            }
        } else {
            StockAvailable::setProductOutOfStock($idProduct, false);
        }
    }

    public function getOrdersByIdCart($idCart)
    {
        $result = Db::getInstance()->executeS(
            'SELECT `id_order` FROM ' . _DB_PREFIX_ . 'orders
            WHERE `id_cart` =' . (int) $idCart
        );
        if ($result) {
            return $result;
        }

        return false;
    }

    public function getVoucherExistForCountry($idCart, $idCountry)
    {
        $result = Db::getInstance()->getRow(
            'SELECT * FROM ' . _DB_PREFIX_ . 'cart_rule_country
            WHERE `id_cart_rule` =' . (int) $idCart . '
            AND `id_country` = ' . (int) $idCountry
        );
        if ($result) {
            return $result;
        }

        return false;
    }

    public static function getPsProductName($idProduct, $idProductAttribute = null, $idLang = null)
    {
        // use the lang in the context if $idLang is not defined
        if (!$idLang) {
            $idLang = (int) Context::getContext()->language->id;
        }

        // creates the query object
        $query = new DbQuery();

        // selects different names, if it is a combination
        if ($idProductAttribute) {
            $query->select(
                'CONCAT(pl.name, \' : \', GROUP_CONCAT(DISTINCT agl.`name`, \' - \', al.name SEPARATOR \', \'))
                as name'
            );
        } else {
            $query->select('DISTINCT pl.name as name');
        }

        // adds joins & WHERE clauses for combinations
        if ($idProductAttribute) {
            $query->from('product_attribute', 'pa');
            $query->join(Shop::addSqlAssociation('product_attribute', 'pa'));
            $query->innerJoin(
                'product_lang',
                'pl',
                'pl.id_product = pa.id_product
                AND pl.id_lang = ' . (int) $idLang . Shop::addSqlRestrictionOnLang('pl')
            );
            $query->leftJoin(
                'product_attribute_combination',
                'pac',
                'pac.id_product_attribute = pa.id_product_attribute'
            );
            $query->leftJoin('attribute', 'atr', 'atr.id_attribute = pac.id_attribute');
            $query->leftJoin(
                'attribute_lang',
                'al',
                'al.id_attribute = atr.id_attribute AND al.id_lang = ' . (int) $idLang
            );
            $query->leftJoin(
                'attribute_group_lang',
                'agl',
                'agl.id_attribute_group = atr.id_attribute_group
                AND agl.id_lang = ' . (int) $idLang
            );
            $query->WHERE(
                'pa.id_product = ' . (int) $idProduct . ' AND pa.id_product_attribute = ' . (int) $idProductAttribute
            );
        } else {
            // or just adds a 'WHERE' clause for a simple product

            $query->from('product_lang', 'pl');
            $query->WHERE('pl.id_product = ' . (int) $idProduct);
            $query->WHERE('pl.id_lang = ' . (int) $idLang . Shop::addSqlRestrictionOnLang('pl'));
        }

        return Db::getInstance()->getValue($query);
    }

    public static function getPreorderProductIdShop($id)
    {
        $sql = 'SELECT `id_shop` FROM `' . _DB_PREFIX_ . 'wk_preorder_product_shop`
        WHERE `id_wk_preorder_product` = ' . (int) $id;
        $result = Db::getInstance()->getValue($sql);
        if ($result) {
            return $result;
        } else {
            return false;
        }
    }
}
