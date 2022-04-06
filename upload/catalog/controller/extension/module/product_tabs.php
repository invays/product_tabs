<?php
class ControllerExtensionModuleProductTabs extends Controller{

	private $marketplace_path 		= 'marketplace/';
	private $module_name         	= 'product_tabs';
	private $module_type 			= "module";
	private $module_model   		= 'model_';
	private $module_path 			= "extension";
	private $module_template_path;
	private $error 					= [];
	private $app_config_path		= 'douxsoft';
	private $image_width;
	private $image_height;
	private $product_attribute_limit;
	private $product_description_limit;


	public function __construct($registry){
		try {
			parent::__construct($registry);

			/* Load library */
			/* $this->load->library("{$this->module_name}/{$this->module_name}"); */

			/* Load Simple HTML DOM */
			/* include_once DIR_SYSTEM . "library/{$this->app_config_path}/simple_html_dom.php"; */

			/* Models and Controllers path  */
			$this->module_template_path		= "{$this->module_path}/{$this->module_type}/{$this->module_name}/{$this->module_name}";
			$this->module_model 			= "{$this->module_model}{$this->module_path}_{$this->module_type}_{$this->module_name}";
			$this->module_path 				= "{$this->module_path}/{$this->module_type}/{$this->module_name}";

			/* Load languages */ 
			$this->load->language($this->module_path);

			/* Load models */
			/* $this->load->model($this->module_path); */
			$this->load->model('catalog/product');
			$this->load->model('tool/image');
			$this->load->model('setting/setting');

			$this->image_width = $this->config->get("{$this->module_type}_{$this->module_name}_picture_width");
			$this->image_height = $this->config->get("{$this->module_type}_{$this->module_name}_picture_heigth");
			$this->product_attribute_limit = $this->config->get("{$this->module_type}_{$this->module_name}_product_attribute_limit");
			$this->product_description_limit = $this->config->get("{$this->module_type}_{$this->module_name}_product_description_limit");
			

		} catch (Error $error) {
			$this->log->write($error->getMessage());
		}
	}

	public function index() {
		$sort_array = $this->config->get("{$this->module_type}_{$this->module_name}_tabs");
		uasort($sort_array, array($this,"cmp_function"));
		$data['module_tabs'] = $sort_array;
		$data['language_id'] = $this->config->get('config_language_id');
		/* $this->log->write($data['module_tabs']); */
		return $this->load->view("{$this->module_template_path}", $data);
	}

	public function cmp_function($a, $b){
		return ($a['tab_sort'] > $b['tab_sort']);
	}

