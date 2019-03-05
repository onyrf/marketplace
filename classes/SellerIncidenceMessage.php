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

class SellerIncidenceMessage extends ObjectModel
{
    public $id_seller_incidence;
    public $id_customer;
    public $id_seller;
    public $description;
    public $readed;
    public $date_add;
    public $date_upd;
    public $active;
    public $readed_cust;

    /**
     * @see ObjectModel::$definition
     */
    public static $definition = array(
        'table' => 'seller_incidence_message',
        'primary' => 'id_seller_incidence_message',
        'fields' => array(
            'id_seller_incidence' => array('type' => self::TYPE_INT, 'validate' => 'isunsignedInt', 'required' => false),
            'id_customer' => array('type' => self::TYPE_INT, 'validate' => 'isunsignedInt', 'required' => false),
            'id_seller' => array('type' => self::TYPE_INT, 'validate' => 'isunsignedInt', 'required' => false),
            'description' => array('type' => self::TYPE_HTML, 'lang' => false, 'validate' => 'isCleanHtml'),
            'readed' => array('type' => self::TYPE_INT, 'validate' => 'isunsignedInt', 'required' => false),
            'date_add' => array('type' => self::TYPE_DATE, 'validate' => 'isDateFormat', 'required' => false),
            'date_upd' => array('type' => self::TYPE_DATE, 'validate' => 'isDateFormat', 'required' => false),
            'active' => array('type' => self::TYPE_INT, 'validate' => 'isunsignedInt', 'required' => false),
            'readed_cust' => array('type' => self::TYPE_INT, 'validate' => 'isunsignedInt', 'required' => false),
        ),
    );
    
    public static function getNumMessagesNotReadedBySeller($id_seller) {
        $active = false;
        if( Configuration::get('MARKETPLACE_MODERATE_MESSAGE') ){
            $active = true;
        }
        
        $data = Db::getInstance()->getValue(
                'SELECT 
                    COUNT(*) 
                FROM '._DB_PREFIX_.'seller_incidence_message sim
                JOIN '._DB_PREFIX_.'seller_incidence si 
                    ON (si.id_seller_incidence = sim.id_seller_incidence)
                WHERE 
                    si.id_seller = '.(int)$id_seller.' 
                    AND sim.id_seller != '.(int)$id_seller.' 
                    AND readed = 0 ' . 
                    ($active ? ' AND sim.active = 1' : '') .
                    ($active ? ' AND si.active = 1' : ''). ' AND si.id_cart = 0'
        );
        return $data ;
        
    }

    public static function getNumMessagesNotReadedByCust($id_customer) {
        $active = false;
        if( Configuration::get('MARKETPLACE_MODERATE_MESSAGE') ){
            $active = true;
        }
        
        $data = Db::getInstance()->getValue(
                'SELECT 
                    COUNT(*) 
                FROM '._DB_PREFIX_.'seller_incidence_message sim
                JOIN '._DB_PREFIX_.'seller_incidence si 
                    ON (si.id_seller_incidence = sim.id_seller_incidence)
                WHERE 
                    sim.id_customer = '.(int)$id_customer.' 
                    AND readed_cust = 0 '
        );
        return $data ;
        
    }

    public static function getNumMessagesByOrder($id_order)
    {
                
        $data = Db::getInstance()->executeS(
                'SELECT sim.*
                FROM '._DB_PREFIX_.'seller_incidence_message sim
                JOIN '._DB_PREFIX_.'seller_incidence si 
                    ON (si.id_seller_incidence = sim.id_seller_incidence)
                WHERE 
                    si.id_cart = '.(int)$id_order.''
        );

        return $data ;
        
    }

    public static function getNumMessagesNotReadedByOrder($id_order)
    {
                
        $data = Db::getInstance()->getValue(
                'SELECT COUNT(*)
                FROM '._DB_PREFIX_.'seller_incidence_message sim
                LEFT JOIN '._DB_PREFIX_.'seller_incidence si 
                    ON (si.id_seller_incidence = sim.id_seller_incidence)
                WHERE 
                    si.id_order = '.(int)$id_order.' AND sim.readed = 0'
        );

        return $data ;
        
    }    

    public static function getNumMessagesNotReadedOrderBySeller($id_seller, $id_order, $active = 1) {
                
        $data = Db::getInstance()->getValue(
                'SELECT 
                    COUNT(*) 
                FROM '._DB_PREFIX_.'seller_incidence_message sim
                JOIN '._DB_PREFIX_.'seller_incidence si 
                    ON (si.id_seller_incidence = sim.id_seller_incidence)
                WHERE 
                    si.id_seller = '.(int)$id_seller.' 
                    AND sim.id_seller != '.(int)$id_seller.' 
                    AND readed = 0 ' . 
                    ($active ? ' AND sim.active = 1' : '') .
                    ($active ? ' AND si.active = 1' : ''). ' AND si.id_cart = 0 AND si.id_order = '.$id_order
        );
        return $data ;
        
    }
}