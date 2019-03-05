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

class marketplaceSellercommentsOrderModuleFrontController extends ModuleFrontController
{
    public $ssl = true;
    
    public function postProcess() {
        
        if (Tools::isSubmit('submitSellerComment')) {
            $title = (string)Tools::getValue('title');
            $contentv = (string)Tools::getValue('content');
            $customer_name = Tools::isSubmit('customer_name');
            $id_seller = (int)Tools::getValue('id_seller');
            $seller = new Seller($id_seller);
            $livraison = (int)Tools::getValue('livraison');
            $conforme = (int)Tools::getValue('conforme');
            $service = (int)Tools::getValue('service');
            $id_order = (int)Tools::getValue('id_order');

            $livraison_txt = array(" "," non ");
            $service_txt = array("Service rapide et courtois","Service lent","N'a pas été contacté");

            $id_guest = 0;
            $id_customer = $this->context->customer->id;
            if (!$id_customer) {
                $id_guest = $this->context->cookie->id_guest;
                $id_customer = 0;
            }
            
            /*if (!$title || !Validate::isGenericName($title))
                $this->errors[] = $this->module->l('Title is incorrect.');*/
            
            if (!$contentv || !Validate::isMessage($contentv))
                $this->errors[] = $this->module->l('Comment is incorrect.', 'sellercomments');
            
            /*$content = 'Article'.$livraison_txt[$livraison]. 'livré à la date estimée, ';
            $content .= 'Article'.$livraison_txt[$conforme]. 'conforme à la description faite par le vendeur, ';
            $content .= $service_txt[$service] .', ';*/

            $content = $contentv;

            if (!$id_customer && (!$customer_name || !$customer_name || !Validate::isGenericName($customer_name)))
                $this->errors[] = $this->module->l('Customer name is incorrect.', 'sellercomments');
            
            if (!$this->context->customer->id && !Configuration::get('MARKETPLACE_ALLOW_GUEST_COMMENT'))
                $this->errors[] = $this->module->l('You must be connected to send a comment', 'sellercomments');
            
            if (!count(Tools::getValue('criterion')))
                $this->errors[] = $this->module->l('You must give a rating.', 'sellercomments');

            if (!$id_seller)
                $this->errors[] = $this->module->l('Seller not found.', 'sellercomments');
            
            if (SellerComment::isAlreadyCommentOrder($id_seller, $id_customer, $id_guest,$id_order)) 
                $this->errors[] = $this->module->l('Your comment has been sent.', 'sellercomments');

            if (!count($this->errors))
            {
                $customer_comment = SellerComment::getByCustomerOrder($id_seller, $id_customer, true, $id_guest,$id_order);
                if (!$customer_comment)
                {

                    $comment = new SellerComment();
                    $comment->title = $title;
                    $comment->content = strip_tags($content);
                    $comment->id_seller = $id_seller;
                    $comment->id_order = $id_order;
                    $comment->id_customer = $id_customer;
                    $comment->id_guest = $id_guest;
                    $comment->customer_name = (string)Tools::getValue('customer_name');
                    if (!$comment->customer_name)
                        $comment->customer_name = pSQL($this->context->customer->firstname.' '.$this->context->customer->lastname);
                    $comment->grade = 0;
                    
                    if (Configuration::get('MARKETPLACE_MODERATE_COMMENTS') == 1)
                        $comment->validate = 0;
                    else
                        $comment->validate = 1;
                    
                    $comment->save();

                    $grade_sum = 0;
                    
                    foreach(Tools::getValue('criterion') as $id_seller_comment_criterion => $grade)
                    {
                        $grade_sum += $grade;
                        $seller_comment_criterion = new SellerCommentCriterion($id_seller_comment_criterion);
                        if ($seller_comment_criterion->id)
                            $seller_comment_criterion->addGrade($comment->id, $grade);
                    }

                    if (count(Tools::getValue('criterion')) >= 1)
                    {
                        $comment->grade = $grade_sum / count(Tools::getValue('criterion'));
                        $comment->save();
                    }
                    
                    if (Configuration::get('MARKETPLACE_MODERATE_COMMENTS') == 1 && Configuration::get('MARKETPLACE_SEND_COMMENT_ADMIN') == 1) {
                        $id_seller_email = false;
                        $to = Configuration::get('MARKETPLACE_SEND_ADMIN');
                        $to_name = Configuration::get('PS_SHOP_NAME');
                        $from = Configuration::get('PS_SHOP_EMAIL');
                        $from_name = Configuration::get('PS_SHOP_NAME');
                        $template = 'base';
                        $reference = 'new-comment-admin';
                        $id_seller_email = SellerEmail::getIdByReference($reference);

                        if ($id_seller_email) {
                            $seller_email = new SellerEmail($id_seller_email, Configuration::get('PS_LANG_DEFAULT'));
                            $vars = array("{shop_name}", "{seller_name}", "{grade}", "{comment}");
                            $values = array(Configuration::get('PS_SHOP_NAME'), $seller->name, ceil($comment->grade), nl2br($comment->content));
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
                    
                    if (Configuration::get('MARKETPLACE_MODERATE_COMMENTS') == 0 && Configuration::get('MARKETPLACE_SEND_COMMENT_SELLER') == 1) {
                        $seller = new Seller($id_seller);
                        $id_seller_email = false;
                        $to = $seller->email;
                        $to_name = $seller->name;
                        $from = Configuration::get('PS_SHOP_EMAIL');
                        $from_name = Configuration::get('PS_SHOP_NAME');
                        $template = 'base';
                        $reference = 'new-comment-seller';
                        $id_seller_email = SellerEmail::getIdByReference($reference);

                        if ($id_seller_email) {
                            $seller_email = new SellerEmail($id_seller_email, $seller->id_lang);
                            $vars = array("{shop_name}", "{grade}", "{comment}");
                            $values = array(Configuration::get('PS_SHOP_NAME'), ceil($comment->grade), nl2br($comment->content));
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
                    
                    $this->context->smarty->assign('confirmation', 1);
                }
            }
            else {
                $this->context->smarty->assign('errors', $this->errors);
            }
        }
    }

    public function initContent() {
        
        parent::initContent();
        
        if(!Tools::getValue('id_order'))
            Tools::redirect($this->context->link->getPageLink('my-account', true));
        
        $id_order = Tools::getValue('id_order');
        
        /*$id_seller = Tools::getValue('id_seller');
        $seller = new Seller($id_seller);*/
        
        /*if (!Configuration::get('MARKETPLACE_SELLER_RATING')) 
            Tools::redirect($this->context->link->getPageLink('my-account', true));

        $param = array('id_seller' => $seller->id, 'link_rewrite' => $seller->link_rewrite);	
        $param2 = array('id_seller' => $seller->id);
        $url_seller_profile = $this->module->getmarketplaceLink('marketplace_seller_rule', $param);
        $url_seller_comments = $this->context->link->getModuleLink('marketplace', 'sellercomments', $param2, true);
        
        $seller_comments = SellerComment::getBySeller($id_seller);
        
         if (file_exists(_PS_IMG_DIR_.'sellers/'.$seller->id_customer.'.jpg'))
            $this->context->smarty->assign(array('photo' => _PS_BASE_URL_.__PS_BASE_URI__.'img/sellers/'.$seller->id_customer.'.jpg'));
        else
            $this->context->smarty->assign(array('photo' =>  _PS_BASE_URL_.__PS_BASE_URI__.'modules/marketplace/views/img/profile.jpg'));
        
        if (Tools::getValue('confirmation'))
            $this->context->smarty->assign(array('confirmation' => 1));
        
        //d(SellerComment::getGradeBySeller($id_seller, $this->context->language->id));
        //d(SellerComment::getRatings($id_seller));
        //d(SellerCommentCriterion::getCriterions($this->context->language->id, true));
        
        $resum_grade = array();
        $seller_comment_criterions = SellerCommentCriterion::getCriterions($this->context->language->id, true);
        if (is_array($seller_comment_criterions)) {
            foreach ($seller_comment_criterions as $scc) {
                $grades = SellerComment::getGradeBySeller($id_seller, $this->context->language->id);
                if (is_array($grades)) {
                    $grade_criterion = 0;
                    foreach ($grades as $gc) {
                        if ($scc['id_seller_comment_criterion'] == $gc['id_seller_comment_criterion']) {
                            $grade_criterion = $grade_criterion + $gc['grade'];
                        }
                    }
                    
                    $number_comments = SellerComment::getCommentNumber($id_seller);
                    if ($number_comments > 0)
                        $grade = ceil($grade_criterion / $number_comments);
                    else
                        $grade = 0;
                    
                    $resum_grade[$scc['id_seller_comment_criterion']] = array(
                        'name' => $scc['name'],
                        'grade' => $grade,
                    );
                }
            }
        }*/

            $order = new Order($id_order);
            if (Validate::isLoadedObject($order) && $order->id_customer == $this->context->customer->id) {
                $id_order_state = (int)$order->getCurrentState();
                $carrier = new Carrier((int)$order->id_carrier, (int)$order->id_lang);
                $addressInvoice = new Address((int)$order->id_address_invoice);
                $addressDelivery = new Address((int)$order->id_address_delivery);

                $inv_adr_fields = AddressFormat::getOrderedAddressFields($addressInvoice->id_country);
                $dlv_adr_fields = AddressFormat::getOrderedAddressFields($addressDelivery->id_country);

                $invoiceAddressFormatedValues = AddressFormat::getFormattedAddressFieldsValues($addressInvoice, $inv_adr_fields);
                $deliveryAddressFormatedValues = AddressFormat::getFormattedAddressFieldsValues($addressDelivery, $dlv_adr_fields);

                if ($order->total_discounts > 0) {
                    $this->context->smarty->assign('total_old', (float)$order->total_paid - $order->total_discounts);
                }
                $products = $order->getProducts();

                /* DEPRECATED: customizedDatas @since 1.5 */
                $customizedDatas = Product::getAllCustomizedDatas((int)$order->id_cart);
                Product::addCustomizationPrice($products, $customizedDatas);

                OrderReturn::addReturnedQuantity($products, $order->id);
                $order_status = new OrderState((int)$id_order_state, (int)$order->id_lang);

                $diff24Hours = new DateInterval(Configuration::get('DELAY_DELIVERY'));
                $diff90days = new DateInterval(Configuration::get('DELAY_RECLAM'));

                $d1 = new DateTime($order->date_add);
                $d3 = new DateTime($order->date_add);
                $d24h = new DateTime($order->date_add);
                $d2 = new DateTime();
                $d2->setTimezone(new DateTimeZone('Europe/Paris'));
                $d1->setTimezone(new DateTimeZone('Europe/Paris'));
                $d3->setTimezone(new DateTimeZone('Europe/Paris'));
                $d24h->setTimezone(new DateTimeZone('Europe/Paris'));
                

                /*$d2->format('Y-m-d H:i:s');
                $d1->format('Y-m-d H:i:s');
                $d3->format('Y-m-d H:i:s');*/

                $d1->add($diff24Hours);
                $d3->add($diff90days);
                $d24h->add(new DateInterval('PT24H'));

                $hours_exp = round(($d1->getTimestamp() - $d2->getTimestamp()) / 3600);
                $hours_reclam = round(($d3->getTimestamp() - $d2->getTimestamp()) / 3600);

                
                $customer = new Customer($order->id_customer);
                $seller_comment_criterions = SellerCommentCriterion::getCriterions($this->context->language->id, true);

                $seller_comments = SellerComment::getByOrder($order->id);

                $this->context->smarty->assign(array(
                    'logged' => $this->context->customer->isLogged(true),
                    'shop_name' => strval(Configuration::get('PS_SHOP_NAME')),
                    'order' => $order,
                    'return_allowed' => (int)$order->isReturnable(),
                    'currency' => new Currency($order->id_currency),
                    'order_state' => (int)$id_order_state,
                    'invoiceAllowed' => (int)Configuration::get('PS_INVOICE'),
                    'invoice' => (OrderState::invoiceAvailable($id_order_state) && count($order->getInvoicesCollection())),
                    'logable' => (bool)$order_status->logable,
                    'order_history' => $order->getHistory($this->context->language->id, false, true),
                    'products' => $products,
                    'discounts' => $order->getCartRules(),
                    'carrier' => $carrier,
                    'address_invoice' => $addressInvoice,
                    'invoiceState' => (Validate::isLoadedObject($addressInvoice) && $addressInvoice->id_state) ? new State($addressInvoice->id_state) : false,
                    'address_delivery' => $addressDelivery,
                    'inv_adr_fields' => $inv_adr_fields,
                    'dlv_adr_fields' => $dlv_adr_fields,
                    'invoiceAddressFormatedValues' => $invoiceAddressFormatedValues,
                    'deliveryAddressFormatedValues' => $deliveryAddressFormatedValues,
                    'deliveryState' => (Validate::isLoadedObject($addressDelivery) && $addressDelivery->id_state) ? new State($addressDelivery->id_state) : false,
                    'is_guest' => false,
                    'messages' => CustomerMessage::getMessagesByOrderId((int)$order->id, false),
                    'CUSTOMIZE_FILE' => Product::CUSTOMIZE_FILE,
                    'CUSTOMIZE_TEXTFIELD' => Product::CUSTOMIZE_TEXTFIELD,
                    'isRecyclable' => Configuration::get('PS_RECYCLABLE_PACK'),
                    'use_tax' => Configuration::get('PS_TAX'),
                    'group_use_tax' => (Group::getPriceDisplayMethod($customer->id_default_group) == PS_TAX_INC),
                    /* DEPRECATED: customizedDatas @since 1.5 */
                    'customizedDatas' => $customizedDatas,
                    /* DEPRECATED: customizedDatas @since 1.5 */
                    'reorderingAllowed' => !(bool)Configuration::get('PS_DISALLOW_HISTORY_REORDERING'),
                    'delay_delivery' => $hours_exp,
                    'hours_reclam' => $hours_reclam,
                    'date_reclam' =>  $d3->format('d M Y'),
                    'date_delivery' => $d1->format('d M Y'),
                    'date_delivery1' => $d24h->format('d M Y'),
                    'criterions' => $seller_comment_criterions,
                    'allow_guests' => Configuration::get('MARKETPLACE_ALLOW_GUEST_COMMENT'),
                    'seller_comments' => $seller_comments,
                   
                ));

            }
            
                /*if ($carrier->url && $order->shipping_number) {
                    $this->context->smarty->assign('followup', str_replace('@', $order->shipping_number, $carrier->url));
                }*/

        
        /*$this->context->smarty->assign(array(
            'logged' => $this->context->customer->isLogged(true),
            'allow_guests' => Configuration::get('MARKETPLACE_ALLOW_GUEST_COMMENT'),
            'moderate' => Configuration::get('MARKETPLACE_MODERATE_COMMENTS'),
            'num_comments' => SellerComment::getCommentNumber($id_seller),
            'seller' => $seller,
            'seller_comments' => $seller_comments, 
            'seller_link' => $url_seller_profile,
            'url_seller_comments' => $url_seller_comments,
            'criterions' => $seller_comment_criterions,
            'resum_grade' => $resum_grade,
            'show_logo' => Configuration::get('MARKETPLACE_SHOW_LOGO'),
        ));*/

        $this->setTemplate('sellercommentsorder.tpl');
    }
}