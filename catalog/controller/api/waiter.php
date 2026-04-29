<?php
class ControllerApiWaiter extends Controller {

	private $api_key = 'VAROL_GARSON_2026';

	private function setJsonHeaders() {
		$this->response->addHeader('Content-Type: application/json; charset=utf-8');
		$this->response->addHeader('Access-Control-Allow-Origin: *');
		$this->response->addHeader('Access-Control-Allow-Methods: GET, POST, OPTIONS');
		$this->response->addHeader('Access-Control-Allow-Headers: Content-Type, X-Api-Key, Authorization');
	}

	private function jsonResponse($data) {
		$this->setJsonHeaders();
		$this->response->setOutput(json_encode($data, JSON_UNESCAPED_UNICODE));
	}

	private function getJsonInput() {
		$raw = file_get_contents('php://input');
		$json = json_decode($raw, true);

		return is_array($json) ? $json : array();
	}

	private function getRequestValue($key, $default = null) {
		$json = $this->getJsonInput();

		if (isset($json[$key])) {
			return $json[$key];
		}

		if (isset($this->request->post[$key])) {
			return $this->request->post[$key];
		}

		if (isset($this->request->get[$key])) {
			return $this->request->get[$key];
		}

		return $default;
	}

	private function getHeaderApiKey() {
		if (isset($this->request->server['HTTP_X_API_KEY'])) {
			return trim($this->request->server['HTTP_X_API_KEY']);
		}

		if (isset($this->request->server['REDIRECT_HTTP_X_API_KEY'])) {
			return trim($this->request->server['REDIRECT_HTTP_X_API_KEY']);
		}

		if (isset($this->request->get['api_key'])) {
			return trim($this->request->get['api_key']);
		}

		if (isset($this->request->post['api_key'])) {
			return trim($this->request->post['api_key']);
		}

		$json = $this->getJsonInput();

		if (isset($json['api_key'])) {
			return trim($json['api_key']);
		}

		return '';
	}

