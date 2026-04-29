<?php
include_once(modification(DIR_APPLICATION . 'model/extension/module/aqe/general.php'));

class ModelExtensionModuleAqeSaleReturn extends ModelExtensionModuleAqeGeneral {
	protected static $count = 0;

	public function getReturns($data = array()) {
		if (isset($data['columns'])) {
			$columns = $data['columns'];
		} else {
			$columns = array('return_id', 'order_id', 'customer', 'product', 'model', 'return_status', 'date_added', 'date_modified');
		}

		$sql = "SELECT SQL_CALC_FOUND_ROWS r.*, CONCAT(r.firstname, ' ', r.lastname) AS customer";

		if (in_array("return_status", $columns)) {
			$sql .= ", rs.name AS return_status";
		}

		if (in_array("return_action", $columns)) {
			$sql .= ", ra.name AS return_action";
		}

		if (in_array("return_reason", $columns)) {
			$sql .= ", rr.name AS return_reason";
		}

		if (in_array("customer_id", $columns)) {
			$sql .= ", CONCAT(c.firstname, ' ', c.lastname) AS customer_name";
		}

		if (in_array("product_id", $columns)) {
			$sql .= ", pd.name AS product_name";
		}

		$sql .= "  FROM `" . DB_PREFIX . "return` r";

		if (in_array("return_status", $columns)) {
			$sql .= "  LEFT JOIN " . DB_PREFIX . "return_status rs ON (r.return_status_id = rs.return_status_id AND rs.language_id = '" . (int)$this->config->get('config_language_id') . "')";
		}

		if (in_array("return_action", $columns)) {
			$sql .= " LEFT JOIN " . DB_PREFIX . "return_action ra ON (r.return_action_id = ra.return_action_id AND ra.language_id = '" . (int)$this->config->get('config_language_id') . "')";
		}

		if (in_array("return_reason", $columns)) {
			$sql .= " LEFT JOIN " . DB_PREFIX . "return_reason rr ON (r.return_reason_id = rr.return_reason_id AND rr.language_id = '" . (int)$this->config->get('config_language_id') . "')";
		}

		if (in_array("customer_id", $columns) || !empty($data['filter_customer_id'])) {
			$sql .= " LEFT JOIN " . DB_PREFIX . "customer c ON (r.customer_id = c.customer_id)";
		}

		if (in_array("product_id", $columns)) {
			$sql .= " LEFT JOIN " . DB_PREFIX . "product_description pd ON (r.product_id = pd.product_id AND pd.language_id = '" . (int)$this->config->get('config_language_id') . "')";
		}

		$where = array();

		$int_filters = array(
			'id'                => 'r.return_id',
			'order_id'          => 'r.order_id',
			'return_status'     => 'r.return_status_id',
			'return_action'     => 'r.return_action_id',
			'return_reason'     => 'r.return_reason_id',
			'opened'            => 'r.opened',
		);

		foreach ($int_filters as $key => $value) {
			if (isset($data["filter_$key"]) && !is_null($data["filter_$key"])) {
				$where[] = "$value = '" . (int)$data["filter_$key"] . "'";
			}
		}

		$exact_filters = array(
			'customer_id'       => "CONCAT(c.firstname, ' ', c.lastname)",
			'product_id'        => 'pd.name',
		);

		foreach ($exact_filters as $key => $value) {
			if (isset($data["filter_$key"]) && !is_null($data["filter_$key"])) {
				$where[] = "$value = '" . $this->db->escape($data["filter_$key"]) . "'";
			}
		}

		$int_interval_filters = array(
			'quantity'          => 'r.quantity',
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
			'date_added'        => 'r.date_added',
			'date_modified'     => 'r.date_modified',
			'date_ordered'      => 'r.date_ordered',
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
			'email'     => 'r.email',
			'telephone' => 'r.telephone',
			'comment'   => 'r.comment',
			'product'   => 'r.product',
			'model'     => 'r.model',
			'customer'  => "CONCAT(r.firstname, ' ', r.lastname)",
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

		$sql .= " GROUP BY r.return_id";

		$sort_data = array(
			'r.return_id',
			'r.order_id',
			'customer_name',
			'customer',
			'r.email',
			'r.telephone',
			'product_name',
			'r.product',
			'r.model',
			'r.quantity',
			'return_reason',
			'r.opened',
			'r.comment',
			'return_action',
			'return_status',
			'r.date_ordered',
			'r.date_added',
			'r.date_modified',
		);

		if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
			$sql .= " ORDER BY " . $data['sort'];
		} else {
			$sql .= " ORDER BY r.return_id";
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

	public function getTotalReturns() {
		return $this->count;
	}

	public function quickEditReturn($return_id, $column, $value, $lang_id=null, $data=null) {
		$editable = array('order_id', 'customer_id', 'customer', 'email', 'telephone', 'product_id', 'product', 'model', 'quantity', 'return_reason', 'opened', 'comment', 'return_action', 'return_status', 'date_ordered');
		$result = false;
		if (in_array($column, $editable)) {
			if (in_array($column, array('email', 'telephone', 'product', 'model', 'comment', 'date_ordered')))
				$result = $this->db->query("UPDATE `" . DB_PREFIX . "return` SET " . $column . " = '" . $this->db->escape($value) . "', date_modified = NOW() WHERE return_id = '" . (int)$return_id . "'");
			else if (in_array($column, array('order_id', 'product_id', 'customer_id', 'quantity', 'opened'))) {
				if ($column == 'quantity' && strpos(trim($value), "#") === 0 && preg_match('/^#\s*(?P<operator>[+-\/\*])\s*(?P<operand>-?\d+\.?\d*)(?P<percent>%)?$/', trim($value), $matches) === 1) {
					list($operator, $operand) = $this->parseExpression($matches);
					$result = $this->db->query("UPDATE `" . DB_PREFIX . "return` SET `$column` = `$column` $operator '" . (float)$operand . "', date_modified = NOW() WHERE return_id = '" . (int)$return_id . "'");
					$query = $this->db->query("SELECT `$column` FROM `" . DB_PREFIX . "return` WHERE return_id = '" . (int)$return_id . "'");
					$result = $query->row[$column];
				} else {
					$result = $this->db->query("UPDATE `" . DB_PREFIX . "return` SET `" . $column . "` = '" . (int)$value . "', date_modified = NOW() WHERE return_id = '" . (int)$return_id . "'");
				}
			} else if (in_array($column, array('return_reason', 'return_action', 'return_status')))
				$result = $this->db->query("UPDATE `" . DB_PREFIX . "return` SET " . $column . "_id = '" . (int)$value . "', date_modified = NOW() WHERE return_id = '" . (int)$return_id . "'");
			else if ($column == "customer") {
				$first_name = $data['first_name'];
				$last_name = $data['last_name'];
				$result = $this->db->query("UPDATE `" . DB_PREFIX . "return` SET firstname = '" . $this->db->escape($first_name) . "', lastname = '" . $this->db->escape($last_name) . "', date_modified = NOW() WHERE return_id = '" . (int)$return_id . "'");
			}
		}

		return $result;
	}
}
