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

class MarketplaceSellersModuleFrontController extends ModuleFrontController
{
    public $ssl = true;

    public function initContent() {
        
        parent::initContent();
        $this->getBreadcrumbLinks();

        $sellers = Seller::getFrontSellers($this->context->shop->id);
        
        $i = 0;
        foreach ($sellers as $s) {
            $param = array('id_seller' => $s['id_seller'], 'link_rewrite' => $s['link_rewrite']);
            
            $url_seller_profile = $this->module->getmarketplaceLink('marketplace_seller_rule', $param);
            
            $sellers[$i]['url'] = $url_seller_profile;           

            if (Seller::hasImage($s['id_customer']))
            {
                $files = glob(_PS_IMG_DIR_.'sellers/'.$s['id_customer'].'_*.jpg');
                foreach ($files as $file) {
                    if (file_exists($file))
                    {
                        $sellers[$i]['photo'] = __PS_BASE_URI__.'img/sellers/'. basename($file);
                    }
                }

                $sellers[$i]['has_image'] = 1;
                
            }
            else
            {
                $sellers[$i]['has_image'] = 0;
                $sellers[$i]['photo'] = '';
            }
            
            if(Configuration::get('MARKETPLACE_SELLER_RATING')) {
                $average = SellerComment::getRatings($s['id_seller']);
                $averageTotal = SellerComment::getCommentNumber($s['id_seller']);

                $sellers[$i]['averageTotal'] = (int)$averageTotal;
                $sellers[$i]['averageMiddle'] = ceil($average['avg']);
            }
            
            $i++;
        }

        $this->context->smarty->assign(array(
            'show_logo' => Configuration::get('MARKETPLACE_SHOW_LOGO'),
            'show_seller_rating' => Configuration::get('MARKETPLACE_SELLER_RATING'),
            'sellers' => $sellers, 
            'seller_link' => $url_seller_profile,
            'modules_dir' => __PS_BASE_URI__.'modules/',
        ));

        //$this->setTemplate('sellers.tpl');
        $this->setTemplate('module:marketplace/views/templates/front/sellers.tpl');
    }

    public function getBreadcrumbLinks()
    {
        $breadcrumb = parent::getBreadcrumbLinks();
        $breadcrumb['links'][] = $this->addMyAccountToBreadcrumb();
        

        return $breadcrumb;
     }
}