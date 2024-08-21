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

class AdminNewPreorderController extends ModuleAdminController
{
    public function __construct()
    {
        $this->identifier = 'id_wk_preorder_product';
        parent::__construct();
        $this->bootstrap = true;
        $this->table = 'wk_preorder_product';
        $this->className = 'PreorderProduct';
        $this->_select = 'a.id_wk_preorder_product as temp_id, CONCAT(" (", pl.`id_product`, ") ", pl.`name` ) as `product_name`,
        wk_preorder_product_shop.original_price + wk_preorder_product_shop.impact_price as wk_original_price,
            IF(wk_preorder_product_shop.maxquantity > wk_preorder_product_shop.prebooked_quantity,
            wk_preorder_product_shop.maxquantity - wk_preorder_product_shop.prebooked_quantity, 0) as aval_quantity,
            pr.`reference` as `prod_reference`';
        $this->_select .= ', wk_preorder_product_shop.*, wk_preorder_product_shop.`is_preorder` as is_preorder,
            wk_preorder_product_shop.`is_auto_available` as is_auto_available';
        Shop::addTableAssociation(
            'wk_preorder_product',
            [
                'type' => 'shop',
                'primary' => 'id_wk_preorder_product',
            ]
        );
        $this->_join .= Shop::addSqlAssociation('wk_preorder_product', 'a', false);
        $this->_join .= 'LEFT JOIN `' . _DB_PREFIX_ . 'product` pr ON (pr.`id_product` = a.`product_id`)';
        $this->_join .= 'LEFT JOIN `' . _DB_PREFIX_ . 'product_lang` pl ON (pl.`id_product` = pr.`id_product`)';
        $this->_join .= 'LEFT JOIN `' . _DB_PREFIX_ . 'wk_preorder_product_shop` plsh
            ON (plsh.`id_wk_preorder_product` = a.`id_wk_preorder_product`)';
        $this->_group = ' GROUP BY a.id_wk_preorder_product';
        $this->_where = ' AND pl.`id_lang` = ' . (int) Context::getContext()->language->id;

        if (!Shop::isFeatureActive() || Shop::getContext() !== Shop::CONTEXT_SHOP) {
            // In case of All Shops
            $this->_where .= ' AND plsh.id_shop IN (' . implode(',', Shop::getContextListShopID()) . ')';
        } else {
            $this->_where .= ' AND plsh.`id_shop` = ' . (int) $this->context->shop->id;
        }

        if (Shop::isFeatureActive()
        && (Shop::getContext() == Shop::CONTEXT_ALL || Shop::getContext() == Shop::CONTEXT_GROUP)
        ) {
            $this->_select .= ', sh.`name` as wk_preorder_product_shop_name';
            $this->_join .= ' LEFT JOIN `' . _DB_PREFIX_ . 'shop` sh ON (sh.`id_shop` = plsh.`id_shop`)';
        }

        $this->list_no_link = true;
        $paymentType = [
            1 => $this->l('Full payment'),
            2 => $this->l('Partially'),
            3 => $this->l('Dynamic'),
        ];
        $paymentMethod = [
            0 => $this->l('N/a'),
            1 => $this->l('Percentage'),
            2 => $this->l('Fixed amount'),
        ];

        $this->fields_list = [
            'temp_id' => [
                'title' => $this->l('ID'),
                'align' => 'center',
                'callback' => 'addCollapseIcon',
                'havingFilter' => true,
            ],
            'product_name' => [
                'title' => $this->l('(ID) Product name'),
                'align' => 'center',
                'callback' => 'displayCombinationName',
                'havingFilter' => true,
            ],
            'prod_reference' => [
                'title' => $this->l('Reference'),
                'align' => 'center',
                'havingFilter' => true,
                'search' => false,
                'callback' => 'displayProductReferenceWithCombination',
            ],
            'payment_type' => [
                'title' => $this->l('Payment type'),
                'align' => 'center',
                'type' => 'select',
                'list' => $paymentType,
                'filter_key' => 'wk_preorder_product_shop!payment_type',
                'callback' => 'paymentTypeCheck',
                'currency' => true,
                'havingFilter' => true,
            ],
            'preorder_price' => [
                'title' => $this->l('Preorder price'),
                'align' => 'center',
                'type' => 'price',
                'callback' => 'preorderPrice',
                'currency' => true,
                'search' => false,
                'orderby' => false,
            ],
            'aval_quantity' => [
                'title' => $this->l('Available quantity'),
                'align' => 'center',
                'havingFilter' => true,
            ],
            'expected_date' => [
                'title' => $this->l('Available date'),
                'filter_key' => 'wk_preorder_product_shop!expected_date',
                'align' => 'center',
                'type' => 'date',
                'callback' => 'getExpectedDate',
                'havingFilter' => true,
            ],
            'is_auto_available' => [
                'title' => $this->l('Timer'),
                'align' => 'center',
                'active' => 'isAutoAvailable',
                'type' => 'bool',
                'filter_key' => 'wk_preorder_product_shop!is_auto_available',
                'callback' => 'automaticStatus',
                'havingFilter' => true,
            ],
            'is_preorder' => [
                'title' => $this->l('Status'),
                'active' => 'isPreorder',
                'align' => 'center',
                'filter_key' => 'wk_preorder_product_shop!is_preorder',
                'type' => 'bool',
                'havingFilter' => true,
            ],
        ];
        if (Shop::isFeatureActive()
        && (Shop::getContext() == Shop::CONTEXT_ALL || Shop::getContext() == Shop::CONTEXT_GROUP)) {
            $this->fields_list['wk_preorder_product_shop_name'] = [
                'title' => $this->l('Shop'),
                'havingFilter' => false,
                'align' => 'center',
                'orderby' => false,
            ];
        }

        $this->bulk_actions = [
            'enableSelection' => [
                'text' => $this->l('Enable selection'),
                'icon' => 'icon-power-off text-success',
            ],
            'disableSelection' => [
                'text' => $this->l('Disable selection'),
                'icon' => 'icon-power-off text-danger',
            ],
            'delete' => [
                'text' => $this->l('Delete selected'),
                'icon' => 'icon-trash',
                'confirm' => $this->l('Delete selected items?'),
            ],
        ];
    }

    public function getExpectedDate($date)
    {
        return date('d/m/Y H:m:s', strtotime($date));
    }

    public function addCollapseIcon($val, $arr)
    {
        if ($val) {
            $this->context->smarty->assign([
                'preorder_product_list' => $arr,
                'preorder_controller' => $this->context->link->getAdminLink('AdminNewPreorder'),
            ]);

            return $this->context->smarty->fetch(
                _PS_MODULE_DIR_ . 'preorder/views/templates/admin/display_collapse_icon.tpl'
            );
        }
    }

