<?php
class ControllerExtensionModuleRestaurantProductGroups extends Controller {
	private $error = array();

	public function index() {
		$this->document->setTitle('Ürün Grupları');

		$this->load->model('extension/module/restaurant_product_groups');
		$this->load->model('localisation/language');
		$this->load->model('tool/image');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			$groups = !empty($this->request->post['groups']) ? $this->request->post['groups'] : array();
			$this->model_extension_module_restaurant_product_groups->saveGroups($groups);

			$this->session->data['success'] = 'Ürün grupları kaydedildi.';
			$this->response->redirect($this->url->link('extension/module/restaurant_product_groups', 'user_token=' . $this->session->data['user_token'], true));
		}

		$data['error_warning'] = isset($this->error['warning']) ? $this->error['warning'] : '';
		$data['success'] = isset($this->session->data['success']) ? $this->session->data['success'] : '';
		unset($this->session->data['success']);

		$data['breadcrumbs'] = array(
			array(
				'text' => 'Ana Sayfa',
				'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
			),
			array(
				'text' => 'Ürün Grupları',
				'href' => $this->url->link('extension/module/restaurant_product_groups', 'user_token=' . $this->session->data['user_token'], true)
			)
		);

		$data['action'] = $this->url->link('extension/module/restaurant_product_groups', 'user_token=' . $this->session->data['user_token'], true);
		$data['cancel'] = $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true);
		$data['user_token'] = $this->session->data['user_token'];
		$data['languages'] = $this->model_localisation_language->getLanguages();
		$data['placeholder'] = $this->model_tool_image->resize('no_image.png', 100, 100);

		$data['groups'] = array();

		foreach ($this->model_extension_module_restaurant_product_groups->getGroups() as $group) {
			$image = $group['image'] && is_file(DIR_IMAGE . $group['image']) ? $group['image'] : 'no_image.png';
			$group['thumb'] = $this->model_tool_image->resize($image, 100, 100);
			$data['groups'][] = $group;
		}

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/module/restaurant_product_groups', $data));
	}

	protected function validate() {
		if (
			!$this->user->hasPermission('modify', 'extension/module/restaurant_product_groups') &&
			!$this->user->hasPermission('modify', 'extension/module/restaurant_settings') &&
			!$this->user->hasPermission('modify', 'catalog/product')
		) {
			$this->error['warning'] = 'Bu sayfayı değiştirme yetkiniz yok.';
		}

		return !$this->error;
	}
}
