<?php
include_once(modification(DIR_APPLICATION . 'model/extension/module/aqe/general.php'));

class ModelExtensionModuleAqeMarketingMarketing extends ModelExtensionModuleAqeGeneral {
	protected static $count = 0;

	public function getMarketings($data = array()) {
		if (isset($data['columns'])) {
			$columns = $data['columns'];
		} else {
			$columns = array('name', 'code', 'clicks', 'orders', 'date_added');
		}

		$sql = "SELECT SQL_CALC_FOUND_ROWS m.*";

		$order_statuses = array();

		$_order_statuses = $this->config->get('config_complete_status');

		foreach ($_order_statuses as $order_status_id) {
			$order_statuses[] = "o.order_status_id = '" . (int)$order_status_id . "'";
		}

		if (in_array("orders", $columns) || isset($data["filter_orders"])) {
			$sql .= ", (SELECT COUNT(*) FROM `" . DB_PREFIX . "order` o WHERE (" . implode(" OR ", $order_statuses) . ") AND o.marketing_id = m.marketing_id) AS orders";
		}

		$sql .= " FROM `" . DB_PREFIX . "marketing` m";

		$where = array();

		$int_filters = array(
			'id'                => 'm.marketing_id',
		);

		foreach ($int_filters as $key => $value) {
			if (isset($data["filter_$key"]) && !is_null($data["filter_$key"])) {
				$where[] = "$value = '" . (int)$data["filter_$key"] . "'";
			}
		}

		$int_interval_filters = array(
			'clicks'        => 'm.clicks',
			'orders'        => "(SELECT COUNT(*) FROM `" . DB_PREFIX . "order` o WHERE (" . implode(" OR ", $order_statuses) . ") AND o.marketing_id = m.marketing_id)",
		);

		foreach ($int_interval_filters as $key => $value) {
			if (isset($data["filter_$key"]) && !is_null($data["filter_$key"])) {
				if ($this->config->get('module_admin_quick_edit_interval_filter')) {
					$where[] = $this->filterInterval($data["filter_$key"], $value);
				} else {
					$where[] = "$value = '" . (int)$data["filter_$key"] . "'";
				}
			}
		}

		$date_filters = array(
			'date_added'        => 'm.date_added',
		);

		foreach ($date_filters as $key => $value) {
			if (isset($data["filter_$key"]) && !is_null($data["filter_$key"])) {
				if ($this->config->get('module_admin_quick_edit_interval_filter')) {
					$where[] = $this->filterInterval($this->db->escape($data["filter_$key"]), $value, true);
				} else {
					$where[] = "DATE($value) = DATE('" . $this->db->escape($data["filter_$key"]) . "')";
				}
			}
		}

		$anywhere_filters = array(
			'code'       => 'm.code',
			'name'       => 'm.name',
			'description'=> 'm.description',
		);

		foreach ($anywhere_filters as $key => $value) {
			if (!empty($data["filter_$key"])) {
				if ($this->config->get('module_admin_quick_edit_match_anywhere')) {
					$tokens = preg_split("/\s+/", trim($data["filter_$key"]));

					foreach ($tokens as $token) {
						$where[] = "$value LIKE '%" . $this->db->escape($token) . "%'";
					}
				} else {
					$where[] = "$value LIKE '" . $this->db->escape($data["filter_$key"]) . "%'";
				}
			}
		}

		if ($where) {
			$sql .= " WHERE " . implode(" AND ", $where);
		}

		$sql .= " GROUP BY m.marketing_id";

		$sort_data = array(
			'm.marketing_id',
			'm.name',
			'm.description',
			'm.code',
			'm.clicks',
			'orders',
			'm.date_added',
		);

		if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
			$sql .= " ORDER BY " . $data['sort'];
		} else {
			$sql .= " ORDER BY m.name";
		}

		if (isset($data['order']) && ($data['order'] == 'DESC')) {
			$sql .= " DESC";
		} else {
			$sql .= " ASC";
		}

		if (isset($data['start']) || isset($data['limit'])) {
			if ($data['start'] < 0) {
				$data['start'] = 0;
			}

			if ($data['limit'] < 1) {
				$data['limit'] = 20;
			}

			$sql .= " LIMIT " . (int)$data['start'] . "," . (int)$data['limit'];
		}

		$query = $this->db->query($sql);

		$count = $this->db->query("SELECT FOUND_ROWS() AS count");
		$this->count = ($count->num_rows) ? (int)$count->row['count'] : 0;

		return $query->rows;
	}

	public function getTotalMarketings() {
		return $this->count;
	}

	public function quickEditMarketing($marketing_id, $column, $value, $lang_id=null, $data=null) {
		$editable = array('code', 'name', 'description');
		$result = false;
		if (in_array($column, $editable)) {
			$result = $this->db->query("UPDATE `" . DB_PREFIX . "marketing` SET " . $column . " = '" . $this->db->escape($value) . "' WHERE marketing_id = '" . (int)$marketing_id . "'");
		}

		return $result;
	}
}
