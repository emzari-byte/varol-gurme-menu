<?php
class ControllerCommonMenu extends Controller {
	public function index() {
		$this->load->language('common/menu');
		$data['text_regional_products'] = $this->language->get('text_regional_products');
		$data['text_most_preferred'] = $this->language->get('text_most_preferred');

		$this->load->model('common/restaurant_settings');
		$data['title'] = $this->config->get('config_meta_title');
		$data['description'] = $this->config->get('config_meta_description');
		$data['keywords'] = $this->config->get('config_meta_keyword');
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

		$date = date('Y-m-d');
		$timestamp = strtotime($date);
		$gun = date('D', $timestamp);

		$qr = isset($this->request->get['qr']) ? trim((string)$this->request->get['qr']) : '';

		if ($qr === '' && !empty($this->session->data['menu_qr_token'])) {
			$qr = $this->session->data['menu_qr_token'];
		}

        $this->load->model('common/menu_order');

        if ($qr !== '') {
            $this->model_common_menu_order->ensureTableSessionFromQr($qr);
            $qr = !empty($this->session->data['menu_qr_token']) ? $this->session->data['menu_qr_token'] : $qr;
        }

		$data['qr'] = $qr;
		$data['table_id'] = !empty($this->session->data['menu_table_id']) ? (int)$this->session->data['menu_table_id'] : 0;
		$data['table_no'] = !empty($this->session->data['menu_table_no']) ? (int)$this->session->data['menu_table_no'] : 0;
		$data['table_name'] = !empty($this->session->data['menu_table_name']) ? $this->session->data['menu_table_name'] : '';
        $data['can_order'] = $this->model_common_menu_order->canOrder();
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

		$qr_param = $qr ? 'qr=' . urlencode($qr) : '';

		if (!empty($this->session->data['language_id'])) {
			$active_language_id = (int)$this->session->data['language_id'];
		} else {
			$active_language_id = (int)$this->config->get('config_language_id');
		}

		$this->load->model('catalog/category');
		$this->load->model('tool/image');
		$this->load->model('catalog/product');
		$this->load->model('common/restaurant_home_products');

		$data['categories'] = array();

		$categories = $this->model_catalog_category->getCategories(0);

		foreach ($categories as $category) {
			if ($gun == 'Sun' && $category['column'] == '99') {
				continue;
			}

			if ($category['image']) {
				$thumb = $this->model_tool_image->resize($category['image'], 90, 90);
			} else {
				$thumb = $this->model_tool_image->resize('no_image.png', 90, 90);
			}

			if ((int)$category['category_id'] === 117) {
				$href = $this->url->link('common/menu_recommendation', $qr_param, true);
			} else {
				$href = $this->url->link('product/category', 'path=' . (int)$category['category_id'] . ($qr_param ? '&' . $qr_param : ''), true);
			}

			$data['categories'][] = array(
				'name'        => $category['name'],
				'thumb'       => $thumb,
				'href'        => $href,
				'category_id' => (int)$category['category_id'],
				'is_ai_menu'  => ((int)$category['category_id'] === 117 ? 1 : 0)
			);
		}

		$regional_section = $this->model_common_restaurant_home_products->getSection('regional', $active_language_id);
		$popular_section = $this->model_common_restaurant_home_products->getSection('popular', $active_language_id);

		$data['yoreselname'] = $regional_section['name'];
		$data['yoreselpro'] = $this->buildHomeProducts($regional_section['products'], $gun, $active_language_id, $show_prices, 750, 550, false);

		$data['encoktercihname'] = $popular_section['name'];
		$data['encoktercihpro'] = $this->buildHomeProducts($popular_section['products'], $gun, $active_language_id, $show_prices, 150, 110, true);

		$data['home'] = $this->url->link('common/home', $qr_param, true);
		$data['serv'] = HTTPS_SERVER;
		$data['menu_footer'] = $this->load->controller('common/menu_footer');

		$this->response->setOutput($this->load->view('common/menu', $data));
	}

	private function buildHomeProducts($product_ids, $day, $language_id, $show_prices, $width, $height, $use_original_image) {
		$products = array();

		foreach ($product_ids as $product_id) {
			$product_info = $this->model_catalog_product->getProduct($product_id);

			if (!$product_info) {
				continue;
			}

			if ($day == 'Sun' && isset($product_info['sku']) && $product_info['sku'] == '99') {
				continue;
			}

			if ($product_info['image']) {
				if ($use_original_image) {
					$image = HTTPS_SERVER . 'image/' . $product_info['image'];
				} else {
					$image = $this->model_tool_image->resize($product_info['image'], $width, $height);
				}
			} else {
				$image = $this->model_tool_image->resize('no_image.png', $width, $height);
			}

			if ($this->customer->isLogged() || !$this->config->get('config_customer_price')) {
				$price = $this->currency->format(
					$this->tax->calculate($product_info['price'], $product_info['tax_class_id'], $this->config->get('config_tax')),
					$this->session->data['currency']
				);
			} else {
				$price = false;
			}

			if (!is_null($product_info['special']) && (float)$product_info['special'] >= 0) {
				$special = $this->currency->format(
					$this->tax->calculate($product_info['special'], $product_info['tax_class_id'], $this->config->get('config_tax')),
					$this->session->data['currency']
				);
			} else {
				$special = false;
			}

			if (!empty($product_info['location'])) {
				$price = $product_info['location'];
			}

			if (!$show_prices) {
				$price = false;
				$special = false;
			}

			$options = array();

			$option_query = $this->db->query("
				SELECT ov.image, ovd.name
				FROM " . DB_PREFIX . "option_value ov
				LEFT JOIN " . DB_PREFIX . "product_option_value pov ON (ov.option_value_id = pov.option_value_id)
				LEFT JOIN " . DB_PREFIX . "option_value_description ovd ON (ov.option_value_id = ovd.option_value_id)
				WHERE pov.product_id = '" . (int)$product_info['product_id'] . "'
				AND ovd.language_id = '" . (int)$language_id . "'
			");

			foreach ($option_query->rows as $option) {
				$options[] = array(
					'img'  => HTTPS_SERVER . 'image/' . $option['image'],
					'name' => $option['name']
				);
			}

			$products[] = array(
				'product_id'  => (int)$product_info['product_id'],
				'name'        => $product_info['name'],
				'description' => html_entity_decode(strip_tags($product_info['description']), ENT_QUOTES, 'UTF-8'),
				'thumb'       => $image,
				'options'     => $options,
				'tag'         => $product_info['tag'],
				'price'       => $price,
				'special'     => $special,
				'sku'         => $product_info['sku'],
				'upc'         => $product_info['upc'],
				'ean'         => $product_info['ean'],
				'jan'         => $product_info['jan'],
				'isbn'        => $product_info['isbn'],
			);
		}

		return $products;
	}
}