    public function ajaxProcessDisplayPreorderPreview()
    {
        if ($id = Tools::getValue('id')) {
            $idShop = PreorderProduct::getPreorderProductIdShop($id);
            $preorderObj = new PreorderProduct((int) $id, $this->context->language->id, $idShop);
            $productobj = new Product(
                (int) $preorderObj->product_id,
                false,
                $this->context->language->id,
                $idShop
            );
            $preorder = [];
            $preorder = (array) $preorderObj;
            $price = '';
            if ($preorder['payment_type'] == 1) {
                $price = $preorder['original_price'];
            } elseif ($preorder['payment_type'] == 2 || $preorder['payment_type'] == 3) {
                if ($preorder['payment_method'] == 1) {
                    $price = Tools::ps_round(($preorder['original_price'] * $preorder['preorder_price']) / 100, 2);
                } elseif ($preorder['payment_method'] == 2) {
                    $price = $preorder['preorder_price'];
                }
            }
            $preorder['original_price'] = PreorderHelper::displayPrice(Tools::convertPrice($preorderObj->original_price), $this->context->currency);
            $preorder['preorder_price'] = PreorderHelper::displayPrice(Tools::convertPrice($price), $this->context->currency);
            $qty = 0;
            if ($preorder['maxquantity'] > $preorder['prebooked_quantity']) {
                $qty = $preorder['maxquantity'] - $preorder['prebooked_quantity'];
            }
            $preorder['aval_quantity'] = $qty;
            $preorder['productRef'] = $this->displayProductReferenceWithCombination($productobj->reference, $preorder);
            $preorder['payment_type'] = $this->paymentTypeCheck($preorder['payment_type']);
            $preorder['payment_method'] = $this->paymentMethodCheck($preorder['payment_method']);
            $this->context->smarty->assign([
                'preorder_product_list' => (array) $preorder,
                'productImage' => Image::getCover($preorderObj->product_id) && Image::getCover($preorderObj->product_id)['id_image'] ? $this->context->link->getImageLink(
                    $productobj->link_rewrite,
                    Image::getCover($preorderObj->product_id)['id_image'],
                    ImageType::getFormattedName('home')
                ) : _MODULE_DIR_ . $this->module->name . '/views/img/home-default.jpg',
                'productName' => Product::getProductName($preorderObj->product_id, $preorderObj->attribute_id, $this->context->language->id),
            ]);
            echo $this->context->smarty->fetch(
                _PS_MODULE_DIR_ . $this->module->name . '/views/templates/admin/preorderpreview_admin.tpl'
            );
            exit;
        }
    }

    public function displayProductReferenceWithCombination($productRef, $arr)
    {
        if ($arr['attribute_id']) { // if specific combination is a preorder
            $objCombi = new Combination($arr['attribute_id']);
            if ($objCombi->reference) {
                return $objCombi->reference; // if combination has reference
            } else {
                return $productRef; // product reference
            }
        } else {
            if ($productRef) {
                return $productRef;
            } else {
                return '-';
            }
        }
    }

    /**
     * [displayCombinationName - making string for combination].
     *
     * @param [type] $val [current value of database]
     * @param [type] $arr [current row of database]
     *
     * @return [type] [description]
     */
    public function displayCombinationName($val, $arr)
    {
        if ($arr['attribute_id']) {
            if ($val) {
                $idProduct = $arr['product_id'];
                $idAttr = $arr['attribute_id'];
                $name = $this->combinationName($idProduct, $idAttr);

                return $val . ' ' . $name;
            }
        }

        return $val;
    }

    /**
     * [automaticStatus - showing string "on" & "off" in case of status yes or no].
     *
     * @param [type] $val [current value either 1 or 0]
     *
     * @return [type] [description]
     */
    public function automaticStatus($val)
    {
        if ($val) {
            return $this->l('On');
        } else {
            return $this->l('Off');
        }
    }

    /**
     * [paymentTypeCheck - showing string in case of full payment or partial payment].
     *
     * @param [type] $val [current value either 1 2 or 3]
     *
     * @return [type] [description]
     */
    public function paymentTypeCheck($val)
    {
        if ($val == 1) {
            return $this->l('Full payment');
        } elseif ($val == 2) {
            return $this->l('Partially');
        } elseif ($val == 3) {
            return $this->l('Dynamic');
        }
    }

    public function paymentMethodCheck($val)
    {
        if ($val == 0) {
            return '';
        } elseif ($val == 1) {
            return $this->l('Percentage');
        } elseif ($val == 2) {
            return $this->l('Fixed amount');
        }
    }

    /**
     * [preorderPrice - calculating the price percentage in amound to show in list].
     *
     * @param [type] $val [current value]
     * @param [type] $arr [current row of database]
     *
     * @return [type] [description]
     */
    public function preorderPrice($val, $arr)
    {
        if ($val) {
            $price = '';
            if ($arr['payment_type'] == 1) {
                $price = $arr['original_price'];
            } elseif ($arr['payment_type'] == 2 || $arr['payment_type'] == 3) {
                if ($arr['payment_method'] == 1) {
                    $price = Tools::ps_round(($arr['original_price'] * $arr['preorder_price']) / 100, 2);
                } elseif ($arr['payment_method'] == 2) {
                    $price = $arr['preorder_price'];
                }
            }

            return PreorderHelper::displayPrice($price, $this->context->currency);
        } else {
            return false;
        }
    }

    /**
     * [combinationName - making combination name].
     *
     * @param [type] $idProduct [product id]
     * @param [type] $idAttr    [attribute id]
     *
     * @return [type] [description]
     */
    public function combinationName($idProduct, $idAttr)
    {
        $name = '';
        // $allAttr = array_map('unserialize', array_unique(
        //     array_map('serialize', Product::getAttributesParams($idProduct, $idAttr))
        // ));
        $allAttr = array_values(array_unique(Product::getAttributesParams($idProduct, $idAttr), SORT_REGULAR));
        if ($allAttr) {
            foreach ($allAttr as $attr) {
                if (!$name) {
                    $name = $attr['group'] . ' - ' . $attr['name'];
                } else {
                    $name = $name . ' , ' . $attr['group'] . ' - ' . $attr['name'];
                }
            }
        } else {
            $name = $this->l('Default');
        }

        return $name;
    }

    public function initContent()
    {
        if (($this->display == 'edit') && (Shop::getContext() == Shop::CONTEXT_SHOP)) {
            if (!$this->loadObject(true)) {
                Tools::redirectAdmin(self::$currentIndex . '&token=' . $this->token);
            }
        }

        return parent::initContent();
    }

