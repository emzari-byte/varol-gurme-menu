<?php
include_once(modification(DIR_APPLICATION . 'model/extension/module/aqe/general.php'));

class ModelExtensionModuleAqeSaleVoucherTheme extends ModelExtensionModuleAqeGeneral {
	protected static $count = 0;

	public function getVoucherThemes($data = array()) {
		if (isset($data['columns'])) {
			$columns = $data['columns'];
		} else {
			$columns = array('name');
		}

		$sql = "SELECT SQL_CALC_FOUND_ROWS vt.*";

		if (in_array("name", $columns)) {
			$sql .= ", vtd.name AS name";
		}

		$sql .= " FROM `" . DB_PREFIX . "voucher_theme` vt";

		if (in_array("name", $columns)) {
			$sql .= " LEFT JOIN " . DB_PREFIX . "voucher_theme_description vtd ON (vt.voucher_theme_id = vtd.voucher_theme_id AND vtd.language_id = '" . (int)$this->config->get('config_language_id') . "')";
		}

		$where = array();

		$int_filters = array(
			'id'                => 'vt.voucher_theme_id',
		);

		foreach ($int_filters as $key => $value) {
			if (isset($data["filter_$key"]) && !is_null($data["filter_$key"])) {
				$where[] = "$value = '" . (int)$data["filter_$key"] . "'";
			}
		}

		$anywhere_filters = array(
			'name'      => 'vtd.name',
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

		$sql .= " GROUP BY vt.voucher_theme_id";

		$sort_data = array(
			'vt.voucher_theme_id',
			'vtd.name',
		);

		if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
			$sql .= " ORDER BY " . $data['sort'];
		} else {
			$sql .= " ORDER BY vtd.name";
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

	public function getTotalVoucherThemes() {
		return $this->count;
	}

	public function quickEditVoucherTheme($voucher_theme_id, $column, $value, $lang_id=null, $data=null) {
		$editable = array('image');
		$result = false;
		if (in_array($column, $editable)) {
			if ($column == 'image') {
				$result = $this->db->query("UPDATE `" . DB_PREFIX . "voucher_theme` SET " . $column . " = '" . $this->db->escape($value) . "' WHERE voucher_theme_id = '" . (int)$voucher_theme_id . "'");
			}
		} else if ($column == 'name') {
			if (isset($data['value']) && is_array($data['value'])) {
				foreach ((array)$data['value'] as $language_id => $value) {
					$this->db->query("UPDATE " . DB_PREFIX . "voucher_theme_description SET " . $column . " = '" . $this->db->escape($value) . "' WHERE voucher_theme_id = '" . (int)$voucher_theme_id . "' AND language_id = '" . (int)$language_id . "'");
				}
				$result = 1;
			} else if ($value) {
				$result = $this->db->query("UPDATE " . DB_PREFIX . "voucher_theme_description SET " . $column . " = '" . $this->db->escape($value) . "' WHERE voucher_theme_id = '" . (int)$voucher_theme_id . "' AND language_id = '" . (int)$lang_id . "'");
				$result = 1;
			} else {
				$result = 0;
			}
		}

		return $result;
	}
}
