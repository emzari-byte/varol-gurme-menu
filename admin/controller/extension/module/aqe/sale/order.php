<?php
class ControllerExtensionModuleAqeSaleOrder extends Controller {
	public function __construct($registry) {
		parent::__construct($registry);

		if (!$this->config->get('module_admin_quick_edit_installed') || !$this->config->get('module_admin_quick_edit_status')) {
			$this->response->redirect($this->url->link('sale/order', 'user_token=' . $this->session->data['user_token'], true));
		}
	}

	public function quick_update() {
		$this->load->language('sale/order');
		$this->load->language('extension/module/aqe/sale/order');
		$this->load->language('extension/module/aqe/sale/general');
		$this->load->model('sale/order');

		$alert = array("error" => array(), "success" => array());
		$response = array();

		if ($this->request->server['REQUEST_METHOD'] == 'POST' && $this->validateUpdateData($this->request->post)) {
			list($column, $id) = explode("-", $this->request->post['id']);
			$id = (array)$id;
			$value = $this->request->post['new'];

			if (isset($this->request->post['ids'])) {
				$id = array_unique(array_merge((array)$id, (array)$this->request->post['ids']));
			}

			$results = array('done' => array(), 'failed' => array());

			if (isset($this->request->post['notify'])) {
				$notify = $this->request->post['notify'];
			} else {
				$notify = $this->config->get('module_admin_quick_edit_sale_orders_notify_customer');
			}

			$post_data = array(
				'order_status_id' => $value,
				'notify' => $notify,
				'override' => "0",
				'append' => "0",
				'comment' => ""
			);

			$store_url = $this->request->server['HTTPS'] ? HTTPS_CATALOG : HTTP_CATALOG;

			// API login
			$this->load->model('user/api');

			$api_info = $this->model_user_api->getApi($this->config->get('config_api_id'));

			if ($api_info) {
				$session = new Session($this->config->get('session_engine'), $this->registry);
				
				$session->start();
						
				$this->model_user_api->deleteApiSessionBySessonId($session->getId());
				
				$this->model_user_api->addApiSession($api_info['api_id'], $session->getId(), $this->request->server['REMOTE_ADDR']);
				
				$session->data['api_id'] = $api_info['api_id'];

				$api_token = $session->getId();

				// Force write api_id to database
				$session->close();
			} else {
				$api_token = '';
			}

			if ($column == "status" && $api_token) {
				foreach ((array)$id as $_id) {
					$order_info = $this->model_sale_order->getOrder($_id);

					if (!empty($order_info)) {
						$store_id = $order_info['store_id'];
						$curl = curl_init();

						// Set SSL if required
						if (substr($store_url, 0, 5) == 'https') {
							curl_setopt($curl, CURLOPT_PORT, 443);
						}

						curl_setopt($curl, CURLOPT_HEADER, false);
						curl_setopt($curl, CURLINFO_HEADER_OUT, true);
						curl_setopt($curl, CURLOPT_USERAGENT, $this->request->server['HTTP_USER_AGENT']);
						curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
						curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
						curl_setopt($curl, CURLOPT_FORBID_REUSE, false);
						curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
						curl_setopt($curl, CURLOPT_URL, $store_url . 'index.php?route=api/order/history&api_token=' . $api_token . '&store_id=' . $store_id . '&order_id=' . $_id);
						curl_setopt($curl, CURLOPT_TIMEOUT, 10);
						curl_setopt($curl, CURLOPT_POST, true);
						curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($post_data));

						$json = curl_exec($curl);

						curl_close($curl);

						if ($json) {
							$json = json_decode($json, true);
						}

						if (!empty($json['error'])) {
							$alert['error']['msg'] = $json['error'];
							$results['failed'][] = $_id;
						} else {
							$results['done'][] = $_id;
						}
					} else {
						$results['failed'][] = $_id;
					}
				}
			} else {
				if (!empty($json['error'])) {
					$alert['error'] = array_merge($alert['error'], (array)$json['error']);
				} else if (empty($json['success']) || empty($json['token'])) {
					$alert['error']['api_login'] = $this->language->get('error_login');
				}
			}

			$response['results'] = $results;

			if ($results['done']) {
				$alert['success']['update'] = sprintf($this->language->get('text_success_update'), count($results['done']));
				if ($results['failed']) {
					$alert['error']['update'] = sprintf($this->language->get('error_failed_update'), count($results['failed']));
				}
				$response['success'] = 1;
				if (in_array($column, array('status'))) {
					$this->load->model('localisation/order_status');
					$status = $this->model_localisation_order_status->getOrderStatus($value);
					$response['value'] = $status['name'];
					$response['values']['*'] = $response['value'];
				} else {
					$response['value'] = $value;
					$response['values']['*'] = $response['value'];
				}
			} else {
				$alert['error']['result'] = $this->language->get('error_update');
			}
		} else {
			$response['error'] = $this->error['warning'];
		}

		$response = array_merge($response, array("errors" => $this->error), array("alerts" => $alert));

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($response));
	}

	protected function validateUpdateData(&$data) {
		$this->checkPermission();

		if (!isset($data['id']) || strpos($data['id'], "-") === false) {
			$this->error['warning'] = $this->language->get('error_update');
			return false;
		}

		list($column, $id) = explode("-", $data['id']);

		if (!isset($data['old'])) {
			$this->error['warning'] = $this->language->get('error_update');
		}

		if (!isset($data['new'])) {
			$this->error['warning'] = $this->language->get('error_update');
		}

		if (!$this->error) {
			return true;
		} else {
			return false;
		}
	}

	protected function checkPermission() {
		if (!$this->user->hasPermission('modify', 'sale/order')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}
	}
}
