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

class marketplaceSellerproductsModuleFrontController extends ModuleFrontController
{
    public $ssl = true;
    
    public function postProcess() {
        
        Hook::exec('actionMarketplaceSellerProducts');
        
        if (Tools::getValue('process') == 'ModifPrice')
            $this->processModifPrice();
        else
        {
        
        if (Tools::getValue('deleteproduct') == 1 && Tools::getValue('id_product') > 0 && Configuration::get('MARKETPLACE_SHOW_DELETE_PRODUCT') == 1) {
            //comprobar si el producto es del usuario
            $id_product = (int)Tools::getValue('id_product');
            if (SellerProduct::existAssociationSellerProduct($id_product)) {
                $product = new Product($id_product);
                $product->delete();
                Tools::redirect($this->context->link->getModuleLink('marketplace', 'sellerproducts', array(), true));
            }
            else {
                $this->errors[] = $this->module->l('You do not have permission to delete this product.', 'sellerproducts');
                $this->context->smarty->assign(array('errors' => $this->errors));     
            }
        }
        
        if (Tools::getValue('statusproduct') == 1 && Tools::getValue('id_product') > 0 && Configuration::get('MARKETPLACE_SHOW_ACTIVE_PRODUCT') == 1) {
            //comprobar si el producto es del usuario
            $id_product = (int)Tools::getValue('id_product');
            if (SellerProduct::existAssociationSellerProduct($id_product)) {
                $product = new Product($id_product);
                
                if ($product->active == 1)
                    $product->active = 0;
                else
                    $product->active = 1;
                
                $product->update();
                
                Tools::redirect($this->context->link->getModuleLink('marketplace', 'sellerproducts', array(), true));
            }
            else {
                $this->errors[] = $this->module->l('You do not have permission to enable/disable this product.', 'sellerproducts');
                $this->context->smarty->assign(array('errors' => $this->errors));     
            }
        }
        }
    }

