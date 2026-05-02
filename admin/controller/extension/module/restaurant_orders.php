<?php
class ControllerExtensionModuleRestaurantOrders extends Controller {
	public function index() {
		if (!$this->user->hasPermission('access', 'extension/module/restaurant_orders') && !$this->user->hasPermission('access', 'extension/module/restaurant_settings')) {
			$this->response->redirect($this->url->link('error/permission', 'user_token=' . $this->session->data['user_token'], true));
			return;
		}

		$this->document->setTitle('Sipariş Raporları');
		$this->load->model('extension/module/restaurant_orders');

		$filter = array(
			'date_from' => isset($this->request->get['date_from']) ? trim((string)$this->request->get['date_from']) : date('Y-m-d', strtotime('-7 days')),
			'date_to' => isset($this->request->get['date_to']) ? trim((string)$this->request->get['date_to']) : date('Y-m-d'),
			'status' => isset($this->request->get['status']) ? trim((string)$this->request->get['status']) : '',
			'payment_status' => isset($this->request->get['payment_status']) ? trim((string)$this->request->get['payment_status']) : '',
			'table' => isset($this->request->get['table']) ? trim((string)$this->request->get['table']) : '',
			'waiter' => isset($this->request->get['waiter']) ? trim((string)$this->request->get['waiter']) : '',
			'limit' => isset($this->request->get['limit']) ? (int)$this->request->get['limit'] : 120
		);

		$url = '';

		foreach ($filter as $key => $value) {
			if ($value !== '' && $value !== 0) {
				$url .= '&' . $key . '=' . urlencode((string)$value);
			}
		}

		$data['breadcrumbs'] = array(
			array(
				'text' => 'Ana Sayfa',
				'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
			),
			array(
				'text' => 'Sipariş Raporları',
				'href' => $this->url->link('extension/module/restaurant_orders', 'user_token=' . $this->session->data['user_token'] . $url, true)
			)
		);

		$data['filter'] = $filter;
		$data['action'] = $this->url->link('extension/module/restaurant_orders', 'user_token=' . $this->session->data['user_token'], true);
		$data['clear'] = $this->url->link('extension/module/restaurant_orders', 'user_token=' . $this->session->data['user_token'], true);
		$data['user_token'] = $this->session->data['user_token'];
		$data['stats'] = $this->model_extension_module_restaurant_orders->getStats($filter);
		$data['orders'] = $this->model_extension_module_restaurant_orders->getOrders($filter);
		$data['activity'] = $this->model_extension_module_restaurant_orders->getActivity($filter, 120);

		$data['statuses'] = array(
			'' => 'Tüm Durumlar',
			'waiting_order' => 'Garson Onayı Bekliyor',
			'in_kitchen' => 'Mutfakta',
			'ready_for_service' => 'Servise Hazır',
			'out_for_service' => 'Servise Çıktı',
			'served' => 'Servis Edildi',
			'payment_pending' => 'Hesap Bekliyor',
			'paid' => 'Ödendi',
			'completed' => 'Tamamlandı',
			'cancelled' => 'İptal'
		);

		$data['payment_statuses'] = array(
			'' => 'Tüm Ödemeler',
			'unpaid' => 'Ödenmedi',
			'partial' => 'Kısmi',
			'paid' => 'Ödendi'
		);

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/module/restaurant_orders', $data));
	}
}
