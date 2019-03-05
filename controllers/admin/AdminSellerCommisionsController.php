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

class AdminSellerCommisionsController extends ModuleAdminController
{
    //public $asso_type = 'shop';

    public function __construct()
    {
        $this->bootstrap = true;
        $this->table = 'seller_commision';
        $this->className = 'SellerCommision';
        $this->lang = false;
        //$this->allow_export = true;
        
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
        $this->addRowAction('edit');
        
        if (Configuration::get('PS_MULTISHOP_FEATURE_ACTIVE') == 1) {
            $this->getFieldsList();
        }

        return parent::renderList();
    }
    
    public function getFieldsList() {
        $this->_select = 'id_seller_commision, s.name seller_name, a.commision';
        $this->_join = 'LEFT JOIN '._DB_PREFIX_.'seller s ON (s.id_seller = a.id_seller)';
        $this->_where = 'AND s.id_shop = '.(int)$this->context->shop->id;

        if (Tools::isSubmit('submitFilter')) {  
            if (Tools::getValue('seller_commisionFilter_id_seller_commision') != '')
                $this->_where = 'AND a.id_seller_commision = '.(int)Tools::getValue('seller_commisionFilter_id_seller_commision');

            if (Tools::getValue('seller_commisionFilter_seller_name') != '')
                $this->_where = 'AND s.name LIKE "%'.pSQL(Tools::getValue('seller_commisionFilter_seller_name')).'%"';

            if (Tools::getValue('seller_commisionFilter_commision') != '')
                $this->_where = 'AND a.commision = '.(int)Tools::getValue('seller_commisionFilter_commision');
        }

        $this->fields_list = array(
            'id_seller_commision' => array(
                'title' => $this->l('ID Commision'),
                'align' => 'center',
                'class' => 'fixed-width-xs',
                'havingFilter' => true,
            ),
            'seller_name' => array(
                'title' => $this->l('Seller name'),
                'havingFilter' => true,
            ),
            'commision' => array(
                'title' => $this->l('Commision (%)'),
                'havingFilter' => true,
            )
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
        $id_seller_commision = (int)Tools::getValue('id_seller_commision');
        $seller_commision = new SellerCommision($id_seller_commision);
        $seller = new Seller($seller_commision->id_seller);
        
        $this->fields_form = array(
            'legend' => array(
                    'title' => $this->l('Edit seller commision of').' '.$seller->name,
                    'icon' => 'icon-globe'
            ),
            'input' => array(
                    array(
                        'type' => 'text',
                        'label' => $this->l('Commison (%)'),
                        'name' => 'commision',
                        'lang' => false,
                        'col' => 2,
                        'required' => true,
                        'desc' => $this->l('Values: 1-100'),
                    ),
            )
        );

        $this->fields_form['submit'] = array(
                'title' => $this->l('Save'),
        );

        return parent::renderForm();
    }
    
    public function postProcess() {            
        parent::postProcess(); 
    }
}