    public function initContent() {
        
        parent::initContent();
        
        if(!$this->context->cookie->id_customer)
            Tools::redirect($this->context->link->getPageLink('my-account', true));
        $offers = array();
        
        $id_seller = Seller::getSellerByCustomer($this->context->cookie->id_customer);
        $is_seller = Seller::isSeller($this->context->cookie->id_customer, $this->context->shop->id);
        
        if (!$is_seller)
            Tools::redirect($this->context->link->getPageLink('my-account', true));

        $seller = new Seller((int)$id_seller);
        
        if ($seller->active == 0)
            Tools::redirect($this->context->link->getPageLink('my-account', true));
        
        $num_products = $seller->getNumProducts();
        $start = 0;
        $limit = Configuration::get('PS_PRODUCTS_PER_PAGE');
        $search_query = '';
        $current_page = 1;

        if (Tools::getValue('orderby') || Tools::getValue('orderway')) {
            $order_by = Tools::getValue('orderby');
            $order_way = Tools::getValue('orderway');
        }
        else {
            $order_by = 'date_add';
            $order_way = 'desc';
        }
        
        if (Tools::getValue('page')) {
            $current_page = Tools::getValue('page');
            $start = (int)($current_page-1) * Configuration::get('PS_PRODUCTS_PER_PAGE');
        }

        if (Tools::getValue('search_query')) {
            $search_query = (string)Tools::getValue('search_query');
            $products = $seller->find($search_query, $this->context->language->id, $start, $limit, $order_by, $order_way);
            if (!$products)
                $num_products = 0;
            else
                $num_products = count($products);
        }
        else {
            $products = $seller->getProducts($this->context->language->id, $start, $limit, $order_by, $order_way);
            $num_products = count($products);
        }
        
        $num_pages = ceil($num_products / $limit);
        			
        $param = array('id_seller' => $seller->id, 'link_rewrite' => $seller->link_rewrite);
        $url_seller_profile = $this->module->getmarketplaceLink('marketplace_seller_rule', $param);
        $id_customer = $this->context->cookie->id_customer;
        $id_cart = $this->context->cookie->id_cart;
        $cart = new Cart($id_cart);
        if ($id_customer) {
            if (Customer::customerHasAddress($id_customer, $cart->id_address_delivery)) {
                $id_zone = Address::getZoneById((int)$cart->id_address_delivery);
            } 
            else {
                $default_country = new Country(Configuration::get('PS_COUNTRY_DEFAULT'), Configuration::get('PS_LANG_DEFAULT'));
                $id_zone = (int)$default_country->id_zone;
            }  
        } 
        else {
            $default_country = new Country(Configuration::get('PS_COUNTRY_DEFAULT'), Configuration::get('PS_LANG_DEFAULT')); 
             $id_zone = (int)$default_country->id_zone;
        }

        if ($products) {
            $i = 0;
            foreach ($products as $p) {
                        $seller_my_carriers = array();                        
                        $seller_my_carriers = SellerTransport::getCarriersForOrder($id_seller, $id_zone); 
                        if (!$seller_my_carriers)
                            $seller_my_carriers = Carrier::getCarriersForOrder($id_zone);

                        $seller_my_shipping_cost = 0;
                        if (count($seller_my_carriers) > 0) {
                            $carrier = new Carrier((int)Configuration::get('PS_CARRIER_DEFAULT'));
                            //$seller_my_shipping_cost = $carrier->getDeliveryPriceByWeight($p['weight'], $id_zone,$carrier->id);
                            
                            if (!$seller_my_shipping_cost)
                                $seller_my_shipping_cost = 0;
                        }
                $products[$i]['seller_shipping_cost'] = $seller_my_shipping_cost + $p['additional_shipping_cost'];

                $params_product_edit = array('id_product' => $p['id_product']);
                $params_product_delete = array('id_product' => $p['id_product'], 'deleteproduct' => 1);
                $params_product_active = array('id_product' => $p['id_product'], 'statusproduct' => 1);
                $edit_product_link = $this->context->link->getModuleLink('marketplace', 'editproduct', $params_product_edit, true);
                $delete_product_link = $this->context->link->getModuleLink('marketplace', 'sellerproducts', $params_product_delete, true);
                $active_product_link = $this->context->link->getModuleLink('marketplace', 'sellerproducts', $params_product_active, true);
                $products[$i]['edit_product_link'] = $edit_product_link;
                $products[$i]['delete_product_link'] = $delete_product_link;
                $products[$i]['active_product_link'] = $active_product_link;
                $products[$i]['real_quantity'] = Product::getRealQuantity($p['id_product'], 0, 0, $this->context->shop->id);

                $offers[$i] = ProductEanComparator::getAllProductsSameSellerBestOffer($p['name'], $this->context->language->id);

                $j = 0;
                if (is_array($offers[$i])) {
                    foreach ($offers[$i] as $s) {
                        $seller_carriers = array();                        
                        $seller_carriers = SellerTransport::getCarriersForOrder($s['id_seller'], $id_zone); 
                        if (!$seller_carriers)              
                            $seller_carriers = Carrier::getCarriersForOrder($id_zone);

                        $seller_shipping_cost = 0;
                        if (count($seller_carriers) > 0) {
                            $carrier = new Carrier((int)Configuration::get('PS_CARRIER_DEFAULT'));//($seller_carriers[0]['id_carrier']);
                            $seller_shipping_cost = $carrier->getDeliveryPriceByWeight($s['weight'], $id_zone) + $s['additional_shipping_cost'];
                            
                            if (!$seller_shipping_cost)
                                $seller_shipping_cost = $s['additional_shipping_cost'];

                            $offers[$i][$j]['carrier_name'] = $carrier->name;

                            $offers[$i][$j]['seller_shipping_cost'] = $seller_shipping_cost; 

                            $offers[$i][$j]['seller_shipping_delay'] = $seller_carriers[0]['delay'];

                            $offers[$i][$j]['price_with_shipping'] = $offers[$i][$j]['price'] + $seller_shipping_cost;
                        }
                        else
                        {
                            $offers[$i][$j]['seller_shipping_cost'] = $s['additional_shipping_cost'];
                            $offers[$i][$j]['price_with_shipping'] = $offers[$i][$j]['price'] + $s['additional_shipping_cost'];
                        }

                        $j++;
                    }

                    foreach ($offers[$i] as $key => $row) {
                        $price_with_shipping[$key] = $row['price_with_shipping'];
                    }

                    array_multisort($price_with_shipping, SORT_ASC, $offers[$i]);
                }
                
                
                $products[$i]['offer_price'] = $offers[$i][0]['price'];

                $products[$i]['seller_offer_shipping_cost'] = $offers[$i][0]['seller_shipping_cost'];
                $products[$i]['id_seller_offer'] = $offers[$i][0]['id_seller'];

                $products[$i]['id_seller'] = $id_seller;

                $i++;
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
            'show_reference' => Configuration::get('MARKETPLACE_SHOW_REFERENCE'),
            'show_quantity' => Configuration::get('MARKETPLACE_SHOW_QUANTITY'),
            'show_price' => Configuration::get('MARKETPLACE_SHOW_PRICE'),
            'show_images' => Configuration::get('MARKETPLACE_SHOW_IMAGES'),
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
            'products' => $products, 
            'num_products' => $num_products,
            'seller_link' => $url_seller_profile,
            'order_by' => $order_by,
            'order_way' => $order_way,
            'search_query' => $search_query,
            'current_page' => $current_page,
            'num_pages' => $num_pages,
            'mesages_not_readed' => SellerIncidenceMessage::getNumMessagesNotReadedBySeller($id_seller),
            'countneworder' => $countneworder,
            'offers' => $offers,
            'num_offers' => count($offers),
            'sign' => $this->context->currency->sign,
            'seller' => $seller,
            'confirmation' => (int)Tools::getValue('confirmation'),
            'incidences' => $incidences,
            'content_only' => 1,
            'modules_dir' => __PS_BASE_URI__.'modules/',
            'menu' => 'module:marketplace/views/templates/front/sellermenu.tpl',
        ));
        
        if (version_compare(_PS_VERSION_, '1.6.0', '<')) 
            $this->context->smarty->assign('version', 15);   
        else
            $this->context->smarty->assign('version', 16);

        //$this->setTemplate('sellerproducts.tpl');
        $this->setTemplate('module:marketplace/views/templates/front/sellerproducts.tpl');
    }

