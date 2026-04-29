<?php
class ControllerCommonMenuRecommendation extends Controller {
	public function index() {
		$this->load->language('common/menu');

		$data['title'] = $this->isEnglishLanguage() ? 'What Should I Eat Today?' : 'Kararsızım';
		$data['description'] = $this->config->get('config_meta_description');
		if (!$this->isEnglishLanguage()) {
			$data['title'] = html_entity_decode('Karars&#305;z&#305;m', ENT_QUOTES, 'UTF-8');
		}
		$data['keywords'] = $this->config->get('config_meta_keyword');
		$data['logo'] = HTTPS_SERVER . 'image/' . $this->config->get('config_logo');
		$data['serv'] = HTTPS_SERVER;
		$data['qr'] = !empty($this->session->data['menu_qr_token']) ? $this->session->data['menu_qr_token'] : '';
		$data['table_id'] = !empty($this->session->data['menu_table_id']) ? (int)$this->session->data['menu_table_id'] : 0;
		$data['table_no'] = !empty($this->session->data['menu_table_no']) ? (int)$this->session->data['menu_table_no'] : 0;
		$data['table_name'] = !empty($this->session->data['menu_table_name']) ? $this->session->data['menu_table_name'] : '';

		$this->load->model('common/menu_order');
		$data['can_order'] = $this->model_common_menu_order->canOrder();

		$qr_param = $data['qr'] ? 'qr=' . urlencode($data['qr']) : '';
		$data['menu_url'] = $this->url->link('common/menu', $qr_param, true);
		$data['menu_footer'] = $this->load->controller('common/menu_footer');
		$data['is_english'] = $this->isEnglishLanguage();

		$this->response->setOutput($this->load->view('common/menu_recommendation', $data));
	}

	public function getSuggestion() {
		$this->load->model('common/menu_recommendation_engine');

		$requested_mode = isset($this->request->get['mode']) ? trim((string)$this->request->get['mode']) : 'combo';
		$period = $this->detectPeriod((int)date('H'));
		$date_key = date('Y-m-d');
		$current_language_id = $this->getCurrentLanguageId();

		$resolved_mode = $this->resolveMode($requested_mode, $period);

		$cache_version = 'v22';
		$language_cache_key = 'lang_' . $current_language_id;

		if (!isset($this->session->data['menu_recommendation']) || !is_array($this->session->data['menu_recommendation'])) {
			$this->session->data['menu_recommendation'] = array();
		}

		if (!isset($this->session->data['menu_recommendation'][$cache_version])) {
			$this->session->data['menu_recommendation'][$cache_version] = array();
		}

		if (!isset($this->session->data['menu_recommendation'][$cache_version][$language_cache_key])) {
			$this->session->data['menu_recommendation'][$cache_version][$language_cache_key] = array();
		}

		if (!isset($this->session->data['menu_recommendation'][$cache_version][$language_cache_key][$date_key])) {
			$this->session->data['menu_recommendation'][$cache_version][$language_cache_key][$date_key] = array();
		}

		if (isset($this->session->data['menu_recommendation'][$cache_version][$language_cache_key][$date_key][$period][$requested_mode])) {
			$this->jsonResponse($this->session->data['menu_recommendation'][$cache_version][$language_cache_key][$date_key][$period][$requested_mode]);
			return;
		}

		$weather = $this->getWeatherData();
		$temp = isset($weather['temp']) ? (float)$weather['temp'] : 15;

		$recommendation = $this->model_common_menu_recommendation_engine->getRecommendation($resolved_mode, $temp);

		if (
	empty($recommendation) ||
	(
		empty($recommendation['main']) &&
		empty($recommendation['drink']) &&
		empty($recommendation['dessert']) &&
		empty($recommendation['second_drink'])
	)
) {
	$this->jsonResponse(array(
		'success' => false,
		'message' => $this->isEnglishLanguage() ? 'No suitable combination found.' : 'Uygun kombin bulunamadı.'
	));
	return;
}

		$ai_text = $this->generateAiText(array(
			'period' => $period,
			'mode' => $resolved_mode,
			'requested_mode' => $requested_mode,
			'weather' => $weather,
			'main' => !empty($recommendation['main']['name']) ? $recommendation['main']['name'] : '',
			'drink' => !empty($recommendation['drink']['name']) ? $recommendation['drink']['name'] : '',
			'dessert' => !empty($recommendation['dessert']['name']) ? $recommendation['dessert']['name'] : ''
		));

		$result = array(
			'success' => true,
			'is_english' => $this->isEnglishLanguage(),
			'language_id' => $current_language_id,
			'mode' => $requested_mode,
			'resolved_mode' => $resolved_mode,
			'period' => $period,
			'day_key' => $date_key,
			'weather' => $weather,
			'use_hot_drink' => !empty($recommendation['use_hot_drink']),
			'title' => !empty($ai_text['title']) ? $ai_text['title'] : $this->getFallbackTitle($resolved_mode),
			'text' => !empty($ai_text['text']) ? $ai_text['text'] : $this->getFallbackText($recommendation, $weather),
			'main' => !empty($recommendation['main']) ? $recommendation['main'] : null,
'drink' => !empty($recommendation['drink']) ? $recommendation['drink'] : null,
'dessert' => !empty($recommendation['dessert']) ? $recommendation['dessert'] : null,
'second_drink' => !empty($recommendation['second_drink']) ? $recommendation['second_drink'] : null
		);

		$this->session->data['menu_recommendation'][$cache_version][$language_cache_key][$date_key][$period][$requested_mode] = $result;

		$this->jsonResponse($result);
	}

