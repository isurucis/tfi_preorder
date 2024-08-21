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

class AdminPreorderOrdersController extends ModuleAdminController
{
    public function __construct()
    {
        $this->identifier = 'id_wk_preorder_product_customer';
        parent::__construct();
        $this->bootstrap = true;
        $this->allow_export = true;
        $this->table = 'wk_preorder_product_customer';
        $this->className = 'PreorderProductCustomer';
        $this->_select = '
			a.order_id as id_order,
			a.`paid_amt` as paid_amt,
			ord.reference as reference,
			ord.payment as payment,
            CONCAT(c.`firstname`," ",c.`lastname`) as customer';
        $this->_join .= 'LEFT JOIN `' . _DB_PREFIX_ . 'orders` ord ON (a.`order_id` = ord.`id_order`) ';
        $this->_join .= 'LEFT JOIN `' . _DB_PREFIX_ . 'wk_preorder_product_customer_shop` wkpcs ON (wkpcs.`id_wk_preorder_product_customer` = a.`id_wk_preorder_product_customer` AND wkpcs.id_shop = ord.id_shop)';
        $this->_join .= 'LEFT JOIN `' . _DB_PREFIX_ . 'customer` c ON (ord.`id_customer` = c.`id_customer`) ';
        $this->_where = Shop::addSqlRestriction(Shop::SHARE_ORDER, 'ord');

        if (Shop::isFeatureActive()
        && (Shop::getContext() == Shop::CONTEXT_ALL || Shop::getContext() == Shop::CONTEXT_GROUP)
        ) {
            $this->_select .= ', sh.`name` as wk_preorder_order_shop_name';
            $this->_join .= ' LEFT JOIN `' . _DB_PREFIX_ . 'shop` sh ON (sh.`id_shop` = wkpcs.`id_shop`)';
        }

        $this->_group = ' GROUP BY a.id_wk_preorder_product_customer';
        $this->list_no_link = true;
        $this->_orderBy = 'date_add';
        $this->_orderWay = 'DESC';
        $this->fields_list = [
            'id_wk_preorder_product_customer' => [
                'title' => $this->l('ID', 'AdminPreorderOrders'),
                'align' => 'center',
                'havingFilter' => true,
            ],
            'reference' => [
                'title' => $this->l('Reference', 'AdminPreorderOrders'),
                'align' => 'center',
                'havingFilter' => true,
            ],
            'customer' => [
                'title' => $this->l('Customer', 'AdminPreorderOrders'),
                'align' => 'center',
                'havingFilter' => true,
            ],
            'original_price' => [
                'title' => $this->l('Total price', 'AdminPreorderOrders'),
                'align' => 'center',
                'type' => 'decimal',
                'currency' => true,
                'callback' => 'checkCurrency',
                'havingFilter' => true,
            ],
            'paid_amt' => [
                'title' => $this->l('Amount paid (tax excl)', 'AdminPreorderOrders'),
                'align' => 'center',
                'type' => 'decimal',
                'currency' => true,
                'callback' => 'checkCurrency',
                'havingFilter' => true,
            ],
            'tax_amt' => [
                'title' => $this->l('Tax paid', 'AdminPreorderOrders'),
                'align' => 'center',
                'type' => 'decimal',
                'callback' => 'checkCurrency',
                'currency' => true,
                'havingFilter' => true,
            ],
            'remaining_amt' => [
                'title' => $this->l('Remaining amount', 'AdminPreorderOrders'),
                'align' => 'center',
                'type' => 'decimal',
                'currency' => true,
                'callback' => 'checkCurrency',
                'havingFilter' => true,
            ],
            'preorder_complete' => [
                'title' => $this->l('Completed', 'AdminPreorderOrders'),
                'align' => 'center',
                'type' => 'bool',
                'callback' => 'preorderCompleted',
                'havingFilter' => true,
            ],
            'disallow_order' => [
                'title' => $this->l('Cancelled', 'AdminPreorderOrders'),
                'align' => 'center',
                'type' => 'bool',
                'callback' => 'preorderCancelled',
                'havingFilter' => true,
            ],
            'booked_date' => [
                'title' => $this->l('Date', 'AdminPreorderOrders'),
                'align' => 'center',
                'type' => 'date',
                'callback' => 'getBookedDate',
                'havingFilter' => true,
            ],
            'id_order' => [
                'title' => $this->l('Details', 'AdminPreorderOrders'),
                'align' => 'center',
                'callback' => 'getOrderDetails',
                'orderby' => false,
                'search' => false,
                'remove_onclick' => true,
                'havingFilter' => true,
            ],
        ];
        if (Shop::isFeatureActive()
        && (Shop::getContext() == Shop::CONTEXT_ALL || Shop::getContext() == Shop::CONTEXT_GROUP)) {
            $this->fields_list['wk_preorder_order_shop_name'] = [
                'title' => $this->l('Shop', 'AdminPreorderOrders'),
                'havingFilter' => true,
                'align' => 'center',
                'orderby' => false,
            ];
        }
    }

    public function getBookedDate($date)
    {
        return date('d/m/Y H:m:s', strtotime($date));
    }

    public function checkCurrency($val, $arr)
    {
        $order = new Order($arr['order_id']);
        $currency = new Currency((int) $order->id_currency);

        return PreorderHelper::displayPrice(Tools::convertPrice($val, $currency), $currency);
    }

    public function initToolbar()
    {
        parent::initToolbar();
        unset($this->toolbar_btn['new']);
    }

    public function processExport($text_delimiter = '"')
    {
        // Prevent "Details" column to export
        unset($this->fields_list['id_order']);
        parent::processExport($text_delimiter);
    }

    public function getOrderDetails($idOrder, $arr)
    {
        $otherorderIDs = [];
        if ($orderIDs = PreorderProductCustomer::getOrderIDs($arr['id_wk_preorder_product_customer'])) {
            foreach ($orderIDs as $orderId) {
                $otherorderIDs[] = [
                    'id_order' => $orderId['order_id'],
                    'reference' => Order::getUniqReferenceOf($orderId['order_id']),
                    'order_page_link' => $this->context->link->getAdminLink(
                        'AdminOrders',
                        true,
                        [],
                        [
                            'id_order' => $orderId['order_id'],
                            'vieworder' => '1',
                            'conf' => '4',
                        ]
                    ),
                ];
            }
        }
        $this->context->smarty->assign([
            'orderlink' => $this->context->link->getAdminLink(
                'AdminOrders',
                true,
                [],
                [
                    'id_order' => $idOrder,
                    'vieworder' => '1',
                    'conf' => '4',
                ]
            ),
            'other_order_ids' => $otherorderIDs,
        ]);

        return $this->createTemplate('get_order_details.tpl')->fetch();
    }

    public function preorderCompleted($val)
    {
        if ($val == '1') {
            return $this->l('Yes', 'AdminPreorderOrders');
        } else {
            return $this->l('No', 'AdminPreorderOrders');
        }
    }

    public function preorderCancelled($val)
    {
        if ($val == '1') {
            return $this->l('Yes', 'AdminPreorderOrders');
        } else {
            return $this->l('No', 'AdminPreorderOrders');
        }
    }
}
