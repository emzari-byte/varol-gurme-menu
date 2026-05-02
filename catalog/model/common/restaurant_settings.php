<?php
class ModelCommonRestaurantSettings extends Model {
	private $settings_table_exists = null;
	private $settings_cache = array();
	private $table_exists_cache = array();
	private $column_exists_cache = array();

	public function get($key, $default = '') {
		$table = DB_PREFIX . 'ayarlar';

		if ($this->settings_table_exists === null) {
			$exists = $this->db->query("SHOW TABLES LIKE '" . $this->db->escape($table) . "'");
			$this->settings_table_exists = $exists->num_rows > 0;
		}

		if (!$this->settings_table_exists) {
			return $default;
		}

		if (array_key_exists($key, $this->settings_cache)) {
			$value = $this->settings_cache[$key];
		} else {
			$query = $this->db->query("SELECT ayar_value FROM `" . $table . "`
				WHERE ayar_key = '" . $this->db->escape($key) . "'
				LIMIT 1");

			$value = $query->num_rows ? $query->row['ayar_value'] : null;
			$this->settings_cache[$key] = $value;
		}

		if ($value === null || $value === '') {
			return $default;
		}

		if ($key === 'restaurant_analytics_code') {
			$value = $this->normalizeAnalyticsCode($value);
		}

		return $value;
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
		if (array_key_exists($table, $this->table_exists_cache)) {
			return $this->table_exists_cache[$table];
		}

		$query = $this->db->query("SHOW TABLES LIKE '" . $this->db->escape($table) . "'");
		$this->table_exists_cache[$table] = $query->num_rows > 0;

		return $this->table_exists_cache[$table];
	}

	private function columnExists($table, $column) {
		$key = $table . '.' . $column;

		if (array_key_exists($key, $this->column_exists_cache)) {
			return $this->column_exists_cache[$key];
		}

		$query = $this->db->query("SHOW COLUMNS FROM `" . $this->db->escape($table) . "` LIKE '" . $this->db->escape($column) . "'");
		$this->column_exists_cache[$key] = $query->num_rows > 0;

		return $this->column_exists_cache[$key];
	}

	private function normalizeAnalyticsCode($value) {
		$value = $this->decodeRepeatedHtml($value);
		$value = trim($value);

		if ($value === '') {
			return '';
		}

		$gtag_id = $this->extractGtagId($value);

		if ($gtag_id !== '') {
			return $this->buildGtagLoader($gtag_id);
		}

		if (stripos($value, '<script') === false && stripos($value, '<noscript') === false) {
			return '';
		}

		$value = preg_replace('#<(iframe|object|embed)[^>]*>.*?</\1>#is', '', $value);
		$value = preg_replace('/\s+on[a-z]+\s*=\s*(".*?"|\'.*?\'|[^\s>]+)/i', '', $value);

		return trim($value);
	}

	private function decodeRepeatedHtml($value) {
		$value = (string)$value;

		for ($i = 0; $i < 8; $i++) {
			$decoded = html_entity_decode($value, ENT_QUOTES, 'UTF-8');

			if ($decoded === $value) {
				break;
			}

			$value = $decoded;
		}

		return $value;
	}

	private function extractGtagId($value) {
		if (preg_match('/\bG-[A-Z0-9]+\b/i', $value, $match)) {
			return strtoupper($match[0]);
		}

		if (preg_match('/\bAW-[A-Z0-9]+\b/i', $value, $match)) {
			return strtoupper($match[0]);
		}

		return '';
	}

	private function buildGtagLoader($gtag_id) {
		$gtag_id = preg_replace('/[^A-Z0-9\-]/i', '', (string)$gtag_id);

		if ($gtag_id === '') {
			return '';
		}

		return "<script>\n(function(w,d,id){\n  w.dataLayer=w.dataLayer||[];\n  w.gtag=w.gtag||function(){w.dataLayer.push(arguments);};\n  w.gtag('js', new Date());\n  w.gtag('config', id);\n  var s=d.createElement('script');\n  s.async=true;\n  s.src='https://www.googletagmanager.com/gtag/js?id='+encodeURIComponent(id);\n  s.onerror=function(){};\n  (d.head||d.documentElement).appendChild(s);\n})(window,document,'" . $gtag_id . "');\n</script>";
	}
}
