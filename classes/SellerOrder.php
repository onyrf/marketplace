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

class SellerOrder
{
    public static function getVisitedOrdersSeller($id_seller, $id_lang)
    {
        return Db::getInstance()->getValue('
        SELECT count(*)
        FROM `'._DB_PREFIX_.'orders` o        
        WHERE o.`id_seller` = '.(int)$id_seller. ' AND o.id_lang = '. $id_lang .' AND o.visited = 0');
    }

    public static function getOrdersBySeller($id_seller, $id_lang) {
        $query = 'SELECT 
                o.id_order,
                o.reference,
                o.id_currency,
                o.total_paid_tax_incl,
                o.date_add,
                o.payment,
                o.current_state,
                o.visited,
                o.visited_seller,
		        o.id_order AS id_pdf,
		CONCAT(LEFT(c.`firstname`, 1), \'. \', c.`lastname`) AS `customer`,
		osl.`name` AS `osname`,
		os.`color`,
		IF((SELECT so.id_order FROM `'._DB_PREFIX_.'orders` so WHERE so.id_customer = o.id_customer AND so.id_order < o.id_order LIMIT 1) > 0, 0, 1) as new,
		country_lang.name as cname,
		IF(o.valid, 1, 0) badge_success
                    FROM '._DB_PREFIX_.'seller_commision_history sch
                    LEFT JOIN `'._DB_PREFIX_.'seller` s ON (s.`id_seller` = sch.`id_seller`) 
                    LEFT JOIN `'._DB_PREFIX_.'orders` o ON (o.`id_order` = sch.`id_order`)  
                    LEFT JOIN `'._DB_PREFIX_.'customer` c ON (c.`id_customer` = o.`id_customer`)
                        INNER JOIN `'._DB_PREFIX_.'address` address ON address.id_address = o.id_address_delivery
                    INNER JOIN `'._DB_PREFIX_.'country` country ON address.id_country = country.id_country
                    INNER JOIN `'._DB_PREFIX_.'country_lang` country_lang ON (country.`id_country` = country_lang.`id_country` AND country_lang.`id_lang` = '.(int)$id_lang.')
                    LEFT JOIN `'._DB_PREFIX_.'order_state` os ON (os.`id_order_state` = o.`current_state`)
                    LEFT JOIN `'._DB_PREFIX_.'order_state_lang` osl ON (os.`id_order_state` = osl.`id_order_state` AND osl.`id_lang` = '.(int)$id_lang.')
                    WHERE s.id_seller = '.(int)$id_seller.' AND o.`current_state` != 1
                    GROUP BY o.id_order
                    ORDER BY o.date_add DESC';
        //d($query);
        $orders = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($query);
        if ($orders)
            return $orders;
        return false;
    }

    public static function getOrdersStateWithSeller($id_lang) {
        
        $shop_name = Configuration::get('PS_SHOP_NAME');

        $query = 'SELECT 
                o.id_order,
                o.reference,
                o.id_currency,
                o.total_paid_tax_incl,
                o.date_add,
                o.payment,
                o.current_state,
        o.id_order AS id_pdf,
        CONCAT(LEFT(c.`firstname`, 1), \'. \', c.`lastname`) AS `customer`,
        osl.`name` AS `osname`,
        os.`color`,
        IF(s.`name` IS NULL,"'.$shop_name.'",s.`name`) AS `seller_name`,
        IF((SELECT so.id_order FROM `'._DB_PREFIX_.'orders` so WHERE so.id_customer = o.id_customer AND so.id_order < o.id_order LIMIT 1) > 0, 0, 1) as new,
        country_lang.name as cname,
        IF(o.valid, 1, 0) badge_success
                    FROM '._DB_PREFIX_.'seller_commision_history sch
                    LEFT JOIN `'._DB_PREFIX_.'seller` s ON (s.`id_seller` = sch.`id_seller`) 
                    LEFT JOIN `'._DB_PREFIX_.'orders` o ON (o.`id_order` = sch.`id_order`)  
                    LEFT JOIN `'._DB_PREFIX_.'customer` c ON (c.`id_customer` = o.`id_customer`)
                        INNER JOIN `'._DB_PREFIX_.'address` address ON address.id_address = o.id_address_delivery
                    INNER JOIN `'._DB_PREFIX_.'country` country ON address.id_country = country.id_country
                    INNER JOIN `'._DB_PREFIX_.'country_lang` country_lang ON (country.`id_country` = country_lang.`id_country` AND country_lang.`id_lang` = '.(int)$id_lang.')
                    LEFT JOIN `'._DB_PREFIX_.'order_state` os ON (os.`id_order_state` = o.`current_state`)
                    LEFT JOIN `'._DB_PREFIX_.'order_state_lang` osl ON (os.`id_order_state` = osl.`id_order_state` AND osl.`id_lang` = '.(int)$id_lang.')
                    WHERE o.current_state <= 3
                    GROUP BY o.id_order
                    ORDER BY o.date_add DESC';
        //d($query);
        $orders = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($query);
        if ($orders)
            return $orders;
        return false;
    }

    public static function getProductsDetailBySeller($id_order)
    {
        return Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS('
        SELECT *
        FROM `'._DB_PREFIX_.'order_detail` od
        LEFT JOIN `'._DB_PREFIX_.'product` p ON (p.id_product = od.product_id)
        LEFT JOIN `'._DB_PREFIX_.'product_shop` ps ON (ps.id_product = p.id_product AND ps.id_shop = od.id_shop)
        LEFT JOIN `'._DB_PREFIX_.'seller_product` sp ON (sp.id_product = od.product_id ) 
        LEFT JOIN `'._DB_PREFIX_.'seller` s ON (sp.`id_seller_product` = s.`id_seller`)
        WHERE od.`id_order` = '.(int)$id_order . ' AND sp.id_product IS NOT NULL');
    }

    public static function getOrdersTotalPaidBySeller($id_order)
    {
        
        $products = $this->getProductsDetailBySeller($id_order);
        

        $return = 0;
        foreach ($products as $row) {
            $return += $row['total_price_tax_incl'] * $row['product_quantity'] ;
        }
        
        return $return;
    }
    
    public static function getSellerByOrder($id_order,$id_lang){
        $query = 'SELECT s.* FROM '._DB_PREFIX_.'seller_commision_history sch
                LEFT JOIN `'._DB_PREFIX_.'seller` s ON (s.`id_seller` = sch.`id_seller`) 
                LEFT JOIN `'._DB_PREFIX_.'orders` o ON (o.`id_order` = sch.`id_order`)  
                LEFT JOIN `'._DB_PREFIX_.'customer` c ON (c.`id_customer` = o.`id_customer`)
                    INNER JOIN `'._DB_PREFIX_.'address` address ON address.id_address = o.id_address_delivery
                INNER JOIN `'._DB_PREFIX_.'country` country ON address.id_country = country.id_country
                INNER JOIN `'._DB_PREFIX_.'country_lang` country_lang ON (country.`id_country` = country_lang.`id_country` AND country_lang.`id_lang` = '.(int)$id_lang.')
                LEFT JOIN `'._DB_PREFIX_.'order_state` os ON (os.`id_order_state` = o.`current_state`)
                LEFT JOIN `'._DB_PREFIX_.'order_state_lang` osl ON (os.`id_order_state` = osl.`id_order_state` AND osl.`id_lang` = '.(int)$id_lang.')
                WHERE o.id_order = '.(int)$id_order.'
                GROUP BY o.id_order
                ORDER BY o.date_add DESC';
                
        $order = Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow($query);
        if ($order)
            return $order;
        return false;
    }

    public static function getOrderHasBuySeller($id_seller,$id_customer){
        $query = 'SELECT count(*) 
                FROM '._DB_PREFIX_.'orders o WHERE `id_customer` = '. $id_customer .' AND `id_seller` = '.$id_seller;

        $row = Db::getInstance()->getValue($query);

        return $row;

    }
}