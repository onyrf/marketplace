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

class marketplaceEditproductModuleFrontController extends ModuleFrontController
{
    public $ssl = true;
    public $categoryTree;
    public $exclude;

    public function setMedia() {
        parent::setMedia();
        $this->addJqueryUI('ui.datepicker');
    }
    
    protected function ajaxProcessDeleteImage()
    {
        $id_image = Tools::getValue('id_image');
        $image = new Image($id_image);
        if ($image->delete())
            die($this->module->l('The image has been deleted ok.', 'editproduct'));
    }
    
    protected function ajaxProcessSelectAttributeGroup()
    {
        $html = '';
        $id_attribute_group = Tools::getValue('id_attribute_group');
        $options = AttributeGroup::getAttributes((int)Context::getContext()->language->id, (int)$id_attribute_group);
        foreach ($options as $option)         
            $html .= '<option value="'.$option['id_attribute'].'">'.$option['name'].'</option>';
        die($html);
    }
    
    protected function ajaxProcessDeleteCombination()
    {
        $id_product = (int)Tools::getValue('id_product');
        $id_product_attribute = (int)Tools::getValue('id_product_attribute');
        $product = new Product($id_product);
        $product->deleteAttributeCombination($id_product_attribute);
    }
    
    public function postProcess() {

        $id_lang = (int)$this->context->language->id;
        
        $id_product = (int)Tools::getValue('id_product');
        $product = new Product($id_product, false, $id_lang, (int)$this->context->shop->id);
        
        $id_seller = Seller::getSellerByCustomer((int)$this->context->cookie->id_customer);
        
        $seller = new Seller($id_seller);
        
        $params = array('id_seller' => $id_seller, 'id_product' => $id_product);
        
        Hook::exec('actionMarketplaceBeforeUpdateProduct', $params);
        
        if (Tools::isSubmit('submitAddProduct')) {

            $url_images = array();
            
            $name = Tools::getValue('name_'.$id_lang);
            $reference = pSQL(Tools::getValue('reference'));
            $ean13 = pSQL(Tools::getValue('ean13'));
            $upc = pSQL(Tools::getValue('upc'));
            $width = (float)Tools::getValue('width');
            $height = (float)Tools::getValue('height');
            $depth = (float)Tools::getValue('depth');
            $weight = (float)Tools::getValue('weight');
            $quantity = (int)Tools::getValue('quantity');
            $minimal_quantity = (int)Tools::getValue('minimal_quantity');
            $price = (float)Tools::getValue('price');
            $specific_price = (float)Tools::getValue('specific_price') + (float)Tools::getValue('additional_shipping_cost');
            $additional_shipping_cost = (float)Tools::getValue('additional_shipping_cost');

            $categories = Tools::getValue('categories');
            
            $new_manufacturer = Tools::getValue('new_manufacturer');
            $new_supplier = Tools::getValue('new_supplier');

            $legends = Tools::getValue('legends');

            $short_desc = Tools::getValue('short_description_'.$id_lang);

            $desc = Tools::getValue('description_'.$id_lang);
            
            $meta_title = Tools::getValue('meta_title_'.$id_lang);

            $meta_keywords = Tools::getValue('meta_keywords_'.$id_lang);

            $meta_description = Tools::getValue('meta_description_'.$id_lang);

            if ($minimal_quantity == '')
                $minimal_quantity = 1;
            
            if ($name == '' || !Validate::isCatalogName($name))
                $this->error[] = $this->module->l('Name product is incorrect.', 'editproduct');
            
            if ($reference != '' && !Validate::isReference($reference))
                $this->error[] = $this->module->l('Reference is incorrect.', 'editproduct');
            
            if ($ean13 != '' && !Validate::isEan13($ean13))
                $this->error[] = $this->module->l('EAN13 is incorrect.', 'editproduct');
            
            if ($upc != '' && !Validate::isUPC($upc))
                $this->error[] = $this->module->l('UPC is incorrect.', 'editproduct');
            
            if ($width != '' && !Validate::isFloat($width))
                $this->error[] = $this->module->l('Width is incorrect.', 'editproduct');
            
            if ($height != '' && !Validate::isFloat($height))
                $this->error[] = $this->module->l('Height is incorrect.', 'editproduct');
            
            if ($depth != '' && !Validate::isFloat($depth))
                $this->error[] = $this->module->l('Depth is incorrect.', 'editproduct');
            
            if ($weight != '' && !Validate::isFloat($weight))
                $this->error[] = $this->module->l('Weight is incorrect.', 'editproduct');
            
            if ($price == '' || !Validate::isPrice($price))
                $this->error[] = $this->module->l('Price is incorrect.', 'editproduct');
            
            if ($specific_price != 0 && !Validate::isPrice($specific_price))
                $this->error[] = $this->module->l('Offer price is incorrect.', 'editproduct');
            
            if ($specific_price != 0 && $specific_price > $price)
                $this->error[] = $this->module->l('Offer price is bigger than price.', 'editproduct');
            
            if ($quantity != '' && !Validate::isInt($quantity))
                $this->error[] = $this->module->l('Quantity is incorrect.', 'editproduct');
            
            if (($minimal_quantity != '' && !Validate::isInt($minimal_quantity)) || ($minimal_quantity != '' && $minimal_quantity < 1))
                $this->error[] = $this->module->l('Minimal quantity is incorrect.', 'editproduct');
            
            if (Configuration::get('MARKETPLACE_SHOW_CATEGORIES') == 1 && !is_array($categories)) 
                $this->error[] = $this->module->l('You must select the category default.', 'editproduct');
            
            if (Configuration::get('MARKETPLACE_SHOW_ATTRIBUTES') == 1) {
                if (Tools::getValue('combination_price') > 0) {
                    foreach (Tools::getValue('combination_price') as $combination_price) {
                        if (!Validate::isFloat($combination_price))
                            $this->error[] = $this->module->l('Combination price is incorrect.', 'editproduct');
                    }
                }
                
                if (Tools::getValue('combination_weight')) {
                    foreach (Tools::getValue('combination_weight') as $combination_weight) {
                        if (!Validate::isFloat($combination_weight))
                            $this->error[] = $this->module->l('Combination weight is incorrect.', 'editproduct');
                    }
                }
                
                if (Tools::getValue('combination_qty')) {
                    foreach (Tools::getValue('combination_qty') as $combination_qty) {
                        if (!Validate::isInt($combination_qty))
                            $this->error[] = $this->module->l('Combination quantity is incorrect.', 'editproduct');
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
                                $this->error[] = $this->module->l('The image format is incorrect or max size to upload is', 'editproduct').' '.ini_get('post_max_size');
                            }
                        }
                        else {
                            $url_images[$i] = '';
                        }
                    }
                }
                else {
                    $this->error[] = $this->module->l('The maxim images to upload is', 'editproduct').' '.Configuration::get('MARKETPLACE_MAX_IMAGES');
                }
            }
            