	private function resolveMode($requested_mode, $period) {
		$valid_modes = array('breakfast', 'light', 'hearty', 'dessert_coffee', 'drink_only');

		if (in_array($requested_mode, $valid_modes, true)) {
			return $requested_mode;
		}

		if ($requested_mode === 'combo') {
			if ($period === 'breakfast') {
				return 'breakfast';
			}

			if ($period === 'lunch') {
				return 'light';
			}

			return 'hearty';
		}

		return 'light';
	}

	private function getCurrentLanguageId() {
		if (isset($this->session->data['language']) && $this->session->data['language']) {
			$language_code = strtolower((string)$this->session->data['language']);

			if ($language_code === 'en-gb' || $language_code === 'en') {
				return 2;
			}

			return 1;
		}

		if (isset($this->request->cookie['language']) && $this->request->cookie['language']) {
			$language_code = strtolower((string)$this->request->cookie['language']);

			if ($language_code === 'en-gb' || $language_code === 'en') {
				return 2;
			}

			return 1;
		}

		return (int)$this->config->get('config_language_id');
	}

	private function isEnglishLanguage() {
		return ((int)$this->getCurrentLanguageId() === 2);
	}

	private function getWeatherApiLang() {
		return $this->isEnglishLanguage() ? 'en' : 'tr';
	}

	private function getPeriodLabelForAi($period) {
		if ($this->isEnglishLanguage()) {
			if ($period === 'breakfast') {
				return 'breakfast';
			}
			if ($period === 'lunch') {
				return 'lunch';
			}
			return 'dinner';
		}

		if ($period === 'breakfast') {
			return 'kahvaltı';
		}
		if ($period === 'lunch') {
			return 'öğle';
		}
		return 'akşam';
	}

	private function getModeLabelForAi($mode) {
		if ($this->isEnglishLanguage()) {
			$map = array(
				'combo' => 'mixed suggestion',
				'breakfast' => 'breakfast',
				'light' => 'light choice',
				'hearty' => 'hearty choice',
				'dessert_coffee' => 'dessert and coffee',
				'drink_only' => 'drinks only'
			);

			return isset($map[$mode]) ? $map[$mode] : $mode;
		}

		$map = array(
			'combo' => 'karışık öneri',
			'breakfast' => 'kahvaltı',
			'light' => 'hafif seçim',
			'hearty' => 'doyurucu seçim',
			'dessert_coffee' => 'tatlı ve kahve',
			'drink_only' => 'sadece içecek'
		);

		return isset($map[$mode]) ? $map[$mode] : $mode;
	}

