<?php
class ControllerExtensionModuleCashierPanel extends Controller {
	public function index() {
		if (!$this->user->hasPermission('access', 'extension/module/cashier_panel') || !$this->isCashierPanelEnabled()) {
			$this->response->redirect($this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true));
			return;
		}

		$this->document->setTitle('Kasa Paneli');
		$this->load->model('extension/module/cashier_panel');

		$data['breadcrumbs'] = array(
			array(
				'text' => 'Ana Sayfa',
				'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
			),
			array(
				'text' => 'Kasa Paneli',
				'href' => $this->url->link('extension/module/cashier_panel', 'user_token=' . $this->session->data['user_token'], true)
			)
		);

		$data['summary'] = $this->model_extension_module_cashier_panel->getSummary();
		$data['open_tables'] = $this->model_extension_module_cashier_panel->getOpenTables();
		$data['payments'] = $this->model_extension_module_cashier_panel->getTodayPayments();
		$data['open_tables_json'] = json_encode($data['open_tables']);
		$data['payments_json'] = json_encode($data['payments']);
		$data['refresh_url'] = $this->url->link('extension/module/cashier_panel/refresh', 'user_token=' . $this->session->data['user_token'], true);
		$data['pay_url'] = $this->url->link('extension/module/cashier_panel/pay', 'user_token=' . $this->session->data['user_token'], true);
		$data['logout_url'] = $this->url->link('common/logout', 'user_token=' . $this->session->data['user_token'], true);
		$data['cashier_fullscreen'] = $this->isCashierOnlyUser() ? 1 : 0;
		$data['cashier_username'] = $this->user->getUserName();
		$data['base'] = HTTP_SERVER;
		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/module/cashier_panel', $data));
	}

	public function refresh() {
		$json = array();

		if (!$this->user->hasPermission('access', 'extension/module/cashier_panel') || !$this->isCashierPanelEnabled()) {
			$json['success'] = false;
			$json['message'] = 'Yetki yok.';
		} else {
			$this->load->model('extension/module/cashier_panel');
			$json['success'] = true;
			$json['summary'] = $this->model_extension_module_cashier_panel->getSummary();
			$json['open_tables'] = $this->model_extension_module_cashier_panel->getOpenTables();
			$json['payments'] = $this->model_extension_module_cashier_panel->getTodayPayments();
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function pay() {
		$json = array();

		if (!$this->user->hasPermission('modify', 'extension/module/cashier_panel') || !$this->isCashierPanelEnabled()) {
			$json['success'] = false;
			$json['message'] = 'Yetkiniz yok.';
		} else {
			$this->load->model('extension/module/cashier_panel');

			$table_id = isset($this->request->post['table_id']) ? (int)$this->request->post['table_id'] : 0;
			$payment_method = isset($this->request->post['payment_method']) ? trim((string)$this->request->post['payment_method']) : '';
			$note = isset($this->request->post['note']) ? trim((string)$this->request->post['note']) : '';

			$json = $this->model_extension_module_cashier_panel->payTable($table_id, $payment_method, (int)$this->user->getId(), $note);
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	private function isCashierPanelEnabled() {
		return $this->getRestaurantSettingValue('restaurant_cashier_panel', 1) === 1
			&& $this->getRestaurantSettingValue('restaurant_akinsoft_enabled', 0) !== 1;
	}

	private function getRestaurantSettingValue($key, $default = 1) {
		$table = DB_PREFIX . 'ayarlar';
		$exists = $this->db->query("SHOW TABLES LIKE '" . $this->db->escape($table) . "'");

		if (!$exists->num_rows) {
			return (int)$default;
		}

		$query = $this->db->query("SELECT ayar_value FROM `" . $table . "`
			WHERE ayar_key = '" . $this->db->escape($key) . "'
			LIMIT 1");

		return $query->num_rows ? (int)$query->row['ayar_value'] : (int)$default;
	}

	private function isCashierOnlyUser() {
		if (
			!$this->isCashierPanelEnabled() ||
			!$this->user->hasPermission('access', 'extension/module/cashier_panel')
		) {
			return false;
		}

		$broader_routes = array(
			'extension/module/waiter_panel',
			'extension/module/kitchen_display',
			'extension/module/restaurant_settings',
			'extension/module/restaurant_tables',
			'extension/module/restaurant_waiters',
			'extension/module/restaurant_home_products',
			'catalog/product',
			'catalog/category',
			'user/user',
			'user/user_permission',
			'setting/setting'
		);

		foreach ($broader_routes as $route) {
			if ($this->user->hasPermission('access', $route)) {
				return false;
			}
		}

		return true;
	}
}
