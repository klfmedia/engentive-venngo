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

			/*
			Order products looks like this:
				[0] => Array
			        (
			            [order_product_id] => 13
			            [order_id] => 13
			            [product_id] => 33
			            [name] => Samsung SyncMaster 941BW
			            [model] => Product 6
			            [quantity] => 1
			            [price] => 200.0000
			            [total] => 200.0000
			            [tax] => 0.0000
			            [reward] => 0
			        )
			*/

			/*
				Insert code for API here
			*/
		}

		//function ends for now but should redirect to the "Success page"
		die();
	}
}
