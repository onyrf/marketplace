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

class marketplaceFavoritesellerModuleFrontController extends ModuleFrontController
{
    public $ssl = true;
    
    public function postProcess() {
        if (Tools::getValue('id_seller')) {
            if (!Seller::existFavoriteSellerByCustomer(Tools::getValue('id_seller'), $this->context->cookie->id_customer))
                Seller::addFavorite(Tools::getValue('id_seller'), $this->context->cookie->id_customer);
        }
        
        if (Tools::getValue('delete')) {
            Seller::deleteFavoriteSellerByCustomer(Tools::getValue('delete'), $this->context->cookie->id_customer);
        }
    }

    public function initContent() {
        
        parent::initContent();

        if (!Configuration::get('MARKETPLACE_SELLER_FAVORITE') || !$this->context->cookie->id_customer) 
            Tools::redirect($this->context->link->getPageLink('my-account', true));
        
        $favorite_sellers = Seller::getFavoriteSellersByCustomer($this->context->cookie->id_customer);

        $this->context->smarty->assign(array(
            'show_contact' => Configuration::get('MARKETPLACE_SHOW_CONTACT'),
            'favorite_sellers' => $favorite_sellers,
        ));

        //$this->setTemplate('favoriteseller.tpl');
        $this->setTemplate('module:marketplace/views/templates/front/favoriteseller.tpl');
    }
}