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

class AdminSellerProductsController extends ModuleAdminController
{
    public function __construct()
    {
        $this->bootstrap = true;
        $this->table = 'product';
        $this->className = 'Product';
        $this->lang = true;        
        $this->context = Context::getContext();
        parent::__construct();

        if (Configuration::get('PS_MULTISHOP_FEATURE_ACTIVE') == 0) {
            $this->getFieldsList();
        }
        
        
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
        
        if (Configuration::get('PS_MULTISHOP_FEATURE_ACTIVE') == 1) {
            $this->getFieldsList();
        }
        
        return parent::renderList();
    }
    
    public function getFieldsList() {
        $this->_select = '
            sp.id_seller_product, 
            b.name product_name, 
            s.name seller_name, 
            a.date_add, 
            a.active';

        $this->_join = '
            '.Shop::addSqlAssociation('product', 'a').' 
            LEFT JOIN '._DB_PREFIX_.'product_shop ps ON (ps.id_product = a.id_product AND ps.id_shop = '.(int)$this->context->shop->id.')
            LEFT JOIN '._DB_PREFIX_.'seller_product sp ON (sp.id_product = a.id_product)
            LEFT JOIN '._DB_PREFIX_.'seller s ON (s.id_seller = sp.id_seller_product)';

        $this->_use_found_rows = false;

        $this->_where = ' 
            AND sp.id_seller_product != "" AND b.id_shop = '.(int)$this->context->shop->id;

        $this->_orderBy = 'a.date_upd';
        $this->_orderWay = 'DESC';

        $this->fields_list = array(
            'id_product' => array(
                'title' => 'Id',
                'align' => 'center',
                'class' => 'fixed-width-xs',
                'orderby' => true,
                'search' => true,
                'havingFilter' => true,
            ),
            'product_name' => array(
                'title' => $this->l('Product name'),
                'orderby' => true,
                'search' => true,
                'havingFilter' => true,
            ),
            'seller_name' => array(
                'title' => $this->l('Seller name'),
                'orderby' => true,
                'search' => true,
                'havingFilter' => true,
            ),
            'date_add' => array(
                'title' => $this->l('Date add'),
                'type' => 'datetime',
                'orderby' => true,
                'search' => true,
                'havingFilter' => true,
            ),
            'date_upd' => array(
                'title' => $this->l('Date upd'),
                'type' => 'datetime',
                'orderby' => true,
                'search' => true,
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
            )
        );

        $this->bulk_actions = array(
                'delete' => array(
                        'text' => 'Delete selected',
                        'confirm' => 'Delete selected items?',
                        'icon' => 'icon-trash'
                )
        );
    }       
    
    public function renderForm()
    {    
        $this->fields_form = array(
            'legend' => array(
                    'title' => $this->l('Add association seller/product'),
                    'icon' => 'icon-globe'
            ),
            'input' => array(
                    array(
                        'type' => 'select',
                        'label' => $this->l('Seller'),
                        'name' => 'id_seller',
                        'required' => false,
                        'options' => array(
                              'query' => Seller::getSellers($this->context->shop->id),
                              'id' => 'id_seller',
                              'name' => 'name'
                        )
                    ),
                    array(
                        'type' => 'select',
                        'label' => $this->l('Product'),
                        'name' => 'id_product',
                        'required' => false,
                        'options' => array(
                            'query' => SellerProduct::getSimpleProductsWithReference($this->context->language->id),
                            'id' => 'id_product',
                            'name' => 'refname',
                        )
                    ),
                
            )
        );

        $this->fields_form['submit'] = array(
                'title' => $this->l('Save'),
        );

        return parent::renderForm();
    }
    
