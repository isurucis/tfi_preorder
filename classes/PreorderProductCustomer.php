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

class PreorderProductCustomer extends ObjectModel
{
    public $id_wk_preorder_product_customer;
    public $product_id;
    public $attribute_id;
    public $customer_id;
    public $order_id;
    public $quantity;
    public $payment_type;
    public $paid_amt;
    public $remaining_amt;
    public $original_price;
    public $tax_amt;
    public $shipping_amt;
    public $booked_date;
    public $preorder_complete;
    public $country;
    public $state;
    public $complete_qty;
    public $limited_time;
    public $allowed_days;
    public $stock_rollback;
    public $date_add;
    public $date_upd;
    public $disallow_order;
    public static $definition = [
        'table' => 'wk_preorder_product_customer',
        'primary' => 'id_wk_preorder_product_customer',
        'fields' => [
            'product_id' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'],
            'attribute_id' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'],
            'customer_id' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'],
            'order_id' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'],
            'quantity' => ['type' => self::TYPE_FLOAT, 'validate' => 'isPrice'],
            'disallow_order' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool'],
            'payment_type' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'],
            'paid_amt' => ['type' => self::TYPE_FLOAT, 'validate' => 'isPrice'],
            'remaining_amt' => ['type' => self::TYPE_FLOAT, 'validate' => 'isPrice'],
            'original_price' => ['type' => self::TYPE_FLOAT, 'validate' => 'isPrice'],
            'tax_amt' => ['type' => self::TYPE_FLOAT, 'validate' => 'isPrice'],
            'shipping_amt' => ['type' => self::TYPE_FLOAT, 'validate' => 'isPrice'],
            'booked_date' => ['type' => self::TYPE_DATE, 'validate' => 'isDateFormat'],
            'preorder_complete' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool'],
            'complete_qty' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'],
            'country' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'],
            'state' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'],
            'limited_time' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool'],
            'allowed_days' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'],
            'stock_rollback' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool'],
            'date_add' => ['type' => self::TYPE_DATE, 'validate' => 'isDateFormat', 'required' => false],
            'date_upd' => ['type' => self::TYPE_DATE, 'validate' => 'isDateFormat', 'required' => false],
        ],
    ];

    public function __construct($id = null, $idLang = null, $idShop = null)
    {
        parent::__construct($id, $idLang, $idShop);
        Shop::addTableAssociation(
            'wk_preorder_product_customer',
            [
                'type' => 'shop',
                'primary' => 'id_wk_preorder_product_customer',
            ]
        );
    }

    public function insertOrderID($id, $idOrder)
    {
        Db::getInstance()->insert(
            'wk_preorder_order_map',
            [
                'id_wk_preorder_product_customer' => (int) $id,
                'order_id' => (int) $idOrder,
            ]
        );
    }

    public static function getOrderIDs($id)
    {
        return Db::getInstance()->executeS(
            'SELECT order_id FROM `' . _DB_PREFIX_ . 'wk_preorder_order_map`
            WHERE id_wk_preorder_product_customer = ' . (int) $id
        );
    }

    public function getCustomerIncompletePreorder($idCustomer, $idProduct, $idAttr)
    {
        $productDetails = Db::getInstance()->getRow(
            'SELECT * FROM ' . _DB_PREFIX_ . 'wk_preorder_product_customer wkpc' .
            Shop::addSqlAssociation('wk_preorder_product_customer', 'wkpc') . '
            WHERE wkpc.product_id  = ' . (int) $idProduct . '
            AND wkpc.customer_id=' . (int) $idCustomer . '
            AND wkpc.attribute_id =' . (int) $idAttr . '
            AND wkpc.preorder_complete = 0'
        );
        if ($productDetails) {
            return $productDetails;
        } else {
            return false;
        }
    }

    /**
     * [getCustomerPreOrderProduct fetching customer details by customer id].
     *
     * @param [type] $idCustomer [customer id]
     *
     * @return [type] [array]
     */
    public function getCustomerPreOrderProduct($idCustomer)
    {
        $productDetails = Db::getInstance()->getRow(
            'SELECT * FROM `' . _DB_PREFIX_ . 'wk_preorder_product_customer` wkpc' .
            Shop::addSqlAssociation('wk_preorder_product_customer', 'wkpc') . '
            WHERE wkpc.`customer_id`  = ' . (int) $idCustomer
        );
        if ($productDetails) {
            return $productDetails;
        } else {
            return false;
        }
    }

