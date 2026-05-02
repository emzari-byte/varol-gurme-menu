<?php
class ModelExtensionModuleRestaurantActivity extends Model {
	public function getOrders($limit = 120) {
		$limit = max(20, min(300, (int)$limit));
		$order_table = DB_PREFIX . 'restaurant_order';

		if (!$this->tableExists($order_table)) {
			return array();
		}

		$select_payment_status = $this->columnExists($order_table, 'payment_status') ? 'ro.payment_status' : "'unpaid' AS payment_status";
		$select_payment_type = $this->columnExists($order_table, 'payment_type') ? 'ro.payment_type' : "'' AS payment_type";
		$select_paid_at = $this->columnExists($order_table, 'paid_at') ? 'ro.paid_at' : "NULL AS paid_at";
		$select_paid_amount = '0 AS paid_amount';
		$select_product_count = '0 AS product_count';
		$select_product_names = "'' AS product_names";
		$join_payments = '';
		$join_items = '';

		if ($this->tableExists(DB_PREFIX . 'restaurant_payment')) {
			$select_paid_amount = 'COALESCE(pay.paid_amount, 0) AS paid_amount';
			$join_payments = "LEFT JOIN (
				SELECT restaurant_order_id, SUM(amount) AS paid_amount
				FROM `" . DB_PREFIX . "restaurant_payment`
				GROUP BY restaurant_order_id
			) pay ON (pay.restaurant_order_id = ro.restaurant_order_id)";
		}

