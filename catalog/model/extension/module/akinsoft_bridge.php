<?php
class ModelExtensionModuleAkinsoftBridge extends Model {
	public function getSetting($key, $default = '') {
		$query = $this->db->query("SELECT ayar_value FROM `" . DB_PREFIX . "ayarlar`
			WHERE ayar_key = '" . $this->db->escape($key) . "'
			LIMIT 1");

		return $query->num_rows ? $query->row['ayar_value'] : $default;
	}

	public function getPendingOrders($limit = 20) {
		$limit = max(1, min(100, (int)$limit));
		$language_id = $this->getTurkishLanguageId();

		$orders = $this->db->query("SELECT ro.restaurant_order_id, ro.table_id, ro.total, ro.customer_note, ro.payment_method,
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

	private function getTurkishLanguageId() {
		$query = $this->db->query("SELECT language_id FROM `" . DB_PREFIX . "language`
			WHERE code IN ('tr-tr', 'tr', 'turkish')
			ORDER BY language_id ASC
			LIMIT 1");

		return $query->num_rows ? (int)$query->row['language_id'] : (int)$this->config->get('config_language_id');
	}
}
