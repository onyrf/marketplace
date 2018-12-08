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

if (!defined('_PS_VERSION_'))
  exit;

//use PrestaShop\PrestaShop\Core\Module\WidgetInterface;

class marketplace extends Module
{
    const INSTALL_SQL_FILE = 'install.sql';
    public $output;
    public $tmFamilyProducts = NULL;
    
    private $_html = '';
    private $_postErrors = array();
    private $_filters = array();

    private $_productCommentsCriterionTypes = array();
    private $_baseUrl;
    private $secure_key ='';
    
    public function __construct()
    {
        $this->name = 'marketplace';
        $this->tab = 'market_place';
        $this->version = '1.0.0';
        $this->need_instance = 0;
        $this->bootstrap = true;
        $this->module_key = "5116f4d7344d9fdd2e57b2bbc885852e";
        $this->controllers = array(
            'addproduct', 
            'addseller', 
            'contactseller', 
            'editproduct',
            'editcarrier', 
            'editseller',
            'favoriteseller',
            'selleraccount',
            'sellercomments',
            'sellercommentsorder',
            'sellermessages',
            'sellerorders',
            'sellerpayment',
            'sellerproductlist',
            'sellerproducts',
            'sellerprofile',
            'Sellerprofilevisite',
            'sellers',
            'dashboard',
            'orders',
            'carriers',
            'addcarrier',
            'csvproducts',
            'searchproduct',
            'sellerofferview',
            'sellerofferadd',
            'sellerholidays',
        );

        parent::__construct();

        $this->displayName = "Marketplace";
        $this->description = $this->l('Allow to your customers sell in your shop to exchange for a commission.');

        $this->confirmUninstall = $this->l('Are you sure you want to uninstall?');

        $titre_message = $this->l('Leave a message');

        include_once dirname(__FILE__).'/classes/Seller.php';
        include_once dirname(__FILE__).'/classes/SellerProduct.php';
        include_once dirname(__FILE__).'/classes/SellerCommision.php';
        include_once dirname(__FILE__).'/classes/SellerCommisionHistory.php';
        include_once dirname(__FILE__).'/classes/SellerCommisionHistoryState.php';
        include_once dirname(__FILE__).'/classes/SellerOrder.php';
        include_once dirname(__FILE__).'/classes/SellerTransport.php';
        include_once dirname(__FILE__).'/classes/SellerPayment.php';
        include_once dirname(__FILE__).'/classes/SellerIncidence.php';
        include_once dirname(__FILE__).'/classes/SellerIncidenceMessage.php';
        include_once dirname(__FILE__).'/classes/SellerEmail.php';
        include_once dirname(__FILE__).'/classes/SellerComment.php';
        include_once dirname(__FILE__).'/classes/SellerCommentCriterion.php';
        include_once dirname(__FILE__).'/classes/SellerCategory.php';
        include_once dirname(__FILE__).'/classes/CategoryTree.php';
        include_once dirname(__FILE__).'/classes/Dashboard.php';        
        
        include_once dirname(__FILE__).'/classes/SellerHoliday.php';
        include_once dirname(__FILE__).'/classes/ProductEanComparator.php';

        include_once dirname(__FILE__).'/classes/SellerProductComment.php';
        include_once dirname(__FILE__).'/classes/SellerProductCriterion.php';
        include_once dirname(__FILE__).'/classes/SellerProductCommentCriterion.php';


    }
  
    public function install() 
    { 
        //GENERAL SETTINGS
        Configuration::updateValue('MARKETPLACE_MODERATE_SELLER', 1);
        Configuration::updateValue('MARKETPLACE_MODERATE_MESSAGE', 0);
        Configuration::updateValue('MARKETPLACE_MODERATE_PRODUCT', 0);
        Configuration::updateValue('MARKETPLACE_CUSTOMER_GROUP_3', 1);
        Configuration::updateValue('MARKETPLACE_COMMISIONS_ORDER', 0);
        Configuration::updateValue('MARKETPLACE_COMMISIONS_STATE', 1);
        Configuration::updateValue('MARKETPLACE_ORDER_STATE', 2);
        Configuration::updateValue('MARKETPLACE_FIXED_COMMISSION', 0);
        Configuration::updateValue('MARKETPLACE_VARIABLE_COMMISSION', 90);
        Configuration::updateValue('MARKETPLACE_SHIPPING_COMMISSION', 0);
        Configuration::updateValue('MARKETPLACE_TAX_COMMISSION', 0);
        Configuration::updateValue('MARKETPLACE_CANCEL_COMMISSION_6', 1);
        Configuration::updateValue('MARKETPLACE_CANCEL_COMMISSION_7', 1);
        Configuration::updateValue('MARKETPLACE_CANCEL_COMMISSION_8', 1);
        Configuration::updateValue('MARKETPLACE_SHOW_CONTACT', 1);
        Configuration::updateValue('MARKETPLACE_SHOW_DASHBOARD', 1);
        Configuration::updateValue('MARKETPLACE_SHOW_MANAGE_ORDERS', 1);
        Configuration::updateValue('MARKETPLACE_SHOW_MANAGE_CARRIER', 0);
        Configuration::updateValue('MARKETPLACE_SHOW_PROFILE', 1);
        Configuration::updateValue('MARKETPLACE_SHOW_ORDERS', 1);
        Configuration::updateValue('MARKETPLACE_SHOW_EDIT_ACCOUNT', 1);
        Configuration::updateValue('MARKETPLACE_SHOW_EDIT_PRODUCT', 1);
        Configuration::updateValue('MARKETPLACE_SHOW_DELETE_PRODUCT', 0);
        Configuration::updateValue('MARKETPLACE_SHOW_ACTIVE_PRODUCT', 0);
        Configuration::updateValue('MARKETPLACE_SELLER_FAVORITE', 1);
        Configuration::updateValue('MARKETPLACE_SELLER_RATING', 1);
        Configuration::updateValue('MARKETPLACE_NEW_PRODUCTS', 1);
        Configuration::updateValue('MARKETPLACE_SHOW_SELLER_PLIST', 1);
        Configuration::updateValue('MARKETPLACE_SELLER_IMPORT_PROD', 1);
        Configuration::updateValue('DELAY_DELIVERY', 'PT48H');
        
        //SELLER ACCOUNT
        Configuration::updateValue('MARKETPLACE_SHOW_SHOP_NAME', 1);
        Configuration::updateValue('MARKETPLACE_SHOW_LANGUAGE', 0);
        Configuration::updateValue('MARKETPLACE_SHOW_CIF', 1);
        Configuration::updateValue('MARKETPLACE_SHOW_PHONE', 1);
        Configuration::updateValue('MARKETPLACE_SHOW_FAX', 1);
        Configuration::updateValue('MARKETPLACE_SHOW_ADDRESS', 1);
        Configuration::updateValue('MARKETPLACE_SHOW_COUNTRY', 0);
        Configuration::updateValue('MARKETPLACE_SHOW_STATE', 0);
        Configuration::updateValue('MARKETPLACE_SHOW_CITY', 0);
        Configuration::updateValue('MARKETPLACE_SHOW_POSTAL_CODE', 0);
        Configuration::updateValue('MARKETPLACE_SHOW_DESCRIPTION', 1);
        Configuration::updateValue('MARKETPLACE_SHOW_LOGO', 1);
        
        //SELLER PRODUCT
        Configuration::updateValue('MARKETPLACE_SHOW_REFERENCE', 0);
        Configuration::updateValue('MARKETPLACE_SHOW_EAN13', 1);
        Configuration::updateValue('MARKETPLACE_SHOW_UPC', 0);
        Configuration::updateValue('MARKETPLACE_SHOW_WIDTH', 0);
        Configuration::updateValue('MARKETPLACE_SHOW_HEIGHT', 0);
        Configuration::updateValue('MARKETPLACE_SHOW_DEPTH', 0);
        Configuration::updateValue('MARKETPLACE_SHOW_WEIGHT', 0);
        Configuration::updateValue('MARKETPLACE_SHOW_SHIP_PRODUCT', 0);
        Configuration::updateValue('MARKETPLACE_SHOW_CONDITION', 0);
        Configuration::updateValue('MARKETPLACE_SHOW_QUANTITY', 1);
        Configuration::updateValue('MARKETPLACE_SHOW_MINIMAL_QTY', 0);
        Configuration::updateValue('MARKETPLACE_SHOW_AVAILABLE_NOW', 0);
        Configuration::updateValue('MARKETPLACE_SHOW_AVAILABLE_LAT', 0);
        Configuration::updateValue('MARKETPLACE_SHOW_AVAILABLE_DATE', 0);
        Configuration::updateValue('MARKETPLACE_SHOW_PRICE', 1);
        Configuration::updateValue('MARKETPLACE_SHOW_OFFER_PRICE', 0);
        Configuration::updateValue('MARKETPLACE_SHOW_TAX', 1);
        Configuration::updateValue('MARKETPLACE_SHOW_DESC_SHORT', 1);
        Configuration::updateValue('MARKETPLACE_SHOW_DESC', 1);
        Configuration::updateValue('MARKETPLACE_SHOW_META_KEYWORDS', 0);
        Configuration::updateValue('MARKETPLACE_SHOW_META_TITLE', 0);
        Configuration::updateValue('MARKETPLACE_SHOW_META_DESC', 0);  
        Configuration::updateValue('MARKETPLACE_SHOW_LINK_REWRITE', 0);  
        Configuration::updateValue('MARKETPLACE_SHOW_IMAGES', 1);
        Configuration::updateValue('MARKETPLACE_MAX_IMAGES', 3);
        Configuration::updateValue('MARKETPLACE_SHOW_SUPPLIERS', 0); 
        Configuration::updateValue('MARKETPLACE_NEW_SUPPLIERS', 0);
        Configuration::updateValue('MARKETPLACE_SHOW_MANUFACTURERS', 0);
        Configuration::updateValue('MARKETPLACE_NEW_MANUFACTURERS', 0);
        Configuration::updateValue('MARKETPLACE_SHOW_CATEGORIES', 1);
        Configuration::updateValue('MARKETPLACE_SHOW_FEATURES', 0);
        Configuration::updateValue('MARKETPLACE_SHOW_ATTRIBUTES', 0);
        Configuration::updateValue('MARKETPLACE_SHOW_VIRTUAL', 0);
        
        //SELLER PAYMENT
        Configuration::updateValue('MARKETPLACE_PAYPAL', 1);
        Configuration::updateValue('MARKETPLACE_BANKWIRE', 1);
        
        //EMAIL
        Configuration::updateValue('MARKETPLACE_SEND_ADMIN', Configuration::get('PS_SHOP_EMAIL'));
        Configuration::updateValue('MARKETPLACE_SEND_ADMIN_REGISTER', 1);
        Configuration::updateValue('MARKETPLACE_SEND_ADMIN_PRODUCT', 1);
        Configuration::updateValue('MARKETPLACE_SEND_SELLER_WELCOME', 1);
        Configuration::updateValue('MARKETPLACE_SEND_SELLER_ACTIVE', 1);
        Configuration::updateValue('MARKETPLACE_SEND_PRODUCT_ACTIVE', 1);
        Configuration::updateValue('MARKETPLACE_SEND_MESSAGE_ACTIVE', 1);
        Configuration::updateValue('MARKETPLACE_SEND_PRODUCT_SOLD', 1);
        
        //SELLER COMMENT
        Configuration::updateValue('MARKETPLACE_MODERATE_COMMENTS', 0);
        Configuration::updateValue('MARKETPLACE_ALLOW_GUEST_COMMENT', 0);
        Configuration::updateValue('MARKETPLACE_SEND_COMMENT_SELLER', 1);
        Configuration::updateValue('MARKETPLACE_SEND_COMMENT_ADMIN', 1);
        
        //SELLER BALANCE
        Configuration::updateValue('MARKETPLACE_BALANCE_DMIN_SELLER', 1);
        Configuration::updateValue('MARKETPLACE_BALANCE_DMAX_SELLER', 1);
        Configuration::updateValue('MARKETPLACE_ACTIVE_SELLER_BALANCE', 1);
        

        // PRODUCT COMPARATOR
        Configuration::updateValue('JPRODUCTCOMPARATOR_SHOW_SHIPPING', 1);

        //THEME FRONT OFFICE
        if (version_compare(_PS_VERSION_, '1.6', '<')) {
            Configuration::updateValue('MARKETPLACE_THEME', 'default');
            Configuration::updateValue('MARKETPLACE_TABS', 0);
        }
        else {
            Configuration::updateValue('MARKETPLACE_THEME', 'default-bootstrap');
            Configuration::updateValue('MARKETPLACE_TABS', 1);
        }
        
        Configuration::updateValue('MARKETPLACE_MENU_OPTIONS', 0);
        Configuration::updateValue('MARKETPLACE_MENU_TOP', 0);
        
        //seller holidays
        Configuration::updateValue('MARKETPLACE_SELLER_HOLIDAYS', 1);
        
        $token = uniqid(rand(), true);
	    Configuration::updateValue('MARKETPLACE_TOKEN', $token);
        
        //SELLER PRODUCT COMMENT
        Configuration::updateValue('JSELLERPRODUCTSCOMMENTS_MODERATE', 0);

        Configuration::updateValue('MARKETPLACE_BALANCE_DMIN_SELLER','2017-07-01');
        Configuration::updateValue('MARKETPLACE_BALANCE_DMAX_SELLER','2017-08-01');
        Configuration::updateValue('MARKETPLACE_SEARCHBAR_TEMPLATE_ITEM','default-with-price.tpl');
        
        Configuration::updateValue('MARKETPLACE_SEARCHBAR_TEMPLATE', "default.tpl");
        Configuration::updateValue('MARKETPLACE_SEARCHBAR_STYLESHEET', "default.css");

        $menu_marketplace = array(
            'en' => 'MarketPlace', 
            'es' => 'MarketPlace',
            'fr' => 'MarketPlace',
            'it' => 'MarketPlace',
            'br' => 'MarketPlace',
        );

        $this->createTab('Adminmarketplace', $menu_marketplace);
        
        $menu_marketplace_sellers = array(
            'en' => 'Sellers', 
            'es' => 'Vendedores',
            'fr' => 'Vendeurs',
            'it' => 'Venditori',
            'br' => 'Sellers',
        );
        
        $this->createTab('AdminSellers', $menu_marketplace_sellers, 'Adminmarketplace','group');
        
        $menu_marketplace_seller_products = array(
            'en' => 'Seller Products', 
            'es' => 'Productos de los vendedores',
            'fr' => 'Produits des vendeurs',
            'it' => 'Prodotti venditore',
            'br' => 'Produtos de vendedores',
        );
        
        $this->createTab('AdminSellerProducts', $menu_marketplace_seller_products, 'Adminmarketplace','business_center');
        
        $menu_marketplace_seller_commissions = array(
            'en' => 'Seller Commissions', 
            'es' => 'Comisiones de los vendedores',
            'fr' => 'Commissions des vendeurs',
            'it' => 'Commissioni  dei venditori',
            'br' => 'Seller Commissions',
        );
        
        $this->createTab('AdminSellerCommisions', $menu_marketplace_seller_commissions, 'Adminmarketplace','attach_money');
        
        $menu_marketplace_seller_commission_history = array(
            'en' => 'Seller Commisions History', 
            'es' => 'Historial de comisiones',
            'fr' => 'Historique de commissions',
            'it' => 'Precedenti delle commissioni',
            'br' => 'Seller Commisions History',
        );
        
        $this->createTab('AdminSellerCommisionsHistory', $menu_marketplace_seller_commission_history, 'Adminmarketplace','list');        
        

        $menu_marketplace_seller_commissiom_history_states = array(
            'en' => 'Seller Payment States', 
            'es' => 'Estado de los pagos',
            'fr' => 'Etats des paiements',
            'it' => 'Stati dei pagamenti',
            'br' => 'Seller Payment States',
        );
        
        $this->createTab('AdminSellerCommisionsHistoryStates', $menu_marketplace_seller_commissiom_history_states, 'Adminmarketplace','check_circle');
        
        $menu_marketplace_seller_comments = array(
            'en' => 'Ratings and comments', 
            'es' => 'Valoraciones y comentarios',
            'fr' => 'Notes et commentaires des vendeurs',
            'it' => 'Valutazioni e commenti',
            'br' => 'Ratings and comments',
        );

        $this->createTab('AdminSellerComments', $menu_marketplace_seller_comments, 'Adminmarketplace','question_answer');
        
        $menu_marketplace_incidences = array(
            'en' => 'Seller Messages', 
            'es' => 'Mensajes',
            'fr' => 'Messages',
            'it' => 'Messaggi',
            'br' => 'Seller Messages',
        );
        
        $this->createTab('AdminSellerIncidences', $menu_marketplace_incidences, 'Adminmarketplace','message');
        
        $menu_marketplace_seller_emails = array(
            'en' => 'Seller Emails', 
            'es' => 'Emails',
            'fr' => 'Emails',
            'it' => 'Emails',
            'br' => 'Emails',
        );

        $this->createTab('AdminSellerEmails', $menu_marketplace_seller_emails, 'Adminmarketplace','mail_outline');
        
        $this->addQuickAccess();
        
        //MOVE FILE TO OVERRIDE        
        if(!file_exists(_PS_ROOT_DIR_.'/controllers/front/PdfDeliverySlipController.php'))
            copy(_PS_ROOT_DIR_.'/modules/marketplace/override/files/PdfDeliverySlipController.php',_PS_ROOT_DIR_.'/controllers/front/PdfDeliverySlipController.php');

        $current_theme_path = _PS_ALL_THEMES_DIR_.$this->context->shop->theme_name;

        $current_theme_admin_path = _PS_BO_ALL_THEMES_DIR_;

        if(file_exists($current_theme_path.'/templates/checkout/_partials/cart-detailed-product-line.tpl'))
        {
            //delete files
            unlink($current_theme_path.'/templates/checkout/_partials/cart-detailed-product-line.tpl');

            copy(_PS_ROOT_DIR_.'/modules/marketplace/override/files/cart-detailed-product-line.tpl',$current_theme_path.'/templates/checkout/_partials/cart-detailed-product-line.tpl');
        }

        if(file_exists($current_theme_path.'/templates/customer/order-detail.tpl'))
        {
            //delete files
            unlink($current_theme_path.'/templates/customer/order-detail.tpl');

            copy(_PS_ROOT_DIR_.'/modules/marketplace/override/files/order-detail.tpl',$current_theme_path.'/templates/customer/order-detail.tpl');
        }

        if(file_exists($current_theme_admin_path.'default/template/controllers/orders/_shipping.tpl'))
        {
            //delete files
            unlink($current_theme_admin_path.'default/template/controllers/orders/_shipping.tpl');

            copy(_PS_ROOT_DIR_.'/modules/marketplace/override/files/_shipping.tpl',$current_theme_admin_path.'default/template/controllers/orders/_shipping.tpl');
        }
        //CREATE ORDER STATES
        
        if (!parent::install() OR 
                !$this->registerHook('displayRefundConfirm') OR
                !$this->registerHook('displayHeader') OR 
                !$this->registerHook('backOfficeHeader') OR
                !$this->registerHook('displayCustomerAccount') OR 
                !$this->registerHook('displayProductAdditionalInfo') OR
                !$this->registerHook('displayProductListReviews') OR
                
                !$this->registerHook('displayFooter') OR 
                !$this->registerHook('actionValidateOrder') OR

                !$this->registerHook('actionProductDelete') OR
                !$this->registerHook('actionProductUpdate') OR
                !$this->registerHook('actionProductAdd') OR

                !$this->registerHook('actionAuthentication') OR

                !$this->registerHook('actionOrderStatusPostUpdate') OR
                !$this->registerHook('moduleRoutes') OR
                
                !$this->registerHook('displayFooterProduct') OR
                !$this->registerHook('displayRightColumnProduct') OR
                !$this->registerHook('displayLeftColumnProduct') OR
                                
                //seller product comment
                !$this->registerHook('displayProductListReviews') OR
                !$this->registerHook('displayTop') OR

                //contacter le vendeur
                !$this->registerHook('displayContactSeller') OR
                !$this->registerHook('displayContactAdmin') OR
                !$this->registerHook('displaySoldAndSell') OR
                
                !$this->createImageFolder('sellers') OR
                !$this->createImageFolder('pid') OR
                !$this->createImageFolder('rcs') OR
                !$this->createTables() OR
                !$this->addData() OR
                !$this->createHook('displayMarketplaceHeader') OR
                !$this->createHook('displayMarketplaceMenu') OR
                !$this->createHook('displayMarketplaceAfterMenu') OR
                !$this->createHook('displayMarketplaceMenuOptions') OR
                !$this->createHook('displayMarketplaceFooter') OR
                !$this->createHook('displayMarketplaceFormAddProduct') OR
                !$this->createHook('actionMarketplaceAfterAddProduct') OR
                !$this->createHook('actionMarketplaceBeforeAddProduct') OR
                !$this->createHook('displayMarketplaceFormAddSeller') OR
                !$this->createHook('displayMarketplaceHeaderProfile') OR
                !$this->createHook('displayMarketplaceFooterProfile') OR
                !$this->createHook('actionMarketplaceAfterAddSeller') OR
                !$this->createHook('actionMarketplaceBeforeAddSeller') OR
                !$this->createHook('actionMarketplaceAfterUpdateSeller') OR
                !$this->createHook('actionMarketplaceBeforeUpdateSeller') OR
                !$this->createHook('actionMarketplaceAfterUpdateProduct') OR
                !$this->createHook('actionMarketplaceBeforeUpdateProduct') OR
                !$this->createHook('actionMarketplaceSellerProducts') OR
                !$this->createHook('displayMarketplaceTableProfile'))
            return false;

            $this->_clearCache('*');

        return true;
    }
    
