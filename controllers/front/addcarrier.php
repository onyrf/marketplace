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

class marketplaceAddcarrierModuleFrontController extends ModuleFrontController
{
    public $ssl = true;
    public $id_seller;
    
    public function postProcess() {
        
        $this->id_seller = Seller::getSellerByCustomer($this->context->cookie->id_customer);
        
        if (Tools::isSubmit('submitAddCarrier')) {
            
            $languages = Language::getLanguages();
            $carrier_name = pSQL(Tools::getValue('carrier_name'));
            $carrier_url = pSQL(Tools::getValue('url'));
            $dafault_carrier_delay = pSQL(Tools::getValue('delay_'.$this->context->language->id));
            $carrier_grade = (int)Tools::getValue('grade');
            $is_free = (int)Tools::getValue('is_free');
            $shipping_method = (int)Tools::getValue('shipping_method');
            $max_width = (float)Tools::getValue('max_width');
            $max_height = (float)Tools::getValue('max_height');
            $max_depth = (float)Tools::getValue('max_depth');
            $max_weight = (float)Tools::getValue('max_weight');
            $fees = Tools::getValue('fees');
            
            if ($carrier_name == '' || !Validate::isCarrierName($carrier_name))
                $this->errors[] = $this->module->l('Name carrier is incorrect.', 'addcarrier');
            
            if ($dafault_carrier_delay == '' || !Validate::isGenericName($dafault_carrier_delay))
                $this->errors[] = $this->module->l('Delay carrier is incorrect.', 'addcarrier');
            
            if (!Validate::isInt($carrier_grade) || $carrier_grade > 9 || $carrier_grade < 0)
                $this->errors[] = $this->module->l('Carrier grade is incorrect. It must be a number between 0 and 9.', 'addcarrier');
            
            if (!Validate::isFloat($max_width))
                $this->errors[] = $this->module->l('Carrier max width is incorrect.', 'addcarrier');
            
            if (!Validate::isFloat($max_height))
                $this->errors[] = $this->module->l('Carrier max height is incorrect.', 'addcarrier');
            
            if (!Validate::isFloat($max_depth))
                $this->errors[] = $this->module->l('Carrier max depth is incorrect.', 'addcarrier');
            
            if (!Validate::isFloat($max_weight))
                $this->errors[] = $this->module->l('Carrier max weight is incorrect.', 'addcarrier');
            
            if ($is_free == 0) {
                if (!is_array($fees) || count($fees) == 0)
                    $this->errors[] = $this->module->l('You must fill the ranges fees for this carrier.', 'addcarrier');
            }
            
            if (count($this->errors) > 0) {
                $carrier_delay = array();
                foreach ($languages as $language) {
                    $carrier_delay[$language['id_lang']] = pSQL(Tools::getValue('delay_'.$language['id_lang']));
                }
                
                $this->context->smarty->assign(array(
                    'errors' => $this->errors,
                    'carrier_name' => $carrier_name,
                    'delay' => $carrier_delay,
                    'url' => $carrier_url,
                    'grade' => $carrier_grade,
                    'is_free' => $is_free,
                    'max_width' => $max_width,
                    'max_height' => $max_height,
                    'max_depth' => $max_depth,
                    'max_weight' => $max_weight,
                )); 
            }
            else {
                $data_carrier = array();
                $carrier = new Carrier();
                $carrier->name = $carrier_name;
                $carrier->url = $carrier_url;

                foreach ($languages as $lang) {
                    if (Tools::getValue('delay_'.$lang['id_lang']) != '')
                        $carrier->delay[$lang['id_lang']] = pSQL(Tools::getValue('delay_'.$lang['id_lang']));
                    else 
                        $carrier->delay[$lang['id_lang']] = pSQL(Tools::getValue('delay_'.$this->context->language->id));
                }

                $carrier->active = 1;
                $carrier->is_free = $is_free;
                $carrier->shipping_method = $shipping_method;
                
                if ($carrier->is_free == 1)
                    $carrier->need_range = 0;
                else
                    $carrier->need_range = 1;
                
                $carrier->max_width = $max_width;
                $carrier->max_height = $max_height;
                $carrier->max_depth = $max_depth;
                $carrier->max_weight = $max_weight;
                $carrier->grade = $carrier_grade; 
                $carrier->position = Carrier::getHigherPosition() + 1;
                $carrier->add();

                /*$zones = Zone::getZones(false);
                foreach ($zones as $zone) {
                    if ($carrier->is_free) {
                        $carrier->addZone((int)$zone['id_zone']);
                    }
                    else {
                        if (count($carrier->getZone($zone['id_zone'])))
                        {
                            if (!Tools::getValue('zone_'.$zone['id_zone']))
                                $carrier->deleteZone((int)$zone['id_zone']);
                        }
                        else
                            if (Tools::getValue('zone_'.$zone['id_zone']))
                                $carrier->addZone((int)$zone['id_zone']); 
                    }   
                }*/

                $zones = Zone::getZones(false);
                foreach ($zones as $zone) {
                    if (count($carrier->getZone($zone['id_zone']))) {
                        if (!isset($_POST['zone_'.$zone['id_zone']]) || !$_POST['zone_'.$zone['id_zone']]) {
                            $carrier->deleteZone($zone['id_zone']);
                        }
                    } elseif (isset($_POST['zone_'.$zone['id_zone']]) && $_POST['zone_'.$zone['id_zone']]) {
                        $carrier->addZone($zone['id_zone']);
                    }
                }


                $carrier->setGroups(Tools::getValue('groupBox'));

                $carrier->setTaxRulesGroup((int)Tools::getValue('id_tax_rules_group'));

                if ($carrier->is_free)
                {
                    //if carrier is free delete shipping cost
                    $carrier->deleteDeliveryPrice('range_weight');
                    $carrier->deleteDeliveryPrice('range_price');
                }
                else {
                    if (!$this->processRanges((int)$carrier->id))
                    {
                        $this->errors[] = $this->l('An error occurred while saving carrier ranges.');
                        $this->context->smarty->assign(array(
                            'errors' => $this->errors,
                        ));
                    }
                }

                if ($_FILES['logo']['name'] != "") {
                    if ((($_FILES['logo']['type'] == "image/pjpeg") || ($_FILES['logo']['type'] == "image/jpeg") || ($_FILES['logo']['type'] == "image/png")) && ($_FILES['logo']['size'] < 1000000)) {
                        if (file_exists(_PS_SHIP_IMG_DIR_.$carrier->id.'.jpg')) {
                            unlink(_PS_SHIP_IMG_DIR_.$carrier->id.'.jpg');
                        }

                        move_uploaded_file($_FILES['logo']['tmp_name'], _PS_SHIP_IMG_DIR_.$carrier->id.'.jpg');
                    }
                }

                //associate seller to carrier
                $data_carrier[] = array(
                    'id_seller' => (int)$this->id_seller,
                    'id_carrier' => (int)$carrier->id,
                );

                Db::getInstance()->insert('seller_carrier', $data_carrier);
                
                Tools::redirect($this->context->link->getModuleLink('marketplace', 'carriers', array(), true));
            }   
        }  
    }