    /**
     * [getCustomerPreOrderByIdProduct -> fetching all the customers details which they bought preorder product].
     *
     * @param [type] $idProduct [product id]
     *
     * @return [type] [array]
     */
    public function getCustomerPreOrderByIdProduct($idProduct)
    {
        $productDetails = Db::getInstance()->getRow(
            'SELECT * FROM `' . _DB_PREFIX_ . 'wk_preorder_product_customer` wkpc' .
            Shop::addSqlAssociation('wk_preorder_product_customer', 'wkpc') . '
            WHERE wkpc.`product_id`  = ' . (int) $idProduct
        );
        if ($productDetails) {
            return $productDetails;
        } else {
            return false;
        }
    }

    /**
     * [getCustomerPreOrderByIdPIdC ->fetching all the customer details using product id and customer id both together].
     *
     * @param [type] $idCustomer [customer id ]
     * @param [type] $idProduct  [product id]
     *
     * @return [type] [array]
     */
    public function getCustomerPreOrderByIdPIdC($idCustomer, $idProduct, $idAttr)
    {
        $productDetails = Db::getInstance()->getRow(
            'SELECT * FROM `' . _DB_PREFIX_ . 'wk_preorder_product_customer` wkpc' .
            Shop::addSqlAssociation('wk_preorder_product_customer', 'wkpc') . '
            WHERE wkpc.`product_id`  = ' . (int) $idProduct . '
            AND wkpc.`customer_id`=' . (int) $idCustomer . '
            AND wkpc.`attribute_id` =' . (int) $idAttr
        );
        if ($productDetails) {
            return $productDetails;
        } else {
            return false;
        }
    }

    /**
     * [getCustomerPreOrderProduct fetching customer details by customer id].
     *
     * @param [type] $idCustomer [customer id]
     *
     * @return [type] [array]
     */
    public function getCustomerPreOrderProductByIdPro(
        $idCustomer,
        $idProduct,
        $idAttr,
        $idCountry,
        $idState,
        $idOrder = null
    ) {
        $sql = 'SELECT * FROM `' . _DB_PREFIX_ . 'wk_preorder_product_customer` wkpc' .
        Shop::addSqlAssociation('wk_preorder_product_customer', 'wkpc') . '
        WHERE wkpc.`preorder_complete` = 0
        AND wkpc.`customer_id` = ' . (int) $idCustomer . '
        AND wkpc.`product_id` = ' . (int) $idProduct . '
        AND wkpc.`attribute_id` = ' . (int) $idAttr . '
        AND wkpc.`country` = ' . (int) $idCountry . '
        AND wkpc.`state` = ' . (int) $idState;
        if ($idOrder) {
            $sql .= ' AND wkpc.`order_id` = ' . (int) $idOrder;
        }
        $productDetails = Db::getInstance()->executeS($sql);
        if ($productDetails) {
            return $productDetails;
        } else {
            return false;
        }
    }

    public function getCustomerPreOrderProductByIdProduct($idCustomer, $idProduct, $idAttr)
    {
        $productDetails = Db::getInstance()->executeS(
            'SELECT * FROM `' . _DB_PREFIX_ . 'wk_preorder_product_customer` wkpc' .
            Shop::addSqlAssociation('wk_preorder_product_customer', 'wkpc') . '
            WHERE wkpc.`preorder_complete` = 0
            AND wkpc.`customer_id` = ' . (int) $idCustomer . '
            AND wkpc.`product_id` = ' . (int) $idProduct . '
            AND wkpc.`attribute_id` =' . (int) $idAttr
        );
        if ($productDetails) {
            return $productDetails;
        } else {
            return false;
        }
    }

    /**
     * Fetching all the customer details using product id and customer id both together].
     *
     * @param [type] $idCustomer [customer id ]
     * @param [type] $idProduct  [product id]
     *
     * @return [type] [array]
     */
    public function getCustomerPreOrderByIdPIdCIdO($idCustomer, $idProduct, $idAttr, $idOrder)
    {
        $productDetails = Db::getInstance()->getRow(
            'SELECT * FROM `' . _DB_PREFIX_ . 'wk_preorder_product_customer` wkpc' .
            Shop::addSqlAssociation('wk_preorder_product_customer', 'wkpc') . '
            WHERE wkpc.`product_id`  = ' . (int) $idProduct . '
            AND wkpc.`attribute_id`  = ' . (int) $idAttr . '
            AND wkpc.`customer_id` =' . (int) $idCustomer . '
            AND wkpc.`order_id` =' . (int) $idOrder . '
            AND wkpc.`disallow_order` = 0'
        );
        if ($productDetails) {
            return $productDetails;
        } else {
            return false;
        }
    }

