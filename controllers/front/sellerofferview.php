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

include_once dirname(__FILE__).'/../../classes/SellerOffer.php';

class marketplaceSellerofferViewModuleFrontController extends ModuleFrontController
{
    public $ssl = true;
    
    public function setMedia() {
        parent::setMedia();
    }
    
    public function postProcess() {
        if (Tools::getValue('delete_specific_price')) {
            $specific_price = new SpecificPrice((int)Tools::getValue('delete_specific_price'));
            $specific_price->delete();
            Tools::redirect($this->context->link->getModuleLink('marketplace', 'sellerofferview'));
        }
    }

    public function initContent() {
        
        parent::initContent();

        $path = _PS_MODULE_DIR_.'marketplace/';

        $this->context->controller->addCSS($path.'views/css/plugins/theme-default.css', 'all');

        $this->context->controller->addCSS($path.'views/css/plugins/mcustomscrollbar/jquery.mCustomScrollbar.css', 'all');

        // START THIS PAGE PLUGINS-->        
        $this->context->controller->addJS($path.'views/js/plugins/mcustomscrollbar/jquery.mCustomScrollbar.min.js');

        $this->context->controller->addJS($path.'views/js/plugins/icheck/icheck.min.js');                
                
        $this->context->controller->addJS($path.'views/js/plugins/bootstrap/bootstrap-file-input.js');

        $this->context->controller->addJS($path.'views/js/plugins/bootstrap/bootstrap-select.js');

                
        $this->context->controller->addJS($path.'views/js/plugins.js');

        $this->context->controller->addJS($path.'views/js/actions.js');
        
        if(!$this->context->cookie->id_customer)
            Tools::redirect($this->context->link->getPageLink('index'));
        
        $is_seller = Seller::isSeller($this->context->cookie->id_customer, $this->context->shop->id);
        if (!$is_seller)
            Tools::redirect($this->context->link->getPageLink('my-account'));
        
        $id_seller = Seller::getSellerByCustomer($this->context->cookie->id_customer);
        
        $specific_prices = SellerOffer::getSpecificPricesBySeller($id_seller, $this->context->language->id, $this->context->shop->id);
        
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
        $url_seller_profile = marketplace:: getmarketplaceLink('marketplace_seller_rule', $param);

        $incidences = SellerIncidence::getIncidencesBySeller($id_seller);

            if ($incidences != false) {
                $counter = 0;
                foreach ($incidences as $i) {
                    $product = new Product((int)$i['id_product'], (int)$this->context->language->id, (int)$this->context->shop->id);
                    $incidences[$counter]['product_name'] = $product->name;
                    $incidences[$counter]['messages_not_readed'] = SellerIncidence::getNumMessagesNotReaded($i['id_seller_incidence'], $id_seller, false);
                      
                    $messages = SellerIncidence::getMessages((int)$i['id_seller_incidence'],false,0,$id_seller);
                    
                    $incidences[$counter] = array_merge($incidences[$counter], array('messages' => $messages));
                    $counter++;
                }
            }

        $this->context->smarty->assign(array(
            'specific_prices' => $specific_prices,
            'seller' => $seller,
            'path' => $path,
            'seller_link' => $url_seller_profile,
            'mesages_not_readed' => SellerIncidenceMessage::getNumMessagesNotReadedBySeller($id_seller),
            'content_only' => 1,
            'incidences' => $incidences,
            'menu' => 'module:marketplace/views/templates/front/sellermenu.tpl',
        ));
        
        //$this->setTemplate('sellerofferview.tpl');
        $this->setTemplate('module:marketplace/views/templates/front/sellerofferview.tpl');         
    }
}