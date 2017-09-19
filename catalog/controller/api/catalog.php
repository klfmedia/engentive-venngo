<?php
class ControllerApiCatalog extends Controller {
	public function update() {
		//$this->load->language('api/coupon');

		$json = array();

		//This will be needed later on for authentication
		/*
		if (!isset($this->session->data['api_id'])) {
			$json['error'] = $this->language->get('error_permission');
		} 
		else {
		}
		*/
		
		//For categories
		/*
		$this->log->write("START Updating categories");
		$categories = $this->getCategories();
		if (!$categories) {
			$this->log->write("ERROR: Cannot fetch categories");
			die();
		}
		$this->log->write("Total of ".count($categories)." rows fetched");
		$this->updateCategories($categories);
		$this->log->write("END Updating categories");
	
		//For brands
		$this->log->write("START Updating brands");
		$brands = $this->getBrands();
		if (!$brands) {
			$this->log->write("ERROR: Cannot fetch brands");
			die();
		}
		$this->log->write("Total of ".count($brands)." rows fetched");
		$this->updateBrands($brands);
		$this->log->write("END Updating brands");
		*/

		//For products
		$this->log->write("START Updating products");
		$products = $this->getProducts();

		if (!$products) {
			$this->log->write("ERROR: Cannot fetch products");
			die();
		}

		$this->log->write("Total of ".count($products)." rows fetched");
		$this->updateProducts($products);
		$this->log->write("END Updating products");

		/*
		if (isset($this->request->server['HTTP_ORIGIN'])) {
			$this->response->addHeader('Access-Control-Allow-Origin: ' . $this->request->server['HTTP_ORIGIN']);
			$this->response->addHeader('Access-Control-Allow-Methods: GET, PUT, POST, DELETE, OPTIONS');
			$this->response->addHeader('Access-Control-Max-Age: 1000');
			$this->response->addHeader('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
		}
		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
		*/
	}


	private function getCategories() {
		$url = $this->config->get('ls_api_base_url') . $this->config->get('ls_api_get_categories');

		$response =$this->fetchCURL($url);
		
		if (empty($response) || isset($response['error_code'])){
			$this->log->write("LS API ERROR - Error while fetching categories - " . json_encode($response));
			return false;
		}
		else {
			return $response;
		}
	}	

	private function getBrands() {
		$url = $this->config->get('ls_api_base_url') . $this->config->get('ls_api_get_brands');

		$response =$this->fetchCURL($url);
		
		if (empty($response) || isset($response['error_code'])){
			$this->log->write("LS API ERROR - Error while fetching brands - " . json_encode($response));
			return false;
		}
		else {
			return $response;
		}
	}

	private function getProducts() {
		$url = $this->config->get('ls_api_base_url') . $this->config->get('ls_api_get_products');

		$response =$this->fetchCURL($url);
		
		if (empty($response) || isset($response['error_code'])){
			$this->log->write("LS API ERROR - Error while fetching products - " . json_encode($response));
			return false;
		}
		else {
			return $response;
		}
	}

	private function updateCategories($categories) {
		$this->load->model('api/catalog');

		foreach($categories as $category) {
			//Add in the store id for each category
			$category['category_store'][] = $this->config->get('ls_venngo_store_id');
			$this->model_api_catalog->setCategory($category);
		}
	}

	private function updateBrands($brands) {
		$this->load->model('api/catalog');

		foreach($brands as $brand) {
			//Add in the store id for each brand (manufacturer)
			$brand['manufacturer_store'][] = $this->config->get('ls_venngo_store_id');
			$this->model_api_catalog->setBrand($brand);
		}
	}

	private function updateProducts($products) {
		$this->load->model('api/catalog');

		foreach($products as $product) {
			//Add in the store id for each product
			$category['product_store'][] = $this->config->get('ls_venngo_store_id');
			$this->model_api_catalog->setProduct($product);
		}
	}

	private function fetchCURL($url, $headers = array(), $params = array()) {
		//Set CURL options
		$options[CURLOPT_RETURNTRANSFER] = true;

		//Set the LS authorization key
		$headers[] = "Authorization: " . $this->config->get('ls_api_auth_key');

		if (!empty($headers))
			$options[CURLOPT_HTTPHEADER] = $headers;

		if (!empty($params)){
			$options[CURLOPT_POST] = 1;
		    $options[CURLOPT_POSTFIELDS] = http_build_query($params);
		}


		$ch = curl_init($url);
		curl_setopt_array($ch, $options);
		$response = curl_exec($ch);
		curl_close($ch);

		return json_decode($response, true);
	}
}