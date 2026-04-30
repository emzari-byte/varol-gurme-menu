<?php
class ModelExtensionModuleAkinsoftBridge extends Model {
	public function getSetting($key, $default = '') {
		$table = DB_PREFIX . 'ayarlar';
		$exists = $this->db->query("SHOW TABLES LIKE '" . $this->db->escape($table) . "'");

		if (!$exists->num_rows) {
			return $default;
		}

		$query = $this->db->query("SELECT ayar_value FROM `" . DB_PREFIX . "ayarlar`
			WHERE ayar_key = '" . $this->db->escape($key) . "'
			LIMIT 1");

		return $query->num_rows ? $query->row['ayar_value'] : $default;
	}

	public function getPendingOrders($limit = 20) {
		$this->ensureIntegrationColumns();

		$limit = max(1, min(100, (int)$limit));
		$language_id = $this->getTurkishLanguageId();

		$orders = $this->db->query("SELECT ro.restaurant_order_id, ro.table_id, ro.total_amount, ro.customer_note,
				ro.date_added, ro.date_modified, rt.table_no, rt.name AS table_name
			FROM `" . DB_PREFIX . "restaurant_order` ro
			LEFT JOIN `" . DB_PREFIX . "restaurant_table` rt ON (rt.table_id = ro.table_id)
			WHERE ro.service_status = 'in_kitchen'
				AND ro.integration_status IN ('pending_export', 'failed')
			ORDER BY ro.restaurant_order_id ASC
			LIMIT " . $limit)->rows;

		foreach ($orders as $key => $order) {
			$orders[$key]['products'] = $this->getOrderProducts((int)$order['restaurant_order_id'], $language_id);
		}

		return $orders;
	}

	public function markOrder($restaurant_order_id, $status, $external_order_no = '', $message = '') {
		$this->ensureIntegrationColumns();

		$restaurant_order_id = (int)$restaurant_order_id;
		$allowed = array('sent', 'failed');

		if (!$restaurant_order_id || !in_array($status, $allowed, true)) {
			return false;
		}

		$this->db->query("UPDATE `" . DB_PREFIX . "restaurant_order`
			SET integration_status = '" . $this->db->escape($status) . "',
				external_order_no = '" . $this->db->escape($external_order_no) . "',
				integration_message = '" . $this->db->escape($message) . "',
				integration_date = NOW()
			WHERE restaurant_order_id = '" . $restaurant_order_id . "'");

		return true;
	}

	public function getSentOrders($limit = 50) {
		$this->ensureIntegrationColumns();

		$limit = max(1, min(200, (int)$limit));

		return $this->db->query("SELECT restaurant_order_id, table_id, external_order_no, service_status, date_modified
			FROM `" . DB_PREFIX . "restaurant_order`
			WHERE integration_status = 'sent'
				AND external_order_no IS NOT NULL
				AND external_order_no <> ''
				AND service_status IN ('waiting_order','in_kitchen','ready_for_service','out_for_service','served')
			ORDER BY restaurant_order_id ASC
			LIMIT " . $limit)->rows;
	}

	public function markOrderPaid($restaurant_order_id, $external_fis_id = 0, $closed_at = '', $message = '') {
		$this->ensureIntegrationColumns();

		$restaurant_order_id = (int)$restaurant_order_id;

		if (!$restaurant_order_id) {
			return false;
		}

		$order_query = $this->db->query("SELECT table_id, service_status
			FROM `" . DB_PREFIX . "restaurant_order`
			WHERE restaurant_order_id = '" . $restaurant_order_id . "'
			LIMIT 1");

		if (!$order_query->num_rows || $order_query->row['service_status'] === 'paid') {
			return false;
		}

		$table_id = (int)$order_query->row['table_id'];
		$old_status = (string)$order_query->row['service_status'];

		if ($message === '') {
			$message = 'Akinsoft adisyon kapandi.';

			if ($external_fis_id) {
				$message .= ' Fis #' . (int)$external_fis_id;
			}

			if ($closed_at !== '') {
				$message .= ' - ' . $closed_at;
			}
		}

		$this->db->query("UPDATE `" . DB_PREFIX . "restaurant_order`
			SET service_status = 'paid',
				is_paid = '1',
				integration_message = '" . $this->db->escape($message) . "',
				integration_date = NOW(),
				date_modified = NOW()
			WHERE restaurant_order_id = '" . $restaurant_order_id . "'");

		$this->db->query("INSERT INTO `" . DB_PREFIX . "restaurant_order_history`
			SET restaurant_order_id = '" . $restaurant_order_id . "',
				old_status = '" . $this->db->escape($old_status) . "',
				new_status = 'paid',
				user_id = '0',
				comment = '" . $this->db->escape($message) . "',
				date_added = NOW()");

		$this->db->query("UPDATE `" . DB_PREFIX . "restaurant_call`
			SET status = 'closed',
				date_modified = NOW()
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

		return true;
	}

	public function getPendingCommands($limit = 10) {
		$this->installBridgeCommandTable();

		$limit = max(1, min(50, (int)$limit));
		$commands = $this->db->query("SELECT command_id, command, status, message, date_added
			FROM `" . DB_PREFIX . "restaurant_akinsoft_bridge_command`
			WHERE status = 'pending'
			ORDER BY command_id ASC
			LIMIT " . $limit)->rows;

		foreach ($commands as $command) {
			$this->db->query("UPDATE `" . DB_PREFIX . "restaurant_akinsoft_bridge_command`
				SET status = 'running',
					date_started = NOW()
				WHERE command_id = '" . (int)$command['command_id'] . "'
					AND status = 'pending'");
		}

		return $commands;
	}

	public function markCommand($command_id, $status, $message = '') {
		$this->installBridgeCommandTable();

		$command_id = (int)$command_id;
		$allowed = array('done', 'failed');

		if (!$command_id || !in_array($status, $allowed, true)) {
			return false;
		}

		$this->db->query("UPDATE `" . DB_PREFIX . "restaurant_akinsoft_bridge_command`
			SET status = '" . $this->db->escape($status) . "',
				message = '" . $this->db->escape($message) . "',
				date_finished = NOW()
			WHERE command_id = '" . $command_id . "'");

		return true;
	}

	public function syncTablesFromBridge(array $rows) {
		$source_table_numbers = array();
		$inserted = 0;
		$updated = 0;

		foreach ($rows as $row) {
			if (!is_array($row)) {
				continue;
			}

			$masaadi = isset($row['MASAADI']) ? $row['MASAADI'] : (isset($row['masaadi']) ? $row['masaadi'] : '');
			$blkodu = isset($row['BLKODU']) ? $row['BLKODU'] : (isset($row['blkodu']) ? $row['blkodu'] : 0);
			$table_no = $this->parseTableNo($masaadi, $blkodu);
			$name = trim((string)$masaadi);
			$area = isset($row['MASAGRUBU']) ? trim((string)$row['MASAGRUBU']) : '';
			$capacity = !empty($row['KACKISILIK']) ? (int)$row['KACKISILIK'] : 4;
			$status = !empty($row['GIZLI']) ? 0 : 1;

			if ($name === '') {
				$name = 'Masa ' . $table_no;
			}

			if ($area === '') {
				$area = 'Salon';
			}

			$source_table_numbers[] = $table_no;

			$existing = $this->db->query("SELECT table_id, qr_token FROM `" . DB_PREFIX . "restaurant_table`
				WHERE table_no = '" . (int)$table_no . "'
				LIMIT 1");

			if ($existing->num_rows) {
				$this->db->query("UPDATE `" . DB_PREFIX . "restaurant_table`
					SET name = '" . $this->db->escape($name) . "',
						area = '" . $this->db->escape($area) . "',
						capacity = '" . (int)$capacity . "',
						sort_order = '" . (int)$table_no . "',
						status = '" . (int)$status . "',
						date_modified = NOW()
					WHERE table_id = '" . (int)$existing->row['table_id'] . "'");
				$updated++;
			} else {
				$qr_token = function_exists('random_bytes') ? bin2hex(random_bytes(8)) : md5(uniqid('', true));

				$this->db->query("INSERT INTO `" . DB_PREFIX . "restaurant_table`
					SET table_no = '" . (int)$table_no . "',
						name = '" . $this->db->escape($name) . "',
						capacity = '" . (int)$capacity . "',
						area = '" . $this->db->escape($area) . "',
						sort_order = '" . (int)$table_no . "',
						status = '" . (int)$status . "',
						qr_token = '" . $this->db->escape($qr_token) . "',
						date_added = NOW(),
						date_modified = NOW()");

				$table_id = (int)$this->db->getLastId();

				$this->db->query("INSERT IGNORE INTO `" . DB_PREFIX . "restaurant_table_status`
					SET table_id = '" . $table_id . "',
						service_status = 'empty',
						active_order_count = '0',
						total_amount = '0.0000',
						date_modified = NOW()");

				$inserted++;
			}
		}

		$source_table_numbers = array_values(array_unique(array_map('intval', $source_table_numbers)));

		if ($source_table_numbers) {
			$this->db->query("UPDATE `" . DB_PREFIX . "restaurant_table`
				SET status = '0',
					date_modified = NOW()
				WHERE table_no NOT IN (" . implode(',', $source_table_numbers) . ")");
		}

		return array(
			'success' => true,
			'message' => 'Masa senkronu Bridge ile tamamlandi. Akinsoft masa: ' . count($rows) . ', eklenen: ' . $inserted . ', guncellenen: ' . $updated,
			'inserted' => $inserted,
			'updated' => $updated,
			'total' => count($rows)
		);
	}

	public function syncPricesFromBridge(array $rows) {
		$this->installProductSyncTable();

		$prices = array();

		foreach ($rows as $row) {
			if (!is_array($row)) {
				continue;
			}

			$stock_code = isset($row['STOKKODU']) ? trim((string)$row['STOKKODU']) : '';

			if ($stock_code !== '' && isset($row['FIYATI'])) {
				$prices[$stock_code] = (float)$row['FIYATI'];
			}
		}

		$matched = 0;
		$missing = 0;
		$products = $this->db->query("SELECT product_id, model, price FROM `" . DB_PREFIX . "product`
			WHERE model IS NOT NULL AND model <> ''
			ORDER BY product_id ASC")->rows;

		$this->db->query("TRUNCATE TABLE `" . DB_PREFIX . "restaurant_akinsoft_product_sync`");

		foreach ($products as $product) {
			$stock_code = trim((string)$product['model']);

			if ($stock_code === '') {
				continue;
			}

			if (isset($prices[$stock_code])) {
				$price = (float)$prices[$stock_code];

				$this->db->query("UPDATE `" . DB_PREFIX . "product`
					SET price = '" . (float)$price . "',
						date_modified = NOW()
					WHERE model = '" . $this->db->escape($stock_code) . "'");

				$this->db->query("INSERT INTO `" . DB_PREFIX . "restaurant_akinsoft_product_sync`
					SET product_id = '" . (int)$product['product_id'] . "',
						model = '" . $this->db->escape($stock_code) . "',
						status = 'matched',
						akinsoft_price = '" . (float)$price . "',
						old_price = '" . (float)$product['price'] . "',
						date_synced = NOW()");

				$matched++;
			} else {
				$this->db->query("INSERT INTO `" . DB_PREFIX . "restaurant_akinsoft_product_sync`
					SET product_id = '" . (int)$product['product_id'] . "',
						model = '" . $this->db->escape($stock_code) . "',
						status = 'missing',
						akinsoft_price = NULL,
						old_price = '" . (float)$product['price'] . "',
						date_synced = NOW()");

				$missing++;
			}
		}

		return array(
			'success' => true,
			'message' => 'Fiyat senkronu Bridge ile tamamlandi. Eslesen urun: ' . $matched . ', OpenCartta bulunmayan stok kodu: ' . $missing,
			'matched' => $matched,
			'missing' => $missing,
			'total_prices' => count($prices)
		);
	}

	private function getOrderProducts($restaurant_order_id, $language_id) {
		return $this->db->query("SELECT rop.restaurant_order_product_id, rop.product_id,
				COALESCE(NULLIF(pd.name, ''), rop.name) AS name,
				p.model,
				rop.quantity,
				rop.price,
				rop.total
			FROM `" . DB_PREFIX . "restaurant_order_product` rop
			LEFT JOIN `" . DB_PREFIX . "product` p ON (p.product_id = rop.product_id)
			LEFT JOIN `" . DB_PREFIX . "product_description` pd ON (pd.product_id = rop.product_id AND pd.language_id = '" . (int)$language_id . "')
			WHERE rop.restaurant_order_id = '" . (int)$restaurant_order_id . "'
			ORDER BY rop.restaurant_order_product_id ASC")->rows;
	}

	private function ensureIntegrationColumns() {
		$table = DB_PREFIX . 'restaurant_order';
		$columns = array();
		$query = $this->db->query("SHOW COLUMNS FROM `" . $this->db->escape($table) . "`");

		foreach ($query->rows as $row) {
			$columns[$row['Field']] = true;
		}

		if (empty($columns['integration_status'])) {
			$this->db->query("ALTER TABLE `" . $this->db->escape($table) . "` ADD `integration_status` varchar(32) NOT NULL DEFAULT 'none'");
		}

		if (empty($columns['integration_message'])) {
			$this->db->query("ALTER TABLE `" . $this->db->escape($table) . "` ADD `integration_message` text NULL");
		}

		if (empty($columns['integration_date'])) {
			$this->db->query("ALTER TABLE `" . $this->db->escape($table) . "` ADD `integration_date` datetime NULL");
		}

		if (empty($columns['external_order_no'])) {
			$this->db->query("ALTER TABLE `" . $this->db->escape($table) . "` ADD `external_order_no` varchar(64) NULL");
		}
	}

	private function getTurkishLanguageId() {
		$query = $this->db->query("SELECT language_id FROM `" . DB_PREFIX . "language`
			WHERE code IN ('tr-tr', 'tr', 'turkish')
			ORDER BY language_id ASC
			LIMIT 1");

		return $query->num_rows ? (int)$query->row['language_id'] : (int)$this->config->get('config_language_id');
	}

	private function installProductSyncTable() {
		$this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "restaurant_akinsoft_product_sync` (
			`sync_id` int(11) NOT NULL AUTO_INCREMENT,
			`product_id` int(11) NOT NULL,
			`model` varchar(64) NOT NULL,
			`status` varchar(16) NOT NULL,
			`akinsoft_price` decimal(15,4) NULL,
			`old_price` decimal(15,4) NULL,
			`date_synced` datetime NOT NULL,
			PRIMARY KEY (`sync_id`),
			KEY `product_id` (`product_id`),
			KEY `model` (`model`),
			KEY `status` (`status`)
		) ENGINE=MyISAM DEFAULT CHARSET=utf8");
	}

	private function installBridgeCommandTable() {
		$this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "restaurant_akinsoft_bridge_command` (
			`command_id` int(11) NOT NULL AUTO_INCREMENT,
			`command` varchar(32) NOT NULL,
			`status` varchar(16) NOT NULL DEFAULT 'pending',
			`message` text NULL,
			`date_added` datetime NOT NULL,
			`date_started` datetime NULL,
			`date_finished` datetime NULL,
			PRIMARY KEY (`command_id`),
			KEY `status` (`status`),
			KEY `command` (`command`)
		) ENGINE=MyISAM DEFAULT CHARSET=utf8");
	}

	private function parseTableNo($masaadi, $fallback) {
		if (preg_match('/\d+/', (string)$masaadi, $match)) {
			return max(1, (int)$match[0]);
		}

		return max(1, (int)$fallback);
	}
}
