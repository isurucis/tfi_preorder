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

class PreorderCustomPrice extends ObjectModel
{
    public $id_wk_preorder_custom_price;
    public $id_shop;
    public $product_id;
    public $attribute_id;
    public $customer_id;
    public $custom_price;

    public static $definition = [
        'table' => 'wk_preorder_custom_price',
        'primary' => 'id_wk_preorder_custom_price',
        'fields' => [
            'id_shop' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt', 'required' => true],
            'attribute_id' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt', 'required' => true],
            'product_id' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt', 'required' => true],
            'customer_id' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt', 'required' => true],
            'custom_price' => ['type' => self::TYPE_FLOAT, 'validate' => 'isPrice', 'required' => true],
        ],
    ];

    public function checkCustomPriceExist($idProduct, $idAttr, $idCust, $idShop)
    {
        return (int) Db::getInstance()->getValue(
            'SELECT `id_wk_preorder_custom_price` FROM `' . _DB_PREFIX_ . 'wk_preorder_custom_price`
            WHERE id_shop = ' . (int) $idShop . ' AND attribute_id = ' . (int) $idAttr . '
            AND product_id = ' . (int) $idProduct . ' AND customer_id = ' . (int) $idCust
        );
    }

    public static function getCustomPrice($idProduct, $idAttr, $idCust, $idShop)
    {
        return (float) Db::getInstance()->getValue(
            'SELECT `custom_price` FROM `' . _DB_PREFIX_ . 'wk_preorder_custom_price`
            WHERE id_shop = ' . (int) $idShop . ' AND attribute_id = ' . (int) $idAttr . '
            AND product_id = ' . (int) $idProduct . ' AND customer_id = ' . (int) $idCust
        );
    }
}
