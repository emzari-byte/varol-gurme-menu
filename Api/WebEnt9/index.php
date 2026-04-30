<?php
$public_root = dirname(__DIR__, 2);
$menu_root = is_file($public_root . '/menu/config.php') ? $public_root . '/menu' : $public_root;
$log_path = $menu_root . '/akinsoft_webent_request.log';

$raw_body = file_get_contents('php://input');
$headers = function_exists('getallheaders') ? getallheaders() : array();
$payload = json_decode($raw_body, true);

$lines = array();
$lines[] = '';
$lines[] = '--- WEBENT API ' . date('Y-m-d H:i:s') . ' ---';
$lines[] = (isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : '') . ' ' . (isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '');
$lines[] = 'Query: ' . json_encode($_GET);
$lines[] = 'Post: ' . json_encode($_POST);
$lines[] = 'Json: ' . json_encode(is_array($payload) ? $payload : array());
$lines[] = 'Headers:';

foreach ($headers as $key => $value) {
	$lines[] = '  ' . $key . ': ' . $value;
}

$lines[] = 'Body:';
$lines[] = $raw_body;

if (is_dir($menu_root)) {
	file_put_contents($log_path, implode(PHP_EOL, $lines) . PHP_EOL, FILE_APPEND);
}

$path = isset($_SERVER['REQUEST_URI']) ? parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) : '';
$endpoint = strtolower(trim(preg_replace('#/api/webent9#i', '', $path), '/'));
$endpoint = preg_replace('#^index\.php/#i', '', $endpoint);
$endpoint = preg_replace('#/index\.php$#i', '', $endpoint);

if ($endpoint === 'siparissayigetirv2') {
	$count = webent_pending_order_count($menu_root);
	webent_json(array(
		'success' => true,
		'result' => true,
		'status' => true,
		'SAYI' => $count,
		'COUNT' => $count,
		'SIPARIS_SAYISI' => $count,
		'SiparisSayisi' => $count,
		'data' => array(
			array(
				'SAYI' => $count,
				'COUNT' => $count,
				'SIPARIS_SAYISI' => $count
			)
		)
	));
}

$order_endpoints = array(
	'siparisgetir',
	'siparisgetirv2',
	'siparisdetaygetir',
	'siparisdetaygetirv2',
	'siparislerigetir',
	'siparislerigetirv2',
	'siparislistesigetir',
	'siparislistesigetirv2'
);

if (in_array($endpoint, $order_endpoints, true)) {
	$orders = webent_pending_orders($menu_root, webent_request_int(array(
		'restaurant_order_id',
		'siparis_id',
		'SIPARIS_ID',
		'id',
		'ID'
	)));

	webent_json(array(
		'success' => true,
		'result' => true,
		'status' => true,
		'count' => count($orders),
		'SAYI' => count($orders),
		'data' => $orders,
		'SIPARISLER' => $orders,
		'siparisler' => $orders
	));
}

$sent_endpoints = array(
	'siparisaktarildi',
	'siparisaktarildiv2',
	'siparisdurumguncelle',
	'siparisdurumguncellev2',
	'siparisonayla',
	'siparisonaylav2',
	'sipariskapat',
	'sipariskapatv2'
);

if (in_array($endpoint, $sent_endpoints, true)) {
	$order_id = webent_request_int(array(
		'restaurant_order_id',
		'siparis_id',
		'SIPARIS_ID',
		'id',
		'ID'
	));
	$external_no = webent_request_string(array('external_order_no', 'FIS_NO', 'fis_no', 'ADISYON_NO', 'adisyon_no', 'SIPARIS_NO', 'siparis_no'));
	$message = webent_request_string(array('message', 'MESAJ', 'mesaj', 'ACIKLAMA', 'aciklama'));

	if ($message === '') {
		$message = 'AKINSOFT WebEnt tarafindan alindi';
	}

	$ok = webent_mark_order_sent($menu_root, $order_id, $external_no, $message);

	webent_json(array(
		'success' => $ok,
		'result' => $ok,
		'status' => $ok,
		'message' => $ok ? 'OK' : 'Siparis bulunamadi',
		'restaurant_order_id' => $order_id
	));
}

header('Content-Type: application/json; charset=utf-8');
echo webent_encode(array(
	'success' => true,
	'result' => true,
	'status' => true,
	'message' => 'OK',
	'endpoint' => $endpoint,
	'data' => array()
));

function webent_json($data) {
	header('Content-Type: application/json; charset=utf-8');
	echo webent_encode($data);
	exit;
}

