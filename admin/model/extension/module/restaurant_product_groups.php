<?php
class ModelExtensionModuleRestaurantProductGroups extends Model {
	private $table_group = 'restaurant_product_group';
	private $table_description = 'restaurant_product_group_description';
	private $table_item = 'restaurant_product_group_item';

	public function install() {
		$this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . $this->table_group . "` (
			`group_id` int(11) NOT NULL AUTO_INCREMENT,
			`category_id` int(11) NOT NULL DEFAULT '0',
			`image` varchar(255) NOT NULL DEFAULT '',
			`meta_label` varchar(255) NOT NULL DEFAULT '',
			`status` tinyint(1) NOT NULL DEFAULT '1',
			`sort_order` int(11) NOT NULL DEFAULT '0',
			`date_added` datetime NOT NULL,
			`date_modified` datetime NOT NULL,
			PRIMARY KEY (`group_id`),
			KEY `category_id` (`category_id`),
			KEY `status` (`status`)
		) ENGINE=MyISAM DEFAULT CHARSET=utf8");

		$this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . $this->table_description . "` (
			`group_id` int(11) NOT NULL,
			`language_id` int(11) NOT NULL,
			`name` varchar(255) NOT NULL DEFAULT '',
			`description` text NOT NULL,
			PRIMARY KEY (`group_id`,`language_id`)
		) ENGINE=MyISAM DEFAULT CHARSET=utf8");

		$this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . $this->table_item . "` (
			`item_id` int(11) NOT NULL AUTO_INCREMENT,
			`group_id` int(11) NOT NULL,
			`product_id` int(11) NOT NULL,
			`variant_label` varchar(255) NOT NULL DEFAULT '',
			`variant_note` varchar(255) NOT NULL DEFAULT '',
			`sort_order` int(11) NOT NULL DEFAULT '0',
			PRIMARY KEY (`item_id`),
			KEY `group_id` (`group_id`),
			KEY `product_id` (`product_id`)
		) ENGINE=MyISAM DEFAULT CHARSET=utf8");

		$this->seedDefaultGroupsOnce();
	}

	public function getGroups() {
		$this->install();

		$groups = array();
		$language_id = (int)$this->config->get('config_language_id');

		$query = $this->db->query("SELECT g.*, gd.name, cd.name AS category_name
			FROM `" . DB_PREFIX . $this->table_group . "` g
			LEFT JOIN `" . DB_PREFIX . $this->table_description . "` gd ON (gd.group_id = g.group_id AND gd.language_id = '" . $language_id . "')
			LEFT JOIN `" . DB_PREFIX . "category_description` cd ON (cd.category_id = g.category_id AND cd.language_id = '" . $language_id . "')
			ORDER BY g.sort_order ASC, g.group_id ASC");

		foreach ($query->rows as $row) {
			$group_id = (int)$row['group_id'];
			$groups[] = array(
				'group_id' => $group_id,
				'category_id' => (int)$row['category_id'],
				'category_name' => $row['category_name'],
				'name' => $row['name'],
				'image' => $row['image'],
				'meta_label' => $row['meta_label'],
				'status' => (int)$row['status'],
				'sort_order' => (int)$row['sort_order'],
				'descriptions' => $this->getGroupDescriptions($group_id),
				'items' => $this->getGroupItems($group_id)
			);
		}

		return $groups;
	}

	public function saveGroups($groups) {
		$this->install();

		$keep_group_ids = array();

		if (!is_array($groups)) {
			$groups = array();
		}

		foreach ($groups as $group) {
			$group_id = !empty($group['group_id']) ? (int)$group['group_id'] : 0;
			$category_id = !empty($group['category_id']) ? (int)$group['category_id'] : 0;
			$image = !empty($group['image']) ? trim((string)$group['image']) : '';
			$meta_label = !empty($group['meta_label']) ? trim((string)$group['meta_label']) : '';
			$status = !empty($group['status']) ? 1 : 0;
			$sort_order = isset($group['sort_order']) ? (int)$group['sort_order'] : 0;

			if ($group_id > 0) {
				$this->db->query("UPDATE `" . DB_PREFIX . $this->table_group . "`
					SET category_id = '" . (int)$category_id . "',
						image = '" . $this->db->escape($image) . "',
						meta_label = '" . $this->db->escape($meta_label) . "',
						status = '" . (int)$status . "',
						sort_order = '" . (int)$sort_order . "',
						date_modified = NOW()
					WHERE group_id = '" . (int)$group_id . "'");
			} else {
				$this->db->query("INSERT INTO `" . DB_PREFIX . $this->table_group . "`
					SET category_id = '" . (int)$category_id . "',
						image = '" . $this->db->escape($image) . "',
						meta_label = '" . $this->db->escape($meta_label) . "',
						status = '" . (int)$status . "',
						sort_order = '" . (int)$sort_order . "',
						date_added = NOW(),
						date_modified = NOW()");

				$group_id = (int)$this->db->getLastId();
			}

			$keep_group_ids[] = $group_id;

			$this->db->query("DELETE FROM `" . DB_PREFIX . $this->table_description . "` WHERE group_id = '" . (int)$group_id . "'");

			if (!empty($group['descriptions']) && is_array($group['descriptions'])) {
				foreach ($group['descriptions'] as $language_id => $description) {
					$language_id = (int)$language_id;
					$name = !empty($description['name']) ? trim((string)$description['name']) : '';
					$text = !empty($description['description']) ? trim((string)$description['description']) : '';

					if ($language_id > 0) {
						$this->db->query("INSERT INTO `" . DB_PREFIX . $this->table_description . "`
							SET group_id = '" . (int)$group_id . "',
								language_id = '" . (int)$language_id . "',
								name = '" . $this->db->escape($name) . "',
								description = '" . $this->db->escape($text) . "'");
					}
				}
			}

			$this->db->query("DELETE FROM `" . DB_PREFIX . $this->table_item . "` WHERE group_id = '" . (int)$group_id . "'");

			if (!empty($group['items']) && is_array($group['items'])) {
				foreach ($group['items'] as $item) {
					$product_id = !empty($item['product_id']) ? (int)$item['product_id'] : 0;

					if ($product_id <= 0) {
						continue;
					}

					$this->db->query("INSERT INTO `" . DB_PREFIX . $this->table_item . "`
						SET group_id = '" . (int)$group_id . "',
							product_id = '" . (int)$product_id . "',
							variant_label = '" . $this->db->escape(!empty($item['variant_label']) ? trim((string)$item['variant_label']) : '') . "',
							variant_note = '" . $this->db->escape(!empty($item['variant_note']) ? trim((string)$item['variant_note']) : '') . "',
							sort_order = '" . (isset($item['sort_order']) ? (int)$item['sort_order'] : 0) . "'");
				}
			}
		}

		if ($keep_group_ids) {
			$this->db->query("DELETE FROM `" . DB_PREFIX . $this->table_group . "` WHERE group_id NOT IN (" . implode(',', array_map('intval', $keep_group_ids)) . ")");
			$this->db->query("DELETE FROM `" . DB_PREFIX . $this->table_description . "` WHERE group_id NOT IN (" . implode(',', array_map('intval', $keep_group_ids)) . ")");
			$this->db->query("DELETE FROM `" . DB_PREFIX . $this->table_item . "` WHERE group_id NOT IN (" . implode(',', array_map('intval', $keep_group_ids)) . ")");
		} else {
			$this->db->query("TRUNCATE TABLE `" . DB_PREFIX . $this->table_group . "`");
			$this->db->query("TRUNCATE TABLE `" . DB_PREFIX . $this->table_description . "`");
			$this->db->query("TRUNCATE TABLE `" . DB_PREFIX . $this->table_item . "`");
		}
	}

	private function getGroupDescriptions($group_id) {
		$descriptions = array();

		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . $this->table_description . "` WHERE group_id = '" . (int)$group_id . "'");

		foreach ($query->rows as $row) {
			$descriptions[(int)$row['language_id']] = array(
				'name' => $row['name'],
				'description' => $row['description']
			);
		}

		return $descriptions;
	}

	private function getGroupItems($group_id) {
		$items = array();
		$language_id = (int)$this->config->get('config_language_id');

		$query = $this->db->query("SELECT gi.*, pd.name AS product_name
			FROM `" . DB_PREFIX . $this->table_item . "` gi
			LEFT JOIN `" . DB_PREFIX . "product_description` pd ON (pd.product_id = gi.product_id AND pd.language_id = '" . $language_id . "')
			WHERE gi.group_id = '" . (int)$group_id . "'
			ORDER BY gi.sort_order ASC, gi.item_id ASC");

		foreach ($query->rows as $row) {
			$items[] = array(
				'product_id' => (int)$row['product_id'],
				'product_name' => $row['product_name'],
				'variant_label' => $row['variant_label'],
				'variant_note' => $row['variant_note'],
				'sort_order' => (int)$row['sort_order']
			);
		}

		return $items;
	}

	private function seedDefaultGroupsOnce() {
		$this->seedOnce('restaurant_product_groups_beverage_seeded', 'seedBeverageGroup');
		$this->seedOnce('restaurant_product_groups_kebab_seeded', 'seedKebabGroup');
	}

	private function seedOnce($key, $method) {
		$seeded = $this->db->query("SELECT `value` FROM `" . DB_PREFIX . "setting`
			WHERE `code` = 'restaurant_product_groups'
				AND `key` = '" . $this->db->escape($key) . "'
				AND store_id = '0'
			LIMIT 1");

		if ($seeded->num_rows) {
			return;
		}

		$this->{$method}();

		$this->db->query("INSERT INTO `" . DB_PREFIX . "setting`
			SET store_id = '0',
				`code` = 'restaurant_product_groups',
				`key` = '" . $this->db->escape($key) . "',
				`value` = '1',
				serialized = '0'");
	}

	private function seedBeverageGroup() {
		$language_id = (int)$this->config->get('config_language_id');
		$product_names = array('Coca-Cola', 'Coca Cola Zero', 'Coca-Cola Zero', 'Fanta', 'Sprite');
		$product_ids = array();
		$category_id = 0;

		foreach ($product_names as $name) {
			$query = $this->db->query("SELECT p.product_id
				FROM `" . DB_PREFIX . "product` p
				LEFT JOIN `" . DB_PREFIX . "product_description` pd ON (pd.product_id = p.product_id AND pd.language_id = '" . $language_id . "')
				WHERE pd.name = '" . $this->db->escape($name) . "'
				LIMIT 1");

			if ($query->num_rows) {
				$product_ids[(int)$query->row['product_id']] = $name;
			}
		}

		if (count($product_ids) < 2) {
			return;
		}

		if ($this->hasExistingGroupForProductIds(array_keys($product_ids))) {
			return;
		}

		$product_id_keys = array_keys($product_ids);
		$first_product_id = (int)reset($product_id_keys);
		$category_query = $this->db->query("SELECT category_id FROM `" . DB_PREFIX . "product_to_category` WHERE product_id = '" . $first_product_id . "' LIMIT 1");

		if ($category_query->num_rows) {
			$category_id = (int)$category_query->row['category_id'];
		}

		$this->db->query("INSERT INTO `" . DB_PREFIX . $this->table_group . "`
			SET category_id = '" . (int)$category_id . "',
				image = 'catalog/icons/soguk-taze.jpg',
				meta_label = 'Çeşit Seçiniz',
				status = '1',
				sort_order = '10',
				date_added = NOW(),
				date_modified = NOW()");

		$group_id = (int)$this->db->getLastId();

		$this->load->model('localisation/language');
		foreach ($this->model_localisation_language->getLanguages() as $language) {
			$name = $language['code'] == 'en-gb' ? 'Carbonated Drinks' : 'Gazlı İçecekler';
			$description = $language['code'] == 'en-gb' ? 'Served cold with Coca-Cola, Coca-Cola Zero, Fanta and Sprite options.' : 'Coca-Cola, Coca-Cola Zero, Fanta ve Sprite seçenekleriyle soğuk servis edilir.';

			$this->db->query("INSERT INTO `" . DB_PREFIX . $this->table_description . "`
				SET group_id = '" . (int)$group_id . "',
					language_id = '" . (int)$language['language_id'] . "',
					name = '" . $this->db->escape($name) . "',
					description = '" . $this->db->escape($description) . "'");
		}

		$sort_order = 1;

		foreach ($product_ids as $product_id => $name) {
			$label = str_replace('Coca Cola', 'Coca-Cola', $name);

			$this->db->query("INSERT INTO `" . DB_PREFIX . $this->table_item . "`
				SET group_id = '" . (int)$group_id . "',
					product_id = '" . (int)$product_id . "',
					variant_label = '" . $this->db->escape($label) . "',
					variant_note = 'Soğuk servis edilir',
					sort_order = '" . (int)$sort_order . "'");

			$sort_order++;
		}
	}

	private function seedKebabGroup() {
		$language_id = (int)$this->config->get('config_language_id');
		$product_ids = array();
		$category_id = 0;
		$group_image = '';
		$kebab_notes = array(
			'250 gr.' => 'Tek Porsiyon',
			'350 gr.' => 'Bol Porsiyon',
			'500 gr.' => 'İki Kişilik',
			'750 gr.' => 'Üç Kişilik',
			'1Kg' => 'Dört Kişilik'
		);

		$query = $this->db->query("SELECT p.product_id, p.image, pd.name
			FROM `" . DB_PREFIX . "product` p
			LEFT JOIN `" . DB_PREFIX . "product_description` pd ON (pd.product_id = p.product_id AND pd.language_id = '" . $language_id . "')
			WHERE pd.name LIKE '%Denizli Kebab%'
			ORDER BY p.price ASC, p.product_id ASC");

		foreach ($query->rows as $row) {
			$label = $this->getKebabVariantLabel($row['name']);

			if ($label === '') {
				continue;
			}

			$product_id = (int)$row['product_id'];

			if (isset($product_ids[$product_id])) {
				continue;
			}

			$product_ids[$product_id] = array(
				'label' => $label,
				'note' => isset($kebab_notes[$label]) ? $kebab_notes[$label] : ''
			);

			if ($group_image === '' && !empty($row['image'])) {
				$group_image = $row['image'];
			}
		}

		if (count($product_ids) < 2) {
			return;
		}

		if ($this->hasExistingGroupForProductIds(array_keys($product_ids))) {
			return;
		}

		$product_id_keys = array_keys($product_ids);
		$first_product_id = (int)reset($product_id_keys);
		$category_query = $this->db->query("SELECT category_id FROM `" . DB_PREFIX . "product_to_category` WHERE product_id = '" . $first_product_id . "' LIMIT 1");

		if ($category_query->num_rows) {
			$category_id = (int)$category_query->row['category_id'];
		}

		$this->db->query("INSERT INTO `" . DB_PREFIX . $this->table_group . "`
			SET category_id = '" . (int)$category_id . "',
				image = '" . $this->db->escape($group_image) . "',
				meta_label = 'Süt Kuzu Tandır',
				status = '1',
				sort_order = '1',
				date_added = NOW(),
				date_modified = NOW()");

		$group_id = (int)$this->db->getLastId();

		$this->load->model('localisation/language');
		foreach ($this->model_localisation_language->getLanguages() as $language) {
			$name = $language['code'] == 'en-gb' ? 'Denizli Kebab' : 'Denizli Kebabı';
			$description = $language['code'] == 'en-gb' ? 'Denizli style milk lamb tandoor kebab served with flatbread and fresh accompaniments.' : "Denizli'ye özgü süt kuzu tandır kebabı; söğüş ve pide ekmeği ile servis edilir.";

			$this->db->query("INSERT INTO `" . DB_PREFIX . $this->table_description . "`
				SET group_id = '" . (int)$group_id . "',
					language_id = '" . (int)$language['language_id'] . "',
					name = '" . $this->db->escape($name) . "',
					description = '" . $this->db->escape($description) . "'");
		}

		$sort_order = 1;

		foreach ($product_ids as $product_id => $item) {
			$this->db->query("INSERT INTO `" . DB_PREFIX . $this->table_item . "`
				SET group_id = '" . (int)$group_id . "',
					product_id = '" . (int)$product_id . "',
					variant_label = '" . $this->db->escape($item['label']) . "',
					variant_note = '" . $this->db->escape($item['note']) . "',
					sort_order = '" . (int)$sort_order . "'");

			$sort_order++;
		}
	}

	private function getKebabVariantLabel($name) {
		$clean = trim(preg_replace('/\s+/', ' ', strip_tags(html_entity_decode($name, ENT_QUOTES, 'UTF-8'))));

		if (preg_match('/^(\d+)\s*gr\.?/iu', $clean, $matches)) {
			return (int)$matches[1] . ' gr.';
		}

		if (preg_match('/^1\s*kg/iu', $clean)) {
			return '1Kg';
		}

		return '';
	}

	private function hasExistingGroupForProductIds($product_ids) {
		$product_ids = array_map('intval', $product_ids);
		$product_ids = array_filter($product_ids);

		if (count($product_ids) < 2) {
			return false;
		}

		$query = $this->db->query("SELECT group_id, COUNT(DISTINCT product_id) AS total
			FROM `" . DB_PREFIX . $this->table_item . "`
			WHERE product_id IN (" . implode(',', $product_ids) . ")
			GROUP BY group_id
			HAVING total >= 2
			LIMIT 1");

		return (bool)$query->num_rows;
	}
}
