<?php
class ModelExtensionModuleRestaurantAllergens extends Model {
	public function install() {
		$this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "restaurant_allergen` (
			`allergen_id` int(11) NOT NULL AUTO_INCREMENT,
			`old_option_value_id` int(11) NOT NULL DEFAULT '0',
			`name` varchar(128) NOT NULL,
			`image` varchar(255) NOT NULL DEFAULT '',
			`sort_order` int(11) NOT NULL DEFAULT '0',
			`status` tinyint(1) NOT NULL DEFAULT '1',
			`date_added` datetime NOT NULL,
			`date_modified` datetime NOT NULL,
			PRIMARY KEY (`allergen_id`),
			KEY `old_option_value_id` (`old_option_value_id`)
		) ENGINE=MyISAM DEFAULT CHARSET=utf8");

		$this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "restaurant_product_allergen` (
			`product_id` int(11) NOT NULL,
			`allergen_id` int(11) NOT NULL,
			PRIMARY KEY (`product_id`,`allergen_id`),
			KEY `allergen_id` (`allergen_id`)
		) ENGINE=MyISAM DEFAULT CHARSET=utf8");

		$this->migrateFromOption();
	}

	private function getLegacyAllergenOptionIds() {
		$option_ids = array(14);

		$query = $this->db->query("SELECT DISTINCT option_id FROM `" . DB_PREFIX . "option_description`
			WHERE LOWER(name) LIKE '%alerjen%'
			OR LOWER(name) LIKE '%alerji%'
			OR LOWER(name) LIKE '%allergen%'");

		foreach ($query->rows as $row) {
			$option_ids[] = (int)$row['option_id'];
		}

		return array_values(array_unique(array_filter($option_ids)));
	}

	public function migrateFromOption() {
		$language_id = (int)$this->config->get('config_language_id');
		$option_ids = $this->getLegacyAllergenOptionIds();

		if (!$option_ids) {
			return;
		}

		$option_id_sql = implode(',', array_map('intval', $option_ids));

		$query = $this->db->query("SELECT ov.option_value_id, ov.image, ov.sort_order, ovd.name
			FROM `" . DB_PREFIX . "option_value` ov
			LEFT JOIN `" . DB_PREFIX . "option_value_description` ovd ON (ov.option_value_id = ovd.option_value_id AND ovd.language_id = '" . $language_id . "')
			WHERE ov.option_id IN (" . $option_id_sql . ")
			ORDER BY ov.sort_order ASC, ovd.name ASC");

		foreach ($query->rows as $row) {
			$exists = $this->db->query("SELECT allergen_id FROM `" . DB_PREFIX . "restaurant_allergen`
				WHERE old_option_value_id = '" . (int)$row['option_value_id'] . "'
				LIMIT 1");

			if (!$exists->num_rows) {
				$this->db->query("INSERT INTO `" . DB_PREFIX . "restaurant_allergen`
					SET old_option_value_id = '" . (int)$row['option_value_id'] . "',
						name = '" . $this->db->escape($row['name']) . "',
						image = '" . $this->db->escape($row['image']) . "',
						sort_order = '" . (int)$row['sort_order'] . "',
						status = '1',
						date_added = NOW(),
						date_modified = NOW()");
			}
		}

		$this->db->query("INSERT IGNORE INTO `" . DB_PREFIX . "restaurant_product_allergen` (product_id, allergen_id)
			SELECT DISTINCT pov.product_id, ra.allergen_id
			FROM `" . DB_PREFIX . "product_option_value` pov
			INNER JOIN `" . DB_PREFIX . "restaurant_allergen` ra ON (ra.old_option_value_id = pov.option_value_id)
			WHERE pov.option_id IN (" . $option_id_sql . ")");
	}

	public function getAllergens() {
		$this->install();

		return $this->db->query("SELECT ra.*,
				(SELECT COUNT(*) FROM `" . DB_PREFIX . "restaurant_product_allergen` rpa WHERE rpa.allergen_id = ra.allergen_id) AS product_count
			FROM `" . DB_PREFIX . "restaurant_allergen` ra
			ORDER BY ra.sort_order ASC, ra.name ASC")->rows;
	}

	public function saveAllergens($rows) {
		$this->install();
		$seen = array();

		foreach ((array)$rows as $row) {
			$allergen_id = (int)($row['allergen_id'] ?? 0);

			if ($allergen_id && !empty($row['delete'])) {
				$this->db->query("DELETE FROM `" . DB_PREFIX . "restaurant_product_allergen` WHERE allergen_id = '" . $allergen_id . "'");
				$this->db->query("DELETE FROM `" . DB_PREFIX . "restaurant_allergen` WHERE allergen_id = '" . $allergen_id . "'");
				continue;
			}

			$name = trim((string)($row['name'] ?? ''));

			if ($name === '') {
				continue;
			}

			$image = trim((string)($row['image'] ?? ''));
			$sort_order = (int)($row['sort_order'] ?? 0);
			$status = !empty($row['status']) ? 1 : 0;

			if ($allergen_id) {
				$this->db->query("UPDATE `" . DB_PREFIX . "restaurant_allergen`
					SET name = '" . $this->db->escape($name) . "',
						image = '" . $this->db->escape($image) . "',
						sort_order = '" . $sort_order . "',
						status = '" . $status . "',
						date_modified = NOW()
					WHERE allergen_id = '" . $allergen_id . "'");
				$seen[] = $allergen_id;
			} else {
				$this->db->query("INSERT INTO `" . DB_PREFIX . "restaurant_allergen`
					SET name = '" . $this->db->escape($name) . "',
						image = '" . $this->db->escape($image) . "',
						sort_order = '" . $sort_order . "',
						status = '" . $status . "',
						date_added = NOW(),
						date_modified = NOW()");
				$seen[] = (int)$this->db->getLastId();
			}
		}
	}
}