		if ($this->tableExists(DB_PREFIX . 'restaurant_order_product')) {
			$select_product_count = 'COALESCE(items.product_count, 0) AS product_count';
			$select_product_names = "COALESCE(items.product_names, '') AS product_names";
			$join_items = "LEFT JOIN (
				SELECT restaurant_order_id, SUM(quantity) AS product_count, GROUP_CONCAT(CONCAT(quantity, 'x ', name) ORDER BY restaurant_order_product_id ASC SEPARATOR ', ') AS product_names
				FROM `" . DB_PREFIX . "restaurant_order_product`
				GROUP BY restaurant_order_id
			) items ON (items.restaurant_order_id = ro.restaurant_order_id)";
		}

		$rows = $this->db->query("SELECT ro.restaurant_order_id, ro.table_id, ro.waiter_user_id, ro.service_status,
				" . $select_payment_status . ", " . $select_payment_type . ", ro.total_amount, " . $select_paid_at . ", ro.date_added, ro.date_modified,
				rt.table_no, rt.name AS table_name, rt.area,
				u.username,
				" . $select_paid_amount . ",
				" . $select_product_count . ",
				" . $select_product_names . "
			FROM `" . DB_PREFIX . "restaurant_order` ro
			LEFT JOIN `" . DB_PREFIX . "restaurant_table` rt ON (rt.table_id = ro.table_id)
			LEFT JOIN `" . DB_PREFIX . "user` u ON (u.user_id = ro.waiter_user_id)
			" . $join_payments . "
			" . $join_items . "
			ORDER BY ro.restaurant_order_id DESC
			LIMIT " . $limit)->rows;

		$orders = array();

		foreach ($rows as $row) {
			$orders[] = array(
				'order_id' => (int)$row['restaurant_order_id'],
				'table' => !empty($row['table_no']) ? 'Masa ' . $row['table_no'] : '-',
				'area' => $row['area'],
				'user' => $row['username'] ? $row['username'] : '-',
				'status' => $this->getStatusLabel($row['service_status']),
				'raw_status' => $row['service_status'],
				'payment_status' => $row['payment_status'] ? $row['payment_status'] : 'unpaid',
				'payment_type' => $row['payment_type'] ? $row['payment_type'] : '-',
				'total_amount' => (float)$row['total_amount'],
				'paid_amount' => (float)$row['paid_amount'],
				'product_count' => (int)$row['product_count'],
				'product_names' => $row['product_names'],
				'date_added' => $row['date_added'],
				'date_modified' => $row['date_modified']
			);
		}

		return $orders;
	}

	public function getActivities($limit = 80) {
		$limit = max(10, min(300, (int)$limit));
		$rows = array();

		if ($this->tableExists(DB_PREFIX . 'restaurant_order_history')) {
			$order_history = $this->db->query("SELECT
					roh.date_added,
					roh.restaurant_order_id,
					roh.old_status,
					roh.new_status,
					roh.user_id,
					roh.comment,
					ro.table_id,
					rt.table_no,
					rt.name AS table_name,
					u.username
				FROM `" . DB_PREFIX . "restaurant_order_history` roh
				LEFT JOIN `" . DB_PREFIX . "restaurant_order` ro ON (ro.restaurant_order_id = roh.restaurant_order_id)
				LEFT JOIN `" . DB_PREFIX . "restaurant_table` rt ON (rt.table_id = ro.table_id)
				LEFT JOIN `" . DB_PREFIX . "user` u ON (u.user_id = roh.user_id)
				ORDER BY roh.date_added DESC
				LIMIT " . $limit)->rows;

			foreach ($order_history as $row) {
				$rows[] = array(
					'date_added' => $row['date_added'],
					'type' => $this->getStatusLabel($row['new_status']),
					'table' => !empty($row['table_no']) ? 'Masa ' . $row['table_no'] : '-',
					'order_id' => (int)$row['restaurant_order_id'],
					'user' => $row['username'] ? $row['username'] : ((int)$row['user_id'] ? 'User #' . (int)$row['user_id'] : 'Müşteri/Sistem'),
					'detail' => trim((string)$row['comment']),
					'raw_status' => $row['new_status']
				);
			}
		}

		if ($this->tableExists(DB_PREFIX . 'restaurant_call')) {
			$calls = $this->db->query("SELECT
					rc.date_added,
					rc.date_modified,
					rc.call_type,
					rc.status,
					rc.note,
					rc.table_id,
					rt.table_no,
					rt.name AS table_name
				FROM `" . DB_PREFIX . "restaurant_call` rc
				LEFT JOIN `" . DB_PREFIX . "restaurant_table` rt ON (rt.table_id = rc.table_id)
				ORDER BY rc.date_added DESC
				LIMIT " . $limit)->rows;

			foreach ($calls as $row) {
				$rows[] = array(
					'date_added' => $row['date_added'],
					'type' => $row['call_type'] === 'bill_request' ? 'Hesap Talebi' : 'Garson Çağrısı',
					'table' => !empty($row['table_no']) ? 'Masa ' . $row['table_no'] : '-',
					'order_id' => 0,
					'user' => 'Müşteri',
					'detail' => trim((string)$row['note']) . ' / Durum: ' . $row['status'],
					'raw_status' => $row['call_type'] . '_' . $row['status']
				);
			}
		}

		usort($rows, function($a, $b) {
			return strtotime($b['date_added']) <=> strtotime($a['date_added']);
		});

		return array_slice($rows, 0, $limit);
	}

	private function getStatusLabel($status) {
		$map = array(
			'waiting_order' => 'Sipariş Oluşturuldu',
			'in_kitchen' => 'Mutfağa Gönderildi',
			'ready_for_service' => 'Mutfakta Hazır',
			'out_for_service' => 'Servise Çıktı',
			'served' => 'Servis Edildi',
			'paid' => 'Ödeme Alındı',
			'cancelled' => 'İptal',
			'bill_request' => 'Hesap Talebi',
			'bill_request_seen' => 'Hesap Talebi Görüldü',
			'waiter_call' => 'Garson Çağrısı',
			'waiter_call_seen' => 'Garson Çağrısı Görüldü',
			'waiter_break_start' => 'Garson Molaya Çıktı',
			'waiter_break_end' => 'Garson Moladan Döndü'
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

	private function columnExists($table, $column) {
		try {
			if (!$this->tableExists($table)) {
				return false;
			}

			$query = $this->db->query("SHOW COLUMNS FROM `" . $this->db->escape($table) . "` LIKE '" . $this->db->escape($column) . "'");

			return $query->num_rows > 0;
		} catch (Exception $e) {
			return false;
		}
	}
}
