<?php
class ModelCommonRestaurantHomeProducts extends Model {
	private $table = 'restaurant_home_section';
	private $periods = array(
		'morning' => array(
			'label' => 'Sabah',
			'range' => '08:30 - 12:00'
		),
		'noon' => array(
			'label' => 'Öğlen',
			'range' => '12:01 - 21:30'
		),
		'evening' => array(
			'label' => 'Akşam',
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

	public function getSection($section_code, $language_id = 0) {
		$this->install();
		$language_id = $language_id ? (int)$language_id : (int)$this->config->get('config_language_id');

		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . $this->table . "`
			WHERE section_code = '" . $this->db->escape($section_code) . "'
			LIMIT 1");

		if (!$query->num_rows || !(int)$query->row['status']) {
			return array(
				'name'     => '',
				'products' => array(),
				'period'   => $this->getActivePeriod()
			);
		}

		$legacy_products = $this->decodeProducts($query->row['products']);
		$period_products = $this->decodePeriodProducts(isset($query->row['products_by_period']) ? $query->row['products_by_period'] : '', $legacy_products);
		$active_period = $this->getActivePeriod();
		$product_ids = !empty($period_products[$active_period]) ? $period_products[$active_period] : $legacy_products;

		return array(
			'name'     => $this->getTitle($query->row, $language_id),
			'products' => $product_ids,
			'period'   => $active_period
		);
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
					'evening' => $product_ids
				))) . "',
				status = '1',
				sort_order = '" . (int)$sort_order . "',
				date_modified = NOW()");
	}

	private function backfillPeriodProducts() {
		$query = $this->db->query("SELECT section_code, products, products_by_period FROM `" . DB_PREFIX . $this->table . "`");

		foreach ($query->rows as $row) {
			if (trim((string)$row['products_by_period']) !== '') {
				continue;
			}

			$product_ids = $this->decodeProducts($row['products']);
			$period_products = array();

			foreach ($this->periods as $period_code => $period) {
				$period_products[$period_code] = $product_ids;
			}

			$this->db->query("UPDATE `" . DB_PREFIX . $this->table . "`
				SET products_by_period = '" . $this->db->escape(json_encode($period_products)) . "'
				WHERE section_code = '" . $this->db->escape($row['section_code']) . "'");
		}
	}

	private function decodeProducts($value) {
		$decoded = json_decode((string)$value, true);

		if (!is_array($decoded)) {
			$decoded = array_filter(array_map('trim', explode(',', (string)$value)));
		}

		$product_ids = array();

		foreach ($decoded as $product_id) {
			$product_id = (int)$product_id;

			if ($product_id > 0) {
				$product_ids[] = $product_id;
			}
		}

		return $product_ids;
	}

	private function decodePeriodProducts($value, $legacy_product_ids = array()) {
		$decoded = json_decode((string)$value, true);
		$has_period_data = false;
		$period_products = array();

		foreach ($this->periods as $period_code => $period) {
			if (is_array($decoded) && array_key_exists($period_code, $decoded)) {
				$has_period_data = true;
				$period_products[$period_code] = $this->decodeProducts(json_encode($decoded[$period_code]));
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

	private function getActivePeriod() {
		$time = (int)date('Hi');

		if ($time >= 830 && $time <= 1200) {
			return 'morning';
		}

		if ($time >= 1201 && $time <= 2130) {
			return 'noon';
		}

		return 'evening';
	}

	private function getTitle($row, $language_id) {
		if (isset($row['title_json'])) {
			$titles = json_decode((string)$row['title_json'], true);

			if (is_array($titles) && !empty($titles[$language_id])) {
				return $titles[$language_id];
			}

			if (is_array($titles)) {
				foreach ($titles as $title) {
					if ($title !== '') {
						return $title;
					}
				}
			}
		}

		return !empty($row['name']) ? $row['name'] : '';
	}

	private function ensureColumn($column, $sql) {
		$query = $this->db->query("SHOW COLUMNS FROM `" . DB_PREFIX . $this->table . "` LIKE '" . $this->db->escape($column) . "'");

		if (!$query->num_rows) {
			$this->db->query($sql);
		}
	}
}
