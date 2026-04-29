<?php
class ControllerCommonMenuFooter extends Controller {
	public function index() {
		$this->load->language('common/menu_footer');
		$this->load->model('common/restaurant_settings');

		$data['feedback_action'] = $this->url->link('common/menu_feedback', '', true);

		$scheme = (!empty($this->request->server['HTTPS']) && $this->request->server['HTTPS'] !== 'off') ? 'https' : 'http';
		$host = !empty($this->request->server['HTTP_HOST']) ? $this->request->server['HTTP_HOST'] : '';
		$uri = !empty($this->request->server['REQUEST_URI']) ? $this->request->server['REQUEST_URI'] : '';
		$data['current_url'] = $scheme . '://' . $host . $uri;

		$data['success_message'] = '';
		$data['error_message'] = '';

		if (!empty($this->session->data['feedback_success'])) {
			$data['success_message'] = $this->session->data['feedback_success'];
			unset($this->session->data['feedback_success']);
		}

		if (!empty($this->session->data['feedback_error'])) {
			$data['error_message'] = $this->session->data['feedback_error'];
			unset($this->session->data['feedback_error']);
		}

		$data['text_feedback_button'] = $this->language->get('text_feedback_button');
		$data['text_whatsapp_button'] = $this->language->get('text_whatsapp_button');

		$data['text_modal_close'] = $this->language->get('text_modal_close');
		$data['text_modal_title'] = $this->language->get('text_modal_title');
		$data['text_modal_subtitle'] = $this->language->get('text_modal_subtitle');

		$data['text_feedback_type'] = $this->language->get('text_feedback_type');
		$data['text_feedback_type_suggestion'] = $this->language->get('text_feedback_type_suggestion');
		$data['text_feedback_type_complaint'] = $this->language->get('text_feedback_type_complaint');

		$data['text_name'] = $this->language->get('text_name');
		$data['text_phone'] = $this->language->get('text_phone');
		$data['text_message'] = $this->language->get('text_message');

		$data['text_name_placeholder'] = $this->language->get('text_name_placeholder');
		$data['text_phone_placeholder'] = $this->language->get('text_phone_placeholder');
		$data['text_message_placeholder'] = $this->language->get('text_message_placeholder');

		$data['text_char_note'] = $this->language->get('text_char_note');
		$data['text_char_note_valid'] = $this->language->get('text_char_note_valid');

		$data['button_cancel'] = $this->language->get('button_cancel');
		$data['button_submit'] = $this->language->get('button_submit');

		$data['error_required'] = $this->language->get('error_required');
		$data['error_name'] = $this->language->get('error_name');
		$data['error_phone'] = $this->language->get('error_phone');
		$data['error_message_length'] = $this->language->get('error_message_length');
		$data['text_ajax_error'] = $this->language->get('text_ajax_error');

		$whatsapp_phone = preg_replace('/[^0-9]/', '', (string)$this->model_common_restaurant_settings->get('restaurant_whatsapp_phone', '905337843120'));

		if ($whatsapp_phone === '') {
			$whatsapp_phone = '905337843120';
		}

		$data['whatsapp_link'] = 'https://wa.me/' . $whatsapp_phone . '?text=' . rawurlencode($this->language->get('text_whatsapp_message'));

		return $this->load->view('common/menu_footer', $data);
	}
}