    /**
     * Fetching all the customer details using product id and customer id and id_order both together.
     *
     * @param [type] $idCustomer [customer id ]
     * @param [type] $idProduct  [product id]
     *
     * @return [type] [array]
     */
    public function getCustomerAllPreOrderByIdPIdCIdO($idCustomer, $idProduct, $idAttr, $idOrder)
    {
        $productDetails = Db::getInstance()->getRow(
            'SELECT * FROM `' . _DB_PREFIX_ . 'wk_preorder_product_customer` wkpc' .
            Shop::addSqlAssociation('wk_preorder_product_customer', 'wkpc') . '
            WHERE wkpc.`product_id`  = ' . (int) $idProduct . '
            AND wkpc.`attribute_id`  = ' . (int) $idAttr . '
            AND wkpc.`customer_id` =' . (int) $idCustomer . '
            AND wkpc.`order_id` =' . (int) $idOrder . ';'
        );
        if ($productDetails) {
            return $productDetails;
        } else {
            return false;
        }
    }

    /**
     * [updatePreOrderProduct -> updating preorder product into customer account].
     *
     * @param [type] $idProduct     [product id]
     * @param [type] $idCustomer    [customer id]
     * @param [type] $paidAmt       [amount which they paid]
     * @param [type] $taxAmt        [tax amount]
     * @param [type] $totalShipping [shipping amount included tax]
     *
     * @return [type] [array]
     */
    public function updatePreOrderProduct($idProduct, $idCustomer, $paidAmt, $taxAmt, $totalShipping)
    {
        $productDetails = Db::getInstance()->getRow(
            'SELECT * FROM `' . _DB_PREFIX_ . 'wk_preorder_product_customer` wkpc' .
            Shop::addSqlAssociation('wk_preorder_product_customer', 'wkpc') . '
            WHERE wkpc.`product_id`  = ' . (int) $idProduct . '
            AND wkpc.`customer_id`  = ' . (int) $idCustomer
        );
        $preorder_update = Db::getInstance()->update(
            'wk_preorder_product_customer',
            [
                'paid_amt' => (float) $productDetails['paid_amt'] + $paidAmt,
                'remaining_amt' => (float) $productDetails['remaining_amt'] + $taxAmt + $totalShipping - $paidAmt,
                'tax_amt' => (float) $taxAmt,
                'shipping_amt' => (float) $totalShipping,
                'preorder_complete' => '1',
            ],
            'product_id = ' . (int) $idProduct . ' AND customer_id = ' . (int) $idCustomer
        );
        if ($preorder_update) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * [getAllCustomerPreOrderByIdProduct -> fetching all customer with product id ].
     *
     * @param [type] $idProduct [product id ]
     *
     * @return [type] [array]
     */
    public function getAllCustomerPreOrderByIdProduct($idProduct, $idAttr)
    {
        $productDetails = Db::getInstance()->executeS(
            'SELECT * FROM `' . _DB_PREFIX_ . 'wk_preorder_product_customer` wkpc' .
            Shop::addSqlAssociation('wk_preorder_product_customer', 'wkpc') . '
            WHERE wkpc.`product_id`  = ' . (int) $idProduct . '
            AND wkpc.`attribute_id`= ' . (int) $idAttr . '
            AND wkpc.`disallow_order` = 0'
        );
        if ($productDetails) {
            return $productDetails;
        } else {
            return false;
        }
    }

    public function getAppliedCartOnOrder($idOrder)
    {
        $cart = Db::getInstance()->executeS(
            'SELECT * FROM `' . _DB_PREFIX_ . 'order_cart_rule` ocr' .
            Shop::addSqlAssociation('wk_preorder_product_customer', 'ocr') . '
            WHERE ocr.`id_order`= ' . (int) $idOrder
        );
        if ($cart) {
            return $cart;
        } else {
            return false;
        }
    }

    public static function getAllOrderDetailsByIdCustomer($idCustomer = false)
    {
        if (!$idCustomer) {
            $idCustomer = Context::getContext()->customer->id;
        }
        $sql = 'SELECT a.preorder_complete, a.product_id, a.attribute_id, a.quantity, a.order_id, a.`paid_amt` as total_paid,
        ord.reference as reference, ord.payment as payment, ord.id_currency, ord.date_add, a.remaining_amt, a.tax_amt
        FROM `' . _DB_PREFIX_ . 'wk_preorder_product_customer` a
        LEFT JOIN `' . _DB_PREFIX_ . 'orders` ord ON (a.`order_id` = ord.`id_order`)
        LEFT JOIN `' . _DB_PREFIX_ . 'wk_preorder_product_customer_shop` wkpcs ON (wkpcs.`id_wk_preorder_product_customer` = a.`id_wk_preorder_product_customer` AND wkpcs.id_shop = ord.id_shop)
        LEFT JOIN `' . _DB_PREFIX_ . 'customer` c ON (ord.`id_customer` = c.`id_customer`)
        WHERE a.`customer_id` = ' . (int) $idCustomer . Shop::addSqlRestriction(Shop::SHARE_ORDER, 'ord') . ' GROUP BY a.id_wk_preorder_product_customer ORDER BY a.date_add DESC';
        $allOrders = Db::getInstance()->executeS($sql);

        $locale = Context::getContext()->getCurrentLocale();
        foreach ($allOrders as $key => $orders) {
            $currency = new Currency($orders['id_currency']);
            $defaultCurrency = new Currency(Configuration::get('PS_CURRENCY_DEFAULT'));
            $orders['total_paid'] = Tools::convertPriceFull(
                $orders['total_paid'],
                $defaultCurrency,
                $currency
            );
            $orders['remaining_amt'] = Tools::convertPriceFull(
                $orders['remaining_amt'],
                $defaultCurrency,
                $currency
            );
            $orders['tax_amt'] = Tools::convertPriceFull(
                $orders['tax_amt'],
                $defaultCurrency,
                $currency
            );
            $allOrders[$key]['priceWithCurrency'] = PreorderHelper::displayPrice($orders['total_paid'], $currency);
            $allOrders[$key]['priceRemWithCurrency'] = PreorderHelper::displayPrice($orders['remaining_amt'] - $orders['tax_amt'], $currency);
            $allOrders[$key]['priceRemWithCurrencyComp'] = PreorderHelper::displayPrice($orders['remaining_amt'], $currency);
            $allOrders[$key]['date_add'] = Tools::displayDate($orders['date_add']);
            if ($productDetails = self::getProductProductDetails($idCustomer, $orders)) {
                $allOrders[$key]['preorder_status'] = $productDetails['preorder_status'];
                $allOrders[$key]['product_name'] = $productDetails['product_name'];
            }
        }

        return $allOrders;
    }

    public static function getProductProductDetails($idCustomer, $orderData)
    {
        $productDetails = [];
        $objOrderDetail = new OrderDetail();
        $idOrder = $orderData['order_id'];
        $orderDataList = $objOrderDetail->getList($idOrder);
        $preorderProduct = new PreorderProduct();
        $preorderProductCust = new PreorderProductCustomer();
        $currentDate = strtotime(date('Y-m-d H:i:s'));
        foreach ($orderDataList as $key => $order_val) {
            $idProduct = $order_val['product_id'];
            $idAttrib = $order_val['product_attribute_id'];
            if ($idProduct && ($idProduct == $orderData['product_id'] && $idAttrib == $orderData['attribute_id'])) {
                $productDetails['product_name'] = $order_val['product_name'];
                $existingPreorder = $preorderProduct->getExistingPreOrderProduct($idProduct, $idAttrib);
                if ($existingPreorder) {
                    $custProduct = $preorderProductCust->getCustomerAllPreOrderByIdPIdCIdO(
                        $idCustomer,
                        $idProduct,
                        $idAttrib,
                        $idOrder
                    );
                    if ($custProduct) {
                        $expected_date = strtotime($existingPreorder['expected_date']);
                        if ($custProduct['disallow_order'] == 1) {
                            $productDetails['preorder_status'] = 4; // preorder was cancelled
                        } elseif (($existingPreorder['is_preorder'] == 0
                            && $expected_date <= $currentDate
                            && $custProduct['preorder_complete'] == '0')
                            || ($custProduct['preorder_complete'] == '0'
                            && $existingPreorder['recreation_date'] > $custProduct['date_add'])
                        ) {
                            $productDetails['preorder_status'] = 1; // preorder not complated
                        } elseif ($custProduct['preorder_complete'] == '1') {
                            $productDetails['preorder_status'] = 2; // preorder completed
                        } else {
                            $productDetails['preorder_status'] = 0;   // not the case
                        }
                    } else {
                        $productDetails['preorder_status'] = 3; // customer not bought this preorder
                    }
                } else {
                    $productDetails['preorder_status'] = 3; // product was not in preorder
                }
            }
        }

        return $productDetails;
    }

    public function addEntryInCompletePreorderTable($idProduct, $idProductAttribute, $idOrder, $idCustomer, $idShop)
    {
        if (!$this->checkEntryExists($idProduct, $idProductAttribute, $idOrder, $idCustomer, $idShop)) {
            $data = [
                'customer_id' => (int) $idCustomer,
                'product_id' => (int) $idProduct,
                'attribute_id' => (int) $idProductAttribute,
                'old_order_id' => (int) $idOrder,
                'id_shop' => (int) $idShop,
            ];

            return Db::getInstance()->insert(
                'wk_preorder_completion_data',
                $data
            );
        }

        return true;
    }

    public function checkEntryExists($idProduct, $idProductAttribute, $idOrder, $idCustomer, $idShop)
    {
        $entry = Db::getInstance()->getRow(
            'SELECT * FROM `' . _DB_PREFIX_ . 'wk_preorder_completion_data`
            WHERE `old_order_id`= ' . (int) $idOrder . ' AND `customer_id` = ' . (int) $idCustomer .
            ' AND `product_id` = ' . (int) $idProduct . ' AND `attribute_id` = ' . (int) $idProductAttribute . ' AND `id_shop` = ' . (int) $idShop
        );
        if (!empty($entry)) {
            return $entry;
        }

        return false;
    }

    public function checkEntryExistsWithoutOrder($idProduct, $idProductAttribute, $idCustomer, $idShop)
    {
        $entry = Db::getInstance()->getRow(
            'SELECT * FROM `' . _DB_PREFIX_ . 'wk_preorder_completion_data`
            WHERE `customer_id` = ' . (int) $idCustomer .
            ' AND `product_id` = ' . (int) $idProduct . ' AND `attribute_id` = ' . (int) $idProductAttribute . ' AND `id_shop` = ' . (int) $idShop
        );
        if (!empty($entry)) {
            return $entry;
        }

        return false;
    }

    public function deteleTempCompletionEntry($idProduct, $idProductAttribute, $idCustomer, $idShop)
    {
        return Db::getInstance()->execute(
            'DELETE FROM `' . _DB_PREFIX_ . 'wk_preorder_completion_data`
            WHERE `customer_id` = ' . (int) $idCustomer .
            ' AND `product_id` = ' . (int) $idProduct . ' AND `attribute_id` = ' . (int) $idProductAttribute .
            ' AND `id_shop` = ' . (int) $idShop
        );
    }

    public static function getIdProductCustomerByOrderId($orderId)
    {
        return Db::getInstance()->executeS(
            'SELECT id_wk_preorder_product_customer FROM `' . _DB_PREFIX_ . 'wk_preorder_order_map` WHERE  `order_id`= ' . (int) $orderId
        );
    }

    public static function getPreorderDetailsByIdPreorderCustomer($idPreCust)
    {
        return Db::getInstance()->getRow(
            'SELECT * FROM `' . _DB_PREFIX_ . 'wk_preorder_product_customer` WHERE  `id_wk_preorder_product_customer`= ' . (int) $idPreCust
        );
    }

    public static function getPreorderDetailsByOrderId($orderId)
    {
        return Db::getInstance()->executeS(
            'SELECT * FROM `' . _DB_PREFIX_ . 'wk_preorder_product_customer` WHERE  `order_id`= ' . (int) $orderId
        );
    }

    public static function getOrderIdByIdPreCustomer($idPreCust)
    {
        return Db::getInstance()->getRow(
            'SELECT * FROM `' . _DB_PREFIX_ . 'wk_preorder_order_map` WHERE  `id_wk_preorder_product_customer`= ' . (int) $idPreCust
        );
    }
}
