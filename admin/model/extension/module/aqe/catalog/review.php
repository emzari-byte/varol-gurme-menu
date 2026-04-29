<?php
include_once(modification(DIR_APPLICATION . 'model/extension/module/aqe/general.php'));

class ModelExtensionModuleAqeCatalogReview extends ModelExtensionModuleAqeGeneral {
	protected static $count = 0;

	public function getReviews($data = array()) {
		if (isset($data['columns'])) {
			$columns = $data['columns'];
		} else {
			$columns = array('product', 'author', 'rating', 'status', 'date_added');
		}

		$sql = "SELECT SQL_CALC_FOUND_ROWS r.*, pd.name AS product FROM " . DB_PREFIX . "review r LEFT JOIN " . DB_PREFIX . "product_description pd ON (r.product_id = pd.product_id AND pd.language_id = '" . (int)$this->config->get('config_language_id') . "')";

		$where = array();

		$int_filters = array(
			'id'                => 'r.review_id',
			'product_id'        => 'r.product_id',
			'status'            => 'r.status',
		);

		foreach ($int_filters as $key => $value) {
			if (isset($data["filter_$key"]) && !is_null($data["filter_$key"])) {
				$where[] = "$value = '" . (int)$data["filter_$key"] . "'";
			}
		}

		$int_interval_filters = array(
			'rating'        => 'r.rating',
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
			'author'    => "r.author",
			'product'   => 'pd.name',
			'text'   	=> 'r.text',
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

		$sql .= " GROUP BY r.review_id";

		$sort_data = array(
			'r.review_id',
			'pd.name',
			'r.author',
			'r.rating',
			'r.status',
			'r.date_added',
			'r.date_modified',
		);

		if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
			$sql .= " ORDER BY " . $data['sort'];
		} else {
			$sql .= " ORDER BY r.date_added";
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

	public function getTotalReviews() {
		return $this->count;
	}

	public function quickEditReview($review_id, $column, $value, $lang_id=null, $data=null) {
		$editable = array('product', 'author', 'text', 'rating', 'status', 'date_added');
		$result = false;
		if (in_array($column, $editable)) {
			if (in_array($column, array('status')))
				$result = $this->db->query("UPDATE " . DB_PREFIX . "review SET `" . $column . "` = '" . (int)$value . "', date_modified = NOW() WHERE review_id = '" . (int)$review_id . "'");
			else if ($column == "product")
				$result = $this->db->query("UPDATE " . DB_PREFIX . "review SET product_id = '" . (int)$value . "', date_modified = NOW()  WHERE review_id = '" . (int)$review_id . "'");
			else if ($column == "author")
				$result = $this->db->query("UPDATE " . DB_PREFIX . "review SET author = '" . $this->db->escape($value) . "', date_modified = NOW()  WHERE review_id = '" . (int)$review_id . "'");
			else if ($column == "text")
				$result = $this->db->query("UPDATE " . DB_PREFIX . "review SET text = '" . $this->db->escape(strip_tags($value)) . "', date_modified = NOW()  WHERE review_id = '" . (int)$review_id . "'");
			else if ($column == "rating") {
				$result = $this->db->query("UPDATE " . DB_PREFIX . "review SET rating = '" . (int)$value . "', date_modified = NOW()  WHERE review_id = '" . (int)$review_id . "'");
			} else if ($column == "date_added") {
				$result = $this->db->query("UPDATE " . DB_PREFIX . "review SET date_added = '" . $this->db->escape($value) . "', date_modified = NOW()  WHERE review_id = '" . (int)$review_id . "'");
			}
		}

		$this->cache->delete('review');

		return $result;
	}
}