            if ($new_manufacturer != '' && !Validate::isCatalogName($new_manufacturer)) 
                $this->error[] = $this->module->l('Name manufacturer is incorrect.', 'editproduct');
            
            if ($new_supplier != '' && !Validate::isCatalogName($new_supplier)) 
                $this->error[] = $this->module->l('Name supplier is incorrect.', 'editproduct');
            
            if (count($this->error) > 0) {
                $this->context->smarty->assign(array('error' => $this->error));     
            }
            else {
                //$id_product = SellerProduct::import($_POST, $_FILES, $url_images, $this->context->language->id);

                $product->name = trim(htmlspecialchars($name));
                $product->reference = $reference;
                $product->ean13 = $ean13;
                $product->upc = $upc;
                $product->width = $width;
                $product->height = $height;
                $product->depth = $depth;
                $product->weight = $weight;
                $product->price = $price;
                $product->specific_price = $specific_price;
                $product->quantity = $quantity;
                $product->additional_shipping_cost = $additional_shipping_cost;

                $product->minimal_quantity = $minimal_quantity;
                $product->description_short = trim($short_desc);
                
                $product->description = trim($desc);

                $product->meta_title = trim($meta_title);

                $product->meta_keywords = trim($meta_keywords);

                $product->meta_description  = trim($meta_description);

                $product->id_tax_rules_group = (int)Tools::getValue('id_tax');

                // && $legends[$i] != ''

                for ($i=1; $i<=Configuration::get('MARKETPLACE_MAX_IMAGES'); $i++) {
                    if ($url_images[$i] != '') {
                        $id_image = SellerProduct::getIdImageByPosition($id_product, $i);

                        if ($id_image > 0)
                            $image = new Image($id_image);
                        else
                            $image = new Image();

                        $image->id_product = $id_product;
                        $image->position = $i;

                        if ($i == 1)
                            $image->cover = 1;
                        else
                            $image->cover = 0;

                        if ($legends[$i]) {
                            foreach (Language::getLanguages() as $language) {
                                if ($legends[$i] != '')
                                    $image->legend[$language['id_lang']] = Tools::stripslashes(trim(pSQL($legends[$i])));    
                                else
                                    $image->legend[$language['id_lang']] = Tools::stripslashes(trim(pSQL($legends[$i])));
                            }
                        }
                        else
                            $image->legend = SellerProduct::createMultiLangField($name);

                        if ($id_image > 0)
                            $image->update();
                        else
                            $image->add();

                        $image->associateTo($this->context->shop->id);
                    
                        if ($url_images[$i] != '') 
                            SellerProduct::copyImg($id_product, $image->id, $url_images[$i]);   
                    }
                }


                //StockAvailable::setQuantity($id_product, 0, $quantity, $this->context->shop->id);

                $sql = 'update '._DB_PREFIX_.'stock_available set quantity='.$quantity.' where id_product = '.$id_product;

                Db::getInstance()->Execute($sql);

                $product->save();


                $params = array('id_product' => $id_product);
                
                Hook::exec('actionMarketplaceAfterUpdateProduct', $params);
                
                if (Configuration::get('MARKETPLACE_MODERATE_PRODUCT') && Configuration::get('MARKETPLACE_SEND_ADMIN_PRODUCT')) {
                    $id_seller_email = false;
                    $to = Configuration::get('MARKETPLACE_SEND_ADMIN');
                    $to_name = Configuration::get('PS_SHOP_NAME');
                    $from = Configuration::get('PS_SHOP_EMAIL');
                    $from_name = Configuration::get('PS_SHOP_NAME');

                    $template = 'base';
                    $reference = 'edit-product';
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
                
                Tools::redirect($this->context->link->getModuleLink('marketplace', 'sellerproducts', array('confirmation' => 1), true));
            }
        }
        
