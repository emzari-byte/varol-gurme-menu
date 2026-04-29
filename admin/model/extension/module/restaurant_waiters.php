<?php
class ModelExtensionModuleRestaurantWaiters extends Model {
	private function ensureWaiterColumns() {
		$columns = array();
		$query = $this->db->query("SHOW COLUMNS FROM `" . DB_PREFIX . "restaurant_waiter`");

		foreach ($query->rows as $row) {
			$columns[$row['Field']] = true;
		}

		if (empty($columns['work_minutes'])) {
			$this->db->query("ALTER TABLE `" . DB_PREFIX . "restaurant_waiter` ADD `work_minutes` int(11) NOT NULL DEFAULT '600'");
		}

		if (empty($columns['break_limit_minutes'])) {
			$this->db->query("ALTER TABLE `" . DB_PREFIX . "restaurant_waiter` ADD `break_limit_minutes` int(11) NOT NULL DEFAULT '60'");
		}
	}

	private function ensureBreakLogTable() {
		$this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "restaurant_waiter_break_log` (
			`break_id` int(11) NOT NULL AUTO_INCREMENT,
			`waiter_id` int(11) NOT NULL,
			`user_id` int(11) NOT NULL,
			`delegate_user_id` int(11) NOT NULL DEFAULT '0',
			`start_at` datetime NOT NULL,
			`end_at` datetime DEFAULT NULL,
			`duration_minutes` int(11) NOT NULL DEFAULT '0',
			`date_added` datetime NOT NULL,
			PRIMARY KEY (`break_id`),
			KEY `waiter_id` (`waiter_id`),
			KEY `user_id` (`user_id`),
			KEY `start_at` (`start_at`)
		) ENGINE=MyISAM DEFAULT CHARSET=utf8");
	}

	public function getGarsonUserGroupId() {
		$query = $this->db->query("SELECT user_group_id
			FROM `" . DB_PREFIX . "user_group`
			WHERE name = 'Garson'
			LIMIT 1");

		return $query->num_rows ? (int)$query->row['user_group_id'] : 0;
	}

	public function getWaiters() {
		$this->ensureWaiterColumns();
		$this->ensureBreakLogTable();

		$sql = "SELECT 
					rw.waiter_id,
					rw.user_id,
					rw.name,
					rw.status,
					rw.work_minutes,
					rw.break_limit_minutes,
					rw.break_status,
					rw.break_started_at,
					u.username,
					(
						SELECT COUNT(*)
						FROM `" . DB_PREFIX . "restaurant_waiter_table` rwt
						WHERE rwt.waiter_id = rw.waiter_id
					) AS table_count,
					(
						SELECT IFNULL(SUM(
							CASE
								WHEN rwbl.end_at IS NULL THEN TIMESTAMPDIFF(MINUTE, rwbl.start_at, NOW())
								ELSE rwbl.duration_minutes
							END
						), 0)
						FROM `" . DB_PREFIX . "restaurant_waiter_break_log` rwbl
						WHERE rwbl.waiter_id = rw.waiter_id
						AND DATE(rwbl.start_at) = CURDATE()
					) AS today_break_minutes
				FROM `" . DB_PREFIX . "restaurant_waiter` rw
				LEFT JOIN `" . DB_PREFIX . "user` u ON (u.user_id = rw.user_id)
				ORDER BY rw.name ASC";

		return $this->db->query($sql)->rows;
	}

	public function getWaiter($waiter_id) {
		$this->ensureWaiterColumns();
		$this->ensureBreakLogTable();

		$query = $this->db->query("SELECT rw.*, u.username
			FROM `" . DB_PREFIX . "restaurant_waiter` rw
			LEFT JOIN `" . DB_PREFIX . "user` u ON (u.user_id = rw.user_id)
			WHERE rw.waiter_id = '" . (int)$waiter_id . "'
			LIMIT 1");

		return $query->row;
	}
private function ocHashPassword($password) {
	$salt = token(9);

	return array(
		'salt'     => $salt,
		'password' => sha1($salt . sha1($salt . sha1($password)))
	);
}
	public function getWaiterByUsername($username) {
		$query = $this->db->query("SELECT rw.waiter_id, rw.user_id, u.username
			FROM `" . DB_PREFIX . "restaurant_waiter` rw
			LEFT JOIN `" . DB_PREFIX . "user` u ON (u.user_id = rw.user_id)
			WHERE u.username = '" . $this->db->escape($username) . "'
			LIMIT 1");

		return $query->row;
	}

	public function addWaiter($data) {
		$this->ensureWaiterColumns();
		$this->ensureBreakLogTable();

		$user_group_id = $this->getGarsonUserGroupId();
		$work_minutes = !empty($data['work_minutes']) ? (int)$data['work_minutes'] : 600;
		$break_limit_minutes = !empty($data['break_limit_minutes']) ? (int)$data['break_limit_minutes'] : 60;

		$hash = $this->ocHashPassword((string)$data['password']);
$email = 'waiter_' . time() . '_' . mt_rand(1000, 9999) . '@local.invalid';

$this->db->query("INSERT INTO `" . DB_PREFIX . "user`
	SET user_group_id = '" . (int)$user_group_id . "',
		username = '" . $this->db->escape($data['username']) . "',
		password = '" . $this->db->escape($hash['password']) . "',
		salt = '" . $this->db->escape($hash['salt']) . "',
		firstname = '" . $this->db->escape($data['name']) . "',
		lastname = '',
		email = '" . $this->db->escape($email) . "',
		image = '',
		code = '',
		ip = '',
		status = '" . (int)$data['status'] . "',
		date_added = NOW()");

		$user_id = (int)$this->db->getLastId();

		$this->db->query("INSERT INTO `" . DB_PREFIX . "restaurant_waiter`
			SET user_id = '" . $user_id . "',
				name = '" . $this->db->escape($data['name']) . "',
				phone = '',
				status = '" . (int)$data['status'] . "',
				work_minutes = '" . $work_minutes . "',
				break_limit_minutes = '" . $break_limit_minutes . "',
				date_added = NOW(),
				date_modified = NOW()");

		$waiter_id = (int)$this->db->getLastId();

		if (!empty($data['assigned_tables'])) {
			foreach ((array)$data['assigned_tables'] as $table_id) {
				$this->db->query("INSERT INTO `" . DB_PREFIX . "restaurant_waiter_table`
					SET waiter_id = '" . $waiter_id . "',
						table_id = '" . (int)$table_id . "',
						date_added = NOW()");
			}
		}

		return $waiter_id;
	}

	public function editWaiter($waiter_id, $data) {
		$this->ensureWaiterColumns();
		$this->ensureBreakLogTable();

		$waiter_id = (int)$waiter_id;

		$waiter = $this->getWaiter($waiter_id);

		if (!$waiter) {
			return false;
		}

		$user_id = (int)$waiter['user_id'];

		$this->db->query("UPDATE `" . DB_PREFIX . "user`
			SET username = '" . $this->db->escape($data['username']) . "',
				firstname = '" . $this->db->escape($data['name']) . "',
				status = '" . (int)$data['status'] . "'
			WHERE user_id = '" . $user_id . "'");

		if (!empty($data['password'])) {
	$hash = $this->ocHashPassword((string)$data['password']);

	$this->db->query("UPDATE `" . DB_PREFIX . "user`
		SET password = '" . $this->db->escape($hash['password']) . "',
			salt = '" . $this->db->escape($hash['salt']) . "'
		WHERE user_id = '" . $user_id . "'");
}

		$this->db->query("UPDATE `" . DB_PREFIX . "restaurant_waiter`
			SET name = '" . $this->db->escape($data['name']) . "',
				phone = '',
				status = '" . (int)$data['status'] . "',
				work_minutes = '" . (!empty($data['work_minutes']) ? (int)$data['work_minutes'] : 600) . "',
				break_limit_minutes = '" . (!empty($data['break_limit_minutes']) ? (int)$data['break_limit_minutes'] : 60) . "',
				date_modified = NOW()
			WHERE waiter_id = '" . $waiter_id . "'");

		$this->db->query("DELETE FROM `" . DB_PREFIX . "restaurant_waiter_table`
			WHERE waiter_id = '" . $waiter_id . "'");

		if (!empty($data['assigned_tables'])) {
			foreach ((array)$data['assigned_tables'] as $table_id) {
				$this->db->query("INSERT INTO `" . DB_PREFIX . "restaurant_waiter_table`
					SET waiter_id = '" . $waiter_id . "',
						table_id = '" . (int)$table_id . "',
						date_added = NOW()");
			}
		}

		return true;
	}

	public function deleteWaiter($waiter_id) {
		$this->ensureBreakLogTable();

		$waiter_id = (int)$waiter_id;
		$waiter = $this->getWaiter($waiter_id);

		if ($waiter) {
			$this->db->query("DELETE FROM `" . DB_PREFIX . "user`
				WHERE user_id = '" . (int)$waiter['user_id'] . "'");
		}

		$this->db->query("DELETE FROM `" . DB_PREFIX . "restaurant_waiter_table`
			WHERE waiter_id = '" . $waiter_id . "'");

		$this->db->query("DELETE FROM `" . DB_PREFIX . "restaurant_waiter`
			WHERE waiter_id = '" . $waiter_id . "'");

		$this->db->query("DELETE FROM `" . DB_PREFIX . "restaurant_waiter_break_log`
			WHERE waiter_id = '" . $waiter_id . "'");
	}

	public function getWaiterTableIds($waiter_id) {
		$results = $this->db->query("SELECT table_id
			FROM `" . DB_PREFIX . "restaurant_waiter_table`
			WHERE waiter_id = '" . (int)$waiter_id . "'")->rows;

		$table_ids = array();

		foreach ($results as $row) {
			$table_ids[] = (int)$row['table_id'];
		}

		return $table_ids;
	}

	public function getWaiterBreakSummary($waiter_id) {
		$this->ensureWaiterColumns();
		$this->ensureBreakLogTable();

		$waiter_id = (int)$waiter_id;
		$query = $this->db->query("SELECT
				rw.work_minutes,
				rw.break_limit_minutes,
				IFNULL(SUM(
					CASE
						WHEN rwbl.end_at IS NULL THEN TIMESTAMPDIFF(MINUTE, rwbl.start_at, NOW())
						ELSE rwbl.duration_minutes
					END
				), 0) AS today_break_minutes,
				COUNT(rwbl.break_id) AS today_break_count
			FROM `" . DB_PREFIX . "restaurant_waiter` rw
			LEFT JOIN `" . DB_PREFIX . "restaurant_waiter_break_log` rwbl ON (rwbl.waiter_id = rw.waiter_id AND DATE(rwbl.start_at) = CURDATE())
			WHERE rw.waiter_id = '" . $waiter_id . "'
			GROUP BY rw.waiter_id
			LIMIT 1");

		if (!$query->num_rows) {
			return array(
				'work_minutes' => 0,
				'break_limit_minutes' => 0,
				'today_break_minutes' => 0,
				'today_break_count' => 0,
				'remaining_minutes' => 0,
				'limit_exceeded' => false
			);
		}

		$used = (int)$query->row['today_break_minutes'];
		$limit = (int)$query->row['break_limit_minutes'];

		return array(
			'work_minutes' => (int)$query->row['work_minutes'],
			'break_limit_minutes' => $limit,
			'today_break_minutes' => $used,
			'today_break_count' => (int)$query->row['today_break_count'],
			'remaining_minutes' => max(0, $limit - $used),
			'limit_exceeded' => ($limit > 0 && $used > $limit)
		);
	}

	public function getWaiterBreakHistory($waiter_id, $limit = 50) {
		$this->ensureBreakLogTable();

		$waiter_id = (int)$waiter_id;
		$limit = max(1, (int)$limit);

		return $this->db->query("SELECT
				rwbl.break_id,
				rwbl.start_at,
				rwbl.end_at,
				CASE
					WHEN rwbl.end_at IS NULL THEN TIMESTAMPDIFF(MINUTE, rwbl.start_at, NOW())
					ELSE rwbl.duration_minutes
				END AS duration_minutes,
				rwbl.delegate_user_id,
				du.username AS delegate_username,
				drw.name AS delegate_name
			FROM `" . DB_PREFIX . "restaurant_waiter_break_log` rwbl
			LEFT JOIN `" . DB_PREFIX . "user` du ON (du.user_id = rwbl.delegate_user_id)
			LEFT JOIN `" . DB_PREFIX . "restaurant_waiter` drw ON (drw.user_id = rwbl.delegate_user_id)
			WHERE rwbl.waiter_id = '" . $waiter_id . "'
			ORDER BY rwbl.start_at DESC
			LIMIT " . $limit)->rows;
	}

	public function getRestaurantTables() {
		$query = $this->db->query("SELECT table_id, table_no, name
			FROM `" . DB_PREFIX . "restaurant_table`
			WHERE status = '1'
			ORDER BY sort_order ASC, table_no ASC");

		return $query->rows;
	}
}