    public function processFilter()
    {
        Hook::exec('action'.$this->controller_name.'ListingFieldsModifier', array(
            'fields' => &$this->fields_list,
        ));

        if (!isset($this->list_id)) {
            $this->list_id = $this->table;
        }

        $prefix = str_replace(array('admin', 'controller'), '', Tools::strtolower(get_class($this)));

        if (isset($this->list_id)) {
            foreach ($_POST as $key => $value) {
                if ($value === '') {
                    unset($this->context->cookie->{$prefix.$key});
                } elseif (stripos($key, $this->list_id.'Filter_') === 0) {
                    $this->context->cookie->{$prefix.$key} = !is_array($value) ? $value : serialize($value);
                } elseif (stripos($key, 'submitFilter') === 0) {
                    $this->context->cookie->$key = !is_array($value) ? $value : serialize($value);
                }
            }

            foreach ($_GET as $key => $value) {
                if (stripos($key, $this->list_id.'Filter_') === 0) {
                    $this->context->cookie->{$prefix.$key} = !is_array($value) ? $value : serialize($value);
                } elseif (stripos($key, 'submitFilter') === 0) {
                    $this->context->cookie->$key = !is_array($value) ? $value : serialize($value);
                }
                if (stripos($key, $this->list_id.'Orderby') === 0 && Validate::isOrderBy($value)) {
                    if ($value === '' || $value == $this->_defaultOrderBy) {
                        unset($this->context->cookie->{$prefix.$key});
                    } else {
                        $this->context->cookie->{$prefix.$key} = $value;
                    }
                } elseif (stripos($key, $this->list_id.'Orderway') === 0 && Validate::isOrderWay($value)) {
                    if ($value === '' || $value == $this->_defaultOrderWay) {
                        unset($this->context->cookie->{$prefix.$key});
                    } else {
                        $this->context->cookie->{$prefix.$key} = $value;
                    }
                }
            }
        }

        $filters = $this->context->cookie->getFamily($prefix.$this->list_id.'Filter_');
        $definition = false;
        if (isset($this->className) && $this->className) {
            $definition = ObjectModel::getDefinition($this->className);
        }

        foreach ($filters as $key => $value) {
            /* Extracting filters from $_POST on key filter_ */
            if ($value != null && !strncmp($key, $prefix.$this->list_id.'Filter_', 7 + Tools::strlen($prefix.$this->list_id))) {
                $key = Tools::substr($key, 7 + Tools::strlen($prefix.$this->list_id));
                /* Table alias could be specified using a ! eg. alias!field */
                $tmp_tab = explode('!', $key);
                $filter = count($tmp_tab) > 1 ? $tmp_tab[1] : $tmp_tab[0];

                if ($field = $this->filterToField($key, $filter)) {
                    $type = (array_key_exists('filter_type', $field) ? $field['filter_type'] : (array_key_exists('type', $field) ? $field['type'] : false));
                    if (($type == 'date' || $type == 'datetime') && is_string($value)) {
                        $value = Tools::unSerialize($value);
                    }
                    $key = isset($tmp_tab[1]) ? $tmp_tab[0].'.`'.$tmp_tab[1].'`' : '`'.$tmp_tab[0].'`';

                    // Assignment by reference
                    if (array_key_exists('tmpTableFilter', $field)) {
                        $sql_filter = & $this->_tmpTableFilter;
                    } elseif (array_key_exists('havingFilter', $field)) {
                        $sql_filter = & $this->_filterHaving;
                    } else {
                        $sql_filter = & $this->_filter;
                    }

                    /* Only for date filtering (from, to) */
                    if (is_array($value)) {
                        if (isset($value[0]) && !empty($value[0])) {
                            if (!Validate::isDate($value[0])) {
                                $this->errors[] = Tools::displayError('The \'From\' date format is invalid (YYYY-MM-DD)');
                            } else {
                                $sql_filter .= ' AND '.pSQL($key).' >= \''.pSQL(Tools::dateFrom($value[0])).'\'';
                            }
                        }

                        if (isset($value[1]) && !empty($value[1])) {
                            if (!Validate::isDate($value[1])) {
                                $this->errors[] = Tools::displayError('The \'To\' date format is invalid (YYYY-MM-DD)');
                            } else {
                                $sql_filter .= ' AND '.pSQL($key).' <= \''.pSQL(Tools::dateTo($value[1])).'\'';
                            }
                        }
                    } else {
                        $sql_filter .= ' AND ';
                        $check_key = ($key == $this->identifier || $key == '`'.$this->identifier.'`');
                        $alias = ($definition && !empty($definition['fields'][$filter]['shop'])) ? 'a' : 'a';

                        if ($type == 'int' || $type == 'bool') {
                            $sql_filter .= (($check_key || $key == '`active`') ?  $alias.'.' : '').pSQL($key).' = '.(int)$value.' ';
                        } elseif ($type == 'decimal') {
                            $sql_filter .= ($check_key ?  $alias.'.' : '').pSQL($key).' = '.(float)$value.' ';
                        } elseif ($type == 'select') {
                            $sql_filter .= ($check_key ?  $alias.'.' : '').pSQL($key).' = \''.pSQL($value).'\' ';
                        } elseif ($type == 'price') {
                            $value = (float)str_replace(',', '.', $value);
                            $sql_filter .= ($check_key ?  $alias.'.' : '').pSQL($key).' = '.pSQL(trim($value)).' ';
                        } else {
                            $sql_filter .= ($check_key ?  $alias.'.' : '').pSQL($key).' LIKE \'%'.pSQL(trim($value)).'%\' ';
                        }
                    }
                }
            }
        }
    }

