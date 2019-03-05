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

class marketplaceDashboardModuleFrontController extends ModuleFrontController
{
    public $ssl = true;
    
    public function setMedia() {
        parent::setMedia();
        $this->addJqueryUI('ui.datepicker');
    }
    
    public function compare_dates($date_start, $date_end) {
        $endTimestamp = strtotime($date_end);
        $beginTimestamp = strtotime($date_start);
        return ceil(($endTimestamp - $beginTimestamp) / 86400);
    }

    public function initContent() {
        
        parent::initContent();
        
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
        
        $months = array(
            $this->module->l('Jan', 'dashboard'),
            $this->module->l('Feb', 'dashboard'),
            $this->module->l('Mar', 'dashboard'),
            $this->module->l('Apr', 'dashboard'),
            $this->module->l('May', 'dashboard'),
            $this->module->l('Jun', 'dashboard'),
            $this->module->l('Jul', 'dashboard'),
            $this->module->l('Aug', 'dashboard'),
            $this->module->l('Sept', 'dashboard'),
            $this->module->l('Oct', 'dashboard'),
            $this->module->l('Nov', 'dashboard'),
            $this->module->l('Dec', 'dashboard')
        );
        
        $chart_label = '';
        $chart_data = '';
        $date = new DateTime();
        $date->modify('-1 month');

        $from = $date -> format('Y-m-t');
        $date->modify('+2 month');

        $to = $date -> format('Y-m-t');

        //$from = explode(' ', $seller->date_add);
        //$from = $from[0].' 00:00:00';        
        //$to = date('Y-m-d').' 23:59:59';        
        
        if (Tools::isSubmit('submitFilterDate')) {
            $from = Tools::getValue('from').' 00:00:00';
            $to = Tools::getValue('to').' 23:59:59';
        }
        
        $commisions_chart = array();
        $from_array = explode(' ', $from);
        $to_array = explode(' ', $to);

        $date_next = $from_array[0];
        $date_end = $to_array[0];
        
        while ($this->compare_dates($date_next, $date_end) > 0) {
            $year_array = explode('-', $date_next);
            $year = $year_array[0];
            $month_array = explode('-', $date_next);
            $month = $month_array[1];
            $commisions_chart[$year.'-'.$month] = 0;
            $date_next = strtotime('+30 day', strtotime($date_next));
            $date_next = date('Y-m-d', $date_next);
        }
        
       //d($commisions_chart);
        
        $commisions = Dashboard::getCommisionHistoryBySeller($id_seller, $this->context->language->id, $this->context->shop->id, $from, $to);
        
        if ($commisions) {
            foreach ($commisions as $c) {
                $date_add_parts = explode(' ', $c['date_add']);
                $date_add_parts = explode('-', $date_add_parts[0]);
                
                /*if ($date_add_parts[1] < 10)
                    $date_add_parts[1] = '0'.$date_add_parts[1];*/
                
                $index = $date_add_parts[0].'-'.($date_add_parts[1]);
                if (array_key_exists($index, $commisions_chart)) 
                    $commisions_chart[$index] = round($commisions_chart[$index] + $c['commision'], 2);
                else 
                    $commisions_chart[$index] = round($c['commision'], 2);
            }

            foreach ($commisions_chart as $key => $value) {
                $date_parts = explode('-', $key);
                $month_value = $months[$date_parts[1]-1];
                $chart_label .= '"'.$month_value.'-'.$date_parts[0].'",';
                $chart_data .= $value.',';
            }
            
            $chart_label = Tools::substr($chart_label, 0, -1);
            $chart_data = Tools::substr($chart_data, 0, -1);
            
            $sales = Dashboard::getSalesBySeller($id_seller, $from, $to);
            $num_orders = Dashboard::getNewOrdersBySeller($id_seller);
            $cart_value = $sales / Dashboard::getNumOrdersBySeller($id_seller, $from, $to);
            $num_products = Dashboard::getProductQuantityBySeller($id_seller, $from, $to);
            $commision = SellerCommision::getCommisionBySeller($id_seller);
            $benefits = Dashboard::getBenefitsBySeller($id_seller, $from, $to);
        }
        else {
            $sales = 0;
            $num_orders = Dashboard::getNewOrdersBySeller($id_seller);
            $cart_value = 0;
            $num_products = 0;
            $commision = SellerCommision::getCommisionBySeller($id_seller);
            $benefits = 0;
        }
        
        $from = explode(' ', $from);
        $from = $from[0];
        $to = explode(' ', $to);
        $to = $to[0];
        
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

        $commisions_chart = ksort($commisions_chart);

        $countneworder = SellerOrder::getVisitedOrdersSeller($id_seller,$this->context->language->id);

        $this->context->smarty->assign(array(
            'seller_link' => $url_seller_profile,
            'show_import_product' => Configuration::get('MARKETPLACE_SELLER_IMPORT_PROD'),
            'show_orders' => Configuration::get('MARKETPLACE_SHOW_ORDERS'),
            'show_contact' => Configuration::get('MARKETPLACE_SHOW_CONTACT'),
            'show_edit_seller_account' => Configuration::get('MARKETPLACE_SHOW_EDIT_ACCOUNT'),
            'show_manage_orders' => Configuration::get('MARKETPLACE_SHOW_MANAGE_ORDERS'),
            'show_manage_carriers' => Configuration::get('MARKETPLACE_SHOW_MANAGE_CARRIER'),
            'show_dashboard' => Configuration::get('MARKETPLACE_SHOW_DASHBOARD'),
            'show_menu_top' => Configuration::get('MARKETPLACE_MENU_TOP'),
            'show_menu_options' => Configuration::get('MARKETPLACE_MENU_OPTIONS'),
            'sales' => $sales,
            'num_orders' => $num_orders,
            'cart_value' => $cart_value,
            'num_products' => $num_products,
            'commision' => $commision,
            'benefits' => $benefits,
            'chart_label' => $chart_label,
            'chart_data' => $chart_data,
            'currency_iso_code' => $this->context->currency->iso_code,
            'from' => $from,
            'to' => $to,
            'mesages_not_readed' => SellerIncidenceMessage::getNumMessagesNotReadedBySeller($id_seller),
            'total_refund' => (float)(Order::getTotalSlipAmount($id_seller) - (Order::getTotalSlipAmount($id_seller)*0.1)),
            'countneworder' => $countneworder,
            'seller' => $seller,
            'incidences' => $incidences,
            'content_only' => 1,
            'menu' => 'module:marketplace/views/templates/front/sellermenu.tpl',

        ));

        //$this->setTemplate('dashboard.tpl');
        $this->setTemplate('module:marketplace/views/templates/front/dashboard.tpl');
    }

    public function getCommisionBySeller($id_seller) {
        return Db::getInstance()->getValue('SELECT SUM(commision) FROM '._DB_PREFIX_.'seller_commision_history WHERE id_seller = '.(int)$id_seller);
    }
}