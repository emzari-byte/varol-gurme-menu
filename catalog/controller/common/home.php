<?php
class ControllerCommonHome extends Controller {
	public function index() {
		$this->document->setTitle($this->config->get('config_meta_title'));
		$this->document->setDescription($this->config->get('config_meta_description'));
		$this->document->setKeywords($this->config->get('config_meta_keyword'));

		if (isset($this->request->get['route'])) {
			$this->document->addLink($this->config->get('config_url'), 'canonical');
		}

		$data['title'] = $this->config->get('config_meta_title');
		$data['description'] = $this->config->get('config_meta_description');
		$data['keywords'] = $this->config->get('config_meta_keyword');

		if ($this->request->server['HTTPS']) {
			$server = $this->config->get('config_ssl');
		} else {
			$server = $this->config->get('config_url');
		}

		if (is_file(DIR_IMAGE . $this->config->get('config_logo'))) {
			$data['logo'] = $server . 'image/' . $this->config->get('config_logo');
		} else {
			$data['logo'] = '';
		}

		$this->load->language('common/home');

		if (!empty($this->session->data['language'])) {
			$data['language_code'] = $this->session->data['language'];
		} else {
			$data['language_code'] = $this->config->get('config_language');
		}

		$data['text_html_lang'] = $this->language->get('text_html_lang');
		$data['text_open_air_menu'] = $this->language->get('text_open_air_menu');
		$data['text_select_language'] = $this->language->get('text_select_language');
		$data['text_english_menu'] = $this->language->get('text_english_menu');
		$data['text_open_menu'] = $this->language->get('text_open_menu');
		$data['text_turkish_menu'] = $this->language->get('text_turkish_menu');
		$data['text_open_turkish_menu'] = $this->language->get('text_open_turkish_menu');
		$data['text_follow_us'] = $this->language->get('text_follow_us');

		$this->load->model('extension/module/restaurant_tables');

		$qr = isset($this->request->get['qr']) ? trim((string)$this->request->get['qr']) : '';

		if ($qr !== '') {
			$table_info = $this->model_extension_module_restaurant_tables->getTableByQrToken($qr);

			if ($table_info && !empty($table_info['status'])) {
				$this->session->data['menu_qr_token'] = $qr;
				$this->session->data['menu_table_id'] = (int)$table_info['table_id'];
				$this->session->data['menu_table_no'] = (int)$table_info['table_no'];
				$this->session->data['menu_table_name'] = $table_info['name'];
				$this->session->data['table_session_token'] = $this->getTableSessionToken((int)$table_info['table_id']);
			} else {
				unset($this->session->data['menu_qr_token']);
				unset($this->session->data['menu_table_id']);
				unset($this->session->data['menu_table_no']);
				unset($this->session->data['menu_table_name']);
				unset($this->session->data['table_session_token']);
			}
		}

		$data['qr'] = !empty($this->session->data['menu_qr_token']) ? $this->session->data['menu_qr_token'] : '';
		$data['table_id'] = !empty($this->session->data['menu_table_id']) ? (int)$this->session->data['menu_table_id'] : 0;
		$data['table_no'] = !empty($this->session->data['menu_table_no']) ? (int)$this->session->data['menu_table_no'] : 0;
		$data['table_name'] = !empty($this->session->data['menu_table_name']) ? $this->session->data['menu_table_name'] : '';

		$qr_param = $data['qr'] ? 'qr=' . urlencode($data['qr']) : '';

		$data['menutr']  = $this->url->link('common/home/setLang', 'lang=tr-tr' . ($qr_param ? '&' . $qr_param : ''), true);
		$data['menueng'] = $this->url->link('common/home/setLang', 'lang=en-gb' . ($qr_param ? '&' . $qr_param : ''), true);
		$data['menu'] = $this->url->link('common/menu', $qr_param, true);
		$data['serv'] = HTTPS_SERVER;

		$this->response->setOutput($this->load->view('common/home', $data));
	}

	public function setLang() {
		$qr = isset($this->request->get['qr']) ? trim((string)$this->request->get['qr']) : '';

		if (!empty($this->request->get['lang'])) {
			$lang = $this->request->get['lang'];

			if ($lang === 'tr-tr') {
				$this->session->data['language'] = 'tr-tr';
				$this->session->data['language_id'] = 1;
				setcookie('language', 'tr-tr', time() + 60 * 60 * 24 * 30, '/');
			}

			if ($lang === 'en-gb') {
				$this->session->data['language'] = 'en-gb';
				$this->session->data['language_id'] = 2;
				setcookie('language', 'en-gb', time() + 60 * 60 * 24 * 30, '/');
			}

			$this->response->redirect($this->url->link('common/menu', ($qr ? 'qr=' . urlencode($qr) : ''), true));
			return;
		}

		$this->response->redirect($this->url->link('common/home', ($qr ? 'qr=' . urlencode($qr) : ''), true));
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
}
