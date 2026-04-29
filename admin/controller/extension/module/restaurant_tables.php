<?php
class ControllerExtensionModuleRestaurantTables extends Controller {
	private $error = array();

	public function index() {
		$this->load->language('extension/module/restaurant_tables');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('extension/module/restaurant_tables');

		$this->getList();
	}

	public function add() {
		$this->load->language('extension/module/restaurant_tables');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('extension/module/restaurant_tables');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
			$this->model_extension_module_restaurant_tables->addTable($this->request->post);

			$this->session->data['success'] = $this->language->get('text_success');

			$this->response->redirect($this->url->link('extension/module/restaurant_tables', 'user_token=' . $this->session->data['user_token'], true));
		}

		$this->getForm();
	}

	public function edit() {
		$this->load->language('extension/module/restaurant_tables');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('extension/module/restaurant_tables');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
			$this->model_extension_module_restaurant_tables->editTable($this->request->get['table_id'], $this->request->post);

			$this->session->data['success'] = $this->language->get('text_success');

			$this->response->redirect($this->url->link('extension/module/restaurant_tables', 'user_token=' . $this->session->data['user_token'], true));
		}

		$this->getForm();
	}

	public function delete() {
		$this->load->language('extension/module/restaurant_tables');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('extension/module/restaurant_tables');

		if (isset($this->request->post['selected']) && $this->validateDelete()) {
			foreach ($this->request->post['selected'] as $table_id) {
				$this->model_extension_module_restaurant_tables->deleteTable($table_id);
			}

			$this->session->data['success'] = $this->language->get('text_success');

			$this->response->redirect($this->url->link('extension/module/restaurant_tables', 'user_token=' . $this->session->data['user_token'], true));
		}

		$this->getList();
	}

	protected function getList() {
		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('extension/module/restaurant_tables', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['add'] = $this->url->link('extension/module/restaurant_tables/add', 'user_token=' . $this->session->data['user_token'], true);
		$data['delete'] = $this->url->link('extension/module/restaurant_tables/delete', 'user_token=' . $this->session->data['user_token'], true);

		$data['tables'] = array();

		$results = $this->model_extension_module_restaurant_tables->getTables();

		foreach ($results as $result) {

	$qr_view = $this->url->link(
		'extension/module/restaurant_tables/qr',
		'user_token=' . $this->session->data['user_token'] .
		'&table_id=' . $result['table_id'],
		true
	);

	$data['tables'][] = array(
		'table_id'    => $result['table_id'],
		'table_no'    => $result['table_no'],
		'name'        => $result['name'],
		'capacity'    => $result['capacity'],
		'area'        => $result['area'],
		'sort_order'  => $result['sort_order'],
		'status'      => $result['status'],

		'edit' => $this->url->link(
			'extension/module/restaurant_tables/edit',
			'user_token=' . $this->session->data['user_token'] .
			'&table_id=' . $result['table_id'],
			true
		),

		'qr_view' => $qr_view
	);
}

		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}

		if (isset($this->session->data['success'])) {
			$data['success'] = $this->session->data['success'];
			unset($this->session->data['success']);
		} else {
			$data['success'] = '';
		}

		if (isset($this->request->post['selected'])) {
			$data['selected'] = (array)$this->request->post['selected'];
		} else {
			$data['selected'] = array();
		}

		$data['user_token'] = $this->session->data['user_token'];

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/module/restaurant_tables_list', $data));
	}

	protected function getForm() {
		$data['text_form'] = !isset($this->request->get['table_id']) ? $this->language->get('text_add') : $this->language->get('text_edit');

		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}

		foreach (array('table_no', 'name', 'capacity', 'area', 'sort_order') as $field) {
			$key = 'error_' . $field;
			$data[$key] = isset($this->error[$field]) ? $this->error[$field] : '';
		}

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('extension/module/restaurant_tables', 'user_token=' . $this->session->data['user_token'], true)
		);

		if (!isset($this->request->get['table_id'])) {
			$data['action'] = $this->url->link('extension/module/restaurant_tables/add', 'user_token=' . $this->session->data['user_token'], true);
		} else {
			$data['action'] = $this->url->link('extension/module/restaurant_tables/edit', 'user_token=' . $this->session->data['user_token'] . '&table_id=' . $this->request->get['table_id'], true);
		}

		$data['cancel'] = $this->url->link('extension/module/restaurant_tables', 'user_token=' . $this->session->data['user_token'], true);

		$table_info = array();

		if (isset($this->request->get['table_id']) && $this->request->server['REQUEST_METHOD'] != 'POST') {
			$table_info = $this->model_extension_module_restaurant_tables->getTable($this->request->get['table_id']);
		}

		$fields = array(
			'table_no'   => 0,
			'name'       => '',
			'capacity'   => 4,
			'area'       => 'Salon',
			'sort_order' => 0,
			'status'     => 1
		);

		foreach ($fields as $field => $default) {
			if (isset($this->request->post[$field])) {
				$data[$field] = $this->request->post[$field];
			} elseif (!empty($table_info)) {
				$data[$field] = $table_info[$field];
			} else {
				$data[$field] = $default;
			}
		}

		$data['user_token'] = $this->session->data['user_token'];

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/module/restaurant_tables_form', $data));
	}

	protected function validateForm() {
		if (!$this->user->hasPermission('modify', 'extension/module/restaurant_tables')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		if (!$this->request->post['table_no']) {
			$this->error['table_no'] = 'Masa no gerekli!';
		}

		if ((utf8_strlen($this->request->post['name']) < 1) || (utf8_strlen($this->request->post['name']) > 64)) {
			$this->error['name'] = 'Masa adı 1-64 karakter olmalı!';
		}

		if ((int)$this->request->post['capacity'] <= 0) {
			$this->error['capacity'] = 'Kapasite 1 veya daha büyük olmalı!';
		}

		if ((utf8_strlen($this->request->post['area']) < 1) || (utf8_strlen($this->request->post['area']) > 50)) {
			$this->error['area'] = 'Bölüm bilgisi gerekli!';
		}

		if (!is_numeric($this->request->post['sort_order'])) {
			$this->error['sort_order'] = 'Sıralama sayı olmalı!';
		}

		return !$this->error;
	}

	protected function validateDelete() {
		if (!$this->user->hasPermission('modify', 'extension/module/restaurant_tables')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		return !$this->error;
	}
	public function qr() {
	if (!$this->user->hasPermission('access', 'extension/module/restaurant_tables')) {
		$this->response->redirect($this->url->link('extension/module/restaurant_tables', 'user_token=' . $this->session->data['user_token'], true));
		return;
	}

	$table_id = isset($this->request->get['table_id']) ? (int)$this->request->get['table_id'] : 0;

	if (!$table_id) {
		$this->response->setOutput('Geçersiz masa.');
		return;
	}

	$this->load->model('extension/module/restaurant_tables');

	$table = $this->model_extension_module_restaurant_tables->getTable($table_id);

	if (!$table) {
		$this->response->setOutput('Masa bulunamadı.');
		return;
	}

	if (empty($table['qr_token'])) {
		$this->response->setOutput('Bu masa için qr_token bulunamadı.');
		return;
	}

	$data['table_id'] = $table['table_id'];
	$data['table_no'] = $table['table_no'];
	$data['table_name'] = $table['name'];
	$data['table_area'] = $table['area'];

	$data['qr_link'] = 'https://varolveranda.com/menu/?qr=' . $table['qr_token'];
	$data['qr_image'] = 'https://api.qrserver.com/v1/create-qr-code/?size=400x400&data=' . urlencode($data['qr_link']);

	$this->response->setOutput($this->load->view('extension/module/restaurant_qr', $data));
}
}