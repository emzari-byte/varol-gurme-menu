<?php
class ModelCommonMenuRecommendationEngine extends Model {

	public function getRecommendation($mode, $temp = null) {
		$config = $this->getConfigs();

		if (empty($config['module_status'])) {
			return array();
		}

		if ($temp === null) {
			$temp = $this->getCurrentTempFallback();
		}

		$threshold = isset($config['temperature_threshold']) ? (int)$config['temperature_threshold'] : 10;
		$use_hot_drink = ((float)$temp < $threshold);

		if ($mode === 'drink_only') {
			return $this->buildDrinkOnlyRecommendation($mode, $use_hot_drink, $temp);
		}

		if ($mode === 'dessert_coffee') {
			return $this->buildDessertCoffeeRecommendation($mode, $use_hot_drink, $temp);
		}

		return $this->buildStandardRecommendation($mode, $use_hot_drink, $temp);
	}

	private function buildStandardRecommendation($mode, $use_hot_drink, $temp) {
		$main_candidates = $this->getCandidatesByRoleAndMode('main', $mode);

		if (!$main_candidates) {
			return array();
		}

		$main = $this->pickWeightedRandom($main_candidates);

		if (!$main) {
			return array();
		}

		$pair_tag = !empty($main['pair_tag']) ? $main['pair_tag'] : '';
		$used_product_ids = array((int)$main['product_id']);

		if ($use_hot_drink) {
			$drink = $this->pickCompanion('hot_drink', $mode, $pair_tag, $used_product_ids);
		} else {
			$drink = $this->pickCompanion('drink', $mode, $pair_tag, $used_product_ids);
		}

		if ($drink && !empty($drink['product_id']) && !in_array((int)$drink['product_id'], $used_product_ids)) {
			$used_product_ids[] = (int)$drink['product_id'];
		}

		$dessert = $this->pickCompanion('dessert', $mode, $pair_tag, $used_product_ids);

		return array(
			'mode' => $mode,
			'temp' => $temp,
			'use_hot_drink' => $use_hot_drink,
			'main' => $this->formatItem($main),
			'drink' => $drink ? $this->formatItem($drink) : array(),
			'dessert' => $dessert ? $this->formatItem($dessert) : array()
		);
	}

	private function buildDessertCoffeeRecommendation($mode, $use_hot_drink, $temp) {
		$dessert_candidates = $this->getCandidatesByRoleAndMode('dessert', $mode);

		if (!$dessert_candidates) {
			return array();
		}

		$dessert = $this->pickWeightedRandom($dessert_candidates);

		if (!$dessert) {
			return array();
		}

		$pair_tag = !empty($dessert['pair_tag']) ? $dessert['pair_tag'] : '';
		$used_product_ids = array((int)$dessert['product_id']);

		$drink = $this->pickCompanion('hot_drink', $mode, $pair_tag, $used_product_ids);

		if (!$drink) {
			$drink = $this->pickCompanion('drink', $mode, $pair_tag, $used_product_ids);
		}

		if ($drink && !empty($drink['product_id']) && !in_array((int)$drink['product_id'], $used_product_ids)) {
			$used_product_ids[] = (int)$drink['product_id'];
		}

		$second_drink = array();

		if ($use_hot_drink) {
			$second_drink = $this->pickCompanion('hot_drink', $mode, $pair_tag, $used_product_ids);
		} else {
			$second_drink = $this->pickCompanion('drink', $mode, $pair_tag, $used_product_ids);
		}

		return array(
			'mode' => $mode,
			'temp' => $temp,
			'use_hot_drink' => $use_hot_drink,
			'main' => array(),
			'drink' => $drink ? $this->formatItem($drink) : array(),
			'dessert' => $dessert ? $this->formatItem($dessert) : array(),
			'second_drink' => $second_drink ? $this->formatItem($second_drink) : array()
		);
	}

