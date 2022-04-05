<?php
class ControllerExtensionModuleProductTabs extends Controller {
	private $marketplace_path 		= 'marketplace/';
	private $module_name         	= 'product_tabs';
	private $module_type 			= "module";
	private $module_model   		= 'model_';
	private $module_path 			= "extension";
	private $module_template_path;
	private $token 					= 'user_token';
	private $error 					= [];
	private $app_config_path		= 'douxsoft';
	private $module_templates 		= [];
	private $module_datas 			= [
		[
			'tab_name' => 'tab_general',
			'tab_priority' => 1,
			'tab_forms' => [
				'name_title' => [
					'type'		=> 'custom',
					'required'	=> false
				],
				'slider_type' => [
					'type'		=> 'select',
					'required'	=> false,
					'options'	=> [
						'slider' 	=> 'Slider',
						'tile'		=> 'Tile'
					]
				],
				'product_attribute' => [
					'type'		=> 'select',
					'required'	=> false
				],
				'product_attribute_limit' => [
					'type'		=> 'text_input',
					'required'	=> false
				],
				'product_description' => [
					'type'		=> 'select',
					'required'	=> false
				],
				'product_description_limit' => [
					'type'		=> 'text_input',
					'required'	=> false
				],
				'picture_width' => [
					'type'		=> 'text_input',
					'required'	=> false
				],
				'picture_heigth' => [
					'type'		=> 'text_input',
					'required'	=> false
				],
				'status' => [
					'type'		=> 'select',
					'required'	=> false
				],
			]
		],
		[
			'tab_name' => 'tab_tabs',
			'tab_priority' => 0,
			'tab_forms' => [
				'tabs' => [
					'type'		=> 'custom',
					'required'	=> false
				],
			]
		]
	];

	public function __construct($registry){
		try {
			parent::__construct($registry);

			/* Load library */
			/* $this->load->library("{$this->module_name}/{$this->module_name}"); */

			/* Load Simple HTML DOM */
			//include_once DIR_SYSTEM . "library/{$this->app_config_path}/simple_html_dom.php";
			
			/* Models and Controllers path  */
			$this->marketplace_path			= "{$this->marketplace_path}{$this->module_path}";
			$this->module_model 			= "{$this->module_model}{$this->module_path}_{$this->module_type}_{$this->module_name}";
			$this->module_template_path		= "{$this->module_path}/{$this->module_type}/{$this->module_name}/{$this->module_name}";
			$this->module_path 				= "{$this->module_path}/{$this->module_type}/{$this->module_name}";

			/* Load languages */ 
			$this->load->language($this->module_path);

			/* Load models */
			/* $this->load->model($this->module_path); */
			$this->load->model('localisation/language');
			$this->load->model('setting/setting');
			$this->load->model('setting/event');
			/* $this->load->model('tool/image'); */

			/* Templates datas */
			// Datas tab
			foreach ($this->module_datas as $tab_key => $tab) {
				if (isset($tab['tab_childrens'])){
					foreach ($tab['tab_childrens'] as $children_tab_key => $children_tab) {
						foreach ($this->module_datas[$tab_key]['tab_childrens'][$children_tab_key]['tab_forms'] as $key => $value) {
							$this->module_datas[$tab_key]['tab_childrens'][$children_tab_key]['tab_forms'][$key]['tab'] = $this->module_datas[$tab_key]['tab_childrens'][$children_tab_key]['tab_name'];
						}
					}
				}
				foreach ($tab['tab_forms'] as $key => $value) {
					$this->module_datas[$tab_key]['tab_forms'][$key]['tab'] = $this->module_datas[$tab_key]['tab_name'];
				}
			}

			// Datas templates
			$this->module_templates = [
				'forms' => "{$this->module_template_path}_forms.twig",
			];
		} catch (Throwable $error) {
			$this->log->write($error->getMessage());
		}
	}

