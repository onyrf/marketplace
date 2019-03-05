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

use PrestaShop\PrestaShop\Adapter\Image\ImageRetriever;
use PrestaShop\PrestaShop\Adapter\Product\PriceFormatter;
use PrestaShop\PrestaShop\Core\Product\ProductListingPresenter;
use PrestaShop\PrestaShop\Adapter\Product\ProductColorsRetriever;

class marketplaceSellerproductlistModuleFrontController extends ModuleFrontController
{
    public $ssl = true;
    public $nbProducts;
    public $cat_products;
    public $seller;
    public $orderBy;

    public function setMedia() {   
        parent::setMedia(); 
        
        if (version_compare(_PS_VERSION_, '1.6.0', '<')) {
            $this->addCSS(_PS_BASE_URL_.__PS_BASE_URI__.'category.css', 'all');
            $this->addCSS(_PS_BASE_URL_.__PS_BASE_URI__.'product_list.css', 'all');
        }
        else {
            $this->addCSS(_THEME_CSS_DIR_.'category.css', 'all');
            $this->addCSS(_THEME_CSS_DIR_.'product_list.css', 'all');
        }  
    }

    public function initContent() {
        
        parent::initContent();
        
        if(!Tools::getValue('id_seller'))
            Tools::redirect($this->context->link->getPageLink('my-account', true));
        
        $id_seller = (int)Tools::getValue('id_seller');
        $this->seller = new Seller($id_seller);
        
        //$this->productSort();
        $this->assignProductList();

        $param = array('id_seller' => $this->seller->id, 'link_rewrite' => $this->seller->link_rewrite);
        
	if (version_compare(_PS_VERSION_, '1.6.0.12', '>'))
            $url_seller_profile = $this->module->getmarketplaceLink('marketplace_seller_rule', $param);
        else
            $url_seller_profile = $this->context->link->getModuleLink('marketplace', 'sellerprofile', $param);

        $searchVariables = array(            
            'label' => '',
            'products' => (isset($this->products) && $this->products) ? $this->products : null,
            'sort_orders' => null,
            'sort_selected' => null,
            'pagination' => null,
            'rendered_facets' => null,
            'rendered_active_filters' => null,            
        );

        $this->context->smarty->assign(array(
            'seller' => $this->seller,
            'products' => (isset($this->products) && $this->products) ? $this->products : null,
            'seller_link' => $url_seller_profile,
            'add_prod_display' => Configuration::get('PS_ATTRIBUTE_CATEGORY_DISPLAY'),
            'homeSize' => Image::getSize(ImageType::getFormatedName('home')),
            'allow_oosp' => (int)Configuration::get('PS_ORDER_OUT_OF_STOCK'),
            'comparator_max_item' => (int)Configuration::get('PS_COMPARATOR_MAX_ITEM'),
            //'listing' => $searchVariables,

            /*'products' => $products,
            'sort_orders' => $sort_orders,
            'sort_selected' => $sort_selected,
            'pagination' => $pagination,
            'rendered_facets' => $rendered_facets,
            'rendered_active_filters' => $rendered_active_filters,*/

        ));

        

        //$rendered_products = $this->render('catalog/_partials/products', array('listing' => $search));

        //$this->setTemplate('sellerproductlist.tpl');
        $this->setTemplate('module:marketplace/views/templates/front/sellerproductlist.tpl');
    }    
    
    protected function assignProductList()
    {    
        $this->nbProducts = $this->seller->getNumActiveProducts();
        $this->orderBy = 'p.`id_product`';

        //$this->pagination((int)$this->nbProducts); // Pagination must be call after "getProducts"
        
        if (!Tools::getValue('p'))
            $this->p = 0;
        else 
            $this->p = (int)(Tools::getValue('p')-1) * Configuration::get('PS_PRODUCTS_PER_PAGE');

        $this->n = Configuration::get('PS_PRODUCTS_PER_PAGE');


        $blocks_products = $this->seller->getProducts($this->context->language->id, $this->p, $this->n, $this->orderBy, $this->orderWay, false, true);

        $this->products = $this->prepareBlocksProducts($blocks_products);

        $this->context->smarty->assign(array(
                'pages_nb' => ceil($this->nbProducts / (int)$this->n),
                'nbProducts' => $this->nbProducts,
        ));
    }

    public function prepareBlocksProducts($products)
    {
        if ($products != false)
        {
            $products_for_template = [];
            $assembler = new ProductAssembler($this->context);
            $presenterFactory = new ProductPresenterFactory($this->context);
            $presentationSettings = $presenterFactory->getPresentationSettings();
            $presenter = new ProductListingPresenter(new ImageRetriever($this->context->link), $this->context->link, new PriceFormatter(), new ProductColorsRetriever(), $this->context->getTranslator());
            $products_for_template = [];
            foreach ($products as $rawProduct)
            {
                $products_for_template[] = $presenter->present($presentationSettings, $assembler->assembleProduct($rawProduct), $this->context->language);
            }

            return $products_for_template;
        }
        else
        {
            return false;
        }
    }

    protected function getProducts()
    {
        $category = new Category((int) Configuration::get('HOME_FEATURED_CAT'));

        $searchProvider = new CategoryProductSearchProvider(
            $this->context->getTranslator(),
            $category
        );

        $context = new ProductSearchContext($this->context);

        $query = new ProductSearchQuery();

        $nProducts = Configuration::get('HOME_FEATURED_NBR');
        if ($nProducts < 0) {
            $nProducts = 12;
        }

        $query
            ->setResultsPerPage($nProducts)
            ->setPage(1)
        ;

        if (Configuration::get('HOME_FEATURED_RANDOMIZE')) {
            $query->setSortOrder(SortOrder::random());
        } else {
            $query->setSortOrder(new SortOrder('product', 'position', 'asc'));
        }

        $result = $searchProvider->runQuery(
            $context,
            $query
        );

        $assembler = new ProductAssembler($this->context);

        $presenterFactory = new ProductPresenterFactory($this->context);
        $presentationSettings = $presenterFactory->getPresentationSettings();
        $presenter = new ProductListingPresenter(
            new ImageRetriever(
                $this->context->link
            ),
            $this->context->link,
            new PriceFormatter(),
            new ProductColorsRetriever(),
            $this->context->getTranslator()
        );

        $products_for_template = [];

        foreach ($result->getProducts() as $rawProduct) {
            $products_for_template[] = $presenter->present(
                $presentationSettings,
                $assembler->assembleProduct($rawProduct),
                $this->context->language
            );
        }

        return $products_for_template;
    }

    
}