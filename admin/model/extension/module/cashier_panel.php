<?php
class ModelExtensionModuleCashierPanel extends Model {
	public function install() {
		if ($this->tableExists(DB_PREFIX . 'restaurant_order')) {
			$this->addColumnIfMissing(DB_PREFIX . 'restaurant_order', 'payment_status', "VARCHAR(32) NOT NULL DEFAULT 'unpaid'");
			$this->addColumnIfMissing(DB_PREFIX . 'restaurant_order', 'payment_type', "VARCHAR(32) DEFAULT NULL");
			$this->addColumnIfMissing(DB_PREFIX . 'restaurant_order', 'payment_total', "DECIMAL(15,4) NOT NULL DEFAULT '0.0000'");
			$this->addColumnIfMissing(DB_PREFIX . 'restaurant_order', 'discount_type', "VARCHAR(16) NOT NULL DEFAULT 'none'");
			$this->addColumnIfMissing(DB_PREFIX . 'restaurant_order', 'discount_value', "DECIMAL(15,4) NOT NULL DEFAULT '0.0000'");
			$this->addColumnIfMissing(DB_PREFIX . 'restaurant_order', 'discount_amount', "DECIMAL(15,4) NOT NULL DEFAULT '0.0000'");
			$this->addColumnIfMissing(DB_PREFIX . 'restaurant_order', 'service_fee', "DECIMAL(15,4) NOT NULL DEFAULT '0.0000'");
			$this->addColumnIfMissing(DB_PREFIX . 'restaurant_order', 'paid_at', "DATETIME DEFAULT NULL");
			$this->addColumnIfMissing(DB_PREFIX . 'restaurant_order', 'cashier_user_id', "INT(11) NOT NULL DEFAULT '0'");
			$this->addColumnIfMissing(DB_PREFIX . 'restaurant_order', 'locked', "TINYINT(1) NOT NULL DEFAULT '0'");
		}

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

		$this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "restaurant_cashier_log` (
			`log_id` int(11) NOT NULL AUTO_INCREMENT,
			`restaurant_order_id` int(11) NOT NULL DEFAULT '0',
			`table_id` int(11) NOT NULL DEFAULT '0',
			`user_id` int(11) NOT NULL DEFAULT '0',
			`user_name` varchar(128) NOT NULL DEFAULT '',
			`action` varchar(64) NOT NULL,
			`message` text,
			`data_json` mediumtext,
			`date_added` datetime NOT NULL,
			PRIMARY KEY (`log_id`),
			KEY `restaurant_order_id` (`restaurant_order_id`),
			KEY `table_id` (`table_id`),
			KEY `action` (`action`),
			KEY `date_added` (`date_added`)
		) ENGINE=MyISAM DEFAULT CHARSET=utf8");

