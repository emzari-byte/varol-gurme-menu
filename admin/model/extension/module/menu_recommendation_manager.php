<?php
class ModelExtensionModuleMenuRecommendationManager extends Model {
	public function install() {
		$this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "menu_recommendation_product` (
			`id` INT(11) NOT NULL AUTO_INCREMENT,
			`product_id` INT(11) NOT NULL,
			`role` ENUM('main','drink','dessert','hot_drink') NOT NULL,
			`pair_tag` VARCHAR(64) NOT NULL DEFAULT '',
			`priority` INT(11) NOT NULL DEFAULT 0,
			`status` TINYINT(1) NOT NULL DEFAULT 1,
			PRIMARY KEY (`id`),
			UNIQUE KEY `uniq_product_role` (`product_id`,`role`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

		$this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "menu_recommendation_product_mode` (
			`id` INT(11) NOT NULL AUTO_INCREMENT,
			`product_id` INT(11) NOT NULL,
			`mode` ENUM('breakfast','light','hearty','dessert_coffee','drink_only') NOT NULL,
			PRIMARY KEY (`id`),
			UNIQUE KEY `uniq_product_mode` (`product_id`,`mode`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

		$this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "menu_recommendation_config` (
			`config_id` INT(11) NOT NULL AUTO_INCREMENT,
			`config_key` VARCHAR(64) NOT NULL,
			`config_value` TEXT NOT NULL,
			PRIMARY KEY (`config_id`),
			UNIQUE KEY `uniq_config_key` (`config_key`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

		$this->seedDefaults();
	}

	public function seedDefaults() {
		$defaults = array(
			'module_status'         => '1',
			'temperature_threshold' => '10',
			'breakfast_end_hour'    => '12',
			'lunch_end_hour'        => '18',
			'prevent_duplicates'    => '1'
		);

		foreach ($defaults as $key => $value) {
			$this->setConfig($key, $value, false);
		}
	}

	public function setConfig($key, $value, $replace = true) {
		$key = $this->db->escape($key);
		$value = $this->db->escape((string)$value);

		if ($replace) {
			$this->db->query("DELETE FROM `" . DB_PREFIX . "menu_recommendation_config` WHERE config_key = '" . $key . "'");
		}

		$query = $this->db->query("SELECT config_id FROM `" . DB_PREFIX . "menu_recommendation_config` WHERE config_key = '" . $key . "' LIMIT 1");

		if (!$query->num_rows) {
			$this->db->query("INSERT INTO `" . DB_PREFIX . "menu_recommendation_config`
				SET config_key = '" . $key . "', config_value = '" . $value . "'");
		}
	}

	public function getConfigs() {
		$result = array();

		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "menu_recommendation_config`");

		foreach ($query->rows as $row) {
			$result[$row['config_key']] = $row['config_value'];
		}

		return $result;
	}

	public function saveGeneral($data) {
		$keys = array(
			'module_status',
			'temperature_threshold',
			'breakfast_end_hour',
			'lunch_end_hour',
			'prevent_duplicates'
		);

		foreach ($keys as $key) {
			$value = isset($data[$key]) ? $data[$key] : '';
			$this->setConfig($key, $value);
		}
	}

	public function getProducts() {
		$language_id = (int)$this->config->get('config_language_id');

		$sql = "SELECT 
					p.product_id,
					pd.name,
					MIN(cd.name) AS category_name
				FROM `" . DB_PREFIX . "product` p
				LEFT JOIN `" . DB_PREFIX . "product_description` pd 
					ON (p.product_id = pd.product_id)
				LEFT JOIN `" . DB_PREFIX . "product_to_category` pc 
					ON (p.product_id = pc.product_id)
				LEFT JOIN `" . DB_PREFIX . "category_description` cd 
					ON (pc.category_id = cd.category_id AND cd.language_id = '" . $language_id . "')
				WHERE pd.language_id = '" . $language_id . "'
				GROUP BY p.product_id, pd.name
				ORDER BY pd.name ASC";

		return $this->db->query($sql)->rows;
	}

	public function getProductProfiles() {
		$profiles = array();

		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "menu_recommendation_product`");

		foreach ($query->rows as $row) {
			$product_id = (int)$row['product_id'];

			$profiles[$product_id] = array(
				'product_id' => $product_id,
				'role'       => $row['role'],
				'pair_tag'   => $row['pair_tag'],
				'priority'   => (int)$row['priority'],
				'status'     => (int)$row['status'],
				'modes'      => array()
			);
		}

		$mode_query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "menu_recommendation_product_mode`");

		foreach ($mode_query->rows as $row) {
			$product_id = (int)$row['product_id'];

			if (!isset($profiles[$product_id])) {
				$profiles[$product_id] = array(
					'product_id' => $product_id,
					'role'       => '',
					'pair_tag'   => '',
					'priority'   => 0,
					'status'     => 0,
					'modes'      => array()
				);
			}

			$profiles[$product_id]['modes'][] = $row['mode'];
		}

		return $profiles;
	}

	public function saveProfiles($profiles) {
		$this->db->query("TRUNCATE TABLE `" . DB_PREFIX . "menu_recommendation_product`");
		$this->db->query("TRUNCATE TABLE `" . DB_PREFIX . "menu_recommendation_product_mode`");

		if (!is_array($profiles)) {
			return;
		}

		$valid_roles = array('main', 'drink', 'dessert', 'hot_drink');
		$valid_modes = array('breakfast', 'light', 'hearty', 'dessert_coffee', 'drink_only');

		foreach ($profiles as $product_id => $profile) {
			$product_id = (int)$product_id;
			$role = isset($profile['role']) ? trim((string)$profile['role']) : '';
			$pair_tag = isset($profile['pair_tag']) ? trim((string)$profile['pair_tag']) : '';
			$priority = isset($profile['priority']) ? (int)$profile['priority'] : 0;
			$status = !empty($profile['status']) ? 1 : 0;
			$modes = isset($profile['modes']) && is_array($profile['modes']) ? $profile['modes'] : array();

			if ($product_id <= 0 || !in_array($role, $valid_roles, true)) {
				continue;
			}

			$this->db->query("INSERT INTO `" . DB_PREFIX . "menu_recommendation_product`
				SET product_id = '" . $product_id . "',
					role = '" . $this->db->escape($role) . "',
					pair_tag = '" . $this->db->escape($pair_tag) . "',
					priority = '" . $priority . "',
					status = '" . $status . "'");

			foreach ($modes as $mode) {
				if (!in_array($mode, $valid_modes, true)) {
					continue;
				}

				$this->db->query("INSERT INTO `" . DB_PREFIX . "menu_recommendation_product_mode`
					SET product_id = '" . $product_id . "',
						mode = '" . $this->db->escape($mode) . "'");
			}
		}
	}
}