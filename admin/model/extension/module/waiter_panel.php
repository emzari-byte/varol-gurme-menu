<?php
class ModelExtensionModuleWaiterPanel extends Model {
	private function ensureWaiterBreakColumns() {
		$columns = array();
		$query = $this->db->query("SHOW COLUMNS FROM `" . DB_PREFIX . "restaurant_waiter`");

		foreach ($query->rows as $row) {
			$columns[$row['Field']] = true;
		}

		if (empty($columns['break_status'])) {
			$this->db->query("ALTER TABLE `" . DB_PREFIX . "restaurant_waiter` ADD `break_status` tinyint(1) NOT NULL DEFAULT '0'");
		}

		if (empty($columns['break_delegate_user_id'])) {
			$this->db->query("ALTER TABLE `" . DB_PREFIX . "restaurant_waiter` ADD `break_delegate_user_id` int(11) NOT NULL DEFAULT '0'");
		}

		if (empty($columns['break_started_at'])) {
			$this->db->query("ALTER TABLE `" . DB_PREFIX . "restaurant_waiter` ADD `break_started_at` datetime DEFAULT NULL");
		}

		if (empty($columns['work_minutes'])) {
			$this->db->query("ALTER TABLE `" . DB_PREFIX . "restaurant_waiter` ADD `work_minutes` int(11) NOT NULL DEFAULT '600'");
		}

		if (empty($columns['break_limit_minutes'])) {
			$this->db->query("ALTER TABLE `" . DB_PREFIX . "restaurant_waiter` ADD `break_limit_minutes` int(11) NOT NULL DEFAULT '60'");
		}
	}

