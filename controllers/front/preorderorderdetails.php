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

class PreorderPreorderOrderDetailsModuleFrontController extends ModuleFrontController
{
    public function __construct()
    {
        $this->display_column_left = false;
        $this->display_column_right = false;
        parent::__construct();
    }

    public function initContent()
    {
        parent::initContent();
        $idCustomer = $this->context->customer->id;
        if ($idCustomer) {
            $orderDetails = PreorderProductCustomer::getAllOrderDetailsByIdCustomer($idCustomer);
            $this->context->smarty->assign([
                'orderDetails' => $orderDetails,
                'static_token' => Tools::getToken(false),
                'detailsOrder' => $this->context->link->getPageLink('order-detail') . '&id_order=',
            ]);
            $this->setTemplate('module:preorder/views/templates/front/preorderList.tpl');
        } else {
            Tools::redirect($this->context->link->getPageLink('my-account'));
        }
    }

    public function getBreadcrumbLinks()
    {
        $breadcrumb = parent::getBreadcrumbLinks();
        $breadcrumb['links'][] = $this->addMyAccountToBreadcrumb();
        $breadcrumb['links'][] = [
            'title' => $this->module->l('Preorder orders', 'preorderorderdetails'),
            'url' => '',
        ];

        return $breadcrumb;
    }
}
