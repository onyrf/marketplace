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

include_once dirname(__FILE__).'/../../classes/SellerIncidence.php';
include_once dirname(__FILE__).'/../../classes/SellerIncidenceMessage.php';

class AdminSellerIncidencesController extends ModuleAdminController
{
    //public $asso_type = 'shop';

    public function __construct()
    {
        $this->bootstrap = true;
        $this->table = 'seller_incidence';
        $this->className = 'SellerIncidence';
        $this->lang = false;
        $this->states_array = array();
        $this->types_array = array();
        $this->priorities_array = array();
        
        $this->context = Context::getContext();

        parent::__construct();
    }

    public function initPageHeaderToolbar()
    {
        parent::initPageHeaderToolbar();
    }

    public function renderList()
    {
        $this->addRowAction('view');
        //$this->addRowAction('edit');
        $this->addRowAction('delete');
        
        $this->_select = 'a.reference as incidence_ref, o.reference as order_ref, c.firstname, c.lastname, a.date_add, IFNULL(s.`name`,"MD") as seller_name';
        $this->_join = 'LEFT JOIN `'._DB_PREFIX_.'customer` c ON (c.`id_customer` = a.`id_customer`)
                        LEFT JOIN `'._DB_PREFIX_.'orders` o ON (o.`id_order` = a.`id_order`)
                        LEFT JOIN `'._DB_PREFIX_.'seller` s ON ( a.`id_seller` = s.`id_seller`)';
        $this->_where = 'AND a.id_shop = '.(int)$this->context->shop->id;
        
        if (Tools::isSubmit('submitFilter')) {            
            if (Tools::getValue('seller_incidenceFilter_incidence_ref') != '')
                $this->_where = 'AND a.reference LIKE "%'.pSQL(Tools::getValue('seller_incidenceFilter_incidence_ref')).'%"';
            
            if (Tools::getValue('seller_incidenceFilter_order_ref') != '')
                $this->_where = 'AND o.reference LIKE "%'.pSQL(Tools::getValue('seller_incidenceFilter_order_ref')).'%"';
            
            if (Tools::getValue('seller_incidenceFilter_firstname') != '')
                $this->_where = 'AND c.firstname LIKE "%'.pSQL(Tools::getValue('seller_incidenceFilter_firstname')).'%"';
            
            if (Tools::getValue('seller_incidenceFilter_lastname') != '')
                $this->_where = 'AND c.lastname LIKE "%'.pSQL(Tools::getValue('seller_incidenceFilter_lastname')).'%"';
            
            if (Tools::getValue('seller_incidenceFilter_id_incidence_state'))
                $this->_where = 'AND ist.id_incidence_state = '.(int)Tools::getValue('seller_incidenceFilter_id_incidence_state');
            
            if (Tools::getValue('seller_incidenceFilter_id_incidence_type'))
                $this->_where = 'AND it.id_incidence_type = '.(int)Tools::getValue('seller_incidenceFilter_id_incidence_type');
            
            if (Tools::getValue('seller_incidenceFilter_id_incidence_priority'))
                $this->_where = 'AND ip.id_incidence_priority = '.(int)Tools::getValue('seller_incidenceFilter_id_incidence_priority');           
        }
        
        $this->_orderBy = 'date_upd';
        $this->_orderWay = 'DESC';
        
        if (Tools::getValue('seller_incidenceOrderby')) {  
            $this->_orderBy = pSQL(Tools::getValue('seller_incidenceOrderby'));
            $this->_orderWay = pSQL(Tools::getValue('seller_incidenceOrderway'));
        }

        $this->fields_list = array(

            'seller_name' => array(
                'title' => $this->l('Seller'),
                'color' => 'red',
                'havingFilter' => false,                
            ),
            'incidence_ref' => array(
                'title' => $this->l('Incidence reference'),
                'havingFilter' => true,
            ),
            'order_ref' => array(
                'title' => $this->l('Order reference'),
                'havingFilter' => true,
            ),
            'active' => array(
                'title' => $this->l('Enabled'),
                'align' => 'center',
                'active' => 'status',
                'type' => 'bool',
                'class' => 'fixed-width-sm',
                'filter_key' => 'a!active',
                'orderby' => true,
                'search' => true,
                'havingFilter' => true,
            ),            
            'firstname' => array(
                'title' => $this->l('Customer name'),
                'havingFilter' => true,
            ),
            'lastname' => array(
                'title' => $this->l('Customer lastname'),
                'havingFilter' => true,
            ),
            'date_add' => array(
                'title' => $this->l('Date add'),
            ),
            'date_upd' => array(
                'title' => $this->l('Date update'),
            )
        );
        
        if( !Configuration::get('MARKETPLACE_MODERATE_MESSAGE') ){
            unset($this->fields_list['active']);
        }
        $this->bulk_actions = array(
                'delete' => array(
                        'text' => $this->l('Delete selected'),
                        'confirm' => $this->l('Delete selected items?'),
                        'icon' => 'icon-trash'
                )
        );

        return parent::renderList();
    }
    