	private function buildDrinkOnlyRecommendation($mode, $use_hot_drink, $temp) {
		$used_product_ids = array();

		if ($use_hot_drink) {
			$drink1 = $this->pickCompanion('hot_drink', $mode, '', $used_product_ids);

			if (!$drink1) {
				$drink1 = $this->pickCompanion('drink', $mode, '', $used_product_ids);
			}
		} else {
			$drink1 = $this->pickCompanion('drink', $mode, '', $used_product_ids);

			if (!$drink1) {
				$drink1 = $this->pickCompanion('hot_drink', $mode, '', $used_product_ids);
			}
		}

		if (!$drink1) {
			return array();
		}

		$used_product_ids[] = (int)$drink1['product_id'];

		if ($use_hot_drink) {
			$drink2 = $this->pickCompanion('drink', $mode, '', $used_product_ids);

			if (!$drink2) {
				$drink2 = $this->pickCompanion('hot_drink', $mode, '', $used_product_ids);
			}
		} else {
			$drink2 = $this->pickCompanion('drink', $mode, '', $used_product_ids);

			if (!$drink2) {
				$drink2 = $this->pickCompanion('hot_drink', $mode, '', $used_product_ids);
			}
		}

		return array(
			'mode' => $mode,
			'temp' => $temp,
			'use_hot_drink' => $use_hot_drink,
			'main' => array(),
			'drink' => $drink1 ? $this->formatItem($drink1) : array(),
			'dessert' => array(),
			'second_drink' => $drink2 ? $this->formatItem($drink2) : array()
		);
	}

	private function pickCompanion($role, $mode, $pair_tag, $used_product_ids = array()) {
		$primary = array();

		if ($pair_tag !== '') {
			$primary = $this->getCandidatesByRoleModeAndTag($role, $mode, $pair_tag, $used_product_ids);
		}

		if ($primary) {
			return $this->pickWeightedRandom($primary);
		}

		$fallback = $this->getCandidatesByRoleAndMode($role, $mode, $used_product_ids);

		if ($fallback) {
			return $this->pickWeightedRandom($fallback);
		}

		return array();
	}

	private function getCandidatesByRoleAndMode($role, $mode, $exclude_product_ids = array()) {
		$sql = "SELECT
					rp.product_id,
					rp.role,
					rp.pair_tag,
					rp.priority,
					rp.status,
					p.image,
					p.price,
					p.tax_class_id,
					pd.name,
					pd.description
				FROM `" . DB_PREFIX . "menu_recommendation_product` rp
				LEFT JOIN `" . DB_PREFIX . "menu_recommendation_product_mode` rpm
					ON (rp.product_id = rpm.product_id)
				LEFT JOIN `" . DB_PREFIX . "product` p
					ON (rp.product_id = p.product_id)
				LEFT JOIN `" . DB_PREFIX . "product_description` pd
					ON (rp.product_id = pd.product_id)
				WHERE rp.status = '1'
					AND p.product_id IS NOT NULL
					AND p.status = '1'
					AND rp.role = '" . $this->db->escape($role) . "'
					AND rpm.mode = '" . $this->db->escape($mode) . "'
					AND pd.language_id = '" . (int)$this->config->get('config_language_id') . "'";

		if (!empty($exclude_product_ids)) {
			$ids = array_map('intval', $exclude_product_ids);
			$sql .= " AND rp.product_id NOT IN (" . implode(',', $ids) . ")";
		}

		$sql .= " GROUP BY rp.product_id ORDER BY rp.priority DESC, pd.name ASC";

		return $this->db->query($sql)->rows;
	}

