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

class SellerCommision extends ObjectModel
{
    public $id_seller;
    public $commision;

    /**
     * @see ObjectModel::$definition
     */
    public static $definition = array(
        'table' => 'seller_commision',
        'primary' => 'id_seller_commision',
        'fields' => array(
            'id_seller' => array('type' => self::TYPE_INT, 'validate' => 'isunsignedInt', 'required' => false),
            'commision' => array('type' => self::TYPE_INT, 'validate' => 'isunsignedInt', 'required' => false),
        ),
    );
    
    public static function getCommisionBySeller($id_seller) {
        return Db::getInstance()->getValue('SELECT commision FROM '._DB_PREFIX_.'seller_commision WHERE id_seller = '.(int)$id_seller);
    }
}