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

class Seller extends ObjectModel
{
    public $id_customer;
    public $id_shop;
    public $id_lang;
    public $name;
    public $shop;
    public $cif;
    public $email;
    public $phone;
    public $fax;
    public $address;
    public $country;
    public $state;
    public $city;
    public $postcode;
    public $description;
    public $link_rewrite;
    public $active;
    public $date_add;
    public $date_upd;

    /**
     * @see ObjectModel::$definition
     */
    public static $definition = array(
        'table' => 'seller',
        'primary' => 'id_seller',
        'multi_shop' => true,
        'fields' => array(
            'id_customer' => array('type' => self::TYPE_INT, 'validate' => 'isunsignedInt', 'required' => false),
            'id_shop' => array('type' => self::TYPE_INT, 'validate' => 'isunsignedInt', 'required' => false),
            'id_lang' => array('type' => self::TYPE_INT, 'validate' => 'isunsignedInt', 'required' => false),
            'name' => array('type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'required' => true),
            'shop' => array('type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'required' => false),
            'cif' => array('type' => self::TYPE_STRING, 'required' => false),
            'email' => array('type' => self::TYPE_STRING, 'validate' => 'isEmail', 'required' => true),
            'phone' => array('type' => self::TYPE_STRING, 'required' => false),
            'fax' => array('type' => self::TYPE_STRING, 'required' => false),
            'address' => array('type' => self::TYPE_STRING, 'required' => false),
            'country' => array('type' => self::TYPE_STRING, 'required' => false),
            'state' => array('type' => self::TYPE_STRING, 'required' => false),
            'city' => array('type' => self::TYPE_STRING, 'required' => false),
            'postcode' => array('type' => self::TYPE_STRING, 'required' => false),
            'description' => array('type' => self::TYPE_HTML, 'lang' => false, 'validate' => 'isCleanHtml'),
            'link_rewrite' => array('type' => self::TYPE_STRING),
            'active' => array('type' => self::TYPE_BOOL, 'validate' => 'isBool'),
            'date_add' => array('type' => self::TYPE_DATE, 'validate' => 'isDateFormat', 'required' => false),
            'date_upd' => array('type' => self::TYPE_DATE, 'validate' => 'isDateFormat', 'required' => false),
        ),
    );
    
    public function add($autodate = true, $null_values = false)
    {
        if (!parent::add($autodate, $null_values))
            return false;
        
        $data_commision = array();
        
        //insert to seller commision
        $data_commision[] = array(
                'id_seller' => (int)$this->id,
                'commision' => (int)Configuration::get('MARKETPLACE_VARIABLE_COMMISSION'),
                'id_shop' => (int)Context::getContext()->shop->id,
        );

	Db::getInstance()->insert('seller_commision', $data_commision);

        return true;
    }
    
    public function delete()
    {
        $result = parent::delete();
        $result = ($this->deleteCommision() && $this->deleteCommisionHistory() && $this->deletePayments() && $this->deleteCarriers() && $this->deleteSellerImage() && $this->deleteSellerProducts());
        return $result;
    }
    
    public function deleteCommision()
    {
        return Db::getInstance()->execute('DELETE FROM `'._DB_PREFIX_.'seller_commision` WHERE `id_seller` = '.(int)$this->id);
    }
    
    public function deleteCommisionHistory()
    {
        return Db::getInstance()->execute('DELETE FROM `'._DB_PREFIX_.'seller_commision_history` WHERE `id_seller` = '.(int)$this->id);
    }
    
    public function deletePayments()
    {
        return Db::getInstance()->execute('DELETE FROM `'._DB_PREFIX_.'seller_payment` WHERE `id_seller` = '.(int)$this->id);
    }
    
    public function deleteCarriers()
    {
        return Db::getInstance()->Execute('DELETE FROM `'._DB_PREFIX_.'seller_carrier` WHERE `id_seller` = '.(int)$this->id); 
    }
    
    public function deleteSellerProducts()
    {
        $products = $this->getIdProducts();
        if ($products) {
            foreach ($products as $p) {
                $product = new Product($p['id_product']);
                $product->delete();
            }
        }
        
        return Db::getInstance()->execute('DELETE FROM `'._DB_PREFIX_.'seller_product` WHERE `id_seller_product` = '.(int)$this->id);
    }
    
    public static function saveSellerImage($file, $id_customer) {
        if ((($file['type'] == "image/pjpeg") || ($file['type'] == "image/jpeg") || ($file['type'] == "image/png")) && ($file['size'] < 1000000)) {

            $files = glob(_PS_IMG_DIR_.'sellers/'.$id_customer.'_*.jpg');

            foreach ($files as $f) {                    

                if (file_exists($f))
                    unlink($f);
                
            }

            move_uploaded_file($file['tmp_name'], _PS_IMG_DIR_.'sellers/'.$id_customer.'_'.time().'.jpg');


            return true;
        }
        
        return false;
    }

    public static function saveSellerRCS($file, $id_customer) {
        $fichiersRCS = glob(_PS_IMG_DIR_.'rcs/'.$id_customer.'.*');
        foreach ($fichiersRCS as $fichierRCS) {
            unlink($fichierRCS);
        }

        $fichiersPID = glob(_PS_IMG_DIR_.'rcs/'.$id_customer.'.*');
        foreach ($fichiersPID as $fichierPID) {
            unlink($fichierPID);
        }
        

        if ((($file['type'] == "image/pjpeg") || ($file['type'] == "image/jpeg") || ($file['type'] == "image/png")) && ($file['size'] < 3145728)) {
            /*if (file_exists(_PS_IMG_DIR_.'rcs/'.$id_customer.'.jpg') ) {
                unlink(_PS_IMG_DIR_.'rcs/'.$id_customer.'.jpg');
            }*/          

            move_uploaded_file($file['tmp_name'], _PS_IMG_DIR_.'rcs/'.$id_customer.'.jpg');
            return true;
        }
        else if ((($file['type'] == "application/pdf")) && ($file['size'] < 3145728)) {
            /*if (file_exists(_PS_IMG_DIR_.'rcs/'.$id_customer.'.pdf') ) {
                unlink(_PS_IMG_DIR_.'rcs/'.$id_customer.'.pdf');
            }*/

            move_uploaded_file($file['tmp_name'], _PS_IMG_DIR_.'rcs/'.$id_customer.'.pdf');
            return true;
        }
        
        return false;
    }

    public static function saveSellerPID($file, $id_customer) {
        if ((($file['type'] == "image/pjpeg") || ($file['type'] == "image/jpeg") || ($file['type'] == "image/png")) && ($file['size'] < 3145728)) {
            if (file_exists(_PS_IMG_DIR_.'pid/'.$id_customer.'.jpg') ) {
                unlink(_PS_IMG_DIR_.'pid/'.$id_customer.'.jpg');
            }

            move_uploaded_file($file['tmp_name'], _PS_IMG_DIR_.'pid/'.$id_customer.'.jpg');
            return true;
        }
        else if ((($file['type'] == "application/pdf")) && ($file['size'] < 3145728)) {
            if (file_exists(_PS_IMG_DIR_.'pid/'.$id_customer.'.pdf') ) {
                unlink(_PS_IMG_DIR_.'pid/'.$id_customer.'.pdf');
            }

            move_uploaded_file($file['tmp_name'], _PS_IMG_DIR_.'pid/'.$id_customer.'.pdf');
            return true;
        }

        
        return false;
    }

    public static function hasRCS($id_customer) {
        if (file_exists(_PS_IMG_DIR_.'rcs/'.$id_customer.'.jpg') || file_exists(_PS_IMG_DIR_.'rcs/'.$id_customer.'.pdf')) 
            return true;
        return false;
    }

    public static function hasPID($id_customer) {
        if (file_exists(_PS_IMG_DIR_.'pid/'.$id_customer.'.jpg') || file_exists(_PS_IMG_DIR_.'pid/'.$id_customer.'.pdf')) 
            return true;
        return false;
    }
    
    public static function hasImage($id_customer) {
         $files = glob(_PS_IMG_DIR_.'sellers/'.$id_customer.'_*.jpg');

        if ($files) 
            return true;
        return false;
    }
    
    public function deleteSellerImage()
    {
        if (file_exists(_PS_IMG_DIR_.'sellers/'.$this->id_customer.'_*.jpg')) {
            unlink(_PS_IMG_DIR_.'sellers/'.$this->id_customer.'_*.jpg');
        }
        return true;
    }
    
    public static function isSeller($id_customer, $id_shop) {
        return Db::getInstance()->getValue('SELECT COUNT(*) FROM '._DB_PREFIX_.'seller WHERE id_customer = '.(int)$id_customer.' AND id_shop ='.(int)$id_shop);
    }
    
    public static function isActiveSeller($id_seller) {
        return Db::getInstance()->getValue('SELECT COUNT(*) FROM '._DB_PREFIX_.'seller WHERE active = 1 AND id_seller = '.(int)$id_seller);
    }
    
    public static function isActiveSellerByCustomer($id_customer) {
        return Db::getInstance()->getValue('SELECT COUNT(*) FROM '._DB_PREFIX_.'seller WHERE active = 1 AND id_customer = '.(int)$id_customer);
    }
    
    public static function getSellerByCustomer($id_customer) {
        $query = 'SELECT id_seller FROM '._DB_PREFIX_.'seller WHERE id_customer = '.(int)$id_customer;
        $id_seller = Db::getInstance()->getValue($query);
        if ($id_seller)
            return $id_seller;
        return false;
    }
    
    public static function getSellers($id_shop, $active=1) {
        $query = 'SELECT s.*,p.active FROM '._DB_PREFIX_.'seller s
        JOIN `'._DB_PREFIX_.'seller_product` sp ON (sp.`id_seller_product` = s.`id_seller`)
        JOIN `'._DB_PREFIX_.'product` p ON (p.`id_product` = sp.`id_product`)
        GROUP BY s.id_seller
        HAVING id_shop = '.(int)$id_shop.' AND s.active='. $active .' AND p.active = 1 ORDER BY name ASC';

        $sellers = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($query);

        if ($sellers)
            return $sellers;
        return false;
    }

    public static function getRandSellers($id_shop = 1) {
        $query = 'SELECT s.*,p.active FROM '._DB_PREFIX_.'seller s
        JOIN `'._DB_PREFIX_.'seller_product` sp ON (sp.`id_seller_product` = s.`id_seller`)
        JOIN `'._DB_PREFIX_.'product` p ON (p.`id_product` = sp.`id_product`)
        GROUP BY s.id_seller
        HAVING id_shop = '.(int)$id_shop.' AND s.active=1 AND p.active=1 ORDER BY RAND() limit 1';

        //$query = 'SELECT * FROM '._DB_PREFIX_.'seller WHERE active = 1 ORDER BY RAND() limit 1';

        $sellers = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($query);
        if ($sellers)
            return $sellers;
        return false;
    }
    
    public static function getFrontSellers($id_shop) {
        $query = 'SELECT * FROM '._DB_PREFIX_.'seller WHERE id_shop ='.(int)$id_shop.' AND active = 1 AND id_seller != 13 ORDER BY name ASC';
        $sellers = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($query);
        if ($sellers)
            return $sellers;
        return false;
    }
    
    public static function getSellerByProduct($id_product) {
        $query = 'SELECT s.id_seller FROM '._DB_PREFIX_.'seller s
                    LEFT JOIN `'._DB_PREFIX_.'seller_product` sp ON (sp.`id_seller_product` = s.`id_seller`) 
                    LEFT JOIN `'._DB_PREFIX_.'product` p ON (sp.`id_product` = p.`id_product`)  
                    WHERE sp.id_product = '.(int)$id_product;
        $id_seller = Db::getInstance()->getValue($query);
        
        if ($id_seller)
            return $id_seller;
        else
            return 0;

        return false;
    }

    public static function getSellerNameByProduct($id_product) {
        $query = 'SELECT s.name FROM '._DB_PREFIX_.'seller s
                    LEFT JOIN `'._DB_PREFIX_.'seller_product` sp ON (sp.`id_seller_product` = s.`id_seller`) 
                    LEFT JOIN `'._DB_PREFIX_.'product` p ON (sp.`id_product` = p.`id_product`)  
                    WHERE sp.id_product = '.(int)$id_product;
        $seller_name = Db::getInstance()->getValue($query);
        if ($seller_name)
            return $seller_name;
        return false;
    }

    public static function getSellerInfosByProduct($id_product) {
        $query = 'SELECT * FROM '._DB_PREFIX_.'seller s
                    LEFT JOIN `'._DB_PREFIX_.'seller_product` sp ON (sp.`id_seller_product` = s.`id_seller`) 
                    LEFT JOIN `'._DB_PREFIX_.'product` p ON (sp.`id_product` = p.`id_product`)  
                    WHERE sp.id_product = '.(int)$id_product;
        $rq = Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow($query);
        if ($rq)
            return $rq;
        return false;
    }


    public static function getSellerNameByOrder($id_order) {
        $query = 'SELECT s.name as seller_name FROM '._DB_PREFIX_.'seller s                    
                    LEFT JOIN `'._DB_PREFIX_.'orders` o ON (o.`id_seller` = s.`id_seller`)  
                    WHERE o.id_order = '.(int)$id_order;
        $seller_name = Db::getInstance()->getValue($query);

        if ($seller_name == "")
            $seller_name = Configuration::get('PS_SHOP_NAME');

        if ($seller_name)
            return $seller_name;
        return false;
    }

    public static function getSellerIdByOrder($id_order) {
        $query = 'SELECT s.id_seller FROM '._DB_PREFIX_.'seller s                    
                    LEFT JOIN `'._DB_PREFIX_.'orders` o ON (o.`id_seller` = s.`id_seller`)  
                    WHERE o.id_order = '.(int)$id_order;
        $seller_id = Db::getInstance()->getValue($query);

        return $seller_id;
    }
    
    public function getIdProducts() {
        $query = 'SELECT p.id_product
                FROM `'._DB_PREFIX_.'product` p
                LEFT JOIN `'._DB_PREFIX_.'seller_product` sp ON (sp.`id_product` = p.`id_product`) 
                WHERE sp.id_seller_product = '.(int)$this->id;
		$rq = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($query);
        if ($rq)
            return $rq;
        return false;
    }
    
    public function getProducts($id_lang, $start, $limit, $order_by, $order_way, $id_category = false, $only_active = false) {
        if ($start < 1) $start = 0;
        $query = 'SELECT p.*, pl.*, i.id_image
                FROM `'._DB_PREFIX_.'product` p
                '.Shop::addSqlAssociation('product', 'p').'
                LEFT JOIN `'._DB_PREFIX_.'product_lang` pl ON (p.`id_product` = pl.`id_product` '.Shop::addSqlRestrictionOnLang('pl').')
                LEFT JOIN `'._DB_PREFIX_.'manufacturer` m ON (m.`id_manufacturer` = p.`id_manufacturer`)
                LEFT JOIN `'._DB_PREFIX_.'supplier` s ON (s.`id_supplier` = p.`id_supplier`)'.
                ($id_category ? 'LEFT JOIN `'._DB_PREFIX_.'category_product` c ON (c.`id_product` = p.`id_product`)' : '').'
                LEFT JOIN `'._DB_PREFIX_.'seller_product` sp ON (sp.`id_product` = p.`id_product`) 
                LEFT JOIN `'._DB_PREFIX_.'image` i ON (i.`id_product` = p.`id_product` AND cover = 1)
                WHERE pl.`id_lang` = '.(int)$id_lang.
                        ($only_active ? ' AND product_shop.`active` = 1' : '').'
                            and sp.id_seller_product = '.(int)$this->id.'
                ORDER BY '.pSQL($order_by).' '.pSQL($order_way).' LIMIT '.(int)$start.','.(int)$limit;
		$rq = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($query);
        if (Product::getProductsProperties($id_lang, $rq))
            return Product::getProductsProperties($id_lang, $rq);
        return false;
    }
    
    public function find($search_query, $id_lang, $start, $limit, $order_by, $order_way, $id_category = false, $only_active = false) {
        if ($start < 1) $start = 0;
        $query = 'SELECT p.*, pl.*, i.id_image
                FROM `'._DB_PREFIX_.'product` p
                '.Shop::addSqlAssociation('product', 'p').'
                LEFT JOIN `'._DB_PREFIX_.'product_lang` pl ON (p.`id_product` = pl.`id_product` '.Shop::addSqlRestrictionOnLang('pl').')
                LEFT JOIN `'._DB_PREFIX_.'manufacturer` m ON (m.`id_manufacturer` = p.`id_manufacturer`)
                LEFT JOIN `'._DB_PREFIX_.'supplier` s ON (s.`id_supplier` = p.`id_supplier`)'.
                ($id_category ? 'LEFT JOIN `'._DB_PREFIX_.'category_product` c ON (c.`id_product` = p.`id_product`)' : '').'
                LEFT JOIN `'._DB_PREFIX_.'seller_product` sp ON (sp.`id_product` = p.`id_product`) 
                LEFT JOIN `'._DB_PREFIX_.'image` i ON (i.`id_product` = p.`id_product` AND cover = 1)
                WHERE pl.`id_lang` = '.(int)$id_lang.
                        ($only_active ? ' AND product_shop.`active` = 1' : '').'
                            and sp.id_seller_product = '.(int)$this->id.'
                            AND pl.name LIKE "%'.$search_query.'%"
                ORDER BY '.pSQL($order_by).' '.pSQL($order_way).' LIMIT '.(int)$start.','.(int)$limit;
		$rq = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($query);
        if (Product::getProductsProperties($id_lang, $rq))
            return Product::getProductsProperties($id_lang, $rq);
        return false;
    }
    
    public static function getFavoriteSellersByCustomer($id_customer) {
        $query = 'SELECT s.id_seller, name, link_rewrite FROM '._DB_PREFIX_.'seller s
                    LEFT JOIN `'._DB_PREFIX_.'seller_favorite` sf ON (sf.`id_seller` = s.`id_seller`)  
                    WHERE sf.id_customer = '.(int)$id_customer;
        $rq = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($query);
        if ($rq)
            return $rq;
        return false;
    }
    
    public function getNumProducts() {
        return Db::getInstance()->getValue('SELECT COUNT(*) FROM '._DB_PREFIX_.'seller_product WHERE id_seller_product = '.(int)$this->id);
    }
    
    public function getNumActiveProducts() {
        return Db::getInstance()->getValue('SELECT COUNT(*) FROM '._DB_PREFIX_.'seller_product sp
                LEFT JOIN '._DB_PREFIX_.'product p on (p.id_product = sp.id_product)
                WHERE active = 1 AND id_seller_product = '.(int)$this->id);
    }
    
    public static function existFavoriteSellerByCustomer($id_seller, $id_customer) {
        return Db::getInstance()->getValue('SELECT COUNT(*) FROM '._DB_PREFIX_.'seller_favorite WHERE id_seller = '.(int)$id_seller.' AND id_customer = '.(int)$id_customer);
    }
    
    public static function addFavorite($id_seller, $id_customer) {
        Db::getInstance()->Execute('INSERT INTO `' . _DB_PREFIX_ . 'seller_favorite` 
					(`id_customer`, `id_seller`)
					VALUES ('.(int)$id_customer.', '.(int)$id_seller.')');
    }
    
    public function getFollowers() {
        return Db::getInstance()->getValue('SELECT COUNT(*) FROM '._DB_PREFIX_.'seller_favorite WHERE id_seller = '.(int)$this->id);
    }
    
    public static function deleteFavoriteSellerByCustomer($id_seller, $id_customer)
    {
        return Db::getInstance()->execute(
                'DELETE FROM `'._DB_PREFIX_.'seller_favorite` WHERE `id_seller` = '.(int)$id_seller.' AND `id_customer` = '.(int)$id_customer
        );
    }
    
    public function getNewProducts($id_lang, $page_number = 0, $nb_products = 10, $count = false, $order_by = null, $order_way = null, Context $context = null)
    {
        if (!$context)
                $context = Context::getContext();

        $front = true;
        if (!in_array($context->controller->controller_type, array('front', 'modulefront')))
                $front = false;

        if ($page_number < 0) $page_number = 0;
        if ($nb_products < 1) $nb_products = 10;
        if (empty($order_by) || $order_by == 'position') $order_by = 'date_add';
        if (empty($order_way)) $order_way = 'DESC';
        if ($order_by == 'id_product' || $order_by == 'price' || $order_by == 'date_add' || $order_by == 'date_upd')
                $order_by_prefix = 'p';
        elseif ($order_by == 'name')
                $order_by_prefix = 'pl';
        if (!Validate::isOrderBy($order_by) || !Validate::isOrderWay($order_way))
                die(Tools::displayError());

        $sql_groups = '';
        if (Group::isFeatureActive())
        {
                $groups = FrontController::getCurrentCustomerGroups();
                $sql_groups = 'AND p.`id_product` IN (
                        SELECT cp.`id_product`
                        FROM `'._DB_PREFIX_.'category_group` cg
                        LEFT JOIN `'._DB_PREFIX_.'category_product` cp ON (cp.`id_category` = cg.`id_category`)
                        WHERE cg.`id_group` '.(count($groups) ? 'IN ('.implode(',', $groups).')' : '= 1').'
                )';
        }

        if (strpos($order_by, '.') > 0)
        {
                $order_by = explode('.', $order_by);
                $order_by_prefix = $order_by[0];
                $order_by = $order_by[1];
        }

        if ($count)
        {
                $sql = 'SELECT COUNT(p.`id_product`) AS nb
                                FROM `'._DB_PREFIX_.'product` p
                                '.Shop::addSqlAssociation('product', 'p').'
                                WHERE product_shop.`active` = 1
                                AND product_shop.`date_add` > "'.date('Y-m-d', strtotime('-'.(Configuration::get('PS_NB_DAYS_NEW_PRODUCT') ? (int)Configuration::get('PS_NB_DAYS_NEW_PRODUCT') : 20).' DAY')).'"
                                '.($front ? ' AND product_shop.`visibility` IN ("both", "catalog")' : '').'
                                '.$sql_groups;
                return (int)Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue($sql);
        }

        $sql = new DbQuery();
        $sql->select(
                'p.*, product_shop.*, stock.out_of_stock, IFNULL(stock.quantity, 0) as quantity, pl.`description`, pl.`description_short`, pl.`link_rewrite`, pl.`meta_description`,
                pl.`meta_keywords`, pl.`meta_title`, pl.`name`, pl.`available_now`, pl.`available_later`, MAX(image_shop.`id_image`) id_image, il.`legend`, m.`name` AS manufacturer_name,
                product_shop.`date_add` > "'.date('Y-m-d', strtotime('-'.(Configuration::get('PS_NB_DAYS_NEW_PRODUCT') ? (int)Configuration::get('PS_NB_DAYS_NEW_PRODUCT') : 20).' DAY')).'" as new'.(Combination::isFeatureActive() ? ', MAX(product_attribute_shop.minimal_quantity) AS product_attribute_minimal_quantity' : '')
        );

        $sql->from('product', 'p');
        $sql->join(Shop::addSqlAssociation('product', 'p'));
        $sql->leftJoin('product_lang', 'pl', '
                p.`id_product` = pl.`id_product`
                AND pl.`id_lang` = '.(int)$id_lang.Shop::addSqlRestrictionOnLang('pl')
        );
        $sql->leftJoin('image', 'i', 'i.`id_product` = p.`id_product`');
        $sql->join(Shop::addSqlAssociation('image', 'i', false, 'image_shop.cover=1'));
        $sql->leftJoin('image_lang', 'il', 'i.`id_image` = il.`id_image` AND il.`id_lang` = '.(int)$id_lang);
        $sql->leftJoin('manufacturer', 'm', 'm.`id_manufacturer` = p.`id_manufacturer`');
        $sql->leftJoin('seller_product', 'sp', 'sp.`id_product` = p.`id_product`');

        $sql->where('product_shop.`active` = 1');
        $sql->where('sp.id_seller_product = '.(int)$this->id);
        if ($front)
                $sql->where('product_shop.`visibility` IN ("both", "catalog")');
        $sql->where('product_shop.`date_add` > "'.date('Y-m-d', strtotime('-'.(Configuration::get('PS_NB_DAYS_NEW_PRODUCT') ? (int)Configuration::get('PS_NB_DAYS_NEW_PRODUCT') : 20).' DAY')).'"');
        if (Group::isFeatureActive())
        {
                $sql->join('JOIN '._DB_PREFIX_.'category_product cp ON (cp.id_product = p.id_product)');
                $sql->join('JOIN '._DB_PREFIX_.'category_group cg ON (cg.id_category = cp.id_category)');
                $sql->where('cg.`id_group` '.(count($groups) ? 'IN ('.implode(',', $groups).')' : '= 1'));
        }
        $sql->groupBy('product_shop.id_product');

        $sql->orderBy((isset($order_by_prefix) ? pSQL($order_by_prefix).'.' : '').'`'.pSQL($order_by).'` '.pSQL($order_way));
        $sql->limit($nb_products, $page_number * $nb_products);

        if (Combination::isFeatureActive())
        {
                $sql->select('MAX(product_attribute_shop.id_product_attribute) id_product_attribute');
                $sql->leftOuterJoin('product_attribute', 'pa', 'p.`id_product` = pa.`id_product`');
                $sql->join(Shop::addSqlAssociation('product_attribute', 'pa', false, 'product_attribute_shop.default_on = 1'));
        }
        $sql->join(Product::sqlStock('p', Combination::isFeatureActive() ? 'product_attribute_shop' : 0));

        $result = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql);

        if ($order_by == 'price')
                Tools::orderbyPrice($result, $order_way);
        if (!$result)
                return false;

        $products_ids = array();
        foreach ($result as $row)
                $products_ids[] = $row['id_product'];
        // Thus you can avoid one query per product, because there will be only one query for all the products of the cart
        Product::cacheFrontFeatures($products_ids, $id_lang);
        return Product::getProductsProperties((int)$id_lang, $result);
    }
    
    public static function generateLinkRewrite($name) {
        $link_rewrite = Tools::strtolower($name);
        $link_rewrite = str_replace(array('á','à','â','ã','ª','ä'),"a",$link_rewrite);
        $link_rewrite = str_replace(array('Á','À','Â','Ã','Ä'),"A",$link_rewrite);
        $link_rewrite = str_replace(array('Í','Ì','Î','Ï'),"I",$link_rewrite);
        $link_rewrite = str_replace(array('í','ì','î','ï'),"i",$link_rewrite);
        $link_rewrite = str_replace(array('é','è','ê','ë'),"e",$link_rewrite);
        $link_rewrite = str_replace(array('É','È','Ê','Ë'),"E",$link_rewrite);
        $link_rewrite = str_replace(array('ó','ò','ô','õ','ö','º'),"o",$link_rewrite);
        $link_rewrite = str_replace(array('Ó','Ò','Ô','Õ','Ö'),"O",$link_rewrite);
        $link_rewrite = str_replace(array('ú','ù','û','ü'),"u",$link_rewrite);
        $link_rewrite = str_replace(array('Ú','Ù','Û','Ü'),"U",$link_rewrite);
        $link_rewrite = str_replace(array('[','^','´','`','¨','~',']'),"",$link_rewrite);
        $link_rewrite = str_replace(array(',','.'),"",$link_rewrite);
        $link_rewrite = str_replace("ç","c",$link_rewrite);
        $link_rewrite = str_replace("Ç","C",$link_rewrite);
        $link_rewrite = str_replace("ñ","n",$link_rewrite);
        $link_rewrite = str_replace("Ñ","N",$link_rewrite);
        $link_rewrite = str_replace("Ý","Y",$link_rewrite);
        $link_rewrite = str_replace("ý","y",$link_rewrite);

        $link_rewrite = str_replace(' ', '-', $link_rewrite);
        return $link_rewrite;
    }
    
    public static function existName($name) {
        return Db::getInstance()->getValue('SELECT COUNT(*) FROM '._DB_PREFIX_.'seller WHERE name = "'.pSQL($name).'"');
    }
    
    public static function existEmail($email) {
        return Db::getInstance()->getValue('SELECT COUNT(*) FROM '._DB_PREFIX_.'seller WHERE email = "'.pSQL($email).'"');
    }

    public static function addCartOtherSeller($id_product,$qty,$id_seller,$id_customer) {
        $query = 'INSERT INTO `'._DB_PREFIX_.'seller_cart` (`id_product`,`qty`,`id_seller`,`id_customer`) VALUES  ('. $id_product .','. $qty .','. $id_seller .','. $id_customer .')';

        return Db::getInstance()->executeS($query);
        
    }

    public static function isCartOtherSeller($id_customer,$id_seller) {
        return Db::getInstance()->getValue('SELECT COUNT(`id_seller`) FROM '._DB_PREFIX_.'seller_cart WHERE id_customer = '.(int)$id_customer.' AND id_cart IS NULL AND `id_seller` = '. $id_seller);
    }

    public static function isExistCartOtherSeller($id_customer,$id_seller,$id_product) {
        return Db::getInstance()->getValue('SELECT COUNT(*) FROM '._DB_PREFIX_.'seller_cart WHERE id_customer = '.(int)$id_customer.' AND id_cart IS NULL AND `id_seller` = '. $id_seller.' AND id_product = '. $id_product);
    }

    public static function updateQtyCartOtherSeller($qty,$id_product,$id_customer) {
        $query = 'UPDATE `'._DB_PREFIX_.'seller_cart` SET `qty` = `qty`+'. $qty .' WHERE `id_customer` ='. $id_customer .' AND `id_product` = '. $id_product;

        Db::getInstance()->execute($query);
        
    }

    public static function idSellerCartOtherSeller($id_customer) {
        return Db::getInstance()->executeS('SELECT COUNT(sc.`id_seller`) as count,sc.`id_seller` as scid_seller, SUM(sc.`qty`) as qty,s.* FROM '._DB_PREFIX_.'seller_cart sc
            LEFT JOIN '._DB_PREFIX_.'seller s ON (s.id_seller = sc.id_seller)
            WHERE sc.id_customer = '.(int)$id_customer.' AND sc.id_cart IS NULL GROUP BY sc.`id_seller`');

    }

    public static function updateCartOtherSeller($id_cart,$id_seller,$id_customer) {
        $query = 'UPDATE `'._DB_PREFIX_.'seller_cart` SET `id_cart` ='. $id_cart .' WHERE `id_customer` ='. $id_customer .' AND `id_seller` = '. $id_seller;

        Db::getInstance()->execute($query);
        
    }

    public static function updateCartOtherSellerNew($id_seller,$id_customer) {
        $query = 'UPDATE `'._DB_PREFIX_.'seller_cart` SET `new` = 0 WHERE `id_customer` ='. $id_customer;

        Db::getInstance()->execute($query);
        
    }

    public static function isNewCartOtherSeller($id_customer) {
        return Db::getInstance()->getValue('SELECT SUM(new) as new FROM '._DB_PREFIX_.'seller_cart WHERE id_customer = '.(int)$id_customer) . ' AND new = 1';
    }

    public static function deleteCartOtherSeller($id_seller,$id_customer) {
        $query = 'DELETE FROM `'._DB_PREFIX_.'seller_cart` WHERE `id_customer` ='. $id_customer .' AND `id_seller` = '. $id_seller;

        Db::getInstance()->executeS($query);
        
    }

    public static function deleteProductCartOtherSeller($id_seller,$id_customer,$id_product) {
        $query = 'DELETE FROM `'._DB_PREFIX_.'seller_cart` WHERE `id_customer` ='. $id_customer .' AND `id_seller` = '. $id_seller. ' AND `id_product` ='. $id_product;

        Db::getInstance()->executeS($query);
        
    }

    public static function deleteAllCartOtherSeller($id_customer) {
        $query = 'DELETE FROM `'._DB_PREFIX_.'seller_cart` WHERE `id_customer` ='. $id_customer ;

        Db::getInstance()->executeS($query);
        
    }

    public static function getCartOtherSeller($id_seller,$id_customer) {
        $query = 'SELECT * FROM `'._DB_PREFIX_.'seller_cart` WHERE `id_cart` IS NULL AND `id_customer` = '. $id_customer .' AND `id_seller`= '. $id_seller;

        $result = Db::getInstance()->executeS($query);

        return $result;        
    }

    

    public static function getAllCartOtherSeller($id_customer) {
        $id_lang = (int) Context::getContext()->language->id;
        $query = 'SELECT sc.id_customer,sc.id_seller,sc.qty,p.*,pl.*,i.id_image FROM `'._DB_PREFIX_.'seller_cart` sc
            LEFT JOIN `' . _DB_PREFIX_ . 'product` p ON (p.id_product = sc.id_product)
            LEFT JOIN `' . _DB_PREFIX_ . 'product_lang` pl ON (pl.id_product = sc.id_product)
            LEFT JOIN `' . _DB_PREFIX_ . 'image` i ON (i.id_product = sc.id_product AND i.cover = 1)
            WHERE sc.`id_cart` IS NULL AND sc.`id_customer`= '. $id_customer .' AND pl.id_lang = '. $id_lang;

        $result = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($query);

        return Product::getProductsProperties($id_lang, $result);
        
    }

    public static function updateSellerCarrier($current_carrier,$new_carrier) {

    
        $sql = 'SELECT COUNT(*) FROM ' . _DB_PREFIX_ . 'seller_carrier WHERE `id_carrier`='.$current_carrier->id;
        if(Db::getInstance()->getValue($sql) > 0)
        {
            //associate seller to carrier
            $sql = 'UPDATE ' . _DB_PREFIX_ . 'seller_carrier SET `id_carrier` = '. $new_carrier->id .' WHERE `id_carrier` ='. (int)$current_carrier->id;
                                    
            return Db::getInstance()->execute($sql);
        }

        return false;
        
    }

    public static function updateCartOtherSellerGuest($id_customer,$id_guest) {
        $query = 'UPDATE `'._DB_PREFIX_.'seller_cart` SET `id_customer` ='. $id_customer .' WHERE `id_customer` ='. $id_guest;

        Db::getInstance()->executeS($query);
        
    }
}