    public function initToolbar()
    {
        parent::initToolbar();

        if ($this->display == 'add') {
            $this->toolbar_title = $this->l('Create');
            $this->page_header_toolbar_btn['back_to_list'] = [
                'href' => self::$currentIndex . '&token=' . $this->token,
                'desc' => $this->l('Back to list'),
                'icon' => 'process-icon-back',
            ];
        } elseif ($this->display == 'edit') {
            $this->toolbar_title = $this->l('Update');
            $this->page_header_toolbar_btn['back_to_list'] = [
                'href' => self::$currentIndex . '&token=' . $this->token,
                'desc' => $this->l('Back to list'),
                'icon' => 'process-icon-back',
            ];
            if (!$this->loadObject(true) && (Shop::getContext() == Shop::CONTEXT_SHOP)) {
                Tools::redirectAdmin(self::$currentIndex . '&token=' . $this->token);
            }
        } else {
            $this->page_header_toolbar_btn['config_page'] = [
                'href' => $this->context->link->getAdminLink(
                    'AdminModules',
                    false
                ) . '&configure=' . $this->module->name . '&module_name=' . $this->module->name . '&token=' . Tools::getAdminTokenLite('AdminModules'),
                'desc' => $this->l('Configuration'),
                'icon' => 'process-icon-cogs',
            ];
            if (Shop::getContext() == Shop::CONTEXT_SHOP) {
                $this->page_header_toolbar_btn['new'] = [
                    'href' => self::$currentIndex . '&add' . $this->table . '&token=' . $this->token,
                    'desc' => $this->l('Add new'),
                ];
            }
        }
    }

    public function renderList()
    {
        $this->addRowAction('preview');
        if (Shop::getContext() == Shop::CONTEXT_SHOP) {
            $this->addRowAction('edit');
        }
        $this->addRowAction('delete');

        return parent::renderList();
    }

    public function displayPreviewLink($token, $id)
    {
        if ($token) {
            $idShop = PreorderProduct::getPreorderProductIdShop($id);
            $preorderObj = new PreorderProduct($id, null, $idShop);
            $idProduct = $preorderObj->product_id;
            $product = new Product($idProduct, null, $this->context->language->id);
            $productLink = $this->context->link->getProductLink(
                $product,
                $product->link_rewrite,
                Category::getLinkRewrite($product->id_category_default, $this->context->language->id),
                null,
                null,
                $idShop,
                $preorderObj->attribute_id
            );
            $this->context->smarty->assign('productlink', $productLink);

            return $this->context->smarty->fetch(_PS_MODULE_DIR_ . 'preorder/views/templates/admin/preorder_preview.tpl');
        }
    }

    public function renderForm()
    {
        if (Shop::isFeatureActive() && Shop::getContext() !== Shop::CONTEXT_SHOP) {
            return $this->context->smarty->fetch(
                _PS_MODULE_DIR_ . $this->module->name . '/views/templates/admin/_partials/shop_warning.tpl'
            );
        }

        $idLang = $this->context->language->id;
        $allProduct = Product::getProducts($idLang, 1, 10, 'id_product', 'ASC');
        if ($allProduct) {
            $this->context->smarty->assign('all_product', $allProduct);
        }
        $this->context->smarty->assign([
            'img_ps_dir' => _PS_IMG_,
            'currency' => $this->context->currency,
            'currentTime' => date('Y-m-d H:s:m'),
        ]);
        if ($this->display == 'add') {
        } elseif ($this->display == 'edit') {
            $id = Tools::getValue('id_wk_preorder_product');
            $objPreorder = new PreOrderProduct($id);
            if ($objPreorder) {
                $idProduct = $objPreorder->product_id;
                $idAttr = $objPreorder->attribute_id;
                if ($objPreorder->is_preorder) {
                    $originalPriceWithImpact = $objPreorder->original_price + $objPreorder->impact_price;
                } else {
                    $originalPriceWithImpact = Product::getPriceStatic($idProduct, false, $idAttr);
                }
                $prodName = PreOrderProduct::getPsProductName($idProduct, $idAttr, $idLang);
                $attrName = $this->combinationName($idProduct, $idAttr);
                $this->context->smarty->assign(
                    [
                        'obj_preorder' => $objPreorder,
                        'expectedTimeStamp' => strtotime($objPreorder->expected_date),
                        'currentTimeStamp' => strtotime(date('Y-m-d H:i:s')),
                        'prod_name' => $prodName,
                        'attr_name' => $attrName,
                        'original_price_with_impact' => (float) $originalPriceWithImpact,
                    ]
                );
            }
        }
        $this->fields_form = [
            'submit' => [
                'title' => $this->l('Save'),
            ],
        ];

        return parent::renderForm();
    }

    public function postProcess()
    {
        PreOrderProduct::autoUpdateAllPreorder(); // updating existing preorder products into database

        $objPreorder = new PreOrderProduct();
        $preorderCarrier = $objPreorder->getPreorderShipping();
        if (Tools::isSubmit('isAutoAvailable' . $this->table)) {
            $id = Tools::getValue('id_wk_preorder_product');
            if ($id) {
                $idShop = PreorderProduct::getPreorderProductIdShop($id);
                $objPreorder = new PreOrderProduct($id, null, $idShop);
                if ($objPreorder->is_auto_available == 1) {
                    $objPreorder->is_auto_available = 0;
                } else {
                    $objPreorder->is_auto_available = 1;
                }
                $objPreorder->update();
                Tools::redirectAdmin(self::$currentIndex . '&conf=5&token=' . $this->token);
            }
        } elseif (Tools::isSubmit('isPreorder' . $this->table)) {
            $id = Tools::getValue('id_wk_preorder_product');
            $appliedCarrier = [];
            if ($id) {
                $idShop = PreorderProduct::getPreorderProductIdShop($id);
                $objPreorder = new PreOrderProduct($id, null, $idShop);
                if ($objPreorder->is_preorder) {
                    $this->deactivePreorderProduct($objPreorder);
                } else {
                    $objProduct = new Product($objPreorder->product_id);
                    $appliedCarriers = $objProduct->getCarriers();
                    if (!empty($appliedCarriers)) {
                        foreach ($appliedCarriers as $carrier) {
                            $appliedCarrier[] = $carrier['id_reference'];
                        }
                        $appliedCarrier = json_encode($appliedCarrier);
                    } else {
                        $appliedCarrier = 0;         // all carries are applied
                    }
                    $this->activePreorderProduct($objPreorder, $preorderCarrier, $appliedCarrier);
                }
                if (empty($this->context->controller->errors)) {
                    Tools::redirectAdmin(self::$currentIndex . '&conf=5&token=' . $this->token);
                }
            }
        }
        parent::postProcess();
    }

