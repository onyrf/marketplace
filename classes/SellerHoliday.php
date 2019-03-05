<?php
/**
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
*  @author    PrestaShop SA <contact@prestashop.com>
*  @copyright 2007-2015 PrestaShop SA
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/

class SellerHoliday extends ObjectModel
{
    public $id_seller;
    public $from;
    public $to;

    /**
     * @see ObjectModel::$definition
     */
    public static $definition = array( 
        'table' => 'seller_holiday',
        'primary' => 'id_seller_holiday',
        'fields' => array(
            'id_seller' => array('type' => self::TYPE_INT, 'validate' => 'isunsignedInt', 'required' => false),
            'from' => array('type' => self::TYPE_DATE, 'validate' => 'isDate'),
            'to' => array('type' => self::TYPE_DATE, 'validate' => 'isDate'),
        ),
    );
    
    public function add($autodate = true, $null_values = false)
    {
        if (!parent::add($autodate, $null_values))
            return false;
        return true;
    }
    
    public static function getHolidays() {
        return Db::getInstance()->ExecuteS('SELECT * FROM '._DB_PREFIX_.'seller_holiday');
    }
    
    public static function getHolidaysBySeller($id_seller) {
        return Db::getInstance()->ExecuteS('SELECT * FROM '._DB_PREFIX_.'seller_holiday WHERE id_seller = '.(int)$id_seller);
    }
    
    public static function compareDates($beginDate, $endDate) {
        $endTimestamp = strtotime($endDate);
        $beginTimestamp = strtotime($beginDate);

        // There are 86400 seconds in a day
        return ($endTimestamp - $beginTimestamp) / 86400;
    }
}