	private function getWeatherData() {
		$this->load->model('common/restaurant_settings');

		$weather_api_key = trim((string)$this->model_common_restaurant_settings->get(
			'restaurant_weatherapi_key',
			defined('WEATHERAPI_KEY') ? trim((string)WEATHERAPI_KEY) : ''
		));
		$lat = (string)$this->model_common_restaurant_settings->get(
			'restaurant_weather_lat',
			defined('MENU_WEATHER_LAT') ? (string)MENU_WEATHER_LAT : ''
		);
		$lon = (string)$this->model_common_restaurant_settings->get(
			'restaurant_weather_lon',
			defined('MENU_WEATHER_LON') ? (string)MENU_WEATHER_LON : ''
		);

		if ($lat === '' || $lon === '') {
			$lat = '37.766670';
			$lon = '29.031022';
		}

		if ($weather_api_key === '') {
			return array(
				'temp' => 20,
				'condition' => 'normal',
				'label' => $this->isEnglishLanguage() ? 'Weather unavailable' : 'Hava bilgisi alınamadı'
			);
		}

		$url = 'https://api.weatherapi.com/v1/current.json?key=' . rawurlencode($weather_api_key) . '&q=' . rawurlencode($lat . ',' . $lon) . '&lang=' . $this->getWeatherApiLang();

		$response = $this->curlRequest($url, array('Accept: application/json'), 15);

		if (empty($response['body'])) {
			return array(
				'temp' => 20,
				'condition' => 'normal',
				'label' => $this->isEnglishLanguage() ? 'Weather unavailable' : 'Hava bilgisi alınamadı'
			);
		}

		$data = json_decode($response['body'], true);

		if (empty($data['current'])) {
			return array(
				'temp' => 20,
				'condition' => 'normal',
				'label' => $this->isEnglishLanguage() ? 'Weather unavailable' : 'Hava bilgisi alınamadı'
			);
		}

		$temp = isset($data['current']['temp_c']) ? (float)$data['current']['temp_c'] : 20;
		$condition_text = !empty($data['current']['condition']['text']) ? trim((string)$data['current']['condition']['text']) : ($this->isEnglishLanguage() ? 'Normal' : 'Normal');
		$normalized_condition = $this->normalizeWeatherCondition($temp, $condition_text);

		return array(
			'temp' => round($temp),
			'condition' => $normalized_condition,
			'label' => $condition_text
		);
	}

	private function normalizeWeatherCondition($temp, $condition_text) {
		$text = $this->normalizeText($condition_text);

		if (
			strpos($text, 'yag') !== false ||
			strpos($text, 'saganak') !== false ||
			strpos($text, 'drizzle') !== false ||
			strpos($text, 'rain') !== false
		) {
			return 'yagmurlu';
		}

		if (
			strpos($text, 'kar') !== false ||
			strpos($text, 'snow') !== false
		) {
			return 'soguk';
		}

		if ($temp <= 5) {
			return 'cok soguk';
		} elseif ($temp <= 10) {
			return 'soguk';
		} elseif ($temp <= 20) {
			return 'serin';
		} elseif ($temp <= 30) {
			return 'ilik';
		}

		return 'sicak';
	}

