<?php
/**
* 2007-2016 PrestaShop
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
*  @author    PrestaShop SA <contact@prestashop.com>
*  @copyright 2007-2016 PrestaShop SA
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/

class Dashboard
{

    public static function getCommisionHistoryBySeller($id_seller, $id_lang, $id_shop, $from, $to) {
        $query = 'SELECT sch.id_order, o.reference, s.name as seller_name, pl.name as product_name, sch.price, sch.quantity, sch.commision, schsl.name as state_name, sch.date_add
                    FROM '._DB_PREFIX_.'seller_commision_history sch
                    LEFT JOIN `'._DB_PREFIX_.'seller` s ON (s.`id_seller` = sch.`id_seller`) 
                    LEFT JOIN `'._DB_PREFIX_.'seller_commision_history_state` schs ON (schs.`id_seller_commision_history_state` = sch.`id_seller_commision_history_state`)
                    LEFT JOIN `'._DB_PREFIX_.'seller_commision_history_state_lang` schsl ON (schsl.`id_seller_commision_history_state` = schs.`id_seller_commision_history_state` AND schsl.id_lang = '.(int)$id_lang.') 
                    LEFT JOIN `'._DB_PREFIX_.'product` p ON (sch.`id_product` = p.`id_product`) 
                    LEFT JOIN `'._DB_PREFIX_.'product_lang` pl ON (pl.`id_product` = p.`id_product` AND pl.id_lang = '.(int)$id_lang.' AND pl.id_shop = '.(int)$id_shop.') 
                    JOIN `'._DB_PREFIX_.'orders` o ON (o.`id_order` = sch.`id_order`)  
                    WHERE s.id_seller = '.(int)$id_seller.' AND o.current_state != 6 AND o.current_state != 1 AND sch.date_add BETWEEN "'.pSQL($from).'" AND "'.pSQL($to).'"
                    ORDER BY sch.date_add ASC';
        $seller = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($query);
        if ($seller)
            return $seller;
        return false;
    }
    
    public static function getBenefitsBySeller($id_seller, $from, $to) {
        return Db::getInstance()->getValue(
                'SELECT SUM(commision) as benefits
                    FROM '._DB_PREFIX_.'seller_commision_history sch
                    LEFT JOIN `'._DB_PREFIX_.'seller_commision_history_state` schs ON (schs.`id_seller_commision_history_state` = sch.`id_seller_commision_history_state`)
                    LEFT JOIN `'._DB_PREFIX_.'orders` o ON (o.`id_order` = sch.`id_order`) 
                    WHERE sch.id_seller = '.(int)$id_seller.' AND o.current_state != 6 AND o.current_state != 1 AND sch.date_add BETWEEN "'.pSQL($from).'" AND "'.pSQL($to).'"'
                );
    }
    
    public static function getSalesBySeller($id_seller, $from, $to) {
        return Db::getInstance()->getValue(
                'SELECT SUM(sch.price*sch.quantity) as sales
                    FROM '._DB_PREFIX_.'seller_commision_history sch
                    LEFT JOIN `'._DB_PREFIX_.'orders` o ON (o.`id_order` = sch.`id_order`)
                    WHERE sch.id_seller = '.(int)$id_seller.' AND o.current_state != 6 AND o.current_state != 1 AND sch.date_add BETWEEN "'.pSQL($from).'" AND "'.pSQL($to).'"'
                );
    }
    
    public static function getProductQuantityBySeller($id_seller, $from, $to) {
        return Db::getInstance()->getValue(
                'SELECT SUM(quantity) as quantities 
                    FROM '._DB_PREFIX_.'seller_commision_history sch
                    LEFT JOIN `'._DB_PREFIX_.'seller_commision_history_state` schs ON (schs.`id_seller_commision_history_state` = sch.`id_seller_commision_history_state`)
                    LEFT JOIN `'._DB_PREFIX_.'orders` o ON (o.`id_order` = sch.`id_order`)
                    WHERE id_product != 0 AND sch.id_seller = '.(int)$id_seller.' AND o.current_state != 6 AND sch.date_add BETWEEN "'.pSQL($from).'" AND "'.pSQL($to).'"'
                );
    }
    
    public static function getNumOrdersBySeller($id_seller, $from, $to) {
        return Db::getInstance()->getValue(
                'SELECT COUNT(DISTINCT(sch.id_order)) as orders
                    FROM '._DB_PREFIX_.'seller_commision_history sch
                    LEFT JOIN `'._DB_PREFIX_.'seller_commision_history_state` schs ON (schs.`id_seller_commision_history_state` = sch.`id_seller_commision_history_state`)
                    LEFT JOIN `'._DB_PREFIX_.'orders` o ON (o.`id_order` = sch.`id_order`)
                    WHERE sch.id_seller = '.(int)$id_seller.' AND o.current_state != 6 AND o.current_state != 1 AND sch.date_add BETWEEN "'.pSQL($from).'" AND "'.pSQL($to).'"'
                );
    }

    public static function getNewOrdersBySeller($id_seller) {
        return Db::getInstance()->getValue(
                'SELECT COUNT(DISTINCT(sch.id_order)) as orders
                    FROM '._DB_PREFIX_.'seller_commision_history sch 
                    LEFT JOIN `'._DB_PREFIX_.'orders` o ON (o.`id_order` = sch.`id_order`)
                    WHERE sch.id_seller = '.(int)$id_seller.' AND o.visited_seller = 0 AND o.current_state > 1'
                );
    }
}