    public function processSave()
    {
        $id = Tools::getValue('id');
        $objPreorder = new PreOrderProduct();
        if (Tools::isSubmit('submitAdd' . $this->table) || Tools::isSubmit('submitAdd' . $this->table . 'AndStay')) {
            if ($id) {
                $wkconf = 4;
            } else {
                $wkconf = 3;
            }
            $idAttr = Tools::getValue('pre-attr');
            $idProduct = Tools::getValue('pre_product_id');
            // Option for all the combinations of the product is selected
            if ($idAttr == 'all') {
                if ($id) {
                    $preorderProduct = new PreOrderProduct();
                    $attributes = $preorderProduct->getAllPreorderAttributesByProductId($idProduct);
                    if ($attributes) {
                        foreach ($attributes as $value) {
                            $comObj = new Combination($value['attribute_id']);
                            if (isset($comObj->id)) {
                                $this->preorderDataProcessAndSave(
                                    $value['id_wk_preorder_product'],
                                    $value['attribute_id']
                                );
                            }
                        }
                        if (Tools::isSubmit('submitAdd' . $this->table . 'AndStay')) {
                            Tools::redirectAdmin(
                                self::$currentIndex . '&id_wk_preorder_product=' . (int) $id . '&update' . $this->table .
                                '&conf=' . (int) $wkconf . '&token=' . $this->token
                            );
                        } else {
                            Tools::redirectAdmin(self::$currentIndex . '&conf=' . (int) $wkconf . '&token=' . $this->token);
                        }
                    }
                } else {
                    $attributes = Product::getProductAttributesIds($idProduct, false);
                    if ($attributes) {
                        foreach ($attributes as $value) {
                            $idAttr = $value['id_product_attribute'];
                            if (!$objPreorder->getExistingPreOrderProduct($idProduct, $idAttr)) {
                                $this->preorderDataProcessAndSave(0, $idAttr);
                            }
                        }
                    } else {
                        $this->preorderDataProcessAndSave();
                    }
                    if (Tools::isSubmit('submitAdd' . $this->table . 'AndStay')) {
                        Tools::redirectAdmin(
                            self::$currentIndex . '&id_wk_preorder_product=' . (int) $id . '&update' . $this->table .
                            '&conf=' . (int) $wkconf . '&token=' . $this->token
                        );
                    } else {
                        Tools::redirectAdmin(self::$currentIndex . '&conf=' . (int) $wkconf . '&token=' . $this->token);
                    }
                }
            } else { // if preorder is set for a specific combination...
                if ($id) {
                    $objPreorder = new PreOrderProduct($id);
                    $idAttr = $objPreorder->attribute_id;
                }
                $this->preorderDataProcessAndSave($id, $idAttr);
                if (empty($this->errors)) {
                    if (Tools::isSubmit('submitAdd' . $this->table . 'AndStay')) {
                        Tools::redirectAdmin(
                            self::$currentIndex . '&id_wk_preorder_product=' . (int) $id . '&update' . $this->table .
                            '&conf=' . (int) $wkconf . '&token=' . $this->token
                        );
                    } else {
                        Tools::redirectAdmin(self::$currentIndex . '&conf=' . (int) $wkconf . '&token=' . $this->token);
                    }
                }
            }
        }
    }