    public function postProcess() {  
        
        if ($this->display == 'view') {
            
            $id_product = (int)Tools::getValue('id_product');
            $product = new Product($id_product, false, (int)$this->context->language->id, (int)$this->context->shop->id);
            $id_seller = Seller::getSellerByProduct($id_product);
            $seller = new Seller($id_seller);
            $this->context->smarty->assign('seller_name', $seller->name);
            
            if (Configuration::get('MARKETPLACE_SHOW_IMAGES') == 1) {
                $images = $product->getImages((int)$this->context->language->id);
                $this->context->smarty->assign('images', $images);
            }
            
            if (Configuration::get('MARKETPLACE_SHOW_TAX') == 1) {
                $taxRuleGroup = new TaxRulesGroup($product->id_tax_rules_group);
                $this->context->smarty->assign('tax_name', $taxRuleGroup->name);
            }
              
            if (Configuration::get('MARKETPLACE_SHOW_CATEGORIES') == 1) {
                $categories = Product::getProductCategoriesFull($product->id);
                $categories_string = '';
                foreach ($categories as $c) {
                    $categories_string .= $c['name'].', ';
                }
                $categories_string = Tools::substr($categories_string, 0, -2);
                //$obj_category_default = new Category($product->id_category_default, (int)$this->context->language->id);
                $this->context->smarty->assign(array(
                    'categories_string' => $categories_string,
                    /*'category_default_name' => $obj_category_default->name*/
                ));
            }
            
            if (Configuration::get('MARKETPLACE_SHOW_SUPPLIERS') == 1) {
                $supplier_name = Supplier::getNameById($product->id_supplier);
                $this->context->smarty->assign('supplier_name', $supplier_name);
            }
            
            if (Configuration::get('MARKETPLACE_SHOW_MANUFACTURERS') == 1) {
                $manufacturer_name = Manufacturer::getNameById($product->id_manufacturer);
                $this->context->smarty->assign('manufacturer_name', $manufacturer_name);
            }
            
            if (Configuration::get('MARKETPLACE_SHOW_FEATURES') == 1) {
                $features = $product->getFrontFeatures($this->context->language->id);
                $this->context->smarty->assign('features', $features);
            }   
            
            if (Configuration::get('MARKETPLACE_SHOW_SHIP_PRODUCT') == 1) {
                $this->context->smarty->assign('carriers', $product->getCarriers());
            }
            
            if (Configuration::get('MARKETPLACE_SHOW_ATTRIBUTES') == 1) {
                $attributes = $product->getAttributesResume($this->context->language->id);
                $this->context->smarty->assign('attributes', $attributes);
            }

            $this->context->smarty->assign(array(
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
                'product' => $product,
                'url_product' => 'index.php?tab=AdminProducts&id_product='.(int)$product->id.'&updateproduct&token='.Tools::getAdminToken('AdminProducts'.(int)Tab::getIdFromClassName('AdminProducts').(int)$this->context->employee->id),
                'token' => $this->token,
            ));
        }

        //enviar email producto borrada si procede
        
        if (Tools::isSubmit('statusproduct')) {
            $id_product = (int)Tools::getValue('id_product');
            $product = new Product($id_product);
            
            if ($product->active == 1) 
                $product->active = 0;
            else
                $product->active = 1;
            
            $product->update();
            $this->reportSellerProductStatusChange($id_product);
        }
        else if (Tools::isSubmit('submitAddproduct')) {
            $id_seller = (int)Tools::getValue('id_seller');
            $id_product = (int)Tools::getValue('id_product');
            
            if (!SellerProduct::existAssociationSellerProduct($id_product))
                SellerProduct::associateSellerProduct($id_seller, $id_product);
            else
                $this->errors[] = $this->module->l('This product is already associated with a seller.', 'AdminSellerProductsController');
        }
        else if (Tools::isSubmit('submitBulkenableSelectionproduct')) {
            //enable products selected
            $products_selected = Tools::getValue('productBox');
            foreach ($products_selected as $id_product) {
                $product = new Product($id_product);
                $product->active = 1;
                $product->update();
                $this->reportSellerProductStatusChange($id_product);
            }
        }
        else if (Tools::isSubmit('submitBulkdisableSelectionproduct')) {
            //disable products selected
            $products_selected = Tools::getValue('productBox');
            foreach ($products_selected as $id_product) {
                $product = new Product($id_product);
                $product->active = 0;
                $product->update();
                $this->reportSellerProductStatusChange($id_product);
            }
        }        
        else if (Tools::isSubmit('submitBulkdeleteproduct')) {
            //delete products selected
            $products_selected = Tools::getValue('productBox');
            foreach ($products_selected as $id_product) {
                $product = new Product($id_product);
                $id_seller = SellerProduct::isSellerProduct($id_product);
                SellerProduct::deleteSellerProduct($id_seller, $id_product);
                $product->delete();
            }
        }
        else {
            parent::postProcess(); 
        }
    }
    
    public function reportSellerProductStatusChange($id_product) {
        if (Configuration::get('MARKETPLACE_SEND_PRODUCT_ACTIVE')) {   
            $product = new Product($id_product, false, (int)$this->context->language->id, (int)$this->context->shop->id);
            Search::indexation(Tools::link_rewrite($product->name), $product->id);

            $id_seller = SellerProduct::isSellerProduct($id_product);
            $seller = new Seller($id_seller);

            if (Configuration::get('MARKETPLACE_SEND_SELLER_ACTIVE')) {
                $id_seller_email = false;
                $to = $seller->email;
                $to_name = $seller->name;
                $from = Configuration::get('PS_SHOP_EMAIL');
                $from_name = Configuration::get('PS_SHOP_NAME');

                $template = 'base';

                if ($product->active == 1) {
                    $reference = 'product-activated';
                    $id_seller_email = SellerEmail::getIdByReference($reference);
                }
                else {
                    $reference = 'product-desactivated';
                    $id_seller_email = SellerEmail::getIdByReference($reference);
                }

                if ($id_seller_email) {
                    $seller_email = new SellerEmail($id_seller_email, $seller->id_lang);         
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