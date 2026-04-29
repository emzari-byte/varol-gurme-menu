<?php
class ControllerExtensionModuleAqeSaleVoucher extends Controller {
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
			$this->response->redirect($this->url->link('sale/voucher', 'user_token=' . $this->session->data['user_token'], true));
		}
	}

	public function index() {
		$this->load->model('sale/voucher');
		$this->load->model('extension/module/aqe/sale/voucher');

		$this->load->language('sale/voucher');
		$this->load->language('extension/module/aqe/sale/general');
		$this->load->language('extension/module/aqe/sale/voucher');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->getList();
	}

	public function delete() {
		$this->load->model('sale/voucher');
		$this->load->model('extension/module/aqe/sale/voucher');

		$this->load->language('sale/voucher');
		$this->load->language('extension/module/aqe/sale/general');
		$this->load->language('extension/module/aqe/sale/voucher');

		$this->document->setTitle($this->language->get('heading_title'));

		if (isset($this->request->post['selected']) && $this->validateDelete()) {
			foreach ($this->request->post['selected'] as $item_id) {
				$this->model_sale_voucher->deleteVoucher($item_id);
			}

			$this->session->data['success'] = sprintf($this->language->get('text_success_delete'), count($this->request->post['selected']));

			$url = '';

			foreach($this->config->get('module_admin_quick_edit_sale_vouchers') as $column => $attr) {
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
				$this->response->redirect($this->url->link('sale/voucher', 'user_token=' . $this->session->data['user_token'] . $url . '&aqer=1', true));
			} else {
				$this->response->redirect($this->url->link('extension/module/admin_quick_edit/sale__voucher__', 'user_token=' . $this->session->data['user_token'] . $url, true));
			}
		}

		$this->getList();
	}

	public function send() {
		$this->load->language('mail/voucher');
		$this->load->language('extension/module/aqe/sale/general');
		$this->load->language('extension/module/aqe/sale/voucher');

		$response = array();

		if ($this->request->server['REQUEST_METHOD'] == 'POST' && $this->validatePermissions()) {
			$this->load->model('sale/voucher');

			$vouchers = array();

			if (isset($this->request->post['selected'])) {
				$vouchers = $this->request->post['selected'];
			} elseif (isset($this->request->post['voucher_id'])) {
				$vouchers[] = $this->request->post['voucher_id'];
			} elseif (isset($this->request->get['voucher_id'])) {
				$vouchers[] = $this->request->get['voucher_id'];
			}

			if ($vouchers) {
				foreach ((array)$vouchers as $voucher_id) {
					$voucher_info = $this->model_sale_voucher->getVoucher($voucher_id);

					if ($voucher_info) {
						if ($voucher_info['order_id']) {
							$order_id = $voucher_info['order_id'];
						} else {
							$order_id = 0;
						}

						$this->load->model('sale/order');

						$order_info = $this->model_sale_order->getOrder($order_id);

						// If voucher belongs to an order
						if ($order_info) {
							$this->load->model('localisation/language');

							$language = new Language($order_info['language_code']);
							$language->load($order_info['language_code']);
							$language->load('mail/voucher');

							// HTML Mail
							$data['title'] = sprintf($language->get('text_subject'), $voucher_info['from_name']);

							$data['text_greeting'] = sprintf($language->get('text_greeting'), $this->currency->format($voucher_info['amount'], (!empty($order_info['currency_code']) ? $order_info['currency_code'] : $this->config->get('config_currency')), (!empty($order_info['currency_value']) ? $order_info['currency_value'] : $this->currency->getValue($this->config->get('config_currency')))));
							$data['text_from'] = sprintf($language->get('text_from'), $voucher_info['from_name']);
							$data['text_message'] = $language->get('text_message');
							$data['text_redeem'] = sprintf($language->get('text_redeem'), $voucher_info['code']);
							$data['text_footer'] = $language->get('text_footer');

							$this->load->model('sale/voucher_theme');

							$voucher_theme_info = $this->model_sale_voucher_theme->getVoucherTheme($voucher_info['voucher_theme_id']);

							if ($voucher_theme_info && is_file(DIR_IMAGE . $voucher_theme_info['image'])) {
								$data['image'] = HTTP_CATALOG . 'image/' . $voucher_theme_info['image'];
							} else {
								$data['image'] = '';
							}

							$data['store_name'] = $order_info['store_name'];
							$data['store_url'] = $order_info['store_url'];
							$data['message'] = nl2br($voucher_info['message']);

							$mail = new Mail($this->config->get('config_mail_engine'));
							$mail->parameter = $this->config->get('config_mail_parameter');
							$mail->smtp_hostname = $this->config->get('config_mail_smtp_hostname');
							$mail->smtp_username = $this->config->get('config_mail_smtp_username');
							$mail->smtp_password = html_entity_decode($this->config->get('config_mail_smtp_password'), ENT_QUOTES, 'UTF-8');
							$mail->smtp_port = $this->config->get('config_mail_smtp_port');
							$mail->smtp_timeout = $this->config->get('config_mail_smtp_timeout');

							$mail->setTo($voucher_info['to_email']);
							$mail->setFrom($this->config->get('config_email'));
							$mail->setSender(html_entity_decode($order_info['store_name'], ENT_QUOTES, 'UTF-8'));
							$mail->setSubject(sprintf($language->get('text_subject'), html_entity_decode($voucher_info['from_name'], ENT_QUOTES, 'UTF-8')));
							$mail->setHtml($this->load->view('mail/voucher', $data));
							$mail->send();

						// If voucher does not belong to an order
						}  else {
							$data['title'] = sprintf($this->language->get('text_subject'), $voucher_info['from_name']);

							$data['text_greeting'] = sprintf($this->language->get('text_greeting'), $this->currency->format($voucher_info['amount'], $this->config->get('config_currency')));
							$data['text_from'] = sprintf($this->language->get('text_from'), $voucher_info['from_name']);
							$data['text_message'] = $this->language->get('text_message');
							$data['text_redeem'] = sprintf($this->language->get('text_redeem'), $voucher_info['code']);
							$data['text_footer'] = $this->language->get('text_footer');

							$this->load->model('sale/voucher_theme');

							$voucher_theme_info = $this->model_sale_voucher_theme->getVoucherTheme($voucher_info['voucher_theme_id']);

							if ($voucher_theme_info && is_file(DIR_IMAGE . $voucher_theme_info['image'])) {
								$data['image'] = HTTP_CATALOG . 'image/' . $voucher_theme_info['image'];
							} else {
								$data['image'] = '';
							}

							$data['store_name'] = $this->config->get('config_name');
							$data['store_url'] = HTTP_CATALOG;
							$data['message'] = nl2br($voucher_info['message']);

							$mail = new Mail($this->config->get('config_mail_engine'));
							$mail->parameter = $this->config->get('config_mail_parameter');
							$mail->smtp_hostname = $this->config->get('config_mail_smtp_hostname');
							$mail->smtp_username = $this->config->get('config_mail_smtp_username');
							$mail->smtp_password = html_entity_decode($this->config->get('config_mail_smtp_password'), ENT_QUOTES, 'UTF-8');
							$mail->smtp_port = $this->config->get('config_mail_smtp_port');
							$mail->smtp_timeout = $this->config->get('config_mail_smtp_timeout');

							$mail->setTo($voucher_info['to_email']);
							$mail->setFrom($this->config->get('config_email'));
							$mail->setSender(html_entity_decode($this->config->get('config_name'), ENT_QUOTES, 'UTF-8'));
							$mail->setSubject(html_entity_decode(sprintf($this->language->get('text_subject'), $voucher_info['from_name']), ENT_QUOTES, 'UTF-8'));
							$mail->setHtml($this->load->view('mail/voucher', $data));
							$mail->send();
						}
					}
				}
				$this->alert['success']['send'] = sprintf($this->language->get('text_success_send'), count($vouchers));
			} else {
				$this->alert['error']['send'] = sprintf($this->language->get('error_selection'), count($vouchers));
			}
		}

		$response = array_merge($response, array("errors" => $this->error), array("alerts" => $this->alert));

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($response));
	}

	protected function getList() {
		$data['module_admin_quick_edit_tooltip'] = ($this->config->get('module_admin_quick_edit_quick_edit_on') == 'dblclick') ? $this->language->get('text_double_click_edit') : $this->language->get('text_click_edit');
		$data['module_admin_quick_edit_quick_edit_on'] = $this->config->get('module_admin_quick_edit_quick_edit_on');
		$data['module_admin_quick_edit_row_hover_highlighting'] = $this->config->get('module_admin_quick_edit_row_hover_highlighting');
		$data['module_admin_quick_edit_alternate_row_colour'] = $this->config->get('module_admin_quick_edit_alternate_row_colour');

		$this->document->addScript('view/javascript/aqe/catalog.min.js?v=' . EXTENSION_VERSION);

		$this->document->addStyle('view/stylesheet/aqe/catalog.min.css?v=' . EXTENSION_VERSION);

		$filters = array();

		foreach($this->config->get('module_admin_quick_edit_sale_vouchers') as $column => $attr) {
			$filters[$column] = (isset($this->request->get['filter_' . $column])) ? $this->request->get['filter_' . $column] : null;
		}

		if (isset($this->request->get['sort'])) {
			$sort = $this->request->get['sort'];
		} else {
			$sort = $this->config->get('module_admin_quick_edit_sale_vouchers_default_sort');
		}

		if (isset($this->request->get['order'])) {
			$order = $this->request->get['order'];
		} else {
			$order = $this->config->get('module_admin_quick_edit_sale_vouchers_default_order');
		}

		if (isset($this->request->get['page'])) {
			$page = $this->request->get['page'];
		} else {
			$page = 1;
		}

		$url = '';

		foreach($this->config->get('module_admin_quick_edit_sale_vouchers') as $column => $attr) {
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
			'href'      => (int)$this->config->get('module_admin_quick_edit_override_menu_entry') ? $this->url->link('sale/voucher', 'user_token=' . $this->session->data['user_token'] . $url . '&aqer=1', true) : $this->url->link('extension/module/admin_quick_edit/sale__voucher__', 'user_token=' . $this->session->data['user_token'] . $url, true),
			'active'    => true
		);

		$data['add'] = $this->url->link('sale/voucher/add', 'user_token=' . $this->session->data['user_token'] . $url . '&aqer=1', true);
		$data['delete'] = $this->url->link('sale/voucher/delete', 'user_token=' . $this->session->data['user_token'] . $url . '&aqer=1', true);
		$data['send'] = $this->url->link('extension/module/admin_quick_edit/sale__voucher__send', 'user_token=' . $this->session->data['user_token'] . $url, true);

		$actions = array(
			'send'              => array('display' => 1, 'index' =>  3, 'short' => 'snd',    'type' =>       'send', 'class' => 'btn-default', 'icon' => 'envelope', 'rel' => array()),
			'edit'              => array('display' => 1, 'index' =>  4, 'short' =>  'ed',    'type' =>       'edit', 'class' => 'btn-primary', 'icon' =>   'pencil', 'rel' => array()),
		);

		$actions = array_filter($actions, 'column_display');
		foreach ($actions as $action => $attr) {
			$actions[$action]['name'] = $this->language->get('action_' . $action);
		}
		uasort($actions, 'column_sort');
		$data['voucher_actions'] = $actions;

		$columns = $this->config->get('module_admin_quick_edit_sale_vouchers');
		$columns = array_filter($columns, 'column_display');
		foreach ($columns as $column => $attr) {
			$columns[$column]['name'] = $this->language->get('column_' . $column);
		}
		uasort($columns, 'column_sort');
		$data['voucher_columns'] = $columns;

		$displayed_columns = array_keys($columns);
		$displayed_actions = array_keys($actions);
		$related_columns = array_merge(array_map(function($v) { return isset($v['rel']) ? $v['rel'] : ''; }, $columns), array_map(function($v) { return isset($v['rel']) ? $v['rel'] : ''; }, $actions));

		$data['vouchers'] = array();

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

		$results = $this->model_extension_module_aqe_sale_voucher->getVouchers($filter_data);

		$voucher_total = $this->model_extension_module_aqe_sale_voucher->getTotalVouchers();

		foreach ($results as $result) {
			$_buttons = array();

			foreach ($actions as $action => $attr) {
				switch ($action) {
					case 'edit':
						$_buttons[] = array(
							'type'  => $attr['type'],
							'action'=> $action,
							'title' => $this->language->get('action_' . $action),
							'url'   => html_entity_decode($this->url->link('sale/voucher/edit', '&voucher_id=' . $result['voucher_id'] . '&user_token=' . $this->session->data['user_token'] . $url . '&aqer=1', true), ENT_QUOTES, 'UTF-8'),
							'icon'  => $attr['icon'],
							'name'  => null,
							'rel'   => json_encode($attr['rel']),
							'class' => $attr['class'],
						);
						break;
					case 'send':
						$_buttons[] = array(
							'type'  => $attr['type'],
							'action'=> $action,
							'title' => $this->language->get('action_' . $action),
							'url'   => html_entity_decode($this->url->link('extension/module/admin_quick_edit/sale__voucher__send', '&voucher_id=' . $result['voucher_id'] . '&user_token=' . $this->session->data['user_token'], true), ENT_QUOTES, 'UTF-8'),
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
				'voucher_id' => $result['voucher_id'],
				'selected'   => isset($this->request->post['selected']) && in_array($result['voucher_id'], $this->request->post['selected']),
				'action'     => $_buttons
			);
			if (!is_array($columns)) {
				$row['code'] = $result['code'];
				$row['from_name'] = $result['from_name'];
				$row['to_name'] = $result['to_name'];
				$row['amount'] = $result['amount'];
				$row['theme'] = $result['theme'];
				$row['status'] = $result['status'];
				$row['date_added'] = $result['date_added'];
			} else {
				foreach ($columns as $column => $attr) {
					if ($column == "amount") {
						$row[$column] = $this->currency->format($result[$column], $this->config->get('config_currency'));
					} else if ($column == 'status') {
						if (!$this->config->get('module_admin_quick_edit_highlight_status')) {
							$row[$column] = ((int)$result['status'] ? $this->language->get('text_enabled') : $this->language->get('text_disabled'));
						} else {
							$row[$column] = ((int)$result['status'] ? '<span class="label label-success">' . $this->language->get('text_enabled') . '</span>' : '<span class="label label-danger">' . $this->language->get('text_disabled') . '</span>');
						}
					} else if ($column == 'id') {
						$row[$column] = $result['voucher_id'];
					} else if (in_array($column, array('date_added'))) {
						$date = new DateTime($result[$column]);
						$row[$column] = $date->format("Y-m-d");
					} else if ($column == 'action') {
						$row[$column] = $_buttons;
					} else if ($column == 'selector') {
						$row[$column] = '';
					} else {
						$row[$column] = $result[$column];
					}
				}
			}
			$data['vouchers'][] = $row;
		}

		$data['language_id'] = $this->config->get('config_language_id');

		$column_classes = array();
		$type_classes = array();
		$non_sortable = array();

		if (!is_array($columns)) {
			$displayed_columns = array('selector', 'code', 'from_name', 'to_name', 'amount', 'theme', 'status', 'date_added', 'action');
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

		$data['update_url'] = html_entity_decode($this->url->link('extension/module/admin_quick_edit/sale__voucher__quick_update', 'user_token=' . $this->session->data['user_token'], true));

		$data['status_select'] = addslashes(json_encode(array(array("id" => "0", "value" => $this->language->get('text_disabled')), array("id" => "1", "value" => $this->language->get('text_enabled')))));

		$data['batch_edit'] = (int)$this->config->get('module_admin_quick_edit_batch_edit');

		if (in_array("theme", $displayed_columns)) {
			$this->load->model('sale/voucher_theme');
			$data['voucher_themes'] = $this->model_sale_voucher_theme->getVoucherThemes();
			$vt_select = array();
			foreach ($data['voucher_themes'] as $vt) {
				$vt_select[] = array("id" => $vt['voucher_theme_id'], "value" => $vt['name']);
			}
			$data['voucher_themes_select'] = addslashes(json_encode($vt_select, JSON_UNESCAPED_SLASHES));
		} else {
			$data['voucher_themes_select'] = addslashes(json_encode(array()));
		}

		$data['user_token'] = $this->session->data['user_token'];

		$url = '';

		foreach ($this->config->get('module_admin_quick_edit_sale_vouchers') as $column => $attr) {
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
		foreach ($this->config->get('module_admin_quick_edit_sale_vouchers') as $column => $attr) {
			if ((int)$this->config->get('module_admin_quick_edit_override_menu_entry')) {
				$data['sorts'][$column] = $this->url->link('sale/voucher', 'user_token=' . $this->session->data['user_token'] . '&sort=' . $attr['sort'] . $url . '&aqer=1', true);
			} else {
				$data['sorts'][$column] = $this->url->link('extension/module/admin_quick_edit/sale__voucher__', 'user_token=' . $this->session->data['user_token'] . '&sort=' . $attr['sort'] . $url, true);
			}
		}

		$url = '';

		foreach ($this->config->get('module_admin_quick_edit_sale_vouchers') as $column => $attr) {
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
		$pagination->total = $voucher_total;
		$pagination->page = $page;
		$pagination->limit = $this->config->get('config_limit_admin');

		if ((int)$this->config->get('module_admin_quick_edit_override_menu_entry')) {
			$pagination->url = $this->url->link('sale/voucher', 'user_token=' . $this->session->data['user_token'] . $url . '&page={page}' . '&aqer=1', true);
		} else {
			$pagination->url = $this->url->link('extension/module/admin_quick_edit/sale__voucher__', 'user_token=' . $this->session->data['user_token'] . $url . '&page={page}', true);
		}

		$data['pagination'] = $pagination->render();

		$data['results'] = sprintf($this->language->get('text_pagination'), ($voucher_total) ? (($page - 1) * $this->config->get('config_limit_admin')) + 1 : 0, ((($page - 1) * $this->config->get('config_limit_admin')) > ($voucher_total - $this->config->get('config_limit_admin'))) ? $voucher_total : ((($page - 1) * $this->config->get('config_limit_admin')) + $this->config->get('config_limit_admin')), $voucher_total, ceil($voucher_total / $this->config->get('config_limit_admin')));

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

		$template = 'extension/module/aqe/sale/voucher_list';

		$this->response->setOutput($this->load->view($template, $data));
	}

	public function quick_update() {
		$this->load->model('sale/voucher');
		$this->load->model('extension/module/aqe/sale/voucher');

		$this->load->language('sale/voucher');
		$this->load->language('extension/module/aqe/sale/general');
		$this->load->language('extension/module/aqe/sale/voucher');

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
				$result = $this->model_extension_module_aqe_sale_voucher->quickEditVoucher($_id, $column, $value, $lang_id, $this->request->post);
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

				if ($column == 'amount') {
					if ($expression) {
						$response['value'] = $this->currency->format((float)$_results[$id[0]], $this->config->get('config_currency'));
						if (count($id) > 1) {
							foreach ($id as $_id) {
								$response['values'][$_id] = $this->currency->format((float)$_results[$_id], $this->config->get('config_currency'));
							}
						}
					} else {
						$response['value'] = $this->currency->format($value, $this->config->get('config_currency'));
						$response['values']['*'] = $response['value'];
					}
				} else if ($column == 'status') {
					if (!$this->config->get('module_admin_quick_edit_highlight_status')) {
						$response['value'] = ((int)$value) ? $this->language->get('text_enabled') : $this->language->get('text_disabled');
					} else {
						$response['value'] = ((int)$value) ? '<span class="label label-success">' . $this->language->get('text_enabled') . '</span>' : '<span class="label label-danger">' . $this->language->get('text_disabled') . '</span>';
					}
					$response['values']['*'] = $response['value'];
				} else if ($column == 'theme') {
					$this->load->model('sale/voucher_theme');
					$voucher_theme = $this->model_sale_voucher_theme->getVoucherTheme((int)$value);
					if ($voucher_theme)
						$response['value'] = $voucher_theme['name'];
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

		if ($column == "name") {
			if ((utf8_strlen(trim($data['first_name'])) < 1) || (utf8_strlen(trim($data['first_name'])) > 32)) {
				$errors = true;
				$this->error['first_name'] = $this->language->get('error_firstname');
			}
			if ((utf8_strlen(trim($data['last_name'])) < 1) || (utf8_strlen(trim($data['last_name'])) > 32)) {
				$errors = true;
				$this->error['last_name'] = $this->language->get('error_lastname');
			}
		}

		if ($column == "email") {
			$voucher_info = $this->model_sale_voucher->getVoucherByEmail($data['new']);

			if (((utf8_strlen($data['new']) > 96) || !preg_match('/^[^\@]+@.*.[a-z]{2,15}$/i', $data['new']))) {
				$errors = true;
				$this->alert['error']['email'] = $this->language->get('error_email');
			} else if ($voucher_info && $id != $voucher_info['voucher_id']) {
				$errors = true;
				$this->alert['error']['email'] = $this->language->get('error_exists');
			}
		}

		if ($column == "code") {
			$voucher_info = $this->model_sale_voucher->getVoucherByCode($data['new']);

			if (isset($data['ids']) && count((array)$data['ids']) > 1) {
				$errors = true;
				$this->alert['error']['request'] = $this->language->get('error_batch_edit_code');
			} else if ((utf8_strlen($data['new']) < 3) || (utf8_strlen($data['new']) > 10)) {
				$errors = true;
				$this->alert['error']['code'] = $this->language->get('error_code');
			} else if ($voucher_info && $voucher_info['voucher_id'] != $id) {
				$errors = true;
				$this->alert['error']['code'] = $this->language->get('error_exists');
			}
		}

		if ($column == "to_name" && ((utf8_strlen($data['new']) < 1) || utf8_strlen($data['new']) > 64)) {
			$errors = true;
			$this->alert['error']['to_name'] = $this->language->get('error_to_name');
		}

		if ($column == "to_email" && ((utf8_strlen($data['new']) > 96) || !preg_match('/^[^\@]+@.*.[a-z]{2,15}$/i', $data['new']))) {
			$errors = true;
			$this->alert['error']['to_email'] = $this->language->get('error_email');
		}

		if ($column == "from_name" && ((utf8_strlen($data['new']) < 1) || utf8_strlen($data['new']) > 64)) {
			$errors = true;
			$this->alert['error']['from_name'] = $this->language->get('error_from_name');
		}

		if ($column == "from_email" && ((utf8_strlen($data['new']) > 96) || !preg_match('/^[^\@]+@.*.[a-z]{2,15}$/i', $data['new']))) {
			$errors = true;
			$this->alert['error']['from_email'] = $this->language->get('error_email');
		}

		if (in_array($column, array('amount')) && strpos(trim($data['new']), "#") === 0 && preg_match('/^#\s*(?P<operation>[+-\/\*])\s*(?P<operand>-?\d+\.?\d*)(?P<percent>%)?$/', trim($data['new'])) !== 1) {
			$errors = true;
			$this->alert['error']['expression'] = $this->language->get('error_expression');
		} elseif (!(strpos(trim($data['new']), "#") === 0 && preg_match('/^#\s*(?P<operation>[+-\/\*])\s*(?P<operand>-?\d+\.?\d*)(?P<percent>%)?$/', trim($data['new'])) === 1) && $column == "amount" && $data['new'] < 1) {
			$errors = true;
			$this->alert['error']['amount'] = $this->language->get('error_amount');
		}

		if ($this->error && !isset($this->alert['warning']['warning'])) {
			$this->alert['warning']['warning'] = $this->language->get('error_warning');
		}

		return !$errors;
	}

	private function validatePermissions() {
		if (!$this->user->hasPermission('modify', 'sale/voucher') || !$this->user->hasPermission('modify', 'extension/module/admin_quick_edit')) {
			$this->alert['error']['permission'] = $this->language->get('error_permission');
			return false;
		} else {
			return true;
		}
	}
}
