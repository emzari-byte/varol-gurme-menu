<?php
class ControllerExtensionModuleAkinsoftBridge extends Controller {
	public function pending() {
		try {
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
		} catch (Exception $e) {
			return $this->error($e);
		}
	}

	public function mark() {
		try {
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
		} catch (Exception $e) {
			return $this->error($e);
		}
	}

	public function sent() {
		try {
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
		} catch (Exception $e) {
			return $this->error($e);
		}
	}

	public function paid() {
		try {
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
		} catch (Exception $e) {
			return $this->error($e);
		}
	}

	public function paidExternal() {
		try {
			$this->load->model('extension/module/akinsoft_bridge');

			if (!$this->isAuthorized()) {
				return $this->json(array(
					'success' => false,
					'message' => 'Unauthorized'
				), 401);
			}

			$external_order_no = isset($this->request->post['external_order_no']) ? (string)$this->request->post['external_order_no'] : '';
			$external_fis_id = isset($this->request->post['external_fis_id']) ? (int)$this->request->post['external_fis_id'] : 0;
			$closed_at = isset($this->request->post['closed_at']) ? (string)$this->request->post['closed_at'] : '';
			$message = isset($this->request->post['message']) ? (string)$this->request->post['message'] : '';

			$result = $this->model_extension_module_akinsoft_bridge->markOrderPaidByExternalNo($external_order_no, $external_fis_id, $closed_at, $message);

			if (empty($result['success'])) {
				return $this->json($result, 400);
			}

			return $this->json($result);
		} catch (Exception $e) {
			return $this->error($e);
		}
	}

	public function commands() {
		try {
			$this->load->model('extension/module/akinsoft_bridge');

			if (!$this->isAuthorized()) {
				return $this->json(array(
					'success' => false,
					'message' => 'Unauthorized'
				), 401);
			}

			$limit = isset($this->request->get['limit']) ? (int)$this->request->get['limit'] : 10;

			return $this->json(array(
				'success' => true,
				'commands' => $this->model_extension_module_akinsoft_bridge->getPendingCommands($limit)
			));
		} catch (Exception $e) {
			return $this->error($e);
		}
	}

	public function commandMark() {
		try {
			$this->load->model('extension/module/akinsoft_bridge');

			if (!$this->isAuthorized()) {
				return $this->json(array(
					'success' => false,
					'message' => 'Unauthorized'
				), 401);
			}

			$command_id = isset($this->request->post['command_id']) ? (int)$this->request->post['command_id'] : 0;
			$status = isset($this->request->post['status']) ? (string)$this->request->post['status'] : '';
			$message = isset($this->request->post['message']) ? (string)$this->request->post['message'] : '';

			if (!$this->model_extension_module_akinsoft_bridge->markCommand($command_id, $status, $message)) {
				return $this->json(array(
					'success' => false,
					'message' => 'Invalid command mark request'
				), 400);
			}

			return $this->json(array(
				'success' => true,
				'message' => 'Command updated'
			));
		} catch (Exception $e) {
			return $this->error($e);
		}
	}

	public function syncTables() {
		try {
			$this->load->model('extension/module/akinsoft_bridge');

			if (!$this->isAuthorized()) {
				return $this->json(array(
					'success' => false,
					'message' => 'Unauthorized'
				), 401);
			}

			$tables = $this->decodePostJson('tables');

			if (!is_array($tables)) {
				return $this->json(array(
					'success' => false,
					'message' => 'Invalid tables payload'
				), 400);
			}

			return $this->json($this->model_extension_module_akinsoft_bridge->syncTablesFromBridge($tables));
		} catch (Exception $e) {
			return $this->error($e);
		}
	}

	public function syncPrices() {
		try {
			$this->load->model('extension/module/akinsoft_bridge');

			if (!$this->isAuthorized()) {
				return $this->json(array(
					'success' => false,
					'message' => 'Unauthorized'
				), 401);
			}

			$prices = $this->decodePostJson('prices');

			if (!is_array($prices)) {
				return $this->json(array(
					'success' => false,
					'message' => 'Invalid prices payload'
				), 400);
			}

			return $this->json($this->model_extension_module_akinsoft_bridge->syncPricesFromBridge($prices));
		} catch (Exception $e) {
			return $this->error($e);
		}
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

	private function decodePostJson($key) {
		$json_key = $key . '_json';
		$payload_key = $key . '_payload';
		$json = isset($this->request->post[$json_key]) ? (string)$this->request->post[$json_key] : '';

		if ($json === '' && isset($this->request->post[$payload_key])) {
			$decoded = base64_decode((string)$this->request->post[$payload_key], true);

			if ($decoded !== false) {
				$json = $decoded;
			}
		}

		if ($json === '') {
			return null;
		}

		$data = json_decode($json, true);

		return is_array($data) ? $data : null;
	}

	private function json($data, $status = 200) {
		if ($status !== 200) {
			$text = $status === 401 ? 'Unauthorized' : 'Bad Request';
			$this->response->addHeader('HTTP/1.1 ' . (int)$status . ' ' . $text);
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($data));
	}

	private function error($e) {
		return $this->json(array(
			'success' => false,
			'message' => 'Bridge endpoint error: ' . $e->getMessage()
		), 500);
	}
}
