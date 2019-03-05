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

class marketplaceSellonModuleFrontController extends ModuleFrontController
{
    public $ssl = true;
    public $exclude;
    public $product ;
    /** @var bool If false, does not build left page column content and hides it. */
    public $display_column_left = false;
    /** @var bool If false, does not build right page column content and hides it. */
    public $display_column_right = false;

    public function setMedia() {
        parent::setMedia();
        $this->addJqueryUI('ui.datepicker');
    }

    public function initContent() {
        parent::initContent();
        global $cookie;
        $link = $this->context->link ;
        //$this->product = $product = new Product((int)Tools::getValue('id_product'),true, $this->context->language->id);
        $this->product = $product = new Product((int)Tools::getValue('id_product'));

        if(
            Tools::getValue('id_product')
            && $cookie->isLogged()
            && Validate::isLoadedObject($product)
            && $product->id
        ){
            $images = $this->product->getImages((int)$this->context->cookie->id_lang);

            foreach ($images as $k => $image) {
                if ($image['cover']) {
                    $this->context->smarty->assign('mainImage', $image);
                    $cover = $image;
                    $cover['id_image'] = (Configuration::get('PS_LEGACY_IMAGES') ? ($this->product->id.'-'.$image['id_image']) : $image['id_image']);
                    $cover['id_image_only'] = (int)$image['id_image'];
                }
                $product_images[(int)$image['id_image']] = $image;
            }

            $languages = Language::getLanguages();
            $id_lang = (int)$this->context->language->id;

            $is_seller = Seller::isSeller($this->context->cookie->id_customer, $this->context->shop->id);
            /**
             * @todo
             * Que faire si l'utilisateur n'a pas encore un compte vendeur
            **/
            if (!$is_seller) {
                Tools::redirect($this->context->link->getModuleLink(
                    'marketplace',
                    'addseller',
                    array(                        
                        'back' =>  $this->context->link->getModuleLink('marketplace','sellon',array("id_product" => $product->id)),
                        'content_only' => 0,
                    )
                ));
            }

            $id_seller = Seller::getSellerByCustomer($this->context->cookie->id_customer);
            $seller = new Seller($id_seller);

            /**
             * @todo
             * Que faire si le compte du vendeur n'est pas encore activé
             **/
            if ($seller->active == 0) {
                //Tools::redirect($this->context->link->getPageLink('my-account', true));
            }else{

            }


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
                    $product_i = new Product((int)$i['id_product'], (int)$this->context->language->id, (int)$this->context->shop->id);
                    $incidences[$counter]['product_name'] = $product_i->name;
                    $incidences[$counter]['messages_not_readed'] = SellerIncidence::getNumMessagesNotReaded($i['id_seller_incidence'], $id_seller, false);
                      
                    $messages = SellerIncidence::getMessages((int)$i['id_seller_incidence'],false,0,$id_seller);
                    
                    $incidences[$counter] = array_merge($incidences[$counter], array('messages' => $messages));
                    $counter++;
                }
            }

            $this->context->smarty->assign(array(
                'product' => $product ,
                'show_reference' => false, //Configuration::get('MARKETPLACE_SHOW_REFERENCE'),
                'show_ean13' => Configuration::get('MARKETPLACE_SHOW_EAN13'),
                'show_upc' => Configuration::get('MARKETPLACE_SHOW_UPC'),
                'show_width' => Configuration::get('MARKETPLACE_SHOW_WIDTH'),
                'show_height' => Configuration::get('MARKETPLACE_SHOW_HEIGHT'),
                'show_depth' => Configuration::get('MARKETPLACE_SHOW_DEPTH'),
                'show_weight' => Configuration::get('MARKETPLACE_SHOW_WEIGHT'),
                'show_shipping_product' => Configuration::get('MARKETPLACE_SHOW_SHIP_PRODUCT'),
                'show_condition' => true, // Configuration::get('MARKETPLACE_SHOW_CONDITION'),
                'show_quantity' => true, // Configuration::get('MARKETPLACE_SHOW_QUANTITY'),
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
                'show_categories' => false,// Configuration::get('MARKETPLACE_SHOW_CATEGORIES'),
                'show_features' => false , //Configuration::get('MARKETPLACE_SHOW_FEATURES'),
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
                'id_product' => $product->id,
                'images' => $images,
                'has_attributes' => 0,
                'mesages_not_readed' => SellerIncidenceMessage::getNumMessagesNotReadedBySeller($id_seller),
                'content_only' => Tools::getValue('content_only'),
                'show_comments' => 1,
                'seller' => $seller,
                'dashboard_link' => $this->context->link->getModuleLink('marketplace', 'dashboard', array(), true),
                'menu' => 'module:marketplace/views/templates/front/sellermenu.tpl',
                'incidences' => $incidences,

            ));

