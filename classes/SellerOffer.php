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

class SellerOffer
{
    public static function getSpecificPricesBySeller($id_seller, $id_lang, $id_shop) {        
        $query_specials = 'SELECT p.`id_product`, p.reference, pl.`name`, pl.link_rewrite, sp.id_specific_price, sp.id_product_attribute, sp.from_quantity, sp.reduction, sp.reduction_type,sp.reduction_mode, sp.from, sp.to 
        FROM `'._DB_PREFIX_.'product` p
        LEFT JOIN `'._DB_PREFIX_.'product_lang` pl on (pl.id_product = p.id_product AND pl.id_lang = '.$id_lang.' AND pl.id_shop = '.$id_shop.')
        LEFT JOIN `'._DB_PREFIX_.'seller_product` sep on (sep.id_product = p.id_product)
        LEFT JOIN `'._DB_PREFIX_.'specific_price` sp on (sp.id_product = p.id_product)
        WHERE sp.reduction > 0 AND sep.id_seller_product = '.$id_seller.'
        ORDER BY sp.id_specific_price ASC';
        
        if ($specials = Db::getInstance()->ExecuteS($query_specials)) {
            $i=0;
            foreach ($specials as $row) {
                
                $product = new Product($row['id_product'], null, $id_lang, $id_shop);
                
                if ($row['id_product_attribute'] != 0) 
                    $id_product_attribute = $row['id_product_attribute'];
                else
                    $id_product_attribute = null;

                $price = $product->getPriceWithoutReduct(false, false);
                $specific_price = $product->getPrice(Product::$_taxCalculationMethod == PS_TAX_INC, $id_product_attribute, 6, null, false, true, $row['from_quantity']);
                
                $attribute_resume = '';
                if ($row['id_product_attribute'] != 0) {
                    $attributes_resume = $product->getAttributesResume($id_lang);
                    foreach ($attributes_resume as $ar) {
                        if ($ar['id_product_attribute'] == $row['id_product_attribute'])
                            $attribute_resume = $ar['attribute_designation'];
                    }
                }
                
                $extra_special = array(
                    'attribute_resume' => $attribute_resume,
                    'original_price' => Tools::displayPrice($price),
                    'specific_price' => Tools::displayPrice($specific_price));

                $specials[$i] = array_merge($specials[$i], $extra_special);
                $i++;
            }
        }
        return $specials;
    }
}