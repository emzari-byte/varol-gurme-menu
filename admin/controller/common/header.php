<?php
class ControllerCommonHeader extends Controller {
	public function index() {
		$data['title'] = $this->document->getTitle();

		if ($this->request->server['HTTPS']) {
			$data['base'] = HTTPS_SERVER;
		} else {
			$data['base'] = HTTP_SERVER;
		}

		if ($this->request->server['HTTPS']) {
            $server = HTTPS_CATALOG;
        } else {
            $server = HTTP_CATALOG;
        }

        if (is_file(DIR_IMAGE . $this->config->get('config_icon'))) {
			$this->document->addLink($server . 'image/' . $this->config->get('config_icon'), 'icon');
        }

		$data['description'] = $this->document->getDescription();
		$data['keywords'] = $this->document->getKeywords();
		$data['links'] = $this->document->getLinks();
		$data['styles'] = $this->document->getStyles();
		$data['scripts'] = $this->document->getScripts();
		$data['lang'] = $this->language->get('code');
		$data['direction'] = $this->language->get('direction');
		$data['waiter_break_url'] = '';
		$data['waiter_status'] = array('is_waiter' => false, 'on_break' => false);
		$data['active_waiters'] = array();
		$data['waiter_panel_pwa'] = (isset($this->request->get['route']) && $this->request->get['route'] === 'extension/module/waiter_panel');
		$data['admin_brand_logo'] = 'view/image/logo.png';
		$data['pwa_icon'] = '/menu/image/catalog/veranda-logo2.png';

		$this->load->model('extension/module/restaurant_settings');
		$restaurant_settings = $this->model_extension_module_restaurant_settings->getSettings();
		$admin_logo = !empty($restaurant_settings['restaurant_admin_logo']) ? (string)$restaurant_settings['restaurant_admin_logo'] : '';
		$menu_logo = !empty($restaurant_settings['restaurant_menu_logo']) ? (string)$restaurant_settings['restaurant_menu_logo'] : (!empty($restaurant_settings['restaurant_brand_logo']) ? (string)$restaurant_settings['restaurant_brand_logo'] : '');

		if ($admin_logo !== '' && is_file(DIR_IMAGE . $admin_logo)) {
			$data['admin_brand_logo'] = HTTP_CATALOG . 'image/' . $admin_logo;
		}

		if ($menu_logo !== '' && is_file(DIR_IMAGE . $menu_logo)) {
			$data['pwa_icon'] = '/menu/image/' . $menu_logo;
		}

		$this->load->language('common/header');

		$data['text_logged'] = sprintf($this->language->get('text_logged'), $this->user->getUserName());

		if (!isset($this->request->get['user_token']) || !isset($this->session->data['user_token']) || ($this->request->get['user_token'] != $this->session->data['user_token'])) {
			$data['logged'] = '';

			$data['home'] = $this->url->link('common/login', '', true);
		} else {
			$data['logged'] = true;

			$data['home'] = $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true);
			$data['logout'] = $this->url->link('common/logout', 'user_token=' . $this->session->data['user_token'], true);
			$data['profile'] = $this->url->link('common/profile', 'user_token=' . $this->session->data['user_token'], true);
			$data['waiter_break_url'] = $this->url->link('extension/module/waiter_panel/updateBreakStatus', 'user_token=' . $this->session->data['user_token'], true);

			$this->load->model('user/user');

			$this->load->model('tool/image');
			$this->load->model('extension/module/waiter_panel');

			$user_info = $this->model_user_user->getUser($this->user->getId());

			if ($user_info) {
				$data['firstname'] = $user_info['firstname'];
				$data['lastname'] = $user_info['lastname'];
				$data['username']  = $user_info['username'];
				$data['user_group'] = $user_info['user_group'];

				if (is_file(DIR_IMAGE . $user_info['image'])) {
					$data['image'] = $this->model_tool_image->resize($user_info['image'], 45, 45);
				} else {
					$data['image'] = $this->model_tool_image->resize('profile.png', 45, 45);
				}
			} else {
				$data['firstname'] = '';
				$data['lastname'] = '';
				$data['user_group'] = '';
				$data['image'] = '';
			}

			$current_user_id = (int)$this->user->getId();
			$data['waiter_status'] = $this->model_extension_module_waiter_panel->getCurrentWaiterStatus($current_user_id);
			$data['active_waiters'] = $this->model_extension_module_waiter_panel->getActiveWaiterDelegates($current_user_id);

			// Online Stores
			$data['stores'] = array();

			$data['stores'][] = array(
				'name' => $this->config->get('config_name'),
				'href' => HTTP_CATALOG
			);

			$this->load->model('setting/store');

			$results = $this->model_setting_store->getStores();

			foreach ($results as $result) {
				$data['stores'][] = array(
					'name' => $result['name'],
					'href' => $result['url']
				);
			}
		}

		return $this->load->view('common/header', $data);
	}
}
