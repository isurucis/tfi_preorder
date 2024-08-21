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

class PreorderAllPreorderDetailsModuleFrontController extends ModuleFrontController
{
    public function initContent()
    {
        parent::initContent();

        $preorderProductObj = new PreorderProduct();
        $preorderProducts = $preorderProductObj->getAllPreorderProducts($this->context->language->id);
        if ($preorderProducts) {
            $factory = new ProductPresenterFactory($this->context, new TaxConfiguration());
            $productSettings = $factory->getPresentationSettings();
            $presenter = $factory->getPresenter();
            $productDetails = [];
            foreach ($preorderProducts as $product) {
                if ($product['active']) {
                    $productInfos = Product::getProductProperties(
                        (int) $this->context->language->id,
                        $product
                    );
                    if (!isset($productInfos['new'])) {
                        $productInfos['new'] = 0;
                    }
                    $productDetails[] = $presenter->present(
                        $productSettings,
                        $productInfos,
                        $this->context->language
                    );
                }
            }
            $this->context->smarty->assign(
                [
                    'products' => $productDetails,
                    'price_type' => Configuration::get('price_type'),
                ]
            );
        }

        $this->setTemplate('module:preorder/views/templates/front/preorderProductListDetails.tpl');
    }

    public function getBreadcrumbLinks()
    {
        $breadcrumb = parent::getBreadcrumbLinks();
        $breadcrumb['links'][] = [
            'title' => $this->module->l('Preorder'),
            'url' => '',
        ];

        return $breadcrumb;
    }

    public function setMedia()
    {
        parent::setMedia();
        $this->registerJavascript(
            'module-preorder-flipclock.min',
            'modules/' . $this->module->name . '/views/js/flipclock.min.js',
            ['position' => 'bottom', 'priority' => 990]
        );
        $this->registerStylesheet(
            'module-preorder-flip-flipclock',
            'modules/' . $this->module->name . '/views/css/flipclock.css'
        );
        // Only on product page
        $this->registerStylesheet(
            'module-preorder-preorder_desc',
            'modules/' . $this->module->name . '/views/css/preorder_desc.css'
        );
        $this->registerStylesheet(
            'module-preorder-flip-responsive',
            'modules/' . $this->module->name . '/views/css/flip_responsive_product_page.css'
        );
        $this->registerStylesheet(
            'module-preorder-preordertimer_css',
            'modules/' . $this->module->name . '/views/css/preorder_timer.css'
        );
    }
}