    public function initContent() {
        
        parent::initContent();
        $languages = Language::getLanguages();
        $id_lang = $this->context->language->id;
        
        if(!$this->context->cookie->id_customer)
            Tools::redirect($this->context->link->getPageLink('my-account', true));
        
        $is_seller = Seller::isSeller($this->context->cookie->id_customer, $this->context->shop->id);
        if (!$is_seller)
            Tools::redirect($this->context->link->getPageLink('my-account', true));
        
        $this->id_seller = Seller::getSellerByCustomer($this->context->cookie->id_customer);
        
        $seller = new Seller($this->id_seller);
        
        if ($seller->active == 0)
            Tools::redirect($this->context->link->getPageLink('my-account', true));
        
        $param = array('id_seller' => $seller->id, 'link_rewrite' => $seller->link_rewrite);
        $url_seller_profile = $this->module->getmarketplaceLink('marketplace_seller_rule', $param);
        
        $this->context->smarty->assign(array(
            'seller_link' => $url_seller_profile,
            'show_import_product' => Configuration::get('MARKETPLACE_SELLER_IMPORT_PROD'),
            'show_orders' => Configuration::get('MARKETPLACE_SHOW_ORDERS'),
            'show_edit_seller_account' => Configuration::get('MARKETPLACE_SHOW_EDIT_ACCOUNT'),
            'show_contact' => Configuration::get('MARKETPLACE_SHOW_CONTACT'),
            'show_manage_orders' => Configuration::get('MARKETPLACE_SHOW_MANAGE_ORDERS'),
            'show_manage_carriers' => Configuration::get('MARKETPLACE_SHOW_MANAGE_CARRIER'),
            'show_dashboard' => Configuration::get('MARKETPLACE_SHOW_DASHBOARD'),
            'show_menu_top' => Configuration::get('MARKETPLACE_MENU_TOP'),
            'show_menu_options' => Configuration::get('MARKETPLACE_MENU_OPTIONS'),
            'languages' => $languages,
            'id_lang' => $id_lang,
            'zones' => Zone::getZones(false),
            'currency_sign' => $this->context->currency->sign,
            'PS_WEIGHT_UNIT' => Configuration::get('PS_WEIGHT_UNIT'),
            'taxes' => Tax::getTaxes($this->context->language->id),
            'groups' => Group::getGroups($this->context->language->id),
            'mesages_not_readed' => SellerIncidenceMessage::getNumMessagesNotReadedBySeller($this->id_seller),
        ));
        
        $this->setTemplate('addcarrier.tpl');          
    }
    
