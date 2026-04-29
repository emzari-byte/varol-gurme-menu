<?php
class ModelExtensionModuleRestaurantTables extends Model {
	public function getTableByQrToken($qr_token) {
		$query = $this->db->query("SELECT *
			FROM `" . DB_PREFIX . "restaurant_table`
			WHERE qr_token = '" . $this->db->escape($qr_token) . "'
			AND status = '1'
			LIMIT 1");

		return $query->row;
	}
}