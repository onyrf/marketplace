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

class marketplaceOrdersModuleFrontController extends ModuleFrontController
{
    public $ssl = true;
    
    public function postProcess() {

        /* Update shipping number */
        if (Tools::isSubmit('submitShippingNumber')) {
                
                $order_carrier = new OrderCarrier(Tools::getValue('id_order_carrier'));
                $id_order = (int)Tools::getValue('id_order');
            
                $order = new Order($id_order);

                if (!Validate::isLoadedObject($order_carrier)) {
                    $this->errors[] = Tools::displayError('The order carrier ID is invalid.');
                } elseif (!Validate::isTrackingNumber(Tools::getValue('tracking_number'))) {
                    $this->errors[] = Tools::displayError('The tracking number is incorrect.');
                } else {
                    // update shipping number
                    // Keep these two following lines for backward compatibility, remove on 1.6 version
                    $order->shipping_number = Tools::getValue('tracking_number');
                    $order->update();

                    // Update order_carrier
                    $order_carrier->tracking_number = pSQL(Tools::getValue('tracking_number'));
                    if ($order_carrier->update()) {
                        // Send mail to customer
                        $from = Configuration::get('PS_SHOP_EMAIL');
                        $from_name = Configuration::get('PS_SHOP_NAME');

                        $customer = new Customer((int)$order->id_customer);
                        $carrier = new Carrier((int)$order->id_carrier, $order->id_lang);
                        if (!Validate::isLoadedObject($customer)) {
                            throw new PrestaShopException('Can\'t load Customer object');
                        }
                        if (!Validate::isLoadedObject($carrier)) {
                            throw new PrestaShopException('Can\'t load Carrier object');
                        }
                        /*if($carrier->url)
                        {
                            $templateVars = array(                            
                                '{followup}' => str_replace('@', $order->shipping_number, $carrier->url),
                                '{firstname}' => $customer->firstname,
                                '{lastname}' => $customer->lastname,
                                '{id_order}' => $order->id,
                                '{shipping_number}' => $order->shipping_number,
                                '{order_name}' => $order->getUniqReference()
                            );
                        }
                        else
                        {*/
                            $templateVars = array(                            
                                '{followup}' => $order->shipping_number,
                                '{firstname}' => $customer->firstname,
                                '{lastname}' => $customer->lastname,
                                '{id_order}' => $order->id,
                                '{shipping_number}' => $order->shipping_number,
                                '{order_name}' => $order->getUniqReference()
                            );
                        //}
                        @Mail::Send(
                                (int)$order->id_lang,
                                'in_transit',
                                Mail::l('Livraison en cours', (int)$order->id_lang),
                                $templateVars,
                                $customer->email, 
                                $customer->firstname.' '.$customer->lastname,
                                $from,
                                $from_name,
                                null,
                                null,
                                _PS_MAIL_DIR_,
                                true,
                                (int)$order->id_shop
                            );
                        /*{
                            $this->errors[] = Tools::displayError('Une erreur est survenue lors de l\'envoie d\'email au client.');
                        }*/

                        /*if (@Mail::Send((int)$order->id_lang, 'in_transit', Mail::l('Package in transit', (int)$order->id_lang), $templateVars,
                            $customer->email, $customer->firstname.' '.$customer->lastname, null, null, null, null,
                            _PS_MAIL_DIR_, true, (int)$order->id_shop)) {
                            Hook::exec('actionAdminOrdersTrackingNumberUpdate', array('order' => $order, 'customer' => $customer, 'carrier' => $carrier), null, false, true, false, $order->id_shop);
                            Tools::redirectAdmin(self::$currentIndex.'&id_order='.$order->id.'&vieworder&conf=4&token='.$this->token);
                        } else {
                            $this->errors[] = Tools::displayError('An error occurred while sending an email to the customer.');
                        }*/
                        //Tools::redirect($this->context->link->getModuleLink('marketplace', 'orders', $params, true));

                    } else {
                        $this->errors[] = Tools::displayError('The order carrier cannot be updated.');
                    }
                }
           
        }

        if (Tools::isSubmit('id_order') && Tools::getValue('id_order') > 0) {
            $order = new Order(Tools::getValue('id_order'));
            if (!Validate::isLoadedObject($order)) {
                $this->errors[] = Tools::displayError('The order cannot be found within your database.');
            }
            ShopUrl::cacheMainDomainForShop((int)$order->id_shop);
        }
        if(Tools::getValue('action') == 'refund_part')
        {
            $id_order = (int)Tools::getValue('id_order');
            $amount = (float)Tools::getValue('amount');
            $mode = Tools::getValue('mode');            
            $motif_id = Tools::getValue('motif');


            $order = new Order($id_order);
            
            if($mode == 'partial')
                $id_order_state = 16;
            elseif($mode == 'total')
                $id_order_state = 17;
            
            $motif = array(
                "",
                "Ne peut expédier la commande",
                "Retour client",
                "Ajustement général",
                "Livraison refusée",
                "L'acheteur a annulé la commande",
                "Article différent",
                "Retard de livraison du fait du transporteur",
                "Marchandise différente de sa description",
                "Ne peut être livré à cette adresse",
                "Pas de stock",
                "Marchandise non reçue",
                "Promesse de traitement non tenue",
                "Erreur de tarification");

            $order_state = new OrderState($id_order_state);

            if (!Validate::isLoadedObject($order_state)) {
                $this->errors[] = Tools::displayError('The new order status is invalid.');
            } 
            else {
                $current_order_state = $order->getCurrentOrderState();
                if ($current_order_state->id != $order_state->id) {
                    // Create new OrderHistory
                    $history = new OrderHistory();
                    $history->id_order = $order->id;
                    $history->id_employee = 1;

                    $use_existings_payment = false;
                    if (!$order->hasInvoice()) {
                        $use_existings_payment = true;
                    }
                    $history->changeIdOrderState((int)$order_state->id, $order, $use_existings_payment);
                    
                    //update history commissions
                    $states = OrderState::getOrderStates($this->context->language->id);
                    $cancel_commissions = false;
                    foreach ($states as $state) {
                        if (Configuration::get('MARKETPLACE_CANCEL_COMMISSION_'.$state['id_order_state']) == 1 && $id_order_state == $state['id_order_state'])
                            $cancel_commissions = true;
                    }
                    
                    //si toca cancelar comisiones
                    if ($cancel_commissions) 
                        SellerCommisionHistory::changeStateCommissionsByOrder($id_order, 'cancel');

                    $carrier = new Carrier($order->id_carrier, $order->id_lang);
                    $templateVars = array();
                    if ($history->id_order_state == Configuration::get('PS_OS_SHIPPING') && $order->shipping_number) {
                        $templateVars = array('{followup}' => str_replace('@', $order->shipping_number, $carrier->url));
                    }

                    // Save all changes
                    if ($history->addWithemail(true, $templateVars)) {
                        // synchronizes quantities if needed..
                        if (Configuration::get('PS_ADVANCED_STOCK_MANAGEMENT')) {
                            foreach ($order->getProducts() as $product) {
                                if (StockAvailable::dependsOnStock($product['product_id'])) {
                                    StockAvailable::synchronize($product['product_id'], $product['id_shop']);
                                }
                            }
                        }
                        // Update or refund amount attribute in Order
                        $order->slip_amount = $amount;
                        $order->slip_motif = $motif_id;
                        $order->update();

                        $customer = new Customer((int)($order->id_customer));
                            $params['{lastname}'] = $customer->lastname;
                            $params['{firstname}'] = $customer->firstname;
                            $params['{id_order}'] = $order->id;
                            $params['{order_name}'] = $order->getUniqReference();
                            $params['{amount}'] = Tools::displayPrice($amount, $this->context->currency, false);
                            $params['{history_url}'] = $this->context->link->getPageLink('history',true);
                            @Mail::Send(
                                (int)$order->id_lang,
                                'refund_cust',
                                Mail::l('Remboursement sur votre commande', (int)$order->id_lang),
                                $params,
                                $customer->email,
                                $customer->firstname.' '.$customer->lastname,
                                Configuration::get('PS_SHOP_EMAIL'),
                                Configuration::get('PS_SHOP_NAME'),
                                null,
                                null,
                                dirname(__FILE__).'/../../mails/',
                                true,
                                (int)$order->id_shop
                            );

                            $params['{lastname}'] = $customer->lastname;
                            $params['{firstname}'] = $customer->firstname;
                            $params['{id_order}'] = $order->id;
                            $params['{order_name}'] = $order->getUniqReference();
                            $params['{seller_name}'] = Seller::getSellerNameByOrder($order->id);
                            $params['{amount}'] = Tools::displayPrice($amount, $this->context->currency, false);
                            /*if($mode == 'partial')
                                $params['{motif}'] = "";
                            else*/
                            $params['{motif}'] = "suite à <span style=\"color:#333; font-size: 18px;\"><strong>\"".$motif[$motif_id]."\"</strong></span>" ;

                            $params['{link_admin}'] = $this->context->link->getAdminLink('index',false);

                            $to = Configuration::get('MARKETPLACE_SEND_ADMIN');
                            //$merchant_mails = explode("\n", Configuration::get('MA_MERCHANT_MAILS'));
                            //foreach ($merchant_mails as $merchant_mail)
                            //{

                                @Mail::Send(
                                    (int)$order->id_lang,
                                    'credit_slip_admin',
                                    Mail::l('Nouveau remboursement', (int)$order->id_lang),
                                    $params,
                                    $to, //Configuration::get('PS_SHOP_EMAIL') ,
                                    null,
                                    Configuration::get('PS_SHOP_EMAIL'),
                                    Configuration::get('PS_SHOP_NAME'),
                                    null,
                                    null,
                                    _PS_MAIL_DIR_,
                                    true,
                                    (int)$order->id_shop
                                );
                            //}
                    }
                }
            }

            die('1');

            
        }
        elseif(Tools::getValue('action') == 'cancel')
        {
            $id_order = (int)Tools::getValue('id_order');
            $motif_id = (int)Tools::getValue('motif');
            
            $motif = array(
                "",
                "Ne peut expédier la commande",
                "Retour client",
                "Ajustement général",
                "Livraison refusée",
                "L'acheteur a annulé la commande",
                "Article différent",
                "Retard de livraison du fait du transporteur",
                "Marchandise différente de sa description",
                "Ne peut être livré à cette adresse",
                "Pas de stock",
                "Marchandise non reçue",
                "Promesse de traitement non tenue",
                "Erreur de tarification");

            $id_order_state = 6;
            $order = new Order($id_order);
            $order_state = new OrderState($id_order_state);

            if (!Validate::isLoadedObject($order_state)) {
                $this->errors[] = Tools::displayError('The new order status is invalid.');
            } 
            else {
                $current_order_state = $order->getCurrentOrderState();
                if ($current_order_state->id != $order_state->id) {
                    // Create new OrderHistory
                    $history = new OrderHistory();
                    $history->id_order = $order->id;
                    $history->id_employee = 1;

                    $use_existings_payment = false;
                    if (!$order->hasInvoice()) {
                        $use_existings_payment = true;
                    }
                    $history->changeIdOrderState((int)$order_state->id, $order, $use_existings_payment);
                    
                    //update history commissions
                    $states = OrderState::getOrderStates($this->context->language->id);
                    $cancel_commissions = false;
                    foreach ($states as $state) {
                        if (Configuration::get('MARKETPLACE_CANCEL_COMMISSION_'.$state['id_order_state']) == 1 && $id_order_state == $state['id_order_state'])
                            $cancel_commissions = true;
                    }
                    
                    //si toca cancelar comisiones
                    if ($cancel_commissions) 
                        SellerCommisionHistory::changeStateCommissionsByOrder($id_order, 'cancel');

                    $carrier = new Carrier($order->id_carrier, $order->id_lang);
                    $templateVars = array();
                    if ($history->id_order_state == Configuration::get('PS_OS_SHIPPING') && $order->shipping_number) {
                        $templateVars = array('{followup}' => str_replace('@', $order->shipping_number, $carrier->url));
                    }

                    // Save all changes
                    if ($history->addWithemail(true, $templateVars)) {
                        // synchronizes quantities if needed..
                        if (Configuration::get('PS_ADVANCED_STOCK_MANAGEMENT')) {
                            foreach ($order->getProducts() as $product) {
                                if (StockAvailable::dependsOnStock($product['product_id'])) {
                                    StockAvailable::synchronize($product['product_id'], $product['id_shop']);
                                }
                            }
                        }
                            //SEND EMAIL TO ADMINISTRATOR
                            $customer = new Customer((int)($order->id_customer));
                            $params['{lastname}'] = $customer->lastname;
                            $params['{firstname}'] = $customer->firstname;
                            $params['{id_order}'] = $order->id;
                            $params['{order_name}'] = $order->getUniqReference();
                            $params['{seller_name}'] = Seller::getSellerNameByOrder($order->id);
                            $params['{motif}'] = "suite à <span style=\"color:#333; font-size: 15px;\"><strong>\"".$motif[$motif_id]."\"</strong></span>" ;

                            //$merchant_mails = explode("\n", Configuration::get('MA_MERCHANT_MAILS'));
                            //foreach ($merchant_mails as $merchant_mail)
                            //{

                                @Mail::Send(
                                    (int)$order->id_lang,
                                    'order_canceled_admin',
                                    Mail::l('Commande annulée', (int)$order->id_lang),
                                    $params,
                                    Configuration::get('MARKETPLACE_SEND_ADMIN'),
                                    null,
                                    Configuration::get('PS_SHOP_EMAIL'),
                                    Configuration::get('PS_SHOP_NAME'),
                                    null,
                                    null,
                                    _PS_MAIL_DIR_,
                                    true,
                                    (int)$order->id_shop
                                );
                            //}

                        $params = array('id_order' => $id_order);
                        Tools::redirect($this->context->link->getModuleLink('marketplace', 'orders', $params, true));
                    }
                }
            }
            die('1');
        }
        elseif(Tools::getValue('action') == 'AddShippingNumber')
        {
            $id_order = (int)Tools::getValue('id_order');
            $shipping_number = Tools::getValue('shipping_number');            
            $order = new Order($id_order);
            $order->shipping_number = $shipping_number;
            if($order->update())
                die('1');
            else
                die('0');

            
        }
        elseif (Tools::getValue('action') =='ShipWithShippingNumber') {
            $id_order = (int)Tools::getValue('id_order');
            $shipping_number = Tools::getValue('shipping_number');
            $id_order_state = 4;
            $order = new Order($id_order);
            $order_state = new OrderState($id_order_state);

            if($shipping_number != '' && $shipping_number != '0')
                $order->shipping_number = $shipping_number;

            if (!Validate::isLoadedObject($order_state)) {
                $this->errors[] = Tools::displayError('The new order status is invalid.');
            } 
            else {
                $current_order_state = $order->getCurrentOrderState();
                if ($current_order_state->id != $order_state->id) {
                    // Create new OrderHistory
                    $history = new OrderHistory();
                    $history->id_order = $order->id;
                    $history->id_employee = 1;

                    $use_existings_payment = false;
                    if (!$order->hasInvoice()) {
                        $use_existings_payment = true;
                    }
                    $history->changeIdOrderState((int)$order_state->id, $order, $use_existings_payment);
                    
                    //update history commissions
                    $states = OrderState::getOrderStates($this->context->language->id);
                    $cancel_commissions = false;
                    foreach ($states as $state) {
                        if (Configuration::get('MARKETPLACE_CANCEL_COMMISSION_'.$state['id_order_state']) == 1 && $id_order_state == $state['id_order_state'])
                            $cancel_commissions = true;
                    }
                    
                    //si toca cancelar comisiones
                    if ($cancel_commissions) 
                        SellerCommisionHistory::changeStateCommissionsByOrder($id_order, 'cancel');

                    $carrier = new Carrier($order->id_carrier, $order->id_lang);
                    $templateVars = array();
                    /*if ($history->id_order_state == Configuration::get('PS_OS_SHIPPING') && $order->shipping_number) {
                        $templateVars = array('{followup}' => str_replace('@', $order->shipping_number, $carrier->url));
                    }*/
                    if ($history->id_order_state == Configuration::get('PS_OS_SHIPPING') && $order->shipping_number) {
                        $templateVars = array('{followup}' => ', portant le numero de suivi: <strong>'.$order->shipping_number.'</strong>');
                    }

                    // Save all changes
                    if ($history->addWithemail(true, $templateVars)) {
                        // synchronizes quantities if needed..
                        if (Configuration::get('PS_ADVANCED_STOCK_MANAGEMENT')) {
                            foreach ($order->getProducts() as $product) {
                                if (StockAvailable::dependsOnStock($product['product_id'])) {
                                    StockAvailable::synchronize($product['product_id'], $product['id_shop']);
                                }
                            }
                        }
                        
                        $params = array('id_order' => $id_order);
                        Tools::redirect($this->context->link->getModuleLink('marketplace', 'orders', $params, true));
                    }
                }
            }
        }

        if (Tools::isSubmit('submitState')) {
            $id_order = (int)Tools::getValue('id_order');
            $id_order_state = (int)Tools::getValue('id_order_state');
            $order = new Order($id_order);
            $order_state = new OrderState($id_order_state);

            if (!Validate::isLoadedObject($order_state)) {
                $this->errors[] = Tools::displayError('The new order status is invalid.');
            } 
            else {
                $current_order_state = $order->getCurrentOrderState();
                if ($current_order_state->id != $order_state->id) {
                    // Create new OrderHistory
                    $history = new OrderHistory();
                    $history->id_order = $order->id;
                    $history->id_employee = 1;

                    $use_existings_payment = false;
                    if (!$order->hasInvoice()) {
                        $use_existings_payment = true;
                    }
                    $history->changeIdOrderState((int)$order_state->id, $order, $use_existings_payment);
                    
                    //update history commissions
                    $states = OrderState::getOrderStates($this->context->language->id);
                    $cancel_commissions = false;
                    foreach ($states as $state) {
                        if (Configuration::get('MARKETPLACE_CANCEL_COMMISSION_'.$state['id_order_state']) == 1 && $id_order_state == $state['id_order_state'])
                            $cancel_commissions = true;
                    }
                    
                    //si toca cancelar comisiones
                    if ($cancel_commissions) 
                        SellerCommisionHistory::changeStateCommissionsByOrder($id_order, 'cancel');

                    $carrier = new Carrier($order->id_carrier, $order->id_lang);
                    $templateVars = array();
                    if ($history->id_order_state == Configuration::get('PS_OS_SHIPPING') && $order->shipping_number) {
                        $templateVars = array('{followup}' => str_replace('@', $order->shipping_number, $carrier->url));
                    }

                    // Save all changes
                    if ($history->addWithemail(true, $templateVars)) {
                        // synchronizes quantities if needed..
                        if (Configuration::get('PS_ADVANCED_STOCK_MANAGEMENT')) {
                            foreach ($order->getProducts() as $product) {
                                if (StockAvailable::dependsOnStock($product['product_id'])) {
                                    StockAvailable::synchronize($product['product_id'], $product['id_shop']);
                                }
                            }
                        }
                        
                        $params = array('id_order' => $id_order);
                        Tools::redirect($this->context->link->getModuleLink('marketplace', 'orders', $params, true));
                    }
                }
            }
        }       
        elseif (Tools::isSubmit('submitStateCancel')) {
            $id_order = (int)Tools::getValue('id_order');
            $id_order_state = 6;
            $order = new Order($id_order);
            $order_state = new OrderState($id_order_state);

            if (!Validate::isLoadedObject($order_state)) {
                $this->errors[] = Tools::displayError('The new order status is invalid.');
            } 
            else {
                $current_order_state = $order->getCurrentOrderState();
                if ($current_order_state->id != $order_state->id) {
                    // Create new OrderHistory
                    $history = new OrderHistory();
                    $history->id_order = $order->id;
                    $history->id_employee = 1;

                    $use_existings_payment = false;
                    if (!$order->hasInvoice()) {
                        $use_existings_payment = true;
                    }
                    $history->changeIdOrderState((int)$order_state->id, $order, $use_existings_payment);
                    
                    //update history commissions
                    $states = OrderState::getOrderStates($this->context->language->id);
                    $cancel_commissions = false;
                    foreach ($states as $state) {
                        if (Configuration::get('MARKETPLACE_CANCEL_COMMISSION_'.$state['id_order_state']) == 1 && $id_order_state == $state['id_order_state'])
                            $cancel_commissions = true;
                    }
                    
                    //si toca cancelar comisiones
                    if ($cancel_commissions) 
                        SellerCommisionHistory::changeStateCommissionsByOrder($id_order, 'cancel');

                    $carrier = new Carrier($order->id_carrier, $order->id_lang);
                    $templateVars = array();
                    if ($history->id_order_state == Configuration::get('PS_OS_SHIPPING') && $order->shipping_number) {
                        $templateVars = array('{followup}' => str_replace('@', $order->shipping_number, $carrier->url));
                    }

                    // Save all changes
                    if ($history->addWithemail(true, $templateVars)) {
                        // synchronizes quantities if needed..
                        if (Configuration::get('PS_ADVANCED_STOCK_MANAGEMENT')) {
                            foreach ($order->getProducts() as $product) {
                                if (StockAvailable::dependsOnStock($product['product_id'])) {
                                    StockAvailable::synchronize($product['product_id'], $product['id_shop']);
                                }
                            }
                        }
                            //SEND EMAIL TO ADMINISTRATOR
                            $customer = new Customer((int)($order->id_customer));
                            $params['{lastname}'] = $customer->lastname;
                            $params['{firstname}'] = $customer->firstname;
                            $params['{id_order}'] = $order->id;
                            $params['{order_name}'] = $order->getUniqReference();
                            $params['{seller_name}'] = Seller::getSellerNameByOrder($order->id);

                            //$merchant_mails = explode("\n", Configuration::get('MA_MERCHANT_MAILS'));
                            //foreach ($merchant_mails as $merchant_mail)
                            //{

                                @Mail::Send(
                                    (int)$order->id_lang,
                                    'order_canceled_admin',
                                    Mail::l('Commande annulée', (int)$order->id_lang),
                                    $params,
                                    Configuration::get('MARKETPLACE_SEND_ADMIN'),
                                    null,
                                    Configuration::get('PS_SHOP_EMAIL'),
                                    Configuration::get('PS_SHOP_NAME'),
                                    null,
                                    null,
                                    _PS_MAIL_DIR_,
                                    true,
                                    (int)$order->id_shop
                                );
                            //}

                        $params = array('id_order' => $id_order);
                        Tools::redirect($this->context->link->getModuleLink('marketplace', 'orders', $params, true));
                    }
                }
            }
        }        
        elseif (Tools::isSubmit('submitConfirmDelivery')) {
            $id_order = (int)Tools::getValue('id_order');            
            $id_order_state = 27;
            $order = new Order($id_order);
            $order_state = new OrderState($id_order_state);

            if (!Validate::isLoadedObject($order_state)) {
                $this->errors[] = Tools::displayError('The new order status is invalid.');
            } 
            else {
                $current_order_state = $order->getCurrentOrderState();
                if ($current_order_state->id != $order_state->id) {
                    // Create new OrderHistory
                    $history = new OrderHistory();
                    $history->id_order = $order->id;
                    $history->id_employee = 1;

                    $use_existings_payment = false;
                    if (!$order->hasInvoice()) {
                        $use_existings_payment = true;
                    }
                    $history->changeIdOrderState((int)$order_state->id, $order, $use_existings_payment);
                    
                    //update history commissions
                    $states = OrderState::getOrderStates($this->context->language->id);
                    $cancel_commissions = false;
                    foreach ($states as $state) {
                        if (Configuration::get('MARKETPLACE_CANCEL_COMMISSION_'.$state['id_order_state']) == 1 && $id_order_state == $state['id_order_state'])
                            $cancel_commissions = true;
                    }
                    
                    //si toca cancelar comisiones
                    if ($cancel_commissions) 
                        SellerCommisionHistory::changeStateCommissionsByOrder($id_order, 'cancel');

                    $carrier = new Carrier($order->id_carrier, $order->id_lang);
                    $templateVars = array();
                    

                    // Save all changes
                    /*if ($history->addWithemail(true, $templateVars)) {
                        // synchronizes quantities if needed..
                        if (Configuration::get('PS_ADVANCED_STOCK_MANAGEMENT')) {
                            foreach ($order->getProducts() as $product) {
                                if (StockAvailable::dependsOnStock($product['product_id'])) {
                                    StockAvailable::synchronize($product['product_id'], $product['id_shop']);
                                }
                            }
                        }
                        
                        $params = array('id_order' => $id_order);
                        Tools::redirect($this->context->link->getModuleLink('marketplace', 'orders', $params, true));
                    }*/

                    
                    $history->add(true);
                    $params = array('id_order' => $id_order);
                    Tools::redirect($this->context->link->getModuleLink('marketplace', 'orders', $params, true));
                }
            }
        }
        elseif (Tools::isSubmit('submitStateDelivery')) {
            $id_order = (int)Tools::getValue('id_order');
            $shipping_number = Tools::getValue('order_shipping_number');
            $id_order_state = 4;
            $order = new Order($id_order);
            $order_state = new OrderState($id_order_state);

            if($shipping_number != '' && $shipping_number != '0')
                $order->shipping_number = $shipping_number;

            if (!Validate::isLoadedObject($order_state)) {
                $this->errors[] = Tools::displayError('The new order status is invalid.');
            } 
            else {
                $current_order_state = $order->getCurrentOrderState();
                if ($current_order_state->id != $order_state->id) {
                    // Create new OrderHistory
                    $history = new OrderHistory();
                    $history->id_order = $order->id;
                    $history->id_employee = 1;

                    $use_existings_payment = false;
                    if (!$order->hasInvoice()) {
                        $use_existings_payment = true;
                    }
                    $history->changeIdOrderState((int)$order_state->id, $order, $use_existings_payment);
                    
                    //update history commissions
                    $states = OrderState::getOrderStates($this->context->language->id);
                    $cancel_commissions = false;
                    foreach ($states as $state) {
                        if (Configuration::get('MARKETPLACE_CANCEL_COMMISSION_'.$state['id_order_state']) == 1 && $id_order_state == $state['id_order_state'])
                            $cancel_commissions = true;
                    }
                    
                    //si toca cancelar comisiones
                    if ($cancel_commissions) 
                        SellerCommisionHistory::changeStateCommissionsByOrder($id_order, 'cancel');

                    $carrier = new Carrier($order->id_carrier, $order->id_lang);
                    $templateVars = array();
                    /*if ($history->id_order_state == Configuration::get('PS_OS_SHIPPING') && $order->shipping_number) {
                        $templateVars = array('{followup}' => str_replace('@', $order->shipping_number, $carrier->url));
                    }*/
                    if ($history->id_order_state == Configuration::get('PS_OS_SHIPPING') && $order->shipping_number) {
                        $templateVars = array('{followup}' => ', portant le numero de suivi: <strong>'.$order->shipping_number.'</strong>');
                    }

                    // Save all changes
                    if ($history->addWithemail(true, $templateVars)) {
                        // synchronizes quantities if needed..
                        if (Configuration::get('PS_ADVANCED_STOCK_MANAGEMENT')) {
                            foreach ($order->getProducts() as $product) {
                                if (StockAvailable::dependsOnStock($product['product_id'])) {
                                    StockAvailable::synchronize($product['product_id'], $product['id_shop']);
                                }
                            }
                        }
                        
                        $params = array('id_order' => $id_order);
                        Tools::redirect($this->context->link->getModuleLink('marketplace', 'orders', $params, true));
                    }
                }
            }
        }
        elseif (Tools::isSubmit('submitStateRefundPart')) {
            $id_order = (int)Tools::getValue('id_order');
            $id_order_state = 16;
            $order = new Order($id_order);
            $order_state = new OrderState($id_order_state);

            if (!Validate::isLoadedObject($order_state)) {
                $this->errors[] = Tools::displayError('The new order status is invalid.');
            } 
            else {
                $current_order_state = $order->getCurrentOrderState();
                if ($current_order_state->id != $order_state->id) {
                    // Create new OrderHistory
                    $history = new OrderHistory();
                    $history->id_order = $order->id;
                    $history->id_employee = 1;

                    $use_existings_payment = false;
                    if (!$order->hasInvoice()) {
                        $use_existings_payment = true;
                    }
                    $history->changeIdOrderState((int)$order_state->id, $order, $use_existings_payment);
                    
                    //update history commissions
                    $states = OrderState::getOrderStates($this->context->language->id);
                    $cancel_commissions = false;
                    foreach ($states as $state) {
                        if (Configuration::get('MARKETPLACE_CANCEL_COMMISSION_'.$state['id_order_state']) == 1 && $id_order_state == $state['id_order_state'])
                            $cancel_commissions = true;
                    }
                    
                    //si toca cancelar comisiones
                    if ($cancel_commissions) 
                        SellerCommisionHistory::changeStateCommissionsByOrder($id_order, 'cancel');

                    $carrier = new Carrier($order->id_carrier, $order->id_lang);
                    $templateVars = array();
                    if ($history->id_order_state == Configuration::get('PS_OS_SHIPPING') && $order->shipping_number) {
                        $templateVars = array('{followup}' => str_replace('@', $order->shipping_number, $carrier->url));
                    }

                    // Save all changes
                    if ($history->addWithemail(true, $templateVars)) {
                        // synchronizes quantities if needed..
                        if (Configuration::get('PS_ADVANCED_STOCK_MANAGEMENT')) {
                            foreach ($order->getProducts() as $product) {
                                if (StockAvailable::dependsOnStock($product['product_id'])) {
                                    StockAvailable::synchronize($product['product_id'], $product['id_shop']);
                                }
                            }
                        }
                        
                        $params = array('id_order' => $id_order);
                        Tools::redirect($this->context->link->getModuleLink('marketplace', 'orders', $params, true));
                    }
                }
            }
        }
        elseif (Tools::isSubmit('submitStateRefundAll')) {
            $id_order = (int)Tools::getValue('id_order');
            $id_order_state = 17;
            $order = new Order($id_order);
            $order_state = new OrderState($id_order_state);

            if (!Validate::isLoadedObject($order_state)) {
                $this->errors[] = Tools::displayError('The new order status is invalid.');
            } 
            else {
                $current_order_state = $order->getCurrentOrderState();
                if ($current_order_state->id != $order_state->id) {
                    // Create new OrderHistory
                    $history = new OrderHistory();
                    $history->id_order = $order->id;
                    $history->id_employee = 1;

                    $use_existings_payment = false;
                    if (!$order->hasInvoice()) {
                        $use_existings_payment = true;
                    }
                    $history->changeIdOrderState((int)$order_state->id, $order, $use_existings_payment);
                    
                    //update history commissions
                    $states = OrderState::getOrderStates($this->context->language->id);
                    $cancel_commissions = false;
                    foreach ($states as $state) {
                        if (Configuration::get('MARKETPLACE_CANCEL_COMMISSION_'.$state['id_order_state']) == 1 && $id_order_state == $state['id_order_state'])
                            $cancel_commissions = true;
                    }
                    
                    //si toca cancelar comisiones
                    if ($cancel_commissions) 
                        SellerCommisionHistory::changeStateCommissionsByOrder($id_order, 'cancel');

                    $carrier = new Carrier($order->id_carrier, $order->id_lang);
                    $templateVars = array();
                    if ($history->id_order_state == Configuration::get('PS_OS_SHIPPING') && $order->shipping_number) {
                        $templateVars = array('{followup}' => str_replace('@', $order->shipping_number, $carrier->url));
                    }

                    // Save all changes
                    if ($history->addWithemail(true, $templateVars)) {
                        // synchronizes quantities if needed..
                        if (Configuration::get('PS_ADVANCED_STOCK_MANAGEMENT')) {
                            foreach ($order->getProducts() as $product) {
                                if (StockAvailable::dependsOnStock($product['product_id'])) {
                                    StockAvailable::synchronize($product['product_id'], $product['id_shop']);
                                }
                            }
                        }
                        
                        $params = array('id_order' => $id_order);
                        Tools::redirect($this->context->link->getModuleLink('marketplace', 'orders', $params, true));
                    }
                }
            }
        }
        elseif (Tools::getValue('action') == 'generateInvoicePDF') {
            $id_order = (int)Tools::getValue('id_order');
            $this->generateInvoicePDFByIdOrder($id_order);
        }

        elseif (Tools::isSubmit('partialRefund') && isset($order)) {
            //if ($this->tabAccess['edit'] == '1') {
                if (Tools::isSubmit('partialRefundProduct') && ($refunds = Tools::getValue('partialRefundProduct')) && is_array($refunds)) {
                    $amount = 0;
                    $order_detail_list = array();
                    $full_quantity_list = array();
                    foreach ($refunds as $id_order_detail => $amount_detail) {
                        $quantity = Tools::getValue('partialRefundProductQuantity');
                        if (!$quantity[$id_order_detail]) {
                            continue;
                        }

                        $full_quantity_list[$id_order_detail] = (int)$quantity[$id_order_detail];

                        $order_detail_list[$id_order_detail] = array(
                            'quantity' => (int)$quantity[$id_order_detail],
                            'id_order_detail' => (int)$id_order_detail
                        );

                        $order_detail = new OrderDetail((int)$id_order_detail);
                        if (empty($amount_detail)) {
                            $order_detail_list[$id_order_detail]['unit_price'] = (!Tools::getValue('TaxMethod') ? $order_detail->unit_price_tax_excl : $order_detail->unit_price_tax_incl);
                            $order_detail_list[$id_order_detail]['amount'] = $order_detail->unit_price_tax_incl * $order_detail_list[$id_order_detail]['quantity'];
                        } else {
                            $order_detail_list[$id_order_detail]['amount'] = (float)str_replace(',', '.', $amount_detail);
                            $order_detail_list[$id_order_detail]['unit_price'] = $order_detail_list[$id_order_detail]['amount'] / $order_detail_list[$id_order_detail]['quantity'];
                        }
                        $amount += $order_detail_list[$id_order_detail]['amount'];
                        if (!$order->hasBeenDelivered() || ($order->hasBeenDelivered() && Tools::isSubmit('reinjectQuantities')) && $order_detail_list[$id_order_detail]['quantity'] > 0) {
                            $this->reinjectQuantity($order_detail, $order_detail_list[$id_order_detail]['quantity']);
                        }
                    }

                    $shipping_cost_amount = (float)str_replace(',', '.', Tools::getValue('partialRefundShippingCost')) ? (float)str_replace(',', '.', Tools::getValue('partialRefundShippingCost')) : false;

                    if ($amount == 0 && $shipping_cost_amount == 0) {
                        if (!empty($refunds)) {
                            $this->errors[] = Tools::displayError('Please enter a quantity to proceed with your refund.');
                        } else {
                            $this->errors[] = Tools::displayError('Please enter an amount to proceed with your refund.');
                        }
                        return false;
                    }

                    $choosen = false;
                    $voucher = 0;

                    if ((int)Tools::getValue('refund_voucher_off') == 1) {
                        $amount -= $voucher = (float)Tools::getValue('order_discount_price');
                    } elseif ((int)Tools::getValue('refund_voucher_off') == 2) {
                        $choosen = true;
                        $amount = $voucher = (float)Tools::getValue('refund_voucher_choose');
                    }

                    if ($shipping_cost_amount > 0) {
                        if (!Tools::getValue('TaxMethod')) {
                            $tax = new Tax();
                            $tax->rate = $order->carrier_tax_rate;
                            $tax_calculator = new TaxCalculator(array($tax));
                            $amount += $tax_calculator->addTaxes($shipping_cost_amount);
                        } else {
                            $amount += $shipping_cost_amount;
                        }
                    }

                    $order_carrier = new OrderCarrier((int)$order->getIdOrderCarrier());
                    if (Validate::isLoadedObject($order_carrier)) {
                        $order_carrier->weight = (float)$order->getTotalWeight();
                        if ($order_carrier->update()) {
                            $order->weight = sprintf("%.3f ".Configuration::get('PS_WEIGHT_UNIT'), $order_carrier->weight);
                        }
                    }

                    if ($amount >= 0) {
                        if (!OrderSlip::create($order, $order_detail_list, $shipping_cost_amount, $voucher, $choosen,
                            (Tools::getValue('TaxMethod') ? false : true))) {
                            $this->errors[] = Tools::displayError('You cannot generate a partial credit slip.');
                        } else {
                            Hook::exec('actionOrderSlipAdd', array('order' => $order, 'productList' => $order_detail_list, 'qtyList' => $full_quantity_list), null, false, true, false, $order->id_shop);
                            $customer = new Customer((int)($order->id_customer));
                            $params['{lastname}'] = $customer->lastname;
                            $params['{firstname}'] = $customer->firstname;
                            $params['{id_order}'] = $order->id;
                            $params['{order_name}'] = $order->getUniqReference();
                            @Mail::Send(
                                (int)$order->id_lang,
                                'credit_slip',
                                Mail::l('New credit slip regarding your order', (int)$order->id_lang),
                                $params,
                                $customer->email,
                                $customer->firstname.' '.$customer->lastname,
                                Configuration::get('PS_SHOP_EMAIL'),
                                Configuration::get('PS_SHOP_NAME'),
                                null,
                                null,
                                _PS_MAIL_DIR_,
                                true,
                                (int)$order->id_shop
                            );

                            $params['{lastname}'] = $customer->lastname;
                            $params['{firstname}'] = $customer->firstname;
                            $params['{id_order}'] = $order->id;
                            $params['{order_name}'] = $order->getUniqReference();
                            $params['{seller_name}'] = Seller::getSellerNameByOrder($order->id);
                            $params['{link_admin}'] = $this->context->link->getAdminLink('index',false);

                            //$merchant_mails = explode("\n", Configuration::get('MA_MERCHANT_MAILS'));
                            //foreach ($merchant_mails as $merchant_mail)
                            //{

                                @Mail::Send(
                                    (int)$order->id_lang,
                                    'credit_slip_admin',
                                    Mail::l('Nouveau remboursement partiel', (int)$order->id_lang),
                                    $params,
                                    Configuration::get('MARKETPLACE_SEND_ADMIN'),
                                    null,
                                    Configuration::get('PS_SHOP_EMAIL'),
                                    Configuration::get('PS_SHOP_NAME'),
                                    null,
                                    null,
                                    _PS_MAIL_DIR_,
                                    true,
                                    (int)$order->id_shop
                                );
                            //}
                        }

                        foreach ($order_detail_list as &$product) {
                            $order_detail = new OrderDetail((int)$product['id_order_detail']);
                            if (Configuration::get('PS_ADVANCED_STOCK_MANAGEMENT')) {
                                StockAvailable::synchronize($order_detail->product_id);
                            }
                        }

                        // UPDATE ORDER STATE TO PARTIAL REFUND
                        $id_order_state = 16;                    
                        $order_state = new OrderState($id_order_state);

                        if (!Validate::isLoadedObject($order_state)) {
                            $this->errors[] = Tools::displayError('The new order status is invalid.');
                        } 
                        else {
                            $current_order_state = $order->getCurrentOrderState();
                            if ($current_order_state->id != $order_state->id) {
                                // Create new OrderHistory
                                $history = new OrderHistory();
                                $history->id_order = $order->id;
                                $history->id_employee = 1;

                                $use_existings_payment = false;
                                if (!$order->hasInvoice()) {
                                    $use_existings_payment = true;
                                }
                                $history->changeIdOrderState((int)$order_state->id, $order, $use_existings_payment);
                                
                                //update history commissions
                                $states = OrderState::getOrderStates($this->context->language->id);
                                $cancel_commissions = false;
                                foreach ($states as $state) {
                                    if (Configuration::get('MARKETPLACE_CANCEL_COMMISSION_'.$state['id_order_state']) == 1 && $id_order_state == $state['id_order_state'])
                                        $cancel_commissions = true;
                                }
                                
                                //si toca cancelar comisiones
                                if ($cancel_commissions) 
                                    SellerCommisionHistory::changeStateCommissionsByOrder($id_order, 'cancel');

                                $carrier = new Carrier($order->id_carrier, $order->id_lang);
                                $templateVars = array();
                                if ($history->id_order_state == Configuration::get('PS_OS_SHIPPING') && $order->shipping_number) {
                                    $templateVars = array('{followup}' => str_replace('@', $order->shipping_number, $carrier->url));
                                }

                                // Save all changes
                                if ($history->addWithemail(true, $templateVars)) {
                                    // synchronizes quantities if needed..
                                    if (Configuration::get('PS_ADVANCED_STOCK_MANAGEMENT')) {
                                        foreach ($order->getProducts() as $product) {
                                            if (StockAvailable::dependsOnStock($product['product_id'])) {
                                                StockAvailable::synchronize($product['product_id'], $product['id_shop']);
                                            }
                                        }
                                    }
                                    
                                    
                                }
                            }
                        }

                        // Generate voucher
                        if (Tools::isSubmit('generateDiscountRefund') && !count($this->errors) && $amount > 0) {
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
                                        $params, $customer->email, $customer->firstname.' '.$customer->lastname, Configuration::get('PS_SHOP_EMAIL'), Configuration::get('PS_SHOP_NAME'), null,
                                        null, _PS_MAIL_DIR_, true, (int)$order->id_shop);
                                }
                            }
                        }
                    } else {
                        if (!empty($refunds)) {
                            $this->errors[] = Tools::displayError('Please enter a quantity to proceed with your refund.');
                        } else {
                            $this->errors[] = Tools::displayError('Please enter an amount to proceed with your refund.');
                        }
                    }
                    // Redirect if no errors
                    /*if (!count($this->errors)) {
                        Tools::redirectAdmin(self::$currentIndex.'&id_order='.$order->id.'&vieworder&conf=30&token='.$this->token);
                    }*/

