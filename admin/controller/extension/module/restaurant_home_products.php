<?php
class ControllerExtensionModuleRestaurantHomeProducts extends Controller {
	private $error = array();

	public function index() {
		$this->document->setTitle('Ana Sayfa Ürünler');

		$this->load->model('extension/module/restaurant_home_products');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			$sections = isset($this->request->post['sections']) ? $this->request->post['sections'] : array();

			$this->model_extension_module_restaurant_home_products->saveSections($sections);

			$this->session->data['success'] = 'Ana sayfa ürünleri kaydedildi.';

			$this->response->redirect($this->url->link('extension/module/restaurant_home_products', 'user_token=' . $this->session->data['user_token'], true));
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
				'text' => 'Ana Sayfa Ürünler',
				'href' => $this->url->link('extension/module/restaurant_home_products', 'user_token=' . $this->session->data['user_token'], true)
			)
		);

		$data['action'] = $this->url->link('extension/module/restaurant_home_products', 'user_token=' . $this->session->data['user_token'], true);
		$data['cancel'] = $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true);
		$data['user_token'] = $this->session->data['user_token'];
		$data['sections'] = $this->model_extension_module_restaurant_home_products->getSections();
		$this->load->model('localisation/language');
		$data['languages'] = $this->model_localisation_language->getLanguages();

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/module/restaurant_home_products', $data));
	}

	protected function validate() {
		if (
			!$this->user->hasPermission('modify', 'extension/module/restaurant_home_products') &&
			!$this->user->hasPermission('modify', 'extension/module/restaurant_settings') &&
			!$this->user->hasPermission('modify', 'catalog/product')
		) {
			$this->error['warning'] = 'Bu sayfayı değiştirme yetkiniz yok.';
		}

		return !$this->error;
	}
}
