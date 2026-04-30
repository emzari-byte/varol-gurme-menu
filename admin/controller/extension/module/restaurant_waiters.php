<?php
class ControllerExtensionModuleRestaurantWaiters extends Controller {
	private $error = array();

	public function index() {
		$this->document->setTitle('Garson Y&ouml;netimi');
		$this->load->model('extension/module/restaurant_waiters');
		$this->getList();
	}

	public function add() {
		$this->document->setTitle('Garson Y&ouml;netimi');
		$this->load->model('extension/module/restaurant_waiters');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
			$this->model_extension_module_restaurant_waiters->addWaiter($this->request->post);

			$this->session->data['success'] = 'Garson ba&#351;ar&#305;yla eklendi.';
			$this->response->redirect($this->url->link('extension/module/restaurant_waiters', 'user_token=' . $this->session->data['user_token'], true));
		}

		$this->getForm();
	}

	public function edit() {
		$this->document->setTitle('Garson Y&ouml;netimi');
		$this->load->model('extension/module/restaurant_waiters');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
			$waiter_id = isset($this->request->get['waiter_id']) ? (int)$this->request->get['waiter_id'] : 0;

			$this->model_extension_module_restaurant_waiters->editWaiter($waiter_id, $this->request->post);

			$this->session->data['success'] = 'Garson ba&#351;ar&#305;yla g&uuml;ncellendi.';
			$this->response->redirect($this->url->link('extension/module/restaurant_waiters', 'user_token=' . $this->session->data['user_token'], true));
		}

		$this->getForm();
	}

	public function delete() {
		$this->load->model('extension/module/restaurant_waiters');

		if (isset($this->request->post['selected']) && $this->validateDelete()) {
			foreach ($this->request->post['selected'] as $waiter_id) {
				$this->model_extension_module_restaurant_waiters->deleteWaiter((int)$waiter_id);
			}

			$this->session->data['success'] = 'Se&ccedil;ilen garsonlar silindi.';
			$this->response->redirect($this->url->link('extension/module/restaurant_waiters', 'user_token=' . $this->session->data['user_token'], true));
		}

		$this->getList();
	}

	protected function getList() {
		$this->load->model('extension/module/restaurant_waiters');

		$data['breadcrumbs'] = array(
			array(
				'text' => 'Ana Sayfa',
				'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
			),
			array(
				'text' => 'Garson Y&ouml;netimi',
				'href' => $this->url->link('extension/module/restaurant_waiters', 'user_token=' . $this->session->data['user_token'], true)
			)
		);

		$data['add'] = $this->url->link('extension/module/restaurant_waiters/add', 'user_token=' . $this->session->data['user_token'], true);
		$data['delete'] = $this->url->link('extension/module/restaurant_waiters/delete', 'user_token=' . $this->session->data['user_token'], true);

		$data['waiters'] = array();

		$results = $this->model_extension_module_restaurant_waiters->getWaiters();

		foreach ($results as $result) {
			$assigned_tables = !empty($result['assigned_table_names']) ? explode('||', $result['assigned_table_names']) : array();

			$data['waiters'][] = array(
				'waiter_id' => $result['waiter_id'],
				'username' => $result['username'],
				'name' => $result['name'],
				'status' => $result['status'],
				'table_count' => $result['table_count'],
				'assigned_tables' => $assigned_tables,
				'work_minutes' => (int)$result['work_minutes'],
				'break_limit_minutes' => (int)$result['break_limit_minutes'],
				'today_break_minutes' => (int)$result['today_break_minutes'],
				'break_remaining_minutes' => max(0, (int)$result['break_limit_minutes'] - (int)$result['today_break_minutes']),
				'on_break' => !empty($result['break_status']),
				'break_started_at' => $result['break_started_at'],
				'edit' => $this->url->link('extension/module/restaurant_waiters/edit', 'user_token=' . $this->session->data['user_token'] . '&waiter_id=' . $result['waiter_id'], true)
			);
		}

		$data['error_warning'] = isset($this->error['warning']) ? $this->error['warning'] : '';
		$data['success'] = isset($this->session->data['success']) ? $this->session->data['success'] : '';

		unset($this->session->data['success']);

		$data['selected'] = isset($this->request->post['selected']) ? (array)$this->request->post['selected'] : array();

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/module/restaurant_waiters_list', $data));
	}

	protected function getForm() {
		$this->load->model('extension/module/restaurant_waiters');

		$data['error_warning'] = isset($this->error['warning']) ? $this->error['warning'] : '';
		$data['error_name'] = isset($this->error['name']) ? $this->error['name'] : '';
		$data['error_username'] = isset($this->error['username']) ? $this->error['username'] : '';
		$data['error_password'] = isset($this->error['password']) ? $this->error['password'] : '';

		$data['breadcrumbs'] = array(
			array(
				'text' => 'Ana Sayfa',
				'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
			),
			array(
				'text' => 'Garson Y&ouml;netimi',
				'href' => $this->url->link('extension/module/restaurant_waiters', 'user_token=' . $this->session->data['user_token'], true)
			)
		);

		$waiter_id = isset($this->request->get['waiter_id']) ? (int)$this->request->get['waiter_id'] : 0;

		if (!$waiter_id) {
			$data['action'] = $this->url->link('extension/module/restaurant_waiters/add', 'user_token=' . $this->session->data['user_token'], true);
		} else {
			$data['action'] = $this->url->link('extension/module/restaurant_waiters/edit', 'user_token=' . $this->session->data['user_token'] . '&waiter_id=' . $waiter_id, true);
		}

		$data['cancel'] = $this->url->link('extension/module/restaurant_waiters', 'user_token=' . $this->session->data['user_token'], true);

		$waiter_info = array();

		if ($waiter_id && $this->request->server['REQUEST_METHOD'] != 'POST') {
			$waiter_info = $this->model_extension_module_restaurant_waiters->getWaiter($waiter_id);
		}

		$data['tables'] = $this->model_extension_module_restaurant_waiters->getRestaurantTables();

		$data['username'] = isset($this->request->post['username']) ? $this->request->post['username'] : (!empty($waiter_info) ? $waiter_info['username'] : '');
		$data['password'] = isset($this->request->post['password']) ? $this->request->post['password'] : '';
		$data['name'] = isset($this->request->post['name']) ? $this->request->post['name'] : (!empty($waiter_info) ? $waiter_info['name'] : '');
		$data['status'] = isset($this->request->post['status']) ? (int)$this->request->post['status'] : (!empty($waiter_info) ? (int)$waiter_info['status'] : 1);
		$data['work_minutes'] = isset($this->request->post['work_minutes']) ? (int)$this->request->post['work_minutes'] : (!empty($waiter_info['work_minutes']) ? (int)$waiter_info['work_minutes'] : 600);
		$data['break_limit_minutes'] = isset($this->request->post['break_limit_minutes']) ? (int)$this->request->post['break_limit_minutes'] : (!empty($waiter_info['break_limit_minutes']) ? (int)$waiter_info['break_limit_minutes'] : 60);

		if (isset($this->request->post['assigned_tables'])) {
			$data['assigned_tables'] = (array)$this->request->post['assigned_tables'];
		} elseif ($waiter_id) {
			$data['assigned_tables'] = $this->model_extension_module_restaurant_waiters->getWaiterTableIds($waiter_id);
		} else {
			$data['assigned_tables'] = array();
		}

		$data['is_edit'] = $waiter_id ? true : false;
		$data['break_summary'] = $waiter_id ? $this->model_extension_module_restaurant_waiters->getWaiterBreakSummary($waiter_id) : array();
		$data['break_history'] = $waiter_id ? $this->model_extension_module_restaurant_waiters->getWaiterBreakHistory($waiter_id, 50) : array();

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/module/restaurant_waiters_form', $data));
	}

	protected function validateForm() {
		if (!$this->user->hasPermission('modify', 'extension/module/restaurant_waiters')) {
			$this->error['warning'] = 'Bu i&#351;lem i&ccedil;in yetkiniz yok.';
		}

		$this->load->model('extension/module/restaurant_waiters');

		$waiter_id = isset($this->request->get['waiter_id']) ? (int)$this->request->get['waiter_id'] : 0;
		$username = isset($this->request->post['username']) ? trim($this->request->post['username']) : '';
		$password = isset($this->request->post['password']) ? (string)$this->request->post['password'] : '';
		$name = isset($this->request->post['name']) ? trim($this->request->post['name']) : '';

		if (utf8_strlen($name) < 2) {
			$this->error['name'] = 'Garson ad&#305; en az 2 karakter olmal&#305;.';
		}

		if (utf8_strlen($username) < 3) {
			$this->error['username'] = 'Kullan&#305;c&#305; ad&#305; en az 3 karakter olmal&#305;.';
		} else {
			$existing = $this->model_extension_module_restaurant_waiters->getWaiterByUsername($username);

			if ($existing && (!$waiter_id || (int)$existing['waiter_id'] !== $waiter_id)) {
				$this->error['username'] = 'Bu kullan&#305;c&#305; ad&#305; zaten kullan&#305;mda.';
			}
		}

		if (!$waiter_id && utf8_strlen($password) < 4) {
			$this->error['password'] = '&#350;ifre en az 4 karakter olmal&#305;.';
		}

		if ($waiter_id && $password !== '' && utf8_strlen($password) < 4) {
			$this->error['password'] = '&#350;ifre en az 4 karakter olmal&#305;.';
		}

		if (!$this->model_extension_module_restaurant_waiters->getGarsonUserGroupId()) {
			$this->error['warning'] = '&Ouml;nce System &gt; Users &gt; User Groups alt&#305;nda &quot;Garson&quot; adl&#305; bir kullan&#305;c&#305; grubu olu&#351;turmal&#305;s&#305;n&#305;z.';
		}

		return !$this->error;
	}

	protected function validateDelete() {
		if (!$this->user->hasPermission('modify', 'extension/module/restaurant_waiters')) {
			$this->error['warning'] = 'Bu i&#351;lem i&ccedil;in yetkiniz yok.';
		}

		return !$this->error;
	}
}