function webent_encode($data) {
	$options = defined('JSON_UNESCAPED_UNICODE') ? JSON_UNESCAPED_UNICODE : 0;
	return json_encode($data, $options);
}

function webent_request_value($keys) {
	global $payload;

	foreach ($keys as $key) {
		if (isset($_GET[$key])) {
			return $_GET[$key];
		}

		if (isset($_POST[$key])) {
			return $_POST[$key];
		}

		if (is_array($payload) && isset($payload[$key])) {
			return $payload[$key];
		}
	}

	return null;
}

function webent_request_int($keys) {
	$value = webent_request_value($keys);
	return $value === null ? 0 : (int)$value;
}

function webent_request_string($keys) {
	$value = webent_request_value($keys);
	return $value === null ? '' : trim((string)$value);
}

function webent_connect($menu_root) {
	$config = $menu_root . '/config.php';

	if (!is_file($config)) {
		return false;
	}

	require_once $config;

	if (!defined('DB_HOSTNAME') || !defined('DB_USERNAME') || !defined('DB_DATABASE')) {
		return false;
	}

	$port = defined('DB_PORT') ? (int)DB_PORT : 3306;
	$mysqli = @new mysqli(DB_HOSTNAME, DB_USERNAME, DB_PASSWORD, DB_DATABASE, $port);

	if ($mysqli->connect_errno) {
		return false;
	}

	$mysqli->set_charset('utf8');

	return $mysqli;
}

function webent_prefix() {
	return defined('DB_PREFIX') ? DB_PREFIX : '';
}

function webent_pending_order_count($menu_root) {
	$mysqli = webent_connect($menu_root);

	if (!$mysqli) {
		return 0;
	}

	$prefix = webent_prefix();
	$sql = "SELECT COUNT(*) AS total
		FROM `" . $mysqli->real_escape_string($prefix) . "restaurant_order`
		WHERE service_status = 'in_kitchen'
		AND (integration_status IN ('pending_export', 'failed') OR integration_status IS NULL OR integration_status = '')";

	$result = $mysqli->query($sql);
	$count = 0;

	if ($result) {
		$row = $result->fetch_assoc();
		$count = isset($row['total']) ? (int)$row['total'] : 0;
		$result->free();
	}

	$mysqli->close();

	return $count;
}

function webent_pending_orders($menu_root, $restaurant_order_id = 0) {
	$mysqli = webent_connect($menu_root);

	if (!$mysqli) {
		return array();
	}

	$prefix = webent_prefix();
	$language_id = webent_turkish_language_id($mysqli, $prefix);
	$where = "ro.service_status = 'in_kitchen' AND (ro.integration_status IN ('pending_export', 'failed') OR ro.integration_status IS NULL OR ro.integration_status = '')";

	if ($restaurant_order_id) {
		$where .= " AND ro.restaurant_order_id = '" . (int)$restaurant_order_id . "'";
	}

	$sql = "SELECT ro.restaurant_order_id, ro.table_id, ro.waiter_user_id, ro.service_status,
			ro.customer_note, ro.payment_method, ro.total_amount, ro.date_added, ro.date_modified,
			ro.integration_status, rt.table_no, rt.name AS table_name
		FROM `" . $mysqli->real_escape_string($prefix) . "restaurant_order` ro
		LEFT JOIN `" . $mysqli->real_escape_string($prefix) . "restaurant_table` rt ON (rt.table_id = ro.table_id)
		WHERE " . $where . "
		ORDER BY ro.restaurant_order_id ASC
		LIMIT 50";

	$result = $mysqli->query($sql);
	$orders = array();

	if ($result) {
		while ($order = $result->fetch_assoc()) {
			$products = webent_order_products($mysqli, $prefix, (int)$order['restaurant_order_id'], $language_id);
			$orders[] = webent_format_order($order, $products);
		}

		$result->free();
	}

	$mysqli->close();

	return $orders;
}

function webent_turkish_language_id($mysqli, $prefix) {
	$sql = "SELECT language_id
		FROM `" . $mysqli->real_escape_string($prefix) . "language`
		WHERE code IN ('tr-tr', 'tr', 'turkish')
		ORDER BY language_id ASC
		LIMIT 1";
	$result = $mysqli->query($sql);

	if ($result && $result->num_rows) {
		$row = $result->fetch_assoc();
		$result->free();
		return (int)$row['language_id'];
	}

	return 1;
}

