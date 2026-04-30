<?php
class ControllerExtensionModuleRestaurantSettings extends Controller {
	private $error = array();

	public function index() {
		$this->document->setTitle('Restoran Ayarlari');

		$this->load->model('extension/module/restaurant_settings');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			if (isset($this->request->post['restaurant_waiter_call_reset_minutes'])) {
				$this->request->post['restaurant_waiter_call_reset_minutes'] = max(1, min(60, (int)$this->request->post['restaurant_waiter_call_reset_minutes']));
			}

			if (isset($this->request->post['restaurant_bill_request_reset_minutes'])) {
				$this->request->post['restaurant_bill_request_reset_minutes'] = max(1, min(60, (int)$this->request->post['restaurant_bill_request_reset_minutes']));
			}

			if (isset($this->request->post['restaurant_whatsapp_phone'])) {
				$this->request->post['restaurant_whatsapp_phone'] = preg_replace('/[^0-9]/', '', (string)$this->request->post['restaurant_whatsapp_phone']);
			}

			if (isset($this->request->post['restaurant_feedback_email'])) {
				$this->request->post['restaurant_feedback_email'] = trim((string)$this->request->post['restaurant_feedback_email']);
			}

			foreach (array('restaurant_brand_logo', 'restaurant_akinsoft_host', 'restaurant_akinsoft_port', 'restaurant_akinsoft_db_path', 'restaurant_akinsoft_charset', 'restaurant_akinsoft_bridge_url', 'restaurant_akinsoft_bridge_token', 'restaurant_akinsoft_company', 'restaurant_akinsoft_branch', 'restaurant_akinsoft_user') as $key) {
				if (isset($this->request->post[$key])) {
					$this->request->post[$key] = trim((string)$this->request->post[$key]);
				}
			}

			if (empty($this->request->post['restaurant_menu_theme']) || !in_array($this->request->post['restaurant_menu_theme'], array('v1', 'v2', 'v3', 'v4', 'v5'), true)) {
				$this->request->post['restaurant_menu_theme'] = 'v1';
			}

			if (empty($this->request->post['restaurant_akinsoft_mode']) || !in_array($this->request->post['restaurant_akinsoft_mode'], array('local_firebird', 'bridge_agent'), true)) {
				$this->request->post['restaurant_akinsoft_mode'] = 'bridge_agent';
			}

			$this->model_extension_module_restaurant_settings->editSettings($this->request->post);

			$this->session->data['success'] = 'Restoran ayarlari kaydedildi.';

			$this->response->redirect($this->url->link('extension/module/restaurant_settings', 'user_token=' . $this->session->data['user_token'], true));
		}

		$settings = $this->model_extension_module_restaurant_settings->getSettings();
		$defaults = $this->model_extension_module_restaurant_settings->getDefaults();
		$this->load->model('extension/module/restaurant_activity');

		foreach ($defaults as $key => $default) {
			if (isset($this->request->post[$key])) {
				$data[$key] = $this->request->post[$key];
			} elseif (isset($settings[$key])) {
				$data[$key] = $settings[$key];
			} else {
				$data[$key] = $default;
			}
		}

		$data['error_warning'] = isset($this->error['warning']) ? $this->error['warning'] : '';
		$data['success'] = isset($this->session->data['success']) ? $this->session->data['success'] : '';
		unset($this->session->data['success']);

		$data['breadcrumbs'] = array(
			array(
				'text' => 'Ana Sayfa',
				'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
			),
			array(
				'text' => 'Restoran Ayarlari',
				'href' => $this->url->link('extension/module/restaurant_settings', 'user_token=' . $this->session->data['user_token'], true)
			)
		);

		$data['action'] = $this->url->link('extension/module/restaurant_settings', 'user_token=' . $this->session->data['user_token'], true);
		$data['cancel'] = $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true);
		$data['restaurant_tables_url'] = $this->url->link('extension/module/restaurant_tables', 'user_token=' . $this->session->data['user_token'], true);
		$data['restaurant_waiters_url'] = $this->url->link('extension/module/restaurant_waiters', 'user_token=' . $this->session->data['user_token'], true);
		$data['allergen_url'] = $this->url->link('catalog/option/edit', 'user_token=' . $this->session->data['user_token'] . '&option_id=14', true);
		$data['recommendation_url'] = $this->url->link('extension/module/menu_recommendation_manager', 'user_token=' . $this->session->data['user_token'], true);
		$data['product_url'] = $this->url->link('catalog/product', 'user_token=' . $this->session->data['user_token'], true);
		$data['category_url'] = $this->url->link('catalog/category', 'user_token=' . $this->session->data['user_token'], true);
		$data['activities'] = $this->model_extension_module_restaurant_activity->getActivities(100);
		$data['production_checks'] = $this->model_extension_module_restaurant_settings->getProductionChecks();
		$data['akinsoft_test_url'] = $this->url->link('extension/module/restaurant_settings/testAkinsoftConnection', 'user_token=' . $this->session->data['user_token'], true);
		$data['akinsoft_sync_tables_url'] = $this->url->link('extension/module/restaurant_settings/syncAkinsoftTables', 'user_token=' . $this->session->data['user_token'], true);
		$data['akinsoft_sync_prices_url'] = $this->url->link('extension/module/restaurant_settings/syncAkinsoftPrices', 'user_token=' . $this->session->data['user_token'], true);
		$this->load->model('tool/image');

		if (!empty($data['restaurant_brand_logo']) && is_file(DIR_IMAGE . $data['restaurant_brand_logo'])) {
			$data['restaurant_brand_logo_thumb'] = $this->model_tool_image->resize($data['restaurant_brand_logo'], 100, 100);
		} else {
			$data['restaurant_brand_logo_thumb'] = $this->model_tool_image->resize('no_image.png', 100, 100);
		}

		$data['placeholder'] = $this->model_tool_image->resize('no_image.png', 100, 100);

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/module/restaurant_settings', $data));
	}

	public function testAkinsoftConnection() {
		$json = $this->runAkinsoftAction('testConnection');

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function syncAkinsoftTables() {
		$json = $this->runAkinsoftAction('syncTables');

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function syncAkinsoftPrices() {
		$json = $this->runAkinsoftAction('syncProductPrices');

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	private function runAkinsoftAction($method) {
		if (!$this->user->hasPermission('modify', 'extension/module/restaurant_settings') && !$this->user->hasPermission('modify', 'setting/setting')) {
			return array(
				'success' => false,
				'message' => 'Bu islemi yapma yetkiniz yok.'
			);
		}

		$this->load->model('extension/module/restaurant_settings');
		$this->load->model('extension/module/akinsoft');

		$settings = $this->model_extension_module_restaurant_settings->getSettings();

		foreach ($this->model_extension_module_restaurant_settings->getDefaults() as $key => $default) {
			if (isset($this->request->post[$key])) {
				$settings[$key] = $this->request->post[$key];
			}
		}

		return $this->model_extension_module_akinsoft->{$method}($settings);
	}

	protected function validate() {
		if (
			!$this->user->hasPermission('modify', 'extension/module/restaurant_settings') &&
			!$this->user->hasPermission('modify', 'setting/setting')
		) {
			$this->error['warning'] = 'Bu ayarlari degistirme yetkiniz yok.';
		}

		return !$this->error;
	}
}