    public function postProcess() {
        
        if($this->display == 'view') {
            
            $incidence = new SellerIncidence((int)Tools::getValue('id_seller_incidence'));
            $seller_name = $incidence->getSellerName();

            $messages = SellerIncidence::getMessages($incidence->id,true);

            foreach ($messages as $message) 
            {
                if ($message['id_seller'] == 0) {
                    $message = new SellerIncidenceMessage($message['id_seller_incidence_message']);
                    $message->readed = 1;
                    $message->update();
                }
            }

            $this->context->smarty->assign(array(
                'incidence' => $incidence, 
                'messages' => $messages, 
                'url_post' => self::$currentIndex.'&id_incidence='.$incidence->id.'&viewincidence&token='.$this->token,
                'seller_name' => $seller_name['name'],
                /*'url_cancel' => self::$currentIndex.'&viewincidence&token='.$this->token,*/
                'token' => $this->token,
            ));
        }

        if (Tools::isSubmit('submitResponseMD')) 
        {    
            $id_seller = 0;  
            $incidenceMessage = new SellerIncidenceMessage();
            $incidenceMessage->id_seller_incidence = (int)Tools::getValue('id_incidence');
            $incidenceMessage->id_customer = 0;
            $incidenceMessage->id_seller = (int)$id_seller;
            $incidenceMessage->description = (string)Tools::getValue('description'); //this is content html
            $incidenceMessage->readed = 0;
            $incidenceMessage->add();
            
            $incidence = new SellerIncidence($incidenceMessage->id_seller_incidence);
            
            $id_seller_email = false;
            $template = 'base';
            $reference = 'new-response-seller';
                        
            $id_seller_email = SellerEmail::getIdByReference($reference);

            if ($id_seller_email) {
                //$id_customer = (int)Tools::getValue('id_customer');
                //$customer = new Customer($id_customer);
                
                $to = 'onyrf82@gmail.com';//Configuration::get('PS_SHOP_EMAIL');
                $to_name = Configuration::get('PS_SHOP_NAME');
                $from = Configuration::get('PS_SHOP_EMAIL');
                $from_name = Configuration::get('PS_SHOP_NAME');
                
                                
                $seller_email = new SellerEmail($id_seller_email, $customer->id_lang);
                $vars = array("{shop_name}", "{incidence_reference}", "{description}");
                $values = array(Configuration::get('PS_SHOP_NAME'), $incidence->reference, nl2br($incidenceMessage->description));
                $subject_var = $seller_email->subject; 
                $subject_value = str_replace($vars, $values, $subject_var);
                $content_var = $seller_email->content;
                $content_value = str_replace($vars, $values, $content_var);

                $template_vars = array(
                    '{content}' => $content_value,
                    '{shop_name}' => Configuration::get('PS_SHOP_NAME')
                );

                $iso = Language::getIsoById(1);

                if (file_exists(dirname(__FILE__).'/../../mails/'.$iso.'/'.$template.'.txt') && file_exists(dirname(__FILE__).'/../../mails/'.$iso.'/'.$template.'.html')) {
                    Mail::Send(
                        1,
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

                Tools::redirectAdmin($this->context->link->getAdminLink('AdminSellerIncidences'). 
                '&id_seller_incidence=' . Tools::getValue('id_incidence') . 
                '&viewseller_incidence' .
                '&token='.$this->token);

            }  
            
        }

        /**
        * anthony.idbooster
        * activate message
        **/
        if (Tools::isSubmit('statusseller_incidence')) {
            $incidence = new SellerIncidence((int)Tools::getValue('id_seller_incidence'));
            if ($incidence->active == 1){
                $incidence->active = 0;  
            }else{
                $incidence->active = 1; 
            }
            $incidence->update(); 
            
            $incidenceMessages = SellerIncidence::getMessages($incidence->id,$bAdmin = true);
            foreach( $incidenceMessages as $incidenceMessageRow ){
                $message = new SellerIncidenceMessage($incidenceMessageRow['id_seller_incidence_message']);
                $message->active = $incidence->active; 
                $message->update();
            }
            $this->reportSellerIncidenceStatusChange($incidence);
        }
        
        /**
        * anthony.idbooster
        * activate response
        **/
        if ( Tools::isSubmit('submitResponse') && Configuration::get('MARKETPLACE_MODERATE_MESSAGE') ) {
            $incidenceMessage = new SellerIncidenceMessage((int)Tools::getValue('id_seller_incidence_message'));
            if( $incidenceMessage->id ){
                if( $incidenceMessage->active == 0 ){
                    $incidenceMessage->active = 1;
                    if( $incidenceMessage->id_seller == 0 ){
                        $this->notifyForResponseSeller($incidenceMessage);
                    }elseif( $incidenceMessage->id_customer == 0 ){
                        $this->notifyForResponseCustomer($incidenceMessage);    
                    }
                }else{
                    $incidenceMessage->active = 0;
                }
                $incidenceMessage->update();
            }
            
            Tools::redirectAdmin($this->context->link->getAdminLink('AdminSellerIncidences'). 
                '&id_seller_incidence=' . Tools::getValue('id_seller_incidence') . 
                '&viewseller_incidence' .
                '&token='.$this->token
            );
        }
        
            
        if (Tools::isSubmit('deleteseller_incidence')) {
            $id_seller_incidence = (int)Tools::getValue('id_seller_incidence');
            $seller_incidence = new SellerIncidence($id_seller_incidence);
            $seller_incidence->delete();
        }
        
        /*if (Tools::isSubmit('submitAddincidence')) {
            $incidence = new SellerIncidence();
            $incidence->reference = SellerIncidence::generateReference();
            $incidence->id_order = (int)Tools::getValue('id_order');
            
            $order = new Order($incidence->id_order);
            $customer = $order->getCustomer();
            
            $incidence->id_customer = (int)$customer->id;
            $incidence->id_state = (int)Tools::getValue('id_incidence_state');
            $incidence->id_type = (int)Tools::getValue('id_incidence_type');
            $incidence->id_priority = (int)Tools::getValue('id_incidence_priority');
            $incidence->id_shop = (int)$this->context->shop->id;
            $incidence->add();
            
            $incidenceMessage = new SellerIncidenceMessage();
            $incidenceMessage->id_incidence = (int)$incidence->id;
            $incidenceMessage->id_customer = (int)$customer->id;
            $incidenceMessage->id_employee = 0;
            $incidenceMessage->description = pSQL(Tools::getValue('description'));
            $incidenceMessage->readed = 0;
            $incidenceMessage->add();
            //mail nueva incidencia
            //parent::postProcess();   
        }*/
    }
    
    public function notifyForResponseCustomer($incidenceMessage){
        $id_seller = Seller::getSellerByCustomer($incidence->id_seller);  
        $incidence = new SellerIncidence($incidenceMessage->id_seller_incidence);
        
        $id_seller_email = false;
        $template = 'base';
        $reference = 'new-response-seller';
        
        $id_seller_email = SellerEmail::getIdByReference($reference);
        if ($id_seller_email) {
            $customer = new Customer($incidence->id_customer);
            
            $to = $customer->email;
            //$to = Configuration::get('MARKETPLACE_SEND_ADMIN');
            
            $to_name = $customer->firstname.' '.$customer->lastname;
            $from = Configuration::get('PS_SHOP_EMAIL');
            $from_name = Configuration::get('PS_SHOP_NAME');
            
            $seller_email = new SellerEmail($id_seller_email, $customer->id_lang);
            $vars = array("{shop_name}", "{incidence_reference}", "{description}");
            $values = array(Configuration::get('PS_SHOP_NAME'), $incidence->reference, nl2br($incidenceMessage->description));
            $subject_var = $seller_email->subject; 
            $subject_value = str_replace($vars, $values, $subject_var);
            $content_var = $seller_email->content;
            $content_value = str_replace($vars, $values, $content_var);

            $template_vars = array(
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
            }else{
                d("error");
            }
        }  
    }
    
    public function notifyForResponseSeller($incidenceMessage){
        $incidence = new SellerIncidence($incidenceMessage->id_seller_incidence);
        
        $id_seller_email = false;
        $template = 'base';
        $reference = 'new-response-customer';
        
        $id_seller_email = SellerEmail::getIdByReference($reference);
        if ($id_seller_email) {
            $seller = new Seller($incidence->id_seller);

            $to = $seller->email;
            $to_name = $seller->name;
            $from = Configuration::get('MARKETPLACE_SEND_ADMIN');
            $from_name = Configuration::get('PS_SHOP_NAME');
            
            $seller_email = new SellerEmail($id_seller_email, $seller->id_lang);
            $vars = array("{shop_name}", "{incidence_reference}", "{description}");
            $values = array(Configuration::get('PS_SHOP_NAME'), $incidence->reference, nl2br($incidenceMessage->description));
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
            }else{
                d("error");
            }
        }
    }

    public function reportSellerIncidenceStatusChange($incidence) {
        if (Configuration::get('MARKETPLACE_SEND_MESSAGE_ACTIVE')) {   
            if (Configuration::get('MARKETPLACE_SEND_SELLER_ACTIVE') && $incidence->active == 1 ) {
                $seller = new Seller($incidence->id_seller);
                
                $id_seller_email = false;
                $to = $seller->email;
                $to_name = $seller->name;
                $from = Configuration::get('MARKETPLACE_SEND_ADMIN');
                $from_name = Configuration::get('PS_SHOP_NAME');
                $template = 'base';
                
                if ($incidence->id_order == 0) {   
                    $reference = 'new-message';
                    if ($incidence->id_product != 0) {
                        $product = new Product($incidence->id_product, false, $seller->id_lang);
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
                    $vars = array("{shop_name}", "{order_reference}", "{incidence_reference}", "{description}");
                    $values = array(Configuration::get('PS_SHOP_NAME'), $order->reference, $incidence->reference, nl2br($incidenceMessage->description));
                }
                
                $id_seller_email = SellerEmail::getIdByReference($reference);
                
                if ($id_seller_email) {
                    $seller_email = new SellerEmail($id_seller_email, $seller->id_lang);
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
            }
        }
    }
}