	private function generateAiText($context) {
		$this->load->model('common/restaurant_settings');

		$api_key = trim((string)$this->model_common_restaurant_settings->get(
			'restaurant_openai_api_key',
			defined('OPENAI_API_KEY') ? trim((string)OPENAI_API_KEY) : ''
		));

		if ($api_key === '') {
			return $this->buildFallbackAiText($context);
		}

		$is_english = $this->isEnglishLanguage();

		if ($is_english) {
			$system_prompt = 'You are a restaurant recommendation assistant for Varol Veranda. Write short, natural, friendly and menu-appropriate English copy.';
			$prompt = '
You are a restaurant recommendation assistant for Varol Veranda.

Writing Rules:
- Keep it short, natural and smooth
- Title: max 6-7 words
- Description: max 2 sentences
- DO NOT always start with temperature
- Sometimes mention weather, sometimes don’t
- Avoid repeating the same sentence structure
- Write like you are speaking to a guest
- Use soft persuasive language (not aggressive)
- Make it feel like a real restaurant suggestion, not a product description

Tone:
- Premium restaurant tone
- Warm, friendly and confident
- Appetizing and inviting

Style by mode:
- breakfast → fresh, energetic, inviting
- light → refreshing, balanced, clean
- hearty → strong, satisfying, rich
- dessert_coffee → cozy, indulgent, relaxing
- drink_only → refreshing, light, chill

Emoji Rules:
- Use 1 or 2 emojis MAX
- Do NOT overuse
- Choose relevant emojis (☕ 🍰 🥗 🍹 🍳)
- Sometimes don’t use emoji at all

Data:
- Period: ' . $this->getPeriodLabelForAi($context['period']) . '
- Mode: ' . $this->getModeLabelForAi($context['mode']) . '
- Weather: ' . (!empty($context['weather']['label']) ? $context['weather']['label'] : $context['weather']['condition']) . '
- Temperature: ' . $context['weather']['temp'] . '°
- Main: ' . $context['main'] . '
- Drink: ' . $context['drink'] . '
- Dessert: ' . $context['dessert'] . '

Return ONLY valid JSON:

{
  "title": "...",
  "text": "..."
}
';
		} else {
			$system_prompt = 'Sen kısa, doğal ve restoran menüsüne uygun Türkçe metinler yazan asistansın.';
			$prompt = '
Sen Varol Veranda için çalışan bir restoran öneri asistanısın.

Yazım Kuralları:
- Metin kısa, akıcı ve doğal olsun
- Başlık max 6-7 kelime
- Açıklama max 2 cümle
- Her zaman sıcaklık ile başlama
- Bazen hava kullan, bazen kullanma
- Aynı cümle kalıplarını tekrar etme
- Müşteriyle konuşur gibi yaz
- Ürün açıklaması gibi değil, öneri gibi yaz
- Hafif satış dili kullan ama abartma

Ton:
- Premium restoran dili
- Samimi ama kaliteli
- İşta açıcı ve akıcı

Moda göre stil:
- breakfast → enerjik ve taze
- light → ferah ve dengeli
- hearty → doyurucu ve güçlü
- dessert_coffee → keyifli ve rahatlatıcı
- drink_only → ferahlatıcı ve hafif

Emoji Kuralları:
- En fazla 1-2 emoji kullan
- Her cümlede kullanma
- Uygun emoji seç (☕ 🍰 🥗 🍹 🍳)
- Bazen hiç kullanma

Veriler:
- Gün bölümü: ' . $this->getPeriodLabelForAi($context['period']) . '
- Mod: ' . $this->getModeLabelForAi($context['mode']) . '
- Hava: ' . (!empty($context['weather']['label']) ? $context['weather']['label'] : $context['weather']['condition']) . '
- Sıcaklık: ' . $context['weather']['temp'] . '°
- Ana ürün: ' . $context['main'] . '
- İçecek: ' . $context['drink'] . '
- Tatlı: ' . $context['dessert'] . '

Sadece geçerli JSON döndür:

{
  "title": "...",
  "text": "..."
}
';
		}

		$payload = array(
			'model' => 'gpt-4o-mini',
			'response_format' => array('type' => 'json_object'),
			'messages' => array(
				array('role' => 'system', 'content' => $system_prompt),
				array('role' => 'user', 'content' => $prompt)
			),
			'temperature' => 0.7,
			'max_tokens' => 180
		);

		$response = $this->curlRequest(
			'https://api.openai.com/v1/chat/completions',
			array(
				'Content-Type: application/json',
				'Authorization: Bearer ' . $api_key
			),
			20,
			json_encode($payload)
		);

		if (empty($response['body'])) {
			return $this->buildFallbackAiText($context);
		}

		$json = json_decode($response['body'], true);

		if (!empty($json['error']) || empty($json['choices'][0]['message']['content'])) {
			return $this->buildFallbackAiText($context);
		}

		$content = trim((string)$json['choices'][0]['message']['content']);
		$content = preg_replace('/^```json\s*/i', '', $content);
		$content = preg_replace('/^```\s*/', '', $content);
		$content = preg_replace('/\s*```$/', '', $content);

		$parsed = json_decode($content, true);

		if (!$parsed || empty($parsed['title']) || empty($parsed['text'])) {
			return $this->buildFallbackAiText($context);
		}

		return array(
			'title' => trim((string)$parsed['title']),
			'text' => trim((string)$parsed['text'])
		);
	}

