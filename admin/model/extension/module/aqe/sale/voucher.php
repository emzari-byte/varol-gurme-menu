<?php
include_once(modification(DIR_APPLICATION . 'model/extension/module/aqe/general.php'));

class ModelExtensionModuleAqeSaleVoucher extends ModelExtensionModuleAqeGeneral {
	protected static $count = 0;

	public function getVouchers($data = array()) {
		if (isset($data['columns'])) {
			$columns = $data['columns'];
		} else {
			$columns = array('code', 'from_name', 'to_name', 'amount', 'theme', 'status', 'date_added');
		}

		$sql = "SELECT SQL_CALC_FOUND_ROWS v.*";

		if (in_array("theme", $columns)) {
			$sql .= ", vtd.name AS theme";
		}

		$sql .= " FROM `" . DB_PREFIX . "voucher` v";

		if (in_array("theme", $columns)) {
			$sql .= " LEFT JOIN " . DB_PREFIX . "voucher_theme_description vtd ON (v.voucher_theme_id = vtd.voucher_theme_id AND vtd.language_id = '" . (int)$this->config->get('config_language_id') . "')";
		}

		$where = array();

		$int_filters = array(
			'id'                => 'v.voucher_id',
			'theme'             => 'v.voucher_theme_id',
			'status'            => 'v.status',
		);

		foreach ($int_filters as $key => $value) {
			if (isset($data["filter_$key"]) && !is_null($data["filter_$key"])) {
				$where[] = "$value = '" . (int)$data["filter_$key"] . "'";
			}
		}

		$date_filters = array(
			'date_added'        => 'v.date_added',
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
			'code'      => 'v.code',
			'from_name' => 'v.from_name',
			'from_email'=> 'v.from_email',
			'to_name'   => 'v.to_name',
			'to_email'  => 'v.to_email',
			'message'   => 'v.message',
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

		$float_interval_filters = array(
			'amount'    => 'v.amount',
		);

		foreach ($float_interval_filters as $key => $value) {
			if (isset($data["filter_$key"]) && !is_null($data["filter_$key"])) {
				if ($this->config->get('module_admin_quick_edit_interval_filter')) {
					$where[] = $this->filterInterval($data["filter_$key"], $value);
				} else {
					$where[] = "$value = '" . $this->db->escape($data["filter_$key"]) . "%'";
				}
			}
		}

		if ($where) {
			$sql .= " WHERE " . implode(" AND ", $where);
		}

		$sql .= " GROUP BY v.voucher_id";

		$sort_data = array(
			'v.voucher_id',
			'v.code',
			'v.from_name',
			'v.from_email',
			'v.to_name',
			'v.to_email',
			'v.amount',
			'theme',
			'v.status',
			'v.date_added',
		);

		if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
			$sql .= " ORDER BY " . $data['sort'];
		} else {
			$sql .= " ORDER BY v.date_added";
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

	public function getTotalVouchers() {
		return $this->count;
	}

	public function quickEditVoucher($voucher_id, $column, $value, $lang_id=null, $data=null) {
		$editable = array('code', 'from_name', 'to_name', 'from_email', 'to_email', 'theme', 'message', 'amount', 'status');
		$result = false;
		if (in_array($column, $editable)) {
			if (in_array($column, array('amount'))) {
				if (strpos(trim($value), "#") === 0 && preg_match('/^#\s*(?P<operator>[+-\/\*])\s*(?P<operand>-?\d+\.?\d*)(?P<percent>%)?$/', trim($value), $matches) === 1) {
					list($operator, $operand) = $this->parseExpression($matches);
					$result = $this->db->query("UPDATE `" . DB_PREFIX . "voucher` SET `$column` = `$column` $operator '" . (float)$operand . "' WHERE voucher_id = '" . (int)$voucher_id . "'");
					$query = $this->db->query("SELECT `$column` FROM `" . DB_PREFIX . "voucher` WHERE voucher_id = '" . (int)$voucher_id . "'");
					$result = $query->row[$column];
				} else {
					$result = $this->db->query("UPDATE `" . DB_PREFIX . "voucher` SET `" . $column . "` = '" . (float)$value . "' WHERE voucher_id = '" . (int)$voucher_id . "'");
				}
			} else if (in_array($column, array('status')))
				$result = $this->db->query("UPDATE `" . DB_PREFIX . "voucher` SET " . $column . " = '" . (int)$value . "' WHERE voucher_id = '" . (int)$voucher_id . "'");
			else if (in_array($column, array('theme')))
				$result = $this->db->query("UPDATE `" . DB_PREFIX . "voucher` SET voucher_theme_id = '" . (int)$value . "' WHERE voucher_id = '" . (int)$voucher_id . "'");
			else if (in_array($column, array('code', 'from_name', 'to_name', 'from_email', 'to_email', 'message'))) {
				$result = $this->db->query("UPDATE `" . DB_PREFIX . "voucher` SET " . $column . " = '" . $this->db->escape($value) . "' WHERE voucher_id = '" . (int)$voucher_id . "'");
			}
		}

		return $result;
	}
}
