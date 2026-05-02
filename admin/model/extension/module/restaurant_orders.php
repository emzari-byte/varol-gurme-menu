<?php
class ModelExtensionModuleRestaurantOrders extends Model {
	public function getStats($filter = array()) {
		if (!$this->tableExists(DB_PREFIX . 'restaurant_order')) {
			return array(
				'order_count' => 0,
				'total_amount' => 0,
				'paid_amount' => 0,
				'open_count' => 0,
				'paid_count' => 0
			);
		}

		$where = $this->buildWhere($filter);

		$query = $this->db->query("SELECT
				COUNT(*) AS order_count,
				SUM(ro.total_amount) AS total_amount,
				SUM(CASE WHEN ro.payment_status = 'paid' THEN ro.payment_total ELSE 0 END) AS paid_amount,
				SUM(CASE WHEN ro.payment_status = 'paid' OR ro.service_status IN ('paid','completed') THEN 0 ELSE 1 END) AS open_count,
				SUM(CASE WHEN ro.payment_status = 'paid' OR ro.service_status IN ('paid','completed') THEN 1 ELSE 0 END) AS paid_count
			FROM `" . DB_PREFIX . "restaurant_order` ro
			LEFT JOIN `" . DB_PREFIX . "restaurant_table` rt ON (rt.table_id = ro.table_id)
			LEFT JOIN `" . DB_PREFIX . "user` u ON (u.user_id = ro.waiter_user_id)
			" . $where);

		return array(
			'order_count' => (int)$query->row['order_count'],
			'total_amount' => (float)$query->row['total_amount'],
			'paid_amount' => (float)$query->row['paid_amount'],
			'open_count' => (int)$query->row['open_count'],
			'paid_count' => (int)$query->row['paid_count']
		);
	}

	public function getOrders($filter = array()) {
		if (!$this->tableExists(DB_PREFIX . 'restaurant_order')) {
			return array();
		}

		$where = $this->buildWhere($filter);
		$limit = isset($filter['limit']) ? max(20, min(300, (int)$filter['limit'])) : 100;

		$payments_join = '';
		$payments_select = "'' AS payment_methods";

		if ($this->tableExists(DB_PREFIX . 'restaurant_payment')) {
			$payments_select = "COALESCE(pay.payment_methods, '') AS payment_methods";
			$payments_join = "LEFT JOIN (
				SELECT restaurant_order_id, GROUP_CONCAT(DISTINCT payment_method ORDER BY payment_method SEPARATOR ', ') AS payment_methods
				FROM `" . DB_PREFIX . "restaurant_payment`
				GROUP BY restaurant_order_id
			) pay ON (pay.restaurant_order_id = ro.restaurant_order_id)";
		}

		$rows = $this->db->query("SELECT
				ro.restaurant_order_id,
				ro.service_status,
				ro.payment_status,
				ro.payment_type,
				ro.total_amount,
				ro.payment_total,
				ro.discount_amount,
				ro.service_fee,
				ro.date_added,
				ro.date_modified,
				ro.paid_at,
				rt.table_no,
				rt.name AS table_name,
				rt.area,
				u.username,
				COALESCE(items.product_count, 0) AS product_count,
				COALESCE(items.product_names, '') AS product_names,
				" . $payments_select . "
			FROM `" . DB_PREFIX . "restaurant_order` ro
			LEFT JOIN `" . DB_PREFIX . "restaurant_table` rt ON (rt.table_id = ro.table_id)
			LEFT JOIN `" . DB_PREFIX . "user` u ON (u.user_id = ro.waiter_user_id)
			LEFT JOIN (
				SELECT restaurant_order_id, SUM(quantity) AS product_count, GROUP_CONCAT(CONCAT(quantity, 'x ', name) ORDER BY restaurant_order_product_id ASC SEPARATOR ', ') AS product_names
				FROM `" . DB_PREFIX . "restaurant_order_product`
				GROUP BY restaurant_order_id
			) items ON (items.restaurant_order_id = ro.restaurant_order_id)
			" . $payments_join . "
			" . $where . "
			ORDER BY ro.restaurant_order_id DESC
			LIMIT " . (int)$limit)->rows;

		$orders = array();

		foreach ($rows as $row) {
			$orders[] = array(
				'order_id' => (int)$row['restaurant_order_id'],
				'table' => !empty($row['table_no']) ? 'Masa ' . $row['table_no'] : '-',
				'table_name' => $row['table_name'],
				'area' => $row['area'],
				'waiter' => $row['username'] ? $row['username'] : '-',
				'status' => $this->getStatusLabel($row['service_status']),
				'raw_status' => $row['service_status'],
				'payment_status' => $this->getPaymentStatusLabel($row['payment_status']),
				'raw_payment_status' => $row['payment_status'],
				'payment_type' => $row['payment_methods'] ? $row['payment_methods'] : ($row['payment_type'] ? $row['payment_type'] : '-'),
				'total_amount' => (float)$row['total_amount'],
				'payment_total' => (float)$row['payment_total'],
				'discount_amount' => (float)$row['discount_amount'],
				'service_fee' => (float)$row['service_fee'],
				'product_count' => (int)$row['product_count'],
				'product_names' => $row['product_names'],
				'date_added' => $row['date_added'],
				'date_modified' => $row['date_modified'],
				'paid_at' => $row['paid_at']
			);
		}

		return $orders;
	}

	public function getActivity($filter = array(), $limit = 120) {
		if (!$this->tableExists(DB_PREFIX . 'restaurant_order_history')) {
			return array();
		}

		$conditions = array();

		if (!empty($filter['date_from'])) {
			$conditions[] = "DATE(roh.date_added) >= '" . $this->db->escape($filter['date_from']) . "'";
		}

		if (!empty($filter['date_to'])) {
			$conditions[] = "DATE(roh.date_added) <= '" . $this->db->escape($filter['date_to']) . "'";
		}

		if (!empty($filter['table'])) {
			$conditions[] = "(rt.table_no LIKE '%" . $this->db->escape($filter['table']) . "%' OR rt.name LIKE '%" . $this->db->escape($filter['table']) . "%')";
		}

		$where = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';
		$limit = max(20, min(250, (int)$limit));

		$query = $this->db->query("SELECT
				roh.date_added,
				roh.restaurant_order_id,
				roh.old_status,
				roh.new_status,
				roh.comment,
				roh.user_id,
				rt.table_no,
				rt.name AS table_name,
				u.username
			FROM `" . DB_PREFIX . "restaurant_order_history` roh
			LEFT JOIN `" . DB_PREFIX . "restaurant_order` ro ON (ro.restaurant_order_id = roh.restaurant_order_id)
			LEFT JOIN `" . DB_PREFIX . "restaurant_table` rt ON (rt.table_id = ro.table_id)
			LEFT JOIN `" . DB_PREFIX . "user` u ON (u.user_id = roh.user_id)
			" . $where . "
			ORDER BY roh.date_added DESC
			LIMIT " . (int)$limit);

		$activity = array();

		foreach ($query->rows as $row) {
			$activity[] = array(
				'date_added' => $row['date_added'],
				'order_id' => (int)$row['restaurant_order_id'],
				'table' => !empty($row['table_no']) ? 'Masa ' . $row['table_no'] : '-',
				'user' => $row['username'] ? $row['username'] : ((int)$row['user_id'] ? 'User #' . (int)$row['user_id'] : 'Müşteri/Sistem'),
				'from' => $this->getStatusLabel($row['old_status']),
				'to' => $this->getStatusLabel($row['new_status']),
				'detail' => trim((string)$row['comment'])
			);
		}

		return $activity;
	}

	private function buildWhere($filter) {
		$conditions = array();

		if (!empty($filter['date_from'])) {
			$conditions[] = "DATE(ro.date_added) >= '" . $this->db->escape($filter['date_from']) . "'";
		}

		if (!empty($filter['date_to'])) {
			$conditions[] = "DATE(ro.date_added) <= '" . $this->db->escape($filter['date_to']) . "'";
		}

		if (!empty($filter['status'])) {
			$conditions[] = "ro.service_status = '" . $this->db->escape($filter['status']) . "'";
		}

		if (!empty($filter['payment_status'])) {
			$conditions[] = "ro.payment_status = '" . $this->db->escape($filter['payment_status']) . "'";
		}

		if (!empty($filter['table'])) {
			$conditions[] = "(rt.table_no LIKE '%" . $this->db->escape($filter['table']) . "%' OR rt.name LIKE '%" . $this->db->escape($filter['table']) . "%')";
		}

		if (!empty($filter['waiter'])) {
			$conditions[] = "u.username LIKE '%" . $this->db->escape($filter['waiter']) . "%'";
		}

		return $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';
	}

	private function getStatusLabel($status) {
		$map = array(
			'waiting_order' => 'Garson Onayı Bekliyor',
			'in_kitchen' => 'Mutfakta',
			'ready_for_service' => 'Servise Hazır',
			'out_for_service' => 'Servise Çıktı',
			'served' => 'Servis Edildi',
			'payment_pending' => 'Hesap Bekliyor',
			'paid' => 'Ödendi',
			'completed' => 'Tamamlandı',
			'cancelled' => 'İptal'
		);

		return isset($map[$status]) ? $map[$status] : (string)$status;
	}

	private function getPaymentStatusLabel($status) {
		$map = array(
			'paid' => 'Ödendi',
			'unpaid' => 'Ödenmedi',
			'partial' => 'Kısmi'
		);

		return isset($map[$status]) ? $map[$status] : (string)$status;
	}

	private function tableExists($table) {
		try {
			$query = $this->db->query("SHOW TABLES LIKE '" . $this->db->escape($table) . "'");

			return $query->num_rows > 0;
		} catch (Exception $e) {
			return false;
		}
	}
}
