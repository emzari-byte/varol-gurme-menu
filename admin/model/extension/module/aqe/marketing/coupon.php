<?php
include_once(modification(DIR_APPLICATION . 'model/extension/module/aqe/general.php'));

class ModelExtensionModuleAqeMarketingCoupon extends ModelExtensionModuleAqeGeneral {
	protected static $count = 0;

	public function getCoupons($data = array()) {
		if (isset($data['columns'])) {
			$columns = $data['columns'];
		} else {
			$columns = array('name', 'code', 'discount', 'date_start', 'date_end', 'status');
		}

		if (isset($data['actions'])) {
			$actions = $data['actions'];
		} else {
			$actions = array();
		}

		$sql = "SELECT SQL_CALC_FOUND_ROWS c.*";

		if (in_array("products", $columns)) {
			$sql .= ", GROUP_CONCAT(DISTINCT pd.name ORDER BY pd.name ASC SEPARATOR '<br/>') AS products";
		}

		if (in_array("categories", $columns)) {
			$sql .= ", GROUP_CONCAT(DISTINCT cd.name ORDER BY cd.name ASC SEPARATOR '<br/>') AS categories";
		}

		if ((int)$this->config->get('module_admin_quick_edit_highlight_actions')) {
			if (in_array("product", $actions)) {
				$sql .= ", (SELECT 1 FROM " . DB_PREFIX . "coupon_product WHERE coupon_id = c.coupon_id LIMIT 1) AS product_exist";
			}

			if (in_array("category", $actions)) {
				$sql .= ", (SELECT 1 FROM " . DB_PREFIX . "coupon_category WHERE coupon_id = c.coupon_id LIMIT 1) AS category_exist";
			}
		}

		$sql .= " FROM `" . DB_PREFIX . "coupon` c";

		if (in_array("products", $columns) || isset($data['filter_products'])) {
			$sql .= " LEFT JOIN " . DB_PREFIX . "coupon_product cp ON (c.coupon_id = cp.coupon_id) LEFT JOIN " . DB_PREFIX . "product_description pd ON (cp.product_id = pd.product_id AND pd.language_id = '" . (int)$this->config->get('config_language_id') . "')";
		}

		if (in_array("categories", $columns) || isset($data['filter_products'])) {
			$sql .= " LEFT JOIN " . DB_PREFIX . "coupon_category cc ON (c.coupon_id = cc.coupon_id) LEFT JOIN " . DB_PREFIX . "category_description cd ON (cc.category_id = cd.category_id AND cd.language_id = '" . (int)$this->config->get('config_language_id') . "')";
		}

		if (in_array("country", $columns) || isset($data['filter_country'])) {
			$sql .= " LEFT JOIN " . DB_PREFIX . "country c ON (c.country_id = c.country_id)";
		}

		if (in_array("region", $columns) || isset($data['filter_region'])) {
			$sql .= " LEFT JOIN " . DB_PREFIX . "zone z ON (c.zone_id = z.zone_id)";
		}

		$where = array();

		$int_filters = array(
			'id'                => 'c.coupon_id',
			'logged'            => 'c.logged',
			'shipping'          => 'c.shipping',
			'status'            => 'c.status',
		);

		foreach ($int_filters as $key => $value) {
			if (isset($data["filter_$key"]) && !is_null($data["filter_$key"])) {
				$where[] = "$value = '" . (int)$data["filter_$key"] . "'";
			}
		}

		$int_interval_filters = array(
			'uses_total'    => 'c.uses_total',
			'uses_customer' => 'c.uses_customer',
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

		$float_interval_filters = array(
			'total'     => 'c.total',
			'discount'  => 'c.discount',
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

		$date_filters = array(
			'date_start'        => 'c.date_start',
			'date_end'          => 'c.date_end',
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

		$exact_filters = array(
			'type'      => 'c.type',
		);

		foreach ($exact_filters as $key => $value) {
			if (!empty($data["filter_$key"])) {
				$where[] = "$value LIKE '" . $this->db->escape($data["filter_$key"]) . "%'";
			}
		}

		$anywhere_filters = array(
			'name'      => 'c.name',
			'code'      => 'c.code',
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

		$sql .= " GROUP BY c.coupon_id";

		$having = array();

		$anywhere_filters = array(
			'products'  => 'products',
			'categories'=> 'categories',
		);

		foreach ($anywhere_filters as $key => $value) {
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
			'c.coupon_id',
			'c.name',
			'c.code',
			'c.type',
			'c.total',
			'c.logged',
			'c.shipping',
			'c.discount',
			'c.date_start',
			'c.date_end',
			'c.uses_total',
			'c.uses_customer',
			'c.status',
		);

		if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
			$sql .= " ORDER BY " . $data['sort'];
		} else {
			$sql .= " ORDER BY name";
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

	public function getTotalCoupons() {
		return $this->count;
	}

	public function getCouponProducts($coupon_id) {
		$coupon_product_data = array();

		$query = $this->db->query("SELECT pd.* FROM " . DB_PREFIX . "coupon_product cp LEFT JOIN " . DB_PREFIX . "product_description pd ON (cp.product_id = pd.product_id AND pd.language_id = '" . (int)$this->config->get('config_language_id') . "') WHERE cp.coupon_id = '" . (int)$coupon_id . "'");

		foreach ($query->rows as $result) {
			$coupon_product_data[$result['product_id']] = $result['name'];
		}

		return $coupon_product_data;
	}

	public function quickEditCoupon($coupon_id, $column, $value, $lang_id=null, $data=null) {
		$editable = array('name', 'code', 'type', 'total', 'logged', 'shipping', 'discount', 'date_start', 'date_end', 'uses_total', 'uses_customer', 'status');
		$result = false;
		if (in_array($column, $editable)) {
			if (in_array($column, array('logged', 'shipping', 'uses_total', 'uses_customer', 'status'))) {
				if (in_array($column, array('uses_total', 'uses_customer')) && strpos(trim($value), "#") === 0 && preg_match('/^#\s*(?P<operator>[+-\/\*])\s*(?P<operand>-?\d+\.?\d*)(?P<percent>%)?$/', trim($value), $matches) === 1) {
					list($operator, $operand) = $this->parseExpression($matches);
					$result = $this->db->query("UPDATE `" . DB_PREFIX . "coupon` SET `$column` = `$column` $operator '" . (float)$operand . "' WHERE coupon_id = '" . (int)$coupon_id . "'");
					$query = $this->db->query("SELECT `$column` FROM `" . DB_PREFIX . "coupon` WHERE coupon_id = '" . (int)$coupon_id . "'");
					$result = $query->row[$column];
				} else {
					$result = $this->db->query("UPDATE `" . DB_PREFIX . "coupon` SET `" . $column . "` = '" . (int)$value . "' WHERE coupon_id = '" . (int)$coupon_id . "'");
				}
			} else if (in_array($column, array('discount', 'total'))) {
				if (strpos(trim($value), "#") === 0 && preg_match('/^#\s*(?P<operator>[+-\/\*])\s*(?P<operand>-?\d+\.?\d*)(?P<percent>%)?$/', trim($value), $matches) === 1) {
					list($operator, $operand) = $this->parseExpression($matches);
					$result = $this->db->query("UPDATE `" . DB_PREFIX . "coupon` SET `$column` = `$column` $operator '" . (float)$operand . "' WHERE coupon_id = '" . (int)$coupon_id . "'");
					$query = $this->db->query("SELECT `$column` FROM `" . DB_PREFIX . "coupon` WHERE coupon_id = '" . (int)$coupon_id . "'");
					$result = $query->row[$column];
				} else {
					$result = $this->db->query("UPDATE `" . DB_PREFIX . "coupon` SET `" . $column . "` = '" . (float)$value . "' WHERE coupon_id = '" . (int)$coupon_id . "'");
				}
			} else if (in_array($column, array('name', 'code', 'type', 'date_start', 'date_end'))) {
				$result = $this->db->query("UPDATE `" . DB_PREFIX . "coupon` SET " . $column . " = '" . $this->db->escape($value) . "' WHERE coupon_id = '" . (int)$coupon_id . "'");
			}
		} else if (in_array($column, array('product', 'products'))) {
			$this->db->query("DELETE FROM " . DB_PREFIX . "coupon_product WHERE coupon_id = '" . (int)$coupon_id . "'");

			if (isset($data['item'])) {
				foreach ((array)$data['item'] as $product_id) {
					$this->db->query("INSERT INTO " . DB_PREFIX . "coupon_product SET coupon_id = '" . (int)$coupon_id . "', product_id = '" . (int)$product_id . "'");
				}
			}
			$result = 1;
		} else if (in_array($column, array('category', 'categories'))) {
			$this->db->query("DELETE FROM " . DB_PREFIX . "coupon_category WHERE coupon_id = '" . (int)$coupon_id . "'");

			if (isset($data['item'])) {
				foreach ((array)$data['item'] as $category_id) {
					$this->db->query("INSERT INTO " . DB_PREFIX . "coupon_category SET coupon_id = '" . (int)$coupon_id . "', category_id = '" . (int)$category_id . "'");
				}
			}
			$result = 1;
		}

		return $result;
	}
}
