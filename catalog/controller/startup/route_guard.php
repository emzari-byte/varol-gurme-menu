<?php
class ControllerStartupRouteGuard extends Controller {
	private $allowed_routes = array(
		'common/home',
		'common/menu',
		'common/menu_recommendation',
		'common/menu_recomendation',
		'common/menu_recommendations',
		'common/menu_recomendations',
		'common/menu_order',
		'common/menu_feedback',
		'product/category'
	);

	public function index() {
		$method = isset($this->request->server['REQUEST_METHOD']) ? strtoupper($this->request->server['REQUEST_METHOD']) : 'GET';

		if ($method !== 'GET' && $method !== 'HEAD') {
			return;
		}

		$route = isset($this->request->get['route']) ? (string)$this->request->get['route'] : 'common/home';
		$route = preg_replace('/[^a-zA-Z0-9_\/]/', '', $route);

		if ($this->isAllowedRoute($route)) {
			return;
		}

		$this->response->redirect($this->getMenuBaseUrl(), 301);
	}

	private function isAllowedRoute($route) {
		foreach ($this->allowed_routes as $allowed_route) {
			if ($route === $allowed_route || strpos($route, $allowed_route . '/') === 0) {
				return true;
			}
		}

		return false;
	}

	private function getMenuBaseUrl() {
		$base_url = $this->request->server['HTTPS'] ? $this->config->get('config_ssl') : $this->config->get('config_url');
		$base_url = rtrim((string)$base_url, '/') . '/';

		if (!empty($this->request->get['qr'])) {
			return $base_url . '?qr=' . rawurlencode((string)$this->request->get['qr']);
		}

		return $base_url;
	}
}
