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

class marketplaceSellermessagesModuleFrontController extends ModuleFrontController
{
    public $ssl = true;
    
    protected function ajaxProcessReadMessage()
    {
        $id_seller_incidence = (int)Tools::getValue('id_seller_incidence');
        $id_seller = Seller::getSellerByCustomer($this->context->cookie->id_customer);  
        $messages = SellerIncidence::getMessages($id_seller_incidence,false,0,$id_seller);
        foreach ($messages as $message) {
            if ($message['id_seller'] == 0) {
                $message = new SellerIncidenceMessage($message['id_seller_incidence_message']);
                $message->readed = 1;
                $message->update();
            }
        }  
    }
    
    protected function ajaxProcessDeleteMessageCustomer()
    {
        $id_seller_incidence = (int)Tools::getValue('id_seller_incidence');
        $messages_incid = new SellerIncidence($id_seller_incidence);

        $messages = SellerIncidence::getMessages($id_seller_incidence,false,(int)$this->context->cookie->id_customer);
        foreach ($messages as $message) {            
                $message_new = new SellerIncidenceMessage($message['id_seller_incidence_message']);                
                $message_new->delete();
            
        }

        $messages_incid->delete();

        return true;
    }

    public function postProcess() {
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

        if (Tools::isSubmit('submitResponse')) {    
            $id_seller = Seller::getSellerByCustomer($this->context->cookie->id_customer);  
            $incidenceMessage = new SellerIncidenceMessage();
            $incidenceMessage->id_seller_incidence = (int)Tools::getValue('id_incidence');
            $incidenceMessage->id_customer = (int)Tools::getValue('id_customer');
            $incidenceMessage->id_seller = (int)$id_seller;
            $incidenceMessage->description = (string)Tools::getValue('description'); //this is content html
            $incidenceMessage->readed = 0;
            $incidenceMessage->add();
            
            $incidence = new SellerIncidence($incidenceMessage->id_seller_incidence);
            
            $messages = SellerIncidence::getMessages($incidenceMessage->id_seller_incidence,false,0,(int)$id_seller,'DESC');

            $id_seller_email = false;
            $template = 'base';
            $reference = 'new-response-seller';
            if( Configuration::get('MARKETPLACE_MODERATE_MESSAGE') ){
                $reference = 'new-response-seller-validation';
            }
            
            $id_seller_email = SellerEmail::getIdByReference($reference);

            if ($id_seller_email) {
                $id_customer = (int)Tools::getValue('id_customer');
                $customer = new Customer($id_customer);
                
                $to = $customer->email;
                $to_name = $customer->firstname.' '.$customer->lastname;
                $from = Configuration::get('PS_SHOP_EMAIL');
                $from_name = Configuration::get('PS_SHOP_NAME');
                
                if( Configuration::get('MARKETPLACE_MODERATE_MESSAGE') ){
                    $to = Configuration::get('MARKETPLACE_SEND_ADMIN');
                    $to_name = "ADMIN";   
                }
                
                $table = '';
                foreach ($messages as $key => $message) {
                    if($message['seller_name'] == "")
                        $sender_msg = $message['customer_firstname'] .' '. $message['customer_lastname'];
                    else
                        $sender_msg = $message['seller_name'];

                    $table .= '<tr><td>Message envoyé par: <strong>'.' '.$sender_msg.'</strong> - du '.Tools::displayDate($message['date_add'],null , true) .'</td></tr>';

                    if($message['customer_firstname'] != '')
                    {
                        $table .= '<tr><td style="padding:10px;border:1px solid red;">'.$message['description'].'</td></tr><tr><td>&nbsp;</td></tr>';
                    }
                    else
                    {
                        $table .= '<tr><td style="padding:10px;border:1px solid green;">'.$message['description'].'</td></tr><tr><td>&nbsp;</td></tr>';
                    }

                    if($key == 0 && count($messages) > 1)
                        $table .= '<tr><td style="text-align:center"><hr> l\'historique de vos conversations<hr></td></tr>';
                }

                               

                $seller_email = new SellerEmail($id_seller_email, $customer->id_lang);
                $vars = array("{shop_name}", "{incidence_reference}", "{description}");
                $values = array(Configuration::get('PS_SHOP_NAME'), $incidence->reference, nl2br($table));
                $subject_var = $seller_email->subject; 
                $subject_value = str_replace($vars, $values, $subject_var);
                $content_var = $seller_email->content;
                $content_value = str_replace($vars, $values, $content_var);

                $template_vars = array(
                    '{incidence_reference}' => $incidence->reference,
                    '{content}' => $content_value,
                    '{shop_name}' => Configuration::get('PS_SHOP_NAME')
                );

                $iso = Language::getIsoById($customer->id_lang);

                if (file_exists(dirname(__FILE__).'/../../mails/'.$iso.'/'.$template.'.txt') && file_exists(dirname(__FILE__).'/../../mails/'.$iso.'/'.$template.'.html')) {
                    Mail::Send(
                        $customer->id_lang,
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
            
            Tools::redirect($this->context->link->getModuleLink('marketplace', 'sellermessages', array('confirmation' => 1), true));
        }

        
    }

    public function initContent() {
        
        parent::initContent();
        
        if (Tools::isSubmit('action'))
        {
            switch(Tools::getValue('action'))
            {
                case 'read_message':
                    $this->ajaxProcessReadMessage();
                    break;
                case 'delete_message_customer':
                    $this->ajaxProcessDeleteMessageCustomer();
                    break;
            }
        }
        
        if(!$this->context->cookie->id_customer)
            Tools::redirect($this->context->link->getPageLink('my-account', true));

        $id_seller = Seller::getSellerByCustomer($this->context->cookie->id_customer);
        $is_seller = Seller::isSeller($this->context->cookie->id_customer, $this->context->shop->id);
        
        if (!$is_seller)
            Tools::redirect($this->context->link->getPageLink('my-account', true));
        
        $seller = new Seller($id_seller);
        
        if ($seller->active == 0)
            Tools::redirect($this->context->link->getPageLink('my-account', true));
        
        if (!Configuration::get('MARKETPLACE_SHOW_CONTACT')) 
            Tools::redirect($this->context->link->getPageLink('my-account', true));

        $param = array('id_seller' => $seller->id, 'link_rewrite' => $seller->link_rewrite);				
        
        $url_seller_profile = $this->module->getmarketplaceLink('marketplace_seller_rule', $param);
        
        $incidences = SellerIncidence::getIncidencesBySeller($id_seller);

        if ($incidences != false) {
            $counter = 0;
            foreach ($incidences as $i) {
                $product = new Product((int)$i['id_product'], (int)$this->context->language->id, (int)$this->context->shop->id);
                $incidences[$counter]['product_name'] = $product->name;
                $incidences[$counter]['messages_not_readed'] = SellerIncidence::getNumMessagesNotReaded($i['id_seller_incidence'], $id_seller, false);
                  
                $messages = SellerIncidence::getMessages((int)$i['id_seller_incidence'],false,0,$id_seller);
                //$messages = SellerIncidence::getMessages((int)$i['id_seller_incidence']);
                $incidences[$counter] = array_merge($incidences[$counter], array('messages' => $messages));
                $counter++;
            }
        }
        
        $countneworder = SellerOrder::getVisitedOrdersSeller($id_seller,$this->context->language->id);
        
        if (Tools::getValue('confirmation'))
            $this->context->smarty->assign(array('confirmation' => 1));
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
            'incidences' => $incidences,
            'seller_link' => $url_seller_profile,
            'show_import_product' => Configuration::get('MARKETPLACE_SELLER_IMPORT_PROD'),
            'show_orders' => Configuration::get('MARKETPLACE_SHOW_ORDERS'),
            'show_manage_orders' => Configuration::get('MARKETPLACE_SHOW_MANAGE_ORDERS'),
            'show_manage_carriers' => Configuration::get('MARKETPLACE_SHOW_MANAGE_CARRIER'),
            'show_edit_seller_account' => Configuration::get('MARKETPLACE_SHOW_EDIT_ACCOUNT'),
            'show_contact' => Configuration::get('MARKETPLACE_SHOW_CONTACT'),
            'show_dashboard' => Configuration::get('MARKETPLACE_SHOW_DASHBOARD'),
            'show_menu_top' => Configuration::get('MARKETPLACE_MENU_TOP'),
            'show_menu_options' => Configuration::get('MARKETPLACE_MENU_OPTIONS'),
            'token' => Configuration::get('MARKETPLACE_TOKEN'),
            'mesages_not_readed' => SellerIncidenceMessage::getNumMessagesNotReadedBySeller($id_seller),
            'countneworder' => $countneworder,
            'seller' => $seller,
            'content_only' => 1,
            'menu' => 'module:marketplace/views/templates/front/sellermenu.tpl',
        ));

        //$this->setTemplate('sellermessages.tpl');
        $this->setTemplate('module:marketplace/views/templates/front/sellermessages.tpl');
    }
}