            if (version_compare(_PS_VERSION_, '1.6.0', '<'))
                $this->context->smarty->assign('version', 15);
            else
                $this->context->smarty->assign('version', 17);

            $this->setTemplate('module:marketplace/views/templates/front/sellon.tpl');
            //$this->setTemplate('sellon.tpl');

        }else{

            if( !$cookie->isLogged() ){
                Tools::redirect( $link->getPageLink('authentication', true,['content_only' => 1,'back' => $link->getProductLink( (int)Tools::getValue('id_product') )]));
            }
            if( !Validate::isLoadedObject($product) || !$product->id  ){
                Controller::getController('PageNotFoundController')->run();
            }
        }
    }

    public static function associateSellerProduct($id_seller, $id_product,$id_product_copy) {
        return Db::getInstance()->Execute('INSERT INTO `' . _DB_PREFIX_ . 'seller_product`
					(`id_seller_product`, `id_product`,`id_product_copy`)
					VALUES ('.(int)$id_seller.', '.(int)$id_product.' , ' . (int)$id_product_copy . ')');
    }

    public function moderateProduct($id_product,$seller){
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
        return true ;
    }

    public function postProcess() {
        if ( Validate::isLoadedObject($product = new Product((int)Tools::getValue('id_product'))) && Tools::isSubmit('submitSellOn') ) {
            $quantity = (int)Tools::getValue('quantity');
            $price = (float)Tools::getValue('price');
            $specific_price = (float)Tools::getValue('specific_price');
            $content_comment = Tools::getValue('content');
            //$title_comment = Tools::getValue('title');
            $additional_shipping_cost = Tools::getValue('additional_shipping_cost');

            $id_customer = $this->context->customer->id;
            $customer_name = pSQL($this->context->customer->firstname.' '.$this->context->customer->lastname);

            
            if ($price == '' || !Validate::isPrice($price))
            {
                
                $this->errors[] = $this->module->l('Price is incorrect.', 'addproduct');
            }

            if ($specific_price != 0 && !Validate::isPrice($specific_price))
                $this->errors[] = $this->module->l('Offer price is incorrect.', 'addproduct');

            if ($specific_price != 0 && $specific_price > $price)
                $this->errors[] = $this->module->l('Offer price is bigger than price.', 'addproduct');

            if ($quantity != '' && !Validate::isInt($quantity))
            {                

                $this->errors[] = $this->module->l('Quantity is incorrect.', 'addproduct');
            }

            $url_images = array();

            if(Configuration::get('MARKETPLACE_SHOW_IMAGES') == 1 && Tools::getValue('rad_product_image') == 0 ) {
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
                                $this->errors[] = $this->module->l('The image format is incorrect or max size to upload is', 'addproduct').' '.ini_get('post_max_size');
                            }
                        }
                        else {
                            $url_images[$i] = '';
                        }
                    }
                }
                else {
                    $this->errors[] = $this->module->l('The maxim images to upload is', 'addproduct').' '.Configuration::get('MARKETPLACE_MAX_IMAGES');
                }
            }

            if( count( $this->errors) > 0 ) {
                $this->context->smarty->assign(array(
                    'errors' => $this->errors,
                    'condition' => Tools::getValue('condition'),
                    'quantity' => $quantity,
                    'price' => $price,
                    'specific_price' => $specific_price,
                    'id_tax' => Tools::getValue('id_tax'),
                    'content_only' => 1,
                ));
            }else{
                $id_product_old = $product->id;
                $id_seller = Seller::getSellerByCustomer($this->context->cookie->id_customer);
                $seller = new Seller((int)$id_seller);
                if (empty($product->price) && Shop::getContext() == Shop::CONTEXT_GROUP) {
                    $shops = ShopGroup::getShopsFromGroup(Shop::getContextShopGroupID());
                    foreach ($shops as $shop) {
                        if ($product->isAssociatedToShop($shop['id_shop'])) {
                            $product_price = new Product($id_product_old, false, null, $shop['id_shop']);
                            $product->price = $product_price->price;
                        }
                    }
                }
                unset($product->id);
                unset($product->id_product);

                $product->indexed = 0;
                $product->price = (float)Tools::getValue('price');
                $product->quantity = (int)Tools::getValue('quantity');
                $product->condition = pSQL(Tools::getValue('condition'));
                $product->additional_shipping_cost = $additional_shipping_cost;

                $product->id_tax_rules_group = (int)Tools::getValue('id_tax');
                
                if (Configuration::get('MARKETPLACE_MODERATE_PRODUCT') == 1)
                    $product->active = 0;
                else
                    $product->active = 1;                

                if (
                    $product->add()
                    && Category::duplicateProductCategories($id_product_old, $product->id)
                    && Product::duplicateSuppliers($id_product_old, $product->id)
                    && ($combination_images = Product::duplicateAttributes($id_product_old, $product->id)) !== false
                    && GroupReduction::duplicateReduction($id_product_old, $product->id)
                    && Product::duplicateAccessories($id_product_old, $product->id)
                    && Product::duplicateFeatures($id_product_old, $product->id)
                    //&& Product::duplicateSpecificPrices($id_product_old, $product->id)
                    && Pack::duplicate($id_product_old, $product->id)
                    && Product::duplicateCustomizationFields($id_product_old, $product->id)
                    && Product::duplicateTags($id_product_old, $product->id)
                    && Product::duplicateDownload($id_product_old, $product->id)

                    && $this->moderateProduct($product->id,$seller) // moderate product
                    && $this->associateSellerProduct($id_seller, $product->id,$id_product_old) // associate seller product
                )
                {

                    if ($product->hasAttributes()) {
                        Product::updateDefaultAttribute($product->id);
                    }

                    //StockAvailable::setQuantity($product->id, 0, (int)Tools::getValue('quantity'));

                    // set quantity to all product attributes
                    $sql = 'update '._DB_PREFIX_.'stock_available set quantity='.(int)Tools::getValue('quantity').' where id_product = '.$product->id;

                    Db::getInstance()->Execute($sql);

                    if($content_comment != '')
                    {                        
                        $query = 'UPDATE `'._DB_PREFIX_.'product` SET `comments` = "'. $content_comment .'" WHERE `id_product` ='. $product->id;

                        Db::getInstance()->execute($query);
                    }

                    /*if($content_comment != '')
                        Product::setComments($product->id,$content_comment);*/

                    /*if(Tools::getValue('title') != '' && Tools::getValue('content') != '')
                    SellerProductComment::addSellerProductComment($product->id,$id_customer,$customer_name,Tools::getValue('title'),strip_tags(Tools::getValue('content')),Tools::getValue('criterion'));*/
    
                    // end comments

                    $bCopyImage = false ;
                    $bDuplicationComplete = false ;

                    switch( Tools::getValue('rad_product_image') ){
                        case "0":
                            $this->uploadImages($_FILES,$url_images,$product,$_POST,$edit_product = false);
                            break;
                        case "1":
                            $bCopyImage = true ;
                            if (!Tools::getValue('noimage') && !Image::duplicateProductImages($id_product_old, $product->id, $combination_images)) {
                                $this->errors[] = Tools::displayError('An error occurred while copying images.');
                            }else{
                                $bDuplicationComplete = true ;
                            }
                            break;
                    }

                    if($bCopyImage && $bDuplicationComplete )
                    {
                        Hook::exec('actionProductAdd', array('id_product' => (int)$product->id, 'product' => $product));
                        if (in_array($product->visibility, array('both', 'search')) && Configuration::get('PS_SEARCH_INDEXATION')) {
                            Search::indexation(false, $product->id);
                        }

                        

                        Tools::redirect( $this->context->link->getModuleLink('marketplace','sellon',['confirmation'=>1,'id_product'=>$id_product_old,'content_only' => Tools::getValue('content_only')] ,true));
                    }else
                    {
                        Tools::redirect( $this->context->link->getModuleLink('marketplace','sellon',['confirmation'=>1,'id_product'=>$id_product_old,'content_only' => Tools::getValue('content_only')] ,true));
                    }

                    

                } else {
                    $this->errors[] = Tools::displayError('An error occurred while creating an object.');
                    Tools::redirect( $this->context->link->getModuleLink('marketplace','sellon',['confirmation'=>0,'id_product' => $id_product_old,'content_only' => Tools::getValue('content_only')] ,true));
                }
            }
        }

        if (Tools::getValue('confirmation'))
            $this->context->smarty->assign(array('confirmation' => 1,'content_only' => Tools::getValue('content_only')));
    }

    public function uploadImages($files,$images,$product,$item,$edit_product = false){
        $id_lang = $this->context->language->id ;

        if (!Shop::isFeatureActive())
            $product->shop = 1;
        elseif (!isset($product->shop) || empty($product->shop))
            $product->shop = implode(',', Shop::getContextListShopID());

        if (!Shop::isFeatureActive())
            $product->id_shop_default = 1;
        else
            $product->id_shop_default = (int)Context::getContext()->shop->id;

        // link product to shops
        $product->id_shop_list = array();
        foreach (explode(',', $product->shop) as $shop)
            if (!is_numeric($shop))
                $product->id_shop_list[] = Shop::getIdByName($shop);
            else
                $product->id_shop_list[] = $shop;

        //images
        $shops = array();
        $product_shop = explode(',', $product->shop);
        foreach ($product_shop as $shop) {
            $shop = trim($shop);
            if (!is_numeric($shop))
                $shop = ShopGroup::getIdByName($shop);
            $shops[] = $shop;
        }
        if (empty($shops))
            $shops = Shop::getContextListShopID();

        if (Configuration::get('MARKETPLACE_MAX_IMAGES') > 0 && count($images) > 0) {
            for ($i=1; $i<=Configuration::get('MARKETPLACE_MAX_IMAGES'); $i++) {
                if ($images[$i] != '' || ($edit_product && $item['legends'][$i] != '')) {
                    $id_image = SellerProduct::getIdImageByPosition($product->id, $i);

                    if ($id_image > 0)
                        $image = new Image($id_image);
                    else
                        $image = new Image();

                    $image->id_product = $product->id;
                    $image->position = $i;

                    if ($i == 1)
                        $image->cover = 1;
                    else
                        $image->cover = 0;

                    if ($item['legends'][$i]) {
                        foreach (Language::getLanguages() as $language) {
                            if ($item['legends'][$i] != '')
                                $image->legend[$language['id_lang']] = Tools::stripslashes(trim(pSQL($item['legends'][$i])));
                            else
                                $image->legend[$language['id_lang']] = Tools::stripslashes(trim(pSQL($item['legends'][$i])));
                        }
                    }
                    else
                        $image->legend =  SellerProduct::createMultiLangField($product->name[$id_lang]);

                    if ($id_image > 0)
                        $image->update();
                    else
                        $image->add();

                    $image->associateTo($shops);

                    if ($images[$i] != '')
                        SellerProduct::copyImg($product->id, $image->id, $images[$i]);
                }
            }
        }
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