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

class SellerIncidence extends ObjectModel
{
    public $reference;
    public $id_order;
    public $id_product;
    public $id_customer;
    public $id_seller;
    public $id_shop;
    public $date_add;
    public $date_upd;
    public $active;
    public $id_cart;
    public $en_attente;

    /**
     * @see ObjectModel::$definition
     */
    public static $definition = array(
        'table' => 'seller_incidence',
        'primary' => 'id_seller_incidence',
        'fields' => array(
            'reference' => array('type' => self::TYPE_STRING, 'validate' => 'isReference', 'size' => 8),
            'id_order' => array('type' => self::TYPE_INT, 'validate' => 'isunsignedInt', 'required' => false),
            'id_product' => array('type' => self::TYPE_INT, 'validate' => 'isunsignedInt', 'required' => false),
            'id_customer' => array('type' => self::TYPE_INT, 'validate' => 'isunsignedInt', 'required' => false),
            'id_seller' => array('type' => self::TYPE_INT, 'validate' => 'isunsignedInt', 'required' => false),
            'id_shop' => array('type' => self::TYPE_INT, 'validate' => 'isunsignedInt', 'required' => false),
            'date_add' => array('type' => self::TYPE_DATE, 'validate' => 'isDateFormat', 'required' => false),
            'date_upd' => array('type' => self::TYPE_DATE, 'validate' => 'isDateFormat', 'required' => false),
            'active' => array('type' => self::TYPE_INT, 'validate' => 'isunsignedInt', 'required' => false),
            'en_attente' => array('type' => self::TYPE_INT, 'validate' => 'isunsignedInt', 'required' => false),
            'id_cart' => array('type' => self::TYPE_INT, 'validate' => 'isunsignedInt', 'required' => false),
        ),
    );
    
    public function getSellerName()
    {
        $query = 'SELECT s.name FROM '._DB_PREFIX_.'seller_incidence a                    
                    LEFT JOIN `'._DB_PREFIX_.'seller` s ON ( a.`id_seller` = s.`id_seller`)
                    WHERE `id_seller_incidence` = '.(int)$this->id;

        return Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow($query);
       
    }

    public function add($autodate = true, $null_values = false)
    {
        if (!parent::add($autodate, $null_values))
            return false;
        return true;
    }
    
    public function delete()
    {
        $result = $this->deleteMessages();
        $result = parent::delete();
        return $result;
    }
    
    public function deleteMessages()
    {
        return Db::getInstance()->execute(
                'DELETE FROM `'._DB_PREFIX_.'seller_incidence_message`
                WHERE `id_seller_incidence` = '.(int)$this->id
        );
    }
    
    public static function getIncidencesByCustomer($id_customer) {
        $query = 'SELECT a.*, o.reference as order_ref FROM '._DB_PREFIX_.'seller_incidence a                    
                    LEFT JOIN `'._DB_PREFIX_.'customer` c ON (c.`id_customer` = a.`id_customer`) 
                    LEFT JOIN `'._DB_PREFIX_.'orders` o ON (o.`id_order` = a.`id_order`)
                    WHERE c.id_customer = '.(int)$id_customer .' ORDER BY a.date_add DESC';
        $result = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($query);
        if ($result)
            return $result;
        return false;
    }
    
    public static function getIncidencesBySeller($id_seller) {
        $active = false;
        if( Configuration::get('MARKETPLACE_MODERATE_MESSAGE') ){
            $active = true;
        }
    
        $query = 'SELECT a.*, o.reference as order_ref FROM '._DB_PREFIX_.'seller_incidence a                    
                    LEFT JOIN `'._DB_PREFIX_.'seller` c ON (c.`id_seller` = a.`id_seller`) 
                    LEFT JOIN `'._DB_PREFIX_.'orders` o ON (o.`id_order` = a.`id_order`)
                    WHERE 
                        c.id_seller = '.(int)$id_seller. '
                        ' . ($active ? ' AND a.active = 1' : '') . ' AND a.id_cart = 0
                        ORDER BY a.date_add DESC';
        $result = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($query);
        if ($result)
            return $result;
        return false;
    }

    public static function getMessagesByCart($id_cart, $bAdmin = false) {
        $active = false;
        if( Configuration::get('MARKETPLACE_MODERATE_MESSAGE') && !$bAdmin){
            $active = true;
        }
        $zWhere = ($active ? ' AND si.active = 1' : '');
        
        /*if( $id_customer ){
            $zWhere = ($active ? ' AND (si.active = 1)' : '');
        }
        
        if( $id_seller ){
            $zWhere = ($active ? ' AND (si.active = 1)' : '');
        }*/
        
        $query = 'SELECT im.*, c.firstname as customer_firstname, c.lastname as customer_lastname, e.name as seller_name
                FROM `'._DB_PREFIX_.'seller_incidence_message` im
                LEFT JOIN '._DB_PREFIX_.'seller_incidence si ON (si.id_seller_incidence = im.id_seller_incidence)
                LEFT JOIN `'._DB_PREFIX_.'customer` c ON (c.`id_customer` = im.`id_customer`)
                LEFT JOIN `'._DB_PREFIX_.'seller` e ON (e.`id_seller` = im.`id_seller`)
                WHERE 
                    si.id_cart = '.(int)$id_cart.
                    $zWhere . '';
        $result = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($query);
        if ($result)
            return $result;

        return false;
    }
    