	public function index(): void {
		try {
			$this->document->setTitle($this->language->get('heading_title'));

			if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
				$this->model_setting_setting->editSetting("{$this->module_type}_{$this->module_name}", $this->request->post);
				$this->session->data['success'] = $this->language->get('text_success');
				//$this->log->write(json_encode($this->request->post));
				//$this->response->redirect($this->url->link($this->marketplace_path, "{$this->token}=" . $this->session->data[$this->token] . "&type={$this->module_type}", true));
			}

			$data['template'] = $this->module_templates;

			$data['request_url']	= "index.php?route={$this->module_path}";
			$data['entry']			= 'entry_';
			$data['help']			= 'help_';
			$data['text']			= 'text_';
			$data['form_name']	 	= $this->module_name;
			$data['module_name'] 	= "{$this->module_type}_{$this->module_name}_";
			$data['module_datas'] 	= $this->module_datas;
			
			$data['error_warning'] = (isset($this->error['warning'])) ? $this->error['warning'] : '';

			$data['user_token'] = $this->session->data[$this->token];
			
			$data['breadcrumbs'] = [];

			$data['breadcrumbs'][] = [
				'text' => $this->language->get('text_home'),
				'href' => $this->url->link('common/dashboard', "{$this->token}=" . $this->session->data[$this->token], true)
			];

			$data['breadcrumbs'][] = [
				'text' => $this->language->get('text_extension'),
				'href' => $this->url->link($this->marketplace_path, "{$this->token}=" . $this->session->data[$this->token] . "&type={$this->module_type}", true)
			];

			$data['breadcrumbs'][] = [
				'text' => $this->language->get('heading_title'),
				'href' => $this->url->link("{$this->module_path}", "{$this->token}=" . $this->session->data[$this->token], true)
			];

			$data['action'] = $this->url->link("{$this->module_path}", "{$this->token}=" . $this->session->data[$this->token], true);

			$data['cancel'] = $this->url->link($this->marketplace_path, "{$this->token}=" . $this->session->data[$this->token] . "&type={$this->module_type}", true);

			foreach ($this->module_datas as $tab_key => $tab) {
				if (isset($tab['tab_childrens'])){
					foreach ($tab['tab_childrens'] as $children_tab_key => $children_tab) {
						foreach ($this->module_datas[$tab_key]['tab_childrens'][$children_tab_key]['tab_forms'] as $key => $value) {
							if (isset($this->request->post["{$this->module_type}_{$this->module_name}_{$key}"])) {
								$data["{$this->module_type}_{$this->module_name}_{$key}"] = $this->request->post["{$this->module_type}_{$this->module_name}_{$key}"];
							} else {
								$data["{$this->module_type}_{$this->module_name}_{$key}"] = $this->config->get("{$this->module_type}_{$this->module_name}_{$key}");
							}
						}
					}
				}
				foreach ($tab['tab_forms'] as $key => $value) {
					if (isset($this->request->post["{$this->module_type}_{$this->module_name}_{$key}"])) {
						$data["{$this->module_type}_{$this->module_name}_{$key}"] = $this->request->post["{$this->module_type}_{$this->module_name}_{$key}"];
					} else {
						$data["{$this->module_type}_{$this->module_name}_{$key}"] = $this->config->get("{$this->module_type}_{$this->module_name}_{$key}");
					}
				}
			}

			$data['module_tab_option'] 	= [
				"latest"=>'Latest',
				"bestseller"=>'Bestseller',
				"special"=>'Special',
				"viewed"=>'Viewed',
				"custom"=>'Custom'
			];

			// Languages 
			$data['languages'] 		= $this->model_localisation_language->getLanguages(); 

			$data['header'] 		= $this->load->controller('common/header');
			$data['column_left'] 	= $this->load->controller('common/column_left');
			$data['footer'] 		= $this->load->controller('common/footer');

			$this->response->setOutput($this->load->view("{$this->module_template_path}", $data));
		} catch (Throwable $error) {
			$this->log->write($error->getMessage());
		}
	}

	/* public function install(): void {
		try {
			//$this->{$this->module_model}->installDB();
		} catch (Throwable $error) {
			$this->log->write($error->getMessage());
		}
	} */

	public function uninstall(): void {
		try {
			///$this->{$this->module_model}->uninstallDB();

			// Delete module settings 
			$this->model_setting_setting->deleteSetting("{$this->module_type}_{$this->module_name}");

		} catch (Throwable $error) {
			$this->log->write($error->getMessage());
		}
	}

	protected function validate() {
		try {
			// check AJAX request! 
			/* if (!isset($this->request->server['HTTP_X_REQUESTED_WITH']) && !empty($this->request->server['HTTP_X_REQUESTED_WITH']) && strtolower($this->request->server['HTTP_X_REQUESTED_WITH']) != 'xmlhttprequest' ) {
				$this->json['error_warning']['xmlhttprequest'] = $this->language->get('error_xmlhttprequest');
			} */
			
			foreach ($this->module_datas as $tab_key => $tab) {
				if (isset($tab['tab_childrens'])){
					foreach ($tab['tab_childrens'] as $children_tab_key => $children_tab) {
						foreach ($this->module_datas[$tab_key]['tab_childrens'][$children_tab_key]['tab_forms'] as $key => $value) {
							if ($value['required']){
								if (empty($this->request->post["{$this->module_type}_{$this->module_name}_{$key}"])){
									$this->error['warning'] = $this->language->get("error_{$key}");
								}
							}
						}
					}
				}
				foreach ($tab['tab_forms'] as $key => $value) {
					if ($value['required']){
						if (empty($this->request->post["{$this->module_type}_{$this->module_name}_{$key}"])){
							$this->error['warning'] = $this->language->get("error_{$key}");
						}
					}
				}
			}

			if (!$this->user->hasPermission('modify', "{$this->module_path}")) {
				$this->error['warning'] = $this->language->get('error_permission');
			}

			return !$this->error;
		} catch (Throwable $error) {
			$this->log->write($error->getMessage());
		}
	}
}