	public function get_id_tab(){
		$sort_array = $this->config->get("{$this->module_type}_{$this->module_name}_tabs");
		uasort($sort_array, array($this,"cmp_function"));
		$module_tab_id = array_search($this->request->get['tab_id'], array_column($sort_array, 'tab_id'));
		$data['tab_id'] = $this->request->get['tab_id'];
		$data['slider_type'] = $this->config->get("{$this->module_type}_{$this->module_name}_slider_type");
		$data['attribute_status'] = $this->config->get("{$this->module_type}_{$this->module_name}_product_attribute");
		$data['description_status'] = $this->config->get("{$this->module_type}_{$this->module_name}_product_description");

		$data['products'] = [];

		/* $data['module_tab_option'] 	= [
			"latest"=>'Latest',
			"bestseller"=>'Bestseller',
			"special"=>'Special',
			"viewed"=>'Viewed',
			"custom"=>'Custom'
		]; */

		/* $this->log->write(json_encode($sort_array[$module_tab_id]['tab_source'])); */

		switch ($sort_array[$module_tab_id]['tab_source']) {
			case 'latest':
				
				$filter_data = array(
					'sort'  => 'p.date_added',
					'order' => 'DESC',
					'start' => 0,
					'limit' => $sort_array[$module_tab_id]['tab_limit']
				);
		
				$results = $this->model_catalog_product->getProducts($filter_data);
		
				if ($results) {
					foreach ($results as $result) {
						if ($result['image']) {
							$image = $this->model_tool_image->resize($result['image'], $this->image_width, $this->image_height);
						} else {
							$image = $this->model_tool_image->resize('placeholder.png', $this->image_width, $this->image_height);
						}
		
						if ($this->customer->isLogged() || !$this->config->get('config_customer_price')) {
							$price = $this->currency->format($this->tax->calculate($result['price'], $result['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
						} else {
							$price = false;
						}
		
						if ((float)$result['special']) {
							$special = $this->currency->format($this->tax->calculate($result['special'], $result['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
						} else {
							$special = false;
						}
		
						if ($this->config->get('config_tax')) {
							$tax = $this->currency->format((float)$result['special'] ? $result['special'] : $result['price'], $this->session->data['currency']);
						} else {
							$tax = false;
						}
		
						if ($this->config->get('config_review_status')) {
							$rating = $result['rating'];
						} else {
							$rating = false;
						}

						$attribute_groups = [];
						$data['attribute_limit'] = $this->product_attribute_limit;
				
						foreach ($this->model_catalog_product->getProductAttributes($result['product_id']) as $attribute_group) {
							$attributes = [];

							foreach ($attribute_group['attribute'] as $attribute) {
								$attributes[] = [
										"attribute_id" => $attribute['attribute_id'],
										"name" => html_entity_decode($attribute['name']),
										"text" => html_entity_decode($attribute['text'])
								];
							}
							$attribute_groups[] = [
										"attribute_group_id" 	=> $attribute_group['attribute_group_id'],
										"name" 					=> html_entity_decode($attribute_group['name']),
										"attribute" 			=> $attributes
							];
						}
		
						$data['products'][] = [
							'product_id'  => $result['product_id'],
							'thumb'       => $image,
							'name'        => $result['name'],
							'description' => utf8_substr(trim(strip_tags(html_entity_decode($result['description'], ENT_QUOTES, 'UTF-8'))), 0, $this->product_description_limit) . '..',
							'price'       => $price,
							'special'     => $special,
							'tax'         => $tax,
							'rating'      => $rating,
							'attribute_groups'  => $attribute_groups,
							'href'        => $this->url->link('product/product', 'product_id=' . $result['product_id'])
						];
					}
		
				}
				break;
			case 'bestseller':
				$results = $this->model_catalog_product->getBestSellerProducts($sort_array[$module_tab_id]['tab_limit']);

				if ($results) {
					foreach ($results as $result) {
						if ($result['image']) {
							$image = $this->model_tool_image->resize($result['image'], $this->image_width, $this->image_height);
						} else {
							$image = $this->model_tool_image->resize('placeholder.png', $this->image_width, $this->image_height);
						}

						if ($this->customer->isLogged() || !$this->config->get('config_customer_price')) {
							$price = $this->currency->format($this->tax->calculate($result['price'], $result['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
						} else {
							$price = false;
						}

						if ((float)$result['special']) {
							$special = $this->currency->format($this->tax->calculate($result['special'], $result['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
						} else {
							$special = false;
						}

						if ($this->config->get('config_tax')) {
							$tax = $this->currency->format((float)$result['special'] ? $result['special'] : $result['price'], $this->session->data['currency']);
						} else {
							$tax = false;
						}

						if ($this->config->get('config_review_status')) {
							$rating = $result['rating'];
						} else {
							$rating = false;
						}

						$attribute_groups = [];
						$data['attribute_limit'] = $this->product_attribute_limit;
				
						foreach ($this->model_catalog_product->getProductAttributes($result['product_id']) as $attribute_group) {
							$attributes = [];

							foreach ($attribute_group['attribute'] as $attribute) {
								$attributes[] = [
										"attribute_id" => $attribute['attribute_id'],
										"name" => html_entity_decode($attribute['name']),
										"text" => html_entity_decode($attribute['text'])
								];
							}
							$attribute_groups[] = [
										"attribute_group_id" 	=> $attribute_group['attribute_group_id'],
										"name" 					=> html_entity_decode($attribute_group['name']),
										"attribute" 			=> $attributes
							];
						}

						$data['products'][] = array(
							'product_id'  => $result['product_id'],
							'thumb'       => $image,
							'name'        => $result['name'],
							'description' => utf8_substr(trim(strip_tags(html_entity_decode($result['description'], ENT_QUOTES, 'UTF-8'))), 0, $this->product_description_limit) . '..',
							'price'       => $price,
							'special'     => $special,
							'tax'         => $tax,
							'rating'      => $rating,
							'attribute_groups'  => $attribute_groups,
							'href'        => $this->url->link('product/product', 'product_id=' . $result['product_id'])
						);
					}
				}
				break;
			case 'special':
				$filter_data = array(
					'sort'  => 'pd.name',
					'order' => 'ASC',
					'start' => 0,
					'limit' => $sort_array[$module_tab_id]['tab_limit']
				);
		
				$results = $this->model_catalog_product->getProductSpecials($filter_data);
		
				if ($results) {
					foreach ($results as $result) {
						if ($result['image']) {
							$image = $this->model_tool_image->resize($result['image'], $this->image_width, $this->image_height);
						} else {
							$image = $this->model_tool_image->resize('placeholder.png', $this->image_width, $this->image_height);
						}
		
						if ($this->customer->isLogged() || !$this->config->get('config_customer_price')) {
							$price = $this->currency->format($this->tax->calculate($result['price'], $result['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
						} else {
							$price = false;
						}
		
						if ((float)$result['special']) {
							$special = $this->currency->format($this->tax->calculate($result['special'], $result['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
						} else {
							$special = false;
						}
		
						if ($this->config->get('config_tax')) {
							$tax = $this->currency->format((float)$result['special'] ? $result['special'] : $result['price'], $this->session->data['currency']);
						} else {
							$tax = false;
						}
		
						if ($this->config->get('config_review_status')) {
							$rating = $result['rating'];
						} else {
							$rating = false;
						}

						$attribute_groups = [];
						$data['attribute_limit'] = $this->product_attribute_limit;
				
						foreach ($this->model_catalog_product->getProductAttributes($result['product_id']) as $attribute_group) {
							$attributes = [];

							foreach ($attribute_group['attribute'] as $attribute) {
								$attributes[] = [
										"attribute_id" => $attribute['attribute_id'],
										"name" => html_entity_decode($attribute['name']),
										"text" => html_entity_decode($attribute['text'])
								];
							}
							$attribute_groups[] = [
										"attribute_group_id" 	=> $attribute_group['attribute_group_id'],
										"name" 					=> html_entity_decode($attribute_group['name']),
										"attribute" 			=> $attributes
							];
						}
		
						$data['products'][] = array(
							'product_id'  => $result['product_id'],
							'thumb'       => $image,
							'name'        => $result['name'],
							'description' => utf8_substr(trim(strip_tags(html_entity_decode($result['description'], ENT_QUOTES, 'UTF-8'))), 0, $this->product_description_limit) . '..',
							'price'       => $price,
							'special'     => $special,
							'tax'         => $tax,
							'rating'      => $rating,
							'attribute_groups'  => $attribute_groups,
							'href'        => $this->url->link('product/product', 'product_id=' . $result['product_id'])
						);
					}
				}
				break;
			case 'viewed':
				$filter_data = array(
					'sort'  => 'p.viewed',
					'order' => 'ASC',
					'start' => 0,
					'limit' => $sort_array[$module_tab_id]['tab_limit']
				);
		
				$results = $this->model_catalog_product->getProducts($filter_data);
		
				if ($results) {
					foreach ($results as $result) {
						if ($result['image']) {
							$image = $this->model_tool_image->resize($result['image'], $this->image_width, $this->image_height);
						} else {
							$image = $this->model_tool_image->resize('placeholder.png', $this->image_width, $this->image_height);
						}
		
						if ($this->customer->isLogged() || !$this->config->get('config_customer_price')) {
							$price = $this->currency->format($this->tax->calculate($result['price'], $result['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
						} else {
							$price = false;
						}
		
						if ((float)$result['special']) {
							$special = $this->currency->format($this->tax->calculate($result['special'], $result['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
						} else {
							$special = false;
						}
		
						if ($this->config->get('config_tax')) {
							$tax = $this->currency->format((float)$result['special'] ? $result['special'] : $result['price'], $this->session->data['currency']);
						} else {
							$tax = false;
						}
		
						if ($this->config->get('config_review_status')) {
							$rating = $result['rating'];
						} else {
							$rating = false;
						}

						$attribute_groups = [];
						$data['attribute_limit'] = $this->product_attribute_limit;
				
						foreach ($this->model_catalog_product->getProductAttributes($result['product_id']) as $attribute_group) {
							$attributes = [];

							foreach ($attribute_group['attribute'] as $attribute) {
								$attributes[] = [
										"attribute_id" => $attribute['attribute_id'],
										"name" => html_entity_decode($attribute['name']),
										"text" => html_entity_decode($attribute['text'])
								];
							}
							$attribute_groups[] = [
										"attribute_group_id" 	=> $attribute_group['attribute_group_id'],
										"name" 					=> html_entity_decode($attribute_group['name']),
										"attribute" 			=> $attributes
							];
						}
		
						$data['products'][] = [
							'product_id'  => $result['product_id'],
							'thumb'       => $image,
							'name'        => $result['name'],
							'description' => utf8_substr(trim(strip_tags(html_entity_decode($result['description'], ENT_QUOTES, 'UTF-8'))), 0, $this->product_description_limit) . '..',
							'price'       => $price,
							'special'     => $special,
							'tax'         => $tax,
							'rating'      => $rating,
							'attribute_groups'  => $attribute_groups,
							'href'        => $this->url->link('product/product', 'product_id=' . $result['product_id'])
						];

					}
					$this->log->write(json_encode($data['products'][0]['attribute_groups']));
		
				}
				break;
			case 'custom':
				$products = $sort_array[$module_tab_id]['module_custom_product'];
				foreach ($products as $product_id => $product_name) {
					$result = $this->model_catalog_product->getProduct($product_id);

					if ($result['image']) {
						$image = $this->model_tool_image->resize($result['image'], $this->image_width, $this->image_height);
					} else {
						$image = $this->model_tool_image->resize('placeholder.png', $this->image_width, $this->image_height);
					}

					if ($this->config->get('config_tax')) {
						$tax = $this->currency->format((float)$result['special'] ? $result['special'] : $result['price'], $this->session->data['currency']);
					} else {
						$tax = false;
					}

					$attribute_groups = [];
					$data['attribute_limit'] = $this->product_attribute_limit;
				
					foreach ($this->model_catalog_product->getProductAttributes($result['product_id']) as $attribute_group) {
						$attributes = [];

						foreach ($attribute_group['attribute'] as $attribute) {
							$attributes[] = [
									"attribute_id" => $attribute['attribute_id'],
									"name" => html_entity_decode($attribute['name']),
									"text" => html_entity_decode($attribute['text'])
							];
						}
						$attribute_groups[] = [
									"attribute_group_id" 	=> $attribute_group['attribute_group_id'],
									"name" 					=> html_entity_decode($attribute_group['name']),
									"attribute" 			=> $attributes
						];
					}

					$data['products'][] = [
						'product_id'  => $result['product_id'],
						'thumb'       => $image,
						'name'        => $result['name'],
						'description' => utf8_substr(trim(strip_tags(html_entity_decode($result['description'], ENT_QUOTES, 'UTF-8'))), 0, $this->product_description_limit) . '..',
						'price'       => $result['price'],
						'special'     => $result['special'],
						'tax'         => $tax,
						'rating'      => $result['rating'],
						'attribute_groups'  => $attribute_groups,
						'href'        => $this->url->link('product/product', 'product_id=' . $result['product_id'])
					];
				}
				break;
			case 'category':
				$results = [];
				$categories = $sort_array[$module_tab_id]['module_custom_category'];
			
				foreach ($categories as $category_id => $category_name) {

					$filter_data = array(
						'filter_category_id' => $category_id,
						'filter_filter'      => [],
						'sort'  			 => 'p.sort_order',
						'order'  			 => 'ASC',
						'start'              => 0,
						'limit'              => $sort_array[$module_tab_id]['tab_limit']
					);

					
	
					$products_result = $this->model_catalog_product->getProducts($filter_data);
					foreach ($products_result as $products_result) {
						$results[] = $products_result;
					}
				}

				foreach ($results as $result) {
					if ($result['image']) {
						$image = $this->model_tool_image->resize($result['image'], $this->image_width, $this->image_height);
					} else {
						$image = $this->model_tool_image->resize('placeholder.png', $this->image_width, $this->image_height);
					}
	
					if ($this->customer->isLogged() || !$this->config->get('config_customer_price')) {
						$price = $this->currency->format($this->tax->calculate($result['price'], $result['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
					} else {
						$price = false;
					}
	
					if ((float)$result['special']) {
						$special = $this->currency->format($this->tax->calculate($result['special'], $result['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
					} else {
						$special = false;
					}
	
					if ($this->config->get('config_tax')) {
						$tax = $this->currency->format((float)$result['special'] ? $result['special'] : $result['price'], $this->session->data['currency']);
					} else {
						$tax = false;
					}
	
					if ($this->config->get('config_review_status')) {
						$rating = (int)$result['rating'];
					} else {
						$rating = false;
					}
	
					$data['products'][] = array(
						'product_id'  => $result['product_id'],
						'thumb'       => $image,
						'name'        => $result['name'],
						'description' => utf8_substr(trim(strip_tags(html_entity_decode($result['description'], ENT_QUOTES, 'UTF-8'))), 0, $this->product_description_limit) . '..',
						'price'       => $price,
						'special'     => $special,
						'tax'         => $tax,
						'minimum'     => $result['minimum'] > 0 ? $result['minimum'] : 1,
						'rating'      => $result['rating'],
						'href'        => $this->url->link('product/product', 'product_id=' . $result['product_id'])
					);
				}

				break;
			default:
				$filter_data = array(
					'sort'  => 'p.date_added',
					'order' => 'DESC',
					'start' => 0,
					'limit' => $sort_array[$module_tab_id]['tab_limit']
				);
		
				$results = $this->model_catalog_product->getProducts($filter_data);
		
				if ($results) {
					foreach ($results as $result) {
						if ($result['image']) {
							$image = $this->model_tool_image->resize($result['image'], $this->image_width, $this->image_height);
						} else {
							$image = $this->model_tool_image->resize('placeholder.png', $this->image_width, $this->image_height);
						}
		
						if ($this->customer->isLogged() || !$this->config->get('config_customer_price')) {
							$price = $this->currency->format($this->tax->calculate($result['price'], $result['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
						} else {
							$price = false;
						}
		
						if ((float)$result['special']) {
							$special = $this->currency->format($this->tax->calculate($result['special'], $result['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
						} else {
							$special = false;
						}
		
						if ($this->config->get('config_tax')) {
							$tax = $this->currency->format((float)$result['special'] ? $result['special'] : $result['price'], $this->session->data['currency']);
						} else {
							$tax = false;
						}
		
						if ($this->config->get('config_review_status')) {
							$rating = $result['rating'];
						} else {
							$rating = false;
						}
						
						$attribute_groups = [];
						$data['attribute_limit'] = $this->product_attribute_limit;
				
						foreach ($this->model_catalog_product->getProductAttributes($result['product_id']) as $attribute_group) {
							$attributes = [];

							foreach ($attribute_group['attribute'] as $attribute) {
								$attributes[] = [
										"attribute_id" => $attribute['attribute_id'],
										"name" => html_entity_decode($attribute['name']),
										"text" => html_entity_decode($attribute['text'])
								];
							}
							$attribute_groups[] = [
										"attribute_group_id" 	=> $attribute_group['attribute_group_id'],
										"name" 					=> html_entity_decode($attribute_group['name']),
										"attribute" 			=> $attributes
							];
						}
		
						$data['products'][] = [
							'product_id'  => $result['product_id'],
							'thumb'       => $image,
							'name'        => $result['name'],
							'description' => utf8_substr(trim(strip_tags(html_entity_decode($result['description'], ENT_QUOTES, 'UTF-8'))), 0, $this->product_description_limit) . '..',
							'price'       => $price,
							'special'     => $special,
							'tax'         => $tax,
							'rating'      => $rating,
							'attribute_groups'  => $attribute_groups,
							'href'        => $this->url->link('product/product', 'product_id=' . $result['product_id'])
						];
					}
		
				}
				break;
		}

		return $this->response->setOutput($this->load->view("extension/module/product_tabs/product_tabs_tab_content", $data));
	}

}