	private function buildFallbackAiText($context) {
		$period = isset($context['period']) ? $context['period'] : 'lunch';
		$temp = isset($context['weather']['temp']) ? (int)$context['weather']['temp'] : 20;
		$condition_label = !empty($context['weather']['label']) ? $context['weather']['label'] : (isset($context['weather']['condition']) ? $context['weather']['condition'] : 'normal');
		$main = !empty($context['main']) ? $context['main'] : ($this->isEnglishLanguage() ? 'our featured selection' : 'özel seçimimiz');
		$drink = !empty($context['drink']) ? $context['drink'] : ($this->isEnglishLanguage() ? 'our drink suggestion' : 'içecek önerimiz');
		$dessert = !empty($context['dessert']) ? $context['dessert'] : ($this->isEnglishLanguage() ? 'our dessert suggestion' : 'tatlı önerimiz');

		if ($this->isEnglishLanguage()) {
			return array(
				'title' => 'Your suggestion is ready',
				'text' => 'At ' . $temp . '°C with ' . $condition_label . ' weather, ' . $main . ', ' . $drink . ' and ' . $dessert . ' can be a pleasant combination.'
			);
		}

		return array(
			'title' => 'Bugün için önerimiz hazır',
			'text' => $temp . '°C ve ' . $condition_label . ' havada ' . $main . ', ' . $drink . ' ve ' . $dessert . ' güzel bir uyum yakalayabilir.'
		);
	}

	private function getFallbackTitle($mode) {
		if ($this->isEnglishLanguage()) {
			$map = array(
				'breakfast' => 'A lovely breakfast idea',
				'light' => 'A fresh and light choice',
				'hearty' => 'A hearty pick for today',
				'dessert_coffee' => 'Dessert time feels right',
				'drink_only' => 'A drink suggestion is ready'
			);

			return isset($map[$mode]) ? $map[$mode] : 'Your suggestion is ready';
		}

		$map = array(
			'breakfast' => 'Güne güzel bir başlangıç',
			'light' => 'Hafif ve keyifli bir seçim',
			'hearty' => 'Doyurucu bir öneri hazır',
			'dessert_coffee' => 'Tatlı keyfi için güzel bir seçim',
			'drink_only' => 'İçecek önerimiz hazır'
		);

		return isset($map[$mode]) ? $map[$mode] : 'Bugün için önerimiz hazır';
	}

	private function getFallbackText($recommendation, $weather) {
		$main = !empty($recommendation['main']['name']) ? $recommendation['main']['name'] : '';
		$drink = !empty($recommendation['drink']['name']) ? $recommendation['drink']['name'] : '';
		$dessert = !empty($recommendation['dessert']['name']) ? $recommendation['dessert']['name'] : '';
		$temp = isset($weather['temp']) ? (int)$weather['temp'] : 20;

		if ($this->isEnglishLanguage()) {
			return 'At ' . $temp . '°C, we paired ' . $main . ' with ' . $drink . ' and ' . $dessert . '.';
		}

		return $temp . '°C hava için ' . $main . ', ' . $drink . ' ve ' . $dessert . ' eşleşmesini seçtik.';
	}

	private function curlRequest($url, $headers = array(), $timeout = 15, $postfields = null) {
		$result = array(
			'body' => '',
			'http_code' => 0,
			'error' => ''
		);

		if (!function_exists('curl_init')) {
			return $result;
		}

		$ch = curl_init($url);

		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
		curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);

		if (!empty($headers)) {
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		}

		if ($postfields !== null) {
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $postfields);
		}

		$body = curl_exec($ch);

		if ($body === false) {
			$result['error'] = curl_error($ch);
		} else {
			$result['body'] = $body;
		}

		$result['http_code'] = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);

		curl_close($ch);

		return $result;
	}

	private function jsonResponse($data) {
		$this->response->addHeader('Content-Type: application/json; charset=utf-8');
		$this->response->setOutput(json_encode($data, JSON_UNESCAPED_UNICODE));
	}

	private function detectPeriod($hour) {
		if ($hour < 12) {
			return 'breakfast';
		}

		if ($hour < 18) {
			return 'lunch';
		}

		return 'dinner';
	}

	private function normalizeText($text) {
		$text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');
		$text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');
		$text = trim($text);

		if (function_exists('mb_strtolower')) {
			$text = mb_strtolower($text, 'UTF-8');
		} else {
			$text = strtolower($text);
		}

		$replace = array(
			'ı' => 'i',
			'İ' => 'i',
			'ş' => 's',
			'Ş' => 's',
			'ğ' => 'g',
			'Ğ' => 'g',
			'ü' => 'u',
			'Ü' => 'u',
			'ö' => 'o',
			'Ö' => 'o',
			'ç' => 'c',
			'Ç' => 'c',
			'&amp;' => ' ',
			'&' => ' ',
			'/' => ' ',
			'-' => ' '
		);

		$text = strtr($text, $replace);
		$text = preg_replace('/\s+/u', ' ', $text);

		return trim($text);
	}
}
