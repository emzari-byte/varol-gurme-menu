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

		$rows = $this->db->query("SELECT *
			FROM `" . DB_PREFIX . "restaurant_review`
			WHERE " . implode(' AND ', $where) . "
			ORDER BY date_added DESC
			LIMIT 250")->rows;

		$reviews = array();

		foreach ($rows as $row) {
			$reviews[] = $this->enrichReview($row);
		}

		return $reviews;
	}

	private function enrichReview($review) {
		$order_ids = array();

		foreach (explode(',', (string)$review['restaurant_order_ids']) as $order_id) {
			$order_id = (int)$order_id;

			if ($order_id > 0) {
				$order_ids[] = $order_id;
			}
		}

		$order_ids = array_values(array_unique($order_ids));
		$waiter_name = trim((string)$review['waiter_name']);
		$waiter_user_id = (int)$review['waiter_user_id'];

		if (!$order_ids || $waiter_name === '') {
			$fallback = $this->getReviewOrderMeta($review, $order_ids);

			if (!$order_ids && !empty($fallback['order_ids'])) {
				$order_ids = $fallback['order_ids'];
			}

			if ($waiter_name === '' && !empty($fallback['waiter_name'])) {
				$waiter_name = $fallback['waiter_name'];
			}

			if (!$waiter_user_id && !empty($fallback['waiter_user_id'])) {
				$waiter_user_id = (int)$fallback['waiter_user_id'];
			}
		}

		$review['display_waiter_name'] = $waiter_name !== '' ? $waiter_name : '-';
		$review['display_order_no'] = $order_ids ? '#' . implode(', #', $order_ids) : '-';
		$review['display_order_count'] = count($order_ids);
		$review['display_table'] = trim((string)$review['table_name']) !== '' ? $review['table_name'] : ((int)$review['table_no'] ? 'Masa ' . (int)$review['table_no'] : '-');
		$review['display_waiter_user_id'] = $waiter_user_id;

		return $review;
	}

	private function getReviewOrderMeta($review, $order_ids = array()) {
		if (!$this->tableExists(DB_PREFIX . 'restaurant_order')) {
			return array();
		}

		$where = array();

		if ($order_ids) {
			$where[] = "ro.restaurant_order_id IN (" . implode(',', array_map('intval', $order_ids)) . ")";
		} else {
			$table_id = (int)$review['table_id'];
			$date_added = $this->db->escape((string)$review['date_added']);

			if (!$table_id || $date_added === '') {
				return array();
			}

			$where[] = "ro.table_id = '" . $table_id . "'";
			$where[] = "ro.date_added <= '" . $date_added . "'";
			$where[] = "ro.date_added >= DATE_SUB('" . $date_added . "', INTERVAL 12 HOUR)";
			$where[] = "ro.service_status IN ('paid','completed','served','payment_pending')";
		}

		$query = $this->db->query("SELECT
				GROUP_CONCAT(DISTINCT ro.restaurant_order_id ORDER BY ro.restaurant_order_id ASC SEPARATOR ',') AS order_ids,
				MAX(ro.waiter_user_id) AS waiter_user_id,
				MAX(COALESCE(NULLIF(TRIM(CONCAT(u.firstname, ' ', u.lastname)), ''), u.username, '')) AS waiter_name
			FROM `" . DB_PREFIX . "restaurant_order` ro
			LEFT JOIN `" . DB_PREFIX . "user` u ON (u.user_id = ro.waiter_user_id)
			WHERE " . implode(' AND ', $where));

		if (!$query->num_rows) {
			return array();
		}

		$resolved_order_ids = array();

		foreach (explode(',', (string)$query->row['order_ids']) as $order_id) {
			$order_id = (int)$order_id;

			if ($order_id > 0) {
				$resolved_order_ids[] = $order_id;
			}
		}

		return array(
			'order_ids' => array_values(array_unique($resolved_order_ids)),
			'waiter_user_id' => (int)$query->row['waiter_user_id'],
			'waiter_name' => trim((string)$query->row['waiter_name'])
		);
	}

	private function tableExists($table) {
		try {
			$query = $this->db->query("SHOW TABLES LIKE '" . $this->db->escape($table) . "'");

			return $query->num_rows > 0;
		} catch (Exception $e) {
			return false;
		}
	}
}
