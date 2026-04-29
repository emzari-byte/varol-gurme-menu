<?php
class ModelExtensionModuleRestaurantActivity extends Model {
	public function getActivities($limit = 80) {
		$limit = max(10, min(300, (int)$limit));
		$rows = array();

		$order_history = $this->db->query("SELECT
				roh.date_added,
				roh.restaurant_order_id,
				roh.old_status,
				roh.new_status,
				roh.user_id,
				roh.comment,
				ro.table_id,
				rt.table_no,
				rt.name AS table_name,
				u.username
			FROM `" . DB_PREFIX . "restaurant_order_history` roh
			LEFT JOIN `" . DB_PREFIX . "restaurant_order` ro ON (ro.restaurant_order_id = roh.restaurant_order_id)
			LEFT JOIN `" . DB_PREFIX . "restaurant_table` rt ON (rt.table_id = ro.table_id)
			LEFT JOIN `" . DB_PREFIX . "user` u ON (u.user_id = roh.user_id)
			ORDER BY roh.date_added DESC
			LIMIT " . $limit)->rows;

		foreach ($order_history as $row) {
			$table_label = !empty($row['table_no']) ? 'Masa ' . $row['table_no'] : '-';

			$rows[] = array(
				'date_added' => $row['date_added'],
				'type' => $this->getStatusLabel($row['new_status']),
				'table' => $table_label,
				'order_id' => (int)$row['restaurant_order_id'],
				'user' => $row['username'] ? $row['username'] : ((int)$row['user_id'] ? 'User #' . (int)$row['user_id'] : 'Müşteri/Sistem'),
				'detail' => trim((string)$row['comment']),
				'raw_status' => $row['new_status']
			);
		}

		$calls = $this->db->query("SELECT
				rc.date_added,
				rc.date_modified,
				rc.call_type,
				rc.status,
				rc.note,
				rc.table_id,
				rt.table_no,
				rt.name AS table_name
			FROM `" . DB_PREFIX . "restaurant_call` rc
			LEFT JOIN `" . DB_PREFIX . "restaurant_table` rt ON (rt.table_id = rc.table_id)
			ORDER BY rc.date_added DESC
			LIMIT " . $limit)->rows;

		foreach ($calls as $row) {
			$rows[] = array(
				'date_added' => $row['date_added'],
				'type' => $row['call_type'] === 'bill_request' ? 'Hesap Talebi' : 'Garson Çağrısı',
				'table' => !empty($row['table_no']) ? 'Masa ' . $row['table_no'] : '-',
				'order_id' => 0,
				'user' => 'Müşteri',
				'detail' => trim((string)$row['note']) . ' / Durum: ' . $row['status'],
				'raw_status' => $row['call_type'] . '_' . $row['status']
			);
		}

		usort($rows, function($a, $b) {
			return strtotime($b['date_added']) <=> strtotime($a['date_added']);
		});

		return array_slice($rows, 0, $limit);
	}

	private function getStatusLabel($status) {
		$map = array(
			'waiting_order' => 'Sipariş Oluşturuldu',
			'in_kitchen' => 'Mutfağa Gönderildi',
			'ready_for_service' => 'Mutfakta Hazır',
			'out_for_service' => 'Servise Çıktı',
			'served' => 'Servis Edildi',
			'paid' => 'Ödeme Alındı',
			'cancelled' => 'İptal',
			'bill_request' => 'Hesap Talebi',
			'bill_request_seen' => 'Hesap Talebi Görüldü',
			'waiter_call' => 'Garson Çağrısı',
			'waiter_call_seen' => 'Garson Çağrısı Görüldü',
			'waiter_break_start' => 'Garson Molaya Çıktı',
			'waiter_break_end' => 'Garson Moladan Döndü'
		);

		return isset($map[$status]) ? $map[$status] : (string)$status;
	}
}
