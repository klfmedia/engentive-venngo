<?php
class ModelApiCatalog extends Model {
	public function resetCatalog() {
		$this->db->query("TRUNCATE TABLE " . DB_PREFIX . "category;");
		$this->db->query("TRUNCATE TABLE " . DB_PREFIX . "category_description;");
		$this->db->query("TRUNCATE TABLE " . DB_PREFIX . "category_path;");
		$this->db->query("TRUNCATE TABLE " . DB_PREFIX . "category_to_store;");

		$this->db->query("TRUNCATE TABLE " . DB_PREFIX . "manufacturer;");
		$this->db->query("TRUNCATE TABLE " . DB_PREFIX . "manufacturer_to_store;");

		$this->db->query("TRUNCATE TABLE " . DB_PREFIX . "product;");
		$this->db->query("TRUNCATE TABLE " . DB_PREFIX . "product_description;");
		$this->db->query("TRUNCATE TABLE " . DB_PREFIX . "product_discount;");
		$this->db->query("TRUNCATE TABLE " . DB_PREFIX . "product_special;");
		$this->db->query("TRUNCATE TABLE " . DB_PREFIX . "product_to_store;");
		$this->db->query("TRUNCATE TABLE " . DB_PREFIX . "product_to_category;");
		$this->db->query("TRUNCATE TABLE " . DB_PREFIX . "product_attribute;");
	}

