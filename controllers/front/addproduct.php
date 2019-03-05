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

class marketplaceAddproductModuleFrontController extends ModuleFrontController
{
    public $ssl = true;
    public $categoryTree;
    public $exclude;

    public function setMedia() {
        parent::setMedia();
        $this->addJqueryUI('ui.datepicker');	
    }
    
    protected function ajaxProcessSearchCategory()
    {
        $key = Tools::getValue('key');
        $search_data = '';
        
        $result_search = Db::getInstance()->ExecuteS('SELECT c.id_category, cl.name
                                                    FROM '._DB_PREFIX_.'category c
                                                    LEFT JOIN '._DB_PREFIX_.'category_lang cl ON (cl.id_category = c.id_category AND cl.id_lang = '.(int)$this->context->language->id.')
                                                    LEFT JOIN '._DB_PREFIX_.'seller_category sc ON (sc.id_category = c.id_category)
                                                    WHERE cl.name LIKE "%'.pSQL($key).'%" AND c.active = 1 AND sc.id_category != ""');
        if ($result_search) {
            foreach ($result_search as $category) {
                $search_data .= '<div class="suggest-element" id="category_'.$category['id_category'].'" data="'.$category['id_category'].'">';
                $search_data .= $category['name'];
                $search_data .= '</div>';
            }
        }
        
        die($search_data);
    }
    
    public function postProcess() {
        
        $languages = Language::getLanguages();
        $id_lang = (int)$this->context->language->id;
        
        $id_seller = Seller::getSellerByCustomer((int)$this->context->cookie->id_customer);
        $seller = new Seller($id_seller);
        
        $params = array('id_seller' => $seller->id);
        
        Hook::exec('actionMarketplaceBeforeAddProduct', $params);
        
        if (Tools::isSubmit('submitAddProduct')) {  
            //d($_POST);            
            $url_images = array();         
            $name = pSQL(Tools::getValue('name_'.$id_lang));
            $reference = pSQL(Tools::getValue('reference'));
            $ean13 = pSQL(Tools::getValue('ean13'));
            $upc = pSQL(Tools::getValue('upc'));
            $width = (float)Tools::getValue('width');
            $height = (float)Tools::getValue('height');
            $depth = (float)Tools::getValue('depth');
            $weight = (float)Tools::getValue('weight');
            $quantity = (int)Tools::getValue('quantity');
            $minimal_quantity = (int)Tools::getValue('minimal_quantity');
            $available_now = pSQL(Tools::getValue('available_now'));
            $available_later = pSQL(Tools::getValue('available_later'));
            $available_date = pSQL(Tools::getValue('available_date'));
            $price = (float)Tools::getValue('price');
            $specific_price = (float)Tools::getValue('specific_price');
            $categories = Tools::getValue('categories');
            $new_manufacturer = pSQL(Tools::getValue('new_manufacturer'));
            $new_supplier = pSQL(Tools::getValue('new_supplier'));
            $additional_shipping_cost = (float)Tools::getValue('additional_shipping_cost');
            $id_tax = (int)Tools::getValue('id_tax');

            if ($minimal_quantity == '')
                $minimal_quantity = 1;

            if ($name == '' || !Validate::isCatalogName($name))
                $this->error[] = $this->module->l('Name product is incorrect.', 'addproduct');
            
            if ($reference != '' && !Validate::isReference($reference))
                $this->error[] = $this->module->l('Reference is incorrect.', 'addproduct');
            
            if ($ean13 != '' && !Validate::isEan13($ean13))
                $this->error[] = $this->module->l('EAN13 is incorrect.', 'addproduct');
            
            if ($upc != '' && !Validate::isUPC($upc))
                $this->error[] = $this->module->l('UPC is incorrect.', 'addproduct');
            
            if ($width != '' && !Validate::isFloat($width))
                $this->error[] = $this->module->l('Width is incorrect.', 'addproduct');
            
            if ($height != '' && !Validate::isFloat($height))
                $this->error[] = $this->module->l('Height is incorrect.', 'addproduct');
            
            if ($depth != '' && !Validate::isFloat($depth))
                $this->error[] = $this->module->l('Depth is incorrect.', 'addproduct');
            
            if ($weight != '' && !Validate::isFloat($weight))
                $this->error[] = $this->module->l('Weight is incorrect.', 'addproduct');
            
            if ($price == '' || !Validate::isPrice($price))
                $this->error[] = $this->module->l('Price is incorrect.', 'addproduct');
            
            if ($specific_price != 0 && !Validate::isPrice($specific_price))
                $this->error[] = $this->module->l('Offer price is incorrect.', 'addproduct');
            
            if ($specific_price != 0 && $specific_price > $price)
                $this->error[] = $this->module->l('Offer price is bigger than price.', 'addproduct');
            
            if ($quantity != '' && !Validate::isInt($quantity))
                $this->error[] = $this->module->l('Quantity is incorrect.', 'addproduct');
            
            if (($minimal_quantity != '' && !Validate::isInt($minimal_quantity)) || ($minimal_quantity != '' && $minimal_quantity < 1))
                $this->error[] = $this->module->l('Minimal quantity is incorrect.', 'addproduct');
            
            if (Configuration::get('MARKETPLACE_SHOW_CATEGORIES') == 1 && !is_array($categories)) 
                $this->error[] = $this->module->l('You must select at least one category.', 'addproduct');
            
            if (Configuration::get('MARKETPLACE_SHOW_ATTRIBUTES') == 1) {
                if (Tools::getValue('combination_price') > 0) {
                    foreach (Tools::getValue('combination_price') as $combination_price) {
                        if (!Validate::isFloat($combination_price))
                            $this->error[] = $this->module->l('Combination price is incorrect.', 'addproduct');
                    }
                }
                
                if (Tools::getValue('combination_weight')) {
                    foreach (Tools::getValue('combination_weight') as $combination_weight) {
                        if (!Validate::isFloat($combination_weight))
                            $this->error[] = $this->module->l('Combination weight is incorrect.', 'addproduct');
                    }
                }
                
                if (Tools::getValue('combination_qty')) {
                    foreach (Tools::getValue('combination_qty') as $combination_qty) {
                        if (!Validate::isInt($combination_qty))
                            $this->error[] = $this->module->l('Combination quantity is incorrect.', 'addproduct');
                    }
                }
            }
            
            if(Configuration::get('MARKETPLACE_SHOW_IMAGES') == 1) {
                $images = count($_FILES['images']['name']);
                
                if ($images <= Configuration::get('MARKETPLACE_MAX_IMAGES')) {
                    for ($i=1; $i<=Configuration::get('MARKETPLACE_MAX_IMAGES'); $i++) {
                        if ($_FILES['images']['name'][$i] != "") {
                            if ((($_FILES['images']['type'][$i] == "image/pjpeg") || 
                                    ($_FILES['images']['type'][$i] == "image/jpeg") || 
                                    ($_FILES['images']['type'][$i] == "image/png")) && 
                                    ($_FILES['images']['size'][$i] < $this->return_bytes(ini_get('post_max_size')))) {
                                $url_images[$i] = $_FILES['images']['tmp_name'][$i];
                            }
                            else {
                                $this->error[] = $this->module->l('The image format is incorrect or max size to upload is', 'addproduct').' '.ini_get('post_max_size');
                            }
                        }
                        else {
                            $url_images[$i] = '';
                        }
                    }
                }
                else {
                    $this->error[] = $this->module->l('The maxim images to upload is', 'addproduct').' '.Configuration::get('MARKETPLACE_MAX_IMAGES');
                }
            }
            
            if ($new_manufacturer != '' && !Validate::isCatalogName($new_manufacturer)) 
                $this->error[] = $this->module->l('Name manufacturer is incorrect.', 'addproduct');
            
            if ($new_supplier != '' && !Validate::isCatalogName($new_supplier)) 
                $this->error[] = $this->module->l('Name supplier is incorrect.', 'addproduct');
            
            if (count($this->error) > 0) {   
                $name = array();
                $short_description = array();
                $description = array();
                $meta_keywords2 = array();
                $meta_title2 = array();
                $meta_description2 = array();
                $link_rewrite2 = array();
                foreach ($languages as $language) {
                    $name[$language['id_lang']] = Tools::getValue('name_'.$language['id_lang']);
                    $short_description[$language['id_lang']] = Tools::getValue('short_description_'.$language['id_lang']);
                    $description[$language['id_lang']] = Tools::getValue('description_'.$language['id_lang']);
                    $meta_keywords2[$language['id_lang']] = Tools::getValue('meta_keywords_'.$language['id_lang']);
                    $meta_title2[$language['id_lang']] = Tools::getValue('meta_title_'.$language['id_lang']);
                    $meta_description2[$language['id_lang']] = Tools::getValue('meta_description_'.$language['id_lang']);
                    $link_rewrite2[$language['id_lang']] = Tools::getValue('link_rewrite_'.$language['id_lang']);
                }
                
                $this->context->smarty->assign(array(
                    'error' => $this->error,
                    'name' => $name,
                    'reference' => $reference,
                    'ean13' => $ean13,
                    'upc' => $upc,
                    'width' => $width,
                    'height' => $height,
                    'depth' => $depth,
                    'weight' => $weight,
                    'additional_shipping_cost' => $additional_shipping_cost,
                    'condition' => Tools::getValue('condition'),
                    'quantity' => $quantity,
                    'minimal_quantity' => $minimal_quantity,
                    'available_now' => $available_now,
                    'available_later' => $available_later,
                    'available_date' => $available_date,
                    'price' => $price,
                    'specific_price' => $specific_price,
                    'id_tax' => Tools::getValue('id_tax'),
                    'short_description' => $short_description,
                    'description' => $description,
                    'meta_keywords2' => $meta_keywords2,
                    'meta_title2' => $meta_title2,
                    'meta_description2' => $meta_description2,
                    'link_rewrite2' => $link_rewrite2,
                    'id_manufacturer' => Tools::getValue('id_manufacturer'),
                    'id_supplier' => Tools::getValue('id_supplier'),
                )); 
            }
            else {

                $id_product = SellerProduct::import($_POST, $_FILES, $url_images, $id_lang);
                SellerProduct::associateSellerProduct($id_seller, $id_product);

                $params = array('id_seller' => $seller->id, 'id_product' => $id_product);
                
                Hook::exec('actionMarketplaceAfterAddProduct', $params);

                if (Configuration::get('MARKETPLACE_MODERATE_PRODUCT') && Configuration::get('MARKETPLACE_SEND_ADMIN_PRODUCT')) {
                    $id_seller_email = false;
                    $to = Configuration::get('MARKETPLACE_SEND_ADMIN');
                    $to_name = Configuration::get('PS_SHOP_NAME');
                    $from = Configuration::get('PS_SHOP_EMAIL');
                    $from_name = Configuration::get('PS_SHOP_NAME');

                    $template = 'base';
                    $reference = 'new-product';
                    $id_seller_email = SellerEmail::getIdByReference($reference);
                    
                    if ($id_seller_email) {
                        $seller_email = new SellerEmail($id_seller_email, Configuration::get('PS_LANG_DEFAULT'));
                        $product = new Product($id_product, false, Configuration::get('PS_LANG_DEFAULT'), $this->context->shop->id);
                        $vars = array("{shop_name}", "{seller_name}", "{product_name}");
                        $values = array(Configuration::get('PS_SHOP_NAME'), $seller->name, $product->name);
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
                
                Tools::redirect($this->context->link->getModuleLink('marketplace', 'sellerproducts',array(), true));
            }  
        }
        
        if (Tools::getValue('confirmation'))
            $this->context->smarty->assign(array('confirmation' => 1));
    }

    public function initContent() {
        
        parent::initContent();
        
        if (Tools::isSubmit('action'))
        {
            switch(Tools::getValue('action'))
            {
                case 'search_category':
                    $this->ajaxProcessSearchCategory();
                    break;
            }
        }
        
        $languages = Language::getLanguages();
        $id_lang = (int)$this->context->language->id;
        
        if(!$this->context->cookie->id_customer)
            Tools::redirect($this->context->link->getPageLink('my-account', true));
        
        $is_seller = Seller::isSeller($this->context->cookie->id_customer, $this->context->shop->id);
        if (!$is_seller)
            Tools::redirect($this->context->link->getPageLink('my-account', true));
        
        $id_seller = Seller::getSellerByCustomer($this->context->cookie->id_customer);
        $seller = new Seller($id_seller);
        
        if ($seller->active == 0)
            Tools::redirect($this->context->link->getPageLink('my-account', true));
        
        $param = array('id_seller' => $seller->id, 'link_rewrite' => $seller->link_rewrite);			
        $url_seller_profile = $this->module->getmarketplaceLink('marketplace_seller_rule', $param);
        
        if (Configuration::get('MARKETPLACE_SHOW_IMAGES') == 1) {
            $imageDimensions = SellerProduct::getImageDimensions();
            $dimensions = $imageDimensions['width'].'x'.$imageDimensions['height'].'px';
            $this->context->smarty->assign(array(
                'max_images' => Configuration::get('MARKETPLACE_MAX_IMAGES'),
                'max_dimensions' => $dimensions,
            ));       
        }
        
        if (Configuration::get('MARKETPLACE_SHOW_TAX') == 1) {
            //$this->context->smarty->assign('taxes', Tax::getTaxes($this->context->language->id));
            $this->context->smarty->assign('taxes', TaxRulesGroup::getTaxRulesGroups(true));
        }

        if (Configuration::get('MARKETPLACE_SHOW_SHIP_PRODUCT') == 1) {
            if (Configuration::get('MARKETPLACE_SHOW_MANAGE_CARRIER') == 1) 
                $carriers = SellerTransport::getCarriers($this->context->language->id, true, $id_seller);
            else
                $carriers = Carrier::getCarriers($this->context->language->id, true, false, false, null, 5);
            
            $this->context->smarty->assign('carriers', $carriers);
        }

        if (Configuration::get('MARKETPLACE_SHOW_CATEGORIES') == 1) {
            if (version_compare(_PS_VERSION_, '1.6.0', '<')) 
                $categories = CategoryTree::getNestedCategories(null, $this->context->language->id);    
            else
                $categories = Category::getNestedCategories(null, $this->context->language->id);

            $categoryTree = '<ul id="tree1">'.CategoryTree::generateCheckboxesCategories($categories).'</ul>';
            $this->context->smarty->assign('categoryTree', $categoryTree);
        }
        
        if (Configuration::get('MARKETPLACE_SHOW_SUPPLIERS') == 1) {
            $this->context->smarty->assign('suppliers', Supplier::getSuppliers());
        }
        
        if (Configuration::get('MARKETPLACE_SHOW_MANUFACTURERS') == 1) {
            $this->context->smarty->assign('manufacturers', Manufacturer::getManufacturers());
        }
        
        if (Configuration::get('MARKETPLACE_SHOW_FEATURES') == 1) {
            $features = Feature::getFeatures($this->context->language->id, (Shop::isFeatureActive() && Shop::getContext() == Shop::CONTEXT_SHOP));

            foreach ($features as $k => $tab_features)
            {
                $features[$k]['current_item'] = false;
                $features[$k]['val'] = array();

                $custom = true;

                $features[$k]['featureValues'] = FeatureValue::getFeatureValuesWithLang($this->context->language->id, (int)$tab_features['id_feature']);
                if (count($features[$k]['featureValues']))
                    foreach ($features[$k]['featureValues'] as $value)
                        if ($features[$k]['current_item'] == $value['id_feature_value'])
                            $custom = false;

                if ($custom)
                    $features[$k]['val'] = FeatureValue::getFeatureValueLang($features[$k]['current_item']);
            }

            $this->context->smarty->assign('features', $features);
        }
        
        if (Configuration::get('MARKETPLACE_SHOW_ATTRIBUTES') == 1) {
            $attribute_groups = AttributeGroup::getAttributesGroups($this->context->language->id);
            if (count($attribute_groups) > 0) {
                $counter = 0;
                foreach ($attribute_groups as $ag) {
                    $attribute_groups[$counter]['options'] = AttributeGroup::getAttributes($this->context->language->id, $ag['id_attribute_group']);
                    $counter++;
                }
            }
            
            $this->context->smarty->assign(array(
                'attribute_groups' => $attribute_groups,
                'first_options' => AttributeGroup::getAttributes($this->context->language->id, $attribute_groups[0]['id_attribute_group']),
            ));
        }

        $countneworder = SellerOrder::getVisitedOrdersSeller($id_seller,$this->context->language->id);

        $incidences = SellerIncidence::getIncidencesBySeller($id_seller);

            if ($incidences != false) {
                $counter = 0;
                foreach ($incidences as $i) {
                    $product_a = new Product((int)$i['id_product'], (int)$this->context->language->id, (int)$this->context->shop->id);
                    $incidences[$counter]['product_name'] = $product_a->name;
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

        $name = array();
        $short_description = array();
        $description = array();
        $meta_keywords2 = array();
        $meta_title2 = array();
        $meta_description2 = array();
        $link_rewrite2 = array();

        $name[0] = '';
        $name[1] = '';
        $name[2] = '';
        $this->context->smarty->assign(array(
            'short_description' => null,
            'description' => null,
            'meta_keywords2' => null,
            'meta_title2' => null,
            'meta_description2' => null,
            'link_rewrite2' => null,
            'name' => null,
            'show_reference' => Configuration::get('MARKETPLACE_SHOW_REFERENCE'),
            'show_ean13' => Configuration::get('MARKETPLACE_SHOW_EAN13'),
            'show_upc' => Configuration::get('MARKETPLACE_SHOW_UPC'),
            'show_width' => Configuration::get('MARKETPLACE_SHOW_WIDTH'),
            'show_height' => Configuration::get('MARKETPLACE_SHOW_HEIGHT'),
            'show_depth' => Configuration::get('MARKETPLACE_SHOW_DEPTH'),
            'show_weight' => Configuration::get('MARKETPLACE_SHOW_WEIGHT'),
            'show_shipping_product' => Configuration::get('MARKETPLACE_SHOW_SHIP_PRODUCT'),
            'show_condition' => Configuration::get('MARKETPLACE_SHOW_CONDITION'),
            'show_quantity' => Configuration::get('MARKETPLACE_SHOW_QUANTITY'),
            'show_minimal_quantity' => Configuration::get('MARKETPLACE_SHOW_MINIMAL_QTY'),
            'show_available_now' => Configuration::get('MARKETPLACE_SHOW_AVAILABLE_NOW'),
            'show_available_later' => Configuration::get('MARKETPLACE_SHOW_AVAILABLE_LAT'),
            'show_available_date' => Configuration::get('MARKETPLACE_SHOW_AVAILABLE_DATE'),
            'show_price' => Configuration::get('MARKETPLACE_SHOW_PRICE'),
            'show_offer_price' => Configuration::get('MARKETPLACE_SHOW_OFFER_PRICE'),
            'show_tax' => Configuration::get('MARKETPLACE_SHOW_TAX'),
            'show_desc_short' => Configuration::get('MARKETPLACE_SHOW_DESC_SHORT'),
            'show_desc' => Configuration::get('MARKETPLACE_SHOW_DESC'),
            'show_meta_keywords' => Configuration::get('MARKETPLACE_SHOW_META_KEYWORDS'),
            'show_meta_title' => Configuration::get('MARKETPLACE_SHOW_META_TITLE'),
            'show_meta_desc' => Configuration::get('MARKETPLACE_SHOW_META_DESC'),
            'show_link_rewrite' => Configuration::get('MARKETPLACE_SHOW_LINK_REWRITE'),
            'show_images' => Configuration::get('MARKETPLACE_SHOW_IMAGES'),
            'max_images' => Configuration::get('MARKETPLACE_MAX_IMAGES'),
            'show_suppliers' => Configuration::get('MARKETPLACE_SHOW_SUPPLIERS'),
            'show_new_suppliers' => Configuration::get('MARKETPLACE_NEW_SUPPLIERS'),
            'show_manufacturers' => Configuration::get('MARKETPLACE_SHOW_MANUFACTURERS'),
            'show_new_manufacturers' => Configuration::get('MARKETPLACE_NEW_MANUFACTURERS'),
            'show_categories' => Configuration::get('MARKETPLACE_SHOW_CATEGORIES'),
            'show_features' => Configuration::get('MARKETPLACE_SHOW_FEATURES'),
            'show_attributes' => Configuration::get('MARKETPLACE_SHOW_ATTRIBUTES'),
            'show_virtual' => Configuration::get('MARKETPLACE_SHOW_VIRTUAL'),
            'moderate' => Configuration::get('MARKETPLACE_MODERATE_PRODUCT'),
            'show_orders' => Configuration::get('MARKETPLACE_SHOW_ORDERS'),
            'show_import_product' => Configuration::get('MARKETPLACE_SELLER_IMPORT_PROD'),
            'show_edit_seller_account' => Configuration::get('MARKETPLACE_SHOW_EDIT_ACCOUNT'),
            'show_contact' => Configuration::get('MARKETPLACE_SHOW_CONTACT'),
            'show_manage_orders' => Configuration::get('MARKETPLACE_SHOW_MANAGE_ORDERS'),
            'show_manage_carriers' => Configuration::get('MARKETPLACE_SHOW_MANAGE_CARRIER'),
            'show_dashboard' => Configuration::get('MARKETPLACE_SHOW_DASHBOARD'),
            'show_menu_top' => Configuration::get('MARKETPLACE_MENU_TOP'),
            'show_menu_options' => Configuration::get('MARKETPLACE_MENU_OPTIONS'),
            'marketplace_theme' => Configuration::get('MARKETPLACE_THEME'),
            'show_tabs' => Configuration::get('MARKETPLACE_TABS'),
            'seller_link' => $url_seller_profile,
            'languages' => $languages,
            'id_lang' => $id_lang,
            'attachment_maximun_size' => Configuration::get('PS_ATTACHMENT_MAXIMUM_SIZE'),
            'token' => Configuration::get('MARKETPLACE_TOKEN'),
            'seller_commission' => SellerCommision::getCommisionBySeller($id_seller),
            'sign' => $this->context->currency->sign,
            'id_product' => 0,
            'has_attributes' => 0,
            'mesages_not_readed' => SellerIncidenceMessage::getNumMessagesNotReadedBySeller($id_seller),
            'countneworder' => $countneworder,
            'seller' => $seller,
            'incidences' => $incidences,
            'content_only' => 1,
            'menu' => 'module:marketplace/views/templates/front/sellermenu.tpl',
        ));
        
        if (version_compare(_PS_VERSION_, '1.6.0', '<')) 
            $this->context->smarty->assign('version', 15);   
        else
            $this->context->smarty->assign('version', 16);
        
        //$this->setTemplate('addproduct.tpl');
        $this->setTemplate('module:marketplace/views/templates/front/addproduct.tpl');
    }
    
    public function return_bytes($val) {
        $val = trim($val);
        $last = Tools::strtolower($val[Tools::strlen($val)-1]);
        switch($last) {
            case 'g':
                $val *= 1024;
            case 'm':
                $val *= 1024;
            case 'k':
                $val *= 1024;
        }

        return $val;
    }
}