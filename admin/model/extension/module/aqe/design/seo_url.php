<?php
include_once(modification(DIR_APPLICATION . 'model/extension/module/aqe/general.php'));

class ModelExtensionModuleAqeDesignSeoUrl extends ModelExtensionModuleAqeGeneral {
	protected static $count = 0;

	public function getSeoUrls($data = array()) {
		if (isset($data['columns'])) {
			$columns = $data['columns'];
		} else {
			$columns = array('query', 'keyword', 'store', 'language');
		}

		$sql = "SELECT SQL_CALC_FOUND_ROWS su.*";

		if (in_array("store", $columns)) {
			$sql .= ", IFNULL(s.name, '" . $this->config->get('config_name') . "') AS store";
		}

		if (in_array("language", $columns)) {
			$sql .= ", l.name as language";
		}

		$sql .= " FROM `" . DB_PREFIX . "seo_url` su";

		if (in_array("store", $columns)) {
			$sql .= " LEFT JOIN " . DB_PREFIX . "store s ON (su.store_id = s.store_id)";
		}

		if (in_array("language", $columns)) {
			$sql .= " LEFT JOIN " . DB_PREFIX . "language l ON (su.language_id = l.language_id)";
		}

		$where = array();

		$int_filters = array(
			'id'                => 'su.seo_url_id',
			'store'             => 'su.store_id',
			'language'          => 'su.language_id',
		);

		foreach ($int_filters as $key => $value) {
			if (isset($data["filter_$key"]) && !is_null($data["filter_$key"])) {
				$where[] = "$value = '" . (int)$data["filter_$key"] . "'";
			}
		}

		$anywhere_filters = array(
			'query'     => 'su.query',
			'keyword'   => 'su.keyword',
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

		$sql .= " GROUP BY su.seo_url_id";

		$sort_data = array(
			'seo_url_id',
			'query',
			'keyword',
			'store',
			'language',
		);

		if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
			$sql .= " ORDER BY " . $data['sort'];
		} else {
			$sql .= " ORDER BY keyword";
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

	public function getTotalSeoUrls() {
		return $this->count;
	}

	public function quickEditSeoUrl($seo_url_id, $column, $value, $lang_id=null, $data=null) {
		$editable = array('query', 'keyword', 'store', 'language');
		$result = false;
		if (in_array($column, $editable)) {
			if (in_array($column, array('query', 'keyword')))
				$result = $this->db->query("UPDATE `" . DB_PREFIX . "seo_url` SET " . $column . " = '" . $this->db->escape($value) . "' WHERE seo_url_id = '" . (int)$seo_url_id . "'");
			else if (in_array($column, array('store', 'language')))
				$result = $this->db->query("UPDATE `" . DB_PREFIX . "seo_url` SET " . $column . "_id = '" . (int)$value . "' WHERE seo_url_id = '" . (int)$seo_url_id . "'");
		}

		return $result;
	}

	public function getSeoUrlsByKeyword($keyword) {
		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "seo_url` WHERE keyword = '" . $this->db->escape($keyword) . "'");

		return $query->rows;
	}

	public function getSeoUrlsByQuery($query) {
		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "seo_url` WHERE query = '" . $this->db->escape($query) . "'");

		return $query->rows;
	}
}
