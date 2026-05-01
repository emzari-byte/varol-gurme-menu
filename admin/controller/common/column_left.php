<?php
class ControllerCommonColumnLeft extends Controller {
	public function index() {
		if (
			!isset($this->request->get['user_token']) ||
			!isset($this->session->data['user_token']) ||
			$this->request->get['user_token'] != $this->session->data['user_token']
		) {
			return;
		}

		$this->load->language('common/column_left');

		$data['menus'] = array();
		$cashier_only = $this->isCashierOnlyUser();

		if (!$cashier_only) {
			$data['menus'][] = array(
				'id'       => 'menu-dashboard',
				'icon'     => 'fa-dashboard',
				'name'     => 'Kontrol Paneli',
				'href'     => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true),
				'children' => array()
			);
		}

		$operations = array();

		if ($this->getRestaurantAyar('restaurant_waiter_panel', 1) && $this->user->hasPermission('access', 'extension/module/waiter_panel')) {
			$operations[] = array(
				'name'     => 'Garson Paneli',
				'href'     => $this->url->link('extension/module/waiter_panel', 'user_token=' . $this->session->data['user_token'], true),
				'children' => array()
			);
		}

		if ($this->getRestaurantAyar('restaurant_kitchen_panel', 1) && !$this->getRestaurantAyar('restaurant_akinsoft_enabled', 0) && $this->user->hasPermission('access', 'extension/module/kitchen_display')) {
			$operations[] = array(
				'name'     => 'Mutfak Ekranı',
				'href'     => $this->url->link('extension/module/kitchen_display', 'user_token=' . $this->session->data['user_token'], true),
				'children' => array()
			);
		}

		if ($this->getRestaurantAyar('restaurant_cashier_panel', 1) && !$this->getRestaurantAyar('restaurant_akinsoft_enabled', 0) && $this->user->hasPermission('access', 'extension/module/cashier_panel')) {
			$operations[] = array(
				'name'     => 'Kasa Paneli',
				'href'     => $this->url->link('extension/module/cashier_panel', 'user_token=' . $this->session->data['user_token'], true),
				'children' => array()
			);
		}

		if ($operations) {
			$data['menus'][] = array(
				'id'       => 'menu-operations',
				'icon'     => 'fa-bell',
				'name'     => 'Operasyon',
				'href'     => '',
				'children' => $operations
			);
		}

		$management = array();

		if ($this->user->hasPermission('access', 'user/user')) {
			$management[] = array(
				'name'     => 'Kullanıcılar',
				'href'     => $this->url->link('user/user', 'user_token=' . $this->session->data['user_token'], true),
				'children' => array()
			);
		}

		if ($this->user->hasPermission('access', 'marketplace/modification')) {
			$management[] = array(
				'name'     => 'Modifikasyonlar',
				'href'     => $this->url->link('marketplace/modification', 'user_token=' . $this->session->data['user_token'], true),
				'children' => array()
			);
		}

		if ($this->user->hasPermission('access', 'user/user_permission')) {
			$management[] = array(
				'name'     => 'Kullanıcı Grupları',
				'href'     => $this->url->link('user/user_permission', 'user_token=' . $this->session->data['user_token'], true),
				'children' => array()
			);
		}

		if ($management) {
			$data['menus'][] = array(
				'id'       => 'menu-management',
				'icon'     => 'fa-sliders',
				'name'     => 'Yönetim',
				'href'     => '',
				'children' => $management
			);
		}

		$restaurant_menu = array();

		if ($this->user->hasPermission('access', 'catalog/product')) {
			$restaurant_menu[] = array(
				'name'     => 'Ürünler',
				'href'     => $this->url->link('catalog/product', 'user_token=' . $this->session->data['user_token'], true),
				'children' => array()
			);
		}

		if ($this->user->hasPermission('access', 'catalog/category')) {
			$restaurant_menu[] = array(
				'name'     => 'Kategoriler',
				'href'     => $this->url->link('catalog/category', 'user_token=' . $this->session->data['user_token'], true),
				'children' => array()
			);
		}

		if (
			$this->user->hasPermission('access', 'extension/module/restaurant_home_products') ||
			$this->user->hasPermission('access', 'extension/module/restaurant_settings') ||
			$this->user->hasPermission('access', 'catalog/product')
		) {
			$restaurant_menu[] = array(
				'name'     => 'Ana Sayfa Ürünler',
				'href'     => $this->url->link('extension/module/restaurant_home_products', 'user_token=' . $this->session->data['user_token'], true),
				'children' => array()
			);
		}

		if ($restaurant_menu) {
			$data['menus'][] = array(
				'id'       => 'menu-food',
				'icon'     => 'fa-cutlery',
				'name'     => 'Restoran Menü',
				'href'     => '',
				'children' => $restaurant_menu
			);
		}

		$reports = array();

		if ($this->user->hasPermission('access', 'extension/module/restaurant_reviews')) {
			$reports[] = array(
				'name'     => 'Değerlendirme',
				'href'     => $this->url->link('extension/module/restaurant_reviews', 'user_token=' . $this->session->data['user_token'], true),
				'children' => array()
			);
		}

		if ($reports) {
			$data['menus'][] = array(
				'id'       => 'menu-restaurant-reports',
				'icon'     => 'fa-bar-chart',
				'name'     => 'Raporlar',
				'href'     => '',
				'children' => $reports
			);
		}

		if ($this->user->hasPermission('access', 'extension/module/restaurant_settings') || $this->user->hasPermission('access', 'setting/setting')) {
			$data['menus'][] = array(
				'id'       => 'menu-restaurant-settings',
				'icon'     => 'fa-cog',
				'name'     => 'Restoran Ayarları',
				'href'     => $this->url->link('extension/module/restaurant_settings', 'user_token=' . $this->session->data['user_token'], true),
				'children' => array()
			);
		}

		$data['complete_status'] = 0;
		$data['processing_status'] = 0;
		$data['other_status'] = 0;

		return $this->load->view('common/column_left', $data);
	}

	private function getRestaurantAyar($key, $default = 1) {
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

	private function isCashierOnlyUser() {
		if (
			!$this->getRestaurantAyar('restaurant_cashier_panel', 1) ||
			$this->getRestaurantAyar('restaurant_akinsoft_enabled', 0) ||
			!$this->user->hasPermission('access', 'extension/module/cashier_panel')
		) {
			return false;
		}

		$broader_routes = array(
			'extension/module/waiter_panel',
			'extension/module/kitchen_display',
			'extension/module/restaurant_settings',
			'extension/module/restaurant_tables',
			'extension/module/restaurant_waiters',
			'extension/module/restaurant_home_products',
			'extension/module/restaurant_reviews',
			'catalog/product',
			'catalog/category',
			'user/user',
			'user/user_permission',
			'setting/setting'
		);

		foreach ($broader_routes as $route) {
			if ($this->user->hasPermission('access', $route)) {
				return false;
			}
		}

		return true;
	}
}
