<?php
$menu_root = dirname(__DIR__, 2) . '/menu';
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

header('Content-Type: application/json; charset=utf-8');
echo json_encode(array(
	'success' => true,
	'result' => true,
	'status' => true,
	'message' => 'OK',
	'data' => array()
));
