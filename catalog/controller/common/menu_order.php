<?php
class ControllerCommonMenuOrder extends Controller {
	public function add() {
		$this->jsonStart();
		$this->load->language('common/menu_order');
		$this->load->model('common/menu_order');

		$product_id = isset($this->request->post['product_id']) ? (int)$this->request->post['product_id'] : 0;

		if (!$this->model_common_menu_order->canOrder()) {
			$this->jsonOut(array(
				'success' => false,
				'message' => $this->language->get('text_order_feature_unavailable')
			));
			return;
		}

		$ok = $this->model_common_menu_order->addItem($product_id, 1);

		if (!$ok) {
			$this->jsonOut(array(
				'success' => false,
				'message' => $this->language->get('text_order_add_failed')
			));
			return;
		}

		$this->jsonOut(array(
			'success' => true,
			'message' => $this->language->get('text_order_item_added'),
			'state'   => $this->model_common_menu_order->getCustomerOrderState()
		));
	}
public function requestWaiter() {
	$this->jsonStart();
	$this->load->model('common/menu_order');

	$result = $this->model_common_menu_order->requestWaiter();

	$this->jsonOut($result);
}
	public function update() {
		$this->jsonStart();
		$this->load->model('common/menu_order');

		$product_id = isset($this->request->post['product_id']) ? (int)$this->request->post['product_id'] : 0;
		$quantity   = isset($this->request->post['quantity']) ? (int)$this->request->post['quantity'] : 0;

		$this->model_common_menu_order->updateItem($product_id, $quantity);

		$this->jsonOut(array(
			'success' => true,
			'state'   => $this->model_common_menu_order->getCustomerOrderState()
		));
	}

	public function remove() {
		$this->jsonStart();
		$this->load->model('common/menu_order');

		$product_id = isset($this->request->post['product_id']) ? (int)$this->request->post['product_id'] : 0;

		$this->model_common_menu_order->removeItem($product_id);

		$this->jsonOut(array(
			'success' => true,
			'state'   => $this->model_common_menu_order->getCustomerOrderState()
		));
	}

	public function summary() {
		$this->jsonStart();
		$this->load->model('common/menu_order');

		$this->jsonOut(array(
			'success' => true,
			'state'   => $this->model_common_menu_order->getCustomerOrderState()
		));
	}

	public function active() {
		$this->jsonStart();
		$this->load->model('common/menu_order');

		$this->jsonOut(array(
			'success' => true,
			'state'   => $this->model_common_menu_order->getCustomerOrderState()
		));
	}

	public function submit() {
		$this->jsonStart();
		$this->load->model('common/menu_order');

		$note = isset($this->request->post['note']) ? trim((string)$this->request->post['note']) : '';
		$result = $this->model_common_menu_order->submitOrder($note);

		$this->jsonOut($result);
	}

	public function requestBill() {
		$this->jsonStart();
		$this->load->model('common/menu_order');

		$note = isset($this->request->post['note']) ? trim((string)$this->request->post['note']) : '';
		$result = $this->model_common_menu_order->requestBill($note);

		$this->jsonOut($result);
	}

	public function submitReview() {
		$this->jsonStart();
		$this->load->model('common/menu_order');

		$rating = isset($this->request->post['rating']) ? (int)$this->request->post['rating'] : 0;
		$note = isset($this->request->post['note']) ? trim((string)$this->request->post['note']) : '';
		$close = !empty($this->request->post['close']) ? 1 : 0;

		$this->jsonOut($this->model_common_menu_order->submitReview($rating, $note, $close));
	}

	private function jsonStart() {
		$this->response->addHeader('Content-Type: application/json; charset=utf-8');
	}

	private function jsonOut($data) {
		$this->response->setOutput(json_encode($data, JSON_UNESCAPED_UNICODE));
	}
}
