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

class AdminSellerCommisionsHistoryController extends ModuleAdminController
{
    //public $asso_type = 'shop';

    public function __construct()
    {
        $this->bootstrap = true;
        $this->table = 'seller_commision_history';
        $this->className = 'SellerCommisionHistory';
        $this->lang = false;
        
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
        //$this->addRowAction('view');
        $this->addRowAction('edit');
        //$this->addRowAction('delete');
        
        if (Configuration::get('PS_MULTISHOP_FEATURE_ACTIVE') == 1) {
            $this->getFieldsList();
        }
        
        return parent::renderList();
    }
    
    public function getFieldsList() {
        $states = SellerCommisionHistoryState::getStates((int)$this->context->language->id);
        foreach ($states as $state)
            $this->states_array[$state['id_seller_commision_history_state']] = $state['name'];

        $this->_select = 'a.id_order, MAX(o.reference) as reference,s.name as seller_name, product_name,SUM(a.price) as price, a.quantity, SUM(a.commision) as commision, MAX(schsl.name) as state_name, a.date_add';
        $this->_join = 'LEFT JOIN `'._DB_PREFIX_.'seller` s ON (s.`id_seller` = a.`id_seller`) 
                        LEFT JOIN `'._DB_PREFIX_.'seller_commision_history_state` schs ON (schs.`id_seller_commision_history_state` = a.`id_seller_commision_history_state`)
                        LEFT JOIN `'._DB_PREFIX_.'seller_commision_history_state_lang` schsl ON (schsl.`id_seller_commision_history_state` = a.`id_seller_commision_history_state` AND schsl.id_lang = '.(int)$this->context->language->id.')
                        LEFT JOIN `'._DB_PREFIX_.'product` p ON (a.`id_product` = p.`id_product`) 
                        LEFT JOIN `'._DB_PREFIX_.'product_lang` pl ON (pl.`id_product` = p.`id_product` AND pl.id_lang = '.(int)$this->context->language->id.' AND pl.id_shop = '.(int)$this->context->shop->id.') 
                        LEFT JOIN `'._DB_PREFIX_.'orders` o ON (o.`id_order` = a.`id_order`)';

        $this->_where = 'AND a.id_shop = '.(int)$this->context->shop->id .' AND o.reference != "" GROUP BY a.id_order';
        $this->_orderBy = 'date_upd';
        $this->_orderWay = 'DESC';

        if (Tools::getValue('seller_incidenceOrderby')) {  
            $this->_orderBy = pSQL(Tools::getValue('seller_incidenceOrderby'));
            $this->_orderWay = pSQL(Tools::getValue('seller_incidenceOrderway'));
        }

        $this->fields_list = array(
            'id_order' => array(
                'title' => $this->l('Order ID'),
                'align' => 'center',
                'class' => 'fixed-width-xs',
                'havingFilter' => true,
            ),
            'reference' => array(
                'title' => $this->l('Order reference'),
                'align' => 'center',
                'class' => 'fixed-width-xs',
                'havingFilter' => true,
            ),
            'seller_name' => array(
                'title' => $this->l('Seller name'),
                'havingFilter' => true,
            ),
            'product_name' => array(
                'title' => $this->l('Product name'),
                'havingFilter' => true,
            ),
            'price' => array(
                'title' => $this->l('Product price'),
                'havingFilter' => true,
                'align' => 'text-right',
                'type' => 'price',
            ),
            'quantity' => array(
                'title' => $this->l('Product quantity'),
                'havingFilter' => true,
                'align' => 'text-right',
            ),
            'commision' => array(
                'title' => $this->l('Commision (€)'),
                'havingFilter' => true,
                'align' => 'text-right',
                'type' => 'price',
            ),
            'state_name' => array(
                'title' => $this->l('Payment state'),
                'type' => 'select',
                'list' => $this->states_array,
                'filter_key' => 'a!id_seller_commision_history_state',
                'filter_type' => 'int',
                'order_key' => 'state_name',
            ),
            'date_add' => array(
                'title' => $this->l('Date add'),
                'type' => 'datetime',
                'orderby' => true,
                'search' => true,
                'havingFilter' => true,
            ),
        );

        $this->bulk_actions = array(
                'delete' => array(
                        'text' => $this->l('Delete selected'),
                        'confirm' => $this->l('Delete selected items?'),
                        'icon' => 'icon-trash'
                )
        );
    }

    public function renderForm()
    {
        $states = SellerCommisionHistoryState::getStates((int)$this->context->language->id);
        
        $this->fields_form = array(
            'legend' => array(
                    'title' => $this->l('Change state of commision '),
                    'icon' => 'icon-globe'
            ),
            'input' => array(
                    array(
                        'type' => 'text',
                        'label' => $this->l('Commison (€)'),
                        'name' => 'commision',
                        'lang' => false,
                        'col' => 2,
                        'required' => false,
                        'disabled' => true,
                    ),
                    array(
                        'type' => 'select',
                        'label' => $this->l('Payment state'),
                        'name' => 'id_seller_commision_history_state',
                        'required' => false,
                        'options' => array(
                              'query' => $states,
                              'id' => 'id_seller_commision_history_state',
                              'name' => 'name'
                        )
                    ),
            )
        );

        $this->fields_form['submit'] = array(
                'title' => $this->l('Save'),
        );

        return parent::renderForm();
    }
    
    public function postProcess() {     
        if (Tools::isSubmit('submitAddseller_commision_history')) {
            $id_seller_commision_history = (int)Tools::getValue('id_seller_commision_history');
            $seller_commision_history = new SellerCommisionHistory($id_seller_commision_history);
            $seller_commision_history->id_seller_commision_history_state = (int)Tools::getValue('id_seller_commision_history_state');
            $seller_commision_history->update();
        }
        else {
            parent::postProcess(); 
        }
    }
}