<?php
class ModelApiAkinsoft extends Model {
	public function getOrderProducts($restaurant_order_id) {
		$restaurant_order_id = (int)$restaurant_order_id;

		if (!$restaurant_order_id) {
			return array();
		}

		$sql = "SELECT 
					product_id,
					name,
					price,
					quantity,
					total
				FROM `" . DB_PREFIX . "restaurant_order_product`
				WHERE restaurant_order_id = '" . $restaurant_order_id . "'
				ORDER BY restaurant_order_product_id ASC";

		return $this->db->query($sql)->rows;
	}

	public function getOrdersForExport() {
		$sql = "SELECT 
					ro.restaurant_order_id,
					ro.table_id,
					ro.waiter_user_id,
					ro.service_status,
					ro.customer_note,
					ro.total_amount,
					ro.date_added,
					ro.integration_status,
					rt.table_no,
					rt.name AS table_name
				FROM `" . DB_PREFIX . "restaurant_order` ro
				LEFT JOIN `" . DB_PREFIX . "restaurant_table` rt ON (ro.table_id = rt.table_id)
				WHERE ro.service_status = 'in_kitchen'
				AND ro.integration_status IN ('pending_export', 'failed')
				ORDER BY ro.restaurant_order_id ASC";

		$orders = $this->db->query($sql)->rows;

		foreach ($orders as $key => $order) {
			$orders[$key]['products'] = $this->getOrderProducts($order['restaurant_order_id']);
		}

		return $orders;
	}

	public function markOrderAsExported($restaurant_order_id, $external_order_no = '', $message = '') {
		$restaurant_order_id = (int)$restaurant_order_id;

		if (!$restaurant_order_id) {
			return false;
		}

		$this->db->query("UPDATE `" . DB_PREFIX . "restaurant_order`
			SET integration_status = 'sent',
				external_order_no = '" . $this->db->escape($external_order_no) . "',
				integration_message = '" . $this->db->escape($message) . "',
				integration_date = NOW()
			WHERE restaurant_order_id = '" . $restaurant_order_id . "'");

		return true;
	}
}