<?php
class ModelExtensionModuleAkinsoft extends Model {
	public function testConnection($settings) {
		$mode = !empty($settings['restaurant_akinsoft_mode']) ? $settings['restaurant_akinsoft_mode'] : 'local_firebird';

		if ($mode === 'bridge_agent') {
			return $this->testBridge($settings);
		}

		return $this->testLocalFirebird($settings);
	}

	public function syncTables($settings) {
		if (!empty($settings['restaurant_akinsoft_mode']) && $settings['restaurant_akinsoft_mode'] === 'bridge_agent') {
			return $this->bridgeOnlyAction('Masa senkronu');
		}

		try {
			$pdo = $this->connectLocalFirebird($settings);
			$rows = $pdo->query("SELECT BLKODU, MASAADI, MASAGRUBU, KACKISILIK, GIZLI FROM MASA ORDER BY BLKODU ASC")->fetchAll(PDO::FETCH_ASSOC);
			$source_table_numbers = array();
			$inserted = 0;
			$updated = 0;

			foreach ($rows as $row) {
				$table_no = $this->parseTableNo($row['MASAADI'], $row['BLKODU']);
				$name = trim((string)$row['MASAADI']);
				$area = trim((string)$row['MASAGRUBU']);
				$capacity = !empty($row['KACKISILIK']) ? (int)$row['KACKISILIK'] : 4;
				$status = !empty($row['GIZLI']) ? 0 : 1;

				if ($name === '') {
					$name = 'Masa ' . $table_no;
				}

				if ($area === '') {
					$area = 'Salon';
				}

				$source_table_numbers[] = $table_no;

				$existing = $this->db->query("SELECT table_id, qr_token FROM `" . DB_PREFIX . "restaurant_table`
					WHERE table_no = '" . (int)$table_no . "'
					LIMIT 1");

				if ($existing->num_rows) {
					$this->db->query("UPDATE `" . DB_PREFIX . "restaurant_table`
						SET name = '" . $this->db->escape($name) . "',
							area = '" . $this->db->escape($area) . "',
							capacity = '" . (int)$capacity . "',
							sort_order = '" . (int)$table_no . "',
							status = '" . (int)$status . "',
							date_modified = NOW()
						WHERE table_id = '" . (int)$existing->row['table_id'] . "'");
					$updated++;
				} else {
					$qr_token = bin2hex(random_bytes(8));

					$this->db->query("INSERT INTO `" . DB_PREFIX . "restaurant_table`
						SET table_no = '" . (int)$table_no . "',
							name = '" . $this->db->escape($name) . "',
							capacity = '" . (int)$capacity . "',
							area = '" . $this->db->escape($area) . "',
							sort_order = '" . (int)$table_no . "',
							status = '" . (int)$status . "',
							qr_token = '" . $this->db->escape($qr_token) . "',
							date_added = NOW(),
							date_modified = NOW()");

					$table_id = (int)$this->db->getLastId();

					$this->db->query("INSERT IGNORE INTO `" . DB_PREFIX . "restaurant_table_status`
						SET table_id = '" . $table_id . "',
							service_status = 'empty',
							active_order_count = '0',
							total_amount = '0.0000',
							date_modified = NOW()");

					$inserted++;
				}
			}

			if ($source_table_numbers) {
				$this->db->query("UPDATE `" . DB_PREFIX . "restaurant_table`
					SET status = '0',
						date_modified = NOW()
					WHERE table_no NOT IN (" . implode(',', array_map('intval', $source_table_numbers)) . ")");
			}

			return array(
				'success' => true,
				'message' => 'Masa senkronu tamamlandi. Akinsoft masa: ' . count($rows) . ', eklenen: ' . $inserted . ', guncellenen: ' . $updated
			);
		} catch (Exception $e) {
			return array(
				'success' => false,
				'message' => 'Masa senkronu basarisiz: ' . $e->getMessage()
			);
		}
	}

	public function syncProductPrices($settings) {
		if (!empty($settings['restaurant_akinsoft_mode']) && $settings['restaurant_akinsoft_mode'] === 'bridge_agent') {
			return $this->bridgeOnlyAction('Fiyat senkronu');
		}

		try {
			$this->installProductSyncTable();

			$pdo = $this->connectLocalFirebird($settings);
			$rows = $pdo->query("SELECT s.STOKKODU, f.FIYATI
				FROM STOK s
				INNER JOIN STOK_FIYAT f ON (f.BLSTKODU = s.BLKODU)
				WHERE s.STOKKODU IS NOT NULL
					AND s.STOKKODU <> ''
					AND f.ALIS_SATIS = 2
					AND f.FIYAT_NO = 1
					AND f.FIYATI IS NOT NULL")->fetchAll(PDO::FETCH_ASSOC);
			$prices = array();

			foreach ($rows as $row) {
				$stock_code = trim((string)$row['STOKKODU']);

				if ($stock_code !== '') {
					$prices[$stock_code] = (float)$row['FIYATI'];
				}
			}

			$matched = 0;
			$missing = 0;
			$products = $this->db->query("SELECT product_id, model, price FROM `" . DB_PREFIX . "product`
				WHERE model IS NOT NULL AND model <> ''
				ORDER BY product_id ASC")->rows;

			$this->db->query("TRUNCATE TABLE `" . DB_PREFIX . "restaurant_akinsoft_product_sync`");

			foreach ($products as $product) {
				$stock_code = trim((string)$product['model']);

				if ($stock_code === '') {
					continue;
				}

				if (isset($prices[$stock_code])) {
					$price = (float)$prices[$stock_code];

					$this->db->query("UPDATE `" . DB_PREFIX . "product`
						SET price = '" . (float)$price . "',
							date_modified = NOW()
						WHERE model = '" . $this->db->escape($stock_code) . "'");

					$this->db->query("INSERT INTO `" . DB_PREFIX . "restaurant_akinsoft_product_sync`
						SET product_id = '" . (int)$product['product_id'] . "',
							model = '" . $this->db->escape($stock_code) . "',
							status = 'matched',
							akinsoft_price = '" . (float)$price . "',
							old_price = '" . (float)$product['price'] . "',
							date_synced = NOW()");

					$matched++;
				} else {
					$this->db->query("INSERT INTO `" . DB_PREFIX . "restaurant_akinsoft_product_sync`
						SET product_id = '" . (int)$product['product_id'] . "',
							model = '" . $this->db->escape($stock_code) . "',
							status = 'missing',
							akinsoft_price = NULL,
							old_price = '" . (float)$product['price'] . "',
							date_synced = NOW()");

					$missing++;
				}
			}

			return array(
				'success' => true,
				'message' => 'Fiyat senkronu tamamlandi. Eslesen urun: ' . $matched . ', OpenCartta bulunmayan stok kodu: ' . $missing
			);
		} catch (Exception $e) {
			return array(
				'success' => false,
				'message' => 'Fiyat senkronu basarisiz: ' . $e->getMessage()
			);
		}
	}

	public function exportRestaurantOrder($settings, $restaurant_order_id) {
		$restaurant_order_id = (int)$restaurant_order_id;

		if (!$restaurant_order_id) {
			return array(
				'success' => false,
				'message' => 'Siparis numarasi eksik.'
			);
		}

		$order_query = $this->db->query("SELECT ro.*, rt.table_no, rt.name AS table_name
			FROM `" . DB_PREFIX . "restaurant_order` ro
			LEFT JOIN `" . DB_PREFIX . "restaurant_table` rt ON (rt.table_id = ro.table_id)
			WHERE ro.restaurant_order_id = '" . $restaurant_order_id . "'
			LIMIT 1");

		if (!$order_query->num_rows) {
			return array(
				'success' => false,
				'message' => 'Siparis bulunamadi.'
			);
		}

		$order = $order_query->row;

		if (!empty($order['external_order_no']) && $order['integration_status'] === 'sent') {
			return array(
				'success' => true,
				'message' => 'Siparis zaten Akinsofta gonderilmis.',
				'external_order_no' => $order['external_order_no']
			);
		}

		$products = $this->db->query("SELECT rop.*, p.model
			FROM `" . DB_PREFIX . "restaurant_order_product` rop
			LEFT JOIN `" . DB_PREFIX . "product` p ON (p.product_id = rop.product_id)
			WHERE rop.restaurant_order_id = '" . $restaurant_order_id . "'
			ORDER BY rop.restaurant_order_product_id ASC")->rows;

		if (!$products) {
			return $this->markRestaurantOrderExportFailed($restaurant_order_id, 'Akinsoft aktarimi basarisiz: sipariste urun yok.');
		}

		try {
			$pdo = $this->connectLocalFirebird($settings);
			$pdo->beginTransaction();

			$table_no = !empty($order['table_no']) ? (int)$order['table_no'] : (int)$order['table_id'];
			$masaadi = $this->formatAkinsoftTableName($table_no);
			$total = 0.0;
			$prepared_products = array();

			foreach ($products as $product) {
				$stock_code = trim((string)$product['model']);

				if ($stock_code === '') {
					throw new Exception('OpenCart urun stok kodu bos: ' . $product['name']);
				}

				$stock = $this->fetchAkinsoftStock($pdo, $stock_code);

				if (!$stock) {
					throw new Exception('Akinsoft stok bulunamadi: ' . $stock_code . ' - ' . $product['name']);
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
					'name' => !empty($stock['STOK_ADI']) ? $stock['STOK_ADI'] : $product['name'],
					'quantity' => $quantity,
					'price' => $price,
					'total' => $row_total
				);
			}

			$fis_id = (int)$pdo->query("SELECT GEN_ID(ADISYONFIS_GEN, 1) FROM RDB\$DATABASE")->fetchColumn();
			$adisyon_no = (int)$pdo->query("SELECT GEN_ID(ADISYONNO_GEN, 1) FROM RDB\$DATABASE")->fetchColumn();
			$description = 'QR Menu Siparis #' . $restaurant_order_id;

			if (!empty($order['customer_note'])) {
				$description .= ' - ' . $order['customer_note'];
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
				mb_substr($description, 0, 400, 'UTF-8'),
				'OPENCART-' . $restaurant_order_id
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
					mb_substr($prepared['name'], 0, 250, 'UTF-8'),
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

			$fis_total_update = $pdo->prepare("UPDATE ADISYONFIS
				SET ARATUTAR = ?,
					TOPLAMTUTAR = ?
				WHERE BLKODU = ?");
			$fis_total_update->execute(array(
				$total,
				$total,
				$fis_id
			));

			$masa_update = $pdo->prepare("UPDATE MASA
				SET DURUMU = 2,
					MASAACILIS = CURRENT_TIMESTAMP
				WHERE MASAADI = ?");
			$masa_update->execute(array($masaadi));

			$pdo->commit();

			$this->db->query("UPDATE `" . DB_PREFIX . "restaurant_order`
				SET integration_status = 'sent',
					external_order_no = '" . $this->db->escape((string)$adisyon_no) . "',
					integration_message = '" . $this->db->escape('Akinsoft adisyon #' . $adisyon_no . ' olusturuldu.') . "',
					integration_date = NOW()
				WHERE restaurant_order_id = '" . $restaurant_order_id . "'");

			return array(
				'success' => true,
				'message' => 'Akinsoft adisyon #' . $adisyon_no . ' olusturuldu.',
				'external_order_no' => (string)$adisyon_no
			);
		} catch (Exception $e) {
			if (isset($pdo) && $pdo->inTransaction()) {
				$pdo->rollBack();
			}

			return $this->markRestaurantOrderExportFailed($restaurant_order_id, 'Akinsoft aktarimi basarisiz: ' . $e->getMessage());
		}
	}

	public function syncClosedRestaurantOrders($settings) {
		try {
			$orders = $this->db->query("SELECT restaurant_order_id, table_id, external_order_no
				FROM `" . DB_PREFIX . "restaurant_order`
				WHERE integration_status = 'sent'
				AND external_order_no IS NOT NULL
				AND external_order_no <> ''
				AND service_status IN ('waiting_order','in_kitchen','ready_for_service','out_for_service','served')
				ORDER BY restaurant_order_id ASC")->rows;

			if (!$orders) {
				return array(
					'success' => true,
					'closed' => 0,
					'message' => 'Kapanan Akinsoft adisyonu yok.'
				);
			}

			$pdo = $this->connectLocalFirebird($settings);
			$closed = 0;

			foreach ($orders as $order) {
				$external_order_no = trim((string)$order['external_order_no']);

				if ($external_order_no === '') {
					continue;
				}

				$stmt = $pdo->prepare("SELECT FIRST 1 BLKODU, ADISYONNO, DURUMU, KAPANISTARIHI
					FROM ADISYONFIS
					WHERE ADISYONNO = ?
					ORDER BY BLKODU DESC");
				$stmt->execute(array((int)$external_order_no));
				$fis = $stmt->fetch(PDO::FETCH_ASSOC);

				if (!$fis || empty($fis['KAPANISTARIHI'])) {
					continue;
				}

				$this->markOpenCartOrderPaidFromAkinsoft(
					(int)$order['restaurant_order_id'],
					(int)$order['table_id'],
					(int)$fis['BLKODU'],
					(string)$fis['KAPANISTARIHI']
				);

				$closed++;
			}

			return array(
				'success' => true,
				'closed' => $closed,
				'message' => $closed ? 'Akinsoft kapanan adisyon senkronu: ' . $closed : 'Kapanan Akinsoft adisyonu yok.'
			);
		} catch (Exception $e) {
			return array(
				'success' => false,
				'closed' => 0,
				'message' => 'Akinsoft kapanis senkronu basarisiz: ' . $e->getMessage()
			);
		}
	}

	private function testLocalFirebird($settings) {
		try {
			$pdo = $this->connectLocalFirebird($settings);

			$table_count = (int)$pdo->query("SELECT COUNT(*) FROM RDB\$RELATIONS WHERE COALESCE(RDB\$SYSTEM_FLAG, 0) = 0")->fetchColumn();
			$masa_count = $this->fetchCount($pdo, 'MASA');
			$stok_count = $this->fetchCount($pdo, 'STOK');
			$adisyon_count = $this->fetchCount($pdo, 'ADISYONFIS');

			return array(
				'success' => true,
				'message' => 'Akinsoft Firebird baglantisi basarili. Tablo: ' . $table_count . ', Masa: ' . $masa_count . ', Stok: ' . $stok_count . ', Adisyon: ' . $adisyon_count
			);
		} catch (Exception $e) {
			return array(
				'success' => false,
				'message' => 'Firebird baglantisi basarisiz: ' . $e->getMessage()
			);
		}
	}

	private function markRestaurantOrderExportFailed($restaurant_order_id, $message) {
		$this->db->query("UPDATE `" . DB_PREFIX . "restaurant_order`
			SET integration_status = 'failed',
				integration_message = '" . $this->db->escape($message) . "',
				integration_date = NOW()
			WHERE restaurant_order_id = '" . (int)$restaurant_order_id . "'");

		return array(
			'success' => false,
			'message' => $message
		);
	}

	private function markOpenCartOrderPaidFromAkinsoft($restaurant_order_id, $table_id, $fis_id, $closed_at) {
		$restaurant_order_id = (int)$restaurant_order_id;
		$table_id = (int)$table_id;

		if (!$restaurant_order_id || !$table_id) {
			return false;
		}

		$order_query = $this->db->query("SELECT service_status
			FROM `" . DB_PREFIX . "restaurant_order`
			WHERE restaurant_order_id = '" . $restaurant_order_id . "'
			LIMIT 1");

		if (!$order_query->num_rows || $order_query->row['service_status'] === 'paid') {
			return false;
		}

		$old_status = $order_query->row['service_status'];
		$message = 'Akinsoft adisyon kapandi. Fis #' . (int)$fis_id . ' - ' . $closed_at;

		$this->db->query("UPDATE `" . DB_PREFIX . "restaurant_order`
			SET service_status = 'paid',
				is_paid = '1',
				integration_message = '" . $this->db->escape($message) . "',
				integration_date = NOW(),
				date_modified = NOW()
			WHERE restaurant_order_id = '" . $restaurant_order_id . "'");

		$this->db->query("INSERT INTO `" . DB_PREFIX . "restaurant_order_history`
			SET restaurant_order_id = '" . $restaurant_order_id . "',
				old_status = '" . $this->db->escape($old_status) . "',
				new_status = 'paid',
				user_id = '0',
				comment = '" . $this->db->escape($message) . "',
				date_added = NOW()");

		$this->db->query("UPDATE `" . DB_PREFIX . "restaurant_call`
			SET status = 'closed', date_modified = NOW()
			WHERE table_id = '" . $table_id . "'
			AND call_type IN ('bill_request','waiter_call')
			AND status IN ('new','seen')");

		$this->db->query("UPDATE `" . DB_PREFIX . "restaurant_table_status`
			SET service_status = 'empty',
				active_order_count = '0',
				total_amount = '0.0000',
				active_session_token = NULL,
				date_modified = NOW()
			WHERE table_id = '" . $table_id . "'");

		return true;
	}

	private function fetchAkinsoftStock($pdo, $stock_code) {
		$stmt = $pdo->prepare("SELECT FIRST 1 BLKODU, STOKKODU, STOK_ADI, BIRIMI, KDV_ORANI
			FROM STOK
			WHERE STOKKODU = ?");
		$stmt->execute(array($stock_code));
		$row = $stmt->fetch(PDO::FETCH_ASSOC);

		return $row ? $row : array();
	}

	private function formatAkinsoftTableName($table_no) {
		$table_no = (int)$table_no;

		if ($table_no > 0 && $table_no < 10) {
			return '0' . $table_no;
		}

		return (string)$table_no;
	}

	private function installProductSyncTable() {
		$this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "restaurant_akinsoft_product_sync` (
			`sync_id` int(11) NOT NULL AUTO_INCREMENT,
			`product_id` int(11) NOT NULL,
			`model` varchar(64) NOT NULL,
			`status` varchar(16) NOT NULL,
			`akinsoft_price` decimal(15,4) NULL,
			`old_price` decimal(15,4) NULL,
			`date_synced` datetime NOT NULL,
			PRIMARY KEY (`sync_id`),
			KEY `product_id` (`product_id`),
			KEY `model` (`model`),
			KEY `status` (`status`)
		) ENGINE=MyISAM DEFAULT CHARSET=utf8");
	}

	private function connectLocalFirebird($settings) {
		if (!extension_loaded('pdo_firebird')) {
			throw new Exception('pdo_firebird PHP eklentisi aktif degil. XAMPP php.ini icinde extension=pdo_firebird acilmali ve Apache yeniden baslatilmali.');
		}

		$host = !empty($settings['restaurant_akinsoft_host']) ? trim((string)$settings['restaurant_akinsoft_host']) : 'localhost';
		$port = !empty($settings['restaurant_akinsoft_port']) ? trim((string)$settings['restaurant_akinsoft_port']) : '3050';
		$path = !empty($settings['restaurant_akinsoft_db_path']) ? trim((string)$settings['restaurant_akinsoft_db_path']) : '';
		$charset = !empty($settings['restaurant_akinsoft_charset']) ? trim((string)$settings['restaurant_akinsoft_charset']) : 'WIN1254';
		$user = !empty($settings['restaurant_akinsoft_user']) ? trim((string)$settings['restaurant_akinsoft_user']) : 'SYSDBA';
		$pass = isset($settings['restaurant_akinsoft_pass']) ? (string)$settings['restaurant_akinsoft_pass'] : '';

		if ($path === '') {
			throw new Exception('Firebird veritabani yolu bos.');
		}

		$dbname = $path;

		if ($host !== '') {
			$dbname = $host . ($port !== '' ? '/' . $port : '') . ':' . $path;
		}

		$pdo = new PDO('firebird:dbname=' . $dbname . ';charset=' . $charset, $user, $pass);
		$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

		return $pdo;
	}

	private function parseTableNo($masaadi, $fallback) {
		if (preg_match('/\d+/', (string)$masaadi, $match)) {
			return max(1, (int)$match[0]);
		}

		return max(1, (int)$fallback);
	}

	private function fetchCount($pdo, $table) {
		try {
			return (int)$pdo->query('SELECT COUNT(*) FROM ' . $table)->fetchColumn();
		} catch (Exception $e) {
			return 0;
		}
	}

	private function testBridge($settings) {
		$url = !empty($settings['restaurant_akinsoft_bridge_url']) ? trim((string)$settings['restaurant_akinsoft_bridge_url']) : '';
		$token = !empty($settings['restaurant_akinsoft_bridge_token']) ? trim((string)$settings['restaurant_akinsoft_bridge_token']) : '';

		if ($url === '') {
			return array(
				'success' => false,
				'message' => 'Canli site URL bos. Ornek: https://varolveranda.com/deneme/menu/'
			);
		}

		if ($token === '') {
			return array(
				'success' => false,
				'message' => 'Bridge token bos. Restoran bilgisayarindaki agent bu token ile canli sisteme baglanacak.'
			);
		}

		return array(
			'success' => true,
			'message' => 'Bridge API hazir. Agent bekleyen siparisleri ' . rtrim($url, '/') . '/index.php?route=extension/module/akinsoft_bridge/pending adresinden cekecek.'
		);
	}

	private function bridgeOnlyAction($name) {
		return array(
			'success' => false,
			'message' => $name . ' canli sunucuda degil, Akinsoft/Firebird kurulu PC uzerindeki Bridge Agent ile calistirilmalidir. Canli sunucuda pdo_firebird gerekmez ve Firebird portu acilmaz.'
		);
	}
}
