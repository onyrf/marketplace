<?php

class marketplaceSearchproductModuleFrontController extends ModuleFrontController
{
    public $ssl = true;
    public $categoryTree;
    public $exclude;
    public $template;
    public $template_item;

    private function osPath($path)
    {
        return str_replace("/", DIRECTORY_SEPARATOR, $path);
    }

    /*public function init()
    {        

        $url = str_replace($_SERVER['HTTP_HOST'],'vendor.mega-discount.fr',$_SERVER['REQUEST_URI']);

        parent::init();

        $url = $_SERVER['REQUEST_URI']

        header('Location: https://seller.'.$url);
    }*/
    
    public function initContent() {
        
        parent::initContent();
        
        $this->template = Configuration::get('MARKETPLACE_SEARCHBAR_TEMPLATE');
        $this->template_item = Configuration::get('MARKETPLACE_SEARCHBAR_TEMPLATE_ITEM');

        $id_seller = Seller::getSellerByCustomer($this->context->cookie->id_customer);
        
        $seller = new Seller($id_seller);

        $param = array('id_seller' => $seller->id, 'link_rewrite' => $seller->link_rewrite);                
        
        $url_seller_profile = $this->module->getmarketplaceLink('marketplace_seller_rule', $param);
        
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

        $templatePath = _PS_ROOT_DIR_ .'/modules/marketplace/views/templates/front/list/'.$this->template_item;
        
        $templateContent = file_get_contents($templatePath);
        
        $this->context->smarty->assign(array(
            'search_controller_url' => $this->context->link->getPageLink('search', null, null, null, false, null, true),
            'list_item_template' =>  $templateContent,
            'search_string' => '',
            'sellon_url' => $this->context->link->getModuleLink('marketplace','sellon'),

        ));

        $this->context->smarty->assign(array(
            'show_orders' => Configuration::get('MARKETPLACE_SHOW_ORDERS'),
            'show_import_product' => Configuration::get('MARKETPLACE_SELLER_IMPORT_PROD'),
            'show_edit_seller_account' => Configuration::get('MARKETPLACE_SHOW_EDIT_ACCOUNT'),
            'show_contact' => Configuration::get('MARKETPLACE_SHOW_CONTACT'),
            'show_manage_orders' => Configuration::get('MARKETPLACE_SHOW_MANAGE_ORDERS'),
            'show_manage_carriers' => Configuration::get('MARKETPLACE_SHOW_MANAGE_CARRIER'),
            'show_dashboard' => Configuration::get('MARKETPLACE_SHOW_DASHBOARD'),
            'show_menu_top' => Configuration::get('MARKETPLACE_MENU_TOP'),
            'show_import_product' => Configuration::get('MARKETPLACE_SELLER_IMPORT_PROD'),
            'show_menu_options' => Configuration::get('MARKETPLACE_MENU_OPTIONS'),
            'seller' => $seller,
            'seller_link' => $url_seller_profile,
            'mesages_not_readed' => SellerIncidenceMessage::getNumMessagesNotReadedBySeller($id_seller),
            'incidences' => $incidences,
            'content_only' => 1,
            'menu' => 'module:marketplace/views/templates/front/sellermenu.tpl',
        ));

        if (version_compare(_PS_VERSION_, '1.6.0', '<')) 
            $this->context->smarty->assign('version', 15);   
        else
            $this->context->smarty->assign('version', 16);
        
        //$this->setTemplate('searchproduct.tpl');
        $this->setTemplate('module:marketplace/views/templates/front/searchproduct.tpl');
    }
    
    
}