    /**
     * [preorderDataProcessAndSave saves data of preorder for a combination].
     *
     * @param int $id [id of the preorder if updating the preorder]
     * @param int $idAttr [id_attribute of the product]
     *
     * @return [type] [description]
     */
    public function preorderDataProcessAndSave($id = 0, $idAttr = 0)
    {
        $preorderProduct = new PreOrderProduct();
        if ($id) {
            $idShop = PreorderProduct::getPreorderProductIdShop($id);
            $objPreorder = new PreOrderProduct((int) $id, null, $idShop);
        }
        $impactPrice = 0;
        $idLang = $this->context->language->id;
        $preorderEnable = Tools::getValue('preorder_enable');
        $originalPriceWithImpact = Tools::getValue('preorder_originalprice');
        $paymentType = (int) Tools::getValue('preorder_payment_type'); // full, partially, dynamic
        $paymentMethod = (int) Tools::getValue('preorder_partial_type'); // 1. Percentage and 2. Amount
        $preorderPrice = trim(Tools::getValue('preorder_price'));
        $expectedDate = Tools::getValue('expected_date');
        // $expectedDate = '2020-01-27 10:04:21';
        $autoAvailable = Tools::getValue('auto_on');
        $quantity = (int) Tools::getValue('quantity');
        $maxquantity = (int) Tools::getValue('maxquantity');
        $preorderCarrier = $preorderProduct->getPreorderShipping();
        $idProduct = Tools::getValue('pre_product_id');
        if ($idAttr) {
            $impactPrice = $preorderProduct->getImpactPrice($idProduct, $idAttr);
            if (!$impactPrice) {
                $this->context->controller->errors[] = $this->l('Product with selected combination no longer belongs to the shop.');

                return;
            }
            if ($impactPrice) {
                $impactPrice = $impactPrice['price'];
            }
        }
        if ($paymentType == 1) {
            $paymentMethod = 0;
        }
        $objProduct = new Product($idProduct, false, $idLang);

        if (!Validate::isLoadedObject($objProduct) || !$objProduct->active) {
            $this->context->controller->errors[] = $this->l('Selected product no longer belongs to the shop.');

            return;
        }

        $idTaxRulesGroup = $objProduct->id_tax_rules_group;
        $originalPrice = $objProduct->price;
        $existingPreorder = $preorderProduct->getExistingPreOrderProduct($idProduct, $idAttr);

        $existActivePreorder = $preorderProduct->checkExsitingPsActivePreorder($idProduct);
        if ($existActivePreorder) {
            $appliedCarrier = $existActivePreorder['id_applied_shipping'];
        } else {
            $appliedCarriers = $objProduct->getCarriers();
            if (!empty($appliedCarriers)) {
                $appliedCarrier = [];
                foreach ($appliedCarriers as $carrier) {
                    $appliedCarrier[] = $carrier['id_reference'];
                }
                $appliedCarrier = json_encode($appliedCarrier);
            } else {
                $appliedCarrier = 0; // all carries are applied
            }
        }

        $expectedDateStamp = strtotime($expectedDate);
        $currentDateStamp = strtotime(date('Y-m-d H:i:s'));

        $isError = $this->validatePreorderField(
            $paymentType,
            $paymentMethod,
            $originalPriceWithImpact,
            $preorderPrice,
            $expectedDate,
            $quantity,
            $maxquantity
        );
        if (empty($isError) && empty(count($this->errors))) {
            if ($preorderEnable == '1' && empty($existingPreorder)) { // new preorder product
                // if all the field are set well then add pre-order product details into database
                $productLang = $preorderProduct->getLangDisplayName($idProduct);
                if ($productLang) {
                    $preorderProduct->product_lang = $productLang['available_now'];
                }

                $preorderProduct->product_id = $idProduct;
                $preorderProduct->attribute_id = $idAttr;
                if (Tools::getValue('pre-attr') == 'all') {
                    $preorderProduct->original_price = $originalPrice + $impactPrice;
                } else {
                    $preorderProduct->original_price = $originalPrice;
                }
                $preorderProduct->impact_price = $impactPrice;
                $preorderProduct->payment_type = $paymentType;
                $preorderProduct->payment_method = $paymentMethod;
                if (Tools::getValue('pre-attr') == 'all') {
                    $preorderProduct->preorder_price = $preorderPrice + $impactPrice;
                } else {
                    $preorderProduct->preorder_price = $preorderPrice;
                }
                $preorderProduct->is_auto_available = $autoAvailable;
                $preorderProduct->id_default_currency = Configuration::get('PS_CURRENCY_DEFAULT');
                $preorderProduct->quantity = $quantity;
                $preorderProduct->maxquantity = $maxquantity;
                $preorderProduct->prebooked_quantity = 0;
                $preorderProduct->expected_date = $expectedDate;
                $preorderProduct->is_preorder = $preorderEnable;
                $preorderProduct->id_tax_rules_group = $idTaxRulesGroup;
                $preorderProduct->id_applied_shipping = $appliedCarrier;
                if ($preorderProduct->save()) {
                    if ($paymentType == '1') { // full payment
                        $reducePrice = 0;
                        $preorderPrice = $originalPrice;
                        $preorderProduct->setPreorderPrice($idProduct, $idAttr, $preorderPrice, $paymentType);
                    } elseif ($paymentType == '2' || $paymentType == '3') { // partially and dynamic
                        if ($paymentMethod == '1') {   // percentage
                            $preorderPrice = (($originalPrice + $impactPrice) * $preorderPrice) / 100;
                            $reducePrice = $preorderPrice - $originalPrice;
                        } elseif ($paymentMethod == '2') {    // fixed amount
                            $reducePrice = $preorderPrice - $originalPrice;
                        }
                    }
                    if ($paymentType == '2') { // partially preorder
                        $preorderProduct->setPreorderPrice($idProduct, $idAttr, $preorderPrice, $paymentType);
                        $preorderProduct->setImpactPrice($idProduct, $idAttr, $reducePrice);
                        $preorderProduct->removeCarriers($idProduct);
                        $objProduct->setCarriers($preorderCarrier);
                    }

                    if ($paymentType == '3') { // dynamic payment
                        $preorderProduct->removeCarriers($idProduct);
                        $objProduct->setCarriers($preorderCarrier);
                        $preorderProduct->setPreorderPrice($idProduct, $idAttr, $preorderPrice, $paymentType);
                    }
                }
            } elseif ($preorderEnable == '0' && !empty($existingPreorder) && $currentDateStamp < $expectedDateStamp) {
                // preorder is disabled and current date is less than expected date of the product
                // if all the field are set well then add pre-order product details into database
                if ($existingPreorder['id_wk_preorder_product']) {
                    $preorderProduct = new PreOrderProduct($existingPreorder['id_wk_preorder_product']);
                }
                $this->updateExistPreorderProduct(
                    $preorderProduct,
                    $paymentType,
                    $paymentMethod,
                    $preorderPrice,
                    $expectedDate,
                    $preorderEnable,
                    $autoAvailable,
                    $originalPrice,
                    $idProduct,
                    $idAttr,
                    $preorderCarrier,
                    $idLang,
                    $quantity,
                    $maxquantity,
                    $appliedCarrier
                );
                $idShop = PreorderProduct::getPreorderProductIdShop($existingPreorder['id_wk_preorder_product']);
                $this->deactivePreorderProduct(new PreOrderProduct($existingPreorder['id_wk_preorder_product'], null, $idShop));
            } elseif (!empty($existingPreorder)) { // editing preorder existing preorder product
                // if all the field are set well then add pre-order product details into database
                if ($existingPreorder['id_wk_preorder_product']) {
                    $preorderProduct = new PreOrderProduct($existingPreorder['id_wk_preorder_product']);
                    if (!isset($originalPrice) || $preorderProduct->is_preorder) {
                        $originalPrice = $preorderProduct->original_price;
                    }
                }
                if ($preorderEnable == '0') {
                    if (isset($objPreorder) && $objPreorder) {
                        $this->deactivePreorderProduct($objPreorder);
                    }
                }
                $this->updateExistPreorderProduct(
                    $preorderProduct,
                    $paymentType,
                    $paymentMethod,
                    $preorderPrice,
                    $expectedDate,
                    $preorderEnable,
                    $autoAvailable,
                    $originalPrice,
                    $idProduct,
                    $idAttr,
                    $preorderCarrier,
                    $idLang,
                    $quantity,
                    $maxquantity,
                    $appliedCarrier
                );
            }
        } else {
            if ($id) {
                $this->display = 'edit';
            } else {
                $this->display = 'add';
            }
        }
    }

    public function activePreorderProduct($objPreorder, $preorderCarrier, $appliedCarrier)
    {
        $objPre = new PreOrderProduct();
        $idProduct = $objPreorder->product_id;
        $idAttr = $objPreorder->attribute_id;
        // $id_tax = $objPreorder->id_tax_rules_group;
        $expectedDateStamp = strtotime($objPreorder->expected_date);
        $currentDateStamp = strtotime(date('Y-m-d H:i:s'));
        $originalPrice = $objPreorder->original_price;
        if ($expectedDateStamp < $currentDateStamp) {
            $this->context->controller->errors[] = $this->l('Availablity date must be greater than current date and time.');
        }
        if (empty($this->context->controller->errors)) {
            $objPreorder->is_preorder = 1;
            $objPreorder->id_applied_shipping = $appliedCarrier;
            if ($objPreorder->update()) {
                if (Shop::CONTEXT_ALL == Shop::getContext()) {
                    StockAvailable::setQuantity($idProduct, $idAttr, 0, null, false);
                } else {
                    StockAvailable::setQuantity($idProduct, $idAttr, 0);
                }
                $paymentType = $objPreorder->payment_type;
                $paymentMethod = $objPreorder->payment_method;
                if ($paymentType == '1') {
                    $reducePrice = 0;
                    $preorderPrice = $originalPrice;
                    $objPre->setPreorderPrice($idProduct, $idAttr, $preorderPrice, $paymentType);
                } elseif ($paymentType == '2' || $paymentType == '3') {
                    if ($paymentMethod == '1') {
                        $preorderPrice = ($originalPrice * $objPreorder->preorder_price) / 100;
                        $reducePrice = $preorderPrice - $originalPrice;
                    } elseif ($paymentMethod == '2') {
                        $preorderPrice = $objPreorder->preorder_price;
                        $reducePrice = $objPreorder->preorder_price - $originalPrice;
                    }
                }
                if ($paymentType == '2') { // partially preorder
                    $objPre->removeCarriers($idProduct);
                    $objPre->setCarriers($idProduct, $preorderCarrier);
                    $objPre->setImpactPrice($idProduct, $idAttr, $reducePrice);
                    $objPre->setPreorderPrice($idProduct, $idAttr, $preorderPrice, $paymentType);
                }
                if ($paymentType == '3') { // dynamic preorder
                    $objPre->removeCarriers($idProduct);
                    $objPre->setCarriers($idProduct, $preorderCarrier);
                    $objPre->setPreorderPrice($idProduct, $idAttr, $preorderPrice, $paymentType);
                }
                StockAvailable::setProductOutOfStock($idProduct, true, null, $idAttr);
            }
        }
    }

