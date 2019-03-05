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

class marketplaceContactsellerModuleFrontController extends ModuleFrontController
{
    public $ssl = true;
    
    protected function ajaxProcessReadMessage()
    {
        $id_seller_incidence = (int)Tools::getValue('id_seller_incidence');
        $messages = SellerIncidence::getMessages($id_seller_incidence,false,(int)$this->context->cookie->id_customer);
        foreach ($messages as $message) {
            if ($message['id_customer'] == 0) {
                $message = new SellerIncidenceMessage($message['id_seller_incidence_message']);
                $message->readed = 1;
                $message->update();
            }
        }  
    }

    protected function ajaxProcessReadMessageCustomer()
    {
        $id_seller_incidence = (int)Tools::getValue('id_seller_incidence');
        $messages = SellerIncidence::getMessages($id_seller_incidence,false,(int)$this->context->cookie->id_customer);
        foreach ($messages as $message) {
            //if ($message['id_customer'] == 0) {
                $message_new = new SellerIncidenceMessage($message['id_seller_incidence_message']);
                $message_new->readed_cust = 1;
                $message_new->update();
            //}
        }  
    }

    protected function ajaxProcessDeleteMessageCustomer()
    {
        $id_seller_incidence = (int)Tools::getValue('id_seller_incidence');
        $messages_incid = new SellerIncidence($id_seller_incidence);

        $messages = SellerIncidence::getMessages($id_seller_incidence,false,(int)$this->context->cookie->id_customer);
        foreach ($messages as $message) {            
                $message_new = new SellerIncidenceMessage($message['id_seller_incidence_message']);
                //$message_new->readed_cust = 1;
                $message_new->delete();
            
        }

        $messages_incid->delete();

        return true;
    }
    
