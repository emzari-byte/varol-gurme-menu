<?php
class ModelExtensionModuleRestaurantTables extends Model {
	public function getTables() {
		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "restaurant_table` ORDER BY sort_order ASC, table_no ASC");
		return $query->rows;
	}

	public function getTable($table_id) {
		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "restaurant_table` WHERE table_id = '" . (int)$table_id . "'");
		return $query->row;
	}

	public function addTable($data) {
		$this->db->query("INSERT INTO `" . DB_PREFIX . "restaurant_table` SET
			table_no = '" . (int)$data['table_no'] . "',
			name = '" . $this->db->escape($data['name']) . "',
			capacity = '" . (int)$data['capacity'] . "',
			area = '" . $this->db->escape($data['area']) . "',
			sort_order = '" . (int)$data['sort_order'] . "',
			status = '" . (int)$data['status'] . "',
			date_added = NOW(),
			date_modified = NOW()");
	}

	public function editTable($table_id, $data) {
		$this->db->query("UPDATE `" . DB_PREFIX . "restaurant_table` SET
			table_no = '" . (int)$data['table_no'] . "',
			name = '" . $this->db->escape($data['name']) . "',
			capacity = '" . (int)$data['capacity'] . "',
			area = '" . $this->db->escape($data['area']) . "',
			sort_order = '" . (int)$data['sort_order'] . "',
			status = '" . (int)$data['status'] . "',
			date_modified = NOW()
			WHERE table_id = '" . (int)$table_id . "'");
	}

	public function deleteTable($table_id) {
		$this->db->query("DELETE FROM `" . DB_PREFIX . "restaurant_table` WHERE table_id = '" . (int)$table_id . "'");
	}
	public function getTableByQrToken($qr_token) {
	$query = $this->db->query("SELECT *
		FROM `" . DB_PREFIX . "restaurant_table`
		WHERE qr_token = '" . $this->db->escape($qr_token) . "'
		AND status = '1'
		LIMIT 1");

	return $query->row;
}
}