    public function deactivePreorderProduct($objPreorder)
    {
        $idProduct = $objPreorder->product_id;
        $originalPrice = $objPreorder->original_price;
        $idAttr = $objPreorder->attribute_id;

        if ($objPreorder->id_applied_shipping !== '0') {
            $objPreorder->setCarriers($idProduct, $objPreorder->id_applied_shipping);
        } else {
            $objPreorder->removeCarriers($idProduct);
        }
        // new quantity update
        $newQuantity = 0;
        if ((int) $objPreorder->quantity >= (int) $objPreorder->prebooked_quantity) {
            $newQuantity = (int) $objPreorder->quantity - (int) $objPreorder->prebooked_quantity;
        } else {
            $newQuantity = $objPreorder->quantity;
        }
        if (Shop::CONTEXT_ALL == Shop::getContext()) {
            StockAvailable::setQuantity($idProduct, $idAttr, $newQuantity, null, false); // update quantity
        } else {
            StockAvailable::setQuantity($idProduct, $idAttr, $newQuantity); // update quantity
        }
        if ($objPreorder->payment_type != 1) {
            if ($idAttr == 0) {
                $objPreorder->setOriginalProduct($idProduct, $originalPrice, $newQuantity, $idAttr);
            } else {
                $objPreorder->setImpactPrice($idProduct, $idAttr, $objPreorder->impact_price);
            }
        }

        $objPreorder->is_preorder = 0;
        if ($objPreorder->update()) {
        }

        PreorderProduct::changeAvailabilityPreference($idProduct, $idAttr);
    }

    protected function processBulkEnableSelection()
    {
        $this->bulkStatusAction(1);

        return parent::processBulkEnableSelection();
    }

    protected function processBulkDisableSelection()
    {
        $this->bulkStatusAction(0);

        return parent::processBulkDisableSelection();
    }

    protected function bulkStatusAction($status)
    {
        $conf = '';
        if (is_array($this->boxes) && !empty($this->boxes)) {
            foreach ($this->boxes as $id) {
                if ($idShop = PreorderProduct::getPreorderProductIdShop($id)) {
                    $objPreorder = new PreorderProduct((int) $id, null, $idShop);
                    $objPreorder->is_preorder = (int) $status;
                    $objPreorder->save();
                    if ($status) {
                        // Make it activated
                        $preorderCarrier = $objPreorder->getPreorderShipping();
                        $appliedCarrier = [];
                        $objProduct = new Product($objPreorder->product_id);
                        $appliedCarriers = $objProduct->getCarriers();
                        if (!empty($appliedCarriers)) {
                            foreach ($appliedCarriers as $carrier) {
                                $appliedCarrier[] = $carrier['id_reference'];
                            }
                            $appliedCarrier = json_encode($appliedCarrier);
                        } else {
                            $appliedCarrier = 0;         // all carries are applied
                        }
                        $this->activePreorderProduct($objPreorder, $preorderCarrier, $appliedCarrier);
                    } else {
                        // Make it deactivated
                        $this->deactivePreorderProduct($objPreorder);
                    }
                }
            }
            $conf = 5;
        }
        Tools::redirectAdmin(self::$currentIndex . '&conf=' . $conf . '&token=' . $this->token);
    }

    public function validatePreorderField(
        $paymentType,
        $paymentMethod,
        $originalPrice,
        $preorderPrice,
        $expectedDate,
        $quantity,
        $maxquantity
    ) {
        $expectedDateStamp = strtotime($expectedDate);
        $currentTimeDateStamp = strtotime(date('Y-m-d H:i:s'));
        $this->context->controller->errors = [];

        if ($paymentType == '1') {
            if ($preorderPrice != $originalPrice) {
                $this->context->controller->errors[] = $this->l('Preorder Price should be equal to base price of the product.');
            }
        } elseif ($paymentType == '2' || $paymentType == '3') {
            if ($paymentMethod == '1') {
                if ($preorderPrice >= 100) {
                    $this->context->controller->errors[] = $this->l('Percentage must be less than 100.');
                } elseif (!Validate::isPrice($preorderPrice)) {
                    $this->context->controller->errors[] = $this->l('Invalid Pre-Order Price.');
                }
            } elseif ($paymentMethod == '2') {
                if (!Validate::isPrice($preorderPrice)) {
                    $this->context->controller->errors[] = $this->l('Invalid Pre-Order Price.');
                } elseif ($preorderPrice >= $originalPrice) {
                    $this->context->controller->errors[] = $this->l(
                        'Pre-Order Price must be less than base price of the product.'
                    );
                }
            }
        }

        if (!$expectedDate) {
            $this->context->controller->errors[] = $this->l('Expected date can not be empty.');
        } elseif (!Validate::isDateFormat($expectedDate)) {
            $this->context->controller->errors[] = $this->l('Date format is wrong.');
        } elseif ($expectedDateStamp < $currentTimeDateStamp) {
            $this->context->controller->errors[] = $this->l('Availablity date must be greater than current date.');
        }

        if ($quantity <= 0) {
            $this->context->controller->errors[] = $this->l('Please set quantity for this product.');
        } elseif (!Validate::isInt($quantity)) {
            $this->context->controller->errors[] = $this->l('Value for quantity should be numeric.');
        }

        if ($maxquantity <= 0) {
            $this->context->controller->errors[] = $this->l('Please set maximum quantity for preorder.');
        } elseif (!Validate::isInt($maxquantity)) {
            $this->context->controller->errors[] = $this->l('Value for maximum quantity should be numeric.');
        }

        if ($maxquantity > $quantity) {
            $this->context->controller->errors[] = $this->l('Maximum quantity for preorder must be less than quantity for this product.');
        }

        return $this->context->controller->errors;
    }