                    $params = array('id_order' => $order->id);
                                Tools::redirect($this->context->link->getModuleLink('marketplace', 'orders', $params, true));

                } else {
                    $this->errors[] = Tools::displayError('The partial refund data is incorrect.');
                }
            /*} else {
                $this->errors[] = Tools::displayError('You do not have permission to delete this.');
            }*/
        }
    }

    public function initContent() {
        
        parent::initContent();
        
        if(!$this->context->cookie->id_customer)
            Tools::redirect($this->context->link->getPageLink('my-account', true));

        $id_seller = Seller::getSellerByCustomer($this->context->cookie->id_customer);
        $is_seller = Seller::isSeller($this->context->cookie->id_customer, $this->context->shop->id);
        
        if (!$is_seller)
            Tools::redirect($this->context->link->getPageLink('my-account', true));
        
        $seller = new Seller($id_seller);
        
        if ($seller->active == 0)
            Tools::redirect($this->context->link->getPageLink('my-account', true));
        
        $param = array('id_seller' => $seller->id, 'link_rewrite' => $seller->link_rewrite);
        $url_seller_profile = $this->module->getmarketplaceLink('marketplace_seller_rule', $param);
        //$delay_exp[] = array();

        $delay_exp[] = array(
                        'id_order' =>'0',
                        'hours_exp' => 0
        );

        if (!Tools::getValue('id_order')) {
            $orders = SellerOrder::getOrdersBySeller($id_seller, $this->context->language->id);

            $diff24Hours = new DateInterval(Configuration::get('DELAY_DELIVERY'));
            
            if(is_array($orders))
            {
                foreach ($orders as $order) {
                    
                    $d1 = new DateTime($order['date_add']);
                    $d2 = new DateTime();
                    $d2->setTimezone(new DateTimeZone('Europe/London'));
                    $d2->format('Y-m-d H:i:s');
                    $d1->format('Y-m-d H:i:s');

                    $d1->add($diff24Hours);

                    $delay_exp[] = array(
                        'id_order' => $order['id_order'],
                        'hours_exp' =>  round(($d1->getTimestamp() - $d2->getTimestamp()) / 3600)
                    );
                    
                }
            }

            $countneworder = SellerOrder::getVisitedOrdersSeller($id_seller,$this->context->language->id);

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
                'orders' => $orders,                
                'mesages_not_readed' => SellerIncidenceMessage::getNumMessagesNotReadedBySeller($id_seller),                
                'delay_exp' => $delay_exp,
                'countneworder' => $countneworder,
                'seller' => $seller,
                'incidences' => $incidences,
                'content_only' => 1,
                'menu' => 'module:marketplace/views/templates/front/sellermenu.tpl',
            ));

            //$this->setTemplate('orders.tpl');
            $this->setTemplate('module:marketplace/views/templates/front/orders.tpl');
        }
        else {
            $id_order = (int)Tools::getValue('id_order');
            $order = new Order($id_order);
            $order->visited = 1;
            $order->visited_seller = 1;
            $order->update();

            $customer = new Customer($order->id_customer);
            $address_delivery = new Address($order->id_address_delivery);
            $address_invoice = new Address($order->id_address_invoice);
            
            $inv_adr_fields = AddressFormat::getOrderedAddressFields($address_invoice->id_country);
            $dlv_adr_fields = AddressFormat::getOrderedAddressFields($address_delivery->id_country);
            
            $invoiceAddressFormatedValues = AddressFormat::getFormattedAddressFieldsValues($address_invoice, $inv_adr_fields);
            $deliveryAddressFormatedValues = AddressFormat::getFormattedAddressFieldsValues($address_delivery, $dlv_adr_fields);
            
            $params_order = array('id_order' => $id_order);
            $params_order_invoice = array('id_order' => $id_order, 'action' => 'generateInvoicePDF');
            
            $products = $order->getProductsDetailSellerOnly();
            //$products = $order->getProductsDetail();
            //$product_tmp = end($products);
            //$resume = OrderSlip::getProductSlipResume((int)$product_tmp['id_order_detail']);
            $allRefund = 0;


            $i = 0;
            foreach ($products as $product) {
                $customized_product_quantity = 0;

                $resume = OrderSlip::getProductSlipResume($product['id_order_detail']);

                /*if (is_array($product['customizedDatas'])) {
                foreach ($product['customizedDatas'] as $customizationPerAddress) {
                    foreach ($customizationPerAddress as $customizationId => $customization) {
                        $customized_product_quantity += (int)$customization['quantity'];
                        }
                    }
                }*/


                $products[$i]['customized_product_quantity'] = $customized_product_quantity;

                $products[$i]['current_stock'] = StockAvailable::getQuantityAvailableByProduct($product['product_id'], $product['product_attribute_id'], $product['id_shop']);

                //$products[$i]['current_stock'] = $product['product_quantity'] - $resume['product_quantity'];
                $products[$i]['quantity_refundable'] = $product['product_quantity'] - $resume['product_quantity'];
                $products[$i]['amount_refundable'] = $product['total_price_tax_excl'] - $resume['amount_tax_excl'];
                $products[$i]['amount_refundable_tax_incl'] = $product['total_price_tax_incl'] - $resume['amount_tax_incl'];

                $products[$i]['amount_refundable'] = $product['total_price_tax_excl'] - $resume['amount_tax_excl'];
                $products[$i]['amount_refund'] = Tools::displayPrice($resume['amount_tax_incl']);

                if($products[$i]['product_quantity_refunded'] > 0)
                    $allRefund = 1;
                else
                    $allRefund = 0;

                $i++;
            }
            
            

            $OrderStates = OrderState::getOrderStates($this->context->language->id);
            $listStateOrderSeller = explode(',', Configuration::get('MARKETPLACE_STATES_ORDER_SELLER'));
            $orderStateOption = array();
            foreach($OrderStates as $orderState){
                if( in_array($orderState['id_order_state'],$listStateOrderSeller) ){
                    $orderStateOption[] = $orderState; 
                }
            }

            $diff24Hours = new DateInterval(Configuration::get('DELAY_DELIVERY'));

            $d1 = new DateTime($order->date_add);
            $d2 = new DateTime();
            $d2->setTimezone(new DateTimeZone('Europe/London'));
            $d2->format('Y-m-d H:i:s');
            $d1->format('Y-m-d H:i:s');

            $d1->add($diff24Hours);

            $hours_exp = round(($d1->getTimestamp() - $d2->getTimestamp()) / 3600);

            if($order->slip_amount > 0)
                $total_commision = $this->getCommisionByOrder($id_order) - $order->slip_amount;
            else
                $total_commision  = $this->getCommisionByOrder($id_order);
            
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
                'seller_link' => $url_seller_profile,
                'show_import_product' => Configuration::get('MARKETPLACE_SELLER_IMPORT_PROD'),
                'show_orders' => Configuration::get('MARKETPLACE_SHOW_ORDERS'),
                'show_manage_orders' => Configuration::get('MARKETPLACE_SHOW_MANAGE_ORDERS'),
                'show_manage_carriers' => Configuration::get('MARKETPLACE_SHOW_MANAGE_CARRIER'),
                'show_contact' => Configuration::get('MARKETPLACE_SHOW_CONTACT'),
                'show_edit_seller_account' => Configuration::get('MARKETPLACE_SHOW_EDIT_ACCOUNT'),
                'show_dashboard' => Configuration::get('MARKETPLACE_SHOW_DASHBOARD'),
                'show_menu_top' => Configuration::get('MARKETPLACE_MENU_TOP'),
                'show_menu_options' => Configuration::get('MARKETPLACE_MENU_OPTIONS'),
                'order_link' => $this->context->link->getModuleLink('marketplace', 'orders', $params_order, true),
                'order_invoice_link' => $this->context->link->getModuleLink('marketplace', 'orders', $params_order_invoice, true),
                'order' => $order,
                'order_state_history' => $order->getHistory($this->context->language->id,false,false,0,'ASC'),
                //'order_states' => OrderState::getOrderStates($this->context->language->id),
                'order_states' => $orderStateOption,
                'customer' => $customer,
                'address_delivery' => $deliveryAddressFormatedValues,
                'address_invoice' => $invoiceAddressFormatedValues,
                'products' => $products,
                //'total_products' => $order->getTotalProductsWithTaxesSellerOnly(),
                'total_products' => $order->getTotalProductsWithTaxes(),
                'total_weight' => $order->getTotalWeight(),
                //'total_paid' => $order->getTotalProductsWithTaxesSellerOnly() + $order->total_shipping_tax_incl,//$order->total_paid,
                'total_paid' => $order->total_paid,
                'total_shipping' => $order->total_shipping_tax_incl,
                'weight_unit' => Configuration::get('PS_WEIGHT_UNIT'),
                'commision' => SellerCommision::getCommisionBySeller($id_seller),
                'total_commision' => $total_commision,
                'mesages_not_readed' => SellerIncidenceMessage::getNumMessagesNotReadedBySeller($id_seller),
                'message_order_not_readed' => SellerIncidenceMessage::getNumMessagesNotReadedOrderBySeller($id_seller,$order->id),
                'hours_exp' => $hours_exp,
                'allRefund' => $allRefund,
                'seller' => $seller,
                'incidences' => $incidences,
                'content_only' => 1,
                'menu' => 'module:marketplace/views/templates/front/sellermenu.tpl',
            ));
            
            //$this->setTemplate('order.tpl');
            $this->setTemplate('module:marketplace/views/templates/front/order.tpl');
        }
    }
    
    

    public function getCommisionByOrder($id_order) {
        return Db::getInstance()->getValue('SELECT SUM(commision) FROM '._DB_PREFIX_.'seller_commision_history WHERE id_order = '.(int)$id_order);
    }
    
    public function generateInvoicePDFByIdOrder($id_order)
    {
        $order = new Order((int)$id_order);
        /*if (!Validate::isLoadedObject($order))
            die(Tools::displayError('The order cannot be found within your database.'));*/

        $order_invoice_list = $order->getInvoicesCollection();
        
        $pdf = new PDF($order_invoice_list, PDF::TEMPLATE_INVOICE, Context::getContext()->smarty);
        $pdf->render();
    }

    /**
     * @param OrderDetail $order_detail
     * @param int $qty_cancel_product
     * @param bool $delete
     */
    public function reinjectQuantity($order_detail, $qty_cancel_product, $delete = false)
    {
        // Reinject product
        $reinjectable_quantity = (int)$order_detail->product_quantity - (int)$order_detail->product_quantity_reinjected;
        $quantity_to_reinject = $qty_cancel_product > $reinjectable_quantity ? $reinjectable_quantity : $qty_cancel_product;
        // @since 1.5.0 : Advanced Stock Management
        $product_to_inject = new Product($order_detail->product_id, false, (int)$this->context->language->id, (int)$order_detail->id_shop);

        $product = new Product($order_detail->product_id, false, (int)$this->context->language->id, (int)$order_detail->id_shop);

        if (Configuration::get('PS_ADVANCED_STOCK_MANAGEMENT') && $product->advanced_stock_management && $order_detail->id_warehouse != 0) {
            $manager = StockManagerFactory::getManager();
            $movements = StockMvt::getNegativeStockMvts(
                                $order_detail->id_order,
                                $order_detail->product_id,
                                $order_detail->product_attribute_id,
                                $quantity_to_reinject
                            );
            $left_to_reinject = $quantity_to_reinject;
            foreach ($movements as $movement) {
                if ($left_to_reinject > $movement['physical_quantity']) {
                    $quantity_to_reinject = $movement['physical_quantity'];
                }

                $left_to_reinject -= $quantity_to_reinject;
                if (Pack::isPack((int)$product->id)) {
                    // Gets items
                        if ($product->pack_stock_type == 1 || $product->pack_stock_type == 2 || ($product->pack_stock_type == 3 && Configuration::get('PS_PACK_STOCK_TYPE') > 0)) {
                            $products_pack = Pack::getItems((int)$product->id, (int)Configuration::get('PS_LANG_DEFAULT'));
                            // Foreach item
                            foreach ($products_pack as $product_pack) {
                                if ($product_pack->advanced_stock_management == 1) {
                                    $manager->addProduct(
                                        $product_pack->id,
                                        $product_pack->id_pack_product_attribute,
                                        new Warehouse($movement['id_warehouse']),
                                        $product_pack->pack_quantity * $quantity_to_reinject,
                                        null,
                                        $movement['price_te'],
                                        true
                                    );
                                }
                            }
                        }
                    if ($product->pack_stock_type == 0 || $product->pack_stock_type == 2 ||
                            ($product->pack_stock_type == 3 && (Configuration::get('PS_PACK_STOCK_TYPE') == 0 || Configuration::get('PS_PACK_STOCK_TYPE') == 2))) {
                        $manager->addProduct(
                                $order_detail->product_id,
                                $order_detail->product_attribute_id,
                                new Warehouse($movement['id_warehouse']),
                                $quantity_to_reinject,
                                null,
                                $movement['price_te'],
                                true
                            );
                    }
                } else {
                    $manager->addProduct(
                            $order_detail->product_id,
                            $order_detail->product_attribute_id,
                            new Warehouse($movement['id_warehouse']),
                            $quantity_to_reinject,
                            null,
                            $movement['price_te'],
                            true
                        );
                }
            }

            $id_product = $order_detail->product_id;
            if ($delete) {
                $order_detail->delete();
            }
            StockAvailable::synchronize($id_product);
        } elseif ($order_detail->id_warehouse == 0) {
            StockAvailable::updateQuantity(
                    $order_detail->product_id,
                    $order_detail->product_attribute_id,
                    $quantity_to_reinject,
                    $order_detail->id_shop
                );

            if ($delete) {
                $order_detail->delete();
            }
        } else {
            $this->errors[] = Tools::displayError('This product cannot be re-stocked.');
        }
    }
    
}