<?php
/*
* 2007-2015 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author PrestaShop SA <contact@prestashop.com>
*  @copyright  2007-2015 PrestaShop SA
*  @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/

if (!defined('_PS_VERSION_'))
	exit;

class ProductEanComparator
{
    public static function getOtherProducts($id_product, $name, $id_lang) {
        $query = 'SELECT p.*, pl.*, i.id_image, pl.name, se.name as seller_name, se.shop as shop_name, se.id_customer, se.id_seller, se.link_rewrite as seller_link_rewrite
                FROM `'._DB_PREFIX_.'product` p
                '.Shop::addSqlAssociation('product', 'p').'
                LEFT JOIN `'._DB_PREFIX_.'product_lang` pl ON (p.`id_product` = pl.`id_product` '.Shop::addSqlRestrictionOnLang('pl').')
                LEFT JOIN `'._DB_PREFIX_.'manufacturer` m ON (m.`id_manufacturer` = p.`id_manufacturer`)
                LEFT JOIN `'._DB_PREFIX_.'supplier` su ON (su.`id_supplier` = p.`id_supplier`)
                LEFT JOIN `'._DB_PREFIX_.'seller_product` sp ON (sp.`id_product` = p.`id_product`) 
                LEFT JOIN `'._DB_PREFIX_.'seller` se ON (sp.`id_seller_product` = se.`id_seller`) 
                LEFT JOIN `'._DB_PREFIX_.'image` i ON (i.`id_product` = p.`id_product` AND cover = 1)
                LEFT JOIN `'._DB_PREFIX_.'stock_available` sa ON (sa.`id_product` = p.`id_product`)
                WHERE pl.`id_lang` = '.(int)$id_lang.' AND pl.`name` LIKE "'.pSQL($name).'" AND pl.`name` != ""  AND p.id_product != '.(int)$id_product .' AND sa.quantity > 0 
                GROUP BY p.id_product
                ORDER BY price ASC';
		$rq = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($query);
        if (Product::getProductsProperties($id_lang, $rq))
            return Product::getProductsProperties($id_lang, $rq);
        return false;
    }

    public static function getAllProductsSameSeller($name, $id_lang) {
        $query = 'SELECT p.*, pl.*, i.id_image, pl.name, se.name as seller_name, se.shop as shop_name, se.id_customer, se.id_seller, se.link_rewrite as seller_link_rewrite
                FROM `'._DB_PREFIX_.'product` p
                '.Shop::addSqlAssociation('product', 'p').'
                LEFT JOIN `'._DB_PREFIX_.'product_lang` pl ON (p.`id_product` = pl.`id_product` '.Shop::addSqlRestrictionOnLang('pl').')
                LEFT JOIN `'._DB_PREFIX_.'manufacturer` m ON (m.`id_manufacturer` = p.`id_manufacturer`)
                LEFT JOIN `'._DB_PREFIX_.'supplier` su ON (su.`id_supplier` = p.`id_supplier`)
                LEFT JOIN `'._DB_PREFIX_.'seller_product` sp ON (sp.`id_product` = p.`id_product`) 
                LEFT JOIN `'._DB_PREFIX_.'seller` se ON (sp.`id_seller_product` = se.`id_seller`) 
                LEFT JOIN `'._DB_PREFIX_.'image` i ON (i.`id_product` = p.`id_product` AND cover = 1)
                WHERE pl.`id_lang` = '.(int)$id_lang.' AND pl.`name` LIKE "'.pSQL($name).'" AND pl.`name` != "" 
                ORDER BY price ASC';
        $rq = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($query);
        if (Product::getProductsProperties($id_lang, $rq))
        {
            Tools::orderbyPrice($rq, 'ASC');
            return Product::getProductsProperties($id_lang, $rq);
        }
        return false;
    }
    
     public static function getAllProductsSameSellerBestOffer($name, $id_lang) {
        $query = 'SELECT p.*, pl.*, i.id_image, pl.name, se.name as seller_name, se.shop as shop_name, se.id_customer, se.id_seller, se.link_rewrite as seller_link_rewrite
                FROM `'._DB_PREFIX_.'product` p
                '.Shop::addSqlAssociation('product', 'p').'
                LEFT JOIN `'._DB_PREFIX_.'product_lang` pl ON (p.`id_product` = pl.`id_product` '.Shop::addSqlRestrictionOnLang('pl').')
                LEFT JOIN `'._DB_PREFIX_.'manufacturer` m ON (m.`id_manufacturer` = p.`id_manufacturer`)
                LEFT JOIN `'._DB_PREFIX_.'supplier` su ON (su.`id_supplier` = p.`id_supplier`)
                LEFT JOIN `'._DB_PREFIX_.'seller_product` sp ON (sp.`id_product` = p.`id_product`) 
                LEFT JOIN `'._DB_PREFIX_.'seller` se ON (sp.`id_seller_product` = se.`id_seller`) 
                LEFT JOIN `'._DB_PREFIX_.'image` i ON (i.`id_product` = p.`id_product` AND cover = 1)
                LEFT JOIN `'._DB_PREFIX_.'stock_available` sa ON (sa.`id_product` = p.`id_product`)
                WHERE pl.`id_lang` = '.(int)$id_lang.' AND pl.`name` LIKE "'.pSQL($name).'" AND pl.`name` != "" AND sa.quantity > 0 
                ORDER BY price ASC';
        $rq = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($query);
        if (Product::getProductsProperties($id_lang, $rq))
        {
            Tools::orderbyPrice($rq, 'ASC');
            return Product::getProductsProperties($id_lang, $rq);
        }
        return false;
    }

    public static function getOtherProductsBestOffer($id_product, $name, $id_lang) {
        $query = 'SELECT p.*, pl.*, i.id_image, pl.name, se.name as seller_name, se.shop as shop_name, se.id_customer, se.id_seller, se.link_rewrite as seller_link_rewrite
                FROM `'._DB_PREFIX_.'product` p
                '.Shop::addSqlAssociation('product', 'p').'
                LEFT JOIN `'._DB_PREFIX_.'product_lang` pl ON (p.`id_product` = pl.`id_product` '.Shop::addSqlRestrictionOnLang('pl').')
                LEFT JOIN `'._DB_PREFIX_.'manufacturer` m ON (m.`id_manufacturer` = p.`id_manufacturer`)
                LEFT JOIN `'._DB_PREFIX_.'supplier` su ON (su.`id_supplier` = p.`id_supplier`)
                LEFT JOIN `'._DB_PREFIX_.'seller_product` sp ON (sp.`id_product` = p.`id_product`) 
                LEFT JOIN `'._DB_PREFIX_.'seller` se ON (sp.`id_seller_product` = se.`id_seller`) 
                LEFT JOIN `'._DB_PREFIX_.'image` i ON (i.`id_product` = p.`id_product` AND cover = 1)
                LEFT JOIN `'._DB_PREFIX_.'stock_available` sa ON (sa.`id_product` = p.`id_product`)
                WHERE pl.`id_lang` = '.(int)$id_lang.' AND pl.`name` LIKE "'.pSQL($name).'" AND pl.`name` != "" AND sa.quantity > 0 AND p.id_product != '.(int)$id_product .'
                GROUP BY p.id_product
                ORDER BY price ASC';
        $rq = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($query);
        if (Product::getProductsProperties($id_lang, $rq))
            return Product::getProductsProperties($id_lang, $rq);
        return array();
    }

    public static function getNumOthersProducts($id_product, $name)
    {
        echo '
                SELECT COUNT(*)
                FROM `'._DB_PREFIX_.'product`
                WHERE `id_product` != '.(int)$id_product.'
                AND `name` = "'.pSQL($name).'"';
        return Db::getInstance()->getValue('
                SELECT COUNT(*)
                FROM `'._DB_PREFIX_.'product`
                WHERE `id_product` = '.(int)$id_product.'
                AND `name` = "'.pSQL($name).'"');
    }
}
