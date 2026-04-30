<?php
class ControllerExtensionModuleRestaurantAllergens extends Controller {
	public function index() {
		$this->document->setTitle('Alerjen Tanimlari');
		$this->load->model('extension/module/restaurant_allergens');
		$this->load->model('tool/image');

		if (isset($this->request->get['delete']) && $this->user->hasPermission('modify', 'extension/module/restaurant_settings')) {
			$this->model_extension_module_restaurant_allergens->deleteAllergen($this->request->get['delete']);
			$this->session->data['success'] = 'Alerjen tanimi silindi.';
			$this->response->redirect($this->url->link('extension/module/restaurant_allergens', 'user_token=' . $this->session->data['user_token'], true));
		}

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->user->hasPermission('modify', 'extension/module/restaurant_settings')) {
			$this->model_extension_module_restaurant_allergens->saveAllergens($this->request->post['allergen'] ?? array());
			$this->session->data['success'] = 'Alerjen tanimlari kaydedildi.';
			$this->response->redirect($this->url->link('extension/module/restaurant_allergens', 'user_token=' . $this->session->data['user_token'], true));
		}

		$data['action'] = $this->url->link('extension/module/restaurant_allergens', 'user_token=' . $this->session->data['user_token'], true);
		$data['success'] = $this->session->data['success'] ?? '';
		unset($this->session->data['success']);
		$data['placeholder'] = $this->model_tool_image->resize('no_image.png', 48, 48);
		$data['allergens'] = array();

		foreach ($this->model_extension_module_restaurant_allergens->getAllergens() as $allergen) {
			$image = !empty($allergen['image']) && is_file(DIR_IMAGE . $allergen['image']) ? $allergen['image'] : 'no_image.png';
			$allergen['thumb'] = $this->model_tool_image->resize($image, 48, 48);
			$allergen['delete'] = $this->url->link('extension/module/restaurant_allergens', 'user_token=' . $this->session->data['user_token'] . '&delete=' . (int)$allergen['allergen_id'], true);
			$data['allergens'][] = $allergen;
		}

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/module/restaurant_allergens', $data));
	}
}
