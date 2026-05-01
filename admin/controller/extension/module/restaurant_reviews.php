<?php
class ControllerExtensionModuleRestaurantReviews extends Controller {
	public function index() {
		if (!$this->user->hasPermission('access', 'extension/module/restaurant_reviews')) {
			$this->response->redirect($this->url->link('error/permission', 'user_token=' . $this->session->data['user_token'], true));
			return;
		}

		$this->document->setTitle('Değerlendirmeler');
		$this->load->model('extension/module/restaurant_reviews');

		$filter = array(
			'date_from' => isset($this->request->get['date_from']) ? trim((string)$this->request->get['date_from']) : '',
			'date_to' => isset($this->request->get['date_to']) ? trim((string)$this->request->get['date_to']) : '',
			'rating' => isset($this->request->get['rating']) ? (int)$this->request->get['rating'] : 0
		);

		$url = '';

		if ($filter['date_from'] !== '') {
			$url .= '&date_from=' . urlencode($filter['date_from']);
		}

		if ($filter['date_to'] !== '') {
			$url .= '&date_to=' . urlencode($filter['date_to']);
		}

		if ($filter['rating']) {
			$url .= '&rating=' . (int)$filter['rating'];
		}

		$data['breadcrumbs'] = array(
			array(
				'text' => 'Ana Sayfa',
				'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
			),
			array(
				'text' => 'Değerlendirmeler',
				'href' => $this->url->link('extension/module/restaurant_reviews', 'user_token=' . $this->session->data['user_token'] . $url, true)
			)
		);

		$data['filter'] = $filter;
		$data['clear'] = $this->url->link('extension/module/restaurant_reviews', 'user_token=' . $this->session->data['user_token'], true);
		$data['action'] = $this->url->link('extension/module/restaurant_reviews', 'user_token=' . $this->session->data['user_token'], true);
		$data['average_all'] = $this->model_extension_module_restaurant_reviews->getAverage(0);
		$data['average_month'] = $this->model_extension_module_restaurant_reviews->getAverage(30);
		$data['average_week'] = $this->model_extension_module_restaurant_reviews->getAverage(7);
		$data['average_today'] = $this->getTodayAverage();
		$data['reviews'] = $this->model_extension_module_restaurant_reviews->getReviews($filter);
		$data['user_token'] = $this->session->data['user_token'];

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/module/restaurant_reviews', $data));
	}

	private function getTodayAverage() {
		$query = $this->db->query("SELECT AVG(rating) AS average_rating, COUNT(*) AS total
			FROM `" . DB_PREFIX . "restaurant_review`
			WHERE rating > 0
			AND DATE(date_added) = CURDATE()");

		return array(
			'average' => $query->row['average_rating'] !== null ? round((float)$query->row['average_rating'], 2) : 0,
			'total' => (int)$query->row['total']
		);
	}
}