	private function ensureWaiterBreakLogTable() {
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

	protected function getAssignedTableIds($user_id = 0) {
		$this->ensureWaiterBreakColumns();
		$user_id = (int)$user_id;

		if (!$user_id) {
			return array();
		}

		$table_ids = array();
		$waiter_query = $this->db->query("SELECT waiter_id, break_status FROM `" . DB_PREFIX . "restaurant_waiter` WHERE user_id = '" . $user_id . "' AND status = '1' LIMIT 1");

		if ($waiter_query->num_rows && !(int)$waiter_query->row['break_status']) {
			$rows = $this->db->query("SELECT table_id FROM `" . DB_PREFIX . "restaurant_waiter_table` WHERE waiter_id = '" . (int)$waiter_query->row['waiter_id'] . "'")->rows;

			foreach ($rows as $row) {
				$table_ids[] = (int)$row['table_id'];
			}
		}

		$delegated_rows = $this->db->query("SELECT rwt.table_id
			FROM `" . DB_PREFIX . "restaurant_waiter` rw
			LEFT JOIN `" . DB_PREFIX . "restaurant_waiter_table` rwt ON (rwt.waiter_id = rw.waiter_id)
			WHERE rw.status = '1'
			AND rw.break_status = '1'
			AND rw.break_delegate_user_id = '" . $user_id . "'
			AND rwt.table_id IS NOT NULL")->rows;

		foreach ($delegated_rows as $row) {
			$table_ids[] = (int)$row['table_id'];
		}

		return array_values(array_unique($table_ids));
	}

	protected function isRestaurantWaiterUser($user_id = 0) {
		$this->ensureWaiterBreakColumns();
		$user_id = (int)$user_id;

		if (!$user_id) {
			return false;
		}

		$query = $this->db->query("SELECT waiter_id FROM `" . DB_PREFIX . "restaurant_waiter` WHERE user_id = '" . $user_id . "' AND status = '1' LIMIT 1");

		return (bool)$query->num_rows;
	}

	protected function hasRestaurantWaiterRecord($user_id = 0) {
		$this->ensureWaiterBreakColumns();
		$user_id = (int)$user_id;

		if (!$user_id) {
			return false;
		}

		$query = $this->db->query("SELECT waiter_id FROM `" . DB_PREFIX . "restaurant_waiter` WHERE user_id = '" . $user_id . "' LIMIT 1");

		return (bool)$query->num_rows;
	}

	protected function buildTableFilterSql($user_id = 0) {
		$user_id = (int)$user_id;

		if (!$this->isRestaurantWaiterUser($user_id)) {
			if ($this->hasRestaurantWaiterRecord($user_id)) {
				return " AND 1=0 ";
			}

			return '';
		}

		$table_ids = $this->getAssignedTableIds($user_id);

		if (!$table_ids) {
			return " AND 1=0 ";
		}

		return " AND rt.table_id IN (" . implode(',', array_map('intval', $table_ids)) . ") ";
	}

	public function canAccessTable($table_id, $user_id = 0) {
		$table_id = (int)$table_id;
		$user_id = (int)$user_id;

		if (!$table_id) {
			return false;
		}

		if (!$this->isRestaurantWaiterUser($user_id)) {
			if ($this->hasRestaurantWaiterRecord($user_id)) {
				return false;
			}

			return true;
		}

		return in_array($table_id, $this->getAssignedTableIds($user_id), true);
	}

	public function getCurrentWaiterStatus($user_id = 0) {
		$this->ensureWaiterBreakColumns();
		$user_id = (int)$user_id;

		$query = $this->db->query("SELECT rw.*, u.username
			FROM `" . DB_PREFIX . "restaurant_waiter` rw
			LEFT JOIN `" . DB_PREFIX . "user` u ON (u.user_id = rw.user_id)
			WHERE rw.user_id = '" . $user_id . "'
			LIMIT 1");

		if (!$query->num_rows) {
			return array(
				'is_waiter' => false,
				'on_break' => false,
				'delegate_user_id' => 0,
				'name' => ''
			);
		}

		return array(
			'is_waiter' => true,
			'on_break' => (bool)$query->row['break_status'],
			'delegate_user_id' => (int)$query->row['break_delegate_user_id'],
			'name' => $query->row['name'],
			'break_started_at' => $query->row['break_started_at']
		);
	}

	public function getActiveWaiterDelegates($user_id = 0) {
		$this->ensureWaiterBreakColumns();
		$user_id = (int)$user_id;

		return $this->db->query("SELECT rw.user_id, rw.name, u.username
			FROM `" . DB_PREFIX . "restaurant_waiter` rw
			LEFT JOIN `" . DB_PREFIX . "user` u ON (u.user_id = rw.user_id)
			WHERE rw.status = '1'
			AND rw.break_status = '0'
			AND rw.user_id <> '" . $user_id . "'
			ORDER BY rw.name ASC")->rows;
	}

	public function setWaiterBreak($user_id, $on_break, $delegate_user_id = 0) {
		$this->ensureWaiterBreakColumns();
		$this->ensureWaiterBreakLogTable();
		$user_id = (int)$user_id;
		$delegate_user_id = (int)$delegate_user_id;
		$on_break = (int)$on_break;

		$waiter = $this->db->query("SELECT waiter_id, name, break_status FROM `" . DB_PREFIX . "restaurant_waiter`
			WHERE user_id = '" . $user_id . "'
			AND status = '1'
			LIMIT 1");

		if (!$waiter->num_rows) {
			return false;
		}

		if ($on_break) {
			if ((int)$waiter->row['break_status']) {
				return true;
			}

			$delegate = $this->db->query("SELECT waiter_id FROM `" . DB_PREFIX . "restaurant_waiter`
				WHERE user_id = '" . $delegate_user_id . "'
				AND user_id <> '" . $user_id . "'
				AND status = '1'
				AND break_status = '0'
				LIMIT 1");

			if (!$delegate->num_rows) {
				return false;
			}

			$this->db->query("UPDATE `" . DB_PREFIX . "restaurant_waiter`
				SET break_status = '1',
					break_delegate_user_id = '" . $delegate_user_id . "',
					break_started_at = NOW(),
					date_modified = NOW()
				WHERE user_id = '" . $user_id . "'");

			$this->db->query("INSERT INTO `" . DB_PREFIX . "restaurant_waiter_break_log`
				SET waiter_id = '" . (int)$waiter->row['waiter_id'] . "',
					user_id = '" . $user_id . "',
					delegate_user_id = '" . $delegate_user_id . "',
					start_at = NOW(),
					date_added = NOW()");

			$status = 'waiter_break_start';
			$comment = 'Garson molaya cikti. Delege user_id: ' . $delegate_user_id;
		} else {
			$this->db->query("UPDATE `" . DB_PREFIX . "restaurant_waiter_break_log`
				SET end_at = NOW(),
					duration_minutes = GREATEST(0, TIMESTAMPDIFF(MINUTE, start_at, NOW()))
				WHERE waiter_id = '" . (int)$waiter->row['waiter_id'] . "'
				AND end_at IS NULL
				ORDER BY break_id DESC
				LIMIT 1");

			$this->db->query("UPDATE `" . DB_PREFIX . "restaurant_waiter`
				SET break_status = '0',
					break_delegate_user_id = '0',
					break_started_at = NULL,
					date_modified = NOW()
				WHERE user_id = '" . $user_id . "'");

			$status = 'waiter_break_end';
			$comment = 'Garson moladan dondu.';
		}

		$this->db->query("INSERT INTO `" . DB_PREFIX . "restaurant_order_history`
			SET restaurant_order_id = '0',
				old_status = NULL,
				new_status = '" . $this->db->escape($status) . "',
				user_id = '" . $user_id . "',
				comment = '" . $this->db->escape($comment) . "',
				date_added = NOW()");

		return true;
	}

	public function getOrderTableId($restaurant_order_id) {
		$query = $this->db->query("SELECT table_id FROM `" . DB_PREFIX . "restaurant_order` WHERE restaurant_order_id = '" . (int)$restaurant_order_id . "' LIMIT 1");

		return $query->num_rows ? (int)$query->row['table_id'] : 0;
	}

	public function getTables($user_id = 0) {
		$this->closeExpiredWaiterCalls();
		$this->closeExpiredBillRequests();
		$this->reconcileAllTableStatuses();

		$filter_sql = $this->buildTableFilterSql($user_id);

		$sql = "SELECT
				rt.table_id,
				rt.table_no,
				rt.name,
				rt.capacity,
				rt.area,
				rt.sort_order,
				rt.status,
				IFNULL(rts.service_status,'empty') AS service_status,
				IFNULL(rts.active_order_count,0) AS active_order_count,
				IFNULL(rts.total_amount,0) AS total_amount,
				IFNULL(rts.waiter_name,'') AS waiter_name,
				IFNULL(rts.note,'') AS note,
				IFNULL(rts.date_modified,'') AS date_modified,
				(
					SELECT COUNT(*) FROM `" . DB_PREFIX . "restaurant_call` rc
					WHERE rc.table_id = rt.table_id AND rc.call_type='bill_request' AND rc.status IN ('new','seen')
				) AS bill_request_pending,
				(
					SELECT COUNT(*) FROM `" . DB_PREFIX . "restaurant_call` rc
					WHERE rc.table_id = rt.table_id AND rc.call_type='waiter_call' AND rc.status = 'new'
				) AS waiter_call_pending,
				(
					SELECT IFNULL(MAX(rc.call_id),0) FROM `" . DB_PREFIX . "restaurant_call` rc
					WHERE rc.table_id = rt.table_id AND rc.call_type='waiter_call' AND rc.status = 'new'
				) AS latest_waiter_call_id,
				(
					SELECT IFNULL(rc.status,'') FROM `" . DB_PREFIX . "restaurant_call` rc
					WHERE rc.table_id = rt.table_id AND rc.call_type='waiter_call' AND rc.status = 'new'
					ORDER BY rc.call_id DESC LIMIT 1
				) AS latest_waiter_call_status,
				(
					SELECT IFNULL(rc.date_modified,'') FROM `" . DB_PREFIX . "restaurant_call` rc
					WHERE rc.table_id = rt.table_id AND rc.call_type='waiter_call' AND rc.status = 'new'
					ORDER BY rc.call_id DESC LIMIT 1
				) AS latest_waiter_call_date,
				(
					SELECT IFNULL(MAX(rc.call_id),0) FROM `" . DB_PREFIX . "restaurant_call` rc
					WHERE rc.table_id = rt.table_id AND rc.call_type='bill_request' AND rc.status IN ('new','seen')
				) AS latest_bill_request_id,
				(
					SELECT IFNULL(rc.status,'') FROM `" . DB_PREFIX . "restaurant_call` rc
					WHERE rc.table_id = rt.table_id AND rc.call_type='bill_request' AND rc.status IN ('new','seen')
					ORDER BY rc.call_id DESC LIMIT 1
				) AS latest_bill_request_status,
				(
					SELECT IFNULL(rc.date_modified,'') FROM `" . DB_PREFIX . "restaurant_call` rc
					WHERE rc.table_id = rt.table_id AND rc.call_type='bill_request' AND rc.status IN ('new','seen')
					ORDER BY rc.call_id DESC LIMIT 1
				) AS latest_bill_request_date,
				(
					SELECT IFNULL(rc.note,'') FROM `" . DB_PREFIX . "restaurant_call` rc
					WHERE rc.table_id = rt.table_id AND rc.call_type='bill_request' AND rc.status IN ('new','seen')
					ORDER BY rc.call_id DESC LIMIT 1
				) AS latest_bill_request_note,
				(
					SELECT COUNT(*) FROM `" . DB_PREFIX . "restaurant_order` ro
					WHERE ro.table_id = rt.table_id AND ro.service_status='ready_for_service'
				) AS ready_service_pending,
				(
					SELECT IFNULL(MAX(ro.date_modified),'') FROM `" . DB_PREFIX . "restaurant_order` ro
					WHERE ro.table_id = rt.table_id AND ro.service_status='ready_for_service'
				) AS latest_ready_service_date,
				(
					SELECT IFNULL(MAX(ro.date_modified),'') FROM `" . DB_PREFIX . "restaurant_order` ro
					WHERE ro.table_id = rt.table_id
					AND ro.service_status IN ('waiting_order','cashier_draft','in_kitchen','ready_for_service','out_for_service','served','payment_pending')
				) AS latest_order_date
			FROM `" . DB_PREFIX . "restaurant_table` rt
			LEFT JOIN `" . DB_PREFIX . "restaurant_table_status` rts ON (rt.table_id = rts.table_id)
			WHERE rt.status='1'
			" . $filter_sql . "
			ORDER BY rt.sort_order ASC, rt.table_no ASC";

		$rows = $this->db->query($sql)->rows;

		foreach ($rows as &$row) {
			$row['notify_id'] = 0;
			$row['notify_type'] = '';
			$row['call_badge'] = '';
			$row['call_badge_class'] = '';
			$row['priority_rank'] = 0;
			$row['operation_label'] = 'Boş masa';
			$row['operation_time'] = '';
			$row['operation_minutes'] = 0;
			$row['operation_class'] = 'idle';

			if (!empty($row['bill_request_pending'])) {
				$row['call_badge'] = 'Hesap İsteği';
				$row['call_badge_class'] = 'bill-call';
				$row['notify_id'] = (int)$row['latest_bill_request_id'];
				$row['notify_type'] = 'bill_request';
				$row['priority_rank'] = 100;
				$row['bill_payment_note'] = !empty($row['latest_bill_request_note']) ? trim((string)$row['latest_bill_request_note']) : '';
				$row['operation_label'] = $row['bill_payment_note'] ? 'Hesap bekliyor - ' . $row['bill_payment_note'] : 'Hesap bekliyor';
				$row['operation_class'] = 'bill';
				$this->setOperationAge($row, $row['latest_bill_request_date']);
			} elseif (!empty($row['waiter_call_pending'])) {
				if ($row['latest_waiter_call_status'] === 'seen') {
					$row['call_badge'] = 'Masaya Gidiliyor';
					$row['call_badge_class'] = 'waiter-call seen';
					$row['priority_rank'] = 60;
					$row['operation_label'] = 'Garson masaya gidiyor';
					$row['operation_class'] = 'waiter-seen';
				} else {
					$row['call_badge'] = 'Garson Çağırıyor';
					$row['call_badge_class'] = 'waiter-call';
					$row['priority_rank'] = 95;
					$row['operation_label'] = 'Garson çağrısı';
					$row['operation_class'] = 'waiter';
				}

				$row['notify_id'] = (int)$row['latest_waiter_call_id'];
				$row['notify_type'] = 'waiter_call';
				$this->setOperationAge($row, $row['latest_waiter_call_date']);
			} elseif (!empty($row['ready_service_pending'])) {
				$row['call_badge'] = 'Servise Hazır';
				$row['call_badge_class'] = 'service-ready';
				$row['notify_type'] = 'kitchen_ready';
				$row['priority_rank'] = 80;
				$row['operation_label'] = 'Servis bekliyor';
				$row['operation_class'] = 'service';
				$this->setOperationAge($row, $row['latest_ready_service_date']);
			} elseif ($row['service_status'] === 'waiting_order') {
				$row['priority_rank'] = 90;
				$row['operation_label'] = 'Onay bekliyor';
				$row['operation_class'] = 'order';
				$this->setOperationAge($row, $row['latest_order_date']);
			} elseif ($row['service_status'] === 'cashier_draft') {
				$row['priority_rank'] = 45;
				$row['operation_label'] = 'Kasada taslak sipariş';
				$row['operation_class'] = 'order';
				$this->setOperationAge($row, $row['latest_order_date']);
			} elseif ($row['service_status'] === 'in_kitchen') {
				$row['priority_rank'] = 50;
				$row['operation_label'] = 'Mutfakta';
				$row['operation_class'] = 'kitchen';
				$this->setOperationAge($row, $row['latest_order_date']);
			} elseif ($row['service_status'] === 'out_for_service') {
				$row['priority_rank'] = 70;
				$row['operation_label'] = 'Servise çıkıyor';
				$row['operation_class'] = 'service';
				$this->setOperationAge($row, $row['latest_order_date']);
			} elseif ($row['service_status'] === 'served') {
				$row['priority_rank'] = 20;
				$row['operation_label'] = 'Servis edildi';
				$row['operation_class'] = 'served';
				$this->setOperationAge($row, $row['latest_order_date']);
			}
		}

		unset($row);

		usort($rows, function($a, $b) {
			$rank_a = isset($a['priority_rank']) ? (int)$a['priority_rank'] : 0;
			$rank_b = isset($b['priority_rank']) ? (int)$b['priority_rank'] : 0;

			if ($rank_a !== $rank_b) {
				return $rank_b - $rank_a;
			}

			$minutes_a = isset($a['operation_minutes']) ? (int)$a['operation_minutes'] : 0;
			$minutes_b = isset($b['operation_minutes']) ? (int)$b['operation_minutes'] : 0;

			if ($minutes_a !== $minutes_b) {
				return $minutes_b - $minutes_a;
			}

			$sort_a = isset($a['sort_order']) ? (int)$a['sort_order'] : 0;
			$sort_b = isset($b['sort_order']) ? (int)$b['sort_order'] : 0;

			if ($sort_a !== $sort_b) {
				return $sort_a - $sort_b;
			}

			return (int)$a['table_no'] - (int)$b['table_no'];
		});

		return $rows;
	}

	private function setOperationAge(&$row, $date) {
		$timestamp = strtotime((string)$date);

		if (!$timestamp) {
			return;
		}

		$minutes = max(0, (int)floor((time() - $timestamp) / 60));
		$row['operation_minutes'] = $minutes;
		$row['operation_time'] = $this->formatOperationAge($minutes);
	}

	private function reconcileAllTableStatuses() {
		if (!$this->tableExists(DB_PREFIX . 'restaurant_table') || !$this->tableExists(DB_PREFIX . 'restaurant_table_status') || !$this->tableExists(DB_PREFIX . 'restaurant_order')) {
			return;
		}

		$tables = $this->db->query("SELECT table_id FROM `" . DB_PREFIX . "restaurant_table` WHERE status = '1'")->rows;

		foreach ($tables as $table) {
			$this->syncTableStatusFromOrders((int)$table['table_id']);
		}
	}

	private function syncTableStatusFromOrders($table_id) {
		$table_id = (int)$table_id;
		$payment_join = '';
		$paid_amount_sql = '0';
		$payment_status_sql = '';

		if ($this->tableExists(DB_PREFIX . 'restaurant_payment')) {
			$payment_join = "LEFT JOIN (
				SELECT restaurant_order_id, SUM(amount) AS paid_amount
				FROM `" . DB_PREFIX . "restaurant_payment`
				GROUP BY restaurant_order_id
			) pay ON (pay.restaurant_order_id = ro.restaurant_order_id)";
			$paid_amount_sql = 'COALESCE(pay.paid_amount, 0)';
		}

		if ($this->columnExists(DB_PREFIX . 'restaurant_order', 'payment_status')) {
			$payment_status_sql = "AND (ro.payment_status IS NULL OR ro.payment_status != 'paid')";
		}

		$active = $this->db->query("SELECT COUNT(*) AS active_order_count,
				COALESCE(SUM(GREATEST(ro.total_amount - " . $paid_amount_sql . ", 0)), 0) AS total_amount
			FROM `" . DB_PREFIX . "restaurant_order` ro
			" . $payment_join . "
			WHERE ro.table_id = '" . $table_id . "'
			AND ro.service_status IN ('waiting_order','cashier_draft','in_kitchen','ready_for_service','out_for_service','served','payment_pending')
			" . $payment_status_sql . "
			AND ro.total_amount > " . $paid_amount_sql . "
			AND EXISTS (
				SELECT 1 FROM `" . DB_PREFIX . "restaurant_order_product` rop
				WHERE rop.restaurant_order_id = ro.restaurant_order_id
			)")->row;

		$count = (int)$active['active_order_count'];
		$total = (float)$active['total_amount'];
		$status = 'empty';

		if ($count > 0 && $total > 0.009) {
			$priority = $this->db->query("SELECT ro.service_status
				FROM `" . DB_PREFIX . "restaurant_order` ro
				" . $payment_join . "
				WHERE ro.table_id = '" . $table_id . "'
				AND ro.service_status IN ('payment_pending','waiting_order','cashier_draft','in_kitchen','ready_for_service','out_for_service','served')
				" . $payment_status_sql . "
				AND ro.total_amount > " . $paid_amount_sql . "
				AND EXISTS (
					SELECT 1 FROM `" . DB_PREFIX . "restaurant_order_product` rop
					WHERE rop.restaurant_order_id = ro.restaurant_order_id
				)
				ORDER BY FIELD(ro.service_status, 'payment_pending', 'waiting_order', 'cashier_draft', 'in_kitchen', 'ready_for_service', 'out_for_service', 'served')
				LIMIT 1");

			$status = $priority->num_rows ? $priority->row['service_status'] : 'served';
		}

		$check = $this->db->query("SELECT table_id FROM `" . DB_PREFIX . "restaurant_table_status` WHERE table_id = '" . $table_id . "' LIMIT 1");

		if ($check->num_rows) {
			$this->db->query("UPDATE `" . DB_PREFIX . "restaurant_table_status`
				SET service_status = '" . $this->db->escape($status) . "',
					active_order_count = '" . $count . "',
					total_amount = '" . $total . "',
					date_modified = NOW()
				WHERE table_id = '" . $table_id . "'");
		} else {
			$this->db->query("INSERT INTO `" . DB_PREFIX . "restaurant_table_status`
				SET table_id = '" . $table_id . "',
					service_status = '" . $this->db->escape($status) . "',
					active_order_count = '" . $count . "',
					total_amount = '" . $total . "',
					date_modified = NOW()");
		}
	}

	private function tableExists($table) {
		try {
			$query = $this->db->query("SHOW TABLES LIKE '" . $this->db->escape($table) . "'");

			return $query->num_rows > 0;
		} catch (Exception $e) {
			return false;
		}
	}

	private function columnExists($table, $column) {
		try {
			$query = $this->db->query("SHOW COLUMNS FROM `" . $this->db->escape($table) . "` LIKE '" . $this->db->escape($column) . "'");

			return $query->num_rows > 0;
		} catch (Exception $e) {
			return false;
		}
	}

	private function formatOperationAge($minutes) {
		$minutes = (int)$minutes;

		if ($minutes <= 0) {
			return 'az önce';
		}

		if ($minutes < 60) {
			return $minutes . ' dk önce';
		}

		$hours = (int)floor($minutes / 60);
		$remaining = $minutes % 60;

		if ($remaining <= 0) {
			return $hours . ' sa önce';
		}

		return $hours . ' sa ' . $remaining . ' dk önce';
	}

	public function acknowledgeWaiterCall($table_id, $user_id = 0) {
		$table_id = (int)$table_id;
		$user_id = (int)$user_id;

		if (!$table_id) {
			return false;
		}

		$call_query = $this->db->query("SELECT call_id, status FROM `" . DB_PREFIX . "restaurant_call`
			WHERE table_id = '" . $table_id . "'
			AND call_type = 'waiter_call'
			AND status IN ('new','seen')
			ORDER BY call_id DESC
			LIMIT 1");

		if (!$call_query->num_rows) {
			return false;
		}

		$call_id = (int)$call_query->row['call_id'];

		if ($call_query->row['status'] !== 'seen') {
			$this->db->query("UPDATE `" . DB_PREFIX . "restaurant_call`
				SET status = 'seen', date_modified = NOW()
				WHERE call_id = '" . $call_id . "'");

			$this->db->query("INSERT INTO `" . DB_PREFIX . "restaurant_order_history`
				SET restaurant_order_id = '0',
					old_status = 'waiter_call',
					new_status = 'waiter_call_seen',
					user_id = '" . $user_id . "',
					comment = 'Garson masaya gidiyorum onayı verdi. Call ID: " . $call_id . "',
					date_added = NOW()");
		}

		return true;
	}

	public function acknowledgeBillRequest($table_id, $user_id = 0) {
		$table_id = (int)$table_id;
		$user_id = (int)$user_id;

		if (!$table_id) {
			return false;
		}

		$call_query = $this->db->query("SELECT call_id, status FROM `" . DB_PREFIX . "restaurant_call`
			WHERE table_id = '" . $table_id . "'
			AND call_type = 'bill_request'
			AND status IN ('new','seen')
			ORDER BY call_id DESC
			LIMIT 1");

		if (!$call_query->num_rows) {
			return false;
		}

		$call_id = (int)$call_query->row['call_id'];

		if ($call_query->row['status'] !== 'seen') {
			$this->db->query("UPDATE `" . DB_PREFIX . "restaurant_call`
				SET status = 'seen', date_modified = NOW()
				WHERE call_id = '" . $call_id . "'");

			$this->db->query("INSERT INTO `" . DB_PREFIX . "restaurant_order_history`
				SET restaurant_order_id = '0',
					old_status = 'bill_request',
					new_status = 'bill_request_seen',
					user_id = '" . $user_id . "',
					comment = 'Garson hesap talebini gördü. Call ID: " . $call_id . "',
					date_added = NOW()");
		}

		return true;
	}

	private function closeExpiredWaiterCalls() {
		$minutes = (int)$this->getRestaurantSettingValue('restaurant_waiter_call_reset_minutes', 5);

		if ($minutes < 1) {
			$minutes = 5;
		}

		if ($minutes > 60) {
			$minutes = 60;
		}

		$this->db->query("UPDATE `" . DB_PREFIX . "restaurant_call`
			SET status = 'closed',
				date_modified = NOW()
			WHERE call_type = 'waiter_call'
			AND status = 'seen'
			AND date_modified <= DATE_SUB(NOW(), INTERVAL " . (int)$minutes . " MINUTE)");
	}

	private function closeExpiredBillRequests() {
		$minutes = (int)$this->getRestaurantSettingValue('restaurant_bill_request_reset_minutes', 5);

		if ($minutes < 1) {
			$minutes = 5;
		}

		if ($minutes > 60) {
			$minutes = 60;
		}

		$this->db->query("UPDATE `" . DB_PREFIX . "restaurant_call`
			SET status = 'closed',
				date_modified = NOW()
			WHERE call_type = 'bill_request'
			AND status = 'seen'
			AND date_modified <= DATE_SUB(NOW(), INTERVAL " . (int)$minutes . " MINUTE)");
	}

	public function getSummary($user_id = 0) {
		$tables = $this->getTables($user_id);
		$summary = array(
			'total_tables'  => 0,
			'active_tables' => 0,
			'new_calls'     => 0,
			'paid_count'    => 0
		);

		foreach ($tables as $table) {
			$summary['total_tables']++;

			if (in_array($table['service_status'], array('waiting_order', 'in_kitchen', 'ready_for_service', 'out_for_service', 'served'), true)) {
				$summary['active_tables']++;
			}

			if ($table['service_status'] == 'waiting_order') {
				$summary['new_calls']++;
			}

			if (!empty($table['bill_request_pending']) || (!empty($table['waiter_call_pending']) && $table['latest_waiter_call_status'] !== 'seen')) {
				$summary['new_calls']++;
			}
		}

		if ($this->isRestaurantWaiterUser($user_id)) {
			$table_ids = $this->getAssignedTableIds($user_id);

			if ($table_ids) {
				$paid_query = $this->db->query("SELECT COUNT(*) AS total FROM `" . DB_PREFIX . "restaurant_order`
					WHERE DATE(date_modified) = CURDATE()
					AND service_status = 'paid'
					AND table_id IN (" . implode(',', array_map('intval', $table_ids)) . ")");
			} else {
				$paid_query = (object)array('row' => array('total' => 0));
			}
		} else {
			$paid_query = $this->db->query("SELECT COUNT(*) AS total FROM `" . DB_PREFIX . "restaurant_order`
				WHERE DATE(date_modified) = CURDATE()
				AND service_status = 'paid'");
		}

		$summary['paid_count'] = (int)$paid_query->row['total'];

		return $summary;
	}

	public function getTableOrders($table_id) {
		$table_id = (int)$table_id;

		if (!$table_id) {
			return array();
		}

		$orders = $this->db->query("SELECT ro.*
			FROM `" . DB_PREFIX . "restaurant_order` ro
			WHERE ro.table_id = '" . $table_id . "'
			AND ro.service_status IN ('waiting_order', 'in_kitchen', 'ready_for_service', 'out_for_service', 'served')
			ORDER BY ro.restaurant_order_id DESC")->rows;

		foreach ($orders as $key => $order) {
			$orders[$key]['products'] = $this->getOrderProducts((int)$order['restaurant_order_id']);
		}

		return $orders;
	}

	public function getOrderProducts($restaurant_order_id) {
		$restaurant_order_id = (int)$restaurant_order_id;

		if (!$restaurant_order_id) {
			return array();
		}

		$turkish_language_id = $this->getTurkishLanguageId();

		return $this->db->query("SELECT
				rop.restaurant_order_product_id,
				rop.product_id,
				COALESCE(NULLIF(pd.name, ''), rop.name) AS name,
				rop.price,
				rop.quantity,
				rop.total
			FROM `" . DB_PREFIX . "restaurant_order_product` rop
			LEFT JOIN `" . DB_PREFIX . "product_description` pd
				ON (pd.product_id = rop.product_id AND pd.language_id = '" . (int)$turkish_language_id . "')
			WHERE rop.restaurant_order_id = '" . $restaurant_order_id . "'
			ORDER BY rop.restaurant_order_product_id ASC")->rows;
	}

	public function getOrderProductTableId($restaurant_order_product_id) {
		$restaurant_order_product_id = (int)$restaurant_order_product_id;

		if (!$restaurant_order_product_id) {
			return 0;
		}

		$query = $this->db->query("SELECT ro.table_id
			FROM `" . DB_PREFIX . "restaurant_order_product` rop
			LEFT JOIN `" . DB_PREFIX . "restaurant_order` ro ON (ro.restaurant_order_id = rop.restaurant_order_id)
			WHERE rop.restaurant_order_product_id = '" . $restaurant_order_product_id . "'
			LIMIT 1");

		return $query->num_rows ? (int)$query->row['table_id'] : 0;
	}

	public function removeOrderProduct($restaurant_order_product_id, $user_id = 0) {
		$restaurant_order_product_id = (int)$restaurant_order_product_id;
		$user_id = (int)$user_id;

		if (!$restaurant_order_product_id) {
			return false;
		}

		$query = $this->db->query("SELECT rop.*, ro.table_id, ro.service_status
			FROM `" . DB_PREFIX . "restaurant_order_product` rop
			LEFT JOIN `" . DB_PREFIX . "restaurant_order` ro ON (ro.restaurant_order_id = rop.restaurant_order_id)
			WHERE rop.restaurant_order_product_id = '" . $restaurant_order_product_id . "'
			LIMIT 1");

		if (!$query->num_rows || $query->row['service_status'] !== 'waiting_order') {
			return false;
		}

		$order_id = (int)$query->row['restaurant_order_id'];
		$table_id = (int)$query->row['table_id'];

		$this->db->query("DELETE FROM `" . DB_PREFIX . "restaurant_order_product`
			WHERE restaurant_order_product_id = '" . $restaurant_order_product_id . "'");

		$total_query = $this->db->query("SELECT COUNT(*) AS product_count, COALESCE(SUM(total), 0) AS total_amount
			FROM `" . DB_PREFIX . "restaurant_order_product`
			WHERE restaurant_order_id = '" . $order_id . "'");

		if ((int)$total_query->row['product_count'] <= 0) {
			$this->updateRestaurantOrderStatus($order_id, 'cancelled', $user_id);
		} else {
			$this->db->query("UPDATE `" . DB_PREFIX . "restaurant_order`
				SET total_amount = '" . (float)$total_query->row['total_amount'] . "',
					date_modified = NOW()
				WHERE restaurant_order_id = '" . $order_id . "'");

			$this->db->query("INSERT INTO `" . DB_PREFIX . "restaurant_order_history`
				SET restaurant_order_id = '" . $order_id . "',
					old_status = 'waiting_order',
					new_status = 'waiting_order',
					user_id = '" . $user_id . "',
					comment = 'Garson onay öncesi ürün satırını çıkardı: " . $this->db->escape($query->row['name']) . "',
					date_added = NOW()");

			$this->syncTableStatus($table_id);
		}

		return true;
	}

	public function updateRestaurantOrderStatus($restaurant_order_id, $service_status, $user_id = 0) {
		$restaurant_order_id = (int)$restaurant_order_id;
		$user_id = (int)$user_id;

		$order_query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "restaurant_order` WHERE restaurant_order_id = '" . $restaurant_order_id . "' LIMIT 1");

		if (!$order_query->num_rows) {
			return false;
		}

		$order = $order_query->row;
		$table_id = (int)$order['table_id'];
		$old_status = $order['service_status'];

		if (!$this->isAllowedStatusTransition($old_status, $service_status)) {
			return false;
		}

		if (
			$service_status === 'in_kitchen'
			&& !$this->isRestaurantSettingEnabled('restaurant_kitchen_panel', 1)
			&& !$this->isRestaurantSettingEnabled('restaurant_akinsoft_enabled', 0)
		) {
			return false;
		}

		$is_paid = ($service_status === 'paid') ? 1 : 0;

		$this->db->query("UPDATE `" . DB_PREFIX . "restaurant_order`
			SET service_status = '" . $this->db->escape($service_status) . "',
				is_paid = '" . (int)$is_paid . "',
				date_modified = NOW()
			WHERE restaurant_order_id = '" . $restaurant_order_id . "'
			AND service_status = '" . $this->db->escape($old_status) . "'");

		if (!$this->db->countAffected()) {
			return false;
		}

		if ($service_status === 'in_kitchen' && $this->isRestaurantSettingEnabled('restaurant_akinsoft_enabled', 0)) {
			$this->db->query("UPDATE `" . DB_PREFIX . "restaurant_order`
				SET integration_status = 'pending_export',
					integration_message = 'AKINSOFT bekliyor',
					integration_date = NOW()
				WHERE restaurant_order_id = '" . $restaurant_order_id . "'");
		}

		if ($service_status === 'paid') {
			$this->insertWaiterCardPayment($restaurant_order_id, $table_id, $user_id);

			$this->db->query("UPDATE `" . DB_PREFIX . "restaurant_call`
				SET status = 'closed', date_modified = NOW()
				WHERE table_id = '" . $table_id . "'
				AND call_type IN ('bill_request','waiter_call')
				AND status IN ('new','seen')");

			$this->createReviewInvite($table_id, array($restaurant_order_id));

			$this->db->query("UPDATE `" . DB_PREFIX . "restaurant_table_status`
				SET active_session_token = NULL, date_modified = NOW()
				WHERE table_id = '" . $table_id . "'");
		}

		$this->db->query("INSERT INTO `" . DB_PREFIX . "restaurant_order_history`
			SET restaurant_order_id = '" . $restaurant_order_id . "',
				old_status = " . ($old_status !== null ? "'" . $this->db->escape($old_status) . "'" : "NULL") . ",
				new_status = '" . $this->db->escape($service_status) . "',
				user_id = '" . $user_id . "',
				comment = '',
				date_added = NOW()");

		$this->syncTableStatus($table_id);

		return true;
	}

	public function canWaiterCloseCardPayment($restaurant_order_id) {
		$order = $this->db->query("SELECT table_id
			FROM `" . DB_PREFIX . "restaurant_order`
			WHERE restaurant_order_id = '" . (int)$restaurant_order_id . "'
			LIMIT 1");

		if (!$order->num_rows) {
			return false;
		}

		$note = $this->getLatestBillRequestNote((int)$order->row['table_id']);
		$note = function_exists('mb_strtolower') ? mb_strtolower($note, 'UTF-8') : strtolower($note);

		return (strpos($note, 'kart') !== false || strpos($note, 'card') !== false || strpos($note, 'pos') !== false || strpos($note, 'kredi') !== false);
	}

	private function getLatestBillRequestNote($table_id) {
		$query = $this->db->query("SELECT note
			FROM `" . DB_PREFIX . "restaurant_call`
			WHERE table_id = '" . (int)$table_id . "'
			AND call_type = 'bill_request'
			AND status IN ('new','seen')
			ORDER BY call_id DESC
			LIMIT 1");

		return $query->num_rows ? trim((string)$query->row['note']) : '';
	}

	private function createReviewInvite($table_id, $order_ids) {
		$table_id = (int)$table_id;

		if (!$table_id || empty($order_ids)) {
			return;
		}

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

		$status = $this->db->query("SELECT active_session_token
			FROM `" . DB_PREFIX . "restaurant_table_status`
			WHERE table_id = '" . $table_id . "'
			LIMIT 1");

		if (!$status->num_rows || trim((string)$status->row['active_session_token']) === '') {
			return;
		}

		$waiter_user_id = 0;
		$waiter_name = '';
		$clean_order_ids = array();

		foreach ($order_ids as $order_id) {
			$order_id = (int)$order_id;

			if (!$order_id) {
				continue;
			}

			$clean_order_ids[] = $order_id;

			if (!$waiter_user_id) {
				$order_user = $this->db->query("SELECT ro.waiter_user_id, u.firstname, u.lastname, u.username
					FROM `" . DB_PREFIX . "restaurant_order` ro
					LEFT JOIN `" . DB_PREFIX . "user` u ON (u.user_id = ro.waiter_user_id)
					WHERE ro.restaurant_order_id = '" . $order_id . "'
					LIMIT 1");

				if ($order_user->num_rows && (int)$order_user->row['waiter_user_id'] > 0) {
					$waiter_user_id = (int)$order_user->row['waiter_user_id'];
					$waiter_name = trim($order_user->row['firstname'] . ' ' . $order_user->row['lastname']);
					if ($waiter_name === '') {
						$waiter_name = (string)$order_user->row['username'];
					}
				}
			}
		}

		if (!$clean_order_ids) {
			return;
		}

		$this->db->query("INSERT INTO `" . DB_PREFIX . "restaurant_review_invite`
			SET table_id = '" . $table_id . "',
				session_token = '" . $this->db->escape((string)$status->row['active_session_token']) . "',
				restaurant_order_ids = '" . $this->db->escape(implode(',', $clean_order_ids)) . "',
				waiter_user_id = '" . (int)$waiter_user_id . "',
				waiter_name = '" . $this->db->escape($waiter_name) . "',
				date_added = NOW()");
	}

	private function insertWaiterCardPayment($restaurant_order_id, $table_id, $user_id) {
		if (!$this->canWaiterCloseCardPayment($restaurant_order_id)) {
			return;
		}

		$existing = $this->db->query("SELECT COUNT(*) AS total
			FROM `" . DB_PREFIX . "restaurant_payment`
			WHERE restaurant_order_id = '" . (int)$restaurant_order_id . "'");

		if ((int)$existing->row['total'] > 0) {
			return;
		}

		$order = $this->db->query("SELECT total_amount
			FROM `" . DB_PREFIX . "restaurant_order`
			WHERE restaurant_order_id = '" . (int)$restaurant_order_id . "'
			LIMIT 1");

		if (!$order->num_rows || (float)$order->row['total_amount'] <= 0) {
			return;
		}

		$this->db->query("INSERT INTO `" . DB_PREFIX . "restaurant_payment`
			SET restaurant_order_id = '" . (int)$restaurant_order_id . "',
				table_id = '" . (int)$table_id . "',
				amount = '" . (float)$order->row['total_amount'] . "',
				payment_method = 'card',
				source = 'waiter_panel',
				user_id = '" . (int)$user_id . "',
				note = 'Garson POS tahsilatı',
				date_added = NOW()");

		$this->db->query("UPDATE `" . DB_PREFIX . "restaurant_order`
			SET payment_status = 'paid',
				payment_type = 'card',
				payment_total = total_amount,
				paid_at = NOW(),
				cashier_user_id = '" . (int)$user_id . "',
				locked = '1'
			WHERE restaurant_order_id = '" . (int)$restaurant_order_id . "'");
	}

	private function isAllowedStatusTransition($old_status, $new_status) {
		$old_status = (string)$old_status;
		$new_status = (string)$new_status;

		if ($old_status === $new_status) {
			return false;
		}

		$transitions = array(
			'waiting_order' => array('in_kitchen', 'served', 'cancelled'),
			'in_kitchen' => array('ready_for_service', 'cancelled'),
			'ready_for_service' => array('out_for_service', 'served', 'cancelled'),
			'out_for_service' => array('served', 'cancelled'),
			'served' => array('paid'),
			'paid' => array(),
			'cancelled' => array()
		);

		if (
			$old_status === 'in_kitchen'
			&& !$this->isRestaurantSettingEnabled('restaurant_kitchen_panel', 1)
			&& $this->isRestaurantSettingEnabled('restaurant_akinsoft_enabled', 0)
		) {
			$transitions['in_kitchen'][] = 'served';
		}

		if (!isset($transitions[$old_status])) {
			return false;
		}

		return in_array($new_status, $transitions[$old_status], true);
	}

	public function syncTableStatus($table_id) {
		$table_id = (int)$table_id;

		if (!$table_id) {
			return false;
		}

		$active_query = $this->db->query("SELECT COUNT(*) AS active_order_count, COALESCE(SUM(total_amount), 0) AS total_amount
			FROM `" . DB_PREFIX . "restaurant_order`
			WHERE table_id = '" . $table_id . "'
			AND service_status IN ('waiting_order','in_kitchen','ready_for_service','out_for_service','served')");

		$active_order_count = (int)$active_query->row['active_order_count'];
		$total_amount = (float)$active_query->row['total_amount'];

		$latest_query = $this->db->query("SELECT service_status FROM `" . DB_PREFIX . "restaurant_order`
			WHERE table_id = '" . $table_id . "'
			AND service_status IN ('waiting_order','in_kitchen','ready_for_service','out_for_service','served')
			ORDER BY restaurant_order_id DESC
			LIMIT 1");

		$table_status = $latest_query->num_rows ? $latest_query->row['service_status'] : 'empty';

		if (!$latest_query->num_rows) {
			$active_order_count = 0;
			$total_amount = 0.0000;
		}

		$check = $this->db->query("SELECT table_id FROM `" . DB_PREFIX . "restaurant_table_status` WHERE table_id = '" . $table_id . "'");

		if ($check->num_rows) {
			$this->db->query("UPDATE `" . DB_PREFIX . "restaurant_table_status`
				SET service_status = '" . $this->db->escape($table_status) . "',
					active_order_count = '" . (int)$active_order_count . "',
					total_amount = '" . (float)$total_amount . "',
					date_modified = NOW()
				WHERE table_id = '" . $table_id . "'");
		} else {
			$this->db->query("INSERT INTO `" . DB_PREFIX . "restaurant_table_status`
				SET table_id = '" . $table_id . "',
					service_status = '" . $this->db->escape($table_status) . "',
					active_order_count = '" . (int)$active_order_count . "',
					total_amount = '" . (float)$total_amount . "',
					date_modified = NOW()");
		}

		return true;
	}

	public function saveTableNote($table_id, $note = '') {
		$table_id = (int)$table_id;

		$check = $this->db->query("SELECT table_id FROM `" . DB_PREFIX . "restaurant_table_status` WHERE table_id = '" . $table_id . "'");

		if ($check->num_rows) {
			$this->db->query("UPDATE `" . DB_PREFIX . "restaurant_table_status`
				SET note = '" . $this->db->escape($note) . "', date_modified = NOW()
				WHERE table_id = '" . $table_id . "'");
		} else {
			$this->db->query("INSERT INTO `" . DB_PREFIX . "restaurant_table_status`
				SET table_id = '" . $table_id . "',
					service_status = 'empty',
					active_order_count = 0,
					total_amount = 0.0000,
					note = '" . $this->db->escape($note) . "',
					date_modified = NOW()");
		}
	}

	public function createRestaurantOrder($table_id, $products = array(), $customer_note = '', $waiter_user_id = 0) {
		return $this->createManualOrder($table_id, $products, $waiter_user_id, $customer_note);
	}

	public function getOrdersForAkinsoftExport() {
		$orders = $this->db->query("SELECT ro.*, rt.table_no, rt.name AS table_name
			FROM `" . DB_PREFIX . "restaurant_order` ro
			LEFT JOIN `" . DB_PREFIX . "restaurant_table` rt ON (ro.table_id = rt.table_id)
			WHERE ro.service_status = 'in_kitchen'
			AND ro.integration_status IN ('pending_export', 'failed')
			ORDER BY ro.restaurant_order_id ASC")->rows;

		foreach ($orders as $key => $order) {
			$orders[$key]['products'] = $this->getOrderProducts($order['restaurant_order_id']);
		}

		return $orders;
	}

	public function markOrderAsExported($restaurant_order_id, $external_order_no = '', $message = '') {
		$restaurant_order_id = (int)$restaurant_order_id;

		if (!$restaurant_order_id) {
			return false;
		}

		$this->db->query("UPDATE `" . DB_PREFIX . "restaurant_order`
			SET integration_status = 'sent',
				external_order_no = '" . $this->db->escape($external_order_no) . "',
				integration_message = '" . $this->db->escape($message) . "',
				integration_date = NOW()
			WHERE restaurant_order_id = '" . $restaurant_order_id . "'");

		return true;
	}

	public function sendToKitchenIntegration($restaurant_order_id) {
		$restaurant_order_id = (int)$restaurant_order_id;

		if (!$restaurant_order_id) {
			return false;
		}

		$this->db->query("UPDATE `" . DB_PREFIX . "restaurant_order`
			SET integration_status = 'sent',
				integration_message = 'Log yazıldı (simülasyon)',
				integration_date = NOW()
			WHERE restaurant_order_id = '" . $restaurant_order_id . "'");

		return true;
	}

	public function searchProducts($keyword) {
		$keyword = trim((string)$keyword);

		if (utf8_strlen($keyword) < 2) {
			return array();
		}

		$language_id = $this->getTurkishLanguageId();
		$rows = $this->db->query("SELECT p.product_id, p.price, p.tax_class_id, pd.name
			FROM `" . DB_PREFIX . "product` p
			LEFT JOIN `" . DB_PREFIX . "product_description` pd ON (p.product_id = pd.product_id)
			WHERE pd.language_id = '" . $language_id . "'
			AND p.status = '1'
			AND pd.name LIKE '%" . $this->db->escape($keyword) . "%'
			ORDER BY pd.name ASC
			LIMIT 20")->rows;

		$products = array();

		foreach ($rows as $row) {
			$products[] = array(
				'product_id' => (int)$row['product_id'],
				'name'       => $row['name'],
				'price_raw'  => (float)$row['price'],
				'price'      => $this->currency->format(
					$this->tax->calculate((float)$row['price'], (int)$row['tax_class_id'], $this->config->get('config_tax')),
					$this->session->data['currency']
				)
			);
		}

		return $products;
	}

	public function createManualOrder($table_id, $products, $user_id = 0, $customer_note = 'Garson ilave siparişi') {
		$table_id = (int)$table_id;
		$user_id = (int)$user_id;

		if (!$table_id || !$products) {
			return 0;
		}

		$total = 0;
		$product_rows = array();

		foreach ($products as $item) {
			$product_id = isset($item['product_id']) ? (int)$item['product_id'] : 0;
			$qty = isset($item['quantity']) ? (int)$item['quantity'] : 0;

			if ($product_id <= 0 || $qty <= 0) {
				continue;
			}

			$q = $this->db->query("SELECT p.price, pd.name
				FROM `" . DB_PREFIX . "product` p
				LEFT JOIN `" . DB_PREFIX . "product_description` pd ON (p.product_id = pd.product_id)
				WHERE p.product_id = '" . $product_id . "'
				AND pd.language_id = '" . (int)$this->getTurkishLanguageId() . "'
				LIMIT 1");

			if (!$q->num_rows) {
				continue;
			}

			$price = (float)$q->row['price'];
			$row_total = $price * $qty;
			$total += $row_total;

			$product_rows[] = array(
				'product_id' => $product_id,
				'name'       => $q->row['name'],
				'price'      => $price,
				'quantity'   => $qty,
				'total'      => $row_total
			);
		}

		if (!$product_rows) {
			return 0;
		}

		$service_status = (
			$this->isRestaurantSettingEnabled('restaurant_kitchen_panel', 1)
			|| $this->isRestaurantSettingEnabled('restaurant_akinsoft_enabled', 0)
		) ? 'in_kitchen' : 'served';

		$this->db->query("INSERT INTO `" . DB_PREFIX . "restaurant_order`
			SET table_id = '" . $table_id . "',
				waiter_user_id = '" . $user_id . "',
				service_status = '" . $this->db->escape($service_status) . "',
				customer_note = '" . $this->db->escape($customer_note) . "',
				total_amount = '" . (float)$total . "',
				is_paid = '0',
				date_added = NOW(),
				date_modified = NOW()");

		$order_id = (int)$this->db->getLastId();

		if ($service_status === 'in_kitchen' && $this->isRestaurantSettingEnabled('restaurant_akinsoft_enabled', 0)) {
			$this->db->query("UPDATE `" . DB_PREFIX . "restaurant_order`
				SET integration_status = 'pending_export',
					integration_message = 'AKINSOFT bekliyor',
					integration_date = NOW()
				WHERE restaurant_order_id = '" . $order_id . "'");
		}

		foreach ($product_rows as $row) {
			$this->db->query("INSERT INTO `" . DB_PREFIX . "restaurant_order_product`
				SET restaurant_order_id = '" . $order_id . "',
					product_id = '" . (int)$row['product_id'] . "',
					name = '" . $this->db->escape($row['name']) . "',
					price = '" . (float)$row['price'] . "',
					quantity = '" . (int)$row['quantity'] . "',
					total = '" . (float)$row['total'] . "'");
		}

		$this->db->query("INSERT INTO `" . DB_PREFIX . "restaurant_order_history`
			SET restaurant_order_id = '" . $order_id . "',
				old_status = NULL,
				new_status = '" . $this->db->escape($service_status) . "',
				user_id = '" . $user_id . "',
				comment = 'İlave sipariş mutfağa gönderildi.',
				date_added = NOW()");

		$this->syncTableStatus($table_id);

		return $order_id;
	}

	private function isRestaurantSettingEnabled($key, $default = 1) {
		return (int)$this->getRestaurantSettingValue($key, $default) === 1;
	}

	private function getRestaurantSettingValue($key, $default = 1) {
		$table = DB_PREFIX . 'ayarlar';
		$exists = $this->db->query("SHOW TABLES LIKE '" . $this->db->escape($table) . "'");

		if (!$exists->num_rows) {
			return $default;
		}

		$query = $this->db->query("SELECT ayar_value FROM `" . $table . "`
			WHERE ayar_key = '" . $this->db->escape($key) . "'
			LIMIT 1");

		if (!$query->num_rows || $query->row['ayar_value'] === '') {
			return $default;
		}

		return $query->row['ayar_value'];
	}

	private function getTurkishLanguageId() {
		$query = $this->db->query("SELECT language_id FROM `" . DB_PREFIX . "language`
			WHERE code = 'tr-tr'
			LIMIT 1");

		if ($query->num_rows) {
			return (int)$query->row['language_id'];
		}

		return (int)$this->config->get('config_language_id');
	}
}
