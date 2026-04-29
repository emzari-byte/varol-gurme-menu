<?php
/**
  * Remaps an array keys to SQL id fields
  *
  **/
if (!function_exists('array_remap_key_to_id')) {
	function array_remap_key_to_id($key, $results) {
		$new_array = array();

		foreach ($results as $result) {
			if (isset($result[$key])) {
				$new_array[$result[$key]] = $result;
			}
		}

		return $new_array;
	}
}

class ControllerExtensionModuleAqeCatalogProduct extends Controller {
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
			$this->response->redirect($this->url->link('catalog/product', 'user_token=' . $this->session->data['user_token'], true));
		}
	}

	public function index() {
		$this->load->model('catalog/product');
		$this->load->model('extension/module/aqe/catalog/product');

		$this->load->language('catalog/product');
		$this->load->language('extension/module/aqe/catalog/general');
		$this->load->language('extension/module/aqe/catalog/product');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->getList();
	}

	public function delete() {
		$this->load->model('catalog/product');
		$this->load->model('extension/module/aqe/catalog/product');

		$this->load->language('catalog/product');
		$this->load->language('extension/module/aqe/catalog/general');
		$this->load->language('extension/module/aqe/catalog/product');

		$this->document->setTitle($this->language->get('heading_title'));

		if (isset($this->request->post['selected']) && $this->validateDelete()) {
			foreach ($this->request->post['selected'] as $product_id) {
				$this->model_catalog_product->deleteProduct($product_id);
			}

			$this->session->data['success'] = sprintf($this->language->get('text_success_delete'), count($this->request->post['selected']));

			$url = '';

			foreach($this->config->get('module_admin_quick_edit_catalog_products') as $column => $attr) {
				if (isset($this->request->get['filter_' . $column])) {
					$url .= '&filter_' . $column . '=' . urlencode(html_entity_decode($this->request->get['filter_' . $column], ENT_QUOTES, 'UTF-8'));
				}
			}

			if (isset($this->request->get['filter_sub_category'])) {
				$url .= '&filter_sub_category=' . urlencode(html_entity_decode($this->request->get['filter_sub_category'], ENT_QUOTES, 'UTF-8'));
			}

			if (isset($this->request->get['filter_special_price'])) {
				$url .= '&filter_special_price=' . urlencode(html_entity_decode($this->request->get['filter_special_price'], ENT_QUOTES, 'UTF-8'));
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
				$this->response->redirect($this->url->link('catalog/product', 'user_token=' . $this->session->data['user_token'] . $url . '&aqer=1', true));
			} else {
				$this->response->redirect($this->url->link('extension/module/admin_quick_edit/catalog__product__', 'user_token=' . $this->session->data['user_token'] . $url, true));
			}
		}

		$this->getList();
	}

	public function copy() {
		$this->load->model('catalog/product');
		$this->load->model('extension/module/aqe/catalog/product');

		$this->load->language('catalog/product');
		$this->load->language('extension/module/aqe/catalog/general');
		$this->load->language('extension/module/aqe/catalog/product');

		$this->document->setTitle($this->language->get('heading_title'));

		if (isset($this->request->post['selected']) && $this->validateCopy()) {
			foreach ($this->request->post['selected'] as $product_id) {
				$this->model_catalog_product->copyProduct($product_id);
			}

			$this->session->data['success'] = sprintf($this->language->get('text_success_copy'), count($this->request->post['selected']));

			$url = '';

			foreach($this->config->get('module_admin_quick_edit_catalog_products') as $column => $attr) {
				if (isset($this->request->get['filter_' . $column])) {
					$url .= '&filter_' . $column . '=' . urlencode(html_entity_decode($this->request->get['filter_' . $column], ENT_QUOTES, 'UTF-8'));
				}
			}

			if (isset($this->request->get['filter_sub_category'])) {
				$url .= '&filter_sub_category=' . urlencode(html_entity_decode($this->request->get['filter_sub_category'], ENT_QUOTES, 'UTF-8'));
			}

			if (isset($this->request->get['filter_special_price'])) {
				$url .= '&filter_special_price=' . urlencode(html_entity_decode($this->request->get['filter_special_price'], ENT_QUOTES, 'UTF-8'));
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
				$this->response->redirect($this->url->link('catalog/product', 'user_token=' . $this->session->data['user_token'] . $url . '&aqer=1', true));
			} else {
				$this->response->redirect($this->url->link('extension/module/admin_quick_edit/catalog__product__', 'user_token=' . $this->session->data['user_token'] . $url, true));
			}
		}

		$this->getList();
	}

	protected function getList() {
		$data['module_admin_quick_edit_tooltip'] = ($this->config->get('module_admin_quick_edit_quick_edit_on') == 'dblclick') ? $this->language->get('text_double_click_edit') : $this->language->get('text_click_edit');
		$data['module_admin_quick_edit_quick_edit_on'] = $this->config->get('module_admin_quick_edit_quick_edit_on');
		$data['module_admin_quick_edit_row_hover_highlighting'] = $this->config->get('module_admin_quick_edit_row_hover_highlighting');
		$data['module_admin_quick_edit_alternate_row_colour'] = $this->config->get('module_admin_quick_edit_alternate_row_colour');
		$data['module_admin_quick_edit_list_view_image_width'] = $this->config->get('module_admin_quick_edit_list_view_image_width');
		$data['module_admin_quick_edit_list_view_image_height'] = $this->config->get('module_admin_quick_edit_list_view_image_height');

		$this->document->addScript('view/javascript/aqe/catalog.min.js?v=' . EXTENSION_VERSION);

		$this->document->addStyle('view/stylesheet/aqe/catalog.min.css?v=' . EXTENSION_VERSION);

		$filters = array();

		foreach($this->config->get('module_admin_quick_edit_catalog_products') as $column => $attr) {
			$filters[$column] = (isset($this->request->get['filter_' . $column])) ? $this->request->get['filter_' . $column] : null;
		}
		$filters['sub_category'] = (isset($this->request->get['filter_sub_category'])) ? $this->request->get['filter_sub_category'] : $this->config->get('module_admin_quick_edit_catalog_products_filter_sub_category');
		$filters['special_price'] = (isset($this->request->get['filter_special_price'])) ? $this->request->get['filter_special_price'] : '';

		if (isset($this->request->get['sort'])) {
			$sort = $this->request->get['sort'];
		} else {
			$sort = $this->config->get('module_admin_quick_edit_catalog_products_default_sort');
		}

		if (isset($this->request->get['order'])) {
			$order = $this->request->get['order'];
		} else {
			$order = $this->config->get('module_admin_quick_edit_catalog_products_default_order');
		}

		if (isset($this->request->get['page'])) {
			$page = $this->request->get['page'];
		} else {
			$page = 1;
		}

		$url = '';

		foreach($this->config->get('module_admin_quick_edit_catalog_products') as $column => $attr) {
			if (isset($this->request->get['filter_' . $column])) {
				$url .= '&filter_' . $column . '=' . urlencode(html_entity_decode($this->request->get['filter_' . $column], ENT_QUOTES, 'UTF-8'));
			}
		}

		if (isset($this->request->get['filter_sub_category'])) {
			$url .= '&filter_sub_category=' . urlencode(html_entity_decode($this->request->get['filter_sub_category'], ENT_QUOTES, 'UTF-8'));
		}

		if (isset($this->request->get['filter_special_price'])) {
			$url .= '&filter_special_price=' . urlencode(html_entity_decode($this->request->get['filter_special_price'], ENT_QUOTES, 'UTF-8'));
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
			'href'      => (int)$this->config->get('module_admin_quick_edit_override_menu_entry') ? $this->url->link('catalog/product', 'user_token=' . $this->session->data['user_token'] . $url . '&aqer=1', true) : $this->url->link('extension/module/admin_quick_edit/catalog__product__', 'user_token=' . $this->session->data['user_token'] . $url, true),
			'active'    => true
		);

		$data['add'] = $this->url->link('catalog/product/add', 'user_token=' . $this->session->data['user_token'] . $url . '&aqer=1', true);
		$data['copy'] = $this->url->link('catalog/product/copy', 'user_token=' . $this->session->data['user_token'] . $url . '&aqer=1', true);
		$data['delete'] = $this->url->link('catalog/product/delete', 'user_token=' . $this->session->data['user_token'] . $url . '&aqer=1', true);

		$_url = new Url(HTTP_CATALOG, HTTPS_CATALOG);

		if ($this->config->get('config_seo_url')) {
			$seo_url_file = 'controller/startup/seo_url.php';

			if (class_exists('VQMod')) {
				if (is_file(DIR_MODIFICATION . 'catalog/' . $seo_url_file)) {
					require_once(\VQMod::modCheck(DIR_MODIFICATION . 'catalog/' . $seo_url_file, DIR_CATALOG . $seo_url_file));
				} else {
					require_once(DIR_CATALOG . $seo_url_file);
				}
			} else {
				if (is_file(DIR_MODIFICATION . 'catalog/' . $seo_url_file)) {
					require_once(DIR_MODIFICATION . 'catalog/' . $seo_url_file);
				} else {
					require_once(DIR_CATALOG . $seo_url_file);
				}
			}
			$seo_url = new ControllerStartupSeoUrl($this->registry);

			$_url->addRewrite($seo_url);
		}

		$this->load->model('setting/store');

		$stores = $this->model_setting_store->getStores();

		$multistore = count($stores);

		$data['stores'] = array();

		$data['stores'][0] = array(
			'name'  => $this->config->get('config_name'),
			'url'   => HTTP_CATALOG,
			'ssl'   => HTTPS_CATALOG,
			'secure'=> $this->config->get('config_secure'),
			'make'  => $_url
		);

		foreach ($stores as $store) {
			$query = $this->db->query("SELECT value FROM " . DB_PREFIX . "setting WHERE store_id = '" . (int)$store['store_id'] . "' AND `key` = 'config_secure'");
			$secure = (int)$query->row['value'];

			$__url = new Url($store['url'], $store['ssl']);

			$data['stores'][$store['store_id']] = array(
				'name'  => $store['name'],
				'url'   => $store['url'],
				'ssl'   => $store['ssl'],
				'secure'=> $secure,
				'make'  => $__url
			);

			if ($this->config->get('config_seo_url')) {
				$data['stores'][$store['store_id']]['make']->addRewrite($seo_url);
			}
		}

		$actions = $this->config->get('module_admin_quick_edit_catalog_products_actions');
		$actions = array_filter($actions, 'column_display');
		foreach ($actions as $action => $attr) {
			$actions[$action]['name'] = $this->language->get('action_' . $action);
		}
		uasort($actions, 'column_sort');
		$data['product_actions'] = $actions;

		$columns = $this->config->get('module_admin_quick_edit_catalog_products');
		$columns = array_filter($columns, 'column_display');
		foreach ($columns as $column => $attr) {
			$columns[$column]['name'] = $this->language->get('column_' . $column);

			if ($column == 'view_in_store' && !$multistore) {
				unset($columns[$column]);
			}
		}
		uasort($columns, 'column_sort');
		$data['product_columns'] = $columns;

		$displayed_columns = array_keys($columns);
		$displayed_actions = array_keys($actions);
		$related_columns = array_merge(array_map(function($v) { return $v['rel']; }, $columns), array_map(function($v) { return $v['rel']; }, $actions));

		$data['products'] = array();

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

		$this->load->model('tool/image');

		$results = $this->model_extension_module_aqe_catalog_product->getProducts($filter_data);

		$product_total = $this->model_extension_module_aqe_catalog_product->getTotalProducts();

		foreach ($results as $result) {
			$_buttons = array();

			foreach ($actions as $action => $attr) {
				switch ($action) {
					case 'edit':
						$_buttons[] = array(
							'type'  => $attr['type'],
							'action'=> $action,
							'title' => $this->language->get('action_' . $action),
							'url'   => html_entity_decode($this->url->link('catalog/product/edit', '&product_id=' . $result['product_id'] . '&user_token=' . $this->session->data['user_token'] . $url . '&aqer=1', true), ENT_QUOTES, 'UTF-8'),
							'icon'  => 'pencil',
							'name'  => null,
							'rel'   => json_encode(array()),
							'class' => $attr['class'],
						);
						break;
					case 'view':
						$_buttons[] = array(
							'type'  => $attr['type'],
							'action'=> $action,
							'title' => $this->language->get('action_' . $action),
							'url'   => html_entity_decode($_url->link('product/product&product_id=' . $result['product_id']), ENT_QUOTES, 'UTF-8'),
							'icon'  => 'eye',
							'name'  => null,
							'rel'   => json_encode(array()),
							'class' => $attr['class'],
						);
						break;
					default:
						$_buttons[] = array(
							'type'  => $attr['type'],
							'action'=> $action,
							'title' => $this->language->get('action_' . $action),
							'url'   => null,
							'icon'  => null,
							'name'  => $this->language->get('action_' . $attr['short']),
							'rel'   => json_encode($attr['rel']),
							'data'  => isset($result[$action . '_exist']) ? (int)$result[$action . '_exist'] : 0,
							'class' => $attr['class'],
						);
						break;
				}
			}

			$special = false;

			$product_specials = $this->model_catalog_product->getProductSpecials($result['product_id']);

			foreach ($product_specials  as $product_special) {
				if (($product_special['date_start'] == '0000-00-00' || strtotime($product_special['date_start']) < time()) && ($product_special['date_end'] == '0000-00-00' || strtotime($product_special['date_end']) > time())) {
					$special = $product_special['price'];
					break;
				}
			}

			$row = array(
				'product_id' => $result['product_id'],
				'selected'   => isset($this->request->post['selected']) && in_array($result['product_id'], $this->request->post['selected']),
				'action'     => $_buttons
			);
			if (!is_array($columns)) {
				$row['name'] = $result['name'];
				$row['model'] = $result['model'];
				$row['price'] = $result['price'];
				$row['special'] = $special;
				$row['image'] = $result['image'];
				$row['status'] = ($result['status'] ? $this->language->get('text_enabled') : $this->language->get('text_disabled'));
				$row['quantity'] = $result['quantity'];
			} else {
				foreach ($columns as $column => $attr) {
					if ($column == 'image') {
						if ($result['image'] && file_exists(DIR_IMAGE . $result['image'])) {
							$image = $this->model_tool_image->resize($result['image'], $this->config->get('module_admin_quick_edit_list_view_image_width'), $this->config->get('module_admin_quick_edit_list_view_image_height'));
						} else {
							$image = $this->model_tool_image->resize('no_image.png', $this->config->get('module_admin_quick_edit_list_view_image_width'), $this->config->get('module_admin_quick_edit_list_view_image_height'));
						}
						$row[$column] = $result['image'];
						$row['thumb'] = $image;
						$row['name'] = $result['name'];
					} else if ($column == 'category') {
						$this->load->model('catalog/category');
						$categories = $this->model_catalog_product->getProductCategories($result['product_id']);
						$category_paths = array();
						foreach($categories as $cat) {
							$category = $this->model_catalog_category->getCategory($cat);
							$category_paths[] = (($category['path']) ? $category['path'] . ' &gt; ' : '') . $category['name'];
						}
						$row[$column] = implode("<br />", $category_paths);
					} else if ($column == 'store') {
						$stores = $this->model_catalog_product->getProductStores($result['product_id']);
						$product_stores = array();
						foreach($stores as $store) {
							$product_stores[] = $data['stores'][$store]['name'];
						}
						$row[$column] = implode("<br />", $product_stores);
					} else if ($column == 'filter') {
						$this->load->model('catalog/filter');
						$fs = $this->model_catalog_product->getProductFilters($result['product_id']);
						$product_filters = array();
						foreach($fs as $filter_id) {
							$f = $this->model_catalog_filter->getFilter($filter_id);
							if ($f) {
								$product_filters[] = strip_tags(html_entity_decode($f['group'] . ' &gt; ' . $f['name'], ENT_QUOTES, 'UTF-8'));
							}
						}
						$row[$column] = implode("<br />", $product_filters);
					} else if ($column == 'download') {
						$this->load->model('catalog/download');
						$downloads = $this->model_catalog_product->getProductDownloads($result['product_id']);
						$product_downloads = array();
						foreach($downloads as $download_id) {
							$dd = $this->model_catalog_download->getDownloadDescriptions($download_id);
							if ($dd) {
								$product_downloads[] = $dd[$this->config->get('config_language_id')]['name'];
							}
						}
						$row[$column] = implode("<br />", $product_downloads);
					} else if ($column == 'status') {
						if (!$this->config->get('module_admin_quick_edit_highlight_status')) {
							$row[$column] = ((int)$result['status'] ? $this->language->get('text_enabled') : $this->language->get('text_disabled'));
						} else {
							$row[$column] = ((int)$result['status'] ? '<span class="label label-success">' . $this->language->get('text_enabled') . '</span>' : '<span class="label label-danger">' . $this->language->get('text_disabled') . '</span>');
						}
					} else if ($column == 'quantity') {
						if ((int)$result['quantity'] < 0) {
							$row[$column] = '<span class="label label-warning">' . $result['quantity'] . '</span>';
						} else if ((int)$result['quantity'] <= 5) {
							$row[$column] = '<span class="label label-danger">' . $result['quantity'] . '</span>';
						} else {
							$row[$column] = '<span class="label label-success">' . $result['quantity'] . '</span>';
						}
					} else if ($column == 'requires_shipping') {
						if (!$this->config->get('module_admin_quick_edit_highlight_yes_no')) {
							$row[$column] = ((int)$result['shipping'] ? $this->language->get('text_yes') : $this->language->get('text_no'));
						} else {
							$row[$column] = ((int)$result['shipping'] ? '<span class="label label-success">' . $this->language->get('text_yes') . '</span>' : '<span class="label label-danger">' . $this->language->get('text_no') . '</span>');
						}
					} else if ($column == 'subtract') {
						if (!$this->config->get('module_admin_quick_edit_highlight_yes_no')) {
							$row[$column] = ((int)$result[$column] ? $this->language->get('text_yes') : $this->language->get('text_no'));
						} else {
							$row[$column] = ((int)$result[$column] ? '<span class="label label-success">' . $this->language->get('text_yes') . '</span>' : '<span class="label label-danger">' . $this->language->get('text_no') . '</span>');
						}
					} else if (in_array($column, array('weight', 'length', 'width', 'height'))) {
						$row[$column] = sprintf("%.4f",round((float)$result[$column], 4));
					} else if ($column == 'date_added' || $column == 'date_modified') {
						$date = new DateTime($result[$column]);
						$row[$column] = $date->format("Y-m-d H:i:s");
					} else if ($column == 'date_available') {
						$date = new DateTime($result['date_available']);
						$row[$column] = $date->format("Y-m-d");
					} else if ($column == 'id') {
						$row[$column] = $result['product_id'];
					} else if ($column == 'action') {
						$row[$column] = $_buttons;
					} else if ($column == 'selector') {
						$row[$column] = '';
					} else if ($column == 'view_in_store') {
						$product_stores = $this->model_catalog_product->getProductStores($result['product_id']);

						$row[$column] = array();

						foreach ($product_stores as $store) {
							if (!in_array($store, array_keys($data['stores'])))
								continue;
							$row[$column][] = array(
								'name' => $data['stores'][$store]['name'],
								'href' => $data['stores'][$store]['make']->link('product/product&product_id=' . $result['product_id'], '', $data['stores'][$store]['secure'])
							);
						}
					} else {
						$row[$column] = $result[$column];
						if ($column == 'price' && $special) {
							$row['special'] = $special;
							$row[$column] = '<span style="text-decoration:line-through;">' . $result['price'] . '</span><br/><span style="color: #b00;">' . $special . '</span>';
						}
					}
				}
			}
			$data['products'][] = $row;
		}

		$data['language_id'] = $this->config->get('config_language_id');

		$column_classes = array();
		$type_classes = array();
		$non_sortable = array();

		if (!is_array($columns)) {
			$displayed_columns = array('selector', 'image', 'name', 'model', 'price', 'quantity', 'status', 'action');
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

		$data['update_url'] = html_entity_decode($this->url->link('extension/module/admin_quick_edit/catalog__product__quick_update', 'user_token=' . $this->session->data['user_token'], true));
		$data['refresh_url'] = html_entity_decode($this->url->link('extension/module/admin_quick_edit/catalog__product__refresh_data', 'user_token=' . $this->session->data['user_token'], true));
		$data['load_popup_url'] = html_entity_decode($this->url->link('extension/module/admin_quick_edit/catalog__product__load_popup', 'user_token=' . $this->session->data['user_token'], true));

		$data['status_select'] = addslashes(json_encode(array(array("id" => "0", "value" => $this->language->get('text_disabled')), array("id" => "1", "value" => $this->language->get('text_enabled')))));
		$data['yes_no_select'] = addslashes(json_encode(array(array("id" => "0", "value" => $this->language->get('text_no')), array("id" => "1", "value" => $this->language->get('text_yes')))));

		$this->load->model('localisation/language');
		$lang_count = $this->model_localisation_language->getTotalLanguages();
		$data['single_lang_editing'] = $this->config->get('module_admin_quick_edit_single_language_editing') || ((int)$lang_count == 1);
		$data['batch_edit'] = (int)$this->config->get('module_admin_quick_edit_batch_edit');

		if (in_array("filter", $displayed_columns)) {
			$this->load->model('extension/module/aqe/catalog/filter');
			$data['_filters'] = $this->model_extension_module_aqe_catalog_filter->getFiltersByGroup();
		}

		if (in_array("manufacturer", $displayed_columns)) {
			$this->load->model('catalog/manufacturer');
			$data['manufacturers'] = $this->model_catalog_manufacturer->getManufacturers();
			$m_select = array(array("id" => "0", "value" => $this->language->get('text_none')));
			foreach ($data['manufacturers'] as $m) {
				$m_select[] = array("id" => $m['manufacturer_id'], "value" => $m['name']);
			}
			$data['manufacturer_select'] = addslashes(json_encode($m_select));
		} else {
			$data['manufacturer_select'] = addslashes(json_encode(array()));
		}

		if (in_array("tax_class", $displayed_columns)) {
			$this->load->model('localisation/tax_class');
			$data['tax_classes'] = $this->model_localisation_tax_class->getTaxClasses();
			$tc_select = array(array("id" => "0", "value" => $this->language->get('text_none')));
			foreach ($data['tax_classes'] as $tc) {
				$tc_select[] = array("id" => $tc['tax_class_id'], "value" => $tc['title']);
			}
			$data['tax_class_select'] = addslashes(json_encode($tc_select));
		} else {
			$data['tax_class_select'] = addslashes(json_encode(array()));
		}

		if (in_array("stock_status", $displayed_columns)) {
			$this->load->model('localisation/stock_status');
			$data['stock_statuses'] = $this->model_localisation_stock_status->getStockStatuses();
			$ss_select = array();
			foreach ($data['stock_statuses'] as $ss) {
				$ss_select[] = array("id" => $ss['stock_status_id'], "value" => $ss['name']);
			}
			$data['stock_status_select'] = addslashes(json_encode($ss_select));
		} else {
			$data['stock_status_select'] = addslashes(json_encode(array()));
		}

		if (in_array("length_class", $displayed_columns)) {
			$this->load->model('localisation/length_class');
			$data['length_classes'] = $this->model_localisation_length_class->getLengthClasses();
			$lc_select = array();
			foreach ($data['length_classes'] as $lc) {
				$lc_select[] = array("id" => $lc['length_class_id'], "value" => $lc['title']);
			}
			$data['length_class_select'] = addslashes(json_encode($lc_select));
		} else {
			$data['length_class_select'] = addslashes(json_encode(array()));
		}

		if (in_array("weight_class", $displayed_columns)) {
			$this->load->model('localisation/weight_class');
			$data['weight_classes'] = $this->model_localisation_weight_class->getWeightClasses();
			$wc_select = array();
			foreach ($data['weight_classes'] as $wc) {
				$wc_select[] = array("id" => $wc['weight_class_id'], "value" => $wc['title']);
			}
			$data['weight_class_select'] = addslashes(json_encode($wc_select));
		} else {
			$data['weight_class_select'] = addslashes(json_encode(array()));
		}

		if (in_array("category", $displayed_columns)) {
			$this->load->model('catalog/category');
			$data['categories'] = $this->model_catalog_category->getCategories(array('sort' => 'name'));
		}

		if (in_array("download", $displayed_columns)) {
			$this->load->model('catalog/download');
			$data['downloads'] = $this->model_catalog_download->getDownloads();
		}

		$data['user_token'] = $this->session->data['user_token'];

		$url = '';

		foreach ($this->config->get('module_admin_quick_edit_catalog_products') as $column => $attr) {
			if (isset($this->request->get['filter_' . $column])) {
				$url .= '&filter_' . $column . '=' . urlencode(html_entity_decode($this->request->get['filter_' . $column], ENT_QUOTES, 'UTF-8'));
			}
		}
		if (isset($this->request->get['filter_sub_category'])) {
			$url .= '&filter_sub_category=' . urlencode(html_entity_decode($this->request->get['filter_sub_category'], ENT_QUOTES, 'UTF-8'));
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
		foreach ($this->config->get('module_admin_quick_edit_catalog_products') as $column => $attr) {
			if ((int)$this->config->get('module_admin_quick_edit_override_menu_entry')) {
				$data['sorts'][$column] = $this->url->link('catalog/product', 'user_token=' . $this->session->data['user_token'] . '&sort=' . $attr['sort'] . $url . '&aqer=1', true);
			} else {
				$data['sorts'][$column] = $this->url->link('extension/module/admin_quick_edit/catalog__product__', 'user_token=' . $this->session->data['user_token'] . '&sort=' . $attr['sort'] . $url, true);
			}
		}

		$url = '';

		foreach ($this->config->get('module_admin_quick_edit_catalog_products') as $column => $attr) {
			if (isset($this->request->get['filter_' . $column])) {
				$url .= '&filter_' . $column . '=' . urlencode(html_entity_decode($this->request->get['filter_' . $column], ENT_QUOTES, 'UTF-8'));
			}
		}
		if (isset($this->request->get['filter_sub_category'])) {
			$url .= '&filter_sub_category=' . urlencode(html_entity_decode($this->request->get['filter_sub_category'], ENT_QUOTES, 'UTF-8'));
		}

		if (isset($this->request->get['filter_special_price'])) {
			$url .= '&filter_special_price=' . urlencode(html_entity_decode($this->request->get['filter_special_price'], ENT_QUOTES, 'UTF-8'));
		}

		if (isset($this->request->get['sort'])) {
			$url .= '&sort=' . $this->request->get['sort'];
		}

		if (isset($this->request->get['order'])) {
			$url .= '&order=' . $this->request->get['order'];
		}

		$pagination = new Pagination();
		$pagination->total = $product_total;
		$pagination->page = $page;
		$pagination->limit = $this->config->get('config_limit_admin');

		if ((int)$this->config->get('module_admin_quick_edit_override_menu_entry')) {
			$pagination->url = $this->url->link('catalog/product', 'user_token=' . $this->session->data['user_token'] . $url . '&page={page}' . '&aqer=1', true);
		} else {
			$pagination->url = $this->url->link('extension/module/admin_quick_edit/catalog__product__', 'user_token=' . $this->session->data['user_token'] . $url . '&page={page}', true);
		}

		$data['pagination'] = $pagination->render();

		$data['results'] = sprintf($this->language->get('text_pagination'), ($product_total) ? (($page - 1) * $this->config->get('config_limit_admin')) + 1 : 0, ((($page - 1) * $this->config->get('config_limit_admin')) > ($product_total - $this->config->get('config_limit_admin'))) ? $product_total : ((($page - 1) * $this->config->get('config_limit_admin')) + $this->config->get('config_limit_admin')), $product_total, ceil($product_total / $this->config->get('config_limit_admin')));

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

		$template = 'extension/module/aqe/catalog/product_list';

		$this->response->setOutput($this->load->view($template, $data));
	}

	public function filter() {
		$this->load->model('extension/module/aqe/catalog/filter');

		if (isset($this->request->get['filter_group_id'])) {
			$filter_group_id = $this->request->get['filter_group_id'];
		} else {
			$filter_group_id = 0;
		}

		$filter_data = array();

		$results = $this->model_extension_module_aqe_catalog_filter->getFiltersByFilterGroupId($filter_group_id);

		foreach ($results as $result) {
			$filter_data[] = array(
				'filter_id'  => $result['filter_id'],
				'name'       => $result['name'],
				'group'      => $result['group']
			);
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($filter_data));
	}

	public function category() {
		$this->load->model('catalog/product');

		if (isset($this->request->get['category_id'])) {
			$category_id = $this->request->get['category_id'];
		} else {
			$category_id = 0;
		}

		$product_data = array();

		$results = $this->model_catalog_product->getProductsByCategoryId($category_id);

		foreach ($results as $result) {
			$product_data[] = array(
				'product_id' => $result['product_id'],
				'name'       => $result['name'],
				'model'      => $result['model']
			);
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($product_data));
	}

	public function autocomplete() {
		$this->load->model('catalog/product');
		$this->load->model('extension/module/aqe/catalog/product');

		$response = array();

		if (isset($this->request->get['filter_name']) ||
			isset($this->request->get['filter_model']) ||
			isset($this->request->get['filter_category']) ||
			isset($this->request->get['filter_seo']) ||
			isset($this->request->get['filter_location']) ||
			isset($this->request->get['filter_sku']) ||
			isset($this->request->get['filter_upc']) ||
			isset($this->request->get['filter_ean']) ||
			isset($this->request->get['filter_jan']) ||
			isset($this->request->get['filter_isbn']) ||
			isset($this->request->get['filter_mpn'])) {

			$this->load->model('catalog/option');

			$filter_types = array('name', 'model', 'category', 'seo', 'location', 'sku', 'upc', 'ean', 'jan', 'isbn' ,'mpn');
			$filters = array();

			foreach($filter_types as $filter) {
				$filters[$filter] = (isset($this->request->get['filter_' . $filter])) ? $this->request->get['filter_' . $filter] : null;
			}
			$filters['sub_category'] = (isset($this->request->get['filter_sub_category'])) ? $this->request->get['filter_sub_category'] : $this->config->get('module_admin_quick_edit_catalog_products_filter_sub_category');

			if (isset($this->request->get['limit'])) {
				$limit = $this->request->get['limit'];
			} else {
				$limit = 20;
			}

			$filter_data = array(
				'start'               => 0,
				'limit'               => $limit,
				'columns'             => $filter_types
			);

			foreach($filters as $filter => $value) {
				$filter_data['filter_' . $filter] = $value;
			}

			$results = $this->model_extension_module_aqe_catalog_product->getProducts($filter_data);

			foreach ($results as $result) {
				$option_data = array();

				$product_options = $this->model_catalog_product->getProductOptions($result['product_id']);

				foreach ($product_options as $product_option) {
					$option_info = $this->model_catalog_option->getOption($product_option['option_id']);

					if ($option_info) {
						$product_option_value_data = array();

						foreach ($product_option['product_option_value'] as $product_option_value) {
							$option_value_info = $this->model_catalog_option->getOptionValue($product_option_value['option_value_id']);

							if ($option_value_info) {
								$product_option_value_data[] = array(
									'product_option_value_id' => $product_option_value['product_option_value_id'],
									'option_value_id'         => $product_option_value['option_value_id'],
									'name'                    => $option_value_info['name'],
									'price'                   => (float)$product_option_value['price'] ? $this->currency->format($product_option_value['price'], $this->config->get('config_currency')) : false,
									'price_prefix'            => $product_option_value['price_prefix']
								);
							}
						}

						$option_data[] = array(
							'product_option_id'    => $product_option['product_option_id'],
							'product_option_value' => $product_option_value_data,
							'option_id'            => $product_option['option_id'],
							'name'                 => $option_info['name'],
							'type'                 => $option_info['type'],
							'value'                => $product_option['value'],
							'required'             => $product_option['required']
						);
					}
				}

				$response[] = array(
					'product_id' => $result['product_id'],
					'name'       => strip_tags(html_entity_decode($result['name'], ENT_QUOTES, 'UTF-8')),
					'seo'        => (isset($result['seo'])) ? $result['seo'] : '',
					'sku'        => $result['sku'],
					'upc'        => $result['upc'],
					'ean'        => $result['ean'],
					'jan'        => $result['jan'],
					'isbn'       => $result['isbn'],
					'mpn'        => $result['mpn'],
					'location'   => $result['location'],
					'model'      => $result['model'],
					'option'     => $option_data,
					'price'      => $result['price'],
				);
			}
		} else if (isset($this->request->get['filter_download'])) {
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($response));
	}

	public function load_popup() {
		$this->load->model('catalog/product');
		$this->load->model('extension/module/aqe/catalog/product');

		$this->load->language('catalog/product');
		$this->load->language('extension/module/aqe/catalog/general');
		$this->load->language('extension/module/aqe/catalog/product');

		$response = array();

		if ($this->request->server['REQUEST_METHOD'] == 'POST' && $this->validateLoadPopup($this->request->post)) {
			$data['error_warning'] = '';
			list($data['parameter'], $data['product_id']) = explode("-", $this->request->post['id']);

			$data['user_token'] = $this->session->data['user_token'];

			$response["success"] = 1;

			switch ($data['parameter']) {
				case "tag":
				case "name":
					$this->load->model('localisation/language');
					$data['languages'] = $this->model_localisation_language->getLanguages();
					$data['value'] = array();
					$descriptions = $this->model_catalog_product->getProductDescriptions($data['product_id']);
					foreach ($descriptions as $language_id => $value) {
						$data['value'][$language_id] = $value[$data['parameter']];
					}
					$response['title'] = $this->language->get('entry_' . $data['parameter']);
					break;
				case "seo":
				case "keywords":
					$this->load->model('localisation/language');
					$data['languages'] = $this->model_localisation_language->getLanguages();

					$this->load->model('setting/store');
					$data['stores'] = array();

					$data['stores'][] = array(
						'store_id' => 0,
						'name'     => $this->language->get('text_default')
					);

					$stores = $this->model_setting_store->getStores();

					foreach ($stores as $store) {
						$data['stores'][] = array(
							'store_id' => $store['store_id'],
							'name'     => $store['name']
						);
					}
					$data['value'] = $this->model_catalog_product->getProductSeoUrls($data['product_id']);
					break;
				case "category":
					$this->load->model('catalog/category');
					$categories = $this->model_catalog_product->getProductCategories($data['product_id']);

					$data['product_categories'] = array();

					foreach ($categories as $category_id) {
						$category_info = $this->model_catalog_category->getCategory($category_id);

						if ($category_info) {
							$data['product_categories'][] = array(
								'category_id'   => $category_info['category_id'],
								'name'          => ($category_info['path']) ? $category_info['path'] . ' &gt; ' . $category_info['name'] : $category_info['name']
							);
						}
					}
					break;
				case "store":
					$this->load->model('setting/store');
					$data['stores'] = $this->model_setting_store->getStores();
					array_unshift($data['stores'], array("store_id" => 0, "name" => $this->config->get('config_name')));
					$data['product_store'] = $this->model_catalog_product->getProductStores($data['product_id']);
					$response['title'] = $this->language->get('entry_store');
					break;
				case "filters":
				case "filter":
					$this->load->model('catalog/filter');
					$filters = $this->model_catalog_product->getProductFilters($data['product_id']);

					$data['product_filters'] = array();

					foreach ($filters as $filter_id) {
						$filter_info = $this->model_catalog_filter->getFilter($filter_id);

						if ($filter_info) {
							$data['product_filters'][] = array(
								'filter_id' => $filter_info['filter_id'],
								'name'      => $filter_info['group'] . ' &gt; ' . $filter_info['name']
							);
						}
					}
					break;
				case "downloads":
				case "download":
					$this->load->model('catalog/download');
					$downloads = $this->model_catalog_product->getProductDownloads($data['product_id']);

					$data['product_downloads'] = array();

					foreach ($downloads as $download_id) {
						$download_info = $this->model_catalog_download->getDownload($download_id);

						if ($download_info) {
							$data['product_downloads'][] = array(
								'download_id'   => $download_info['download_id'],
								'name'          => $download_info['name']
							);
						}
					}
					break;
				case "attributes":
					$this->load->model('localisation/language');
					$data['languages'] = $this->model_localisation_language->getLanguages();
					$this->load->model('catalog/attribute');
					$product_attributes = $this->model_catalog_product->getProductAttributes($data['product_id']);

					$data['product_attributes'] = array();

					foreach ($product_attributes as $product_attribute) {
						$attribute_info = $this->model_catalog_attribute->getAttribute($product_attribute['attribute_id']);

						if ($attribute_info) {
							$data['product_attributes'][] = array(
								'attribute_id'                  => $product_attribute['attribute_id'],
								'name'                          => $attribute_info['name'],
								'product_attribute_description' => $product_attribute['product_attribute_description']
							);
						}
					}
					break;
				case "discounts":
					$this->load->model('customer/customer_group');
					$data['customer_groups'] = $this->model_customer_customer_group->getCustomerGroups();

					$product_discounts = $this->model_catalog_product->getProductDiscounts($data['product_id']);

					$data['product_discounts'] = array();

					foreach ($product_discounts as $product_discount) {
						$data['product_discounts'][] = array(
							'discount_id'       => $product_discount['product_discount_id'],
							'customer_group_id' => $product_discount['customer_group_id'],
							'quantity'          => $product_discount['quantity'],
							'priority'          => $product_discount['priority'],
							'price'             => $product_discount['price'],
							'date_start'        => ($product_discount['date_start'] != '0000-00-00') ? $product_discount['date_start'] : '',
							'date_end'          => ($product_discount['date_end'] != '0000-00-00') ? $product_discount['date_end'] : ''
						);
					}
					break;
				case "images":
					$product_images = $this->model_catalog_product->getProductImages($data['product_id']);

					$this->load->model('tool/image');

					$data['product_images'] = array();

					foreach ($product_images as $product_image) {
						if (is_file(DIR_IMAGE . $product_image['image'])) {
							$image = $product_image['image'];
							$thumb = $product_image['image'];
						} else {
							$image = '';
							$thumb = 'no_image.png';
						}

						$data['product_images'][] = array(
							'image'      => $image,
							'thumb'      => $this->model_tool_image->resize($thumb, 100, 100),
							'sort_order' => $product_image['sort_order']
						);
					}

					$data['placeholder'] = $this->model_tool_image->resize('no_image.png', 100, 100);
					break;
				case "options":
					$product_options = $this->model_catalog_product->getProductOptions($data['product_id']);
					$data['product_options'] = array();

					foreach ($product_options as $product_option) {
						$product_option_value_data = array();

						if (isset($product_option['product_option_value'])) {
							foreach ($product_option['product_option_value'] as $product_option_value) {
								$product_option_value_data[] = array(
									'product_option_value_id' => $product_option_value['product_option_value_id'],
									'option_value_id'         => $product_option_value['option_value_id'],
									'quantity'                => $product_option_value['quantity'],
									'subtract'                => $product_option_value['subtract'],
									'price'                   => $product_option_value['price'],
									'price_prefix'            => $product_option_value['price_prefix'],
									'points'                  => $product_option_value['points'],
									'points_prefix'           => $product_option_value['points_prefix'],
									'weight'                  => $product_option_value['weight'],
									'weight_prefix'           => $product_option_value['weight_prefix']
								);
							}
						}

						$data['product_options'][] = array(
							'product_option_id'    => $product_option['product_option_id'],
							'product_option_value' => $product_option_value_data,
							'option_id'            => $product_option['option_id'],
							'name'                 => $product_option['name'],
							'type'                 => $product_option['type'],
							'value'                => isset($product_option['value']) ? $product_option['value'] : '',
							'required'             => $product_option['required']
						);
					}

					$this->load->model('catalog/option');
					$data['option_values'] = array();

					foreach ($data['product_options'] as $product_option) {
						if ($product_option['type'] == 'select' || $product_option['type'] == 'radio' || $product_option['type'] == 'checkbox' || $product_option['type'] == 'image') {
							if (!isset($data['option_values'][$product_option['option_id']])) {
								$data['option_values'][$product_option['option_id']] = $this->model_catalog_option->getOptionValues($product_option['option_id']);
							}
						}
					}
					break;
				case "recurrings":
					$this->load->model('catalog/recurring');
					$data['recurrings'] = $this->model_catalog_recurring->getRecurrings();

					$this->load->model('customer/customer_group');
					$data['customer_groups'] = $this->model_customer_customer_group->getCustomerGroups();

					$data['product_recurrings'] = $this->model_catalog_product->getRecurrings($data['product_id']);
					break;
				case "specials":
					$this->load->model('customer/customer_group');
					$data['customer_groups'] = $this->model_customer_customer_group->getCustomerGroups();

					$product_specials = $this->model_catalog_product->getProductSpecials($data['product_id']);

					$data['product_specials'] = array();

					foreach ($product_specials as $product_special) {
						$data['product_specials'][] = array(
							'special_id'        => $product_special['product_special_id'],
							'customer_group_id' => $product_special['customer_group_id'],
							'priority'          => $product_special['priority'],
							'price'             => $product_special['price'],
							'date_start'        => ($product_special['date_start'] != '0000-00-00') ? $product_special['date_start'] : '',
							'date_end'          => ($product_special['date_end'] != '0000-00-00') ? $product_special['date_end'] :  ''
						);
					}
					break;
				case "related":
					$products = $this->model_catalog_product->getProductRelated($data['product_id']);
					$data['product_relateds'] = array();

					foreach ($products as $product_id) {
						$related_info = $this->model_catalog_product->getProduct($product_id);

						if ($related_info) {
							$data['product_relateds'][] = array(
								'product_id' => $related_info['product_id'],
								'name'       => $related_info['name']
							);
						}
					}
					break;
				case "descriptions":
					$this->load->model('localisation/language');
					$data['languages'] = $this->model_localisation_language->getLanguages();
					$data['product_description'] = $this->model_catalog_product->getProductDescriptions($data['product_id']);
					break;
				default:
					$response["success"] = 0;
					$response['error'] = $this->language->get('error_load_popup');
					break;
			}
			$response['title'] = $this->language->get('action_' . $data['parameter']);
		} else {
			$this->alert['error']['load'] = $this->language->get('error_load_popup');
		}

		$template = 'extension/module/aqe/catalog/product_quick_form';

		$response['popup'] = $this->load->view($template, $data);

		$response = array_merge($response, array("errors" => $this->error), array("alerts" => $this->alert));

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($response));
	}

	public function refresh_data() {
		$this->load->model('catalog/product');
		$this->load->model('extension/module/aqe/catalog/product');

		$this->load->language('catalog/product');
		$this->load->language('extension/module/aqe/catalog/general');
		$this->load->language('extension/module/aqe/catalog/product');

		$response = array();

		if ($this->request->server['REQUEST_METHOD'] == 'POST' && $this->validateRefreshData($this->request->post)) {
			$response['values'] = array();

			foreach ($this->request->post['data'] as $column => $products) {
				foreach ($products as $id) {
					switch ($column) {
						case 'filter':
							$this->load->model('catalog/filter');
							$filters = $this->model_catalog_product->getProductFilters($id);

							$product_filters = array();

							foreach ($filters as $filter_id) {
								$f = $this->model_catalog_filter->getFilter($filter_id);
								$product_filters[] = strip_tags(html_entity_decode($f['group'] . ' &gt; ' . $f['name'], ENT_QUOTES, 'UTF-8'));
							}
							$response['values'][$id][$column] = implode("<br/>", $product_filters);
							break;
						case "download":
							$this->load->model('catalog/download');
							$downloads = $this->model_catalog_product->getProductDownloads($id);

							$product_downloads = array();

							foreach ($downloads as $download_id) {
								$download_info = $this->model_catalog_download->getDownload($download_id);

								if ($download_info) {
									$product_downloads[] = $download_info['name'];
								}
							}
							$response['values'][$id][$column] = implode("<br/>", $product_downloads);
							break;
						case 'price':
							$special = false;

							$product = $this->model_catalog_product->getProduct($id);
							$product_specials = $this->model_catalog_product->getProductSpecials($id);

							foreach ($product_specials  as $product_special) {
								if (($product_special['date_start'] == '0000-00-00' || strtotime($product_special['date_start']) < time()) && ($product_special['date_end'] == '0000-00-00' || strtotime($product_special['date_end']) > time())) {
									$special = $product_special['price'];
									break;
								}
							}
							if ($special)
								$ret = '<span style="text-decoration:line-through">' . sprintf("%.4f",round((float)$product['price'], 4)) . '</span><br/><span style="color:#b00;">' . $special . '</span>';
							else
								$ret = sprintf("%.4f",round((float)$product['price'], 4));
							$response['values'][$id][$column] = $ret;
							break;
						case 'view_in_store':
							$_url = new Url(HTTP_CATALOG, HTTPS_CATALOG);

							if ($this->config->get('config_seo_url')) {
								$seo_url_file = 'controller/startup/seo_url.php';

								if (class_exists('VQMod')) {
									if (is_file(DIR_MODIFICATION . 'catalog/' . $seo_url_file)) {
										require_once(\VQMod::modCheck(DIR_MODIFICATION . 'catalog/' . $seo_url_file, DIR_CATALOG . $seo_url_file));
									} else {
										require_once(DIR_CATALOG . $seo_url_file);
									}
								} else {
									if (is_file(DIR_MODIFICATION . 'catalog/' . $seo_url_file)) {
										require_once(DIR_MODIFICATION . 'catalog/' . $seo_url_file);
									} else {
										require_once(DIR_CATALOG . $seo_url_file);
									}
								}
								$seo_url = new ControllerStartupSeoUrl($this->registry);

								$_url->addRewrite($seo_url);
							}

							$this->load->model('setting/store');

							$stores = $this->model_setting_store->getStores();

							$data['stores'] = array();

							$data['stores'][0] = array(
								'name'  => $this->config->get('config_name'),
								'url'   => HTTP_CATALOG,
								'ssl'   => HTTPS_CATALOG,
								'secure'=> $this->config->get('config_secure'),
								'make'  => $_url
							);

							foreach ($stores as $store) {
								$query = $this->db->query("SELECT value FROM " . DB_PREFIX . "setting WHERE store_id = '" . (int)$store['store_id'] . "' AND `key` = 'config_secure'");
								$secure = (int)$query->row['value'];

								$__url = new Url($store['url'], $store['ssl']);

								$data['stores'][$store['store_id']] = array(
									'name'  => $store['name'],
									'url'   => $store['url'],
									'ssl'   => $store['ssl'],
									'secure'=> $secure,
									'make'  => $__url
								);

								if ($this->config->get('config_seo_url')) {
									$data['stores'][$store['store_id']]['make']->addRewrite($seo_url);
								}
							}

							$product_stores = $this->model_catalog_product->getProductStores($id);


							$resp = '<select onchange="((this.value !== \'\') ? window.open(this.value) : null); this.value = \'\';">';
							$resp .= '<option value="">' . $this->language->get('text_select') . '</option>';

							foreach ($product_stores as $store) {
								if (!in_array($store, array_keys($data['stores'])))
									continue;
								$resp .= '<option value="' . $data['stores'][$store]['make']->link('product/product&product_id=' . $id, '', $data['stores'][$store]['secure']) . '">' . $data['stores'][$store]['name'] . '</option>';
							}

							$resp .= '</select>';

							$response['values'][$id][$column] = $resp;
							break;
						case 'seo':
							$keywords = $this->model_catalog_product->getProductSeoUrls($id);

							if (isset($keywords['0'][$this->config->get('config_language_id')]))
								$response['values'][$id][$column] = $keywords['0'][$this->config->get('config_language_id')];
							else
								$response['values'][$id][$column] = "";
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
		$this->load->model('catalog/product');
		$this->load->model('extension/module/aqe/catalog/product');

		$this->load->language('catalog/product');
		$this->load->language('extension/module/aqe/catalog/general');
		$this->load->language('extension/module/aqe/catalog/product');

		$response = array();

		if ($this->request->server['REQUEST_METHOD'] == 'POST' && $this->validateUpdateData($this->request->post)) {
			list($column, $id) = explode("-", $this->request->post['id']);
			$id = (array)$id;
			$value = $this->request->post['new'];
			$lang_id = isset($this->request->post['lang_id']) ? $this->request->post['lang_id'] : null;
			$alt = isset($this->request->post['alt']) ? $this->request->post['alt'] : "";
			$expression = !is_array($value) && strpos(trim($value), "#") === 0 && preg_match('/^#\s*(?P<operator>[+-\/\*])\s*(?P<operand>-?\d+\.?\d*)(?P<percent>%)?$/', trim($value)) === 1;

			if ($column == "requires_shipping") {
				$column = "shipping";
			}

			if (isset($this->request->post['ids'])) {
				$id = array_unique(array_merge($id, (array)$this->request->post['ids']));
			}

			$results = array('done' => array(), 'failed' => array());
			$_results = array();

			foreach ((array)$id as $_id) {
				$result = $this->model_extension_module_aqe_catalog_product->quickEditProduct($_id, $column, $value, $lang_id, $this->request->post);
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

				if (in_array($column, array('model', 'sku', 'upc', 'location', 'attributes', 'discounts', 'images', 'options', 'recurrings', 'related', 'specials', 'descriptions'))) {
					$response['value'] = $value;
					$response['values']['*'] = $response['value'];
				} else if (in_array($column, array('sort_order', 'points', 'minimum', 'viewed'))) {
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
				} else if (in_array($column, array('subtract', 'shipping'))) {
					if (!$this->config->get('module_admin_quick_edit_highlight_yes_no')) {
						$response['value'] = ((int)$value) ? $this->language->get('text_yes') : $this->language->get('text_no');
					} else {
						$response['value'] = ((int)$value) ? '<span class="label label-success">' . $this->language->get('text_yes') . '</span>' : '<span class="label label-danger">' . $this->language->get('text_no') . '</span>';
					}
					$response['values']['*'] = $response['value'];
				} else if ($column == 'status') {
					if (!$this->config->get('module_admin_quick_edit_highlight_status')) {
						$response['value'] = ((int)$value) ? $this->language->get('text_enabled') : $this->language->get('text_disabled');
					} else {
						$response['value'] = ((int)$value) ? '<span class="label label-success">' . $this->language->get('text_enabled') . '</span>' : '<span class="label label-danger">' . $this->language->get('text_disabled') . '</span>';
					}
					$response['values']['*'] = $response['value'];
				} else if ($column == 'image') {
					$this->load->model('tool/image');
					if ($value && file_exists(DIR_IMAGE . $value)) {
						$image = $this->model_tool_image->resize($value, $this->config->get('module_admin_quick_edit_list_view_image_width'), $this->config->get('module_admin_quick_edit_list_view_image_height'));
					} else {
						$image = $this->model_tool_image->resize('no_image.png', $this->config->get('module_admin_quick_edit_list_view_image_width'), $this->config->get('module_admin_quick_edit_list_view_image_height'));
					}
					foreach ($id as $_id) {
						$response['values'][$_id] = '<img src="' . $image . '" data-id="' . $_id . '" data-image="' . $value . '" alt="' . $alt . '" class="img-thumbnail" />';
					}
					$response['value'] = '<img src="' . $image . '" data-id="' . $id[0] . '" data-image="' . $value . '" alt="' . $alt . '" class="img-thumbnail" />';
				} else if ($column == 'tax_class') {
					$this->load->model('localisation/tax_class');
					$tax_class = $this->model_localisation_tax_class->getTaxClass((int)$value);
					if ($tax_class)
						$response['value'] = $tax_class['title'];
					else
						$response['value'] = '';
					$response['values']['*'] = $response['value'];
				} else if ($column == 'stock_status') {
					$this->load->model('localisation/stock_status');
					$stock_status = $this->model_localisation_stock_status->getStockStatus((int)$value);
					if ($stock_status)
						$response['value'] = $stock_status['name'];
					else
						$response['value'] = '';
					$response['values']['*'] = $response['value'];
				} else if ($column == 'length_class') {
					$this->load->model('localisation/length_class');
					$length_class = $this->model_localisation_length_class->getLengthClass((int)$value);
					if ($length_class)
						$response['value'] = $length_class['title'];
					else
						$response['value'] = '';
					$response['values']['*'] = $response['value'];
				} else if ($column == 'weight_class') {
					$this->load->model('localisation/weight_class');
					$weight_class = $this->model_localisation_weight_class->getWeightClass((int)$value);
					if ($weight_class)
						$response['value'] = $weight_class['title'];
					else
						$response['value'] = '';
					$response['values']['*'] = $response['value'];
				} else if ($column == 'manufacturer') {
					$this->load->model('catalog/manufacturer');
					$manufacturer = $this->model_catalog_manufacturer->getManufacturer((int)$value);
					if ($manufacturer)
						$response['value'] = $manufacturer['name'];
					else
						$response['value'] = '';
					$response['values']['*'] = $response['value'];
				} else if ($column == 'quantity') {
					if ($expression) {
						$value = (int)$_results[$id[0]];
						if ($value <= 0)
							$ret = '<span class="label label-warning">' . (int)$value . '</span>';
						elseif ($value <= 5)
							$ret = '<span class="label label-danger">' . (int)$value . '</span>';
						else
							$ret = '<span class="label label-success">' . (int)$value . '</span>';

						$response['value'] = $ret;
						if (count($id) > 1) {
							foreach ($id as $_id) {
								$_value = (int)$_results[$_id];
								if ($_value <= 0)
									$ret = '<span class="label label-warning">' . (int)$_value . '</span>';
								elseif ($_value <= 5)
									$ret = '<span class="label label-danger">' . (int)$_value . '</span>';
								else
									$ret = '<span class="label label-success">' . (int)$_value . '</span>';

								$response['values'][$_id] = $ret;
							}
						}
					} else {
						$value = (int)$value;
						if ($value <= 0)
							$ret = '<span class="label label-warning">' . (int)$value . '</span>';
						elseif ($value <= 5)
							$ret = '<span class="label label-danger">' . (int)$value . '</span>';
						else
							$ret = '<span class="label label-success">' . (int)$value . '</span>';

						$response['value'] = $ret;
						$response['values']['*'] = $response['value'];
					}
				} else if (in_array($column, array('weight', 'length', 'width', 'height'))) {
					if ($expression) {
						$response['value'] = sprintf('%.4f',round((float)$_results[$id[0]], 4));
						if (count($id) > 1) {
							foreach ($id as $_id) {
								$response['values'][$_id] = sprintf('%.4f',round((float)$_results[$_id], 4));
							}
						}
					} else {
						$response['value'] = sprintf("%.4f",round((float)$value, 4));
						$response['values']['*'] = $response['value'];
					}
				} else if (in_array($column, array('name', 'tag'))) {
					if (isset($this->request->post['value'])) {
						$response['value'] = isset($this->request->post['value'][$this->config->get('config_language_id')]) ? $this->request->post['value'][$this->config->get('config_language_id')] : '';
					} else
						$response['value'] = $value;
					$response['values']['*'] = $response['value'];
				} else if (in_array($column, array('seo'))) {
					if (isset($this->request->post['value'])) {
						$response['value'] = isset($this->request->post['value']['0'][$this->config->get('config_language_id')]) ? $this->request->post['value']['0'][$this->config->get('config_language_id')] : '';
					} else
						$response['value'] = $value;
					$response['values']['*'] = $response['value'];
				} else if ($column == 'category') {
					if (isset($this->request->post['product_category'])) {
						$this->load->model('catalog/category');
						$categories = $this->request->post['product_category'];

						$product_categories = array();

						foreach ($categories as $category_id) {
							$category_info = $this->model_catalog_category->getCategory($category_id);
							if ($category_info)
								$product_categories[] = ($category_info['path']) ? $category_info['path'] . ' &gt; ' . $category_info['name'] : $category_info['name'];
						}
						$response['value'] = implode("<br>", $product_categories);
					} else {
						$response['value'] = "";
					}
					$response['values']['*'] = $response['value'];
				} else if ($column == 'store') {
					if (isset($this->request->post['product_store'])) {
						$this->request->post['product_store'] = (array)$this->request->post['product_store'];

						$this->load->model('setting/store');
						$stores = $this->model_setting_store->getStores();
						array_unshift($stores, array("store_id" => 0, "name" => $this->config->get('config_name')));

						$product_stores = array();

						foreach ($stores as $store) {
							if (in_array($store['store_id'], $this->request->post['product_store']))
								$product_stores[] = $store['name'];
						}
						$response['value'] = implode("<br>", $product_stores);
					} else {
						$response['value'] = "";
					}
					$response['values']['*'] = $response['value'];
				} else if ($column == 'filter') {
					if (isset($this->request->post['product_filter'])) {
						$this->load->model('catalog/filter');
						$filters = $this->request->post['product_filter'];

						$product_filters = array();

						foreach ($filters as $filter_id) {
							$filter_info = $this->model_catalog_filter->getFilter($filter_id);
							if ($filter_info)
								$product_filters[] = $filter_info['group'] . ' &gt; ' . $filter_info['name'];
						}
						$response['value'] = implode("<br>", $product_filters);
					} else {
						$response['value'] = "";
					}
					$response['values']['*'] = $response['value'];
				} else if ($column == 'download') {
					if (isset($this->request->post['product_download'])) {
						$this->load->model('catalog/download');
						$downloads = $this->request->post['product_download'];

						$product_downloads = array();

						foreach ($downloads as $download_id) {
							$download = $this->model_catalog_download->getDownload($download_id);
							if ($download)
								$product_downloads[] = $download['name'];
						}
						$response['value'] = implode("<br>", $product_downloads);
					} else {
						$response['value'] = "";
					}
					$response['values']['*'] = $response['value'];
				} else if ($column == 'price') {
					if ($expression) {
						$response['value'] = sprintf('%.4f',round((float)$_results[$id[0]], 4));
					} else {
						$response['value'] = sprintf('%.4f',round((float)$value, 4));
					}

					foreach ($id as $_id) {
						$special = false;
						$product_specials = $this->model_catalog_product->getProductSpecials($_id);
						foreach ($product_specials  as $product_special) {
							if (($product_special['date_start'] == '0000-00-00' || strtotime($product_special['date_start']) < time()) && ($product_special['date_end'] == '0000-00-00' || strtotime($product_special['date_end']) > time())) {
								$special = $product_special['price'];
								break;
							}
						}
						if ($special) {
							if ($expression) {
								$response['values'][$_id] = '<span style="text-decoration:line-through">' . sprintf("%.4f",round((float)$_results[$_id], 4)) . '</span><br/><span style="color:#b00;">' . $special . '</span>';
							} else {
								$response['values'][$_id] = '<span style="text-decoration:line-through">' . sprintf("%.4f",round((float)$value, 4)) . '</span><br/><span style="color:#b00;">' . $special . '</span>';
							}
						} else {
							if ($expression) {
								$response['value'][$_id] = sprintf('%.4f',round((float)$_results[$_id], 4));
							} else {
								$response['values'][$_id] = sprintf("%.4f",round((float)$value, 4));
							}
						}
					}
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

	protected function validateCopy() {
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

		if (in_array($column, array('quantity', 'sort_order', 'minimum', 'points', 'viewed', 'price', 'length', 'width', 'height', 'weight')) && strpos(trim($data['new']), "#") === 0 && preg_match('/^#\s*(?P<operation>[+-\/\*])\s*(?P<operand>-?\d+\.?\d*)(?P<percent>%)?$/', trim($data['new'])) !== 1) {
			$errors = true;
			$this->alert['error']['expression'] = $this->language->get('error_expression');
		}

		if ($column == "model" && ((strlen(utf8_decode($data['new'])) < 1) || (strlen(utf8_decode($data['new'])) > 64))) {
			$errors = true;
			$this->alert['error']['model'] = $this->language->get('error_model');
		}

		if ($column == "name") {
			if (isset($data['value'])) {
				foreach ((array)$data['value'] as $language_id => $value) {
					if ((utf8_strlen($value) < 1) || (utf8_strlen($value) > 255)) {
						$errors = true;
						$this->error["value[$language_id]"] = $this->language->get('error_name');
					}
				}
			} else {
				if ((utf8_strlen($data['new']) < 1) || (utf8_strlen($data['new']) > 255)) {
					$errors = true;
					$this->alert['error']['name'] = $this->language->get('error_name');
				}
			}
		}

		if ($column == "descriptions") {
			if (isset($data['product_description'])) {
				foreach ((array)$data['product_description'] as $language_id => $value) {
					if (!isset($value['meta_title']) || utf8_strlen($value['meta_title']) < 3 || utf8_strlen($value['meta_title']) > 255) {
						$errors = true;
						$this->error["product_description[$language_id][meta_title]"] = $this->language->get('error_meta_title');
					}
				}
			}
		}

		if ($column == "seo" || $column == "keywords") {
			if (isset($data['ids']) && count((array)$data['ids']) > 1) {
				$errors = true;
				$this->alert['error']['seo'] = $this->language->get('error_batch_edit_seo');
			} else {
				if (isset($data['value'])) {
					$this->load->model('design/seo_url');

					foreach ((array)$data['value'] as $store_id => $language) {
						foreach ($language as $language_id => $keyword) {
							if (!empty($keyword)) {
								if (count(array_keys($language, $keyword)) > 1) {
									$errors = true;
									$this->error["value[$store_id][$language_id]"] = $this->language->get('error_unique');
								}

								$seo_urls = $this->model_design_seo_url->getSeoUrlsByKeyword($keyword);

								foreach ($seo_urls as $seo_url) {
									if ($seo_url['store_id'] == $store_id && $seo_url['query'] != 'product_id=' . $id) {
										$errors = true;
										$this->error["value[$store_id][$language_id]"] = $this->language->get('error_duplicate_seo_keyword');

										break;
									}
								}
							}
						}
					}
				}
			}
		}

		if ($column == 'discounts' && isset($data['product_discount'])) {
			foreach ((array)$data['product_discount'] as $idx => $v) {
				if (strpos(trim($v['price']), "#") === 0 && preg_match('/^#\s*(?P<operation>[+-\/\*])\s*(?P<operand>-?\d+\.?\d*)(?P<percent>%)?$/', trim($v['price'])) !== 1) {
					$errors = true;
					$this->alert['error']['expression'] = $this->language->get('error_expression');
				}
			}
		}

		if ($column == 'specials' && isset($data['product_special'])) {
			foreach ((array)$data['product_special'] as $idx => $v) {
				if (strpos(trim($v['price']), "#") === 0 && preg_match('/^#\s*(?P<operation>[+-\/\*])\s*(?P<operand>-?\d+\.?\d*)(?P<percent>%)?$/', trim($v['price'])) !== 1) {
					$errors = true;
					$this->alert['error']['expression'] = $this->language->get('error_expression');
				}
			}
		}

		if ($column == 'options' && isset($data['product_option'])) {
			foreach ((array)$data['product_option'] as $product_option) {
				if (isset($product_option['product_option_value'])) {
					foreach ((array)$product_option['product_option_value'] as $v) {
						if (strpos(trim($v['price']), "#") === 0 && preg_match('/^#\s*(?P<operation>[+-\/\*])\s*(?P<operand>-?\d+\.?\d*)(?P<percent>%)?$/', trim($v['price'])) !== 1) {
							$errors = true;
							$this->alert['error']['expression'] = $this->language->get('error_expression');
						}
					}
				}
			}
		}

		if (in_array($column, array("category"))) {
			switch ($column) {
				case 'category':
					// Nothing to check here, p_c may be missing if no categories have been selected for the product
					break;
				default:
					$errors = true;
					$this->alert['error']['request'] = $this->language->get('error_update');
					break;
			}

			if (!isset($data['p_id'])) {
				$errors = true;
				$this->alert['error']['request'] = $this->language->get('error_update');
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
		if (!$this->user->hasPermission('modify', 'catalog/product') || !$this->user->hasPermission('modify', 'extension/module/admin_quick_edit')) {
			$this->alert['error']['permission'] = $this->language->get('error_permission');
			return false;
		} else {
			return true;
		}
	}
}
