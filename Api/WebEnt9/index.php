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
$endpoint = strtolower(trim(str_replace('/Api/WebEnt9', '', $path), '/'));

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

header('Content-Type: application/json; charset=utf-8');
echo json_encode(array(
	'success' => true,
	'result' => true,
	'status' => true,
	'message' => 'OK',
	'data' => array()
));

function webent_json($data) {
	header('Content-Type: application/json; charset=utf-8');
	echo json_encode($data);
	exit;
}

function webent_pending_order_count($menu_root) {
	$config = $menu_root . '/config.php';

	if (!is_file($config)) {
		return 0;
	}

	require_once $config;

	if (!defined('DB_HOSTNAME') || !defined('DB_USERNAME') || !defined('DB_DATABASE')) {
		return 0;
	}

	$port = defined('DB_PORT') ? (int)DB_PORT : 3306;
	$mysqli = @new mysqli(DB_HOSTNAME, DB_USERNAME, DB_PASSWORD, DB_DATABASE, $port);

	if ($mysqli->connect_errno) {
		return 0;
	}

	$mysqli->set_charset('utf8');

	$prefix = defined('DB_PREFIX') ? DB_PREFIX : '';
	$sql = "SELECT COUNT(*) AS total
		FROM `" . $mysqli->real_escape_string($prefix) . "restaurant_order`
		WHERE service_status = 'in_kitchen'
		AND integration_status IN ('pending_export', 'failed')";

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
