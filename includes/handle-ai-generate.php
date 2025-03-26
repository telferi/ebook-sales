<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function generate_ai_content_callback() {
	// Ellenőrizze a nonce-t
	check_ajax_referer('generate_ai_content', 'ai_content_nonce');
	
	$post_id = intval($_POST['post_id']);

	// Mentés: ha vannak új meta értékek a POST-ban, frissítsük őket
	if ( isset($_POST['ai_writing_style']) ) {
		update_post_meta($post_id, 'ai_writing_style', sanitize_text_field($_POST['ai_writing_style']));
	}
	if ( isset($_POST['ai_writing_tone']) ) {
		update_post_meta($post_id, 'ai_writing_tone', sanitize_text_field($_POST['ai_writing_tone']));
	}
	if ( isset($_POST['ai_output_language']) ) {
		update_post_meta($post_id, 'ai_output_language', sanitize_text_field($_POST['ai_output_language']));
	}

	// Frissített meta értékek lekérése: ha POST-ban vannak, azokat használjuk
	$writing_style   = isset($_POST['ai_writing_style'])   ? sanitize_text_field($_POST['ai_writing_style'])   : get_post_meta($post_id, 'ai_writing_style', true);
	$writing_tone    = isset($_POST['ai_writing_tone'])    ? sanitize_text_field($_POST['ai_writing_tone'])    : get_post_meta($post_id, 'ai_writing_tone', true);
	$output_language = isset($_POST['ai_output_language']) ? sanitize_text_field($_POST['ai_output_language']) : get_post_meta($post_id, 'ai_output_language', true);

	// Alapértelmezett értékek, ha a meta mezők üresek
	if ( empty($writing_style) ) { $writing_style = 'Tájékoztató'; }
	if ( empty($writing_tone) ) { $writing_tone = 'Semleges'; }
	if ( empty($output_language) ) { $output_language = 'hu'; }

	//error_log("Meta values: style={$writing_style} tone={$writing_tone} language={$output_language}");

	// Lekérjük a mentett basic system prompt sablont
	$basic_prompt = get_option('basic_system_prompt', '');
	//error_log("Basic prompt: " . $basic_prompt);
	// Biztosítjuk, hogy ne legyen üres prompt (fallback)
	if ( empty($basic_prompt) ) {
		$basic_prompt ="TE EGY PRÉMIUM EBOOK MARKETING SZAKÉRTŐ VAGY, AKINEK FELADATA LENYŰGÖZŐ, ÉRDEKES ÉS MEGGYŐZŐ ISMERTETŐT ÍRNI A FELTÖLTÖTT EBOOKHOZ. A CÉL, HOGY AZ ISMERTETŐ FELKELTSE AZ OLVASÓ FIGYELMÉT ÉS ÖSZTÖNÖZZE A VÁSÁRLÁST.
        
 ### FELADAT:
 - **ANALIZÁLD** az eBook tartalmát és azonosítsd a legfontosabb témákat.
 - **FOGALMAZD MEG** röviden és érthetően, miről szól az eBook.
 - **HANGSÚLYOZD** az olvasó számára nyújtott előnyöket és értéket.
 - **ALKALMAZKODJ** a Következő preferenciákhoz:
 	1, A szöveg stílusa legyen <Írási stílus> stílusú.
 	2, A szöveg hangneme legyen <Írási hangnem> hangnemű.
 	3, A szöveg nyelve legyen <Nyelv> nyelvű.
 
 ### FORMÁTUM:
 A generált szöveg legyen:
 - Rövid, tömör (maximum 3-5 mondat).
 - Meggyőző és figyelemfelkeltő.
 - Világosan kiemelve az eBook fő témáját és hasznát.
 
 ### PÉLDA KIMENETEK:
 
 **[Önfejlesztő eBook esetén]**
Szeretnéd kihozni magadból a legtöbbet? Ez az eBook lépésről lépésre megmutatja, hogyan építs sikeres szokásokat, növeld a produktivitásod és érd el a céljaid. Kezdd el még ma! 

 **[Üzleti eBook esetén]**
Ismerd meg a modern üzleti stratégiák titkait! Ez az útmutató segít növelni bevételeidet, hatékonyabbá tenni vállalkozásodat és megalapozni a hosszú távú sikert.

 **[Regény esetén]**
Egy lebilincselő történet tele izgalommal és fordulatokkal! Merülj el egy világban, ahol minden döntés számít, és fedezd fel a karakterek lenyűgöző történetét. 

 ### MIT NE TEGYÉL:
NE generálj túl hosszú vagy unalmas ismertetőt.  
NE írj túl általánosan – emeld ki a konkrét értékajánlatot.  
NE hagyd figyelembe kívül az Írási stílus, Írási hangnem és Nyelv beállításokat.  
 
A generált szöveg mindig legyen *érdekes, figyelemfelkeltő és ösztönző*!"
 ;
	}
	
	// Cseréljük ki a placeholder-eket a basic_prompt sablonban
	$processed_prompt = str_replace(
		array('<Írási stílus>', '<Írási hangnem>', '<Nyelv>'),
		array($writing_style, $writing_tone, $output_language),
			$basic_prompt
	);
	
	// Extra adatok hozzáadása: raw input használata wp_unslash()-tal
	$extra_data = isset($_POST['ai_extra_data']) ? wp_unslash($_POST['ai_extra_data']) : '';
	if(!empty($extra_data)) {
		$processed_prompt .= "\n\n" . $extra_data;
	}
	update_option('system_prompt', $processed_prompt);

	//error_log("Processed prompt: " . $processed_prompt);
	//error_log("Processed prompt: " . $system_prompt);

	// ÚJ FUNKCIÓK KEZDETE: Ebook fájl feltöltése és elemzéshez csatolása
	$ebook_file_url = get_post_meta($post_id, '_ebook_file', true);
	$ebook_text = '';
	$file_id = null; // új változó a file_id tárolásához
	if ( $ebook_file_url ) {
		$upload_dir = wp_upload_dir();
		if ( strpos( $ebook_file_url, $upload_dir['baseurl'] ) !== false ) {
			$relative_path = str_replace( $upload_dir['baseurl'], '', $ebook_file_url );
			$file_path = $upload_dir['basedir'] . $relative_path;
			if ( file_exists( $file_path ) ) {
				$ext = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));
				if ( $ext === 'pdf' ) {
					// PDF szöveg kinyerése
					if ( class_exists('\Smalot\PdfParser\Parser') ) {
						$parser = new \Smalot\PdfParser\Parser();
						$pdf = $parser->parseFile($file_path);
						$ebook_text = $pdf->getText();
					} else {
						$ebook_text = file_get_contents( $file_path );
					}
					// Új: PDF feltöltése az OpenAI API-ra, timeout növelése
					$ch = curl_init();
					curl_setopt($ch, CURLOPT_URL, 'https://api.openai.com/v1/files');
					curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
					// Timeout 30 másodpercre állítása
					curl_setopt($ch, CURLOPT_TIMEOUT, 30);
					$headers = array(
						'Authorization: Bearer ' . trim(get_option('openai_api_key', ''))
					);
					curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
					$post_fields = array(
						'purpose' => 'assistants',
						'file'    => new CURLFile($file_path, 'application/pdf', basename($file_path))
					);
					curl_setopt($ch, CURLOPT_POST, true);
					curl_setopt($ch, CURLOPT_POSTFIELDS, $post_fields);
					$upload_result = curl_exec($ch);
					$upload_http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
					curl_close($ch);
					if($upload_http_code == 200 || $upload_http_code == 201) {
						$upload_data = json_decode($upload_result, true);
						if(isset($upload_data['id'])) {
							$file_id = $upload_data['id'];
						}
					} else {
						error_log("OpenAI File Upload Error: HTTP code: " . $upload_http_code . " Response: " . $upload_result);
					}
				} else {
					// Nem PDF fájl esetén, egyszersűen beolvassuk a tartalmat
					$ebook_text = file_get_contents( $file_path );
				}
			}
		}
	}
	// Kiegészítjük a promptot az ebook tartalmával
	if ( ! empty($ebook_text) ) {
		$processed_prompt .= "\n\nEbook tartalom elemzéshez:\n" . $ebook_text;
	}
	// ÚJ FUNKCIÓK VÉGE

	// OpenAI API hívás
	$openai_api_key = get_option('openai_api_key', '');
	$openai_api_model = get_option('openai_api_model', 'text-davinci-003');

	// Győződjünk meg róla, hogy a prompt érvényesen UTF-8 kódolt
	$processed_prompt = mb_convert_encoding($processed_prompt, 'UTF-8', 'auto');

	// Új kód: Ha file_id létezik, akkor chat completions hívást végzünk (REMOVED file_ids param)
	if ($file_id) {
		$api_endpoint = 'https://api.openai.com/v1/chat/completions';
		$request_body = array(
			'model'    => $openai_api_model, // itt használjuk a beállított modelt
			'messages' => array(
				array("role" => "system", "content" => "Te egy PDF értelmező asszisztens vagy."),
				array("role" => "user", "content" => $processed_prompt)
			)
		);
	} else {
		// Ha nincs file_id, akkor a régi módon kérjük le a generált tartalmat
		$api_endpoint = 'https://api.openai.com/v1/completions';
		$request_body = array(
			'model' => $openai_api_model,
			'prompt' => $processed_prompt,
			'max_tokens' => 150
		);
	}

	$json_body = json_encode($request_body, JSON_UNESCAPED_UNICODE);
	if (false === $json_body) {
		$error = json_last_error_msg();
		wp_send_json_error(array('message' => "JSON encoding error: " . $error));
	}
	// Debug: logoljuk a kész JSON-t
	//error_log("OpenAI Request JSON: " . $json_body);

	$response = wp_remote_post($api_endpoint, array(
		'headers' => array(
			'Content-Type' => 'application/json',
			'Authorization' => 'Bearer ' . trim($openai_api_key)
		),
		'body' => $json_body,
		'timeout' => 30
	));

	if ( is_wp_error( $response ) ) {
		$error_message = $response->get_error_message();
		wp_send_json_error(array( 'message' => "OpenAI API hiba: " . $error_message ));
	}
	// Debug: logoljuk a HTTP válasz kódot és a body-t
	$response_code = wp_remote_retrieve_response_code( $response );
	$response_body_raw = wp_remote_retrieve_body( $response );
	error_log("OpenAI Response Code: " . $response_code);
	error_log("OpenAI Response Body: " . $response_body_raw);

	$response_body = json_decode( $response_body_raw, true );
	// Ha nincs megfelelő válasz
	if ( empty($response_body) || !isset($response_body['choices']) ) {
		error_log("API válasz nem megfelelő: " . print_r($response_body, true));
		$generated_content = "Nincs API kapcsolat.";
	} else {
		if (isset($response_body['choices'][0]['message']['content']) && !empty($response_body['choices'][0]['message']['content'])) {
			// ChatCompletion válasz esetén
			$generated_content = trim($response_body['choices'][0]['message']['content']);
		} elseif (isset($response_body['choices'][0]['text']) && !empty($response_body['choices'][0]['text'])) {
			// Completions válasz esetén
			$generated_content = trim($response_body['choices'][0]['text']);
		} else {
			$generated_content = "Nincs generált tartalom.";
		}
	}

	// Frissített válasz adatok
	$response_data = array(
		'content' => "Generált tartalom: " . $generated_content,
		'message' => 'Sikeres generálás!',
		'generated_content' => $generated_content
	);

	// Debug: log the generated content
	error_log("Generated content: " . $generated_content);

	wp_send_json_success($response_data);
}
add_action('wp_ajax_generate_ai_content', 'generate_ai_content_callback');