    public function notifyForQuestionOrder(){
        if (Tools::getValue('id_order') && (int)Tools::getValue('id_order') > 0) {
            //$string_order_product = Tools::getValue('id_order_product');
            //$id_order_product = explode('-', $string_order_product);
            $id_order = (int)Tools::getValue('id_order');//$id_order_product[0];
            $id_product = 0;//(int)$id_order_product[1];
            $seller = SellerOrder::getSellerByOrder($id_order,$this->context->language->id);
        }
        else {
            $id_order = 0;
            $id_product = (int)Tools::getValue('id_product');
            $seller = Seller::getSellerInfosByProduct($id_product);
        }

        if(Tools::getValue('id_cart'))
            $id_cart = (int)Tools::getValue('id_cart');
        else
            $id_cart = 0;

        if(isset($seller['id_seller']) && $seller['id_seller'])
            $id_seller = $seller['id_seller'];
        else
            $id_seller = 0;

        if ($id_seller == 0) // si mega-discount
        {
            $lang = $this->context->language->id;
            $ct = new CustomerThread();
            if (isset($this->context->customer->id)) {
                $ct->id_customer = (int)$this->context->customer->id;
            }
            $ct->id_shop = (int)$this->context->shop->id;
            $ct->id_order = (int)$id_order;
            if ($id_product = (int)Tools::getValue('id_product')) {
                $ct->id_product = $id_product;
            }
            $ct->id_contact = 0;
            $ct->id_lang = (int)$this->context->language->id;
            $ct->email = $this->context->customer->email;
            $ct->status = 'open';
            $ct->token = Tools::passwdGen(12);
            $ct->add();

            if ($ct->id) {
                $cm = new CustomerMessage();
                $cm->id_customer_thread = $ct->id;
                $cm->message = (string)Tools::getValue('description');
                
                $cm->ip_address = (int)ip2long(Tools::getRemoteAddr());
                $cm->user_agent = $_SERVER['HTTP_USER_AGENT'];
                if (!$cm->add()) {
                    return false;
                }
            } else {
                return false;
            }

        }
        else
        {
            $seller = new Seller($id_seller);
            $lang = $seller->id_lang;
        }

        $incidence = new SellerIncidence();
        $incidence->reference = pSQL(SellerIncidence::generateReference());
        $incidence->id_order = (int)$id_order;
        $incidence->id_product = (int)$id_product;
        $incidence->id_customer = (int)$this->context->cookie->id_customer;
        $incidence->id_seller = (int)$id_seller;
        $incidence->id_shop = (int)$this->context->shop->id;
        $incidence->id_cart = $id_cart;
        $incidence->en_attente = 1;

        /*if( Configuration::get('MARKETPLACE_MODERATE_MESSAGE') ){
            $incidence->active = 0;
        }else{*/
            $incidence->active = 1;
        //}

        $incidence->add();
        
        $incidenceMessage = new SellerIncidenceMessage();
        $incidenceMessage->id_seller_incidence = (int)$incidence->id;
        $incidenceMessage->id_customer = (int)$this->context->cookie->id_customer;
        $incidenceMessage->id_seller = 0;        
        $incidenceMessage->description = (string)Tools::getValue('description'); //this is content html
        $incidenceMessage->active = 1;
        $incidenceMessage->readed = 0;
        $incidenceMessage->readed_cust = 1;
        $incidenceMessage->add();

        if($id_seller == 0)
        {
            $id_seller_email = false;
            $to = Configuration::get('MARKETPLACE_SEND_ADMIN');
            $to_name = Configuration::get('PS_SHOP_NAME');
        }
        else
        {
            $id_seller_email = false;
            $to = $seller->email;
            $to_name = $seller->name;
        }
        
        $from = Configuration::get('PS_SHOP_EMAIL');
        $from_name = Configuration::get('PS_SHOP_NAME');
        $template = 'base';

        if ($id_order == 0) { // message placé dans le tunnel du panier
            $reference = 'new-message';
            if( Configuration::get('MARKETPLACE_MODERATE_MESSAGE') ){
                $to = Configuration::get('MARKETPLACE_SEND_ADMIN');
                $to_name = "ADMIN";
                $reference = 'new-message-validation';   
            }
        
            if ($id_product != 0) {
                $product = new Product($id_product, false, $lang);
                $vars = array("{shop_name}", "{incidence_reference}", "{description}", "{product_name}");
                $values = array(Configuration::get('PS_SHOP_NAME'), $incidence->reference, nl2br($incidenceMessage->description), $product->name);
            }
            else {
                $vars = array("{shop_name}", "{incidence_reference}", "{description}");
                $values = array(Configuration::get('PS_SHOP_NAME'), $incidence->reference, nl2br($incidenceMessage->description));
            }
        }
        else { // message placé dans la commande
            $order = new Order($incidence->id_order); 
            $reference = 'new-incidence';
            if( Configuration::get('MARKETPLACE_MODERATE_MESSAGE') ){
                $to = Configuration::get('MARKETPLACE_SEND_ADMIN');
                $to_name = "ADMIN";
                $reference = 'new-incidence-validation';   
            }
            $vars = array("{shop_name}", "{order_reference}", "{incidence_reference}", "{description}");
            $values = array(Configuration::get('PS_SHOP_NAME'), $order->reference, $incidence->reference, nl2br(Tools::getValue('description')));
        }
        
        // si ce n'est pas un message laissez dans le tunnel de commande
        if ($id_cart == 0 )
        {
            $id_seller_email = SellerEmail::getIdByReference($reference);

            if ($id_seller_email) {
                $seller_email = new SellerEmail($id_seller_email, $lang);
                $subject_var = $seller_email->subject; 
                $subject_value = str_replace($vars, $values, $subject_var);
                $content_var = $seller_email->content;
                $content_value = str_replace($vars, $values, $content_var);

                $template_vars = array(
                    '{content}' => $content_value,
                    '{shop_name}' => Configuration::get('PS_SHOP_NAME')
                );

                $iso = Language::getIsoById($lang);

                if (file_exists(dirname(__FILE__).'/../../mails/'.$iso.'/'.$template.'.txt') && file_exists(dirname(__FILE__).'/../../mails/'.$iso.'/'.$template.'.html')) 
                {
                    Mail::Send(
                        $lang,
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

                    $seller_email = new SellerEmail($id_seller_email, $lang);
                    $subject_var = $seller_email->subject; 
                    $subject_value = str_replace($vars, $values, $subject_var);
                    $content_var = $seller_email->content;
                    $content_value = str_replace($vars, $values, $content_var);

                    $template_vars = array(
                        '{content}' => $content_value,
                        '{shop_name}' => Configuration::get('PS_SHOP_NAME')
                    );
                
                    $to = Configuration::get('MARKETPLACE_SEND_ADMIN');
                    $to_name = "ADMIN";
                    $reference = 'new-incidence-validation';

                    //$merchant_mails = explode("\n", Configuration::get('MA_MERCHANT_MAILS'));
                    //foreach ($merchant_mails as $merchant_mail)
                    //{
                        Mail::Send(
                            $lang,
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

                    //notify client for this message sent
        $reference = 'new-message-client';
                    
        $id_seller_email = SellerEmail::getIdByReference($reference);

        $seller_email = new SellerEmail($id_seller_email, $lang);

        $subject_var = $seller_email->subject; 

        $vars = array("{seller_name}","{shop_name}", "{incidence_reference}", "{description}");

        $values = array($seller->name,Configuration::get('PS_SHOP_NAME'), $incidence->reference, nl2br($incidenceMessage->description));

        $subject_value = str_replace($vars, $values, $subject_var);

        $content_var = $seller_email->content;
        $content_value = str_replace($vars, $values, $content_var);

        $template_vars = array(
            '{content}' => $content_value,
            '{shop_name}' => Configuration::get('PS_SHOP_NAME')
        );
        $customer = new Customer($this->context->cookie->id_customer);

        $to = $customer->email;
        $to_name = $customer->name;
        $from = Configuration::get('PS_SHOP_EMAIL');
        $from_name = Configuration::get('PS_SHOP_NAME');
                    
        $template = 'base';                    

        if (file_exists(dirname(__FILE__).'/../../mails/'.$iso.'/'.$template.'.txt') && file_exists(dirname(__FILE__).'/../../mails/'.$iso.'/'.$template.'.html')) {
                Mail::Send(
                    $lang,
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
        }
        
        $params = array('id_seller' => $id_seller, 'id_product' => $id_product, 'confirmation' => 1);
        
        die('1');
        //$url_contact_seller_confirmation = $this->context->link->getModuleLink('marketplace', 'contactseller', $params, true);      


        //Tools::redirect($url_contact_seller_confirmation);
    }

    
    public function notifyForQuestion(){
        if (Tools::getValue('id_order_product')) {
            $string_order_product = Tools::getValue('id_order_product');
            $id_order_product = explode('-', $string_order_product);
            $id_order = (int)$id_order_product[0];
            $id_product = (int)$id_order_product[1];
        }
        else {
            $id_order = 0;
            $id_product = (int)Tools::getValue('id_product');
        }

        $id_seller = Seller::getSellerByProduct($id_product);
        $seller = new Seller($id_seller);

        $incidence = new SellerIncidence();
        $incidence->reference = pSQL(SellerIncidence::generateReference());
        $incidence->id_order = (int)$id_order;
        $incidence->id_product = (int)$id_product;
        $incidence->id_customer = (int)$this->context->cookie->id_customer;
        $incidence->id_seller = (int)$id_seller;
        $incidence->id_shop = (int)$this->context->shop->id;
        /*if( Configuration::get('MARKETPLACE_MODERATE_MESSAGE') ){
            $incidence->active = 0;
        }else{*/
            $incidence->active = 1;
        //}

        $incidence->add();
        
        $incidenceMessage = new SellerIncidenceMessage();
        $incidenceMessage->id_seller_incidence = (int)$incidence->id;
        $incidenceMessage->id_customer = (int)$this->context->cookie->id_customer;
        $incidenceMessage->id_seller = 0;
        $incidenceMessage->description = (string)Tools::getValue('description'); //this is content html
        $incidenceMessage->readed = 0;
        $incidenceMessage->active = 1;
        $incidenceMessage->readed_cust = 1;
        $incidenceMessage->add();
        
        $id_seller_email = false;
        if($id_seller == 0)
        {
            $to = Configuration::get('MARKETPLACE_SEND_ADMIN');
            $to_name = Configuration::get('PS_SHOP_NAME');
        }
        else
        {
            $to = $seller->email;
            $to_name = $seller->name;
        }
        $from = Configuration::get('PS_SHOP_EMAIL');
        $from_name = Configuration::get('PS_SHOP_NAME');
        $template = 'base';
        
        if ($id_order == 0) {
            $reference = 'new-message';
            if( Configuration::get('MARKETPLACE_MODERATE_MESSAGE') ){
                $to = Configuration::get('MARKETPLACE_SEND_ADMIN');
                $to_name = "ADMIN";
                $reference = 'new-message-validation';   
            }
        
            if ($id_product != 0) {
                $product = new Product($id_product, false, $lang);
                $vars = array("{shop_name}", "{incidence_reference}", "{description}", "{product_name}");
                $values = array(Configuration::get('PS_SHOP_NAME'), $incidence->reference, nl2br($incidenceMessage->description), $product->name);
            }
            else {
                $vars = array("{shop_name}", "{incidence_reference}", "{description}");
                $values = array(Configuration::get('PS_SHOP_NAME'), $incidence->reference, nl2br($incidenceMessage->description));
            }
        }
        else {
            $order = new Order($incidence->id_order); 
            $reference = 'new-incidence';
            if( Configuration::get('MARKETPLACE_MODERATE_MESSAGE') ){
                $to = Configuration::get('MARKETPLACE_SEND_ADMIN');
                $to_name = "ADMIN";
                $reference = 'new-incidence-validation';   
            }
            $vars = array("{shop_name}", "{order_reference}", "{incidence_reference}", "{description}");
            $values = array(Configuration::get('PS_SHOP_NAME'), $order->reference, $incidence->reference, nl2br($incidenceMessage->description));
        }
        
        $id_seller_email = SellerEmail::getIdByReference($reference);

        if ($id_seller_email) {
            $seller_email = new SellerEmail($id_seller_email, $lang);
            $subject_var = $seller_email->subject; 
            $subject_value = str_replace($vars, $values, $subject_var);
            $content_var = $seller_email->content;
            $content_value = str_replace($vars, $values, $content_var);

            $template_vars = array(
                '{content}' => $content_value,
                '{shop_name}' => Configuration::get('PS_SHOP_NAME')
            );

            $iso = Language::getIsoById($lang);

            if (file_exists(dirname(__FILE__).'/../../mails/'.$iso.'/'.$template.'.txt') && file_exists(dirname(__FILE__).'/../../mails/'.$iso.'/'.$template.'.html')) {
                Mail::Send(
                    $lang,
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
        
        //notify client for this message sent
        $reference = 'new-message-client';
                    
        $id_seller_email = SellerEmail::getIdByReference($reference);

        $seller_email = new SellerEmail($id_seller_email, $lang);

        $subject_var = $seller_email->subject; 

        $vars = array("{seller_name}","{shop_name}", "{incidence_reference}", "{description}");

        $values = array($seller->name,Configuration::get('PS_SHOP_NAME'), $incidence->reference, nl2br($incidenceMessage->description));

        $subject_value = str_replace($vars, $values, $subject_var);

        $content_var = $seller_email->content;
        $content_value = str_replace($vars, $values, $content_var);

        $template_vars = array(
            '{content}' => $content_value,
            '{shop_name}' => Configuration::get('PS_SHOP_NAME')
        );
        $customer = new Customer($this->context->cookie->id_customer);

        $to = $customer->email;
        $to_name = $customer->name;
        $from = Configuration::get('PS_SHOP_EMAIL');
        $from_name = Configuration::get('PS_SHOP_NAME');
                    
        $template = 'base';                    

        if (file_exists(dirname(__FILE__).'/../../mails/'.$iso.'/'.$template.'.txt') && file_exists(dirname(__FILE__).'/../../mails/'.$iso.'/'.$template.'.html')) {
                Mail::Send(
                    $lang,
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
            
        $params = array('id_seller' => $id_seller, 'id_product' => $id_product, 'confirmation' => 1);
        
        $url_contact_seller_confirmation = $this->context->link->getModuleLink('marketplace', 'contactseller', $params, true);      

        Tools::redirect($url_contact_seller_confirmation);
    }
    
    public function notifyForResponse(){
        $incidenceMessage = new SellerIncidenceMessage();
        $incidenceMessage->id_seller_incidence = (int)Tools::getValue('id_incidence');
        $incidenceMessage->id_customer = (int)$this->context->cookie->id_customer;
        $incidenceMessage->id_seller = 0;
        $incidenceMessage->description = (string)Tools::getValue('description'); //this is content html
        $incidenceMessage->readed = 0;
        $incidenceMessage->readed_cust = 1;
        
        /*if( Configuration::get('MARKETPLACE_MODERATE_MESSAGE') ){
            $incidenceMessage->active = 0;
        }else{*/
            $incidenceMessage->active = 1;
        //}
        $incidenceMessage->add();
        
        $incidence = new SellerIncidence($incidenceMessage->id_seller_incidence);
        
        $id_seller_email = false;
        $template = 'base';
        $reference = 'new-response-customer';
        
        $messages = SellerIncidence::getMessages($incidenceMessage->id_seller_incidence,false,0,(int)$this->context->cookie->id_customer,'DESC');

        if( Configuration::get('MARKETPLACE_MODERATE_MESSAGE') ){
            $reference = 'new-response-customer-validation';
        }
        
        $id_seller_email = SellerEmail::getIdByReference($reference);
        if ($id_seller_email) {
            $seller = new Seller($incidence->id_seller);
       
            $to = $seller->email;
            $to_name = $seller->name;
            $from = Configuration::get('PS_SHOP_EMAIL');
            $from_name = Configuration::get('PS_SHOP_NAME');
            
            if( Configuration::get('MARKETPLACE_MODERATE_MESSAGE') ){
                $to = Configuration::get('MARKETPLACE_SEND_ADMIN');
                $to_name = "ADMIN";   
            }
            
            $table = '';
                foreach ($messages as $key => $message) {
                    $table .= '<tr><td>Message envoyé par: <b>'.$message['customer_firstname'] .' '. $message['customer_lastname'].' '.$message['seller_name'].'</b> - du '.Tools::displayDate($message['date_add'],null , true) .'</td></tr>';

                    if($message['seller_name'] != '')
                    {
                        $table .= '<tr><td style="padding:10px;border:1px solid red;">'.$message['description'].'</td></tr><tr><td>&nbsp;</td></tr>';
                    }
                    else
                    {
                        $table .= '<tr><td style="padding:10px;border:1px solid green;">'.$message['description'].'</td></tr><tr><td>&nbsp;</td></tr>';
                    }


                    if($key == 0 && count($messages) > 1)
                        $table .= '<tr><td style="text-align:center"><hr> l\'historique de vos conversations<hr><br></td></tr>';
                }

            $seller_email = new SellerEmail($id_seller_email, $seller->id_lang);
            $vars = array("{shop_name}", "{incidence_reference}", "{description}");
            $values = array(Configuration::get('PS_SHOP_NAME'), $incidence->reference, nl2br($table));
            $subject_var = $seller_email->subject; 
            $subject_value = str_replace($vars, $values, $subject_var);
            $content_var = $seller_email->content;
            $content_value = str_replace($vars, $values, $content_var);

            $template_vars = array(
                '{content}' => $content_value,
                '{shop_name}' => Configuration::get('PS_SHOP_NAME')
            );

            $iso = Language::getIsoById($seller->id_lang);

            if (file_exists(dirname(__FILE__).'/../../mails/'.$iso.'/'.$template.'.txt') && file_exists(dirname(__FILE__).'/../../mails/'.$iso.'/'.$template.'.html')) {
                Mail::Send(
                    $seller->id_lang,
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

        //notify client for this message sent
        $reference = 'new-message-client';
                    
        $id_seller_email = SellerEmail::getIdByReference($reference);

        $seller_email = new SellerEmail($id_seller_email, $seller->id_lang);

        $subject_var = $seller_email->subject; 

        $vars = array("{seller_name}","{shop_name}", "{incidence_reference}", "{description}");

        $values = array($seller->name,Configuration::get('PS_SHOP_NAME'), $incidence->reference, nl2br($incidenceMessage->description));

        $subject_value = str_replace($vars, $values, $subject_var);

        $content_var = $seller_email->content;
        $content_value = str_replace($vars, $values, $content_var);

        $template_vars = array(
            '{content}' => $content_value,
            '{shop_name}' => Configuration::get('PS_SHOP_NAME')
        );
        $customer = new Customer($this->context->cookie->id_customer);

        $to = $customer->email;
        $to_name = $customer->name;
        $from = Configuration::get('PS_SHOP_EMAIL');
        $from_name = Configuration::get('PS_SHOP_NAME');
                    
        $template = 'base';                    

        if (file_exists(dirname(__FILE__).'/../../mails/'.$iso.'/'.$template.'.txt') && file_exists(dirname(__FILE__).'/../../mails/'.$iso.'/'.$template.'.html')) {
                Mail::Send(
                    $seller->id_lang,
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
            
        Tools::redirect($this->context->link->getModuleLink('marketplace', 'contactseller', array('confirmation' => 1), true));
    }
    public function postProcess() {
        if (Tools::isSubmit('submitAddIncidence')) {
            $this->notifyForQuestion();
        }
        
        if (Tools::isSubmit('submitResponse')) {
            $this->notifyForResponse();
        }

        if (Tools::isSubmit('submitResponse')) {
            $this->notifyForResponse();
        }

        if (Tools::getValue('process') == 'QuestionOrder')
            $this->notifyForQuestionOrder();

        if (Tools::getValue('process') =='sendAdminEmail')
            $this->processSendAdminEmail();
        
        if(Tools::getValue('action') == 'SendRefundConfirm')
            $this->SendRefundConfirm();

        if(Tools::getValue('action') == 'SendRefundCashConfirm')
            $this->SendRefundCashConfirm();
        
    }
    
    public function processSendAdminEmail()
    {
        $context = Context::getContext();
        $email = Tools::getValue('email');
        $seller_name = Tools::getValue('sname');
        $sujet = Tools::getValue('sujet');
        $message = Tools::getValue('message');
        $id_customer = (int)$context->customer->id;
        
        if($this->SendContactSeller($seller_name,$id_customer,$email,$sujet,$message))
            die('1');
        else
            die('0');

        die('1');
    }

    public function SendContactSeller($seller_name,$id_customer,$email,$sujet,$message)
    {
        $context = Context::getContext();
        $id_shop = (int)$context->shop->id;
        $id_lang = (int)$context->language->id;
        $iso = Language::getIsoById($id_lang);

        
        $customer = new Customer($id_customer);     
        $customer_lastname = $customer->lastname;
        $customer_firstname = $customer->firstname;

        
        $template_vars = array(
                '{seller_name}' => $seller_name,
                '{lastname}'    => $customer_lastname,
                '{firstname}'   => $customer_firstname,
                '{sujet}'       => $sujet,
                '{message}'     => $message         
            );

            $cm = new CustomerMessage();
                    if (!$id_customer_thread) {
                        $ct = new CustomerThread();
                        $ct->id_contact = 0;
                        $ct->id_customer = (int)$id_customer;
                        $ct->id_shop = (int)$this->context->shop->id;                        
                        $ct->id_order = 0;
                        $ct->id_lang = (int)$this->context->language->id;
                        $ct->email = $this->context->customer->email;
                        $ct->status = 'open';
                        $ct->token = Tools::passwdGen(12);
                        $ct->add();
                    } else {
                        $ct = new CustomerThread((int)$id_customer_thread);
                        $ct->status = 'open';
                        $ct->update();
                    }

                    $cm->id_customer_thread = $ct->id;
                    $cm->message = $message;
                    $client_ip_address = Tools::getRemoteAddr();
                    $cm->ip_address = (int)ip2long($client_ip_address);
                    $cm->add();
            // Do not send mail if multiples product are created / imported.
            //if (file_exists(dirname(__FILE__).'/mails/'.$iso.'/contact_seller.txt') &&
            //    file_exists(dirname(__FILE__).'/mails/'.$iso.'/contact_seller.html'))   
            if (file_exists(dirname(__FILE__).'/../../mails/'.$iso.'/contact_seller.txt') && file_exists(dirname(__FILE__).'/../../mails/'.$iso.'/contact_seller.html'))
            {                

                Mail::Send(
                        $id_lang,
                        'contact_seller_admin',
                        sprintf(Mail::l('Le vendeur %s vous a envoyé un message', $id_lang), $seller_name),
                        $template_vars,
                        Configuration::get('MARKETPLACE_SEND_ADMIN'),
                        'Admin',
                        Configuration::get('PS_SHOP_EMAIL'),
                        Configuration::get('PS_SHOP_NAME'),
                        null,
                        null,
                        dirname(__FILE__).'/../../mails/',
                        false,
                        $id_shop
                    );
                

                return true;
            }

        return false;

    }

    public function initContent() {
        
        parent::initContent();        

        if (Tools::isSubmit('proceed')) 
            {
            $selected_messages = Tools::getValue('selected_messages');
            if(!empty($selected_messages)) {
                $messages_to_get = implode( ',', array_map( 'intval', array_unique( $selected_messages ) ) );
                
                foreach ($selected_messages as $messageid) {
                    $messages_incid = new SellerIncidence($messageid);
                    $messages = SellerIncidence::getMessages($messageid,false,(int)$this->context->cookie->id_customer);
                    foreach ($messages as $message) {            
                            $message_new = new SellerIncidenceMessage($message['id_seller_incidence_message']);                
                            $message_new->delete();
                        
                    }

                    $messages_incid->delete();

                }
                

            }

        }
        
        if (Tools::isSubmit('action'))
        {
            switch(Tools::getValue('action'))
            {
                case 'read_message':
                    $this->ajaxProcessReadMessage();
                    break;
                case 'read_message_customer':
                    $this->ajaxProcessReadMessageCustomer();
                    break;
                case 'delete_message_customer':
                    $this->ajaxProcessDeleteMessageCustomer();
                    break;
            }
        }
        
        if (!Configuration::get('MARKETPLACE_SHOW_CONTACT')) 
            Tools::redirect($this->context->link->getPageLink('my-account', true));
        
        if($this->context->cookie->id_customer) {
            $orders = Order::getCustomerOrders($this->context->customer->id);
           
            $num_orders = count($orders);
            $orders_products = false;

            if ($num_orders > 0) { 
                $orders_products = array();
                foreach ($orders as $o) {    
                    $order = new Order($o['id_order']);
                    $products = $order->getProducts();
                    foreach ($products as $p) {             
                        $orders_products[] = array(
                            'id_order_product' => $o['id_order'].'-'.$p['product_id'], 
                            'order_reference' => $order->reference,
                            'order_date_add' => $order->date_add,
                            'product_name' => $p['product_name'],
                        );
                    }
                }
            }

            $incidences = SellerIncidence::getIncidencesByCustomer((int)$this->context->cookie->id_customer);

            if ($incidences != false) {
                $counter = 0;
                foreach ($incidences as $i) {
                    $product = new Product((int)$i['id_product'], (int)$this->context->language->id, (int)$this->context->shop->id);
                    $incidences[$counter]['product_name'] = $product->name;
                    $incidences[$counter]['messages_not_readed'] = SellerIncidence::getNumMessagesNotReaded($i['id_seller_incidence'], false, (int)$this->context->cookie->id_customer);
                    $incidences[$counter]['messages_not_readed_cust'] = SellerIncidence::getNumMessagesNotReadedCust((int)$i['id_seller_incidence'], false, (int)$this->context->cookie->id_customer);
                    $messages = SellerIncidence::getMessages((int)$i['id_seller_incidence'],false,$this->context->cookie->id_customer);
                    $incidences[$counter] = array_merge($incidences[$counter], array('messages' => $messages));
                    $counter++;
                }
            }
            
            $url_contact_seller = $this->context->link->getModuleLink('marketplace', 'contactseller', array(), true);

            if (Tools::getValue('id_seller') && Tools::getValue('id_product')) {
                $id_product = (int)Tools::getValue('id_product');
                $id_seller = (int)Tools::getValue('id_seller');
            
                $params = array('id_seller' => $id_seller, 'id_product' => $id_product);		
                $url_contact_seller = $this->context->link->getModuleLink('marketplace', 'contactseller', $params, true);
                
                $product = new Product($id_product, false, (int)$this->context->language->id, (int)$this->context->shop->id);
                
                $this->context->smarty->assign(array(
                    'product' => $product,
                    'id_product' => $id_product, 
                    'id_seller' => $id_seller
                ));
            }
            
            $this->context->smarty->assign(array(
                'logged' => $this->context->customer->isLogged(true),
                'incidences' => $incidences, 
                'orders_products' => $orders_products,
                'num_orders' => $num_orders,
                'url_contact_seller' => $url_contact_seller,
                'token' => Configuration::get('MARKETPLACE_TOKEN'),
            ));
        }
        
        if (Tools::getValue('confirmation'))
            $this->context->smarty->assign(array('confirmation' => 1));
        
        //$this->setTemplate('contactseller.tpl');
        $this->setTemplate('module:marketplace/views/templates/front/contactseller.tpl');
    }


    public function displayAjaxSendReclam()
    {
        $id_order = Tools::getValue( 'id_order' );
        $motif_id = Tools::getValue( 'motif' );
        

        $order = new Order($id_order);

        $customer = new Customer((int)($order->id_customer));
        $seller = SellerOrder::getSellerByOrder($id_order,1);

        if(!is_array($seller))
            $seller = array('email' => Configuration::get('PS_SHOP_EMAIL') );

        $motif = array(
                "",
                "Achat effectué par erreur",
                "Meilleur prix trouvé ailleurs",
                "Performances ou qualité non adéquates",
                "Article incompatible ou inutile",
                "Article endommagé mais emballage intact",
                "L'article est arrivé trop tard",
                "Pièces ou accessoires manquants",
                "Mauvais article reçu",
                "Défectueux/Ne fonctionne pas",
                "Arrivé en plus de ce qui a été commandé",
                "Plus besoin de l'article",
                "Achat non autorisé",
                "Description erronée sur le site");

        $params['{lastname}'] = $customer->lastname;
        $params['{firstname}'] = $customer->firstname;
        $params['{id_order}'] = $order->id;
        $params['{order_name}'] = $order->getUniqReference();
        $params['{motif}'] = $motif[$motif_id];

        print Tools::jsonEncode( array(
            'motif' => $motif,
            'orderid' => $id_order,
            'seller_mail' => $seller['email'],
            'params' => $params,
            'msg' => 'Hello PS AJAX!!!',
            'success' => 1,
            ) );

        @Mail::Send(
            (int)$order->id_lang,
            'base',
            sprintf(Mail::l('Réclamation sur votre commande : #%d - %s', (int)$order->id_lang), $id_order, $order->reference),            
            $params,
            $seller['email'],
            $customer->firstname.' '.$customer->lastname,
            null,
            null,
            null,
            null,
            _PS_MAIL_DIR_,
            true,
            (int)$order->id_shop
        );

        $merchant_mails = explode("\n", Configuration::get('MA_MERCHANT_MAILS'));
        foreach ($merchant_mails as $merchant_mail)
        {

            @Mail::Send(
                (int)$order->id_lang,
                'base',
                sprintf(Mail::l('Réclamation sur commande : #%d - %s', (int)$order->id_lang), $id_order, $order->reference),            
                $params,
                $merchant_mail,//Configuration::get('PS_SHOP_EMAIL'),
                $customer->firstname.' '.$customer->lastname,
                null,
                null,
                null,
                null,
                _PS_MAIL_DIR_,
                true,
                (int)$order->id_shop
            );
        }

 
        // your action code ....
    }

    public function SendRefundConfirm()
    {
        $id_order = Tools::getValue( 'id_order' );
        $order = new Order($id_order);
        $iso = Language::getIsoById($order->id_lang);
        
        $customer = new Customer((int)($order->id_customer));
        $seller = SellerOrder::getSellerByOrder($id_order,1);

        $order_details = $order->getOrderDetailList();

        $amount = 0;
        $amount_order = $order->slip_amount;        
        $motif_cancel = 0;//$order->getSlipmotif();

        if(!$motif_cancel)
            $motif_cancel = 'Annulé par l\'utilisateur';

        $count_orderdet = count($order_details);

        $order_detail_list = array();
        $full_quantity_list = array();
        foreach ($order_details as $order_detail){
            $quantity = 0;
            $id_order_detail = $order_detail['id_order_detail'];

            $full_quantity_list[$id_order_detail] = 0;
            $order_detail_list[$id_order_detail] = array(
                'quantity' => $order_detail['product_quantity'],
                'id_order_detail' => (int)$id_order_detail
            );

            
                $order_detail_list[$id_order_detail]['amount'] = (float)$amount_order/$count_orderdet;
                $order_detail_list[$id_order_detail]['unit_price'] = $order_detail_list[$id_order_detail]['amount'] / $order_detail_list[$id_order_detail]['quantity'];
            
                $amount += $order_detail_list[$id_order_detail]['amount'];

            $product_det = '<td style="padding: 10px;border-right: 1px solid;">'
                        . $order_detail['product_quantity'] .'</td>'
                        . '<td style="padding: 10px;border-right: 1px solid;">'
                        . $order_detail['product_name'] . '</td>'
                        .'<td style="padding: 10px;border-right: 1px solid;">'
                        . $motif_cancel .'</td>';
                        
        }

        $choosen = false;
        $voucher = 0;

        if ($amount >= 0) 
        {
                        
            //if (!OrderSlip::create($order, $order_detail_list)) 
            //{
                //$this->errors[] = Tools::displayError('You cannot generate a partial credit slip.');
                //die('1');
                            
            //} else {
                Hook::exec('actionOrderSlipAdd', array('order' => $order, 'productList' => $order_detail_list, 'qtyList' => $full_quantity_list), null, false, true, false, $order->id_shop);
                $customer = new Customer((int)($order->id_customer));

                //update order confirmation credit slip
                $order->slip_confirmed = 1;
                $order->update();
                            
                            $cart_rule = new CartRule();
                            $gen_pass = strtoupper(Tools::passwdGen(8));
                            $cart_rule->id_customer = $order->id_customer;
                            $cart_rule->name = array(2=>"Avoir Commande n°".$order->id);
                            $cart_rule->description = "Avoir concernant la commande n°".$order->id;                            
                            $cart_rule->code = $gen_pass;
                            $cart_rule->date_from = date('Y-m-d H:i:s', time());
                            $cart_rule->date_to = date("Y-m-d",mktime(0, 0, 0, date("m")+3, date("d"),   date("Y")));
                            $cart_rule->reduction_amount = $order->slip_amount;

                            $cart_rule->minimum_amount_currency = $order->id_currency;
                            $cart_rule->reduction_currency = $order->id_currency;          

                            $cart_rule->active = 1;
                            $cart_rule->reduction_tax = 1;

                            //die('1');

                            /*if($seller['id_seller'] > 0)
                                $cart_rule->reduction_seller = $seller['id_seller'];
                            else
                                $cart_rule->reduction_seller =-1;*/

                            if (!$cart_rule->add()) {
                                die('1');
                            }                            

                            //$avoir = new Discount();
                            //$gen_pass = strtoupper(Tools::passwdGen(8));
                            //$avoir->name = array(1=>"Avoir Commande n°".$order->id);
                            //$avoir->description = "Avoir concernant la commande n°".$order->id;                            
                            //$avoir->code = $gen_pass;
                            //$avoir->id_customer = $order->id_customer;
                            //$avoir->date_from = date("Y-m-d H:i:s");
                            //$avoir->date_to = date("Y-m-d",mktime(0, 0, 0, date("m")+3, date("d"),   date("Y")));
                            //$avoir->reduction_amount = $order->slip_amount;
                            //$avoir->reduction_tax = 1;

                            /*if($seller['id_seller'] > 0)
                                $cart_rule->reduction_seller = $seller['id_seller'];
                            else
                                $cart_rule->reduction_seller =-1;

                            $avoir->add();*/

                            $params['{lastname}'] = $customer->lastname;
                            $params['{firstname}'] = $customer->firstname;
                            $params['{id_order}'] = $order->id;
                            $params['{order_name}'] = $order->getUniqReference();
                            $params['{amount_refund}'] = Tools::displayPrice($amount_order, $this->context->currency, false);
                            $params['{produits}'] = $product_det;
                            $params['{code}'] = $gen_pass;

                            @Mail::Send(
                                (int)$order->id_lang,
                                'credit_slip',
                                Mail::l('New credit slip regarding your order', (int)$order->id_lang),
                                $params,
                                $customer->email,
                                $customer->firstname.' '.$customer->lastname,
                                null,
                                null,
                                null,
                                null,
                                _PS_MAIL_DIR_,
                                true,
                                (int)$order->id_shop
                            );

                            if (file_exists(dirname(__FILE__).'/../../mails/'.$iso.'/refund_admin.txt') && file_exists(dirname(__FILE__).'/../../mails/'.$iso.'/refund_admin.html'))
                            {                            

                                @Mail::Send(
                                    (int)$order->id_lang,
                                    'refund_admin',
                                    Mail::l('Remboursement de la commande', (int)$order->id_lang),
                                    $params,
                                    Configuration::get('PS_SHOP_EMAIL'),
                                    Configuration::get('PS_SHOP_NAME'),
                                    null,
                                    null,
                                    null,
                                    null,
                                    dirname(__FILE__).'/../../mails/',
                                    true,
                                    (int)$order->id_shop
                                );
                            }

                            if(is_array($seller))
                            {
                                $params['{seller_name}'] = $seller['name'];
                                if (file_exists(dirname(__FILE__).'/../../mails/'.$iso.'/refund_seller.txt') && file_exists(dirname(__FILE__).'/../../mails/'.$iso.'/refund_seller.html'))
                                {
                                @Mail::Send(
                                    (int)$order->id_lang,
                                    'refund_seller',
                                    sprintf(Mail::l('Remboursement de la commande %s', $id_lang), $order->getUniqReference()),
                                    $params,
                                    $seller['email'],
                                    Configuration::get('PS_SHOP_NAME'),
                                    null,
                                    null,
                                    null,
                                    null,
                                    dirname(__FILE__).'/../../mails/',
                                    true,
                                    (int)$order->id_shop
                                );
                                }
                            }

            //}
        }
                        /*foreach ($order_detail_list as &$product) {
                            $order_detail = new OrderDetail((int)$product['id_order_detail']);
                            if (Configuration::get('PS_ADVANCED_STOCK_MANAGEMENT')) {
                                StockAvailable::synchronize($order_detail->product_id);
                            }
                        }*/

                        // Generate voucher
                        /*if (Tools::isSubmit('generateDiscountRefund') && !count($this->errors) && $amount > 0) {
                            $cart_rule = new CartRule();
                            $cart_rule->description = sprintf($this->l('Credit slip for order #%d'), $order->id);
                            $language_ids = Language::getIDs(false);
                            foreach ($language_ids as $id_lang) {
                                // Define a temporary name
                                $cart_rule->name[$id_lang] = sprintf('V0C%1$dO%2$d', $order->id_customer, $order->id);
                            }

                            // Define a temporary code
                            $cart_rule->code = sprintf('V0C%1$dO%2$d', $order->id_customer, $order->id);
                            $cart_rule->quantity = 1;
                            $cart_rule->quantity_per_user = 1;

                            // Specific to the customer
                            $cart_rule->id_customer = $order->id_customer;
                            $now = time();
                            $cart_rule->date_from = date('Y-m-d H:i:s', $now);
                            $cart_rule->date_to = date('Y-m-d H:i:s', strtotime('+1 year'));
                            $cart_rule->partial_use = 1;
                            $cart_rule->active = 1;

                            $cart_rule->reduction_amount = $amount;
                            $cart_rule->reduction_tax = true;
                            $cart_rule->minimum_amount_currency = $order->id_currency;
                            $cart_rule->reduction_currency = $order->id_currency;

                            if (!$cart_rule->add()) {
                                $this->errors[] = Tools::displayError('You cannot generate a voucher.');
                            } else {
                                // Update the voucher code and name
                                foreach ($language_ids as $id_lang) {
                                    $cart_rule->name[$id_lang] = sprintf('V%1$dC%2$dO%3$d', $cart_rule->id, $order->id_customer, $order->id);
                                }
                                $cart_rule->code = sprintf('V%1$dC%2$dO%3$d', $cart_rule->id, $order->id_customer, $order->id);

                                if (!$cart_rule->update()) {
                                    $this->errors[] = Tools::displayError('You cannot generate a voucher.');
                                } else {
                                    $currency = $this->context->currency;
                                    $customer = new Customer((int)($order->id_customer));
                                    $params['{lastname}'] = $customer->lastname;
                                    $params['{firstname}'] = $customer->firstname;
                                    $params['{id_order}'] = $order->id;
                                    $params['{order_name}'] = $order->getUniqReference();
                                    $params['{voucher_amount}'] = Tools::displayPrice($cart_rule->reduction_amount, $currency, false);
                                    $params['{voucher_num}'] = $cart_rule->code;
                                    @Mail::Send((int)$order->id_lang, 'voucher', sprintf(Mail::l('New voucher for your order #%s', (int)$order->id_lang), $order->reference),
                                        $params, $customer->email, $customer->firstname.' '.$customer->lastname, null, null, null,
                                        null, _PS_MAIL_DIR_, true, (int)$order->id_shop);
                                }
                            }
                        }
                    } else {
                        if (!empty($order_details)) {
                            $this->errors[] = Tools::displayError('Please enter a quantity to proceed with your refund.');
                        } else {
                            $this->errors[] = Tools::displayError('Please enter an amount to proceed with your refund.');
                        }
                    }*/

        die('1');

        
    }

    public function displayAjaxsetDeliveryConfirm()
    {
        $id_order = Tools::getValue( 'o' );
                

        $order = new Order($id_order);

        $customer = new Customer((int)($order->id_customer));
        //$seller = SellerOrder::getSellerByOrder($id_order,1);

        $order->delivery_confirmed = 1;
        $order->update();
        
        // Construct order detail table for the email
                                       
        $items_table = '';

                    $products = $order->getOrderDetailList();
                    $context = Context::getContext();

                    foreach ($products as $product) {

                        $prod = new Product((int)$product['product_id'], false, $context->language->id);

                        $price = Product::getPriceStatic((int)$product['product_id'], false, ($product['product_attribute_id'] ? (int)$product['product_attribute_id'] : null), 6, null, false, true, $product['product_quantity'], false, (int)$order->id_customer, (int)$order->id_cart, (int)$order->{Configuration::get('PS_TAX_ADDRESS_TYPE')});
                        $price_wt = Product::getPriceStatic((int)$product['product_id'], true, ($product['product_attribute_id'] ? (int)$product['product_attribute_id'] : null), 2, null, false, true, $product['product_quantity'], false, (int)$order->id_customer, (int)$order->id_cart, (int)$order->{Configuration::get('PS_TAX_ADDRESS_TYPE')});

                        $product_price = Product::getTaxCalculationMethod() == PS_TAX_EXC ? Tools::ps_round($price, 2) : $price_wt;

                        
                        $url = $context->link->getProductLink((int)$product['product_id']);
                        $image = Product::getCover((int)$prod->id);
                        $cover =  (int)$prod->id.'-'.(int)$image['id_image'];

                        $src_img = $context->link->getImageLink($prod->link_rewrite,$cover,'medium_default');

                        $items_table .=
                        '<table width="100%"><tr style="padding:9px;">
                            <td style="background:'.($key % 2 ? '#646B79' : '#0398FC').';vertical-align: middle;" align="center" width="50%">
                            <a href="'.$url.'" class="product_desc" style="text-transform: uppercase;font-size: 24px;color: white;font-weight: 500;text-decoration: none;vertical-align: middle;"><br />'.$prod->name.'</a><br><hr style="color: #fff;"><span style="font-size: 22px;color: #fff;">'.Tools::displayPrice($price, $this->context->currency, false).'</span>
                            </td>
                            <td align="center" style="padding:12px;background:white;">
                                <a href="'.$url.'" class="img_link"><img class="width:125px;height:125px;" src="'.
                                $src_img . '" alt="" /></a>
                            </td>

                        </tr></table><br>';


                        
                    } // end foreach ($products)

        $params['{lastname}'] = $customer->lastname;
        $params['{firstname}'] = $customer->firstname;
        $params['{id_order}'] = $order->id;
        $params['{order_name}'] = $order->getUniqReference();
        $params['{items}'] = $items_table;

        $merchant_mails = explode("\n", Configuration::get('MA_MERCHANT_MAILS'));
        foreach ($merchant_mails as $merchant_mail)
        {
            @Mail::Send(
                (int)$order->id_lang,
                'delivery_confirmed_admin',
                sprintf(Mail::l('Commande %s bien reçue par %s', (int)$order->id_lang),$order->getUniqReference(), $customer->lastname),            
                $params,
                $merchant_mail,
                Configuration::get('PS_SHOP_NAME'),
                null,
                null,
                null,
                null,
                _PS_MAIL_DIR_,
                true,
                (int)$order->id_shop
            );
        }

        
    }

    public function SendRefundCashConfirm()
    {
        $id_order = Tools::getValue( 'id_order' );

        $order = new Order($id_order);

        $iso = Language::getIsoById($order->id_lang);

        $customer = new Customer((int)($order->id_customer));
        $seller = SellerOrder::getSellerByOrder($id_order,1);

        $order_details = $order->getOrderDetailList();

        $amount = 0;
        $amount_order = $order->slip_amount;
        $motif_cancel = 0;//$order->getSlipmotif();

        if(!$motif_cancel)
            $motif_cancel = 'Annulé par l\'utilisateur';

        $count_orderdet = count($order_details);

        foreach ($order_details as $order_detail){            

            $product_det = '<td style="padding: 10px;border-right: 1px solid;">'
                        . $order_detail['product_quantity'] .'</td>'
                        . '<td style="padding: 10px;border-right: 1px solid;">'
                        . $order_detail['product_name'] . '</td>'
                        .'<td style="padding: 10px;border-right: 1px solid;">'
                        . $motif_cancel .'</td>';                        
        }

        //update order confirmation credit slip
        $order->slip_confirmed = 1;
        $order->update();

        $customer = new Customer((int)($order->id_customer));

        $params['{lastname}'] = $customer->lastname;
        $params['{firstname}'] = $customer->firstname;
        $params['{id_order}'] = $order->id;
        $params['{order_name}'] = $order->getUniqReference();
        $params['{amount_refund}'] = Tools::displayPrice($amount_order, $this->context->currency, false);
        $params['{produits}'] = $product_det;

        if (file_exists(dirname(__FILE__).'/../../mails/'.$iso.'/refund_cash.txt') && file_exists(dirname(__FILE__).'/../../mails/'.$iso.'/refund_cash.html'))
        {
            @Mail::Send(
                (int)$order->id_lang,
                'refund_cash',
                Mail::l('Remboursement de votre commande', (int)$order->id_lang),
                $params,
                $customer->email,
                $customer->firstname.' '.$customer->lastname,
                null,
                null,
                null,
                null,
                dirname(__FILE__).'/../../mails/',
                true,
                (int)$order->id_shop
            );
        }
        /*$merchant_mails = explode("\n", Configuration::get('MA_MERCHANT_MAILS'));
        foreach ($merchant_mails as $merchant_mail)
        {*/
        if (file_exists(dirname(__FILE__).'/../../mails/'.$iso.'/refund_cash_admin.txt') && file_exists(dirname(__FILE__).'/../../mails/'.$iso.'/refund_cash_admin.html'))
        {
            @Mail::Send(
                (int)$order->id_lang,
                'refund_cash_admin',
                Mail::l('Remboursement de la commande', (int)$order->id_lang),
                $params,
                Configuration::get('PS_SHOP_EMAIL'),
                Configuration::get('PS_SHOP_NAME'),
                null,
                null,
                null,
                null,
                dirname(__FILE__).'/../../mails/',
                true,
                (int)$order->id_shop
            );
        }

        if(is_array($seller))
        {
            $params['{seller_name}'] = $seller['name'];
            if (file_exists(dirname(__FILE__).'/../../mails/'.$iso.'/refund_seller.txt') && file_exists(dirname(__FILE__).'/../../mails/'.$iso.'/refund_seller.html'))
            {
                @Mail::Send(
                    (int)$order->id_lang,
                    'refund_seller',
                    sprintf(Mail::l('Remboursement de la commande %s', $id_lang), $order->getUniqReference()),
                    $params,
                    $seller['email'],
                    Configuration::get('PS_SHOP_NAME'),
                    null,
                    null,
                    null,
                    null,
                    dirname(__FILE__).'/../../mails/',
                    true,
                    (int)$order->id_shop
                );
            }
        }
        
        die('1');

        
    }
}