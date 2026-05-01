<?php
class ModelCommonRestaurantSettings extends Model {
	public function get($key, $default = '') {
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

	public function getOccupancyLoad() {
		if (!$this->tableExists(DB_PREFIX . 'restaurant_table') || !$this->tableExists(DB_PREFIX . 'restaurant_table_status')) {
			return array('level' => 'calm', 'percent' => 0, 'open_count' => 0, 'total_count' => 0);
		}

		$total = $this->db->query("SELECT COUNT(*) AS total FROM `" . DB_PREFIX . "restaurant_table` WHERE status = '1'")->row;
		$has_order_table = $this->tableExists(DB_PREFIX . 'restaurant_order');
		$has_payment_status = $has_order_table && $this->columnExists(DB_PREFIX . 'restaurant_order', 'payment_status');
		$has_active_order_count = $this->columnExists(DB_PREFIX . 'restaurant_table_status', 'active_order_count');

		$payment_condition = $has_payment_status ? "AND (ro.payment_status IS NULL OR ro.payment_status != 'paid')" : "";
		$active_order_condition = $has_active_order_count ? "COALESCE(rts.active_order_count, 0) > 0 OR " : "";

		$order_join = $has_order_table
			? "LEFT JOIN `" . DB_PREFIX . "restaurant_order` ro ON (ro.table_id = rt.table_id
				AND ro.service_status IN ('waiting_order','in_kitchen','ready_for_service','out_for_service','served','payment_pending','cashier_draft')
				" . $payment_condition . ")"
			: "";
		$order_condition = $has_order_table ? " OR ro.restaurant_order_id IS NOT NULL" : "";
		$open = $this->db->query("SELECT COUNT(DISTINCT rt.table_id) AS total
			FROM `" . DB_PREFIX . "restaurant_table` rt
			LEFT JOIN `" . DB_PREFIX . "restaurant_table_status` rts ON (rts.table_id = rt.table_id)
			" . $order_join . "
			WHERE rt.status = '1'
			AND (
				" . $active_order_condition . "
				COALESCE(rts.service_status, 'empty') NOT IN ('', 'empty', 'paid', 'completed', 'cancelled')
				" . $order_condition . "
			)")->row;

		$total_count = max(0, (int)$total['total']);
		$open_count = max(0, (int)$open['total']);
		$percent = $total_count > 0 ? round(($open_count / $total_count) * 100, 1) : 0;
		$level = 'calm';

		if ($percent > 60) {
			$level = 'high';
		} elseif ($percent > 30) {
			$level = 'medium';
		}

		return array(
			'level' => $level,
			'percent' => $percent,
			'open_count' => $open_count,
			'total_count' => $total_count
		);
	}

	public function getPreparationExtraMinutes() {
		$load = $this->getOccupancyLoad();

		if ($load['level'] === 'high') {
			return max(0, (int)$this->get('restaurant_high_density_prep_extra_minutes', 20));
		}

		if ($load['level'] === 'medium') {
			return max(0, (int)$this->get('restaurant_medium_density_prep_extra_minutes', 10));
		}

		return 0;
	}

	public function adjustPreparationTag($tag, $extra_minutes = null) {
		$tag = (string)$tag;
		$tag = trim($tag);

		if ($tag === '') {
			return $tag;
		}

		$tag = html_entity_decode($tag, ENT_QUOTES, 'UTF-8');
		$tag = str_replace(array('–', '—'), '-', $tag);
		$tag = preg_replace('/\b(dakika|minute|min|dk)\b\.?/iu', '', $tag);
		$tag = trim(preg_replace('/\s+/', '', $tag));

		if ($tag === '') {
			return $tag;
		}

		if ($extra_minutes === null) {
			$extra_minutes = $this->getPreparationExtraMinutes();
		}

		$extra_minutes = max(0, (int)$extra_minutes);

		if ($extra_minutes > 0) {
			$adjusted = preg_replace_callback('/\d+/', function($matches) use ($extra_minutes) {
				return (string)((int)$matches[0] + $extra_minutes);
			}, $tag);

			$tag = $adjusted !== null ? $adjusted : $tag;
		}

		$suffix = $this->config->get('config_language') == 'tr-tr' ? ' Dakika' : ' min';

		return $tag . $suffix;
	}

	public function cleanProductDescriptionHtml($html) {
		$html = (string)$html;

		for ($i = 0; $i < 12; $i++) {
			$decoded = html_entity_decode($html, ENT_QUOTES, 'UTF-8');

			if ($decoded === $html) {
				break;
			}

			$html = $decoded;
		}

		$html = preg_replace('#<(script|style|iframe|object|embed)[^>]*>.*?</\1>#is', '', $html);
		$html = strip_tags($html, '<p><br><strong><b><em><i><u><span><div><ul><ol><li>');
		$html = preg_replace('/\s+on[a-z]+\s*=\s*(".*?"|\'.*?\'|[^\s>]+)/i', '', $html);
		$html = preg_replace('/\s+(href|src)\s*=\s*("|\')?\s*javascript:[^"\'>\s]+("|\')?/i', '', $html);
		$html = preg_replace('/\s+style\s*=\s*("|\')[^"\']*text-align\s*:\s*justify[^"\']*("|\')/i', '', $html);

		return trim($html);
	}

	private function tableExists($table) {
		$query = $this->db->query("SHOW TABLES LIKE '" . $this->db->escape($table) . "'");
		return $query->num_rows > 0;
	}

	private function columnExists($table, $column) {
		$query = $this->db->query("SHOW COLUMNS FROM `" . $this->db->escape($table) . "` LIKE '" . $this->db->escape($column) . "'");
		return $query->num_rows > 0;
	}
}
