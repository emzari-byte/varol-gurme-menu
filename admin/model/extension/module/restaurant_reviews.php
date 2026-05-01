<?php
class ModelExtensionModuleRestaurantReviews extends Model {
	public function install() {
		$this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "restaurant_review` (
			`review_id` int(11) NOT NULL AUTO_INCREMENT,
			`table_id` int(11) NOT NULL DEFAULT '0',
			`table_no` int(11) NOT NULL DEFAULT '0',
			`table_name` varchar(128) NOT NULL DEFAULT '',
			`waiter_user_id` int(11) NOT NULL DEFAULT '0',
			`waiter_name` varchar(128) NOT NULL DEFAULT '',
			`restaurant_order_ids` varchar(255) NOT NULL DEFAULT '',
			`session_token` varchar(64) NOT NULL DEFAULT '',
			`rating` tinyint(1) NOT NULL DEFAULT '0',
			`note` text NOT NULL,
			`is_closed` tinyint(1) NOT NULL DEFAULT '0',
			`ip` varchar(64) NOT NULL DEFAULT '',
			`user_agent` varchar(255) NOT NULL DEFAULT '',
			`date_added` datetime NOT NULL,
			`date_modified` datetime NOT NULL,
			PRIMARY KEY (`review_id`),
			KEY `table_id` (`table_id`),
			KEY `rating` (`rating`),
			KEY `date_added` (`date_added`),
			KEY `session_token` (`session_token`)
		) ENGINE=MyISAM DEFAULT CHARSET=utf8");

		$this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "restaurant_review_invite` (
			`invite_id` int(11) NOT NULL AUTO_INCREMENT,
			`table_id` int(11) NOT NULL DEFAULT '0',
			`session_token` varchar(64) NOT NULL DEFAULT '',
			`restaurant_order_ids` varchar(255) NOT NULL DEFAULT '',
			`waiter_user_id` int(11) NOT NULL DEFAULT '0',
			`waiter_name` varchar(128) NOT NULL DEFAULT '',
			`date_added` datetime NOT NULL,
			PRIMARY KEY (`invite_id`),
			KEY `table_id` (`table_id`),
			KEY `session_token` (`session_token`),
			KEY `date_added` (`date_added`)
		) ENGINE=MyISAM DEFAULT CHARSET=utf8");
	}

	public function getAverage($days = 0) {
		$this->install();

		$where = "rating > 0";

		if ((int)$days > 0) {
			$where .= " AND date_added >= DATE_SUB(NOW(), INTERVAL " . (int)$days . " DAY)";
		}

		$query = $this->db->query("SELECT AVG(rating) AS average_rating, COUNT(*) AS total
			FROM `" . DB_PREFIX . "restaurant_review`
			WHERE " . $where);

		return array(
			'average' => $query->row['average_rating'] !== null ? round((float)$query->row['average_rating'], 2) : 0,
			'total' => (int)$query->row['total']
		);
	}

	public function getReviews($filter = array()) {
		$this->install();

		$where = array("rating > 0");

		if (!empty($filter['date_from']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $filter['date_from'])) {
			$where[] = "DATE(date_added) >= '" . $this->db->escape($filter['date_from']) . "'";
		}

		if (!empty($filter['date_to']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $filter['date_to'])) {
			$where[] = "DATE(date_added) <= '" . $this->db->escape($filter['date_to']) . "'";
		}

		if (!empty($filter['rating'])) {
			$where[] = "rating = '" . (int)$filter['rating'] . "'";
		}

		return $this->db->query("SELECT *
			FROM `" . DB_PREFIX . "restaurant_review`
			WHERE " . implode(' AND ', $where) . "
			ORDER BY date_added DESC
			LIMIT 250")->rows;
	}
}
