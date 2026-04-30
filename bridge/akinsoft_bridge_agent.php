<?php
/**
 * Varol Gurme Akinsoft Bridge Agent
 *
 * Bu dosya restoran bilgisayarinda calisir. Canli QR sistemden bekleyen
 * siparisleri ceker, local Firebird Akinsoft veritabanina yazar ve sonucu
 * canli sisteme bildirir.
 */

if (PHP_SAPI !== 'cli') {
	exit("This script must be run from CLI.\n");
}

$config_file = __DIR__ . DIRECTORY_SEPARATOR . 'akinsoft_bridge_config.php';

if (!is_file($config_file)) {
	exit("Config bulunamadi: " . $config_file . "\nakinsoft_bridge_config.example.php dosyasini kopyalayip duzenleyin.\n");
}

$config = require $config_file;
$agent = new AkinsoftBridgeAgent($config);

if (in_array('--check', $argv, true)) {
	$agent->check();
	exit;
}

if (in_array('--once', $argv, true)) {
	$agent->runOnce();
	exit;
}

if (in_array('--sync-tables', $argv, true)) {
	$agent->syncTables();
	exit;
}

if (in_array('--sync-prices', $argv, true)) {
	$agent->syncPrices();
	exit;
}

if (in_array('--sync-all', $argv, true)) {
	$agent->syncTables();
	$agent->syncPrices();
	exit;
}

$agent->run();

class AkinsoftBridgeAgent {
	private $config;
	private $pdo;

	public function __construct(array $config) {
		$this->config = $config;
	}

	public function run() {
		$this->log('Bridge agent basladi.');

		while (true) {
			$this->runOnce();

			sleep(max(2, (int)$this->get('poll_seconds', 5)));
		}
	}

	public function runOnce() {
		try {
			$this->syncPendingOrders();
			$this->syncClosedOrders();
		} catch (Exception $e) {
			$this->log('HATA: ' . $e->getMessage());
		}
	}

	public function check() {
		$this->log('Bridge kontrol basladi.');
		$this->log('PHP: ' . PHP_VERSION);
		$this->log('cURL: ' . (extension_loaded('curl') ? 'aktif' : 'eksik'));
		$this->log('pdo_firebird: ' . (extension_loaded('pdo_firebird') ? 'aktif' : 'eksik'));

		$site_url = rtrim((string)$this->get('site_base_url', ''), '/');
		$token = (string)$this->get('bridge_token', '');

		$this->log('Canli site URL: ' . ($site_url !== '' ? $site_url : 'bos'));
		$this->log('Bridge token: ' . ($token !== '' ? 'var' : 'bos'));

		try {
			$response = $this->request('extension/module/akinsoft_bridge/pending', array('limit' => 1));
			$this->log('Canli site baglantisi: ' . (!empty($response['success']) ? 'basarili' : 'basarisiz - ' . $this->message($response)));
		} catch (Exception $e) {
			$this->log('Canli site baglantisi: basarisiz - ' . $e->getMessage());
		}

		try {
			$this->firebird();
			$this->log('Firebird baglantisi: basarili');
		} catch (Exception $e) {
			$this->log('Firebird baglantisi: basarisiz - ' . $e->getMessage());
		}

		$this->log('Bridge kontrol bitti.');
	}

	public function syncTables() {
		try {
			$rows = $this->firebird()->query("SELECT BLKODU, MASAADI, MASAGRUBU, KACKISILIK, GIZLI FROM MASA ORDER BY BLKODU ASC")->fetchAll(PDO::FETCH_ASSOC);

			$response = $this->request('extension/module/akinsoft_bridge/syncTables', array(), array(
				'tables_json' => $this->jsonEncodeFirebirdRows($rows)
			));

			if (empty($response['success'])) {
				$this->log('Masa senkronu basarisiz: ' . $this->message($response));
				return;
			}

			$this->log($this->message($response));
		} catch (Exception $e) {
			$this->log('Masa senkronu hatasi: ' . $e->getMessage());
		}
	}

