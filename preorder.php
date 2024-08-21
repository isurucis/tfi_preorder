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
use PrestaShop\PrestaShop\Adapter\ServiceLocator;

include_once dirname(__FILE__) . '/classes/PreorderClasses.php';
class Preorder extends CarrierModule
{
    public $idCarrier;
    public $secure_key;
    public $absolutePath;

    public function __construct()
    {
        $this->name = 'preorder';
        $this->tab = 'front_office_features';
        $this->version = '5.3.1';
        $this->module_key = 'df70272385d9f2f60b57187f2a0f19d1';
        $this->author = 'Webkul';
        $this->ps_versions_compliancy = ['min' => '1.7', 'max' => _PS_VERSION_];
        $this->bootstrap = true;
        $this->need_instance = 0;
        $this->secure_key = Tools::hash($this->name);
        $this->absolutePath = _MODULE_DIR_ . 'views/img/';
        parent::__construct();
        $this->displayName = $this->l('Preorder');
        $this->description = $this->l('Allow pre-Booking of the products before releasing it.');
    }

    public function enable($force_all = false)
    {
        if ($force_all) {
        }
        if (!Configuration::hasKey('PS_PREORDER_MENU')) {
            Configuration::updateValue('PS_PREORDER_MENU', '1');
            $this->callPreorderMenu();
        } else {
            if (Configuration::get('PS_PREORDER_MENU')) {
                $this->callPreorderMenu(Shop::getContextListShopID());
            }
        }
        if (parent::enable() == false) {
            return false;
        }

        return true;
    }

    public function getContent()
    {
        $cron = null;
        if (Tools::isSubmit('submit_preorder_conf')) {
            if ($this->postProcess()) {
                $cron .= $this->displayConfirmation($this->l('Settings updated'));
            }
        }

        Media::addJsDef([
            'wkModuleAddonKey' => $this->module_key,
            'wkModuleAddonsId' => 17707,
            'wkModuleTechName' => $this->name,
            'wkModuleDoc' => file_exists(_PS_MODULE_DIR_ . $this->name . '/doc_en.pdf'),
        ]);
        $this->context->controller->addJs('https://prestashop.webkul.com/crossselling/wkcrossselling.min.js?t=' . time());

        $updateProductsAfterCron = $this->context->link->getModuleLink(
            'preorder',
            'cron'
        ) . '?token=' . $this->secure_key;

        $this->context->smarty->assign('updateProductsAfterCron', $updateProductsAfterCron);
        $this->context->controller->addJs(_MODULE_DIR_ . 'preorder/views/js/preorder_list.js');
        $cron .= $this->context->smarty->fetch($this->local_path . 'views/templates/admin/cron_settings.tpl');

        $geoIpAvailable = false;
        if (@filemtime(_PS_GEOIP_DIR_ . _PS_GEOIP_CITY_FILE_)) {
            $geoIpAvailable = true;
        }
        $this->context->smarty->assign([
            'geoip_database_url' => 'https://dev.maxmind.com/geoip/geoip2/geolite2',
            'geoIpAvailable' => $geoIpAvailable,
        ]);

        $html = $this->renderForm();

        return $cron . $html;
    }

