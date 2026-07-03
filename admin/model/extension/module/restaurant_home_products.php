<?php
class ModelExtensionModuleRestaurantHomeProducts extends Model {
	private $table = 'restaurant_home_section';
	private $periods = array(
		'morning' => array(
			'label' => 'Sabah',
			'range' => '08:30 - 12:00'
		),
		'noon' => array(
			'label' => 'Öğlen',
			'range' => '12:01 - 15:00'
		),
		'afternoon' => array(
			'label' => 'İkindi',
			'range' => '15:01 - 18:00'
		),
		'evening' => array(
			'label' => 'Akşam',
			'range' => '18:01 - 21:30'
		),
		'night' => array(
			'label' => 'Gece',
			'range' => '21:31 - 08:29'
		)
	);

	public function install() {
		$this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . $this->table . "` (
			`section_code` varchar(32) NOT NULL,
			`name` varchar(255) NOT NULL DEFAULT '',
			`title_json` text NOT NULL,
			`products` text NOT NULL,
			`products_by_period` text NOT NULL,
			`status` tinyint(1) NOT NULL DEFAULT '1',
			`sort_order` int(11) NOT NULL DEFAULT '0',
			`date_modified` datetime NOT NULL,
			PRIMARY KEY (`section_code`)
		) ENGINE=MyISAM DEFAULT CHARSET=utf8");