        if (Tools::getValue('confirmation'))
            $this->context->smarty->assign(array('confirmation' => 1));
        
        if (Tools::isSubmit('download'))
            $this->downloadProduct();
    }

    public function initContent() {
        
        parent::initContent();
        
        if (Tools::isSubmit('action'))
        {
            switch(Tools::getValue('action'))
            {
                case 'select_attribute_group':
                    $this->ajaxProcessSelectAttributeGroup();
                    break;
                case 'delete_combination':
                    $this->ajaxProcessDeleteCombination();
                    break;
                case 'delete_image':
                    $this->ajaxProcessDeleteImage();
                    break;
            }
        }
        
        $languages = Language::getLanguages();
        $id_lang = (int)$this->context->language->id;

        if(!$this->context->cookie->id_customer)
            Tools::redirect($this->context->link->getPageLink('my-account', true));

        $is_seller = Seller::isSeller($this->context->cookie->id_customer, $this->context->shop->id);
        $id_seller = Seller::getSellerByCustomer($this->context->cookie->id_customer);
        
        if (!$is_seller)
            Tools::redirect($this->context->link->getPageLink('my-account', true));
        
        $seller = new Seller($id_seller);
        
        if ($seller->active == 0)
            Tools::redirect($this->context->link->getPageLink('my-account', true));
        
        if (Configuration::get('MARKETPLACE_SHOW_EDIT_PRODUCT') == 0)
            Tools::redirect($this->context->link->getPageLink('my-account', true));
        
        $id_product = (int)Tools::getValue('id_product');
        $product = new Product($id_product);
        
        $param = array('id_seller' => $id_seller, 'link_rewrite' => $seller->link_rewrite);	
        
        $url_seller_profile = $this->module->getmarketplaceLink('marketplace_seller_rule', $param);
        
        if (Configuration::get('MARKETPLACE_SHOW_SHIP_PRODUCT') == 1) {
            if (Configuration::get('MARKETPLACE_SHOW_MANAGE_CARRIER') == 1) 
                $carriers = SellerTransport::getCarriers($this->context->language->id, true, $id_seller);
            else
                $carriers = Carrier::getCarriers($this->context->language->id, true, false, false, null, 5);
        
            $product_carriers = $product->getCarriers();
            $counter = 0;
            foreach ($carriers as $c) {
                $carriers[$counter]['checked'] = 0;
                foreach ($product_carriers as $pc) {
                    if ($c['id_reference'] == $pc['id_reference'])
                        $carriers[$counter]['checked'] = 1;
                    
                }
                $counter++;
            }
            
            $this->context->smarty->assign('carriers', $carriers);
        }
        
        if (Configuration::get('MARKETPLACE_SHOW_IMAGES') == 1) {
            $images = $product->getImages((int)$this->context->language->id);
            $imageDimensions = SellerProduct::getImageDimensions();
            $dimensions = $imageDimensions['width'].'x'.$imageDimensions['height'].'px';
            $this->context->smarty->assign(array(
                'max_images' => Configuration::get('MARKETPLACE_MAX_IMAGES'),
                'max_dimensions' => $dimensions,
                'images' => $images
            ));       
        }
        
        if (Configuration::get('MARKETPLACE_SHOW_TAX') == 1) {
            //$this->context->smarty->assign('taxes', Tax::getTaxes($this->context->language->id));
            $this->context->smarty->assign('taxes', TaxRulesGroup::getTaxRulesGroups(true));
        }

        if (Configuration::get('MARKETPLACE_SHOW_CATEGORIES') == 1) {
            if (version_compare(_PS_VERSION_, '1.6.0', '<')) 
                $categories = CategoryTree::getNestedCategories(null, $this->context->language->id);    
            else
                $categories = Category::getNestedCategories(null, $this->context->language->id);

            $categoryTree = '<ul id="tree1">'.CategoryTree::generateCheckboxesCategories($categories, $id_product).'</ul>';
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
                foreach ($product->getFeatures() as $tab_products)
                    if ($tab_products['id_feature'] == $tab_features['id_feature'])
                        $features[$k]['current_item'] = $tab_products['id_feature_value'];

                $features[$k]['featureValues'] = FeatureValue::getFeatureValuesWithLang((int)$this->context->language->id, (int)$tab_features['id_feature']);
                if (count($features[$k]['featureValues']))
                    foreach ($features[$k]['featureValues'] as $value)
                        if ($features[$k]['current_item'] == $value['id_feature_value'])
                            $custom = false;

                    if ($custom)
                        $features[$k]['val'] = FeatureValue::getFeatureValueLang($features[$k]['current_item']);
            }

            $this->context->smarty->assign('features', $features);
        }     
        
        $attributes = $product->getAttributesResume($this->context->language->id);
            
        if (is_array($attributes))
            $this->context->smarty->assign('has_attributes', 1);
        else
            $this->context->smarty->assign('has_attributes', 0);
        
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
                'first_options' => AttributeGroup::getAttributes((int)$this->context->language->id, (int)$attribute_groups[0]['id_attribute_group']), 
                'attributes' => $attributes
            ));
        }
        
        $categories = Product::getProductCategoriesFull($product->id);
        $categories_string = '';
        foreach ($categories as $c) {
            $categories_string .= $c['name'].', ';
        }
        $categories_string = Tools::substr($categories_string, 0, -2);
        
        $specificPrice = SpecificPrice::getByProductId($product->id);
        if ($specificPrice) {
            $specific_price = $product->price - $specificPrice[0]['reduction'];
            $this->context->smarty->assign('specific_price', $specific_price);
        }

        $incidences = SellerIncidence::getIncidencesBySeller($id_seller);

            if ($incidences != false) {
                $counter = 0;
                foreach ($incidences as $i) {
                    $product_m = new Product((int)$i['id_product'], (int)$this->context->language->id, (int)$this->context->shop->id);
                    $incidences[$counter]['product_name'] = $product_m->name;
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
        
        $this->context->smarty->assign(array(
            'form_edit' => $this->context->link->getModuleLink('marketplace', 'editproduct', array('id_product' => $id_product), true),
            'show_reference' => Configuration::get('MARKETPLACE_SHOW_REFERENCE'),
            'show_ean13' => Configuration::get('MARKETPLACE_SHOW_EAN13'),
            'show_upc' => Configuration::get('MARKETPLACE_SHOW_UPC'),
            'show_width' => Configuration::get('MARKETPLACE_SHOW_WIDTH'),
            'show_height' => Configuration::get('MARKETPLACE_SHOW_HEIGHT'),
            'show_depth' => Configuration::get('MARKETPLACE_SHOW_DEPTH'),
            'show_weight' => Configuration::get('MARKETPLACE_SHOW_WEIGHT'),
            'show_shipping_product' => Configuration::get('MARKETPLACE_SHOW_SHIP_PRODUCT'),
            'show_condition' => Configuration::get('MARKETPLACE_SHOW_CONDITION'),
            'show_transport' => Configuration::get('MARKETPLACE_SHOW_TRANSPORT'),            
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
            'show_import_product' => Configuration::get('MARKETPLACE_SELLER_IMPORT_PROD'),
            'show_orders' => Configuration::get('MARKETPLACE_SHOW_ORDERS'),
            'show_manage_orders' => Configuration::get('MARKETPLACE_SHOW_MANAGE_ORDERS'),
            'show_manage_carriers' => Configuration::get('MARKETPLACE_SHOW_MANAGE_CARRIER'),
            'show_dashboard' => Configuration::get('MARKETPLACE_SHOW_DASHBOARD'),
            'show_edit_seller_account' => Configuration::get('MARKETPLACE_SHOW_EDIT_ACCOUNT'),
            'show_contact' => Configuration::get('MARKETPLACE_SHOW_CONTACT'),
            'show_menu_top' => Configuration::get('MARKETPLACE_MENU_TOP'),
            'show_menu_options' => Configuration::get('MARKETPLACE_MENU_OPTIONS'),
            'show_tabs' => Configuration::get('MARKETPLACE_TABS'),
            'product' => $product,
            'real_quantity' => Product::getRealQuantity($id_product, 0, 0, $this->context->shop->id),
            'categories_string' => $categories_string,
            'categories_selected' => $categories,
            'seller_link' => $url_seller_profile,
            'languages' => $languages,
            'id_lang' => $id_lang,
            'attachment_maximun_size' => Configuration::get('PS_ATTACHMENT_MAXIMUM_SIZE'),
            'token' => Configuration::get('MARKETPLACE_TOKEN'),
            'is_virtual' => 0,
            'seller_commission' => SellerCommision::getCommisionBySeller($id_seller),
            'sign' => $this->context->currency->sign,
            'id_product' => $product->id,
            'mesages_not_readed' => SellerIncidenceMessage::getNumMessagesNotReadedBySeller($id_seller),
            'is_product_copy' => SellerProduct::hasProductCopyParent($id_product),
            'seller' => $seller,
            'content_only' => 1,
            'modules_dir' => __PS_BASE_URI__.'modules/',
            'menu' => 'module:marketplace/views/templates/front/sellermenu.tpl',
            'incidences' => $incidences,
        ));  
        
        if ($product->is_virtual == 1) {
            $id_product_download = ProductDownload::getIdFromIdProduct($product->id);
            $product_download = new ProductDownload($id_product_download);
            $filename = ProductDownload::getFilenameFromIdProduct($product->id);
            $display_filename = ProductDownload::getFilenameFromFilename($filename);
            
            $this->context->smarty->assign(array(
                'is_virtual' => $product->is_virtual,
                'filename' => $filename,
                'product_hash' => $product_download->getHash(),
                'display_filename' => $display_filename,
                'product_download_link' => $product_download->getHtmlLink(),
                'content_only' => 1,
                'modules_dir' => __PS_BASE_URI__.'modules/',
                'menu' => 'module:marketplace/views/templates/front/sellermenu.tpl',
            ));
        } 
        
        if (version_compare(_PS_VERSION_, '1.6.0', '<')) 
            $this->context->smarty->assign('version', 15);   
        else
            $this->context->smarty->assign('version', 16);
        
        Media::addJsDef(array('is_product_copy' => SellerProduct::hasProductCopyParent($id_product)));

        //$this->setTemplate('editproduct.tpl');
        $this->setTemplate('module:marketplace/views/templates/front/editproduct.tpl');
    }
    
    public function downloadProduct() {
            
        $filename = ProductDownload::getFilenameFromIdProduct(Tools::getValue('id_product'));
        $display_filename = ProductDownload::getFilenameFromFilename($filename);

        $file = _PS_DOWNLOAD_DIR_.$filename;
        $filename = $display_filename;

        $mimeType = false;

        if (empty($mimeType)) {
            $bName = basename($filename);
            $bName = explode('.', $bName);
            $bName = Tools::strtolower($bName[count($bName) - 1]);

            $mimeTypes = array(
            'ez' => 'application/andrew-inset',
            'hqx' => 'application/mac-binhex40',
            'cpt' => 'application/mac-compactpro',
            'doc' => 'application/msword',
            'oda' => 'application/oda',
            'pdf' => 'application/pdf',
            'ai' => 'application/postscript',
            'eps' => 'application/postscript',
            'ps' => 'application/postscript',
            'smi' => 'application/smil',
            'smil' => 'application/smil',
            'wbxml' => 'application/vnd.wap.wbxml',
            'wmlc' => 'application/vnd.wap.wmlc',
            'wmlsc' => 'application/vnd.wap.wmlscriptc',
            'bcpio' => 'application/x-bcpio',
            'vcd' => 'application/x-cdlink',
            'pgn' => 'application/x-chess-pgn',
            'cpio' => 'application/x-cpio',
            'csh' => 'application/x-csh',
            'dcr' => 'application/x-director',
            'dir' => 'application/x-director',
            'dxr' => 'application/x-director',
            'dvi' => 'application/x-dvi',
            'spl' => 'application/x-futuresplash',
            'gtar' => 'application/x-gtar',
            'hdf' => 'application/x-hdf',
            'js' => 'application/x-javascript',
            'skp' => 'application/x-koan',
            'skd' => 'application/x-koan',
            'skt' => 'application/x-koan',
            'skm' => 'application/x-koan',
            'latex' => 'application/x-latex',
            'nc' => 'application/x-netcdf',
            'cdf' => 'application/x-netcdf',
            'sh' => 'application/x-sh',
            'shar' => 'application/x-shar',
            'swf' => 'application/x-shockwave-flash',
            'sit' => 'application/x-stuffit',
            'sv4cpio' => 'application/x-sv4cpio',
            'sv4crc' => 'application/x-sv4crc',
            'tar' => 'application/x-tar',
            'tcl' => 'application/x-tcl',
            'tex' => 'application/x-tex',
            'texinfo' => 'application/x-texinfo',
            'texi' => 'application/x-texinfo',
            't' => 'application/x-troff',
            'tr' => 'application/x-troff',
            'roff' => 'application/x-troff',
            'man' => 'application/x-troff-man',
            'me' => 'application/x-troff-me',
            'ms' => 'application/x-troff-ms',
            'ustar' => 'application/x-ustar',
            'src' => 'application/x-wais-source',
            'xhtml' => 'application/xhtml+xml',
            'xht' => 'application/xhtml+xml',
            'zip' => 'application/zip',
            'au' => 'audio/basic',
            'snd' => 'audio/basic',
            'mid' => 'audio/midi',
            'midi' => 'audio/midi',
            'kar' => 'audio/midi',
            'mpga' => 'audio/mpeg',
            'mp2' => 'audio/mpeg',
            'mp3' => 'audio/mpeg',
            'aif' => 'audio/x-aiff',
            'aiff' => 'audio/x-aiff',
            'aifc' => 'audio/x-aiff',
            'm3u' => 'audio/x-mpegurl',
            'ram' => 'audio/x-pn-realaudio',
            'rm' => 'audio/x-pn-realaudio',
            'rpm' => 'audio/x-pn-realaudio-plugin',
            'ra' => 'audio/x-realaudio',
            'wav' => 'audio/x-wav',
            'pdb' => 'chemical/x-pdb',
            'xyz' => 'chemical/x-xyz',
            'bmp' => 'image/bmp',
            'gif' => 'image/gif',
            'ief' => 'image/ief',
            'jpeg' => 'image/jpeg',
            'jpg' => 'image/jpeg',
            'jpe' => 'image/jpeg',
            'png' => 'image/png',
            'tiff' => 'image/tiff',
            'tif' => 'image/tif',
            'djvu' => 'image/vnd.djvu',
            'djv' => 'image/vnd.djvu',
            'wbmp' => 'image/vnd.wap.wbmp',
            'ras' => 'image/x-cmu-raster',
            'pnm' => 'image/x-portable-anymap',
            'pbm' => 'image/x-portable-bitmap',
            'pgm' => 'image/x-portable-graymap',
            'ppm' => 'image/x-portable-pixmap',
            'rgb' => 'image/x-rgb',
            'xbm' => 'image/x-xbitmap',
            'xpm' => 'image/x-xpixmap',
            'xwd' => 'image/x-windowdump',
            'igs' => 'model/iges',
            'iges' => 'model/iges',
            'msh' => 'model/mesh',
            'mesh' => 'model/mesh',
            'silo' => 'model/mesh',
            'wrl' => 'model/vrml',
            'vrml' => 'model/vrml',
            'css' => 'text/css',
            'html' => 'text/html',
            'htm' => 'text/html',
            'asc' => 'text/plain',
            'txt' => 'text/plain',
            'rtx' => 'text/richtext',
            'rtf' => 'text/rtf',
            'sgml' => 'text/sgml',
            'sgm' => 'text/sgml',
            'tsv' => 'text/tab-seperated-values',
            'wml' => 'text/vnd.wap.wml',
            'wmls' => 'text/vnd.wap.wmlscript',
            'etx' => 'text/x-setext',
            'xml' => 'text/xml',
            'xsl' => 'text/xml',
            'mpeg' => 'video/mpeg',
            'mpg' => 'video/mpeg',
            'mpe' => 'video/mpeg',
            'qt' => 'video/quicktime',
            'mov' => 'video/quicktime',
            'mxu' => 'video/vnd.mpegurl',
            'avi' => 'video/x-msvideo',
            'movie' => 'video/x-sgi-movie',
            'ice' => 'x-conference-xcooltalk');

            if (isset($mimeTypes[$bName])) {
                $mimeType = $mimeTypes[$bName];
            } else {
                $mimeType = 'application/octet-stream';
            }
        }

        if (ob_get_level() && ob_get_length() > 0) {
            ob_end_clean();
        }

        /* Set headers for download */
        header('Content-Transfer-Encoding: binary');
        header('Content-Type: '.$mimeType);
        header('Content-Length: '.filesize($file));
        header('Content-Disposition: attachment; filename="'.$filename.'"');
        //prevents max execution timeout, when reading large files
        @set_time_limit(0);
        $fp = fopen($file, 'rb');

        if ($fp && is_resource($fp)) {
            while (!feof($fp)) {
                echo fgets($fp, 16384);
            }
        }

        exit;
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