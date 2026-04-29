<?php
class ControllerExtensionModuleAqeCatalogCategory extends Controller {
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
			$this->response->redirect($this->url->link('catalog/category', 'user_token=' . $this->session->data['user_token'], true));
		}
	}

	public function index() {
		$this->load->model('catalog/category');
		$this->load->model('extension/module/aqe/catalog/category');

		$this->load->language('catalog/category');
		$this->load->language('extension/module/aqe/catalog/general');
		$this->load->language('extension/module/aqe/catalog/category');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->getList();
	}

	public function delete() {
		$this->load->model('catalog/category');
		$this->load->model('extension/module/aqe/catalog/category');

		$this->load->language('catalog/category');
		$this->load->language('extension/module/aqe/catalog/general');
		$this->load->language('extension/module/aqe/catalog/category');

		$this->document->setTitle($this->language->get('heading_title'));

		if (isset($this->request->post['selected']) && $this->validateDelete()) {
			foreach ($this->request->post['selected'] as $item_id) {
				$this->model_catalog_category->deleteCategory($item_id);
			}

			$this->session->data['success'] = sprintf($this->language->get('text_success_delete'), count($this->request->post['selected']));

			$url = '';

			foreach($this->config->get('module_admin_quick_edit_catalog_categories') as $column => $attr) {
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
				$this->response->redirect($this->url->link('catalog/category', 'user_token=' . $this->session->data['user_token'] . $url . '&aqer=1', true));
			} else {
				$this->response->redirect($this->url->link('extension/module/admin_quick_edit/catalog__category__', 'user_token=' . $this->session->data['user_token'] . $url, true));
			}
		}

		$this->getList();
	}

	public function copy() {
		$this->load->model('catalog/category');
		$this->load->model('extension/module/aqe/catalog/category');

		$this->load->language('catalog/category');
		$this->load->language('extension/module/aqe/catalog/general');
		$this->load->language('extension/module/aqe/catalog/category');

		$this->document->setTitle($this->language->get('heading_title'));

		if (isset($this->request->post['selected']) && $this->validateCopy()) {
			foreach ($this->request->post['selected'] as $item_id) {
				$this->model_catalog_category->copyCategory($item_id);
			}

			$this->session->data['success'] = sprintf($this->language->get('text_success_copy'), count($this->request->post['selected']));

			$url = '';

			foreach($this->config->get('module_admin_quick_edit_catalog_categories') as $column => $attr) {
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
				$this->response->redirect($this->url->link('catalog/category', 'user_token=' . $this->session->data['user_token'] . $url . '&aqer=1', true));
			} else {
				$this->response->redirect($this->url->link('extension/module/admin_quick_edit/catalog__category__', 'user_token=' . $this->session->data['user_token'] . $url, true));
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

		foreach($this->config->get('module_admin_quick_edit_catalog_categories') as $column => $attr) {
			$filters[$column] = (isset($this->request->get['filter_' . $column])) ? $this->request->get['filter_' . $column] : null;
		}

		if (isset($this->request->get['sort'])) {
			$sort = $this->request->get['sort'];
		} else {
			$sort = $this->config->get('module_admin_quick_edit_catalog_categories_default_sort');
		}

		if (isset($this->request->get['order'])) {
			$order = $this->request->get['order'];
		} else {
			$order = $this->config->get('module_admin_quick_edit_catalog_categories_default_order');
		}

		if (isset($this->request->get['page'])) {
			$page = $this->request->get['page'];
		} else {
			$page = 1;
		}

		$url = '';

		foreach($this->config->get('module_admin_quick_edit_catalog_categories') as $column => $attr) {
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
			'href'      => (int)$this->config->get('module_admin_quick_edit_override_menu_entry') ? $this->url->link('catalog/category', 'user_token=' . $this->session->data['user_token'] . $url . '&aqer=1', true) : $this->url->link('extension/module/admin_quick_edit/catalog__category__', 'user_token=' . $this->session->data['user_token'] . $url, true),
			'active'    => true
		);

		$data['add'] = $this->url->link('catalog/category/add', 'user_token=' . $this->session->data['user_token'] . $url . '&aqer=1', true);
		$data['copy'] = $this->url->link('catalog/category/copy', 'user_token=' . $this->session->data['user_token'] . $url . '&aqer=1', true);
		$data['delete'] = $this->url->link('catalog/category/delete', 'user_token=' . $this->session->data['user_token'] . $url . '&aqer=1', true);
		$data['rebuild'] = $this->url->link('catalog/category/repair', 'user_token=' . $this->session->data['user_token'] . $url . '&aqer=1', true);

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

		$actions = array(
			'filters'           => array('display' => 1, 'index' =>  1, 'short' => 'fltr',  'type' => 'filters_qe', 'class' =>            '', 'rel' => array('filter')),
			'descriptions'      => array('display' => 1, 'index' =>  2, 'short' => 'desc',  'type' =>   'descr_qe', 'class' =>            '', 'rel' => array()),
			'keywords'          => array('display' => 1, 'index' =>  3, 'short' => 'seo',   'type' =>    'keyw_qe', 'class' =>            '', 'rel' => array('seo', 'view_in_store')),
			'view'              => array('display' => 1, 'index' =>  4, 'short' => 'vw',    'type' =>       'view', 'class' =>            '', 'rel' => array()),
			'edit'              => array('display' => 1, 'index' =>  5, 'short' => 'ed',    'type' =>       'edit', 'class' => 'btn-primary', 'rel' => array()),
		);

		$actions = array_filter($actions, 'column_display');
		foreach ($actions as $action => $attr) {
			$actions[$action]['name'] = $this->language->get('action_' . $action);
		}
		uasort($actions, 'column_sort');
		$data['category_actions'] = $actions;

		$columns = $this->config->get('module_admin_quick_edit_catalog_categories');
		$columns = array_filter($columns, 'column_display');
		foreach ($columns as $column => $attr) {
			$columns[$column]['name'] = $this->language->get('column_' . $column);

			if ($column == 'view_in_store' && !$multistore) {
				unset($columns[$column]);
			}
		}
		uasort($columns, 'column_sort');
		$data['category_columns'] = $columns;

		$displayed_columns = array_keys($columns);
		$displayed_actions = array_keys($actions);
		$related_columns = array_merge(array_map(function($v) { return isset($v['rel']) ? $v['rel'] : ''; }, $columns), array_map(function($v) { return isset($v['rel']) ? $v['rel'] : ''; }, $actions));

		$data['categories'] = array();

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

		$results = $this->model_extension_module_aqe_catalog_category->getCategories($filter_data);

		$category_total = $this->model_extension_module_aqe_catalog_category->getTotalCategories();

		foreach ($results as $result) {
			$_buttons = array();

			foreach ($actions as $action => $attr) {
				switch ($action) {
					case 'edit':
						$_buttons[] = array(
							'type'  => $attr['type'],
							'action'=> $action,
							'title' => $this->language->get('action_' . $action),
							'url'   => html_entity_decode($this->url->link('catalog/category/edit', '&category_id=' . $result['category_id'] . '&user_token=' . $this->session->data['user_token'] . $url . '&aqer=1', true), ENT_QUOTES, 'UTF-8'),
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
							'url'   => html_entity_decode($_url->link('product/category&path=' . $result['category_id']), ENT_QUOTES, 'UTF-8'),
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

			$row = array(
				'category_id'=> $result['category_id'],
				'selected'   => isset($this->request->post['selected']) && in_array($result['category_id'], $this->request->post['selected']),
				'action'     => $_buttons
			);
			if (!is_array($columns)) {
				$row['name'] = $result['name'];
				$row['sort_order'] = $result['sort_order'];
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
						// $row['name'] = $result['name'];
					} else if ($column == 'store') {
						$stores = $this->model_catalog_category->getCategoryStores($result['category_id']);
						$category_stores = array();
						foreach($stores as $store) {
							$category_stores[] = $data['stores'][$store]['name'];
						}
						$row[$column] = implode("<br />", $category_stores);
					} else if ($column == 'filter') {
						$this->load->model('catalog/filter');
						$fs = $this->model_catalog_category->getCategoryFilters($result['category_id']);
						$category_filters = array();
						foreach($fs as $filter_id) {
							$f = $this->model_catalog_filter->getFilter($filter_id);
							if ($f) {
								$category_filters[] = strip_tags(html_entity_decode($f['group'] . ' &gt; ' . $f['name'], ENT_QUOTES, 'UTF-8'));
							}
						}
						$row[$column] = implode("<br />", $category_filters);
					} else if ($column == 'top') {
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
						$row[$column] = $result['category_id'];
					} else if ($column == 'name') {
						if (in_array("parent", $displayed_columns)) {
							$row[$column] = $result['short_name'];
						} else {
							$row[$column] = $result['name'];
						}
					} else if ($column == 'parent') {
						$row[$column] = $result['path'];
					} else if ($column == 'action') {
						$row[$column] = $_buttons;
					} else if ($column == 'selector') {
						$row[$column] = '';
					} else if ($column == 'view_in_store') {
						$category_stores = $this->model_catalog_category->getCategoryStores($result['category_id']);

						$row[$column] = array();

						foreach ($category_stores as $store) {
							if (!in_array($store, array_keys($data['stores'])))
								continue;
							$row[$column][] = array(
								'name' => $data['stores'][$store]['name'],
								'href' => $data['stores'][$store]['make']->link('product/category&path=' . $result['category_id'], '', $data['stores'][$store]['secure'])
							);
						}
					} else {
						$row[$column] = $result[$column];
					}
				}
			}
			$data['categories'][] = $row;
		}

		$data['language_id'] = $this->config->get('config_language_id');

		$column_classes = array();
		$type_classes = array();
		$non_sortable = array();

		if (!is_array($columns)) {
			$displayed_columns = array('selector', 'name', 'sort_order', 'action');
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

		$data['update_url'] = html_entity_decode($this->url->link('extension/module/admin_quick_edit/catalog__category__quick_update', 'user_token=' . $this->session->data['user_token'], true));
		$data['refresh_url'] = html_entity_decode($this->url->link('extension/module/admin_quick_edit/catalog__category__refresh_data', 'user_token=' . $this->session->data['user_token'], true));
		$data['load_popup_url'] = html_entity_decode($this->url->link('extension/module/admin_quick_edit/catalog__category__load_popup', 'user_token=' . $this->session->data['user_token'], true));

		$data['status_select'] = addslashes(json_encode(array(array("id" => "0", "value" => $this->language->get('text_disabled')), array("id" => "1", "value" => $this->language->get('text_enabled')))));
		$data['yes_no_select'] = addslashes(json_encode(array(array("id" => "0", "value" => $this->language->get('text_no')), array("id" => "1", "value" => $this->language->get('text_yes')))));

		$this->load->model('localisation/language');
		$lang_count = $this->model_localisation_language->getTotalLanguages();
		$data['single_lang_editing'] = $this->config->get('module_admin_quick_edit_single_language_editing') || ((int)$lang_count == 1);
		$data['batch_edit'] = (int)$this->config->get('module_admin_quick_edit_batch_edit');

		if (in_array("parent", $displayed_columns)) {
			$data['_categories'] = $this->model_extension_module_aqe_catalog_category->getCategories(array('sort' => 'name'));
			$pc_select = array(array("id" => "0", "value" => $this->language->get('text_none')));
			foreach ($data['_categories'] as $pc) {
				$pc_select[] = array("id" => $pc['category_id'], "value" => $pc['name']);
			}
			$data['parent_select'] = addslashes(json_encode($pc_select, JSON_UNESCAPED_SLASHES));
		} else {
			$data['parent_select'] = addslashes(json_encode(array()));
		}

		if (in_array("filter", $displayed_columns)) {
			$this->load->model('extension/module/aqe/catalog/filter');
			$data['_filters'] = $this->model_extension_module_aqe_catalog_filter->getFiltersByGroup();
		}

		$data['user_token'] = $this->session->data['user_token'];

		$url = '';

		foreach ($this->config->get('module_admin_quick_edit_catalog_categories') as $column => $attr) {
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
		foreach ($this->config->get('module_admin_quick_edit_catalog_categories') as $column => $attr) {
			if ((int)$this->config->get('module_admin_quick_edit_override_menu_entry')) {
				$data['sorts'][$column] = $this->url->link('catalog/category', 'user_token=' . $this->session->data['user_token'] . '&sort=' . $attr['sort'] . $url . '&aqer=1', true);
			} else {
				$data['sorts'][$column] = $this->url->link('extension/module/admin_quick_edit/catalog__category__', 'user_token=' . $this->session->data['user_token'] . '&sort=' . $attr['sort'] . $url, true);
			}
		}

		$url = '';

		foreach ($this->config->get('module_admin_quick_edit_catalog_categories') as $column => $attr) {
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
		$pagination->total = $category_total;
		$pagination->page = $page;
		$pagination->limit = $this->config->get('config_limit_admin');

		if ((int)$this->config->get('module_admin_quick_edit_override_menu_entry')) {
			$pagination->url = $this->url->link('catalog/category', 'user_token=' . $this->session->data['user_token'] . $url . '&page={page}' . '&aqer=1', true);
		} else {
			$pagination->url = $this->url->link('extension/module/admin_quick_edit/catalog__category__', 'user_token=' . $this->session->data['user_token'] . $url . '&page={page}', true);
		}

		$data['pagination'] = $pagination->render();

		$data['results'] = sprintf($this->language->get('text_pagination'), ($category_total) ? (($page - 1) * $this->config->get('config_limit_admin')) + 1 : 0, ((($page - 1) * $this->config->get('config_limit_admin')) > ($category_total - $this->config->get('config_limit_admin'))) ? $category_total : ((($page - 1) * $this->config->get('config_limit_admin')) + $this->config->get('config_limit_admin')), $category_total, ceil($category_total / $this->config->get('config_limit_admin')));

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

		$template = 'extension/module/aqe/catalog/category_list';

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
		$this->load->model('catalog/category');

		if (isset($this->request->get['category_id'])) {
			$category_id = $this->request->get['category_id'];
		} else {
			$category_id = 0;
		}

		$category_data = array();

		$results = $this->model_catalog_category->getCategoriesByCategoryId($category_id);

		foreach ($results as $result) {
			$category_data[] = array(
				'category_id' => $result['category_id'],
				'name'       => $result['name'],
				'model'      => $result['model']
			);
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($category_data));
	}

	public function autocomplete() {
		$this->load->model('extension/module/aqe/catalog/category');

		$response = array();

		if (isset($this->request->get['filter_name']) ||
			isset($this->request->get['filter_seo'])) {

			$filter_types = array('name', 'seo');
			$filters = array();

			foreach($filter_types as $filter) {
				$filters[$filter] = (isset($this->request->get['filter_' . $filter])) ? $this->request->get['filter_' . $filter] : null;
			}

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

			$results = $this->model_extension_module_aqe_catalog_category->getCategories($filter_data);

			foreach ($results as $result) {
				$response[] = array(
					'category_id'=> $result['category_id'],
					'name'       => strip_tags(html_entity_decode($result['name'], ENT_QUOTES, 'UTF-8')),
					'short_name' => strip_tags(html_entity_decode($result['short_name'], ENT_QUOTES, 'UTF-8')),
					'seo'        => (isset($result['seo'])) ? $result['seo'] : '',
				);
			}
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($response));
	}

	public function load_popup() {
		$this->load->model('catalog/category');
		$this->load->model('extension/module/aqe/catalog/category');

		$this->load->language('catalog/category');
		$this->load->language('extension/module/aqe/catalog/general');
		$this->load->language('extension/module/aqe/catalog/category');

		$response = array();

		if ($this->request->server['REQUEST_METHOD'] == 'POST' && $this->validateLoadPopup($this->request->post)) {
			$data['error_warning'] = '';
			list($data['parameter'], $data['i_id']) = explode("-", $this->request->post['id']);

			$data['user_token'] = $this->session->data['user_token'];

			$response["success"] = 1;

			switch ($data['parameter']) {
				case "name":
					$this->load->model('localisation/language');
					$data['languages'] = $this->model_localisation_language->getLanguages();
					$data['value'] = array();
					$descriptions = $this->model_catalog_category->getCategoryDescriptions($data['i_id']);
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
					$data['value'] = $this->model_catalog_category->getCategorySeoUrls($data['i_id']);
					break;
				case "store":
					$this->load->model('setting/store');
					$data['stores'] = $this->model_setting_store->getStores();
					array_unshift($data['stores'], array("store_id" => 0, "name" => $this->config->get('config_name')));
					$data['i_s'] = $this->model_catalog_category->getCategoryStores($data['i_id']);
					$response['title'] = $this->language->get('entry_store');
					break;
				case "filters":
				case "filter":
					$this->load->model('catalog/filter');
					$filters = $this->model_catalog_category->getCategoryFilters($data['i_id']);

					$data['i_f'] = array();

					foreach ($filters as $filter_id) {
						$filter_info = $this->model_catalog_filter->getFilter($filter_id);

						if ($filter_info) {
							$data['i_f'][] = array(
								'filter_id' => $filter_info['filter_id'],
								'name'      => $filter_info['group'] . ' &gt; ' . $filter_info['name']
							);
						}
					}
					break;
				case "descriptions":
					$this->load->model('localisation/language');
					$data['languages'] = $this->model_localisation_language->getLanguages();
					$data['i_d'] = $this->model_catalog_category->getCategoryDescriptions($data['i_id']);
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

		$template = 'extension/module/aqe/catalog/quick_edit_form';

		$response['popup'] = $this->load->view($template, $data);

		$response = array_merge($response, array("errors" => $this->error), array("alerts" => $this->alert));

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($response));
	}

	public function refresh_data() {
		$this->load->model('catalog/category');

		$this->load->language('catalog/category');
		$this->load->language('extension/module/aqe/catalog/general');
		$this->load->language('extension/module/aqe/catalog/category');

		$response = array();

		if ($this->request->server['REQUEST_METHOD'] == 'POST' && $this->validateRefreshData($this->request->post)) {
			$response['values'] = array();

			foreach ($this->request->post['data'] as $column => $categories) {
				foreach ($categories as $id) {
					switch ($column) {
						case 'filter':
							$this->load->model('catalog/filter');
							$filters = $this->model_catalog_category->getCategoryFilters($id);

							$category_filters = array();

							foreach ($filters as $filter_id) {
								$f = $this->model_catalog_filter->getFilter($filter_id);
								$category_filters[] = strip_tags(html_entity_decode($f['group'] . ' &gt; ' . $f['name'], ENT_QUOTES, 'UTF-8'));
							}
							$response['values'][$id][$column] = implode("<br/>", $category_filters);
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

							$category_stores = $this->model_catalog_category->getCategoryStores($id);


							$resp = '<select onchange="((this.value !== \'\') ? window.open(this.value) : null); this.value = \'\';">';
							$resp .= '<option value="">' . $this->language->get('text_select') . '</option>';

							foreach ($category_stores as $store) {
								if (!in_array($store, array_keys($data['stores'])))
									continue;
								$resp .= '<option value="' . $data['stores'][$store]['make']->link('product/category&path=' . $id, '', $data['stores'][$store]['secure']) . '">' . $data['stores'][$store]['name'] . '</option>';
							}

							$resp .= '</select>';

							$response['values'][$id][$column] = $resp;
							break;
						case 'seo':
							$keywords = $this->model_catalog_category->getCategorySeoUrls($id);

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
		$this->load->model('catalog/category');
		$this->load->model('extension/module/aqe/catalog/category');

		$this->load->language('catalog/category');
		$this->load->language('extension/module/aqe/catalog/general');
		$this->load->language('extension/module/aqe/catalog/category');

		$response = array();

		if ($this->request->server['REQUEST_METHOD'] == 'POST' && $this->validateUpdateData($this->request->post)) {
			list($column, $id) = explode("-", $this->request->post['id']);
			$id = (array)$id;
			$value = $this->request->post['new'];
			$lang_id = isset($this->request->post['lang_id']) ? $this->request->post['lang_id'] : null;
			$alt = isset($this->request->post['alt']) ? $this->request->post['alt'] : "";
			$expression = !is_array($value) && strpos(trim($value), "#") === 0 && preg_match('/^#\s*(?P<operator>[+-\/\*])\s*(?P<operand>-?\d+\.?\d*)(?P<percent>%)?$/', trim($value)) === 1;

			if (isset($this->request->post['ids'])) {
				$id = array_unique(array_merge($id, (array)$this->request->post['ids']));
			}

			$results = array('done' => array(), 'failed' => array());
			$_results = array();

			foreach ((array)$id as $_id) {
				$result = $this->model_extension_module_aqe_catalog_category->quickEditCategory($_id, $column, $value, $lang_id, $this->request->post);
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

				if (in_array($column, array('descriptions'))) {
					$response['value'] = $value;
					$response['values']['*'] = $response['value'];
				} else if (in_array($column, array('sort_order', 'column'))) {
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
				} else if (in_array($column, array('top'))) {
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
				} else if(in_array($column, array('seo'))) {
					if (isset($this->request->post['value'])) {
						$response['value'] = isset($this->request->post['value']['0'][$this->config->get('config_language_id')]) ? $this->request->post['value']['0'][$this->config->get('config_language_id')] : '';
					} else
						$response['value'] = $value;
					$response['values']['*'] = $response['value'];
				} else if($column == 'name') {
					$columns = $this->config->get('module_admin_quick_edit_catalog_categories');
					$columns = array_filter($columns, 'column_display');
					$displayed_columns = array_keys($columns);

					foreach ((array)$id as $_id) {
						$category = $this->model_catalog_category->getCategory($_id);
						$response['values'][$_id] = (($category['path'] && !in_array("parent", $displayed_columns)) ? $category['path'] . ' &gt; ' : '') . $category['name'];
					}
					$response['value'] = $response['values'][$id[0]];
				} else if($column == 'parent') {
					if ((int)$value) {
						$category = $this->model_catalog_category->getCategory($value);
						$response['value'] = (($category['path']) ? $category['path'] . ' &gt; ' : '') . $category['name'];
					} else {
						$response['value'] = '';
					}
					$response['values']['*'] = $response['value'];
				} else if($column == 'store') {
					if (isset($this->request->post['i_s'])) {
						$this->request->post['i_s'] = (array)$this->request->post['i_s'];

						$this->load->model('setting/store');
						$stores = $this->model_setting_store->getStores();
						array_unshift($stores, array("store_id" => 0, "name" => $this->config->get('config_name')));

						$category_stores = array();

						foreach ($stores as $store) {
							if (in_array($store['store_id'], $this->request->post['i_s']))
								$category_stores[] = $store['name'];
						}
						$response['value'] = implode("<br>", $category_stores);
					} else {
						$response['value'] = "";
					}
					$response['values']['*'] = $response['value'];
				} else if($column == 'filter') {
					if (isset($this->request->post['i_f'])) {
						$this->request->post['i_f'] = (array)$this->request->post['i_f'];

						$this->load->model('catalog/filter');
						$filters = $this->model_catalog_filter->getFilters(array());

						$category_filters = array();

						foreach ($filters as $filter) {
							if (in_array($filter['filter_id'], $this->request->post['i_f']))
								$category_filters[] = strip_tags(html_entity_decode($filter['group'] . ' &gt; ' . $filter['name'], ENT_QUOTES, 'UTF-8'));
						}
						$response['value'] = implode("<br>", $category_filters);
					} else {
						$response['value'] = "";
					}
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

		if (in_array($column, array('sort_order')) && strpos(trim($data['new']), "#") === 0 && preg_match('/^#\s*(?P<operation>[+-\/\*])\s*(?P<operand>-?\d+\.?\d*)(?P<percent>%)?$/', trim($data['new'])) !== 1) {
			$errors = true;
			$this->alert['error']['expression'] = $this->language->get('error_expression');
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
			if (isset($data['description'])) {
				foreach ((array)$data['description'] as $language_id => $value) {
					if (!isset($value['meta_title']) || utf8_strlen($value['meta_title']) < 3 || utf8_strlen($value['meta_title']) > 255) {
						$errors = true;
						$this->error["description[$language_id][meta_title]"] = $this->language->get('error_meta_title');
					}
				}
			}
		}

		if ($column == "parent") {
			$results = $this->model_catalog_category->getCategoryPath($data['new']);

			foreach ($results as $result) {
				if ($result['path_id'] == $id) {
					$errors = true;
					$this->alert['error']['parent'] = $this->language->get('error_parent');
					break;
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
									if ($seo_url['store_id'] == $store_id && $seo_url['query'] != 'category_id=' . $id) {
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

		if (in_array($column, array("store"))) {
			if (!isset($data['i_id'])) {
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
		if (!$this->user->hasPermission('modify', 'catalog/category') || !$this->user->hasPermission('modify', 'extension/module/admin_quick_edit')) {
			$this->alert['error']['permission'] = $this->language->get('error_permission');
			return false;
		} else {
			return true;
		}
	}
}