	public function syncPrices() {
		try {
			$rows = $this->firebird()->query("SELECT s.STOKKODU, f.FIYATI
				FROM STOK s
				INNER JOIN STOK_FIYAT f ON (f.BLSTKODU = s.BLKODU)
				WHERE s.STOKKODU IS NOT NULL
					AND s.STOKKODU <> ''
					AND f.ALIS_SATIS = 2
					AND f.FIYAT_NO = 1
					AND f.FIYATI IS NOT NULL")->fetchAll(PDO::FETCH_ASSOC);

			$response = $this->request('extension/module/akinsoft_bridge/syncPrices', array(), array(
				'prices_json' => $this->jsonEncodeFirebirdRows($rows)
			));

			if (empty($response['success'])) {
				$this->log('Fiyat senkronu basarisiz: ' . $this->message($response));
				return;
			}

			$this->log($this->message($response));
		} catch (Exception $e) {
			$this->log('Fiyat senkronu hatasi: ' . $e->getMessage());
		}
	}

	private function syncPendingOrders() {
		$response = $this->request('extension/module/akinsoft_bridge/pending', array(
			'limit' => (int)$this->get('limit', 20)
		));

		if (empty($response['success'])) {
			$this->log('Bekleyen siparis alinamadi: ' . $this->message($response));
			return;
		}

		if (empty($response['orders'])) {
			return;
		}

		foreach ($response['orders'] as $order) {
			$order_id = (int)$order['restaurant_order_id'];

			try {
				$external_order_no = $this->exportOrder($order);

				$this->request('extension/module/akinsoft_bridge/mark', array(), array(
					'restaurant_order_id' => $order_id,
					'status' => 'sent',
					'external_order_no' => $external_order_no,
					'message' => 'Akinsoft adisyon #' . $external_order_no . ' bridge ile olusturuldu.'
				));

				$this->log('Siparis #' . $order_id . ' Akinsoft adisyon #' . $external_order_no . ' olarak aktarildi.');
			} catch (Exception $e) {
				$this->request('extension/module/akinsoft_bridge/mark', array(), array(
					'restaurant_order_id' => $order_id,
					'status' => 'failed',
					'message' => 'Bridge aktarim hatasi: ' . $e->getMessage()
				));

				$this->log('Siparis #' . $order_id . ' aktarilamadi: ' . $e->getMessage());
			}
		}
	}

	private function syncClosedOrders() {
		$response = $this->request('extension/module/akinsoft_bridge/sent', array(
			'limit' => 100
		));

		if (empty($response['success']) || empty($response['orders'])) {
			return;
		}

		foreach ($response['orders'] as $order) {
			$external_order_no = trim((string)$order['external_order_no']);

			if ($external_order_no === '') {
				continue;
			}

			$fis = $this->fetchClosedReceipt($external_order_no);

			if (!$fis) {
				continue;
			}

			$this->request('extension/module/akinsoft_bridge/paid', array(), array(
				'restaurant_order_id' => (int)$order['restaurant_order_id'],
				'external_fis_id' => (int)$fis['BLKODU'],
				'closed_at' => (string)$fis['KAPANISTARIHI'],
				'message' => 'Akinsoft adisyon kapandi. Fis #' . (int)$fis['BLKODU'] . ' - ' . (string)$fis['KAPANISTARIHI']
			));

			$this->log('Siparis #' . (int)$order['restaurant_order_id'] . ' Akinsoft kapanisiyla paid yapildi.');
		}
	}

	private function exportOrder(array $order) {
		if (empty($order['products'])) {
			throw new Exception('Sipariste urun yok.');
		}

		$pdo = $this->firebird();
		$pdo->beginTransaction();

		try {
			$table_no = !empty($order['table_no']) ? (int)$order['table_no'] : (int)$order['table_id'];
			$table = $this->resolveTable($table_no, !empty($order['table_name']) ? (string)$order['table_name'] : '');
			$masaadi = $table['name'];
			$total = 0.0;
			$prepared_products = array();

			foreach ($order['products'] as $product) {
				$stock_code = trim((string)$product['model']);

				if ($stock_code === '') {
					throw new Exception('OpenCart stok kodu bos: ' . (string)$product['name']);
				}

				$stock = $this->fetchStock($stock_code);

				if (!$stock) {
					throw new Exception('Akinsoft stok bulunamadi: ' . $stock_code . ' - ' . (string)$product['name']);
				}

				$quantity = max(1, (float)$product['quantity']);
				$price = (float)$product['price'];
				$row_total = (float)$product['total'];

				if ($row_total <= 0) {
					$row_total = $price * $quantity;
				}

				$total += $row_total;

				$prepared_products[] = array(
					'stock' => $stock,
					'stock_code' => $stock_code,
					'name' => !empty($stock['STOK_ADI']) ? $stock['STOK_ADI'] : (string)$product['name'],
					'quantity' => $quantity,
					'price' => $price,
					'total' => $row_total
				);
			}

			$fis_id = (int)$pdo->query("SELECT GEN_ID(ADISYONFIS_GEN, 1) FROM RDB\$DATABASE")->fetchColumn();
			$adisyon_no = (int)$pdo->query("SELECT GEN_ID(ADISYONNO_GEN, 1) FROM RDB\$DATABASE")->fetchColumn();
			$description = 'QR Menu Siparis #' . (int)$order['restaurant_order_id'];

			if (!empty($order['customer_note'])) {
				$description .= ' - ' . (string)$order['customer_note'];
			}

			$fis = $pdo->prepare("INSERT INTO ADISYONFIS (
					BLKODU, MASAADI, ADISYONNO, DURUMU, ACILISTARIHI, ADI,
					KISISAYISI, ARATUTAR, INDIRIM, TOPLAMTUTAR, TESLIM_DURUM,
					ADISYONNO_GORUNTU, DOVIZ_BIRIMI, YAZDIRILDI, FISIACAN,
					TSM_DURUMU, YSEPETI_DURUMU, FISTIPIKODU, PRINTSESSIONID,
					TSM_IDKODU, MASATIPI, MANUELEKSTRA, DISARDANEKSTRA,
					ACIKLAMA, ONLINE_ID
				) VALUES (
					?, ?, ?, 1, CURRENT_TIMESTAMP, 'GENEL MUSTERI',
					1, ?, 0, ?, 1,
					?, 'TL', 0, 'QR MENU',
					0, 0, 0, 1,
					?, 1, 0, 0,
					?, ?
				)");

			$fis->execute(array(
				$fis_id,
				$masaadi,
				$adisyon_no,
				$total,
				$total,
				(string)$adisyon_no,
				$adisyon_no,
				$this->truncate($description, 400),
				'OPENCART-' . (int)$order['restaurant_order_id']
			));

			$line_no = 1;
			$hareket = $pdo->prepare("INSERT INTO ADISYONHAREKET (
					BLKODU, BLFISKODU, BLSTKODU, STOKKODU, URUNADI,
					FIYATI, MIKTARI, TOPLAM, EKLEYEN, TARIH,
					TEMELFIYAT, SIRALAMAKODU, IPTAL, MENU, YAZDIRILDI,
					BIRIMI, KDVORANI, STOK_BIRIMI, PRINTSESSIONID
				) VALUES (
					?, ?, ?, ?, ?,
					?, ?, ?, 'Yetkili  (SYSDBA)', CURRENT_TIMESTAMP,
					?, ?, NULL, NULL, NULL,
					NULL, ?, ?, NULL
				)");

			foreach ($prepared_products as $prepared) {
				$line_id = (int)$pdo->query("SELECT GEN_ID(ADISYONHAREKET_GEN, 1) FROM RDB\$DATABASE")->fetchColumn();
				$unit = !empty($prepared['stock']['BIRIMI']) ? $prepared['stock']['BIRIMI'] : 'Adet';
				$tax = isset($prepared['stock']['KDV_ORANI']) ? (float)$prepared['stock']['KDV_ORANI'] : 0;

				$hareket->execute(array(
					$line_id,
					$fis_id,
					(int)$prepared['stock']['BLKODU'],
					$prepared['stock_code'],
					$this->truncate($prepared['name'], 250),
					$prepared['price'],
					$prepared['quantity'],
					$prepared['total'],
					$prepared['price'],
					$line_no,
					$tax,
					$unit
				));

				$line_no++;
			}

			$pdo->prepare("UPDATE ADISYONFIS SET ARATUTAR = ?, TOPLAMTUTAR = ? WHERE BLKODU = ?")
				->execute(array($total, $total, $fis_id));

			$this->markTableOccupied($table, $masaadi);

			$pdo->commit();

			return (string)$adisyon_no;
		} catch (Exception $e) {
			$pdo->rollBack();
			throw $e;
		}
	}

	private function fetchClosedReceipt($external_order_no) {
		$stmt = $this->firebird()->prepare("SELECT FIRST 1 BLKODU, ADISYONNO, DURUMU, KAPANISTARIHI
			FROM ADISYONFIS
			WHERE ADISYONNO = ?
			ORDER BY BLKODU DESC");
		$stmt->execute(array((int)$external_order_no));
		$row = $stmt->fetch(PDO::FETCH_ASSOC);

		return ($row && !empty($row['KAPANISTARIHI'])) ? $row : array();
	}

	private function fetchStock($stock_code) {
		$stmt = $this->firebird()->prepare("SELECT FIRST 1 BLKODU, STOKKODU, STOK_ADI, BIRIMI, KDV_ORANI
			FROM STOK
			WHERE STOKKODU = ?");
		$stmt->execute(array($stock_code));
		$row = $stmt->fetch(PDO::FETCH_ASSOC);

		return $row ? $row : array();
	}

	private function firebird() {
		if ($this->pdo instanceof PDO) {
			return $this->pdo;
		}

		if (!extension_loaded('pdo_firebird')) {
			throw new Exception('pdo_firebird PHP eklentisi aktif degil.');
		}

		$fb = $this->config['firebird'];
		$host = !empty($fb['host']) ? trim((string)$fb['host']) : 'localhost';
		$port = !empty($fb['port']) ? trim((string)$fb['port']) : '3050';
		$path = !empty($fb['path']) ? trim((string)$fb['path']) : '';
		$charset = !empty($fb['charset']) ? trim((string)$fb['charset']) : 'WIN1254';

		if ($path === '') {
			throw new Exception('Firebird DB yolu bos.');
		}

		$dbname = $host !== '' ? $host . ($port !== '' ? '/' . $port : '') . ':' . $path : $path;
		$this->pdo = new PDO('firebird:dbname=' . $dbname . ';charset=' . $charset, (string)$fb['user'], (string)$fb['pass']);
		$this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

		return $this->pdo;
	}

	private function request($route, array $query = array(), array $post = array()) {
		$query['route'] = $route;
		$query['token'] = (string)$this->get('bridge_token', '');

		$url = rtrim((string)$this->get('site_base_url', ''), '/') . '/index.php?' . http_build_query($query);
		$ch = curl_init($url);

		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
		curl_setopt($ch, CURLOPT_TIMEOUT, 40);

		if ($post) {
			$post['token'] = (string)$this->get('bridge_token', '');
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post));
		}

		$body = curl_exec($ch);
		$error = curl_error($ch);
		$http_code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);

		if ($body === false || $error) {
			throw new Exception('Canli site istegi basarisiz: ' . $error);
		}

		$data = json_decode($body, true);

		if (!is_array($data)) {
			throw new Exception('Canli site JSON donmedi. HTTP ' . $http_code . ' Body: ' . substr($body, 0, 200));
		}

		return $data;
	}

