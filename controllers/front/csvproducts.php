<?php
/**
* 2007-2014 PrestaShop
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
*  @copyright 2007-2014 PrestaShop SA
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/

include_once dirname(__FILE__).'/../../classes/CSVSellerProduct.php';
include_once dirname(__FILE__).'/../../classes/CSVSellerProductLog.php';

class marketplaceCsvproductsModuleFrontController extends ModuleFrontController
{
    public $ssl = true;
    public $id_seller;

    public function initContent() {
        
        parent::initContent();
        
        if(!$this->context->cookie->id_customer)
            Tools::redirect($this->context->link->getPageLink('my-account', true));
        
        $is_seller = Seller::isSeller($this->context->cookie->id_customer, $this->context->shop->id);
        if (!$is_seller)
            Tools::redirect($this->context->link->getPageLink('my-account', true));
        
        $this->id_seller = Seller::getSellerByCustomer($this->context->cookie->id_customer);
        
        $seller = new Seller($this->id_seller);
        
        if ($seller->active == 0)
            Tools::redirect($this->context->link->getPageLink('my-account', true));
        
        $languages = Language::getLanguages();
        
        $csv_seller_product = new CSVSellerProduct();
        
        $param = array('id_seller' => $seller->id, 'link_rewrite' => $seller->link_rewrite);
        $url_seller_profile = $this->module->getmarketplaceLink('marketplace_seller_rule', $param);

        $countneworder = SellerOrder::getVisitedOrdersSeller($this->id_seller,$this->context->language->id);
        $incidences = SellerIncidence::getIncidencesBySeller($this->id_seller);

            if ($incidences != false) {
                $counter = 0;
                foreach ($incidences as $i) {
                    $product_c = new Product((int)$i['id_product'], (int)$this->context->language->id, (int)$this->context->shop->id);
                    $incidences[$counter]['product_name'] = $product_c->name;
                    $incidences[$counter]['messages_not_readed'] = SellerIncidence::getNumMessagesNotReaded($i['id_seller_incidence'], $this->id_seller, false);
                      
                    $messages = SellerIncidence::getMessages((int)$i['id_seller_incidence'],false,0,$this->id_seller);
                    
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
            'show_orders' => Configuration::get('MARKETPLACE_SHOW_ORDERS'),
            'show_manage_orders' => Configuration::get('MARKETPLACE_SHOW_MANAGE_ORDERS'),
            'show_manage_carriers' => Configuration::get('MARKETPLACE_SHOW_MANAGE_CARRIER'),
            'show_edit_seller_account' => Configuration::get('MARKETPLACE_SHOW_EDIT_ACCOUNT'),
            'show_edit_product' => Configuration::get('MARKETPLACE_SHOW_EDIT_PRODUCT'),
            'show_delete_product' => Configuration::get('MARKETPLACE_SHOW_DELETE_PRODUCT'),
            'show_active_product' => Configuration::get('MARKETPLACE_SHOW_ACTIVE_PRODUCT'),
            'show_import_product' => Configuration::get('MARKETPLACE_SELLER_IMPORT_PROD'),
            'show_contact' => Configuration::get('MARKETPLACE_SHOW_CONTACT'),
            'show_dashboard' => Configuration::get('MARKETPLACE_SHOW_DASHBOARD'),
            'show_menu_top' => Configuration::get('MARKETPLACE_MENU_TOP'),
            'show_menu_options' => Configuration::get('MARKETPLACE_MENU_OPTIONS'),
            'mesages_not_readed' => SellerIncidenceMessage::getNumMessagesNotReadedBySeller($this->id_seller),
            'seller_link' => $url_seller_profile,
            'languages' => $languages,
            'id_lang' => $this->context->language->id,
            'available_fields' => $csv_seller_product->getFields(),
            'submitNextStep' => 0,
            'countneworder' => $countneworder,
            'seller' => $seller,
            'incidences' => $incidences,
            'modules_dir' => __PS_BASE_URI__.'modules/',
            'base_dir' => __PS_BASE_URI__,
            'menu' => 'module:marketplace/views/templates/front/sellermenu.tpl',
        ));
        
        if (Tools::isSubmit('submitExport')) {
            $num_products = $csv_seller_product->export($this->id_seller);
            $this->context->smarty->assign(array(
                'submitExport' => 1,
                'num_products' => $num_products,
                'file' => 'products_'.$this->id_seller.'.csv',
                'errors' => $this->errors,
            ));
        }  
        
        if (Tools::isSubmit('submitNextStep')) {            
            if ($_FILES['file']["type"] == "text/comma-separated-values" || 
                    $_FILES['file']["type"] == "text/csv" || 
                    $_FILES['file']["type"] == "application/force-download" || 
                    $_FILES['file']["type"] == "application/vnd.ms-excel") {

                $id_lang = Tools::getValue('id_lang');
                $truncate = Tools::getValue('truncate');
                $match_ref = Tools::getValue('match_ref');
                $csv_seller_product = new CSVSellerProduct();
                $first_rows = $csv_seller_product->getFirstLine($_FILES['file']);
                $header = $csv_seller_product->getHeaderLine($_FILES['file']);
                
                $this->context->smarty->assign(array(
                    'id_lang' => $id_lang,
                    'truncate' => $truncate,
                    'match_ref' => $match_ref,
                    'filename' => $_FILES['file']['name'],
                    'first_rows' => $first_rows,
                    'header' => $header,
                    'submitNextStep' => 1,
                )); 
            }
            else {
                $this->errors[] = $this->module->l('File is incorrect.', 'add');
                $this->context->smarty->assign(array(
                    'errors' => $this->errors,
                ));
            }
        } 
        
        if (Tools::isSubmit('submitImport')) {
            $id_lang = Tools::getValue('id_lang');
            $truncate = Tools::getValue('truncate');
            $match_ref = Tools::getValue('match_ref');
            $filename = Tools::getValue('filename');
            $type_value = Tools::getValue('type_value');
            $id_seller = Seller::getSellerByCustomer($this->context->cookie->id_customer);
            $seller = new Seller($id_seller);
            
            if ($truncate == 1) {
                //ignorar columna id_product
                foreach ($type_value as $key => $value) {
                    if ($value == 'id_product')
                        $type_value[$key] = false;
                }
                
                $seller->deleteSellerProducts();
            }
            
            $result = $csv_seller_product->import($id_seller, $filename, $id_lang, $type_value, $match_ref);
            

            if (is_array($result)) {
                $this->context->smarty->assign(array(
                    'id_seller' => $id_seller,
                    'submitImport' => 1,
                    'added' => $result['added'],
                    'updated' => $result['updated'],
                    'invalid' => $result['invalid'],
                )); 
            }
            else if ($result == 'error_name') {
                $this->errors = $this->module->l('Missing name column.', 'add');
                $this->context->smarty->assign(array(
                    'errors' => $this->errors,
                )); 
            }
            else if ($result == 'error_reference') {
                $this->errors = $this->module->l('Missing reference column.', 'add');
                $this->context->smarty->assign(array(
                    'errors' => $this->errors,
                )); 
            }
            else if ($result == 'error_ean13NULL') {
                $this->errors = $this->module->l('la colonne EAN13 ne peut être NULL.', 'add');
                $this->context->smarty->assign(array(
                    'errors' => $this->errors,
                )); 
            }
            else {
                $this->errors = $this->module->l('File type is incorrect.', 'add');
                $this->context->smarty->assign(array(
                    'errors' => $this->errors,
                )); 
            }



        } 
        
        if (Tools::isSubmit('example')) {
            $csv_seller_product->generateExample();
        }
        
        //$this->setTemplate('csvproducts.tpl');          
        $this->setTemplate('module:marketplace/views/templates/front/csvproducts.tpl');
    }
}