    public function updateExistPreorderProduct(
        $preorderProduct,
        $paymentType,
        $paymentMethod,
        $preorderPrice,
        $expectedDate,
        $preorderEnable,
        $autoAvailable,
        $originalPrice,
        $idProduct,
        $idAttr,
        $preorderCarrier,
        $idLang,
        $quantity,
        $maxquantity,
        $appliedCarrier = null
    ) {
        $oldPaymentType = $preorderProduct->payment_type;
        $objProduct = new Product($idProduct, false, $idLang);
        $preorderProduct->payment_type = $paymentType;
        $preorderProduct->payment_method = $paymentMethod;
        $preorderProduct->preorder_price = $preorderPrice;
        if (!$preorderProduct->is_preorder
        && $preorderEnable
        && $preorderProduct->expected_date != $expectedDate
        && !Tools::getValue('complete_preorder')) {
            $preorderProduct->prebooked_quantity = 0;
            $preorderProduct->recreation_date = date('Y-m-d H:i:s');
        }
        if (Tools::getValue('complete_preorder')) {
            $preorderProduct->expected_date = date('Y-m-d H:i:s');
        } else {
            $preorderProduct->expected_date = $expectedDate;
        }

        $preorderProduct->is_preorder = $preorderEnable;
        $preorderProduct->is_auto_available = $autoAvailable;
        $preorderProduct->id_default_currency = Configuration::get('PS_CURRENCY_DEFAULT');
        $preorderProduct->id_tax_rules_group = $preorderProduct->id_tax_rules_group;
        if ($appliedCarrier) {
            $preorderProduct->id_applied_shipping = $appliedCarrier;
        } else {
            $preorderProduct->id_applied_shipping = $preorderProduct->id_applied_shipping;
        }

        $preorderProduct->quantity = $quantity;
        $preorderProduct->maxquantity = $maxquantity;
        if ($preorderProduct->update()) {
            $newQuantity = 0;
            if ((int) $preorderProduct->quantity >= (int) $preorderProduct->prebooked_quantity) {
                $newQuantity = (int) $preorderProduct->quantity - (int) $preorderProduct->prebooked_quantity;
            } else {
                $newQuantity = $preorderProduct->quantity;
            }
            if ($paymentType == '1') {
                $reducePrice = 0;
                $preorderPrice = $originalPrice;
                if ($oldPaymentType != 1) {
                    if ($preorderProduct->id_applied_shipping !== '0') {
                        $preorderProduct->setCarriers($idProduct, $preorderProduct->id_applied_shipping);
                    } else {
                        $preorderProduct->removeCarriers($idProduct);
                    }
                    if ($preorderProduct->attribute_id == 0) {
                        $preorderProduct->setOriginalProductPrice(
                            $idProduct,
                            $originalPrice
                        );
                    } else {
                        $preorderProduct->setImpactPrice($idProduct, $idAttr, $preorderProduct->impact_price);
                        if ($preorderEnable) {
                            if (Shop::CONTEXT_ALL == Shop::getContext()) {
                                StockAvailable::setQuantity($idProduct, $idAttr, 0, null, false);
                            } else {
                                StockAvailable::setQuantity($idProduct, $idAttr, 0);
                            }
                        } else {
                            if (Shop::CONTEXT_ALL == Shop::getContext()) {
                                StockAvailable::setQuantity($idProduct, $idAttr, $newQuantity, null, false);
                            } else {
                                StockAvailable::setQuantity($idProduct, $idAttr, $newQuantity);
                            }
                        }
                    }
                } else {
                    $preorderProduct->setPreorderPrice($idProduct, $idAttr, $preorderPrice, $paymentType);
                    if ($preorderEnable) {
                        if (Shop::CONTEXT_ALL == Shop::getContext()) {
                            StockAvailable::setQuantity($idProduct, $idAttr, 0, null, false);
                        } else {
                            StockAvailable::setQuantity($idProduct, $idAttr, 0);
                        }
                    } else {
                        if (Shop::CONTEXT_ALL == Shop::getContext()) {
                            StockAvailable::setQuantity($idProduct, $idAttr, $newQuantity, null, false);
                        } else {
                            StockAvailable::setQuantity($idProduct, $idAttr, $newQuantity);
                        }
                    }
                }
                if (Tools::getValue('complete_preorder')) {
                    StockAvailable::setProductOutOfStock($idProduct, false); // deny order
                } else {
                    StockAvailable::setProductOutOfStock($idProduct, true); // allow order
                }
            } elseif ($paymentType == '2' && $paymentMethod == '1') { // partially with percentage
                $originalPriceWithImpact = $originalPrice + $preorderProduct->impact_price;
                $preorderPrice = ($originalPriceWithImpact * $preorderPrice) / 100;
                $reducePrice = $preorderPrice - $originalPrice;
            } elseif ($paymentType == '2' && $paymentMethod == '2') { // partially with fixed amount
                $reducePrice = $preorderPrice - $originalPrice;
            }
            if ($paymentType == '2') { // partially preorder
                $preorderProduct->setPreorderPrice($idProduct, $idAttr, $preorderPrice, $paymentType);
                $preorderProduct->setImpactPrice($idProduct, $idAttr, $reducePrice);
                $preorderProduct->removeCarriers($idProduct);
                $objProduct->setCarriers($preorderCarrier);

                if ($preorderEnable) {
                    if (Shop::CONTEXT_ALL == Shop::getContext()) {
                        StockAvailable::setQuantity($idProduct, $idAttr, 0, null, false);
                    } else {
                        StockAvailable::setQuantity($idProduct, $idAttr, 0);
                    }
                    StockAvailable::setProductOutOfStock($idProduct, true, $this->context->shop->id, $idAttr);
                } else {
                    if (Shop::CONTEXT_ALL == Shop::getContext()) {
                        StockAvailable::setQuantity($idProduct, $idAttr, $newQuantity, null, false);
                    } else {
                        StockAvailable::setQuantity($idProduct, $idAttr, $newQuantity);
                    }
                    StockAvailable::setProductOutOfStock($idProduct, true);
                }
            }

            if ($paymentType == '3') { // dynamic preorder
                $preorderProduct->removeCarriers($idProduct);
                $objProduct->setCarriers($preorderCarrier);

                $preorderProduct->setOriginalProduct($idProduct, $originalPrice, $newQuantity, $idAttr);
                // if admin update the preorder for dynamic payment then delete all the specific price

                if ($preorderEnable) {
                    if (Shop::CONTEXT_ALL == Shop::getContext()) {
                        StockAvailable::setQuantity($idProduct, $idAttr, 0, null, false);
                    } else {
                        StockAvailable::setQuantity($idProduct, $idAttr, 0);
                    }
                    StockAvailable::setProductOutOfStock($idProduct, true, $this->context->shop->id, $idAttr);
                } else {
                    if (Shop::CONTEXT_ALL == Shop::getContext()) {
                        StockAvailable::setQuantity($idProduct, $idAttr, $newQuantity, null, false);
                    } else {
                        StockAvailable::setQuantity($idProduct, $idAttr, $newQuantity);
                    }
                    StockAvailable::setProductOutOfStock($idProduct, true);
                }
            }
        }
        if ($paymentType == '3' && $oldPaymentType == '2') {
            if ($idAttr) {
                $preorderProduct->setImpactPrice($idProduct, $idAttr, 0);
            }
        }
    }

    public function setMedia($isNewTheme = false)
    {
        parent::setMedia($isNewTheme);
        $this->addjQueryPlugin([
            'select2',
        ]);
        $this->addJS(_PS_JS_DIR_ . 'jquery/plugins/select2/select2_locale_' . $this->context->language->iso_code . '.js');

        $this->addCSS(_MODULE_DIR_ . 'preorder/views/css/preorder.css');
        $this->addJs(_MODULE_DIR_ . 'preorder/views/js/preorder.js');
        $this->addJs(_MODULE_DIR_ . 'preorder/views/js/preorder_list.js');
    }