	private function get($key, $default = null) {
		return array_key_exists($key, $this->config) ? $this->config[$key] : $default;
	}

	private function resolveTable($table_no, $site_table_name = '') {
		$table_no = (int)$table_no;
		$candidates = array();
		$site_table_name = trim((string)$site_table_name);

		if ($site_table_name !== '') {
			$candidates[] = $site_table_name;
		}

		if ($table_no > 0) {
			$candidates[] = $this->formatTableName($table_no);
			$candidates[] = (string)$table_no;
			$candidates[] = 'Masa ' . $table_no;
			$candidates[] = 'MASA ' . $table_no;
		}

		$candidates = array_values(array_unique(array_filter($candidates, 'strlen')));

		foreach ($candidates as $candidate) {
			$stmt = $this->firebird()->prepare("SELECT FIRST 1 BLKODU, MASAADI FROM MASA WHERE MASAADI = ?");
			$stmt->execute(array($candidate));
			$row = $stmt->fetch(PDO::FETCH_ASSOC);

			if ($row && !empty($row['MASAADI'])) {
				return array(
					'id' => (int)$row['BLKODU'],
					'name' => (string)$row['MASAADI']
				);
			}
		}

		$fallback = $this->formatTableName($table_no);
		$this->log('UYARI: Akinsoft MASA eslesmesi bulunamadi. Adaylar: ' . implode(', ', $candidates) . '. Fallback: ' . $fallback);

		return array(
			'id' => 0,
			'name' => $fallback
		);
	}

