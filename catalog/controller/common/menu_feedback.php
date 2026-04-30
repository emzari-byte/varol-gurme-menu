<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once dirname(DIR_APPLICATION, 2) . '/PHPMailer/src/Exception.php';
require_once dirname(DIR_APPLICATION, 2) . '/PHPMailer/src/PHPMailer.php';
require_once dirname(DIR_APPLICATION, 2) . '/PHPMailer/src/SMTP.php';

class ControllerCommonMenuFeedback extends Controller {
	public function index() {
		$this->load->language('common/menu_feedback');
		$this->load->model('common/restaurant_settings');

		if ($this->request->server['REQUEST_METHOD'] !== 'POST') {
			$this->response->redirect($this->url->link('common/menu', '', true));
			return;
		}

		$type    = trim((string)($this->request->post['feedback_type'] ?? ''));
		$name    = trim((string)($this->request->post['name'] ?? ''));
		$phone   = trim((string)($this->request->post['phone'] ?? ''));
		$message = trim((string)($this->request->post['message'] ?? ''));
		$pageUrl = trim((string)($this->request->post['page_url'] ?? ''));

		if (!in_array($type, array('oneri', 'sikayet'), true) || $name === '' || $message === '') {
			$this->session->data['feedback_error'] = $this->language->get('error_required');
			$this->response->redirect($pageUrl ?: $this->url->link('common/menu', '', true));
			return;
		}

		if (!preg_match('/^[A-Za-zÇçĞğİıÖöŞşÜü\s]+$/u', $name)) {
			$this->session->data['feedback_error'] = $this->language->get('error_name');
			$this->response->redirect($pageUrl ?: $this->url->link('common/menu', '', true));
			return;
		}

		if (!preg_match('/^[0-9]+$/', $phone)) {
			$this->session->data['feedback_error'] = $this->language->get('error_phone');
			$this->response->redirect($pageUrl ?: $this->url->link('common/menu', '', true));
			return;
		}

		if (mb_strlen($message, 'UTF-8') < 20) {
			$this->session->data['feedback_error'] = $this->language->get('error_message_length');
			$this->response->redirect($pageUrl ?: $this->url->link('common/menu', '', true));
			return;
		}

		$mail_host     = trim((string)$this->model_common_restaurant_settings->get('restaurant_mail_host', 'proxy.uzmanposta.com'));
		$mail_username = trim((string)$this->model_common_restaurant_settings->get('restaurant_mail_username', 'emzari@varoltekstil.com.tr'));
		$mail_password = trim((string)$this->model_common_restaurant_settings->get('restaurant_mail_password', '.Emir190522*'));
		$mail_port     = (int)$this->model_common_restaurant_settings->get('restaurant_mail_port', '465');

		$from_email = trim((string)$this->model_common_restaurant_settings->get('restaurant_mail_from_email', $mail_username));
		$from_name  = trim((string)$this->model_common_restaurant_settings->get('restaurant_mail_from_name', 'Varol Gurme Sikayet & Oneri'));

		if ($mail_port <= 0) {
			$mail_port = 465;
		}

		if ($from_email === '' || !filter_var($from_email, FILTER_VALIDATE_EMAIL)) {
			$from_email = $mail_username;
		}

		$to_email = trim((string)$this->model_common_restaurant_settings->get('restaurant_feedback_email', 'can@varoltekstil.com.tr'));
		if ($to_email === '' || !filter_var($to_email, FILTER_VALIDATE_EMAIL)) {
			$to_email = 'can@varoltekstil.com.tr';
		}
		$to_name  = 'Varol Gurme';

		$type_label = ($type === 'sikayet')
			? $this->language->get('text_mail_type_complaint')
			: $this->language->get('text_mail_type_suggestion');

		$subject = ($type === 'sikayet')
			? $this->language->get('text_mail_subject_complaint')
			: $this->language->get('text_mail_subject_suggestion');

		$safe_name    = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
		$safe_phone   = htmlspecialchars($phone, ENT_QUOTES, 'UTF-8');
		$safe_message = nl2br(htmlspecialchars($message, ENT_QUOTES, 'UTF-8'));
		$safe_page    = htmlspecialchars($pageUrl, ENT_QUOTES, 'UTF-8');

		$body  = '<div style="font-family:Arial,Helvetica,sans-serif;font-size:14px;color:#222;">';
		$body .= '<h2 style="margin:0 0 16px;">' . $type_label . ' ' . $this->language->get('text_mail_form_title') . '</h2>';
		$body .= '<p><strong>' . $this->language->get('text_mail_name') . ':</strong> ' . $safe_name . '</p>';
		$body .= '<p><strong>' . $this->language->get('text_mail_phone') . ':</strong> ' . $safe_phone . '</p>';
		$body .= '<p><strong>' . $this->language->get('text_mail_page') . ':</strong> ' . $safe_page . '</p>';
		$body .= '<hr>';
		$body .= '<p><strong>' . $this->language->get('text_mail_message') . ':</strong></p>';
		$body .= '<div style="padding:10px;background:#f5f5f5;border-radius:6px;">' . $safe_message . '</div>';
		$body .= '</div>';

		try {
			$mail = new PHPMailer(true);

			$mail->CharSet = 'UTF-8';
			$mail->Encoding = 'base64';
			$mail->isSMTP();
			$mail->Host       = $mail_host;
			$mail->SMTPAuth   = true;
			$mail->Username   = $mail_username;
			$mail->Password   = $mail_password;
			$mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
			$mail->Port       = $mail_port;
			$mail->isHTML(true);

			$mail->setFrom($from_email, $from_name);
			$mail->addAddress($to_email, $to_name);
			$mail->addReplyTo($from_email, $from_name);

			$mail->Subject = $subject;
			$mail->Body    = $body;
			$mail->AltBody =
				$type_label . " " . $this->language->get('text_mail_form_title') . "\n" .
				$this->language->get('text_mail_name') . ": " . $name . "\n" .
				$this->language->get('text_mail_phone') . ": " . $phone . "\n" .
				$this->language->get('text_mail_page') . ": " . $pageUrl . "\n\n" .
				$this->language->get('text_mail_message') . ":\n" . $message;

			$mail->send();

			$this->session->data['feedback_success'] = $this->language->get('text_submit_success');
		} catch (Exception $e) {
			error_log('MAIL ERROR: ' . $e->getMessage());
			$this->session->data['feedback_error'] = sprintf($this->language->get('error_mail_send'), $e->getMessage());
		}

		$this->response->redirect($pageUrl ?: $this->url->link('common/menu', '', true));
	}
}
