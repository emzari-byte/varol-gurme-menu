<?php
class ModelCommonMenuOrder extends Model {
	private function ensureTableOrderColumn() {
		$query = $this->db->query("SHOW COLUMNS FROM `" . DB_PREFIX . "restaurant_table` LIKE 'qr_order_enabled'");

		if (!$query->num_rows) {
			$this->db->query("ALTER TABLE `" . DB_PREFIX . "restaurant_table` ADD `qr_order_enabled` TINYINT(1) NOT NULL DEFAULT '1' AFTER `qr_token`");
		}
	}

	public function ensureTableSessionFromQr($qr_token) {
		$qr_token = trim((string)$qr_token);

		if ($qr_token === '') {
			return false;
		}

		$this->ensureTableOrderColumn();

		$table_query = $this->db->query("SELECT *
			FROM `" . DB_PREFIX . "restaurant_table`
			WHERE qr_token = '" . $this->db->escape($qr_token) . "'
			AND status = '1'
			LIMIT 1");

		if (!$table_query->num_rows) {
			unset($this->session->data['menu_qr_token']);
			unset($this->session->data['menu_table_id']);
			unset($this->session->data['menu_table_no']);
			unset($this->session->data['menu_table_name']);
			unset($this->session->data['table_session_token']);
			unset($this->session->data['menu_table_qr_order_enabled']);
			return false;
		}

		$table_id = (int)$table_query->row['table_id'];
		$session_token = $this->getTableSessionToken($table_id);

		$this->session->data['menu_qr_token'] = $qr_token;
		$this->session->data['menu_table_id'] = $table_id;
		$this->session->data['menu_table_no'] = (int)$table_query->row['table_no'];
		$this->session->data['menu_table_name'] = $table_query->row['name'];
		$this->session->data['table_session_token'] = $session_token;
		$this->session->data['menu_table_qr_order_enabled'] = isset($table_query->row['qr_order_enabled']) ? (int)$table_query->row['qr_order_enabled'] : 1;

		if ((int)$this->session->data['menu_table_qr_order_enabled'] !== 1) {
			$this->clearCart();
		}

		return true;
	}

	public function canOrder() {
		if (!$this->isQrOrderMenuEnabled()) {
			return false;
		}

		if (
			!$this->isWaiterPanelEnabled()
			&& !$this->isKitchenPanelEnabled()
			&& !$this->isAkinsoftEnabled()
			&& !$this->isCashierPanelEnabled()
		) {
			return false;
		}

		if (!$this->isCurrentTableQrOrderEnabled()) {
			return false;
		}

		return $this->hasValidTableSession();
	}

	public function canTrackOrder() {
		return $this->hasValidTableSession();
	}

	private function hasValidTableSession() {
		if (
			empty($this->session->data['menu_qr_token']) ||
			empty($this->session->data['menu_table_id']) ||
			empty($this->session->data['table_session_token'])
		) {
			return false;
		}

		$table_id = (int)$this->session->data['menu_table_id'];

		$query = $this->db->query("
			SELECT active_session_token
			FROM `" . DB_PREFIX . "restaurant_table_status`
			WHERE table_id = '" . $table_id . "'
			LIMIT 1
		");

		if (!$query->num_rows || empty($query->row['active_session_token'])) {
			return false;
		}

		return hash_equals(
			(string)$query->row['active_session_token'],
			(string)$this->session->data['table_session_token']
		);
	}

	private function isCurrentTableQrOrderEnabled() {
		$table_id = !empty($this->session->data['menu_table_id'])
			? (int)$this->session->data['menu_table_id']
			: 0;

		if (!$table_id) {
			return false;
		}

		$this->ensureTableOrderColumn();

		$query = $this->db->query("SELECT qr_order_enabled
			FROM `" . DB_PREFIX . "restaurant_table`
			WHERE table_id = '" . $table_id . "'
			AND status = '1'
			LIMIT 1");

		if (!$query->num_rows) {
			return false;
		}

		$this->session->data['menu_table_qr_order_enabled'] = (int)$query->row['qr_order_enabled'];

		return ((int)$query->row['qr_order_enabled'] === 1);
	}

	private function getTableSessionToken($table_id) {
		$table_id = (int)$table_id;

		if (!$table_id) {
			return '';
		}

		$query = $this->db->query("
			SELECT active_session_token
			FROM `" . DB_PREFIX . "restaurant_table_status`
			WHERE table_id = '" . $table_id . "'
			LIMIT 1
		");

		if ($query->num_rows && !empty($query->row['active_session_token'])) {
			return $query->row['active_session_token'];
		}

		$session_token = bin2hex(random_bytes(16));

		if ($query->num_rows) {
			$this->db->query("
				UPDATE `" . DB_PREFIX . "restaurant_table_status`
				SET active_session_token = '" . $this->db->escape($session_token) . "',
					date_modified = NOW()
				WHERE table_id = '" . $table_id . "'
			");
		} else {
			$this->db->query("
				INSERT INTO `" . DB_PREFIX . "restaurant_table_status`
				SET table_id = '" . $table_id . "',
					service_status = 'empty',
					active_order_count = '0',
					total_amount = '0.0000',
					active_session_token = '" . $this->db->escape($session_token) . "',
					date_modified = NOW()
			");
		}

		return $session_token;
	}

	public function getCart() {
		if (!isset($this->session->data['menu_order_cart']) || !is_array($this->session->data['menu_order_cart'])) {
			$this->session->data['menu_order_cart'] = array();
		}

		return $this->session->data['menu_order_cart'];
	}

	public function saveCart(array $cart) {
		$this->session->data['menu_order_cart'] = $cart;
	}

	public function clearCart() {
		$this->session->data['menu_order_cart'] = array();
	}

	public function addItem($product_id, $quantity = 1) {
		$product_id = (int)$product_id;
		$quantity   = (int)$quantity;

		if (!$this->canOrder() || $product_id <= 0 || $quantity <= 0) {
			return false;
		}

		$product = $this->getProductInfo($product_id);

		if (!$product) {
			return false;
		}

		$cart = $this->getCart();

		if (isset($cart[$product_id])) {
			$cart[$product_id]['quantity'] += $quantity;
		} else {
			$cart[$product_id] = array(
				'product_id' => $product_id,
				'name'       => $product['name'],
				'price_raw'  => (float)$product['price'],
				'price'      => $this->formatPrice($product['price'], $product['tax_class_id']),
				'image'      => $product['image'] ? HTTPS_SERVER . 'image/' . $product['image'] : '',
				'quantity'   => $quantity
			);
		}

		$this->saveCart($cart);

		return true;
	}

	public function updateItem($product_id, $quantity) {
		$product_id = (int)$product_id;
		$quantity   = (int)$quantity;

		if (!$this->canOrder()) {
			return false;
		}

		$cart = $this->getCart();

		if (!isset($cart[$product_id])) {
			return false;
		}

		if ($quantity <= 0) {
			unset($cart[$product_id]);
		} else {
			$cart[$product_id]['quantity'] = $quantity;
		}

		$this->saveCart($cart);

		return true;
	}

	public function removeItem($product_id) {
		$product_id = (int)$product_id;

		if (!$this->canOrder()) {
			return false;
		}

		$cart = $this->getCart();

		if (isset($cart[$product_id])) {
			unset($cart[$product_id]);
			$this->saveCart($cart);
		}

		return true;
	}

	public function getCartSummary() {
		if (!empty($this->session->data['menu_table_id']) && !$this->isCurrentTableQrOrderEnabled()) {
			$this->clearCart();
		}

		$cart = $this->getCart();

		$items = array();
		$total_qty = 0;
		$total_raw = 0.0;

		foreach ($cart as $row) {
			$row_total_raw = (float)$row['price_raw'] * (int)$row['quantity'];

			$items[] = array(
				'product_id' => (int)$row['product_id'],
				'name'       => $row['name'],
				'image'      => $row['image'],
				'price'      => $row['price'],
				'price_raw'  => (float)$row['price_raw'],
				'quantity'   => (int)$row['quantity'],
				'total'      => $this->currency->format(
					$this->tax->calculate($row_total_raw, 0, $this->config->get('config_tax')),
					$this->session->data['currency']
				),
				'total_raw'  => $row_total_raw
			);

			$total_qty += (int)$row['quantity'];
			$total_raw += $row_total_raw;
		}

		return array(
			'can_order'  => $this->canOrder(),
			'can_track_order' => $this->canTrackOrder(),
			'table_id'   => !empty($this->session->data['menu_table_id']) ? (int)$this->session->data['menu_table_id'] : 0,
			'table_no'   => !empty($this->session->data['menu_table_no']) ? (int)$this->session->data['menu_table_no'] : 0,
			'table_name' => !empty($this->session->data['menu_table_name']) ? $this->session->data['menu_table_name'] : '',
			'items'      => $items,
			'total_qty'  => $total_qty,
			'total_raw'  => $total_raw,
			'total'      => $this->currency->format(
				$this->tax->calculate($total_raw, 0, $this->config->get('config_tax')),
				$this->session->data['currency']
			)
		);
	}

	public function hasOpenOrders() {
		$table_id = !empty($this->session->data['menu_table_id'])
			? (int)$this->session->data['menu_table_id']
			: 0;

		if (!$table_id) {
			return false;
		}

		$q = $this->db->query("
			SELECT COUNT(*) AS total
			FROM `" . DB_PREFIX . "restaurant_order`
			WHERE table_id = '" . $table_id . "'
			AND service_status IN ('waiting_order','in_kitchen','ready_for_service','out_for_service','served')
		");

		return ((int)$q->row['total'] > 0);
	}

	public function getActiveOrders() {
		$table_id = !empty($this->session->data['menu_table_id'])
			? (int)$this->session->data['menu_table_id']
			: 0;

		$data = array();

		if (!$table_id) {
			return $data;
		}

		$orders = $this->db->query("
			SELECT *
			FROM `" . DB_PREFIX . "restaurant_order`
			WHERE table_id = '" . $table_id . "'
			AND service_status IN ('waiting_order','in_kitchen','ready_for_service','out_for_service','served')
			ORDER BY restaurant_order_id ASC
		");

		foreach ($orders->rows as $order) {
			$items = array();

			$products = $this->db->query("
				SELECT rop.*, IFNULL(pd.tag, '') AS prep_tag
				FROM `" . DB_PREFIX . "restaurant_order_product` rop
				LEFT JOIN `" . DB_PREFIX . "product_description` pd
					ON (rop.product_id = pd.product_id AND pd.language_id = '" . (int)$this->config->get('config_language_id') . "')
				WHERE rop.restaurant_order_id = '" . (int)$order['restaurant_order_id'] . "'
			");

			$prep_minutes = 0;

			foreach ($products->rows as $p) {
				$item_prep_minutes = $this->parsePrepMinutes($p['prep_tag']);
				$prep_minutes = max($prep_minutes, $item_prep_minutes);

				$items[] = array(
					'name'  => $p['name'],
					'qty'   => (int)$p['quantity'],
					'prep_tag' => $p['prep_tag'],
					'prep_minutes' => $item_prep_minutes,
					'total' => $this->currency->format(
						(float)$p['total'],
						$this->session->data['currency']
					)
				);
			}

			$data[] = array(
	'order_id'  => (int)$order['restaurant_order_id'],
	'time'      => date('H:i', strtotime($order['date_added'])),
	'status'    => $order['service_status'],
	'prep_minutes' => $prep_minutes,
	'prep_label' => $prep_minutes ? $prep_minutes . ' dk' : '',
	'prep_deadline_ts' => $this->getPrepDeadlineTs($order, $prep_minutes),
	'total_raw' => (float)$order['total_amount'],
	'total'     => $this->currency->format(
		(float)$order['total_amount'],
		$this->session->data['currency']
	),
	'items'     => $items
);
		}

		return $data;
	}

	public function canRequestBill() {
		$table_id = !empty($this->session->data['menu_table_id'])
			? (int)$this->session->data['menu_table_id']
			: 0;

		if (!$table_id) {
			return false;
		}

		$served = $this->db->query("
			SELECT COUNT(*) AS total
			FROM `" . DB_PREFIX . "restaurant_order`
			WHERE table_id = '" . $table_id . "'
			AND service_status = 'served'
			AND is_paid = '0'
		");

		if ((int)$served->row['total'] <= 0) {
			return false;
		}

		$table_status = $this->db->query("
			SELECT service_status
			FROM `" . DB_PREFIX . "restaurant_table_status`
			WHERE table_id = '" . $table_id . "'
			LIMIT 1
		");

		if ($table_status->num_rows) {
			$blocked = array('paid', 'closed', 'empty');
			if (in_array((string)$table_status->row['service_status'], $blocked, true)) {
				return false;
			}
		}

		return true;
	}
public function requestWaiter() {
	if (!$this->canTrackOrder()) {
		return array(
			'success' => false,
			'message' => 'Garson çağırma özelliği sadece masadaki QR menü üzerinden kullanılabilir.'
		);
	}

	$table_id = !empty($this->session->data['menu_table_id']) ? (int)$this->session->data['menu_table_id'] : 0;
	$table_no = !empty($this->session->data['menu_table_no']) ? (string)$this->session->data['menu_table_no'] : '';

	if (!$table_id) {
		return array(
			'success' => false,
			'message' => 'Masa bilgisi bulunamadı.'
		);
	}

	$pending = $this->getPendingWaiterCall();

	if (!empty($pending['call_id'])) {
		return array(
			'success' => true,
			'message' => $pending['status'] === 'seen'
				? 'Garson çağrınızı gördü, en kısa sürede yanınıza gelecek.'
				: 'Garson çağrınız alındı. Lütfen kısa bir süre bekleyiniz.',
			'state'   => $this->getCustomerOrderState()
		);
	}

	$this->db->query("INSERT INTO `" . DB_PREFIX . "restaurant_call`
		SET table_id = '" . (int)$table_id . "',
			call_type = 'waiter_call',
			status = 'new',
			note = '" . $this->db->escape('Masa ' . $table_no . ' garson çağırıyor.') . "',
			date_added = NOW(),
			date_modified = NOW()");

	$call_id = (int)$this->db->getLastId();

	$this->db->query("INSERT INTO `" . DB_PREFIX . "restaurant_order_history`
		SET restaurant_order_id = '0',
			old_status = NULL,
			new_status = 'waiter_call',
			user_id = '0',
			comment = 'Müşteri garson çağırdı. Call ID: " . $call_id . "',
			date_added = NOW()");

	return array(
		'success' => true,
		'message' => 'Garson çağrınız alındı. Sizi bekletmeden ilgileneceğiz.',
		'state'   => $this->getCustomerOrderState()
	);
}

	public function getPendingWaiterCall() {
	$table_id = !empty($this->session->data['menu_table_id'])
		? (int)$this->session->data['menu_table_id']
		: 0;

	if (!$table_id) {
		return array();
	}

	$this->closeExpiredWaiterCalls($table_id);

	$q = $this->db->query("SELECT call_id, status, date_added, date_modified
		FROM `" . DB_PREFIX . "restaurant_call`
		WHERE table_id = '" . (int)$table_id . "'
		AND call_type = 'waiter_call'
		AND status IN ('new','seen')
		ORDER BY call_id DESC
		LIMIT 1");

	if (!$q->num_rows) {
		return array();
	}

	return array(
		'call_id'       => (int)$q->row['call_id'],
		'status'        => (string)$q->row['status'],
		'date_added'    => $q->row['date_added'],
		'date_modified' => $q->row['date_modified']
	);
}

	private function closeExpiredWaiterCalls($table_id) {
		$table_id = (int)$table_id;
		$minutes = (int)$this->getRestaurantSettingValue('restaurant_waiter_call_reset_minutes', 5);

		if ($minutes < 1) {
			$minutes = 5;
		}

		if ($minutes > 60) {
			$minutes = 60;
		}

		$this->db->query("UPDATE `" . DB_PREFIX . "restaurant_call`
			SET status = 'closed',
				date_modified = NOW()
			WHERE table_id = '" . $table_id . "'
			AND call_type = 'waiter_call'
			AND status = 'seen'
			AND date_modified <= DATE_SUB(NOW(), INTERVAL " . (int)$minutes . " MINUTE)");
	}
	public function hasPendingBillRequest() {
	$table_id = !empty($this->session->data['menu_table_id'])
		? (int)$this->session->data['menu_table_id']
		: 0;

	if (!$table_id) {
		return false;
	}

	$this->closeExpiredBillRequests($table_id);

	// Masa kapandıysa / boşaldıysa / ödeme alındıysa eski hesap talebi geçersiz say
	$table_status = $this->db->query("
		SELECT service_status
		FROM `" . DB_PREFIX . "restaurant_table_status`
		WHERE table_id = '" . $table_id . "'
		LIMIT 1
	");

	if ($table_status->num_rows) {
		$closed_statuses = array('paid', 'closed', 'empty');

		if (in_array((string)$table_status->row['service_status'], $closed_statuses, true)) {
			return false;
		}
	}

	// Masada açık sipariş yoksa hesap talebi de geçerli sayılmasın
	$open_orders = $this->db->query("
		SELECT COUNT(*) AS total
		FROM `" . DB_PREFIX . "restaurant_order`
		WHERE table_id = '" . $table_id . "'
		AND service_status IN ('waiting_order','in_kitchen','ready_for_service','out_for_service','served')
		AND is_paid = '0'
	");

	if ((int)$open_orders->row['total'] <= 0) {
		return false;
	}

	// En son aktif sipariş zamanı
	$latest_order = $this->db->query("
		SELECT date_added
		FROM `" . DB_PREFIX . "restaurant_order`
		WHERE table_id = '" . $table_id . "'
		AND service_status IN ('waiting_order','in_kitchen','ready_for_service','out_for_service','served')
		AND is_paid = '0'
		ORDER BY restaurant_order_id DESC
		LIMIT 1
	");

	if (!$latest_order->num_rows) {
		return false;
	}

	$latest_order_date = $this->db->escape($latest_order->row['date_added']);

	// Sadece en son aktif siparişten SONRA oluşmuş ve hâlâ açık olan hesap taleplerini say
	$q = $this->db->query("
		SELECT COUNT(*) AS total
		FROM `" . DB_PREFIX . "restaurant_call`
		WHERE table_id = '" . $table_id . "'
		AND call_type = 'bill_request'
		AND status IN ('new','seen')
		AND date_added >= '" . $latest_order_date . "'
	");

	return ((int)$q->row['total'] > 0);
}

	public function getPendingBillRequest() {
		$table_id = !empty($this->session->data['menu_table_id'])
			? (int)$this->session->data['menu_table_id']
			: 0;

		if (!$table_id) {
			return array();
		}

		$this->closeExpiredBillRequests($table_id);

		$q = $this->db->query("SELECT call_id, status, date_added, date_modified
			FROM `" . DB_PREFIX . "restaurant_call`
			WHERE table_id = '" . (int)$table_id . "'
			AND call_type = 'bill_request'
			AND status IN ('new','seen')
			ORDER BY call_id DESC
			LIMIT 1");

		if (!$q->num_rows) {
			return array();
		}

		return array(
			'call_id'       => (int)$q->row['call_id'],
			'status'        => (string)$q->row['status'],
			'date_added'    => $q->row['date_added'],
			'date_modified' => $q->row['date_modified']
		);
	}

	private function closeExpiredBillRequests($table_id) {
		$table_id = (int)$table_id;
		$minutes = (int)$this->getRestaurantSettingValue('restaurant_bill_request_reset_minutes', 5);

		if ($minutes < 1) {
			$minutes = 5;
		}

		if ($minutes > 60) {
			$minutes = 60;
		}

		$this->db->query("UPDATE `" . DB_PREFIX . "restaurant_call`
			SET status = 'closed',
				date_modified = NOW()
			WHERE table_id = '" . $table_id . "'
			AND call_type = 'bill_request'
			AND status = 'seen'
			AND date_modified <= DATE_SUB(NOW(), INTERVAL " . (int)$minutes . " MINUTE)");
	}

	public function requestBill($note = '') {
		if (!$this->canTrackOrder()) {
			return array(
				'success' => false,
				'message' => 'QR masa doğrulaması bulunamadı.'
			);
		}

		if (!$this->canRequestBill()) {
			return array(
				'success' => false,
				'message' => 'Şu anda hesap isteği oluşturulamıyor.'
			);
		}

		if ($this->hasPendingBillRequest()) {
			return array(
				'success' => false,
				'message' => 'Bu masa için zaten aktif bir hesap talebi bulunuyor.'
			);
		}

		$table_id = (int)$this->session->data['menu_table_id'];
$this->db->query("UPDATE `" . DB_PREFIX . "restaurant_call`
	SET status = 'closed',
		date_modified = NOW()
	WHERE table_id = '" . $table_id . "'
	AND call_type = 'bill_request'
	AND status IN ('new','seen')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "restaurant_call`
			SET table_id = '" . $table_id . "',
				call_type = 'bill_request',
				status = 'new',
				note = '" . $this->db->escape(trim((string)$note)) . "',
				date_added = NOW(),
				date_modified = NOW()");

		$call_id = (int)$this->db->getLastId();

		$this->db->query("INSERT INTO `" . DB_PREFIX . "restaurant_order_history`
			SET restaurant_order_id = '0',
				old_status = NULL,
				new_status = 'bill_request',
				user_id = '0',
				comment = 'Müşteri hesap talebi oluşturdu. Call ID: " . $call_id . "',
				date_added = NOW()");
        $this->sendBillRequestNotification($table_id, $call_id);
		return array(
			'success' => true,
			'message' => 'Hesap talebiniz garsona iletildi.',
			'state'   => $this->getCustomerOrderState()
		);
	}

	public function getCustomerOrderState() {
	$active_orders = $this->getActiveOrders();

	$table_total_raw = 0.0;

	foreach ($active_orders as $order) {
		$table_total_raw += isset($order['total_raw']) ? (float)$order['total_raw'] : 0;
	}

	return array(
		'can_order'            => $this->canOrder(),
		'can_track_order'      => $this->canTrackOrder(),
		'has_open_orders'      => $this->hasOpenOrders(),
		'active_orders'        => $active_orders,
		'cart'                 => $this->getCartSummary(),
		'table_total_raw'      => $table_total_raw,
		'table_total'          => $this->currency->format(
			$table_total_raw,
			$this->session->data['currency']
		),
		'can_request_bill'     => $this->canRequestBill(),
		'has_pending_bill_request' => $this->hasPendingBillRequest(),
		'bill_request'         => $this->getPendingBillRequest(),
		'waiter_call'          => $this->getPendingWaiterCall()
	);
}

	public function submitOrder($note = '') {
		if (!$this->canOrder()) {
			return array(
				'success' => false,
				'message' => 'QR sipariş akışı şu anda aktif değil.'
			);
		}

		$summary = $this->getCartSummary();

		if (empty($summary['items'])) {
			return array(
				'success' => false,
				'message' => 'Siparişinizde ürün bulunmuyor.'
			);
		}

		$table_id = (int)$this->session->data['menu_table_id'];
		$waiter_user_id = 0;
		$total_amount = (float)$summary['total_raw'];
		if ($this->isWaiterPanelEnabled()) {
			$service_status = 'waiting_order';
			$success_message = 'Siparisiniz garsona ulasti. En kisa surede yaniniza gelecektir.';
		} elseif ($this->isAkinsoftEnabled() || $this->isKitchenPanelEnabled()) {
			$service_status = 'in_kitchen';
			$success_message = $this->isAkinsoftEnabled() ? 'Siparisiniz ekibe iletildi.' : 'Siparisiniz mutfaga iletildi.';
		} else {
			$service_status = 'served';
			$success_message = 'Siparisiniz alindi. Odeme/kasa sureci icin ekibimiz ilgilenecek.';
		}

		$this->db->query("INSERT INTO `" . DB_PREFIX . "restaurant_order`
			SET table_id = '" . $table_id . "',
				waiter_user_id = '" . $waiter_user_id . "',
				service_status = '" . $this->db->escape($service_status) . "',
				customer_note = '" . $this->db->escape(trim((string)$note)) . "',
				total_amount = '" . (float)$total_amount . "',
				is_paid = '0',
				date_added = NOW(),
				date_modified = NOW()");

		$restaurant_order_id = (int)$this->db->getLastId();

		foreach ($summary['items'] as $item) {
			$row_total = (float)$item['price_raw'] * (int)$item['quantity'];

			$this->db->query("INSERT INTO `" . DB_PREFIX . "restaurant_order_product`
				SET restaurant_order_id = '" . $restaurant_order_id . "',
					product_id = '" . (int)$item['product_id'] . "',
					name = '" . $this->db->escape($item['name']) . "',
					price = '" . (float)$item['price_raw'] . "',
					quantity = '" . (int)$item['quantity'] . "',
					total = '" . (float)$row_total . "'");
		}

		$this->db->query("INSERT INTO `" . DB_PREFIX . "restaurant_order_history`
			SET restaurant_order_id = '" . $restaurant_order_id . "',
				old_status = NULL,
				new_status = '" . $this->db->escape($service_status) . "',
				user_id = '0',
				comment = 'QR menu üzerinden sipariş oluşturuldu.',
				date_added = NOW()");

		if ($service_status === 'in_kitchen' && $this->isAkinsoftEnabled()) {
			$this->db->query("UPDATE `" . DB_PREFIX . "restaurant_order`
				SET integration_status = 'pending_export',
					integration_message = 'AKINSOFT bekliyor',
					integration_date = NOW()
				WHERE restaurant_order_id = '" . $restaurant_order_id . "'");
		}

		$this->syncTableStatus($table_id);

		$this->clearCart();

		return array(
			'success'             => true,
			'message'             => $success_message,
			'restaurant_order_id' => $restaurant_order_id,
			'state'               => $this->getCustomerOrderState()
		);
	}
private function sendBillRequestNotification($table_id, $call_id) {
	$table_id = (int)$table_id;
	$call_id = (int)$call_id;

	$log_file = DIR_LOGS . 'waiter_push.log';

	$table_no = $table_id;
	$table_name = 'Masa ' . $table_id;

	$table_query = $this->db->query("SELECT table_no, name
		FROM `" . DB_PREFIX . "restaurant_table`
		WHERE table_id = '" . $table_id . "'
		LIMIT 1");

	if ($table_query->num_rows) {
		if (!empty($table_query->row['table_no'])) {
			$table_no = $table_query->row['table_no'];
		}

		if (!empty($table_query->row['name'])) {
			$table_name = $table_query->row['name'];
		}
	}

	$title = 'Hesap İste';
	$body  = 'Masa ' . $table_no . ' hesap talebinde bulundu.';

	$payload = array(
		'type'      => 'bill_request',
		'table_id'  => $table_id,
		'table_no'  => $table_no,
		'table_name'=> $table_name,
		'call_id'   => $call_id,
		'title'     => $title,
		'body'      => $body
	);

	file_put_contents(
		$log_file,
		'[' . date('Y-m-d H:i:s') . '] BILL REQUEST: ' . json_encode($payload, JSON_UNESCAPED_UNICODE) . PHP_EOL,
		FILE_APPEND
	);

	/*
	 |------------------------------------------------------------------
	 | Eğer mevcut projede garson push gönderen ortak bir helper/fonksiyon
	 | varsa burada onu çağıracağız.
	 | Örnek:
	 |
	 | $this->load->model('extension/module/waiter_panel');
	 | $this->model_extension_module_waiter_panel->sendPushNotification($title, $body, $payload);
	 |
	 | veya sende daha önce kullandığın özel FCM helper neyse onu buraya koyacağız.
	 |------------------------------------------------------------------
	 */

	if (class_exists('Log')) {
		$log = new Log('waiter_push.log');
		$log->write('Bill request notification prepared: ' . json_encode($payload, JSON_UNESCAPED_UNICODE));
	}
}
	private function getProductInfo($product_id) {
		$product_id = (int)$product_id;
		$language_id = (int)$this->config->get('config_language_id');

		$query = $this->db->query("SELECT p.product_id, p.image, p.price, p.tax_class_id, pd.name
			FROM `" . DB_PREFIX . "product` p
			LEFT JOIN `" . DB_PREFIX . "product_description` pd ON (p.product_id = pd.product_id)
			WHERE p.product_id = '" . $product_id . "'
			AND pd.language_id = '" . $language_id . "'
			AND p.status = '1'
			LIMIT 1");

		return $query->num_rows ? $query->row : array();
	}

	private function formatPrice($price, $tax_class_id = 0) {
		return $this->currency->format(
			$this->tax->calculate((float)$price, (int)$tax_class_id, $this->config->get('config_tax')),
			$this->session->data['currency']
		);
	}

	private function syncTableStatus($table_id) {
		$table_id = (int)$table_id;

		$active_query = $this->db->query("SELECT
				COUNT(*) AS active_order_count,
				COALESCE(SUM(total_amount), 0) AS total_amount
			FROM `" . DB_PREFIX . "restaurant_order`
			WHERE table_id = '" . $table_id . "'
			AND service_status IN ('waiting_order','in_kitchen','ready_for_service','out_for_service','served')");

		$active_order_count = (int)$active_query->row['active_order_count'];
		$total_amount = (float)$active_query->row['total_amount'];

		$latest_query = $this->db->query("SELECT service_status
			FROM `" . DB_PREFIX . "restaurant_order`
			WHERE table_id = '" . $table_id . "'
			AND service_status IN ('waiting_order','in_kitchen','ready_for_service','out_for_service','served')
			ORDER BY restaurant_order_id DESC
			LIMIT 1");

		if ($latest_query->num_rows) {
			$table_status = $latest_query->row['service_status'];
		} else {
			$table_status = 'empty';
			$active_order_count = 0;
			$total_amount = 0.0000;
		}

		$check = $this->db->query("SELECT table_id
			FROM `" . DB_PREFIX . "restaurant_table_status`
			WHERE table_id = '" . $table_id . "'");

		if ($check->num_rows) {
			$this->db->query("UPDATE `" . DB_PREFIX . "restaurant_table_status`
				SET service_status = '" . $this->db->escape($table_status) . "',
					active_order_count = '" . (int)$active_order_count . "',
					total_amount = '" . (float)$total_amount . "',
					date_modified = NOW()
				WHERE table_id = '" . $table_id . "'");
		} else {
			$this->db->query("INSERT INTO `" . DB_PREFIX . "restaurant_table_status`
				SET table_id = '" . $table_id . "',
					service_status = '" . $this->db->escape($table_status) . "',
					active_order_count = '" . (int)$active_order_count . "',
					total_amount = '" . (float)$total_amount . "',
					date_modified = NOW()");
		}
	}

	private function parsePrepMinutes($text) {
		$text = (string)$text;

		if ($text === '') {
			return 0;
		}

		preg_match_all('/\d+/', $text, $matches);

		if (empty($matches[0])) {
			return 0;
		}

		$numbers = array_map('intval', $matches[0]);

		return max($numbers);
	}

	private function getPrepDeadlineTs($order, $prep_minutes) {
		$prep_minutes = (int)$prep_minutes;

		if ($prep_minutes <= 0 || empty($order['date_modified']) || $order['date_modified'] == '0000-00-00 00:00:00') {
			return 0;
		}

		if (!in_array($order['service_status'], array('in_kitchen', 'ready_for_service', 'out_for_service'), true)) {
			return 0;
		}

		$start = strtotime($order['date_modified']);

		if (!$start) {
			return 0;
		}

		return $start + ($prep_minutes * 60);
	}

	public function getRestaurantSettingValue($key, $default = 1) {
		$table = DB_PREFIX . 'ayarlar';

		$exists = $this->db->query("SHOW TABLES LIKE '" . $this->db->escape($table) . "'");

		if (!$exists->num_rows) {
			return (int)$default;
		}

		$query = $this->db->query("SELECT ayar_value FROM `" . $table . "`
			WHERE ayar_key = '" . $this->db->escape($key) . "'
			LIMIT 1");

		if (!$query->num_rows || $query->row['ayar_value'] === '') {
			return (int)$default;
		}

		return (int)$query->row['ayar_value'];
	}

	private function isQrOrderMenuEnabled() {
		return $this->getRestaurantSettingValue('restaurant_qr_order_menu', 1) === 1;
	}

	private function isWaiterPanelEnabled() {
		return $this->getRestaurantSettingValue('restaurant_waiter_panel', 1) === 1;
	}

	private function isKitchenPanelEnabled() {
		return $this->getRestaurantSettingValue('restaurant_kitchen_panel', 1) === 1;
	}

	private function isAkinsoftEnabled() {
		return $this->getRestaurantSettingValue('restaurant_akinsoft_enabled', 0) === 1;
	}

	private function isCashierPanelEnabled() {
		return $this->getRestaurantSettingValue('restaurant_cashier_panel', 1) === 1;
	}
}
