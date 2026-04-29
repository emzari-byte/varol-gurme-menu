<?php
class ControllerExtensionModuleAkinsoftBridge extends Controller {
	public function pending() {
		$this->load->model('extension/module/akinsoft_bridge');

		if (!$this->isAuthorized()) {
			return $this->json(array(
				'success' => false,
				'message' => 'Unauthorized'
			), 401);
		}

		if ((int)$this->model_extension_module_akinsoft_bridge->getSetting('restaurant_akinsoft_enabled', 0) !== 1) {
			return $this->json(array(
				'success' => false,
				'message' => 'Akinsoft integration disabled'
			));
		}

		$limit = isset($this->request->get['limit']) ? (int)$this->request->get['limit'] : 20;

		return $this->json(array(
			'success' => true,
			'orders' => $this->model_extension_module_akinsoft_bridge->getPendingOrders($limit)
		));
	}

	public function mark() {
		$this->load->model('extension/module/akinsoft_bridge');

		if (!$this->isAuthorized()) {
			return $this->json(array(
				'success' => false,
				'message' => 'Unauthorized'
			), 401);
		}

		$order_id = isset($this->request->post['restaurant_order_id']) ? (int)$this->request->post['restaurant_order_id'] : 0;
		$status = isset($this->request->post['status']) ? (string)$this->request->post['status'] : '';
		$external_order_no = isset($this->request->post['external_order_no']) ? (string)$this->request->post['external_order_no'] : '';
		$message = isset($this->request->post['message']) ? (string)$this->request->post['message'] : '';

		if (!$this->model_extension_module_akinsoft_bridge->markOrder($order_id, $status, $external_order_no, $message)) {
			return $this->json(array(
				'success' => false,
				'message' => 'Invalid mark request'
			), 400);
		}

		return $this->json(array(
			'success' => true,
			'message' => 'Order integration status updated'
		));
	}

	public function sent() {
		$this->load->model('extension/module/akinsoft_bridge');

		if (!$this->isAuthorized()) {
			return $this->json(array(
				'success' => false,
				'message' => 'Unauthorized'
			), 401);
		}

		$limit = isset($this->request->get['limit']) ? (int)$this->request->get['limit'] : 50;

		return $this->json(array(
			'success' => true,
			'orders' => $this->model_extension_module_akinsoft_bridge->getSentOrders($limit)
		));
	}

	public function paid() {
		$this->load->model('extension/module/akinsoft_bridge');

		if (!$this->isAuthorized()) {
			return $this->json(array(
				'success' => false,
				'message' => 'Unauthorized'
			), 401);
		}

		$order_id = isset($this->request->post['restaurant_order_id']) ? (int)$this->request->post['restaurant_order_id'] : 0;
		$external_fis_id = isset($this->request->post['external_fis_id']) ? (int)$this->request->post['external_fis_id'] : 0;
		$closed_at = isset($this->request->post['closed_at']) ? (string)$this->request->post['closed_at'] : '';
		$message = isset($this->request->post['message']) ? (string)$this->request->post['message'] : '';

		if (!$this->model_extension_module_akinsoft_bridge->markOrderPaid($order_id, $external_fis_id, $closed_at, $message)) {
			return $this->json(array(
				'success' => false,
				'message' => 'Invalid paid request'
			), 400);
		}

		return $this->json(array(
			'success' => true,
			'message' => 'Order marked paid'
		));
	}

	private function isAuthorized() {
		$expected = (string)$this->model_extension_module_akinsoft_bridge->getSetting('restaurant_akinsoft_bridge_token', '');
		$token = '';

		if (!empty($this->request->get['token'])) {
			$token = (string)$this->request->get['token'];
		} elseif (!empty($this->request->post['token'])) {
			$token = (string)$this->request->post['token'];
		} elseif (!empty($this->request->server['HTTP_X_AKINSOFT_TOKEN'])) {
			$token = (string)$this->request->server['HTTP_X_AKINSOFT_TOKEN'];
		}

		return $expected !== '' && $token !== '' && hash_equals($expected, $token);
	}

	private function json($data, $status = 200) {
		if ($status !== 200) {
			$text = $status === 401 ? 'Unauthorized' : 'Bad Request';
			$this->response->addHeader('HTTP/1.1 ' . (int)$status . ' ' . $text);
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($data));
	}
}