	private function authenticate() {
		if (($this->request->server['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
			$this->setJsonHeaders();
			$this->response->setOutput('');
			return false;
		}

		$api_key = $this->getHeaderApiKey();

		if ($api_key !== $this->api_key) {
			$this->jsonResponse(array(
				'success' => false,
				'message' => 'Geçersiz API anahtarı'
			));
			return false;
		}

		return true;
	}

	private function getOrderProducts($restaurant_order_id) {
		$restaurant_order_id = (int)$restaurant_order_id;

		$query = $this->db->query("SELECT 
				product_id,
				name,
				price,
				quantity,
				total
			FROM `" . DB_PREFIX . "restaurant_order_product`
			WHERE restaurant_order_id = '" . $restaurant_order_id . "'
			ORDER BY restaurant_order_product_id ASC");

		return $query->rows;
	}

	private function syncTableStatus($table_id) {
		$table_id = (int)$table_id;

		if (!$table_id) {
			return false;
		}

		$active_query = $this->db->query("SELECT 
				COUNT(*) AS active_order_count,
				COALESCE(SUM(total_amount), 0) AS total_amount
			FROM `" . DB_PREFIX . "restaurant_order`
			WHERE table_id = '" . $table_id . "'
			AND service_status IN ('waiting_order','in_kitchen','served')");

		$active_order_count = (int)$active_query->row['active_order_count'];
		$total_amount = (float)$active_query->row['total_amount'];

		$latest_query = $this->db->query("SELECT service_status
			FROM `" . DB_PREFIX . "restaurant_order`
			WHERE table_id = '" . $table_id . "'
			AND service_status IN ('waiting_order','in_kitchen','served')
			ORDER BY restaurant_order_id DESC
			LIMIT 1");

		if ($latest_query->num_rows) {
			$table_status = $latest_query->row['service_status'];
		} else {
			$table_status = 'empty';
			$active_order_count = 0;
			$total_amount = 0.0000;
		}

		$check = $this->db->query("SELECT table_id FROM `" . DB_PREFIX . "restaurant_table_status` WHERE table_id = '" . $table_id . "'");

		if ($check->num_rows) {
			$this->db->query("UPDATE `" . DB_PREFIX . "restaurant_table_status`
				SET service_status = '" . $this->db->escape($table_status) . "',
					active_order_count = '" . (int)$active_order_count . "',
					total_amount = '" . (float)$total_amount . "',
					date_modified = NOW()
				WHERE table_id = '" . $table_id . "'");
		} else {
			$this->db->query("INSERT INTO `" . DB_PREFIX . "restaurant_table_status`
				SET table_id = '" . $table_id . "',
					service_status = '" . $this->db->escape($table_status) . "',
					active_order_count = '" . (int)$active_order_count . "',
					total_amount = '" . (float)$total_amount . "',
					date_modified = NOW()");
		}

		return true;
	}

	public function tables() {
		if (!$this->authenticate()) {
			return;
		}

		$tables_query = $this->db->query("SELECT 
				rt.table_id,
				rt.table_no,
				rt.name,
				rt.capacity,
				rt.area,
				rt.sort_order,
				rt.status,
				IFNULL(rts.service_status, 'empty') AS service_status,
				IFNULL(rts.active_order_count, 0) AS active_order_count,
				IFNULL(rts.total_amount, 0) AS total_amount,
				IFNULL(rts.waiter_name, '') AS waiter_name,
				IFNULL(rts.note, '') AS note,
				IFNULL(rts.date_modified, '') AS date_modified
			FROM `" . DB_PREFIX . "restaurant_table` rt
			LEFT JOIN `" . DB_PREFIX . "restaurant_table_status` rts ON (rt.table_id = rts.table_id)
			WHERE rt.status = 1
			ORDER BY rt.sort_order ASC, rt.table_no ASC");

		$tables = $tables_query->rows;

		$summary = array(
			'total_tables'  => 0,
			'active_tables' => 0,
			'new_calls'     => 0,
			'paid_count'    => 0
		);

		foreach ($tables as $table) {
			$summary['total_tables']++;

			if (in_array($table['service_status'], array('waiting_order', 'in_kitchen', 'served'))) {
				$summary['active_tables']++;
			}

			if ($table['service_status'] === 'waiting_order') {
				$summary['new_calls']++;
			}
		}

		$paid_query = $this->db->query("SELECT COUNT(*) AS total
			FROM `" . DB_PREFIX . "restaurant_order`
			WHERE DATE(date_modified) = CURDATE()
			AND service_status = 'paid'");

		$summary['paid_count'] = (int)$paid_query->row['total'];

		$this->jsonResponse(array(
			'success' => true,
			'tables'  => $tables,
			'summary' => $summary
		));
	}

	public function table_orders() {
		if (!$this->authenticate()) {
			return;
		}

		$table_id = (int)$this->getRequestValue('table_id', 0);

		if (!$table_id) {
			$this->jsonResponse(array(
				'success' => false,
				'message' => 'table_id gerekli',
				'orders'  => array()
			));
			return;
		}

		$query = $this->db->query("SELECT 
				ro.restaurant_order_id,
				ro.table_id,
				ro.waiter_user_id,
				ro.service_status,
				ro.customer_note,
				ro.total_amount,
				ro.is_paid,
				ro.date_added,
				ro.date_modified
			FROM `" . DB_PREFIX . "restaurant_order` ro
			WHERE ro.table_id = '" . $table_id . "'
			AND ro.service_status IN ('waiting_order', 'in_kitchen', 'served')
			ORDER BY ro.restaurant_order_id DESC");

		$orders = $query->rows;

		foreach ($orders as $key => $order) {
			$orders[$key]['products'] = $this->getOrderProducts((int)$order['restaurant_order_id']);
		}

		$this->jsonResponse(array(
			'success' => true,
			'orders'  => $orders
		));
	}

	public function update_order_status() {
		if (!$this->authenticate()) {
			return;
		}

		$restaurant_order_id = (int)$this->getRequestValue('restaurant_order_id', 0);
		$service_status = trim((string)$this->getRequestValue('service_status', ''));

		$allowed = array('waiting_order', 'in_kitchen', 'served', 'paid', 'cancelled');

		if (!$restaurant_order_id || !in_array($service_status, $allowed)) {
			$this->jsonResponse(array(
				'success' => false,
				'message' => 'Geçersiz veri'
			));
			return;
		}

		$order_query = $this->db->query("SELECT * 
			FROM `" . DB_PREFIX . "restaurant_order`
			WHERE restaurant_order_id = '" . $restaurant_order_id . "'
			LIMIT 1");

		if (!$order_query->num_rows) {
			$this->jsonResponse(array(
				'success' => false,
				'message' => 'Sipariş bulunamadı'
			));
			return;
		}

		$order = $order_query->row;
		$table_id = (int)$order['table_id'];
		$old_status = $order['service_status'];
		$is_paid = ($service_status === 'paid') ? 1 : 0;

		$this->db->query("UPDATE `" . DB_PREFIX . "restaurant_order`
			SET service_status = '" . $this->db->escape($service_status) . "',
				is_paid = '" . (int)$is_paid . "',
				date_modified = NOW()
			WHERE restaurant_order_id = '" . $restaurant_order_id . "'");

		if ($service_status === 'in_kitchen') {
			$this->db->query("UPDATE `" . DB_PREFIX . "restaurant_order`
				SET integration_status = 'pending_export',
					integration_message = 'AKINSOFT bekliyor',
					integration_date = NOW()
				WHERE restaurant_order_id = '" . $restaurant_order_id . "'");
		}

		$this->db->query("INSERT INTO `" . DB_PREFIX . "restaurant_order_history`
			SET restaurant_order_id = '" . $restaurant_order_id . "',
				old_status = " . ($old_status !== null ? "'" . $this->db->escape($old_status) . "'" : "NULL") . ",
				new_status = '" . $this->db->escape($service_status) . "',
				user_id = '0',
				comment = 'Mobil uygulama üzerinden güncellendi',
				date_added = NOW()");

		$this->syncTableStatus($table_id);

		$this->jsonResponse(array(
			'success' => true,
			'message' => 'Sipariş durumu güncellendi'
		));
	}

	public function save_table_note() {
		if (!$this->authenticate()) {
			return;
		}

		$table_id = (int)$this->getRequestValue('table_id', 0);
		$note = trim((string)$this->getRequestValue('note', ''));

		if (!$table_id) {
			$this->jsonResponse(array(
				'success' => false,
				'message' => 'table_id gerekli'
			));
			return;
		}

		$check = $this->db->query("SELECT table_id FROM `" . DB_PREFIX . "restaurant_table_status` WHERE table_id = '" . $table_id . "'");

		if ($check->num_rows) {
			$this->db->query("UPDATE `" . DB_PREFIX . "restaurant_table_status`
				SET note = '" . $this->db->escape($note) . "',
					date_modified = NOW()
				WHERE table_id = '" . $table_id . "'");
		} else {
			$this->db->query("INSERT INTO `" . DB_PREFIX . "restaurant_table_status`
				SET table_id = '" . $table_id . "',
					service_status = 'empty',
					active_order_count = 0,
					total_amount = 0.0000,
					note = '" . $this->db->escape($note) . "',
					date_modified = NOW()");
		}

		$this->jsonResponse(array(
			'success' => true,
			'message' => 'Masa notu kaydedildi'
		));
	}
}