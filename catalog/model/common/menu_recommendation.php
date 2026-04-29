<?php
class ModelCommonMenuRecommendation extends Model {
	public function getMenuProducts($language_id = 0) {
		$language_id = (int)$language_id;

		if ($language_id <= 0) {
			$language_id = $this->getCurrentLanguageId();
		}

		$store_id = (int)$this->config->get('config_store_id');
		$customer_group_id = (int)$this->config->get('config_customer_group_id');

		$sql = "SELECT
					p.product_id,
					p.image,
					p.price,
					p.tax_class_id,
					(
						SELECT ps.price
						FROM " . DB_PREFIX . "product_special ps
						WHERE ps.product_id = p.product_id
							AND ps.customer_group_id = '" . $customer_group_id . "'
							AND ((ps.date_start = '0000-00-00' OR ps.date_start < NOW())
							AND (ps.date_end = '0000-00-00' OR ps.date_end > NOW()))
						ORDER BY ps.priority ASC, ps.price ASC
						LIMIT 1
					) AS special,
					pd.name,
					pd.description,
					(
						SELECT p2c2.category_id
						FROM " . DB_PREFIX . "product_to_category p2c2
						WHERE p2c2.product_id = p.product_id
						ORDER BY
							CASE p2c2.category_id
								WHEN 111 THEN 1
								WHEN 113 THEN 2
								WHEN 114 THEN 3
								WHEN 112 THEN 4
								WHEN 115 THEN 5
								WHEN 71 THEN 6
								WHEN 70 THEN 7
								WHEN 65 THEN 8
								WHEN 110 THEN 9
								WHEN 116 THEN 10
								WHEN 69 THEN 11
								WHEN 109 THEN 12
								WHEN 59 THEN 13
								WHEN 73 THEN 14
								WHEN 83 THEN 15
								WHEN 76 THEN 16
								WHEN 87 THEN 17
								WHEN 84 THEN 18
								WHEN 77 THEN 19
								WHEN 80 THEN 20
								WHEN 66 THEN 21
								WHEN 85 THEN 22
								WHEN 78 THEN 23
								WHEN 86 THEN 24
								WHEN 79 THEN 25
								WHEN 81 THEN 26
								WHEN 68 THEN 27
								ELSE 999
							END,
							p2c2.category_id ASC
						LIMIT 1
					) AS category_id,
					(
						SELECT cd2.name
						FROM " . DB_PREFIX . "product_to_category p2c2
						LEFT JOIN " . DB_PREFIX . "category_description cd2
							ON (p2c2.category_id = cd2.category_id AND cd2.language_id = '" . $language_id . "')
						WHERE p2c2.product_id = p.product_id
						ORDER BY
							CASE p2c2.category_id
								WHEN 111 THEN 1
								WHEN 113 THEN 2
								WHEN 114 THEN 3
								WHEN 112 THEN 4
								WHEN 115 THEN 5
								WHEN 71 THEN 6
								WHEN 70 THEN 7
								WHEN 65 THEN 8
								WHEN 110 THEN 9
								WHEN 116 THEN 10
								WHEN 69 THEN 11
								WHEN 109 THEN 12
								WHEN 59 THEN 13
								WHEN 73 THEN 14
								WHEN 83 THEN 15
								WHEN 76 THEN 16
								WHEN 87 THEN 17
								WHEN 84 THEN 18
								WHEN 77 THEN 19
								WHEN 80 THEN 20
								WHEN 66 THEN 21
								WHEN 85 THEN 22
								WHEN 78 THEN 23
								WHEN 86 THEN 24
								WHEN 79 THEN 25
								WHEN 81 THEN 26
								WHEN 68 THEN 27
								ELSE 999
							END,
							p2c2.category_id ASC
						LIMIT 1
					) AS category_name
				FROM " . DB_PREFIX . "product p
				LEFT JOIN " . DB_PREFIX . "product_description pd
					ON (p.product_id = pd.product_id AND pd.language_id = '" . $language_id . "')
				LEFT JOIN " . DB_PREFIX . "product_to_store p2s
					ON (p.product_id = p2s.product_id AND p2s.store_id = '" . $store_id . "')
				WHERE p.status = '1'
					AND p.date_available <= NOW()
					AND p2s.product_id IS NOT NULL
					AND pd.name IS NOT NULL
					AND pd.name != ''
				ORDER BY p.sort_order ASC, LCASE(pd.name) ASC";

		$query = $this->db->query($sql);

		return $query->rows;
	}

	private function getCurrentLanguageId() {
		if (!empty($this->session->data['language'])) {
			$language_code = strtolower((string)$this->session->data['language']);

			if ($language_code === 'en-gb' || $language_code === 'en') {
				return 2;
			}

			return 1;
		}

		if (!empty($this->request->get['lang'])) {
			$language_code = strtolower((string)$this->request->get['lang']);

			if ($language_code === 'en-gb' || $language_code === 'en') {
				return 2;
			}

			return 1;
		}

		if (!empty($this->request->cookie['language'])) {
			$language_code = strtolower((string)$this->request->cookie['language']);

			if ($language_code === 'en-gb' || $language_code === 'en') {
				return 2;
			}

			return 1;
		}

		$config_language_id = (int)$this->config->get('config_language_id');

		if ($config_language_id > 0) {
			return $config_language_id;
		}

		return 1;
	}
}