    public function processRanges($id_carrier)
    {
        /*$carrier = new Carrier((int)$id_carrier);
        if (!Validate::isLoadedObject($carrier))
            return false;
        
        //d($_POST);

        $range_inf = Tools::getValue('range_inf');
        $range_sup = Tools::getValue('range_sup');
        $range_type = (int)Tools::getValue('shipping_method');

        $fees = Tools::getValue('fees');

        $carrier->deleteDeliveryPrice($carrier->getRangeTable());
        if ($range_type != Carrier::SHIPPING_METHOD_FREE)
        {
            foreach ($range_inf as $key => $delimiter1)
            {
                if (!isset($range_sup[$key]))
                    continue;
                
                $add_range = true;
                
                if ($range_type == Carrier::SHIPPING_METHOD_WEIGHT)
                {
                    if (!RangeWeight::rangeExist((int)$carrier->id, (float)$delimiter1, (float)$range_sup[$key]))
                        $range = new RangeWeight();
                    else
                    {
                        $range = new RangeWeight((int)$key);
                        $add_range = false;
                    }
                }

                if ($range_type == Carrier::SHIPPING_METHOD_PRICE)
                {
                    if (!RangePrice::rangeExist((int)$carrier->id, (float)$delimiter1, (float)$range_sup[$key]))
                        $range = new RangePrice();
                    else
                    {
                        $range = new RangePrice((int)$key);
                        $add_range = false;
                    }
                }
                if ($add_range)
                {
                    $range->id_carrier = (int)$carrier->id;
                    $range->delimiter1 = (float)$delimiter1;
                    $range->delimiter2 = (float)$range_sup[$key];
                    $range->save();
                }

                if (!Validate::isLoadedObject($range))
                    return false;
                
                $price_list = array();
                if (is_array($fees) && count($fees))
                {
                    foreach ($fees as $id_zone => $fee)
                    {
                        $price_list[] = array(
                                'id_range_price' => ($range_type == Carrier::SHIPPING_METHOD_PRICE ? (int)$range->id : null),
                                'id_range_weight' => ($range_type == Carrier::SHIPPING_METHOD_WEIGHT ? (int)$range->id : null),
                                'id_carrier' => (int)$carrier->id,
                                'id_zone' => (int)$id_zone,
                                'price' => isset($fee[$key]) ? (float)$fee[$key] : 0,
                        );

                    }
                }

                if (count($price_list) && !$carrier->addDeliveryPrice($price_list, true))
                    return false;
            }
        }
        return true;*/

        $carrier = new Carrier((int)$id_carrier);
        if (!Validate::isLoadedObject($carrier)) {
            return false;
        }

        $range_inf = Tools::getValue('range_inf');
        $range_sup = Tools::getValue('range_sup');
        $range_type = Tools::getValue('shipping_method');

        $fees = Tools::getValue('fees');

        $carrier->deleteDeliveryPrice($carrier->getRangeTable());
        if ($range_type != Carrier::SHIPPING_METHOD_FREE) {
            foreach ($range_inf as $key => $delimiter1) {
                if (!isset($range_sup[$key])) {
                    continue;
                }
                $add_range = true;
                if ($range_type == Carrier::SHIPPING_METHOD_WEIGHT) {
                    if (!RangeWeight::rangeExist(null, (float)$delimiter1, (float)$range_sup[$key], $carrier->id_reference)) {
                        $range = new RangeWeight();
                    } else {
                        $range = new RangeWeight((int)$key);
                        $range->id_carrier = (int)$carrier->id;
                        $range->save();
                        $add_range = false;
                    }
                }

                if ($range_type == Carrier::SHIPPING_METHOD_PRICE) {
                    if (!RangePrice::rangeExist(null, (float)$delimiter1, (float)$range_sup[$key], $carrier->id_reference)) {
                        $range = new RangePrice();
                    } else {
                        $range = new RangePrice((int)$key);
                        $range->id_carrier = (int)$carrier->id;
                        $range->save();
                        $add_range = false;
                    }
                }
                if ($add_range) {
                    $range->id_carrier = (int)$carrier->id;
                    $range->delimiter1 = (float)$delimiter1;
                    $range->delimiter2 = (float)$range_sup[$key];
                    $range->save();
                }

                if (!Validate::isLoadedObject($range)) {
                    return false;
                }
                $price_list = array();
                if (is_array($fees) && count($fees)) {
                    foreach ($fees as $id_zone => $fee) {
                        $price_list[] = array(
                            'id_shop' => NULL,
                            'id_shop_group' => NULL,
                            'id_range_price' => ($range_type == Carrier::SHIPPING_METHOD_PRICE ? (int)$range->id : null),
                            'id_range_weight' => ($range_type == Carrier::SHIPPING_METHOD_WEIGHT ? (int)$range->id : null),
                            'id_carrier' => (int)$carrier->id,
                            'id_zone' => (int)$id_zone,
                            'price' => isset($fee[$key]) ? (float)str_replace(',', '.', $fee[$key]) : 0,
                        );
                    }
                }

                if (count($price_list) && !$carrier->addDeliveryPrice($price_list, true)) {
                    return false;
                }
            }
        }
        return true;
    }
}