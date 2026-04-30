<?php
class ModelExtensionModuleRestaurantSettings extends Model {
	private $table = 'ayarlar';

	public function install() {
		$this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . $this->table . "` (
			`ayar_id` int(11) NOT NULL AUTO_INCREMENT,
			`ayar_key` varchar(128) NOT NULL,
			`ayar_value` text NOT NULL,
			`date_modified` datetime NOT NULL,
			PRIMARY KEY (`ayar_id`),
			UNIQUE KEY `ayar_key` (`ayar_key`)
		) ENGINE=MyISAM DEFAULT CHARSET=utf8");

		foreach ($this->getDefaults() as $key => $value) {
			$this->db->query("INSERT IGNORE INTO `" . DB_PREFIX . $this->table . "`
				SET ayar_key = '" . $this->db->escape($key) . "',
					ayar_value = '" . $this->db->escape($value) . "',
					date_modified = NOW()");
		}
	}

	public function getSettings() {
		$this->install();

		$data = $this->getDefaults();
		$query = $this->db->query("SELECT ayar_key, ayar_value FROM `" . DB_PREFIX . $this->table . "`");

		foreach ($query->rows as $row) {
			$data[$row['ayar_key']] = $row['ayar_value'];
		}

		return $data;
	}

	public function editSettings($data) {
		$this->install();

		foreach ($this->getDefaults() as $key => $default) {
			$value = isset($data[$key]) ? $data[$key] : $default;

			$this->db->query("INSERT INTO `" . DB_PREFIX . $this->table . "`
				SET ayar_key = '" . $this->db->escape($key) . "',
					ayar_value = '" . $this->db->escape($value) . "',
					date_modified = NOW()
				ON DUPLICATE KEY UPDATE
					ayar_value = '" . $this->db->escape($value) . "',
					date_modified = NOW()");
		}
	}

	public function getDefaults() {
		return array(
			'restaurant_plain_qr_menu' => '1',
			'restaurant_qr_order_menu' => '1',
			'restaurant_menu_theme' => 'v1',
			'restaurant_brand_logo' => 'catalog/veranda-logo2.png',
			'restaurant_waiter_panel' => '1',
			'restaurant_kitchen_panel' => '1',
			'restaurant_waiter_call_reset_minutes' => '5',
			'restaurant_bill_request_reset_minutes' => '5',
			'restaurant_openai_api_key' => defined('OPENAI_API_KEY') ? (string)OPENAI_API_KEY : '',
			'restaurant_weatherapi_key' => defined('WEATHERAPI_KEY') ? (string)WEATHERAPI_KEY : '',
			'restaurant_weather_lat' => defined('MENU_WEATHER_LAT') ? (string)MENU_WEATHER_LAT : '37.766670',
			'restaurant_weather_lon' => defined('MENU_WEATHER_LON') ? (string)MENU_WEATHER_LON : '29.031022',
			'restaurant_whatsapp_phone' => '905337843120',
			'restaurant_feedback_email' => 'can@varoltekstil.com.tr',
			'restaurant_akinsoft_enabled' => '0',
			'restaurant_akinsoft_mode' => 'bridge_agent',
			'restaurant_akinsoft_host' => 'localhost',
			'restaurant_akinsoft_port' => '3050',
			'restaurant_akinsoft_db_path' => 'C:\\AKINSOFT\\Wolvox9\\Database_FB\\DEMOWOLVOX\\2026\\WOLVOX.FDB',
			'restaurant_akinsoft_charset' => 'WIN1254',
			'restaurant_akinsoft_bridge_url' => '',
			'restaurant_akinsoft_bridge_token' => '',
			'restaurant_akinsoft_url' => '',
			'restaurant_akinsoft_company' => '',
			'restaurant_akinsoft_branch' => '',
			'restaurant_akinsoft_user' => 'SYSDBA',
			'restaurant_akinsoft_pass' => 'masterkey'
		);
	}

	public function getProductionChecks() {
		$this->install();

		$settings = $this->getSettings();
		$checks = array();
		$active_token_count = $this->countRows("SELECT COUNT(*) AS total FROM `" . DB_PREFIX . "restaurant_table_status` WHERE active_session_token IS NOT NULL AND active_session_token <> ''");

		$checks[] = $this->buildCheck(
			'QR token aktif mi?',
			true,
			'Aktif masa token say&#305;s&#305;: ' . $active_token_count,
			$active_token_count > 0 ? 'ok' : 'warning'
		);

		$checks[] = $this->buildCheck(
			'Garson panel a&ccedil;&#305;k m&#305;?',
			!empty($settings['restaurant_waiter_panel']) && (int)$settings['restaurant_waiter_panel'] === 1,
			((int)$settings['restaurant_waiter_panel'] === 1) ? 'Garson paneli aktif.' : 'Garson paneli kapal&#305;.'
		);

		$checks[] = $this->buildCheck(
			'Mutfak panel a&ccedil;&#305;k m&#305;?',
			!empty($settings['restaurant_kitchen_panel']) && (int)$settings['restaurant_kitchen_panel'] === 1,
			((int)$settings['restaurant_kitchen_panel'] === 1) ? 'Mutfak paneli aktif.' : 'Mutfak paneli kapal&#305;.'
		);

		$waiter_count = $this->countRows("SELECT COUNT(*) AS total FROM `" . DB_PREFIX . "restaurant_waiter` WHERE status = '1'");
		$assigned_waiter_count = $this->countRows("SELECT COUNT(DISTINCT rw.waiter_id) AS total
			FROM `" . DB_PREFIX . "restaurant_waiter` rw
			INNER JOIN `" . DB_PREFIX . "restaurant_waiter_table` rwt ON (rwt.waiter_id = rw.waiter_id)
			WHERE rw.status = '1'");
		$unassigned_waiter_count = max(0, $waiter_count - $assigned_waiter_count);

		$checks[] = $this->buildCheck(
			'Masa yetki kontrol&uuml; do&#287;ru mu?',
			$waiter_count > 0 && $unassigned_waiter_count === 0,
			'Aktif garson: ' . $waiter_count . ', masas&#305; olmayan garson: ' . $unassigned_waiter_count,
			$unassigned_waiter_count > 0 || $waiter_count === 0 ? 'warning' : 'ok'
		);

		$sound_file = DIR_APPLICATION . '../sound/notify.wav';
		$sound_ok = is_file($sound_file) && filesize($sound_file) > 0;

		$checks[] = $this->buildCheck(
			'Bildirim sesi &ccedil;al&#305;&#351;&#305;yor mu?',
			$sound_ok,
			$sound_ok ? 'notify.wav bulundu.' : 'sound/notify.wav bulunamad&#305; veya bo&#351;.',
			$sound_ok ? 'ok' : 'error'
		);

		$open_waiter_calls = $this->countRows("SELECT COUNT(*) AS total FROM `" . DB_PREFIX . "restaurant_call` WHERE call_type = 'waiter_call' AND status IN ('new','seen')");
		$open_bill_requests = $this->countRows("SELECT COUNT(*) AS total FROM `" . DB_PREFIX . "restaurant_call` WHERE call_type = 'bill_request' AND status IN ('new','seen')");

		$checks[] = $this->buildCheck(
			'A&ccedil;&#305;k hesap/garson &ccedil;a&#287;r&#305;s&#305; var m&#305;?',
			($open_waiter_calls + $open_bill_requests) === 0,
			'Garson &ccedil;a&#287;r&#305;s&#305;: ' . $open_waiter_calls . ', hesap talebi: ' . $open_bill_requests,
			($open_waiter_calls + $open_bill_requests) > 0 ? 'warning' : 'ok'
		);

		$active_orders = $this->countRows("SELECT COUNT(*) AS total FROM `" . DB_PREFIX . "restaurant_order` WHERE service_status IN ('waiting_order','in_kitchen','ready_for_service','out_for_service','served')");
		$late_orders = $this->countLateKitchenOrders();

		$checks[] = $this->buildCheck(
			'Son sipari&#351; ak&#305;&#351;&#305; nerede?',
			true,
			'Aktif sipari&#351;: ' . $active_orders . ', geciken mutfak sipari&#351;i: ' . $late_orders,
			$late_orders > 0 ? 'warning' : 'ok'
		);

		return $checks;
	}

	private function buildCheck($title, $ok, $detail, $state = '') {
		if ($state === '') {
			$state = $ok ? 'ok' : 'error';
		}

		return array(
			'title' => $title,
			'ok' => (bool)$ok,
			'state' => $state,
			'detail' => $detail
		);
	}

	private function countRows($sql) {
		try {
			$query = $this->db->query($sql);

			return $query->num_rows ? (int)$query->row['total'] : 0;
		} catch (Exception $e) {
			return 0;
		}
	}

	private function countLateKitchenOrders() {
		try {
			$language_id = (int)$this->config->get('config_language_id');
			$orders = $this->db->query("SELECT ro.restaurant_order_id, ro.date_modified
				FROM `" . DB_PREFIX . "restaurant_order` ro
				WHERE ro.service_status = 'in_kitchen'")->rows;
			$late = 0;

			foreach ($orders as $order) {
				$products = $this->db->query("SELECT IFNULL(pd.tag, '') AS prep_tag
					FROM `" . DB_PREFIX . "restaurant_order_product` rop
					LEFT JOIN `" . DB_PREFIX . "product_description` pd ON (pd.product_id = rop.product_id AND pd.language_id = '" . $language_id . "')
					WHERE rop.restaurant_order_id = '" . (int)$order['restaurant_order_id'] . "'")->rows;
				$minutes = 0;

				foreach ($products as $product) {
					preg_match_all('/\d+/', (string)$product['prep_tag'], $matches);

					if (!empty($matches[0])) {
						$minutes = max($minutes, max(array_map('intval', $matches[0])));
					}
				}

				if ($minutes > 0 && strtotime($order['date_modified']) + ($minutes * 60) < time()) {
					$late++;
				}
			}

			return $late;
		} catch (Exception $e) {
			return 0;
		}
	}
}
