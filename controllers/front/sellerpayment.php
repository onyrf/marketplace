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

class marketplaceSellerpaymentModuleFrontController extends ModuleFrontController
{
    public $ssl = true;
    
    public function postProcess() {
        if (Tools::isSubmit('submitPayment')) {
            $id_seller = Seller::getSellerByCustomer($this->context->cookie->id_customer);
            $paypal = pSQL(Tools::getValue('paypal'));
            $bankwire = (string)Tools::getValue('bankwire'); //this is content html
            
            if ($paypal != '' && !Validate::isEmail($paypal)) {
                $this->errors[] = $this->module->l('The email of paypal is incorrect.', 'sellerpayment');
            }
            
            if (count($this->errors) > 0) {
                $this->context->smarty->assign(array('errors' => $this->errors));     
            }
            else {
                if ($paypal != '') {
                    $id_seller_payment = SellerPayment::getIdByPayment((int)$id_seller, 'paypal');
                    $sellerPayment = new SellerPayment((int)$id_seller_payment);
                    $sellerPayment->account = $paypal;
                    $sellerPayment->update();
                }
                
                if ($bankwire != '') {
                    $id_seller_payment = SellerPayment::getIdByPayment((int)$id_seller, 'bankwire');
                    $sellerPayment = new SellerPayment((int)$id_seller_payment);
                    $sellerPayment->account = $bankwire; 
                    $sellerPayment->update();
                }
                
                Tools::redirect($this->context->link->getModuleLink('marketplace', 'sellerpayment', array('confirmation' => 1), true));
            }
        }
        
        if (Tools::getValue('confirmation'))
            $this->context->smarty->assign(array('confirmation' => 1));
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
        $countneworder = SellerOrder::getVisitedOrdersSeller($id_seller,$this->context->language->id);

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
            'show_import_product' => Configuration::get('MARKETPLACE_SELLER_IMPORT_PROD'),
            'show_orders' => Configuration::get('MARKETPLACE_SHOW_ORDERS'),
            'show_manage_orders' => Configuration::get('MARKETPLACE_SHOW_MANAGE_ORDERS'),
            'show_manage_carriers' => Configuration::get('MARKETPLACE_SHOW_MANAGE_CARRIER'),
            'show_dashboard' => Configuration::get('MARKETPLACE_SHOW_DASHBOARD'),
            'show_edit_seller_account' => Configuration::get('MARKETPLACE_SHOW_EDIT_ACCOUNT'),
            'show_contact' => Configuration::get('MARKETPLACE_SHOW_CONTACT'),
            'show_dashboard' => Configuration::get('MARKETPLACE_SHOW_DASHBOARD'),
            'show_menu_top' => Configuration::get('MARKETPLACE_MENU_TOP'),
            'show_menu_options' => Configuration::get('MARKETPLACE_MENU_OPTIONS'),
            'payments' => SellerPayment::getPaymentsBySeller($id_seller),
            'seller_link' => $url_seller_profile,
            'show_paypal' => Configuration::get('MARKETPLACE_PAYPAL'),
            'show_bankwire' => Configuration::get('MARKETPLACE_BANKWIRE'),
            'mesages_not_readed' => SellerIncidenceMessage::getNumMessagesNotReadedBySeller($id_seller),
            'countneworder' => $countneworder,
            'seller' => $seller,
            'content_only' => 1,
            'incidences' => $incidences,
            'menu' => 'module:marketplace/views/templates/front/sellermenu.tpl',
        ));

        //$this->setTemplate('sellerpayment.tpl');
        $this->setTemplate('module:marketplace/views/templates/front/sellerpayment.tpl');
    }
}