<?php
class ControllerExtensionModuleAqeMarketingCoupon extends Controller {
	protected $error = array();
	protected $alert = array(
		'error'     => array(),
		'warning'   => array(),
		'success'   => array(),
		'info'      => array()
	);

	public function __construct($registry) {
		parent::__construct($registry);

		if (!$this->config->get('module_admin_quick_edit_installed') || !$this->config->get('module_admin_quick_edit_status')) {
			$this->response->redirect($this->url->link('marketing/coupon', 'user_token=' . $this->session->data['user_token'], true));
		}
	}

	public function index() {
		$this->load->model('marketing/coupon');
		$this->load->model('extension/module/aqe/marketing/coupon');

		$this->load->language('marketing/coupon');
		$this->load->language('extension/module/aqe/marketing/general');
		$this->load->language('extension/module/aqe/marketing/coupon');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->getList();
	}

	public function delete() {
		$this->load->model('marketing/coupon');
		$this->load->model('extension/module/aqe/marketing/coupon');

		$this->load->language('marketing/coupon');
		$this->load->language('extension/module/aqe/marketing/general');
		$this->load->language('extension/module/aqe/marketing/coupon');

		$this->document->setTitle($this->language->get('heading_title'));

		if (isset($this->request->post['selected']) && $this->validateDelete()) {
			foreach ($this->request->post['selected'] as $item_id) {
				$this->model_marketing_coupon->deleteCoupon($item_id);
			}

			$this->session->data['success'] = sprintf($this->language->get('text_success_delete'), count($this->request->post['selected']));

			$url = '';

			foreach($this->config->get('module_admin_quick_edit_marketing_coupons') as $column => $attr) {
				if (isset($this->request->get['filter_' . $column])) {
					$url .= '&filter_' . $column . '=' . urlencode(html_entity_decode($this->request->get['filter_' . $column], ENT_QUOTES, 'UTF-8'));
				}
			}

			if (isset($this->request->get['sort'])) {
				$url .= '&sort=' . $this->request->get['sort'];
			}

			if (isset($this->request->get['order'])) {
				$url .= '&order=' . $this->request->get['order'];
			}

			if (isset($this->request->get['page'])) {
				$url .= '&page=' . $this->request->get['page'];
			}

			if ((int)$this->config->get('module_admin_quick_edit_override_menu_entry')) {
				$this->response->redirect($this->url->link('marketing/coupon', 'user_token=' . $this->session->data['user_token'] . $url . '&aqer=1', true));
			} else {
				$this->response->redirect($this->url->link('extension/module/admin_quick_edit/marketing__coupon__', 'user_token=' . $this->session->data['user_token'] . $url, true));
			}
		}

		$this->getList();
	}