		$this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "restaurant_order_product_cancel` (
			`cancel_id` int(11) NOT NULL AUTO_INCREMENT,
			`restaurant_order_product_id` int(11) NOT NULL DEFAULT '0',
			`restaurant_order_id` int(11) NOT NULL DEFAULT '0',
			`table_id` int(11) NOT NULL DEFAULT '0',
			`product_id` int(11) NOT NULL DEFAULT '0',
			`name` varchar(255) NOT NULL DEFAULT '',
			`quantity` int(11) NOT NULL DEFAULT '0',
			`price` decimal(15,4) NOT NULL DEFAULT '0.0000',
			`total` decimal(15,4) NOT NULL DEFAULT '0.0000',
			`reason_code` varchar(64) NOT NULL DEFAULT '',
			`reason_text` varchar(255) NOT NULL DEFAULT '',
			`note` text NOT NULL,
			`user_id` int(11) NOT NULL DEFAULT '0',
			`user_name` varchar(128) NOT NULL DEFAULT '',
			`date_added` datetime NOT NULL,
			PRIMARY KEY (`cancel_id`),
			KEY `restaurant_order_id` (`restaurant_order_id`),
			KEY `table_id` (`table_id`),
			KEY `reason_code` (`reason_code`),
			KEY `date_added` (`date_added`)
		) ENGINE=MyISAM DEFAULT CHARSET=utf8");
	}

	public function tableExists($table) {
		$query = $this->db->query("SHOW TABLES LIKE '" . $this->db->escape($table) . "'");
		return $query->num_rows > 0;
	}

	public function columnExists($table, $column) {
		$query = $this->db->query("SHOW COLUMNS FROM `" . $this->db->escape($table) . "` LIKE '" . $this->db->escape($column) . "'");
		return $query->num_rows > 0;
	}

	private function addColumnIfMissing($table, $column, $definition) {
		if (!$this->columnExists($table, $column)) {
			$this->db->query("ALTER TABLE `" . $this->db->escape($table) . "` ADD `" . $this->db->escape($column) . "` " . $definition);
		}
	}

	public function getOpenTables() {
		$this->install();
		$this->cleanupClosedOrEmptyOrders();

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
				WHERE ro.service_status IN ('served','payment_pending')
				AND (ro.payment_status IS NULL OR ro.payment_status != 'paid')
				AND ro.total_amount > COALESCE(pay.paid_amount, 0)
				AND EXISTS (
					SELECT 1 FROM `" . DB_PREFIX . "restaurant_order_product` rop
					WHERE rop.restaurant_order_id = ro.restaurant_order_id
				)
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
		$this->cleanupClosedOrEmptyOrders();
		$table_id = (int)$table_id;

		$table = $this->db->query("SELECT * FROM `" . DB_PREFIX . "restaurant_table` WHERE table_id = '" . $table_id . "' AND status = '1' LIMIT 1")->row;

		if (!$table) {
			return array();
		}

		$orders = $this->db->query("SELECT ro.* FROM `" . DB_PREFIX . "restaurant_order` ro
			LEFT JOIN (
				SELECT restaurant_order_id, SUM(amount) AS paid_amount
				FROM `" . DB_PREFIX . "restaurant_payment`
				GROUP BY restaurant_order_id
			) pay ON (pay.restaurant_order_id = ro.restaurant_order_id)
			WHERE ro.table_id = '" . $table_id . "'
			AND ro.service_status IN ('served','payment_pending')
			AND (ro.payment_status IS NULL OR ro.payment_status != 'paid')
			AND ro.total_amount > COALESCE(pay.paid_amount, 0)
			AND EXISTS (
				SELECT 1 FROM `" . DB_PREFIX . "restaurant_order_product` rop
				WHERE rop.restaurant_order_id = ro.restaurant_order_id
			)
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

		$locked = $this->db->query("SELECT restaurant_order_id FROM `" . DB_PREFIX . "restaurant_order`
			WHERE table_id = '" . $table_id . "'
			AND service_status = 'payment_pending'
			AND (payment_status IS NULL OR payment_status != 'paid')
			LIMIT 1");

		if ($locked->num_rows) {
			return array('success' => false, 'message' => 'Hesap alınmış masaya ürün eklenemez.');
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

		if (!$query->num_rows || $query->row['service_status'] !== 'served' || !empty($query->row['locked'])) {
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

	public function removeProductFromTable($restaurant_order_product_id, $user_id = 0, $user_name = '', $reason_code = '', $reason_text = '', $note = '') {
		$this->install();
		$restaurant_order_product_id = (int)$restaurant_order_product_id;
		$user_id = (int)$user_id;
		$user_name = trim((string)$user_name);
		$reason_code = trim((string)$reason_code);
		$reason_text = trim((string)$reason_text);
		$note = trim((string)$note);

		$allowed_reasons = array(
			'waste' => 'Ürün zayi oldu',
			'wrong_item' => 'Yanlış ürün girildi',
			'customer_cancel' => 'Müşteri vazgeçti',
			'service_error' => 'Servis / hazırlık hatası',
			'other' => 'Diğer'
		);

		if (!isset($allowed_reasons[$reason_code])) {
			return array('success' => false, 'message' => 'Ürün iptal sebebi seçilmelidir.');
		}

		if ($reason_text === '') {
			$reason_text = $allowed_reasons[$reason_code];
		}

		if ($reason_code === 'other' && utf8_strlen($note) < 3) {
			return array('success' => false, 'message' => 'Diğer iptal sebebi için açıklama yazılmalıdır.');
		}

		$query = $this->db->query("SELECT rop.*, ro.table_id, ro.service_status, ro.payment_status, ro.locked,
				COALESCE(pay.paid_amount, 0) AS paid_amount
			FROM `" . DB_PREFIX . "restaurant_order_product` rop
			LEFT JOIN `" . DB_PREFIX . "restaurant_order` ro ON (ro.restaurant_order_id = rop.restaurant_order_id)
			LEFT JOIN (
				SELECT restaurant_order_id, SUM(amount) AS paid_amount
				FROM `" . DB_PREFIX . "restaurant_payment`
				GROUP BY restaurant_order_id
			) pay ON (pay.restaurant_order_id = ro.restaurant_order_id)
			WHERE rop.restaurant_order_product_id = '" . $restaurant_order_product_id . "'
			LIMIT 1");

		if (!$query->num_rows || !in_array($query->row['service_status'], array('served', 'payment_pending'), true) || $query->row['payment_status'] === 'paid') {
			return array('success' => false, 'message' => 'Ürün satırı silinemedi.');
		}

		if ((float)$query->row['paid_amount'] > 0.009) {
			return array('success' => false, 'message' => 'Tahsilat başlamış adisyonda ürün iptali yapılamaz.');
		}

		$order_id = (int)$query->row['restaurant_order_id'];
		$table_id = (int)$query->row['table_id'];
		$old_status = $query->row['service_status'];

		$this->db->query("INSERT INTO `" . DB_PREFIX . "restaurant_order_product_cancel`
			SET restaurant_order_product_id = '" . $restaurant_order_product_id . "',
				restaurant_order_id = '" . $order_id . "',
				table_id = '" . $table_id . "',
				product_id = '" . (int)$query->row['product_id'] . "',
				name = '" . $this->db->escape($query->row['name']) . "',
				quantity = '" . (int)$query->row['quantity'] . "',
				price = '" . (float)$query->row['price'] . "',
				total = '" . (float)$query->row['total'] . "',
				reason_code = '" . $this->db->escape($reason_code) . "',
				reason_text = '" . $this->db->escape($reason_text) . "',
				note = '" . $this->db->escape($note) . "',
				user_id = '" . $user_id . "',
				user_name = '" . $this->db->escape($user_name) . "',
				date_added = NOW()");

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
				old_status = '" . $this->db->escape($old_status) . "',
				new_status = '" . $this->db->escape($old_status) . "',
				user_id = '" . $user_id . "',
				comment = '" . $this->db->escape('Kasa ürün iptali: ' . $query->row['name'] . ' - ' . $reason_text) . "',
				date_added = NOW()");

		$this->addLog(array(
			'restaurant_order_id' => $order_id,
			'table_id' => $table_id,
			'user_id' => $user_id,
			'user_name' => $user_name,
			'action' => 'product_cancel',
			'message' => 'Kasa ürün iptali kaydedildi.',
			'data' => array(
				'restaurant_order_product_id' => $restaurant_order_product_id,
				'product_id' => (int)$query->row['product_id'],
				'name' => $query->row['name'],
				'quantity' => (int)$query->row['quantity'],
				'total' => (float)$query->row['total'],
				'reason_code' => $reason_code,
				'reason_text' => $reason_text,
				'note' => $note
			)
		));

		return array('success' => true, 'message' => 'Ürün satırı silindi.', 'detail' => $this->getTableDetail($table_id));
	}

	public function markPaymentPending($table_id, $user_id = 0, $user_name = '') {
		$this->install();
		$table_id = (int)$table_id;
		$user_id = (int)$user_id;

		if (!$table_id) {
			return array('success' => false, 'message' => 'Masa bulunamadı.');
		}

		$orders = $this->db->query("SELECT ro.restaurant_order_id, ro.service_status
			FROM `" . DB_PREFIX . "restaurant_order` ro
			WHERE ro.table_id = '" . $table_id . "'
			AND ro.service_status = 'served'
			AND (ro.payment_status IS NULL OR ro.payment_status != 'paid')
			AND EXISTS (
				SELECT 1 FROM `" . DB_PREFIX . "restaurant_order_product` rop
				WHERE rop.restaurant_order_id = ro.restaurant_order_id
			)
			ORDER BY restaurant_order_id ASC")->rows;

		if (!$orders) {
			return array('success' => false, 'message' => 'Hesap alınacak servis edilmiş sipariş bulunamadı.');
		}

		foreach ($orders as $order) {
			$order_id = (int)$order['restaurant_order_id'];
			$this->db->query("UPDATE `" . DB_PREFIX . "restaurant_order`
				SET service_status = 'payment_pending',
					payment_status = 'pending',
					locked = '1',
					date_modified = NOW()
				WHERE restaurant_order_id = '" . $order_id . "'");

			$this->db->query("INSERT INTO `" . DB_PREFIX . "restaurant_order_history`
				SET restaurant_order_id = '" . $order_id . "',
					old_status = '" . $this->db->escape($order['service_status']) . "',
					new_status = 'payment_pending',
					user_id = '" . $user_id . "',
					comment = 'Kasa panelinden hesap alındı.',
					date_added = NOW()");
		}

		$this->syncTableStatus($table_id);
		$this->addLog(array(
			'table_id' => $table_id,
			'user_id' => $user_id,
			'user_name' => $user_name,
			'action' => 'payment_pending',
			'message' => 'Hesap alındı.',
			'data' => array('orders' => $orders)
		));

		return array('success' => true, 'message' => 'Masa ödeme bekliyor durumuna alındı.', 'detail' => $this->getTableDetail($table_id));
	}

	public function addLog($data) {
		$this->install();
		$this->db->query("INSERT INTO `" . DB_PREFIX . "restaurant_cashier_log`
			SET restaurant_order_id = '" . (int)(isset($data['restaurant_order_id']) ? $data['restaurant_order_id'] : 0) . "',
				table_id = '" . (int)(isset($data['table_id']) ? $data['table_id'] : 0) . "',
				user_id = '" . (int)(isset($data['user_id']) ? $data['user_id'] : 0) . "',
				user_name = '" . $this->db->escape(isset($data['user_name']) ? $data['user_name'] : '') . "',
				action = '" . $this->db->escape(isset($data['action']) ? $data['action'] : '') . "',
				message = '" . $this->db->escape(isset($data['message']) ? $data['message'] : '') . "',
				data_json = '" . $this->db->escape(json_encode(isset($data['data']) ? $data['data'] : array())) . "',
				date_added = NOW()");
	}

	public function getSummary() {
		$this->install();
		$this->cleanupClosedOrEmptyOrders();

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

		$open = $this->db->query("SELECT COUNT(*) AS count_total, COALESCE(SUM(GREATEST(ro.total_amount - COALESCE(pay.paid_amount, 0), 0)), 0) AS amount_total
			FROM `" . DB_PREFIX . "restaurant_order` ro
			LEFT JOIN (
				SELECT restaurant_order_id, SUM(amount) AS paid_amount
				FROM `" . DB_PREFIX . "restaurant_payment`
				GROUP BY restaurant_order_id
			) pay ON (pay.restaurant_order_id = ro.restaurant_order_id)
			WHERE ro.service_status IN ('served','payment_pending')
			AND (ro.payment_status IS NULL OR ro.payment_status != 'paid')
			AND ro.total_amount > COALESCE(pay.paid_amount, 0)
			AND EXISTS (
				SELECT 1 FROM `" . DB_PREFIX . "restaurant_order_product` rop
				WHERE rop.restaurant_order_id = ro.restaurant_order_id
			)")->row;

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
		$this->cleanupClosedOrEmptyOrders();

		$table_id = (int)$table_id;
		$user_id = (int)$user_id;
		$payment_method = (string)$payment_method;
		$amount = (float)$amount;
		$allowed = array('cash', 'card');

		if (!$table_id || !in_array($payment_method, $allowed, true)) {
			return array('success' => false, 'message' => 'Geçersiz ödeme bilgisi.');
		}

		$orders = $this->db->query("SELECT ro.restaurant_order_id, ro.table_id, ro.service_status, ro.total_amount
			FROM `" . DB_PREFIX . "restaurant_order` ro
			LEFT JOIN (
				SELECT restaurant_order_id, SUM(amount) AS paid_amount
				FROM `" . DB_PREFIX . "restaurant_payment`
				GROUP BY restaurant_order_id
			) pay ON (pay.restaurant_order_id = ro.restaurant_order_id)
			WHERE ro.table_id = '" . $table_id . "'
			AND ro.service_status IN ('served','payment_pending')
			AND (ro.payment_status IS NULL OR ro.payment_status != 'paid')
			AND ro.total_amount > COALESCE(pay.paid_amount, 0)
			AND EXISTS (
				SELECT 1 FROM `" . DB_PREFIX . "restaurant_order_product` rop
				WHERE rop.restaurant_order_id = ro.restaurant_order_id
			)
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
				$payment_types = $this->db->query("SELECT COUNT(DISTINCT payment_method) AS type_count, COUNT(*) AS payment_count
					FROM `" . DB_PREFIX . "restaurant_payment`
					WHERE restaurant_order_id = '" . $order_id . "'")->row;
				$final_payment_type = ((int)$payment_types['type_count'] > 1 || (int)$payment_types['payment_count'] > 1) ? 'mixed' : $payment_method;

				$this->db->query("UPDATE `" . DB_PREFIX . "restaurant_order`
					SET service_status = 'paid',
						is_paid = '1',
						payment_status = 'paid',
						payment_type = '" . $this->db->escape($final_payment_type) . "',
						payment_total = total_amount,
						paid_at = NOW(),
						cashier_user_id = '" . $user_id . "',
						locked = '1',
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

			$this->addLog(array(
				'table_id' => $table_id,
				'user_id' => $user_id,
				'action' => 'payment_complete',
				'message' => 'Masa ödemesi tamamlandı.',
				'data' => array('payment_method' => $payment_method, 'amount' => $amount)
			));

			return array('success' => true, 'closed' => true, 'message' => 'Ödeme alındı ve masa kapatıldı.');
		}

		foreach ($orders as $order) {
			$order_id = (int)$order['restaurant_order_id'];
			$paid = $this->db->query("SELECT COALESCE(SUM(amount), 0) AS paid_amount
				FROM `" . DB_PREFIX . "restaurant_payment`
				WHERE restaurant_order_id = '" . $order_id . "'")->row;

			$this->db->query("UPDATE `" . DB_PREFIX . "restaurant_order`
				SET service_status = 'payment_pending',
					payment_status = 'partial',
					payment_type = 'mixed',
					payment_total = '" . (float)$paid['paid_amount'] . "',
					cashier_user_id = '" . $user_id . "',
					locked = '1',
					date_modified = NOW()
				WHERE restaurant_order_id = '" . $order_id . "'");
		}

		$this->syncTableStatus($table_id);
		$this->addLog(array(
			'table_id' => $table_id,
			'user_id' => $user_id,
			'action' => 'payment_partial',
			'message' => 'Parçalı tahsilat alındı.',
			'data' => array('payment_method' => $payment_method, 'amount' => $amount, 'remaining' => $detail['total_amount'])
		));
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

	public function getDailyReport($date) {
		$this->install();
		$date = preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) ? $date : date('Y-m-d');

		$summary = array(
			'total' => 0,
			'cash' => 0,
			'card' => 0,
			'count' => 0
		);

		$payments = $this->db->query("SELECT payment_method, COUNT(*) AS count_total, COALESCE(SUM(amount), 0) AS amount_total
			FROM `" . DB_PREFIX . "restaurant_payment`
			WHERE DATE(date_added) = '" . $this->db->escape($date) . "'
			GROUP BY payment_method")->rows;

		foreach ($payments as $payment) {
			$amount = (float)$payment['amount_total'];
			$summary['total'] += $amount;
			$summary['count'] += (int)$payment['count_total'];

			if ($payment['payment_method'] === 'cash') {
				$summary['cash'] += $amount;
			} elseif ($payment['payment_method'] === 'card') {
				$summary['card'] += $amount;
			}
		}

		$tables = $this->db->query("SELECT rt.table_no, rt.area, COALESCE(SUM(rp.amount), 0) AS amount_total
			FROM `" . DB_PREFIX . "restaurant_payment` rp
			LEFT JOIN `" . DB_PREFIX . "restaurant_table` rt ON (rt.table_id = rp.table_id)
			WHERE DATE(rp.date_added) = '" . $this->db->escape($date) . "'
			GROUP BY rp.table_id
			ORDER BY amount_total DESC")->rows;

		$products = $this->db->query("SELECT rop.name, SUM(rop.quantity) AS quantity_total, SUM(rop.total) AS total_amount
			FROM `" . DB_PREFIX . "restaurant_order_product` rop
			INNER JOIN `" . DB_PREFIX . "restaurant_order` ro ON (ro.restaurant_order_id = rop.restaurant_order_id)
			WHERE DATE(ro.paid_at) = '" . $this->db->escape($date) . "'
			AND ro.payment_status = 'paid'
			GROUP BY rop.product_id, rop.name
			ORDER BY total_amount DESC
			LIMIT 50")->rows;

		return array(
			'date' => $date,
			'summary' => $summary,
			'tables' => $tables,
			'products' => $products
		);
	}

	public function getReceiptHtml($table_id) {
		$detail = $this->getTableDetail($table_id);

		if (!$detail || empty($detail['table'])) {
			return '<!doctype html><html><body>Fiş bulunamadı.</body></html>';
		}

		$escape = function($value) {
			return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
		};

		$html = '<!doctype html><html><head><meta charset="utf-8"><title>Fiş</title>';
		$html .= '<style>body{font-family:Arial,sans-serif;width:72mm;margin:0 auto;color:#111;font-size:12px}.center{text-align:center}.line{border-top:1px dashed #111;margin:8px 0}.row{display:flex;justify-content:space-between;gap:8px}.item{margin:5px 0}.total{font-weight:bold;font-size:14px}</style>';
		$html .= '</head><body>';
		$html .= '<div class="center"><h3>Varol Veranda</h3><div>Afiyet olsun</div></div><div class="line"></div>';
		$html .= '<div>Masa: ' . $escape($detail['table']['table_no']) . '</div>';
		$html .= '<div>Alan: ' . $escape($detail['table']['area']) . '</div>';
		$html .= '<div>Tarih: ' . date('d.m.Y H:i') . '</div><div class="line"></div>';

		foreach ($detail['items'] as $item) {
			$html .= '<div class="item"><div>' . $escape($item['name']) . '</div><div class="row"><span>' . (int)$item['quantity'] . ' x ' . number_format((float)$item['price'], 2, ',', '.') . '</span><strong>' . number_format((float)$item['total'], 2, ',', '.') . ' TL</strong></div></div>';
		}

		$html .= '<div class="line"></div>';
		$html .= '<div class="row"><span>Ara Toplam</span><strong>' . number_format((float)$detail['subtotal_amount'], 2, ',', '.') . ' TL</strong></div>';
		$html .= '<div class="row"><span>Alınan</span><strong>' . number_format((float)$detail['paid_amount'], 2, ',', '.') . ' TL</strong></div>';
		$html .= '<div class="row total"><span>Kalan</span><strong>' . number_format((float)$detail['total_amount'], 2, ',', '.') . ' TL</strong></div>';
		$html .= '<div class="line"></div><div class="center">Teşekkür ederiz</div>';
		$html .= '</body></html>';

		return $html;
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

	private function cleanupClosedOrEmptyOrders() {
		$orders = $this->db->query("SELECT ro.restaurant_order_id, ro.table_id, ro.service_status, ro.total_amount,
				COALESCE(pay.paid_amount, 0) AS paid_amount,
				COALESCE(products.product_count, 0) AS product_count
			FROM `" . DB_PREFIX . "restaurant_order` ro
			LEFT JOIN (
				SELECT restaurant_order_id, SUM(amount) AS paid_amount
				FROM `" . DB_PREFIX . "restaurant_payment`
				GROUP BY restaurant_order_id
			) pay ON (pay.restaurant_order_id = ro.restaurant_order_id)
			LEFT JOIN (
				SELECT restaurant_order_id, COUNT(*) AS product_count
				FROM `" . DB_PREFIX . "restaurant_order_product`
				GROUP BY restaurant_order_id
			) products ON (products.restaurant_order_id = ro.restaurant_order_id)
			WHERE ro.service_status IN ('served','payment_pending')
			AND (ro.payment_status IS NULL OR ro.payment_status != 'paid')
			AND (COALESCE(products.product_count, 0) = 0 OR ro.total_amount <= COALESCE(pay.paid_amount, 0))")->rows;

		foreach ($orders as $order) {
			$order_id = (int)$order['restaurant_order_id'];
			$table_id = (int)$order['table_id'];
			$new_status = ((float)$order['total_amount'] > 0 && (float)$order['paid_amount'] >= (float)$order['total_amount']) ? 'paid' : 'cancelled';
			$payment_status = ($new_status == 'paid') ? 'paid' : 'unpaid';

			$this->db->query("UPDATE `" . DB_PREFIX . "restaurant_order`
				SET service_status = '" . $this->db->escape($new_status) . "',
					payment_status = '" . $this->db->escape($payment_status) . "',
					locked = '1',
					date_modified = NOW()
				WHERE restaurant_order_id = '" . $order_id . "'");

			$this->db->query("INSERT INTO `" . DB_PREFIX . "restaurant_order_history`
				SET restaurant_order_id = '" . $order_id . "',
					old_status = '" . $this->db->escape($order['service_status']) . "',
					new_status = '" . $this->db->escape($new_status) . "',
					user_id = '0',
					comment = 'Kasa paneli eski/boş adisyonu otomatik temizledi.',
					date_added = NOW()");

			$this->syncTableStatus($table_id);
		}
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
			AND ro.service_status IN ('served','payment_pending')
			AND (ro.payment_status IS NULL OR ro.payment_status != 'paid')
			AND ro.total_amount > COALESCE(pay.paid_amount, 0)
			AND EXISTS (
				SELECT 1 FROM `" . DB_PREFIX . "restaurant_order_product` rop
				WHERE rop.restaurant_order_id = ro.restaurant_order_id
			)")->row;

		$count = (int)$active['active_order_count'];
		$total = (float)$active['total_amount'];
		$pending = $this->db->query("SELECT ro.restaurant_order_id FROM `" . DB_PREFIX . "restaurant_order` ro
			LEFT JOIN (
				SELECT restaurant_order_id, SUM(amount) AS paid_amount
				FROM `" . DB_PREFIX . "restaurant_payment`
				GROUP BY restaurant_order_id
			) pay ON (pay.restaurant_order_id = ro.restaurant_order_id)
			WHERE ro.table_id = '" . $table_id . "'
			AND ro.service_status = 'payment_pending'
			AND (ro.payment_status IS NULL OR ro.payment_status != 'paid')
			AND ro.total_amount > COALESCE(pay.paid_amount, 0)
			AND EXISTS (
				SELECT 1 FROM `" . DB_PREFIX . "restaurant_order_product` rop
				WHERE rop.restaurant_order_id = ro.restaurant_order_id
			)
			LIMIT 1");
		$status = ($count > 0 && $total > 0.009) ? ($pending->num_rows ? 'payment_pending' : 'served') : 'empty';
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
