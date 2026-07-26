<?php
class ControllerProductCategory extends Controller {
    private $restaurant_allergens_ensured = false;

	public function index() {
        $date = date('Y-m-d');
        $timestamp = strtotime($date);
        $gun = date('D', $timestamp);

		$this->load->language('product/category');
		$this->load->model('catalog/category');
		$this->load->model('catalog/product');
		$this->load->model('tool/image');
		$this->load->model('common/restaurant_settings');
		$this->load->model('common/restaurant_product_groups');

		$data['title'] = $this->config->get('config_meta_title');
		$data['description'] = $this->config->get('config_meta_description');
		$preview_host = isset($this->request->server['HTTP_HOST']) ? (string)$this->request->server['HTTP_HOST'] : '';
		$local_preview = strpos($preview_host, 'localhost') === 0 || strpos($preview_host, '127.0.0.1') === 0 || strpos($preview_host, '[::1]') === 0;
		$data['nutrition_preview'] = $local_preview && isset($this->request->get['nutrition_preview']) && (string)$this->request->get['nutrition_preview'] === '1';
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
		$data['restaurant_wifi_name'] = trim((string)$this->model_common_restaurant_settings->get('restaurant_wifi_name', ''));
		$data['restaurant_wifi_password'] = trim((string)$this->model_common_restaurant_settings->get('restaurant_wifi_password', ''));
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
        $data['text_coming_soon'] = $this->language->get('text_coming_soon');
        $data['text_coming_soon_note'] = $this->language->get('text_coming_soon_note');

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

        $closed_category_ids = $this->model_catalog_category->getClosedCategoryIdsByDay($gun);
        $is_path_closed = false;

        foreach ($parts as $path_id) {
            $path_category_info = $this->model_catalog_category->getCategory((int)$path_id);

            if ($path_category_info && $this->isCategoryClosedOnDay($path_category_info, $gun, $closed_category_ids)) {
                $is_path_closed = true;
                break;
            }
        }

        if ($is_path_closed || $this->isCategoryClosedOnDay($category_info, $gun, $closed_category_ids)) {
            $this->response->redirect($this->url->link('common/menu', $qr_param_no_prefix, true));
            return;
        }

        $data['category_info'] = array(
            'category_id' => (int)$category_info['category_id'],
            'name'        => $category_info['name'],
            'parent_id'   => (int)$category_info['parent_id'],
            'path'        => isset($this->request->get['path']) ? (string)$this->request->get['path'] : (string)(int)$category_info['category_id']
        );

        $seo_category_name = trim((string)$category_info['name']);
        $seo_meta_title = !empty($category_info['meta_title']) ? trim((string)$category_info['meta_title']) : '';
        $seo_title_base = $seo_meta_title !== '' && $seo_meta_title !== $this->config->get('config_meta_title') ? $seo_meta_title : $seo_category_name;
        $seo_title_base = $seo_title_base !== '' ? $seo_title_base : $this->config->get('config_meta_title');

        if (stripos($seo_title_base, 'varol veranda') !== false) {
            $data['title'] = $seo_title_base;
        } else {
            $data['title'] = $seo_title_base . ' | Varol Veranda Menü';
        }

        if (!empty($category_info['meta_description'])) {
            $data['description'] = trim((string)$category_info['meta_description']);
        } elseif ($data['language_code'] === 'en-gb') {
            $data['description'] = 'Explore ' . $seo_category_name . ' options on the Varol Veranda menu with fresh dishes, coffee and a relaxed garden atmosphere.';
        } else {
            $data['description'] = $seo_category_name . ' seçeneklerini Varol Veranda menüsünde inceleyin. Kahvaltı, kahve ve gün boyu lezzetlerle ferah bahçe deneyimi.';
        }

        $data['canonical'] = $this->url->link('product/category', 'path=' . $data['category_info']['path'], true);

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
            if ($this->isCategoryClosedOnDay($top_category, $gun, $closed_category_ids)) {
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
                if ($this->isCategoryClosedOnDay($child, $gun, $closed_category_ids)) {
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

        $direct_products = $this->getProductsByCategoryId($category_id, $gun, $prep_extra_minutes);
        $direct_display = $this->buildDisplayGroupsForCategory($direct_products, $category_id);

        if (!empty($direct_products)) {
            $data['categories'][] = array(
                'category_id' => (int)$category_info['category_id'],
                'name'     => $category_info['name'],
                'products' => $direct_products,
                'display_products' => $direct_display['products'],
                'product_groups' => $direct_display['groups'],
                'display_items' => $direct_display['items'],
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
                if ($this->isCategoryClosedOnDay($child, $gun, $closed_category_ids)) {
                    continue;
                }

                $child_products = $this->getProductsByCategoryId((int)$child['category_id'], $gun, $prep_extra_minutes);

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

                $child_display = $this->buildDisplayGroupsForCategory($child_products, (int)$child['category_id']);

                $data['categories'][] = array(
                    'category_id' => (int)$child_info['category_id'],
                    'name'     => $child_info['name'],
                    'products' => $child_products,
                    'display_products' => $child_display['products'],
                    'product_groups' => $child_display['groups'],
                    'display_items' => $child_display['items'],
                    'thumb'    => $child_thumb,
                    'href'     => $this->url->link('product/category', 'path=' . (int)$category_info['category_id'] . '_' . (int)$child_info['category_id'] . $qr_param, true)
                );
            }
        }

        $data['menu_footer'] = $this->load->controller('common/menu_footer');
		$this->response->setOutput($this->load->view('product/category', $data));
	}

    private function getProductsByCategoryId(int $category_id, string $gun, int $prep_extra_minutes = 0): array {
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
        $product_ids = array();

        foreach ($results as $result) {
            $product_ids[] = (int)$result['product_id'];
        }

        $allergens_by_product = $this->getAllergensByProductIds($product_ids);

        foreach ($results as $result) {
            if ($this->isProductClosedOnDay(isset($result['sku']) ? $result['sku'] : '', $gun)) {
                continue;
            }

            if (!empty($result['image'])) {
                $image = $this->model_tool_image->resize($result['image'], 800, 600);
                $popup = HTTPS_SERVER . 'image/' . $result['image'];
            } else {
                $image = $this->model_tool_image->resize('no_image.png', 800, 600);
                $popup = $this->model_tool_image->resize('no_image.png', 1000, 740);
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

            $price = $this->applyPricePrefix($price, $result['location']);

            if (!$show_prices) {
                $price = false;
                $special = false;
            }

            $options = isset($allergens_by_product[(int)$result['product_id']]) ? $allergens_by_product[(int)$result['product_id']] : array();

            $name_info = $this->normalizeUpcomingProductName($result['name']);

            $products[] = array(
                 'product_id' => (int)$result['product_id'],
                'name'        => $name_info['name'],
                'raw_name'    => $result['name'],
                'is_upcoming' => $name_info['is_upcoming'],
                'thumb'       => $image,
                'popup'       => $popup,
                'options'     => $options,
                'price'       => $price,
                'special'     => $special,
                'sku'         => $result['sku'],
                'tag'         => $this->model_common_restaurant_settings->adjustPreparationTag($result['tag'], $prep_extra_minutes),
                'upc'         => $result['upc'],
                'ean'         => $result['ean'],
                'jan'         => $result['jan'],
                'isbn'        => $result['isbn'],
                'sort_order'  => isset($result['sort_order']) ? (int)$result['sort_order'] : 0,
                'description' => $this->model_common_restaurant_settings->cleanProductDescriptionHtml($result['description'])
            );
        }

        return $products;
    }

    private function buildDisplayGroupsForCategory(array $products, int $category_id): array {
        $configured_display = $this->getConfiguredProductGroupsForDisplay($products, $category_id);
        $automatic_display = $this->groupPortionProductsForDisplay($configured_display['products']);

        $groups = array_merge($configured_display['groups'], $automatic_display['groups']);

        return array(
            'groups' => $groups,
            'products' => $automatic_display['products'],
            'items' => $this->buildSortedDisplayItems($groups, $automatic_display['products'])
        );
    }

    private function buildSortedDisplayItems(array $groups, array $products): array {
        $items = array();

        foreach ($groups as $group) {
            $items[] = array(
                'type' => 'group',
                'sort_order' => isset($group['sort_order']) ? (int)$group['sort_order'] : 0,
                'data' => $group
            );
        }

        foreach ($products as $product) {
            $items[] = array(
                'type' => 'product',
                'sort_order' => isset($product['sort_order']) ? (int)$product['sort_order'] : 0,
                'data' => $product
            );
        }

        usort($items, function ($a, $b) {
            if ($a['sort_order'] === $b['sort_order']) {
                return strcmp($a['type'], $b['type']);
            }

            return $a['sort_order'] <=> $b['sort_order'];
        });

        return $items;
    }

    private function getConfiguredProductGroupsForDisplay(array $products, int $category_id): array {
        $groups = array();
        $singles = $products;

        if (!$products || $category_id <= 0) {
            return array(
                'groups' => $groups,
                'products' => $singles
            );
        }

        $products_by_id = array();

        foreach ($products as $product) {
            $products_by_id[(int)$product['product_id']] = $product;
        }

        $configured_groups = $this->model_common_restaurant_product_groups->getGroupsByCategoryId($category_id, (int)$this->config->get('config_language_id'));
        $used_product_ids = array();

        foreach ($configured_groups as $configured_group) {
            $variants = array();

            foreach ($configured_group['items'] as $item) {
                $product_id = (int)$item['product_id'];

                if (!isset($products_by_id[$product_id])) {
                    continue;
                }

                $variant = $products_by_id[$product_id];
                $variant['portion_label'] = trim((string)$item['variant_label']) !== '' ? $item['variant_label'] : $variant['name'];
                $variant['portion_note'] = trim((string)$item['variant_note']);
                $variant['group_type'] = 'configured';
                $variants[] = $variant;
                $used_product_ids[$product_id] = true;
            }

            if (count($variants) < 2) {
                foreach ($variants as $variant) {
                    unset($used_product_ids[(int)$variant['product_id']]);
                }

                continue;
            }

            $first_variant = $variants[0];
            $image = trim((string)$configured_group['image']);
            $thumb = $first_variant['popup'];
            $popup = $first_variant['popup'];

            if ($image !== '' && is_file(DIR_IMAGE . $image)) {
                $thumb = $this->model_tool_image->resize($image, 800, 600);
                $popup = HTTPS_SERVER . 'image/' . $image;
            }

            $groups[] = array(
                'name' => trim((string)$configured_group['name']) !== '' ? $configured_group['name'] : $first_variant['name'],
                'description' => nl2br(htmlspecialchars(trim((string)$configured_group['description']), ENT_QUOTES, 'UTF-8')),
                'thumb' => $thumb,
                'popup' => $popup,
                'tag' => $first_variant['tag'],
                'type' => 'configured',
                'sort_order' => (int)$configured_group['sort_order'],
                'meta_label' => trim((string)$configured_group['meta_label']),
                'options' => $first_variant['options'],
                'variants' => $variants
            );
        }

        if ($used_product_ids) {
            $singles = array();

            foreach ($products as $product) {
                if (!isset($used_product_ids[(int)$product['product_id']])) {
                    $singles[] = $product;
                }
            }
        }

        return array(
            'groups' => $groups,
            'products' => $singles
        );
    }

    private function groupPortionProductsForDisplay(array $products): array {
        $groups = array();
        $singles = array();

        foreach ($products as $product) {
            $parsed = $this->parseDisplayGroupProduct($product);

            if (!$parsed) {
                $singles[] = $product;
                continue;
            }

            $group_key = $this->slugifyDisplayKey($parsed['base']);

            if (!isset($groups[$group_key])) {
                $groups[$group_key] = array(
                    'name' => $parsed['base'],
                    'description' => !empty($parsed['description']) ? $parsed['description'] : $product['description'],
                    'thumb' => !empty($parsed['thumb']) ? $parsed['thumb'] : $product['popup'],
                    'popup' => !empty($parsed['popup']) ? $parsed['popup'] : $product['popup'],
                    'tag' => $product['tag'],
                    'type' => $parsed['type'],
                    'sort_order' => isset($product['sort_order']) ? (int)$product['sort_order'] : 0,
                    'meta_label' => $parsed['meta_label'],
                    'options' => $product['options'],
                    'variants' => array()
                );
            }

            $product['portion_label'] = $parsed['variant'];
            $product['portion_note'] = $parsed['note'];
            $product['group_type'] = $parsed['type'];
            $groups[$group_key]['variants'][] = $product;
        }

        foreach ($groups as $key => $group) {
            if (count($group['variants']) < 2) {
                foreach ($group['variants'] as $variant) {
                    unset($variant['portion_label'], $variant['portion_note']);
                    $singles[] = $variant;
                }

                unset($groups[$key]);
                continue;
            }

            usort($groups[$key]['variants'], function ($a, $b) {
                if (!empty($a['group_type']) && $a['group_type'] === 'beverage') {
                    return strcmp($a['portion_label'], $b['portion_label']);
                }

                return $this->portionWeightValue($a['portion_label']) <=> $this->portionWeightValue($b['portion_label']);
            });
        }

        return array(
            'groups' => array_values($groups),
            'products' => $singles
        );
    }

    private function parseDisplayGroupProduct(array $product) {
        $portion = $this->parsePortionProductName($product['name']);

        if ($portion) {
            return array(
                'type' => 'portion',
                'variant' => $portion['portion'],
                'base' => $portion['base'],
                'note' => $this->getPortionNote($portion['portion']),
                'meta_label' => 'Süt Kuzu Tandır',
                'description' => '',
                'thumb' => '',
                'popup' => ''
            );
        }

        return $this->parseBeverageGroupProduct($product);
    }

    private function parseBeverageGroupProduct(array $product) {
        $clean = trim(preg_replace('/\s+/', ' ', strip_tags(html_entity_decode($product['name'], ENT_QUOTES, 'UTF-8'))));
        $normalized = $this->slugifyDisplayKey($clean);

        $beverages = array(
            'coca-cola' => 'Coca-Cola',
            'coca-cola-zero' => 'Coca-Cola Zero',
            'fanta' => 'Fanta',
            'sprite' => 'Sprite'
        );

        if (!isset($beverages[$normalized])) {
            return false;
        }

        $shared_image = 'catalog/icons/soguk-taze.jpg';
        $thumb = is_file(DIR_IMAGE . $shared_image) ? $this->model_tool_image->resize($shared_image, 800, 600) : $product['thumb'];
        $popup = is_file(DIR_IMAGE . $shared_image) ? HTTPS_SERVER . 'image/' . $shared_image : $product['popup'];

        return array(
            'type' => 'beverage',
            'variant' => $beverages[$normalized],
            'base' => 'Gazlı İçecekler',
            'note' => 'Soğuk servis edilir',
            'meta_label' => 'Çeşit Seçiniz',
            'description' => 'Coca-Cola, Coca-Cola Zero, Fanta ve Sprite seçenekleriyle soğuk servis edilir.',
            'thumb' => $thumb,
            'popup' => $popup
        );
    }

    private function parsePortionProductName(string $name) {
        $clean = trim(preg_replace('/\s+/', ' ', strip_tags(html_entity_decode($name, ENT_QUOTES, 'UTF-8'))));

        if (!preg_match('/^(\d+(?:[,.]\d+)?\s*(?:gr\.?|gram|kg\.?|kilogram))\s+(.+)$/iu', $clean, $matches)) {
            return false;
        }

        $base = trim($matches[2]);

        if (mb_stripos($base, 'Denizli Kebab', 0, 'UTF-8') === false) {
            return false;
        }

        return array(
            'portion' => trim($matches[1]),
            'base' => $base
        );
    }

    private function getPortionNote(string $portion): string {
        $value = $this->portionWeightValue($portion);

        if ($value <= 250) {
            return 'Tek Porsiyon';
        }

        if ($value <= 350) {
            return 'Bol Porsiyon';
        }

        if ($value <= 500) {
            return 'İki Kişilik';
        }

        if ($value <= 750) {
            return 'Üç Kişilik';
        }

        return 'Dört Kişilik';
    }

    private function portionWeightValue(string $portion): int {
        $normalized = mb_strtolower(str_replace(',', '.', $portion), 'UTF-8');

        if (strpos($normalized, 'kg') !== false || strpos($normalized, 'kilogram') !== false) {
            return (int)round((float)$normalized * 1000);
        }

        return (int)round((float)$normalized);
    }

    private function slugifyDisplayKey(string $value): string {
        $value = mb_strtolower($value, 'UTF-8');
        $search = array('ı', 'ğ', 'ü', 'ş', 'ö', 'ç');
        $replace = array('i', 'g', 'u', 's', 'o', 'c');
        $value = str_replace($search, $replace, $value);
        $value = preg_replace('/[^a-z0-9]+/i', '-', $value);
        return trim($value, '-');
    }

    private function normalizeUpcomingProductName($name) {
        $name = trim((string)$name);
        $patterns = array(
            '/^\s*Pek\s+Yak[ıi\?]nda\s*!\s*/iu',
            '/^\s*Çok\s+Yak[ıi\?]nda\s*!\s*/iu',
            '/^\s*Cok\s+Yak[ıi\?]nda\s*!\s*/iu',
            '/^\s*Coming\s+Soon\s*!\s*/iu'
        );

        $clean_name = preg_replace($patterns, '', $name);
        $is_upcoming = $clean_name !== $name;
        $clean_name = trim((string)$clean_name);

        return array(
            'name' => $clean_name !== '' ? $clean_name : $name,
            'is_upcoming' => $is_upcoming
        );
    }

    private function ensureRestaurantAllergens(): void {
        if ($this->restaurant_allergens_ensured) {
            return;
        }

        $this->restaurant_allergens_ensured = true;

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

    private function getAllergensByProductIds(array $product_ids): array {
        $product_ids = array_values(array_unique(array_filter(array_map('intval', $product_ids))));
        $allergens = array();

        if (!$product_ids) {
            return $allergens;
        }

        $query = $this->db->query("
            SELECT rpa.product_id, ra.image, ra.name
            FROM " . DB_PREFIX . "restaurant_product_allergen rpa
            INNER JOIN " . DB_PREFIX . "restaurant_allergen ra ON (ra.allergen_id = rpa.allergen_id)
            WHERE rpa.product_id IN (" . implode(',', $product_ids) . ")
            AND ra.status = '1'
            ORDER BY rpa.product_id ASC, ra.sort_order ASC, ra.name ASC
        ");

        foreach ($query->rows as $row) {
            $product_id = (int)$row['product_id'];

            if (!isset($allergens[$product_id])) {
                $allergens[$product_id] = array();
            }

            $allergens[$product_id][] = array(
                'img'  => HTTPS_SERVER . 'image/' . $row['image'],
                'name' => $row['name']
            );
        }

        return $allergens;
    }

    private function isProductClosedOnDay($sku, $day) {
        return in_array($day, $this->parseProductClosedDays($sku), true);
    }

    private function isCategoryClosedOnDay($category, $day, $closed_category_ids) {
        $category_id = isset($category['category_id']) ? (int)$category['category_id'] : 0;

        if ($category_id && in_array($category_id, $closed_category_ids, true)) {
            return true;
        }

        return $day === 'Sun' && isset($category['column']) && (int)$category['column'] === 99;
    }

    private function parseProductClosedDays($sku) {
        $sku = trim((string)$sku);

        if ($sku === '') {
            return array();
        }

        if ($sku === '99') {
            return array('Sun');
        }

        if (stripos($sku, 'closed:') !== 0) {
            return array();
        }

        $valid_days = array(
            'mon' => 'Mon',
            'tue' => 'Tue',
            'wed' => 'Wed',
            'thu' => 'Thu',
            'fri' => 'Fri',
            'sat' => 'Sat',
            'sun' => 'Sun'
        );
        $closed_days = array();
        $parts = explode(',', substr($sku, 7));

        foreach ($parts as $part) {
            $key = strtolower(trim($part));

            if (isset($valid_days[$key]) && !in_array($valid_days[$key], $closed_days, true)) {
                $closed_days[] = $valid_days[$key];
            }
        }

        return $closed_days;
    }

    private function applyPricePrefix($price, $prefix) {
        $prefix = trim((string)$prefix);

        if ($price === false || $prefix === '') {
            return $price;
        }

        if (preg_match('/\d/u', $prefix)) {
            return $prefix;
        }

        return $prefix . ' ' . $price;
    }
}
