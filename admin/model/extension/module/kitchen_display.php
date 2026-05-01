<?php
class ModelExtensionModuleKitchenDisplay extends Model {
	public function getKitchenOrders() {
		$sql = "SELECT 
					ro.restaurant_order_id,
					ro.table_id,
					ro.waiter_user_id,
					ro.service_status,
					ro.customer_note,
					ro.total_amount,
					ro.date_added,
					ro.date_modified,
					rt.table_no,
					rt.name AS table_name,
					rt.area
				FROM `" . DB_PREFIX . "restaurant_order` ro
				LEFT JOIN `" . DB_PREFIX . "restaurant_table` rt ON (ro.table_id = rt.table_id)
				WHERE ro.service_status IN (
'in_kitchen',
'ready_for_service',
'out_for_service'
)
				ORDER BY 
					CASE 
 WHEN ro.service_status='in_kitchen' THEN 1
 WHEN ro.service_status='ready_for_service' THEN 2
 WHEN ro.service_status='out_for_service' THEN 3
 ELSE 9
END ASC,
					ro.date_modified ASC,
					ro.restaurant_order_id ASC";

		$orders = $this->db->query($sql)->rows;
		$prep_extra_minutes = $this->getPreparationExtraMinutes();

		foreach ($orders as $key => $order) {
			$products = $this->getOrderProducts((int)$order['restaurant_order_id']);
			$prep_minutes = $this->getOrderPrepMinutes($products);

			if ($prep_minutes > 0 && $prep_extra_minutes > 0) {
				$prep_minutes += $prep_extra_minutes;
			}

			$orders[$key]['products'] = $products;
			$orders[$key]['categories'] = $this->getOrderCategories($products);
			$orders[$key]['category_label'] = implode(', ', $orders[$key]['categories']);
			$orders[$key]['elapsed_minutes'] = $this->getElapsedMinutes($order['date_modified']);
			$orders[$key]['status_label'] = $this->getStatusLabel($order['service_status']);
			$orders[$key]['time_label'] = date('H:i', strtotime($order['date_added']));
			$orders[$key]['prep_minutes'] = $prep_minutes;
			$orders[$key]['prep_label'] = $prep_minutes ? $prep_minutes . ' dk' : '';
			$orders[$key]['prep_deadline_ts'] = $this->getPrepDeadlineTs($order, $prep_minutes);
			$orders[$key]['prep_state'] = 'none';
			$orders[$key]['prep_state_label'] = '';
			$orders[$key]['prep_remaining_seconds'] = 0;
			$orders[$key]['prep_overdue_minutes'] = 0;
			$orders[$key]['kitchen_rank'] = $this->getKitchenRank($orders[$key]);
		}

		foreach ($orders as $key => $order) {
			$this->applyPrepState($orders[$key]);
		}

		usort($orders, function($a, $b) {
			$rank_a = isset($a['kitchen_rank']) ? (int)$a['kitchen_rank'] : 0;
			$rank_b = isset($b['kitchen_rank']) ? (int)$b['kitchen_rank'] : 0;

			if ($rank_a !== $rank_b) {
				return $rank_b - $rank_a;
			}

			$remaining_a = isset($a['prep_remaining_seconds']) ? (int)$a['prep_remaining_seconds'] : 999999;
			$remaining_b = isset($b['prep_remaining_seconds']) ? (int)$b['prep_remaining_seconds'] : 999999;

			if ($remaining_a !== $remaining_b) {
				return $remaining_a - $remaining_b;
			}

			return (int)$a['restaurant_order_id'] - (int)$b['restaurant_order_id'];
		});

		return $orders;
	}

	public function getOrderProducts($restaurant_order_id) {
		$restaurant_order_id = (int)$restaurant_order_id;

		if (!$restaurant_order_id) {
			return array();
		}

		$turkish_language_id = $this->getTurkishLanguageId();

		$sql = "SELECT 
					rop.product_id,
					COALESCE(NULLIF(pd.name, ''), rop.name) AS name,
					rop.price,
					rop.quantity,
					rop.total,
					IFNULL(pd.tag, '') AS prep_tag,
					(
						SELECT GROUP_CONCAT(cd.name ORDER BY c.sort_order ASC SEPARATOR ', ')
						FROM `" . DB_PREFIX . "product_to_category` p2c
						LEFT JOIN `" . DB_PREFIX . "category` c ON (c.category_id = p2c.category_id)
						LEFT JOIN `" . DB_PREFIX . "category_description` cd ON (cd.category_id = p2c.category_id AND cd.language_id = '" . (int)$turkish_language_id . "')
						WHERE p2c.product_id = rop.product_id
					) AS category_names
				FROM `" . DB_PREFIX . "restaurant_order_product` rop
				LEFT JOIN `" . DB_PREFIX . "product_description` pd
					ON (rop.product_id = pd.product_id AND pd.language_id = '" . (int)$turkish_language_id . "')
				WHERE rop.restaurant_order_id = '" . $restaurant_order_id . "'
				ORDER BY rop.restaurant_order_product_id ASC";

		$rows = $this->db->query($sql)->rows;

		foreach ($rows as &$row) {
			$row['prep_minutes'] = $this->parsePrepMinutes($row['prep_tag']);
			$row['categories'] = array();

			if (!empty($row['category_names'])) {
				foreach (explode(',', $row['category_names']) as $category_name) {
					$category_name = trim($category_name);

					if ($category_name !== '') {
						$row['categories'][] = $category_name;
					}
				}
			}
		}

		return $rows;
	}

