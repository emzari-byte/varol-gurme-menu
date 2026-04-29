<?php
class ModelExtensionModuleRestaurantHomeProducts extends Model {
	private $table = 'restaurant_home_section';

	public function install() {
		$this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . $this->table . "` (
			`section_code` varchar(32) NOT NULL,
			`name` varchar(255) NOT NULL DEFAULT '',
			`title_json` text NOT NULL,
			`products` text NOT NULL,
			`status` tinyint(1) NOT NULL DEFAULT '1',
			`sort_order` int(11) NOT NULL DEFAULT '0',
			`date_modified` datetime NOT NULL,
			PRIMARY KEY (`section_code`)
		) ENGINE=MyISAM DEFAULT CHARSET=utf8");

		$this->ensureColumn('title_json', "ALTER TABLE `" . DB_PREFIX . $this->table . "` ADD `title_json` text NOT NULL AFTER `name`");
		$this->seedSection('regional', 'Yöresel Ürünler', 2, 1);
		$this->seedSection('popular', 'En Çok Tercih Edilenler', 7, 2);
	}

	public function getSections() {
		$this->install();

		$sections = array(
			'regional' => array(
				'section_code' => 'regional',
				'name'         => 'Yöresel Ürünler',
				'titles'       => array(),
				'status'       => 1,
				'products'     => array()
			),
			'popular' => array(
				'section_code' => 'popular',
				'name'         => 'En Çok Tercih Edilenler',
				'titles'       => array(),
				'status'       => 1,
				'products'     => array()
			)
		);

		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . $this->table . "` ORDER BY sort_order ASC");

		foreach ($query->rows as $row) {
			if (!isset($sections[$row['section_code']])) {
				continue;
			}

			$product_ids = $this->decodeProducts($row['products']);

			$sections[$row['section_code']] = array(
				'section_code' => $row['section_code'],
				'name'         => $row['name'],
				'titles'       => $this->decodeTitles($row),
				'status'       => (int)$row['status'],
				'products'     => $this->getProductsByIds($product_ids)
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
			$default_name = $code == 'regional' ? 'Yöresel Ürünler' : 'En Çok Tercih Edilenler';
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
			$product_ids = array();

			if (!empty($section['products']) && is_array($section['products'])) {
				foreach ($section['products'] as $product_id) {
					$product_id = (int)$product_id;

					if ($product_id > 0 && !in_array($product_id, $product_ids)) {
						$product_ids[] = $product_id;
					}
				}
			}

			$this->db->query("REPLACE INTO `" . DB_PREFIX . $this->table . "`
				SET section_code = '" . $this->db->escape($code) . "',
					name = '" . $this->db->escape($name) . "',
					title_json = '" . $this->db->escape(json_encode($titles)) . "',
					products = '" . $this->db->escape(json_encode($product_ids)) . "',
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
				status = '1',
				sort_order = '" . (int)$sort_order . "',
				date_modified = NOW()");
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

		$product_ids = array();

		foreach ($decoded as $product_id) {
			$product_id = (int)$product_id;

			if ($product_id > 0) {
				$product_ids[] = $product_id;
			}
		}

		return $product_ids;
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