    public function createTablesSellerHolidays() {
        return Db::getInstance()->Execute('
            CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'seller_holiday` (
            `id_seller_holiday` int( 10 ) NOT NULL AUTO_INCREMENT ,
            `id_seller` int( 10 ) NOT NULL ,
            `from` DATE NULL DEFAULT NULL ,
            `to` DATE NULL DEFAULT NULL ,
            PRIMARY KEY ( `id_seller_holiday` )
            ) ENGINE = InnoDB DEFAULT CHARSET=utf8;'
        );
    }
    
    private function _setBaseUrl()
    {
        $this->_baseUrl = 'index.php?';
        foreach ($_GET as $k => $value)
            if (!in_array($k, array('deleteCriterion', 'editCriterion')))
                $this->_baseUrl .= $k.'='.$value.'&';
        $this->_baseUrl = rtrim($this->_baseUrl, '&');
    }

    public function deleteTablesSellerHolidays() 
    {
        return Db::getInstance()->Executes('DROP TABLE IF EXISTS `'._DB_PREFIX_.'seller_holiday`');
    }

    public function hookDisplayRightColumnProduct(){
               
        $id_product = (int)Tools::getValue('id_product');
        
        $product = new Product($id_product, null, $this->context->language->id, $this->context->shop->id);
        
        $offersOther = ProductEanComparator::getOtherProductsBestOffer($id_product,$product->name, $this->context->language->id);
        
        $id_seller = Seller::getSellerByProduct($id_product);
        
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

        $i=0;

        if (is_array($offersOther)) {
            foreach ($offersOther as $s) {

                $param = array('id_seller' => $s['id_seller'], 'link_rewrite' => $s['seller_link_rewrite']);
                $url_seller_profile = Module::getInstanceByName('marketplace')->getmarketplaceLink('marketplace_seller_rule', $param);

                $offersOther[$i]['seller_link'] = $url_seller_profile;
                if (Seller::hasImage($s['id_customer']))
                    $offersOther[$i]['has_image'] = 1;
                else
                    $offersOther[$i]['has_image'] = 0;

                if(Configuration::get('MARKETPLACE_SELLER_RATING')) {
                    $marketplace = Module::getInstanceByName('marketplace');
                    
                        $average = SellerComment::getRatings($s['id_seller']);
                        $averageTotal = SellerComment::getCommentNumber($s['id_seller']);
                        if ($averageTotal > 0)
                            $averageMiddle = ceil($average['avg']);  
                        else
                            $averageMiddle = 0;
                    

                    $offersOther[$i]['averageTotal'] = (int)$averageTotal;
                    $offersOther[$i]['averageMiddle'] = (int)$averageMiddle; 
                }

                $seller_carriers = array();

                //if (class_exists('SellerCarrier')) 
                $seller_carriers = SellerTransport::getCarriersForOrder($s['id_seller'], $id_zone); 
                if (!$seller_carriers)                
                    $seller_carriers = Carrier::getCarriersForOrder($id_zone); 
                

                $seller_shipping_cost = 0;
                if (count($seller_carriers) > 0) {
                    $carrier = new Carrier($seller_carriers[0]['id_carrier']);
                    $seller_shipping_cost = $carrier->getDeliveryPriceByWeight($s['weight'], $id_zone);
                    //$seller_shipping_cost = $seller_carriers[0]['price'];
                    if (!$seller_shipping_cost)
                        $seller_shipping_cost = 0;

                    $seller_shipping_cost = $seller_shipping_cost + $s['additional_shipping_cost'];

                    $offersOther[$i]['carrier_name'] = $carrier->name; 
                    $offersOther[$i]['seller_shipping_cost'] = $seller_shipping_cost; 
                    $offersOther[$i]['seller_shipping_delay'] = $seller_carriers[0]['delay'];
                    $offersOther[$i]['price_with_shipping'] = $offersOther[$i]['price'] + $seller_shipping_cost;
                }

                if (Seller::hasImage($s['id_customer']))
                    $offersOther[$i]['seller_has_image'] = 1;
                else
                    $offersOther[$i]['seller_has_image'] = 0;

                $i++;
            }
        }
        
        foreach ($offersOther as $key => $row) {           
            $price_with_shipping[$key] = $row['price_with_shipping']+$row['additional_shipping_cost'];
        }
                
        array_multisort($price_with_shipping, SORT_ASC, $offersOther);

        $this->context->smarty->assign(array(
            'show_shipping' => Configuration::get('JPRODUCTCOMPARATOR_SHOW_SHIPPING'),
            'show_seller_rating' => Configuration::get('MARKETPLACE_SELLER_RATING'),
            'product' => $product,            
            'offersOther' => $offersOther,
            'countCopyProduct' => count($offersOther),
            'productFirstCopy' => $offersOther,
            'seller_shipping_cost' => $seller_shipping_cost,            
            
        )); 

        return $this->display(__FILE__, 'count-copy-product.tpl');
    }

    public function hookDisplayLeftColumnProduct($params)
    {
        $id_guest = (!$id_customer = (int)$this->context->cookie->id_customer) ? (int)$this->context->cookie->id_guest : false;
        $customerComment = SellerProductComment::getByCustomer((int)(Tools::getValue('id_product')), (int)$this->context->cookie->id_customer, true, (int)$id_guest);

        $average = SellerProductComment::getAverageGrade((int)Tools::getValue('id_product'));
        $product = $this->context->controller->getProduct();
        $image = Product::getCover((int)Tools::getValue('id_product'));
        $cover_image = $this->context->link->getImageLink($product->link_rewrite, $image['id_image'], 'medium_default');

        $this->context->smarty->assign(array(
                'id_seller_product_comment_form' => (int)Tools::getValue('id_product'),
                'product' => $product,
                'secure_key' => $this->secure_key,
                'logged' => $this->context->customer->isLogged(true),
                /*'allow_guests' => (int)Configuration::get('JSELLERPRODUCTSCOMMENTS_ALLOW_GUESTS'),*/
                'allow_only_order' => (int)Configuration::get('JSELLERPRODUCTSCOMMENTS_ALLOW_ONLY_ORDER'),
                'productcomment_cover' => (int)Tools::getValue('id_product').'-'.(int)$image['id_image'], // retro compat
                'productcomment_cover_image' => $cover_image,
                'mediumSize' => Image::getSize(ImageType::getFormatedName('medium')),
                'criterions' => SellerProductCommentCriterion::getByProduct((int)Tools::getValue('id_product'), $this->context->language->id),
                'action_url' => '',
                'averageTotal' => round($average['grade']),
                'ratings' => SellerProductComment::getRatings((int)Tools::getValue('id_product')),
                'nbComments' => (int)(SellerProductComment::getCommentNumber((int)Tools::getValue('id_product')))
            ));

        return ($this->display(__FILE__, 'productcomments-extra.tpl'));

    }

    public function hookTop($params)
    {

        // SET IN CONFIG TO DISPLAY THESE LINES
        if ($this->context->controller instanceof ProductController)
        {
            //require_once(dirname(__FILE__).'/classes/SellerProductComment.php');
            //require_once(dirname(__FILE__).'/classes/SellerProductCommentCriterion.php');
            
            $id_guest = (!$id_customer = (int)$this->context->cookie->id_customer) ? (int)$this->context->cookie->id_guest : false;
            $customerComment = SellerProductComment::getByCustomer((int)(Tools::getValue('id_product')), (int)$this->context->cookie->id_customer, true, (int)$id_guest);


            $average = SellerProductComment::getAverageGrade((int)Tools::getValue('id_product'));
            
            $product = $this->context->controller->getProduct();

            $id_seller = SellerProduct::isSellerProduct($product->id);

            if($id_seller)
            {
                $id_carrier = SellerTransport::idSellerByCarrier($id_seller);

                $carrier = new Carrier($id_carrier); //28
            }
            else
                $carrier = new Carrier((int)Configuration::get('PS_CARRIER_DEFAULT'));

            $carrier_zones = $carrier->getZones();
            
            if (isset($carrier_zones) && !empty($carrier_zones)) {
                $first_carrier_zone = $carrier_zones[0]['id_zone'];
                $delivery_price = $carrier->getDeliveryPriceByWeight($product->weight, $first_carrier_zone);
            }
            else
            {
                $delivery_price = 0;
            }

            $image = Product::getCover((int)Tools::getValue('id_product'));
            $cover_image = $this->context->link->getImageLink($product->link_rewrite, $image['id_image'], 'small_default');

            $this->context->smarty->assign(array(
                    'id_seller_product_comment_form' => (int)Tools::getValue('id_product'),
                    'product' => $product,
                    'secure_key' => $this->secure_key,
                    'logged' => $this->context->customer->isLogged(true),
                    'allow_only_order' => (int)Configuration::get('JSELLERPRODUCTSCOMMENTS_ALLOW_ONLY_ORDER'),
                    'product_name' => $product->name,
                    'product_price' => $product->getPrice(true, NULL, 6),
                    'productcomment_cover' => (int)Tools::getValue('id_product').'-'.(int)$image['id_image'], // retro compat
                    'productcomment_cover_image' => $cover_image,
                    'mediumSize' => Image::getSize(ImageType::getFormatedName('medium')),
                    'criterions' => SellerProductCommentCriterion::getByProduct((int)Tools::getValue('id_product'), $this->context->language->id),
                    'action_url' => '',
                    'averageTotal' => round($average['grade']),
                    'ratings' => SellerProductComment::getRatings((int)Tools::getValue('id_product')),
                    'nbComments' => (int)(SellerProductComment::getCommentNumber((int)Tools::getValue('id_product'))),
                    'delivery_price' => $delivery_price + $product->additional_shipping_cost,
                ));

                return ($this->display(__FILE__, 'productcomments_top.tpl'));

        }
    }
    public function hookActionMarketplaceAfterUpdateProduct(){
        $this->_clearCache("*");
    }

    public function hookActionAuthentication(){
        $this->_clearCache("*");
    }
    public function hookActionMarketplaceAfterAddProduct(){
        $this->_clearCache("*");
    }

    public function hookActionProductUpdate($params)
    {
        $this->_clearCache('*');
    }
    public function hookActionProductAdd($params)
    {
        $this->_clearCache('*');
    }

    public function _clearCache($template, $cache_id = NULL, $compile_id = NULL)
    {
        parent::_clearCache('homefeatured.tpl');
        parent::_clearCache('blocknewproducts_home.tpl');
        parent::_clearCache('blocknewproducts.tpl');

        parent::_clearCache('tab.tpl', 'homefeatured-tab');
    }
  
    public function uninstall() 
    {  
        //SEARCH
        Configuration::deleteByName('MARKETPLACE_SEARCHBAR_TEMPLATE');
        Configuration::deleteByName('MARKETPLACE_SEARCHBAR_TEMPLATE_ITEM');
        Configuration::deleteByName('MARKETPLACE_SEARCHBAR_STYLESHEET');

        //GENERAL SETTINGS
        Configuration::deleteByName('MARKETPLACE_MODERATE_SELLER');
        Configuration::deleteByName('MARKETPLACE_MODERATE_PRODUCT');
        Configuration::deleteByName('MARKETPLACE_MODERATE_MESSAGE');
        Configuration::deleteByName('DELAY_DELIVERY');

        $logged_groups = $this->getGroupsToSeller();
        foreach ($logged_groups as $group) 
            Configuration::deleteByName('MARKETPLACE_CUSTOMER_GROUP_'.$group['id_group']);
        
        Configuration::deleteByName('MARKETPLACE_COMMISIONS_ORDER');
        Configuration::deleteByName('MARKETPLACE_COMMISIONS_STATE');
        Configuration::deleteByName('MARKETPLACE_ORDER_STATE');
        Configuration::deleteByName('MARKETPLACE_VARIABLE_COMMISSION');
        Configuration::deleteByName('MARKETPLACE_FIXED_COMMISSION');
        Configuration::deleteByName('MARKETPLACE_SHIPPING_COMMISSION');
        Configuration::deleteByName('MARKETPLACE_TAX_COMMISSION');
        
        $states = OrderState::getOrderStates($this->context->language->id);
        foreach ($states as $state) 
            Configuration::deleteByName('MARKETPLACE_CANCEL_COMMISSION_'.$state['id_order_state']);
        
        Configuration::deleteByName('MARKETPLACE_SHOW_CONTACT');
        Configuration::deleteByName('MARKETPLACE_SHOW_DASHBOARD');
        Configuration::deleteByName('MARKETPLACE_SHOW_MANAGE_ORDERS');
        Configuration::deleteByName('MARKETPLACE_SHOW_MANAGE_CARRIER');
        Configuration::deleteByName('MARKETPLACE_SHOW_PROFILE');
        Configuration::deleteByName('MARKETPLACE_SHOW_ORDERS');
        Configuration::deleteByName('MARKETPLACE_SHOW_EDIT_ACCOUNT');
        Configuration::deleteByName('MARKETPLACE_SHOW_EDIT_PRODUCT');
        Configuration::deleteByName('MARKETPLACE_SHOW_DELETE_PRODUCT');
        Configuration::deleteByName('MARKETPLACE_SHOW_ACTIVE_PRODUCT');
        Configuration::deleteByName('MARKETPLACE_SELLER_FAVORITE');
        Configuration::deleteByName('MARKETPLACE_SHOW_SELLER_RATING');
        Configuration::deleteByName('MARKETPLACE_NEW_PRODUCTS');
        Configuration::deleteByName('MARKETPLACE_SHOW_SELLER_PLIST');
        Configuration::deleteByName('MARKETPLACE_SELLER_IMPORT_PROD');
        
        //SELLER ACCOUNT
        Configuration::deleteByName('MARKETPLACE_SHOW_SHOP_NAME');
        Configuration::deleteByName('MARKETPLACE_SHOW_LANGUAGE');
        Configuration::deleteByName('MARKETPLACE_SHOW_CIF');
        Configuration::deleteByName('MARKETPLACE_SHOW_PHONE');
        Configuration::deleteByName('MARKETPLACE_SHOW_FAX');
        Configuration::deleteByName('MARKETPLACE_SHOW_ADDRESS');
        Configuration::deleteByName('MARKETPLACE_SHOW_COUNTRY');
        Configuration::deleteByName('MARKETPLACE_SHOW_STATE');
        Configuration::deleteByName('MARKETPLACE_SHOW_CITY');
        Configuration::deleteByName('MARKETPLACE_SHOW_POSTAL_CODE');
        Configuration::deleteByName('MARKETPLACE_SHOW_DESCRIPTION');
        Configuration::deleteByName('MARKETPLACE_SHOW_LOGO');
        
        //SELLER PRODUCT
        Configuration::deleteByName('MARKETPLACE_SHOW_REFERENCE');
        Configuration::deleteByName('MARKETPLACE_SHOW_EAN13');
        Configuration::deleteByName('MARKETPLACE_SHOW_UPC');
        Configuration::deleteByName('MARKETPLACE_SHOW_WIDTH');
        Configuration::deleteByName('MARKETPLACE_SHOW_HEIGHT');
        Configuration::deleteByName('MARKETPLACE_SHOW_DEPTH');
        Configuration::deleteByName('MARKETPLACE_SHOW_WEIGHT');
        Configuration::deleteByName('MARKETPLACE_SHOW_SHIP_PRODUCT');
        Configuration::deleteByName('MARKETPLACE_SHOW_CONDITION');
        Configuration::deleteByName('MARKETPLACE_SHOW_QUANTITY');
        Configuration::deleteByName('MARKETPLACE_SHOW_MINIMAL_QTY');
        Configuration::deleteByName('MARKETPLACE_SHOW_AVAILABLE_NOW');
        Configuration::deleteByName('MARKETPLACE_SHOW_AVAILABLE_LAT');
        Configuration::deleteByName('MARKETPLACE_SHOW_AVAILABLE_DATE');
        Configuration::deleteByName('MARKETPLACE_SHOW_PRICE');
        Configuration::deleteByName('MARKETPLACE_SHOW_OFFER_PRICE');
        Configuration::deleteByName('MARKETPLACE_SHOW_TAX');
        Configuration::deleteByName('MARKETPLACE_SHOW_DESC_SHORT');
        Configuration::deleteByName('MARKETPLACE_SHOW_DESC');
        Configuration::deleteByName('MARKETPLACE_SHOW_META_KEYWORDS');
        Configuration::deleteByName('MARKETPLACE_SHOW_META_TITLE');
        Configuration::deleteByName('MARKETPLACE_SHOW_LINK_REWRITE'); 
        Configuration::deleteByName('MARKETPLACE_SHOW_META_DESC');
        Configuration::deleteByName('MARKETPLACE_SHOW_IMAGES');
        Configuration::deleteByName('MARKETPLACE_MAX_IMAGES');
        Configuration::deleteByName('MARKETPLACE_SHOW_SUPPLIERS'); 
        Configuration::deleteByName('MARKETPLACE_NEW_SUPPLIERS'); 
        Configuration::deleteByName('MARKETPLACE_SHOW_MANUFACTURERS');
        Configuration::deleteByName('MARKETPLACE_NEW_MANUFACTURERS');
        Configuration::deleteByName('MARKETPLACE_SHOW_CATEGORIES'); 
        Configuration::deleteByName('MARKETPLACE_SHOW_FEATURES');
        Configuration::deleteByName('MARKETPLACE_SHOW_ATTRIBUTES');
        Configuration::deleteByName('MARKETPLACE_SHOW_VIRTUAL'); 
        
        //SELLER PAYMENT
        Configuration::deleteByName('MARKETPLACE_PAYPAL');
        Configuration::deleteByName('MARKETPLACE_BANKWIRE');
        
        //EMAIL
        Configuration::deleteByName('MARKETPLACE_SEND_ADMIN');
        Configuration::deleteByName('MARKETPLACE_SEND_ADMIN_REGISTER');
        Configuration::deleteByName('MARKETPLACE_SEND_ADMIN_PRODUCT');
        Configuration::deleteByName('MARKETPLACE_SEND_SELLER_WELCOME');
        Configuration::deleteByName('MARKETPLACE_SEND_SELLER_ACTIVE');
        Configuration::deleteByName('MARKETPLACE_SEND_PRODUCT_ACTIVE');
        Configuration::deleteByName('MARKETPLACE_SEND_PRODUCT_SOLD');
        
        //PRODUCT COMPARATOR
        Configuration::deleteByName('JPRODUCTCOMPARATOR_SHOW_SHIPPING');

        //THEME FRONT OFFICE
        Configuration::deleteByName('MARKETPLACE_THEME');
        Configuration::deleteByName('MARKETPLACE_TABS');
        Configuration::deleteByName('MARKETPLACE_MENU_OPTIONS');
        Configuration::deleteByName('MARKETPLACE_MENU_TOP');
        
        Configuration::deleteByName('MARKETPLACE_TOKEN');
        
        $this->deleteQuickAccess();
        
        Configuration::deleteByName('MARKETPLACE_QUICK_ACCESS');

        // SELLER PRODUCT COMMENTS
        Configuration::deleteByName('JSELLERPRODUCTSCOMMENTS_MODERATE');        
        
        Configuration::deleteByName('MARKETPLACE_SELLER_RATING');
        Configuration::deleteByName('MARKETPLACE_SEND_MESSAGE_ACTIVE');
        Configuration::deleteByName('MARKETPLACE_MODERATE_COMMENTS');
        Configuration::deleteByName('MARKETPLACE_ALLOW_GUEST_COMMENT');
        Configuration::deleteByName('MARKETPLACE_SEND_COMMENT_SELLER');
        Configuration::deleteByName('MARKETPLACE_SEND_COMMENT_ADMIN');
        Configuration::deleteByName('MARKETPLACE_BALANCE_DMIN_SELLER');
        Configuration::deleteByName('MARKETPLACE_BALANCE_DMAX_SELLER');
        Configuration::deleteByName('MARKETPLACE_SELLER_HOLIDAYS');
        Configuration::deleteByName('MARKETPLACE_ACTIVE_SELLER_BALANCE');

        $this->deleteTab('AdminSellers');
        $this->deleteTab('AdminSellerProducts');
        $this->deleteTab('AdminSellerCommisions');
        $this->deleteTab('AdminSellerCommisionsHistory');
        $this->deleteTab('AdminSellerCommisionsHistoryStates');
        $this->deleteTab('AdminSellerRulesBalance');        
        $this->deleteTab('AdminSellerIncidences');
        $this->deleteTab('AdminSellerEmails');
        $this->deleteTab('Adminmarketplace');

        $this->unregisterHook('displayProductListReviews');

        //DELETE ALL ENTRIES FROM ORDER STATE
        $sql = "DELETE FROM " ._DB_PREFIX_. "order_state WHERE id_order_state > 14";
        Db::getInstance()->execute($sql);
        $sql = "DELETE FROM " ._DB_PREFIX_. "order_state_lang WHERE id_order_state > 14";
        Db::getInstance()->execute($sql);

        Db::getInstance()->execute('ALTER TABLE `' ._DB_PREFIX_. 'specific_price` DROP `reduction_mode`');
        Db::getInstance()->execute('ALTER TABLE `' ._DB_PREFIX_. 'orders`  DROP `visited_seller`');
        Db::getInstance()->execute('ALTER TABLE `' ._DB_PREFIX_. 'orders`  DROP `id_seller`');
        Db::getInstance()->execute('ALTER TABLE `' ._DB_PREFIX_. 'orders`  DROP `visited`');
        Db::getInstance()->execute('ALTER TABLE `' ._DB_PREFIX_. 'orders`  DROP `slip_amount`');
        Db::getInstance()->execute('ALTER TABLE `' ._DB_PREFIX_. 'orders`  DROP `slip_confirmed`');
        Db::getInstance()->execute('ALTER TABLE `' ._DB_PREFIX_. 'orders`  DROP `slip_motif`');

        Db::getInstance()->execute('ALTER TABLE `' ._DB_PREFIX_. 'product`  DROP `comments`');

        if (!$this->deleteTables() || !$this->deleteTablesSellerHolidays() || !parent::uninstall())
            return false;

        return true;
    }
    
    public function createTables() 
    {
        if (!file_exists(dirname(__FILE__) . '/' . self::INSTALL_SQL_FILE))
            return false;
        else if (!$sql = Tools::file_get_contents(dirname(__FILE__) . '/' . self::INSTALL_SQL_FILE))
            return false;
        
        $sql = str_replace(array('PREFIX_', 'ENGINE_TYPE'), array(_DB_PREFIX_, _MYSQL_ENGINE_), $sql); 
        $sql = preg_split("/;\s*[\r\n]+/", $sql);
        
        foreach ($sql AS $query)
        {
            if ($query)
                if (!Db::getInstance()->execute(trim($query)))
                    return false;
        }


        $order_state_color = array(
            '#4169E1',
            '#EC2E15',
            '#EC2E15',
            '#DDEEFF',
            '#4169E1',
            '#FFF168',
            '#FFF168',
            '#FFF168',
            '#FF7F50',
            '#FF8C00',
            '#41664D',
            '#FF8C00',
            '#ADFFB3',
        );

        $order_state_fr = array(
            'En attente d\'autorisation',
            'Partiellement remboursé',
            'Totalement remboursé',
            'Autorisation acceptée',
            'En attente de paiement vendeur',
            'Annulation en cours',
            'Remboursement en cours',
            'Remboursement partiel en attente',
            'Remboursement partiel en cours',
            'Demande de financement en cours',
            'Paiement à valider',
            'En attente d\'autorisation vendeurs',
            'Commande consultée',
        );

        $order_state_en = array(
            'Pending authorization',            
            'Partially refunded',
            'Totally remboursé',
            'Authorization accepted',
            'Pending payment seller',
            'Cancel in progress',
            'Refund in progress',
            'Pending Partial refund',
            'Partial refund in progress',
            'Request for financing in progress',
            'Payment to be validated',
            'Pending authorization from sellers',
            'Order consulted',
        );

        $order_state = new OrderState();

        foreach($order_state_color as $key => $color)
        {   
            $order_state->color = $color;
            $order_state->module_name = 'marketplace';         
            foreach (Language::getLanguages(false) as $lang)
            {                

                if ($lang['iso_code'] == 'fr')
                    $order_state->name[$lang['id_lang']] = $order_state_fr[$key];
                else
                    $order_state->name[$lang['id_lang']] = $order_state_en[$key];                
            }
            $order_state->add();
        }

        
        $SellerCom_fr = array('Qualité');
        $SellerCom_en = array('Quality');

        $SellerCom = new SellerCommentCriterion();

        foreach (Language::getLanguages(false) as $lang)
        { 
            foreach($SellerCom_fr as $key => $com)
            {                          

                if ($lang['iso_code'] == 'fr')
                    $SellerCom->name[$lang['id_lang']] = $com;
                else
                    $SellerCom->name[$lang['id_lang']] = $SellerCom_en[$key];
            }

        }

        $SellerCom->add();

        $SellerPcom = new SellerProductCommentCriterion();

        foreach (Language::getLanguages(false) as $lang)
        { 
            foreach($SellerCom_fr as $key => $com)
            {
                if ($lang['iso_code'] == 'fr')
                    $SellerPcom->name[$lang['id_lang']] = $com;
                else
                    $SellerPcom->name[$lang['id_lang']] = $SellerCom_en[$key];                
            }

        }
        $SellerPcom->id_seller_product_comment_criterion_type = 1;

        $SellerPcom->add();
        return true;
    }
    
    public function addData() 
    {
        //states
        $state = new SellerCommisionHistoryState();
        
        $state->active = 1;
        $state->reference = 'pending';
        foreach (Language::getLanguages() as $lang) {
            if ($lang['iso_code'] == 'es' || $lang['iso_code'] == 'mx' || $lang['iso_code'] == 'co' || $lang['iso_code'] == 'ar') 
                $state->name[$lang['id_lang']] = 'Pendiente';
            else if ($lang['iso_code'] == 'fr')
                $state->name[$lang['id_lang']] = 'En attente';
            else
                $state->name[$lang['id_lang']] = 'Pending';
        }

        $state->add();
        
        $state->active = 1;
        $state->reference = 'paid';
        foreach (Language::getLanguages() as $lang) {
            if ($lang['iso_code'] == 'es' || $lang['iso_code'] == 'mx' || $lang['iso_code'] == 'co' || $lang['iso_code'] == 'ar') 
                $state->name[$lang['id_lang']] = 'Pagado';
            else if ($lang['iso_code'] == 'fr')
                $state->name[$lang['id_lang']] = 'Payé';
            else
                $state->name[$lang['id_lang']] = 'Paid';
        }
        $state->add();
        
        $state->active = 1;
        $state->reference = 'cancel';
        foreach (Language::getLanguages() as $lang) {
            if ($lang['iso_code'] == 'es' || $lang['iso_code'] == 'mx' || $lang['iso_code'] == 'co' || $lang['iso_code'] == 'ar') 
                $state->name[$lang['id_lang']] = 'Cancelado';
            else if ($lang['iso_code'] == 'fr')
                $state->name[$lang['id_lang']] = 'Annulé';
            else
                $state->name[$lang['id_lang']] = 'Cancel';
        }

        $state->add();
        
        $this->addSellerCategories();
        
        //emails
        $this->createEmails();
        
        return true;
    }
    
    public function createEmails() 
    {
        $url_shop = _PS_BASE_URL_ . __PS_BASE_URI__;
        
        //welcome-seller
        $seller_email = new SellerEmail();
        $seller_email->reference = 'welcome-seller';
        
        foreach (Language::getLanguages(false) as $lang) {
            if ($lang['iso_code'] == 'es' || $lang['iso_code'] == 'mx' || $lang['iso_code'] == 'co' || $lang['iso_code'] == 'ar') {
                $seller_email->subject[$lang['id_lang']] = 'Bienvenido a {shop_name}';
                $seller_email->description[$lang['id_lang']] = 'Este email es enviado al vendedor cuando su cuenta de vendedor ha sido creada correctamente.';
                $seller_email->content[$lang['id_lang']] = '<p>Bienvenido <strong>{seller_name}</strong>!</p>';
                $seller_email->content[$lang['id_lang']] .= '<p>Gracias por crear una cuenta de vendedor en {shop_name}!</p>';
                $seller_email->content[$lang['id_lang']] .= '<p>Su cuenta de vendedor ha sido creada correctamente en <a href="'.$url_shop.'">{shop_name}</a></p>';
            }
            else if($lang['iso_code'] == 'fr') {
                $seller_email->subject[$lang['id_lang']] = 'Bienvenue à {shop_name}';
                $seller_email->description[$lang['id_lang']] = 'Ce courriel est envoyé au vendeur lorsque votre compte marchand a été créé avec succès.';
                $seller_email->content[$lang['id_lang']] = '<p>Bienvenue <strong>{seller_name}</strong>!</p>';
                $seller_email->content[$lang['id_lang']] .= '<p>Merci pour votre inscription sur le site de  {shop_name}, votre compte vendeur a été créé avec succès. Il est désormais en attente d\'approbation le temps que nous puissions étudier votre demande dans les plus brefs délais. Ainsi un email de confirmation vous sera envoyé dès acceptation de votre demande d\'ouverture de compte vendeur par notre équipe. Cordialement.</p>';
                $seller_email->content[$lang['id_lang']] .= '<p><br><br><span style="color: #2445a2;"><strong>{shop_name}</strong></span></p>';
            }
            else if($lang['iso_code'] == 'it') {
                $seller_email->subject[$lang['id_lang']] = 'Benvenuto a {shop_name}';
                $seller_email->description[$lang['id_lang']] = 'Questa email viene inviata al venditore quando il tuo conto commerciante è stato creato con successo.';
                $seller_email->content[$lang['id_lang']] = '<p>Benvenuto <strong>{seller_name}</strong>!</p>';
                $seller_email->content[$lang['id_lang']] .= '<p>Grazie per creare un account venditore in {shop_name}!</p>';
                $seller_email->content[$lang['id_lang']] .= '<p>Il tuo account venditore è stato creato con successo in <a href="'.$url_shop.'">{shop_name}</a></p>';
            }
            else {
                $seller_email->subject[$lang['id_lang']] = 'Welcome to {shop_name}';
                $seller_email->description[$lang['id_lang']] = 'This email is sent to the seller when it has been correctly created your seller account.';
                $seller_email->content[$lang['id_lang']] = '<p>Welcome <strong>{seller_name}</strong>!</p>';
                $seller_email->content[$lang['id_lang']] .= '<p>Thank you by create a seller account in {shop_name}!</p>';
                $seller_email->content[$lang['id_lang']] .= '<p>Your seller account has been created successfully in <a href="'.$url_shop.'">{shop_name}</a></p>';
            }
        }
        
        $seller_email->add();
        
        //new-seller
        $seller_email = new SellerEmail();
        $seller_email->reference = 'new-seller';
        
        foreach (Language::getLanguages(false) as $lang) {
            if ($lang['iso_code'] == 'es' || $lang['iso_code'] == 'mx' || $lang['iso_code'] == 'co' || $lang['iso_code'] == 'ar') {
                $seller_email->subject[$lang['id_lang']] = 'Nuevo vendedor registrado';
                $seller_email->description[$lang['id_lang']] = 'Este correo se envía al administrador cuando un cliente se registra como vendedor.';
                $seller_email->content[$lang['id_lang']] = '<p>Nuevo cliente registrado como vendedor.</p>';
                $seller_email->content[$lang['id_lang']] .= '<p>Detalles del vendedor:</p>';
                $seller_email->content[$lang['id_lang']] .= '<p>NOMBRE: {seller_name}</p>';
                $seller_email->content[$lang['id_lang']] .= '<p>TIENDA: {seller_shop}</p>';
                $seller_email->content[$lang['id_lang']] .= '<p>Puede acceder a su tienda para activar la nueva cuenta de vendedor en <a href="'.$url_shop.'">{shop_name}</a></p>';
            }
            else if($lang['iso_code'] == 'fr') {
                $seller_email->subject[$lang['id_lang']] = 'Nouveau vendeur enregistré';
                $seller_email->description[$lang['id_lang']] = 'Ce courrier est envoyé à administrateur lorsqu un client enregistre en tant que vendeur.';
                $seller_email->content[$lang['id_lang']] = '<p>Nouveau client enregistré en tant que vendeur.</p>';
                $seller_email->content[$lang['id_lang']] .= '<p>Informations sur le vendeur:</p>';
                $seller_email->content[$lang['id_lang']] .= '<p>Nom et prènoms : {seller_name}</p>';
                $seller_email->content[$lang['id_lang']] .= '<p>Nom de l\'entreprise : {seller_shop}</p>';
                $seller_email->content[$lang['id_lang']] .= '<p>N° SIREN : {siren}</p>';
                $seller_email->content[$lang['id_lang']] .= '<p>Adresse mail : {email}</p>';
                $seller_email->content[$lang['id_lang']] .= '<p>Tel : {phone}</p>';
                $seller_email->content[$lang['id_lang']] .= '<p>Adresse : {address}</p>';                
                $seller_email->content[$lang['id_lang']] .= '<p><p><p>Ci-attaché l\'extrait RCS ainsi que la pièce d\'identité du gérant.</p>';                
                $seller_email->content[$lang['id_lang']] .= '<p><p><p>Accédez à votre boutique pour activer le nouveau compte vendeur <strong><span style="color: #2445a2;"><a href="'.$url_shop.'"><span style="color: #2445a2;">{shop_name}</span></a></span></strong></p>';
                
            }
            else if($lang['iso_code'] == 'it') {
                $seller_email->subject[$lang['id_lang']] = 'Nuovo venditore registrato';
                $seller_email->description[$lang['id_lang']] = 'Questa mail viene inviata all amministratore quando un cliente si registra come venditore.';
                $seller_email->content[$lang['id_lang']] = '<p>Nuovo cliente registrato come venditore.</p>';
                $seller_email->content[$lang['id_lang']] .= '<p>Dettagli del venditore:</p>';
                $seller_email->content[$lang['id_lang']] .= '<p>Nome: {seller_name}</p>';
                $seller_email->content[$lang['id_lang']] .= '<p>Shop: {seller_shop}</p>';
                $seller_email->content[$lang['id_lang']] .= '<p>Accedi al tuo negozio per attivare il nuovo account venditore <a href="'.$url_shop.'">{shop_name}</a></p>';
            }
            else {
                $seller_email->subject[$lang['id_lang']] = 'New seller registered';
                $seller_email->description[$lang['id_lang']] = 'This email is sent to the administrator when a customer registers as seller.';
                $seller_email->content[$lang['id_lang']] = '<p>New customer registered as a seller.</p>';                
                $seller_email->content[$lang['id_lang']] .= '<p>Name : {seller_name}</p>';
                $seller_email->content[$lang['id_lang']] .= '<p>Company Name : {seller_shop}</p>';
                $seller_email->content[$lang['id_lang']] .= '<p>SIREN number: {siren}</p>';
                $seller_email->content[$lang['id_lang']] .= '<p>Email address: {email}</p>';
                $seller_email->content[$lang['id_lang']] .= '<p>Phone : {phone}</p>';
                $seller_email->content[$lang['id_lang']] .= '<p>Address : {address}</p>';                
                $seller_email->content[$lang['id_lang']] .= '<p><p><p>Herewith the RCS extract and the identity document of the manager.</p>';                
                $seller_email->content[$lang['id_lang']] .= '<p><p><p>You can access your shop to activate the new seller account in <strong><span style="color: #2445a2;"><a href="'.$url_shop.'"><span style="color: #2445a2;">{shop_name}</span></a></span></strong></p>';
            }
        }
        
        $seller_email->add();
        
        //edit-seller
        $seller_email = new SellerEmail();
        $seller_email->reference = 'edit-seller';
        
        foreach (Language::getLanguages(false) as $lang) {
            if ($lang['iso_code'] == 'es' || $lang['iso_code'] == 'mx' || $lang['iso_code'] == 'co' || $lang['iso_code'] == 'ar') {
                $seller_email->subject[$lang['id_lang']] = '"{seller_name}" ha editado su cuenta de vendedor';
                $seller_email->description[$lang['id_lang']] = 'Este correo se envía al administrador Cuando un vendedor ha cambiado la información de su cuenta de vendedor.';
                $seller_email->content[$lang['id_lang']] = '<p><strong>{seller_name}</strong> ha modificado su perfil de vendedor.</p>';
                $seller_email->content[$lang['id_lang']] .= '<p>Puede acceder a su tienda para validar los cambios en <a href="'.$url_shop.'">{shop_name}</a></p>';
            }
            else if($lang['iso_code'] == 'fr') {
                $seller_email->subject[$lang['id_lang']] = '"{seller_name}" a modifié son profil';
                $seller_email->description[$lang['id_lang']] = 'Ce courriel est envoyé à administrateur quand un vendeur a changé les informations de votre compte vendeur.';
                $seller_email->content[$lang['id_lang']] = '<p><strong>{seller_name}</strong> a modifié son profil.</p>';
                $seller_email->content[$lang['id_lang']] .= '<p>Accédez à votre boutique pour valider les changements dans <a href="'.$url_shop.'">{shop_name}</a></p>';
            }
            else if($lang['iso_code'] == 'it') {
                $seller_email->subject[$lang['id_lang']] = '"{seller_name}" ha cambiato il profilo del venditore';
                $seller_email->description[$lang['id_lang']] = 'Questa email viene inviata all amministratore quando un venditore ha cambiato il informazioni del tuo account venditore.';
                $seller_email->content[$lang['id_lang']] = '<p><strong>{seller_name}</strong> ha cambiato il profilo del venditore.</p>';
                $seller_email->content[$lang['id_lang']] .= '<p>Accedi al tuo negozio per validare le modifiche <a href="'.$url_shop.'">{shop_name}</a></p>';
            }
            else {
                $seller_email->subject[$lang['id_lang']] = '"{seller_name}" has changed your seller account';
                $seller_email->description[$lang['id_lang']] = 'This email is sent to the administrator when a seller has changed the information of your seller account.';
                $seller_email->content[$lang['id_lang']] = '<p><strong>{seller_name}</strong> has changed your seller account.</p>';
                $seller_email->content[$lang['id_lang']] .= '<p>You can access your shop to validate changes in <a href="'.$url_shop.'">{shop_name}</a></p>';
            }
        }
        
        $seller_email->add();
        
        //seller-activated
        $seller_email = new SellerEmail();
        $seller_email->reference = 'seller-activated';
        
        foreach (Language::getLanguages(false) as $lang) {
            if ($lang['iso_code'] == 'es' || $lang['iso_code'] == 'ca' || $lang['iso_code'] == 'gl' || $lang['iso_code'] == 'mx' || $lang['iso_code'] == 'co' || $lang['iso_code'] == 'ar') {
                $seller_email->subject[$lang['id_lang']] = 'Su cuenta de vendedor ha sido activada';
                $seller_email->description[$lang['id_lang']] = 'Este correo se envía al vendedor cuando su cuenta de vendedor ha sido aceptada.';
                $seller_email->content[$lang['id_lang']] = '<p>Su cuenta de vendedor ha sido activada en <strong>{shop_name}</strong>.</p>';
                $seller_email->content[$lang['id_lang']] .= '<p>Ahora puede comenzar a añadir sus productos en <a href="'.$url_shop.'">{shop_name}</a></p>';
            }
            else if($lang['iso_code'] == 'fr') {
                $seller_email->subject[$lang['id_lang']] = 'Votre compte vendeur a été activé';
                $seller_email->description[$lang['id_lang']] = 'Ce courriel est envoyé au vendeur lorsque votre compte vendeur a été activé.';
                $seller_email->content[$lang['id_lang']] = '<p>Votre compte vendeur a été activé sur le site de <strong>{shop_name}</strong></p>';
                $seller_email->content[$lang['id_lang']] .= '<p>Maintenant, vous pouvez commencer à vendre vos produits sur le site de <span style="color: #2445a2;"><a href="'. $url_shop .'"><span style="color: #2445a2;">{shop_name}</span></a></span></p>';
            }
            else if($lang['iso_code'] == 'it') {
                $seller_email->subject[$lang['id_lang']] = 'Il tuo account venditore è stato attivato';
                $seller_email->description[$lang['id_lang']] = 'Questa email viene inviata al venditore quando è stato attivato il tuo account venditore.';
                $seller_email->content[$lang['id_lang']] = '<p>Il tuo account venditore è stato attivato in <strong>{shop_name}</strong></p>';
                $seller_email->content[$lang['id_lang']] .= '<p>È ora possibile iniziare ad aggiungere prodotti in <a href="'.$url_shop.'">{shop_name}</a></p>';
            }
            else {
                $seller_email->subject[$lang['id_lang']] = 'Your seller account has been activated';
                $seller_email->description[$lang['id_lang']] = 'This email is sent to the seller when your seller account has been activated.';
                $seller_email->content[$lang['id_lang']] = '<p>Your seller account has been activated in <strong>{shop_name}</strong></p>';
                $seller_email->content[$lang['id_lang']] .= '<p>You can now begin to add products in <a href="'.$url_shop.'">{shop_name}</a></p>';
            }
        }
        
        $seller_email->add();
        
        //seller-desactivated
        $seller_email = new SellerEmail();
        $seller_email->reference = 'seller-desactivated';
        
        foreach (Language::getLanguages(false) as $lang) {
            if ($lang['iso_code'] == 'es' || $lang['iso_code'] == 'ca' || $lang['iso_code'] == 'gl' || $lang['iso_code'] == 'mx' || $lang['iso_code'] == 'co' || $lang['iso_code'] == 'ar') {
                $seller_email->subject[$lang['id_lang']] = 'Su cuenta de vendedor ha sido rechazada';
                $seller_email->description[$lang['id_lang']] = 'Este correo se envía al vendedor cuando su cuenta de vendedor ha sido aceptada.';
                $seller_email->content[$lang['id_lang']] = '<p>Su cuenta de vendedor ha sido desactivada en <strong>{shop_name}</strong>.</p>';
            }
            else if($lang['iso_code'] == 'fr') {
                $seller_email->subject[$lang['id_lang']] = 'Votre compte vendeur a été désactivé';
                $seller_email->description[$lang['id_lang']] = 'Ce courriel est envoyé au vendeur lorsque vous compte vendeur a été refusé.';
                $seller_email->content[$lang['id_lang']] = '<p>Votre compte vendeur a été désactivé dans <strong>{shop_name}</strong>.</p>';
            }
            else if($lang['iso_code'] == 'it') {
                $seller_email->subject[$lang['id_lang']] = 'Il tuo account venditore non è attivato';
                $seller_email->description[$lang['id_lang']] = 'Questa email viene inviata al venditore quando il tuo account venditore è stata rifiutata.';
                $seller_email->content[$lang['id_lang']] = '<p>Il tuo account venditore non è attivato in <strong>{shop_name}</strong></p>';
            }
            else {
                $seller_email->subject[$lang['id_lang']] = 'Your seller account has been declined';
                $seller_email->description[$lang['id_lang']] = 'This email is sent to the seller when your seller account has been desactivated.';
                $seller_email->content[$lang['id_lang']] = '<p>Your seller account has been activated in <strong>{shop_name}</strong></p>';
            }
        }
        
        $seller_email->add();
        
        //new-product
        $seller_email = new SellerEmail();
        $seller_email->reference = 'new-product';
        
        foreach (Language::getLanguages(false) as $lang) {
            if ($lang['iso_code'] == 'es' || $lang['iso_code'] == 'ca' || $lang['iso_code'] == 'gl' || $lang['iso_code'] == 'mx' || $lang['iso_code'] == 'co' || $lang['iso_code'] == 'ar') {
                $seller_email->subject[$lang['id_lang']] = '"{seller_name}" ha ha añadido un nuevo producto';
                $seller_email->description[$lang['id_lang']] = 'Este correo se envía al administrador cuando un vendedor añade un nuevo producto.';
                $seller_email->content[$lang['id_lang']] = '<p><strong>{seller_name}</strong> ha añadido un nuevo producto <strong>{product_name}</strong>.</p>';
                $seller_email->content[$lang['id_lang']] .= '<p>Puede acceder a su tienda para validarlo en <a href="'.$url_shop.'">{shop_name}</a></p>';
            }
            else if($lang['iso_code'] == 'fr') {
                $seller_email->subject[$lang['id_lang']] = '"{seller_name}" a ajouté un nouveau produit';
                $seller_email->description[$lang['id_lang']] = 'Ce courriel est envoyé à administrateur quand un vendeur ajoute un nouveau produit.';
                $seller_email->content[$lang['id_lang']] = '<p><strong>{seller_name}</strong> a ajouté un nouveau produit <strong>{product_name}</strong>.</p>';
                $seller_email->content[$lang['id_lang']] .= '<p>Vous pouvez accéder à votre boutique pour gérer <a href="'.$url_shop.'">{shop_name}</a></p>';
            }
            else if($lang['iso_code'] == 'it') {
                $seller_email->subject[$lang['id_lang']] = '"{seller_name}" ha aggiunto un nuovo prodotto';
                $seller_email->description[$lang['id_lang']] = 'Questa email viene inviata all amministratore Quando un venditore aggiunge un nuovo prodotto.';
                $seller_email->content[$lang['id_lang']] = '<p><strong>{seller_name}</strong> ha aggiunto un nuovo prodotto <strong>{product_name}</strong>.</p>';
                $seller_email->content[$lang['id_lang']] .= '<p>Puoi accedere al tuo negozio per gestirlo in <a href="'.$url_shop.'">{shop_name}</a></p>';
            }
            else {
                $seller_email->subject[$lang['id_lang']] = '"{seller_name}" has added a new product';
                $seller_email->description[$lang['id_lang']] = 'This email is sent to the administrator when a seller adds a new product.';
                $seller_email->content[$lang['id_lang']] = '<p><strong>{seller_name}</strong> has modified your product <strong>{product_name}</strong>.</p>';
                $seller_email->content[$lang['id_lang']] .= '<p>You can access your shop to manage it <a href="'.$url_shop.'">{shop_name}</a></p>';
            }
        }
        
        $seller_email->add();
        
        //product-activated
        $seller_email = new SellerEmail();
        $seller_email->reference = 'product-activated';
        
        foreach (Language::getLanguages(false) as $lang) {
            if ($lang['iso_code'] == 'es' || $lang['iso_code'] == 'ca' || $lang['iso_code'] == 'gl' || $lang['iso_code'] == 'mx' || $lang['iso_code'] == 'co' || $lang['iso_code'] == 'ar') {
                $seller_email->subject[$lang['id_lang']] = 'Su producto "{product_name}" ha sido aceptado';
                $seller_email->description[$lang['id_lang']] = 'Este correo se envía al vendedor cuando su producto ha sido aceptado.';
                $seller_email->content[$lang['id_lang']] = '<p>Su producto <strong>{product_name}</strong> ha sido aceptado.</p>';
                $seller_email->content[$lang['id_lang']] .= '<p>Ahora está disponible en <a href="'.$url_shop.'">{shop_name}</a></p>';
            }
            else if($lang['iso_code'] == 'fr') {
                $seller_email->subject[$lang['id_lang']] = 'Son produit "{product_name}" a été accepté';
                $seller_email->description[$lang['id_lang']] = 'Ce courriel est envoyé au vendeur lorsque votre produit a été accepté.';
                $seller_email->content[$lang['id_lang']] = '<p>Votre produit <strong>{product_name}</strong> a été accepté dans <strong>{shop_name}</strong></p>';
                $seller_email->content[$lang['id_lang']] .= '<p>Maintenant disponible dans <a href="'.$url_shop.'">{shop_name}</a></p>';
            }
            else if($lang['iso_code'] == 'it') {
                $seller_email->subject[$lang['id_lang']] = 'Il suo prodotto "{product_name}" e stato accettato';
                $seller_email->description[$lang['id_lang']] = 'Questa email viene inviata al venditore quando il prodotto è stato accettato.';
                $seller_email->content[$lang['id_lang']] = '<p>Il prodotto <strong>{product_name}</strong> è stato accettato in <strong>{shop_name}</strong></p>';
                $seller_email->content[$lang['id_lang']] .= '<p>Ora disponibile in <a href="'.$url_shop.'">{shop_name}</a></p>';
            }
            else {
                $seller_email->subject[$lang['id_lang']] = 'Your product "{product_name}" has been accepted';
                $seller_email->description[$lang['id_lang']] = 'This email is sent to the seller when your product has been accepted.';
                $seller_email->content[$lang['id_lang']] = '<p>Your product <strong>{product_name}</strong> has been accepted in <strong>{shop_name}</strong></p>';
                $seller_email->content[$lang['id_lang']] .= '<p>Now available in <a href="'.$url_shop.'">{shop_name}</a></p>';
            }
        }
        
        $seller_email->add();
        
        //product-desactivated
        $seller_email = new SellerEmail();
        $seller_email->reference = 'product-desactivated';
        
        foreach (Language::getLanguages(false) as $lang) {
            if ($lang['iso_code'] == 'es' || $lang['iso_code'] == 'ca' || $lang['iso_code'] == 'gl' || $lang['iso_code'] == 'mx' || $lang['iso_code'] == 'co' || $lang['iso_code'] == 'ar') {
                $seller_email->subject[$lang['id_lang']] = 'Su producto "{product_name}" ha sido rechazado';
                $seller_email->description[$lang['id_lang']] = 'Este correo se envía al vendedor cuando su producto ha sido rechazado.';
                $seller_email->content[$lang['id_lang']] = '<p>Su producto <strong>{product_name}</strong> ha sido rechazado.</p>';
                $seller_email->content[$lang['id_lang']] .= '<p>Contacta ahora en <a href="'.$url_shop.'">{shop_name}</a></p>';
            }
            else if($lang['iso_code'] == 'fr') {
                $seller_email->subject[$lang['id_lang']] = 'Votre produit "{product_name}" a été refusé';
                $seller_email->description[$lang['id_lang']] = 'Ce courriel est envoyé au vendeur lorsque son produit a été refusé.';
                $seller_email->content[$lang['id_lang']] = '<p>Votre produit <strong>{product_name}</strong> n\'a pas été accepté dans <strong>{shop_name}</strong>.</p>';
                $seller_email->content[$lang['id_lang']] .= '<p>Maintenant disponible dans <a href="'.$url_shop.'">{shop_name}</a></p>';
            }
            else if($lang['iso_code'] == 'it') {
                $seller_email->subject[$lang['id_lang']] = 'Il suo prodotto "{product_name}" e stato accettato';
                $seller_email->description[$lang['id_lang']] = 'Questa email viene inviata al venditore quando il prodotto è stato rifiutato.';
                $seller_email->content[$lang['id_lang']] = '<p>Il prodotto <strong>{product_name}</strong> non è stato accettato in <strong>{shop_name}</strong></p>';
                $seller_email->content[$lang['id_lang']] .= '<p>Ora disponibile in <a href="'.$url_shop.'">{shop_name}</a></p>';
            }
            else {
                $seller_email->subject[$lang['id_lang']] = 'Your product "{product_name}"  has been declined';
                $seller_email->description[$lang['id_lang']] = 'This email is sent to the seller when your product has been declined.';
                $seller_email->content[$lang['id_lang']] = '<p>Your product <strong>{product_name}</strong>  has been declined in <strong>{shop_name}</strong></p>';
                $seller_email->content[$lang['id_lang']] .= '<p>Contact now in <a href="'.$url_shop.'">{shop_name}</a></p>';
            }
        }
        
        $seller_email->add();
        
        //edit-product
        $seller_email = new SellerEmail();
        $seller_email->reference = 'edit-product';
        
        foreach (Language::getLanguages(false) as $lang) {
            if ($lang['iso_code'] == 'es' || $lang['iso_code'] == 'ca' || $lang['iso_code'] == 'gl' || $lang['iso_code'] == 'mx' || $lang['iso_code'] == 'co' || $lang['iso_code'] == 'ar') {
                $seller_email->subject[$lang['id_lang']] = '"{seller_name}" ha editado un producto';
                $seller_email->description[$lang['id_lang']] = 'Este correo se envía al administrador cuando un vendedor ha editado un producto.';
                $seller_email->content[$lang['id_lang']] = '<p><strong>{seller_name}</strong> ha editado su producto <strong>{product_name}</strong>.</p>';
                $seller_email->content[$lang['id_lang']] .= '<p>Puede acceder a su tienda para gestionarlo en <a href="'.$url_shop.'">{shop_name}</a></p>';
            }
            else if($lang['iso_code'] == 'fr') {
                $seller_email->subject[$lang['id_lang']] = '"{seller_name}" a modifié un produit';
                $seller_email->description[$lang['id_lang']] = 'Cet email sera envoyé à l\'administrateur quand un vendeur a été modifié un produit.';
                $seller_email->content[$lang['id_lang']] = '<p><strong>{seller_name}</strong> a modifié son produit <strong>{product_name}</strong>.</p>';
                $seller_email->content[$lang['id_lang']] .= '<p>Vous pouvez accéder à votre boutique pour gérer <a href="'.$url_shop.'">{shop_name}</a></p>';
            }
            else if($lang['iso_code'] == 'it') {
                $seller_email->subject[$lang['id_lang']] = '"{seller_name}" ha modificato il suo prodotto';
                $seller_email->description[$lang['id_lang']] = 'Questa email viene inviata all amministratore quando un venditore ha modificato il suo prodotto.';
                $seller_email->content[$lang['id_lang']] = '<p><strong>{seller_name}</strong> ha modificato il prodotto "{product_name}".</p>';
                $seller_email->content[$lang['id_lang']] .= '<p>Puoi accedere al tuo negozio per gestirlo in <a href="'.$url_shop.'">{shop_name}</a></p>';
            }
            else {
                $seller_email->subject[$lang['id_lang']] = '"{seller_name}" has edited a product';
                $seller_email->description[$lang['id_lang']] = 'This email is sent to the administrator when a seller has edited a product.';
                $seller_email->content[$lang['id_lang']] = '<p><strong>{seller_name}</strong> has modified your product <strong>{product_name}</strong>.</p>';
                $seller_email->content[$lang['id_lang']] .= '<p>You can access your shop to manage it <a href="'.$url_shop.'">{shop_name}</a></p>';
            }
        }
        
        $seller_email->add();

        //new-order
        $seller_email = new SellerEmail();
        $seller_email->reference = 'new-order';
        
        foreach (Language::getLanguages(false) as $lang) {
            if ($lang['iso_code'] == 'es' || $lang['iso_code'] == 'ca' || $lang['iso_code'] == 'gl' || $lang['iso_code'] == 'mx' || $lang['iso_code'] == 'co' || $lang['iso_code'] == 'ar') {
                $seller_email->subject[$lang['id_lang']] = 'Nuevo pedido. "{product_name}" - {order_reference}';
                $seller_email->description[$lang['id_lang']] = 'Este correo se envía al vendedor cuando su producto ha sido comprado por un cliente.';
                $seller_email->content[$lang['id_lang']] = '<p>Hola <strong>{seller_name}</strong>!</p>';
                $seller_email->content[$lang['id_lang']] .= '<p>¡Enhorabuena! <strong>{product_name}</strong> fue comprado por un cliente en la tienda <strong>{shop_name}</strong>.</p>';
                $seller_email->content[$lang['id_lang']] .= '<p>Visita tu cuenta de vendedor ahora en <a href="'.$url_shop.'">{shop_name}</a></p>';
            }
            else if($lang['iso_code'] == 'fr') {
                $seller_email->subject[$lang['id_lang']] = 'Nouvelle commande. "{product_name}" - {order_reference}';
                $seller_email->description[$lang['id_lang']] = 'Ce courriel est envoyé au vendeur Lorsque son produit a été acheté par un client.';
                $seller_email->content[$lang['id_lang']] = '<p>Bonjour <strong>{seller_name}</strong>!</p>';
                $seller_email->content[$lang['id_lang']] .= '<p>Félicitations à vous! <br> Vous avez une nouvelle commande à expédier : <br> <strong> {product_name} <br> commande "{order_reference}" N°{order_id}</strong> Merci de vous rendre sur votre compte marchand afin de valider et d\'expédier cette commande dans les 48H <br>Pour cela cliquez ici : <a href="'.$url_shop.'/connexion?back=my-account">Mon compte</a><br><strong>{shop_name}</strong> vous remercie de vendre vos produits sur Megamarket.</p>';                
            }
            else if($lang['iso_code'] == 'it') {
                $seller_email->subject[$lang['id_lang']] = 'Nuevo ordine. "{product_name}" - {order_reference}';
                $seller_email->description[$lang['id_lang']] = 'Questa email viene inviata al venditore quando il prodotto è stato acquistato da un cliente.';
                $seller_email->content[$lang['id_lang']] = '<p>Ciao <strong>{seller_name}</strong>!</p>';
                $seller_email->content[$lang['id_lang']] .= '<p>Complimenti! <strong>{product_name}</strong> è stato acquistato da un cliente nel mercato <strong>{shop_name}</strong>.</p>';
                $seller_email->content[$lang['id_lang']] .= '<p>Visita il tuo account commerciante <a href="'.$url_shop.'">{shop_name}</a></p>';
            }
            else {
                $seller_email->subject[$lang['id_lang']] = 'New order. "{product_name}" - {order_reference}';
                $seller_email->description[$lang['id_lang']] = 'This email is sent to the seller when your product has been purchased by a customer.';
                $seller_email->content[$lang['id_lang']] = '<p>Hello <strong>{seller_name}</strong>!</p>';
                $seller_email->content[$lang['id_lang']] .= '<p>¡Congratulations! <strong>{product_name}</strong> was purchased by a customer in <strong>{shop_name}</strong>.</p>';
                $seller_email->content[$lang['id_lang']] .= '<p>Visit your seller account now <a href="'.$url_shop.'">{shop_name}</a></p>';
            }
        }
        
        $seller_email->add();
        
        //new-incidence
        $seller_email = new SellerEmail();
        $seller_email->reference = 'new-incidence';
        
        foreach (Language::getLanguages(false) as $lang) {
            if ($lang['iso_code'] == 'es' || $lang['iso_code'] == 'ca' || $lang['iso_code'] == 'gl' || $lang['iso_code'] == 'mx' || $lang['iso_code'] == 'co' || $lang['iso_code'] == 'ar') {
                $seller_email->subject[$lang['id_lang']] = 'Nueva incidencia recibida sobre el pedido "{order_reference}"';
                $seller_email->description[$lang['id_lang']] = 'Este correo se envía al vendedor cuando un cliente tiene problemas con algún pedido.';
                $seller_email->content[$lang['id_lang']] = '<p>Ha habido una nueva incidencia sobre el pedido <strong>{order_reference}</strong>:</p>';
                $seller_email->content[$lang['id_lang']] .= '<p>{description}</p>';
                $seller_email->content[$lang['id_lang']] .= '<p>Acceda a su cuenta de vendedor para dar una respuesta en <a href="'.$url_shop.'">{shop_name}</a></p>';
            }
            else if($lang['iso_code'] == 'fr') {
                $seller_email->subject[$lang['id_lang']] = 'Nouveau message reçu sur la commande "{order_reference}"';
                $seller_email->description[$lang['id_lang']] = 'Ce courrier est envoyé au vendeur quand un client a un problème avec unu commande.';
                $seller_email->content[$lang['id_lang']] = '<p>Bonjour,</p><p>Il y a eu un un nouveau message reçu sur la commande <strong>{order_reference}</strong>:</p>';
                $seller_email->content[$lang['id_lang']] .= '<p>{description}</p>';
                $seller_email->content[$lang['id_lang']] .= '<p>Accédez à votre compte marchand pour donner une réponse dans <a href="'.$url_shop.'">{shop_name}</a></p>';
            }
            else if($lang['iso_code'] == 'it') {
                $seller_email->subject[$lang['id_lang']] = 'Nuovo incidenza ricevuto su richiesta "{order_reference}"';
                $seller_email->description[$lang['id_lang']] = 'Questa mail viene inviata al venditore quando un cliente ha un problema con un ordine.';
                $seller_email->content[$lang['id_lang']] = '<p>Cè stato un nuovo incidente su richiesta <strong>{order_reference}</strong>:</p>';
                $seller_email->content[$lang['id_lang']] .= '<p>{description}</p>';
                $seller_email->content[$lang['id_lang']] .= '<p>Accedi al tuo account venditore per rispondere <a href="'.$url_shop.'">{shop_name}</a></p>';
            }
            else {
                $seller_email->subject[$lang['id_lang']] = 'New incidence received about order "{order_reference}"';
                $seller_email->description[$lang['id_lang']] = 'This mail is sent to the seller when a customer has a problem with an order.';
                $seller_email->content[$lang['id_lang']] = '<p>There has been a new incident on order <strong>{order_reference}</strong>:</p>';
                $seller_email->content[$lang['id_lang']] .= '<p>{description}</p>';
                $seller_email->content[$lang['id_lang']] .= '<p>Access your seller account to respond in en <a href="'.$url_shop.'">{shop_name}</a></p>';
            }
        }
        
        $seller_email->add();
        
        //new-message
        $seller_email = new SellerEmail();
        $seller_email->reference = 'new-message';
        
        foreach (Language::getLanguages(false) as $lang) {
            if ($lang['iso_code'] == 'es' || $lang['iso_code'] == 'ca' || $lang['iso_code'] == 'gl' || $lang['iso_code'] == 'mx' || $lang['iso_code'] == 'co' || $lang['iso_code'] == 'ar') {
                $seller_email->subject[$lang['id_lang']] = 'Nuevo mensaje recibido sobre "{product_name}"';
                $seller_email->description[$lang['id_lang']] = 'Este correo se envía al vendedor cuando un cliente quiere saber más información sobre un producto.';
                $seller_email->content[$lang['id_lang']] = '<p>Ha recibido un nuevo mensaje con referencia <strong>{incidence_reference}</strong>:</p>';
                $seller_email->content[$lang['id_lang']] .= '<p>{description}</p>';
                $seller_email->content[$lang['id_lang']] .= '<p>Acceda a su cuenta de vendedor para dar una respuesta en <a href="'.$url_shop.'">{shop_name}</a></p>';
            }
            else if($lang['iso_code'] == 'fr') {
                $seller_email->subject[$lang['id_lang']] = 'Nouveau message reçu sur "{product_name}"';
                $seller_email->description[$lang['id_lang']] = 'Ce courrier est envoyé au vendeur quand un client veut en savoir plus sur un produit.';
                $seller_email->content[$lang['id_lang']] = '<p>Vous avez reçu un nouveau message en référence <strong>{incidence_reference}</strong>:</p>';
                $seller_email->content[$lang['id_lang']] .= '<p>{description}</p>';
                $seller_email->content[$lang['id_lang']] .= '<p>Accédez à votre compte marchand pour donner une réponse dans <a href="'.$url_shop.'">{shop_name}</a></p>';
            }
            else if($lang['iso_code'] == 'it') {
                $seller_email->subject[$lang['id_lang']] = 'Nuovo messaggio ricevuto circa "{product_name}"';
                $seller_email->description[$lang['id_lang']] = 'Questa mail viene inviata al venditore quando un cliente vuole sapere di più su un prodotto.';
                $seller_email->content[$lang['id_lang']] = '<p>Ha ricevuto un nuovo messaggio con riferimento <strong>{incidence_reference}</strong>:</p>';
                $seller_email->content[$lang['id_lang']] .= '<p>{description}</p>';
                $seller_email->content[$lang['id_lang']] .= '<p>Accedi al tuo account venditore per rispondere <a href="'.$url_shop.'">{shop_name}</a></p>';
            }
            else {
                $seller_email->subject[$lang['id_lang']] = 'New message received about "{product_name}"';
                $seller_email->description[$lang['id_lang']] = 'This mail is sent to the seller when a customer wants to know more about a product.';
                $seller_email->content[$lang['id_lang']] = '<p>You have received a new message reference <strong>{incidence_reference}</strong>:</p>';
                $seller_email->content[$lang['id_lang']] .= '<p>{description}</p>';
                $seller_email->content[$lang['id_lang']] .= '<p>Access your seller account to respond in en <a href="'.$url_shop.'">{shop_name}</a></p>';
            }
        }
        
        $seller_email->add();
        
        //new-response-seller
        $seller_email = new SellerEmail();
        $seller_email->reference = 'new-response-seller';
        
        foreach (Language::getLanguages(false) as $lang) {
            if ($lang['iso_code'] == 'es' || $lang['iso_code'] == 'ca' || $lang['iso_code'] == 'gl' || $lang['iso_code'] == 'mx' || $lang['iso_code'] == 'co' || $lang['iso_code'] == 'ar') {
                $seller_email->subject[$lang['id_lang']] = 'Nueva respuesta a su mensaje con referencia "{incidence_reference}"';
                $seller_email->description[$lang['id_lang']] = 'Este correo se envía al cliente cuando el vendedor responde a un mensaje previo.';
                $seller_email->content[$lang['id_lang']] = '<p>Hay una nueva respuesta sobre su mensaje con referencia <strong>{incidence_reference}</strong>:</p>';
                $seller_email->content[$lang['id_lang']] .= '<p>{description}</p>';
                $seller_email->content[$lang['id_lang']] .= '<p>Acceda a su cuenta para revisar el historial de mensajes <a href="'.$url_shop.'">{shop_name}</a></p>';
            }
            else if($lang['iso_code'] == 'fr') {
                $seller_email->subject[$lang['id_lang']] = 'Nouvelle réponse à votre message en référence "{incidence_reference}"';
                $seller_email->description[$lang['id_lang']] = 'Ce courrier est envoyé au client lorsque le vendeur répond à un message précédent.';
                $seller_email->content[$lang['id_lang']] = '<p>Il y a une nouvelle réponse à votre message en référence <strong>{incidence_reference}</strong>:</p>';
                $seller_email->content[$lang['id_lang']] .= '<p>{description}</p>';
                $seller_email->content[$lang['id_lang']] .= '<p>Accédez à votre compte pour donner une réponse dans <a href="'.$url_shop.'">{shop_name}</a></p>';
            }
            else if($lang['iso_code'] == 'it') {
                $seller_email->subject[$lang['id_lang']] = 'Nuova risposta al vostro messaggio con riferimento "{incidence_reference}"';
                $seller_email->description[$lang['id_lang']] = 'Questa mail viene inviata al cliente quando il venditore risponde ad un messaggio precedente.';
                $seller_email->content[$lang['id_lang']] = '<p>Cè una nuova risposta al messaggio con riferimento <strong>{incidence_reference}</strong>:</p>';
                $seller_email->content[$lang['id_lang']] .= '<p>{description}</p>';
                $seller_email->content[$lang['id_lang']] .= '<p>Accedi al tuo account venditore per rispondere <a href="'.$url_shop.'">{shop_name}</a></p>';
            }
            else {
                $seller_email->subject[$lang['id_lang']] = 'New response to your message with reference "{incidence_reference}"';
                $seller_email->description[$lang['id_lang']] = 'This mail is sent to the customer when the seller responds to a previous message.';
                $seller_email->content[$lang['id_lang']] = '<p>There is a new answer to your message reference <strong>{incidence_reference}</strong>:</p>';
                $seller_email->content[$lang['id_lang']] .= '<p>{description}</p>';
                $seller_email->content[$lang['id_lang']] .= '<p>Access your seller account to respond in en <a href="'.$url_shop.'">{shop_name}</a></p>';
            }
        }
        
        $seller_email->add();
        
        //new-response-customer
        $seller_email = new SellerEmail();
        $seller_email->reference = 'new-response-customer';
        
        foreach (Language::getLanguages(false) as $lang) {
            if ($lang['iso_code'] == 'es' || $lang['iso_code'] == 'ca' || $lang['iso_code'] == 'gl' || $lang['iso_code'] == 'mx' || $lang['iso_code'] == 'co' || $lang['iso_code'] == 'ar') {
                $seller_email->subject[$lang['id_lang']] = 'Nueva respuesta del cliente sobre el mensaje con referencia "{incidence_reference}"';
                $seller_email->description[$lang['id_lang']] = 'Este correo se envía al vendedor cuando el cliente responde a un mensaje previo.';
                $seller_email->content[$lang['id_lang']] = '<p>Hay una nueva respuesta del cliente en el mensaje con referencia <strong>{incidence_reference}</strong>:</p>';
                $seller_email->content[$lang['id_lang']] .= '<p>{description}</p>';
                $seller_email->content[$lang['id_lang']] .= '<p>Acceda a su cuenta para dar una respuesta en <a href="'.$url_shop.'">{shop_name}</a></p>';
            }
            else if($lang['iso_code'] == 'fr') {
                $seller_email->subject[$lang['id_lang']] = 'Réponse Nouveau client sur le message "{incidence_reference}"';
                $seller_email->description[$lang['id_lang']] = 'Ce courrier est envoyé au vendeur lorsque le client répond à un message précédent.';
                $seller_email->content[$lang['id_lang']] = '<p>Il y a une nouvelle réponse du client dans le message en référence <strong>{incidence_reference}</strong>:</p>';
                $seller_email->content[$lang['id_lang']] .= '<p>{description}</p>';
                $seller_email->content[$lang['id_lang']] .= '<p>Accédez à votre compte marchand pour donner une réponse dans <a href="'.$url_shop.'">{shop_name}</a></p>';
            }
            else if($lang['iso_code'] == 'it') {
                $seller_email->subject[$lang['id_lang']] = 'Nuova risposta dei clienti sul messaggio con riferimento "{incidence_reference}"';
                $seller_email->description[$lang['id_lang']] = 'Questa mail viene inviata al venditore quando il cliente risponde ad un messaggio precedente.';
                $seller_email->content[$lang['id_lang']] = '<p>Cè una nuova risposta de cliente nel messaggio con riferimento <strong>{incidence_reference}</strong>:</p>';
                $seller_email->content[$lang['id_lang']] .= '<p>{description}</p>';
                $seller_email->content[$lang['id_lang']] .= '<p>Accedi al tuo account venditore per rispondere <a href="'.$url_shop.'">{shop_name}</a></p>';
            }
            else {
                $seller_email->subject[$lang['id_lang']] = 'New customer response on the message with reference "{incidence_reference}"';
                $seller_email->description[$lang['id_lang']] = 'This mail is sent to the seller when the customer responds to a previous message.';
                $seller_email->content[$lang['id_lang']] = '<p>There is a new customer response in the message with reference <strong>{incidence_reference}</strong>:</p>';
                $seller_email->content[$lang['id_lang']] .= '<p>{description}</p>';
                $seller_email->content[$lang['id_lang']] .= '<p>Access your seller account to respond in en <a href="'.$url_shop.'">{shop_name}</a></p>';
            }
        }
        
        $seller_email->add();
        
        //new-comment-admin
        $seller_email = new SellerEmail();
        $seller_email->reference = 'new-comment-admin';
        
        foreach (Language::getLanguages(false) as $lang) {
            if ($lang['iso_code'] == 'es' || $lang['iso_code'] == 'ca' || $lang['iso_code'] == 'gl' || $lang['iso_code'] == 'mx' || $lang['iso_code'] == 'co' || $lang['iso_code'] == 'ar') {
                $seller_email->subject[$lang['id_lang']] = 'Nuevo comentario recibido sobre "{seller_name}"';
                $seller_email->description[$lang['id_lang']] = 'Este correo se envía al administrador cuando un cliente valora el grado de satisfacción de un vendedor.';
                $seller_email->content[$lang['id_lang']] = '<p>Nuevo comentario recibido sobre el vendedor <strong>{seller_name}</strong>:</p>';
                $seller_email->content[$lang['id_lang']] .= '<p>Grado de satisfacción: {grade}/5</p>';
                $seller_email->content[$lang['id_lang']] .= '<p>{comment}</p>';
                $seller_email->content[$lang['id_lang']] .= '<p>Acceda a su tienda para validarlo <a href="'.$url_shop.'">{shop_name}</a></p>';
            }
            else if($lang['iso_code'] == 'fr') {
                $seller_email->subject[$lang['id_lang']] = 'Nouveau commentaire reçu sur "{seller_name}"';
                $seller_email->description[$lang['id_lang']] = 'Ce courrier est envoyé à administrateur lorsquun client évalue le degré de satisfaction dun vendeur.';
                $seller_email->content[$lang['id_lang']] = '<p>Nouveau commentaire reçu sur vendeur <strong>{seller_name} </strong>de la part du client <strong>{sender} </strong>:</p>';
                $seller_email->content[$lang['id_lang']] .= '<p>Satisfaction: {grade}/5</p>';
                $seller_email->content[$lang['id_lang']] .= '<p>{comment}</p>';
                $seller_email->content[$lang['id_lang']] .= '<p>Accédez à votre boutique pour valider <a href="'.$url_shop.'">{shop_name}</a></p>';
            }
            else if($lang['iso_code'] == 'it') {
                $seller_email->subject[$lang['id_lang']] = 'Nuovo commento ricevuto il "{seller_name}"';
                $seller_email->description[$lang['id_lang']] = 'Questa mail viene inviata all amministratore quando un cliente valuta il grado di soddisfazione di un venditore.';
                $seller_email->content[$lang['id_lang']] = '<p>Nuovo commento ricevuto il venditore <strong>{seller_name}</strong>:</p>';
                $seller_email->content[$lang['id_lang']] .= '<p>Soddisfazione: {grade}/5</p>';
                $seller_email->content[$lang['id_lang']] .= '<p>{comment}</p>';
                $seller_email->content[$lang['id_lang']] .= '<p>Accedi al tuo negozio per convalidare <a href="'.$url_shop.'">{shop_name}</a></p>';
            }
            else {
                $seller_email->subject[$lang['id_lang']] = 'New comment received about "{seller_name}"';
                $seller_email->description[$lang['id_lang']] = 'This mail is sent to the administrator when a customer assesses the degree of satisfaction of a seller.';
                $seller_email->content[$lang['id_lang']] = '<p>New comment received about seller <strong>{seller_name}</strong>:</p>';
                $seller_email->content[$lang['id_lang']] .= '<p>Satisfaction: {grade}/5</p>';
                $seller_email->content[$lang['id_lang']] .= '<p>{comment}</p>';
                $seller_email->content[$lang['id_lang']] .= '<pAccess your shop to validate <a href="'.$url_shop.'">{shop_name}</a></p>';
            }
        }
        
        $seller_email->add();
        
        //new-comment-seller
        $seller_email = new SellerEmail();
        $seller_email->reference = 'new-comment-seller';
        
        foreach (Language::getLanguages(false) as $lang) {
            if ($lang['iso_code'] == 'es' || $lang['iso_code'] == 'ca' || $lang['iso_code'] == 'gl' || $lang['iso_code'] == 'mx' || $lang['iso_code'] == 'co' || $lang['iso_code'] == 'ar') {
                $seller_email->subject[$lang['id_lang']] = 'Nuevo comentario recibido';
                $seller_email->description[$lang['id_lang']] = 'Este correo se envía al vendedor cuando un cliente valora el grado de satisfacción de un vendedor o cuando el admininistrador valida un comentario.';
                $seller_email->content[$lang['id_lang']] = '<p>Nuevo comentario recibido:</p>';
                $seller_email->content[$lang['id_lang']] .= '<p>Grado de satisfacción: {grade}/5</p>';
                $seller_email->content[$lang['id_lang']] .= '<p>{comment}</p>';
                $seller_email->content[$lang['id_lang']] .= '<p>Puede verlo en <a href="'.$url_shop.'">{shop_name}</a></p>';
            }
            else if($lang['iso_code'] == 'fr') {
                $seller_email->subject[$lang['id_lang']] = 'Nouveau commentaire reçu';
                $seller_email->description[$lang['id_lang']] = 'Ce courrier est envoyé au vendeur quand un client évalue le degré de satisfaction dun fournisseur ou lorsque le admininistrador valide un commentaire.';
                $seller_email->content[$lang['id_lang']] = '<p>Nouveau commentaire reçu de la part du client <strong>{sender} </strong>:</p>';
                $seller_email->content[$lang['id_lang']] .= '<p>Satisfaction: {grade}/5</p>';
                $seller_email->content[$lang['id_lang']] .= '<p>{comment}</p>';
                $seller_email->content[$lang['id_lang']] .= '<p>Vous pouvez le voir dans <a href="'.$url_shop.'">{shop_name}</a></p>';
            }
            else if($lang['iso_code'] == 'it') {
                $seller_email->subject[$lang['id_lang']] = 'Nuovo commento ricevuto';
                $seller_email->description[$lang['id_lang']] = 'Questa mail viene inviata al venditore quando un cliente valuta il grado di soddisfazione di un venditore o quando il admininistrador convalida un commento.';
                $seller_email->content[$lang['id_lang']] = '<p>Nuovo commento ricevuto:</p>';
                $seller_email->content[$lang['id_lang']] .= '<p>Soddisfazione: {grade}/5</p>';
                $seller_email->content[$lang['id_lang']] .= '<p>{comment}</p>';
                $seller_email->content[$lang['id_lang']] .= '<p>Lo si può vedere in <a href="'.$url_shop.'">{shop_name}</a></p>';
            }
            else {
                $seller_email->subject[$lang['id_lang']] = 'New comment received';
                $seller_email->description[$lang['id_lang']] = 'This mail is sent to the seller when a customer assesses the degree of satisfaction of a vendor or when the admininistrador validates a comment.';
                $seller_email->content[$lang['id_lang']] = '<p>New comment received:</p>';
                $seller_email->content[$lang['id_lang']] .= '<p>Satisfaction: {grade}/5</p>';
                $seller_email->content[$lang['id_lang']] .= '<p>{comment}</p>';
                $seller_email->content[$lang['id_lang']] .= '<p>You can see it in <a href="'.$url_shop.'">{shop_name}</a></p>';
            }
        }
        
        $seller_email->add();

        //new-message-validation
        $seller_email = new SellerEmail();
        $seller_email->reference = 'new-message-validation';
        
        foreach (Language::getLanguages(false) as $lang) {
                $seller_email->subject[$lang['id_lang']] = 'A valider - Nouveau message reçu sur "{product_name}"';
                $seller_email->description[$lang['id_lang']] = 'Ce courrier est envoyé à l\'admin pour approuver une question du client qui veut en savoir plus sur un produit.';
                $seller_email->content[$lang['id_lang']] = '<p>Il y a un nouveau message en référence <strong>{incidence_reference}</strong>:</p>';
                $seller_email->content[$lang['id_lang']] .= '<p>{description}</p>';
                $seller_email->content[$lang['id_lang']] .= '<p>Accédez dans le backoffice pour valider le message dans <a href="'.$url_shop.'">{shop_name}</a></p>';                           
        }
        
        $seller_email->add();

        //new-incidence-validation
        $seller_email = new SellerEmail();
        $seller_email->reference = 'new-incidence-validation';
        
        foreach (Language::getLanguages(false) as $lang) {
                $seller_email->subject[$lang['id_lang']] = 'A valider - Nouvelle message sur la commande "{order_reference}"';
                $seller_email->description[$lang['id_lang']] = 'Ce courrier est envoyé à l\'administrateur avant que le ne vendeur recoive le message d\'un client qui a un problème avec une commande.';
                $seller_email->content[$lang['id_lang']] = '<p>Un vendeur a reçu un nouveau message sur la commande N°: <strong>{order_reference}</strong>:</p>';
                $seller_email->content[$lang['id_lang']] .= '<p>{description}</p>';
                $seller_email->content[$lang['id_lang']] .= '<p>Accèder à l\'administration pour approuver ce message <a href="'.$url_shop.'">{shop_name}</a></p>';
        }
        
        $seller_email->add();

        //new-response-customer-validation
        $seller_email = new SellerEmail();
        $seller_email->reference = 'new-response-customer-validation';
        
        foreach (Language::getLanguages(false) as $lang) {
                $seller_email->subject[$lang['id_lang']] = 'A valider - Réponse Nouveau client sur le message "{incidence_reference}"';
                $seller_email->description[$lang['id_lang']] = 'Ce courrier est envoyé à l\'administrateur lorsque le client répond à un message précédent.';
                $seller_email->content[$lang['id_lang']] = '<p>Il y a une nouvelle réponse du client dans le message en référence <strong>{incidence_reference}</strong>:</p>';
                $seller_email->content[$lang['id_lang']] .= '<p>{description}</p>';                
                $seller_email->content[$lang['id_lang']] .= '<p>Accèder à l\'administration pour approuver ce message <a href="'.$url_shop.'">{shop_name}</a></p>';            
        }
        
        $seller_email->add();

        //new-response-seller-validation
        $seller_email = new SellerEmail();
        $seller_email->reference = 'new-response-seller-validation';
        
        foreach (Language::getLanguages(false) as $lang) {
                $seller_email->subject[$lang['id_lang']] = 'A valider - Nouvelle réponse à votre message en référence "{incidence_reference}"';
                $seller_email->description[$lang['id_lang']] = 'Ce courrier est envoyé à l\'administrateur lorsque le vendeur répond à un message précédent.';
                $seller_email->content[$lang['id_lang']] = '<p>Il y a une nouvelle réponse du vendeur dans le message en référence <strong>{incidence_reference}</strong>:</p>';
                $seller_email->content[$lang['id_lang']] .= '<p>{description}</p>';                
                $seller_email->content[$lang['id_lang']] .= '<p>Accèder à l\'administration pour approuver ce message <a href="'.$url_shop.'">{shop_name}</a></p>';
        }
        
        $seller_email->add();

        //new-message-client
        $seller_email = new SellerEmail();
        $seller_email->reference = 'new-message-client';
        
        foreach (Language::getLanguages(false) as $lang) {
                $seller_email->subject[$lang['id_lang']] = 'Message envoyé au vendeur {seller_name}';
                $seller_email->description[$lang['id_lang']] = 'Ce courrier est envoyé au client quand un message a été envoyé à un vendeur.';
                $seller_email->content[$lang['id_lang']] = '<p>Votre message ayant la référence {incidence_reference} a bien été envoyé au vendeur {seller_name} sur {shop_name}.</p>';
                $seller_email->content[$lang['id_lang']] .= '<p></p><p></p><p>{description}</p><p></p><p></p>';                
                $seller_email->content[$lang['id_lang']] .= '<p>Vous pouvez le voir dans <a href="'.$url_shop.'">{shop_name}</a></p';
        }
        
        $seller_email->add();

    }
    
    public function addSellerCategories() 
    {
        $data_seller_category = array();
        $categories = Category::getSimpleCategories(Context::getContext()->language->id);
        foreach ($categories as $category) {
            $data_seller_category[] = array(
                'id_category' => (int)$category['id_category'],
                'id_shop' => (int)Context::getContext()->shop->id,
            );
        }
        Db::getInstance()->insert('seller_category', $data_seller_category);
    }
    
    public function deleteTables() 
    {
        Db::getInstance()->Executes('DROP TABLE IF EXISTS `'._DB_PREFIX_.'seller`');
        Db::getInstance()->Executes('DROP TABLE IF EXISTS `'._DB_PREFIX_.'seller_product`');   
        Db::getInstance()->Executes('DROP TABLE IF EXISTS `'._DB_PREFIX_.'seller_incidence`');
        Db::getInstance()->Executes('DROP TABLE IF EXISTS `'._DB_PREFIX_.'seller_incidence_message`');
        Db::getInstance()->Executes('DROP TABLE IF EXISTS `'._DB_PREFIX_.'seller_payment`');
        Db::getInstance()->Executes('DROP TABLE IF EXISTS `'._DB_PREFIX_.'seller_commision`');
        Db::getInstance()->Executes('DROP TABLE IF EXISTS `'._DB_PREFIX_.'seller_commision_history`');
        Db::getInstance()->Executes('DROP TABLE IF EXISTS `'._DB_PREFIX_.'seller_commision_history_state`');
        Db::getInstance()->Executes('DROP TABLE IF EXISTS `'._DB_PREFIX_.'seller_commision_history_state_lang`');
        Db::getInstance()->Executes('DROP TABLE IF EXISTS `'._DB_PREFIX_.'seller_favorite`');
        Db::getInstance()->Executes('DROP TABLE IF EXISTS `'._DB_PREFIX_.'seller_category`');
        Db::getInstance()->Executes('DROP TABLE IF EXISTS `'._DB_PREFIX_.'seller_carrier`');
        Db::getInstance()->Executes('DROP TABLE IF EXISTS `'._DB_PREFIX_.'seller_email`');
        Db::getInstance()->Execute('DROP TABLE IF EXISTS `'._DB_PREFIX_.'seller_email_lang`');
        Db::getInstance()->Executes('DROP TABLE IF EXISTS `'._DB_PREFIX_.'seller_comment`');
        Db::getInstance()->Executes('DROP TABLE IF EXISTS `'._DB_PREFIX_.'seller_comment_criterion`');
        Db::getInstance()->Executes('DROP TABLE IF EXISTS `'._DB_PREFIX_.'seller_comment_criterion_lang`');
        Db::getInstance()->Executes('DROP TABLE IF EXISTS `'._DB_PREFIX_.'seller_comment_grade`');

        Db::getInstance()->Executes('
                DROP TABLE IF EXISTS
                `'._DB_PREFIX_.'seller_product_comment`,
                `'._DB_PREFIX_.'seller_product_comment_criterion`,
                `'._DB_PREFIX_.'seller_product_comment_criterion_product`,
                `'._DB_PREFIX_.'seller_product_comment_criterion_lang`,
                `'._DB_PREFIX_.'seller_product_comment_criterion_category`,
                `'._DB_PREFIX_.'seller_product_comment_grade`,
                `'._DB_PREFIX_.'seller_product_comment_usefulness`,
                `'._DB_PREFIX_.'seller_product_comment_report`,
                `'._DB_PREFIX_.'seller_comment_criterion_seller`');

        return true;
    }
    
    public function createTab($class_name, $tab_name, $tab_parent_name = false, $icon=null) 
    {
        $tab = new Tab();
        $tab->active = 1;
        $tab->class_name = $class_name;
        $tab->name = array();
        //$tab->name[1] = $tab_name['fr'];

        foreach (Language::getLanguages(true) as $lang) {
            switch($lang['iso_code']) {
                case 'es':
                    $tab->name[$lang['id_lang']] = $tab_name['es'];
                    break;
                case 'co':
                    $tab->name[$lang['id_lang']] = $tab_name['es'];
                    break;
                case 'ar':
                    $tab->name[$lang['id_lang']] = $tab_name['es'];
                    break;
                case 'mx':
                    $tab->name[$lang['id_lang']] = $tab_name['es'];
                    break;
                case 'fr':
                    $tab->name[$lang['id_lang']] = $tab_name['fr'];
                    break;
                case 'it':
                    $tab->name[$lang['id_lang']] = $tab_name['it'];
                    break;
                case 'br':
                    $tab->name[$lang['id_lang']] = $tab_name['br'];
                    break;
                default:
                    $tab->name[$lang['id_lang']] = $tab_name['en'];
                    break;
            }
        }   
        
        if($tab_parent_name) 
            $tab->id_parent = (int)Tab::getIdFromClassName($tab_parent_name);
        else 
            $tab->id_parent = 0;

        $tab->module = $this->name;
        $tab->icon = $icon;
        return $tab->add();
    }
    
    public function updateTab($class_name, $tab_name) 
    {
        $tab = Tab::getInstanceFromClassName($class_name);
        
        foreach (Language::getLanguages(true) as $lang) {
            switch($lang['iso_code']) {
                case 'es':
                    $tab->name[$lang['id_lang']] = $tab_name['es'];
                    break;
                case 'fr':
                    $tab->name[$lang['id_lang']] = $tab_name['fr'];
                    break;
                case 'it':
                    $tab->name[$lang['id_lang']] = $tab_name['it'];
                    break;
                case 'br':
                    $tab->name[$lang['id_lang']] = $tab_name['br'];
                    break;
                default:
                    $tab->name[$lang['id_lang']] = $tab_name['en'];
                    break;
            }
        }    

        return $tab->update();
    }
    
    public function deleteTab($class_name) 
    {
        $id_tab = (int)Tab::getIdFromClassName($class_name);
        if ($id_tab) {
            $tab = new Tab($id_tab);
            return $tab->delete();
        }
        else
            return false;
    }
    
    private function createImageFolder($imageFolderName) 
    {
        if(is_dir(_PS_IMG_DIR_.$imageFolderName))
            return true;
        
        if(!mkdir(_PS_IMG_DIR_.$imageFolderName, 0755))
            return false;        

        return true;
    }   
    
    public function removeThemeColumnByPage($page) {
        $meta = Meta::getMetaByPage($page, Context::getContext()->language->id);
        return Db::getInstance()->Execute('UPDATE `'._DB_PREFIX_.'theme_meta` SET `left_column` = 0 WHERE id_meta = '.(int)$meta['id_meta']);
    }
    
    public function createHook($name) 
    {
        $hook = '';
        
        if (version_compare(_PS_VERSION_, '1.6', '<'))
            $hook = Hook::get($name);
        else
            $hook = Hook::getIdByName($name);
        
        if (!$hook) {
            $hook = new Hook();
            $hook->name = $name;
            $hook->save();
        }

        return $this->registerHook($name);
    }
    
    public function addQuickAccess() 
    {
        $quick_access = new QuickAccess();
        $quick_access->link = $this->context->link->getAdminLink('AdminModules').'&configure=marketplace';

        $languages = Language::getLanguages(false);
        foreach ($languages as $lang) {
            if ($lang['iso_code'] == 'fr')
                $quick_access->name[$lang['id_lang']] = 'Configuration Marketplace';
            else
                $quick_access->name[$lang['id_lang']] = 'Marketplace Setting';
            
        }

        $quick_access->new_window = '0';
        
        if($quick_access->save())
            Configuration::updateValue('MARKETPLACE_QUICK_ACCESS', $quick_access->id);
    }
    
    public function deleteQuickAccess() 
    {
        $quick_access = new QuickAccess(Configuration::get('MARKETPLACE_QUICK_ACCESS'));
        $quick_access->delete();
    }
    
    public function addMetas() 
    {
        $this->controllers = array(
            'addproduct', 
            'addseller', 
            'contactseller', 
            'editproduct', 
            'editseller',
            'favoriteseller',
            'sellermessages',
            'sellerorders',
            'sellerpayment',
            'sellerproducts',
            'sellers',
            'searchproduct'
        );
    }
    
    public function postProcess() 
    { 
        $errors = array();
        
        if (Tools::isSubmit('submitGeneralSettings')) {    
            $fixed_commission = Tools::getValue('MARKETPLACE_FIXED_COMMISSION');
            $variable_commission = Tools::getValue('MARKETPLACE_VARIABLE_COMMISSION');
            
            if (!Validate::isFloat($fixed_commission)) {
                $errors[] = $this->l('Invalid fixed commission value.');
            }
            
            if ($variable_commission < 0 OR $variable_commission > 100 OR !Validate::isInt($variable_commission)) {
                $errors[] = $this->l('Invalid variable commission value.');
            }

            if (count($errors) == 0) {
                Configuration::updateValue('MARKETPLACE_MODERATE_SELLER', Tools::getValue('MARKETPLACE_MODERATE_SELLER'));
                Configuration::updateValue('MARKETPLACE_MODERATE_PRODUCT', Tools::getValue('MARKETPLACE_MODERATE_PRODUCT'));
                Configuration::updateValue('MARKETPLACE_MODERATE_MESSAGE', Tools::getValue('MARKETPLACE_MODERATE_MESSAGE'));

                $logged_groups = $this->getGroupsToSeller();

                $selected_group = false;
                foreach ($logged_groups as $group) {
                    if (Tools::getValue('MARKETPLACE_CUSTOMER_GROUP_'.$group['id_group']))
                        $selected_group = true;
                }

                if ($selected_group) {
                    foreach ($logged_groups as $group) {
                        if (Tools::getValue('MARKETPLACE_CUSTOMER_GROUP_'.$group['id_group']))
                            Configuration::updateValue('MARKETPLACE_CUSTOMER_GROUP_'.$group['id_group'], 1);
                        else
                            Configuration::updateValue('MARKETPLACE_CUSTOMER_GROUP_'.$group['id_group'], 0);
                    }
                }
                else {
                    $errors[] = $this->l('You must select at least one group.');
                }

                $states = OrderState::getOrderStates($this->context->language->id);
                $selected_state = false;
                foreach ($states as $state) {
                    if (Tools::getValue('MARKETPLACE_CANCEL_COMMISSION_'.$state['id_order_state']))
                        $selected_state = true;
                }
                
                $orderSellerStates = array(); 
                foreach ($states as $state) {
                    if (Tools::getValue('MARKETPLACE_STATES_ORDER_SELLER_'.$state['id_order_state']))
                        $orderSellerStates[] = $state['id_order_state'];
                }
                
                Configuration::updateValue('MARKETPLACE_STATES_ORDER_SELLER', implode(',',$orderSellerStates));
                
                    
                if ($selected_state) {
                    foreach ($states as $state) {
                        if (Tools::getValue('MARKETPLACE_CANCEL_COMMISSION_'.$state['id_order_state']))
                            Configuration::updateValue('MARKETPLACE_CANCEL_COMMISSION_'.$state['id_order_state'], 1);
                        else
                            Configuration::updateValue('MARKETPLACE_CANCEL_COMMISSION_'.$state['id_order_state'], 0);
                    }
                }
                else {
                    $errors[] = $this->l('You must select at least one order state for cancel commissions.');
                }

                if (Tools::getValue('MARKETPLACE_COMMISIONS_ORDER') == 1 && Configuration::get('MARKETPLACE_COMMISIONS_ORDER') == 0) {
                    Configuration::updateValue('MARKETPLACE_COMMISIONS_ORDER', Tools::getValue('MARKETPLACE_COMMISIONS_ORDER'));
                    Configuration::updateValue('MARKETPLACE_COMMISIONS_STATE', 0);
                }
                else if (Tools::getValue('MARKETPLACE_COMMISIONS_STATE') == 1 && Configuration::get('MARKETPLACE_COMMISIONS_STATE') == 0) {
                    Configuration::updateValue('MARKETPLACE_COMMISIONS_ORDER', 0);
                    Configuration::updateValue('MARKETPLACE_COMMISIONS_STATE', Tools::getValue('MARKETPLACE_COMMISIONS_STATE'));
                }

                Configuration::updateValue('MARKETPLACE_ORDER_STATE', Tools::getValue('MARKETPLACE_ORDER_STATE'));
                Configuration::updateValue('MARKETPLACE_FIXED_COMMISSION', $fixed_commission);
                Configuration::updateValue('MARKETPLACE_VARIABLE_COMMISSION', $variable_commission);
                Configuration::updateValue('MARKETPLACE_SHIPPING_COMMISSION', Tools::getValue('MARKETPLACE_SHIPPING_COMMISSION'));
                Configuration::updateValue('MARKETPLACE_TAX_COMMISSION', Tools::getValue('MARKETPLACE_TAX_COMMISSION'));
                Configuration::updateValue('MARKETPLACE_SELLER_IMPORT_PROD', Tools::getValue('MARKETPLACE_SELLER_IMPORT_PROD'));
                Configuration::updateValue('MARKETPLACE_SHOW_CONTACT', Tools::getValue('MARKETPLACE_SHOW_CONTACT'));
                Configuration::updateValue('MARKETPLACE_SHOW_DASHBOARD', Tools::getValue('MARKETPLACE_SHOW_DASHBOARD'));
                Configuration::updateValue('MARKETPLACE_SHOW_MANAGE_ORDERS', Tools::getValue('MARKETPLACE_SHOW_MANAGE_ORDERS'));
                Configuration::updateValue('MARKETPLACE_SHOW_MANAGE_CARRIER', Tools::getValue('MARKETPLACE_SHOW_MANAGE_CARRIER'));
                Configuration::updateValue('MARKETPLACE_SHOW_PROFILE', Tools::getValue('MARKETPLACE_SHOW_PROFILE'));
                Configuration::updateValue('MARKETPLACE_SHOW_ORDERS', Tools::getValue('MARKETPLACE_SHOW_ORDERS'));
                Configuration::updateValue('MARKETPLACE_SHOW_EDIT_ACCOUNT', Tools::getValue('MARKETPLACE_SHOW_EDIT_ACCOUNT'));
                Configuration::updateValue('MARKETPLACE_SHOW_EDIT_PRODUCT', Tools::getValue('MARKETPLACE_SHOW_EDIT_PRODUCT'));
                Configuration::updateValue('MARKETPLACE_SHOW_DELETE_PRODUCT', Tools::getValue('MARKETPLACE_SHOW_DELETE_PRODUCT'));
                Configuration::updateValue('MARKETPLACE_SHOW_ACTIVE_PRODUCT', Tools::getValue('MARKETPLACE_SHOW_ACTIVE_PRODUCT'));
                Configuration::updateValue('MARKETPLACE_SELLER_FAVORITE', Tools::getValue('MARKETPLACE_SELLER_FAVORITE'));
                Configuration::updateValue('MARKETPLACE_SELLER_RATING', Tools::getValue('MARKETPLACE_SELLER_RATING'));
                Configuration::updateValue('MARKETPLACE_NEW_PRODUCTS', Tools::getValue('MARKETPLACE_NEW_PRODUCTS'));
                Configuration::updateValue('MARKETPLACE_SHOW_SELLER_PLIST', Tools::getValue('MARKETPLACE_SHOW_SELLER_PLIST'));

                if (Configuration::get('MARKETPLACE_SHOW_MANAGE_ORDERS') == 1 || Configuration::get('MARKETPLACE_SHOW_MANAGE_CARRIER') == 1)
                    Configuration::updateValue('PS_BLOCK_CART_AJAX', 0);

                if (Configuration::get('MARKETPLACE_SHOW_MANAGE_CARRIER') == 1)
                    Configuration::updateValue('MARKETPLACE_SHOW_SHIP_PRODUCT', 1);
            }
            
            if (isset($errors) AND sizeof($errors))
                $this->output .= $this->displayError(implode('<br />', $errors));
            else
                $this->output .= $this->displayConfirmation($this->l('General settings updated ok.')); 
        }
        
        if (Tools::isSubmit('submitSellerAccountSettings')) {
            Configuration::updateValue('MARKETPLACE_SHOW_SHOP_NAME', Tools::getValue('MARKETPLACE_SHOW_SHOP_NAME'));
            Configuration::updateValue('MARKETPLACE_SHOW_LANGUAGE', Tools::getValue('MARKETPLACE_SHOW_LANGUAGE'));
            Configuration::updateValue('MARKETPLACE_SHOW_CIF', Tools::getValue('MARKETPLACE_SHOW_CIF'));
            Configuration::updateValue('MARKETPLACE_SHOW_PHONE', Tools::getValue('MARKETPLACE_SHOW_PHONE'));
            Configuration::updateValue('MARKETPLACE_SHOW_FAX', Tools::getValue('MARKETPLACE_SHOW_FAX'));
            Configuration::updateValue('MARKETPLACE_SHOW_ADDRESS', Tools::getValue('MARKETPLACE_SHOW_ADDRESS'));
            Configuration::updateValue('MARKETPLACE_SHOW_COUNTRY', Tools::getValue('MARKETPLACE_SHOW_COUNTRY'));
            Configuration::updateValue('MARKETPLACE_SHOW_STATE', Tools::getValue('MARKETPLACE_SHOW_STATE'));
            Configuration::updateValue('MARKETPLACE_SHOW_CITY', Tools::getValue('MARKETPLACE_SHOW_CITY'));
            Configuration::updateValue('MARKETPLACE_SHOW_POSTAL_CODE', Tools::getValue('MARKETPLACE_SHOW_POSTAL_CODE'));
            Configuration::updateValue('MARKETPLACE_SHOW_DESCRIPTION', Tools::getValue('MARKETPLACE_SHOW_DESCRIPTION'));
            Configuration::updateValue('MARKETPLACE_SHOW_LOGO', Tools::getValue('MARKETPLACE_SHOW_LOGO'));
            Configuration::updateValue('MARKETPLACE_SHOW_TERMS', Tools::getValue('MARKETPLACE_SHOW_TERMS'));
            Configuration::updateValue('MARKETPLACE_CMS_TERMS', Tools::getValue('MARKETPLACE_CMS_TERMS'));
            $this->output .= $this->displayConfirmation($this->l('Seller account settings updated ok.'));
        }
        
        if (Tools::isSubmit('submitSellerProductTabInformation')) {
            Configuration::updateValue('MARKETPLACE_SHOW_REFERENCE', Tools::getValue('MARKETPLACE_SHOW_REFERENCE'));
            Configuration::updateValue('MARKETPLACE_SHOW_EAN13', Tools::getValue('MARKETPLACE_SHOW_EAN13'));
            Configuration::updateValue('MARKETPLACE_SHOW_UPC', Tools::getValue('MARKETPLACE_SHOW_UPC'));
            Configuration::updateValue('MARKETPLACE_SHOW_CONDITION', Tools::getValue('MARKETPLACE_SHOW_CONDITION'));
            Configuration::updateValue('MARKETPLACE_SHOW_DESC_SHORT', Tools::getValue('MARKETPLACE_SHOW_DESC_SHORT'));
            Configuration::updateValue('MARKETPLACE_SHOW_DESC', Tools::getValue('MARKETPLACE_SHOW_DESC')); 
            $this->output .= $this->displayConfirmation($this->l('Seller product settings updated ok.'));
        }
        
        if (Tools::isSubmit('submitSellerProductTabPrices')) {
            Configuration::updateValue('MARKETPLACE_SHOW_PRICE', Tools::getValue('MARKETPLACE_SHOW_PRICE'));
            Configuration::updateValue('MARKETPLACE_SHOW_OFFER_PRICE', Tools::getValue('MARKETPLACE_SHOW_OFFER_PRICE'));
            Configuration::updateValue('MARKETPLACE_SHOW_TAX', Tools::getValue('MARKETPLACE_SHOW_TAX'));
            $this->output .= $this->displayConfirmation($this->l('Seller product settings updated ok.'));
        }
        
        if (Tools::isSubmit('submitSellerProductTabSeo')) {
            Configuration::updateValue('MARKETPLACE_SHOW_META_KEYWORDS', Tools::getValue('MARKETPLACE_SHOW_META_KEYWORDS'));
            Configuration::updateValue('MARKETPLACE_SHOW_META_TITLE', Tools::getValue('MARKETPLACE_SHOW_META_TITLE'));
            Configuration::updateValue('MARKETPLACE_SHOW_META_DESC', Tools::getValue('MARKETPLACE_SHOW_META_DESC'));
            Configuration::updateValue('MARKETPLACE_SHOW_LINK_REWRITE', Tools::getValue('MARKETPLACE_SHOW_LINK_REWRITE'));
            $this->output .= $this->displayConfirmation($this->l('Seller product settings updated ok.'));
        }
        
        if (Tools::isSubmit('submitSellerProductTabAssociations')) {
            Configuration::updateValue('MARKETPLACE_SHOW_SUPPLIERS', Tools::getValue('MARKETPLACE_SHOW_SUPPLIERS')); 
            
            if (Tools::getValue('MARKETPLACE_NEW_SUPPLIERS') == 1)
                Configuration::updateValue('MARKETPLACE_SHOW_SUPPLIERS', 1); 
            
            Configuration::updateValue('MARKETPLACE_NEW_SUPPLIERS', Tools::getValue('MARKETPLACE_NEW_SUPPLIERS')); 
            Configuration::updateValue('MARKETPLACE_SHOW_MANUFACTURERS', Tools::getValue('MARKETPLACE_SHOW_MANUFACTURERS')); 
            
            if (Tools::getValue('MARKETPLACE_NEW_MANUFACTURERS') == 1)
                Configuration::updateValue('MARKETPLACE_SHOW_MANUFACTURERS', 1); 
            
            Configuration::updateValue('MARKETPLACE_NEW_MANUFACTURERS', Tools::getValue('MARKETPLACE_NEW_MANUFACTURERS')); 
            Configuration::updateValue('MARKETPLACE_SHOW_CATEGORIES', Tools::getValue('MARKETPLACE_SHOW_CATEGORIES'));

            //selected categories
            if (Configuration::get('MARKETPLACE_SHOW_CATEGORIES') == 1) {
                $selected_categories = Tools::getValue('categories');
                if (is_array($selected_categories) && count($selected_categories) > 0) {
                    SellerCategory::deleteSelectedCategories($this->context->shop->id);
                    foreach ($selected_categories as $sc) {
                        $seller_category = new SellerCategory();
                        $seller_category->id_category = (int)$sc;
                        $seller_category->id_shop = (int)$this->context->shop->id;
                        $seller_category->add();
                    }
                }
                else {
                    $errors[] = $this->l('You must select at least one category.');
                }
            }
            $this->output .= $this->displayConfirmation($this->l('Seller product settings updated ok.'));
        }
        
        if (Tools::isSubmit('submitSellerProductTabShipping')) {
            Configuration::updateValue('MARKETPLACE_SHOW_WIDTH', Tools::getValue('MARKETPLACE_SHOW_WIDTH'));
            Configuration::updateValue('MARKETPLACE_SHOW_HEIGHT', Tools::getValue('MARKETPLACE_SHOW_HEIGHT'));
            Configuration::updateValue('MARKETPLACE_SHOW_DEPTH', Tools::getValue('MARKETPLACE_SHOW_DEPTH'));
            Configuration::updateValue('MARKETPLACE_SHOW_WEIGHT', Tools::getValue('MARKETPLACE_SHOW_WEIGHT'));
            Configuration::updateValue('MARKETPLACE_SHOW_SHIP_PRODUCT', Tools::getValue('MARKETPLACE_SHOW_SHIP_PRODUCT'));
            $this->output .= $this->displayConfirmation($this->l('Seller product settings updated ok.'));
        }
        
        if (Tools::isSubmit('submitSellerProductTabCombinations')) {
            Configuration::updateValue('MARKETPLACE_SHOW_ATTRIBUTES', Tools::getValue('MARKETPLACE_SHOW_ATTRIBUTES'));
            
            /*if (Configuration::get('MARKETPLACE_SHOW_ATTRIBUTES') == 1)
                Configuration::updateValue('MARKETPLACE_SHOW_QUANTITY', 0); */
            
            $this->output .= $this->displayConfirmation($this->l('Seller product settings updated ok.'));
        }
        
        if (Tools::isSubmit('submitSellerProductTabQuantities')) {
            Configuration::updateValue('MARKETPLACE_SHOW_QUANTITY', Tools::getValue('MARKETPLACE_SHOW_QUANTITY')); 
            
            /*if (Configuration::get('MARKETPLACE_SHOW_ATTRIBUTES') == 1)
                Configuration::updateValue('MARKETPLACE_SHOW_QUANTITY', 0);*/
            
            Configuration::updateValue('MARKETPLACE_SHOW_MINIMAL_QTY', Tools::getValue('MARKETPLACE_SHOW_MINIMAL_QTY'));
            Configuration::updateValue('MARKETPLACE_SHOW_AVAILABLE_NOW', Tools::getValue('MARKETPLACE_SHOW_AVAILABLE_NOW'));
            Configuration::updateValue('MARKETPLACE_SHOW_AVAILABLE_LAT', Tools::getValue('MARKETPLACE_SHOW_AVAILABLE_LAT'));
            Configuration::updateValue('MARKETPLACE_SHOW_AVAILABLE_DATE', Tools::getValue('MARKETPLACE_SHOW_AVAILABLE_DATE'));
            $this->output .= $this->displayConfirmation($this->l('Seller product settings updated ok.'));
        }
        
        if (Tools::isSubmit('submitSellerProductTabImages')) {
            $max_images = Tools::getValue('MARKETPLACE_MAX_IMAGES');
            if (!$max_images OR $max_images <= 0 OR $max_images > 100 OR !Validate::isInt($max_images)) {
                $errors[] = $this->l('Invalid max images value.');
            }
            else {
                Configuration::updateValue('MARKETPLACE_SHOW_IMAGES', Tools::getValue('MARKETPLACE_SHOW_IMAGES'));
                Configuration::updateValue('MARKETPLACE_MAX_IMAGES', Tools::getValue('MARKETPLACE_MAX_IMAGES')); 
                $this->output .= $this->displayConfirmation($this->l('Seller product settings updated ok.'));
            }
        }
        
        if (Tools::isSubmit('submitSellerProductTabFeatures')) {
            Configuration::updateValue('MARKETPLACE_SHOW_FEATURES', Tools::getValue('MARKETPLACE_SHOW_FEATURES'));
            $this->output .= $this->displayConfirmation($this->l('Seller product settings updated ok.'));
        }
        
        if (Tools::isSubmit('submitSellerProductTabVirtual')) {
            Configuration::updateValue('MARKETPLACE_SHOW_VIRTUAL', Tools::getValue('MARKETPLACE_SHOW_VIRTUAL'));
            $this->output .= $this->displayConfirmation($this->l('Seller product settings updated ok.'));
        }
        
        if (isset($errors) AND sizeof($errors))
            $this->output .= $this->displayError(implode('<br />', $errors));
        
        if (Tools::isSubmit('submitEmailSettings')) {
            Configuration::updateValue('MARKETPLACE_SEND_ADMIN', Tools::getValue('MARKETPLACE_SEND_ADMIN'));
            Configuration::updateValue('MARKETPLACE_SEND_ADMIN_REGISTER', Tools::getValue('MARKETPLACE_SEND_ADMIN_REGISTER'));
            Configuration::updateValue('MARKETPLACE_SEND_ADMIN_PRODUCT', Tools::getValue('MARKETPLACE_SEND_ADMIN_PRODUCT'));
            Configuration::updateValue('MARKETPLACE_SEND_SELLER_WELCOME', Tools::getValue('MARKETPLACE_SEND_SELLER_WELCOME'));
            Configuration::updateValue('MARKETPLACE_SEND_SELLER_ACTIVE', Tools::getValue('MARKETPLACE_SEND_SELLER_ACTIVE'));
            Configuration::updateValue('MARKETPLACE_SEND_PRODUCT_ACTIVE', Tools::getValue('MARKETPLACE_SEND_PRODUCT_ACTIVE'));
            Configuration::updateValue('MARKETPLACE_SEND_MESSAGE_ACTIVE', Tools::getValue('MARKETPLACE_SEND_MESSAGE_ACTIVE'));
            
            Configuration::updateValue('MARKETPLACE_SEND_PRODUCT_SOLD', Tools::getValue('MARKETPLACE_SEND_PRODUCT_SOLD'));
            $this->output .= $this->displayConfirmation($this->l('Email settings updated ok.'));
        }
        
        if (Tools::isSubmit('submitBalanceSettings')) {
            $date_bal_min = new DateTime(Tools::getValue('MARKETPLACE_BALANCE_DMIN_SELLER'));
            $date_bal_max = new DateTime(Tools::getValue('MARKETPLACE_BALANCE_DMAX_SELLER'));

            $diff = round(($date_bal_max->getTimestamp() - $date_bal_min->getTimestamp()));

            if($diff >= 0)
            {
                Configuration::updateValue('MARKETPLACE_BALANCE_DMIN_SELLER', Tools::getValue('MARKETPLACE_BALANCE_DMIN_SELLER'));
                Configuration::updateValue('MARKETPLACE_BALANCE_DMAX_SELLER', Tools::getValue('MARKETPLACE_BALANCE_DMAX_SELLER'));
            }
            else
                $this->output .= $this->displayError($this->l('Start of period must not be greater than end of period.'));
        }

        if (Tools::isSubmit('submitThemeSettings')) {
            Configuration::updateValue('MARKETPLACE_THEME', Tools::getValue('MARKETPLACE_THEME'));
            Configuration::updateValue('MARKETPLACE_TABS', Tools::getValue('MARKETPLACE_TABS'));
            Configuration::updateValue('MARKETPLACE_MENU_TOP', Tools::getValue('MARKETPLACE_MENU_TOP'));
            Configuration::updateValue('MARKETPLACE_MENU_OPTIONS', Tools::getValue('MARKETPLACE_MENU_OPTIONS'));
            $this->output .= $this->displayConfirmation($this->l('Theme settings updated ok.'));
        }

        if (Tools::isSubmit('submitSellerHolidays')) {
            Configuration::updateValue('MARKETPLACE_SELLER_HOLIDAYS', Tools::getValue('MARKETPLACE_SELLER_HOLIDAYS'));
            
            $this->output .= $this->displayConfirmation($this->l('Seller holidays updated ok.'));
        }
        
        if (Tools::isSubmit('submitPayments')) {
            if (Tools::getValue('MARKETPLACE_PAYPAL') == 0 && Tools::getValue('MARKETPLACE_BANKWIRE') == 0) {
                $this->output .= $this->displayError($this->l('You must select a payment method.'));
            }
            else {
                Configuration::updateValue('MARKETPLACE_PAYPAL', Tools::getValue('MARKETPLACE_PAYPAL'));
                Configuration::updateValue('MARKETPLACE_BANKWIRE', Tools::getValue('MARKETPLACE_BANKWIRE'));
                $this->output .= $this->displayConfirmation($this->l('Seller payments updated ok.'));
            }  
        }

        if (Tools::isSubmit('submitOtherSettings')) {    
            Configuration::updateValue('JPRODUCTCOMPARATOR_SHOW_SHIPPING', Tools::getValue('JPRODUCTCOMPARATOR_SHOW_SHIPPING'));
        }
    }
    
    public function getContent15() 
    {
        $this->output .= '<link href="../modules/'.$this->name.'/css/'.$this->name.'.css" rel="stylesheet" type="text/css" />';
        $this->output .= '<link rel="stylesheet" href="//code.jquery.com/ui/1.10.4/themes/smoothness/jquery-ui.css">';
        $this->output .= '<script src="//code.jquery.com/ui/1.10.4/jquery-ui.js"></script>';
        $this->output .= '<script type="text/javascript">
                            $(document).ready(function(){
                                $( "#tabs" ).tabs();
                            });
                            </script>';
        $this->output .= '<div id="tabs">';
        $this->output .= '<ul>
                            <li><a href="#tabs-1">'.$this->l('General settings').'</a></li>
                            <li><a href="#tabs-2">'.$this->l('Seller account').'</a></li>
                            <li><a href="#tabs-3">'.$this->l('Seller product').'</a></li>
                            <li><a href="#tabs-6">'.$this->l('Seller payment').'</a></li> 
                            <li><a href="#tabs-4">'.$this->l('Emails').'</a></li> 
                            <li><a href="#tabs-5">'.$this->l('Front office theme').'</a></li> 
                          </ul>';

        $this->output .= '<div id="tabs-1">';
        $this->output .= $this->displayFormGeneralSettings();
        $this->output .= '</div>';

        $this->output .= '<div id="tabs-2">';
        $this->output .= $this->displayFormSellerAccountSettings();
        $this->output .= '</div>';

        $this->output .= '<div id="tabs-3">';

        $this->output .= $this->displayFormSellerProductSettings();

        $this->output .= '</div>';

        $this->output .= '<div id="tabs-4">';

        $this->output .= $this->displayFormEmailSettings();

        $this->output .= '</div>';

        $this->output .= '<div id="tabs-5">';

        $this->output .= $this->displayFormThemeSettings();

        $this->output .= '</div>';

        $this->output .= '<div id="tabs-6">';

        $this->output .= $this->displayFormPayments();

        $this->output .= '</div>';

        $this->output .= '<div id="tabs-7">';

        $this->output .= $this->displayCronSellerHolidays();

        $this->output .= '</div>';

        $this->output .= '</div>';

        return $this->output;
    }
    
    public function getContent16() 
    {
        $this->output .= '<div id="modulecontent" class="clearfix">';
        $this->output .= '<div class="col-lg-2">';
        $this->output .= '<div class="list-group">';
        
        $this->output .= '<a href="#information" class="list-group-item active" data-toggle="tab">'.$this->l('Informations').'</a>';
        
        $this->output .= '<a href="#general_settings" class="list-group-item" data-toggle="tab">'.$this->l('General settings').'</a>';
        $this->output .= '<a href="#seller_account_settings" class="list-group-item" data-toggle="tab">'.$this->l('Seller account').'</a>';
        $this->output .= '<a href="#seller_product_settings" class="list-group-item" data-toggle="tab">'.$this->l('Seller product').'</a>';
        $this->output .= '<a href="#balance_settings" class="list-group-item" data-toggle="tab">'.$this->l('Rules balances').'</a>';

        $this->output .= '<a href="#email_settings" class="list-group-item" data-toggle="tab">'.$this->l('Emails').'</a>';

        $this->output .= '<a href="#seller_payment_settings" class="list-group-item" data-toggle="tab">'.$this->l('Seller payment').'</a>';

        $this->output .= '<a href="#seller_holidays_settings" class="list-group-item" data-toggle="tab">'.$this->l('Seller holidays').'</a>';

        $this->output .= '<a href="#seller_other_settings" class="list-group-item" data-toggle="tab">'.$this->l('Other sellers sell').'</a>';

        $this->output .= '<a href="#seller_comments_settings" class="list-group-item" data-toggle="tab">'.$this->l('Seller product comments').'</a>';

        //$this->output .= '<a href="#theme_settings" class="list-group-item" data-toggle="tab">'.$this->l('Front office theme').'</a>';
        
        $this->output .= '</div>';
        $this->output .= '</div>';

        
        $this->output .= '<div class="tab-content col-lg-10">';
        
        $this->output .= '<div class="tab-pane active panel" id="information">';
        
        $this->context->smarty->assign(array(
            'module_dir' => $this->_path,
            'name' => $this->displayName, 
            'version' => $this->version, 
            'description' => $this->description,
            'iso_code' => $this->context->language->iso_code,
        ));
	$this->output .= $this->context->smarty->fetch($this->local_path.'views/templates/admin/information.tpl');
        
        $this->output .= '</div>';
        
        $this->output .= '<div class="tab-pane panel" id="general_settings">';
        $this->output .= $this->displayFormGeneralSettings();
        $this->output .= '</div>';

        $this->output .= '<div class="tab-pane panel" id="seller_account_settings">';   
        $this->output .= $this->displayFormSellerAccountSettings();   
        $this->output .= '</div>';

        $this->output .= '<div class="tab-pane panel" id="seller_product_settings">';   
        $this->output .= $this->displayFormSellerProductSettings();    
        $this->output .= '</div>';

        $this->output .= '<div class="tab-pane panel" id="balance_settings">';
        $this->output .= $this->displayFormBalanceSettings();
        $this->output .= '</div>';

        $this->output .= '<div  class="tab-pane panel" id="email_settings">';
        $this->output .= $this->displayFormEmailSettings();
        $this->output .= '</div>';

        $this->output .= '<div  class="tab-pane panel" id="seller_payment_settings">';
        $this->output .= $this->displayFormPayments();   
        $this->output .= '</div>';       

        $this->output .= '<div  class="tab-pane panel" id="seller_holidays_settings">';
        $this->output .= $this->displayCronSellerHolidays();  
        $this->output .= '</div>'; 

        $this->output .= '<div  class="tab-pane panel" id="seller_other_settings">';
        $this->output .= $this->displayFormOtherSeller();  
        $this->output .= '</div>';

        $this->output .= '<div  class="tab-pane panel" id="seller_comments_settings">';
        $this->output .= $this->displayFormSellerCommentSettings();  
        $this->output .= '</div>';

        /*$this->output .= '<div  class="tab-pane panel" id="theme_settings">';
        $this->output .= $this->displayFormThemeSettings();      
        $this->output .= '</div>';*/
        
        $this->output .= '</div>';

        $this->output .= '</div>';
        
        $this->output .= '<script type="text/javascript">
                            $(document).ready(function(){
                                $(".list-group-item").on("click", function() {
                                    $(".list-group-item").removeClass("active");
                                    $(this).addClass("active");
                                });
                            });
                            </script>';
        
        return $this->output;
    }
    
    public function getContent() 
    {        
        $this->postProcess();
        
        if (version_compare(_PS_VERSION_, '1.6', '<'))
            return $this->getContent15();
        else
            return $this->getContent16();
        
    }
    
    public function getGroupsToSeller() 
    {
        $logged_groups = array();
        $groups = Group::getGroups($this->context->language->id);
        foreach ($groups as $group) {
            if ($group['id_group'] > 2)
                $logged_groups[] = $group;
        }
        return $logged_groups;
    }
    
    private function displayFormGeneralSettings() 
    {
        if (version_compare(_PS_VERSION_, '1.6', '<')) {
            $type = 'radio';
            $disabled = true;
        }
        else {
            $type = 'switch';
            $disabled = false;
        }
        
        $customer_groups_to_seller = $this->getGroupsToSeller();
        
        $states = OrderState::getOrderStates($this->context->language->id);
        
        $languages = Language::getLanguages(false);
        foreach ($languages as $k => $language)
            $languages[$k]['is_default'] = (int)$language['id_lang'] == Configuration::get('PS_LANG_DEFAULT');

        $helper = new HelperForm();
        $helper->module = $this;
        $helper->name_controller = $this->name;
        $helper->identifier = $this->identifier;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->languages = $languages;
        $helper->currentIndex = AdminController::$currentIndex.'&configure='.$this->name;
        $helper->default_form_language = (int)Configuration::get('PS_LANG_DEFAULT');
        $helper->allow_employee_form_lang = true;
        $helper->toolbar_scroll = true;
        $helper->title = $this->displayName;
        $helper->submit_action = 'submitGeneralSettings';

        $this->fields_form[0]['form'] = array(
            'tinymce' => false,
            'legend' => array(
                'title' => $this->l('General settings')
            ),
            'input' => array(
                array(
                    'type' => $type,
                    'label' => $this->l('Moderate sellers'),
                    'name' => 'MARKETPLACE_MODERATE_SELLER',
                    'required' => false,
                    'is_bool' => true,
                    'class' => 't',
                    'values' => array(
                        array(
                                'id' => 'active_on',
                                'value' => 1,
                                'label' => $this->l('Enabled')
                        ),
                        array(
                                'id' => 'active_off',
                                'value' => 0,
                                'label' => $this->l('Disabled')
                        )
                    ),
                ),
                array(
                    'type' => $type,
                    'label' => $this->l('Moderate products'),
                    'name' => 'MARKETPLACE_MODERATE_PRODUCT',
                    'required' => false,
                    'is_bool' => true,
                    'class' => 't',
                    'values' => array(
                        array(
                                'id' => 'active_on',
                                'value' => 1,
                                'label' => $this->l('Enabled')
                        ),
                        array(
                                'id' => 'active_off',
                                'value' => 0,
                                'label' => $this->l('Disabled')
                        )
                    ),
                ),
                array(
                    'type' => $type,
                    'label' => $this->l('Moderate messages'),
                    'name' => 'MARKETPLACE_MODERATE_MESSAGE',
                    'required' => false,
                    'is_bool' => true,
                    'class' => 't',
                    'values' => array(
                        array(
                                'id' => 'active_on',
                                'value' => 1,
                                'label' => $this->l('Enabled')
                        ),
                        array(
                                'id' => 'active_off',
                                'value' => 0,
                                'label' => $this->l('Disabled')
                        )
                    ),
                ),
                array(
                    'type' => 'text',
                    'label' => $this->l('Fixed commission'),
                    'suffix' => $this->context->currency->sign,
                    'name' => 'MARKETPLACE_FIXED_COMMISSION',
                    'desc' => $this->l('Fixed commission for each sale.'),
                    'required' => true,
                    'lang' => false,
                    'col' => 2,
                ),
                array(
                    'type' => 'text',
                    'label' => $this->l('Variable commission'),
                    'suffix' => '%',
                    'name' => 'MARKETPLACE_VARIABLE_COMMISSION',
                    'desc' => $this->l('This percentage is applied to the total price of products sold. The seller collect').' '.Configuration::get('MARKETPLACE_VARIABLE_COMMISSION').'% '.$this->l('of sale of your products. Values: 0-100'),
                    'required' => true,
                    'lang' => false,
                    'col' => 2,
                ),
                array(
                    'type' => 'checkbox',
                    'label' => $this->l('Customer group'), 
                    'desc' => $this->l('Select group of customers who may be selling.'),  
                    'name' => 'MARKETPLACE_CUSTOMER_GROUP', 
                    'values' => array(
                        'query' => $customer_groups_to_seller,
			'id' => 'id_group',
			'name' => 'name'
                    ),
                ),
                array(
                    'type' => $type,
                    'label' => $this->l('Assign commisions when a customer places an order'),
                    'name' => 'MARKETPLACE_COMMISIONS_ORDER',
                    'required' => false,
                    'is_bool' => true,
                    'class' => 't',
                    'desc' => $this->l('Commissions are awarded when a customer places an order'),
                    'values' => array(
                        array(
                                'id' => 'active_on',
                                'value' => 1,
                                'label' => $this->l('Enabled')
                        ),
                        array(
                                'id' => 'active_off',
                                'value' => 0,
                                'label' => $this->l('Disabled')
                        )
                    ),
                ),
                array(
                    'type' => $type,
                    'label' => $this->l('Assign commisions when an order status changes'),
                    'name' => 'MARKETPLACE_COMMISIONS_STATE',
                    'required' => false,
                    'is_bool' => true,
                    'class' => 't',
                    'desc' => $this->l('Commissions are awarded when an order status changes'),
                    'values' => array(
                        array(
                                'id' => 'active_on',
                                'value' => 1,
                                'label' => $this->l('Enabled')
                        ),
                        array(
                                'id' => 'active_off',
                                'value' => 0,
                                'label' => $this->l('Disabled')
                        )
                    ),
                ),
                array(
		  'type' => 'select',
		  'label' => $this->l('Order state'),
		  'name' => 'MARKETPLACE_ORDER_STATE',
                  'desc' => $this->l('Select the order status to send notification to vendors when an order is changed to this state.'),
		  'required' => false,
		  'options' => array(
			'query' => $states,
			'id' => 'id_order_state',
			'name' => 'name'
		  )
		),
                array(
                    'type' => $type,
                    'label' => $this->l('Seller assumes shipping'),
                    'name' => 'MARKETPLACE_SHIPPING_COMMISSION',
                    'required' => false,
                    'is_bool' => true,
                    'class' => 't',
                    'desc' => $this->l('The shipping cost is asssumed by seller.'),
                    'values' => array(
                        array(
                                'id' => 'active_on',
                                'value' => 1,
                                'label' => $this->l('Enabled')
                        ),
                        array(
                                'id' => 'active_off',
                                'value' => 0,
                                'label' => $this->l('Disabled')
                        )
                    ),
                ),
                array(
                    'type' => $type,
                    'label' => $this->l('Seller assumes taxes'),
                    'name' => 'MARKETPLACE_TAX_COMMISSION',
                    'required' => false,
                    'is_bool' => true,
                    'class' => 't',                    
                    'values' => array(
                        array(
                                'id' => 'active_on',
                                'value' => 1,
                                'label' => $this->l('Enabled')
                        ),
                        array(
                                'id' => 'active_off',
                                'value' => 0,
                                'label' => $this->l('Disabled')
                        )
                    ),
                ),
                array(
                    'type' => 'checkbox',
                    'label' => $this->l('Cancel commissions'), 
                    'desc' => $this->l('Cancel commissions automatequely when the order changes state.'),  
                    'name' => 'MARKETPLACE_CANCEL_COMMISSION', 
                    'values' => array(
                        'query' => $states,
			             'id' => 'id_order_state',
			             'name' => 'name'
                    ),
                ),
                array(
                    'type' => 'checkbox',
                    'label' => $this->l('Order states allowed for seller'), 
                    'desc' => $this->l('Order states allowed for seller'),  
                    'name' => 'MARKETPLACE_STATES_ORDER_SELLER', 
                    'values' => array(
                        'query' => $states,
			             'id' => 'id_order_state',
			             'name' => 'name'
                    ),
                ),
                array(
                    'type' => $type,
                    'label' => $this->l('Show contact seller in product page'),
                    'name' => 'MARKETPLACE_SHOW_CONTACT',
                    'required' => false,
                    'is_bool' => true,
                    'class' => 't',
                    'values' => array(
                        array(
                                'id' => 'active_on',
                                'value' => 1,
                                'label' => $this->l('Enabled')
                        ),
                        array(
                                'id' => 'active_off',
                                'value' => 0,
                                'label' => $this->l('Disabled')
                        )
                    ),
                ),
                /*array(
                    'type' => $type,
                    'label' => $this->l('Show dashboard'),
                    'name' => 'MARKETPLACE_SHOW_DASHBOARD',
                    'required' => false,
                    'is_bool' => true,
                    'class' => 't',
                    'values' => array(
                        array(
                                'id' => 'active_on',
                                'value' => 1,
                                'label' => $this->l('Enabled')
                        ),
                        array(
                                'id' => 'active_off',
                                'value' => 0,
                                'label' => $this->l('Disabled')
                        )
                    ),
                ),*/
                array(
                    'type' => $type,
                    'label' => $this->l('Show seller profile'),
                    'name' => 'MARKETPLACE_SHOW_PROFILE',
                    'required' => false,
                    'is_bool' => true,
                    'class' => 't',
                    'values' => array(
                        array(
                                'id' => 'active_on',
                                'value' => 1,
                                'label' => $this->l('Enabled')
                        ),
                        array(
                                'id' => 'active_off',
                                'value' => 0,
                                'label' => $this->l('Disabled')
                        )
                    ),
                ),
                array(
                    'type' => $type,
                    'label' => $this->l('Show orders and commisions to sellers'),
                    'name' => 'MARKETPLACE_SHOW_ORDERS',
                    'required' => false,
                    'is_bool' => true,
                    'class' => 't',
                    'values' => array(
                        array(
                                'id' => 'active_on',
                                'value' => 1,
                                'label' => $this->l('Enabled')
                        ),
                        array(
                                'id' => 'active_off',
                                'value' => 0,
                                'label' => $this->l('Disabled')
                        )
                    ),
                ),
                array(
                    'type' => $type,
                    'label' => $this->l('Allow to sellers manage your orders'),
                    'name' => 'MARKETPLACE_SHOW_MANAGE_ORDERS',
                    'required' => false,
                    'disabled' => $disabled,
                    'is_bool' => true,
                    'class' => 't',
                    'values' => array(
                        array(
                                'id' => 'active_on',
                                'value' => 1,
                                'label' => $this->l('Enabled')
                        ),
                        array(
                                'id' => 'active_off',
                                'value' => 0,
                                'label' => $this->l('Disabled')
                        )
                    ),
                ),
                array(
                    'type' => $type,
                    'label' => $this->l('Allow to sellers manage your carriers and shipping cost'),
                    'name' => 'MARKETPLACE_SHOW_MANAGE_CARRIER',
                    'required' => false,
                    'disabled' => $disabled,
                    'is_bool' => true,
                    'class' => 't',
                    'values' => array(
                        array(
                                'id' => 'active_on',
                                'value' => 1,
                                'label' => $this->l('Enabled')
                        ),
                        array(
                                'id' => 'active_off',
                                'value' => 0,
                                'label' => $this->l('Disabled')
                        )
                    ),
                ),
                array(
                    'type' => $type,
                    'label' => $this->l('Show edit sellers account'),
                    'name' => 'MARKETPLACE_SHOW_EDIT_ACCOUNT',
                    'required' => false,
                    'is_bool' => true,
                    'class' => 't',
                    'values' => array(
                        array(
                                'id' => 'active_on',
                                'value' => 1,
                                'label' => $this->l('Enabled')
                        ),
                        array(
                                'id' => 'active_off',
                                'value' => 0,
                                'label' => $this->l('Disabled')
                        )
                    ),
                ),
                array(
                    'type' => $type,
                    'label' => $this->l('Allow to sellers edit product'),
                    'name' => 'MARKETPLACE_SHOW_EDIT_PRODUCT',
                    'required' => false,
                    'is_bool' => true,
                    'class' => 't',
                    'values' => array(
                        array(
                                'id' => 'active_on',
                                'value' => 1,
                                'label' => $this->l('Enabled')
                        ),
                        array(
                                'id' => 'active_off',
                                'value' => 0,
                                'label' => $this->l('Disabled')
                        )
                    ),
                ),
                array(
                    'type' => $type,
                    'label' => $this->l('Allow to sellers delete product'),
                    'name' => 'MARKETPLACE_SHOW_DELETE_PRODUCT',
                    'required' => false,
                    'is_bool' => true,
                    'class' => 't',
                    'values' => array(
                        array(
                                'id' => 'active_on',
                                'value' => 1,
                                'label' => $this->l('Enabled')
                        ),
                        array(
                                'id' => 'active_off',
                                'value' => 0,
                                'label' => $this->l('Disabled')
                        )
                    ),
                ),
                array(
                    'type' => $type,
                    'label' => $this->l('Allow to sellers enable and disable your products'),
                    'name' => 'MARKETPLACE_SHOW_ACTIVE_PRODUCT',
                    'required' => false,
                    'is_bool' => true,
                    'class' => 't',
                    'values' => array(
                        array(
                                'id' => 'active_on',
                                'value' => 1,
                                'label' => $this->l('Enabled')
                        ),
                        array(
                                'id' => 'active_off',
                                'value' => 0,
                                'label' => $this->l('Disabled')
                        )
                    ),
                ),
                array(
                    'type' => $type,
                    'label' => $this->l('Allow to sellers import and export your products with csv file'),
                    'name' => 'MARKETPLACE_SELLER_IMPORT_PROD',
                    'required' => false,
                    'is_bool' => true,
                    'class' => 't',
                    'values' => array(
                        array(
                                'id' => 'active_on',
                                'value' => 1,
                                'label' => $this->l('Enabled')
                        ),
                        array(
                                'id' => 'active_off',
                                'value' => 0,
                                'label' => $this->l('Disabled')
                        )
                    ),
                ),
                array(
                    'type' => $type,
                    'label' => $this->l('Allow favorite seller'),
                    'name' => 'MARKETPLACE_SELLER_FAVORITE',
                    'required' => false,
                    'is_bool' => true,
                    'class' => 't',
                    'desc' => $this->l('Allow customers to add favorite sellers in your account (followers)'),
                    'values' => array(
                        array(
                                'id' => 'active_on',
                                'value' => 1,
                                'label' => $this->l('Enabled')
                        ),
                        array(
                                'id' => 'active_off',
                                'value' => 0,
                                'label' => $this->l('Disabled')
                        )
                    ),
                ),
                array(
                    'type' => $type,
                    'label' => $this->l('Allow rating seller'),
                    'name' => 'MARKETPLACE_SELLER_RATING',
                    'required' => false,
                    'is_bool' => true,
                    'class' => 't',
                    'values' => array(
                        array(
                                'id' => 'active_on',
                                'value' => 1,
                                'label' => $this->l('Enabled')
                        ),
                        array(
                                'id' => 'active_off',
                                'value' => 0,
                                'label' => $this->l('Disabled')
                        )
                    ),
                ),
                array(
                    'type' => $type,
                    'label' => $this->l('Show new products in seller profile'),
                    'name' => 'MARKETPLACE_NEW_PRODUCTS',
                    'required' => false,
                    'is_bool' => true,
                    'class' => 't',
                    'desc' => $this->l('Allow show new products in sellers profile.'),
                    'values' => array(
                        array(
                                'id' => 'active_on',
                                'value' => 1,
                                'label' => $this->l('Enabled')
                        ),
                        array(
                                'id' => 'active_off',
                                'value' => 0,
                                'label' => $this->l('Disabled')
                        )
                    ),
                ),
                array(
                    'type' => $type,
                    'label' => $this->l('Show seller in product list'),
                    'name' => 'MARKETPLACE_SHOW_SELLER_PLIST',
                    'required' => false,
                    'is_bool' => true,
                    'class' => 't',
                    'desc' => $this->l('Allow show the seller in all product listings.'),
                    'values' => array(
                        array(
                                'id' => 'active_on',
                                'value' => 1,
                                'label' => $this->l('Enabled')
                        ),
                        array(
                                'id' => 'active_off',
                                'value' => 0,
                                'label' => $this->l('Disabled')
                        )
                    ),
                ),
            ),
            'submit' => array(
                'name' => 'submitGeneralSettings',
                'title' => $this->l('Save'),
                'class' => 't btn btn-default pull-right',
            ),
        );
        
        if ($customer_groups_to_seller) {
            foreach ($customer_groups_to_seller as $group) {
                if (Configuration::get('MARKETPLACE_CUSTOMER_GROUP_'.$group['id_group']) == 1)
                    $helper->fields_value['MARKETPLACE_CUSTOMER_GROUP_'.$group['id_group']] = 1;
                else
                    $helper->fields_value['MARKETPLACE_CUSTOMER_GROUP_'.$group['id_group']] = 0;
            }
        }
        
        if ($states) {
            $listStateOrderSeller = explode(',', Configuration::get('MARKETPLACE_STATES_ORDER_SELLER'));
            
            foreach ($states as $state) {
                if (Configuration::get('MARKETPLACE_CANCEL_COMMISSION_'.$state['id_order_state']) == 1)
                    $helper->fields_value['MARKETPLACE_CANCEL_COMMISSION_'.$state['id_order_state']] = 1;
                else
                    $helper->fields_value['MARKETPLACE_CANCEL_COMMISSION_'.$state['id_order_state']] = 0;
                    
                if( in_array($state['id_order_state'],$listStateOrderSeller) ){
                    $helper->fields_value['MARKETPLACE_STATES_ORDER_SELLER_'.$state['id_order_state']] = 1;
                }else{
                    $helper->fields_value['MARKETPLACE_STATES_ORDER_SELLER_'.$state['id_order_state']] = 0;
                }
            }
        }

        $helper->fields_value['MARKETPLACE_MODERATE_SELLER'] = Configuration::get('MARKETPLACE_MODERATE_SELLER');
        $helper->fields_value['MARKETPLACE_MODERATE_PRODUCT'] = Configuration::get('MARKETPLACE_MODERATE_PRODUCT');
        $helper->fields_value['MARKETPLACE_MODERATE_MESSAGE'] = Configuration::get('MARKETPLACE_MODERATE_MESSAGE');
        $helper->fields_value['MARKETPLACE_COMMISIONS_ORDER'] = Configuration::get('MARKETPLACE_COMMISIONS_ORDER');
        $helper->fields_value['MARKETPLACE_COMMISIONS_STATE'] = Configuration::get('MARKETPLACE_COMMISIONS_STATE');
        $helper->fields_value['MARKETPLACE_FIXED_COMMISSION'] = Configuration::get('MARKETPLACE_FIXED_COMMISSION');
        $helper->fields_value['MARKETPLACE_VARIABLE_COMMISSION'] = Configuration::get('MARKETPLACE_VARIABLE_COMMISSION');
        $helper->fields_value['MARKETPLACE_ORDER_STATE'] = Configuration::get('MARKETPLACE_ORDER_STATE');
        $helper->fields_value['MARKETPLACE_SHIPPING_COMMISSION'] = Configuration::get('MARKETPLACE_SHIPPING_COMMISSION');
        $helper->fields_value['MARKETPLACE_TAX_COMMISSION'] = Configuration::get('MARKETPLACE_TAX_COMMISSION');
        $helper->fields_value['MARKETPLACE_SHOW_CONTACT'] = Configuration::get('MARKETPLACE_SHOW_CONTACT');
        $helper->fields_value['MARKETPLACE_SHOW_DASHBOARD'] = Configuration::get('MARKETPLACE_SHOW_DASHBOARD');
        $helper->fields_value['MARKETPLACE_SHOW_MANAGE_ORDERS'] = Configuration::get('MARKETPLACE_SHOW_MANAGE_ORDERS');
        $helper->fields_value['MARKETPLACE_SHOW_MANAGE_CARRIER'] = Configuration::get('MARKETPLACE_SHOW_MANAGE_CARRIER');
        $helper->fields_value['MARKETPLACE_SHOW_PROFILE'] = Configuration::get('MARKETPLACE_SHOW_PROFILE');
        $helper->fields_value['MARKETPLACE_SHOW_EDIT_ACCOUNT'] = Configuration::get('MARKETPLACE_SHOW_EDIT_ACCOUNT');
        $helper->fields_value['MARKETPLACE_SHOW_EDIT_PRODUCT'] = Configuration::get('MARKETPLACE_SHOW_EDIT_PRODUCT');
        $helper->fields_value['MARKETPLACE_SHOW_DELETE_PRODUCT'] = Configuration::get('MARKETPLACE_SHOW_DELETE_PRODUCT');
        $helper->fields_value['MARKETPLACE_SHOW_ACTIVE_PRODUCT'] = Configuration::get('MARKETPLACE_SHOW_ACTIVE_PRODUCT');
        $helper->fields_value['MARKETPLACE_SELLER_IMPORT_PROD'] = Configuration::get('MARKETPLACE_SELLER_IMPORT_PROD');
        $helper->fields_value['MARKETPLACE_SHOW_ORDERS'] = Configuration::get('MARKETPLACE_SHOW_ORDERS');
        $helper->fields_value['MARKETPLACE_SELLER_FAVORITE'] = Configuration::get('MARKETPLACE_SELLER_FAVORITE');
        $helper->fields_value['MARKETPLACE_SELLER_RATING'] = Configuration::get('MARKETPLACE_SELLER_RATING');
        $helper->fields_value['MARKETPLACE_NEW_PRODUCTS'] = Configuration::get('MARKETPLACE_NEW_PRODUCTS');
        $helper->fields_value['MARKETPLACE_SHOW_SELLER_PLIST'] = Configuration::get('MARKETPLACE_SHOW_SELLER_PLIST');

        return $helper->generateForm($this->fields_form);
    }
    
    private function displayFormSellerAccountSettings() 
    {
        if (version_compare(_PS_VERSION_, '1.6', '<'))
            $type = 'radio';
        else
            $type = 'switch';
        
        $languages = Language::getLanguages(false);
        foreach ($languages as $k => $language)
            $languages[$k]['is_default'] = (int)$language['id_lang'] == Configuration::get('PS_LANG_DEFAULT');

        $helper = new HelperForm();
        $helper->module = $this;
        $helper->name_controller = $this->name;
        $helper->identifier = $this->identifier;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->languages = $languages;
        $helper->currentIndex = AdminController::$currentIndex.'&configure='.$this->name;
        $helper->default_form_language = (int)Configuration::get('PS_LANG_DEFAULT');
        $helper->allow_employee_form_lang = true;
        $helper->toolbar_scroll = true;
        $helper->title = $this->displayName;
        $helper->submit_action = 'submitSellerAccountSettings';

        $this->fields_form[0]['form'] = array(
            'tinymce' => false, 
            'legend' => array(
                'title' => $this->l('Seller account')
            ),
            'input' => array(
                array(
                    'type' => $type,
                    'label' => $this->l('Show seller shop name'),
                    'name' => 'MARKETPLACE_SHOW_SHOP_NAME',
                    'required' => false,
                    'is_bool' => true,
                    'class' => 't',
                    'values' => array(
                        array(
                                'id' => 'active_on',
                                'value' => 1,
                                'label' => $this->l('Enabled')
                        ),
                        array(
                                'id' => 'active_off',
                                'value' => 0,
                                'label' => $this->l('Disabled')
                        )
                    ),
                ),
                array(
                    'type' => $type,
                    'label' => $this->l('Show seller CIF/NIF'),
                    'name' => 'MARKETPLACE_SHOW_CIF',
                    'required' => false,
                    'is_bool' => true,
                    'class' => 't',
                    'values' => array(
                        array(
                                'id' => 'active_on',
                                'value' => 1,
                                'label' => $this->l('Enabled')
                        ),
                        array(
                                'id' => 'active_off',
                                'value' => 0,
                                'label' => $this->l('Disabled')
                        )
                    ),
                ),
                array(
                    'type' => $type,
                    'label' => $this->l('Show seller language'),
                    'name' => 'MARKETPLACE_SHOW_LANGUAGE',
                    'required' => false,
                    'is_bool' => true,
                    'class' => 't',
                    'values' => array(
                        array(
                                'id' => 'active_on',
                                'value' => 1,
                                'label' => $this->l('Enabled')
                        ),
                        array(
                                'id' => 'active_off',
                                'value' => 0,
                                'label' => $this->l('Disabled')
                        )
                    ),
                ),
                array(
                    'type' => $type,
                    'label' => $this->l('Show seller phone'),
                    'name' => 'MARKETPLACE_SHOW_PHONE',
                    'required' => false,
                    'is_bool' => true,
                    'class' => 't',
                    'values' => array(
                        array(
                                'id' => 'active_on',
                                'value' => 1,
                                'label' => $this->l('Enabled')
                        ),
                        array(
                                'id' => 'active_off',
                                'value' => 0,
                                'label' => $this->l('Disabled')
                        )
                    ),
                ),
                array(
                    'type' => $type,
                    'label' => $this->l('Show seller fax'),
                    'name' => 'MARKETPLACE_SHOW_FAX',
                    'required' => false,
                    'is_bool' => true,
                    'class' => 't',
                    'values' => array(
                        array(
                                'id' => 'active_on',
                                'value' => 1,
                                'label' => $this->l('Enabled')
                        ),
                        array(
                                'id' => 'active_off',
                                'value' => 0,
                                'label' => $this->l('Disabled')
                        )
                    ),
                ),
                array(
                    'type' => $type,
                    'label' => $this->l('Show seller address'),
                    'name' => 'MARKETPLACE_SHOW_ADDRESS',
                    'required' => false,
                    'is_bool' => true,
                    'class' => 't',
                    'values' => array(
                        array(
                                'id' => 'active_on',
                                'value' => 1,
                                'label' => $this->l('Enabled')
                        ),
                        array(
                                'id' => 'active_off',
                                'value' => 0,
                                'label' => $this->l('Disabled')
                        )
                    ),
                ),
                array(
                    'type' => $type,
                    'label' => $this->l('Show seller country'),
                    'name' => 'MARKETPLACE_SHOW_COUNTRY',
                    'required' => false,
                    'is_bool' => true,
                    'class' => 't',
                    'values' => array(
                        array(
                                'id' => 'active_on',
                                'value' => 1,
                                'label' => $this->l('Enabled')
                        ),
                        array(
                                'id' => 'active_off',
                                'value' => 0,
                                'label' => $this->l('Disabled')
                        )
                    ),
                ),
                array(
                    'type' => $type,
                    'label' => $this->l('Show seller state'),
                    'name' => 'MARKETPLACE_SHOW_STATE',
                    'required' => false,
                    'is_bool' => true,
                    'class' => 't',
                    'values' => array(
                        array(
                                'id' => 'active_on',
                                'value' => 1,
                                'label' => $this->l('Enabled')
                        ),
                        array(
                                'id' => 'active_off',
                                'value' => 0,
                                'label' => $this->l('Disabled')
                        )
                    ),
                ),
                array(
                    'type' => $type,
                    'label' => $this->l('Show seller city'),
                    'name' => 'MARKETPLACE_SHOW_CITY',
                    'required' => false,
                    'is_bool' => true,
                    'class' => 't',
                    'values' => array(
                        array(
                                'id' => 'active_on',
                                'value' => 1,
                                'label' => $this->l('Enabled')
                        ),
                        array(
                                'id' => 'active_off',
                                'value' => 0,
                                'label' => $this->l('Disabled')
                        )
                    ),
                ),
                array(
                    'type' => $type,
                    'label' => $this->l('Show seller postal code'),
                    'name' => 'MARKETPLACE_SHOW_POSTAL_CODE',
                    'required' => false,
                    'is_bool' => true,
                    'class' => 't',
                    'values' => array(
                        array(
                                'id' => 'active_on',
                                'value' => 1,
                                'label' => $this->l('Enabled')
                        ),
                        array(
                                'id' => 'active_off',
                                'value' => 0,
                                'label' => $this->l('Disabled')
                        )
                    ),
                ),
                array(
                    'type' => $type,
                    'label' => $this->l('Show seller description'),
                    'name' => 'MARKETPLACE_SHOW_DESCRIPTION',
                    'required' => false,
                    'is_bool' => true,
                    'class' => 't',
                    'values' => array(
                        array(
                                'id' => 'active_on',
                                'value' => 1,
                                'label' => $this->l('Enabled')
                        ),
                        array(
                                'id' => 'active_off',
                                'value' => 0,
                                'label' => $this->l('Disabled')
                        )
                    ),
                ),
                array(
                    'type' => $type,
                    'label' => $this->l('Show seller logo'),
                    'name' => 'MARKETPLACE_SHOW_LOGO',
                    'required' => false,
                    'is_bool' => true,
                    'class' => 't',
                    'values' => array(
                        array(
                                'id' => 'active_on',
                                'value' => 1,
                                'label' => $this->l('Enabled')
                        ),
                        array(
                                'id' => 'active_off',
                                'value' => 0,
                                'label' => $this->l('Disabled')
                        )
                    ),
                ),
                array(
                    'type' => $type,
                    'label' => $this->l('Show terms of service'),
                    'name' => 'MARKETPLACE_SHOW_TERMS',
                    'required' => false,
                    'is_bool' => true,
                    'class' => 't',
                    'values' => array(
                        array(
                                'id' => 'active_on',
                                'value' => 1,
                                'label' => $this->l('Enabled')
                        ),
                        array(
                                'id' => 'active_off',
                                'value' => 0,
                                'label' => $this->l('Disabled')
                        )
                    ),
                ),
                array(
                    'type' => 'select',
                    'label' => $this->l('Page CMS Terms'),
                    'name' => 'MARKETPLACE_CMS_TERMS',
                    'desc' => $this->l('Select page cms to terms of service to sellers.'),
                    'required' => false,
                    'options' => array(
                          'query' => CMS::getCMSPages($this->context->language->id),
                          'id' => 'id_cms',
                          'name' => 'meta_title'
                    )
		),
            ),
            'submit' => array(
                'name' => 'submitSellerAccountSettings',
                'title' => $this->l('Save'),
                'class' => 't btn btn-default pull-right',
            ),
        );

        $helper->fields_value['MARKETPLACE_SHOW_SHOP_NAME'] = Configuration::get('MARKETPLACE_SHOW_SHOP_NAME');
        $helper->fields_value['MARKETPLACE_SHOW_CIF'] = Configuration::get('MARKETPLACE_SHOW_CIF');
        $helper->fields_value['MARKETPLACE_SHOW_LANGUAGE'] = Configuration::get('MARKETPLACE_SHOW_LANGUAGE');
        $helper->fields_value['MARKETPLACE_SHOW_PHONE'] = Configuration::get('MARKETPLACE_SHOW_PHONE');
        $helper->fields_value['MARKETPLACE_SHOW_FAX'] = Configuration::get('MARKETPLACE_SHOW_FAX');
        $helper->fields_value['MARKETPLACE_SHOW_ADDRESS'] = Configuration::get('MARKETPLACE_SHOW_ADDRESS');
        $helper->fields_value['MARKETPLACE_SHOW_COUNTRY'] = Configuration::get('MARKETPLACE_SHOW_COUNTRY');
        $helper->fields_value['MARKETPLACE_SHOW_STATE'] = Configuration::get('MARKETPLACE_SHOW_STATE');
        $helper->fields_value['MARKETPLACE_SHOW_CITY'] = Configuration::get('MARKETPLACE_SHOW_CITY');
        $helper->fields_value['MARKETPLACE_SHOW_POSTAL_CODE'] = Configuration::get('MARKETPLACE_SHOW_POSTAL_CODE');
        $helper->fields_value['MARKETPLACE_SHOW_DESCRIPTION'] = Configuration::get('MARKETPLACE_SHOW_DESCRIPTION');
        $helper->fields_value['MARKETPLACE_SHOW_LOGO'] = Configuration::get('MARKETPLACE_SHOW_LOGO');
        $helper->fields_value['MARKETPLACE_SHOW_TERMS'] = Configuration::get('MARKETPLACE_SHOW_TERMS');
        $helper->fields_value['MARKETPLACE_CMS_TERMS'] = Configuration::get('MARKETPLACE_CMS_TERMS');

        return $helper->generateForm($this->fields_form);
    }
    
    private function displayFormSellerProductInformation() 
    {
        if (version_compare(_PS_VERSION_, '1.6', '<')) 
            $type = 'radio';
        else 
            $type = 'switch';
        
        $languages = Language::getLanguages(false);
        foreach ($languages as $k => $language)
            $languages[$k]['is_default'] = (int)$language['id_lang'] == Configuration::get('PS_LANG_DEFAULT');

        $helper = new HelperForm();
        $helper->module = $this;
        $helper->name_controller = $this->name;
        $helper->identifier = $this->identifier;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->languages = $languages;
        $helper->currentIndex = AdminController::$currentIndex.'&configure='.$this->name;
        $helper->default_form_language = (int)Configuration::get('PS_LANG_DEFAULT');
        $helper->allow_employee_form_lang = true;
        $helper->toolbar_scroll = true;
        $helper->title = $this->displayName;
        $helper->submit_action = 'submitSellerProductTabInformation';

        $this->fields_form[0]['form'] = array(
            'tinymce' => false, 
            'legend' => array(
                'title' => $this->l('Tab information')
            ),
            'input' => array(
                array(
                    'type' => $type,
                    'label' => $this->l('Show reference'),
                    'name' => 'MARKETPLACE_SHOW_REFERENCE',
                    'required' => false,
                    'is_bool' => true,
                    'class' => 't',
                    'values' => array(
                        array(
                                'id' => 'active_on',
                                'value' => 1,
                                'label' => $this->l('Enabled')
                        ),
                        array(
                                'id' => 'active_off',
                                'value' => 0,
                                'label' => $this->l('Disabled')
                        )
                    ),
                ),
                array(
                    'type' => $type,
                    'label' => $this->l('Show EAN13'),
                    'name' => 'MARKETPLACE_SHOW_EAN13',
                    'required' => false,
                    'is_bool' => true,
                    'class' => 't',
                    'values' => array(
                        array(
                                'id' => 'active_on',
                                'value' => 1,
                                'label' => $this->l('Enabled')
                        ),
                        array(
                                'id' => 'active_off',
                                'value' => 0,
                                'label' => $this->l('Disabled')
                        )
                    ),
                ),
                array(
                    'type' => $type,
                    'label' => $this->l('Show UPC'),
                    'name' => 'MARKETPLACE_SHOW_UPC',
                    'required' => false,
                    'is_bool' => true,
                    'class' => 't',
                    'values' => array(
                        array(
                                'id' => 'active_on',
                                'value' => 1,
                                'label' => $this->l('Enabled')
                        ),
                        array(
                                'id' => 'active_off',
                                'value' => 0,
                                'label' => $this->l('Disabled')
                        )
                    ),
                ),
                array(
                    'type' => $type,
                    'label' => $this->l('Show condition'),
                    'name' => 'MARKETPLACE_SHOW_CONDITION',
                    'required' => false,
                    'is_bool' => true,
                    'class' => 't',
                    'values' => array(
                        array(
                                'id' => 'active_on',
                                'value' => 1,
                                'label' => $this->l('Enabled')
                        ),
                        array(
                                'id' => 'active_off',
                                'value' => 0,
                                'label' => $this->l('Disabled')
                        )
                    ),
                ),
                array(
                    'type' => $type,
                    'label' => $this->l('Show description short'),
                    'name' => 'MARKETPLACE_SHOW_DESC_SHORT',
                    'required' => false,
                    'is_bool' => true,
                    'class' => 't',
                    'values' => array(
                        array(
                                'id' => 'active_on',
                                'value' => 1,
                                'label' => $this->l('Enabled')
                        ),
                        array(
                                'id' => 'active_off',
                                'value' => 0,
                                'label' => $this->l('Disabled')
                        )
                    ),
                ),
                array(
                    'type' => $type,
                    'label' => $this->l('Show description'),
                    'name' => 'MARKETPLACE_SHOW_DESC',
                    'required' => false,
                    'is_bool' => true,
                    'class' => 't',
                    'values' => array(
                        array(
                                'id' => 'active_on',
                                'value' => 1,
                                'label' => $this->l('Enabled')
                        ),
                        array(
                                'id' => 'active_off',
                                'value' => 0,
                                'label' => $this->l('Disabled')
                        )
                    ),
                ),
            ),
            'submit' => array(
                'name' => 'submitSellerProductTabInformation',
                'title' => $this->l('Save'),
                'class' => 't btn btn-default pull-right',
            ),
        );

        $helper->fields_value['MARKETPLACE_SHOW_REFERENCE'] = Configuration::get('MARKETPLACE_SHOW_REFERENCE');
        $helper->fields_value['MARKETPLACE_SHOW_EAN13'] = Configuration::get('MARKETPLACE_SHOW_EAN13');
        $helper->fields_value['MARKETPLACE_SHOW_UPC'] = Configuration::get('MARKETPLACE_SHOW_UPC');        
        $helper->fields_value['MARKETPLACE_SHOW_CONDITION'] = Configuration::get('MARKETPLACE_SHOW_CONDITION');
        $helper->fields_value['MARKETPLACE_SHOW_DESC_SHORT'] = Configuration::get('MARKETPLACE_SHOW_DESC_SHORT');
        $helper->fields_value['MARKETPLACE_SHOW_DESC'] = Configuration::get('MARKETPLACE_SHOW_DESC');

        return $helper->generateForm($this->fields_form);
    }
    
    private function displayFormSellerProductPrices() 
    {
        if (version_compare(_PS_VERSION_, '1.6', '<')) 
            $type = 'radio';
        else 
            $type = 'switch';
        
        $languages = Language::getLanguages(false);
        foreach ($languages as $k => $language)
            $languages[$k]['is_default'] = (int)$language['id_lang'] == Configuration::get('PS_LANG_DEFAULT');

        $helper = new HelperForm();
        $helper->module = $this;
        $helper->name_controller = $this->name;
        $helper->identifier = $this->identifier;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->languages = $languages;
        $helper->currentIndex = AdminController::$currentIndex.'&configure='.$this->name;
        $helper->default_form_language = (int)Configuration::get('PS_LANG_DEFAULT');
        $helper->allow_employee_form_lang = true;
        $helper->toolbar_scroll = true;
        $helper->title = $this->displayName;
        $helper->submit_action = 'submitSellerProductTabPrices';

        $this->fields_form[0]['form'] = array(
            'tinymce' => false, 
            'legend' => array(
                'title' => $this->l('Tab prices')
            ),
            'input' => array(
                array(
                    'type' => $type,
                    'label' => $this->l('Show price'),
                    'name' => 'MARKETPLACE_SHOW_PRICE',
                    'required' => false,
                    'is_bool' => true,
                    'class' => 't',
                    'values' => array(
                        array(
                                'id' => 'active_on',
                                'value' => 1,
                                'label' => $this->l('Enabled')
                        ),
                        array(
                                'id' => 'active_off',
                                'value' => 0,
                                'label' => $this->l('Disabled')
                        )
                    ),
                ),
                array(
                    'type' => $type,
                    'label' => $this->l('Show offer price'),
                    'name' => 'MARKETPLACE_SHOW_OFFER_PRICE',
                    'required' => false,
                    'is_bool' => true,
                    'class' => 't',
                    'values' => array(
                        array(
                                'id' => 'active_on',
                                'value' => 1,
                                'label' => $this->l('Enabled')
                        ),
                        array(
                                'id' => 'active_off',
                                'value' => 0,
                                'label' => $this->l('Disabled')
                        )
                    ),
                ),
                array(
                    'type' => $type,
                    'label' => $this->l('Show tax'),
                    'name' => 'MARKETPLACE_SHOW_TAX',
                    'required' => false,
                    'is_bool' => true,
                    'class' => 't',
                    'values' => array(
                        array(
                                'id' => 'active_on',
                                'value' => 1,
                                'label' => $this->l('Enabled')
                        ),
                        array(
                                'id' => 'active_off',
                                'value' => 0,
                                'label' => $this->l('Disabled')
                        )
                    ),
                ),
            ),
            'submit' => array(
                'name' => 'submitSellerProductTabPrices',
                'title' => $this->l('Save'),
                'class' => 't btn btn-default pull-right',
            ),
        );

        $helper->fields_value['MARKETPLACE_SHOW_PRICE'] = Configuration::get('MARKETPLACE_SHOW_PRICE');
        $helper->fields_value['MARKETPLACE_SHOW_OFFER_PRICE'] = Configuration::get('MARKETPLACE_SHOW_OFFER_PRICE');
        $helper->fields_value['MARKETPLACE_SHOW_TAX'] = Configuration::get('MARKETPLACE_SHOW_TAX');

        return $helper->generateForm($this->fields_form);
    }
    
    private function displayFormSellerProductSEO() 
    {
        if (version_compare(_PS_VERSION_, '1.6', '<')) 
            $type = 'radio';
        else 
            $type = 'switch';
        
        $languages = Language::getLanguages(false);
        foreach ($languages as $k => $language)
            $languages[$k]['is_default'] = (int)$language['id_lang'] == Configuration::get('PS_LANG_DEFAULT');

        $helper = new HelperForm();
        $helper->module = $this;
        $helper->name_controller = $this->name;
        $helper->identifier = $this->identifier;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->languages = $languages;
        $helper->currentIndex = AdminController::$currentIndex.'&configure='.$this->name;
        $helper->default_form_language = (int)Configuration::get('PS_LANG_DEFAULT');
        $helper->allow_employee_form_lang = true;
        $helper->toolbar_scroll = true;
        $helper->title = $this->displayName;
        $helper->submit_action = 'submitSellerProductTabSeo';

        $this->fields_form[0]['form'] = array(
            'tinymce' => false, 
            'legend' => array(
                'title' => $this->l('Tab SEO')
            ),
            'input' => array(
                array(
                    'type' => $type,
                    'label' => $this->l('Show meta keywords'),
                    'name' => 'MARKETPLACE_SHOW_META_KEYWORDS',
                    'required' => false,
                    'is_bool' => true,
                    'class' => 't',
                    'values' => array(
                        array(
                                'id' => 'active_on',
                                'value' => 1,
                                'label' => $this->l('Enabled')
                        ),
                        array(
                                'id' => 'active_off',
                                'value' => 0,
                                'label' => $this->l('Disabled')
                        )
                    ),
                ),
                array(
                    'type' => $type,
                    'label' => $this->l('Show meta title'),
                    'name' => 'MARKETPLACE_SHOW_META_TITLE',
                    'required' => false,
                    'is_bool' => true,
                    'class' => 't',
                    'values' => array(
                        array(
                                'id' => 'active_on',
                                'value' => 1,
                                'label' => $this->l('Enabled')
                        ),
                        array(
                                'id' => 'active_off',
                                'value' => 0,
                                'label' => $this->l('Disabled')
                        )
                    ),
                ),
                array(
                    'type' => $type,
                    'label' => $this->l('Show meta description'),
                    'name' => 'MARKETPLACE_SHOW_META_DESC',
                    'required' => false,
                    'is_bool' => true,
                    'class' => 't',
                    'values' => array(
                        array(
                                'id' => 'active_on',
                                'value' => 1,
                                'label' => $this->l('Enabled')
                        ),
                        array(
                                'id' => 'active_off',
                                'value' => 0,
                                'label' => $this->l('Disabled')
                        )
                    ),
                ),
                array(
                    'type' => $type,
                    'label' => $this->l('Friendly URL'),
                    'name' => 'MARKETPLACE_SHOW_LINK_REWRITE',
                    'required' => false,
                    'is_bool' => true,
                    'class' => 't',
                    'values' => array(
                        array(
                                'id' => 'active_on',
                                'value' => 1,
                                'label' => $this->l('Enabled')
                        ),
                        array(
                                'id' => 'active_off',
                                'value' => 0,
                                'label' => $this->l('Disabled')
                        )
                    ),
                ),
            ),
            'submit' => array(
                'name' => 'submitSellerProductTabSeo',
                'title' => $this->l('Save'),
                'class' => 't btn btn-default pull-right',
            ),
        );

        $helper->fields_value['MARKETPLACE_SHOW_META_KEYWORDS'] = Configuration::get('MARKETPLACE_SHOW_META_KEYWORDS');
        $helper->fields_value['MARKETPLACE_SHOW_META_TITLE'] = Configuration::get('MARKETPLACE_SHOW_META_TITLE');
        $helper->fields_value['MARKETPLACE_SHOW_META_DESC'] = Configuration::get('MARKETPLACE_SHOW_META_DESC');
        $helper->fields_value['MARKETPLACE_SHOW_LINK_REWRITE'] = Configuration::get('MARKETPLACE_SHOW_LINK_REWRITE');

        return $helper->generateForm($this->fields_form);
    }
    
    private function displayFormSellerProductAssociations() 
    {
        if (version_compare(_PS_VERSION_, '1.6', '<')) {
            $type = 'radio';
            $type_selector_categories = 'select';
        }
        else {
            $type = 'switch';
            $type_selector_categories = 'categories';
        }
        
        $selected_categories = SellerCategory::getSelectedCategories($this->context->shop->id);
        $finalCategories = array();
            
        foreach ($selected_categories as $category)
            $finalCategories[] = $category['id_category'];
        
        $languages = Language::getLanguages(false);
        foreach ($languages as $k => $language)
            $languages[$k]['is_default'] = (int)$language['id_lang'] == Configuration::get('PS_LANG_DEFAULT');

        $helper = new HelperForm();
        $helper->module = $this;
        $helper->name_controller = $this->name;
        $helper->identifier = $this->identifier;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->languages = $languages;
        $helper->currentIndex = AdminController::$currentIndex.'&configure='.$this->name;
        $helper->default_form_language = (int)Configuration::get('PS_LANG_DEFAULT');
        $helper->allow_employee_form_lang = true;
        $helper->toolbar_scroll = true;
        $helper->title = $this->displayName;
        $helper->submit_action = 'submitSellerProductTabAssociations';

        $this->fields_form[0]['form'] = array(
            'tinymce' => false, 
            'legend' => array(
                'title' => $this->l('Tab Associations')
            ),
            'input' => array(
                array(
                    'type' => $type,
                    'label' => $this->l('Show select categories'),
                    'name' => 'MARKETPLACE_SHOW_CATEGORIES',
                    'required' => false,
                    'is_bool' => true,
                    'class' => 't',
                    'values' => array(
                        array(
                                'id' => 'active_on',
                                'value' => 1,
                                'label' => $this->l('Enabled')
                        ),
                        array(
                                'id' => 'active_off',
                                'value' => 0,
                                'label' => $this->l('Disabled')
                        )
                    ),
                ),
                 array(
                    'type' => $type_selector_categories,
                    'label' => $this->l('Selected categories to sellers'), 
                    'name' => 'categories', 
                    'tree' => array(
                        'id' => 'categories', 
                        'title' => $this->l('Choose categories where sellers can add their products.'),
                        'use_search'    => false,
                        'use_checkbox'  => true,
                        'selected_categories' => $finalCategories
                    )
                ), 
                array(
                    'type' => $type,
                    'label' => $this->l('Show select suppliers'),
                    'name' => 'MARKETPLACE_SHOW_SUPPLIERS',
                    'required' => false,
                    'is_bool' => true,
                    'class' => 't',
                    'values' => array(
                        array(
                                'id' => 'active_on',
                                'value' => 1,
                                'label' => $this->l('Enabled')
                        ),
                        array(
                                'id' => 'active_off',
                                'value' => 0,
                                'label' => $this->l('Disabled')
                        )
                    ),
                ),
                array(
                    'type' => $type,
                    'label' => $this->l('Add new suppliers'),
                    'name' => 'MARKETPLACE_NEW_SUPPLIERS',
                    'required' => false,
                    'is_bool' => true,
                    'class' => 't',
                    'values' => array(
                        array(
                                'id' => 'active_on',
                                'value' => 1,
                                'label' => $this->l('Enabled')
                        ),
                        array(
                                'id' => 'active_off',
                                'value' => 0,
                                'label' => $this->l('Disabled')
                        )
                    ),
                ),
                array(
                    'type' => $type,
                    'label' => $this->l('Show select manufacturers'),
                    'name' => 'MARKETPLACE_SHOW_MANUFACTURERS',
                    'required' => false,
                    'is_bool' => true,
                    'class' => 't',
                    'values' => array(
                        array(
                                'id' => 'active_on',
                                'value' => 1,
                                'label' => $this->l('Enabled')
                        ),
                        array(
                                'id' => 'active_off',
                                'value' => 0,
                                'label' => $this->l('Disabled')
                        )
                    ),
                ),
                array(
                    'type' => $type,
                    'label' => $this->l('Add new manufacturers'),
                    'name' => 'MARKETPLACE_NEW_MANUFACTURERS',
                    'required' => false,
                    'is_bool' => true,
                    'class' => 't',
                    'values' => array(
                        array(
                                'id' => 'active_on',
                                'value' => 1,
                                'label' => $this->l('Enabled')
                        ),
                        array(
                                'id' => 'active_off',
                                'value' => 0,
                                'label' => $this->l('Disabled')
                        )
                    ),
                ),
            ),
            'submit' => array(
                'name' => 'submitSellerProductTabAssociations',
                'title' => $this->l('Save'),
                'class' => 't btn btn-default pull-right',
            ),
        );

        $helper->fields_value['MARKETPLACE_SHOW_CATEGORIES'] = Configuration::get('MARKETPLACE_SHOW_CATEGORIES');
        $helper->fields_value['MARKETPLACE_SHOW_SUPPLIERS'] = Configuration::get('MARKETPLACE_SHOW_SUPPLIERS');
        $helper->fields_value['MARKETPLACE_NEW_SUPPLIERS'] = Configuration::get('MARKETPLACE_NEW_SUPPLIERS');
        $helper->fields_value['MARKETPLACE_SHOW_MANUFACTURERS'] = Configuration::get('MARKETPLACE_SHOW_MANUFACTURERS');
        $helper->fields_value['MARKETPLACE_NEW_MANUFACTURERS'] = Configuration::get('MARKETPLACE_NEW_MANUFACTURERS');

        return $helper->generateForm($this->fields_form);
    }
    
    private function displayFormSellerProductShipping() 
    {
        if (version_compare(_PS_VERSION_, '1.6', '<')) 
            $type = 'radio';
        else 
            $type = 'switch';
        
        $languages = Language::getLanguages(false);
        foreach ($languages as $k => $language)
            $languages[$k]['is_default'] = (int)$language['id_lang'] == Configuration::get('PS_LANG_DEFAULT');

        $helper = new HelperForm();
        $helper->module = $this;
        $helper->name_controller = $this->name;
        $helper->identifier = $this->identifier;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->languages = $languages;
        $helper->currentIndex = AdminController::$currentIndex.'&configure='.$this->name;
        $helper->default_form_language = (int)Configuration::get('PS_LANG_DEFAULT');
        $helper->allow_employee_form_lang = true;
        $helper->toolbar_scroll = true;
        $helper->title = $this->displayName;
        $helper->submit_action = 'submitSellerProductTabShipping';

        $this->fields_form[0]['form'] = array(
            'tinymce' => false, 
            'legend' => array(
                'title' => $this->l('Tab Shipping')
            ),
            'input' => array(
                array(
                    'type' => $type,
                    'label' => $this->l('Show width'),
                    'name' => 'MARKETPLACE_SHOW_WIDTH',
                    'required' => false,
                    'is_bool' => true,
                    'class' => 't',
                    'values' => array(
                        array(
                                'id' => 'active_on',
                                'value' => 1,
                                'label' => $this->l('Enabled')
                        ),
                        array(
                                'id' => 'active_off',
                                'value' => 0,
                                'label' => $this->l('Disabled')
                        )
                    ),
                ),
                array(
                    'type' => $type,
                    'label' => $this->l('Show depth'),
                    'name' => 'MARKETPLACE_SHOW_DEPTH',
                    'required' => false,
                    'is_bool' => true,
                    'class' => 't',
                    'values' => array(
                        array(
                                'id' => 'active_on',
                                'value' => 1,
                                'label' => $this->l('Enabled')
                        ),
                        array(
                                'id' => 'active_off',
                                'value' => 0,
                                'label' => $this->l('Disabled')
                        )
                    ),
                ),
                array(
                    'type' => $type,
                    'label' => $this->l('Show height'),
                    'name' => 'MARKETPLACE_SHOW_HEIGHT',
                    'required' => false,
                    'is_bool' => true,
                    'class' => 't',
                    'values' => array(
                        array(
                                'id' => 'active_on',
                                'value' => 1,
                                'label' => $this->l('Enabled')
                        ),
                        array(
                                'id' => 'active_off',
                                'value' => 0,
                                'label' => $this->l('Disabled')
                        )
                    ),
                ),
                array(
                    'type' => $type,
                    'label' => $this->l('Show weight'),
                    'name' => 'MARKETPLACE_SHOW_WEIGHT',
                    'required' => false,
                    'is_bool' => true,
                    'class' => 't',
                    'values' => array(
                        array(
                                'id' => 'active_on',
                                'value' => 1,
                                'label' => $this->l('Enabled')
                        ),
                        array(
                                'id' => 'active_off',
                                'value' => 0,
                                'label' => $this->l('Disabled')
                        )
                    ),
                ),
                array(
                    'type' => $type,
                    'label' => $this->l('Show shipping by product'),
                    'name' => 'MARKETPLACE_SHOW_SHIP_PRODUCT',
                    'required' => false,
                    'is_bool' => true,
                    'class' => 't',
                    'values' => array(
                        array(
                                'id' => 'active_on',
                                'value' => 1,
                                'label' => $this->l('Enabled')
                        ),
                        array(
                                'id' => 'active_off',
                                'value' => 0,
                                'label' => $this->l('Disabled')
                        )
                    ),
                ),
            ),
            'submit' => array(
                'name' => 'submitSellerProductTabShipping',
                'title' => $this->l('Save'),
                'class' => 't btn btn-default pull-right',
            ),
        );

        $helper->fields_value['MARKETPLACE_SHOW_WIDTH'] = Configuration::get('MARKETPLACE_SHOW_WIDTH');
        $helper->fields_value['MARKETPLACE_SHOW_HEIGHT'] = Configuration::get('MARKETPLACE_SHOW_HEIGHT');
        $helper->fields_value['MARKETPLACE_SHOW_DEPTH'] = Configuration::get('MARKETPLACE_SHOW_DEPTH');
        $helper->fields_value['MARKETPLACE_SHOW_WEIGHT'] = Configuration::get('MARKETPLACE_SHOW_WEIGHT');
        $helper->fields_value['MARKETPLACE_SHOW_SHIP_PRODUCT'] = Configuration::get('MARKETPLACE_SHOW_SHIP_PRODUCT');

        return $helper->generateForm($this->fields_form);
    }

    private function displayFormSellerProductCombinations() 
    {
        if (version_compare(_PS_VERSION_, '1.6', '<')) 
            $type = 'radio';
        else 
            $type = 'switch';
        
        $languages = Language::getLanguages(false);
        foreach ($languages as $k => $language)
            $languages[$k]['is_default'] = (int)$language['id_lang'] == Configuration::get('PS_LANG_DEFAULT');

        $helper = new HelperForm();
        $helper->module = $this;
        $helper->name_controller = $this->name;
        $helper->identifier = $this->identifier;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->languages = $languages;
        $helper->currentIndex = AdminController::$currentIndex.'&configure='.$this->name;
        $helper->default_form_language = (int)Configuration::get('PS_LANG_DEFAULT');
        $helper->allow_employee_form_lang = true;
        $helper->toolbar_scroll = true;
        $helper->title = $this->displayName;
        $helper->submit_action = 'submitSellerProductTabCombinations';

        $this->fields_form[0]['form'] = array(
            'tinymce' => false, 
            'legend' => array(
                'title' => $this->l('Tab Combinations')
            ),
            'input' => array(
                array(
                    'type' => $type,
                    'label' => $this->l('Show attributes'),
                    'name' => 'MARKETPLACE_SHOW_ATTRIBUTES',
                    'required' => false,
                    'is_bool' => true,
                    'class' => 't',
                    'values' => array(
                        array(
                                'id' => 'active_on',
                                'value' => 1,
                                'label' => $this->l('Enabled')
                        ),
                        array(
                                'id' => 'active_off',
                                'value' => 0,
                                'label' => $this->l('Disabled')
                        )
                    ),
                ),
            ),
            'submit' => array(
                'name' => 'submitSellerProductTabFeatures',
                'title' => $this->l('Save'),
                'class' => 't btn btn-default pull-right',
            ),
        );

        $helper->fields_value['MARKETPLACE_SHOW_ATTRIBUTES'] = Configuration::get('MARKETPLACE_SHOW_ATTRIBUTES');

        return $helper->generateForm($this->fields_form);
    }
    
    private function displayFormSellerProductQuantities() 
    {
        if (version_compare(_PS_VERSION_, '1.6', '<')) 
            $type = 'radio';
        else 
            $type = 'switch';
        
        $languages = Language::getLanguages(false);
        foreach ($languages as $k => $language)
            $languages[$k]['is_default'] = (int)$language['id_lang'] == Configuration::get('PS_LANG_DEFAULT');

        $helper = new HelperForm();
        $helper->module = $this;
        $helper->name_controller = $this->name;
        $helper->identifier = $this->identifier;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->languages = $languages;
        $helper->currentIndex = AdminController::$currentIndex.'&configure='.$this->name;
        $helper->default_form_language = (int)Configuration::get('PS_LANG_DEFAULT');
        $helper->allow_employee_form_lang = true;
        $helper->toolbar_scroll = true;
        $helper->title = $this->displayName;
        $helper->submit_action = 'submitSellerProductTabQuantities';

        $this->fields_form[0]['form'] = array(
            'tinymce' => false, 
            'legend' => array(
                'title' => $this->l('Tab Quantities')
            ),
            'input' => array(
                array(
                    'type' => $type,
                    'label' => $this->l('Show quantity'),
                    'name' => 'MARKETPLACE_SHOW_QUANTITY',
                    'required' => false,
                    'is_bool' => true,
                    'class' => 't',
                    'values' => array(
                        array(
                                'id' => 'active_on',
                                'value' => 1,
                                'label' => $this->l('Enabled')
                        ),
                        array(
                                'id' => 'active_off',
                                'value' => 0,
                                'label' => $this->l('Disabled')
                        )
                    ),
                ),
                array(
                    'type' => $type,
                    'label' => $this->l('Show minimal quantity'),
                    'name' => 'MARKETPLACE_SHOW_MINIMAL_QTY',
                    'required' => false,
                    'is_bool' => true,
                    'class' => 't',
                    'values' => array(
                        array(
                                'id' => 'active_on',
                                'value' => 1,
                                'label' => $this->l('Enabled')
                        ),
                        array(
                                'id' => 'active_off',
                                'value' => 0,
                                'label' => $this->l('Disabled')
                        )
                    ),
                ),
                array(
                    'type' => $type,
                    'label' => $this->l('Show available now'),
                    'name' => 'MARKETPLACE_SHOW_AVAILABLE_NOW',
                    'required' => false,
                    'is_bool' => true,
                    'class' => 't',
                    'values' => array(
                        array(
                                'id' => 'active_on',
                                'value' => 1,
                                'label' => $this->l('Enabled')
                        ),
                        array(
                                'id' => 'active_off',
                                'value' => 0,
                                'label' => $this->l('Disabled')
                        )
                    ),
                ),
                array(
                    'type' => $type,
                    'label' => $this->l('Show available later'),
                    'name' => 'MARKETPLACE_SHOW_AVAILABLE_LAT',
                    'required' => false,
                    'is_bool' => true,
                    'class' => 't',
                    'values' => array(
                        array(
                                'id' => 'active_on',
                                'value' => 1,
                                'label' => $this->l('Enabled')
                        ),
                        array(
                                'id' => 'active_off',
                                'value' => 0,
                                'label' => $this->l('Disabled')
                        )
                    ),
                ),
                array(
                    'type' => $type,
                    'label' => $this->l('Show available date'),
                    'name' => 'MARKETPLACE_SHOW_AVAILABLE_DATE',
                    'required' => false,
                    'is_bool' => true,
                    'class' => 't',
                    'values' => array(
                        array(
                                'id' => 'active_on',
                                'value' => 1,
                                'label' => $this->l('Enabled')
                        ),
                        array(
                                'id' => 'active_off',
                                'value' => 0,
                                'label' => $this->l('Disabled')
                        )
                    ),
                ),      
            ),
            'submit' => array(
                'name' => 'submitSellerProductTabQuantities',
                'title' => $this->l('Save'),
                'class' => 't btn btn-default pull-right',
            ),
        );

        $helper->fields_value['MARKETPLACE_SHOW_QUANTITY'] = Configuration::get('MARKETPLACE_SHOW_QUANTITY');
        $helper->fields_value['MARKETPLACE_SHOW_MINIMAL_QTY'] = Configuration::get('MARKETPLACE_SHOW_MINIMAL_QTY');
        $helper->fields_value['MARKETPLACE_SHOW_AVAILABLE_NOW'] = Configuration::get('MARKETPLACE_SHOW_AVAILABLE_NOW');
        $helper->fields_value['MARKETPLACE_SHOW_AVAILABLE_LAT'] = Configuration::get('MARKETPLACE_SHOW_AVAILABLE_LAT');
        $helper->fields_value['MARKETPLACE_SHOW_AVAILABLE_DATE'] = Configuration::get('MARKETPLACE_SHOW_AVAILABLE_DATE'); 

        return $helper->generateForm($this->fields_form);
    }
    
    private function displayFormSellerProductImages() 
    {
        if (version_compare(_PS_VERSION_, '1.6', '<')) 
            $type = 'radio';
        else 
            $type = 'switch';
        
        $languages = Language::getLanguages(false);
        foreach ($languages as $k => $language)
            $languages[$k]['is_default'] = (int)$language['id_lang'] == Configuration::get('PS_LANG_DEFAULT');

        $helper = new HelperForm();
        $helper->module = $this;
        $helper->name_controller = $this->name;
        $helper->identifier = $this->identifier;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->languages = $languages;
        $helper->currentIndex = AdminController::$currentIndex.'&configure='.$this->name;
        $helper->default_form_language = (int)Configuration::get('PS_LANG_DEFAULT');
        $helper->allow_employee_form_lang = true;
        $helper->toolbar_scroll = true;
        $helper->title = $this->displayName;
        $helper->submit_action = 'submitSellerProductTabImages';

        $this->fields_form[0]['form'] = array(
            'tinymce' => false, 
            'legend' => array(
                'title' => $this->l('Tab Images')
            ),
            'input' => array(
                array(
                    'type' => $type,
                    'label' => $this->l('Show images'),
                    'name' => 'MARKETPLACE_SHOW_IMAGES',
                    'required' => false,
                    'is_bool' => true,
                    'class' => 't',
                    'values' => array(
                        array(
                                'id' => 'active_on',
                                'value' => 1,
                                'label' => $this->l('Enabled')
                        ),
                        array(
                                'id' => 'active_off',
                                'value' => 0,
                                'label' => $this->l('Disabled')
                        )
                    ),
                ),
                array(
                    'type' => 'text',
                    'label' => $this->l('Maximum number of images to upload'),
                    'name' => 'MARKETPLACE_MAX_IMAGES',
                    'required' => false,
                    'lang' => false,
                    'col' => 2,
                ),
            ),
            'submit' => array(
                'name' => 'submitSellerProductTabImages',
                'title' => $this->l('Save'),
                'class' => 't btn btn-default pull-right',
            ),
        );

        $helper->fields_value['MARKETPLACE_SHOW_IMAGES'] = Configuration::get('MARKETPLACE_SHOW_IMAGES');
        $helper->fields_value['MARKETPLACE_MAX_IMAGES'] = Configuration::get('MARKETPLACE_MAX_IMAGES');

        return $helper->generateForm($this->fields_form);
    }
    
    private function displayFormSellerProductFeatures() 
    {
        if (version_compare(_PS_VERSION_, '1.6', '<')) 
            $type = 'radio';
        else 
            $type = 'switch';
        
        $languages = Language::getLanguages(false);
        foreach ($languages as $k => $language)
            $languages[$k]['is_default'] = (int)$language['id_lang'] == Configuration::get('PS_LANG_DEFAULT');

        $helper = new HelperForm();
        $helper->module = $this;
        $helper->name_controller = $this->name;
        $helper->identifier = $this->identifier;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->languages = $languages;
        $helper->currentIndex = AdminController::$currentIndex.'&configure='.$this->name;
        $helper->default_form_language = (int)Configuration::get('PS_LANG_DEFAULT');
        $helper->allow_employee_form_lang = true;
        $helper->toolbar_scroll = true;
        $helper->title = $this->displayName;
        $helper->submit_action = 'submitSellerProductTabFeatures';

        $this->fields_form[0]['form'] = array(
            'tinymce' => false, 
            'legend' => array(
                'title' => $this->l('Tab Features')
            ),
            'input' => array(
                array(
                    'type' => $type,
                    'label' => $this->l('Show features'),
                    'name' => 'MARKETPLACE_SHOW_FEATURES',
                    'required' => false,
                    'is_bool' => true,
                    'class' => 't',
                    'values' => array(
                        array(
                                'id' => 'active_on',
                                'value' => 1,
                                'label' => $this->l('Enabled')
                        ),
                        array(
                                'id' => 'active_off',
                                'value' => 0,
                                'label' => $this->l('Disabled')
                        )
                    ),
                ),
            ),
            'submit' => array(
                'name' => 'submitSellerProductTabFeatures',
                'title' => $this->l('Save'),
                'class' => 't btn btn-default pull-right',
            ),
        );

        $helper->fields_value['MARKETPLACE_SHOW_FEATURES'] = Configuration::get('MARKETPLACE_SHOW_FEATURES');

        return $helper->generateForm($this->fields_form);
    }
    
    private function displayFormSellerProductVirtual() 
    {
        if (version_compare(_PS_VERSION_, '1.6', '<')) 
            $type = 'radio';
        else 
            $type = 'switch';
        
        $languages = Language::getLanguages(false);
        foreach ($languages as $k => $language)
            $languages[$k]['is_default'] = (int)$language['id_lang'] == Configuration::get('PS_LANG_DEFAULT');

        $helper = new HelperForm();
        $helper->module = $this;
        $helper->name_controller = $this->name;
        $helper->identifier = $this->identifier;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->languages = $languages;
        $helper->currentIndex = AdminController::$currentIndex.'&configure='.$this->name;
        $helper->default_form_language = (int)Configuration::get('PS_LANG_DEFAULT');
        $helper->allow_employee_form_lang = true;
        $helper->toolbar_scroll = true;
        $helper->title = $this->displayName;
        $helper->submit_action = 'submitSellerProductTabVirtual';

        $this->fields_form[0]['form'] = array(
            'tinymce' => false, 
            'legend' => array(
                'title' => $this->l('Tab Virtual product')
            ),
            'input' => array(
                array(
                    'type' => $type,
                    'label' => $this->l('Show virtual product'),
                    'name' => 'MARKETPLACE_SHOW_VIRTUAL',
                    'required' => false,
                    'is_bool' => true,
                    'class' => 't',
                    'values' => array(
                        array(
                                'id' => 'active_on',
                                'value' => 1,
                                'label' => $this->l('Enabled')
                        ),
                        array(
                                'id' => 'active_off',
                                'value' => 0,
                                'label' => $this->l('Disabled')
                        )
                    ),
                ),
            ),
            'submit' => array(
                'name' => 'submitSellerProductTabVirtual',
                'title' => $this->l('Save'),
                'class' => 't btn btn-default pull-right',
            ),
        );

        $helper->fields_value['MARKETPLACE_SHOW_VIRTUAL'] = Configuration::get('MARKETPLACE_SHOW_VIRTUAL');

        return $helper->generateForm($this->fields_form);
    }
    
    private function displayFormSellerProductSettings() 
    {
        return $this->displayFormSellerProductInformation().
                $this->displayFormSellerProductPrices().
                $this->displayFormSellerProductSEO().
                $this->displayFormSellerProductAssociations().
                $this->displayFormSellerProductShipping().
                $this->displayFormSellerProductCombinations().
                $this->displayFormSellerProductQuantities().
                $this->displayFormSellerProductImages().
                $this->displayFormSellerProductFeatures().
                $this->displayFormSellerProductVirtual();
    }
    
    
    private function displayFormBalanceSettings() 
    {
        if (version_compare(_PS_VERSION_, '1.6', '<'))
            $type = 'radio';
        else
            $type = 'switch';
        
        $languages = Language::getLanguages(false);
        foreach ($languages as $k => $language)
            $languages[$k]['is_default'] = (int)$language['id_lang'] == Configuration::get('PS_LANG_DEFAULT');

        $helper = new HelperForm();
        $helper->module = $this;
        $helper->name_controller = $this->name;
        $helper->identifier = $this->identifier;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->languages = $languages;
        $helper->currentIndex = AdminController::$currentIndex.'&configure='.$this->name;
        $helper->default_form_language = (int)Configuration::get('PS_LANG_DEFAULT');
        $helper->allow_employee_form_lang = true;
        $helper->toolbar_scroll = true;
        $helper->title = $this->displayName;
        $helper->submit_action = 'submitBalanceSettings';

        /*$this->fields_form[0]['form'] = array(
            'tinymce' => false, 
            'legend' => array(
                'title' => $this->l('Période des Soldes')
            ),
            'input' => array(
                array(
                    'type' => 'date',
                    'label' => $this->l('Date du'),
                    'name' => 'MARKETPLACE_BALANCE_DMIN_SELLER',
                    'desc' => $this->l('Début de la période'),
                    'required' => true,
                    'lang' => false,
                    'col' => 6,
                ),
                array(
                    'type' => 'date',
                    'label' => $this->l('au'),
                    'name' => 'MARKETPLACE_BALANCE_DMAX_SELLER',
                    'desc' => $this->l('Fin de la période'),
                    'required' => true,
                    'lang' => false,
                    'col' => 6,
                ),
                
            ),
            'submit' => array(
                'name' => 'submitBalanceSettings',
                'title' => $this->l('Save'),
            ),
        );*/

        $this->fields_form[0]['form'] = array(
            'tinymce' => false,
            'legend' => array(
                'title' => $this->l('Rules balances for sellers')
            ),
            'input' => array(                
                array(
                    'type' => $type,
                    'label' => $this->l('Activate rules balances for sellers'),
                    'name' => 'MARKETPLACE_ACTIVE_SELLER_BALANCE',                    
                    'required' => false,
                    'is_bool' => true,
                    'class' => 't',
                    'values' => array(
                        array(
                                'id' => 'active_on',
                                'value' => 1,
                                'label' => $this->l('Enabled')
                        ),
                        array(
                                'id' => 'active_off',
                                'value' => 0,
                                'label' => $this->l('Disabled')
                        )
                    ),
                ),                
            ),
            'submit' => array(
                'name' => 'submitBalanceSettings',
                'title' => $this->l('Save'),
                'class' => 't btn btn-default pull-right',
            ),
        );
        
        $helper->fields_value['MARKETPLACE_ACTIVE_SELLER_BALANCE'] = Configuration::get('MARKETPLACE_ACTIVE_SELLER_BALANCE');
        


        return $helper->generateForm($this->fields_form);
    }

    private function displayFormEmailSettings() 
    {
        if (version_compare(_PS_VERSION_, '1.6', '<'))
            $type = 'radio';
        else
            $type = 'switch';
        
        $languages = Language::getLanguages(false);
        foreach ($languages as $k => $language)
            $languages[$k]['is_default'] = (int)$language['id_lang'] == Configuration::get('PS_LANG_DEFAULT');

        $helper = new HelperForm();
        $helper->module = $this;
        $helper->name_controller = $this->name;
        $helper->identifier = $this->identifier;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->languages = $languages;
        $helper->currentIndex = AdminController::$currentIndex.'&configure='.$this->name;
        $helper->default_form_language = (int)Configuration::get('PS_LANG_DEFAULT');
        $helper->allow_employee_form_lang = true;
        $helper->toolbar_scroll = true;
        $helper->title = $this->displayName;
        $helper->submit_action = 'submitEmailSettings';

        $this->fields_form[0]['form'] = array(
            'tinymce' => false, 
            'legend' => array(
                'title' => $this->l('Emails')
            ),
            'input' => array(
                array(
                    'type' => 'text',
                    'label' => $this->l('Email'),
                    'name' => 'MARKETPLACE_SEND_ADMIN',
                    'desc' => $this->l('This email receives all notifications from the marketplace.'),
                    'required' => false,
                    'lang' => false,
                    'col' => 6,
                ),
                array(
                    'type' => $type,
                    'label' => $this->l('Send email to administrator when seller register'),
                    'name' => 'MARKETPLACE_SEND_ADMIN_REGISTER',
                    'required' => false,
                    'is_bool' => true,
                    'class' => 't',
                    'values' => array(
                        array(
                                'id' => 'active_on',
                                'value' => 1,
                                'label' => $this->l('Enabled')
                        ),
                        array(
                                'id' => 'active_off',
                                'value' => 0,
                                'label' => $this->l('Disabled')
                        )
                    ),
                ),
                array(
                    'type' => $type,
                    'label' => $this->l('Send email to admininstrator when selle add new product'),
                    'name' => 'MARKETPLACE_SEND_ADMIN_PRODUCT',
                    'required' => false,
                    'is_bool' => true,
                    'class' => 't',
                    'values' => array(
                        array(
                                'id' => 'active_on',
                                'value' => 1,
                                'label' => $this->l('Enabled')
                        ),
                        array(
                                'id' => 'active_off',
                                'value' => 0,
                                'label' => $this->l('Disabled')
                        )
                    ),
                ),
                array(
                    'type' => $type,
                    'label' => $this->l('Send welcome email to seller'),
                    'name' => 'MARKETPLACE_SEND_SELLER_WELCOME',
                    'required' => false,
                    'is_bool' => true,
                    'class' => 't',
                    'values' => array(
                        array(
                                'id' => 'active_on',
                                'value' => 1,
                                'label' => $this->l('Enabled')
                        ),
                        array(
                                'id' => 'active_off',
                                'value' => 0,
                                'label' => $this->l('Disabled')
                        )
                    ),
                ),
                array(
                    'type' => $type,
                    'label' => $this->l('Send email to seller when your account has been activated'),
                    'name' => 'MARKETPLACE_SEND_SELLER_ACTIVE',
                    'required' => false,
                    'is_bool' => true,
                    'class' => 't',
                    'values' => array(
                        array(
                                'id' => 'active_on',
                                'value' => 1,
                                'label' => $this->l('Enabled')
                        ),
                        array(
                                'id' => 'active_off',
                                'value' => 0,
                                'label' => $this->l('Disabled')
                        )
                    ),
                ),
                array(
                    'type' => $type,
                    'label' => $this->l('Send email to seller when your product has been activated'),
                    'name' => 'MARKETPLACE_SEND_PRODUCT_ACTIVE',
                    'required' => false,
                    'is_bool' => true,
                    'class' => 't',
                    'values' => array(
                        array(
                                'id' => 'active_on',
                                'value' => 1,
                                'label' => $this->l('Enabled')
                        ),
                        array(
                                'id' => 'active_off',
                                'value' => 0,
                                'label' => $this->l('Disabled')
                        )
                    ),
                ),
                array(
                    'type' => $type,
                    'label' => $this->l('Send email to seller when your product has been solded'),
                    'name' => 'MARKETPLACE_SEND_PRODUCT_SOLD',
                    'required' => false,
                    'is_bool' => true,
                    'class' => 't',
                    'values' => array(
                        array(
                                'id' => 'active_on',
                                'value' => 1,
                                'label' => $this->l('Enabled')
                        ),
                        array(
                                'id' => 'active_off',
                                'value' => 0,
                                'label' => $this->l('Disabled')
                        )
                    ),
                ),
                /**
                 * Anthony.rho
                **/
                array(
                    'type' => $type,
                    'label' => $this->l('Send email to seller when message is validated'),
                    'name' => 'MARKETPLACE_SEND_MESSAGE_ACTIVE',
                    'required' => false,
                    'is_bool' => true,
                    'class' => 't',
                    'values' => array(
                        array(
                                'id' => 'active_on',
                                'value' => 1,
                                'label' => $this->l('Enabled')
                        ),
                        array(
                                'id' => 'active_off',
                                'value' => 0,
                                'label' => $this->l('Disabled')
                        )
                    ),
                ),
                
            ),
            'submit' => array(
                'name' => 'submitEmailSettings',
                'title' => $this->l('Save'),
                'class' => 't btn btn-default pull-right',
            ),
        );
        
        $helper->fields_value['MARKETPLACE_SEND_ADMIN'] = Configuration::get('MARKETPLACE_SEND_ADMIN');
        $helper->fields_value['MARKETPLACE_SEND_ADMIN_REGISTER'] = Configuration::get('MARKETPLACE_SEND_ADMIN_REGISTER');
        $helper->fields_value['MARKETPLACE_SEND_ADMIN_PRODUCT'] = Configuration::get('MARKETPLACE_SEND_ADMIN_PRODUCT');
        $helper->fields_value['MARKETPLACE_SEND_SELLER_WELCOME'] = Configuration::get('MARKETPLACE_SEND_SELLER_WELCOME');
        $helper->fields_value['MARKETPLACE_SEND_SELLER_ACTIVE'] = Configuration::get('MARKETPLACE_SEND_SELLER_ACTIVE');
        $helper->fields_value['MARKETPLACE_SEND_PRODUCT_ACTIVE'] = Configuration::get('MARKETPLACE_SEND_PRODUCT_ACTIVE');
        $helper->fields_value['MARKETPLACE_SEND_PRODUCT_SOLD'] = Configuration::get('MARKETPLACE_SEND_PRODUCT_SOLD');
        $helper->fields_value['MARKETPLACE_SEND_MESSAGE_ACTIVE'] = Configuration::get('MARKETPLACE_SEND_MESSAGE_ACTIVE');

        return $helper->generateForm($this->fields_form);
    }
    
    private function displayFormThemeSettings() 
    {
        if (version_compare(_PS_VERSION_, '1.6', '<'))
            $type = 'radio';
        else
            $type = 'switch';
        
        $languages = Language::getLanguages(false);
        foreach ($languages as $k => $language)
            $languages[$k]['is_default'] = (int)$language['id_lang'] == Configuration::get('PS_LANG_DEFAULT');

        $helper = new HelperForm();
        $helper->module = $this;
        $helper->name_controller = $this->name;
        $helper->identifier = $this->identifier;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->languages = $languages;
        $helper->currentIndex = AdminController::$currentIndex.'&configure='.$this->name;
        $helper->default_form_language = (int)Configuration::get('PS_LANG_DEFAULT');
        $helper->allow_employee_form_lang = true;
        $helper->toolbar_scroll = true;
        $helper->title = $this->displayName;
        $helper->submit_action = 'submitThemeSettings';

        $this->fields_form[0]['form'] = array(
            'tinymce' => false,
            'legend' => array(
                'title' => $this->l('Front office theme')
            ),
            'input' => array(  
                array(
                    'type' => 'radio',
                    'label' => $this->l('Select theme to front office'),
                    'name' => 'MARKETPLACE_THEME',
                    'required' => false,
                    'class' => 't',
                    'values' => array(
                        array(
                                'id' => 'default',
                                'value' => 'default',
                                'label' => $this->l('default')
                        ),
                        array(
                                'id' => 'default-bootstrap',
                                'value' => 'default-bootstrap',
                                'label' => $this->l('default-bootstrap')
                        )
                    ),
                ),
                array(
                    'type' => $type,
                    'label' => $this->l('Show tabs'),
                    'name' => 'MARKETPLACE_TABS',
                    'desc' => $this->l('Page to add products and edit product has tabs.'),
                    'required' => false,
                    'is_bool' => true,
                    'class' => 't',
                    'values' => array(
                        array(
                                'id' => 'active_on',
                                'value' => 1,
                                'label' => $this->l('Enabled')
                        ),
                        array(
                                'id' => 'active_off',
                                'value' => 0,
                                'label' => $this->l('Disabled')
                        )
                    ),
                ),
                array(
                    'type' => $type,
                    'label' => $this->l('Show top menu'),
                    'name' => 'MARKETPLACE_MENU_TOP',
                    'desc' => $this->l('Show top menu of options on all page.'),
                    'required' => false,
                    'is_bool' => true,
                    'class' => 't',
                    'values' => array(
                        array(
                                'id' => 'active_on',
                                'value' => 1,
                                'label' => $this->l('Enabled')
                        ),
                        array(
                                'id' => 'active_off',
                                'value' => 0,
                                'label' => $this->l('Disabled')
                        )
                    ),
                ),
                array(
                    'type' => $type,
                    'label' => $this->l('Show menu options'),
                    'name' => 'MARKETPLACE_MENU_OPTIONS',
                    'desc' => $this->l('Show side menu of options on all page.'),
                    'required' => false,
                    'is_bool' => true,
                    'class' => 't',
                    'values' => array(
                        array(
                                'id' => 'active_on',
                                'value' => 1,
                                'label' => $this->l('Enabled')
                        ),
                        array(
                                'id' => 'active_off',
                                'value' => 0,
                                'label' => $this->l('Disabled')
                        )
                    ),
                ),
            ),
            'submit' => array(
                'name' => 'submitThemeSettings',
                'title' => $this->l('Save'),
                'class' => 't btn btn-default pull-right',
            ),
        );
        
        $helper->fields_value['MARKETPLACE_THEME'] = Configuration::get('MARKETPLACE_THEME');
        $helper->fields_value['MARKETPLACE_TABS'] = Configuration::get('MARKETPLACE_TABS');
        $helper->fields_value['MARKETPLACE_MENU_TOP'] = Configuration::get('MARKETPLACE_MENU_TOP');
        $helper->fields_value['MARKETPLACE_MENU_OPTIONS'] = Configuration::get('MARKETPLACE_MENU_OPTIONS');

        return $helper->generateForm($this->fields_form);
    }
    
    public function displayCronSellerHolidays() {        

        if (version_compare(_PS_VERSION_, '1.6', '<'))
            $type = 'radio';
        else
            $type = 'switch';
        
        $languages = Language::getLanguages(false);
        foreach ($languages as $k => $language)
            $languages[$k]['is_default'] = (int)$language['id_lang'] == Configuration::get('PS_LANG_DEFAULT');

        $helper = new HelperForm();
        $helper->module = $this;
        $helper->name_controller = $this->name;
        $helper->identifier = $this->identifier;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->languages = $languages;
        $helper->currentIndex = AdminController::$currentIndex.'&configure='.$this->name;
        $helper->default_form_language = (int)Configuration::get('PS_LANG_DEFAULT');
        $helper->allow_employee_form_lang = true;
        $helper->toolbar_scroll = true;
        $helper->title = $this->displayName;
        $helper->submit_action = 'submitSellerHolidays';

        $this->fields_form[0]['form'] = array(
            'tinymce' => false,
            'legend' => array(
                'title' => $this->l('Seller holidays')
            ),
            'input' => array(                
                array(
                    'type' => $type,
                    'label' => $this->l('Show Seller holidays'),
                    'name' => 'MARKETPLACE_SELLER_HOLIDAYS',                    
                    'required' => false,
                    'is_bool' => true,
                    'class' => 't',
                    'values' => array(
                        array(
                                'id' => 'active_on',
                                'value' => 1,
                                'label' => $this->l('Enabled')
                        ),
                        array(
                                'id' => 'active_off',
                                'value' => 0,
                                'label' => $this->l('Disabled')
                        )
                    ),
                ),                
            ),
            'submit' => array(
                'name' => 'submitSellerHolidays',
                'title' => $this->l('Save'),
                'class' => 't btn btn-default pull-right',
            ),
        );
        
        $helper->fields_value['MARKETPLACE_SELLER_HOLIDAYS'] = Configuration::get('MARKETPLACE_SELLER_HOLIDAYS');        

        return $helper->generateForm($this->fields_form);
    }

    private function displayFormOtherSeller() {
        
        if (version_compare(_PS_VERSION_, '1.6', '<'))
            $type = 'radio';
        else
            $type = 'switch';
        
        $languages = Language::getLanguages(false);
        foreach ($languages as $k => $language)
            $languages[$k]['is_default'] = (int)$language['id_lang'] == Configuration::get('PS_LANG_DEFAULT');

        $helper = new HelperForm();
        $helper->module = $this;
        $helper->name_controller = $this->name;
        $helper->identifier = $this->identifier;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->languages = $languages;
        $helper->currentIndex = AdminController::$currentIndex.'&configure='.$this->name;
        $helper->default_form_language = (int)Configuration::get('PS_LANG_DEFAULT');
        $helper->allow_employee_form_lang = true;
        $helper->toolbar_scroll = true;
        $helper->title = $this->displayName;
        $helper->submit_action = 'submitOtherSettings';

        $this->fields_form[0]['form'] = array(
            'tinymce' => false, 
            'legend' => array(
                'title' => $this->l('Settings')
            ),
            'input' => array(
                array(
                    'type' => $type,
                    'label' => $this->l('Consider shipping in product comparator'),
                    'name' => 'JPRODUCTCOMPARATOR_SHOW_SHIPPING',
                    'required' => false,
                    'is_bool' => true,
                    'class' => 't',
                    'values' => array(
                        array(
                                'id' => 'active_on',
                                'value' => 1,
                                'label' => $this->l('Enabled')
                        ),
                        array(
                                'id' => 'active_off',
                                'value' => 0,
                                'label' => $this->l('Disabled')
                        )
                    ),
                ), 
            ),
            'submit' => array(
                'name' => 'submitOtherSettings',
                'title' => $this->l('Save'),
                'class' => 't btn btn-default pull-right',
            ),
        );
        
        $helper->fields_value['JPRODUCTCOMPARATOR_SHOW_SHIPPING'] = Configuration::get('JPRODUCTCOMPARATOR_SHOW_SHIPPING');

        return $helper->generateForm($this->fields_form);
    }

    private function displayFormSellerCommentSettings()
    {
        $this->_html  = '';
        if (Tools::isSubmit('updateseller_product_comment_criterion'))
                $this->_html .= $this->renderCriterionForm((int)Tools::getValue('id_seller_product_comment_criterion'));
        else

        {
                $this->_html .= $this->renderConfigForm();
                $this->_html .= $this->renderModerateLists();
                $this->_html .= $this->renderCriterionList();
                $this->_html .= $this->renderCommentsList();
        }

        $this->_setBaseUrl();
        $this->_productCommentsCriterionTypes = SellerProductCommentCriterion::getTypes();

        $this->context->controller->addJs($this->_path.'js/moderate.js');

        return $this->_html;
    }

    public function renderConfigForm()
    {
        $fields_form_1 = array(
                'form' => array(
                        'legend' => array(
                                'title' => $this->l('Configuration'),
                                'icon' => 'icon-cogs'
                        ),
                        'input' => array(
                                array(
                                        'type' => 'switch',
                                        'is_bool' => true, //retro compat 1.5
                                        'label' => $this->l('All reviews must be validated by an employee'),
                                        'name' => 'JSELLERPRODUCTSCOMMENTS_MODERATE',
                                        'class' => 't',
                                        'values' => array(
                                                                        array(
                                                                                'id' => 'active_on',
                                                                                'value' => 1,
                                                                                'label' => $this->l('Enabled')
                                                                        ),
                                                                        array(
                                                                                'id' => 'active_off',
                                                                                'value' => 0,
                                                                                'label' => $this->l('Disabled')
                                                                        )
                                                                ),
                                ),
                                array(
                                        'type' => 'switch',
                                        'is_bool' => true, //retro compat 1.5
                                        'label' => $this->l('Allow guest reviews'),
                                        'name' => 'JSELLERPRODUCTSCOMMENTS_ALLOW_GUESTS',
                                        'class' => 't',
                                        'values' => array(
                                                array(
                                                        'id' => 'active_on',
                                                        'value' => 1,
                                                        'label' => $this->l('Enabled')
                                                ),
                                                array(
                                                        'id' => 'active_off',
                                                        'value' => 0,
                                                        'label' => $this->l('Disabled')
                                                )
                                        ),
                                ),
                                array(
                                        'type' => 'switch',
                                        'is_bool' => true, //retro compat 1.5
                                        'disabled' => true,
                                        'label' => $this->l('Comments are only allowed for customers who have bought the product'),
                                        'name' => 'JSELLERPRODUCTSCOMMENTS_ALLOW_ONLY_ORDER',
                                        'class' => 't',
                                        'values' => array(
                                                array(
                                                        'id' => 'active_on',
                                                        'value' => 1,
                                                        'label' => $this->l('Enabled')
                                                ),
                                                array(
                                                        'id' => 'active_off',
                                                        'value' => 0,
                                                        'label' => $this->l('Disabled')
                                                )
                                        ),
                                ),
                                
                        ),
                'submit' => array(
                        'title' => $this->l('Save'),
                        'class' => 't btn btn-default pull-right',
                        'name' => 'submitModerate',
                        )
                ),
        );

        $helper = new HelperForm();
        $helper->show_toolbar = false;
        $helper->table =  $this->name;
        $lang = new Language((int)Configuration::get('PS_LANG_DEFAULT'));
        $helper->default_form_language = $lang->id;
        $helper->module = $this;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') ? Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') : 0;
        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitProducCommentsConfiguration';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false).'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->tpl_vars = array(
                'fields_value' => $this->getConfigFieldsValues(),
                'languages' => $this->context->controller->getLanguages(),
                'id_language' => $this->context->language->id
        );

        return $helper->generateForm(array($fields_form_1));
    }

    public function renderModerateLists()
    {
        require_once(dirname(__FILE__).'/classes/SellerProductComment.php');
        $return = null;

        if (Configuration::get('JSELLERPRODUCTSCOMMENTS_MODERATE'))
        {
                $comments = SellerProductComment::getByValidate(0, false);

                $fields_list = $this->getStandardFieldList();

                if (version_compare(_PS_VERSION_, '1.6', '<'))
                {
                        $return .= "<h1>".$this->l('Reviews waiting for approval')."</h1>";
                        $actions = array('enable', 'delete');
                }
                else
                        $actions = array('approve', 'delete');

                $helper = new HelperList();
                $helper->shopLinkType = '';
                $helper->simple_header = true;
                $helper->actions = $actions;
                $helper->show_toolbar = false;
                $helper->module = $this;
                $helper->listTotal = count($comments);
                $helper->identifier = 'id_seller_product_comment';
                $helper->title = $this->l('Reviews waiting for approval');
                $helper->table = 'seller_product_comment';
                $helper->token = Tools::getAdminTokenLite('AdminModules');
                $helper->currentIndex = AdminController::$currentIndex.'&configure='.$this->name;
                //$helper->tpl_vars = array('priority' => array($this->l('High'), $this->l('Medium'), $this->l('Low')));

                $return .= $helper->generateList($comments, $fields_list);
        }

        return $return;

    }

    public function renderCriterionList()
    {
        $criterions = SellerProductCommentCriterion::getCriterions($this->context->language->id, false, false);

        $fields_list = array(
                'id_seller_product_comment_criterion' => array(
                        'title' => $this->l('ID'),
                        'type' => 'text',
                ),
                'name' => array(
                        'title' => $this->l('Name'),
                        'type' => 'text',
                ),
                'type_name' => array(
                        'title' => $this->l('Type'),
                        'type' => 'text',
                ),
                'active' => array(
                        'title' => $this->l('Status'),
                        'active' => 'status',
                        'type' => 'bool',
                ),
        );

        $helper = new HelperList();
        $helper->shopLinkType = '';
        $helper->simple_header = false;
        $helper->actions = array('edit', 'delete');
        $helper->show_toolbar = true;
        $helper->toolbar_btn['new'] = array(
                'href' => $this->context->link->getAdminLink('AdminModules').'&configure='.$this->name.'&module_name='.$this->name.'&updateseller_product_comment_criterion#seller_comments_settings',
                'desc' => $this->l('Add New Criterion', null, null, false)
        );
        $helper->module = $this;
        $helper->identifier = 'id_seller_product_comment_criterion';
        $helper->title = $this->l('Review Criteria');
        $helper->table = 'seller_product_comment_criterion';
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex.'&configure='.$this->name;
        //$helper->tpl_vars = array('priority' => array($this->l('High'), $this->l('Medium'), $this->l('Low')));

        return $helper->generateList($criterions, $fields_list);
    }

    public function renderCommentsList()
    {
        require_once(dirname(__FILE__).'/classes/SellerProductComment.php');

        $comments = SellerProductComment::getByValidate(1, false);

        $fields_list = $this->getStandardFieldList();

        $helper = new HelperList();
        $helper->shopLinkType = '';
        $helper->simple_header = true;
        $helper->actions = array('delete');
        $helper->show_toolbar = false;
        $helper->module = $this;
        $helper->listTotal = count($comments);
        $helper->identifier = 'id_seller_product_comment';
        $helper->title = $this->l('Approved Reviews');
        $helper->table = 'seller_product_comment';
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex.'&configure='.$this->name;
        //$helper->tpl_vars = array('priority' => array($this->l('High'), $this->l('Medium'), $this->l('Low')));

        return $helper->generateList($comments, $fields_list);
    }

    public function getConfigFieldsValues()
    {
        return array(
                'JSELLERPRODUCTSCOMMENTS_MODERATE' => Tools::getValue('JSELLERPRODUCTSCOMMENTS_MODERATE', Configuration::get('JSELLERPRODUCTSCOMMENTS_MODERATE')),
                'JSELLERPRODUCTSCOMMENTS_ALLOW_GUESTS' => Tools::getValue('JSELLERPRODUCTSCOMMENTS_ALLOW_GUESTS', Configuration::get('JSELLERPRODUCTSCOMMENTS_ALLOW_GUESTS')),
                'JSELLERPRODUCTSCOMMENTS_ALLOW_ONLY_ORDER' => Tools::getValue('JSELLERPRODUCTSCOMMENTS_ALLOW_ONLY_ORDER', Configuration::get('JSELLERPRODUCTSCOMMENTS_ALLOW_ONLY_ORDER')),
        );
    }

    public function getCriterionFieldsValues($id = 0)
    {
        $criterion = new SellerProductCommentCriterion($id);

        return array(
                                'name' => $criterion->name,
                                'id_seller_product_comment_criterion_type' => $criterion->id_seller_product_comment_criterion_type,
                                'active' => $criterion->active,
                                'id_seller_product_comment_criterion' => $criterion->id,
                        );
    }

    public function getStandardFieldList()
    {
        return array(
                'id_seller_product_comment' => array(
                        'title' => $this->l('ID'),
                        'type' => 'text',
                ),
                'title' => array(
                        'title' => $this->l('Review title'),
                        'type' => 'text',
                ),
                'content' => array(
                        'title' => $this->l('Review'),
                        'type' => 'text',
                ),
                'grade' => array(
                        'title' => $this->l('Rating'),
                        'type' => 'text',
                        'suffix' => '/5',
                ),
                'customer_name' => array(
                        'title' => $this->l('Author'),
                        'type' => 'text',
                ),
                'name' => array(
                        'title' => $this->l('Product'),
                        'type' => 'text',
                ),
                'date_add' => array(
                        'title' => $this->l('Time of publication'),
                        'type' => 'date',
                ),
        );
    }

    function renderCriterionForm($id_criterion = 0)
    {
        $types = SellerProductCommentCriterion::getTypes();
        $query =array();
        foreach ($types as $key => $value)
        {
                $query[] = array(
                                'id' => $key,
                                'label' => $value,
                        );
        }

        $criterion = new SellerProductCommentCriterion((int)$id_criterion);
        $selected_categories = $criterion->getCategories();

        $product_table_values = Product::getSimpleProducts($this->context->language->id);
        $selected_products = $criterion->getProducts();
        foreach ($product_table_values as $key => $product) {
                if(false !== array_search($product['id_product'], $selected_products))
                        $product_table_values[$key]['selected'] = 1;
        }

        if (version_compare(_PS_VERSION_, '1.6', '<'))
                $field_category_tree = array(
                                                                'type' => 'categories_select',
                                                                'name' => 'categoryBox',
                                                                'label' => $this->l('Criterion will be restricted to the following categories'),
                                                                'category_tree' => $this->initCategoriesAssociation(null, $id_criterion),
                                                        );
        else
                $field_category_tree = array(
                                                'type' => 'categories',
                                                'label' => $this->l('Criterion will be restricted to the following categories'),
                                                'name' => 'categoryBox',
                                                'desc' => $this->l('Mark the boxes of categories to which this criterion applies.'),
                                                'tree' => array(
                                                        'use_search' => false,
                                                        'id' => 'categoryBox',
                                                        'use_checkbox' => true,
                                                        'selected_categories' => $selected_categories,
                                                ),
                                                //retro compat 1.5 for category tree
                                                'values' => array(
                                                        'trads' => array(
                                                                'Root' => Category::getTopCategory(),
                                                                'selected' => $this->l('Selected'),
                                                                'Collapse All' => $this->l('Collapse All'),
                                                                'Expand All' => $this->l('Expand All'),
                                                                'Check All' => $this->l('Check All'),
                                                                'Uncheck All' => $this->l('Uncheck All')
                                                        ),
                                                        'selected_cat' => $selected_categories,
                                                        'input_name' => 'categoryBox[]',
                                                        'use_radio' => false,
                                                        'use_search' => false,
                                                        'disabled_categories' => array(),
                                                        'top_category' => Category::getTopCategory(),
                                                        'use_context' => true,
                                                )
                                        );

        $fields_form_1 = array(
                'form' => array(
                        'legend' => array(
                                'title' => $this->l('Add new criterion'),
                                'icon' => 'icon-cogs'
                        ),
                        'input' => array(
                                array(
                                        'type' => 'hidden',
                                        'name' => 'id_seller_product_comment_criterion',
                                ),
                                array(
                                        'type' => 'text',
                                        'lang' => true,
                                        'label' => $this->l('Criterion name'),
                                        'name' => 'name',
                                ),
                                array(
                                        'type' => 'select',
                                        'name' => 'id_seller_product_comment_criterion_type',
                                        'label' => $this->l('Application scope of the criterion'),
                                        'options' => array(
                                                                        'query' => $query,
                                                                        'id' => 'id',
                                                                        'name' => 'label'
                                                                ),
                                ),
                                $field_category_tree,
                                array(
                                        'type' => 'products',
                                        'label' => $this->l('The criterion will be restricted to the following products'),
                                        'name' => 'ids_product',
                                        'values' => $product_table_values,
                                ),
                                array(
                                        'type' => 'switch',
                                        'is_bool' => true, //retro compat 1.5
                                        'label' => $this->l('Active'),
                                        'name' => 'active',
                                        'values' => array(
                                                                        array(
                                                                                'id' => 'active_on',
                                                                                'value' => 1,
                                                                                'label' => $this->l('Enabled')
                                                                        ),
                                                                        array(
                                                                                'id' => 'active_off',
                                                                                'value' => 0,
                                                                                'label' => $this->l('Disabled')
                                                                        )
                                                                ),
                                ),
                        ),
                'submit' => array(
                        'title' => $this->l('Save'),
                        'class' => 'btn btn-default pull-right',
                        'name' => 'submitEditCriterion',
                        )
                ),
        );

        $helper = new HelperForm();
        $helper->show_toolbar = false;
        $helper->table =  $this->name;
        $lang = new Language((int)Configuration::get('PS_LANG_DEFAULT'));
        $helper->default_form_language = $lang->id;
        $helper->module = $this;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') ? Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') : 0;
        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitEditCriterion';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false).'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->tpl_vars = array(
                'fields_value' => $this->getCriterionFieldsValues($id_criterion),
                'languages' => $this->context->controller->getLanguages(),
                'id_language' => $this->context->language->id
        );

        return $helper->generateForm(array($fields_form_1));
    }

    private function _checkDeleteComment()
    {
        $action = Tools::getValue('delete_action');
        if (empty($action) === false)
        {
                $seller_product_comments = Tools::getValue('delete_id_seller_product_comment');

                if (count($seller_product_comments))
                {
                        require_once(dirname(__FILE__).'/classes/SellerProductComment.php');
                        if ($action == 'delete')
                        {
                                foreach ($seller_product_comments as $id_seller_product_comment)
                                {
                                        if (!$id_seller_product_comment)
                                                continue;
                                        $comment = new SellerProductComment((int)$id_seller_product_comment);
                                        $comment->delete();
                                        SellerProductComment::deleteGrades((int)$id_seller_product_comment);
                                }
                        }
                }
        }
    }

    private function _setFilters()
    {
        $this->_filters = array(
                                                'page' => (string)Tools::getValue('submitFilter'.$this->name),
                                                'pagination' => (string)Tools::getValue($this->name.'_pagination'),
                                                'filter_id' => (string)Tools::getValue($this->name.'Filter_id_seller_product_comment'),
                                                'filter_content' => (string)Tools::getValue($this->name.'Filter_content'),
                                                'filter_customer_name' => (string)Tools::getValue($this->name.'Filter_customer_name'),
                                                'filter_grade' => (string)Tools::getValue($this->name.'Filter_grade'),
                                                'filter_name' => (string)Tools::getValue($this->name.'Filter_name'),
                                                'filter_date_add' => (string)Tools::getValue($this->name.'Filter_date_add'),
                                        );
    }

    public function displayApproveLink($token = null, $id, $name = null)
    {
        $this->smarty->assign(array(
                'href' => $this->context->link->getAdminLink('AdminModules').'&configure='.$this->name.'&module_name='.$this->name.'&approveComment='.$id,
                'action' => $this->l('Approve'),
        ));

        return $this->display(__FILE__, 'views/templates/admin/list_action_approve.tpl');
    }

    public function displayNoabuseLink($token = null, $id, $name = null)
    {
        $this->smarty->assign(array(
                'href' => $this->context->link->getAdminLink('AdminModules').'&configure='.$this->name.'&module_name='.$this->name.'&noabuseComment='.$id,
                'action' => $this->l('Not abusive'),
        ));

        return $this->display(__FILE__, 'views/templates/admin/list_action_noabuse.tpl');
    }

    private function displayFormPayments() 
    {
        if (version_compare(_PS_VERSION_, '1.6', '<'))
            $type = 'radio';
        else
            $type = 'switch';

        $languages = Language::getLanguages(false);
        foreach ($languages as $k => $language)
            $languages[$k]['is_default'] = (int)$language['id_lang'] == Configuration::get('PS_LANG_DEFAULT');

        $helper = new HelperForm();
        $helper->module = $this;
        $helper->name_controller = $this->name;
        $helper->identifier = $this->identifier;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->languages = $languages;
        $helper->currentIndex = AdminController::$currentIndex.'&configure='.$this->name;
        $helper->default_form_language = (int)Configuration::get('PS_LANG_DEFAULT');
        $helper->allow_employee_form_lang = true;
        $helper->toolbar_scroll = true;
        $helper->title = $this->displayName;
        $helper->submit_action = 'submitPayments';

        $this->fields_form[0]['form'] = array(
            'tinymce' => false,
            'legend' => array(
                'title' => $this->l('Seller payment')
            ),
            'input' => array(  
                array(
                    'type' => $type,
                    'label' => $this->l('Paypal'),
                    'name' => 'MARKETPLACE_PAYPAL',
                    'required' => false,
                    'is_bool' => true,
                    'class' => 't',
                    'desc' => $this->l('Pay your sellers with Paypal'),
                    'values' => array(
                        array(
                                'id' => 'active_on',
                                'value' => 1,
                                'label' => $this->l('Enabled')
                        ),
                        array(
                                'id' => 'active_off',
                                'value' => 0,
                                'label' => $this->l('Disabled')
                        )
                    ),
                ),
                array(
                    'type' => $type,
                    'label' => $this->l('Bankwire'),
                    'name' => 'MARKETPLACE_BANKWIRE',
                    'required' => false,
                    'is_bool' => true,
                    'class' => 't',
                    'desc' => $this->l('Pay your sellers with Bankwire'),
                    'values' => array(
                        array(
                                'id' => 'active_on',
                                'value' => 1,
                                'label' => $this->l('Enabled')
                        ),
                        array(
                                'id' => 'active_off',
                                'value' => 0,
                                'label' => $this->l('Disabled')
                        )
                    ),
                ),    
            ),
            'submit' => array(
                'name' => 'submitPayments',
                'title' => $this->l('Save'),
            ),
        );
        
        $helper->fields_value['MARKETPLACE_PAYPAL'] = Configuration::get('MARKETPLACE_PAYPAL');
        $helper->fields_value['MARKETPLACE_BANKWIRE'] = Configuration::get('MARKETPLACE_BANKWIRE');

        return $helper->generateForm($this->fields_form);
    }
    
    public function hookDisplayCustomerAccount($params) 
    {
        $customer_can_be_seller = false;
        $customer_groups = Customer::getGroupsStatic($this->context->cookie->id_customer);

        foreach ($customer_groups as $id_group) {
            if (Configuration::get('MARKETPLACE_CUSTOMER_GROUP_'.$id_group) == 1)
                $customer_can_be_seller = true;
        }
        
        /*$incidences = SellerIncidence::getIncidencesByCustomer((int)$this->context->cookie->id_customer);

        if ($incidences != false) {
                $counter = 0;
                foreach ($incidences as $i) {
                    $incidences[$counter]['messages_not_readed'] = SellerIncidence::getNumMessagesNotReaded($i['id_seller_incidence'], false, (int)$this->context->cookie->id_customer);
                    $messages = SellerIncidence::getMessages((int)$i['id_seller_incidence'],false,$this->context->cookie->id_customer);
                    $incidences[$counter] = array_merge($incidences[$counter], array('messages' => $messages));
                    $counter++;
                }
            }*/

        $this->context->smarty->assign(array(
            'is_seller' => Seller::isSeller($this->context->cookie->id_customer, $this->context->shop->id),
            'is_active_seller' => Seller::isActiveSellerByCustomer($this->context->cookie->id_customer),
            'customer_can_be_seller' => $customer_can_be_seller,
            'id_default_group' => $this->context->customer->id_default_group,
            'show_contact' => Configuration::get('MARKETPLACE_SHOW_CONTACT'),
            'show_seller_favorite' => Configuration::get('MARKETPLACE_SELLER_FAVORITE'),
            'ssl_enabled' => Configuration::get('PS_SSL_ENABLED'),
            'mesages_not_readed' => SellerIncidenceMessage::getNumMessagesNotReadedByCust((int)$this->context->cookie->id_customer),
        ));

        return $this->display(__FILE__, 'customer-account.tpl');
    }
    
    public function hookDisplayProductAdditionalInfo() 
    {
        $offersOther = array();
        $offers = array();
        $id_product = (int)Tools::getValue('id_product');
        $id_seller = SellerProduct::isSellerProduct($id_product);
        $seller = new Seller($id_seller);

        $id_lang = $this->context->language->id ;
        $link = $this->context->link ;

        $params = array('id_seller' => $id_seller, 'id_product' => $id_product);	
        $params_seller_profile = array('id_seller' => $id_seller, 'link_rewrite' => $seller->link_rewrite);        

        $url_seller_comments = $this->context->link->getModuleLink('marketplace', 'sellercomments', $params, true);	
	    $url_contact_seller = $this->context->link->getModuleLink('marketplace', 'contactseller', $params, true);
        $url_favorite_seller = $this->context->link->getModuleLink('marketplace', 'favoriteseller', $params, true);        
        $url_seller_profile = marketplace::getmarketplaceLink('marketplace_seller_rule', $params_seller_profile);
        $url_seller_products = marketplace::getmarketplaceLink('marketplace_sellerproductlist_rule', $params_seller_profile);
        
        $product = new Product($id_product, null, $this->context->language->id, $this->context->shop->id);
        
        $back_url = $link->getProductLink($product);

        $is_seller = Seller::isSeller($this->context->customer->id, $this->context->shop->id);

        // seller holidays   

        $current_date = date('Y-m-d');
        $vacancy = false;
        $seller_holidays = SellerHoliday::getHolidaysBySeller($id_seller);
        if (is_array($seller_holidays) && count($seller_holidays) > 0) {
            foreach ($seller_holidays as $holiday) {
                if (SellerHoliday::compareDates($current_date, $holiday['from']) <= 0 && SellerHoliday::compareDates($current_date, $holiday['to']) > 0) {
                    $vacancy = true;
                    $to = $holiday['to'];
                    $from = $holiday['from'];
                    break;
                }
            }
        }
        
        if ($vacancy) {
            $this->context->smarty->assign(array(
                'to' => $to,
                'from' => $from,
                'vacancy' => Configuration::get('MARKETPLACE_SELLER_HOLIDAYS'),
            ));
        }

        //product comparator
        $offers = ProductEanComparator::getAllProductsSameSellerBestOffer($product->name, $this->context->language->id);

        $offersOther = ProductEanComparator::getOtherProductsBestOffer($product->id,$product->name, $this->context->language->id);

        //$id_seller = Seller::getSellerByProduct($id_product);
        
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

        $i=0;

        if (is_array($offers)) {
            foreach ($offers as $s) {

                $prod_ttc = new Product($s['id_product']);
                $price_ttc = $prod_ttc->getPrice(true, NULL, 6);
                if($id_product == $s['id_product'])
                {
                    $param = array('id_seller' => $s['id_seller'], 'link_rewrite' => $s['seller_link_rewrite']);
                
                    $url_seller_profile = Module::getInstanceByName('marketplace')->getmarketplaceLink('marketplace_seller_rule', $param);
                }

                $offers[$i]['seller_link'] = $url_seller_profile;
                if (Seller::hasImage($s['id_customer']))
                    $offers[$i]['has_image'] = 1;
                else
                    $offers[$i]['has_image'] = 0;

                if(Configuration::get('MARKETPLACE_SELLER_RATING')) {
                    $marketplace = Module::getInstanceByName('marketplace');

                    
                        $average = SellerComment::getRatings($s['id_seller']);
                        $averageTotal = SellerComment::getCommentNumber($s['id_seller']);
                        if ($averageTotal > 0)
                            $averageMiddle = ceil($average['avg']);  
                        else
                            $averageMiddle = 0;
                    
                    $offers[$i]['averageTotal'] = (int)$averageTotal;
                    $offers[$i]['averageMiddle'] = (int)$averageMiddle; 
                }

                $seller_carriers = array();

                //if (class_exists('SellerCarrier')) 
                $seller_carriers = SellerTransport::getCarriersForOrder($s['id_seller'], $id_zone);

                if (!$seller_carriers)                
                    $seller_carriers = Carrier::getCarriersForOrder($id_zone); 
                

                $seller_shipping_cost = 0;

                if (count($seller_carriers) > 0) {
                    $carrier = new Carrier($seller_carriers[0]['id_carrier']);
                    $seller_shipping_cost = $carrier->getDeliveryPriceByWeight($s['weight'], $id_zone);
                    //$seller_shipping_cost = $seller_carriers[0]['price'];
                    if (!$seller_shipping_cost)
                        $seller_shipping_cost = 0;

                    $seller_shipping_cost = $seller_shipping_cost + $s['additional_shipping_cost'];

                    $offers[$i]['carrier_name'] = $carrier->name; 
                    $offers[$i]['seller_shipping_cost'] = $seller_shipping_cost; 
                    $offers[$i]['seller_shipping_delay'] = $seller_carriers[0]['delay'];
                    $offers[$i]['price_with_shipping'] = $price_ttc + $seller_shipping_cost;
                }
                else
                {
                    $offers[$i]['seller_shipping_cost'] = $s['additional_shipping_cost'];

                    $offers[$i]['price_with_shipping'] = $offers[$i]['price'] + $s['additional_shipping_cost'];

                }

                if (Seller::hasImage($s['id_customer']))
                    $offers[$i]['seller_has_image'] = 1;
                else
                    $offers[$i]['seller_has_image'] = 0;

                $offers[$i]['price_without_reduction'] = $price_ttc;

                $i++;
            }

            foreach($offers as $key => $row) {           
                $price_with_shipping[$key] = $row['price_with_shipping'] ;
            }

            array_multisort($price_with_shipping, SORT_ASC, $offers);
        }        

        if(count($offersOther) > 0)
            $this->bestoffer_showed = 1;
                
        $this->context->smarty->assign(array(
            'show_shipping' => Configuration::get('JPRODUCTCOMPARATOR_SHOW_SHIPPING'),
            'show_seller_rating' => Configuration::get('MARKETPLACE_SELLER_RATING'),
            'offers' => $offers,
            'num_offers' => count($offers),
            'offersOther' => $offersOther,
        ));

        $this->context->smarty->assign(array(
            'back_url' => $back_url,
            'is_seller' => $is_seller,
            'id_product' => $id_product,
            'url_contact_seller' => $url_contact_seller,
            'seller_link' => $url_seller_profile,
            'url_seller_comments' => $url_seller_comments,
            'url_seller_products' => $url_seller_products,
            'url_favorite_seller' => $url_favorite_seller,
            'is_product_seller' => $id_seller,
            'show_contact_seller' => Configuration::get('MARKETPLACE_SHOW_CONTACT'),
            'show_seller_profile' => Configuration::get('MARKETPLACE_SHOW_PROFILE'),
            'show_seller_favorite' => Configuration::get('MARKETPLACE_SELLER_FAVORITE'),
            'show_seller_rating' => Configuration::get('MARKETPLACE_SELLER_RATING'),
            'seller' => $seller,
            'id_customer' => $this->context->cookie->id_customer,
            'is_logged' => $this->context->customer->isLogged(true),
        ));
        
        if(Configuration::get('MARKETPLACE_SELLER_RATING')) {
            $average = SellerComment::getRatings($id_seller);
            $averageTotal = SellerComment::getCommentNumber($id_seller);
            
            $this->context->smarty->assign(array(
                'averageTotal' => (int)$averageTotal,
                'averageMiddle' => ceil($average['avg']),
                'averagePositif' => (int)SellerComment::getPositifComment($id_seller),
            ));       
        }
        
        //return $this->display(__FILE__, 'product-buttons.tpl');
        return $this->context->smarty->fetch('module:marketplace/views/templates/hook/product-buttons.tpl');
    }
    
    public function hookDisplayProductListReviews($params)
    {
        if (Configuration::get('MARKETPLACE_SHOW_PROFILE') == 1) {
            $id_product = (int)$params['product']['id_product'];
            $id_seller = SellerProduct::isSellerProduct($id_product);

            if ($id_seller > 0 && Configuration::get('MARKETPLACE_SHOW_SELLER_PLIST') == 1) {
                $seller = new Seller($id_seller);
                $param = array('id_seller' => $seller->id, 'link_rewrite' => $seller->link_rewrite);			
                $url_seller_profile = $this->getmarketplaceLink('marketplace_seller_rule', $param);

                $this->context->smarty->assign(array(
                    'seller' => $seller,
                    'seller_link' => $url_seller_profile,
                ));
                
                //if (!$this->isCached('productcomments_reviews.tpl', $this->getCacheId($id_product)))
                //{
                        //require_once(dirname(__FILE__).'/classes/SellerProductComment.php');

                        $average = SellerProductComment::getAverageGrade($id_product);
                        $this->smarty->assign(array(
                                'product' => $params['product'],
                                'averageTotal' => round($average['grade']),
                                'ratings' => SellerProductComment::getRatings($id_product),
                                'nbComments' => (int)SellerProductComment::getCommentNumber($id_product),
                                'moderation_active' => (int)Configuration::get('JSELLERPRODUCTSCOMMENTS_MODERATE')

                        ));
                //}

                return $this->display(__FILE__, 'productcomments_reviews.tpl');//, $this->getCacheId($id_product));
            }
            else
            {
                $average = SellerProductComment::getAverageGrade($id_product);
                $this->smarty->assign(array(
                    'product' => $params['product'],
                    'averageTotal' => round($average['grade']),
                    'ratings' => SellerProductComment::getRatings($id_product),
                    'nbComments' => (int)SellerProductComment::getCommentNumber($id_product),
                    'moderation_active' => (int)Configuration::get('JSELLERPRODUCTSCOMMENTS_MODERATE')
                ));
            
                return $this->display(__FILE__, 'productcomments_reviews.tpl');
            }           
        }
        else
        {
            $id_product = (int)$params['product']['id_product'];
            //if (!$this->isCached('productcomments_reviews.tpl', $this->getCacheId($id_product)))
            //{
                //require_once(dirname(__FILE__).'/classes/SellerProductComment.php');
                $average = SellerProductComment::getAverageGrade($id_product);
                $this->smarty->assign(array(
                    'product' => $params['product'],
                    'averageTotal' => round($average['grade']),
                    'ratings' => SellerProductComment::getRatings($id_product),
                    'nbComments' => (int)SellerProductComment::getCommentNumber($id_product)
                ));
            //}
            return $this->display(__FILE__, 'productcomments_reviews.tpl');//, $this->getCacheId($id_product));
        }
    }
    
    public function assingProductCopy(){
        $id_lang = $this->context->language->id ;
        $id_product_master = SellerProduct::getProductCopyParentMaster( Tools::getValue('id_product') );
        
        $this->tmFamilyProducts = array();
        if( $id_product_master ){
            $tiProductID = array();
            $tmFamilyProductsTemp = SellerProduct::getProductFamilyFull($id_product_master,$id_lang,true);
            
            if( !empty($tmFamilyProductsTemp) ){
                foreach( $tmFamilyProductsTemp as $mFamilyProduct ){
                    $tiProductID[] = $mFamilyProduct['id_product'];
                    if( $mFamilyProduct['id_product'] != Tools::getValue('id_product') ){
                        $this->tmFamilyProducts[] = $mFamilyProduct;
                    }
                }
            }
        }
    }

    public function HookDisplayProductTab($params){
        if( $this->tmFamilyProducts === NULL ){
            $this->assingProductCopy();
        }
        
        if( !empty($this->tmFamilyProducts) ){
            
            $average = SellerProductComment::getAverageGrade((int)Tools::getValue('id_product'));

            $this->context->smarty->assign(array(
                'allow_guests' => (int)Configuration::get('PRODUCT_COMMENTS_ALLOW_GUESTS'),
                'comments' => SellerProductComment::getByProduct((int)(Tools::getValue('id_product'))),
                'criterions' => SellerProductCommentCriterion::getByProduct((int)(Tools::getValue('id_product')), $this->context->language->id),
                'averageTotal' => round($average['grade']),
                'nbComments' => (int)(SellerProductComment::getCommentNumber((int)(Tools::getValue('id_product'))))
            ));

            $this->context->smarty->assign(array(
                'products' => $this->tmFamilyProducts,
                'is_sell_by_another_seller' => count($this->tmFamilyProducts)
            ));

            return $this->display(__FILE__,'copy-product-tab.tpl');
        }
    }

    public function HookDisplayProductTabContent($params){
        return null;

        if( $this->tmFamilyProducts === NULL ){
            $this->assingProductCopy();
        }
        
        if( !empty($this->tmFamilyProducts) ) {
            $this->context->smarty->assign(array(
                'products' => $this->tmFamilyProducts,
                'is_sell_by_another_seller' => count($this->tmFamilyProducts)
            ));

            return $this->display(__FILE__, 'copy-product-list.tpl');
        }
    }
    
    public function hookDisplayFooterProduct($params){

        // product comparator
        $offers = array();
        $id_product = (int)Tools::getValue('id_product');
        
        $product = new Product($id_product, null, $this->context->language->id, $this->context->shop->id);
        
        $offers = ProductEanComparator::getOtherProducts($id_product, $product->name, $this->context->language->id);
        
        $id_seller = Seller::getSellerByProduct($id_product);
        
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

        $i=0;

        if (is_array($offers)) {
            foreach ($offers as $s) {

                $param = array('id_seller' => $s['id_seller'], 'link_rewrite' => $s['seller_link_rewrite']);
                $url_seller_profile = Module::getInstanceByName('marketplace')->getmarketplaceLink('marketplace_seller_rule', $param);

                $offers[$i]['seller_link'] = $url_seller_profile;
                if (Seller::hasImage($s['id_customer']))
                    $offers[$i]['has_image'] = 1;
                else
                    $offers[$i]['has_image'] = 0;

                if(Configuration::get('MARKETPLACE_SELLER_RATING')) {
                    $marketplace = Module::getInstanceByName('marketplace');

                    $average = SellerComment::getRatings($s['id_seller']);
                    $averageTotal = SellerComment::getCommentNumber($s['id_seller']);
                    if ($averageTotal > 0)
                        $averageMiddle = ceil($average['avg']);  
                    else
                        $averageMiddle = 0;
                    
                    $offers[$i]['averageTotal'] = (int)$averageTotal;
                    $offers[$i]['averageMiddle'] = (int)$averageMiddle; 
                }

                $seller_carriers = array();

                //if (class_exists('SellerCarrier')) 
                $seller_carriers = SellerTransport::getCarriersForOrder($s['id_seller'], $id_zone); 
                if (!$seller_carriers)                
                    $seller_carriers = Carrier::getCarriersForOrder($id_zone); 
                

                $seller_shipping_cost = 0;
                if (count($seller_carriers) > 0) {
                    $carrier = new Carrier($seller_carriers[0]['id_carrier']);
                    $seller_shipping_cost = $carrier->getDeliveryPriceByWeight($s['weight'], $id_zone) + $s['additional_shipping_cost'];
                    //$seller_shipping_cost = $seller_carriers[0]['price'];
                    if (!$seller_shipping_cost)
                        $seller_shipping_cost = $s['additional_shipping_cost'];

                    $offers[$i]['carrier_name'] = $carrier->name; 
                    $offers[$i]['seller_shipping_cost'] = $seller_shipping_cost; 
                    $offers[$i]['seller_shipping_delay'] = $seller_carriers[0]['delay']; 
                }

                if (Seller::hasImage($s['id_customer']))
                    $offers[$i]['seller_has_image'] = 1;
                else
                    $offers[$i]['seller_has_image'] = 0;

                $current_date = date('Y-m-d');
                $vacancy = false;
                $seller_holidays = SellerHoliday::getHolidaysBySeller($s['id_seller']);
                if (is_array($seller_holidays) && count($seller_holidays) > 0) {
                    foreach ($seller_holidays as $holiday) {
                        if (SellerHoliday::compareDates($current_date, $holiday['from']) <= 0 && SellerHoliday::compareDates($current_date, $holiday['to']) > 0) {
                            $vacancy = true;
                            $to = $holiday['to'];
                            $from = $holiday['from'];
                            break;
                        }
                    }
                }
                
                if ($vacancy) {
                    $offers[$i]['holiday_to'] = $to;
                    $offers[$i]['holiday_from'] = $from;
                    $offers[$i]['holiday_vacancy'] = Configuration::get('MARKETPLACE_SELLER_HOLIDAYS');
                }

                $i++;
            }
        }        

        $this->context->smarty->assign(array(
            'show_shipping' => Configuration::get('JPRODUCTCOMPARATOR_SHOW_SHIPPING'),
            'show_seller_rating' => Configuration::get('MARKETPLACE_SELLER_RATING'),
            'product' => $product,
            'offers' => $offers,
            'num_offers' => count($offers),
        )); 
        
        // product seller comment
        if (Tools::isSubmit('submitMessage')) {
            
            $result = true;
            $id_guest = 0;
            $id_customer = $this->context->customer->id;
            $id_lang = $this->context->language->id;
            /*if (!$id_customer)
                $id_guest = $this->context->cookie->id_guest;*/
            
            $errors = array();
            
            // Validation
            if (!Validate::isInt(Tools::getValue('id_product')))
                $errors[] = $this->l('Product ID is incorrect');
            if (!Tools::getValue('title') || !Validate::isGenericName(Tools::getValue('title')))
                $errors[] = $this->l('Title is incorrect');
            if (!Tools::getValue('content') || !Validate::isMessage(Tools::getValue('content')))
                $errors[] = $this->l('Comment is incorrect');
            if (!$id_customer && (!Tools::isSubmit('customer_name') || !Tools::getValue('customer_name') || !Validate::isGenericName(Tools::getValue('customer_name'))))
                $errors[] = $this->l('Customer name is incorrect');
            if (!$this->context->customer->id && !Configuration::get('PRODUCT_COMMENTS_ALLOW_GUESTS'))
                $errors[] = $this->l('You must be connected in order to send a comment');
            if (!count(Tools::getValue('criterion')))
                $errors[] = $this->l('You must give a rating');

            $product = new Product(Tools::getValue('id_product'), false, $this->context->language->id, $this->context->shop->id);
            if (!$product->id)
                $errors[] = $this->l('Product not found');
            
            if (SellerProductComment::isAlreadyComment($product->id, $id_customer)) 
                $errors[] = $this->l('You have already sent a comment about this product');

            if (!count($errors))
            {
                $customer_comment = SellerProductComment::getByCustomer(Tools::getValue('id_product'), $id_customer, true, $id_guest);
                if (!$customer_comment || ($customer_comment && (strtotime($customer_comment['date_add']) + (int)Configuration::get('JSELLERPRODUCTSCOMMENTS_MINIMAL_TIME')) < time()))
                {
                    $comment = new SellerProductComment();
                    $comment->content = strip_tags(Tools::getValue('content'));
                    $comment->id_product = (int)Tools::getValue('id_product');
                    $comment->id_customer = (int)$id_customer;
                    $comment->id_guest = $id_guest;
                    $comment->customer_name = Tools::getValue('customer_name');
                    if (!$comment->customer_name)
                            $comment->customer_name = pSQL($this->context->customer->firstname.' '.$this->context->customer->lastname);
                    $comment->title = Tools::getValue('title');
                    $comment->grade = 0;
                    
                    if (Configuration::get('JSELLERPRODUCTSCOMMENTS_MODERATE') == 1)
                        $comment->validate = 0;
                    else
                        $comment->validate = 1;
                    
                    $comment->save();

                    $grade_sum = 0;
                    foreach(Tools::getValue('criterion') as $id_seller_product_comment_criterion => $grade)
                    {
                        $grade_sum += $grade;
                        $seller_product_comment_criterion = new SellerProductCommentCriterion($id_seller_product_comment_criterion);
                        if ($seller_product_comment_criterion->id)
                            $seller_product_comment_criterion->addGrade($comment->id, $grade);
                    }

                    if (count(Tools::getValue('criterion')) >= 1)
                    {
                        $comment->grade = $grade_sum / count(Tools::getValue('criterion'));
                        // Update Grade average of comment
                        $comment->save();
                    }
                    
                    if (Configuration::get('JSELLERPRODUCTSCOMMENTS_MODERATE') == 1) {
                    $subject = $this->l('Nouveau commentaire sur').' '.$product->name;
                    $template = 'new_comment';

                    $customer = new Customer($id_customer);
                    
                    $to = Configuration::get('PS_SHOP_EMAIL');
                    $to_name = Configuration::get('PS_SHOP_NAME');
                    $from = $customer->email;
                    $from_name = $customer->firstname.' '.$customer->lastname;

                    $template_vars = array(
                        '{content}' => Tools::getValue('content'),
                        '{product_name}' => $product->name,
                        '{shop_name}' => Configuration::get('PS_SHOP_NAME')
                    );

                    $iso = Language::getIsoById($id_lang);

                    if (file_exists(dirname(__FILE__).'/mails/'.$iso.'/'.$template.'.txt') && file_exists(dirname(__FILE__).'/mails/'.$iso.'/'.$template.'.html')) {
                        Mail::Send(
                            $id_lang,
                            $template,
                            $subject,
                            $template_vars,
                            $to,
                            $to_name,
                            $from,
                            $from_name,
                            null,
                            null,
                            dirname(__FILE__).'/mails/'
                        );
                    }

                    }
                    else {
                        $id_seller = Seller::getSellerByProduct($product->id);
                        $seller = new Seller($id_seller);
                        $customer = new Customer($seller->id_customer);

                        $subject = $this->l('Nouveau commentaire sur').' '.$product->name;
                        $template = 'seller_notification';

                        $to = $customer->email;
                        $to_name = $seller->name;
                        $from = Configuration::get('PS_SHOP_EMAIL');
                        $from_name = Configuration::get('PS_SHOP_NAME');

                        $template_vars = array(
                            '{content}' => Tools::getValue('content'),
                            '{product_name}' => $product->name,
                            '{product_link}' => $this->context->link->getProductLink($product).'#product_questions_block',
                            '{shop_name}' => Configuration::get('PS_SHOP_NAME')
                        );

                        $iso = Language::getIsoById($this->context->language->id);

                        if (file_exists(dirname(__FILE__).'/mails/'.$iso.'/'.$template.'.txt') && file_exists(dirname(__FILE__).'/mails/'.$iso.'/'.$template.'.html')) {
                            Mail::Send(
                                $this->context->language->id,
                                $template,
                                $subject,
                                $template_vars,
                                $to,
                                $to_name,
                                $from,
                                $from_name,
                                null,
                                null,
                                dirname(__FILE__).'/mails/'
                            );
                        } 
                    }
                    
                    $this->context->smarty->assign('confirmation_add', 1);
                }
                else
                {
                    $errors[] = $this->l('Please wait before posting another comment', 'default').' '.Configuration::get('JSELLERPRODUCTSCOMMENTS_MINIMAL_TIME').' '.$module_instance->l('seconds before posting a new comment', 'default');
                }
            }
            else {
                $this->context->smarty->assign('errors', $errors);
            }
        }
        
        $id_guest = (!$id_customer = (int)$this->context->cookie->id_customer) ? (int)$this->context->cookie->id_guest : false;
        $customerComment = SellerProductComment::getByCustomer((int)(Tools::getValue('id_product')), (int)$this->context->cookie->id_customer, true, (int)$id_guest);
        
        $averages = SellerProductComment::getAveragesByProduct((int)Tools::getValue('id_product'), $this->context->language->id);
        $averageTotal = 0;
        foreach ($averages as $average)
                $averageTotal += (float)($average);
        $averageTotal = count($averages) ? ($averageTotal / count($averages)) : 0;

        //$product = $this->context->controller->getProduct();
        $product = new Product((int)Tools::getValue('id_product'), false, $this->context->language->id, $this->context->shop->id);
        $image = Product::getCover((int)Tools::getValue('id_product'));
        $cover_image = $this->context->link->getImageLink($product->link_rewrite, $image['id_image'], 'medium_default');

        $back_url = $this->context->link->getProductLink($product);        

        $this->context->smarty->assign(array(
                'back_url' => $back_url,
                'logged' => $this->context->customer->isLogged(true),
                'action_url' => '',
                'product_name' => $product->name,
                'product_description_short' => $product->description_short,
                'comments' => SellerProductComment::getByProduct((int)Tools::getValue('id_product'), 1, null, $this->context->cookie->id_customer),
                'criterions' => SellerProductCommentCriterion::getByProduct((int)Tools::getValue('id_product'), $this->context->language->id),
                'averages' => $averages,
                'seller_product_comment_path' => $this->_path,
                'averageTotal' => $averageTotal,
                'id_seller_product_comment_form' => (int)Tools::getValue('id_product'),
                'id_product' => (int)Tools::getValue('id_product'),
                'productcomment_cover' => (int)Tools::getValue('id_product').'-'.(int)$image['id_image'],
                'productcomment_cover_image' => $cover_image,
                'mediumSize' => Image::getSize(ImageType::getFormatedName('medium')),
                'nbComments' => (int)SellerProductComment::getCommentNumber((int)Tools::getValue('id_product')),
                'moderation_active' => (int)Configuration::get('JSELLERPRODUCTSCOMMENTS_MODERATE')
        ));        
        
        return ($this->display(__FILE__, 'product-footer.tpl'));
    }

    public function hookDisplayFooter($params)
    {
        if (Configuration::get('MARKETPLACE_SHOW_MANAGE_ORDERS') == 1 || Configuration::get('MARKETPLACE_SHOW_MANAGE_CARRIER') == 1)
            return $this->display(__FILE__, 'footer.tpl');
    }
    
    public function hookDisplayContactAdmin($params)
    {   
            $context = Context::getContext();

            $id_customer = (int)$context->customer->id;

            if ($context->customer->isLogged())
            {
                $id_customer = (int)$context->customer->id;
                $customer = new Customer($id_customer);
                $customer_email = (string)$customer->email;
                $customer_name = (string)$customer->firstname .' ' .(string)$customer->lastname;
            }

            $this->context->smarty->assign(
                array(
                    'email' => Configuration::get('PS_SHOP_EMAIL'),
                    'customer_name' => $customer_name,
                    'logged' => $this->context->customer->isLogged(),
                    'customer_email' => $customer_email
                )
            );

            return $this->display(__FILE__, 'contact_admin.tpl');
    }

    public function hookDisplaySoldAndSell($params)
    {
        
        $id_product = $params['id_product'];

        if(Seller::getSellerByProduct($id_product) > 0)
        {
            $seller_name = Seller::getSellerNameByProduct($id_product);
        }
        else
            $seller_name = Configuration::get('PS_SHOP_NAME');

        $this->context->smarty->assign(
                array(
                    'seller_name' => $seller_name
                )
            );

        return $this->display(__FILE__, 'sold_sell.tpl');           
    }
    public function hookDisplayRefundConfirm($params)
    {
        $context = Context::getContext();
        $id_order = (int)$params['order'];
        $order = new Order($id_order);

        $this->context->smarty->assign(
                array(
                    'order' => $order,
                    'sign' => $this->context->currency->sign,
                )
            );

        return $this->display(__FILE__, 'refund_order.tpl');
    }
    public function hookDisplayContactSeller($params)
    {   
            $context = Context::getContext();
            $id_product = isset($params['id_product']) ? (int)$params['id_product'] : 0;
            
            $id_order = isset($params['order']) ? (int)$params['order'] : 0;
            $id_cart = isset($params['id_cart']) ? (int)$params['id_cart'] : 0;
            $show_seller = isset($params['show_seller']) ? (int)$params['show_seller'] : 0;

            if(isset($params['titre']))
                $titre = $this->l((string)$params['titre']);

            if(!isset($titre))
                $titre = "";

            $id_customer = (int)$context->customer->id;

            if ($context->customer->isLogged())
            {
                $id_customer = (int)$context->customer->id;
                $customer = new Customer($id_customer);
                $customer_email = (string)$customer->email;
            }

            if($id_product)            
                $seller = Seller::getSellerInfosByProduct($id_product);
            

            if(isset($id_order))
                $seller = SellerOrder::getSellerByOrder($id_order,1);

            if(!is_array($seller))
                $seller = array('email' => Configuration::get('PS_SHOP_EMAIL') );

            $this->context->smarty->assign(
                array(
                    'email' => $seller['email'],
                    'seller_name' => '',//$seller['name'],
                    'logged' => $this->context->customer->isLogged(),
                    'customer_email' => $customer_email,
                    'id_order' => $id_order,
                    'titre' => $titre,
                    'id_product' => $id_product,
                    'id_cart' => $id_cart,
                    'show_seller' => $show_seller,
                    'active_incart' => SellerIncidence::getMessageCountByProd($id_product,$id_cart),
                )
            );

            return $this->display(__FILE__, 'contact_seller.tpl');
    }

    public function sendCommision($params) 
    {     
        if (Validate::isLoadedObject($params['newOrderStatus']))
            $id_order = $params['id_order'];
        else
            $id_order = $params['order']->id;
        
        $order = new Order($id_order);
        
        //mirar si el pedido tiene comisiones
        $order_has_commissions = SellerCommisionHistory::getCommisionHistoryByOrder($id_order, $this->context->language->id, $this->context->shop->id);
        if ($order_has_commissions) {
            //reactivar comisiones
            SellerCommisionHistory::changeStateCommissionsByOrder($id_order, 'pending');
        }
        else {
            $this->createCommissionsForProducts($order);
        }  
    }
    
    public function createCommissionsForProducts($order) {
        $products = $order->getProducts();
        foreach ($products as $p) {
            $id_seller = Seller::getSellerByProduct($p['product_id']);            
            if ($id_seller) {
                $seller = new Seller($id_seller);
                if ($seller->active == 1) {
                    //si quiere que el vendedor asuma la tasa de impuestos
                    if (Configuration::get('MARKETPLACE_TAX_COMMISSION') != 0) {
                        $unit_price = $p['unit_price_tax_incl'];
                        $total_price = $p['total_price_tax_incl'];
                    }
                    else {
                        $unit_price = $p['unit_price_tax_excl'];
                        $total_price = $p['total_price_tax_excl'];
                    }
                    
                    $sch = new SellerCommisionHistory();
                    $sch->id_order = $order->id;
                    $sch->id_product = $p['product_id'];
                    $sch->product_name = $p['product_name'];
                    $sch->id_seller = $id_seller;
                    $sch->id_shop = $this->context->shop->id;
                    $sch->price = $unit_price;
                    $sch->quantity = (int)$p['product_quantity'];
                    
                    $commision = (int)SellerCommision::getCommisionBySeller($id_seller);

                    $sch->commision = (float)number_format(($total_price * $commision) / 100,1);
                    
                    $sch->id_seller_commision_history_state = SellerCommisionHistoryState::getIdByReference('pending');
                    $sch->add(); 

                    $this->createFixedCommission($order, $id_seller);                    
                    $this->notifyNewOrderToSeller($order, $seller, $sch->id_product);   
                }
            }  
        } 
        
        $this->createCommissionForShipping($order, $id_seller);
        $this->createCommissionForDiscounts($order, $id_seller);
    }
    
    public function notifyForQuestionCart($id_order)
    {

        $order = new Order((int)$id_order);

        $seller = SellerOrder::getSellerByOrder($id_order,1);
        
        $id_seller = $seller['id_seller'];
        $seller = new Seller($id_seller);
        
        $messages = SellerIncidence::getMessagesByCart((int)$order->id_cart);

        if($messages)
        {
            foreach ($messages as $message) 
            {
                $incidence = new SellerIncidence($message['id_seller_incidence']);

                $incidenceMessage = new SellerIncidenceMessage($message['id_seller_incidence_message']);
            
                $id_seller_email = false;
                $to = $seller->email;
                $to_name = $seller->name;
                $from = Configuration::get('MARKETPLACE_SEND_ADMIN');
                $from_name = Configuration::get('PS_SHOP_NAME');
                $template = 'base';        
                
                //$order = new Order($incidence->id_order); 
                $reference = 'new-incidence';
                if( Configuration::get('MARKETPLACE_MODERATE_MESSAGE') ){
                    $to = Configuration::get('MARKETPLACE_SEND_ADMIN');
                    $to_name = "ADMIN";
                    $reference = 'new-incidence-validation';   
                }
                $vars = array("{shop_name}", "{order_reference}", "{incidence_reference}", "{description}");
                $values = array(Configuration::get('PS_SHOP_NAME'), $order->reference, $incidence->reference, nl2br($incidenceMessage->description));
                
                
                $id_seller_email = SellerEmail::getIdByReference($reference);

                if ($id_seller_email) {
                    $seller_email = new SellerEmail($id_seller_email, $seller->id_lang);
                    $subject_var = $seller_email->subject; 
                    $subject_value = str_replace($vars, $values, $subject_var);
                    $content_var = $seller_email->content;
                    $content_value = str_replace($vars, $values, $content_var);

                    $template_vars = array(
                        '{content}' => $content_value,
                        '{shop_name}' => Configuration::get('PS_SHOP_NAME')
                    );

                    $iso = Language::getIsoById($seller->id_lang);

                    if (file_exists(dirname(__FILE__).'/mails/'.$iso.'/'.$template.'.txt') && file_exists(dirname(__FILE__).'/mails/'.$iso.'/'.$template.'.html')) {
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
                            dirname(__FILE__).'/mails/'
                        );

                        $seller_email = new SellerEmail($id_seller_email, $seller->id_lang);
                        $subject_var = $seller_email->subject; 
                        $subject_value = str_replace($vars, $values, $subject_var);
                        $content_var = $seller_email->content;
                        $content_value = str_replace($vars, $values, $content_var);

                        $template_vars = array(
                            '{content}' => $content_value,
                            '{shop_name}' => Configuration::get('PS_SHOP_NAME')
                        );
                    
                        $to = Configuration::get('MARKETPLACE_SEND_ADMIN');
                        $to_name = "ADMIN";
                        $reference = 'new-incidence-validation';

                        //$merchant_mails = explode("\n", Configuration::get('MA_MERCHANT_MAILS'));
                        //foreach ($merchant_mails as $merchant_mail)
                        //{
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
                                dirname(__FILE__).'/mails/'
                            );
                        //}

                    }
                }
            }

            /*$query = 'UPDATE `'._DB_PREFIX_.'seller_incidence` SET `id_order` = '. (int)$order->id .',`id_cart` = 0 WHERE `id_cart` ='. (int)$order->id_cart ;

            Db::getInstance()->executeS($query);*/

            return true;       
        }

        return false;
    }

    public function notifyNewOrderToSeller($order, $seller, $id_product) {
        // send mail if order payement is not cheque
        if($order->current_state > 1)
        {
            if (Configuration::get('MARKETPLACE_SEND_PRODUCT_SOLD') == 1) {
                $product = new Product($id_product, false, $seller->id_lang, $this->context->shop->id);
                $id_seller_email = false;
                $to = $seller->email;
                $to_name = $seller->name;
                $from = Configuration::get('PS_SHOP_EMAIL');
                $from_name = Configuration::get('PS_SHOP_NAME');
                
                $template = 'base';
                $reference = 'new-order';
                $id_seller_email = SellerEmail::getIdByReference($reference);

                if ($id_seller_email) {
                    $seller_email = new SellerEmail($id_seller_email, $seller->id_lang);
                    $vars = array("{shop_name}", "{seller_name}", "{product_name}", "{order_reference}","{order_id}");
                    $values = array(Configuration::get('PS_SHOP_NAME'), $seller->name, $product->name, $order->reference,$order->id);
                    $subject_var = $seller_email->subject; 
                    $subject_value = str_replace($vars, $values, $subject_var);
                    $content_var = $seller_email->content;
                    $content_value = str_replace($vars, $values, $content_var);

                    $template_vars = array(
                        '{content}' => $content_value,
                        '{shop_name}' => Configuration::get('PS_SHOP_NAME')
                    );

                    $iso = Language::getIsoById($seller->id_lang);

                    if (file_exists(dirname(__FILE__).'/mails/'.$iso.'/'.$template.'.txt') && file_exists(dirname(__FILE__).'/mails/'.$iso.'/'.$template.'.html')) {
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
                            dirname(__FILE__).'/mails/'
                        );
                    }
                }
            }
        }
    }
    
    public function notifyNewOrderChequeToSeller($order, $seller, $id_product) {
        // send mail if order payement is not cheque
        //if($order->current_state > 1)
        //{
            if (Configuration::get('MARKETPLACE_SEND_PRODUCT_SOLD') == 1) {
                $product = new Product($id_product, false, $seller->id_lang, $this->context->shop->id);
                $id_seller_email = false;
                $to = $seller->email;
                $to_name = $seller->name;
                $from = Configuration::get('PS_SHOP_EMAIL');
                $from_name = Configuration::get('PS_SHOP_NAME');
                
                $template = 'base';
                $reference = 'new-order';
                $id_seller_email = SellerEmail::getIdByReference($reference);

                if ($id_seller_email) {
                    $seller_email = new SellerEmail($id_seller_email, $seller->id_lang);
                    $vars = array("{shop_name}", "{seller_name}", "{product_name}", "{order_reference}","{order_id}");
                    $values = array(Configuration::get('PS_SHOP_NAME'), $seller->name, $product->name, $order->reference,$order->id);
                    $subject_var = $seller_email->subject; 
                    $subject_value = str_replace($vars, $values, $subject_var);
                    $content_var = $seller_email->content;
                    $content_value = str_replace($vars, $values, $content_var);

                    $template_vars = array(
                        '{content}' => $content_value,
                        '{shop_name}' => Configuration::get('PS_SHOP_NAME')
                    );

                    $iso = Language::getIsoById($seller->id_lang);

                    if (file_exists(dirname(__FILE__).'/mails/'.$iso.'/'.$template.'.txt') && file_exists(dirname(__FILE__).'/mails/'.$iso.'/'.$template.'.html')) {
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
                            dirname(__FILE__).'/mails/'
                        );
                    }
                }
            }
        //}
    }

    public function createCommissionForShipping($order, $id_seller) {
        if (Configuration::get('MARKETPLACE_SHIPPING_COMMISSION') == 1 && $order->total_shipping_tax_excl > 0) { 
            if (Configuration::get('MARKETPLACE_TAX_COMMISSION'))
            {
                $commision = (int)SellerCommision::getCommisionBySeller($id_seller);

                $total_shipping = (float)($order->total_shipping_tax_incl);// * $commision) / 100;

                //$total_shipping = $order->total_shipping_tax_incl;
            }
            else
                $total_shipping = $order->total_shipping_tax_excl;
            
            $sch = new SellerCommisionHistory();
            $sch->id_order = $order->id;
            $sch->id_product = 0;
            $sch->product_name = $this->l('Shipping cost for').' '.$order->reference;
            $sch->id_seller = $id_seller;
            $sch->id_shop = $this->context->shop->id;
            $sch->price = $total_shipping;
            $sch->quantity = 1;
            $sch->commision = (float)($total_shipping * $commision) / 100;
            $sch->id_seller_commision_history_state = SellerCommisionHistoryState::getIdByReference('pending');
            $sch->add(); 
        }
    }
    
    public function createFixedCommission($order, $id_seller) {
        if (Configuration::get('MARKETPLACE_FIXED_COMMISSION') > 0) {        
            if (!SellerCommisionHistory::getFixedCommissionOfSellerInOrder($id_seller, $order->id)) {
                $sch = new SellerCommisionHistory();
                $sch->id_order = $order->id;
                $sch->id_product = 0;
                $sch->product_name = $this->l('Fixed commission for sale').' '.$order->reference;
                $sch->id_seller = $id_seller;
                $sch->id_shop = $this->context->shop->id;
                $sch->price = -Configuration::get('MARKETPLACE_FIXED_COMMISSION');
                $sch->quantity = 1;
                $sch->commision = - Configuration::get('MARKETPLACE_FIXED_COMMISSION');
                $sch->id_seller_commision_history_state = SellerCommisionHistoryState::getIdByReference('pending');
                $sch->add(); 
            }
        }
    }
    
    public function createCommissionForDiscounts($order, $id_seller) {
        if ($order->total_discounts > 0) {             
            $sch = new SellerCommisionHistory();
            $sch->id_order = $order->id;
            $sch->id_product = 0;
            $sch->product_name = $this->l('Total discounts').' '.$order->reference;
            $sch->id_seller = $id_seller;
            $sch->id_shop = $this->context->shop->id;
            $sch->price = -$order->total_discounts;
            $sch->quantity = 1;
            $sch->commision = -$order->total_discounts;
            $sch->id_seller_commision_history_state = SellerCommisionHistoryState::getIdByReference('pending');
            $sch->add(); 
        }
    }
    
    public function hookActionValidateOrder($params) 
    {
        if (Configuration::get('MARKETPLACE_COMMISIONS_ORDER') == 1) {
            $this->sendCommision($params);
        }

        /*$order = $params['order'];

        $query = 'UPDATE `'._DB_PREFIX_.'seller_incidence` SET `id_order` = '. (int)$order->id .',`id_cart` = 0 WHERE `id_cart` ='. (int)$order->id_cart ;

        Db::getInstance()->executeS($query);*/

    }
    
    public function hookActionOrderStatusPostUpdate($params) 
    {
        if (Configuration::get('MARKETPLACE_COMMISIONS_STATE') == 1 && Configuration::get('MARKETPLACE_ORDER_STATE') == $params['newOrderStatus']->id) {
            $this->sendCommision($params);
        }
        else {
            
            //update history commissions
            $states = OrderState::getOrderStates($this->context->language->id);
            $cancel_commissions = false;
            foreach ($states as $state) {
                if (Configuration::get('MARKETPLACE_CANCEL_COMMISSION_'.$state['id_order_state']) == 1 && $params['newOrderStatus']->id == $state['id_order_state'])
                    $cancel_commissions = true;
            }

            //si toca cancelar comisiones
            if ($cancel_commissions) 
                SellerCommisionHistory::changeStateCommissionsByOrder($params['id_order'], 'cancel');
        }

        $order = new Order((int)$params['id_order']);
        if(($params['newOrderStatus']->id > 1 && $params['newOrderStatus']->id < 6) || $params['newOrderStatus']->id == 12)
        {            
            $this->notifyForQuestionCart($order->id);
            $query = 'UPDATE `'._DB_PREFIX_.'seller_incidence` SET `id_order` = '. (int)$order->id .',`id_cart` = 0 WHERE `id_cart` ='. (int)$order->id_cart ;

            Db::getInstance()->execute($query);            
        }
    }
    
    public function hookActionProductDelete($params) 
    {
        $id_product = (int)Tools::getValue('id_product');
        Db::getInstance()->Execute('DELETE FROM `'._DB_PREFIX_.'seller_product` WHERE id_product = '.$id_product);
        $this->_clearCache('*');
    }
    
    public function hookDisplayHeader($params){        

        if( Tools::getValue('RegisterHook') ){
            $this->registerHook('actionAuthentication');
        }

        //$this->context->controller->addJS($this->_path.'views/js/global.js');

        $this->context->controller->addJqueryPlugin('fancybox');

        $this->context->controller->addCSS($this->_path.'views/css/font-awesome.css', 'all');
        
        $this->context->controller->addCSS($this->_path.'views/css/marketplace.css', 'all');

        $this->context->controller->addJS($this->_path.'views/js/jquery-confirm.min.js');

        $this->context->controller->addJS($this->_path.'views/js/notify.min.js');
        
        $this->context->controller->addCSS($this->_path.'views/css/jquery-confirm.min.css', 'all');
        
        // PRODUCT COMPARATOR                
        $this->context->controller->addCSS($this->_path.'views/css/sellercomparator.css', 'all');

        // PRODUCT SELLER COMMENTS
        $this->context->controller->addJS($this->_path.'views/js/jquery.rating.pack.js');
        
        $this->context->controller->addJS($this->_path.'views/js/contact_admin.js');

        $this->context->controller->addJS($this->_path.'views/js/productcomments.js');

        $this->context->controller->addJS($this->_path.'views/js/productprice.js');

        $this->context->controller->addCSS($this->_path.'views/css/jquery.rating.css', 'all');
        $this->context->controller->addCSS($this->_path.'views/css/productcomments.css', 'all');

        //if (Configuration::get('MARKETPLACE_THEME') == 'default') 
        //$this->context->controller->addCSS($this->_path.'views/css/default.css', 'all');
        $this->context->controller->registerStylesheet('modules-marketplace', 'modules/'.$this->name.'/views/css/default.css', ['media' => 'all', 'priority' => 150]);
        
        if (isset($this->context->controller->page_name)) {
            
            if($this->context->controller->page_name == 'module-marketplace-sellerproductlist')
            {
                $this->context->controller->addJS($this->_path.'views/js/sellerproductlist.js');
            }

            if ($this->context->controller->page_name == 'module-marketplace-sellerholidays' || $this->context->controller->page_name == 'module-marketplace-sellerofferview' || $this->context->controller->page_name == 'module-marketplace-sellerofferadd')
            {
                $this->context->controller->addCSS($this->_path.'views/css/plugins/bootstrap/bootstrap.min.css', 'all');

                $this->context->controller->addJS($this->_path.'views/js/plugins/bootstrap/bootstrap.min.js');
            }

            if ($this->context->controller->page_name == 'module-marketplace-dashboard' )
            {
                //$this->context->controller->addJS($this->_path.'views/js/plugins/jquery/jquery.min.js');

                $this->context->controller->addCSS($this->_path.'views/css/plugins/mcustomscrollbar/jquery.mCustomScrollbar.css', 'all');
                $this->context->controller->addCSS($this->_path.'views/css/plugins/bootstrap/bootstrap.min.css', 'all');

                $this->context->controller->addCSS($this->_path.'views/css/plugins/theme-default.css', 'all');

                //themes css
                //$this->context->controller->addCSS($this->_path.'views/css/marketplace.css', 'all');
                // themes js
                

                $this->context->controller->addJS($this->_path.'views/js/plugins/bootstrap/bootstrap.min.js');

                // END PLUGINS -->

                // START THIS PAGE PLUGINS-->       
                $this->context->controller->addJS($this->_path.'views/js/plugins/icheck/icheck.min.js');

                $this->context->controller->addJS($this->_path.'views/js/plugins/mcustomscrollbar/jquery.mCustomScrollbar.min.js');

                $this->context->controller->addJS($this->_path.'views/js/plugins/scrolltotop/scrolltopcontrol.js');              


                $this->context->controller->addJS($this->_path.'views/js/plugins/bootstrap/bootstrap-datepicker.js');

                $this->context->controller->addJS($this->_path.'views/js/plugins/owl/owl.carousel.min.js');

                $this->context->controller->addJS($this->_path.'views/js/plugins/moment.min.js');               
                if(Context::getContext()->language->iso_code == 'fr')
                    $this->context->controller->addJS($this->_path.'views/js/plugins.js');
                else
                    $this->context->controller->addJS($this->_path.'views/js/plugins.en.js');
                $this->context->controller->addJS($this->_path.'views/js/actions.js');                

                //end themes js
            }

            // searchproduct
            if($this->context->controller->page_name == 'module-marketplace-searchproduct')
            {
                $this->context->controller->registerJavascript('eager-searchbar-jquery.autocomplete', 'modules/'.$this->name.'/views/libs/jquery.autocomplete.min.js', ['position' => 'bottom', 'priority' => 150]);
                $this->context->controller->registerJavascript('eager-searchbar', 'modules/'.$this->name.'/views/js/eager_searchbar.js', ['position' => 'bottom', 'priority' => 150]);
                $this->context->controller->registerJavascript('eager-searchbar-hogan', 'modules/'.$this->name.'/views/libs/hogan-3.0.1.js', ['position' => 'bottom', 'priority' => 150]);
                $this->context->controller->registerStylesheet('eager-searchbar-styles', 'modules/'.$this->name.'/views/css/eager.css', ['position' => 'bottom', 'priority' => 0]);
                //$this->context->controller->registerStylesheet('eager-searchbar-styles-template', 'modules/'.$this->name.'/views/css/'.$this->stylesheet, ['position' => 'bottom', 'priority' => 0]);

            }
            //addseller and editseller
            if ($this->context->controller->page_name == 'module-marketplace-addseller' || 
                    $this->context->controller->page_name == 'module-marketplace-editseller') 
            {

                /*$this->context->controller->addJS(__PS_BASE_URI__ . 'js/tiny_mce/tiny_mce.js');*/

                $this->context->controller->addCSS($this->_path.'views/css/plugins/summernote/summernote.css', 'all');

                /*if(Configuration::get('PS_SSL_ENABLED'))
                    $this->context->controller->addJS('https://cdn.tinymce.com/4/tinymce.min.js', 'all');
                else
                    $this->context->controller->addJS('http://cdn.tinymce.com/4/tinymce.min.js', 'all');*/

                $this->context->controller->addJqueryPlugin('fancybox');
                $this->context->controller->addJS($this->_path.'views/js/plugins/summernote/summernote.js', 'all');
                $this->context->controller->addJS($this->_path.'views/js/addseller.js', 'all');
            }
            
            //sellerprofile
            if ($this->context->controller->page_name == 'module-marketplace-sellerprofile' || $this->context->controller->page_name == 'module-marketplace-sellerprofilevisite') {
                $this->context->controller->addCSS(_THEME_CSS_DIR_.'product_list.css', 'all');
                $this->context->controller->addJS($this->_path.'views/js/sellerproductlist.js', 'all'); 
            }

            //orders
            if ($this->context->controller->page_name == 'module-marketplace-orders') {
                $this->context->controller->addJS($this->_path.'views/js/order.js', 'all');
                
            }

            if ($this->context->controller->page_name == 'module-marketplace-searchproduct' || $this->context->controller->page_name == 'module-marketplace-orders' || $this->context->controller->page_name == 'module-marketplace-sellerorders' || $this->context->controller->page_name == 'module-marketplace-sellermessages' || $this->context->controller->page_name == 'module-marketplace-editseller' || $this->context->controller->page_name == 'module-marketplace-sellerprofile' || $this->context->controller->page_name == 'module-marketplace-sellerpayment' || $this->context->controller->page_name == 'module-marketplace-sellon')
            {
                
                $path_cart = _PS_MODULE_DIR_.'blockcart/';

                $this->context->controller->addCSS($this->_path.'views/css/plugins/mcustomscrollbar/jquery.mCustomScrollbar.css', 'all');
                $this->context->controller->addCSS($this->_path.'views/css/plugins/bootstrap/bootstrap.min.css', 'all');
                $this->context->controller->addCSS($this->_path.'views/css/plugins/theme-default.css', 'all');
                // START THIS PAGE PLUGINS-->

                $this->context->controller->addJqueryPlugin('fancybox');

                //$this->context->controller->addJS($this->_path.'views/js/plugins/bootstrap/bootstrap.min.js');
                if ((int)(Configuration::get('PS_BLOCK_CART_AJAX')))
                {
                    $this->context->controller->addJS($path_cart.'ajax-cart.js');
                    $this->context->controller->addJqueryPlugin(array('scrollTo', 'serialScroll', 'bxslider'));
                }

                $this->context->controller->addJS($this->_path.'views/js/plugins/mcustomscrollbar/jquery.mCustomScrollbar.min.js');

                $this->context->controller->addJS($this->_path.'views/js/plugins/icheck/icheck.min.js');                
                
                $this->context->controller->addJS($this->_path.'views/js/plugins/bootstrap/bootstrap-file-input.js');

                $this->context->controller->addJS($this->_path.'views/js/plugins/bootstrap/bootstrap-select.js');

                
                $this->context->controller->addJS($this->_path.'views/js/plugins.js');
                $this->context->controller->addJS($this->_path.'views/js/actions.js');
            }

            if ($this->context->controller->page_name == 'module-marketplace-sellerproducts')
            {
                $this->context->controller->addJS($this->_path.'views/js/sellerproducts.js');
            }

            //addproduct and editproduct
            if ($this->context->controller->page_name == 'module-marketplace-addproduct' || $this->context->controller->page_name == 'module-marketplace-editproduct' || $this->context->controller->page_name == 'module-marketplace-sellerproducts' || $this->context->controller->page_name == 'module-marketplace-csvproducts') {
                
                $this->context->controller->addCSS($this->_path.'views/css/sellon.css', 'all');

                $this->context->controller->addJqueryPlugin('fancybox');

                //$this->context->controller->addJS($this->_path.'views/js/tinymce/langs/fr_FR.js', 'all');                             
                
                $this->context->controller->addJS($this->_path.'views/js/addproduct.js', 'all'); 
                $this->context->controller->addJS($this->_path.'views/js/attributes.js', 'all');                

                $this->context->controller->addCSS($this->_path.'views/css/plugins/mcustomscrollbar/jquery.mCustomScrollbar.css', 'all');
                $this->context->controller->addCSS($this->_path.'views/css/plugins/bootstrap/bootstrap.min.css', 'all');
                $this->context->controller->addCSS($this->_path.'views/css/plugins/summernote/summernote.css', 'all');

                $this->context->controller->addCSS($this->_path.'views/css/plugins/theme-default.css', 'all');

                // START THIS PAGE PLUGINS-->
                $this->context->controller->addJS($this->_path.'views/js/plugins/summernote/summernote.js', 'all');

                $this->context->controller->addJS($this->_path.'views/js/plugins/mcustomscrollbar/jquery.mCustomScrollbar.min.js');

                $this->context->controller->addJS($this->_path.'views/js/plugins/icheck/icheck.min.js');                
                
                $this->context->controller->addJS($this->_path.'views/js/plugins/bootstrap/bootstrap-file-input.js');

                $this->context->controller->addJS($this->_path.'views/js/plugins/bootstrap/bootstrap-select.js');

                
                $this->context->controller->addJS($this->_path.'views/js/plugins.js');
                $this->context->controller->addJS($this->_path.'views/js/actions.js');  
                

                //end themes js
            }
            
            //addproduct and editproduct sellon
            if ($this->context->controller->page_name == 'module-marketplace-sellon') {                

                $this->context->controller->addCSS($this->_path.'views/css/sellon.css', 'all');

                $this->context->controller->addJqueryPlugin('fancybox');                

                $this->context->controller->addJS($this->_path.'views/js/sellon.js', 'all');

                $this->context->controller->addJS($this->_path.'views/js/attributes.js', 'all');
            }

            if ($this->context->controller->page_name == 'module-marketplace-addcarrier') {
                $this->context->controller->addCSS($this->_path.'views/css/addcarrier.css', 'all'); 
                $this->context->controller->addJS($this->_path.'views/js/addcarrier.js', 'all');
            }

            if ($this->context->controller->page_name == 'module-marketplace-editcarrier') {
                $this->context->controller->addCSS($this->_path.'views/css/addcarrier.css', 'all'); 
                $this->context->controller->addJS($this->_path.'views/js/addcarrier.js', 'all');
            }
            
            if ($this->context->controller->page_name == 'module-marketplace-dashboard') {
                $this->context->controller->addJS($this->_path.'views/js/Chart.bundle.min.js', 'all');
                $this->context->controller->addJS($this->_path.'views/js/dashboard.js', 'all');
            }
            
            if ($this->context->controller->page_name == 'module-marketplace-sellercomments') {
                $this->context->controller->addCSS($this->_path.'views/css/sellercomments.css', 'all'); 
                //$this->context->controller->addCSS($this->_path.'views/css/jquery.rating.css', 'all'); 
                $this->context->controller->addJS($this->_path.'views/js/jquery.rating.pack.js', 'all');
                $this->context->controller->addJS($this->_path.'views/js/sellercomments.js', 'all');
            }

            if ($this->context->controller->page_name == 'module-marketplace-sellercommentsorder') {
                $this->context->controller->addCSS($this->_path.'views/css/sellercomments.css', 'all'); 
                //$this->context->controller->addCSS($this->_path.'views/css/jquery.rating.css', 'all'); 
                $this->context->controller->addJS($this->_path.'views/js/jquery.rating.pack.js', 'all');
                $this->context->controller->addJS($this->_path.'views/js/sellercomments.js', 'all');
            }
            
            if ($this->context->controller->page_name == 'module-marketplace-sellermessages' || $this->context->controller->page_name == 'module-marketplace-contactseller') {
                $this->context->controller->addJS($this->_path.'views/js/sellermessages.js', 'all');
            }
        }
        
        /*if (Configuration::get('MARKETPLACE_SHOW_MANAGE_ORDERS') == 1 || Configuration::get('MARKETPLACE_SHOW_MANAGE_CARRIER') == 1)
            $this->context->controller->addJS($this->_path.'views/js/addsellerproductcart.js', 'all');*/
    }
    
    public function hookBackOfficeHeader($params) 
    {
        $this->context->controller->addCSS($this->_path.'views/css/back.css');
    }
    
    public function hookModuleRoutes($params) 
    {
        $my_link = array(
            'marketplace_seller_rule' => array(
                'controller' => 'sellerprofile',
                'rule' =>       $this->l('marketplace').'/{id_seller}_{link_rewrite}',
                'keywords' => array(
                    'id_seller' =>  array('regexp' => '[_a-zA-Z0-9-\pL]*', 'param' => 'id_seller'),
                    'link_rewrite' => array('regexp' => '[_a-zA-Z0-9-\pL]*'),
                ),
                'params' => array(
                    'fc' => 'module',
                    'module' => 'marketplace',
                ),
            ),
            'marketplace_sellerproductlist_rule' => array(
                'controller' => 'sellerproductlist',
                'rule' =>       $this->l('marketplace').'/{id_seller}_{link_rewrite}/'.$this->l('products').'/',
                'keywords' => array(
                    'id_seller' =>  array('regexp' => '[_a-zA-Z0-9-\pL]*', 'param' => 'id_seller'),
                    'link_rewrite' => array('regexp' => '[_a-zA-Z0-9-\pL]*'),
                ),
                'params' => array(
                    'fc' => 'module',
                    'module' => 'marketplace',
                ),
            ),
            'marketplace_sellers_rule' => array(
                'controller' => 'sellers',
                'rule' =>       $this->l('marketplace').'/'.$this->l('sellers').'/',
                'keywords' => array(),
                'params' => array(
                    'fc' => 'module',
                    'module' => 'marketplace',
                ),
            ),
        );

        return $my_link;

    }
    
    public static function getJmarketPlaceUrl() 
    {
        $ssl_enable = Configuration::get('PS_SSL_ENABLED');
        $id_lang = (int)Context::getContext()->language->id;
        $id_shop = (int)Context::getContext()->shop->id;
        $rewrite_set = (int)Configuration::get('PS_REWRITING_SETTINGS');
        $ssl = null;
        static $force_ssl = null;
        
        if ($ssl === null)  
        {
            if ($force_ssl === null)
                $force_ssl = (Configuration::get('PS_SSL_ENABLED') && Configuration::get('PS_SSL_ENABLED_EVERYWHERE'));

            $ssl = $force_ssl;
        }
        
        if (Configuration::get('PS_MULTISHOP_FEATURE_ACTIVE') && $id_shop !== null)
            $shop = new Shop($id_shop);
        else
            $shop = Context::getContext()->shop;

        $base = (($ssl && $ssl_enable) ? 'https://'.$shop->domain_ssl : 'http://'.$shop->domain);   
        $langUrl = Language::getIsoById($id_lang).'/';
        
        if ((!$rewrite_set && in_array($id_shop, array((int)Context::getContext()->shop->id,  null))) || 
                !Language::isMultiLanguageActivated($id_shop) || 
                !(int)Configuration::get('PS_REWRITING_SETTINGS', null, null, $id_shop))
            $langUrl = '';

        return $base.$shop->getBaseURI().$langUrl;
    }
    
    public static function getmarketplaceLink($rewrite = 'marketplace', $params = null, $id_lang = null) 
    {
        $url = marketplace::getJmarketPlaceUrl();
        $dispatcher = Dispatcher::getInstance();
        
        if($params != null)
            return $url.$dispatcher->createUrl($rewrite, $id_lang, $params);
        else
            return $url.$dispatcher->createUrl($rewrite); 
    }

    public function initCategoriesAssociation($id_root = null, $id_criterion = 0)
    {
        if (is_null($id_root))
            $id_root = Configuration::get('PS_ROOT_CATEGORY');

        $id_shop = (int)Tools::getValue('id_shop');
        $shop = new Shop($id_shop);
        if ($id_criterion == 0)
                $selected_cat = array();
        else
        {
                $pdc_object = new SellerProductCommentCriterion($id_criterion);
                $selected_cat = $pdc_object->getCategories();
        }

        if (Shop::getContext() == Shop::CONTEXT_SHOP && Tools::isSubmit('id_shop'))
                $root_category = new Category($shop->id_category);
        else
                $root_category = new Category($id_root);
        $root_category = array('id_category' => $root_category->id, 'name' => $root_category->name[$this->context->language->id]);

        $helper = new Helper();
        return $helper->renderCategoryTree($root_category, $selected_cat, 'categoryBox', false, true);
    }


}
?>
