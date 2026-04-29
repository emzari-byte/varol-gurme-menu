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

		if (!$user) {
			$candidates = array(
				array('username', 'password'),
				array('user', 'pass'),
				array('kullanici', 'sifre'),
				array('WEBUSERNAME', 'WEBPASSWORD'),
				array('webusername', 'webpassword'),
				array('KULLANICI_ADI', 'SIFRE')
			);

			foreach ($candidates as $candidate) {
				$user_key = $candidate[0];
				$pass_key = $candidate[1];

				if (isset($this->request->post[$user_key]) || isset($this->request->get[$user_key])) {
					$user = isset($this->request->post[$user_key]) ? (string)$this->request->post[$user_key] : (string)$this->request->get[$user_key];
					$pass = isset($this->request->post[$pass_key]) ? (string)$this->request->post[$pass_key] : (isset($this->request->get[$pass_key]) ? (string)$this->request->get[$pass_key] : '');
					break;
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
			'message' => 'Yetkisiz erisim'
		)));
	}

	public function index() {
		$command = isset($this->request->request['command']) ? strtolower((string)$this->request->request['command']) : '';

		if ($command !== '') {
			$this->command($command);
			return;
		}

		$this->ping();
	}

	public function test() {
		$this->ping();
	}

	public function login() {
		$this->ping();
	}

	public function command($command = '') {
		if ($command === '') {
			$command = isset($this->request->request['command']) ? strtolower((string)$this->request->request['command']) : '';
		}

		if ($command === 'wlogin') {
			$this->webLogin();
			return;
		}

		if ($command === 'wlogout') {
			$this->plain('OK');
			return;
		}

		if ($command === 'ping' || $command === 'test') {
			$this->ping();
			return;
		}

		$this->plain('Komut Bulunamadi');
	}

	public function ping() {
		if (!$this->checkAuth()) {
			$this->deny();
			return;
		}

		$this->response->addHeader('Content-Type: application/json; charset=utf-8');
		$this->response->setOutput(json_encode(array(
			'success' => true,
			'message' => 'Akinsoft API baglantisi basarili',
			'api'     => 'akinsoft',
			'time'    => date('Y-m-d H:i:s')
		)));
	}

	private function webLogin() {
		$user = isset($this->request->request['username']) ? (string)$this->request->request['username'] : '';
		$pass = isset($this->request->request['password']) ? (string)$this->request->request['password'] : '';

		if ($user !== $this->api_user || $pass !== $this->api_pass) {
			$this->plain('AS42001');
			return;
		}

		$token = hash('sha256', $this->api_user . ':' . $this->api_pass);

		$this->response->addHeader('Content-Type: application/json; charset=utf-8');
		$this->response->setOutput(json_encode(array(
			'success' => true,
			'result'  => true,
			'tpwd'    => $token,
			'tPwd'    => $token,
			'message' => 'OK'
		)));
	}

	private function plain($value) {
		$this->response->addHeader('Content-Type: text/plain; charset=utf-8');
		$this->response->setOutput($value);
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
		)));
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
		$message = 'AKINSOFT tarafindan alindi';

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
			)));
			return;
		}

		$this->load->model('api/akinsoft');

		$ok = $this->model_api_akinsoft->markOrderAsExported($restaurant_order_id, $external_order_no, $message);

		$this->response->addHeader('Content-Type: application/json; charset=utf-8');
		$this->response->setOutput(json_encode(array(
			'success' => (bool)$ok
		)));
	}
}