    public function renderForm()
    {
        $fields_form = [];
        $groups = Group::getGroups($this->context->language->id, $this->context->shop->id);
        $country = Country::getCountries($this->context->language->id);

        $this->context->smarty->assign([
            'country' => $country,
            'allowed_countries' => Tools::getValue('WK_PREORDER_COUNTRY[]', json_decode(Configuration::get('WK_PREORDER_COUNTRY'))),
        ]);

        $fields_form['form'] = [
            'legend' => [
                'title' => $this->l('General'),
                'icon' => 'icon-cogs',
            ],
            'input' => [
                [
                    'type' => 'select',
                    'label' => $this->l('Partial/Dynamic payment preorder display price'),
                    'hint' => $this->l('Select the price which will appear on the product page in case of partial/dynamic payment preorder'),
                    'name' => 'price_type',
                    'options' => [
                        'query' => [
                            [
                                'id' => '0',
                                'name' => $this->l('Preorder price'),
                            ],
                            [
                                'id' => '1',
                                'name' => $this->l('Original price'),
                            ],
                            [
                                'id' => '2',
                                'name' => $this->l('Both'),
                            ],
                        ],
                        'id' => 'id',
                        'name' => 'name',
                    ],
                ],
                [
                    'type' => 'switch',
                    'label' => $this->l('Guest can purchase partial payment preorder product'),
                    'desc' => $this->l('Guest checkout must be enabled.'),
                    'hint' => $this->l('Guest is not allowed to purchase dynamic payment type preorder products'),
                    'name' => 'WK_GUEST_PREORDER_ENABLED',
                    'values' => [
                        [
                            'id' => 'WK_GUEST_PREORDER_ENABLED_on',
                            'value' => '1',
                            'label' => $this->l('Yes'),
                        ],
                        [
                            'id' => 'WK_GUEST_PREORDER_ENABLED_off',
                            'value' => '0',
                            'label' => $this->l('No'),
                        ],
                    ],
                ],
                [
                    'type' => 'switch',
                    'label' => $this->l('Restrict checkout of preorder product with normal product'),
                    'name' => 'WK_RESTRICT_CHECKOUT',
                    'values' => [
                        [
                            'id' => 'WK_RESTRICT_CHECKOUT_on',
                            'value' => '1',
                            'label' => $this->l('Yes'),
                        ],
                        [
                            'id' => 'WK_RESTRICT_CHECKOUT_off',
                            'value' => '0',
                            'label' => $this->l('No'),
                        ],
                    ],
                ],
                [
                    'type' => 'switch',
                    'label' => $this->l('Show preorder product availability date'),
                    'name' => 'WK_SHOW_PRODUCT_AVAILABLE_ON',
                    'values' => [
                        [
                            'id' => 'WK_SHOW_PRODUCT_AVAILABLE_ON',
                            'value' => '1',
                            'label' => $this->l('Yes'),
                        ],
                        [
                            'id' => 'WK_SHOW_PRODUCT_AVAILABLE_ON_off',
                            'value' => '0',
                            'label' => $this->l('No'),
                        ],
                    ],
                ],
                [
                    'type' => 'switch',
                    'label' => $this->l('Show preorder content'),
                    'name' => 'WK_SHOW_PAYMENT_TYPE',
                    'values' => [
                        [
                            'id' => 'WK_SHOW_PAYMENT_TYPE_on',
                            'value' => '1',
                            'label' => $this->l('Yes'),
                        ],
                        [
                            'id' => 'WK_SHOW_PAYMENT_TYPE_off',
                            'value' => '0',
                            'label' => $this->l('No'),
                        ],
                    ],
                ],
                [
                    'label' => $this->l('Full payment content'),
                    'name' => 'WK_FULL_PAYMENT',
                    'form_group_class' => 'wk_show_payment_content',
                    'lang' => true,
                    'type' => 'textarea',
                    'autoload_rte' => true,
                    'required' => true,
                ],
                [
                    'desc' => $this->l('Available variable name for partial/dynamic payment content {preorderPrice} for preorder price (tax excl) {originalPrice} for original price.'),
                    'label' => $this->l('Partial/Dynamic payment content'),
                    'form_group_class' => 'wk_show_payment_content',
                    'name' => 'WK_PARTIAL_PAYMENT',
                    'lang' => true,
                    'type' => 'textarea',
                    'autoload_rte' => true,
                    'required' => true,
                ],
                [
                    'type' => 'switch',
                    'label' => $this->l('Enable limited time to complete order'),
                    'hint' => $this->l('If yes, customer has to complete preorder in limited time else order will be automatically cancelled.'),
                    'name' => 'WK_LIMITED_TIME',
                    'values' => [
                        [
                            'id' => 'WK_LIMITED_TIME_On',
                            'value' => '1',
                            'label' => $this->l('Yes'),
                        ],
                        [
                            'id' => 'WK_LIMITED_TIME_off',
                            'value' => '0',
                            'label' => $this->l('No'),
                        ],
                    ],
                ],
                [
                    'type' => 'text',
                    'label' => $this->l('Number of days to complete order'),
                    'hint' => $this->l('Once the product get available, given number of days are allowed to complete the order.'),
                    'name' => 'WK_ALLOW_DAYS',
                    'suffix' => $this->l('days'),
                    'required' => true,
                    'class' => 'fixed-width-md wk-limited-time',
                    'desc' => $this->l('Days should be greater than 0.'),
                ],
                [
                    'type' => 'switch',
                    'label' => $this->l('Rollback preorder stock when limited time is over'),
                    'name' => 'WK_STOCK_ROLLBACK',
                    'hint' => $this->l('If yes, the stock will be added to catalog stock when limited time is over and customer did not complete preorder.'),
                    'values' => [
                        [
                            'id' => 'WK_STOCK_ROLLBACK_On',
                            'value' => '1',
                            'label' => $this->l('Yes'),
                        ],
                        [
                            'id' => 'WK_STOCK_ROLLBACK_off',
                            'value' => '0',
                            'label' => $this->l('No'),
                        ],
                    ],
                ],
                [
                    'type' => 'group',
                    'label' => $this->l('Select the customer group for which preorder is allowed
                    '),
                    'name' => 'groupBox',
                    'values' => $groups,
                    'required' => true,
                    'hint' => $this->l('Preorder products will be available only for selected groups.'),
                ],
                [
                    'type' => 'html',
                    'label' => $this->l('Select the countries for which preorder is allowed'),
                    'name' => 'WK_PREORDER_COUNTRY[]',
                    'required' => true,
                    'html_content' => $this->context->smarty->fetch(
                        'module:' . $this->name . '/views/templates/admin/countries.tpl'
                    ),
                    'hint' => $this->l('Preorder products will be available only for selected countries.'),
                ],
                [
                    'type' => 'switch',
                    'label' => $this->l('Enable geolocation for customer without address'),
                    'hint' => $this->l('If yes, IP address will be used to find the country of the user.'),
                    'name' => 'WK_ALLOW_GEOLOCATION',
                    'values' => [
                        [
                            'id' => 'WK_ALLOW_GEOLOCATION_On',
                            'value' => '1',
                            'label' => $this->l('Yes'),
                        ],
                        [
                            'id' => 'WK_ALLOW_GEOLOCATION_off',
                            'value' => '0',
                            'label' => $this->l('No'),
                        ],
                    ],
                    'desc' => $this->context->smarty->fetch($this->local_path . 'views/templates/admin/geoip_alert.tpl'),
                ],
            ],
            'submit' => [
                'title' => $this->l('Save'),
                'name' => 'submit_preorder_conf',
            ],
        ];

        $helper = new HelperForm();
        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $lang = new Language((int) Configuration::get('PS_LANG_DEFAULT'));
        $helper->default_form_language = $lang->id;
        $this->fields_form = [];
        $helper->identifier = $this->identifier;
        $helper->submit_action = 'btnSubmit';
        $helper->currentIndex = $this->context->link->getAdminLink(
            'AdminModules',
            false
        ) . '&configure=' . $this->name . '&module_name=' . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->tpl_vars = [
            'fields_value' => $this->getConfigFieldsValues(),
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        ];

        return $helper->generateForm([$fields_form]);
    }

    public function getConfigFieldsValues()
    {
        $WK_FULL_PAYMENT = [];
        foreach (Language::getLanguages(false) as $lang) {
            $WK_FULL_PAYMENT[$lang['id_lang']] = Configuration::get('WK_FULL_PAYMENT', $lang['id_lang']);
        }
        $WK_PARTIAL_PAYMENT = [];
        foreach (Language::getLanguages(false) as $lang) {
            $WK_PARTIAL_PAYMENT[$lang['id_lang']] = Configuration::get('WK_PARTIAL_PAYMENT', $lang['id_lang']);
        }

        $groupBox = [];
        $configGroupData = json_decode(Configuration::get('WK_PREORDER_GROUP'));
        if ($configGroupData && is_array($configGroupData)) {
            $groups = Group::getGroups($this->context->language->id);
            foreach ($groups as $group) {
                $groupBox['groupBox_' . $group['id_group']] =
                    Tools::getValue('groupBox_' . $group['id_group'], in_array($group['id_group'], $configGroupData));
            }
        }

        $configValues = [
            'price_type' => Tools::getValue('price_type', Configuration::get('price_type')),
            'WK_GUEST_PREORDER_ENABLED' => Tools::getValue('WK_GUEST_PREORDER_ENABLED', Configuration::get('WK_GUEST_PREORDER_ENABLED')),
            'WK_RESTRICT_CHECKOUT' => Tools::getValue(
                'WK_RESTRICT_CHECKOUT',
                Configuration::get('WK_RESTRICT_CHECKOUT')
            ),
            'WK_SHOW_PRODUCT_AVAILABLE_ON' => Tools::getValue(
                'WK_SHOW_PRODUCT_AVAILABLE_ON',
                Configuration::get('WK_SHOW_PRODUCT_AVAILABLE_ON')
            ),
            'WK_SHOW_PAYMENT_TYPE' => Tools::getValue('WK_SHOW_PAYMENT_TYPE', Configuration::get('WK_SHOW_PAYMENT_TYPE')),
            'WK_FULL_PAYMENT' => $WK_FULL_PAYMENT,
            'WK_PARTIAL_PAYMENT' => $WK_PARTIAL_PAYMENT,
            'WK_LIMITED_TIME' => Tools::getValue('WK_LIMITED_TIME', Configuration::get('WK_LIMITED_TIME')),
            'WK_ALLOW_DAYS' => Tools::getValue('WK_ALLOW_DAYS', Configuration::get('WK_ALLOW_DAYS')),
            'WK_STOCK_ROLLBACK' => Tools::getValue('WK_STOCK_ROLLBACK', Configuration::get('WK_STOCK_ROLLBACK')),
            'WK_ALLOW_GEOLOCATION' => Tools::getValue('WK_ALLOW_GEOLOCATION', Configuration::get('WK_ALLOW_GEOLOCATION')),
        ];

        return array_merge($configValues, $groupBox);
    }

    public function hookDisplayOverrideTemplate($params)
    {
        if (Configuration::get('price_type')) {
            if ($params['template_file'] == 'catalog/listing/category'
                || $params['template_file'] == 'catalog/listing/best-sales'
                || $params['template_file'] == 'catalog/listing/prices-drop'
                || $params['template_file'] == 'catalog/listing/new-products'
                || $params['template_file'] == 'catalog/listing/manufacturer'
                || $params['template_file'] == 'catalog/listing/supplier'
                || $params['template_file'] == 'catalog/_partials/products'
                || $params['template_file'] == 'catalog/listing/search'
            ) {
                $this->context->smarty->assign([
                    'wkListingLayout' => $params['template_file'] . '.tpl',
                    'price_type' => Configuration::get('price_type'),
                ]);

                return 'module:' . $this->name . '/views/templates/hook/global-listing.tpl';
            }
        }
        if ($params['template_file'] == 'catalog/product'
            || $params['template_file'] == 'catalog/_partials/quickview'
            || $params['template_file'] == 'catalog/_partials/product-prices') {
            if ($idProduct = Tools::getValue('id_product')) {
                $idProductAttribute = Tools::getValue('id_product_attribute');
                $preorderObj = new PreOrderProduct();

                if ($idProductAttribute) {
                } else {
                    if (Tools::getValue('group')) {
                        $idProductAttribute = Product::getIdProductAttributeByIdAttributes(
                            $idProduct,
                            Tools::getValue('group')
                        );
                    } else {
                        $idProductAttribute = Product::getDefaultAttribute(
                            $idProduct
                        );
                    }
                }
                $existingPreorder = $preorderObj->getExistingPreOrderProduct($idProduct, $idProductAttribute);
                if ($existingPreorder && $existingPreorder['is_preorder'] == 1) {
                    if ($existingPreorder['payment_type'] == 2 || $existingPreorder['payment_type'] == 3) {
                        if (Configuration::get('price_type')) {
                            $this->context->smarty->assign(
                                [
                                    'preorderOriginalPriceWithoutReduction' => PreorderHelper::calculatePreorderOriginalPrice(
                                        $idProduct,
                                        $existingPreorder,
                                        false,
                                        false,
                                        1
                                    ),
                                    'preorderOriginalPrice' => PreorderHelper::calculatePreorderOriginalPrice(
                                        $idProduct,
                                        $existingPreorder
                                    ),
                                    'price_type' => Configuration::get('price_type'),
                                ]
                            );
                        } else {
                            if ($existingPreorder['payment_type'] == 3) {
                                $this->context->smarty->assign(
                                    [
                                        'wk_dynamic_payment' => 1,
                                    ]
                                );
                            }
                        }

                        if ($params['template_file'] == 'catalog/_partials/quickview') {
                            return 'module:' . $this->name . '/views/templates/hook/product-quickview.tpl';
                        } elseif ($params['template_file'] == 'catalog/_partials/product-prices') {
                            return 'module:' . $this->name . '/views/templates/hook/override_preorder_price.tpl';
                        } elseif ($params['template_file'] == 'catalog/product') {
                            return 'module:' . $this->name . '/views/templates/hook/preorder_product.tpl';
                        }
                    }
                }
            }
        }
    }

    /**
     * For creating preorder menu in front end.
     *
     * @return bool
     */
    public function callPreorderMenu($shopIDs = null)
    {
        if (Module::isEnabled('ps_mainmenu')) {
            Configuration::updateValue('PS_PREORDER_MENU', 1);
            if ($this->addTopMenuPreorder($shopIDs)) {
                return true;
            }
        } else {
            Configuration::updateValue('PS_PREORDER_MENU', 0);
        }

        return true;
    }

    public function addTopMenuPreorder($shops = null)
    {
        $linksLabel = [];
        $labels = [];
        $link = new Link();
        if ($shops) {
        } else {
            $shops = Shop::getCompleteListOfShopsID();
        }
        foreach ($shops as $shopId) {
            $shopGroupId = Shop::getGroupFromShop($shopId);
            $languages = Language::getLanguages();
            foreach ($languages as $val) {
                $linksLabel[$val['id_lang']] = $link->getModuleLink('preorder', 'allpreorderdetails', [], null, $val['id_lang'], $shopId);
                $labels[$val['id_lang']] = $this->l('Preorder');
            }
            $idLinksMenutop = $this->addMenu($linksLabel, $labels, (int) $shopId, 1);
            if ($idLinksMenutop) {
                $newItem = 'LNK' . $idLinksMenutop;
                // Add new menu with available menus
                $lastItems = Configuration::get('MOD_BLOCKTOPMENU_ITEMS', null, $shopGroupId, $shopId);
                $itemsArr = explode(',', $lastItems);
                if (!in_array($newItem, $itemsArr)) {
                    $itemsArr[] = $newItem;
                }

                Configuration::updateValue(
                    'MOD_BLOCKTOPMENU_ITEMS',
                    (string) implode(',', $itemsArr),
                    false,
                    (int) $shopGroupId,
                    (int) $shopId
                );

                Configuration::updateValue(
                    'PS_PREORDER_MENU_ID',
                    $idLinksMenutop,
                    false,
                    (int) $shopGroupId,
                    (int) $shopId
                );
            }
        }

        $this->clearMainMenuCache();

        return true;
    }

    public function clearMainMenuCache()
    {
        $dir = _PS_CACHE_DIR_ . DIRECTORY_SEPARATOR . 'ps_mainmenu';
        if (!is_dir($dir)) {
            return;
        }
        foreach (scandir($dir) as $entry) {
            if (preg_match('/\.json$/', $entry)) {
                unlink($dir . DIRECTORY_SEPARATOR . $entry);
            }
        }
    }

    public function addMenu($link, $label, $idShop, $newWindow = 0)
    {
        if (!is_array($label)) {
            return false;
        }
        if (!is_array($link)) {
            return false;
        }

        PreorderHelper::insertIntolinksmenutop($newWindow, $idShop);
        $idLinksMenutop = PreorderHelper::getLastInsertedId();

        foreach ($label as $idLang => $label) {
            PreorderHelper::insertIntoLinkMenuTopLang($idLinksMenutop, $idLang, $idShop, $label, $link[$idLang]);
        }

        if ($idLinksMenutop) {
            return $idLinksMenutop;
        } else {
            return false;
        }
    }

    public function postProcess()
    {
        if (Tools::isSubmit('submit_preorder_conf')) {
            if (!Tools::getValue('WK_FULL_PAYMENT_' . Configuration::get('PS_LANG_DEFAULT'))) {
                $this->context->controller->errors[] = $this->l('Full payment content is required.');
            } elseif (!Tools::getValue('WK_PARTIAL_PAYMENT_' . Configuration::get('PS_LANG_DEFAULT'))) {
                $this->context->controller->errors[] = $this->l('Partial/Dynamic payment content is required.');
            } else {
                foreach (Language::getLanguages(false) as $language) {
                    if (!Validate::isCleanHtml(Tools::getValue('WK_FULL_PAYMENT_' . $language['id_lang']))) {
                        $this->context->controller->errors[] = sprintf(
                            $this->l('Full payment content is invalid in %s.'), $language['name']
                        );
                    }
                    if (!Validate::isCleanHtml(Tools::getValue('WK_PARTIAL_PAYMENT_' . $language['id_lang']))) {
                        $this->context->controller->errors[] = sprintf(
                            $this->l('Partial/Dynamic payment content is invalid in %s.'), $language['name']
                        );
                    }
                }
            }

            if (Tools::getValue('WK_LIMITED_TIME')) {
                $wkAllowDays = trim(Tools::getValue('WK_ALLOW_DAYS'));
                if (empty($wkAllowDays)) {
                    $this->context->controller->errors[] = $this->l('Number of days to complete order field is required.');
                } elseif (!Validate::isUnsignedInt($wkAllowDays)) {
                    $this->context->controller->errors[] = $this->l('Number of days to complete order must be an integer.');
                } elseif ($wkAllowDays <= 0) {
                    $this->context->controller->errors[] = $this->l('Number of days to complete order must greater than 0.');
                }
            }
            if (!Tools::getIsset('groupBox')) {
                $this->context->controller->errors[] = $this->l('Please select at least one group for preorder products.');
            }
            if (!Tools::getIsset('WK_PREORDER_COUNTRY')) {
                $this->context->controller->errors[] = $this->l('Please select at least one country for preorder products.');
            }

            if (Tools::getValue('WK_ALLOW_GEOLOCATION')) {
                if (!@filemtime(_PS_GEOIP_DIR_ . _PS_GEOIP_CITY_FILE_)) {
                    $this->context->controller->errors[] = $this->l('Maxmind files does not exists, kindly download it and place it in the /app/Resources/geoip/ directory.');
                }
            }

            if (empty($this->context->controller->errors)) {
                $preorderGuestCheckout = Tools::getValue('WK_GUEST_PREORDER_ENABLED');
                $preorderAvailableOn = Tools::getValue('WK_SHOW_PAYMENT_TYPE');
                $preorderPaymentType = Tools::getValue('WK_SHOW_PRODUCT_AVAILABLE_ON');
                Configuration::updateValue(
                    'price_type',
                    Tools::getValue('price_type')
                );
                Configuration::updateValue(
                    'WK_RESTRICT_CHECKOUT',
                    Tools::getValue('WK_RESTRICT_CHECKOUT')
                );

                if (isset($preorderGuestCheckout)) {
                    Configuration::updateValue(
                        'WK_GUEST_PREORDER_ENABLED',
                        Tools::getValue('WK_GUEST_PREORDER_ENABLED')
                    );
                }
                if (isset($preorderAvailableOn)) {
                    Configuration::updateValue(
                        'WK_SHOW_PRODUCT_AVAILABLE_ON',
                        Tools::getValue('WK_SHOW_PRODUCT_AVAILABLE_ON')
                    );
                }
                if (isset($preorderPaymentType)) {
                    Configuration::updateValue(
                        'WK_SHOW_PAYMENT_TYPE',
                        Tools::getValue('WK_SHOW_PAYMENT_TYPE')
                    );
                }

                $WK_FULL_PAYMENT = [];
                $WK_PARTIAL_PAYMENT = [];
                $defaultLang = Configuration::get('PS_LANG_DEFAULT');

                foreach (Language::getLanguages(false) as $language) {
                    if (Tools::getValue('WK_FULL_PAYMENT_' . $language['id_lang'])) {
                        $WK_FULL_PAYMENT[$language['id_lang']] = trim(
                            Tools::getValue('WK_FULL_PAYMENT_' . $language['id_lang'])
                        );
                    } else {
                        $WK_FULL_PAYMENT[$language['id_lang']] = trim(
                            Tools::getValue('WK_FULL_PAYMENT_' . $defaultLang)
                        );
                    }

                    if (Tools::getValue('WK_PARTIAL_PAYMENT_' . $language['id_lang'])) {
                        $WK_PARTIAL_PAYMENT[$language['id_lang']] = trim(
                            Tools::getValue('WK_PARTIAL_PAYMENT_' . $language['id_lang'])
                        );
                    } else {
                        $WK_PARTIAL_PAYMENT[$language['id_lang']] = trim(
                            Tools::getValue('WK_PARTIAL_PAYMENT_' . $defaultLang)
                        );
                    }
                }
                Configuration::updateValue('WK_FULL_PAYMENT', $WK_FULL_PAYMENT, true);
                Configuration::updateValue('WK_PARTIAL_PAYMENT', $WK_PARTIAL_PAYMENT, true);

                Configuration::updateValue('WK_LIMITED_TIME', Tools::getValue('WK_LIMITED_TIME'));
                Configuration::updateValue('WK_ALLOW_DAYS', Tools::getValue('WK_ALLOW_DAYS'));
                Configuration::updateValue('WK_STOCK_ROLLBACK', Tools::getValue('WK_STOCK_ROLLBACK'));

                Configuration::updateValue('WK_PREORDER_GROUP', json_encode(Tools::getValue('groupBox')));
                Configuration::updateValue('WK_PREORDER_COUNTRY', json_encode(Tools::getValue('WK_PREORDER_COUNTRY')));
                Configuration::updateValue('WK_ALLOW_GEOLOCATION', Tools::getValue('WK_ALLOW_GEOLOCATION'));

                return true;
            }

            return false;
        }
    }

    /**
     * [getOrderShippingCost To show shipping on;ly for preorder product.
     *
     * @param [Objec]t $cart          [Object of the current cart]
     * @param [Float]  $shippingCost [previous shipping cost calculated in the Cart.php]
     *
     * @return [Float|false] [Shipping cost of the carrier]
     */
    public function getOrderShippingCost($cart, $shippingCost)
    {
        $preorderProductExist = false;
        $objPreorderProd = new PreOrderProduct();
        if ($cartProducts = $cart->getProducts()) {
            foreach ($cartProducts as $product) {
                $preorderProd = $objPreorderProd->getExistingActivePreOrderProduct(
                    $product['id_product'],
                    $product['id_product_attribute']
                );
                if ($preorderProd) {
                    if ($preorderProd['payment_type'] != 1) {
                        $preorderProductExist = true;
                        break;
                    }
                }
            }
        }

        if ($preorderProductExist) {
            return $shippingCost;
        } else {
            return false;
        }
    }

    public function getOrderShippingCostExternal($params)
    {
        $this->getOrderShippingCost($params, 0);
    }

    public function hookDisplayProductPriceBlock($params)
    {
        if ($this->context->controller->php_self == 'cart'
            || $this->context->controller->php_self == 'order'
        ) {
            if ($params['type'] == 'unit_price') {
                $idProductAttribute = $params['product']->id_product_attribute;
                $idProduct = $params['product']->id_product;
                $preorderObj = new PreOrderProduct();
                $existingPreorder = $preorderObj->getExistingPreOrderProduct($idProduct, $idProductAttribute);
                if (!empty($existingPreorder) && $existingPreorder['is_preorder'] == '1') {
                    $full_payment = 1;
                    if ($existingPreorder['payment_type'] == 2 || $existingPreorder['payment_type'] == 3) {
                        $full_payment = 0;
                    }
                    $currency = new Currency($this->context->currency->id);
                    $originalPrice = PreorderHelper::calculatePreorderOriginalPrice(
                        $idProduct,
                        $existingPreorder
                    );
                    $objPreorderCustomer = new PreorderProductCustomer();
                    $checkCookie = $objPreorderCustomer->checkEntryExistsWithoutOrder(
                        $idProduct,
                        $idProductAttribute,
                        Context::getContext()->customer->id,
                        Context::getContext()->shop->id
                    );
                    $secondOrder = false;
                    if ($checkCookie) {
                        $preorderCustomerObj = new PreorderProductCustomer();
                        $secondOrder = $preorderCustomerObj->getCustomerPreOrderByIdPIdCIdO(
                            Context::getContext()->customer->id,
                            $idProduct,
                            $idProductAttribute,
                            $checkCookie['old_order_id']
                        );
                    }
                    $this->context->smarty->assign(
                        [
                            'orginalPrice' => $originalPrice,
                            'price_type' => Configuration::get('price_type'),
                            'full_payment' => $full_payment,
                            'secondOrder' => $secondOrder,
                        ]
                    );

                    return $this->fetch('module:preorder/views/templates/hook/preorder_cart_orignal_price.tpl');
                }
            }
        } elseif ($params['type'] == 'after_price') {
            $productId = $params['product']['id_product'];
            $idAttrib = $params['product']['id_product_attribute'];

            if ($productId) {
                $objProduct = new Product((int) $productId, false, $this->context->language->id);
                $preorderObj = new PreOrderProduct();
                $existingPreorder = $preorderObj->getExistingPreOrderProduct($productId, $idAttrib);
                if (!empty($existingPreorder) && $existingPreorder['is_preorder'] == '1') {
                    $var = 1;
                    $notAllowed = false;
                    if (PreorderHelper::validateConfigConditions()) {
                        $var = 0;
                        $notAllowed = true;
                    }

                    $originalPrice = $existingPreorder['original_price'] + $existingPreorder['impact_price'];
                    $preorderPaymentType = $existingPreorder['payment_type'];
                    $remainingQty = $existingPreorder['maxquantity'] - $existingPreorder['prebooked_quantity'];

                    if ($preorderPaymentType == '1') {
                        $preorderProductPrice = $existingPreorder['original_price'];
                    } elseif ($preorderPaymentType == '2' || $preorderPaymentType == '3') {
                        if ($existingPreorder['payment_method'] == 1) {
                            $preorderProductPrice = ($originalPrice * $existingPreorder['preorder_price']) / 100;
                        } elseif ($existingPreorder['payment_method'] == 2) {
                            $preorderProductPrice = $existingPreorder['preorder_price'];
                        }
                    }
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

                    $taxTxt = $this->l(' (tax excl)');
                    if (!$priceDisplay || $priceDisplay == 2) {
                        $priceTax = true;
                    } elseif ($priceDisplay == 1) {
                        $priceTax = false;
                    }

                    if ($priceTax) {
                        $taxTxt = $this->l(' (tax incl)');
                        $taxRate = $objProduct->getTaxesRate();
                        $preorderProductPrice += ((float) $preorderProductPrice * $taxRate) / 100;
                        $originalPriceWithTax = (float) $originalPriceWithTax +
                        ((float) $originalPriceWithTax * $taxRate) / 100;
                    }
                    $priceProduct = Product::getPriceStatic($productId, $priceTax, $idAttrib);

                    $langIso = $this->context->language->iso_code;
                    if ($langIso == 'en') {
                        $contextLanguageCode = $this->context->language->language_code;
                        $explodeLanguageCode = explode('-', $contextLanguageCode);
                        $langLocal = $explodeLanguageCode[0] . '_' . Tools::strtoupper($explodeLanguageCode[1]);
                    } else {
                        $langLocal = Tools::strtolower($langIso) . '_' . Tools::strtoupper($langIso);
                    }
                    setlocale(LC_TIME, $langLocal . '.utf8', $langLocal);

                    $currency = new Currency($this->context->currency->id);
                    $defaultCurrency = new Currency((int) Configuration::get('PS_CURRENCY_DEFAULT'));

                    $preorderProductPrice = Tools::ps_round(
                        Tools::convertPriceFull($preorderProductPrice, $defaultCurrency, $currency),
                        2
                    );
                    $originalPriceWithTax = Tools::ps_round(
                        Tools::convertPriceFull($originalPriceWithTax, $defaultCurrency, $currency),
                        2
                    );
                    $partialPaymentContent = '';
                    $idLang = $this->context->language->id;
                    if ($existingPreorder['payment_type'] != 1) {
                        $partialPayment = [];
                        foreach (Language::getLanguages(true) as $lang) {
                            $partialPayment[$lang['id_lang']] = Configuration::get(
                                'WK_PARTIAL_PAYMENT',
                                $lang['id_lang']
                            );
                        }
                        if (array_key_exists($idLang, $partialPayment) && strip_tags($partialPayment[$idLang])) {
                            $from = [
                                '{preorderPrice}',
                                '{originalPrice}',
                            ];

                            if ($existingPreorder['payment_type'] == 2) {
                                $this->context->smarty->assign([
                                    'wk_current_price' => PreorderHelper::displayPrice(Tools::ps_round(
                                        $priceProduct,
                                        2
                                    ), $currency),
                                    'taxTxt' => $taxTxt,
                                    'original_price' => PreorderHelper::calculatePreorderOriginalPrice(
                                        $productId,
                                        $existingPreorder
                                    ),
                                ]);
                                $to = [
                                    $this->fetch('module:preorder/views/templates/hook/override_product_price_block_to.tpl'),
                                    $this->fetch('module:preorder/views/templates/hook/override_price_block_orignal.tpl'),
                                ];
                            } elseif ($existingPreorder['payment_type'] == 3) {
                                $this->context->smarty->assign([
                                    'wk_current_price' => PreorderHelper::displayPrice(Tools::ps_round(
                                        $priceProduct,
                                        2
                                    ), $currency),
                                    'taxTxt' => $taxTxt,
                                    'original_price' => PreorderHelper::calculatePreorderOriginalPrice(
                                        $productId,
                                        $existingPreorder
                                    ),
                                ]);
                                $to = [
                                    $this->fetch('module:preorder/views/templates/hook/override_product_price_block_to.tpl'),
                                    $this->fetch('module:preorder/views/templates/hook/override_price_block_orignal.tpl'),
                                ];
                            }
                            $partialPaymentContent = str_replace($from, $to, $partialPayment[$idLang]);
                        }
                    }
                    $this->context->smarty->assign([
                        'product' => $params['product'],
                        'call_ajax' => Tools::getValue('action') ? Tools::getValue('action') : Tools::getValue('ajax'),
                        'var' => $var,
                        'attr_id' => $idAttrib,
                        'taxTxt' => $taxTxt,
                        'id_product' => $productId,
                        'ps_module_dir' => _MODULE_DIR_,
                        'remaining_qty' => $remainingQty,
                        'preorder_product' => $existingPreorder,
                        'id_customer' => $this->context->customer->id,
                        'time_left_time_stamp' => $timeLeftTimeStamp,
                        'expected_date' => $existingPreorder['expected_date'],
                        'pscurrency' => new Currency($this->context->currency->id),
                        'prebook_price' => PreorderHelper::displayPrice(
                            Tools::ps_round($preorderProductPrice, 2),
                            $currency
                        ),
                        'original_price_with_tax' => PreorderHelper::displayPrice($originalPriceWithTax, $currency),
                        'prebook_price_with_tax' => PreorderHelper::displayPrice($preorderProductPrice, $currency),
                        'price_with_tax' => PreorderHelper::displayPrice(Tools::ps_round($priceProduct, 2), $currency),
                        'fullpaymentContent' => Configuration::get(
                            'WK_FULL_PAYMENT',
                            $this->context->language->id
                        ),
                        'partialPaymentContent' => $partialPaymentContent,
                        'not_allowed' => $notAllowed,
                    ]);

                    return $this->fetch('module:preorder/views/templates/hook/preorderprice.tpl');
                }
            }
        }
    }

    /**
     * Display preorder complete tab under each product details which product set as preorder.
     *
     * @return [type] [description]
     */
    public function hookDisplayOrderDetail($params)
    {
        $idCustomer = $this->context->customer->id;
        $objOrderDetail = new OrderDetail();
        $idOrder = Tools::getValue('id_order');
        $isPreorderOrder = PreorderProductCustomer::getPreorderDetailsByOrderId($idOrder);
        if ($isPreorderOrder) {
            $orderData = $objOrderDetail->getList($idOrder);
            $preorderProduct = new PreorderProduct();
            $preorderProductCust = new PreorderProductCustomer();
            $currentDate = strtotime(date('Y-m-d H:i:s'));
            foreach ($orderData as $key => $order_val) {
                $idProduct = $order_val['product_id'];
                $idAttrib = $order_val['product_attribute_id'];
                if ($idProduct) {
                    $existingPreorder = $preorderProduct->getExistingPreOrderProduct($idProduct, $idAttrib);
                    if ($existingPreorder) {
                        $custProduct = $preorderProductCust->getCustomerPreOrderByIdPIdCIdO(
                            $idCustomer,
                            $idProduct,
                            $idAttrib,
                            $idOrder
                        );
                        if ($custProduct) {
                            $expected_date = strtotime($existingPreorder['expected_date']);
                            if (($existingPreorder['is_preorder'] == 0
                            && $expected_date <= $currentDate
                            && $custProduct['preorder_complete'] == '0')
                            || ($custProduct['preorder_complete'] == '0'
                            && $existingPreorder['recreation_date'] > $custProduct['date_add'])
                            ) {
                                $orderData[$key]['is_preorder'] = 1; // preorder not complated
                            } elseif ($custProduct['preorder_complete'] == '1') {
                                $orderData[$key]['is_preorder'] = 2; // preorder completed
                            } else {
                                $orderData[$key]['is_preorder'] = 0;   // not the case
                            }
                        } else {
                            $orderData[$key]['is_preorder'] = 3; // customer not bought this preorder
                        }
                    } else {
                        $orderData[$key]['is_preorder'] = 3; // product was not in preorder
                    }
                }
            }
            $this->context->smarty->assign(
                [
                    'preorder_order' => $orderData,
                    'ps_module_dir' => _MODULE_DIR_,
                    'id_order' => $idOrder,
                ]
            );

            return $this->fetch('module:preorder/views/templates/hook/preorder_tab.tpl');
        }
    }

    public function hookDisplayCustomerAccount($aprams)
    {
        $idCustomer = $this->context->customer->id;
        if ($idCustomer) {
            $this->context->smarty->assign([
                'preorderOrder' => $this->context->link->getModuleLink('preorder', 'preorderorderdetails'),
            ]);

            return $this->fetch('module:preorder/views/templates/hook/preorderOrders.tpl');
        }
    }

    public function hookActionOrderStatusPostUpdate($params)
    {
        $idOrder = $params['id_order'];
        $currentState = $params['newOrderStatus']->id;
        if ($currentState == Configuration::get('PS_OS_CANCELED')) {
            $preorderProduct = new PreOrderProduct();
            $objPreorderCustomer = new PreorderProductCustomer();
            $order = new Order((int) $idOrder);
            $products = $order->getProducts();
            foreach ($products as $product) {
                $existingPreorder = $preorderProduct->getExistingPreOrderProduct(
                    $product['product_id'],
                    $product['product_attribute_id']
                );
                if (!empty($existingPreorder)) {
                    if ($existingPreorder['is_preorder'] == 1) {
                        $preorderProduct = new PreorderProduct((int) $existingPreorder['id_wk_preorder_product']);
                        if ($preorderProduct->prebooked_quantity) {
                            $preorderProduct->prebooked_quantity -= $product['product_quantity'];
                            $preorderProduct->save();
                        }
                        $preorderCustomer = $objPreorderCustomer->getCustomerPreOrderByIdPIdCIdO(
                            $order->id_customer,
                            $product['product_id'],
                            $product['product_attribute_id'],
                            $idOrder
                        );
                        $preorderCustomerObj = new PreorderProductCustomer(
                            (int) $preorderCustomer['id_wk_preorder_product_customer']
                        );
                        if ($product['product_quantity'] == $preorderCustomerObj->quantity) {
                            $preorderCustomerObj->disallow_order = 1;
                            $preorderCustomerObj->save();
                        }
                        StockAvailable::setQuantity(
                            $product['product_id'],
                            $product['product_attribute_id'],
                            0
                        );
                    } else {
                        $preorderCustomer = $objPreorderCustomer->getCustomerPreOrderByIdPIdCIdO(
                            $order->id_customer,
                            $product['product_id'],
                            $product['product_attribute_id'],
                            $idOrder
                        );
                        $preorderCustomerObj = new PreorderProductCustomer(
                            (int) $preorderCustomer['id_wk_preorder_product_customer']
                        );

                        if ($preorderCustomerObj) {
                            $preorderCustomerObj->disallow_order = 1;
                            $preorderCustomerObj->update();
                        }
                    }
                }
            }
        }
    }

    public function hookActionFrontControllerSetMedia($params)
    {
        if (Tools::getValue('controller') == 'index') {
            $this->context->smarty->assign([
                'var' => 1,
            ]);
        }
        if ('product' === $this->context->controller->php_self
            || 'index' === $this->context->controller->php_self
            || 'category' === $this->context->controller->php_self
            || 'search' === $this->context->controller->php_self
            || 'best-sales' === $this->context->controller->php_self
            || 'new-products' === $this->context->controller->php_self
            || 'prices-drop' === $this->context->controller->php_self
            || 'order-detail' === $this->context->controller->php_self
            || 'cart' === $this->context->controller->php_self
            || 'allpreorderdetails' === $this->context->controller->php_self
        ) {
            $this->context->controller->registerJavascript(
                'module-preorder-flipclock.min',
                'modules/' . $this->name . '/views/js/flipclock.min.js',
                ['position' => 'bottom', 'priority' => 990]
            );
            $this->context->controller->registerStylesheet(
                'module-preorder-preordertimer_css',
                'modules/' . $this->name . '/views/css/preorder_timer.css'
            );

            $this->context->controller->registerStylesheet(
                'module-preorder-flip-responsive',
                'modules/' . $this->name . '/views/css/flip_responsive_product_page.css'
            );

            $this->context->controller->registerStylesheet(
                'module-preorder-flip-flipclock',
                'modules/' . $this->name . '/views/css/flipclock.css'
            );
            // Only on product page
            $this->context->controller->registerStylesheet(
                'module-preorder-preorder_desc',
                'modules/' . $this->name . '/views/css/preorder_desc.css'
            );
        }
        if ('history' == $this->context->controller->php_self
        || 'order-detail' == $this->context->controller->php_self) {
            $this->context->controller->registerJavascript(
                'module-preorder-preordercheck',
                'modules/' . $this->name . '/views/js/preordercheck.js',
                ['position' => 'bottom', 'priority' => 990]
            );
        }

        if ('product' == $this->context->controller->php_self
        || 'allpreorderdetails' === $this->context->controller->php_self
        ) {
            $this->context->controller->registerJavascript(
                'module-preorder-preordercustomprice',
                'modules/' . $this->name . '/views/js/preordercustomprice.js',
                ['position' => 'bottom', 'priority' => 990]
            );
            $idProduct = Tools::getValue('id_product');
            $objPreorder = new PreorderProduct();
            $isPreorderExit = $objPreorder->checkExsitingPsPreorder($idProduct);
            $isPreorder = false;
            if (!empty($isPreorderExit)) {
                foreach ($isPreorderExit as $preorder) {
                    if ($preorder['is_preorder'] == 1) {
                        $isPreorder = true;
                        break;
                    }
                }
            }
            Media::addJsDef(
                [
                    'addtocart_btn' => $this->l('Add to cart'),
                    'id_customer' => $this->context->customer->id,
                ]
            );
            if ($isPreorder) {
                Media::addJsDef([
                    'product_id' => Tools::getValue('id_product'),
                    'checkpreorder_url' => $this->context->link->getModuleLink('preorder', 'existspreordercustomer'),
                ]);

                $this->context->controller->registerJavascript(
                    'module-preorder-preordertimer',
                    'modules/' . $this->name . '/views/js/preorder_timer.js'
                );
            }
        } elseif ('order-detail' == $this->context->controller->php_self) {
            Media::addJsDef([
                'notavail' => $this->l('Not available'),
                'preordertitle' => $this->l('Preorder process'),
                'complete_preorder' => $this->l('Complete preorder'),
                'completed_preorder' => $this->l('Preorder completed'),
                'preorder_process' => $this->context->link->getPageLink('cart', true, null),
                'preorder_process_url' => $this->context->link->getModuleLink(
                    $this->name,
                    'process',
                    ['add' => 1, 'id_order' => Tools::getValue('id_order')]
                ),
            ]);
            $this->context->controller->registerJavascript(
                'module-preorder-preorderprocess',
                'modules/' . $this->name . '/views/js/preorderprocess.js',
                ['position' => 'bottom', 'priority' => 990]
            );
        }
        $this->context->controller->addJqueryPlugin('growl', null, false);
        $this->context->controller->registerStylesheet(
            'growl-css',
            'js/jquery/plugins/growl/jquery.growl.css'
        );
    }

    /**
     * Change add to cart button with preoder button on home page as well as on product detail page.
     *
     * @param [type] $params [array containing information of the product]
     *
     * @return [type] [description]
     */
    public function hookDisplayHeader($params)
    {
        $preorderObj = new PreOrderProduct();
        if ($this->context->customer->id) {
            $customer = 1;
        } else {
            $customer = 0;
        }

        Media::addJsDef(
            [
                'customer' => $customer,
                'preorder_now' => $this->l('Preorder now'),
                'sold_out' => $this->l('Sold out'),
                'static_token' => Tools::getToken(false),
                'add_to_cart' => $this->l('Add to cart'),
                'iso_code' => $this->context->language->iso_code,
                'current_cust_id' => $this->context->customer->id,
                'notenoughstock' => $this->l('There is not enough stock to buy preorder product.'),
                'loginerror' => $this->l('Please login to buy preorder product.'),
                'specificerror' => $this->l('You have not set any specific price for preorder product.'),
                'addresserror' => $this->l('You can complete preorder on your current address.'),
                'invalidPrice' => $this->l('Please enter valid price.'),
                'noLonger' => $this->l('Pre booking is no longer available.'),
                'notAvailable' => $this->l('Sorry! Preorder has been sold out.'),
                'loginreq' => $this->l('To buy preorder product you need to login first.'),
                'minPrice' => $this->l('Price should be greater than or equal to the minimum preorder price.'),
                'customPriceLower' => $this->l('Custom price must be lower than product price.'),
                'customPrice' => $this->l('Custom price already exist for you. Please change the custom price.'),
                'specificProcess' => $this->context->link->getModuleLink('preorder', 'specificprocess'),
                'checkpreorder_url' => $this->context->link->getModuleLink('preorder', 'existspreordercustomer'),
                'out_of_stock' => $this->l('Out of stock'),
            ]
        );
        PreOrderProduct::autoUpdateAllPreorder(); // updating existing preorder products into database
    }

    /**
     * If voucher has been applied on preorder product but not display at first time,
     * to show it we are here reloading the order controller with specific condition.
     *
     * @param [type] $params [summary of the cart]
     *
     * @return [type] [description]
     */
    public function hookActionCartSummary($params)
    {
        $redirect = false;
        $preorderObj = new PreOrderProduct();
        $idCustomer = $this->context->customer->id;
        $currentAddressDelivery = $this->context->cart->id_address_delivery;

        $objAddress = new Address($currentAddressDelivery);

        $cartProducts = $this->context->cart->getProducts();
        if (!empty($cartProducts) && $idCustomer) {
            foreach ($cartProducts as $prods) {
                $currentPid = $prods['id_product'];
                $idAttrib = $prods['id_product_attribute'];
                $existingPreorderProduct = $preorderObj->getExistingPreOrderProduct($currentPid, $idAttrib);
                if (!empty($existingPreorderProduct)
                && $existingPreorderProduct['is_preorder'] == 0
                && $existingPreorderProduct['payment_type'] != 1
                ) {
                    if (empty($params['discounts'])) {
                    } else {
                        foreach ($params['discounts'] as $dis_prod) {
                            if ($currentPid == $dis_prod['reduction_product']) {
                                $redirect = false;
                            }
                        }
                    }
                }
            }
            if ($redirect) {
                if (Configuration::get('PS_ORDER_PROCESS_TYPE') == 1) {
                    Tools::redirect('index.php?controller=order-opc&addingCartRule=1');
                }
                Tools::redirect('index.php?controller=order&addingCartRule=1');
            }
        }

        return $params;
    }

    /**
     * [hookActionValidateOrder -> saving the updating voucher and saving preorder details into database ].
     *
     * @param [type] $params [Details of the order]
     *
     * @return [type] [description]
     */
    public function hookActionValidateOrder($params)
    {
        $customer_id = $params['order']->id_customer;
        $idOrder = $params['order']->id;
        $idCountry = $this->context->country->id;
        $currentAddressDelivery = $params['order']->id_address_delivery;

        $objAddress = new Address($currentAddressDelivery);
        $idState = $objAddress->id_state;

        $objOrderDetail = new OrderDetail();
        $orderData = $objOrderDetail->getList($idOrder);
        $preorderProduct = new PreOrderProduct();
        $preorderCust = new PreorderProductCustomer();
        $checkCookie = isset(Context::getContext()->cookie->preorder_complete);
        if ($checkCookie) {
            $checkCookie = (array) json_decode(Context::getContext()->cookie->preorder_complete);
            $checkCookie = array_unique($checkCookie);
        }
        $wkSendpreOrderMail = false;
        $preorderProductName = '';
        if (!empty($orderData)) {
            foreach ($orderData as $data) {
                $idAttrib = $data['product_attribute_id'];
                $idProduct = $data['product_id'];
                $boughtQuantity = $data['product_quantity'];
                $existingPreorder = $preorderProduct->getExistingPreOrderProduct($idProduct, $idAttrib);
                if (!empty($existingPreorder)) {
                    // if currency is changed by buyer then recalculated preorder price
                    $currentCurrency = $this->context->currency->id;
                    $preorderCurrency = $existingPreorder['id_default_currency'];
                    if ($currentCurrency != $preorderCurrency) {
                        $currency = new Currency($preorderCurrency);
                        $oldCurrency = new Currency($this->context->currency->id);
                        $data['total_price_tax_incl'] = Tools::convertPriceFull(
                            $data['total_price_tax_incl'],
                            $oldCurrency,
                            $currency
                        );
                        $data['total_price_tax_excl'] = Tools::convertPriceFull(
                            $data['total_price_tax_excl'],
                            $oldCurrency,
                            $currency
                        );
                        $data['unit_price_tax_incl'] = Tools::convertPriceFull(
                            $data['unit_price_tax_incl'],
                            $oldCurrency,
                            $currency
                        );
                        $data['unit_price_tax_excl'] = Tools::convertPriceFull(
                            $data['unit_price_tax_excl'],
                            $oldCurrency,
                            $currency
                        );
                    }

                    $originalPrice = $existingPreorder['original_price'] + $existingPreorder['impact_price'];
                    $checkCookie = $preorderCust->checkEntryExistsWithoutOrder(
                        $idProduct,
                        $idAttrib,
                        $customer_id,
                        $params['order']->id_shop
                    );
                    if ($checkCookie) {
                        $isCustBought = $preorderCust->getCustomerPreOrderProductByIdPro(
                            $customer_id,
                            $idProduct,
                            $idAttrib,
                            $idCountry,
                            $idState,
                            $checkCookie['old_order_id']
                        );
                        if ($isCustBought) {
                            $j = 0;
                            foreach ($isCustBought as $custValue) {
                                if ($j == $boughtQuantity) {
                                    break;
                                }
                                $taxAmt = $data['total_price_tax_incl'] - $data['total_price_tax_excl'];
                                $prevBoughtQty = $custValue['quantity'] - $custValue['complete_qty'];
                                $paidAmt = $data['total_price_tax_excl'];
                                $paidAmtWithTax = $data['total_price_tax_incl'];
                                $objPreorderCust = new PreorderProductCustomer(
                                    $custValue['id_wk_preorder_product_customer']
                                );
                                for ($i = 0; $i < $prevBoughtQty; ++$i) {
                                    if ($i == $boughtQuantity) {
                                        break;
                                    }
                                    $objPreorderCust->paid_amt += $paidAmt;
                                    $remainingPreorderAmt = $objPreorderCust->remaining_amt - $paidAmtWithTax;
                                    if ($remainingPreorderAmt < 0) {
                                        $objPreorderCust->remaining_amt = 0.00;
                                    } else {
                                        $objPreorderCust->remaining_amt = Tools::ps_round($remainingPreorderAmt, 2);
                                    }
                                    $objPreorderCust->tax_amt += $taxAmt;
                                    ++$objPreorderCust->complete_qty;
                                    $objPreorderCust->country = $idCountry;
                                    $objPreorderCust->state = $idState;
                                    $objPreorderCust->update();
                                    ++$j;
                                }
                                $objPreorderCust->insertOrderID(
                                    $custValue['id_wk_preorder_product_customer'],
                                    $idOrder
                                );

                                if ($objPreorderCust->quantity == $objPreorderCust->complete_qty) {
                                    $objPreorderCust->preorder_complete = 1;
                                    //  mapped order id in case of 1 order
                                    $objPreorderCust->update();
                                    // new code to manage quantity
                                    $idAttr = $objPreorderCust->attribute_id;
                                    $quantity = StockAvailable::getQuantityAvailableByProduct(
                                        $objPreorderCust->product_id,
                                        $objPreorderCust->attribute_id
                                    );
                                    $afterOrderQuantity = (int) $quantity + (int) $objPreorderCust->complete_qty;
                                    if ((int) $afterOrderQuantity <= 0) {
                                        $afterOrderQuantity = 0;
                                    }
                                    if ($objPreorderCust) {
                                        if (Shop::CONTEXT_ALL == Shop::getContext()) {
                                            StockAvailable::setQuantity(
                                                $idProduct,
                                                $idAttr,
                                                $afterOrderQuantity,
                                                null,
                                                false
                                            );
                                        } else {
                                            StockAvailable::setQuantity($idProduct, $idAttr, $afterOrderQuantity);
                                        }
                                    }
                                    // end
                                }
                            }
                        }
                        $checkCookie = $preorderCust->deteleTempCompletionEntry(
                            $idProduct,
                            $idAttrib,
                            $customer_id,
                            $params['order']->id_shop
                        );
                    } elseif ($existingPreorder['is_preorder'] == 1 && $existingPreorder['payment_type'] != '1') {
                        // partial payment
                        $objpreordercust = new PreorderProductCustomer();
                        $existCart = $objpreordercust->getCustomerPreOrderByIdPIdCIdO(
                            $customer_id,
                            $existingPreorder['product_id'],
                            $existingPreorder['attribute_id'],
                            $idOrder
                        );
                        if (!$existCart) {
                            $taxAmt = $data['total_price_tax_incl'] - $data['total_price_tax_excl'];
                            $originalPriceWithTax = $originalPrice;
                            if ($taxAmt > 0) {
                                $taxRule = PreOrderProduct::getPreorderTaxRulesByGroupId(
                                    $this->context->language->id,
                                    $data['id_tax_rules_group'],
                                    $this->context->country->id
                                );
                                $rate = $taxRule['rate'];
                                if ($rate > 0) {
                                    $original_tax = $originalPrice * $rate / 100;
                                    $originalPriceWithTax = $originalPrice + $original_tax;
                                }
                            }
                            // guest preorder if any
                            if (Configuration::get('PS_GUEST_CHECKOUT_ENABLED')) {
                                if (Configuration::get('WK_GUEST_PREORDER_ENABLED')) {
                                    $this->transformGuestIntoCustomer($customer_id);
                                }
                            }
                            $objpreordercust->product_id = $idProduct;
                            $objpreordercust->attribute_id = $idAttrib;
                            $objpreordercust->customer_id = $customer_id;
                            $objpreordercust->country = $idCountry;
                            $objpreordercust->state = $idState;
                            $objpreordercust->order_id = $data['id_order'];
                            $objpreordercust->quantity = $boughtQuantity;
                            $objpreordercust->limited_time = Configuration::get('WK_LIMITED_TIME');
                            $objpreordercust->allowed_days = Configuration::get('WK_ALLOW_DAYS');
                            $objpreordercust->stock_rollback = Configuration::get('WK_STOCK_ROLLBACK');
                            $objpreordercust->payment_type = $existingPreorder['payment_type'];
                            $objpreordercust->paid_amt = $data['total_price_tax_excl'];
                            if ($existingPreorder['payment_type'] == 2) {
                                $originalPriceWithTax = PreorderHelper::calculatePreorderOriginalPrice(
                                    $idProduct,
                                    $existingPreorder,
                                    false,
                                    $objAddress,
                                    0,
                                    false,
                                    (float) $taxAmt
                                ) * $boughtQuantity;
                            } else {
                                $originalPriceWithTax = PreorderProduct::getPriceStatic(
                                    (int) $idProduct,
                                    true,
                                    $existingPreorder['attribute_id'],
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
                                ) * $boughtQuantity;
                            }
                            // Convert price back to default currency
                            if ($currentCurrency != $preorderCurrency) {
                                $originalPriceWithTax = Tools::convertPriceFull(
                                    $originalPriceWithTax,
                                    $oldCurrency,
                                    $currency
                                );
                            }
                            $objpreordercust->remaining_amt = Tools::ps_round(
                                $originalPriceWithTax - $data['total_price_tax_incl'],
                                2
                            );
                            // $objpreordercust->original_price = $originalPrice;
                            $objpreordercust->original_price = $originalPriceWithTax;
                            $objpreordercust->tax_amt = $taxAmt;
                            $objpreordercust->shipping_amt = $data['total_shipping_price_tax_incl'];
                            $objpreordercust->preorder_complete = 0;
                            $objpreordercust->booked_date = date('Y-m-d H:i:s');
                            if ($objpreordercust->save()) {
                                $preorderProduct = new PreOrderProduct($existingPreorder['id_wk_preorder_product']);
                                $preorderProduct->prebooked_quantity += $boughtQuantity;
                                $preorderProduct->update();
                            }
                            $wkSendpreOrderMail = true;
                            $preorderProductName .= $data['product_name'] . '<br>';
                        }
                    } elseif ($existingPreorder['is_preorder'] == 1 && $existingPreorder['payment_type'] == '1') {
                        // Full payment
                        $objpreordercust = new PreorderProductCustomer();
                        $taxAmt = $data['total_price_tax_incl'] - $data['total_price_tax_excl'];
                        $objpreordercust->product_id = $data['product_id'];
                        $objpreordercust->attribute_id = $idAttrib;
                        $objpreordercust->customer_id = $customer_id;
                        $objpreordercust->order_id = $data['id_order'];
                        $objpreordercust->payment_type = $existingPreorder['payment_type'];
                        $objpreordercust->tax_amt = $taxAmt;
                        $objpreordercust->shipping_amt = $params['order']->total_shipping;
                        $objpreordercust->preorder_complete = '1';
                        $objpreordercust->quantity = $boughtQuantity;
                        $objpreordercust->paid_amt = $data['total_price_tax_excl'];
                        $objpreordercust->remaining_amt = 0;
                        $objpreordercust->original_price = Tools::ps_round(
                            Product::getPriceStatic($data['product_id'], true, $existingPreorder['attribute_id'])
                            * $boughtQuantity,
                            6
                        );
                        $objpreordercust->country = $idCountry;
                        $objpreordercust->state = $idState;
                        $objpreordercust->limited_time = Configuration::get('WK_LIMITED_TIME');
                        $objpreordercust->allowed_days = Configuration::get('WK_ALLOW_DAYS');
                        $objpreordercust->stock_rollback = Configuration::get('WK_STOCK_ROLLBACK');
                        $objpreordercust->booked_date = date('Y-m-d H:i:s');
                        if ($objpreordercust->save()) {
                            $preorderProduct = new PreOrderProduct($existingPreorder['id_wk_preorder_product']);
                            $preorderProduct->prebooked_quantity += $data['product_quantity'];
                            $preorderProduct->update();
                        }
                        $wkSendpreOrderMail = true;
                        $preorderProductName .= $data['product_name'] . '<br>';
                    }
                }
            }

            if ($wkSendpreOrderMail) {
                $this->preOrderMail($params['order']->reference, $preorderProductName);
            }
        }
    }

    public function transformGuestIntoCustomer($idCustomer)
    {
        $customer = new Customer($idCustomer);
        if (Configuration::get('PS_GUEST_CHECKOUT_ENABLED')) {
        }
        if (!$customer->isGuest()) {
            return false;
        }

        $password = Tools::passwdGen(8, 'RANDOM');

        if (!Validate::isPlaintextPassword($password)) {
            return false;
        }
        $idLang = $this->context->language->id;
        $language = new Language($idLang);
        if (!Validate::isLoadedObject($language)) {
            $language = Context::getContext()->language;
        }

        /** @var PrestaShop\PrestaShop\Core\Crypto\Hashing $crypto */
        $crypto = ServiceLocator::get('\\PrestaShop\\PrestaShop\\Core\\Crypto\\Hashing');
        $customer->is_guest = 0;
        $customer->passwd = $crypto->hash($password);
        $customer->cleanGroups();
        $customer->addGroups([Configuration::get('PS_CUSTOMER_GROUP')]);
        $customer->id_default_group = Configuration::get('PS_CUSTOMER_GROUP');
        if ($customer->update()) {
            $vars = [
                '{firstname}' => $customer->firstname,
                '{lastname}' => $customer->lastname,
                '{fullname}' => $customer->firstname . ' ' . $customer->lastname,
                '{email}' => $customer->email,
                '{password}' => $password,
            ];
            Mail::Send(
                (int) $idLang,
                'preorder_guest_to_customer',
                Context::getContext()->getTranslator()->trans(
                    'To complete the preorder your account has been transformed from guest account to customer account',
                    [],
                    'Emails.Subject',
                    $language->locale
                ),
                $vars,
                $customer->email,
                $customer->firstname . ' ' . $customer->lastname,
                null,
                null,
                null,
                null,
                _PS_MODULE_DIR_ . 'preorder/mails/',
                false,
                (int) $customer->id_shop
            );

            return true;
        }

        return false;
    }

    public function preorderMail($orderReference, $preorderProductName)
    {
        $idLang = $this->context->language->id;
        $objCustomer = new Customer($this->context->customer->id);

        $templateVars = [
            '{firstname}' => $objCustomer->firstname,
            '{lastname}' => $objCustomer->lastname,
            '{order_name}' => $orderReference,
            '{shop_name}' => $this->context->shop->name,
            '{preorder_product_name}' => $preorderProductName,
        ];

        $templatePath = _PS_MODULE_DIR_ . 'preorder/mails/';
        Mail::Send(
            (int) $idLang,
            'preorder_reserved',
            Mail::l('Your preorder product has been reserved.', (int) $idLang),
            $templateVars,
            $objCustomer->email,
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
    }

    public function hookActionOrderHistoryAddAfter($params)
    {
        if (isset($params['order_history']->id_order_state)) {
            $idOrderState = $params['order_history']->id_order_state;
            if (($idOrderState == Configuration::get('PS_OS_OUTOFSTOCK_PAID'))
            || ($idOrderState == Configuration::get('PS_OS_OUTOFSTOCK_UNPAID'))
            ) { // After backorder
                if (isset($params['order_history']->id_order)) {
                    $idOrder = $params['order_history']->id_order;
                    $order = new Order($idOrder);
                    $idCart = $order->id_cart;
                    $preorderProduct = new PreOrderProduct();
                    $orders = $preorderProduct->getOrdersByIdCart($idCart);
                    $objOrderDetail = new OrderDetail();
                    if (!empty($orders)) {
                        foreach ($orders as $order) {
                            $idOrder = $order['id_order'];
                            $orderData = $objOrderDetail->getList($idOrder);
                            if (!empty($orderData)) {
                                $preorderHas = false;
                                foreach ($orderData as $data) {
                                    $idAttrib = $data['product_attribute_id'];
                                    $idProduct = $data['product_id'];
                                    $preorderProduct = new PreOrderProduct();
                                    $existingPreorder = $preorderProduct->getExistingPreOrderProduct($idProduct, $idAttrib);
                                    if (!empty($existingPreorder)) {
                                        if ($existingPreorder['is_preorder'] == 1) {
                                            $preorderHas = true;
                                        }
                                    }
                                    if ($preorderHas) {
                                        $this->updateOrderStatusAsPreorderStatus($idOrder);
                                        break;
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * [hookDisplayOrderConfirmation -> create voucher with the amount of preorder price].
     *
     * @param [type] $params [array containing cart details which order has completed]
     *
     * @return [type] [description]
     */
    public function hookDisplayOrderConfirmation($params)
    {
        $idLang = $this->context->language->id;
        $idOrder = $params['order']->id;
        $idCart = $params['order']->id_cart;
        $preorderProduct = new PreOrderProduct();
        $orders = $preorderProduct->getOrdersByIdCart($idCart);
        $objOrderDetail = new OrderDetail();
        $this->smarty->assign(
            [
                'shop_name' => [$this->context->shop->name],
            ]
        );
        if (!empty($orders)) {
            $preorderTemp = false;
            foreach ($orders as $order) {
                $idOrder = $order['id_order'];
                $orderData = $objOrderDetail->getList($idOrder);
                if (!empty($orderData)) {
                    $preorderHas = false;
                    // Set preorder product qty to Zero again after order
                    foreach ($orderData as $data) {
                        $idAttrib = $data['product_attribute_id'];
                        $idProduct = $data['product_id'];
                        $preorderProduct = new PreOrderProduct();
                        $existingPreorder = $preorderProduct->getExistingPreOrderProduct($idProduct, $idAttrib);
                        if (!empty($existingPreorder)) {
                            if ($existingPreorder['is_preorder'] == 1) {
                                StockAvailable::setQuantity(
                                    $data['product_id'],
                                    $data['product_attribute_id'],
                                    0
                                );
                            }
                        }
                    }
                    foreach ($orderData as $data) {
                        $idAttrib = $data['product_attribute_id'];
                        $idProduct = $data['product_id'];
                        $preorderProduct = new PreOrderProduct();
                        $existingPreorder = $preorderProduct->getExistingPreOrderProduct($idProduct, $idAttrib);
                        if (!empty($existingPreorder)) {
                            if ($existingPreorder['is_preorder'] == 1) {
                                $preorderHas = true;
                            }
                        }
                        if ($preorderHas) {
                            $preorderTemp = true;
                            // $this->updateOrderStatusAsPreorderStatus($idOrder);
                            break;
                        }
                    }
                }
            }

            $state = $params['order']->getCurrentState();
            $orderStateData = OrderState::getOrderStates($idLang);
            if ($preorderTemp && $orderStateData) {
                foreach ($orderStateData as $orderState) {
                    if ($orderState['id_order_state'] == $state) {
                        $currency = new Currency($params['order']->id_currency, false);
                        $this->smarty->assign(
                            [
                                'total_to_pay' => PreorderHelper::displayPrice(
                                    $params['order']->total_paid,
                                    $currency
                                ),
                                'status' => 'ok',
                                'id_order' => $idOrder, ]
                        );
                        if (isset($params['order']->reference) && !empty($params['order']->reference)) {
                            $this->smarty->assign('reference', $params['order']->reference);
                        }

                        return $this->fetch('module:preorder/views/templates/hook/payment_return.tpl');
                    } else {
                        $this->smarty->assign('status', 'failed');
                    }
                }
            }
        }
    }

    public function updateOrderStatusAsPreorderStatus($idOrder)
    {
        $orderObj = new Order($idOrder);
        $oldOrderStatus = $orderObj->current_state;
        $orderObj->current_state = Configuration::get('PS_OS_PREORDER');
        if ($orderObj->update()) {
            PreorderHelper::updateOrderHistory(Configuration::get('PS_OS_PREORDER'), $idOrder, $oldOrderStatus);
        }
    }

    /**
     * Create new order status with name of Pre-Order Product and set image for order status.
     *
     * @return [type] [description]
     */
    public function insertNewStatusPreorder()
    {
        if (!Configuration::get('PS_OS_PREORDER')) {
            $objPreorderHelper = new PreorderHelper();
            if ($objPreorderHelper->createNewOrderStatus()) {
                return true;
            }

            return false;
        } else {
            return true;
        }
    }

    public function revertPreorderProductAsNormal($disable = 0)
    {
        $objPreorder = new PreOrderProduct();
        $allPreorder = $objPreorder->getAllPreOrderProduct();
        if ($allPreorder && !empty($allPreorder)) {
            foreach ($allPreorder as $preorder) {
                if ($preorder && !empty($preorder)) {
                    if ($disable) {
                        $objPreorder = new PreOrderProduct($preorder['id_wk_preorder_product']);
                        $idProduct = $objPreorder->product_id;
                        $originalPrice = $objPreorder->original_price;
                        $idAttr = $objPreorder->attribute_id;
                        $objPreorder->setCarriers($idProduct, $objPreorder->id_applied_shipping);
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
                        StockAvailable::setProductOutOfStock($idProduct, false); // Set product deny order
                    } else {
                        $idProduct = $preorder['product_id'];
                        $idAttrib = $preorder['attribute_id'];
                        $quantity = $preorder['quantity'];
                        if (Shop::CONTEXT_ALL == Shop::getContext()) {
                            StockAvailable::setQuantity((int) $idProduct, (int) $idAttrib, (int) $quantity, null, false);
                        } else {
                            StockAvailable::setQuantity((int) $idProduct, (int) $idAttrib, (int) $quantity, null);
                        }
                        if ($preorder['payment_type'] == 1) {
                        } else {
                            if ($preorder['attribute_id'] == 0) {
                                $objPreorder->setOriginalProduct(
                                    $idProduct,
                                    $preorder['original_price'],
                                    $quantity,
                                    $idAttrib
                                );
                            } else {
                                $objPreorder->setImpactPrice(
                                    $idProduct,
                                    $idAttrib,
                                    $preorder['impact_price']
                                );
                            }
                        }
                    }
                }
            }
        }

        return true;
    }

    /**
     * [deletePreorderShipping -> deleting preorder shipping].
     *
     * @return [type] [description]
     */
    public function deletePreorderShipping()
    {
        PreorderHelper::deletePreorderShippingData();

        return true;
    }

    public function callInstallTab()
    {
        $this->installTab('AdminPsWkPreorder', 'Preorder');
        $this->installTab('AdminWkPreorder', 'Preorder', 'AdminPsWkPreorder');
        $this->installTab('AdminNewPreorder', 'Products', 'AdminWkPreorder');
        $this->installTab('AdminPreorderOrders', 'Orders', 'AdminWkPreorder');

        return true;
    }

    public function installTab($className, $tabName, $tabParentName = false)
    {
        $tab = new Tab();
        $tab->active = 1;
        $tab->class_name = $className;
        if ($className == 'AdminWkPreorder') { // Tab name for which you want to add icon
            $tab->icon = 'archive'; // Material Icon name
        }
        $tab->name = [];
        foreach (Language::getLanguages(true) as $lang) {
            $tab->name[$lang['id_lang']] = $tabName;
        }
        if ($tabParentName) {
            $tab->id_parent = (int) Tab::getIdFromClassName($tabParentName);
        } else {
            $tab->id_parent = 0;
        }

        $tab->module = $this->name;

        return $tab->add();
    }

    public function registerPreorderHook()
    {
        return $this->registerHook([
            'displayProductPriceBlock',
            'actionFrontControllerSetMedia',
            'displayOrderConfirmation',
            'displayHeader',
            'displayOrderDetail',
            'actionOrderStatusPostUpdate',
            'actionValidateOrder',
            'actionCartSummary',
            'displayCustomerAccount',
            'actionOrderHistoryAddAfter',
            'displayOverrideTemplate',
            'actionObjectCombinationDeleteAfter',
            'displayAdminOrderMain',
        ]);
    }

    public function hookDisplayAdminOrderMain($params)
    {
        $orderId = $params['id_order'];
        $idProductCustomers = PreorderProductCustomer::getIdProductCustomerByOrderId($orderId);
        $preorderDetails = PreorderProductCustomer::getPreorderDetailsByOrderId($params['id_order']);

        $performedOrder = 0;
        if ($idProductCustomers || $preorderDetails) {
            $currency = new Currency($this->context->currency->id);
            $productDetails = [];
            if ($idProductCustomers && !empty($idProductCustomers)) {
                $details = [];
                $orderDetails = OrderDetail::getList($params['id_order']);
                foreach ($idProductCustomers as $productCustomer) {
                    $preorderDetail = PreorderProductCustomer::getPreorderDetailsByIdPreorderCustomer($productCustomer['id_wk_preorder_product_customer']);
                    foreach ($orderDetails as $ordDetail) {
                        if ($ordDetail['product_id'] == $preorderDetail['product_id']
                        && $ordDetail['product_attribute_id'] == $preorderDetail['attribute_id']) {
                            $idPreCust = $preorderDetail['id_wk_preorder_product_customer'];

                            $preorderOrderDetails = OrderDetail::getList($preorderDetail['order_id']);
                            foreach ($preorderOrderDetails as $pre) {
                                if ($pre['product_id'] == $ordDetail['product_id']
                                    && $pre['product_attribute_id'] == $ordDetail['product_attribute_id']) {
                                    $paid_amt = $pre['total_price_tax_incl'];
                                    $quantity = $pre['product_quantity'];
                                }
                            }
                            $booked_date = $preorderDetail['booked_date'];

                            $orderReference = Order::getUniqReferenceOf($preorderDetail['order_id']);
                            $link = $this->context->link->getAdminLink(
                                'AdminOrders',
                                true,
                                [],
                                [
                                    'id_order' => $preorderDetail['order_id'],
                                    'vieworder' => '1',
                                ]
                            );

                            $details = [
                                'product_name' => $ordDetail['product_name'],
                                'quantity' => $quantity,
                                'paid_amt' => PreorderHelper::displayPrice($paid_amt, $currency),
                                'order_date' => $booked_date,
                                'order_reference' => $orderReference,
                                'order_link' => $link,
                            ];
                        }
                    }
                }
                if (!empty($details)) {
                    $performedOrder = 2;
                    $productDetails[] = $details;
                }
            } else {
                $orderDetails = OrderDetail::getList($params['id_order']);
                foreach ($preorderDetails as $preDetail) {
                    $details = [];
                    foreach ($orderDetails as $ordDetail) {
                        if ($ordDetail['product_id'] == $preDetail['product_id']
                        && $ordDetail['product_attribute_id'] == $preDetail['attribute_id']) {
                            $idPreCust = $preDetail['id_wk_preorder_product_customer'];
                            $paid_amt = $preDetail['paid_amt'] + $preDetail['tax_amt'];
                            $orderReference = $this->l('-');

                            $link = false;
                            if ($preDetail['preorder_complete']) {
                                $orderMapDetails = PreorderProductCustomer::getOrderIdByIdPreCustomer($idPreCust);
                                if ($orderMapDetails && $idOrder = $orderMapDetails['order_id']) {
                                    $orderReference = Order::getUniqReferenceOf($idOrder);
                                    $link = $this->context->link->getAdminLink(
                                        'AdminOrders',
                                        true,
                                        [],
                                        [
                                            'id_order' => $idOrder,
                                            'vieworder' => '1',
                                        ]
                                    );
                                }
                            }

                            $details = [
                                'product_name' => $ordDetail['product_name'],
                                'quantity' => $ordDetail['product_quantity'],
                                'paid_amt' => PreorderHelper::displayPrice($paid_amt, $currency),
                                'rem_amt' => PreorderHelper::displayPrice($preDetail['remaining_amt'], $currency),
                                'status' => $preDetail['preorder_complete'] ? $this->l('Completed') : $this->l('Not completed'),
                                'order_reference' => $orderReference,
                                'order_link' => $link,
                            ];
                        }
                    }
                    if (!empty($details)) {
                        $performedOrder = 1;
                        $productDetails[] = $details;
                    }
                }
            }

            $this->context->smarty->assign([
                'product_details' => $productDetails,
                'performed_orders' => $performedOrder,
            ]);

            return $this->fetch('module:preorder/views/templates/hook/order_page_details.tpl');
        }
    }

    /**
     * Delete preorder product if combination deleted from catalog
     *
     * @param array $params
     *
     * @return void
     */
    public function hookActionObjectCombinationDeleteAfter($params)
    {
        $idProduct = (int) $params['object']->id_product;
        $idProductAttr = (int) $params['object']->id;
        $objPreorderProduct = new PreorderProduct();
        if ($idProduct) {
            if ($preorderProduct = $objPreorderProduct->getExistingActivePreOrderProduct($idProduct, $idProductAttr)) {
                $objPreorderProduct = new PreorderProduct((int) $preorderProduct['id_wk_preorder_product']);
                $objPreorderProduct->delete();
            }
        }
    }

    /** [createNewShippingMethodForPreorder - creating new shipping method for preorder] */
    public function createNewShippingMethodForPreorder()
    {
        $carrier = new Carrier();
        $carrier->name = 'Preorder Shipping';
        $carrier->active = true;
        $carrier->url = '';
        $carrier->position = Carrier::getHigherPosition() + 1;
        $carrier->shipping_method = 2;
        $carrier->max_width = 0;
        $carrier->max_height = 0;
        $carrier->max_depth = 0;
        $carrier->max_weight = 0;
        $carrier->grade = 0;
        $carrier->id_tax_rules_group = 0;
        $carrier->id_zone = 1;
        $carrier->deleted = 0;
        $carrier->shipping_handling = false;
        $carrier->range_behavior = 0;
        $carrier->is_module = true;
        $carrier->shipping_external = true;
        $carrier->external_module_name = $this->name;
        $carrier->need_range = 1;

        foreach (Language::getLanguages(true) as $lang) {
            $carrier->delay[$lang['id_lang']] = 'Free';
        }

        if ($carrier->add()) {
            $groups = Group::getGroups(true);
            foreach ($groups as $group) {
                PreorderHelper::insertIntpCarrierGroup($carrier->id, $group['id_group']);
            }

            $rangePrice = new RangePrice();
            $rangePrice->id_carrier = $carrier->id;
            $rangePrice->delimiter1 = '0';
            $rangePrice->delimiter2 = '100000';
            $rangePrice->add();

            $rangeWeight = new RangeWeight();
            $rangeWeight->id_carrier = $carrier->id;
            $rangeWeight->delimiter1 = '0';
            $rangeWeight->delimiter2 = '100000';
            $rangeWeight->add();

            $zones = Zone::getZones(true);
            foreach ($zones as $zone) {
                PreorderHelper::insertIntoCarrierZone($carrier->id, $zone['id_zone']);
                PreorderHelper::insertIntoDelivery($carrier->id, $rangePrice->id, $zone['id_zone']);
            }

            Configuration::updateValue('WK_PREORDER_SHIPPING', (int) $carrier->id);
        }

        return true;
    }

    public function installConfigData()
    {
        Configuration::updateValue('WK_GUEST_PREORDER_ENABLED', '1');
        // Configuration::updateValue('preorder_voucher_expiry', 'year');
        Configuration::updateValue('WK_SHOW_PRODUCT_AVAILABLE_ON', '1');
        Configuration::updateValue('WK_SHOW_PAYMENT_TYPE', '1');
        Configuration::updateValue('WK_RESTRICT_CHECKOUT', '0');
        Configuration::updateValue('price_type', '0');

        $WK_FULL_PAYMENT = [];
        $WK_PARTIAL_PAYMENT = [];

        foreach (Language::getLanguages(false) as $language) {
            $WK_FULL_PAYMENT[$language['id_lang']] = $this->l('This is a pre-order product. Once it is available in stock, your order will be dispatched.');

            $WK_PARTIAL_PAYMENT[$language['id_lang']] = $this->l('Pay preorder price {preorderPrice} instead of paying full {originalPrice}');
        }
        Configuration::updateValue('WK_FULL_PAYMENT', $WK_FULL_PAYMENT, true);
        Configuration::updateValue('WK_PARTIAL_PAYMENT', $WK_PARTIAL_PAYMENT, true);

        $groups = Group::getGroups($this->context->language->id);
        $groupBox = [];
        foreach ($groups as $group) {
            $groupBox[] = $group['id_group'];
        }

        if ($countries = Country::getCountries($this->context->language->id)) {
            $countryIds = [];
            foreach ($countries as $country) {
                $countryIds[] = $country['id_country'];
            }
            $wkAllowedCountries = json_encode($countryIds);
        } else {
            $wkAllowedCountries = json_encode(Configuration::get('PS_COUNTRY_DEFAULT'));
        }

        Configuration::updateValue('WK_PREORDER_COUNTRY', $wkAllowedCountries);
        Configuration::updateValue('WK_LIMITED_TIME', 0);
        Configuration::updateValue('WK_PREORDER_GROUP', json_encode($groupBox));
        Configuration::updateValue('WK_ALLOW_GEOLOCATION', 0);

        return true;
    }

    public function install()
    {
        $objPreorderInstall = new PreorderInstall();
        // multishop compatible
        if (Shop::isFeatureActive()) {
            Shop::setContext(Shop::CONTEXT_ALL);
        }

        if (!parent::install()
            || !$this->registerPreorderHook()
            || !$this->callInstallTab()
            || !$this->insertNewStatusPreorder()
            || !$objPreorderInstall->createTables()
            // || !$this->callPreorderMenu()
            || !$this->createNewShippingMethodForPreorder()
            || !$this->installConfigData()
        ) {
            return false;
        }

        return true;
    }

    public function uninstallTab()
    {
        $moduleTabs = Tab::getCollectionFromModule($this->name);
        if (!empty($moduleTabs)) {
            foreach ($moduleTabs as $moduleTab) {
                $moduleTab->delete();
            }

            return true;
        }

        return false;
    }

    public function removeTopMenuPreorder($shops = null)
    {
        if ($shops) {
        } else {
            $shops = Shop::getCompleteListOfShopsID();
        }
        foreach ($shops as $shopId) {
            $shopGroupId = Shop::getGroupFromShop($shopId);
            $preorderMenuId = Configuration::get('PS_PREORDER_MENU_ID', null, $shopGroupId, $shopId);
            if ($preorderMenuId) {
                $StlLnkItem = 'LNK' . $preorderMenuId;
                $lastMenuItems = Configuration::get('MOD_BLOCKTOPMENU_ITEMS', null, $shopGroupId, $shopId);
                $itemsarr = explode(',', $lastMenuItems);
                if (($itemkey = array_search($StlLnkItem, $itemsarr)) !== false) {
                    unset($itemsarr[$itemkey]);
                }
                $this->updateTopMenuItems($itemsarr);

                // Remove from database
                PreorderHelper::deleteFromlinksmenutopTable($preorderMenuId);
                PreorderHelper::deleteFromlinksmenutoplangTable($preorderMenuId);
            }
        }

        return true;
    }

    /**
     * Update the top menu.
     *
     * @param [type] $itemsarr
     *
     * @return void
     */
    public function updateTopMenuItems($itemsarr)
    {
        $shops = Shop::getCompleteListOfShopsID();
        foreach ($shops as $shopId) {
            $shopGroupId = Shop::getGroupFromShop($shopId);
            if (1 == count($shops)) {
                if (is_array($itemsarr) && count($itemsarr)) {
                    Configuration::updateValue(
                        'MOD_BLOCKTOPMENU_ITEMS',
                        (string) implode(',', $itemsarr),
                        false,
                        (int) $shopGroupId,
                        (int) $shopId
                    );
                } else {
                    Configuration::updateValue('MOD_BLOCKTOPMENU_ITEMS', '', false, (int) $shopGroupId, (int) $shopId);
                }
            }
        }
        $this->clearMainMenuCache();

        return true;
    }

    public function uninstall()
    {
        $objPreorderInstall = new PreorderInstall();

        if (!parent::uninstall()
            || !$this->uninstallTab()
            || !$this->deletePreorderShipping()
            || !$this->revertPreorderProductAsNormal()
            || !$this->removeTopMenuPreorder()
            || !$this->deleteConfigValues()
            || !$objPreorderInstall->deletePreorderTable()
        ) {
            return false;
        }

        return true;
    }

    public function disable($force_all = false)
    {
        if ($force_all) {
        }
        $this->revertPreorderProductAsNormal(1);
        if (Configuration::get('PS_PREORDER_MENU')) {
            $this->removeTopMenuPreorder(Shop::getContextListShopID());
        }
        if (parent::disable() == false) {
            return false;
        }

        return true;
    }

    protected function deleteConfigValues()
    {
        $var = [
            'WK_PARTIAL_PAYMENT', 'WK_PREORDER_SHIPPING', 'PS_PREORDER_MENU',
            'WK_FULL_PAYMENT', 'WK_RESTRICT_CHECKOUT', 'price_type',
            'WK_GUEST_PREORDER_ENABLED', 'PS_PREORDER_MENU_ID', 'WK_SHOW_PRODUCT_AVAILABLE_ON', 'WK_LIMITED_TIME', 'WK_ALLOW_DAYS',
            'WK_STOCK_ROLLBACK', 'WK_PREORDER_COUNTRY', 'WK_PREORDER_GROUP',
            'WK_ALLOW_GEOLOCATION',
        ];
        foreach ($var as $key) {
            if (!Configuration::deleteByName($key)) {
                return false;
            }
        }

        return true;
    }
}
