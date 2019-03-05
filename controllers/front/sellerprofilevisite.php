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

use PrestaShop\PrestaShop\Adapter\Image\ImageRetriever;
use PrestaShop\PrestaShop\Adapter\Product\PriceFormatter;
use PrestaShop\PrestaShop\Core\Product\ProductListingPresenter;
use PrestaShop\PrestaShop\Adapter\Product\ProductColorsRetriever;

class MarketplaceSellerprofilevisiteModuleFrontController extends ModuleFrontController
{
    public $ssl = true;
    public $products;

    public function initContent() {
        
        parent::initContent();
        
        $is_active_seller = Seller::isActiveSeller(Tools::getValue('id_seller'));
        
        if (!Configuration::get('MARKETPLACE_SHOW_PROFILE') || !Tools::getValue('id_seller') || !$is_active_seller) 
            Tools::redirect($this->context->link->getPageLink('my-account', true));

        //$by_owner = (int)Tools::getValue('by_owner');

        $id_seller = (int)Tools::getValue('id_seller');
        $seller = new Seller($id_seller); 
        
        $files = glob(_PS_IMG_DIR_.'sellers/'.$seller->id_customer.'_*.jpg');
        
        if(!$files)
            $this->context->smarty->assign(array('photo' =>  __PS_BASE_URI__.'modules/marketplace/views/img/profile.jpg'));
        else
        {
            foreach ($files as $file) {       

                if (file_exists($file))
                {                
                    $this->context->smarty->assign(array('photo' => __PS_BASE_URI__.'img/sellers/'. basename($file)));
                }               

            }
        }
        	
        $param = array('id_seller' => $seller->id, 'link_rewrite' => $seller->link_rewrite);
        $param2 = array('id_seller' => $seller->id);
        
        $url_seller_products = $this->module->getmarketplaceLink('marketplace_sellerproductlist_rule', $param);
        $url_sellers = $this->module->getmarketplaceLink('marketplace_sellers_rule'); 
        $url_favorite_seller = $this->context->link->getModuleLink('marketplace', 'favoriteseller', $param2, true);
        $url_seller_comments = $this->context->link->getModuleLink('marketplace', 'sellercomments', $param2, true);	
        
        if ($seller->id_customer == $this->context->cookie->id_customer)
            $seller_me = true;
        else
            $seller_me = false;
        
        if(Configuration::get('MARKETPLACE_SELLER_RATING')) {
            $average = SellerComment::getRatings($id_seller);
            $averageTotal = SellerComment::getCommentNumber($id_seller);
            
            $this->context->smarty->assign(array(
                'averageTotal' => (int)$averageTotal,
                'averageMiddle' => ceil($average['avg']),
                'averagePositif' => (int)SellerComment::getPositifComment($id_seller),
            ));       
        }

        $this->assignProductList($seller);

        $this->context->smarty->assign(array(
            'show_shop_name' => Configuration::get('MARKETPLACE_SHOW_SHOP_NAME'),
            'show_phone' => Configuration::get('MARKETPLACE_SHOW_PHONE'),
            'show_fax' => Configuration::get('MARKETPLACE_SHOW_FAX'),
            'show_address' => Configuration::get('MARKETPLACE_SHOW_ADDRESS'),
            'show_country' => Configuration::get('MARKETPLACE_SHOW_COUNTRY'),
            'show_state' => Configuration::get('MARKETPLACE_SHOW_STATE'),
            'show_city' => Configuration::get('MARKETPLACE_SHOW_CITY'),
            'show_postcode' => Configuration::get('MARKETPLACE_SHOW_POSTAL_CODE'),
            'show_description' => Configuration::get('MARKETPLACE_SHOW_DESCRIPTION'),
            'show_logo' => Configuration::get('MARKETPLACE_SHOW_LOGO'),
            'moderate' => Configuration::get('MARKETPLACE_MODERATE_SELLER'),
            'show_orders' => Configuration::get('MARKETPLACE_SHOW_ORDERS'),
            'show_edit_seller_account' => Configuration::get('MARKETPLACE_SHOW_EDIT_ACCOUNT'),
            'show_contact' => Configuration::get('MARKETPLACE_SHOW_CONTACT'),
            'show_seller_favorite' => Configuration::get('MARKETPLACE_SELLER_FAVORITE'),
            'show_seller_rating' => Configuration::get('MARKETPLACE_SELLER_RATING'),
            'show_new_products' => Configuration::get('MARKETPLACE_NEW_PRODUCTS'),
            'seller' => $seller, 
            'seller_me' => $seller_me,
            'seller_products_link' => $url_seller_products,
            'url_favorite_seller' => $url_favorite_seller,
            'url_seller_comments' => $url_seller_comments,
            'url_sellers' => $url_sellers,
            'followers' => $seller->getFollowers(),
            'products' => $this->products,//$seller->getNewProducts($this->context->language->id),
            'token' => Configuration::get('MARKETPLACE_TOKEN'),
            'show_menu_options' => Configuration::get('MARKETPLACE_MENU_OPTIONS'),
            'base_dir' => __PS_BASE_URI__,
            'modules_dir' => __PS_BASE_URI__ .'/modules/'
        ));

        $this->setTemplate('module:marketplace/views/templates/front/sellerprofile_visit.tpl');
        //$this->setTemplate('sellerprofile_visit.tpl');
        

    }

    protected function assignProductList($seller)
    {    
        $this->nbProducts = $seller->getNumActiveProducts();
        $this->orderBy = 'p.`id_product`';

        //$this->pagination((int)$this->nbProducts); // Pagination must be call after "getProducts"
        
        if (!Tools::getValue('p'))
            $this->p = 0;
        else 
            $this->p = (int)(Tools::getValue('p')-1) * Configuration::get('PS_PRODUCTS_PER_PAGE');

        $this->n = Configuration::get('PS_PRODUCTS_PER_PAGE');


        $blocks_products = $seller->getNewProducts($this->context->language->id);

        $this->products = $this->prepareBlocksProducts($blocks_products);

        $this->context->smarty->assign(array(
                'pages_nb' => ceil($this->nbProducts / (int)$this->n),
                'nbProducts' => $this->nbProducts,
        ));
    }

    public function prepareBlocksProducts($products)
    {
        if ($products != false)
        {
            $products_for_template = [];
            $assembler = new ProductAssembler($this->context);
            $presenterFactory = new ProductPresenterFactory($this->context);
            $presentationSettings = $presenterFactory->getPresentationSettings();
            $presenter = new ProductListingPresenter(new ImageRetriever($this->context->link), $this->context->link, new PriceFormatter(), new ProductColorsRetriever(), $this->context->getTranslator());
            $products_for_template = [];
            foreach ($products as $rawProduct)
            {
                $products_for_template[] = $presenter->present($presentationSettings, $assembler->assembleProduct($rawProduct), $this->context->language);
            }

            return $products_for_template;
        }
        else
        {
            return false;
        }
    }

    public function getBreadcrumbLinks()
    {
        $breadcrumb = parent::getBreadcrumbLinks();        
        $breadcrumb['links'][] = [
            'title' => $this->module->l('sellers'),
            'url' => $this->context->link->getModuleLink('marketplace', 'sellers')
         ];
        
         return $breadcrumb;
     }
}