function webent_order_products($mysqli, $prefix, $restaurant_order_id, $language_id) {
	$sql = "SELECT rop.restaurant_order_product_id, rop.product_id,
			COALESCE(NULLIF(pd.name, ''), rop.name) AS name,
			p.model,
			rop.quantity,
			rop.price,
			rop.total
		FROM `" . $mysqli->real_escape_string($prefix) . "restaurant_order_product` rop
		LEFT JOIN `" . $mysqli->real_escape_string($prefix) . "product` p ON (p.product_id = rop.product_id)
		LEFT JOIN `" . $mysqli->real_escape_string($prefix) . "product_description` pd ON (pd.product_id = rop.product_id AND pd.language_id = '" . (int)$language_id . "')
		WHERE rop.restaurant_order_id = '" . (int)$restaurant_order_id . "'
		ORDER BY rop.restaurant_order_product_id ASC";

	$result = $mysqli->query($sql);
	$products = array();

	if ($result) {
		while ($product = $result->fetch_assoc()) {
			$quantity = (float)$product['quantity'];
			$price = (float)$product['price'];
			$total = (float)$product['total'];

			if ($total <= 0) {
				$total = $price * $quantity;
			}

			$products[] = array(
				'ID' => (int)$product['restaurant_order_product_id'],
				'SATIR_ID' => (int)$product['restaurant_order_product_id'],
				'URUN_ID' => (int)$product['product_id'],
				'STOK_KODU' => (string)$product['model'],
				'STOKKODU' => (string)$product['model'],
				'URUN_ADI' => (string)$product['name'],
				'ADI' => (string)$product['name'],
				'MIKTAR' => $quantity,
				'ADET' => $quantity,
				'BIRIM' => 'Adet',
				'FIYAT' => $price,
				'BIRIM_FIYAT' => $price,
				'TUTAR' => $total,
				'TOPLAM' => $total
			);
		}

		$result->free();
	}

	return $products;
}

function webent_format_order($order, $products) {
	$table_no = !empty($order['table_no']) ? (int)$order['table_no'] : (int)$order['table_id'];
	$table_name = trim((string)$order['table_name']);

	if ($table_name === '') {
		$table_name = 'Masa ' . $table_no;
	}

	$total = (float)$order['total_amount'];

	if ($total <= 0) {
		foreach ($products as $product) {
			$total += (float)$product['TOPLAM'];
		}
	}

	return array(
		'ID' => (int)$order['restaurant_order_id'],
		'SIPARIS_ID' => (int)$order['restaurant_order_id'],
		'SIPARIS_NO' => (string)$order['restaurant_order_id'],
		'BELGE_NO' => (string)$order['restaurant_order_id'],
		'TARIH' => date('Y-m-d', strtotime($order['date_added'])),
		'SAAT' => date('H:i:s', strtotime($order['date_added'])),
		'MASA_ID' => (int)$order['table_id'],
		'MASA_NO' => $table_no,
		'MASA_ADI' => $table_name,
		'MUSTERI_ADI' => $table_name,
		'ACIKLAMA' => (string)$order['customer_note'],
		'NOT' => (string)$order['customer_note'],
		'ODEME_TIPI' => (string)$order['payment_method'],
		'PARA_BIRIMI' => 'TL',
		'TOPLAM' => $total,
		'GENEL_TOPLAM' => $total,
		'KALEM_SAYISI' => count($products),
		'KALEMLER' => $products,
		'DETAYLAR' => $products,
		'URUNLER' => $products,
		'products' => $products,
		'restaurant_order_id' => (int)$order['restaurant_order_id'],
		'table_id' => (int)$order['table_id'],
		'table_no' => $table_no,
		'table_name' => $table_name,
		'customer_note' => (string)$order['customer_note'],
		'total_amount' => $total,
		'date_added' => (string)$order['date_added']
	);
}

function webent_mark_order_sent($menu_root, $restaurant_order_id, $external_order_no = '', $message = '') {
	$restaurant_order_id = (int)$restaurant_order_id;

	if (!$restaurant_order_id) {
		return false;
	}

	$mysqli = webent_connect($menu_root);

	if (!$mysqli) {
		return false;
	}

	$prefix = webent_prefix();
	$sql = "UPDATE `" . $mysqli->real_escape_string($prefix) . "restaurant_order`
		SET integration_status = 'sent',
			external_order_no = '" . $mysqli->real_escape_string($external_order_no) . "',
			integration_message = '" . $mysqli->real_escape_string($message) . "',
			integration_date = NOW()
		WHERE restaurant_order_id = '" . $restaurant_order_id . "'
		AND integration_status IN ('pending_export', 'failed')";

	$mysqli->query($sql);
	$ok = $mysqli->affected_rows > 0;
	$mysqli->close();

	return $ok;
}
