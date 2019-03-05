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

class marketplaceaddproductcartconfirmModuleFrontController extends ModuleFrontController
{    
    public $ssl = true;
    
    protected function ajaxProcessConfirmDeleteSellerProduct()
    {
        $confirm = false;
        $id_product = (int)Tools::getValue('id_product');
        $id_seller = SellerProduct::isSellerProduct($id_product);
        
        //revisar los productos del carrito
        $products_in_cart = $this->context->cart->getProducts();
        
        if (is_array($products_in_cart) && count($products_in_cart) > 0) {
            foreach ($products_in_cart as $product) {
                $id_seller_product_cart = SellerProduct::isSellerProduct($product['id_product']);
                if ($id_seller != $id_seller_product_cart)
                    $confirm = false;
            }
        }
        
        if ($confirm)
            echo 1;
        else
            echo 2;
        die('');
    }

    public function initContent() {
        
        parent::initContent();
        
        if (Tools::isSubmit('action'))
        {
            switch(Tools::getValue('action'))
            {
                case 'review':
                    $this->ajaxProcessConfirmDeleteSellerProduct();
                    break;
            }
        } 
    }
}