	private function getCandidatesByRoleModeAndTag($role, $mode, $pair_tag, $exclude_product_ids = array()) {
		$sql = "SELECT
					rp.product_id,
					rp.role,
					rp.pair_tag,
					rp.priority,
					rp.status,
					p.image,
					p.price,
					p.tax_class_id,
					pd.name,
					pd.description
				FROM `" . DB_PREFIX . "menu_recommendation_product` rp
				LEFT JOIN `" . DB_PREFIX . "menu_recommendation_product_mode` rpm
					ON (rp.product_id = rpm.product_id)
				LEFT JOIN `" . DB_PREFIX . "product` p
					ON (rp.product_id = p.product_id)
				LEFT JOIN `" . DB_PREFIX . "product_description` pd
					ON (rp.product_id = pd.product_id)
				WHERE rp.status = '1'
					AND p.product_id IS NOT NULL
					AND p.status = '1'
					AND rp.role = '" . $this->db->escape($role) . "'
					AND rp.pair_tag = '" . $this->db->escape($pair_tag) . "'
					AND rpm.mode = '" . $this->db->escape($mode) . "'
					AND pd.language_id = '" . (int)$this->config->get('config_language_id') . "'";

		if (!empty($exclude_product_ids)) {
			$ids = array_map('intval', $exclude_product_ids);
			$sql .= " AND rp.product_id NOT IN (" . implode(',', $ids) . ")";
		}

		$sql .= " GROUP BY rp.product_id ORDER BY rp.priority DESC, pd.name ASC";

		return $this->db->query($sql)->rows;
	}

	private function pickWeightedRandom($items) {
		if (empty($items)) {
			return array();
		}

		$total_weight = 0;
		$weighted = array();

		foreach ($items as $item) {
			$priority = isset($item['priority']) ? (int)$item['priority'] : 0;
			$weight = max(1, $priority + 1);
			$total_weight += $weight;
			$weighted[] = array(
				'weight' => $weight,
				'item' => $item
			);
		}

		$rand = mt_rand(1, $total_weight);
		$current = 0;

		foreach ($weighted as $row) {
			$current += $row['weight'];

			if ($rand <= $current) {
				return $row['item'];
			}
		}

		return $weighted[0]['item'];
	}

	private function formatItem($item) {
		if (empty($item)) {
			return array();
		}

		$this->load->model('tool/image');

		$image = !empty($item['image']) && is_file(DIR_IMAGE . $item['image'])
			? $this->model_tool_image->resize($item['image'], 600, 450)
			: $this->model_tool_image->resize('placeholder.png', 600, 450);

		$price = isset($item['price']) ? (float)$item['price'] : 0;
		$tax_class_id = isset($item['tax_class_id']) ? (int)$item['tax_class_id'] : 0;

		return array(
			'product_id'  => (int)$item['product_id'],
			'name'        => isset($item['name']) ? $item['name'] : '',
			'description' => $this->cleanDescription(isset($item['description']) ? $item['description'] : ''),
			'thumb'       => $image,
			'href'        => $this->url->link('product/product', 'product_id=' . (int)$item['product_id']),
			'price'       => $this->currency->format(
				$this->tax->calculate($price, $tax_class_id, $this->config->get('config_tax')),
				$this->session->data['currency']
			),
			'role'        => isset($item['role']) ? $item['role'] : '',
			'pair_tag'    => isset($item['pair_tag']) ? $item['pair_tag'] : '',
			'priority'    => isset($item['priority']) ? (int)$item['priority'] : 0
		);
	}

	private function cleanDescription($text) {
		$text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');
		$text = preg_replace('/<br\s*\/?>/i', ' ', $text);
		$text = preg_replace('/<\/(div|p|li|h1|h2|h3|h4|h5|h6|b|strong|span)>/i', ' ', $text);
		$text = strip_tags($text);
		$text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');
		$text = preg_replace('/\x{00A0}/u', ' ', $text);
		$text = preg_replace('/\s+/u', ' ', $text);

		return trim($text);
	}

	private function getConfigs() {
		$result = array();

		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "menu_recommendation_config`");

		foreach ($query->rows as $row) {
			$result[$row['config_key']] = $row['config_value'];
		}

		return $result;
	}

	private function getCurrentTempFallback() {
		return 15;
	}
}