	protected function getOrderCategories($products) {
		$categories = array();

		foreach ($products as $product) {
			if (!empty($product['categories'])) {
				foreach ($product['categories'] as $category_name) {
					$categories[$category_name] = $category_name;
				}
			}
		}

		return array_values($categories);
	}

	public function updateKitchenOrderStatus($restaurant_order_id, $service_status, $user_id = 0) {
		$restaurant_order_id = (int)$restaurant_order_id;
		$user_id = (int)$user_id;

		if (!$restaurant_order_id) {
			return false;
		}

		if (!in_array($service_status, array(
'in_kitchen',
'ready_for_service',
'out_for_service'
))) {
			return false;
		}

		$order_query = $this->db->query("SELECT *
			FROM `" . DB_PREFIX . "restaurant_order`
			WHERE restaurant_order_id = '" . $restaurant_order_id . "'
			LIMIT 1");

		if (!$order_query->num_rows) {
			return false;
		}

		$order = $order_query->row;
		$old_status = $order['service_status'];
		$table_id = (int)$order['table_id'];

		$this->db->query("UPDATE `" . DB_PREFIX . "restaurant_order`
			SET service_status = '" . $this->db->escape($service_status) . "',
				date_modified = NOW()
			WHERE restaurant_order_id = '" . $restaurant_order_id . "'");

		// History kaydı varsa yazalım; hata oluşursa ana akışı bozmasın.
		try {
			$this->db->query("INSERT INTO `" . DB_PREFIX . "restaurant_order_history`
				SET restaurant_order_id = '" . $restaurant_order_id . "',
					old_status = '" . $this->db->escape($old_status) . "',
					new_status = '" . $this->db->escape($service_status) . "',
					user_id = '" . $user_id . "',
					comment = 'Mutfak ekranı üzerinden güncellendi.',
					date_added = NOW()");
		} catch (Exception $e) {
			// Sessiz geç.
		}

		$this->syncTableStatus($table_id);

		return true;
	}

	public function syncTableStatus($table_id) {
		$table_id = (int)$table_id;

		if (!$table_id) {
			return false;
		}

		$active_query = $this->db->query("SELECT 
				COUNT(*) AS active_order_count,
				COALESCE(SUM(total_amount), 0) AS total_amount
			FROM `" . DB_PREFIX . "restaurant_order`
			WHERE table_id = '" . $table_id . "'
			AND service_status IN ('waiting_order','in_kitchen','ready_for_service','out_for_service','served')");

		$active_order_count = (int)$active_query->row['active_order_count'];
		$total_amount = (float)$active_query->row['total_amount'];

		$latest_query = $this->db->query("SELECT service_status
			FROM `" . DB_PREFIX . "restaurant_order`
			WHERE table_id = '" . $table_id . "'
			AND service_status IN ('waiting_order','in_kitchen','ready_for_service','out_for_service','served')
			ORDER BY restaurant_order_id DESC
			LIMIT 1");

		if ($latest_query->num_rows) {
			$table_status = $latest_query->row['service_status'];
		} else {
			$table_status = 'empty';
			$active_order_count = 0;
			$total_amount = 0.0000;
		}

		$check = $this->db->query("SELECT table_id 
			FROM `" . DB_PREFIX . "restaurant_table_status` 
			WHERE table_id = '" . $table_id . "'");

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

	protected function getElapsedMinutes($date_modified) {
		if (!$date_modified || $date_modified == '0000-00-00 00:00:00') {
			return 0;
		}

		$time = strtotime($date_modified);

		if (!$time) {
			return 0;
		}

		return max(0, floor((time() - $time) / 60));
	}

	protected function getStatusLabel($status) {
		$map = array(
'in_kitchen' => 'Hazırlanıyor',
'ready_for_service' => 'Garsona Bildirildi',
'out_for_service' => 'Garson Teslim Aldı'
);

		return isset($map[$status]) ? $map[$status] : $status;
	}

	protected function getOrderPrepMinutes($products) {
		$minutes = 0;

		foreach ($products as $product) {
			$minutes = max($minutes, isset($product['prep_minutes']) ? (int)$product['prep_minutes'] : 0);
		}

		return $minutes;
	}

	protected function getPreparationExtraMinutes() {
		$total = $this->db->query("SELECT COUNT(*) AS total FROM `" . DB_PREFIX . "restaurant_table` WHERE status = '1'")->row;
		$open = $this->db->query("SELECT COUNT(DISTINCT rt.table_id) AS total
			FROM `" . DB_PREFIX . "restaurant_table` rt
			LEFT JOIN `" . DB_PREFIX . "restaurant_table_status` rts ON (rts.table_id = rt.table_id)
			LEFT JOIN `" . DB_PREFIX . "restaurant_order` ro ON (ro.table_id = rt.table_id
				AND ro.service_status IN ('waiting_order','in_kitchen','ready_for_service','out_for_service','served','payment_pending','cashier_draft')
				AND (ro.payment_status IS NULL OR ro.payment_status != 'paid'))
			WHERE rt.status = '1'
			AND (
				COALESCE(rts.active_order_count, 0) > 0
				OR COALESCE(rts.service_status, 'empty') NOT IN ('', 'empty', 'paid', 'completed', 'cancelled')
				OR ro.restaurant_order_id IS NOT NULL
			)")->row;

		$total_count = max(0, (int)$total['total']);
		$open_count = max(0, (int)$open['total']);

		if ($total_count <= 0) {
			return 0;
		}

		$percent = ($open_count / $total_count) * 100;
		$this->load->model('extension/module/restaurant_settings');
		$settings = $this->model_extension_module_restaurant_settings->getSettings();

		if ($percent > 60) {
			return max(0, (int)$settings['restaurant_high_density_prep_extra_minutes']);
		}

		if ($percent > 30) {
			return max(0, (int)$settings['restaurant_medium_density_prep_extra_minutes']);
		}

		return 0;
	}

	protected function parsePrepMinutes($text) {
		$text = (string)$text;

		if ($text === '') {
			return 0;
		}

		preg_match_all('/\d+/', $text, $matches);

		if (empty($matches[0])) {
			return 0;
		}

		$numbers = array_map('intval', $matches[0]);

		return max($numbers);
	}

	protected function getPrepDeadlineTs($order, $prep_minutes) {
		$prep_minutes = (int)$prep_minutes;

		if ($prep_minutes <= 0 || empty($order['date_modified']) || $order['date_modified'] == '0000-00-00 00:00:00') {
			return 0;
		}

		if (!in_array($order['service_status'], array('in_kitchen', 'ready_for_service', 'out_for_service'), true)) {
			return 0;
		}

		$start = strtotime($order['date_modified']);

		if (!$start) {
			return 0;
		}

		return $start + ($prep_minutes * 60);
	}

	protected function applyPrepState(&$order) {
		if ($order['service_status'] !== 'in_kitchen' || empty($order['prep_deadline_ts'])) {
			if ($order['service_status'] === 'ready_for_service') {
				$order['prep_state'] = 'ready';
				$order['prep_state_label'] = 'Servis bekliyor';
				$order['kitchen_rank'] = max((int)$order['kitchen_rank'], 60);
			} elseif ($order['service_status'] === 'out_for_service') {
				$order['prep_state'] = 'out';
				$order['prep_state_label'] = 'Teslimde';
			}

			return;
		}

		$remaining = ((int)$order['prep_deadline_ts'] - time());
		$order['prep_remaining_seconds'] = $remaining;

		if ($remaining < 0) {
			$minutes = max(1, (int)ceil(abs($remaining) / 60));
			$order['prep_state'] = 'late';
			$order['prep_state_label'] = 'Gecikti ' . $minutes . ' dk';
			$order['prep_overdue_minutes'] = $minutes;
			$order['kitchen_rank'] = 100 + $minutes;
		} elseif ($remaining <= 180) {
			$order['prep_state'] = 'warning';
			$order['prep_state_label'] = 'Süre bitiyor';
			$order['kitchen_rank'] = 90;
		} else {
			$order['prep_state'] = 'normal';
			$order['prep_state_label'] = 'Zamanında';
			$order['kitchen_rank'] = max((int)$order['kitchen_rank'], 70);
		}
	}

	protected function getKitchenRank($order) {
		if ($order['service_status'] === 'in_kitchen') {
			return 70;
		}

		if ($order['service_status'] === 'ready_for_service') {
			return 60;
		}

		if ($order['service_status'] === 'out_for_service') {
			return 30;
		}

		return 0;
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
