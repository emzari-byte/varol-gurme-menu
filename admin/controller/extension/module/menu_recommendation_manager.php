<?php
class ControllerExtensionModuleMenuRecommendationManager extends Controller {
	private $error = array();

	public function install() {
		$this->load->model('extension/module/menu_recommendation_manager');
		$this->model_extension_module_menu_recommendation_manager->install();
	}

	public function index() {
		$this->load->language('extension/module/menu_recommendation_manager');
		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('extension/module/menu_recommendation_manager');
		$this->model_extension_module_menu_recommendation_manager->install();

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			$this->model_extension_module_menu_recommendation_manager->saveGeneral($this->request->post);

			if (isset($this->request->post['profiles'])) {
				$this->model_extension_module_menu_recommendation_manager->saveProfiles($this->request->post['profiles']);
			}

			$this->session->data['success'] = $this->language->get('text_success');

			$this->response->redirect($this->url->link('extension/module/menu_recommendation_manager', 'user_token=' . $this->session->data['user_token'], true));
		}

		$data['heading_title'] = $this->language->get('heading_title');
		$data['text_edit'] = $this->language->get('text_edit');
		$data['text_general'] = $this->language->get('text_general');
		$data['text_products'] = $this->language->get('text_products');

		$data['text_enabled'] = $this->language->get('text_enabled');
		$data['text_disabled'] = $this->language->get('text_disabled');

		$data['text_role_main'] = $this->language->get('text_role_main');
		$data['text_role_drink'] = $this->language->get('text_role_drink');
		$data['text_role_dessert'] = $this->language->get('text_role_dessert');
		$data['text_role_hot_drink'] = $this->language->get('text_role_hot_drink');

		$data['text_mode_breakfast'] = $this->language->get('text_mode_breakfast');
		$data['text_mode_light'] = $this->language->get('text_mode_light');
		$data['text_mode_hearty'] = $this->language->get('text_mode_hearty');
		$data['text_mode_dessert_coffee'] = $this->language->get('text_mode_dessert_coffee');
		$data['text_mode_drink_only'] = $this->language->get('text_mode_drink_only');

		$data['entry_status'] = $this->language->get('entry_status');
		$data['entry_temperature_threshold'] = $this->language->get('entry_temperature_threshold');
		$data['entry_breakfast_end_hour'] = $this->language->get('entry_breakfast_end_hour');
		$data['entry_lunch_end_hour'] = $this->language->get('entry_lunch_end_hour');
		$data['entry_prevent_duplicates'] = $this->language->get('entry_prevent_duplicates');

		$data['column_product_id'] = $this->language->get('column_product_id');
		$data['column_name'] = $this->language->get('column_name');
		$data['column_category'] = $this->language->get('column_category');
		$data['column_status'] = $this->language->get('column_status');
		$data['column_role'] = $this->language->get('column_role');
		$data['column_pair_tag'] = $this->language->get('column_pair_tag');
		$data['column_priority'] = $this->language->get('column_priority');
		$data['column_modes'] = $this->language->get('column_modes');

		$data['help_pair_tag'] = $this->language->get('help_pair_tag');
		$data['help_priority'] = $this->language->get('help_priority');

		$data['button_save'] = $this->language->get('button_save');
		$data['button_cancel'] = $this->language->get('button_cancel');

		$data['error_warning'] = !empty($this->error['warning']) ? $this->error['warning'] : '';

		$data['breadcrumbs'] = array(
			array(
				'text' => $this->language->get('text_extension'),
				'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true)
			),
			array(
				'text' => $this->language->get('heading_title'),
				'href' => $this->url->link('extension/module/menu_recommendation_manager', 'user_token=' . $this->session->data['user_token'], true)
			)
		);

		$data['action'] = $this->url->link('extension/module/menu_recommendation_manager', 'user_token=' . $this->session->data['user_token'], true);
		$data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true);

		$configs = $this->model_extension_module_menu_recommendation_manager->getConfigs();

		$data['module_status'] = isset($this->request->post['module_status']) ? $this->request->post['module_status'] : (isset($configs['module_status']) ? $configs['module_status'] : 1);
		$data['temperature_threshold'] = isset($this->request->post['temperature_threshold']) ? $this->request->post['temperature_threshold'] : (isset($configs['temperature_threshold']) ? $configs['temperature_threshold'] : 10);
		$data['breakfast_end_hour'] = isset($this->request->post['breakfast_end_hour']) ? $this->request->post['breakfast_end_hour'] : (isset($configs['breakfast_end_hour']) ? $configs['breakfast_end_hour'] : 12);
		$data['lunch_end_hour'] = isset($this->request->post['lunch_end_hour']) ? $this->request->post['lunch_end_hour'] : (isset($configs['lunch_end_hour']) ? $configs['lunch_end_hour'] : 18);
		$data['prevent_duplicates'] = isset($this->request->post['prevent_duplicates']) ? $this->request->post['prevent_duplicates'] : (isset($configs['prevent_duplicates']) ? $configs['prevent_duplicates'] : 1);

		$products = $this->model_extension_module_menu_recommendation_manager->getProducts();
		$profiles = $this->model_extension_module_menu_recommendation_manager->getProductProfiles();

		foreach ($products as &$product) {
			$product_id = (int)$product['product_id'];

			if (isset($this->request->post['profiles'][$product_id])) {
				$product['profile'] = array(
					'status'   => !empty($this->request->post['profiles'][$product_id]['status']) ? 1 : 0,
					'role'     => isset($this->request->post['profiles'][$product_id]['role']) ? $this->request->post['profiles'][$product_id]['role'] : '',
					'pair_tag' => isset($this->request->post['profiles'][$product_id]['pair_tag']) ? $this->request->post['profiles'][$product_id]['pair_tag'] : '',
					'priority' => isset($this->request->post['profiles'][$product_id]['priority']) ? (int)$this->request->post['profiles'][$product_id]['priority'] : 0,
					'modes'    => isset($this->request->post['profiles'][$product_id]['modes']) ? $this->request->post['profiles'][$product_id]['modes'] : array()
				);
			} elseif (isset($profiles[$product_id])) {
				$product['profile'] = $profiles[$product_id];
			} else {
				$product['profile'] = array(
					'status'   => 0,
					'role'     => '',
					'pair_tag' => '',
					'priority' => 0,
					'modes'    => array()
				);
			}
		}
		unset($product);

		$data['products'] = $products;

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/module/menu_recommendation_manager', $data));
	}

	protected function validate() {
		if (!$this->user->hasPermission('modify', 'extension/module/menu_recommendation_manager')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		return !$this->error;
	}
}