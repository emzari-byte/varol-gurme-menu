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

		return $this->db->query("SELECT rt.table_id, rt.table_no, rt.name, rt.area, rt.capacity,
				COALESCE(active.order_count, 0) AS order_count,
				COALESCE(active.total_amount, 0) AS total_amount,
				COALESCE(active.last_activity, '') AS last_activity,
				COALESCE(active.order_ids, '') AS order_ids,
				COALESCE(rts.service_status, 'empty') AS service_status,
				MAX(CASE WHEN rc.call_id IS NOT NULL THEN 1 ELSE 0 END) AS bill_request_pending
			FROM `" . DB_PREFIX . "restaurant_table` rt
			LEFT JOIN (
				SELECT table_id,
					COUNT(*) AS order_count,
					SUM(GREATEST(ro.total_amount - COALESCE(pay.paid_amount, 0), 0)) AS total_amount,
					MAX(ro.date_modified) AS last_activity,
					GROUP_CONCAT(ro.restaurant_order_id ORDER BY ro.restaurant_order_id ASC SEPARATOR ',') AS order_ids
				FROM `" . DB_PREFIX . "restaurant_order` ro
				LEFT JOIN (
					SELECT restaurant_order_id, SUM(amount) AS paid_amount
					FROM `" . DB_PREFIX . "restaurant_payment`
					GROUP BY restaurant_order_id
				) pay ON (pay.restaurant_order_id = ro.restaurant_order_id)
				WHERE ro.service_status = 'served'
				GROUP BY ro.table_id
			) active ON (active.table_id = rt.table_id)
			LEFT JOIN `" . DB_PREFIX . "restaurant_table_status` rts ON (rts.table_id = rt.table_id)
			LEFT JOIN `" . DB_PREFIX . "restaurant_call` rc ON (rc.table_id = rt.table_id AND rc.call_type = 'bill_request' AND rc.status IN ('new','seen'))
			WHERE rt.status = '1'
			GROUP BY rt.table_id
			ORDER BY rt.sort_order ASC, rt.table_no ASC")->rows;
	}

	public function getTableDetail($table_id) {
		$this->install();
		$table_id = (int)$table_id;

		$table = $this->db->query("SELECT * FROM `" . DB_PREFIX . "restaurant_table` WHERE table_id = '" . $table_id . "' AND status = '1' LIMIT 1")->row;

		if (!$table) {
			return array();
		}

		$orders = $this->db->query("SELECT * FROM `" . DB_PREFIX . "restaurant_order`
			WHERE table_id = '" . $table_id . "'
			AND service_status = 'served'
			ORDER BY restaurant_order_id ASC")->rows;

		$items = array();
		$subtotal = 0.0;
		$paid_total = 0.0;

		foreach ($orders as $order) {
			$subtotal += (float)$order['total_amount'];
			$paid = $this->db->query("SELECT COALESCE(SUM(amount), 0) AS paid_amount
				FROM `" . DB_PREFIX . "restaurant_payment`
				WHERE restaurant_order_id = '" . (int)$order['restaurant_order_id'] . "'")->row;
			$paid_total += (float)$paid['paid_amount'];

			$products = $this->db->query("SELECT * FROM `" . DB_PREFIX . "restaurant_order_product`
				WHERE restaurant_order_id = '" . (int)$order['restaurant_order_id'] . "'
				ORDER BY restaurant_order_product_id ASC")->rows;

			foreach ($products as $product) {
				$items[] = array(
					'restaurant_order_product_id' => (int)$product['restaurant_order_product_id'],
					'restaurant_order_id' => (int)$product['restaurant_order_id'],
					'product_id' => (int)$product['product_id'],
					'name' => $product['name'],
					'price' => (float)$product['price'],
					'quantity' => (int)$product['quantity'],
					'total' => (float)$product['total']
				);
			}
		}

		return array(
			'table' => array(
				'table_id' => (int)$table['table_id'],
				'table_no' => (int)$table['table_no'],
				'name' => $table['name'],
				'area' => $table['area']
			),
			'orders' => $orders,
			'items' => $items,
			'subtotal_amount' => $subtotal,
			'paid_amount' => $paid_total,
			'total_amount' => max(0, $subtotal - $paid_total),
			'is_occupied' => ($subtotal - $paid_total) > 0
		);
	}

	public function getCategories() {
		$language_id = $this->getTurkishLanguageId();

		return $this->db->query("SELECT c.category_id, cd.name
			FROM `" . DB_PREFIX . "category` c
			INNER JOIN `" . DB_PREFIX . "category_description` cd ON (cd.category_id = c.category_id AND cd.language_id = '" . $language_id . "')
			WHERE c.status = '1'
			ORDER BY c.sort_order ASC, cd.name ASC
			LIMIT 40")->rows;
	}

	public function getProducts($category_id = 0, $search = '') {
		$language_id = $this->getTurkishLanguageId();
		$category_id = (int)$category_id;
		$search = trim((string)$search);

		$sql = "SELECT DISTINCT p.product_id, p.price, p.model, p.sku, p.image, pd.name
			FROM `" . DB_PREFIX . "product` p
			INNER JOIN `" . DB_PREFIX . "product_description` pd ON (pd.product_id = p.product_id AND pd.language_id = '" . $language_id . "')";

		if ($category_id) {
			$sql .= " INNER JOIN `" . DB_PREFIX . "product_to_category` p2c ON (p2c.product_id = p.product_id AND p2c.category_id = '" . $category_id . "')";
		}

		$sql .= " WHERE p.status = '1'";

		if ($search !== '') {
			$sql .= " AND (pd.name LIKE '%" . $this->db->escape($search) . "%' OR p.model LIKE '%" . $this->db->escape($search) . "%' OR p.sku LIKE '%" . $this->db->escape($search) . "%')";
		}

		$sql .= " ORDER BY p.sort_order ASC, pd.name ASC LIMIT 80";

		return $this->db->query($sql)->rows;
	}

	public function addProductToTable($table_id, $product_id, $quantity = 1, $user_id = 0) {
		$this->install();
		$table_id = (int)$table_id;
		$product_id = (int)$product_id;
		$quantity = max(1, min(99, (int)$quantity));
		$user_id = (int)$user_id;

		$table = $this->db->query("SELECT table_id FROM `" . DB_PREFIX . "restaurant_table` WHERE table_id = '" . $table_id . "' AND status = '1' LIMIT 1");

		if (!$table->num_rows) {
			return array('success' => false, 'message' => 'Masa bulunamadı.');
		}

		$product = $this->db->query("SELECT p.product_id, p.price, pd.name
			FROM `" . DB_PREFIX . "product` p
			INNER JOIN `" . DB_PREFIX . "product_description` pd ON (pd.product_id = p.product_id AND pd.language_id = '" . (int)$this->getTurkishLanguageId() . "')
			WHERE p.product_id = '" . $product_id . "'
			AND p.status = '1'
			LIMIT 1");

		if (!$product->num_rows) {
			return array('success' => false, 'message' => 'Ürün bulunamadı.');
		}

		$order_id = $this->getOrCreateCashierOrder($table_id, $user_id);
		$price = (float)$product->row['price'];
		$total = $price * $quantity;

		$this->db->query("INSERT INTO `" . DB_PREFIX . "restaurant_order_product`
			SET restaurant_order_id = '" . $order_id . "',
				product_id = '" . $product_id . "',
				name = '" . $this->db->escape($product->row['name']) . "',
				price = '" . (float)$price . "',
				quantity = '" . (int)$quantity . "',
				total = '" . (float)$total . "'");

		$this->recalculateOrderTotal($order_id);
		$this->syncTableStatus($table_id);

		return array('success' => true, 'message' => 'Ürün adisyona eklendi.', 'detail' => $this->getTableDetail($table_id));
	}

	public function updateProductQuantity($restaurant_order_product_id, $quantity = 1, $user_id = 0) {
		$this->install();
		$restaurant_order_product_id = (int)$restaurant_order_product_id;
		$quantity = max(1, min(99, (int)$quantity));
		$user_id = (int)$user_id;

		$query = $this->db->query("SELECT rop.*, ro.table_id, ro.service_status
			FROM `" . DB_PREFIX . "restaurant_order_product` rop
			LEFT JOIN `" . DB_PREFIX . "restaurant_order` ro ON (ro.restaurant_order_id = rop.restaurant_order_id)
			WHERE rop.restaurant_order_product_id = '" . $restaurant_order_product_id . "'
			LIMIT 1");

		if (!$query->num_rows || $query->row['service_status'] !== 'served') {
			return array('success' => false, 'message' => 'Ürün satırı güncellenemedi.');
		}

		$order_id = (int)$query->row['restaurant_order_id'];
		$table_id = (int)$query->row['table_id'];
		$total = (float)$query->row['price'] * $quantity;

		$this->db->query("UPDATE `" . DB_PREFIX . "restaurant_order_product`
			SET quantity = '" . $quantity . "',
				total = '" . (float)$total . "'
			WHERE restaurant_order_product_id = '" . $restaurant_order_product_id . "'");

		$this->recalculateOrderTotal($order_id);
		$this->syncTableStatus($table_id);

		$this->db->query("INSERT INTO `" . DB_PREFIX . "restaurant_order_history`
			SET restaurant_order_id = '" . $order_id . "',
				old_status = 'served',
				new_status = 'served',
				user_id = '" . $user_id . "',
				comment = '" . $this->db->escape('Kasa ürün adedini güncelledi: ' . $query->row['name'] . ' x ' . $quantity) . "',
				date_added = NOW()");

		return array('success' => true, 'message' => 'Ürün adedi güncellendi.', 'detail' => $this->getTableDetail($table_id));
	}

	public function removeProductFromTable($restaurant_order_product_id, $user_id = 0) {
		$this->install();
		$restaurant_order_product_id = (int)$restaurant_order_product_id;
		$user_id = (int)$user_id;

		$query = $this->db->query("SELECT rop.*, ro.table_id, ro.service_status
			FROM `" . DB_PREFIX . "restaurant_order_product` rop
			LEFT JOIN `" . DB_PREFIX . "restaurant_order` ro ON (ro.restaurant_order_id = rop.restaurant_order_id)
			WHERE rop.restaurant_order_product_id = '" . $restaurant_order_product_id . "'
			LIMIT 1");

		if (!$query->num_rows || $query->row['service_status'] !== 'served') {
			return array('success' => false, 'message' => 'Ürün satırı silinemedi.');
		}

		$order_id = (int)$query->row['restaurant_order_id'];
		$table_id = (int)$query->row['table_id'];

		$this->db->query("DELETE FROM `" . DB_PREFIX . "restaurant_order_product`
			WHERE restaurant_order_product_id = '" . $restaurant_order_product_id . "'");

		$count = $this->db->query("SELECT COUNT(*) AS product_count
			FROM `" . DB_PREFIX . "restaurant_order_product`
			WHERE restaurant_order_id = '" . $order_id . "'")->row;

		if ((int)$count['product_count'] <= 0) {
			$this->db->query("UPDATE `" . DB_PREFIX . "restaurant_order`
				SET service_status = 'cancelled',
					total_amount = '0.0000',
					date_modified = NOW()
				WHERE restaurant_order_id = '" . $order_id . "'");
		} else {
			$this->recalculateOrderTotal($order_id);
		}

		$this->syncTableStatus($table_id);

		$this->db->query("INSERT INTO `" . DB_PREFIX . "restaurant_order_history`
			SET restaurant_order_id = '" . $order_id . "',
				old_status = 'served',
				new_status = 'served',
				user_id = '" . $user_id . "',
				comment = '" . $this->db->escape('Kasa ürün satırını sildi: ' . $query->row['name']) . "',
				date_added = NOW()");

		return array('success' => true, 'message' => 'Ürün satırı silindi.', 'detail' => $this->getTableDetail($table_id));
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

	public function payTablePartial($table_id, $payment_method, $user_id = 0, $note = '', $amount = 0) {
		$this->install();

		$table_id = (int)$table_id;
		$user_id = (int)$user_id;
		$payment_method = (string)$payment_method;
		$amount = (float)$amount;
		$allowed = array('cash', 'card');

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

		$remaining_total = 0.0;
		$order_remaining = array();

		foreach ($orders as $order) {
			$order_id = (int)$order['restaurant_order_id'];
			$paid = $this->db->query("SELECT COALESCE(SUM(amount), 0) AS paid_amount
				FROM `" . DB_PREFIX . "restaurant_payment`
				WHERE restaurant_order_id = '" . $order_id . "'")->row;
			$remaining = max(0, (float)$order['total_amount'] - (float)$paid['paid_amount']);
			$order_remaining[$order_id] = $remaining;
			$remaining_total += $remaining;
		}

		if ($remaining_total <= 0.009) {
			return array('success' => false, 'message' => 'Bu masa için ödenecek kalan tutar yok.');
		}

		if ($amount <= 0 || $amount > $remaining_total) {
			$amount = $remaining_total;
		}

		$payment_left = $amount;

		foreach ($orders as $order) {
			$order_id = (int)$order['restaurant_order_id'];
			$order_pay_amount = min($payment_left, $order_remaining[$order_id]);

			if ($order_pay_amount <= 0) {
				continue;
			}

			$this->db->query("INSERT INTO `" . DB_PREFIX . "restaurant_payment`
				SET restaurant_order_id = '" . $order_id . "',
					table_id = '" . $table_id . "',
					amount = '" . (float)$order_pay_amount . "',
					payment_method = '" . $this->db->escape($payment_method) . "',
					source = 'cashier_panel',
					user_id = '" . $user_id . "',
					note = '" . $this->db->escape($note) . "',
					date_added = NOW()");

			$payment_left -= $order_pay_amount;

			if ($payment_left <= 0.009) {
				break;
			}
		}

		$detail = $this->getTableDetail($table_id);

		if ((float)$detail['total_amount'] <= 0.009) {
			foreach ($orders as $order) {
				$order_id = (int)$order['restaurant_order_id'];
				$old_status = (string)$order['service_status'];

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

			return array('success' => true, 'closed' => true, 'message' => 'Ödeme alındı ve masa kapatıldı.');
		}

		$this->syncTableStatus($table_id);
		return array('success' => true, 'closed' => false, 'message' => 'Tahsilat alındı.', 'detail' => $detail);
	}

	public function payTable($table_id, $payment_method, $user_id = 0, $note = '', $amount = 0) {
		$this->install();

		$table_id = (int)$table_id;
		$user_id = (int)$user_id;
		$payment_method = (string)$payment_method;
		$amount = (float)$amount;
		$allowed = array('cash', 'card');

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

	private function getOrCreateCashierOrder($table_id, $user_id = 0) {
		$query = $this->db->query("SELECT restaurant_order_id FROM `" . DB_PREFIX . "restaurant_order`
			WHERE table_id = '" . (int)$table_id . "'
			AND service_status = 'served'
			ORDER BY restaurant_order_id DESC
			LIMIT 1");

		if ($query->num_rows) {
			return (int)$query->row['restaurant_order_id'];
		}

		$this->db->query("INSERT INTO `" . DB_PREFIX . "restaurant_order`
			SET table_id = '" . (int)$table_id . "',
				waiter_user_id = '" . (int)$user_id . "',
				service_status = 'served',
				customer_note = 'Kasa manuel adisyon',
				total_amount = '0.0000',
				is_paid = '0',
				date_added = NOW(),
				date_modified = NOW()");

		$order_id = (int)$this->db->getLastId();

		$this->db->query("INSERT INTO `" . DB_PREFIX . "restaurant_order_history`
			SET restaurant_order_id = '" . $order_id . "',
				old_status = NULL,
				new_status = 'served',
				user_id = '" . (int)$user_id . "',
				comment = 'Kasa panelinden manuel adisyon açıldı.',
				date_added = NOW()");

		return $order_id;
	}

	private function recalculateOrderTotal($order_id) {
		$total = $this->db->query("SELECT COALESCE(SUM(total), 0) AS total_amount
			FROM `" . DB_PREFIX . "restaurant_order_product`
			WHERE restaurant_order_id = '" . (int)$order_id . "'")->row;

		$this->db->query("UPDATE `" . DB_PREFIX . "restaurant_order`
			SET total_amount = '" . (float)$total['total_amount'] . "',
				date_modified = NOW()
			WHERE restaurant_order_id = '" . (int)$order_id . "'");
	}

	private function syncTableStatus($table_id) {
		$table_id = (int)$table_id;
		$active = $this->db->query("SELECT COUNT(*) AS active_order_count,
				COALESCE(SUM(GREATEST(ro.total_amount - COALESCE(pay.paid_amount, 0), 0)), 0) AS total_amount
			FROM `" . DB_PREFIX . "restaurant_order` ro
			LEFT JOIN (
				SELECT restaurant_order_id, SUM(amount) AS paid_amount
				FROM `" . DB_PREFIX . "restaurant_payment`
				GROUP BY restaurant_order_id
			) pay ON (pay.restaurant_order_id = ro.restaurant_order_id)
			WHERE ro.table_id = '" . $table_id . "'
			AND ro.service_status = 'served'")->row;

		$count = (int)$active['active_order_count'];
		$total = (float)$active['total_amount'];
		$status = ($count > 0 && $total > 0.009) ? 'served' : 'empty';
		$check = $this->db->query("SELECT table_id FROM `" . DB_PREFIX . "restaurant_table_status` WHERE table_id = '" . $table_id . "' LIMIT 1");

		if ($check->num_rows) {
			$this->db->query("UPDATE `" . DB_PREFIX . "restaurant_table_status`
				SET service_status = '" . $this->db->escape($status) . "',
					active_order_count = '" . $count . "',
					total_amount = '" . $total . "',
					date_modified = NOW()
				WHERE table_id = '" . $table_id . "'");
		} else {
			$this->db->query("INSERT INTO `" . DB_PREFIX . "restaurant_table_status`
				SET table_id = '" . $table_id . "',
					service_status = '" . $this->db->escape($status) . "',
					active_order_count = '" . $count . "',
					total_amount = '" . $total . "',
					date_modified = NOW()");
		}
	}

	private function getTurkishLanguageId() {
		$query = $this->db->query("SELECT language_id FROM `" . DB_PREFIX . "language`
			WHERE code = 'tr-tr'
			LIMIT 1");

		if ($query->num_rows) {
			return (int)$query->row['language_id'];
		}

		return (int)$this->config->get('config_language_id');
	}
}
