<?php
include_once(modification(DIR_APPLICATION . 'model/extension/module/aqe/general.php'));

class ModelExtensionModuleAqeCatalogProduct extends ModelExtensionModuleAqeGeneral {
	protected static $productCount = 0;

	public function getProducts($data = array()) {
		if (isset($data['columns'])) {
			$columns = $data['columns'];
		} else {
			$columns = array('image', 'name', 'model', 'price', 'quantity', 'status');
		}

		if (isset($data['actions'])) {
			$actions = $data['actions'];
		} else {
			$actions = array();
		}

		$sql = "SELECT SQL_CALC_FOUND_ROWS p.*, pd.*";

		if (in_array("seo", $columns)) {
			$sql .= ", (SELECT keyword FROM " . DB_PREFIX . "seo_url WHERE query = CONCAT('product_id=', p.product_id) AND store_id = '0' AND (language_id IS NULL OR language_id = '" . (int)$this->config->get('config_language_id') . "') ORDER BY language_id DESC LIMIT 1) AS seo";
		}

		if (in_array("manufacturer", $columns)) {
			$sql .= ", m.name AS manufacturer";
		}

		if (in_array("tax_class", $columns)) {
			$sql .= ", tc.title AS tax_class";
		}

		if (in_array("stock_status", $columns)) {
			$sql .= ", ss.name AS stock_status";
		}

		if (in_array("length_class", $columns)) {
			$sql .= ", lcd.title AS length_class";
		}

		if (in_array("weight_class", $columns)) {
			$sql .= ", wcd.title AS weight_class";
		}

		// Actions
		if ((int)$this->config->get('module_admin_quick_edit_highlight_actions')) {
			if (in_array("attributes", $actions)) {
				$sql .= ", (SELECT 1 FROM " . DB_PREFIX . "product_attribute WHERE product_id = p.product_id LIMIT 1) AS attributes_exist";
			}

			if (in_array("discounts", $actions)) {
				$sql .= ", (SELECT 1 FROM " . DB_PREFIX . "product_discount WHERE product_id = p.product_id LIMIT 1) AS discounts_exist";
			}

			if (in_array("images", $actions)) {
				$sql .= ", (SELECT 1 FROM " . DB_PREFIX . "product_image WHERE product_id = p.product_id LIMIT 1) AS images_exist";
			}

			if (in_array("filters", $actions)) {
				$sql .= ", (SELECT 1 FROM " . DB_PREFIX . "product_filter WHERE product_id = p.product_id LIMIT 1) AS filters_exist";
			}

			if (in_array("options", $actions)) {
				$sql .= ", (SELECT 1 FROM " . DB_PREFIX . "product_option WHERE product_id = p.product_id LIMIT 1) AS options_exist";
			}

			if (in_array("recurrings", $actions)) {
				$sql .= ", (SELECT 1 FROM " . DB_PREFIX . "product_recurring WHERE product_id = p.product_id LIMIT 1) AS recurrings_exist";
			}

			if (in_array("related", $actions)) {
				$sql .= ", (SELECT 1 FROM " . DB_PREFIX . "product_related WHERE product_id = p.product_id LIMIT 1) AS related_exist";
			}

			if (in_array("downloads", $actions)) {
				$sql .= ", (SELECT 1 FROM " . DB_PREFIX . "product_to_download WHERE product_id = p.product_id LIMIT 1) AS downloads_exist";
			}

			if (in_array("specials", $actions)) {
				$sql .= ", (SELECT 1 FROM " . DB_PREFIX . "product_special WHERE product_id = p.product_id LIMIT 1) AS specials_exist";
			}

			if (in_array("descriptions", $actions)) {
				$sql .= ", (SELECT 1 FROM " . DB_PREFIX . "product_description WHERE product_id = p.product_id LIMIT 1) AS descriptions_exist";
			}

			if (in_array("keywords", $actions)) {
				$sql .= ", (SELECT 1 FROM " . DB_PREFIX . "seo_url WHERE query = CONCAT('product_id=', p.product_id) LIMIT 1) AS keywords_exist";
			}
		}

		$sql .= " FROM " . DB_PREFIX . "product p LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id AND pd.language_id = '" . (int)$this->config->get('config_language_id') . "')";

		if (!empty($data['filter_special_price']) && in_array($data['filter_special_price'], array("active", "expired", "future", "na"))) {
			$sql .= " LEFT JOIN " . DB_PREFIX . "product_special ps ON (ps.product_id = p.product_id)";
		}

		if (in_array("manufacturer", $columns)) {
			$sql .= " LEFT JOIN " . DB_PREFIX . "manufacturer m ON (m.manufacturer_id = p.manufacturer_id)";
		}

		if (isset($data['filter_filter'])) {
			$sql .= " LEFT JOIN " . DB_PREFIX . "product_filter p2f ON (p.product_id = p2f.product_id) LEFT JOIN " . DB_PREFIX . "filter_description fd ON (fd.filter_id = p2f.filter_id AND fd.language_id = '" . (int)$this->config->get('config_language_id') . "')";
		}

		if (isset($data['filter_download'])) {
			$sql .= " LEFT JOIN " . DB_PREFIX . "product_to_download p2d ON (p.product_id = p2d.product_id) LEFT JOIN " . DB_PREFIX . "download_description dd ON (dd.download_id = p2d.download_id AND dd.language_id = '" . (int)$this->config->get('config_language_id') . "')";
		}

		if (in_array("tax_class", $columns)) {
			$sql .= " LEFT JOIN " . DB_PREFIX . "tax_class tc ON (tc.tax_class_id = p.tax_class_id)";
		}

		if (in_array("stock_status", $columns)) {
			$sql .= " LEFT JOIN " . DB_PREFIX . "stock_status ss ON (ss.stock_status_id = p.stock_status_id)";
		}

		if (in_array("length_class", $columns)) {
			$sql .= " LEFT JOIN " . DB_PREFIX . "length_class lc ON (lc.length_class_id = p.length_class_id) LEFT JOIN " . DB_PREFIX . "length_class_description lcd ON (lcd.length_class_id = lc.length_class_id AND lcd.language_id = '" . (int)$this->config->get('config_language_id') . "')";
		}

		if (in_array("weight_class", $columns)) {
			$sql .= " LEFT JOIN " . DB_PREFIX . "weight_class wc ON (wc.weight_class_id = p.weight_class_id) LEFT JOIN " . DB_PREFIX . "weight_class_description wcd ON (wcd.weight_class_id = wc.weight_class_id AND wcd.language_id = '" . (int)$this->config->get('config_language_id') . "')";
		}

		if (!empty($data['filter_category'])) {
			$sql .= " LEFT JOIN " . DB_PREFIX . "product_to_category p2c ON (p.product_id = p2c.product_id)";
		}

		if (isset($data['filter_store'])) {
			$sql .= " LEFT JOIN " . DB_PREFIX . "product_to_store p2s ON (p.product_id = p2s.product_id)";
		}

		$where = array();

		$int_filters = array(
			'tax_class'         => 'p.tax_class_id',
			'length_class'      => 'p.length_class_id',
			'weight_class'      => 'p.weight_class_id',
			'manufacturer'      => 'p.manufacturer_id',
			'stock_status'      => 'p.stock_status_id',
			'subtract'          => 'p.subtract',
			'id'                => 'p.product_id',
			'status'            => 'p.status',
			'requires_shipping' => 'p.shipping',
		);

		foreach ($int_filters as $key => $value) {
			if (isset($data["filter_$key"]) && !is_null($data["filter_$key"])) {
				$where[] = "$value = '" . (int)$data["filter_$key"] . "'";
			}
		}

		$date_filters = array(
			'date_added'        => 'p.date_added',
			'date_available'    => 'p.date_available',
			'date_modified'     => 'p.date_modified',
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

		$float_interval_filters = array(
			'length'    => 'p.length',
			'width'     => 'p.width',
			'height'    => 'p.height',
			'weight'    => 'p.weight',
			'price'     => 'p.price',
		);

		foreach ($float_interval_filters as $key => $value) {
			if ($key == "price" && !empty($data['filter_special_price']) && in_array($data['filter_special_price'], array("active", "expired", "future", "na"))) {
				if ($data['filter_special_price'] == "active") {
					$where[] = "((ps.date_start = '0000-00-00' OR ps.date_start < NOW()) AND (ps.date_end = '0000-00-00' OR ps.date_end > NOW()))";
				} elseif ($data['filter_special_price'] == "expired") {
					$where[] = "(ps.date_end != '0000-00-00' AND ps.date_end < NOW())";
				} elseif ($data['filter_special_price'] == "future") {
					$where[] = "(ps.date_start > NOW() AND ps.date_start != '0000-00-00')";
				} elseif ($data['filter_special_price'] == "na") {
					$where[] = "(ps.price IS NULL)";
				}
			} else {
				if (isset($data["filter_$key"]) && !is_null($data["filter_$key"])) {
					if ($this->config->get('module_admin_quick_edit_interval_filter')) {
						$where[] = $this->filterInterval($data["filter_$key"], $value);
					} else {
						$where[] = "$value = '" . $this->db->escape($data["filter_$key"]) . "%'";
					}
				}
			}
		}

		$int_interval_filters = array(
			'quantity'      => 'p.quantity',
			'minimum'       => 'p.minimum',
			'points'        => 'p.points',
			'sort_order'    => 'p.sort_order',
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
			'sku'       => 'p.sku',
			'upc'       => 'p.upc',
			'ean'       => 'p.ean',
			'jan'       => 'p.jan',
			'isbn'      => 'p.isbn',
			'mpn'       => 'p.mpn',
			'location'  => 'p.location',
			'name'      => 'pd.name',
			'model'     => 'p.model',
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

		if (isset($data['filter_image']) && !is_null($data['filter_image'])) {
			if ($data['filter_image'] == 1) {
				$where[] = "(p.image IS NOT NULL AND p.image <> '' AND p.image <> 'no_image.png')";
			} else {
				$where[] = "(p.image IS NULL OR p.image = '' OR p.image = 'no_image.png')";
			}
		}

		if (!empty($data['filter_seo'])) {
			if ($this->config->get('module_admin_quick_edit_match_anywhere')) {
				$tokens = preg_split("/\s+/", trim($data['filter_seo']));

				foreach ($tokens as $token) {
					$where[] = "(SELECT keyword FROM " . DB_PREFIX . "seo_url WHERE query = CONCAT('product_id=', p.product_id) LIMIT 1) LIKE '%" . $this->db->escape($token) . "%'";
				}
			} else {
				$where[] = "(SELECT keyword FROM " . DB_PREFIX . "seo_url WHERE query = CONCAT('product_id=', p.product_id) LIMIT 1) LIKE '" . $this->db->escape($data['filter_seo']) . "%'";
			}
		}

		if (!empty($data['filter_tag'])) {
			$where[] = "pd.tag LIKE '%" . $this->db->escape($data['filter_tag']) . "%'";
		}

		if (isset($data['filter_store'])) {
			if ($data['filter_store'] == '*')
				$where[] = "p2s.store_id IS NULL";
			else
				$where[] = "p2s.store_id = '" . (int)$data['filter_store'] . "'";
		}

		if (isset($data['filter_filter'])) {
			if ($data['filter_filter'] == '*')
				$where[] = "p2f.filter_id IS NULL";
			else
				$where[] = "p2f.filter_id = '" . (int)$data['filter_filter'] . "'";
		}

		if (isset($data['filter_download'])) {
			if ($data['filter_download'] == '*')
				$where[] = "p2d.download_id IS NULL";
			else
				$where[] = "p2d.download_id = '" . (int)$data['filter_download'] . "'";
		}

		if (!empty($data['filter_category'])) {
			if (!empty($data['filter_sub_category'])) {
				$implode_data = array();

				if ($data['filter_category'] == '*')
					$implode_data[] = "p2c.category_id IS NULL";
				else
					$implode_data[] = "p2c.category_id = '" . (int)$data['filter_category'] . "'";

				$categories = $this->getSubCategories($data['filter_category']);

				foreach ($categories as $category) {
					if ($data['filter_category'] != '*')
						$implode_data[] = "p2c.category_id = '" . (int)$category['category_id'] . "'";
				}

				$where[] = "(" . implode(" OR ", $implode_data) . ")";
			} else {
				if ($data['filter_category'] == '*')
					$where[] = "p2c.category_id IS NULL";
				else
					$where[] = "p2c.category_id = '" . (int)$data['filter_category'] . "'";
			}
		}

		if ($where) {
			$sql .= " WHERE " . implode(" AND ", $where);
		}

		$sql .= " GROUP BY p.product_id";

		$sort_data = array(
			'p.product_id',
			'tc.title',
			'p.minimum',
			'p.subtract',
			'ss.name',
			'p.shipping',
			'p.date_added',
			'p.date_available',
			'p.date_modified',
			'lc.title',
			'wc.title',
			'p.points',
			'p.length',
			'p.width',
			'p.height',
			'p.weight',
			'p.sku',
			'p.upc',
			'p.ean',
			'p.jan',
			'p.isbn',
			'p.mpn',
			'p.location',
			'm.name',
			'seo',
			'pd.name',
			'p.model',
			'p.price',
			'p.quantity',
			'p.status',
			'p.sort_order',
			'p.viewed'
		);

		if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
			$sql .= " ORDER BY " . $data['sort'];
		} else {
			$sql .= " ORDER BY pd.name";
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
		$this->productCount = ($count->num_rows) ? (int)$count->row['count'] : 0;

		return $query->rows;
	}

	public function getTotalProducts() {
		return $this->productCount;
	}

	public function quickEditProduct($product_id, $column, $value, $lang_id=null, $data=null) {
		$editable = array('manufacturer', 'image', 'model', 'sku', 'upc', 'ean', 'jan', 'mpn', 'isbn', 'location', 'quantity', 'price', 'weight', 'status', 'sort_order', 'tax_class', 'minimum', 'subtract', 'stock_status', 'shipping', 'date_available', 'date_added', 'length', 'width', 'height', 'length_class', 'weight_class', 'points', 'viewed');
		$result = false;
		if (in_array($column, $editable)) {
			if (in_array($column, array('image', 'model', 'sku', 'upc', 'ean', 'jan', 'mpn', 'isbn', 'location', 'date_available', 'date_added')))
				$result = $this->db->query("UPDATE " . DB_PREFIX . "product SET " . $column . " = '" . $this->db->escape($value) . "', date_modified = NOW() WHERE product_id = '" . (int)$product_id . "'");
			else if (in_array($column, array('quantity', 'sort_order', 'status', 'minimum', 'subtract', 'shipping', 'points', 'viewed'))) {
				if (strpos(trim($value), "#") === 0 && preg_match('/^#\s*(?P<operator>[+-\/\*])\s*(?P<operand>-?\d+\.?\d*)(?P<percent>%)?$/', trim($value), $matches) === 1) {
					list($operator, $operand) = $this->parseExpression($matches);
					$result = $this->db->query("UPDATE `" . DB_PREFIX . "product` SET `$column` = `$column` $operator '" . (float)$operand . "', date_modified = NOW() WHERE product_id = '" . (int)$product_id . "'");
					$query = $this->db->query("SELECT `$column` FROM `" . DB_PREFIX . "product` WHERE product_id = '" . (int)$product_id . "'");
					$result = $query->row[$column];
				} else {
					$result = $this->db->query("UPDATE `" . DB_PREFIX . "product` SET `" . $column . "` = '" . (int)$value . "', date_modified = NOW() WHERE product_id = '" . (int)$product_id . "'");
				}
			} else if (in_array($column, array('manufacturer', 'tax_class', 'stock_status', 'length_class', 'weight_class')))
				$result = $this->db->query("UPDATE " . DB_PREFIX . "product SET " . $column . "_id = '" . (int)$value . "', date_modified = NOW() WHERE product_id = '" . (int)$product_id . "'");
			else {
				if (strpos(trim($value), "#") === 0 && preg_match('/^#\s*(?P<operator>[+-\/\*])\s*(?P<operand>-?\d+\.?\d*)(?P<percent>%)?$/', trim($value), $matches) === 1) {
					list($operator, $operand) = $this->parseExpression($matches);
					$result = $this->db->query("UPDATE `" . DB_PREFIX . "product` SET `$column` = `$column` $operator '" . (float)$operand . "', date_modified = NOW() WHERE product_id = '" . (int)$product_id . "'");
					$query = $this->db->query("SELECT `$column` FROM `" . DB_PREFIX . "product` WHERE product_id = '" . (int)$product_id . "'");
					$result = $query->row[$column];
				} else {
					$result = $this->db->query("UPDATE `" . DB_PREFIX . "product` SET `" . $column . "` = '" . (float)$value . "', date_modified = NOW() WHERE product_id = '" . (int)$product_id . "'");
				}
			}
		} else if ($column == 'seo' || $column == 'keywords') {
			$this->db->query("DELETE FROM " . DB_PREFIX . "seo_url WHERE query = 'product_id=" . (int)$product_id. "'");

			if (isset($data['value']) && is_array($data['value'])) {
				foreach ((array)$data['value'] as $store_id => $language) {
					foreach ($language as $language_id => $keyword) {
						if (!empty($keyword)) {
							$this->db->query("INSERT INTO " . DB_PREFIX . "seo_url SET store_id = '" . (int)$store_id . "', language_id = '" . (int)$language_id . "', query = 'product_id=" . (int)$product_id . "', keyword = '" . $this->db->escape($keyword) . "'");
						}
					}
				}
				$result = 1;
			} else {
				$result = 1;
			}
		} else if (in_array($column, array('name', 'tag'))) {
			if (isset($data['value']) && is_array($data['value'])) {
				foreach ((array)$data['value'] as $language_id => $value) {
					$this->db->query("UPDATE " . DB_PREFIX . "product_description SET " . $column . " = '" . $this->db->escape($value) . "' WHERE product_id = '" . (int)$product_id . "' AND language_id = '" . (int)$language_id . "'");
				}
				$result = 1;
			} else if ($value) {
				$result = $this->db->query("UPDATE " . DB_PREFIX . "product_description SET " . $column . " = '" . $this->db->escape($value) . "' WHERE product_id = '" . (int)$product_id . "' AND language_id = '" . (int)$lang_id . "'");
				$result = 1;
			} else {
				$result = 0;
			}
		} else if ($column == 'category') {
			$this->db->query("DELETE FROM " . DB_PREFIX . "product_to_category WHERE product_id = '" . (int)$product_id . "'");

			if (isset($data['product_category'])) {
				foreach ((array)$data['product_category'] as $category_id) {
					$this->db->query("INSERT INTO " . DB_PREFIX . "product_to_category SET product_id = '" . (int)$product_id . "', category_id = '" . (int)$category_id . "'");
				}
			}
			$result = 1;
		} else if ($column == 'store') {
			$this->db->query("DELETE FROM " . DB_PREFIX . "product_to_store WHERE product_id = '" . (int)$product_id . "'");

			if (isset($data['product_store'])) {
				foreach ((array)$data['product_store'] as $store_id) {
					$this->db->query("INSERT INTO " . DB_PREFIX . "product_to_store SET product_id = '" . (int)$product_id . "', store_id = '" . (int)$store_id . "'");
				}
			}
			$result = 1;
		} else if ($column == 'filter' || $column == 'filters') {
			$this->db->query("DELETE FROM " . DB_PREFIX . "product_filter WHERE product_id = '" . (int)$product_id . "'");

			if (isset($data['product_filter'])) {
				foreach ((array)$data['product_filter'] as $filter_id) {
					$this->db->query("INSERT INTO " . DB_PREFIX . "product_filter SET product_id = '" . (int)$product_id . "', filter_id = '" . (int)$filter_id . "'");
				}
			}
			$result = 1;
		} else if ($column == 'download' || $column == 'downloads') {
			$this->db->query("DELETE FROM " . DB_PREFIX . "product_to_download WHERE product_id = '" . (int)$product_id . "'");

			if (isset($data['product_download'])) {
				foreach ((array)$data['product_download'] as $download_id) {
					$this->db->query("INSERT INTO " . DB_PREFIX . "product_to_download SET product_id = '" . (int)$product_id . "', download_id = '" . (int)$download_id . "'");
				}
			}
			$result = 1;
		} else if ($column == 'attributes') {
			$this->db->query("DELETE FROM " . DB_PREFIX . "product_attribute WHERE product_id = '" . (int)$product_id . "'");

			if (!empty($data['product_attribute'])) {
				foreach ((array)$data['product_attribute'] as $product_attribute) {
					if ($product_attribute['attribute_id']) {
						$this->db->query("DELETE FROM " . DB_PREFIX . "product_attribute WHERE product_id = '" . (int)$product_id . "' AND attribute_id = '" . (int)$product_attribute['attribute_id'] . "'");

						foreach ($product_attribute['product_attribute_description'] as $language_id => $product_attribute_description) {
							$this->db->query("INSERT INTO " . DB_PREFIX . "product_attribute SET product_id = '" . (int)$product_id . "', attribute_id = '" . (int)$product_attribute['attribute_id'] . "', language_id = '" . (int)$language_id . "', text = '" .  $this->db->escape($product_attribute_description['text']) . "'");
						}
					}
				}
			}
			$result = 1;
		} else if ($column == 'discounts') {
			$module_admin_quick_edit_price_relative_to = $this->config->get('module_admin_quick_edit_price_relative_to');
			if ($module_admin_quick_edit_price_relative_to == 'product') {
				$query = $this->db->query("SELECT price FROM " . DB_PREFIX . "product WHERE product_id = '" . (int)$product_id . "'");
				if ($query->num_rows) {
					$product_price = $query->row['price'];
				} else {
					$product_price = 0;
				}
			} else {
				$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "product_discount WHERE product_id = '" . (int)$product_id . "'");
				$old_discounts = array_remap_key_to_id('product_discount_id', (array)$query->rows);
			}

			$this->db->query("DELETE FROM " . DB_PREFIX . "product_discount WHERE product_id = '" . (int)$product_id . "'");

			if (isset($data['product_discount'])) {
				foreach ((array)$data['product_discount'] as $product_discount) {
					$_value = $product_discount['price'];
					if (strpos(trim($_value), "#") === 0 && preg_match('/^#\s*(?P<operator>[+-\/\*])\s*(?P<operand>-?\d+\.?\d*)(?P<percent>%)?$/', trim($_value), $matches) === 1) {
						list($operator, $operand) = $this->parseExpression($matches);
						$old_value = $module_admin_quick_edit_price_relative_to == 'product' ? $product_price : (isset($product_discount['discount_id']) && isset($old_discounts[$product_discount['discount_id']]) ? $old_discounts[$product_discount['discount_id']]['price'] : 0);

						eval("\$new_value = (float)$old_value $operator (float)$operand;");

						$this->db->query("INSERT INTO " . DB_PREFIX . "product_discount SET product_id = '" . (int)$product_id . "', customer_group_id = '" . (int)$product_discount['customer_group_id'] . "', quantity = '" . (int)$product_discount['quantity'] . "', priority = '" . (int)$product_discount['priority'] . "', price = '" . (float)$new_value . "', date_start = '" . $this->db->escape($product_discount['date_start']) . "', date_end = '" . $this->db->escape($product_discount['date_end']) . "'");
					} else {
						$this->db->query("INSERT INTO " . DB_PREFIX . "product_discount SET product_id = '" . (int)$product_id . "', customer_group_id = '" . (int)$product_discount['customer_group_id'] . "', quantity = '" . (int)$product_discount['quantity'] . "', priority = '" . (int)$product_discount['priority'] . "', price = '" . (float)$product_discount['price'] . "', date_start = '" . $this->db->escape($product_discount['date_start']) . "', date_end = '" . $this->db->escape($product_discount['date_end']) . "'");
					}
				}
			}
			$result = 1;
		} else if ($column == 'images') {
			$this->db->query("DELETE FROM " . DB_PREFIX . "product_image WHERE product_id = '" . (int)$product_id . "'");

			if (isset($data['product_image'])) {
				foreach ((array)$data['product_image'] as $product_image) {
					$this->db->query("INSERT INTO " . DB_PREFIX . "product_image SET product_id = '" . (int)$product_id . "', image = '" . $this->db->escape(html_entity_decode($product_image['image'], ENT_QUOTES, 'UTF-8')) . "', sort_order = '" . (int)$product_image['sort_order'] . "'");
				}
			}
			$result = 1;
		} else if ($column == 'options') {
			$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "product_option_value WHERE product_id = '" . (int)$product_id . "'");
			$old_option_values = array_remap_key_to_id('product_option_value_id', (array)$query->rows);

			$this->db->query("DELETE FROM " . DB_PREFIX . "product_option WHERE product_id = '" . (int)$product_id . "'");
			$this->db->query("DELETE FROM " . DB_PREFIX . "product_option_value WHERE product_id = '" . (int)$product_id . "'");

			if (isset($data['product_option'])) {
				foreach ($data['product_option'] as $product_option) {
					if ($product_option['type'] == 'select' || $product_option['type'] == 'radio' || $product_option['type'] == 'checkbox' || $product_option['type'] == 'image') {
						$query = $this->db->query("SELECT 1 FROM " . DB_PREFIX . "product_option WHERE product_option_id = '" . (int)$product_option['product_option_id'] . "'");
						if ($query->num_rows) {
							$this->db->query("INSERT INTO " . DB_PREFIX . "product_option SET product_id = '" . (int)$product_id . "', option_id = '" . (int)$product_option['option_id'] . "', required = '" . (int)$product_option['required'] . "'");
						} else {
							$this->db->query("INSERT INTO " . DB_PREFIX . "product_option SET product_option_id = '" . (int)$product_option['product_option_id'] . "', product_id = '" . (int)$product_id . "', option_id = '" . (int)$product_option['option_id'] . "', required = '" . (int)$product_option['required'] . "'");
						}

						$product_option_id = $this->db->getLastId();

						if (isset($product_option['product_option_value'])) {
							foreach ((array)$product_option['product_option_value'] as $product_option_value) {
								$query = $this->db->query("SELECT 1 FROM " . DB_PREFIX . "product_option_value WHERE product_option_value_id = '" . (int)$product_option_value['product_option_value_id'] . "'");
								$_value = $product_option_value['price'];
								if ($query->num_rows) {
									if (strpos(trim($_value), "#") === 0 && preg_match('/^#\s*(?P<operator>[+-\/\*])\s*(?P<operand>-?\d+\.?\d*)(?P<percent>%)?$/', trim($_value), $matches) === 1) {
										list($operator, $operand) = $this->parseExpression($matches);
										$old_value = isset($product_option_value['product_option_value_id']) && isset($old_option_values[$product_option_value['product_option_value_id']]) ? $old_option_values[$product_option_value['product_option_value_id']]['price'] : 0;

										eval("\$new_value = (float)$old_value $operator (float)$operand;");

										$this->db->query("INSERT INTO " . DB_PREFIX . "product_option_value SET product_option_id = '" . (int)$product_option_id . "', product_id = '" . (int)$product_id . "', option_id = '" . (int)$product_option['option_id'] . "', option_value_id = '" . (int)$product_option_value['option_value_id'] . "', quantity = '" . (int)$product_option_value['quantity'] . "', subtract = '" . (int)$product_option_value['subtract'] . "', price = '" . (float)$new_value . "', price_prefix = '" . $this->db->escape($product_option_value['price_prefix']) . "', points = '" . (int)$product_option_value['points'] . "', points_prefix = '" . $this->db->escape($product_option_value['points_prefix']) . "', weight = '" . (float)$product_option_value['weight'] . "', weight_prefix = '" . $this->db->escape($product_option_value['weight_prefix']) . "'");
									} else {
										$this->db->query("INSERT INTO " . DB_PREFIX . "product_option_value SET product_option_id = '" . (int)$product_option_id . "', product_id = '" . (int)$product_id . "', option_id = '" . (int)$product_option['option_id'] . "', option_value_id = '" . (int)$product_option_value['option_value_id'] . "', quantity = '" . (int)$product_option_value['quantity'] . "', subtract = '" . (int)$product_option_value['subtract'] . "', price = '" . (float)$product_option_value['price'] . "', price_prefix = '" . $this->db->escape($product_option_value['price_prefix']) . "', points = '" . (int)$product_option_value['points'] . "', points_prefix = '" . $this->db->escape($product_option_value['points_prefix']) . "', weight = '" . (float)$product_option_value['weight'] . "', weight_prefix = '" . $this->db->escape($product_option_value['weight_prefix']) . "'");
									}
								} else {
									if (strpos(trim($_value), "#") === 0 && preg_match('/^#\s*(?P<operator>[+-\/\*])\s*(?P<operand>-?\d+\.?\d*)(?P<percent>%)?$/', trim($_value), $matches) === 1) {
										list($operator, $operand) = $this->parseExpression($matches);
										$old_value = isset($product_option_value['product_option_value_id']) && isset($old_option_values[$product_option_value['product_option_value_id']]) ? $old_option_values[$product_option_value['product_option_value_id']]['price'] : 0;

										eval("\$new_value = (float)$old_value $operator (float)$operand;");

										$this->db->query("INSERT INTO " . DB_PREFIX . "product_option_value SET product_option_value_id = '" . (int)$product_option_value['product_option_value_id'] . "', product_option_id = '" . (int)$product_option_id . "', product_id = '" . (int)$product_id . "', option_id = '" . (int)$product_option['option_id'] . "', option_value_id = '" . (int)$product_option_value['option_value_id'] . "', quantity = '" . (int)$product_option_value['quantity'] . "', subtract = '" . (int)$product_option_value['subtract'] . "', price = '" . (float)$new_value . "', price_prefix = '" . $this->db->escape($product_option_value['price_prefix']) . "', points = '" . (int)$product_option_value['points'] . "', points_prefix = '" . $this->db->escape($product_option_value['points_prefix']) . "', weight = '" . (float)$product_option_value['weight'] . "', weight_prefix = '" . $this->db->escape($product_option_value['weight_prefix']) . "'");
									} else {
										$this->db->query("INSERT INTO " . DB_PREFIX . "product_option_value SET product_option_value_id = '" . (int)$product_option_value['product_option_value_id'] . "', product_option_id = '" . (int)$product_option_id . "', product_id = '" . (int)$product_id . "', option_id = '" . (int)$product_option['option_id'] . "', option_value_id = '" . (int)$product_option_value['option_value_id'] . "', quantity = '" . (int)$product_option_value['quantity'] . "', subtract = '" . (int)$product_option_value['subtract'] . "', price = '" . (float)$product_option_value['price'] . "', price_prefix = '" . $this->db->escape($product_option_value['price_prefix']) . "', points = '" . (int)$product_option_value['points'] . "', points_prefix = '" . $this->db->escape($product_option_value['points_prefix']) . "', weight = '" . (float)$product_option_value['weight'] . "', weight_prefix = '" . $this->db->escape($product_option_value['weight_prefix']) . "'");
									}
								}
							}
						}
					} else {
						$query = $this->db->query("SELECT 1 FROM " . DB_PREFIX . "product_option WHERE product_option_id = '" . (int)$product_option['product_option_id'] . "'");
						if ($query->num_rows) {
							$this->db->query("INSERT INTO " . DB_PREFIX . "product_option SET product_id = '" . (int)$product_id . "', option_id = '" . (int)$product_option['option_id'] . "', value = '" . $this->db->escape($product_option['value']) . "', required = '" . (int)$product_option['required'] . "'");
						} else {
							$this->db->query("INSERT INTO " . DB_PREFIX . "product_option SET product_option_id = '" . (int)$product_option['product_option_id'] . "', product_id = '" . (int)$product_id . "', option_id = '" . (int)$product_option['option_id'] . "', value = '" . $this->db->escape($product_option['value']) . "', required = '" . (int)$product_option['required'] . "'");
						}
					}
				}
			}
			$result = 1;
		} else if ($column == 'recurrings') {
			$this->db->query("DELETE FROM `" . DB_PREFIX . "product_recurring` WHERE product_id = " . (int)$product_id);

			if (isset($data['product_recurrings'])) {
				foreach ($data['product_recurrings'] as $recurring) {
					$this->db->query("INSERT INTO `" . DB_PREFIX . "product_recurring` SET `product_id` = " . (int)$product_id . ", customer_group_id = " . (int)$recurring['customer_group_id'] . ", `recurring_id` = " . (int)$recurring['recurring_id']);
				}
			}
			$result = 1;
		} else if ($column == 'specials') {
			$module_admin_quick_edit_price_relative_to = $this->config->get('module_admin_quick_edit_price_relative_to');
			if ($module_admin_quick_edit_price_relative_to == 'product') {
				$query = $this->db->query("SELECT price FROM " . DB_PREFIX . "product WHERE product_id = '" . (int)$product_id . "'");
				if ($query->num_rows) {
					$product_price = $query->row['price'];
				} else {
					$product_price = 0;
				}
			} else {
				$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "product_special WHERE product_id = '" . (int)$product_id . "'");
				$old_specials = array_remap_key_to_id('product_special_id', (array)$query->rows);
			}

			$this->db->query("DELETE FROM " . DB_PREFIX . "product_special WHERE product_id = '" . (int)$product_id . "'");

			if (isset($data['product_special'])) {
				foreach ((array)$data['product_special'] as $product_special) {
					$_value = $product_special['price'];
					if (strpos(trim($_value), "#") === 0 && preg_match('/^#\s*(?P<operator>[+-\/\*])\s*(?P<operand>-?\d+\.?\d*)(?P<percent>%)?$/', trim($_value), $matches) === 1) {
						list($operator, $operand) = $this->parseExpression($matches);
						$old_value = $module_admin_quick_edit_price_relative_to == 'product' ? $product_price : (isset($product_special['special_id']) && isset($old_specials[$product_special['special_id']]) ? $old_specials[$product_special['special_id']]['price'] : 0);

						eval("\$new_value = (float)$old_value $operator (float)$operand;");

						$this->db->query("INSERT INTO " . DB_PREFIX . "product_special SET product_id = '" . (int)$product_id . "', customer_group_id = '" . (int)$product_special['customer_group_id'] . "', priority = '" . (int)$product_special['priority'] . "', price = '" . (float)$new_value . "', date_start = '" . $this->db->escape($product_special['date_start']) . "', date_end = '" . $this->db->escape($product_special['date_end']) . "'");
					} else {
						$this->db->query("INSERT INTO " . DB_PREFIX . "product_special SET product_id = '" . (int)$product_id . "', customer_group_id = '" . (int)$product_special['customer_group_id'] . "', priority = '" . (int)$product_special['priority'] . "', price = '" . (float)$product_special['price'] . "', date_start = '" . $this->db->escape($product_special['date_start']) . "', date_end = '" . $this->db->escape($product_special['date_end']) . "'");
					}
				}
			}
			$result = 1;
		} else if ($column == 'related') {
			$this->db->query("DELETE FROM " . DB_PREFIX . "product_related WHERE product_id = '" . (int)$product_id . "'");
			$this->db->query("DELETE FROM " . DB_PREFIX . "product_related WHERE related_id = '" . (int)$product_id . "'");

			if (isset($data['product_related'])) {
				foreach ((array)$data['product_related'] as $related_id) {
					$this->db->query("DELETE FROM " . DB_PREFIX . "product_related WHERE product_id = '" . (int)$product_id . "' AND related_id = '" . (int)$related_id . "'");
					$this->db->query("INSERT INTO " . DB_PREFIX . "product_related SET product_id = '" . (int)$product_id . "', related_id = '" . (int)$related_id . "'");
					$this->db->query("DELETE FROM " . DB_PREFIX . "product_related WHERE product_id = '" . (int)$related_id . "' AND related_id = '" . (int)$product_id . "'");
					$this->db->query("INSERT INTO " . DB_PREFIX . "product_related SET product_id = '" . (int)$related_id . "', related_id = '" . (int)$product_id . "'");
				}
			}
			$result = 1;
		} else if ($column == 'descriptions') {
			foreach ((array)$data['product_description'] as $language_id => $value) {
				$this->db->query("UPDATE " . DB_PREFIX . "product_description SET description = '" . $this->db->escape($value['description']) . "', meta_title = '" . $this->db->escape($value['meta_title']) . "', meta_description = '" . $this->db->escape($value['meta_description']) . "', meta_keyword = '" . $this->db->escape($value['meta_keyword']) . "' WHERE product_id = '" . (int)$product_id . "' AND language_id = '" . (int)$language_id . "'");
			}
			$result = 1;
		}

		$this->cache->delete('product');

		return $result;
	}

	public function getSubCategories($category_id) {
		$sql = "SELECT DISTINCT category_id FROM " . DB_PREFIX . "category_path WHERE path_id = '" . (int)$category_id . "'";

		$query = $this->db->query($sql);

		return $query->rows;
	}
}