    public function ajaxProcessGetProducts()
    {
        $this->context = Context::getContext();
        $idLang = $this->context->language->id;
        $preorderProduct = new PreOrderProduct();
        $idProduct = Tools::getValue('id_product');
        $idAttr = Tools::getValue('id_attribute');
        $query = Tools::getValue('query');
        if ($query) {
            $allproducts = Product::searchByName($idLang, $query, null);
            if ($allproducts) {
                foreach ($allproducts as $key => $prod) {
                    if (!$prod['active']) {
                        unset($allproducts[$key]);
                        continue;
                    }
                    $found = true;
                    $idProduct = $prod['id_product'];
                    $attributes = Product::getProductAttributesIds($idProduct, true);
                    if (!empty($attributes)) {
                        foreach ($attributes as $attr) {
                            $is_preorder = $preorderProduct->getExistingPreOrderProduct(
                                $idProduct,
                                $attr['id_product_attribute']
                            );
                            if (!$is_preorder) {
                                $found = false;
                            }
                        }
                        if ($found) {
                            unset($allproducts[$key]);
                        }
                    } else {
                        $is_preorder = $preorderProduct->getExistingPreOrderProduct($idProduct, 0);
                        if ($is_preorder) {
                            unset($allproducts[$key]);
                        }
                    }
                }
                foreach ($allproducts as $key => &$prod) {
                    $productobj = new Product((int) $prod['id_product'], false, $this->context->language->id, $this->context->shop->id);
                    $image = Product::getCover($prod['id_product']);

                    if ($image && is_array($image) && isset($image['id_image'])) {
                        $prod['image'] = str_replace(
                            'http://',
                            Tools::getShopProtocol(),
                            $this->context->link->getImageLink(
                                $productobj->link_rewrite,
                                Image::getCover($prod['id_product'])['id_image'],
                                ImageType::getFormattedName('home')
                            )
                        );
                    } else {
                        $prod['image'] = _MODULE_DIR_ . $this->module->name . '/views/img/home-default.jpg';
                    }
                }
                $allproductsNew = [];
                foreach ($allproducts as &$prod) {
                    $allproductsNew[] = &$prod;
                }

                $jdata = json_encode($allproductsNew);
                exit($jdata);
            } else {
                exit('0');
            }
        } elseif ($idProduct && !$idAttr) {
            $combinations = [];
            $currency = $this->context->currency->id;
            $productObj = new Product((int) $idProduct, false, (int) $idLang);
            $attributes = $productObj->getAttributesGroups((int) $idLang);
            if ($attributes) {
                foreach ($attributes as $attribute) {
                    if (!isset($combinations[$attribute['id_product_attribute']]['attributes'])) {
                        $combinations[$attribute['id_product_attribute']]['attributes'] = '';
                    }
                    $combinations[$attribute['id_product_attribute']]['attributes'] .= $attribute['attribute_name'] . '-';
                    $combinations[$attribute['id_product_attribute']]['id_product_attribute'] =
                    $attribute['id_product_attribute'];
                    $combinations[$attribute['id_product_attribute']]['default_on'] = $attribute['default_on'];
                    if (!isset($combinations[$attribute['id_product_attribute']]['price'])) {
                        $priceTaxIncl = Product::getPriceStatic(
                            (int) $idProduct,
                            true,
                            $attribute['id_product_attribute'],
                            6,
                            null,
                            false,
                            false
                        );
                        $priceTaxExcl = Product::getPriceStatic(
                            (int) $idProduct,
                            false,
                            $attribute['id_product_attribute'],
                            6,
                            null,
                            false,
                            false
                        );
                        $combinations[$attribute['id_product_attribute']]['price_tax_incl'] = Tools::ps_round(
                            Tools::convertPrice($priceTaxIncl, $currency),
                            2
                        );
                        $combinations[$attribute['id_product_attribute']]['price_tax_excl'] = Tools::ps_round(
                            Tools::convertPrice($priceTaxExcl, $currency),
                            2
                        );
                        $combinations[$attribute['id_product_attribute']]['formatted_price'] = PreorderHelper::displayPrice(
                            Tools::convertPrice($priceTaxExcl, $currency),
                            $this->context->currency
                        );
                    }
                    if (!isset($combinations[$attribute['id_product_attribute']]['qty_in_stock'])) {
                        $combinations[
                            $attribute[
                                'id_product_attribute'
                            ]
                        ]['qty_in_stock'] = StockAvailable::getQuantityAvailableByProduct(
                            (int) $idProduct,
                            $attribute['id_product_attribute'],
                            (int) $this->context->shop->id
                        );
                    }
                }

                foreach ($combinations as $key => $value) {
                    $is_preorder = $preorderProduct->getExistingPreOrderProduct(
                        $idProduct,
                        $value['id_product_attribute']
                    );
                    if ($is_preorder) {
                        unset($combinations[$key]);
                    }
                }
            } else {
                $combinations[0]['price_tax_incl'] = Tools::ps_round(
                    Tools::convertPrice($productObj->price, $currency),
                    2
                );
                $combinations[0]['qty_in_stock'] = StockAvailable::getQuantityAvailableByProduct(
                    (int) $idProduct,
                    0,
                    (int) $this->context->shop->id
                );
            }
            if ($combinations) {
                $jdata = json_encode($combinations);
                exit($jdata);
            } else {
                exit(false);
            }
        } elseif ($idProduct && $idAttr) {
            $combinations['price'] = Tools::ps_round(
                Product::getPriceStatic(
                    (int) $idProduct,
                    false,
                    $idAttr,
                    6,
                    null,
                    false,
                    false
                ),
                2
            );
            $combinations['qty'] = StockAvailable::getQuantityAvailableByProduct(
                (int) $idProduct,
                $idAttr,
                (int) $this->context->shop->id
            );
            if ($combinations) {
                $jdata = json_encode($combinations);
                exit($jdata);
            } else {
                exit(false);
            }
        }
    }

    public function ajaxProcessGetProductPrice()
    {
        $idProduct = Tools::getValue('id_product');
        $product = new Product((int) $idProduct);
        $idAttr = Tools::getValue('id_attribute');
        $combinations = [];
        if ($idAttr) {
            $combinations['price'] = Tools::ps_round(
                Product::getPriceStatic(
                    (int) $idProduct,
                    false,
                    $idAttr,
                    6,
                    null,
                    false,
                    false
                ),
                2
            );
        } else {
            $combinations['price'] = Tools::ps_round(
                $product->price,
                2
            );
        }
        $combinations['qty'] = StockAvailable::getQuantityAvailableByProduct(
            (int) $idProduct,
            $idAttr,
            (int) $this->context->shop->id
        );
        if ($combinations) {
            $jdata = json_encode($combinations);
            exit($jdata);
        } else {
            exit(false);
        }
    }
}
