<?php
class ControllerExtensionModuleAqeDesignSeoUrl extends Controller {
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
			$this->response->redirect($this->url->link('design/seo_url', 'user_token=' . $this->session->data['user_token'], true));
		}
	}

	public function index() {
		$this->load->model('design/seo_url');
		$this->load->model('extension/module/aqe/design/seo_url');

		$this->load->language('design/seo_url');
		$this->load->language('extension/module/aqe/design/general');
		$this->load->language('extension/module/aqe/design/seo_url');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->getList();
	}

	public function delete() {
		$this->load->model('design/seo_url');
		$this->load->model('extension/module/aqe/design/seo_url');

		$this->load->language('design/seo_url');
		$this->load->language('extension/module/aqe/design/general');
		$this->load->language('extension/module/aqe/design/seo_url');

		$this->document->setTitle($this->language->get('heading_title'));

		if (isset($this->request->post['selected']) && $this->validateDelete()) {
			foreach ($this->request->post['selected'] as $item_id) {
				$this->model_design_seo_url->deleteSeoUrl($item_id);
			}

			$this->session->data['success'] = sprintf($this->language->get('text_success_delete'), count($this->request->post['selected']));

			$url = '';

			foreach($this->config->get('module_admin_quick_edit_design_seo_urls') as $column => $attr) {
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
				$this->response->redirect($this->url->link('design/seo_url', 'user_token=' . $this->session->data['user_token'] . $url . '&aqer=1', true));
			} else {
				$this->response->redirect($this->url->link('extension/module/admin_quick_edit/design__seo_url__', 'user_token=' . $this->session->data['user_token'] . $url, true));
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

		foreach($this->config->get('module_admin_quick_edit_design_seo_urls') as $column => $attr) {
			$filters[$column] = (isset($this->request->get['filter_' . $column])) ? $this->request->get['filter_' . $column] : null;
		}

		if (isset($this->request->get['sort'])) {
			$sort = $this->request->get['sort'];
		} else {
			$sort = $this->config->get('module_admin_quick_edit_design_seo_urls_default_sort');
		}

		if (isset($this->request->get['order'])) {
			$order = $this->request->get['order'];
		} else {
			$order = $this->config->get('module_admin_quick_edit_design_seo_urls_default_order');
		}

		if (isset($this->request->get['page'])) {
			$page = $this->request->get['page'];
		} else {
			$page = 1;
		}

		$url = '';

		foreach($this->config->get('module_admin_quick_edit_design_seo_urls') as $column => $attr) {
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
			'href'      => (int)$this->config->get('module_admin_quick_edit_override_menu_entry') ? $this->url->link('design/seo_url', 'user_token=' . $this->session->data['user_token'] . $url . '&aqer=1', true) : $this->url->link('extension/module/admin_quick_edit/design__seo_url__', 'user_token=' . $this->session->data['user_token'] . $url, true),
			'active'    => true
		);

		$data['add'] = $this->url->link('design/seo_url/add', 'user_token=' . $this->session->data['user_token'] . $url . '&aqer=1', true);
		$data['delete'] = $this->url->link('design/seo_url/delete', 'user_token=' . $this->session->data['user_token'] . $url . '&aqer=1', true);

		$actions = array(
			'edit'              => array('display' => 1, 'index' =>  1, 'short' => 'ed',    'type' =>       'edit', 'class' => 'btn-primary', 'icon' => 'pencil', 'rel' => array()),
		);

		$actions = array_filter($actions, 'column_display');
		foreach ($actions as $action => $attr) {
			$actions[$action]['name'] = $this->language->get('action_' . $action);
		}
		uasort($actions, 'column_sort');
		$data['seo_url_actions'] = $actions;

		$columns = $this->config->get('module_admin_quick_edit_design_seo_urls');
		$columns = array_filter($columns, 'column_display');
		foreach ($columns as $column => $attr) {
			$columns[$column]['name'] = $this->language->get('column_' . $column);
		}
		uasort($columns, 'column_sort');
		$data['seo_url_columns'] = $columns;

		$displayed_columns = array_keys($columns);
		$displayed_actions = array_keys($actions);
		$related_columns = array_merge(array_map(function($v) { return isset($v['rel']) ? $v['rel'] : ''; }, $columns), array_map(function($v) { return isset($v['rel']) ? $v['rel'] : ''; }, $actions));

		$data['seo_urls'] = array();

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

		$results = $this->model_extension_module_aqe_design_seo_url->getSeoUrls($filter_data);

		$seo_url_total = $this->model_extension_module_aqe_design_seo_url->getTotalSeoUrls();

		foreach ($results as $result) {
			$_buttons = array();

			foreach ($actions as $action => $attr) {
				switch ($action) {
					case 'edit':
						$_buttons[] = array(
							'type'  => $attr['type'],
							'action'=> $action,
							'title' => $this->language->get('action_' . $action),
							'url'   => html_entity_decode($this->url->link('design/seo_url/edit', '&seo_url_id=' . $result['seo_url_id'] . '&user_token=' . $this->session->data['user_token'] . $url . '&aqer=1', true), ENT_QUOTES, 'UTF-8'),
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
							'class' => $attr['class'],
						);
						break;
				}
			}

			$row = array(
				'seo_url_id'  => $result['seo_url_id'],
				'selected'   => isset($this->request->post['selected']) && in_array($result['seo_url_id'], $this->request->post['selected']),
				'action'     => $_buttons
			);
			if (!is_array($columns)) {
				$row['query'] = $result['query'];
				$row['keyword'] = $result['keyword'];
				$row['store'] = $result['store'];
				$row['language'] = $result['language'];
			} else {
				foreach ($columns as $column => $attr) {
					if ($column == 'id') {
						$row[$column] = $result['seo_url_id'];
					} else if ($column == 'action') {
						$row[$column] = $_buttons;
					} else if ($column == 'selector') {
						$row[$column] = '';
					} else {
						$row[$column] = $result[$column];
					}
				}
			}
			$data['seo_urls'][] = $row;
		}

		$data['language_id'] = $this->config->get('config_language_id');

		$column_classes = array();
		$type_classes = array();
		$non_sortable = array();

		if (!is_array($columns)) {
			$displayed_columns = array('selector', 'query', 'keyword', 'store', 'language', 'action');
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

		$data['update_url'] = html_entity_decode($this->url->link('extension/module/admin_quick_edit/design__seo_url__quick_update', 'user_token=' . $this->session->data['user_token'], true));

		$data['batch_edit'] = (int)$this->config->get('module_admin_quick_edit_batch_edit');

		if (in_array("store", $displayed_columns)) {
			$this->load->model('setting/store');

			$stores = $this->model_setting_store->getStores();

			$data['stores'] = array();

			$data['stores'][0] = array(
				'store_id'  => '0',
				'name'      => $this->config->get('config_name'),
				'url'       => HTTP_CATALOG
			);

			foreach ($stores as $store) {
				$data['stores'][$store['store_id']] = array(
					'store_id'  => $store['store_id'],
					'name'      => $store['name'],
					'url'       => $store['url']
				);
			}

			$s_select = array();
			foreach ($data['stores'] as $s) {
				$s_select[] = array("id" => $s['store_id'], "value" => $s['name']);
			}
			$data['stores_select'] = addslashes(json_encode($s_select, JSON_UNESCAPED_SLASHES));
		} else {
			$data['stores_select'] = addslashes(json_encode(array()));
		}

		if (in_array("language", $displayed_columns)) {
			$this->load->model('localisation/language');

			$data['languages'] = $this->model_localisation_language->getLanguages();

			$l_select = array();
			foreach ($data['languages'] as $l) {
				$l_select[] = array("id" => $l['language_id'], "value" => $l['name']);
			}
			$data['languages_select'] = addslashes(json_encode($l_select, JSON_UNESCAPED_SLASHES));
		} else {
			$data['languages_select'] = addslashes(json_encode(array()));
		}

		$data['user_token'] = $this->session->data['user_token'];

		$url = '';

		foreach ($this->config->get('module_admin_quick_edit_design_seo_urls') as $column => $attr) {
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
		foreach ($this->config->get('module_admin_quick_edit_design_seo_urls') as $column => $attr) {
			if ((int)$this->config->get('module_admin_quick_edit_override_menu_entry')) {
				$data['sorts'][$column] = $this->url->link('design/seo_url', 'user_token=' . $this->session->data['user_token'] . '&sort=' . $attr['sort'] . $url . '&aqer=1', true);
			} else {
				$data['sorts'][$column] = $this->url->link('extension/module/admin_quick_edit/design__seo_url__', 'user_token=' . $this->session->data['user_token'] . '&sort=' . $attr['sort'] . $url, true);
			}
		}

		$url = '';

		foreach ($this->config->get('module_admin_quick_edit_design_seo_urls') as $column => $attr) {
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
		$pagination->total = $seo_url_total;
		$pagination->page = $page;
		$pagination->limit = $this->config->get('config_limit_admin');

		if ((int)$this->config->get('module_admin_quick_edit_override_menu_entry')) {
			$pagination->url = $this->url->link('design/seo_url', 'user_token=' . $this->session->data['user_token'] . $url . '&page={page}' . '&aqer=1', true);
		} else {
			$pagination->url = $this->url->link('extension/module/admin_quick_edit/design__seo_url__', 'user_token=' . $this->session->data['user_token'] . $url . '&page={page}', true);
		}

		$data['pagination'] = $pagination->render();

		$data['results'] = sprintf($this->language->get('text_pagination'), ($seo_url_total) ? (($page - 1) * $this->config->get('config_limit_admin')) + 1 : 0, ((($page - 1) * $this->config->get('config_limit_admin')) > ($seo_url_total - $this->config->get('config_limit_admin'))) ? $seo_url_total : ((($page - 1) * $this->config->get('config_limit_admin')) + $this->config->get('config_limit_admin')), $seo_url_total, ceil($seo_url_total / $this->config->get('config_limit_admin')));

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

		$template = 'extension/module/aqe/design/seo_url_list';

		$this->response->setOutput($this->load->view($template, $data));
	}

	public function quick_update() {
		$this->load->model('design/seo_url');
		$this->load->model('extension/module/aqe/design/seo_url');

		$this->load->language('design/seo_url');
		$this->load->language('extension/module/aqe/design/general');
		$this->load->language('extension/module/aqe/design/seo_url');

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
				$result = $this->model_extension_module_aqe_design_seo_url->quickEditSeoUrl($_id, $column, $value, $lang_id, $this->request->post);
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

				if (in_array($column, array('query', 'keyword'))) {
					$response['value'] = $value;
					$response['values']['*'] = $response['value'];
				} else if ($column == 'store') {
					if ((int)$value) {
						$this->load->model('setting/store');

						$store = $this->model_setting_store->getStore((int)$value);

						if ($store)
							$response['value'] = $store['name'];
						else
							$response['value'] = '';
					} else {
						$response['value'] = $this->config->get('config_name');
					}

					$response['values']['*'] = $response['value'];
				} else if ($column == 'language') {
					$this->load->model('localisation/language');

					$language = $this->model_localisation_language->getLanguage((int)$value);

					if ($language)
						$response['value'] = $language['name'];
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

		if (isset($data['ids'])) {
			$id = array_unique(array_merge((array)$id, (array)$data['ids']));
		}

		foreach((array)$id as $_id) {
			if ($column == "keyword") {
				if ((utf8_strlen(trim($data['new'])) < 1)) {
					$errors = true;

					$this->alert['error']['keyword'] = $this->language->get('error_keyword');
				} else {
					$old_seo_url = $this->model_design_seo_url->getSeoUrl($_id);
					$seo_urls = $this->model_extension_module_aqe_design_seo_url->getSeoUrlsByKeyword(trim($data['new']));

					foreach ($seo_urls as $seo_url) {
						if ($seo_url['store_id'] == $old_seo_url['store_id'] && $seo_url['query'] != $old_seo_url['query']) {
							$errors = true;
							$this->alert['error']['keyword'] = sprintf($this->language->get('error_keyword_exists'), $seo_url['query']);
							break;
						}
					}
				}
			}

			if ($column == "query") {
				if ((utf8_strlen(trim($data['new'])) < 3) || (utf8_strlen(trim($data['new'])) > 64)) {
					$errors = true;

					$this->alert['error']['query'] = $this->language->get('error_query');
				} else {
					$old_seo_url = $this->model_design_seo_url->getSeoUrl($_id);
					$seo_urls = $this->model_extension_module_aqe_design_seo_url->getSeoUrlsByQuery(trim($data['new']));

					foreach ($seo_urls as $seo_url) {
						if ($seo_url['seo_url_id'] != $old_seo_url['seo_url_id'] && $seo_url['store_id'] == $old_seo_url['store_id'] && $seo_url['language_id'] == $old_seo_url['language_id']) {
							$errors = true;
							$this->alert['error']['query'] = sprintf($this->language->get('error_query_exists'), $seo_url['keyword']);
							break;
						}
					}
				}
			}

			if ($column == "store") {
				$old_seo_url = $this->model_design_seo_url->getSeoUrl($_id);
				$seo_urls = $this->model_extension_module_aqe_design_seo_url->getSeoUrlsByQuery($old_seo_url['query']);

				foreach ($seo_urls as $seo_url) {
					if ($seo_url['seo_url_id'] != $old_seo_url['seo_url_id'] && $seo_url['store_id'] == $data['new'] && $seo_url['language_id'] == $old_seo_url['language_id']) {
						$errors = true;
						$this->alert['error']['query'] = $this->language->get('error_store_exists');
						break;
					}
				}

				$seo_urls = $this->model_extension_module_aqe_design_seo_url->getSeoUrlsByKeyword($old_seo_url['keyword']);

				foreach ($seo_urls as $seo_url) {
					if ($seo_url['seo_url_id'] != $old_seo_url['seo_url_id'] && $seo_url['store_id'] == $data['new'] && $seo_url['language_id'] == $old_seo_url['language_id']) {
						$errors = true;
						$this->alert['error']['query'] = $this->language->get('error_store_exists');
						break;
					}
				}
			}

			if ($column == "language") {
				$old_seo_url = $this->model_design_seo_url->getSeoUrl($_id);
				$seo_urls = $this->model_extension_module_aqe_design_seo_url->getSeoUrlsByQuery($old_seo_url['query']);

				foreach ($seo_urls as $seo_url) {
					if ($seo_url['seo_url_id'] != $old_seo_url['seo_url_id'] && $seo_url['store_id'] == $old_seo_url['store_id'] && $seo_url['language_id'] == $data['new']) {
						$errors = true;
						$this->alert['error']['query'] = $this->language->get('error_language_exists');
						break;
					}
				}

				$seo_urls = $this->model_extension_module_aqe_design_seo_url->getSeoUrlsByKeyword($old_seo_url['keyword']);

				foreach ($seo_urls as $seo_url) {
					if ($seo_url['seo_url_id'] != $old_seo_url['seo_url_id'] && $seo_url['store_id'] == $old_seo_url['store_id'] && $seo_url['language_id'] == $data['new']) {
						$errors = true;
						$this->alert['error']['query'] = $this->language->get('error_store_exists');
						break;
					}
				}
			}
		}

		if ($this->error && !isset($this->alert['warning']['warning'])) {
			$this->alert['warning']['warning'] = $this->language->get('error_warning');
		}

		return !$errors;
	}

	private function validatePermissions() {
		if (!$this->user->hasPermission('modify', 'design/seo_url') || !$this->user->hasPermission('modify', 'extension/module/admin_quick_edit')) {
			$this->alert['error']['permission'] = $this->language->get('error_permission');
			return false;
		} else {
			return true;
		}
	}
}