	protected function getList() {
		$data['module_admin_quick_edit_tooltip'] = ($this->config->get('module_admin_quick_edit_quick_edit_on') == 'dblclick') ? $this->language->get('text_double_click_edit') : $this->language->get('text_click_edit');
		$data['module_admin_quick_edit_quick_edit_on'] = $this->config->get('module_admin_quick_edit_quick_edit_on');
		$data['module_admin_quick_edit_row_hover_highlighting'] = $this->config->get('module_admin_quick_edit_row_hover_highlighting');
		$data['module_admin_quick_edit_alternate_row_colour'] = $this->config->get('module_admin_quick_edit_alternate_row_colour');

		$this->document->addScript('view/javascript/aqe/catalog.min.js?v=' . EXTENSION_VERSION);

		$this->document->addStyle('view/stylesheet/aqe/catalog.min.css?v=' . EXTENSION_VERSION);

		$filters = array();

		foreach($this->config->get('module_admin_quick_edit_marketing_coupons') as $column => $attr) {
			$filters[$column] = (isset($this->request->get['filter_' . $column])) ? $this->request->get['filter_' . $column] : null;
		}

		if (isset($this->request->get['sort'])) {
			$sort = $this->request->get['sort'];
		} else {
			$sort = $this->config->get('module_admin_quick_edit_marketing_coupons_default_sort');
		}

		if (isset($this->request->get['order'])) {
			$order = $this->request->get['order'];
		} else {
			$order = $this->config->get('module_admin_quick_edit_marketing_coupons_default_order');
		}

		if (isset($this->request->get['page'])) {
			$page = $this->request->get['page'];
		} else {
			$page = 1;
		}

		$url = '';

		foreach($this->config->get('module_admin_quick_edit_marketing_coupons') as $column => $attr) {
			if (isset($this->request->get['filter_' . $column])) {
				$url .= '&filter_' . $column . '=' . urlencode(html_entity_decode($this->request->get['filter_' . $column], ENT_QUOTES, 'UTF-8'));
			}
		}

		if (isset($this->request->get['sort'])) {
			$url .= '&sort=' . $this->request->get['sort'];
		}

		if (isset($this->request->get['order'])) {
			$url .= '&order=' . $this->request->get['order'];
		}

		if (isset($this->request->get['page'])) {
			$url .= '&page=' . $this->request->get['page'];
		}

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text'      => $this->language->get('text_home'),
			'href'      => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true),
			'active'    => false
		);

		$data['breadcrumbs'][] = array(
			'text'      => $this->language->get('heading_title'),
			'href'      => (int)$this->config->get('module_admin_quick_edit_override_menu_entry') ? $this->url->link('marketing/coupon', 'user_token=' . $this->session->data['user_token'] . $url . '&aqer=1', true) : $this->url->link('extension/module/admin_quick_edit/marketing__coupon__', 'user_token=' . $this->session->data['user_token'] . $url, true),
			'active'    => true
		);

		$data['add'] = $this->url->link('marketing/coupon/add', 'user_token=' . $this->session->data['user_token'] . $url . '&aqer=1', true);
		$data['delete'] = $this->url->link('marketing/coupon/delete', 'user_token=' . $this->session->data['user_token'] . $url . '&aqer=1', true);

		$actions = array(
			'product'           => array('display' => 1, 'index' =>  1, 'short' => 'prod',    'type' =>   'products_qe', 'class' => 'btn-default', 'icon' => '',       'rel' => array('products')),
			'category'          => array('display' => 1, 'index' =>  2, 'short' =>  'cat',    'type' => 'categories_qe', 'class' => 'btn-default', 'icon' => '',       'rel' => array('categories')),
			'edit'              => array('display' => 1, 'index' =>  3, 'short' =>   'ed',    'type' =>          'edit', 'class' => 'btn-primary', 'icon' => 'pencil', 'rel' => array()),
		);

		$actions = array_filter($actions, 'column_display');
		foreach ($actions as $action => $attr) {
			$actions[$action]['name'] = $this->language->get('action_' . $action);
		}
		uasort($actions, 'column_sort');
		$data['coupon_actions'] = $actions;

		$columns = $this->config->get('module_admin_quick_edit_marketing_coupons');
		$columns = array_filter($columns, 'column_display');
		foreach ($columns as $column => $attr) {
			$columns[$column]['name'] = $this->language->get('column_' . $column);
		}
		uasort($columns, 'column_sort');
		$data['coupon_columns'] = $columns;

		$displayed_columns = array_keys($columns);
		$displayed_actions = array_keys($actions);
		$related_columns = array_merge(array_map(function($v) { return isset($v['rel']) ? $v['rel'] : ''; }, $columns), array_map(function($v) { return isset($v['rel']) ? $v['rel'] : ''; }, $actions));

		$data['coupons'] = array();

		$filter_data = array(
			'sort'      => $sort,
			'order'     => $order,
			'start'     => ($page - 1) * $this->config->get('config_limit_admin'),
			'limit'     => $this->config->get('config_limit_admin'),
			'columns'   => $displayed_columns,
			'actions'   => $displayed_actions
		);

		foreach ($filters as $filter => $value) {
			$filter_data['filter_' . $filter] = $value;
		}

		$results = $this->model_extension_module_aqe_marketing_coupon->getCoupons($filter_data);

		$coupon_total = $this->model_extension_module_aqe_marketing_coupon->getTotalCoupons();

		foreach ($results as $result) {
			$_buttons = array();

			foreach ($actions as $action => $attr) {
				switch ($action) {
					case 'edit':
						$_buttons[] = array(
							'type'  => $attr['type'],
							'action'=> $action,
							'title' => $this->language->get('action_' . $action),
							'url'   => html_entity_decode($this->url->link('marketing/coupon/edit', '&coupon_id=' . $result['coupon_id'] . '&user_token=' . $this->session->data['user_token'] . $url . '&aqer=1', true), ENT_QUOTES, 'UTF-8'),
							'icon'  => $attr['icon'],
							'name'  => null,
							'rel'   => json_encode($attr['rel']),
							'class' => $attr['class'],
						);
						break;
					default:
						$_buttons[] = array(
							'type'  => $attr['type'],
							'action'=> $action,
							'title' => $this->language->get('action_' . $action),
							'url'   => null,
							'icon'  => $attr['icon'],
							'name'  => $this->language->get('action_' . $attr['short']),
							'rel'   => json_encode($attr['rel']),
							'data'  => isset($result[$action . '_exist']) ? (int)$result[$action . '_exist'] : 0,
							'class' => $attr['class'],
						);
						break;
				}
			}

			$row = array(
				'coupon_id'  => $result['coupon_id'],
				'selected'   => isset($this->request->post['selected']) && in_array($result['coupon_id'], $this->request->post['selected']),
				'action'     => $_buttons
			);
			if (!is_array($columns)) {
				$row['name'] = $result['name'];
				$row['code'] = $result['code'];
				$row['discount'] = $result['discount'];
				$row['date_start'] = $result['date_start'];
				$row['date_end'] = $result['date_end'];
				$row['status'] = $result['status'];
			} else {
				foreach ($columns as $column => $attr) {
					if (in_array($column, array('logged', 'shipping'))) {
						if (!$this->config->get('module_admin_quick_edit_highlight_yes_no')) {
							$row[$column] = ((int)$result[$column] ? $this->language->get('text_yes') : $this->language->get('text_no'));
						} else {
							$row[$column] = ((int)$result[$column] ? '<span class="label label-success">' . $this->language->get('text_yes') . '</span>' : '<span class="label label-danger">' . $this->language->get('text_no') . '</span>');
						}
					} else if ($column == 'status') {
						if (!$this->config->get('module_admin_quick_edit_highlight_status')) {
							$row[$column] = ((int)$result['status'] ? $this->language->get('text_enabled') : $this->language->get('text_disabled'));
						} else {
							$row[$column] = ((int)$result['status'] ? '<span class="label label-success">' . $this->language->get('text_enabled') . '</span>' : '<span class="label label-danger">' . $this->language->get('text_disabled') . '</span>');
						}
					} else if ($column == 'id') {
						$row[$column] = $result['coupon_id'];
					} else if (in_array($column, array('date_start', 'date_end'))) {
						$date = new DateTime($result[$column]);
						$row[$column] = $date->format("Y-m-d");
					} else if ($column == 'type') {
						$row[$column] = $result[$column] == 'P' ? $this->language->get('text_percent') : $this->language->get('text_amount');
					} else if ($column == 'action') {
						$row[$column] = $_buttons;
					} else if ($column == 'selector') {
						$row[$column] = '';
					} else if ($column == 'products') {
						$products = $this->model_extension_module_aqe_marketing_coupon->getCouponProducts($result['coupon_id']);
						$row[$column] = implode("<br />", $products);
					} else if ($column == 'categories') {
						$this->load->model('catalog/category');
						$categories = $this->model_marketing_coupon->getCouponCategories($result['coupon_id']);
						$category_paths = array();
						foreach($categories as $cat) {
							$category = $this->model_catalog_category->getCategory($cat);
							$category_paths[] = (($category['path']) ? $category['path'] . ' &gt; ' : '') . $category['name'];
						}
						$row[$column] = implode("<br />", $category_paths);
					} else {
						$row[$column] = $result[$column];
					}
				}
			}
			$data['coupons'][] = $row;
		}

		$data['language_id'] = $this->config->get('config_language_id');

		$column_classes = array();
		$type_classes = array();
		$non_sortable = array();

		if (!is_array($columns)) {
			$displayed_columns = array('selector', 'name', 'code', 'discount', 'date_start', 'date_end', 'status', 'action');
			$columns = array();
		} else {
			foreach ($columns as $column => $attr) {
				if (empty($attr['sort'])) {
					$non_sortable[] = 'col_' . $column;
				}

				if (!empty($attr['type']) && !in_array($attr['type'], $type_classes)) {
					$type_classes[] = $attr['type'];
				}

				if (!empty($attr['align'])) {
					if (!empty($attr['type']) && $attr['editable']) {
						$column_classes[] = $attr['align'] . ' ' . $attr['type'];
					} else {
						$column_classes[] = $attr['align'];
					}
				} else {
					if (!empty($attr['type'])) {
						$column_classes[] = $attr['type'];
					} else {
						$column_classes[] = null;
					}
				}
			}
		}

		$data['columns'] = $displayed_columns;
		$data['actions'] = $displayed_actions;
		$data['related'] = $related_columns;
		$data['column_info'] = $columns;
		$data['non_sortable_columns'] = json_encode($non_sortable);
		$data['column_classes'] = $column_classes;
		$data['types'] = $type_classes;

		$data['update_url'] = html_entity_decode($this->url->link('extension/module/admin_quick_edit/marketing__coupon__quick_update', 'user_token=' . $this->session->data['user_token'], true));
		$data['load_popup_url'] = html_entity_decode($this->url->link('extension/module/admin_quick_edit/marketing__coupon__load_popup', 'user_token=' . $this->session->data['user_token'], true));
		$data['refresh_url'] = html_entity_decode($this->url->link('extension/module/admin_quick_edit/marketing__coupon__refresh_data', 'user_token=' . $this->session->data['user_token'], true));

		$data['yes_no_select'] = addslashes(json_encode(array(array("id" => "0", "value" => $this->language->get('text_no')), array("id" => "1", "value" => $this->language->get('text_yes')))));
		$data['status_select'] = addslashes(json_encode(array(array("id" => "0", "value" => $this->language->get('text_disabled')), array("id" => "1", "value" => $this->language->get('text_enabled')))));
		$data['type_select'] = addslashes(json_encode(array(array("id" => "P", "value" => $this->language->get('text_percent')), array("id" => "F", "value" => $this->language->get('text_amount')))));

		$data['batch_edit'] = (int)$this->config->get('module_admin_quick_edit_batch_edit');

		$data['user_token'] = $this->session->data['user_token'];

		$url = '';

		foreach ($this->config->get('module_admin_quick_edit_marketing_coupons') as $column => $attr) {
			if (isset($this->request->get['filter_' . $column])) {
				$url .= '&filter_' . $column . '=' . urlencode(html_entity_decode($this->request->get['filter_' . $column], ENT_QUOTES, 'UTF-8'));
			}
		}
		if ($order == 'ASC') {
			$url .= '&order=DESC';
		} else {
			$url .= '&order=ASC';
		}

		if (isset($this->request->get['page'])) {
			$url .= '&page=' . $this->request->get['page'];
		}

		$data['sorts'] = array();
		foreach ($this->config->get('module_admin_quick_edit_marketing_coupons') as $column => $attr) {
			if ((int)$this->config->get('module_admin_quick_edit_override_menu_entry')) {
				$data['sorts'][$column] = $this->url->link('marketing/coupon', 'user_token=' . $this->session->data['user_token'] . '&sort=' . $attr['sort'] . $url . '&aqer=1', true);
			} else {
				$data['sorts'][$column] = $this->url->link('extension/module/admin_quick_edit/marketing__coupon__', 'user_token=' . $this->session->data['user_token'] . '&sort=' . $attr['sort'] . $url, true);
			}
		}

		$url = '';

		foreach ($this->config->get('module_admin_quick_edit_marketing_coupons') as $column => $attr) {
			if (isset($this->request->get['filter_' . $column])) {
				$url .= '&filter_' . $column . '=' . urlencode(html_entity_decode($this->request->get['filter_' . $column], ENT_QUOTES, 'UTF-8'));
			}
		}

		if (isset($this->request->get['sort'])) {
			$url .= '&sort=' . $this->request->get['sort'];
		}

		if (isset($this->request->get['order'])) {
			$url .= '&order=' . $this->request->get['order'];
		}

		$pagination = new Pagination();
		$pagination->total = $coupon_total;
		$pagination->page = $page;
		$pagination->limit = $this->config->get('config_limit_admin');

		if ((int)$this->config->get('module_admin_quick_edit_override_menu_entry')) {
			$pagination->url = $this->url->link('marketing/coupon', 'user_token=' . $this->session->data['user_token'] . $url . '&page={page}' . '&aqer=1', true);
		} else {
			$pagination->url = $this->url->link('extension/module/admin_quick_edit/marketing__coupon__', 'user_token=' . $this->session->data['user_token'] . $url . '&page={page}', true);
		}

		$data['pagination'] = $pagination->render();

		$data['results'] = sprintf($this->language->get('text_pagination'), ($coupon_total) ? (($page - 1) * $this->config->get('config_limit_admin')) + 1 : 0, ((($page - 1) * $this->config->get('config_limit_admin')) > ($coupon_total - $this->config->get('config_limit_admin'))) ? $coupon_total : ((($page - 1) * $this->config->get('config_limit_admin')) + $this->config->get('config_limit_admin')), $coupon_total, ceil($coupon_total / $this->config->get('config_limit_admin')));

		if (isset($this->session->data['error'])) {
			$this->error = $this->session->data['error'];

			unset($this->session->data['error']);
		}

		if (isset($this->error['warning'])) {
			$this->alert['warning']['warning'] = $this->error['warning'];
		}

		if (isset($this->error['error'])) {
			$this->alert['error']['error'] = $this->error['error'];
		}

		if (isset($this->session->data['success'])) {
			$this->alert['success']['success'] = $this->session->data['success'];

			unset($this->session->data['success']);
		}

		$data['filters'] = $filters;
		$data['alerts'] = $this->alert;

		$data['sort'] = $sort;
		$data['order'] = $order;

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$template = 'extension/module/aqe/marketing/coupon_list';

		$this->response->setOutput($this->load->view($template, $data));
	}

	public function load_popup() {
		$this->load->model('marketing/coupon');
		$this->load->model('extension/module/aqe/marketing/coupon');

		$this->load->language('marketing/coupon');
		$this->load->language('extension/module/aqe/marketing/general');
		$this->load->language('extension/module/aqe/marketing/coupon');

		$response = array();

		if ($this->request->server['REQUEST_METHOD'] == 'POST' && $this->validateLoadPopup($this->request->post)) {
			$data['error_warning'] = '';
			list($data['parameter'], $data['i_id']) = explode("-", $this->request->post['id']);

			$data['user_token'] = $this->session->data['user_token'];

			$response["success"] = 1;

			switch ($data['parameter']) {
				case 'product':
				case 'products':
					$data['text_autocomplete'] = $this->language->get('text_autocomplete');
					$data['autocomplete'] = html_entity_decode($this->url->link('catalog/product/autocomplete', 'user_token=' . $this->session->data['user_token'] . '&filter_name=', true));
					$data['item_label'] = 'name';
					$data['item_value'] = 'product_id';
					$items = array();
					$products = $this->model_extension_module_aqe_marketing_coupon->getCouponProducts($data['i_id']);
					foreach ($products as $product_id => $name) {
						$items[] = array("id" => $product_id, "name" => $name);
					}
					$data['items'] = $items;
					break;
				case 'category':
				case 'categories':
					$this->load->model('catalog/category');

					$data['text_autocomplete'] = $this->language->get('text_autocomplete');
					$data['autocomplete'] = html_entity_decode($this->url->link('catalog/category/autocomplete', 'user_token=' . $this->session->data['user_token'] . '&filter_name=', true));
					$data['item_label'] = 'name';
					$data['item_value'] = 'category_id';
					$items = array();
					$categories = $this->model_marketing_coupon->getCouponCategories($data['i_id']);
					foreach ($categories as $category_id) {
						$category_info = $this->model_catalog_category->getCategory($category_id);

						if ($category_info) {
							$items[] = array("id" => $category_id, "name" => ($category_info['path'] ? $category_info['path'] . ' &gt; ' : '') . $category_info['name']);
						}
					}
					$data['items'] = $items;
					break;
				default:
					$response["success"] = 0;
					$this->alert['error']['load'] = $this->language->get('error_load_popup');
					break;
			}
			$response['title'] = $this->language->get('action_' . $data['parameter']);
		} else {
			$this->alert['error']['load'] = $this->language->get('error_load_popup');
		}

		$template = 'extension/module/aqe/marketing/quick_edit_form';

		$response['popup'] = $this->load->view($template, $data);

		$response = array_merge($response, array("errors" => $this->error), array("alerts" => $this->alert));

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($response));
	}

	public function refresh_data() {
		$this->load->model('marketing/coupon');
		$this->load->model('extension/module/aqe/marketing/coupon');

		$this->load->language('marketing/coupon');
		$this->load->language('extension/module/aqe/marketing/general');
		$this->load->language('extension/module/aqe/marketing/coupon');

		$response = array();

		if ($this->request->server['REQUEST_METHOD'] == 'POST' && $this->validateRefreshData($this->request->post)) {
			$response['values'] = array();

			foreach ($this->request->post['data'] as $column => $coupons) {
				foreach ($coupons as $id) {
					switch ($column) {
						case 'products':
							$products = $this->model_extension_module_aqe_marketing_coupon->getCouponProducts($id);
							$response['values'][$id][$column] = implode("<br />", $products);
							break;
						case 'categories':
							$this->load->model('catalog/category');
							$categories = $this->model_marketing_coupon->getCouponCategories($id);
							$category_paths = array();
							foreach($categories as $cat) {
								$category = $this->model_catalog_category->getCategory($cat);
								$category_paths[] = (($category['path']) ? $category['path'] . ' &gt; ' : '') . $category['name'];
							}
							$response['values'][$id][$column] = implode("<br />", $category_paths);
							break;
						default:
							$response['value'] = "";
							break;
					}
				}
			}
			$response['success'] = 1;
		}

		$response = array_merge($response, array("errors" => $this->error), array("alerts" => $this->alert));

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($response));
	}

	public function quick_update() {
		$this->load->model('marketing/coupon');
		$this->load->model('extension/module/aqe/marketing/coupon');

		$this->load->language('marketing/coupon');
		$this->load->language('extension/module/aqe/marketing/general');
		$this->load->language('extension/module/aqe/marketing/coupon');

		$response = array();

		if ($this->request->server['REQUEST_METHOD'] == 'POST' && $this->validateUpdateData($this->request->post)) {
			list($column, $id) = explode("-", $this->request->post['id']);
			$id = (array)$id;
			$value = $this->request->post['new'];
			$lang_id = isset($this->request->post['lang_id']) ? $this->request->post['lang_id'] : null;
			$expression = !is_array($value) && strpos(trim($value), "#") === 0 && preg_match('/^#\s*(?P<operator>[+-\/\*])\s*(?P<operand>-?\d+\.?\d*)(?P<percent>%)?$/', trim($value)) === 1;

			if (isset($this->request->post['ids'])) {
				$id = array_unique(array_merge($id, (array)$this->request->post['ids']));
			}

			$results = array('done' => array(), 'failed' => array());
			$_results = array();

			foreach ((array)$id as $_id) {
				$result = $this->model_extension_module_aqe_marketing_coupon->quickEditCoupon($_id, $column, $value, $lang_id, $this->request->post);
				if ($result !== false) {
					$_results[$_id] = $result;
					$results['done'][] = $_id;
				} else {
					$results['failed'][] = $_id;
				}
			}

			$response['results'] = $results;

			if ($results['done']) {
				$this->alert['success']['update'] = $this->language->get('text_success');
				$response['success'] = 1;

				if (in_array($column, array('product', 'category'))) {
					$response['value'] = '';
					$response['values']['*'] = $response['value'];
				} else if (in_array($column, array('logged', 'shipping'))) {
					if (!$this->config->get('module_admin_quick_edit_highlight_yes_no')) {
						$response['value'] = ((int)$value) ? $this->language->get('text_yes') : $this->language->get('text_no');
					} else {
						$response['value'] = ((int)$value) ? '<span class="label label-success">' . $this->language->get('text_yes') . '</span>' : '<span class="label label-danger">' . $this->language->get('text_no') . '</span>';
					}
					$response['values']['*'] = $response['value'];
				} else if (in_array($column, array('type'))) {
					$response['value'] = ($value == 'P') ? $this->language->get('text_percent') : $this->language->get('text_amount');
					$response['values']['*'] = $response['value'];
				} else if ($column == 'status') {
					if (!$this->config->get('module_admin_quick_edit_highlight_status')) {
						$response['value'] = ((int)$value) ? $this->language->get('text_enabled') : $this->language->get('text_disabled');
					} else {
						$response['value'] = ((int)$value) ? '<span class="label label-success">' . $this->language->get('text_enabled') . '</span>' : '<span class="label label-danger">' . $this->language->get('text_disabled') . '</span>';
					}
					$response['values']['*'] = $response['value'];
				} else if (in_array($column, array('uses_total', 'uses_customer'))) {
					if ($expression) {
						$response['value'] = (int)$_results[$id[0]];
						if (count($id) > 1) {
							foreach ($id as $_id) {
								$response['values'][$_id] = (int)$_results[$_id];
							}
						}
					} else {
						$response['value'] = (int)$value;
						$response['values']['*'] = $response['value'];
					}
				} else if (in_array($column, array('total', 'discount'))) {
					if ($expression) {
						$response['value'] = sprintf("%.4f",round((float)$_results[$id[0]], 4));
						if (count($id) > 1) {
							foreach ($id as $_id) {
								$response['values'][$_id] = sprintf("%.4f",round((float)$_results[$_id], 4));
							}
						}
					} else {
						$response['value'] = sprintf("%.4f",round((float)$value, 4));
						$response['values']['*'] = $response['value'];
					}
				} else if (in_array($column, array('product', 'products'))) {
					$products = $this->model_extension_module_aqe_marketing_coupon->getCouponProducts($id[0]);
					if ($products)
						$response['value'] = implode('<br/>', $products);
					else
						$response['value'] = '';
					$response['values']['*'] = $response['value'];
				} else if (in_array($column, array('category', 'categories'))) {
					$this->load->model('catalog/category');
					$categories = $this->model_marketing_coupon->getCouponCategories($id[0]);
					$category_paths = array();
					foreach($categories as $cat) {
						$category = $this->model_catalog_category->getCategory($cat);
						$category_paths[] = (($category['path']) ? $category['path'] . ' &gt; ' : '') . $category['name'];
					}

					if ($category_paths)
						$response['value'] = implode('<br/>', $category_paths);
					else
						$response['value'] = '';
					$response['values']['*'] = $response['value'];
				} else {
					$response['value'] = $value;
					$response['values']['*'] = $response['value'];
				}
			} else {
				$this->alert['error']['result'] = $this->language->get('error_update');
			}
		}

		$response = array_merge($response, array("errors" => $this->error), array("alerts" => $this->alert));

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($response));
	}

	protected function validateDelete() {
		return $this->validatePermissions();
	}

	protected function validateLoadPopup(&$data) {
		$errors = !$this->validatePermissions();

		if (!isset($data['id']) || strpos($data['id'], "-") === false) {
			$errors = true;
			$this->alert['error']['request'] = $this->language->get('error_update');
		}

		return !$errors;
	}

	protected function validateUpdateData(&$data) {
		$errors = !$this->validatePermissions();

		if (!isset($data['id']) || strpos($data['id'], "-") === false) {
			$errors = true;
			$this->alert['error']['request'] = $this->language->get('error_update');
			return false;
		}

		list($column, $id) = explode("-", $data['id']);

		if (!isset($data['old'])) {
			$errors = true;
			$this->alert['error']['request'] = $this->language->get('error_update');
		}

		if (!isset($data['new'])) {
			$errors = true;
			$this->alert['error']['request'] = $this->language->get('error_update');
		}

		if (in_array($column, array('uses_total', 'uses_customer', 'total', 'discount')) && strpos(trim($data['new']), "#") === 0 && preg_match('/^#\s*(?P<operation>[+-\/\*])\s*(?P<operand>-?\d+\.?\d*)(?P<percent>%)?$/', trim($data['new'])) !== 1) {
			$errors = true;
			$this->alert['error']['expression'] = $this->language->get('error_expression');
		}

		if ($column == "name" && ((utf8_strlen(trim($data['new'])) < 3) || utf8_strlen(trim($data['new'])) > 128)) {
			$errors = true;
			$this->alert['error']['name'] = $this->language->get('error_name');
		}

		if ($column == "code") {
			$coupon_info = $this->model_marketing_coupon->getCouponByCode($data['new']);

			if (isset($data['ids']) && count((array)$data['ids']) > 1) {
				$errors = true;
				$this->alert['error']['request'] = $this->language->get('error_batch_edit_code');
			} else if (utf8_strlen(trim($data['new'])) < 3 || utf8_strlen(trim($data['new'])) > 10) {
				$errors = true;
				$this->alert['error']['code'] = $this->language->get('error_code');
			} else if ($coupon_info && $coupon_info['coupon_id'] != $id) {
				$errors = true;
				$this->alert['error']['code'] = $this->language->get('error_exists');
			}
		}

		if ($this->error && !isset($this->alert['warning']['warning'])) {
			$this->alert['warning']['warning'] = $this->language->get('error_warning');
		}

		return !$errors;
	}

	protected function validateRefreshData(&$data) {
		$errors = !$this->validatePermissions();

		if (!isset($data['data'])) {
			$errors = true;
			$this->alert['error']['request'] = $this->language->get('error_update');
			return false;
		}

		return !$errors;
	}

	private function validatePermissions() {
		if (!$this->user->hasPermission('modify', 'marketing/coupon') || !$this->user->hasPermission('modify', 'extension/module/admin_quick_edit')) {
			$this->alert['error']['permission'] = $this->language->get('error_permission');
			return false;
		} else {
			return true;
		}
	}
}
