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

class marketplaceSellerofferAddModuleFrontController extends ModuleFrontController
{
    public $ssl = true;
    
    public function setMedia() {
        parent::setMedia();
    }
    
    public function postProcess() {
        if (Tools::isSubmit('submitAddSpecificPrice')) {
            
            $id_shop = 0;
            $id_shop_group = 0;

            if (Shop::isFeatureActive()) {
                $id_shop = (int)$this->context->shop->id;
                $id_shop_group = (int)$this->context->shop->id_shop_group;
            }
            
            $id_specific_price = Tools::getValue('id_specific_price');
            $id_product = Tools::getValue('id_product');
            $id_product_attribute = 0;
            $id_currency = 0;
            $id_country = 0;
            $id_group = 0;
            $id_customer = 0;
            $from_quantity = 1;
            $reduction = Tools::getValue('reduction');
            $reduction_tax = Tools::getValue('reduction_tax');
            $reduction_type = Tools::getValue('reduction_type');
            $reduction_mode = Tools::getValue('reduction_mode');
            $from = '0000-00-00 00:00:00';
            $to = '0000-00-00 00:00:00';
            
            if ($id_product == 0) 
                $this->errors[] = $this->module->l('You must select the product.', 'add');
            
            if ($reduction_type == 'amount') {
                if (!$reduction OR $reduction <= 0)
                    $this->errors[] = $this->module->l('Invalid reduction price.', 'add');
            }
            else {
                if (!$reduction OR $reduction > 1 OR $reduction <= 0)
                    $this->errors[] = $this->module->l('Invalid reduction price. Should be a value between 0 and 1.', 'add');
            }

            if (SpecificPrice::exists($id_product, $id_product_attribute, $id_shop, $id_group, $id_country, $id_currency, $id_customer, $from_quantity, $from, $to))
                $this->errors[] = $this->module->l('Specific price already exist.', 'add');

            if (count($this->errors) > 0) {
                $this->context->smarty->assign(array(
                    'errors' => $this->errors,
                    'id_product' => $id_product,
                    'reduction' => $reduction,
                    'reduction_tax' => $reduction_tax,
                    'reduction_type' => $reduction_type,
                ));
            }
            else {
                if ($id_specific_price > 0) 
                    $specificPrice = new SpecificPrice((int)$id_specific_price);
                else 
                    $specificPrice = new SpecificPrice();      

                $specificPrice->id_product = (int)$id_product;
                $specificPrice->id_shop = (int)$id_shop;
                $specificPrice->id_shop_group = (int)$id_shop_group;
                $specificPrice->id_currency = (int)$id_currency;
                $specificPrice->id_country = (int)$id_country;
                $specificPrice->id_group = (int)$id_group;
                $specificPrice->id_customer = (int)$id_customer;
                $specificPrice->id_product_attribute = (int)$id_product_attribute;
                $specificPrice->price = -1;
                $specificPrice->from_quantity = (int)$from_quantity;
                $specificPrice->reduction = (float)$reduction;
                $specificPrice->reduction_tax = (int)$reduction_tax;
                $specificPrice->reduction_type = $reduction_type;
                $specificPrice->reduction_mode = $reduction_mode;                
                $specificPrice->from = '0000-00-00 00:00:00';
                $specificPrice->to = '0000-00-00 00:00:00';

                $specificPrice->add();

                Hook::exec('actionProductUpdate', array('id_product' => (int)$id_product));
                Tools::redirect($this->context->link->getModuleLink('marketplace', 'sellerofferview'));
            }
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

        if (Tools::isSubmit('action'))
        {
            switch(Tools::getValue('action'))
            {
                case 'select_product':
                    $this->ajaxProcessShowCombinations();
                    break;
            }
        }
        
        if(!$this->context->cookie->id_customer)
            Tools::redirect($this->context->link->getPageLink('index'));
        
        $is_seller = Seller::isSeller($this->context->cookie->id_customer, $this->context->shop->id);
        if (!$is_seller)
            Tools::redirect($this->context->link->getPageLink('my-account'));
        
        $id_seller = Seller::getSellerByCustomer($this->context->cookie->id_customer);
        $seller = new Seller($id_seller);
        
        $start = 0;
        $limit = 9999;
        $order_by = 'date_add';
        $order_way = 'desc';

        $products = $seller->getProducts((int)$this->context->language->id, (int)$start, (int)$limit, (string)$order_by, (string)$order_way);
        
        if (is_array($products) && count($products) > 0) { 
            $currencies = Currency::getCurrencies();
            array_unshift($currencies, array('id_currency' => 0, 'name' => $this->module->l('All currencies', 'add')));

            $countries = Country::getCountries($this->context->language->id);
            array_unshift($countries, array('id_country' => 0, 'name' => $this->module->l('All countries', 'add')));

            $groups = Group::getGroups($this->context->language->id);
            array_unshift($groups, array('id_group' => 0, 'name' => $this->module->l('All groups', 'add')));

            $customers = Customer::getCustomers();
            array_unshift($customers, array('id_customer' => 0, 'email' =>$this->module->l('All customers', 'add')));

            $solde_act = false;

            $curdate = new DateTime(date('Y-m-d'));            

            $date_bal_min = new DateTime(Configuration::get('MARKETPLACE_BALANCE_DMIN_SELLER'));
            $date_bal_max = new DateTime(Configuration::get('MARKETPLACE_BALANCE_DMAX_SELLER'));

            $diffmax = round(($date_bal_max->getTimestamp() - $curdate->getTimestamp()) );
            $diffmin = round(($date_bal_min->getTimestamp() - $curdate->getTimestamp()) );
            //$diffmax = $date_bal_max - $curdate; //$curdate->diff($date_bal_max); 
            //$ = $curdate - $date_bal_min;//$curdate->diff($date_bal_min);

            if($diffmax>=0 && $diffmin<=0 )
                $solde_act = true;

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
            $url_seller_profile = marketplace::getmarketplaceLink('marketplace_seller_rule', $param);
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
                'id_specific_price' => 0,
                'id_seller' => $id_seller,
                'products' => $products,
                'solde_act' => $solde_act,
                'diffmax' => $diffmax,
                'diffmin' => $diffmin,
                'seller' => $seller,
                'path' => $path,
                'seller_link' => $url_seller_profile,
                'mesages_not_readed' => SellerIncidenceMessage::getNumMessagesNotReadedBySeller($id_seller),
                'content_only' => 1,
                'incidences' => $incidences,
                'menu' => 'module:marketplace/views/templates/front/sellermenu.tpl',
            ));

            //$this->setTemplate('sellerofferadd.tpl');
            $this->setTemplate('module:marketplace/views/templates/front/sellerofferadd.tpl');
        }
        else {
            Tools::redirect($this->context->link->getModuleLink('marketplace', 'sellerofferview'));
        }
    }
}