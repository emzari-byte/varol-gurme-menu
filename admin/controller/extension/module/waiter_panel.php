<?php
class ControllerExtensionModuleWaiterPanel extends Controller {
	public function index() {
		if (!$this->user->hasPermission('access', 'extension/module/waiter_panel')) {
			$this->response->redirect($this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true));
			return;
		}

		if (!$this->isRestaurantSettingEnabled('restaurant_waiter_panel', 1)) {
			$this->response->redirect($this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true));
			return;
		}

		$this->document->setTitle('Garson Paneli');
        $this->document->addStyle('/menu/css/waiter-panel.css?v=5');
		$this->load->model('extension/module/waiter_panel');

		$current_user_id = (int)$this->user->getId();

		if ($this->isAkinsoftDirectFirebirdMode()) {
			$this->load->model('extension/module/restaurant_settings');
			$this->load->model('extension/module/akinsoft');
			$this->model_extension_module_akinsoft->syncClosedRestaurantOrders(
				$this->model_extension_module_restaurant_settings->getSettings()
			);
		}

		$data['breadcrumbs'] = array(
			array(
				'text' => 'Ana Sayfa',
				'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
			),
			array(
				'text' => 'Garson Paneli',
				'href' => $this->url->link('extension/module/waiter_panel', 'user_token=' . $this->session->data['user_token'], true)
			)
		);

		$data['tables'] = $this->model_extension_module_waiter_panel->getTables($current_user_id);
		$summary = $this->model_extension_module_waiter_panel->getSummary($current_user_id);

		$data['total_tables']  = $summary['total_tables'];
		$data['active_tables'] = $summary['active_tables'];
		$data['new_calls']     = $summary['new_calls'];
		$data['paid_count']    = $summary['paid_count'];
		$data['waiter_status'] = $this->model_extension_module_waiter_panel->getCurrentWaiterStatus($current_user_id);
		$data['admin_notification_controls'] = empty($data['waiter_status']['is_waiter']) ? 1 : 0;
		$data['active_waiters'] = $this->model_extension_module_waiter_panel->getActiveWaiterDelegates($current_user_id);

		$data['user_token']       = $this->session->data['user_token'];
		$data['kitchen_panel_enabled'] = $this->isRestaurantSettingEnabled('restaurant_kitchen_panel', 1) ? 1 : 0;
		$data['akinsoft_enabled'] = $this->isRestaurantSettingEnabled('restaurant_akinsoft_enabled', 0) ? 1 : 0;
		$data['cashier_panel_enabled'] = ($this->isRestaurantSettingEnabled('restaurant_cashier_panel', 1) && !$this->isRestaurantSettingEnabled('restaurant_akinsoft_enabled', 0)) ? 1 : 0;
		$data['refresh_url']      = $this->url->link('extension/module/waiter_panel/refresh', 'user_token=' . $this->session->data['user_token'], true);
		$data['orders_url']       = $this->url->link('extension/module/waiter_panel/getTableOrders', 'user_token=' . $this->session->data['user_token'], true);
		$data['order_status_url'] = $this->url->link('extension/module/waiter_panel/updateOrderStatus', 'user_token=' . $this->session->data['user_token'], true);
		$data['remove_order_product_url'] = $this->url->link('extension/module/waiter_panel/removeOrderProduct', 'user_token=' . $this->session->data['user_token'], true);
		$data['table_note_url']   = $this->url->link('extension/module/waiter_panel/saveTableNote', 'user_token=' . $this->session->data['user_token'], true);
		$data['waiter_call_ack_url'] = $this->url->link('extension/module/waiter_panel/acknowledgeWaiterCall', 'user_token=' . $this->session->data['user_token'], true);
		$data['bill_request_ack_url'] = $this->url->link('extension/module/waiter_panel/acknowledgeBillRequest', 'user_token=' . $this->session->data['user_token'], true);
		$data['waiter_break_url'] = $this->url->link('extension/module/waiter_panel/updateBreakStatus', 'user_token=' . $this->session->data['user_token'], true);
        $data['product_search_url'] = $this->url->link('extension/module/waiter_panel/searchProducts', 'user_token=' . $this->session->data['user_token'], true);
		$data['header']      = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer']      = $this->load->controller('common/footer');
        $data['manual_order_url']=$this->url->link(
'extension/module/waiter_panel/addManualOrder',
'user_token='.$this->session->data['user_token'],
true
);
		$this->response->setOutput($this->load->view('extension/module/waiter_panel', $data));
	}

	public function refresh() {
		$json = array();

		if (!$this->user->hasPermission('access', 'extension/module/waiter_panel') || !$this->isRestaurantSettingEnabled('restaurant_waiter_panel', 1)) {
			$json['error'] = 'Yetki yok';
			$json['tables'] = array();
			$json['summary'] = array(
				'total_tables'  => 0,
				'active_tables' => 0,
				'new_calls'     => 0,
				'paid_count'    => 0
			);
		} else {
			$this->load->model('extension/module/waiter_panel');

			$current_user_id = (int)$this->user->getId();

			if ($this->isAkinsoftDirectFirebirdMode()) {
				$this->load->model('extension/module/restaurant_settings');
				$this->load->model('extension/module/akinsoft');
				$this->model_extension_module_akinsoft->syncClosedRestaurantOrders(
					$this->model_extension_module_restaurant_settings->getSettings()
				);
			}

			$json = array(
				'tables'  => $this->model_extension_module_waiter_panel->getTables($current_user_id),
				'summary' => $this->model_extension_module_waiter_panel->getSummary($current_user_id)
			);
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function updateBreakStatus() {
		$json = array();

		if (!$this->user->hasPermission('access', 'extension/module/waiter_panel') || !$this->isRestaurantSettingEnabled('restaurant_waiter_panel', 1)) {
			$json['success'] = false;
			$json['message'] = 'Yetkiniz yok!';
		} else {
			$this->load->model('extension/module/waiter_panel');

			$user_id = (int)$this->user->getId();
			$on_break = isset($this->request->post['on_break']) ? (int)$this->request->post['on_break'] : 0;
			$delegate_user_id = isset($this->request->post['delegate_user_id']) ? (int)$this->request->post['delegate_user_id'] : 0;

			$ok = $this->model_extension_module_waiter_panel->setWaiterBreak($user_id, $on_break, $delegate_user_id);

			$json['success'] = (bool)$ok;
			$json['message'] = $ok
				? ($on_break ? 'Mola baslatildi. Masalar secilen garsona yonlendirildi.' : 'Mola kapatildi. Masalar tekrar size yonlendirildi.')
				: 'Mola durumu guncellenemedi.';
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function getTableOrders() {
	$json = array();

	if (!$this->user->hasPermission('access', 'extension/module/waiter_panel')) {
		$json['error'] = 'Yetki yok';
		$json['orders'] = array();
	} else {
		$table_id = isset($this->request->get['table_id']) ? (int)$this->request->get['table_id'] : 0;

		$this->load->model('extension/module/waiter_panel');

		$current_user_id = (int)$this->user->getId();

		if (!$table_id) {
			$json['error'] = 'Geçersiz masa.';
			$json['orders'] = array();
		} elseif (!$this->model_extension_module_waiter_panel->canAccessTable($table_id, $current_user_id)) {
			$json['error'] = 'Bu masaya erişim yetkiniz yok.';
			$json['orders'] = array();
		} else {
			$json['orders'] = $this->model_extension_module_waiter_panel->getTableOrders($table_id);
		}
	}

	$this->response->addHeader('Content-Type: application/json');
	$this->response->setOutput(json_encode($json));
}

	public function acknowledgeWaiterCall() {
		$json = array();

		if (!$this->user->hasPermission('modify', 'extension/module/waiter_panel') || !$this->isRestaurantSettingEnabled('restaurant_waiter_panel', 1)) {
			$json['success'] = false;
			$json['message'] = 'Yetkiniz yok!';
		} else {
			$table_id = isset($this->request->post['table_id']) ? (int)$this->request->post['table_id'] : 0;

			$this->load->model('extension/module/waiter_panel');

			$current_user_id = (int)$this->user->getId();

			if (!$table_id) {
				$json['success'] = false;
				$json['message'] = 'Masa bilgisi eksik.';
			} elseif (!$this->model_extension_module_waiter_panel->canAccessTable($table_id, $current_user_id)) {
				$json['success'] = false;
				$json['message'] = 'Bu masaya erişim yetkiniz yok!';
			} else {
				$ok = $this->model_extension_module_waiter_panel->acknowledgeWaiterCall($table_id, $current_user_id);
				$json['success'] = (bool)$ok;
				$json['message'] = $ok ? 'Çağrı görüldü. Müşteriye bilgi verildi.' : 'Aktif garson çağrısı bulunamadı.';
			}
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function acknowledgeBillRequest() {
		$json = array();

		if (!$this->user->hasPermission('modify', 'extension/module/waiter_panel') || !$this->isRestaurantSettingEnabled('restaurant_waiter_panel', 1)) {
			$json['success'] = false;
			$json['message'] = 'Yetkiniz yok!';
		} else {
			$table_id = isset($this->request->post['table_id']) ? (int)$this->request->post['table_id'] : 0;

			$this->load->model('extension/module/waiter_panel');

			$current_user_id = (int)$this->user->getId();

			if (!$table_id) {
				$json['success'] = false;
				$json['message'] = 'Masa bilgisi eksik.';
			} elseif (!$this->model_extension_module_waiter_panel->canAccessTable($table_id, $current_user_id)) {
				$json['success'] = false;
				$json['message'] = 'Bu masaya erişim yetkiniz yok!';
			} else {
				$ok = $this->model_extension_module_waiter_panel->acknowledgeBillRequest($table_id, $current_user_id);
				$json['success'] = (bool)$ok;
				$json['message'] = $ok ? 'Hesap talebi görüldü. Müşteriye bilgi verildi.' : 'Aktif hesap talebi bulunamadı.';
			}
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function updateOrderStatus() {
		$json = array();

		if (!$this->user->hasPermission('modify', 'extension/module/waiter_panel') || !$this->isRestaurantSettingEnabled('restaurant_waiter_panel', 1)) {
			$json['success'] = false;
			$json['message'] = 'Yetkiniz yok!';
		} else {
			$restaurant_order_id = isset($this->request->post['restaurant_order_id']) ? (int)$this->request->post['restaurant_order_id'] : 0;
			$service_status = isset($this->request->post['service_status']) ? trim($this->request->post['service_status']) : '';

			$allowed = array(
	'waiting_order',
	'in_kitchen',
	'ready_for_service',
	'out_for_service',
	'served',
	'paid',
	'cancelled'
);

			if (
				$service_status === 'in_kitchen'
				&& !$this->isRestaurantSettingEnabled('restaurant_kitchen_panel', 1)
				&& !$this->isRestaurantSettingEnabled('restaurant_akinsoft_enabled', 0)
			) {
				$json['success'] = false;
				$json['message'] = 'Mutfak paneli kapalı.';
			} elseif (
				$service_status === 'paid'
				&& $this->isRestaurantSettingEnabled('restaurant_cashier_panel', 1)
				&& !$this->isRestaurantSettingEnabled('restaurant_akinsoft_enabled', 0)
			) {
				$json['success'] = false;
				$json['message'] = 'Kasa paneli aktif. Odeme islemi kasa panelinden yapilmali.';
			} elseif ($restaurant_order_id && in_array($service_status, $allowed)) {
				$this->load->model('extension/module/waiter_panel');

				$current_user_id = (int)$this->user->getId();

				$table_id = $this->model_extension_module_waiter_panel->getOrderTableId($restaurant_order_id);

				if (!$this->model_extension_module_waiter_panel->canAccessTable($table_id, $current_user_id)) {
					$json['success'] = false;
					$json['message'] = 'Bu siparişe erişim yetkiniz yok!';
				} else {
					$ok = $this->model_extension_module_waiter_panel->updateRestaurantOrderStatus(
						$restaurant_order_id,
						$service_status,
						$current_user_id
					);

					$json['success'] = (bool)$ok;
					$json['message'] = $ok ? 'Sipariş durumu güncellendi.' : 'Sipariş güncellenemedi.';

					if ($ok && $service_status === 'in_kitchen' && $this->isRestaurantSettingEnabled('restaurant_akinsoft_enabled', 0)) {
						$this->load->model('extension/module/restaurant_settings');

						$settings = $this->model_extension_module_restaurant_settings->getSettings();
						$akinsoft_mode = !empty($settings['restaurant_akinsoft_mode']) ? $settings['restaurant_akinsoft_mode'] : 'local_firebird';

						if ($akinsoft_mode === 'local_firebird' && extension_loaded('pdo_firebird')) {
							$this->load->model('extension/module/akinsoft');

							$export = $this->model_extension_module_akinsoft->exportRestaurantOrder(
								$settings,
								$restaurant_order_id
							);
						} else {
							$export = array(
								'success' => true,
								'mode' => $akinsoft_mode,
								'message' => 'AKINSOFT Bridge Agent kuyruguna alindi.'
							);
						}

						$json['akinsoft'] = $export;

						if (!empty($export['success'])) {
							$json['message'] = 'Sipariş mutfağa gönderildi. ' . $export['message'];
						} else {
							$json['success'] = false;
							$json['message'] = $export['message'];
						}
					}
				}
			} else {
				$json['success'] = false;
				$json['message'] = 'Geçersiz veri!';
			}
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function removeOrderProduct() {
		$json = array();

		if (!$this->user->hasPermission('modify', 'extension/module/waiter_panel') || !$this->isRestaurantSettingEnabled('restaurant_waiter_panel', 1)) {
			$json['success'] = false;
			$json['message'] = 'Yetkiniz yok!';
		} else {
			$restaurant_order_product_id = isset($this->request->post['restaurant_order_product_id']) ? (int)$this->request->post['restaurant_order_product_id'] : 0;

			$this->load->model('extension/module/waiter_panel');

			$current_user_id = (int)$this->user->getId();
			$table_id = $this->model_extension_module_waiter_panel->getOrderProductTableId($restaurant_order_product_id);

			if (!$restaurant_order_product_id) {
				$json['success'] = false;
				$json['message'] = 'Ürün satırı eksik.';
			} elseif (!$this->model_extension_module_waiter_panel->canAccessTable($table_id, $current_user_id)) {
				$json['success'] = false;
				$json['message'] = 'Bu siparişe erişim yetkiniz yok!';
			} else {
				$ok = $this->model_extension_module_waiter_panel->removeOrderProduct($restaurant_order_product_id, $current_user_id);
				$json['success'] = (bool)$ok;
				$json['message'] = $ok ? 'Ürün siparişten çıkarıldı.' : 'Ürün çıkarılamadı. Sipariş mutfağa gönderilmiş olabilir.';
			}
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function saveTableNote() {
		$json = array();

		if (!$this->user->hasPermission('modify', 'extension/module/waiter_panel')) {
			$json['success'] = false;
			$json['message'] = 'Yetkiniz yok!';
		} else {
			$table_id = isset($this->request->post['table_id']) ? (int)$this->request->post['table_id'] : 0;
			$note = isset($this->request->post['note']) ? trim($this->request->post['note']) : '';

			$this->load->model('extension/module/waiter_panel');

			$current_user_id = (int)$this->user->getId();

			if ($table_id) {
				if (!$this->model_extension_module_waiter_panel->canAccessTable($table_id, $current_user_id)) {
					$json['success'] = false;
					$json['message'] = 'Bu masaya erişim yetkiniz yok!';
				} else {
					$this->model_extension_module_waiter_panel->saveTableNote($table_id, $note);

					$json['success'] = true;
					$json['message'] = 'Masa notu kaydedildi.';
				}
			} else {
				$json['success'] = false;
				$json['message'] = 'Masa bilgisi eksik!';
			}
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}
	public function searchProducts() {
	$json = array();

	if (!$this->user->hasPermission('access', 'extension/module/waiter_panel')) {
		$json['error'] = 'Yetki yok';
		$json['products'] = array();
	} else {
		$this->load->model('extension/module/waiter_panel');

		$keyword = isset($this->request->get['keyword']) ? trim($this->request->get['keyword']) : '';

		if (utf8_strlen($keyword) < 2) {
			$json['products'] = array();
		} else {
			$json['products'] = $this->model_extension_module_waiter_panel->searchProducts($keyword);
		}
	}

	$this->response->addHeader('Content-Type: application/json');
	$this->response->setOutput(json_encode($json));
}
public function addManualOrder() {
	$json = array();

	if (!$this->user->hasPermission('modify', 'extension/module/waiter_panel')) {
		$json['success'] = false;
		$json['message'] = 'Yetki yok';
	} else {
		$this->load->model('extension/module/waiter_panel');

		$table_id = isset($this->request->post['table_id']) ? (int)$this->request->post['table_id'] : 0;
		$products = array();

		if (isset($this->request->post['products']) && is_array($this->request->post['products'])) {
			$products = $this->request->post['products'];
		}

		$current_user_id = (int)$this->user->getId();
		$order_id = 0;

		if (!$table_id) {
			$json['success'] = false;
			$json['message'] = 'Masa bilgisi eksik';
		} elseif (!$this->model_extension_module_waiter_panel->canAccessTable($table_id, $current_user_id)) {
			$json['success'] = false;
			$json['message'] = 'Bu masaya erişim yetkiniz yok!';
		} else {
			$order_id = $this->model_extension_module_waiter_panel->createManualOrder($table_id, $products, $current_user_id);
		}

		if ($order_id) {
			$json['success'] = true;
			$json['message'] = 'İlave sipariş mutfağa gönderildi.';
			$json['order_id'] = $order_id;
		} elseif (empty($json['message'])) {
			$json['success'] = false;
			$json['message'] = 'Sipariş oluşturulamadı.';
		}
	}

	$this->response->addHeader('Content-Type: application/json');
	$this->response->setOutput(json_encode($json));
}

	private function isRestaurantSettingEnabled($key, $default = 1) {
		$table = DB_PREFIX . 'ayarlar';

		$exists = $this->db->query("SHOW TABLES LIKE '" . $this->db->escape($table) . "'");

		if (!$exists->num_rows) {
			return (bool)$default;
		}

		$query = $this->db->query("SELECT ayar_value FROM `" . $table . "`
			WHERE ayar_key = '" . $this->db->escape($key) . "'
			LIMIT 1");

		if (!$query->num_rows || $query->row['ayar_value'] === '') {
			return (bool)$default;
		}

		return (int)$query->row['ayar_value'] === 1;
	}

	private function getRestaurantSettingValue($key, $default = '') {
		$table = DB_PREFIX . 'ayarlar';

		$exists = $this->db->query("SHOW TABLES LIKE '" . $this->db->escape($table) . "'");

		if (!$exists->num_rows) {
			return $default;
		}

		$query = $this->db->query("SELECT ayar_value FROM `" . $table . "`
			WHERE ayar_key = '" . $this->db->escape($key) . "'
			LIMIT 1");

		if (!$query->num_rows || $query->row['ayar_value'] === '') {
			return $default;
		}

		return $query->row['ayar_value'];
	}

	private function isAkinsoftDirectFirebirdMode() {
		return $this->isRestaurantSettingEnabled('restaurant_akinsoft_enabled', 0)
			&& $this->getRestaurantSettingValue('restaurant_akinsoft_mode', 'local_firebird') === 'local_firebird'
			&& extension_loaded('pdo_firebird');
	}
}
