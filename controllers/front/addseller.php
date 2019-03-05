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

class marketplaceAddsellerModuleFrontController extends ModuleFrontController
{
    public $ssl = true;
    
    public function postProcess() {
        Hook::exec('actionMarketplaceBeforeAddSeller');
        $is_seller = Seller::isSeller($this->context->cookie->id_customer, $this->context->shop->id);
        
        if (Tools::isSubmit('submitAddSeller') && !$is_seller) {
            $seller_name = pSQL(Tools::getValue('name'));
            $seller_email = pSQL(Tools::getValue('email'));
            $shop = pSQL(Tools::getValue('shop'));
            $cif = pSQL(Tools::getValue('cif'));
            
            if (Tools::getValue('id_lang'))
                $id_lang = (int)Tools::getValue('id_lang');
            else
                $id_lang = $this->context->language->id;            
            
            if (Seller::existName($seller_name) > 0)
                $this->errors[] = $this->module->l('The name of seller already exists in our database.', 'addseller');
            
            if (!isset($seller_name) || $seller_name == '')
                $this->errors[] = $this->module->l('Invalid seller name.', 'addseller');

            if (!isset($shop) || $shop == '')
                $this->errors[] = $this->module->l('Vous devez remplir le nom de l\'entreprise.', 'addseller');
            
            if (!isset($cif) || $cif == '')
                $this->errors[] = $this->module->l('Vous devez remplir le numéro de SIREN.', 'addseller');

            if (Seller::existEmail($seller_email) > 0)
                $this->errors[] = $this->module->l('The email of seller already exists in our database.', 'addseller');
            
            if (!isset($seller_email) || $seller_email == '' || !Validate::isEmail($seller_email))
                $this->errors[] = $this->module->l('Invalid seller email.', 'addseller');
            
            if ($_FILES['sellerImage']['name'] != "") {
                if(!Seller::saveSellerImage($_FILES['sellerImage'], (int)$this->context->cookie->id_customer))
                    $this->errors[] = $this->module->l('The image seller format is incorrect.', 'addseller');
            }

            if($_FILES['sellerRCS']['name'] == "")
            {
                $this->errors[] = $this->module->l('Vous devez envoyez un extrait de RCS', 'addseller');
            }
            else if($_FILES['sellerRCS']['name'] != "") {
                if(!Seller::saveSellerRCS($_FILES['sellerRCS'], (int)$this->context->cookie->id_customer))
                    $this->errors[] = $this->module->l('RCS, format ou taille de fichier incorrect.', 'addseller');
            }


            if($_FILES['sellerCIN']['name'] == "")
            {
                $this->errors[] = $this->module->l('Vous devez envoyez une pièce d\'identité du gérant de l\'entreprise', 'addseller');
            }
            else if($_FILES['sellerCIN']['name'] != "") {
                if(!Seller::saveSellerPID($_FILES['sellerCIN'], (int)$this->context->cookie->id_customer))
                    $this->errors[] = $this->module->l('PID ,format ou taille de fichier incorrect.', 'addseller');
            }

            if (!Tools::getValue('conditions') && Configuration::get('MARKETPLACE_SHOW_TERMS') == 1)
                $this->errors[] = $this->module->l('You must agree to the terms of service before continuing.', 'addseller');
            
            if (!count($this->errors)) {
                $seller = new Seller();
                $seller->id_customer = (int)$this->context->cookie->id_customer;
                $seller->id_shop = (int)$this->context->shop->id;
                $seller->id_lang = $id_lang;
                $seller->name = $seller_name;
                $seller->email = $seller_email;
                $seller->link_rewrite = Seller::generateLinkRewrite($seller->name);
                $seller->shop = pSQL(Tools::getValue('shop'));
                $seller->cif = pSQL(Tools::getValue('cif'));
                $seller->phone = pSQL(Tools::getValue('phone'));
                $seller->fax = pSQL(Tools::getValue('fax'));
                $seller->address = pSQL(Tools::getValue('address'));
                $seller->country = pSQL(Tools::getValue('country'));
                $seller->state = pSQL(Tools::getValue('state'));
                $seller->city = pSQL(Tools::getValue('city'));
                $seller->postcode = pSQL(Tools::getValue('postcode'));
                $seller->description = (string)Tools::getValue('description'); //this is content html

                if (Configuration::get('MARKETPLACE_MODERATE_SELLER'))
                    $seller->active = 0;
                else
                    $seller->active = 1;
                
                $seller->add();
                
                $sellerPayment = new SellerPayment();
                $sellerPayment->id_seller = $seller->id;
                $sellerPayment->payment = 'paypal';
                $sellerPayment->add();
                
                $sellerPayment->payment = 'bankwire';
                $sellerPayment->add();
                
                $params = array('id_seller' => $seller->id);

                Hook::exec('actionMarketplaceAfterAddSeller', $params);
                
                if (Configuration::get('MARKETPLACE_MODERATE_SELLER') && Configuration::get('MARKETPLACE_SEND_ADMIN_REGISTER')) {

                    $attach[] = array();
                    // RCS file
                    if (file_exists(_PS_IMG_DIR_.'rcs/'.(int)$this->context->cookie->id_customer.'.pdf'))
                    {
                        
                        $nka_file = _PS_IMG_DIR_.'rcs/'.(int)$this->context->cookie->id_customer.'.pdf';
                        $content = Tools::file_get_contents($nka_file);
                        $attach[0]['content'] = $content;
                        $attach[0]['name'] ='RCS_'.(int)$this->context->cookie->id_customer;

                        $attach[0]['mime'] = 'application/pdf';
                    }
                    else if (file_exists(_PS_IMG_DIR_.'rcs/'.(int)$this->context->cookie->id_customer.'.jpg'))
                    {
                        //$attach = array();
                        $nka_file = _PS_IMG_DIR_.'rcs/'.(int)$this->context->cookie->id_customer.'.jpg';
                        $content = Tools::file_get_contents($nka_file);
                        $attach[0]['content'] = $content;
                        $attach[0]['name'] ='RCS_'.(int)$this->context->cookie->id_customer;
                        $attach[0]['mime'] = 'image/jpg';
                    }

                    // PID file
                    if (file_exists(_PS_IMG_DIR_.'pid/'.(int)$this->context->cookie->id_customer.'.pdf'))
                    {
                        //$attach = array();
                        $nka_file = _PS_IMG_DIR_.'pid/'.(int)$this->context->cookie->id_customer.'.pdf';
                        $content = Tools::file_get_contents($nka_file);
                        $attach[1]['content'] = $content;
                        $attach[1]['name'] ='PID_'.(int)$this->context->cookie->id_customer;

                        $attach[1]['mime'] = 'application/pdf';
                    }
                    else if (file_exists(_PS_IMG_DIR_.'pid/'.(int)$this->context->cookie->id_customer.'.jpg'))
                    {
                        //$attach = array();
                        $nka_file = _PS_IMG_DIR_.'pid/'.(int)$this->context->cookie->id_customer.'.jpg';
                        $content = Tools::file_get_contents($nka_file);
                        $attach[1]['content'] = $content;
                        $attach[1]['name'] ='PID_'.(int)$this->context->cookie->id_customer;
                        $attach[1]['mime'] = 'image/jpg';
                    }

                    $id_seller_email = false;
                    $to = Configuration::get('MARKETPLACE_SEND_ADMIN');
                    $to_name = Configuration::get('PS_SHOP_NAME');
                    $from = Configuration::get('PS_SHOP_EMAIL');
                    $from_name = Configuration::get('PS_SHOP_NAME');

                    $template = 'base';
                    $reference = 'new-seller';
                    $id_seller_email = SellerEmail::getIdByReference($reference);
                    
                    if ($id_seller_email) {
                        $seller_email = new SellerEmail($id_seller_email, Configuration::get('PS_LANG_DEFAULT'));
                        $vars = array("{shop_name}", "{seller_name}", "{seller_shop}","{siren}","{email}","{phone}","{address}","{country}");

                        $values = array(Configuration::get('PS_SHOP_NAME'), $seller->name,$seller->shop,$seller->cif,$seller->email,$seller->phone,$seller->address,$seller->country);

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
                                Mail::Send(
                                    Configuration::get('PS_LANG_DEFAULT'),
                                    $template,
                                    $subject_value,
                                    $template_vars,
                                    $to,
                                    $to_name,
                                    $from,
                                    $from_name,
                                    $attach,
                                    null,
                                    dirname(__FILE__).'/../../mails/'
                                );
                        }
                    }
                }
                
                if (Configuration::get('MARKETPLACE_SEND_SELLER_WELCOME')) {

                    

                    $id_seller_email = false;
                    $to = $seller->email;
                    $to_name = $seller->name;
                    $from = Configuration::get('PS_SHOP_EMAIL');
                    $from_name = Configuration::get('PS_SHOP_NAME');

                    $template = 'base';
                    $reference = 'welcome-seller';
                    $id_seller_email = SellerEmail::getIdByReference($reference);
                    
                    if ($id_seller_email) {
                        $seller_email = new SellerEmail($id_seller_email, $id_lang);

                        $vars = array("{shop_name}", "{seller_name}");
                        $values = array(Configuration::get('PS_SHOP_NAME'), $seller->name);
                        $subject_var = $seller_email->subject; 
                        $subject_value = str_replace($vars, $values, $subject_var);
                        $content_var = $seller_email->content;
                        $content_value = str_replace($vars, $values, $content_var);

                        $template_vars = array(
                            '{content}' => $content_value,
                            '{shop_name}' => Configuration::get('PS_SHOP_NAME')
                        );

                        $iso = Language::getIsoById($id_lang);

                        if (file_exists(dirname(__FILE__).'/../../mails/'.$iso.'/'.$template.'.txt') && file_exists(dirname(__FILE__).'/../../mails/'.$iso.'/'.$template.'.html')) {
                            Mail::Send(
                                $id_lang,
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
                        }
                    }
                }


                if (Configuration::get('MARKETPLACE_MODERATE_SELLER')){
                    if( Tools::getValue('back') ){
                        Tools::redirect( Tools::getValue('back') );
                    }else{
                        Tools::redirect($this->context->link->getPageLink('my-account', true));
                    }

                    $this->context->smarty->assign(array('confirmation' => 1));
                }
                else{
                    if( Tools::getValue('back') ){
                        Tools::redirect( Tools::getValue('back') );
                    }else{
                        Tools::redirect($this->context->link->getPageLink('my-account', true));
                    }
                }
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

        if ($is_seller) 
            Tools::redirect($this->context->link->getPageLink('my-account', true));
        
        $customer = new Customer($this->context->cookie->id_customer);
        
        if (Configuration::get('MARKETPLACE_SHOW_COUNTRY')) {
            $countries = Country::getCountries($this->context->language->id, true);
            $this->context->smarty->assign('countries', $countries);
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
            'show_postcode' => Configuration::get('MARKETPLACE_SHOW_POSTAL_CODE'),
            'show_description' => Configuration::get('MARKETPLACE_SHOW_DESCRIPTION'),
            'show_logo' => Configuration::get('MARKETPLACE_SHOW_LOGO'),
            'show_terms' => Configuration::get('MARKETPLACE_SHOW_TERMS'),
            'moderate' => Configuration::get('MARKETPLACE_MODERATE_SELLER'),
            'show_orders' => Configuration::get('MARKETPLACE_SHOW_ORDERS'),
            'show_edit_seller_account' => Configuration::get('MARKETPLACE_SHOW_EDIT_ACCOUNT'),
            'show_contact' => Configuration::get('MARKETPLACE_SHOW_CONTACT'),
            'customer_name' => $customer->firstname.' '.$customer->lastname,
            'customer_email' => $customer->email,
            'id_lang' => $this->context->language->id,
            'languages' => Language::getLanguages()
        ));
        
        if (Configuration::get('MARKETPLACE_SHOW_TERMS') == 1) {
            $cms = new CMS(Configuration::get('MARKETPLACE_CMS_TERMS'), $this->context->language->id);
            $cms_link = $this->context->link->getCMSLink($cms, $cms->link_rewrite, Configuration::get('PS_SSL_ENABLED'));
            
            if (!strpos($cms_link, '?'))
                $cms_link .= '?content_only=1';
            else
                $cms_link .= '&content_only=1';
            
            $this->context->smarty->assign(array(
                'cms_name' => $cms->meta_title,
                'cms_link' => $cms_link,
            ));
        }

        //$this->setTemplate('addseller.tpl');
        $this->setTemplate('module:marketplace/views/templates/front/addseller.tpl');
    }
}