<?php
class ControllerExtensionPaymentLs extends Controller {
	public function index() {
		$data['button_confirm'] = $this->language->get('button_confirm');

		$data['text_loading'] = $this->language->get('text_loading');

		$data['continue'] = $this->url->link('checkout/success');

		return $this->load->view('extension/payment/ls', $data);
	}

	public function confirm() {
		if ($this->session->data['payment_method']['code'] == 'ls') {
			$this->load->model('checkout/order');
			$this->load->model('account/order');
			$this->load->model('catalog/product');
			$this->load->library('curl');

			//This marks the order as "paid"
			$this->model_checkout_order->addOrderHistory($this->session->data['order_id'], $this->config->get('ls_order_status_id'));

			//Getting the order details including payment and shipping addresses
			$order_info = $this->model_account_order->getOrder($this->session->data['order_id']);
			/*
			Here is what the order_info array contains:

				[order_id] => 13
			    [invoice_no] => 0
			    [invoice_prefix] => INV-2013-00
			    [store_id] => 0
			    [store_name] => Your Store
			    [store_url] => http://localhost/venngo/
			    [customer_id] => 0
			    [firstname] => Thanh
			    [lastname] => Nguyen
			    [telephone] => 5148254589
			    [fax] => 
			    [email] => thanh@klfmedia.com
			    [payment_firstname] => Thanh
			    [payment_lastname] => Nguyen
			    [payment_company] => 
			    [payment_address_1] => 9090 Cavendish
			    [payment_address_2] => 
			    [payment_postcode] => H4T 1Z8
			    [payment_city] => Saint-Laurent
			    [payment_zone_id] => 612
			    [payment_zone] => Qu&eacute;bec
			    [payment_zone_code] => QC
			    [payment_country_id] => 38
			    [payment_country] => Canada
			    [payment_iso_code_2] => CA
			    [payment_iso_code_3] => CAN
			    [payment_address_format] => 
			    [payment_method] => DEV - Loyalty Source
			    [shipping_firstname] => Thanh
			    [shipping_lastname] => Nguyen
			    [shipping_company] => 
			    [shipping_address_1] => 9090 Cavendish
			    [shipping_address_2] => 
			    [shipping_postcode] => H4T 1Z8
			    [shipping_city] => Saint-Laurent
			    [shipping_zone_id] => 612
			    [shipping_zone] => Qu&eacute;bec
			    [shipping_zone_code] => QC
			    [shipping_country_id] => 38
			    [shipping_country] => Canada
			    [shipping_iso_code_2] => CA
			    [shipping_iso_code_3] => CAN
			    [shipping_address_format] => 
			    [shipping_method] => Flat Shipping Rate
			    [comment] => 
			    [total] => 205.0000
			    [order_status_id] => 15
			    [language_id] => 1
			    [currency_id] => 2
			    [currency_code] => USD
			    [currency_value] => 1.00000000
			    [date_modified] => 2017-09-19 11:48:25
			    [date_added] => 2017-09-19 11:42:39
			    [ip] => ::1
			*/

			//Get order products
			$order_products = $this->model_account_order->getOrderProducts($this->session->data['order_id']);

			$items = array();
			foreach ($order_products as $op) {
				$product_info = $this->model_catalog_product->getProduct($op['product_id']);
				$items[] = array(
							'careOf' => $order_info['firstname'],
							'internalOrderLineNumber' => $op['order_product_id'],
							'internalProductId' => $op['product_id'],
							'lsProductId' => $product_info['sku'],
							'quantity' => intval($op['quantity']));
			}
			
			$reqest_body = array(
				'customer' => array(
					'companyName' => 'VannGo',
					'deliveryAddress' => array(
							'city' => $order_info['shipping_city'],
							'complement' => '',
							'country' => $order_info['shipping_country'],
							'postalCode' => $order_info['shipping_postcode'],
							'province' => $order_info['shipping_zone'],
							'street' => $order_info['shipping_address_1']
						 ),
					'emailAddress' => $order_info['email'],
					'firstname' => $order_info['firstname'],
					'lastname' => $order_info['lastname'],
					'phoneNumber' => $order_info['telephone'] 
				),
				'internalOrderId' => $order_info['order_id'],
				'items' => $items
			);
			//use CURL class to init a curl object
			$ls_curl = new Curl();
			//set headers for request 
			$ls_curl->setHeader('Authorization',$this->config->get('ls_api_auth_key'));
			$ls_curl->setHeader('Content-Type','application/json');
			// create new order
			$ls_curl->post($this->config->get('ls_api_base_url').'orders',json_encode($reqest_body));

			if($ls_curl->isSuccess())
			{
				$ls_curl->get($this->config->get('ls_api_base_url').$this->config->get('ls_api_get_order').$order_info['order_id']);
				echo "<br/>";
				echo $ls_curl->response;
			}
			else
			{
				echo $ls_curl->response;
			}
			//close connection
			$ls_curl->close();
			exit;
		}

		//function ends for now but should redirect to the "Success page"
		die();
	}
}
