<?php
include_once(dirname(__FILE__).'/../../config/config.inc.php');
//include_once('jmarketpalce.php');
include_once dirname(__FILE__).'/classes/SellerOrder.php';

$dir = str_replace('\\', '/', dirname(__FILE__));

/*if (is_dir($dir.'/../../themes/'._THEME_NAME_.'/modules/jmarketpalce'))
{
    // rmdir($dir.'/../../themes/'._THEME_NAME_.'/modules/cartabandonmentpro');
	CartAbandonmentPro::deleteDirectory($dir.'/../../themes/'._THEME_NAME_.'/modules/cartabandonmentpro');
}*/

$id_lang = Tools::getValue('id_lang');
$id_shop = 1;

if(!$id_lang){
	$id_lang = Tools::getValue('amp;id_lang');
	if(!$id_lang)
		$id_lang = $argv[1];
	if(!$id_lang)
	{
		echo 'No Lang ...';die;
	}
}

	
	$orders = SellerOrder::getOrdersStateWithSeller($id_lang);

	$diff24Hours = new DateInterval(Configuration::get('DELAY_DELIVERY'));

            foreach ($orders as $order) {
                
                $d1 = new DateTime($order['date_add']);
                $d2 = new DateTime();
                $d2->setTimezone(new DateTimeZone('Europe/London'));
                $d2->format('Y-m-d H:i:s');
                $d1->format('Y-m-d H:i:s');

                $d1->add($diff24Hours);

                $delay_exp[] = array(
                    'id_order' => $order['id_order'],
                    'order_ref' => $order['reference'],
                    'seller_name' => $order['seller_name'],
                    'order_dateadd' => $order['date_add'],
                    'order_payment' => $order['payment'],
                    'order_state' => $order['osname'],
                    'hours_exp' =>  round(($d1->getTimestamp() - $d2->getTimestamp()) / 3600)
                );
                
            }

            $iso = Language::getIsoById($id_lang);

            
            $customer_email = Configuration::get('PS_SHOP_EMAIL');

            foreach ($delay_exp as $delay) {

            	
            	$template_vars = $arrayName = array(
            		'{order_id}' => $delay['id_order'],
            		'{order_dateadd}' => $delay['order_dateadd'],            		
                    '{order_ref}' => $delay['order_ref'],
                    '{seller_name}' => $delay['seller_name'],                    
                    '{order_payment}' => $delay['order_payment'],
                    '{order_state}' => $delay['order_state'],
                    '{hours_exp}' => $delay['hours_exp'],
            		);

            	if (file_exists(dirname(__FILE__).'/mails/'.$iso.'/delivery_exp.txt') &&
					file_exists(dirname(__FILE__).'/mails/'.$iso.'/delivery_exp.html') && $delay['hours_exp'] <= 0)
					Mail::Send(
						$id_lang,
						'delivery_exp',
						Mail::l('Livraison à relancer', $id_lang),
						$template_vars,
						(string)$customer_email,
						null,
						(string)Configuration::get('PS_SHOP_EMAIL', null, null, $id_shop),
						(string)Configuration::get('PS_SHOP_NAME', null, null, $id_shop),
						null,
						null,
						dirname(__FILE__).'/mails/',
						false,
						$id_shop
				);

				echo "mail send <br>" ;
            }

            
   		

    print_r($delay_exp);