	private function markTableOccupied(array $table, $masaadi) {
		$pdo = $this->firebird();
		$updated = 0;

		if (!empty($table['id'])) {
			$stmt = $pdo->prepare("UPDATE MASA SET DURUMU = 2, MASAACILIS = CURRENT_TIMESTAMP WHERE BLKODU = ?");
			$stmt->execute(array((int)$table['id']));
			$updated = $stmt->rowCount();
		}

		if (!$updated) {
			$stmt = $pdo->prepare("UPDATE MASA SET DURUMU = 2, MASAACILIS = CURRENT_TIMESTAMP WHERE MASAADI = ?");
			$stmt->execute(array($masaadi));
			$updated = $stmt->rowCount();
		}

		$status = array();

		if (!empty($table['id'])) {
			$stmt = $pdo->prepare("SELECT FIRST 1 BLKODU, MASAADI, DURUMU FROM MASA WHERE BLKODU = ?");
			$stmt->execute(array((int)$table['id']));
			$status = $stmt->fetch(PDO::FETCH_ASSOC);
		}

		if (!$status) {
			$stmt = $pdo->prepare("SELECT FIRST 1 BLKODU, MASAADI, DURUMU FROM MASA WHERE MASAADI = ?");
			$stmt->execute(array($masaadi));
			$status = $stmt->fetch(PDO::FETCH_ASSOC);
		}

		if ($status) {
			$this->log('Masa durumu guncellendi: BLKODU=' . (int)$status['BLKODU'] . ', MASAADI=' . (string)$status['MASAADI'] . ', DURUMU=' . (string)$status['DURUMU'] . ', etkilenen=' . (int)$updated);
		} else {
			$this->log('UYARI: MASA durumu okunamadi. MASAADI=' . (string)$masaadi . ', etkilenen=' . (int)$updated);
		}
	}

