<?php
include_once(modification(DIR_APPLICATION . 'model/extension/module/aqe/general.php'));

class ModelExtensionModuleAqeCatalogAttributeGroup extends ModelExtensionModuleAqeGeneral {
	protected static $count = 0;

	public function getAttributeGroups($data = array()) {
		if (isset($data['columns'])) {
			$columns = $data['columns'];
		} else {
			$columns = array('name', 'sort_order');
		}

		$sql = "SELECT SQL_CALC_FOUND_ROWS ag.*, agd.* FROM " . DB_PREFIX . "attribute_group ag LEFT JOIN " . DB_PREFIX . "attribute_group_description agd ON (ag.attribute_group_id = agd.attribute_group_id AND agd.language_id = '" . (int)$this->config->get('config_language_id') . "')";

		$where = array();

		$int_filters = array(
			'id'    => 'ag.attribute_group_id',
		);

		foreach ($int_filters as $key => $value) {
			if (isset($data["filter_$key"]) && !is_null($data["filter_$key"])) {
				$where[] = "$value = '" . (int)$data["filter_$key"] . "'";
			}
		}

		$int_interval_filters = array(
			'sort_order'    => 'ag.sort_order',
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

		$anywhere_filters = array(
			'name'      => 'agd.name',
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
			$sql .= " WHERE " . implode($where, " AND ");
		}

		$sql .= " GROUP BY ag.attribute_group_id";

		$sort_data = array(
			'ag.attribute_group_id',
			'agd.name',
			'ag.sort_order',
		);

		if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
			$sql .= " ORDER BY " . $data['sort'];
		} else {
			$sql .= " ORDER BY agd.name";
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

	public function getTotalAttributeGroups() {
		return $this->count;
	}

	public function quickEditAttributeGroup($attribute_group_id, $column, $value, $lang_id=null, $data=null) {
		$editable = array('sort_order');
		$result = false;
		if (in_array($column, $editable)) {
			if (strpos(trim($value), "#") === 0 && preg_match('/^#\s*(?P<operator>[+-\/\*])\s*(?P<operand>-?\d+\.?\d*)(?P<percent>%)?$/', trim($value), $matches) === 1) {
				list($operator, $operand) = $this->parseExpression($matches);
				$result = $this->db->query("UPDATE `" . DB_PREFIX . "attribute_group` SET `$column` = `$column` $operator '" . (float)$operand . "' WHERE attribute_group_id = '" . (int)$attribute_group_id . "'");
				$query = $this->db->query("SELECT `$column` FROM `" . DB_PREFIX . "attribute_group` WHERE attribute_group_id = '" . (int)$attribute_group_id . "'");
				$result = $query->row[$column];
			} else {
				$result = $this->db->query("UPDATE `" . DB_PREFIX . "attribute_group` SET `" . $column . "` = '" . (int)$value . "' WHERE attribute_group_id = '" . (int)$attribute_group_id . "'");
			}
		} else if (in_array($column, array('name'))) {
			if (isset($data['value']) && is_array($data['value'])) {
				foreach ((array)$data['value'] as $language_id => $value) {
					$this->db->query("UPDATE " . DB_PREFIX . "attribute_group_description SET " . $column . " = '" . $this->db->escape($value) . "' WHERE attribute_group_id = '" . (int)$attribute_group_id . "' AND language_id = '" . (int)$language_id . "'");
				}
				$result = 1;
			} else if ($value) {
				$result = $this->db->query("UPDATE " . DB_PREFIX . "attribute_group_description SET " . $column . " = '" . $this->db->escape($value) . "' WHERE attribute_group_id = '" . (int)$attribute_group_id . "' AND language_id = '" . (int)$lang_id . "'");
				$result = 1;
			} else {
				$result = 0;
			}
		}

		return $result;
	}
}
