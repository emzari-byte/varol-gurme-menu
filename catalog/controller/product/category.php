<?php
class ControllerProductCategory extends Controller {
	public function index() {
        $date = date('Y-m-d');
        $timestamp = strtotime($date);
        $gun = date('D', $timestamp);

		$this->load->language('product/category');
		$this->load->model('catalog/category');
		$this->load->model('catalog/product');
		$this->load->model('tool/image');
		$this->load->model('common/restaurant_settings');

		$data['title'] = $this->config->get('config_meta_title');
		$data['description'] = $this->config->get('config_meta_description');
		$data['serv'] = HTTPS_SERVER;
		$brand_logo = (string)$this->model_common_restaurant_settings->get('restaurant_menu_logo', $this->model_common_restaurant_settings->get('restaurant_brand_logo', $this->config->get('config_logo')));

		if (is_file(DIR_IMAGE . $brand_logo)) {
			$data['logo'] = HTTPS_SERVER . 'image/' . $brand_logo;
		} elseif (is_file(DIR_IMAGE . $this->config->get('config_logo'))) {
			$data['logo'] = HTTPS_SERVER . 'image/' . $this->config->get('config_logo');
		} else {
			$data['logo'] = '';
		}

		$menu_theme = (string)$this->model_common_restaurant_settings->get('restaurant_menu_theme', 'default');
		$data['restaurant_menu_theme'] = in_array($menu_theme, array('default', 'v1', 'v2', 'v3', 'v4', 'v5'), true) ? $menu_theme : 'default';
		$data['restaurant_analytics_code'] = (string)$this->model_common_restaurant_settings->get('restaurant_analytics_code', '');
		$prep_extra_minutes = $this->model_common_restaurant_settings->getPreparationExtraMinutes();

        $this->load->model('common/menu_order');

        $qr = isset($this->request->get['qr']) ? trim((string)$this->request->get['qr']) : '';

        if ($qr !== '') {
            $this->model_common_menu_order->ensureTableSessionFromQr($qr);
        } elseif (!empty($this->session->data['menu_qr_token'])) {
            $qr = $this->session->data['menu_qr_token'];
        }

        $qr = !empty($this->session->data['menu_qr_token']) ? $this->session->data['menu_qr_token'] : $qr;
        $table_id = !empty($this->session->data['menu_table_id']) ? (int)$this->session->data['menu_table_id'] : 0;

        $data['qr'] = $qr;
        $data['table_id'] = $table_id;
        $data['table_no'] = !empty($this->session->data['menu_table_no']) ? (int)$this->session->data['menu_table_no'] : 0;
        $data['table_name'] = !empty($this->session->data['menu_table_name']) ? $this->session->data['menu_table_name'] : '';
        $data['can_order'] = $this->model_common_menu_order->canOrder();
        $data['can_track_order'] = $this->model_common_menu_order->canTrackOrder();
        $show_prices = $this->model_common_menu_order->getRestaurantSettingValue('restaurant_qr_order_menu', 1) === 1;
        $data['menu_order_endpoint'] = $this->url->link('common/menu_order/add', '', true);

        if (!empty($this->session->data['language'])) {
            $data['language_code'] = $this->session->data['language'];
        } else {
            $data['language_code'] = $this->config->get('config_language');
        }

        $this->load->language('common/menu_order');
        $data['text_menu_order'] = $this->language->get('text_menu_order');
        $data['text_table'] = $this->language->get('text_table');
        $data['text_menu_choices'] = $this->language->get('text_menu_choices');
        $data['text_menu_add'] = $this->language->get('text_menu_add');
        $data['text_menu_added'] = $this->language->get('text_menu_added');
        $data['text_order_note'] = $this->language->get('text_order_note');
        $data['text_order_note_placeholder'] = $this->language->get('text_order_note_placeholder');
        $data['text_active_order_info'] = $this->language->get('text_active_order_info');
        $data['text_new_order'] = $this->language->get('text_new_order');
        $data['text_additional_order'] = $this->language->get('text_additional_order');
        $data['text_request_bill'] = $this->language->get('text_request_bill');
        $data['text_bill_requested'] = $this->language->get('text_bill_requested');
        $data['text_bill_request_seen'] = $this->language->get('text_bill_request_seen');
        $data['text_bill_payment_title'] = $this->language->get('text_bill_payment_title');
        $data['text_bill_payment_subtitle'] = $this->language->get('text_bill_payment_subtitle');
        $data['text_bill_payment_cash'] = $this->language->get('text_bill_payment_cash');
        $data['text_bill_payment_card'] = $this->language->get('text_bill_payment_card');
        $data['text_bill_payment_cancel'] = $this->language->get('text_bill_payment_cancel');
        $data['text_send_order'] = $this->language->get('text_send_order');
        $data['text_order_send_failed'] = $this->language->get('text_order_send_failed');
        $data['text_order_sent'] = $this->language->get('text_order_sent');
        $data['text_order_item_add_failed'] = $this->language->get('text_order_item_add_failed');
        $data['text_order_item_added'] = $this->language->get('text_order_item_added');
        $data['text_request_bill_failed'] = $this->language->get('text_request_bill_failed');
        $data['text_waiter_confirm'] = $this->language->get('text_waiter_confirm');
        $data['text_waiter_call_failed'] = $this->language->get('text_waiter_call_failed');
        $data['text_waiter_called'] = $this->language->get('text_waiter_called');
        $data['text_modal_close'] = $this->language->get('text_modal_close');
        $data['text_menu_order_feature_unavailable'] = $this->language->get('text_order_feature_unavailable');
        $data['text_menu_order_add_failed'] = $this->language->get('text_order_add_failed');
        $data['text_table_bill'] = $this->language->get('text_table_bill');
        $data['text_active_orders'] = $this->language->get('text_active_orders');
        $data['text_table_total'] = $this->language->get('text_table_total');
        $data['text_order_waiting'] = $this->language->get('text_order_waiting');
        $data['text_order_in_kitchen'] = $this->language->get('text_order_in_kitchen');
        $data['text_order_ready_for_service'] = $this->language->get('text_order_ready_for_service');
        $data['text_order_out_for_service'] = $this->language->get('text_order_out_for_service');
        $data['text_order_served'] = $this->language->get('text_order_served');
        $data['text_bill_summary'] = $this->language->get('text_bill_summary');
        $data['text_order_history'] = $this->language->get('text_order_history');
        $data['text_order_sent_to_waiter'] = $this->language->get('text_order_sent_to_waiter');
        $data['text_call_waiter'] = $this->language->get('text_call_waiter');
        $data['text_view_more'] = $this->language->get('text_view_more');
        $data['text_view_less'] = $this->language->get('text_view_less');
        $data['text_menu_home'] = $this->language->get('text_menu_home');
        $data['text_group_card_description'] = $this->language->get('text_group_card_description');
        $data['text_group'] = $this->language->get('text_group');
        $data['text_category'] = $this->language->get('text_category');

        $qr_param = $qr ? '&qr=' . urlencode($qr) : '';
        $qr_param_no_prefix = $qr ? 'qr=' . urlencode($qr) : '';

        $data['breadcrumbs'] = array();
        $data['top_menu_categories'] = array();
        $data['group_mode'] = false;
        $data['group_categories'] = array();
        $data['categories'] = array();

        $parts = array();
        $path = '';
        $category_id = 0;

		if (isset($this->request->get['path'])) {
			$url = '';

			if (isset($this->request->get['sort'])) {
				$url .= '&sort=' . $this->request->get['sort'];
			}

			if (isset($this->request->get['order'])) {
				$url .= '&order=' . $this->request->get['order'];
			}

			if (isset($this->request->get['limit'])) {
				$url .= '&limit=' . $this->request->get['limit'];
			}

            if ($qr) {
                $url .= '&qr=' . urlencode($qr);
            }

			$parts = explode('_', (string)$this->request->get['path']);
			$category_id = (int)array_pop($parts);

			foreach ($parts as $path_id) {
				if (!$path) {
					$path = (int)$path_id;
				} else {
					$path .= '_' . (int)$path_id;
				}

				$category_info_path = $this->model_catalog_category->getCategory($path_id);

				if ($category_info_path) {
					$data['breadcrumbs'][] = array(
						'text' => $category_info_path['name'],
						'href' => $this->url->link('product/category', 'path=' . $path . $url, true)
					);
				}
			}
		}

		$category_info = $this->model_catalog_category->getCategory($category_id);

		if (!$category_info) {
			$this->response->redirect($this->url->link('common/home', $qr_param_no_prefix, true));
            return;
		}

        $current_top_category_id = 0;

        if ((int)$category_info['parent_id'] === 0) {
            $current_top_category_id = (int)$category_info['category_id'];
        } elseif (!empty($parts)) {
            $current_top_category_id = (int)$parts[0];
        } else {
            $parent_info = $this->model_catalog_category->getCategory($category_info['parent_id']);

            if ($parent_info && (int)$parent_info['parent_id'] === 0) {
                $current_top_category_id = (int)$parent_info['category_id'];
            } else {
                $current_top_category_id = (int)$category_info['category_id'];
            }
        }

        $top_categories = $this->model_catalog_category->getCategories(0);

        foreach ($top_categories as $top_category) {
            if ($gun == 'Sun' && $top_category['column'] == '99') {
                continue;
            }

            if (!empty($top_category['image'])) {
                $top_thumb = $this->model_tool_image->resize($top_category['image'], 90, 90);
            } else {
                $top_thumb = $this->model_tool_image->resize('no_image.png', 90, 90);
            }

            if ((int)$top_category['category_id'] === 117) {
                $top_href = $this->url->link('common/menu_recommendation', $qr_param_no_prefix, true);
                $top_active = (isset($this->request->get['route']) && $this->request->get['route'] === 'common/menu_recommendation');
            } else {
                $top_href = $this->url->link('product/category', 'path=' . (int)$top_category['category_id'] . $qr_param, true);
                $top_active = ((int)$top_category['category_id'] === (int)$current_top_category_id);
            }

            $data['top_menu_categories'][] = array(
                'category_id' => (int)$top_category['category_id'],
                'name'        => $top_category['name'],
                'thumb'       => $top_thumb,
                'href'        => $top_href,
                'active'      => $top_active
            );
        }

        $child_query = $this->db->query("
            SELECT *
            FROM " . DB_PREFIX . "category
            WHERE parent_id = '" . (int)$category_id . "'
            ORDER BY sort_order ASC
        ");

        $has_children = (bool)$child_query->num_rows;
        $is_group_mode = !empty($category_info['top']) && (int)$category_info['top'] === 1;

        if ($has_children && $is_group_mode) {
            $data['group_mode'] = true;

            foreach ($child_query->rows as $child) {
                if ($gun == 'Sun' && $child['column'] == '99') {
                    continue;
                }

                $child_info = $this->model_catalog_category->getCategory($child['category_id']);

                if (!$child_info) {
                    continue;
                }

                if (!empty($child_info['image'])) {
                    $child_thumb = HTTPS_SERVER . 'image/' . $child_info['image'];
                } else {
                    $child_thumb = $this->model_tool_image->resize('no_image.png', 150, 110);
                }

                $data['group_categories'][] = array(
                    'category_id' => (int)$child_info['category_id'],
                    'name'        => $child_info['name'],
                    'thumb'       => $child_thumb,
                    'href'        => $this->url->link('product/category', 'path=' . (int)$category_info['category_id'] . '_' . (int)$child_info['category_id'] . $qr_param, true),
                    'top'         => isset($child_info['top']) ? (int)$child_info['top'] : 0
                );
            }

            $data['menu_footer'] = $this->load->controller('common/menu_footer');
            $this->response->setOutput($this->load->view('product/category', $data));
            return;
        }

        $direct_products = $this->getProductsByCategoryId($category_id, $gun);

        if (!empty($direct_products)) {
            $data['categories'][] = array(
                'name'     => $category_info['name'],
                'products' => $direct_products,
                'thumb'    => !empty($category_info['image'])
                    ? (HTTPS_SERVER . 'image/' . $category_info['image'])
                    : $this->model_tool_image->resize('no_image.png', 40, 40),
                'href'     => $this->url->link('product/category', 'path=' . (int)$category_info['category_id'] . $qr_param, true)
            );

            $data['menu_footer'] = $this->load->controller('common/menu_footer');
            $this->response->setOutput($this->load->view('product/category', $data));
            return;
        }

        if ($has_children) {
            foreach ($child_query->rows as $child) {
                if ($gun == 'Sun' && $child['column'] == '99') {
                    continue;
                }

                $child_products = $this->getProductsByCategoryId((int)$child['category_id'], $gun);

                if (empty($child_products)) {
                    continue;
                }

                $child_info = $this->model_catalog_category->getCategory($child['category_id']);

                if (!$child_info) {
                    continue;
                }

                if (!empty($child_info['image'])) {
                    $child_thumb = HTTPS_SERVER . 'image/' . $child_info['image'];
                } else {
                    $child_thumb = $this->model_tool_image->resize('no_image.png', 40, 40);
                }

                $data['categories'][] = array(
                    'name'     => $child_info['name'],
                    'products' => $child_products,
                    'thumb'    => $child_thumb,
                    'href'     => $this->url->link('product/category', 'path=' . (int)$category_info['category_id'] . '_' . (int)$child_info['category_id'] . $qr_param, true)
                );
            }
        }

        $data['menu_footer'] = $this->load->controller('common/menu_footer');
		$this->response->setOutput($this->load->view('product/category', $data));
	}

    private function getProductsByCategoryId(int $category_id, string $gun): array {
        $products = array();
        $this->ensureRestaurantAllergens();
        $show_prices = $this->model_common_menu_order->getRestaurantSettingValue('restaurant_qr_order_menu', 1) === 1;

        $filter_data = array(
            'filter_category_id' => $category_id,
            'filter_filter'      => '',
            'sort'               => 'p.sort_order',
            'order'              => 'ASC',
            'start'              => 0,
            'limit'              => 1000
        );

        $results = $this->model_catalog_product->getProducts($filter_data);

        foreach ($results as $result) {
            if ($gun == 'Sun' && $result['sku'] == '99') {
                continue;
            }

            if (!empty($result['image'])) {
                $image = HTTPS_SERVER . 'image/' . $result['image'];
            } else {
                $image = $this->model_tool_image->resize('no_image.png', 150, 110);
            }

            if ($this->customer->isLogged() || !$this->config->get('config_customer_price')) {
                $price = $this->currency->format(
                    $this->tax->calculate($result['price'], $result['tax_class_id'], $this->config->get('config_tax')),
                    $this->session->data['currency']
                );
            } else {
                $price = false;
            }

            if (!is_null($result['special']) && (float)$result['special'] >= 0) {
                $special = $this->currency->format(
                    $this->tax->calculate($result['special'], $result['tax_class_id'], $this->config->get('config_tax')),
                    $this->session->data['currency']
                );
            } else {
                $special = false;
            }

            if (!empty($result['location'])) {
                $price = $result['location'];
            }

            if (!$show_prices) {
                $price = false;
                $special = false;
            }

            $options = array();

            if (!empty($this->session->data['language_id'])) {
                $active_language_id = (int)$this->session->data['language_id'];
            } else {
                $active_language_id = (int)$this->config->get('config_language_id');
            }

            $querya = $this->db->query("
                SELECT ra.image, ra.name
                FROM " . DB_PREFIX . "restaurant_product_allergen rpa
                INNER JOIN " . DB_PREFIX . "restaurant_allergen ra ON (ra.allergen_id = rpa.allergen_id)
                WHERE rpa.product_id = '" . (int)$result['product_id'] . "'
                AND ra.status = '1'
                ORDER BY ra.sort_order ASC, ra.name ASC
            ");

            foreach ($querya->rows as $resa) {
                $options[] = array(
                    'img'  => HTTPS_SERVER . 'image/' . $resa['image'],
                    'name' => $resa['name']
                );
            }

            $products[] = array(
                 'product_id' => (int)$result['product_id'],
                'name'        => $result['name'],
                'thumb'       => $image,
                'options'     => $options,
                'price'       => $price,
                'special'     => $special,
                'sku'         => $result['sku'],
                'tag'         => $this->model_common_restaurant_settings->adjustPreparationTag($result['tag'], $prep_extra_minutes),
                'upc'         => $result['upc'],
                'ean'         => $result['ean'],
                'jan'         => $result['jan'],
                'isbn'        => $result['isbn'],
                'description' => $this->model_common_restaurant_settings->cleanProductDescriptionHtml($result['description'])
            );
        }

        return $products;
    }

    private function ensureRestaurantAllergens(): void {
        $this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "restaurant_allergen` (
            `allergen_id` int(11) NOT NULL AUTO_INCREMENT,
            `old_option_value_id` int(11) NOT NULL DEFAULT '0',
            `name` varchar(128) NOT NULL,
            `image` varchar(255) NOT NULL DEFAULT '',
            `sort_order` int(11) NOT NULL DEFAULT '0',
            `status` tinyint(1) NOT NULL DEFAULT '1',
            `date_added` datetime NOT NULL,
            `date_modified` datetime NOT NULL,
            PRIMARY KEY (`allergen_id`),
            KEY `old_option_value_id` (`old_option_value_id`)
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8");

        $this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "restaurant_product_allergen` (
            `product_id` int(11) NOT NULL,
            `allergen_id` int(11) NOT NULL,
            PRIMARY KEY (`product_id`,`allergen_id`),
            KEY `allergen_id` (`allergen_id`)
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8");

        $language_id = (int)$this->config->get('config_language_id');
        $option_ids = array(14);

        $option_query = $this->db->query("SELECT DISTINCT option_id FROM `" . DB_PREFIX . "option_description`
            WHERE LOWER(name) LIKE '%alerjen%'
            OR LOWER(name) LIKE '%alerji%'
            OR LOWER(name) LIKE '%allergen%'");

        foreach ($option_query->rows as $row) {
            $option_ids[] = (int)$row['option_id'];
        }

        $option_ids = array_values(array_unique(array_filter($option_ids)));

        if (!$option_ids) {
            return;
        }

        $option_id_sql = implode(',', array_map('intval', $option_ids));

        $this->db->query("INSERT IGNORE INTO `" . DB_PREFIX . "restaurant_allergen` (old_option_value_id, name, image, sort_order, status, date_added, date_modified)
            SELECT ov.option_value_id, ovd.name, ov.image, ov.sort_order, 1, NOW(), NOW()
            FROM `" . DB_PREFIX . "option_value` ov
            LEFT JOIN `" . DB_PREFIX . "option_value_description` ovd ON (ov.option_value_id = ovd.option_value_id AND ovd.language_id = '" . $language_id . "')
            WHERE ov.option_id IN (" . $option_id_sql . ")
            AND NOT EXISTS (
                SELECT 1 FROM `" . DB_PREFIX . "restaurant_allergen` ra WHERE ra.old_option_value_id = ov.option_value_id
            )");

        $this->db->query("INSERT IGNORE INTO `" . DB_PREFIX . "restaurant_product_allergen` (product_id, allergen_id)
            SELECT DISTINCT pov.product_id, ra.allergen_id
            FROM `" . DB_PREFIX . "product_option_value` pov
            INNER JOIN `" . DB_PREFIX . "restaurant_allergen` ra ON (ra.old_option_value_id = pov.option_value_id)
            WHERE pov.option_id IN (" . $option_id_sql . ")");
    }
}
