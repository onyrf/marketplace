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

class marketplaceSellerholidaysModuleFrontController extends ModuleFrontController
{
    public $ssl = true;
    private $path;

    public function setMedia() {   
        parent::setMedia();

        $this->path = _PS_MODULE_DIR_.'marketplace/';

        $this->context->controller->addCSS($this->path.'views/css/plugins/mcustomscrollbar/jquery.mCustomScrollbar.css', 'all');

        $this->addJqueryUI('ui.datepicker');
        $this->context->controller->addJS($this->module->getPathUri().'views/js/sellerholidays.js');

    }
    
    public function postProcess() {
        
        $id_seller = Seller::getSellerByCustomer((int)$this->context->cookie->id_customer);
        
        if (Tools::isSubmit('submitAddHolidays')) {  
            //$from = Tools::getValue('from');
            //$to = Tools::getValue('todate');
            $seller_holiday = new SellerHoliday();

            $from_array = explode('/', pSQL(Tools::getValue('from')));
            $from = $from_array[2].'-'.$from_array[1].'-'.$from_array[0];
            
            $to_array = explode('/', pSQL(Tools::getValue('todate')));
            $to = $to_array[2].'-'.$to_array[1].'-'.$to_array[0];
            
            
            $seller_holiday->id_seller = $id_seller;
            $seller_holiday->from = $from;
            $seller_holiday->to = $to;
            
            $seller_holiday->add();
            
            $current_date = date('Y-m-d');                    
            if (SellerHoliday::compareDates($current_date, $seller_holiday->from) <= 0) {
                $seller = new Seller($id_seller);
                $seller_products = $seller->getIdProducts();
                if (is_array($seller_products)) {
                    foreach ($seller_products as $sp) {
                        $product = new Product($sp['id_product']);
                        $product->available_for_order = 0;
                        $product->update();
                    }
                }
            }
            
            Tools::redirect($this->context->link->getModuleLink('marketplace', 'sellerholidays').'?confirmation=1');
        }
        
        if (Tools::getValue('cancel')) {  
            if ($id_seller) {
                $id_seller_holiday = Tools::getValue('cancel');
                $seller_holiday = new SellerHoliday((int)$id_seller_holiday);
                
                if ($seller_holiday->id_seller == $id_seller) {
                    $current_date = date('Y-m-d');                    
                    if (SellerHoliday::compareDates($current_date, $seller_holiday->from) <= 0) {
                        //cancelar vacaciones en curso
                        $seller = new Seller($id_seller);
                        $seller_products = $seller->getIdProducts();
                        if (is_array($seller_products)) {
                            foreach ($seller_products as $sp) {
                                $product = new Product($sp['id_product']);
                                $product->available_for_order = 1;
                                $product->update();
                            }
                        }
                    }
                    
                    $seller_holiday->delete();
                }
            }
        }
    }

    public function initContent() {
        
        parent::initContent();
        
        $this->path = _PS_MODULE_DIR_.'marketplace/';        
        
        $this->context->controller->addCSS($this->path.'views/css/plugins/theme-default.css', 'all');        
        
        // START THIS PAGE PLUGINS-->        
        $this->context->controller->addJS($this->path.'views/js/plugins/mcustomscrollbar/jquery.mCustomScrollbar.min.js');

        $this->context->controller->addJS($this->path.'views/js/plugins/icheck/icheck.min.js');                
                
        $this->context->controller->addJS($this->path.'views/js/plugins/bootstrap/bootstrap-file-input.js');

        $this->context->controller->addJS($this->path.'views/js/plugins/bootstrap/bootstrap-select.js');

                
        $this->context->controller->addJS($this->path.'views/js/plugins.js');

        $this->context->controller->addJS($this->path.'views/js/actions.js');

        if(!$this->context->cookie->id_customer)
            Tools::redirect($this->context->link->getPageLink('index'));
        
        $is_seller = Seller::isSeller($this->context->cookie->id_customer, $this->context->shop->id);
        if (!$is_seller)
            Tools::redirect($this->context->link->getPageLink('my-account'));
        
        $id_seller = Seller::getSellerByCustomer($this->context->cookie->id_customer);
        
        $holidays = SellerHoliday::getHolidaysBySeller($id_seller);        

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
            'holidays' => $holidays,
            'seller' => $seller,
            'path' => $this->path,
            'seller_link' => $url_seller_profile,
            'mesages_not_readed' => SellerIncidenceMessage::getNumMessagesNotReadedBySeller($id_seller),
            'content_only' => 1,
            'incidences' => $incidences,
            'menu' => 'module:marketplace/views/templates/front/sellermenu.tpl',
        ));
        
        if (Tools::getValue('confirmation'))
            $this->context->smarty->assign(array('confirmation' => 1));

        //$this->setTemplate('sellerholidays.tpl');
        $this->setTemplate('module:marketplace/views/templates/front/sellerholidays.tpl');
    }
}