    public function processModifPrice()
    {         
        $id_product = Tools::getValue('id_product');
        $price = Tools::getValue('price');
        $shipping_cost = Tools::getValue('shipping');

        $price = str_replace(",",".",$price);

        $product = new Product($id_product);
        $ean13 = $product->ean13;
        $product->price = (float)$price;
        $product->save();
        die('1');

        /*$id_customer = $this->context->cookie->id_customer;
        $id_cart = $this->context->cookie->id_cart;
        $cart = new Cart($id_cart);
        if ($id_customer) {
            if (Customer::customerHasAddress($id_customer, $cart->id_address_delivery)) {
                $id_zone = Address::getZoneById((int)$cart->id_address_delivery);
            } 
            else {
                $default_country = new Country(Configuration::get('PS_COUNTRY_DEFAULT'), Configuration::get('PS_LANG_DEFAULT'));
                $id_zone = (int)$default_country->id_zone;
            }  
        } 
        else {
            $default_country = new Country(Configuration::get('PS_COUNTRY_DEFAULT'), Configuration::get('PS_LANG_DEFAULT')); 
             $id_zone = (int)$default_country->id_zone;
        }

        
        $offers = ProductEanComparator::getAllProductsSameSeller($ean13,$this->context->language->id);


        
        $j = 0;*/

        /*if (is_array($offers)) {
            foreach ($offers as $s) {
                $seller_carriers = array();                        
                $seller_carriers = SellerTransport::getCarriersForOrder($s['id_seller'], $id_zone); 
                if (!$seller_carriers)              
                    $seller_carriers = Carrier::getCarriersForOrder($id_zone);

                $seller_shipping_cost = 0;
                if (count($seller_carriers) > 0) {
                    $carrier = new Carrier($seller_carriers[0]['id_carrier']);
                    $seller_shipping_cost = $carrier->getDeliveryPriceByWeight($s['weight'], $id_zone,$carrier->id) + $s['additional_shipping_cost'];
                            
                    if (!$seller_shipping_cost)
                        $seller_shipping_cost = $s['additional_shipping_cost'];

                    $offers[$j]['seller_shipping_cost'] = $seller_shipping_cost;

                    $offers[$j]['price_with_shipping'] = $offers[$j]['price'] + $seller_shipping_cost;
                }
                

                $j++;
            }
        }

        foreach ($offers as $key => $row) {         
            $price_with_shipping[$key] = $row['price_with_shipping'];
        }
                
        array_multisort($price_with_shipping, SORT_ASC, $offers);

        if($price + $shipping_cost <= $offers[0]['price_with_shipping'])
        {
            die('-1');
        }
        else*/
        
    }


}