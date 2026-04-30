<?php
class ModelExtensionModuleCashierPanel extends Model {
	public function install() {
		$this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "restaurant_payment` (
			`payment_id` int(11) NOT NULL AUTO_INCREMENT,
			`restaurant_order_id` int(11) NOT NULL,
			`table_id` int(11) NOT NULL,
			`amount` decimal(15,4) NOT NULL DEFAULT '0.0000',
			`payment_method` varchar(32) NOT NULL,
			`source` varchar(32) NOT NULL,
			`user_id` int(11) NOT NULL DEFAULT '0',
			`note` text NOT NULL,
			`date_added` datetime NOT NULL,
			PRIMARY KEY (`payment_id`),
			KEY `restaurant_order_id` (`restaurant_order_id`),
			KEY `table_id` (`table_id`),
			KEY `date_added` (`date_added`)
		) ENGINE=MyISAM DEFAULT CHARSET=utf8");
	}

	public function getOpenTables() {
		$this->install();

		return $this->db->query("SELECT rt.table_id, rt.table_no, rt.name, rt.area,
				COUNT(ro.restaurant_order_id) AS order_count,
				COALESCE(SUM(ro.total_amount), 0) AS total_amount,
				MAX(ro.date_modified) AS last_activity,
				GROUP_CONCAT(ro.restaurant_order_id ORDER BY ro.restaurant_order_id ASC SEPARATOR ',') AS order_ids,
				MAX(CASE WHEN rc.call_id IS NOT NULL THEN 1 ELSE 0 END) AS bill_request_pending
			FROM `" . DB_PREFIX . "restaurant_order` ro
			INNER JOIN `" . DB_PREFIX . "restaurant_table` rt ON (rt.table_id = ro.table_id)
			LEFT JOIN `" . DB_PREFIX . "restaurant_call` rc ON (rc.table_id = ro.table_id AND rc.call_type = 'bill_request' AND rc.status IN ('new','seen'))
			WHERE ro.service_status = 'served'
			GROUP BY rt.table_id
			ORDER BY bill_request_pending DESC, rt.sort_order ASC, rt.table_no ASC")->rows;
	}

	public function getSummary() {
		$this->install();

		$summary = array(
			'today_total' => 0,
			'cash_total' => 0,
			'card_total' => 0,
			'meal_card_total' => 0,
			'transfer_total' => 0,
			'payment_count' => 0,
			'open_total' => 0,
			'open_count' => 0
		);

		$payments = $this->db->query("SELECT payment_method, COUNT(*) AS count_total, COALESCE(SUM(amount), 0) AS amount_total
			FROM `" . DB_PREFIX . "restaurant_payment`
			WHERE DATE(date_added) = CURDATE()
			GROUP BY payment_method")->rows;

		foreach ($payments as $payment) {
			$amount = (float)$payment['amount_total'];
			$summary['today_total'] += $amount;
			$summary['payment_count'] += (int)$payment['count_total'];

			if ($payment['payment_method'] === 'cash') {
				$summary['cash_total'] += $amount;
			} elseif ($payment['payment_method'] === 'card') {
				$summary['card_total'] += $amount;
			} elseif ($payment['payment_method'] === 'meal_card') {
				$summary['meal_card_total'] += $amount;
			} elseif ($payment['payment_method'] === 'transfer') {
				$summary['transfer_total'] += $amount;
			}
		}

		$open = $this->db->query("SELECT COUNT(*) AS count_total, COALESCE(SUM(total_amount), 0) AS amount_total
			FROM `" . DB_PREFIX . "restaurant_order`
			WHERE service_status = 'served'")->row;

		$summary['open_count'] = (int)$open['count_total'];
		$summary['open_total'] = (float)$open['amount_total'];

		return $summary;
	}

	public function getTodayPayments($limit = 100) {
		$this->install();
		$limit = max(1, min(300, (int)$limit));

		return $this->db->query("SELECT rp.*, rt.table_no, rt.name AS table_name, u.username
			FROM `" . DB_PREFIX . "restaurant_payment` rp
			LEFT JOIN `" . DB_PREFIX . "restaurant_table` rt ON (rt.table_id = rp.table_id)
			LEFT JOIN `" . DB_PREFIX . "user` u ON (u.user_id = rp.user_id)
			WHERE DATE(rp.date_added) = CURDATE()
			ORDER BY rp.payment_id DESC
			LIMIT " . $limit)->rows;
	}

	public function payTable($table_id, $payment_method, $user_id = 0, $note = '') {
		$this->install();

		$table_id = (int)$table_id;
		$user_id = (int)$user_id;
		$payment_method = (string)$payment_method;
		$allowed = array('cash', 'card', 'meal_card', 'transfer', 'complimentary', 'mixed');

		if (!$table_id || !in_array($payment_method, $allowed, true)) {
			return array('success' => false, 'message' => 'Geçersiz ödeme bilgisi.');
		}

		$orders = $this->db->query("SELECT restaurant_order_id, table_id, service_status, total_amount
			FROM `" . DB_PREFIX . "restaurant_order`
			WHERE table_id = '" . $table_id . "'
			AND service_status = 'served'
			ORDER BY restaurant_order_id ASC")->rows;

		if (!$orders) {
			return array('success' => false, 'message' => 'Bu masa için ödemeye uygun açık hesap yok.');
		}

		foreach ($orders as $order) {
			$order_id = (int)$order['restaurant_order_id'];
			$old_status = (string)$order['service_status'];
			$amount = (float)$order['total_amount'];

			$this->db->query("INSERT INTO `" . DB_PREFIX . "restaurant_payment`
				SET restaurant_order_id = '" . $order_id . "',
					table_id = '" . $table_id . "',
					amount = '" . (float)$amount . "',
					payment_method = '" . $this->db->escape($payment_method) . "',
					source = 'cashier_panel',
					user_id = '" . $user_id . "',
					note = '" . $this->db->escape($note) . "',
					date_added = NOW()");

			$this->db->query("UPDATE `" . DB_PREFIX . "restaurant_order`
				SET service_status = 'paid',
					is_paid = '1',
					date_modified = NOW()
				WHERE restaurant_order_id = '" . $order_id . "'");

			$this->db->query("INSERT INTO `" . DB_PREFIX . "restaurant_order_history`
				SET restaurant_order_id = '" . $order_id . "',
					old_status = '" . $this->db->escape($old_status) . "',
					new_status = 'paid',
					user_id = '" . $user_id . "',
					comment = '" . $this->db->escape('Kasa panelinden ödeme alındı: ' . $payment_method) . "',
					date_added = NOW()");
		}

		$this->db->query("UPDATE `" . DB_PREFIX . "restaurant_call`
			SET status = 'closed', date_modified = NOW()
			WHERE table_id = '" . $table_id . "'
			AND call_type IN ('bill_request','waiter_call')
			AND status IN ('new','seen')");

		$this->db->query("UPDATE `" . DB_PREFIX . "restaurant_table_status`
			SET service_status = 'empty',
				active_order_count = '0',
				total_amount = '0.0000',
				active_session_token = NULL,
				date_modified = NOW()
			WHERE table_id = '" . $table_id . "'");

		return array('success' => true, 'message' => 'Ödeme alındı ve masa kapatıldı.');
	}
}
