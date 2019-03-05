<?php

class marketplaceOrderWaitingModuleFrontController extends ModuleFrontController
{
    public function setMedia() {
        parent::setMedia();
        $this->context->controller->addCSS($this->module->getPathUri().'views/css/carriers.css');
    }

    public function addCartToPreOrderList($seller_ids)
    {
        if ($this->context->cart->id) {
            
            if($this->context->cookie->id_customer)            
                $id_customer = $this->context->customer->id;
            else
                $id_customer = Context::getContext()->cookie->id_guest;            
            
            $cart_products = $this->context->cart->getProducts();

            if (is_array($cart_products)) {
                foreach ($cart_products as $cart_product) {
                    
                    //$id_seller = Seller::getSellerByProduct($cart_product['id_product']);
                             
                    Seller::addCartOtherSeller($cart_product['id_product'],1,$seller_ids,$id_customer);
                    $this->context->cart->deleteProduct($cart_product['id_product']);

                }
            }
        }        

        return true;
    }

    public function addSellerCartToCart($seller_ids)
    {
        $res = array();
        if($this->context->cookie->id_customer)
            $id_customer = $this->context->customer->id;
        else
            $id_customer = Context::getContext()->cookie->id_guest;

        
        $results = Seller::idSellerCartOtherSeller($id_customer);
        $count_cos = 0;
            
            
            $id_seller = $seller_ids;
            $count_cos++;
            

            if($count_cos > 0)
            {
                // ajout de la nouvelle liste par vendeur dans la cart
                $seller_carts = Seller::getCartOtherSeller($id_seller,$id_customer);
                
                foreach ($seller_carts as $seller_cart) {
                    $id_product_other .= $seller_cart['id_product'] . ',';
                    $qty_other .= $seller_cart['qty'] . ',';
                    $id_seller_other = $seller_cart['id_seller'];
                    $id_customer_other= $seller_cart['id_customer'];
                }

                $res['id_product'] = rtrim($id_product_other,',');
                $res['qty'] = rtrim($qty_other,',');
                $res['id_seller'] = $id_seller_other;
                $res['id_customer'] = $id_customer_other;
                
                return $res;
            }

        return null;
    }
    public function postProcess() {
        if (Tools::getValue('product_ids')) {
            
            $id_seller = (int)Tools::getValue('delete');
            $id_product = (int)Tools::getValue('product_ids');            
            
            $id_customer = $this->context->customer->id;

            Seller::deleteProductCartOtherSeller($id_seller,$id_customer,$id_product);

            Tools::redirect($this->context->link->getModuleLink('marketplace', 'orderwaiting'));

        }
        if (Tools::getValue('seller_ids')) {
            if( $seller_ids == -1)
                $id_seller = 0;
            else
                $id_seller = (int)Tools::getValue('seller_ids');

            $this->addCartToPreOrderList($id_seller);
            $seller_cart = $this->addSellerCartToCart($id_seller);
            Tools::redirect('index.php?controller=precommande&products_ids='.$seller_cart['id_product'].'&qts='.$seller_cart['qty'].'&seller_ids='.$seller_cart['id_seller'].'&customer_ids='.$seller_cart['id_customer']);
            
        }
    }
    
	public function initContent() {
        
        parent::initContent();
        
        if(!$this->context->cookie->id_customer)
            $id_customer = Context::getContext()->cookie->id_guest;
        else
            $id_customer = $this->context->customer->id;

        //$id_customer = $this->context->customer->id;
        $cart_sellers = Seller::idSellerCartOtherSeller($id_customer);
        $products = Seller::getAllCartOtherSeller($id_customer);
        $count_cos = 0;

        
        $results = Seller::idSellerCartOtherSeller($id_customer);

        $nb_otherorder = 0;
        $nb_product_otherorder = 0;

        foreach ($results as $result) {
           $nb_otherorder++;
           $nb_product_otherorder += (int)$result['qty'];
        }

        $this->context->smarty->assign(array(
            'cart_sellers' => $cart_sellers,
            'products' => $products,  
            'nb_otherorder' => $nb_otherorder,
            'nb_product_otherorder' => $nb_product_otherorder
            ));
            
        $this->setTemplate('orderwaitinglist.tpl');    
    }


    


}