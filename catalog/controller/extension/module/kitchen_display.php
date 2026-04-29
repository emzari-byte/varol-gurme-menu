<?php
class ControllerExtensionModuleKitchenDisplay extends Controller {
	public function index() {
		if (!$this->user->hasPermission('access', 'extension/module/kitchen_display')) {
			$this->response->redirect($this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true));
			return;
		}

		$this->document->setTitle('Mutfak Ekranı');
		$this->document->addStyle(HTTPS_CATALOG . 'css/kitchen-display.css?v=1');

		$this->load->model('extension/module/kitchen_display');

		$data['breadcrumbs'] = array(
			array(
				'text' => 'Ana Sayfa',
				'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
			),
			array(
				'text' => 'Mutfak Ekranı',
				'href' => $this->url->link('extension/module/kitchen_display', 'user_token=' . $this->session->data['user_token'], true)
			)
		);

		$data['orders'] = $this->model_extension_module_kitchen_display->getKitchenOrders();

		$data['refresh_url'] = $this->url->link('extension/module/kitchen_display/refresh', 'user_token=' . $this->session->data['user_token'], true);
		$data['status_url']  = $this->url->link('extension/module/kitchen_display/updateStatus', 'user_token=' . $this->session->data['user_token'], true);

		$data['header']      = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer']      = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/module/kitchen_display', $data));
	}

	public function refresh() {
		$json = array();

		if (!$this->user->hasPermission('access', 'extension/module/kitchen_display')) {
			$json['success'] = false;
			$json['message'] = 'Yetki yok';
			$json['orders'] = array();
		} else {
			$this->load->model('extension/module/kitchen_display');

			$json['success'] = true;
			$json['orders'] = $this->model_extension_module_kitchen_display->getKitchenOrders();
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json, JSON_UNESCAPED_UNICODE));
	}

	public function updateStatus() {

$json=[];

$restaurant_order_id=
isset($this->request->post['restaurant_order_id'])
?(int)$this->request->post['restaurant_order_id']
:0;

$service_status=
isset($this->request->post['service_status'])
?trim($this->request->post['service_status'])
:'';

if(!$restaurant_order_id){
$json['success']=false;
$json['message']='order yok';
}else{

$this->db->query("
UPDATE `" . DB_PREFIX . "restaurant_order`
SET service_status='ready_for_service',
date_modified=NOW()
WHERE restaurant_order_id='".(int)$restaurant_order_id."'
");

$json['success']=true;
$json['message']='OK';
}

$this->response->addHeader(
'Content-Type: application/json'
);

$this->response->setOutput(
json_encode($json)
);

}
}