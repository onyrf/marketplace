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

class marketplaceEditsellerModuleFrontController extends ModuleFrontController
{
    public $ssl = true;
    
    
    public function postProcess() {
        
        $id_seller = Seller::getSellerByCustomer((int)$this->context->cookie->id_customer);
        $seller = new Seller($id_seller);
        
        $params = array('id_seller' => $seller->id);
        
        Hook::exec('actionMarketplaceBeforeUpdateSeller');
        
        if (Tools::isSubmit('submitEditSeller')) {
            $seller_name = pSQL(Tools::getValue('name'));
            $seller_email = pSQL(Tools::getValue('email'));
            
            if (Tools::getValue('id_lang'))
                $id_lang = (int)Tools::getValue('id_lang');
            else
                $id_lang = (int)$this->context->language->id;
            
            if (Seller::existName($seller_name) > 0 && $seller->name != $seller_name)
                $this->errors[] = $this->module->l('The name of seller already exists in our database.', 'editseller');
            
            if (!isset($seller_name) || $seller_name == '')
                $this->errors[] = $this->module->l('Invalid seller name.', 'editseller');
            
            if (Seller::existEmail($seller_email) > 0 && $seller->email != $seller_email)
                $this->errors[] = $this->module->l('The email of seller already exists in our database.', 'editseller');
            
            if (!isset($seller_email) || $seller_email == '' || !Validate::isEmail($seller_email))
                $this->errors[] = $this->module->l('Invalid seller email.', 'editseller');
            
            if ($_FILES['sellerImage']['name'] != "") {
                if(!Seller::saveSellerImage($_FILES['sellerImage'], $this->context->cookie->id_customer))
                    $this->errors[] = $this->module->l('The image seller format is incorrect.', 'editseller');
            }
            
            if (!count($this->errors)) {
                
                $seller->name = $seller_name;
                $seller->link_rewrite = Seller::generateLinkRewrite($seller->name);
                $seller->email = $seller_email;
                $seller->shop = Tools::getValue('shop');
                $seller->cif = pSQL(Tools::getValue('cif'));
                $seller->id_lang = $id_lang;
                $seller->phone = pSQL(Tools::getValue('phone'));
                $seller->fax = pSQL(Tools::getValue('fax'));
                $seller->address = pSQL(Tools::getValue('address'));
                $seller->country = pSQL(Tools::getValue('country'));
                $seller->state = pSQL(Tools::getValue('state'));
                $seller->city = pSQL(Tools::getValue('city'));
                $seller->postcode = pSQL(Tools::getValue('postcode'));
                $seller->description = (string)Tools::getValue('description'); //this is content html
                
                if (Configuration::get('MARKETPLACE_MODERATE_SELLER'))
                    $seller->active = 1;
                
                $seller->update();
                
                $params = array('id_seller' => $seller->id);

                Hook::exec('actionMarketplaceAfterUpdateSeller', $params);
                
                if (Configuration::get('MARKETPLACE_MODERATE_SELLER') && Configuration::get('MARKETPLACE_SEND_ADMIN_REGISTER')) {
                    $id_seller_email = false;
                    $to = Configuration::get('MARKETPLACE_SEND_ADMIN');
                    $to_name = Configuration::get('PS_SHOP_NAME');
                    $from = Configuration::get('PS_SHOP_EMAIL');
                    $from_name = Configuration::get('PS_SHOP_NAME');

                    $template = 'base';
                    $reference = 'edit-seller';
                    $id_seller_email = SellerEmail::getIdByReference($reference);
                    
                    if ($id_seller_email) {
                        $seller_email = new SellerEmail($id_seller_email, Configuration::get('PS_LANG_DEFAULT'));
                        $vars = array("{shop_name}", "{seller_name}", "{seller_shop}");
                        $values = array(Configuration::get('PS_SHOP_NAME'), $seller->name, $seller->shop);
                        $subject_var = $seller_email->subject; 
                        $subject_value = str_replace($vars, $values, $subject_var);
                        $content_var = $seller_email->content;
                        $content_value = str_replace($vars, $values, $content_var);

                        $template_vars = array(
                            '{content}' => $content_value,
                            '{shop_name}' => Configuration::get('PS_SHOP_NAME')
                        );

                        $iso = Language::getIsoById(Configuration::get('PS_LANG_DEFAULT'));

                        if (file_exists(dirname(__FILE__).'/../../mails/'.$iso.'/'.$template.'.txt') && file_exists(dirname(__FILE__).'/../../mails/'.$iso.'/'.$template.'.html')) {
                            //$merchant_mails = explode("\n", Configuration::get('MA_MERCHANT_MAILS'));
                            //foreach ($merchant_mails as $merchant_mail)
                            //{
                                Mail::Send(
                                    Configuration::get('PS_LANG_DEFAULT'),
                                    $template,
                                    $subject_value,
                                    $template_vars,
                                    $to,
                                    $to_name,
                                    $from,
                                    $from_name,
                                    null,
                                    null,
                                    dirname(__FILE__).'/../../mails/'
                                );
                            //}
                        }
                    }
                }
                
                $this->context->smarty->assign(array('confirmation' => 1));
            }
            else {   
                $this->context->smarty->assign(array('errors' => $this->errors));
            }
        }
    }

