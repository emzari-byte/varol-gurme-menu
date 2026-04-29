<?php
class ControllerApiAkinsoft extends Controller {
	private $api_user = 'akinsoft_api';
	private $api_pass = 'GucluSifreBuraya123';

	private function checkAuth() {
		$user = isset($this->request->server['PHP_AUTH_USER']) ? $this->request->server['PHP_AUTH_USER'] : '';
		$pass = isset($this->request->server['PHP_AUTH_PW']) ? $this->request->server['PHP_AUTH_PW'] : '';

		if (!$user && isset($this->request->server['HTTP_AUTHORIZATION'])) {
			$auth = $this->request->server['HTTP_AUTHORIZATION'];

			if (stripos($auth, 'basic ') === 0) {
				$decoded = base64_decode(substr($auth, 6));

				if (strpos($decoded, ':') !== false) {
					list($user, $pass) = explode(':', $decoded, 2);
				}
			}
		}

		return ($user === $this->api_user && $pass === $this->api_pass);
	}

	private function deny() {
		$this->response->addHeader('WWW-Authenticate: Basic realm="AKINSOFT API"');
		$this->response->addHeader('HTTP/1.0 401 Unauthorized');
		$this->response->addHeader('Content-Type: application/json; charset=utf-8');
		$this->response->setOutput(json_encode(array(
			'success' => false,
			'message' => 'Yetkisiz erişim'
		), JSON_UNESCAPED_UNICODE));
	}

	public function ping() {
		if (!$this->checkAuth()) {
			$this->deny();
			return;
		}

		$this->response->addHeader('Content-Type: application/json; charset=utf-8');
		$this->response->setOutput(json_encode(array(
			'success' => true,
			'message' => 'Bağlantı başarılı',
			'time'    => date('Y-m-d H:i:s')
		), JSON_UNESCAPED_UNICODE));
	}

	public function pendingOrders() {
		if (!$this->checkAuth()) {
			$this->deny();
			return;
		}

		$this->load->model('api/akinsoft');

		$orders = $this->model_api_akinsoft->getOrdersForExport();

		$this->response->addHeader('Content-Type: application/json; charset=utf-8');
		$this->response->setOutput(json_encode(array(
			'success' => true,
			'count'   => count($orders),
			'orders'  => $orders
		), JSON_UNESCAPED_UNICODE));
	}

	public function markSent() {
		if (!$this->checkAuth()) {
			$this->deny();
			return;
		}

		$raw = file_get_contents('php://input');
		$data = json_decode($raw, true);

		$restaurant_order_id = 0;
		$external_order_no = '';
		$message = 'AKINSOFT tarafından alındı';

		if (is_array($data)) {
			$restaurant_order_id = isset($data['restaurant_order_id']) ? (int)$data['restaurant_order_id'] : 0;
			$external_order_no   = isset($data['external_order_no']) ? trim($data['external_order_no']) : '';
			$message             = isset($data['message']) ? trim($data['message']) : $message;
		} else {
			$restaurant_order_id = isset($this->request->post['restaurant_order_id']) ? (int)$this->request->post['restaurant_order_id'] : 0;
			$external_order_no   = isset($this->request->post['external_order_no']) ? trim($this->request->post['external_order_no']) : '';
			$message             = isset($this->request->post['message']) ? trim($this->request->post['message']) : $message;
		}

		if (!$restaurant_order_id) {
			$this->response->addHeader('Content-Type: application/json; charset=utf-8');
			$this->response->setOutput(json_encode(array(
				'success' => false,
				'message' => 'restaurant_order_id gerekli'
			), JSON_UNESCAPED_UNICODE));
			return;
		}

		$this->load->model('api/akinsoft');

		$ok = $this->model_api_akinsoft->markOrderAsExported($restaurant_order_id, $external_order_no, $message);

		$this->response->addHeader('Content-Type: application/json; charset=utf-8');
		$this->response->setOutput(json_encode(array(
			'success' => (bool)$ok
		), JSON_UNESCAPED_UNICODE));
	}
}