    public static function getMessages($id_incidence,$bAdmin = false,$id_customer = 0,$id_seller = 0,$tri = 'ASC') {
        $active = false;
        if( Configuration::get('MARKETPLACE_MODERATE_MESSAGE') && !$bAdmin){
            $active = true;
        }
        $zWhere = ($active ? ' AND im.active = 1' : '');
        
        if( $id_customer ){
            $zWhere = ($active ? ' AND (im.active = 1 OR im.id_customer = ' . (int)$id_customer . ' )' : '');
        }
        
        if( $id_seller ){
            $zWhere = ($active ? ' AND (im.active = 1 OR im.id_seller = ' . (int)$id_seller . ' )' : '');
        }
        
        $query = 'SELECT im.*, c.firstname as customer_firstname, c.lastname as customer_lastname, e.name as seller_name
                FROM `'._DB_PREFIX_.'seller_incidence_message` im
                LEFT JOIN `'._DB_PREFIX_.'customer` c ON (c.`id_customer` = im.`id_customer`)
                LEFT JOIN `'._DB_PREFIX_.'seller` e ON (e.`id_seller` = im.`id_seller`)
                WHERE 
                    im.id_seller_incidence = '.(int)$id_incidence.
                    $zWhere
                .' ORDER BY im.date_add '. $tri;
	$result = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($query);
        if ($result)
            return $result;
        return false;
    }
    
    public static function generateReference() {
        $str = "ABCDEFGHIJKLMNOPQRSTUVWXYZ123456789";
        $ref = "#";
        for($i=0;$i<6;$i++) {
           $ref .= Tools::substr($str,rand(0,36),1);
        }
        return $ref;
    }
    
    public static function getNumMessagesNotReaded($id_seller_incidence, $id_seller = false, $id_customer = false) {
        $active = false;
        if( Configuration::get('MARKETPLACE_MODERATE_MESSAGE') ){
            $active = true;
        }
        
        if ($id_seller != false) {
            $messages_not_readed = Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow('
                SELECT 
                    COUNT(*) as num 
                FROM '._DB_PREFIX_.'seller_incidence_message im
                WHERE 
                    im.readed = 0 
                    AND im.id_seller = 0 
                    AND im.id_customer != 0 
                    AND im.id_seller_incidence = '.(int)$id_seller_incidence .
                    ($active ? ' AND im.active = 1' : '')
            );
        }
        elseif ($id_customer != false) {
            $messages_not_readed = Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow(
                'SELECT 
                    COUNT(*) as num 
                FROM '._DB_PREFIX_.'seller_incidence_message im
                WHERE 
                    im.readed = 0 
                    AND im.id_customer = 0 
                    AND im.id_seller != 0 
                    AND im.id_seller_incidence = '.(int)$id_seller_incidence . 
                    ($active ? ' AND im.active = 1' : '')
            );
        }
        return $messages_not_readed['num'];
    }

    public static function getNumMessagesNotReadedCust($id_seller_incidence, $id_seller = false, $id_customer = false) {
        $active = true;
        
        
        if ($id_seller != false) {
            $messages_not_readed = Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow('
                SELECT 
                    COUNT(*) as num 
                FROM '._DB_PREFIX_.'seller_incidence_message im
                WHERE 
                    im.readed_cust = 0                    
                    AND im.id_seller_incidence = '.(int)$id_seller_incidence .
                    ($active ? ' AND im.active = 1' : '')
            );
        }
        elseif ($id_customer != false) {
            $messages_not_readed = Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow(
                'SELECT 
                    COUNT(*) as num 
                FROM '._DB_PREFIX_.'seller_incidence_message im
                WHERE 
                    im.readed_cust = 0                     
                    AND im.id_seller_incidence = '.(int)$id_seller_incidence . 
                    ($active ? ' AND im.active = 1' : '')
            );
        }
        return $messages_not_readed['num'];
    }
    public static function getMessagesNotRead($id_seller,$lang)
    {
        $incidences = $this->getIncidencesBySeller($id_seller);

        /*if ($incidences != false) {
            $counter = 0;
            foreach ($incidences as $i) {
                $product = new Product((int)$i['id_product'], (int)$lang);
                $incidences[$counter]['product_name'] = $product->name;
                $incidences[$counter]['messages_not_readed'] = $this->getNumMessagesNotReaded($i['id_seller_incidence'], $id_seller, false);
                  
                $messages = $this->getMessages((int)$i['id_seller_incidence'],false,0,$id_seller);
                
                $incidences[$counter] = array_merge($incidences[$counter], array('messages' => $messages));
                $counter++;
            }
        }*/

        return $incidences;
    }

    public static function getMessageCountByProd($id_product,$id_cart) {
                
        $query = 'SELECT count(*)
                FROM `'._DB_PREFIX_.'seller_incidence` si               
                WHERE 
                    si.id_product = '.(int)$id_product.' AND si.id_cart = '.(int)$id_cart.'';
                
    return Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue($query);
    
    }

    public static function getidIncidenceByOrder($id_order)
    {
                
        $data = Db::getInstance()->getValue(
                'SELECT id_seller_incidence
                FROM '._DB_PREFIX_.'seller_incidence si                    
                WHERE 
                    si.id_order = '.(int)$id_order.''
        );

        return $data ;
        
    }

    

}