<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function generate_ai_content_callback() {
	// Ellenőrizze a nonce-t
	check_ajax_referer('generate_ai_content', 'ai_content_nonce');
	
	$post_id = intval($_POST['post_id']);
	// Lekérjük a mentett basic system prompt sablont
	$basic_prompt = get_option('basic_system_prompt', '');
	error_log("Basic prompt: " . $basic_prompt);
	// Biztosítjuk, hogy ne legyen üres prompt (fallback)
	if ( empty($basic_prompt) ) {
		$basic_prompt = "  
TE EGY PRÉMIUM EBOOK MARKETING SZAKÉRTŐ VAGY, AKINEK FELADATA LENYŰGÖZŐ, ÉRDEKES ÉS MEGGYŐZŐ ISMERTETŐT ÍRNI A FELTÖLTÖTT EBOOKHOZ.  

- **ANALIZÁLD** az eBook tartalmát  
- **FOGALMAZD MEG**: <Írási stílus>, <Írási hangnem>, <Nyelv>";
	}
	
	// Lekérjük a post meta adatokat
	$writing_style   = get_post_meta($post_id, 'ai_writing_style', true);
	$writing_tone    = get_post_meta($post_id, 'ai_writing_tone', true);
	$output_language = get_post_meta($post_id, 'ai_output_language', true);

	// Alapértelmezett értékek, ha a meta mezők üresek
	if ( empty($writing_style) ) {
		$writing_style = 'Tájékoztató';
	}
	if ( empty($writing_tone) ) {
		$writing_tone = 'Semleges';
	}
	if ( empty($output_language) ) {
		$output_language = 'hu';
	}
	
	error_log("Meta values: style={$writing_style} tone={$writing_tone} language={$output_language}");

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
	
	error_log("Processed prompt: " . $processed_prompt);

	// Itt kell az OpenAI API hívást végrehajtani a $processed_prompt értékkel…
	// Példa eredmény:
	$response_data = array(
		'content' => "Generált tartalom a következő prompttal: " . $processed_prompt,
		'message' => 'Sikeres generálás!'
	);

	// Ha szeretnéd a végleges promptot elmenteni a system_prompt opcióba, akkor:
	 update_option('system_prompt', $processed_prompt);
	
	wp_send_json_success($response_data);
}
add_action('wp_ajax_generate_ai_content', 'generate_ai_content_callback');
