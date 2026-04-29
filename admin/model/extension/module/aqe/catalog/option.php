<?php
include_once(modification(DIR_APPLICATION . 'model/extension/module/aqe/general.php'));

class ModelExtensionModuleAqeCatalogOption extends ModelExtensionModuleAqeGeneral {
	protected static $count = 0;

	public function getOptions($data = array()) {
		if (isset($data['columns'])) {
			$columns = $data['columns'];
		} else {
			$columns = array('name', 'sort_order');
		}

		if (isset($data['actions'])) {
			$actions = $data['actions'];
		} else {
			$actions = array();
		}

		$sql = "SELECT SQL_CALC_FOUND_ROWS o.*, od.*";

		if (in_array("option_value", $columns)) {
			$sql .= ", GROUP_CONCAT(ov.option_value_id ORDER BY ov.sort_order SEPARATOR '_') AS option_value_ids, GROUP_CONCAT(ovd.name ORDER BY ov.sort_order SEPARATOR '<br/>') AS option_value_names";
		}

		if ((int)$this->config->get('module_admin_quick_edit_highlight_actions')) {
			if (in_array("option_values", $actions)) {
				$sql .= ", (SELECT 1 FROM " . DB_PREFIX . "option_value WHERE option_id = o.option_id LIMIT 1) AS option_values_exist";
			}
		}

		$sql .= " FROM `" . DB_PREFIX . "option` o LEFT JOIN " . DB_PREFIX . "option_description od ON (o.option_id = od.option_id AND od.language_id = '" . (int)$this->config->get('config_language_id') . "')";

		if (in_array("option_value", $columns)) {
			$sql .= " LEFT JOIN " . DB_PREFIX . "option_value ov ON (o.option_id = ov.option_id) LEFT JOIN " . DB_PREFIX . "option_value_description ovd ON (ov.option_value_id = ovd.option_value_id AND ovd.language_id = '" . (int)$this->config->get('config_language_id') . "')";
		}

		$where = array();

		$int_options = array(
			'id'    => 'o.option_id',
		);

		foreach ($int_options as $key => $value) {
			if (isset($data["filter_$key"]) && !is_null($data["filter_$key"])) {
				$where[] = "$value = '" . (int)$data["filter_$key"] . "'";
			}
		}

		$exact_filters = array(
			'type'      => 'o.type',
		);

		foreach ($exact_filters as $key => $value) {
			if (isset($data["filter_$key"]) && !is_null($data["filter_$key"])) {
				$where[] = "$value = '" . $this->db->escape($data["filter_$key"]) . "'";
			}
		}

		$int_interval_options = array(
			'sort_order'    => 'o.sort_order',
		);

		foreach ($int_interval_options as $key => $value) {
			if (isset($data["filter_$key"]) && !is_null($data["filter_$key"])) {
				if ($this->config->get('module_admin_quick_edit_interval_option')) {
					$where[] = $this->filterInterval($data["filter_$key"], $value);
				} else {
					$where[] = "$value = '" . (int)$data["filter_$key"] . "'";
				}
			}
		}

		$anywhere_options = array(
			'name'      => 'od.name',
		);

		foreach ($anywhere_options as $key => $value) {
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

		$sql .= " GROUP BY o.option_id";

		$having = array();

		$anywhere_options = array(
			'option_value' => 'option_value_names',
		);

		foreach ($anywhere_options as $key => $value) {
			if (!empty($data["filter_$key"])) {
				if ($this->config->get('module_admin_quick_edit_match_anywhere')) {
					$tokens = preg_split("/\s+/", trim($data["filter_$key"]));

					foreach ($tokens as $token) {
						$having[] = "$value LIKE '%" . $this->db->escape($token) . "%'";
					}
				} else {
					$having[] = "$value LIKE '" . $this->db->escape($data["filter_$key"]) . "%'";
				}
			}
		}

		if ($having) {
			$sql .= " HAVING " . implode(" AND ", $having);
		}

		$sort_data = array(
			'od.name',
			'o.option_id',
			'o.type',
			'o.sort_order',
		);

		if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
			$sql .= " ORDER BY " . $data['sort'];
		} else {
			$sql .= " ORDER BY od.name";
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

	public function getTotalOptions() {
		return $this->count;
	}

	public function getOptionValues($data = array()) {
		$sql = "SELECT * FROM " . DB_PREFIX . "option_value ov LEFT JOIN " . DB_PREFIX . "option_value_description ovd ON (ov.option_value_id = ovd.option_value_id) WHERE ovd.language_id = '" . (int)$this->config->get('config_language_id') . "'";

		if (!empty($data['filter_name'])) {
			$sql .= " AND ovd.name LIKE '" . $this->db->escape($data['filter_name']) . "%'";
		}

		$sort_data = array(
			'ovd.name',
			'ov.sort_order'
		);

		if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
			$sql .= " ORDER BY " . $data['sort'];
		} else {
			$sql .= " ORDER BY ovd.name";
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

		return $query->rows;
	}

	public function quickEditOption($option_id, $column, $value, $lang_id=null, $data=null) {
		$editable = array('sort_order', 'type');
		$result = false;
		if (in_array($column, $editable)) {
			if ($column == "type") {
				$result = $this->db->query("UPDATE `" . DB_PREFIX . "option` SET " . $column . " = '" . $this->db->escape($value) . "' WHERE option_id = '" . (int)$option_id . "'");

				if (!in_array($value, array('select', 'radio', 'checkbox', 'image'))) {
					$this->db->query("DELETE FROM " . DB_PREFIX . "option_value WHERE option_id = '" . (int)$option_id . "'");
					$this->db->query("DELETE FROM " . DB_PREFIX . "option_value_description WHERE option_id = '" . (int)$option_id . "'");
				}
			} else {
				if (strpos(trim($value), "#") === 0 && preg_match('/^#\s*(?P<operator>[+-\/\*])\s*(?P<operand>-?\d+\.?\d*)(?P<percent>%)?$/', trim($value), $matches) === 1) {
					list($operator, $operand) = $this->parseExpression($matches);
					$result = $this->db->query("UPDATE `" . DB_PREFIX . "option` SET `$column` = `$column` $operator '" . (float)$operand . "' WHERE option_id = '" . (int)$option_id . "'");
					$query = $this->db->query("SELECT `$column` FROM `" . DB_PREFIX . "option` WHERE option_id = '" . (int)$option_id . "'");
					$result = $query->row[$column];
				} else {
					$result = $this->db->query("UPDATE `" . DB_PREFIX . "option` SET `" . $column . "` = '" . (int)$value . "' WHERE option_id = '" . (int)$option_id . "'");
				}
			}
		} else if (in_array($column, array('name'))) {
			if (isset($data['value']) && is_array($data['value'])) {
				foreach ((array)$data['value'] as $language_id => $value) {
					$this->db->query("UPDATE " . DB_PREFIX . "option_description SET name = '" . $this->db->escape($value) . "' WHERE option_id = '" . (int)$option_id . "' AND language_id = '" . (int)$language_id . "'");
				}
				$result = 1;
			} else if ($value) {
				$result = $this->db->query("UPDATE " . DB_PREFIX . "option_description SET name = '" . $this->db->escape($value) . "' WHERE option_id = '" . (int)$option_id . "' AND language_id = '" . (int)$lang_id . "'");
				$result = 1;
			} else {
				$result = 0;
			}
		} else if (in_array($column, array('option_value', 'option_values'))) {
			$this->db->query("DELETE FROM " . DB_PREFIX . "option_value WHERE option_id = '" . (int)$option_id . "'");
			$this->db->query("DELETE FROM " . DB_PREFIX . "option_value_description WHERE option_id = '" . (int)$option_id . "'");

			if (isset($data['option_value'])) {
				foreach ($data['option_value'] as $option_value) {
					if ($option_value['option_value_id']) {
						$this->db->query("INSERT INTO " . DB_PREFIX . "option_value SET option_value_id = '" . (int)$option_value['option_value_id'] . "', option_id = '" . (int)$option_id . "', image = '" . $this->db->escape(html_entity_decode($option_value['image'], ENT_QUOTES, 'UTF-8')) . "', sort_order = '" . (int)$option_value['sort_order'] . "'");
					} else {
						$this->db->query("INSERT INTO " . DB_PREFIX . "option_value SET option_id = '" . (int)$option_id . "', image = '" . $this->db->escape(html_entity_decode($option_value['image'], ENT_QUOTES, 'UTF-8')) . "', sort_order = '" . (int)$option_value['sort_order'] . "'");
					}

					$option_value_id = $this->db->getLastId();

					foreach ($option_value['option_value_description'] as $language_id => $option_value_description) {
						$this->db->query("INSERT INTO " . DB_PREFIX . "option_value_description SET option_value_id = '" . (int)$option_value_id . "', language_id = '" . (int)$language_id . "', option_id = '" . (int)$option_id . "', name = '" . $this->db->escape($option_value_description['name']) . "'");
					}
				}
			}
			$result = 1;
		}

		return $result;
	}
}
