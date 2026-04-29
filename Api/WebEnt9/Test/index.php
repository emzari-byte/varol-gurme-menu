<?php
$menu_root = dirname(__DIR__, 3) . '/menu';
$log_path = $menu_root . '/akinsoft_webent_request.log';

$raw_body = file_get_contents('php://input');
$headers = function_exists('getallheaders') ? getallheaders() : array();

$lines = array();
$lines[] = '';
$lines[] = '--- WEBENT TEST ' . date('Y-m-d H:i:s') . ' ---';
$lines[] = (isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : '') . ' ' . (isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '');
$lines[] = 'Query: ' . json_encode($_GET);
$lines[] = 'Post: ' . json_encode($_POST);
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
	'tpwd' => 'local-test-token',
	'tPwd' => 'local-test-token'
));
