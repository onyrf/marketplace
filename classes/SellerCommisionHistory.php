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

class SellerCommisionHistory extends ObjectModel
{
    public $id_order;
    public $id_product;
    public $product_name;
    public $id_seller;
    public $id_shop;
    public $price;
    public $quantity;
    public $commision;
    public $id_seller_commision_history_state;
    public $date_add;
    public $date_upd;
    public $paid;

    /**
     * @see ObjectModel::$definition
     */
    public static $definition = array(
        'table' => 'seller_commision_history',
        'primary' => 'id_seller_commision_history',
        'fields' => array(
            'id_order' => array('type' => self::TYPE_INT, 'validate' => 'isunsignedInt', 'required' => false),
            'id_product' => array('type' => self::TYPE_INT, 'validate' => 'isunsignedInt', 'required' => false),
            'product_name' => array('type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'required' => false),
            'id_seller' => array('type' => self::TYPE_INT, 'validate' => 'isunsignedInt', 'required' => false),
            'id_shop' => array('type' => self::TYPE_INT, 'validate' => 'isunsignedInt', 'required' => false),
            'price' => array('type' => self::TYPE_FLOAT, 'required' => false),
            'quantity' => array('type' => self::TYPE_INT, 'validate' => 'isunsignedInt', 'required' => false),
            'commision' => array('type' => self::TYPE_FLOAT, 'required' => false),
            'id_seller_commision_history_state' => array('type' => self::TYPE_INT, 'validate' => 'isunsignedInt', 'required' => false),
            'date_add' => array('type' => self::TYPE_DATE, 'validate' => 'isDateFormat', 'required' => false),
            'date_upd' => array('type' => self::TYPE_DATE, 'validate' => 'isDateFormat', 'required' => false),
            'paid' => array('type' => self::TYPE_FLOAT, 'required' => false),
        ),
    );
    
    
    public static function getCommisionHistoryBySeller($id_seller, $id_lang, $id_shop) {
        /*$query = 'SELECT sch.id_order, MIN(o.reference) as reference, MAX(s.name) as seller_name, IF(MIN(sch.product_name) LIKE "%Frais%",MAX(sch.product_name),MIN(sch.product_name)) as product_name,IF((sch.product_name) NOT LIKE "Frais%",MAX(sch.price), 0) AS prix, SUM(sch.price) as price,SUM(sch.price) - (IF((sch.product_name) NOT LIKE "%Frais%",MAX(sch.price),0)) as frais, MAX(sch.quantity) as quantity, SUM(sch.commision) as commision, MAX(osl.name) as state_name, MIN(sch.date_add) as date_add,MAX(o.current_state) as state,SUM(o.total_paid),SUM(o.slip_amount),SUM(sch.paid), MAX(schs.reference) as state_commission, MAX(schs.color) as state_color
                    FROM '._DB_PREFIX_.'seller_commision_history sch
                    LEFT JOIN `'._DB_PREFIX_.'seller` s ON (s.`id_seller` = sch.`id_seller`) 
                    LEFT JOIN `'._DB_PREFIX_.'seller_commision_history_state` schs ON (schs.`id_seller_commision_history_state` = sch.`id_seller_commision_history_state`)
                    LEFT JOIN `'._DB_PREFIX_.'seller_commision_history_state_lang` schsl ON (schsl.`id_seller_commision_history_state` = schs.`id_seller_commision_history_state` AND schsl.id_lang = '.(int)$id_lang.') 
                    LEFT JOIN `'._DB_PREFIX_.'product` p ON (sch.`id_product` = p.`id_product`) 
                    LEFT JOIN `'._DB_PREFIX_.'product_lang` pl ON (pl.`id_product` = p.`id_product` AND pl.id_lang = '.(int)$id_lang.' AND pl.id_shop = '.(int)$id_shop.') 
                    JOIN `'._DB_PREFIX_.'orders` o ON (o.`id_order` = sch.`id_order`)
                    LEFT JOIN `'._DB_PREFIX_.'order_state_lang` osl ON (osl.id_order_state = o.current_state)                    
                    WHERE s.id_seller = '.(int)$id_seller.' AND o.current_state > 1
                    GROUP BY sch.id_order
                    ORDER BY sch.date_add DESC';*/
        $query = 'SELECT sch.id_order, MIN(o.reference) as reference, MAX(s.name) as seller_name, IF(MIN(sch.product_name) LIKE "%Frais%",MAX(sch.product_name),MIN(sch.product_name)) as product_name,IF((sch.product_name) NOT LIKE "Frais%",MAX(sch.price), 0) AS prix, MAX(sch.price) as price,(IF((sch.product_name) LIKE "%Frais%",MAX(sch.price),0)) as frais, MAX(sch.quantity) as quantity, MAX(sch.commision) as commision, MAX(osl.name) as state_name, MIN(sch.date_add) as date_add,MAX(o.current_state) as state,SUM(o.total_paid),SUM(o.slip_amount),SUM(sch.paid), MAX(schs.reference) as state_commission, MAX(schs.color) as state_color
                    FROM '._DB_PREFIX_.'seller_commision_history sch
                    LEFT JOIN `'._DB_PREFIX_.'seller` s ON (s.`id_seller` = sch.`id_seller`) 
                    LEFT JOIN `'._DB_PREFIX_.'seller_commision_history_state` schs ON (schs.`id_seller_commision_history_state` = sch.`id_seller_commision_history_state`)
                    LEFT JOIN `'._DB_PREFIX_.'seller_commision_history_state_lang` schsl ON (schsl.`id_seller_commision_history_state` = schs.`id_seller_commision_history_state` AND schsl.id_lang = '.(int)$id_lang.') 
                    LEFT JOIN `'._DB_PREFIX_.'product` p ON (sch.`id_product` = p.`id_product`) 
                    LEFT JOIN `'._DB_PREFIX_.'product_lang` pl ON (pl.`id_product` = p.`id_product` AND pl.id_lang = '.(int)$id_lang.' AND pl.id_shop = '.(int)$id_shop.') 
                    JOIN `'._DB_PREFIX_.'orders` o ON (o.`id_order` = sch.`id_order`)
                    LEFT JOIN `'._DB_PREFIX_.'order_state_lang` osl ON (osl.id_order_state = o.current_state AND osl.id_lang = '.(int)$id_lang.')                    
                    WHERE s.id_seller = '.(int)$id_seller.' AND o.current_state > 1
                    GROUP BY sch.id_order
                    ORDER BY sch.date_add DESC';
        $seller = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($query);
        if ($seller)
            return $seller;
        return false;
    }
    
    public static function getCommisionHistoryByOrder($id_order, $id_lang, $id_shop) {
        $query = 'SELECT sch.id_seller_commision_history, sch.id_order, o.reference, sch.product_name, sch.price, sch.quantity, sch.commision, schsl.name as state_name, sch.date_add,sch.paid 
                    FROM '._DB_PREFIX_.'seller_commision_history sch
                    LEFT JOIN `'._DB_PREFIX_.'seller_commision_history_state` schs ON (schs.`id_seller_commision_history_state` = sch.`id_seller_commision_history_state`)
                    LEFT JOIN `'._DB_PREFIX_.'seller_commision_history_state_lang` schsl ON (schsl.`id_seller_commision_history_state` = schs.`id_seller_commision_history_state` AND schsl.id_lang = '.(int)$id_lang.') 
                    LEFT JOIN `'._DB_PREFIX_.'product` p ON (sch.`id_product` = p.`id_product`) 
                    LEFT JOIN `'._DB_PREFIX_.'product_lang` pl ON (pl.`id_product` = p.`id_product` AND pl.id_lang = '.(int)$id_lang.' AND pl.id_shop = '.(int)$id_shop.') 
                    LEFT JOIN `'._DB_PREFIX_.'orders` o ON (o.`id_order` = sch.`id_order`)  
                    WHERE sch.id_order = '.(int)$id_order.'
                    ORDER BY sch.date_add DESC';
        $seller = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($query);
        if ($seller)
            return $seller;
        return false;
    }
    
    public static function getBenefitsBySeller($id_seller) {
        return Db::getInstance()->getValue('SELECT SUM(commision) as benefits FROM '._DB_PREFIX_.'seller_commision_history sch JOIN `'._DB_PREFIX_.'orders` o ON (o.`id_order` = sch.`id_order`) WHERE o.current_state != 6 AND sch.id_seller = '.(int)$id_seller);
    }

    public static function getBenefitsCancelBySeller($id_seller) {
        return Db::getInstance()->getValue('SELECT SUM(commision) as benefits FROM '._DB_PREFIX_.'seller_commision_history sch JOIN `'._DB_PREFIX_.'orders` o ON (o.`id_order` = sch.`id_order`) WHERE o.current_state = 6 AND sch.id_seller = '.(int)$id_seller);
    }
    
    public static function getFixedCommissionOfSellerInOrder($id_seller, $id_order) {
        return Db::getInstance()->getValue('SELECT commision FROM '._DB_PREFIX_.'seller_commision_history WHERE id_seller = '.(int)$id_seller.' AND id_order ='.(int)$id_order.' AND id_product = 0 AND commision < 0');
    }
    
    public static function changeStateCommissionsByOrder($id_order, $reference) {
        $order_commissions = SellerCommisionHistory::getCommisionHistoryByOrder($id_order, Context::getContext()->language->id, Context::getContext()->shop->id);
        if ($order_commissions) {
            foreach ($order_commissions as $commission) {
                $seller_commision_history = new SellerCommisionHistory($commission['id_seller_commision_history']);
                $seller_commision_history->id_seller_commision_history_state = SellerCommisionHistoryState::getIdByReference($reference);
                $seller_commision_history->update();
            }
        }
    }
}