<?php
class ModelExtensionModuleRestaurantTables extends Model {
	private function ensureColumns() {
		$query = $this->db->query("SHOW COLUMNS FROM `" . DB_PREFIX . "restaurant_table` LIKE 'qr_order_enabled'");

		if (!$query->num_rows) {
			$this->db->query("ALTER TABLE `" . DB_PREFIX . "restaurant_table` ADD `qr_order_enabled` TINYINT(1) NOT NULL DEFAULT '1' AFTER `qr_token`");
		}
	}

	public function getTables() {
		$this->ensureColumns();

		$query = $this->db->query("SELECT rt.*,
				(
					SELECT COUNT(*)
					FROM `" . DB_PREFIX . "restaurant_waiter_table` rwt
					INNER JOIN `" . DB_PREFIX . "restaurant_waiter` rw ON (rw.waiter_id = rwt.waiter_id)
					WHERE rwt.table_id = rt.table_id
					AND rw.status = '1'
				) AS waiter_count,
				(
					SELECT GROUP_CONCAT(rw.name ORDER BY rw.name ASC SEPARATOR ', ')
					FROM `" . DB_PREFIX . "restaurant_waiter_table` rwt
					INNER JOIN `" . DB_PREFIX . "restaurant_waiter` rw ON (rw.waiter_id = rwt.waiter_id)
					WHERE rwt.table_id = rt.table_id
					AND rw.status = '1'
				) AS waiter_names
			FROM `" . DB_PREFIX . "restaurant_table` rt
			ORDER BY rt.sort_order ASC, rt.table_no ASC");
		return $query->rows;
	}

	public function getTable($table_id) {
		$this->ensureColumns();

		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "restaurant_table` WHERE table_id = '" . (int)$table_id . "'");
		return $query->row;
	}

	public function addTable($data) {
		$this->ensureColumns();

		$this->db->query("INSERT INTO `" . DB_PREFIX . "restaurant_table` SET
			table_no = '" . (int)$data['table_no'] . "',
			name = '" . $this->db->escape($data['name']) . "',
			capacity = '" . (int)$data['capacity'] . "',
			area = '" . $this->db->escape($data['area']) . "',
			sort_order = '" . (int)$data['sort_order'] . "',
			qr_order_enabled = '" . (isset($data['qr_order_enabled']) ? (int)$data['qr_order_enabled'] : 1) . "',
			status = '" . (int)$data['status'] . "',
			date_added = NOW(),
			date_modified = NOW()");
	}

	public function editTable($table_id, $data) {
		$this->ensureColumns();

		$this->db->query("UPDATE `" . DB_PREFIX . "restaurant_table` SET
			table_no = '" . (int)$data['table_no'] . "',
			name = '" . $this->db->escape($data['name']) . "',
			capacity = '" . (int)$data['capacity'] . "',
			area = '" . $this->db->escape($data['area']) . "',
			sort_order = '" . (int)$data['sort_order'] . "',
			qr_order_enabled = '" . (isset($data['qr_order_enabled']) ? (int)$data['qr_order_enabled'] : 1) . "',
			status = '" . (int)$data['status'] . "',
			date_modified = NOW()
			WHERE table_id = '" . (int)$table_id . "'");
	}

	public function deleteTable($table_id) {
		$this->db->query("DELETE FROM `" . DB_PREFIX . "restaurant_table` WHERE table_id = '" . (int)$table_id . "'");
	}

	public function setQrOrderEnabled($table_id, $enabled) {
		$this->ensureColumns();

		$this->db->query("UPDATE `" . DB_PREFIX . "restaurant_table`
			SET qr_order_enabled = '" . ((int)$enabled ? 1 : 0) . "',
				date_modified = NOW()
			WHERE table_id = '" . (int)$table_id . "'");
	}

	public function getTableByQrToken($qr_token) {
	$this->ensureColumns();

	$query = $this->db->query("SELECT *
		FROM `" . DB_PREFIX . "restaurant_table`
		WHERE qr_token = '" . $this->db->escape($qr_token) . "'
		AND status = '1'
		LIMIT 1");

	return $query->row;
}
}