	public function setCategory($data) {
		$category_id = $data['id'];

		//Find out if record exists with the same category_id
		$query = $this->db->query("SELECT COUNT(*) as `exists` FROM " . DB_PREFIX . "category WHERE category_id = " . (int)$category_id);
		
		//If so, then update it.
		if ($query->row['exists']) {
			$this->db->query("UPDATE " . DB_PREFIX . "category 
							  SET  
							  	parent_id = '" . (int)$data['parentId'] . "', 
							  	date_modified = NOW()
							  WHERE
							  	category_id = '" . (int)$category_id . "' 
							  LIMIT 1");

		}
		//If not, REPLACE it.
		else {
			$this->db->query("REPLACE INTO " . DB_PREFIX . "category 
							  SET 
							  	category_id = '" . (int)$category_id . "', 
							  	parent_id = '" . (int)$data['parentId'] . "', 
							  	`top` = 0, 
							  	`column` = 0, 
							  	status = 1, 
							  	date_modified = NOW(), 
							  	date_added = NOW()");

		}

		//Add in the category names by language
		foreach ($data['name'] as $n) {
			if ($n['locale'] == $this->config->get('ls_lang_name_en'))
				$language_id = $this->config->get('ls_lang_code_en');
			elseif ($n['locale'] == $this->config->get('ls_lang_name_fr'))
				$language_id = $this->config->get('ls_lang_code_fr');

			$this->db->query("REPLACE INTO " . DB_PREFIX . "category_description 
							  SET 
							  	category_id = '" . (int)$category_id . "', 
							  	language_id = '" . (int)$language_id . "', 
							  	name = '" . $this->db->escape($n['value']) . "', 
							  	description = '', 
							  	meta_title = '" . $this->db->escape($n['value']) . "',
							  	meta_description = '', 
							  	meta_keyword = ''");
		}

		// MySQL Hierarchical Data Closure Table Pattern
		$level = 0;

		//Modifies all of the paths to reflect new category position
		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "category_path` WHERE category_id = '" . (int)$data['parentId'] . "' ORDER BY `level` ASC");

		foreach ($query->rows as $result) {
			$this->db->query("REPLACE INTO `" . DB_PREFIX . "category_path` SET `category_id` = '" . (int)$category_id . "', `path_id` = '" . (int)$result['path_id'] . "', `level` = '" . (int)$level . "'");

			$level++;
		}

		$this->db->query("REPLACE INTO `" . DB_PREFIX . "category_path` SET `category_id` = '" . (int)$category_id . "', `path_id` = '" . (int)$category_id . "', `level` = '" . (int)$level . "'");

		if (isset($data['category_filter'])) {
			foreach ($data['category_filter'] as $filter_id) {
				$this->db->query("REPLACE INTO " . DB_PREFIX . "category_filter SET category_id = '" . (int)$category_id . "', filter_id = '" . (int)$filter_id . "'");
			}
		}

		if (isset($data['category_store'])) {
			foreach ($data['category_store'] as $store_id) {
				$this->db->query("REPLACE INTO " . DB_PREFIX . "category_to_store SET category_id = '" . (int)$category_id . "', store_id = '" . (int)$store_id . "'");
			}
		}

		// Set which layout to use with this category
		if (isset($data['category_layout'])) {
			foreach ($data['category_layout'] as $store_id => $layout_id) {
				$this->db->query("REPLACE INTO " . DB_PREFIX . "category_to_layout SET category_id = '" . (int)$category_id . "', store_id = '" . (int)$store_id . "', layout_id = '" . (int)$layout_id . "'");
			}
		}

		if (isset($data['keyword'])) {
			$this->db->query("REPLACE INTO " . DB_PREFIX . "url_alias SET query = 'category_id=" . (int)$category_id . "', keyword = '" . $this->db->escape($data['keyword']) . "'");
		}

		$this->cache->delete('category');
	}


	//For our purposes, we're using the term "brands" but in OpenCart, they use the term "manufacturer"
	public function setBrand($data) {
		$manufacturer_id = $data['id'];

		//Find out if record exists with the same manufacturer_id
		$query = $this->db->query("SELECT COUNT(*) as `exists` FROM " . DB_PREFIX . "manufacturer WHERE manufacturer_id = " . (int)$manufacturer_id);

		//If so, then update it.
		if ($query->row['exists']) {
			$this->db->query("UPDATE " . DB_PREFIX . "manufacturer 
							  SET 
							  	name = '" . $this->db->escape($data['name']) . "'
							  WHERE
							  	manufacturer_id = " . (int)$manufacturer_id);
		}
		else {
			$this->db->query("REPLACE INTO " . DB_PREFIX . "manufacturer 
							  SET 
							  	manufacturer_id = " . (int)$manufacturer_id . ",
							  	name = '" . $this->db->escape($data['name']) . "'");
		}

		if (isset($data['imageUrl'])) {
			$this->db->query("UPDATE " . DB_PREFIX . "manufacturer SET image = '" . $this->db->escape($data['imageUrl']) . "' WHERE manufacturer_id = '" . (int)$manufacturer_id . "'");
		}

		if (isset($data['manufacturer_store'])) {
			foreach ($data['manufacturer_store'] as $store_id) {
				$this->db->query("REPLACE INTO " . DB_PREFIX . "manufacturer_to_store SET manufacturer_id = '" . (int)$manufacturer_id . "', store_id = '" . (int)$store_id . "'");
			}
		}

		if (isset($data['keyword'])) {
			$this->db->query("REPLACE INTO " . DB_PREFIX . "url_alias SET query = 'manufacturer_id=" . (int)$manufacturer_id . "', keyword = '" . $this->db->escape($data['keyword']) . "'");
		}

		$this->cache->delete('manufacturer');

		return $manufacturer_id;
	}


	public function setProduct($data) {
		//Find out if record exists with the same SKU (lsProductId)
		$query = $this->db->query("SELECT product_id FROM " . DB_PREFIX . "product WHERE sku = '" . $this->db->escape($data['lsProductId']) . "'");

		//If so, then update it.
		if (!empty($query->row['product_id'])) {
			$product_id = $query->row['product_id'];
			$this->db->query("UPDATE " . DB_PREFIX . "product 
							  SET model = '" . $this->db->escape($data['modelNumber']) . "', 
								  sku = '" . $this->db->escape($data['lsProductId']) . "', 
								  upc = '" . $this->db->escape($data['upc']) . "', 
								  quantity = 100, 
								  manufacturer_id = '" . (int)$data['brandId'] . "', 
								  price = '" . (float)$data['msrp'] . "', 
								  weight = '" . (float)$data['sizeArray']['weight'] . "', 
								  length = '" . (float)$data['sizeArray']['length'] . "', 
								  width = '" . (float)$data['sizeArray']['width'] . "', 
								  height = '" . (float)$data['sizeArray']['high'] . ", 
								  date_modified = NOW()'
							  WHERE
							  	  product_id = " . (int)$product_id);
		}
		else {
			/*
				Need to translate:
					- stock_status_id
					- status
					- images

				Eventually we'll need to add attributes (for specs and etc)

				Build a table for MSRP

				Using the last date modified as the Date Availabe since it's always earlier than the current date

				Need to also set the weight class, length class and tax classes

				Hard-coded quantity since "stock" doesn't seem to be present in the API... need to clarify
			*/
			$this->db->query("INSERT INTO " . DB_PREFIX . "product 
							   SET model = '" . $this->db->escape($data['modelNumber']) . "', 
								  sku = '" . $this->db->escape($data['lsProductId']) . "', 
								  upc = '" . $this->db->escape($data['upc']) . "', 
								  ean = '', 
								  jan = '', 
								  isbn = '', 
								  mpn = '', 
								  location = '', 
								  quantity = '100', 
								  minimum = '1', 
								  subtract = '1', 
								  stock_status_id = 1, 
								  date_available = '" . $this->db->escape($data['lastDateModified']) . "', 
								  manufacturer_id = '" . (int)$data['brandId'] . "', 
								  shipping = 1,
								  price = '" . (float)$data['price'] . "', 
								  points = '', 
								  weight = '" . (float)$data['sizeArray']['weight'] . "', 
								  weight_class_id = 1, 
								  length = '" . (float)$data['sizeArray']['length'] . "', 
								  width = '" . (float)$data['sizeArray']['width'] . "', 
								  height = '" . (float)$data['sizeArray']['high'] . "', 
								  length_class_id = 1, 
								  status = 1, 
								  tax_class_id = 1, 
								  sort_order = 0, 
								  date_added = NOW()");

			$product_id = $this->db->getLastId();
		}

		//Set the category. At the moment, products only have 1 category
		$this->db->query("REPLACE INTO " . DB_PREFIX . "product_to_category SET product_id = '" . (int)$product_id . "', category_id = '" . (int)$data['categoryId'] . "'");

		//Need to fetch the image somehow
		if (isset($data['image'])) {
			$this->db->query("UPDATE " . DB_PREFIX . "product SET image = '" . $this->db->escape($data['image']) . "' WHERE product_id = '" . (int)$product_id . "'");
		}

		//Save the name
		foreach ($data['namesArray'] as $name) {
			if ($name['locale'] == $this->config->get('ls_lang_name_en'))
				$language_id = $this->config->get('ls_lang_code_en');
			elseif ($name['locale'] == $this->config->get('ls_lang_name_fr'))
				$language_id = $this->config->get('ls_lang_code_fr');

			$this->db->query("REPLACE INTO " . DB_PREFIX . "product_description 
							  SET product_id = '" . (int)$product_id . "', 
							  	  language_id = '" . (int)$language_id . "', 
							  	  name = '" . $this->db->escape($name['value']) . "', 
							  	  description = '', 
							  	  tag = '', 
							  	  meta_title = '" . $this->db->escape($name['value']) . "',  
							  	  meta_description = '', 
							  	  meta_keyword = ''");
		}

		//Once the name is saved, then we'll know for sure that a record exists in the description. Now loop through the descriptions array and update those records
		foreach ($data['descriptionsArray'] as $description) {
			if ($description['locale'] == $this->config->get('ls_lang_name_en'))
				$language_id = $this->config->get('ls_lang_code_en');
			elseif ($description['locale'] == $this->config->get('ls_lang_name_fr'))
				$language_id = $this->config->get('ls_lang_code_fr');

			$this->db->query("UPDATE " . DB_PREFIX . "product_description 
							  SET 
							  	  description = '" . $this->db->escape($description['value']) . "'
							  WHERE
							  	  product_id = " . (int)$product_id . " AND language_id = " . $language_id ."
							  LIMIT 1");
		}

		//We'll need to append the "specifications" values to the description column since there's no specifications field.
		foreach ($data['specificationsArray'] as $specification) {
			if ($specification['locale'] == $this->config->get('ls_lang_name_en'))
				$language_id = $this->config->get('ls_lang_code_en');
			elseif ($specification['locale'] == $this->config->get('ls_lang_name_fr'))
				$language_id = $this->config->get('ls_lang_code_fr');

			$this->db->query("UPDATE " . DB_PREFIX . "product_description 
							  SET 
							  	  description = CONCAT(description, '" . $this->db->escape($specification['value']) . "')
							  WHERE
							  	  product_id = " . (int)$product_id . " AND language_id = " . $language_id ."
							  LIMIT 1");
		}

		//Save this product to the proper store
		if (isset($data['product_store'])) {
			foreach ($data['product_store'] as $store_id) {
				$this->db->query("REPLACE INTO " . DB_PREFIX . "product_to_store SET product_id = '" . (int)$product_id . "', store_id = '" . (int)$store_id . "'");
			}
		}


		//Save customer's discounted price
		$this->db->query("REPLACE INTO " . DB_PREFIX . "product_discount 
						  SET product_id = '" . (int)$product_id . "', 
						  	  customer_group_id = '" . (int)$this->config->get('ls_venngo_customer_group_id') . "', 
						  	  quantity = '1', 
						  	  priority = '1', 
						  	  price = '" . (float)$data['price'] . "', 
						  	  date_start = '" . $this->db->escape($data['lastDateModified']) . "', 
						  	  date_end = ''");


		//Misc inserts that might be needed later
		if (isset($data['product_discount'])) {
			foreach ($data['product_discount'] as $product_discount) {
				$this->db->query("REPLACE INTO " . DB_PREFIX . "product_discount SET product_id = '" . (int)$product_id . "', customer_group_id = '" . (int)$product_discount['customer_group_id'] . "', quantity = '" . (int)$product_discount['quantity'] . "', priority = '" . (int)$product_discount['priority'] . "', price = '" . (float)$product_discount['price'] . "', date_start = '" . $this->db->escape($product_discount['date_start']) . "', date_end = '" . $this->db->escape($product_discount['date_end']) . "'");
			}
		}

		if (isset($data['product_attribute'])) {
			foreach ($data['product_attribute'] as $product_attribute) {
				if ($product_attribute['attribute_id']) {
					// Removes duplicates
					$this->db->query("DELETE FROM " . DB_PREFIX . "product_attribute WHERE product_id = '" . (int)$product_id . "' AND attribute_id = '" . (int)$product_attribute['attribute_id'] . "'");

					foreach ($product_attribute['product_attribute_description'] as $language_id => $product_attribute_description) {
						$this->db->query("DELETE FROM " . DB_PREFIX . "product_attribute WHERE product_id = '" . (int)$product_id . "' AND attribute_id = '" . (int)$product_attribute['attribute_id'] . "' AND language_id = '" . (int)$language_id . "'");

						$this->db->query("REPLACE INTO " . DB_PREFIX . "product_attribute SET product_id = '" . (int)$product_id . "', attribute_id = '" . (int)$product_attribute['attribute_id'] . "', language_id = '" . (int)$language_id . "', text = '" .  $this->db->escape($product_attribute_description['text']) . "'");
					}
				}
			}
		}

		if (isset($data['product_option'])) {
			foreach ($data['product_option'] as $product_option) {
				if ($product_option['type'] == 'select' || $product_option['type'] == 'radio' || $product_option['type'] == 'checkbox' || $product_option['type'] == 'image') {
					if (isset($product_option['product_option_value'])) {
						$this->db->query("REPLACE INTO " . DB_PREFIX . "product_option SET product_id = '" . (int)$product_id . "', option_id = '" . (int)$product_option['option_id'] . "', required = '" . (int)$product_option['required'] . "'");

						$product_option_id = $this->db->getLastId();

						foreach ($product_option['product_option_value'] as $product_option_value) {
							$this->db->query("REPLACE INTO " . DB_PREFIX . "product_option_value SET product_option_id = '" . (int)$product_option_id . "', product_id = '" . (int)$product_id . "', option_id = '" . (int)$product_option['option_id'] . "', option_value_id = '" . (int)$product_option_value['option_value_id'] . "', quantity = '" . (int)$product_option_value['quantity'] . "', subtract = '" . (int)$product_option_value['subtract'] . "', price = '" . (float)$product_option_value['price'] . "', price_prefix = '" . $this->db->escape($product_option_value['price_prefix']) . "', points = '" . (int)$product_option_value['points'] . "', points_prefix = '" . $this->db->escape($product_option_value['points_prefix']) . "', weight = '" . (float)$product_option_value['weight'] . "', weight_prefix = '" . $this->db->escape($product_option_value['weight_prefix']) . "'");
						}
					}
				} else {
					$this->db->query("REPLACE INTO " . DB_PREFIX . "product_option SET product_id = '" . (int)$product_id . "', option_id = '" . (int)$product_option['option_id'] . "', value = '" . $this->db->escape($product_option['value']) . "', required = '" . (int)$product_option['required'] . "'");
				}
			}
		}

		if (isset($data['product_special'])) {
			foreach ($data['product_special'] as $product_special) {
				$this->db->query("REPLACE INTO " . DB_PREFIX . "product_special SET product_id = '" . (int)$product_id . "', customer_group_id = '" . (int)$product_special['customer_group_id'] . "', priority = '" . (int)$product_special['priority'] . "', price = '" . (float)$product_special['price'] . "', date_start = '" . $this->db->escape($product_special['date_start']) . "', date_end = '" . $this->db->escape($product_special['date_end']) . "'");
			}
		}

		if (isset($data['product_image'])) {
			foreach ($data['product_image'] as $product_image) {
				$this->db->query("REPLACE INTO " . DB_PREFIX . "product_image SET product_id = '" . (int)$product_id . "', image = '" . $this->db->escape($product_image['image']) . "', sort_order = '" . (int)$product_image['sort_order'] . "'");
			}
		}

		if (isset($data['product_download'])) {
			foreach ($data['product_download'] as $download_id) {
				$this->db->query("REPLACE INTO " . DB_PREFIX . "product_to_download SET product_id = '" . (int)$product_id . "', download_id = '" . (int)$download_id . "'");
			}
		}

		//Keeping this in because it might be needed later. At the moment, products belong to 1 category but that can change
		if (isset($data['product_category'])) {
			foreach ($data['product_category'] as $category_id) {
				$this->db->query("REPLACE INTO " . DB_PREFIX . "product_to_category SET product_id = '" . (int)$product_id . "', category_id = '" . (int)$category_id . "'");
			}
		}

		if (isset($data['product_filter'])) {
			foreach ($data['product_filter'] as $filter_id) {
				$this->db->query("REPLACE INTO " . DB_PREFIX . "product_filter SET product_id = '" . (int)$product_id . "', filter_id = '" . (int)$filter_id . "'");
			}
		}

		if (isset($data['product_related'])) {
			foreach ($data['product_related'] as $related_id) {
				$this->db->query("DELETE FROM " . DB_PREFIX . "product_related WHERE product_id = '" . (int)$product_id . "' AND related_id = '" . (int)$related_id . "'");
				$this->db->query("REPLACE INTO " . DB_PREFIX . "product_related SET product_id = '" . (int)$product_id . "', related_id = '" . (int)$related_id . "'");
				$this->db->query("DELETE FROM " . DB_PREFIX . "product_related WHERE product_id = '" . (int)$related_id . "' AND related_id = '" . (int)$product_id . "'");
				$this->db->query("REPLACE INTO " . DB_PREFIX . "product_related SET product_id = '" . (int)$related_id . "', related_id = '" . (int)$product_id . "'");
			}
		}

		if (isset($data['product_reward'])) {
			foreach ($data['product_reward'] as $customer_group_id => $product_reward) {
				if ((int)$product_reward['points'] > 0) {
					$this->db->query("REPLACE INTO " . DB_PREFIX . "product_reward SET product_id = '" . (int)$product_id . "', customer_group_id = '" . (int)$customer_group_id . "', points = '" . (int)$product_reward['points'] . "'");
				}
			}
		}

		if (isset($data['product_layout'])) {
			foreach ($data['product_layout'] as $store_id => $layout_id) {
				$this->db->query("REPLACE INTO " . DB_PREFIX . "product_to_layout SET product_id = '" . (int)$product_id . "', store_id = '" . (int)$store_id . "', layout_id = '" . (int)$layout_id . "'");
			}
		}

		if (isset($data['keyword'])) {
			$this->db->query("REPLACE INTO " . DB_PREFIX . "url_alias SET query = 'product_id=" . (int)$product_id . "', keyword = '" . $this->db->escape($data['keyword']) . "'");
		}

		if (isset($data['product_recurring'])) {
			foreach ($data['product_recurring'] as $recurring) {
				$this->db->query("REPLACE INTO `" . DB_PREFIX . "product_recurring` SET `product_id` = " . (int)$product_id . ", customer_group_id = " . (int)$recurring['customer_group_id'] . ", `recurring_id` = " . (int)$recurring['recurring_id']);
			}
		}

		$this->cache->delete('product');

		return $product_id;
	}
}