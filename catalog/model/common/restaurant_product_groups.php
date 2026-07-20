<?php
class ModelCommonRestaurantProductGroups extends Model {
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
	}

	public function getGroupsByCategoryId($category_id, $language_id) {
		$this->install();

		$groups = array();
		$default_language_id = (int)$this->config->get('config_language_id');

		$query = $this->db->query("SELECT g.*,
				COALESCE(gd.name, gd_default.name, '') AS name,
				COALESCE(gd.description, gd_default.description, '') AS description
			FROM `" . DB_PREFIX . $this->table_group . "` g
			LEFT JOIN `" . DB_PREFIX . $this->table_description . "` gd ON (gd.group_id = g.group_id AND gd.language_id = '" . (int)$language_id . "')
			LEFT JOIN `" . DB_PREFIX . $this->table_description . "` gd_default ON (gd_default.group_id = g.group_id AND gd_default.language_id = '" . (int)$default_language_id . "')
			WHERE g.category_id = '" . (int)$category_id . "'
				AND g.status = '1'
			ORDER BY g.sort_order ASC, g.group_id ASC");

		foreach ($query->rows as $row) {
			$group_id = (int)$row['group_id'];
			$groups[] = array(
				'group_id' => $group_id,
				'name' => $row['name'],
				'description' => $row['description'],
				'image' => $row['image'],
				'meta_label' => $row['meta_label'],
				'sort_order' => (int)$row['sort_order'],
				'items' => $this->getGroupItems($group_id)
			);
		}

		return $groups;
	}

	private function getGroupItems($group_id) {
		$items = array();

		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . $this->table_item . "`
			WHERE group_id = '" . (int)$group_id . "'
			ORDER BY sort_order ASC, item_id ASC");

		foreach ($query->rows as $row) {
			$items[] = array(
				'product_id' => (int)$row['product_id'],
				'variant_label' => $row['variant_label'],
				'variant_note' => $row['variant_note'],
				'sort_order' => (int)$row['sort_order']
			);
		}

		return $items;
	}
}
