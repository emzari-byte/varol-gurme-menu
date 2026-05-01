<?php
class ControllerExtensionModuleCashierPanel extends Controller {
	public function install() {
		$this->load->model('extension/module/cashier_panel');
		$this->model_extension_module_cashier_panel->install();
	}

	public function index() {
		if (!$this->user->hasPermission('access', 'extension/module/cashier_panel') || !$this->isCashierPanelEnabled()) {
			$this->response->redirect($this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true));
			return;
		}

		$this->document->setTitle('Kasa Paneli');
		$this->load->model('extension/module/cashier_panel');
		$this->model_extension_module_cashier_panel->install();

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

		$data['tables'] = $this->model_extension_module_cashier_panel->getOpenTables();
		$data['categories'] = $this->model_extension_module_cashier_panel->getCategories();
		$data['products'] = $this->model_extension_module_cashier_panel->getProducts();
		$data['tables_json'] = json_encode($data['tables']);
		$data['categories_json'] = json_encode($data['categories']);
		$data['products_json'] = json_encode($data['products']);
		$data['refresh_url'] = $this->url->link('extension/module/cashier_panel/refresh', 'user_token=' . $this->session->data['user_token'], true);
		$data['pay_url'] = $this->url->link('extension/module/cashier_panel/pay', 'user_token=' . $this->session->data['user_token'], true);
		$data['detail_url'] = $this->url->link('extension/module/cashier_panel/tableDetail', 'user_token=' . $this->session->data['user_token'], true);
		$data['products_url'] = $this->url->link('extension/module/cashier_panel/products', 'user_token=' . $this->session->data['user_token'], true);
		$data['add_product_url'] = $this->url->link('extension/module/cashier_panel/addProduct', 'user_token=' . $this->session->data['user_token'], true);
		$data['update_product_url'] = $this->url->link('extension/module/cashier_panel/updateProduct', 'user_token=' . $this->session->data['user_token'], true);
		$data['remove_product_url'] = $this->url->link('extension/module/cashier_panel/removeProduct', 'user_token=' . $this->session->data['user_token'], true);
		$data['mark_payment_pending_url'] = $this->url->link('extension/module/cashier_panel/markPaymentPending', 'user_token=' . $this->session->data['user_token'], true);
		$data['print_receipt_url'] = $this->url->link('extension/module/cashier_panel/printReceipt', 'user_token=' . $this->session->data['user_token'], true);
		$data['logout_url'] = $this->url->link('common/logout', 'user_token=' . $this->session->data['user_token'], true);
		$data['image_base'] = (!empty($this->request->server['HTTPS']) && $this->request->server['HTTPS'] !== 'off' ? HTTPS_CATALOG : HTTP_CATALOG) . 'image/';
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
			$json['tables'] = $this->model_extension_module_cashier_panel->getOpenTables();
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function tableDetail() {
		$json = array();

		if (!$this->user->hasPermission('access', 'extension/module/cashier_panel') || !$this->isCashierPanelEnabled()) {
			$json['success'] = false;
			$json['message'] = 'Yetki yok.';
		} else {
			$this->load->model('extension/module/cashier_panel');
			$table_id = isset($this->request->get['table_id']) ? (int)$this->request->get['table_id'] : 0;
			$detail = $this->model_extension_module_cashier_panel->getTableDetail($table_id);
			$json['success'] = !empty($detail);
			$json['detail'] = $detail;
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function products() {
		$json = array();

		if (!$this->user->hasPermission('access', 'extension/module/cashier_panel') || !$this->isCashierPanelEnabled()) {
			$json['success'] = false;
			$json['message'] = 'Yetki yok.';
		} else {
			$this->load->model('extension/module/cashier_panel');
			$category_id = isset($this->request->get['category_id']) ? (int)$this->request->get['category_id'] : 0;
			$search = isset($this->request->get['search']) ? trim((string)$this->request->get['search']) : '';
			$json['success'] = true;
			$json['products'] = $this->model_extension_module_cashier_panel->getProducts($category_id, $search);
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function addProduct() {
		$json = array();

		if (!$this->user->hasPermission('modify', 'extension/module/cashier_panel') || !$this->isCashierPanelEnabled()) {
			$json['success'] = false;
			$json['message'] = 'Yetkiniz yok.';
		} else {
			$this->load->model('extension/module/cashier_panel');
			$table_id = isset($this->request->post['table_id']) ? (int)$this->request->post['table_id'] : 0;
			$product_id = isset($this->request->post['product_id']) ? (int)$this->request->post['product_id'] : 0;
			$quantity = isset($this->request->post['quantity']) ? (int)$this->request->post['quantity'] : 1;
			$json = $this->model_extension_module_cashier_panel->addProductToTable($table_id, $product_id, $quantity, (int)$this->user->getId());
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function updateProduct() {
		$json = array();

		if (!$this->user->hasPermission('modify', 'extension/module/cashier_panel') || !$this->isCashierPanelEnabled()) {
			$json['success'] = false;
			$json['message'] = 'Yetkiniz yok.';
		} else {
			$this->load->model('extension/module/cashier_panel');
			$restaurant_order_product_id = isset($this->request->post['restaurant_order_product_id']) ? (int)$this->request->post['restaurant_order_product_id'] : 0;
			$quantity = isset($this->request->post['quantity']) ? (int)$this->request->post['quantity'] : 1;
			$json = $this->model_extension_module_cashier_panel->updateProductQuantity($restaurant_order_product_id, $quantity, (int)$this->user->getId());
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function removeProduct() {
		$json = array();

		if (!$this->user->hasPermission('modify', 'extension/module/cashier_panel') || !$this->isCashierPanelEnabled()) {
			$json['success'] = false;
			$json['message'] = 'Yetkiniz yok.';
		} else {
			$this->load->model('extension/module/cashier_panel');
			$restaurant_order_product_id = isset($this->request->post['restaurant_order_product_id']) ? (int)$this->request->post['restaurant_order_product_id'] : 0;
			$reason_code = isset($this->request->post['reason_code']) ? trim((string)$this->request->post['reason_code']) : '';
			$reason_text = isset($this->request->post['reason_text']) ? trim((string)$this->request->post['reason_text']) : '';
			$note = isset($this->request->post['note']) ? trim((string)$this->request->post['note']) : '';
			$json = $this->model_extension_module_cashier_panel->removeProductFromTable($restaurant_order_product_id, (int)$this->user->getId(), $this->user->getUserName(), $reason_code, $reason_text, $note);
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
			$amount_raw = isset($this->request->post['amount']) ? (string)$this->request->post['amount'] : '0';
			$amount_clean = strpos($amount_raw, ',') !== false
				? str_replace(',', '.', str_replace('.', '', $amount_raw))
				: $amount_raw;
			$amount = (float)preg_replace('/[^0-9.]/', '', $amount_clean);
			$note = isset($this->request->post['note']) ? trim((string)$this->request->post['note']) : '';

			$json = $this->model_extension_module_cashier_panel->payTablePartial($table_id, $payment_method, (int)$this->user->getId(), $note, $amount);
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function markPaymentPending() {
		$json = array();

		if (!$this->user->hasPermission('modify', 'extension/module/cashier_panel') || !$this->isCashierPanelEnabled()) {
			$json['success'] = false;
			$json['message'] = 'Yetkiniz yok.';
		} else {
			$this->load->model('extension/module/cashier_panel');
			$table_id = isset($this->request->post['table_id']) ? (int)$this->request->post['table_id'] : 0;
			$json = $this->model_extension_module_cashier_panel->markPaymentPending($table_id, (int)$this->user->getId(), $this->user->getUserName());
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function printReceipt() {
		if (!$this->user->hasPermission('access', 'extension/module/cashier_panel') || !$this->isCashierPanelEnabled()) {
			$this->response->addHeader('Content-Type: application/json');
			$this->response->setOutput(json_encode(array('success' => false, 'message' => 'Yetki yok.')));
			return;
		}

		$this->load->model('extension/module/cashier_panel');
		$table_id = isset($this->request->get['table_id']) ? (int)$this->request->get['table_id'] : 0;
		$html = $this->model_extension_module_cashier_panel->getReceiptHtml($table_id);

		$this->response->addHeader('Content-Type: text/html; charset=utf-8');
		$this->response->setOutput($html);
	}

	public function dailyReport() {
		$json = array();

		if (!$this->user->hasPermission('access', 'extension/module/cashier_panel') || !$this->isCashierPanelEnabled()) {
			$json['success'] = false;
			$json['message'] = 'Yetki yok.';
		} else {
			$this->load->model('extension/module/cashier_panel');
			$date = isset($this->request->get['date']) ? trim((string)$this->request->get['date']) : date('Y-m-d');
			$json['success'] = true;
			$json['report'] = $this->model_extension_module_cashier_panel->getDailyReport($date);
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
