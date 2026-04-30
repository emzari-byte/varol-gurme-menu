<?php
class ControllerCommonDashboard extends Controller {
	public function index() {
		$waiter_group_id = 3;

		$user_group_query = $this->db->query("SELECT user_group_id FROM `" . DB_PREFIX . "user` WHERE user_id = '" . (int)$this->user->getId() . "' LIMIT 1");

		if (
			$this->user->isLogged() &&
			$user_group_query->num_rows &&
			(int)$user_group_query->row['user_group_id'] === (int)$waiter_group_id &&
			$this->user->hasPermission('access', 'extension/module/waiter_panel') &&
			$this->getRestaurantAyar('restaurant_waiter_panel', 1)
		) {
			$this->response->redirect($this->url->link('extension/module/waiter_panel', 'user_token=' . $this->session->data['user_token'], true));
			return;
		}

		if ($this->user->isLogged() && $this->isCashierOnlyUser()) {
			$this->response->redirect($this->url->link('extension/module/cashier_panel', 'user_token=' . $this->session->data['user_token'], true));
			return;
		}

		$this->load->language('common/dashboard');

		$this->document->setTitle('Restoran Kontrol Paneli');

		$data['user_token'] = $this->session->data['user_token'];
		$data['heading_title'] = 'Restoran Kontrol Paneli';
		$data['error_install'] = is_dir(DIR_CATALOG . '../install') ? $this->language->get('error_install') : '';
		$data['security'] = '';

		$data['breadcrumbs'] = array(
			array(
				'text' => 'Ana Sayfa',
				'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
			),
			array(
				'text' => 'Restoran Kontrol Paneli',
				'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
			)
		);

		$data['quick_links'] = array(
			array('title' => 'Garson Paneli', 'icon' => 'fa-bell', 'href' => $this->url->link('extension/module/waiter_panel', 'user_token=' . $this->session->data['user_token'], true), 'enabled' => $this->getRestaurantAyar('restaurant_waiter_panel', 1)),
			array('title' => 'Mutfak Ekranı', 'icon' => 'fa-fire', 'href' => $this->url->link('extension/module/kitchen_display', 'user_token=' . $this->session->data['user_token'], true), 'enabled' => $this->getRestaurantAyar('restaurant_kitchen_panel', 1)),
			array('title' => 'Masa Yönetimi', 'icon' => 'fa-qrcode', 'href' => $this->url->link('extension/module/restaurant_tables', 'user_token=' . $this->session->data['user_token'], true), 'enabled' => true),
			array('title' => 'Menü Ürünleri', 'icon' => 'fa-cutlery', 'href' => $this->url->link('catalog/product', 'user_token=' . $this->session->data['user_token'], true), 'enabled' => true),
			array('title' => 'Restoran Ayarları', 'icon' => 'fa-sliders', 'href' => $this->url->link('extension/module/restaurant_settings', 'user_token=' . $this->session->data['user_token'], true), 'enabled' => true)
		);

		$data['settings'] = array(
			'qr_order_menu' => $this->getRestaurantAyar('restaurant_qr_order_menu', 1),
			'waiter_panel'  => $this->getRestaurantAyar('restaurant_waiter_panel', 1),
			'kitchen_panel' => $this->getRestaurantAyar('restaurant_kitchen_panel', 1),
			'akinsoft'      => $this->getRestaurantAyar('restaurant_akinsoft_enabled', 0)
		);

		$data['stats'] = $this->getRestaurantStats();
		$data['latest_orders'] = $this->getLatestOrders();

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('common/dashboard', $data));
	}

	private function getRestaurantStats() {
		$stats = array(
			'total_tables' => 0,
			'active_tables' => 0,
			'waiting_orders' => 0,
			'in_kitchen' => 0,
			'ready_for_service' => 0,
			'served' => 0,
			'bill_requests' => 0,
			'today_revenue_raw' => 0.0,
			'today_revenue' => $this->currency->format(0, $this->config->get('config_currency'))
		);

		if ($this->tableExists('restaurant_table')) {
			$query = $this->db->query("SELECT COUNT(*) AS total FROM `" . DB_PREFIX . "restaurant_table` WHERE status = '1'");
			$stats['total_tables'] = (int)$query->row['total'];
		}

		if ($this->tableExists('restaurant_table_status')) {
			$query = $this->db->query("SELECT COUNT(*) AS total FROM `" . DB_PREFIX . "restaurant_table_status` WHERE service_status IN ('waiting_order','in_kitchen','ready_for_service','out_for_service','served')");
			$stats['active_tables'] = (int)$query->row['total'];
		}

		if ($this->tableExists('restaurant_order')) {
			$query = $this->db->query("SELECT
				SUM(service_status = 'waiting_order') AS waiting_orders,
				SUM(service_status = 'in_kitchen') AS in_kitchen,
				SUM(service_status IN ('ready_for_service','out_for_service')) AS ready_for_service,
				SUM(service_status = 'served') AS served,
				COALESCE(SUM(CASE WHEN service_status = 'paid' AND DATE(date_modified) = CURDATE() THEN total_amount ELSE 0 END), 0) AS today_revenue
				FROM `" . DB_PREFIX . "restaurant_order`");

			$stats['waiting_orders'] = (int)$query->row['waiting_orders'];
			$stats['in_kitchen'] = (int)$query->row['in_kitchen'];
			$stats['ready_for_service'] = (int)$query->row['ready_for_service'];
			$stats['served'] = (int)$query->row['served'];
			$stats['today_revenue_raw'] = (float)$query->row['today_revenue'];
			$stats['today_revenue'] = $this->currency->format($stats['today_revenue_raw'], $this->config->get('config_currency'));
		}

		if ($this->tableExists('restaurant_call')) {
			$query = $this->db->query("SELECT COUNT(*) AS total FROM `" . DB_PREFIX . "restaurant_call` WHERE call_type = 'bill_request' AND status IN ('new','seen')");
			$stats['bill_requests'] = (int)$query->row['total'];
		}

		return $stats;
	}

	private function getLatestOrders() {
		if (!$this->tableExists('restaurant_order')) {
			return array();
		}

		$query = $this->db->query("SELECT ro.restaurant_order_id, ro.table_id, ro.service_status, ro.total_amount, ro.date_added, rt.table_no, rt.name AS table_name
			FROM `" . DB_PREFIX . "restaurant_order` ro
			LEFT JOIN `" . DB_PREFIX . "restaurant_table` rt ON (ro.table_id = rt.table_id)
			ORDER BY ro.restaurant_order_id DESC
			LIMIT 8");

		$orders = array();

		foreach ($query->rows as $row) {
			$orders[] = array(
				'id' => (int)$row['restaurant_order_id'],
				'table' => $row['table_no'] ? 'Masa ' . $row['table_no'] : 'Masa ' . (int)$row['table_id'],
				'table_name' => $row['table_name'],
				'status' => $row['service_status'],
				'status_label' => $this->getStatusLabel($row['service_status']),
				'total' => $this->currency->format((float)$row['total_amount'], $this->config->get('config_currency')),
				'time' => date('H:i', strtotime($row['date_added']))
			);
		}

		return $orders;
	}

	private function getStatusLabel($status) {
		$map = array(
			'waiting_order' => 'Onay Bekliyor',
			'in_kitchen' => 'Mutfakta',
			'ready_for_service' => 'Servise Hazır',
			'out_for_service' => 'Serviste',
			'served' => 'Servis Edildi',
			'paid' => 'Ödeme Alındı',
			'cancelled' => 'İptal'
		);

		return isset($map[$status]) ? $map[$status] : $status;
	}

	private function getRestaurantAyar($key, $default = 1) {
		if (!$this->tableExists('ayarlar')) {
			return (bool)$default;
		}

		$query = $this->db->query("SELECT ayar_value FROM `" . DB_PREFIX . "ayarlar`
			WHERE ayar_key = '" . $this->db->escape($key) . "'
			LIMIT 1");

		if (!$query->num_rows || $query->row['ayar_value'] === '') {
			return (bool)$default;
		}

		return (int)$query->row['ayar_value'] === 1;
	}

	private function tableExists($table) {
		$query = $this->db->query("SHOW TABLES LIKE '" . $this->db->escape(DB_PREFIX . $table) . "'");

		return $query->num_rows > 0;
	}

	private function isCashierOnlyUser() {
		if (
			!$this->getRestaurantAyar('restaurant_cashier_panel', 1) ||
			$this->getRestaurantAyar('restaurant_akinsoft_enabled', 0) ||
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