    public function initContent() {
        
        parent::initContent();
        
        if(!$this->context->cookie->id_customer)
            Tools::redirect($this->context->link->getPageLink('my-account', true));
        
        $is_seller = Seller::isSeller($this->context->cookie->id_customer, $this->context->shop->id);
        $id_seller = Seller::getSellerByCustomer($this->context->cookie->id_customer);

        if (!$is_seller) 
            Tools::redirect($this->context->link->getPageLink('my-account', true));
        
        $seller = new Seller($id_seller);
        
        if ($seller->active == 0)
            Tools::redirect($this->context->link->getPageLink('my-account', true));
        
        if (!Configuration::get('MARKETPLACE_SHOW_EDIT_ACCOUNT')) 
            Tools::redirect($this->context->link->getPageLink('my-account', true));
        
        $param = array('id_seller' => $id_seller);			
	if (version_compare(_PS_VERSION_, '1.6.0.12', '>')) {
            $param = array('id_seller' => $seller->id, 'link_rewrite' => $seller->link_rewrite);
            $url_seller_profile = $this->module->getmarketplaceLink('marketplace_seller_rule', $param);
        }
        else {
            $url_seller_profile = $this->context->link->getModuleLink('marketplace', 'sellerprofile', $param);
        }
        
        if (Configuration::get('MARKETPLACE_SHOW_COUNTRY')) {
            $countries = Country::getCountries($this->context->language->id, true);
            $this->context->smarty->assign('countries', $countries);
        }

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

        

        $this->context->smarty->assign(array(
            'show_shop_name' => Configuration::get('MARKETPLACE_SHOW_SHOP_NAME'),
            'show_cif' => Configuration::get('MARKETPLACE_SHOW_CIF'),
            'show_language' => Configuration::get('MARKETPLACE_SHOW_LANGUAGE'),
            'show_phone' => Configuration::get('MARKETPLACE_SHOW_PHONE'),
            'show_fax' => Configuration::get('MARKETPLACE_SHOW_FAX'),
            'show_address' => Configuration::get('MARKETPLACE_SHOW_ADDRESS'),
            'show_country' => Configuration::get('MARKETPLACE_SHOW_COUNTRY'),
            'show_state' => Configuration::get('MARKETPLACE_SHOW_STATE'),
            'show_city' => Configuration::get('MARKETPLACE_SHOW_CITY'),
            'show_country' => Configuration::get('MARKETPLACE_SHOW_COUNTRY'),
            'show_state' => Configuration::get('MARKETPLACE_SHOW_STATE'),
            'show_city' => Configuration::get('MARKETPLACE_SHOW_CITY'),
            'show_postcode' => Configuration::get('MARKETPLACE_SHOW_POSTAL_CODE'),
            'show_description' => Configuration::get('MARKETPLACE_SHOW_DESCRIPTION'),
            'show_logo' => Configuration::get('MARKETPLACE_SHOW_LOGO'),
            'moderate' => Configuration::get('MARKETPLACE_MODERATE_SELLER'),
            'show_import_product' => Configuration::get('MARKETPLACE_SELLER_IMPORT_PROD'),
            'show_orders' => Configuration::get('MARKETPLACE_SHOW_ORDERS'),
            'show_edit_seller_account' => Configuration::get('MARKETPLACE_SHOW_EDIT_ACCOUNT'),
            'show_contact' => Configuration::get('MARKETPLACE_SHOW_CONTACT'),
            'show_manage_orders' => Configuration::get('MARKETPLACE_SHOW_MANAGE_ORDERS'),
            'show_manage_carriers' => Configuration::get('MARKETPLACE_SHOW_MANAGE_CARRIER'),
            'show_dashboard' => Configuration::get('MARKETPLACE_SHOW_DASHBOARD'),
            'show_menu_top' => Configuration::get('MARKETPLACE_MENU_TOP'),
            'show_menu_options' => Configuration::get('MARKETPLACE_MENU_OPTIONS'),
            'seller' => $seller, 
            'seller_link' => $url_seller_profile,
            'languages' => Language::getLanguages(),
            'mesages_not_readed' => SellerIncidenceMessage::getNumMessagesNotReadedBySeller($id_seller),
            'incidences' => $incidences,
            'content_only' => 1,
            'menu' => 'module:marketplace/views/templates/front/sellermenu.tpl',
            
        ));
        
        /*if (file_exists(_PS_IMG_DIR_.'sellers/'.$this->context->cookie->id_customer.'.jpg'))
            $this->context->smarty->assign(array('photo' => $this->context->cookie->id_customer.'.jpg'));*/

        //$this->setTemplate('editseller.tpl');
        $this->setTemplate('module:marketplace/views/templates/front/editseller.tpl');
    }
}