	private function formatTableName($table_no) {
		$table_no = (int)$table_no;

		return ($table_no > 0 && $table_no < 10) ? '0' . $table_no : (string)$table_no;
	}

	private function truncate($text, $length) {
		$text = (string)$text;

		if (function_exists('mb_substr')) {
			return mb_substr($text, 0, $length, 'UTF-8');
		}

		return substr($text, 0, $length);
	}

	private function jsonEncodeFirebirdRows(array $rows) {
		$rows = $this->convertFirebirdEncoding($rows);
		$json = json_encode($rows);

		if ($json === false) {
			throw new Exception('Firebird verisi JSON formatina cevrilemedi: ' . json_last_error_msg());
		}

		return $json;
	}

	private function convertFirebirdEncoding($value) {
		if (is_array($value)) {
			$converted = array();

			foreach ($value as $key => $item) {
				$converted[$key] = $this->convertFirebirdEncoding($item);
			}

			return $converted;
		}

		if (!is_string($value)) {
			return $value;
		}

		if (function_exists('mb_check_encoding') && mb_check_encoding($value, 'UTF-8')) {
			return $value;
		}

		if (function_exists('mb_convert_encoding')) {
			return mb_convert_encoding($value, 'UTF-8', 'Windows-1254');
		}

		if (function_exists('iconv')) {
			$converted = iconv('Windows-1254', 'UTF-8//IGNORE', $value);

			if ($converted !== false) {
				return $converted;
			}
		}

		return $value;
	}

	private function message($response) {
		return !empty($response['message']) ? (string)$response['message'] : 'Bilinmeyen cevap';
	}

	private function log($message) {
		$line = '[' . date('Y-m-d H:i:s') . '] ' . $message . PHP_EOL;
		echo $line;
		file_put_contents(__DIR__ . DIRECTORY_SEPARATOR . 'bridge_agent.log', $line, FILE_APPEND);
	}
}
