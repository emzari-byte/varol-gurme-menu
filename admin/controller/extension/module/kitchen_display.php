<?php
class ControllerExtensionModuleKitchenDisplay extends Controller {
	public function index() {
		if (!$this->user->hasPermission('access', 'extension/module/kitchen_display')) {
			$this->response->redirect($this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true));
			return;
		}

		if (!$this->isRestaurantSettingEnabled('restaurant_kitchen_panel', 1)) {
			$this->response->redirect($this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true));
			return;
		}

		$this->document->setTitle('Mutfak Ekranı');
		$this->document->addStyle('/menu/css/kitchen-display.css');

		$this->load->model('extension/module/kitchen_display');

		$data['breadcrumbs'] = array(
			array(
				'text' => 'Ana Sayfa',
				'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
			),
			array(
				'text' => 'Mutfak Ekranı',
				'href' => $this->url->link('extension/module/kitchen_display', 'user_token=' . $this->session->data['user_token'], true)
			)
		);

		$data['orders'] = $this->model_extension_module_kitchen_display->getKitchenOrders();
		$data['orders_json'] = json_encode($data['orders'], JSON_UNESCAPED_UNICODE);

		$data['refresh_url'] = $this->url->link('extension/module/kitchen_display/refresh', 'user_token=' . $this->session->data['user_token'], true);
		$data['status_url']  = $this->url->link('extension/module/kitchen_display/updateStatus', 'user_token=' . $this->session->data['user_token'], true);

		$data['header']      = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer']      = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/module/kitchen_display', $data));
	}

	public function refresh() {
		$json = array();

		if (!$this->user->hasPermission('access', 'extension/module/kitchen_display') || !$this->isRestaurantSettingEnabled('restaurant_kitchen_panel', 1)) {
			$json['success'] = false;
			$json['message'] = 'Yetki yok';
			$json['orders'] = array();
		} else {
			$this->load->model('extension/module/kitchen_display');

			$json['success'] = true;
			$json['orders'] = $this->model_extension_module_kitchen_display->getKitchenOrders();
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json, JSON_UNESCAPED_UNICODE));
	}

	public function updateStatus() {
		$json = array();

		if (!$this->user->hasPermission('modify', 'extension/module/kitchen_display') || !$this->isRestaurantSettingEnabled('restaurant_kitchen_panel', 1)) {
			$json['success'] = false;
			$json['message'] = 'Yetkiniz yok!';
		} else {
			$this->load->model('extension/module/kitchen_display');

			$restaurant_order_id = isset($this->request->post['restaurant_order_id']) ? (int)$this->request->post['restaurant_order_id'] : 0;
			$service_status = isset($this->request->post['service_status']) ? trim((string)$this->request->post['service_status']) : '';

			$allowed = array('in_kitchen', 'ready_for_service');

			if (!$restaurant_order_id || !in_array($service_status, $allowed)) {
				$json['success'] = false;
				$json['message'] = 'Geçersiz veri!';
			} else {
				$ok = $this->model_extension_module_kitchen_display->updateKitchenOrderStatus(
					$restaurant_order_id,
					$service_status,
					(int)$this->user->getId()
				);

				$json['success'] = (bool)$ok;
				$json['message'] = $ok ? 'Sipariş güncellendi.' : 'Sipariş güncellenemedi.';
			}
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json, JSON_UNESCAPED_UNICODE));
	}

	private function isRestaurantSettingEnabled($key, $default = 1) {
		$table = DB_PREFIX . 'ayarlar';

		$exists = $this->db->query("SHOW TABLES LIKE '" . $this->db->escape($table) . "'");

		if (!$exists->num_rows) {
			return (bool)$default;
		}

		$query = $this->db->query("SELECT ayar_value FROM `" . $table . "`
			WHERE ayar_key = '" . $this->db->escape($key) . "'
			LIMIT 1");

		if (!$query->num_rows || $query->row['ayar_value'] === '') {
			return (bool)$default;
		}

		return (int)$query->row['ayar_value'] === 1;
	}
}