		$this->ensureColumn('title_json', "ALTER TABLE `" . DB_PREFIX . $this->table . "` ADD `title_json` text NOT NULL AFTER `name`");
		$this->ensureColumn('products_by_period', "ALTER TABLE `" . DB_PREFIX . $this->table . "` ADD `products_by_period` text NOT NULL AFTER `products`");
		$this->seedSection('regional', 'Popüler Ürünler', 2, 1);
		$this->seedSection('popular', 'Paylaşmalık Menü', 7, 2);
		$this->backfillPeriodProducts();
	}

	public function getSections() {
		$this->install();

		$sections = array(
			'regional' => array(
				'section_code' => 'regional',
				'name'         => 'Popüler Ürünler',
				'titles'       => array(),
				'status'       => 1,
				'products'     => array(),
				'periods'      => $this->buildEmptyPeriods()
			),
			'popular' => array(
				'section_code' => 'popular',
				'name'         => 'Paylaşmalık Menü',
				'titles'       => array(),
				'status'       => 1,
				'products'     => array(),
				'periods'      => $this->buildEmptyPeriods()
			)
		);

		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . $this->table . "` ORDER BY sort_order ASC");

		foreach ($query->rows as $row) {
			if (!isset($sections[$row['section_code']])) {
				continue;
			}

			$legacy_product_ids = $this->decodeProducts($row['products']);
			$period_products = $this->decodePeriodProducts(isset($row['products_by_period']) ? $row['products_by_period'] : '', $legacy_product_ids);

			$sections[$row['section_code']] = array(
				'section_code' => $row['section_code'],
				'name'         => $row['name'],
				'titles'       => $this->decodeTitles($row),
				'status'       => (int)$row['status'],
				'products'     => $this->getProductsByIds($legacy_product_ids),
				'periods'      => $this->hydratePeriods($period_products)
			);
		}

		return $sections;
	}

	public function saveSections($sections) {
		$this->install();

		$allowed = array(
			'regional' => 1,
			'popular'  => 2
		);

		foreach ($allowed as $code => $sort_order) {
			$section = isset($sections[$code]) ? $sections[$code] : array();
			$default_name = $code == 'regional' ? 'Popüler Ürünler' : 'Paylaşmalık Menü';
			$titles = array();

			if (!empty($section['titles']) && is_array($section['titles'])) {
				foreach ($section['titles'] as $language_id => $title) {
					$language_id = (int)$language_id;
					$title = trim((string)$title);

					if ($language_id > 0) {
						$titles[$language_id] = $title;
					}
				}
			}

			$name = $this->getFirstTitle($titles, $default_name);
			$status = !empty($section['status']) ? 1 : 0;
			$period_product_ids = array();

			foreach ($this->periods as $period_code => $period) {
				$period_product_ids[$period_code] = array();

				if (!empty($section['periods'][$period_code]['products']) && is_array($section['periods'][$period_code]['products'])) {
					$period_product_ids[$period_code] = $this->normaliseProductIds($section['periods'][$period_code]['products']);
				}
			}

			$product_ids = $this->getFirstPeriodProductIds($period_product_ids);

			if (!$product_ids && !empty($section['products']) && is_array($section['products'])) {
				$product_ids = $this->normaliseProductIds($section['products']);
			}

			$this->db->query("REPLACE INTO `" . DB_PREFIX . $this->table . "`
				SET section_code = '" . $this->db->escape($code) . "',
					name = '" . $this->db->escape($name) . "',
					title_json = '" . $this->db->escape(json_encode($titles)) . "',
					products = '" . $this->db->escape(json_encode($product_ids)) . "',
					products_by_period = '" . $this->db->escape(json_encode($period_product_ids)) . "',
					status = '" . (int)$status . "',
					sort_order = '" . (int)$sort_order . "',
					date_modified = NOW()");
		}
	}

	private function seedSection($code, $name, $module_id, $sort_order) {
		$exists = $this->db->query("SELECT section_code FROM `" . DB_PREFIX . $this->table . "`
			WHERE section_code = '" . $this->db->escape($code) . "'
			LIMIT 1");

		if ($exists->num_rows) {
			return;
		}

		$product_ids = array();
		$module = $this->db->query("SELECT setting FROM `" . DB_PREFIX . "module`
			WHERE module_id = '" . (int)$module_id . "'
			LIMIT 1");

		if ($module->num_rows) {
			$setting = json_decode($module->row['setting'], true);

			if (!empty($setting['product']) && is_array($setting['product'])) {
				foreach ($setting['product'] as $product_id) {
					$product_ids[] = (int)$product_id;
				}
			}
		}

		$this->db->query("INSERT INTO `" . DB_PREFIX . $this->table . "`
			SET section_code = '" . $this->db->escape($code) . "',
				name = '" . $this->db->escape($name) . "',
				title_json = '" . $this->db->escape(json_encode(array((int)$this->config->get('config_language_id') => $name))) . "',
				products = '" . $this->db->escape(json_encode($product_ids)) . "',
				products_by_period = '" . $this->db->escape(json_encode(array(
					'morning' => $product_ids,
					'noon' => $product_ids,
					'afternoon' => $product_ids,
					'evening' => $product_ids,
					'night' => $product_ids
				))) . "',
				status = '1',
				sort_order = '" . (int)$sort_order . "',
				date_modified = NOW()");
	}

	private function backfillPeriodProducts() {
		$query = $this->db->query("SELECT section_code, products, products_by_period FROM `" . DB_PREFIX . $this->table . "`");

		foreach ($query->rows as $row) {
			$decoded = json_decode((string)$row['products_by_period'], true);
			$needs_update = !is_array($decoded);

			if (!is_array($decoded)) {
				$decoded = array();
			}

			foreach ($this->periods as $period_code => $period) {
				if (!array_key_exists($period_code, $decoded)) {
					$needs_update = true;
					break;
				}
			}

			if (!$needs_update) {
				continue;
			}

			$product_ids = $this->decodeProducts($row['products']);
			$legacy_noon_products = isset($decoded['noon']) ? $this->normaliseProductIds($decoded['noon']) : $product_ids;
			$legacy_evening_products = isset($decoded['evening']) ? $this->normaliseProductIds($decoded['evening']) : $product_ids;
			$period_products = array();

			foreach ($this->periods as $period_code => $period) {
				if (array_key_exists($period_code, $decoded)) {
					$period_products[$period_code] = $this->normaliseProductIds($decoded[$period_code]);
				} elseif ($period_code === 'afternoon' || $period_code === 'evening') {
					$period_products[$period_code] = $legacy_noon_products;
				} elseif ($period_code === 'night') {
					$period_products[$period_code] = $legacy_evening_products;
				} else {
					$period_products[$period_code] = $product_ids;
				}
			}

			$this->db->query("UPDATE `" . DB_PREFIX . $this->table . "`
				SET products_by_period = '" . $this->db->escape(json_encode($period_products)) . "'
				WHERE section_code = '" . $this->db->escape($row['section_code']) . "'");
		}
	}

	private function buildEmptyPeriods() {
		$periods = array();

		foreach ($this->periods as $period_code => $period) {
			$periods[$period_code] = array(
				'code'     => $period_code,
				'label'    => $period['label'],
				'range'    => $period['range'],
				'products' => array()
			);
		}

		return $periods;
	}

	private function hydratePeriods($period_product_ids) {
		$periods = array();

		foreach ($this->periods as $period_code => $period) {
			$product_ids = isset($period_product_ids[$period_code]) ? $period_product_ids[$period_code] : array();

			$periods[$period_code] = array(
				'code'     => $period_code,
				'label'    => $period['label'],
				'range'    => $period['range'],
				'products' => $this->getProductsByIds($product_ids)
			);
		}

		return $periods;
	}

	private function getProductsByIds($product_ids) {
		$products = array();

		if (!$product_ids) {
			return $products;
		}

		$language_id = (int)$this->config->get('config_language_id');

		foreach ($product_ids as $product_id) {
			$query = $this->db->query("SELECT p.product_id, pd.name
				FROM `" . DB_PREFIX . "product` p
				LEFT JOIN `" . DB_PREFIX . "product_description` pd ON (pd.product_id = p.product_id AND pd.language_id = '" . $language_id . "')
				WHERE p.product_id = '" . (int)$product_id . "'
				LIMIT 1");

			if ($query->num_rows) {
				$products[] = array(
					'product_id' => (int)$query->row['product_id'],
					'name'       => $query->row['name']
				);
			}
		}

		return $products;
	}

	private function decodeProducts($value) {
		$decoded = json_decode((string)$value, true);

		if (!is_array($decoded)) {
			$decoded = array_filter(array_map('trim', explode(',', (string)$value)));
		}

		return $this->normaliseProductIds($decoded);
	}

	private function decodePeriodProducts($value, $legacy_product_ids = array()) {
		$decoded = json_decode((string)$value, true);
		$has_period_data = false;
		$period_products = array();

		foreach ($this->periods as $period_code => $period) {
			if (is_array($decoded) && array_key_exists($period_code, $decoded)) {
				$has_period_data = true;
				$period_products[$period_code] = $this->normaliseProductIds($decoded[$period_code]);
			} else {
				$period_products[$period_code] = $legacy_product_ids;
			}
		}

		if (!$has_period_data) {
			foreach ($this->periods as $period_code => $period) {
				$period_products[$period_code] = $legacy_product_ids;
			}
		}

		return $period_products;
	}

	private function normaliseProductIds($products) {
		$product_ids = array();

		if (!is_array($products)) {
			return $product_ids;
		}

		foreach ($products as $product_id) {
			$product_id = (int)$product_id;

			if ($product_id > 0 && !in_array($product_id, $product_ids)) {
				$product_ids[] = $product_id;
			}
		}

		return $product_ids;
	}

	private function getFirstPeriodProductIds($period_product_ids) {
		foreach ($this->periods as $period_code => $period) {
			if (!empty($period_product_ids[$period_code])) {
				return $period_product_ids[$period_code];
			}
		}

		return array();
	}

	private function decodeTitles($row) {
		$titles = array();

		if (isset($row['title_json'])) {
			$decoded = json_decode((string)$row['title_json'], true);

			if (is_array($decoded)) {
				foreach ($decoded as $language_id => $title) {
					$titles[(int)$language_id] = (string)$title;
				}
			}
		}

		if (!$titles && !empty($row['name'])) {
			$titles[(int)$this->config->get('config_language_id')] = $row['name'];
		}

		return $titles;
	}

	private function getFirstTitle($titles, $default_name) {
		foreach ($titles as $title) {
			if ($title !== '') {
				return $title;
			}
		}

		return $default_name;
	}

	private function ensureColumn($column, $sql) {
		$query = $this->db->query("SHOW COLUMNS FROM `" . DB_PREFIX . $this->table . "` LIKE '" . $this->db->escape($column) . "'");

		if (!$query->num_rows) {
			$this->db->query($sql);
		}
	}
}
