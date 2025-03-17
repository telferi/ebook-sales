<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function generate_ai_content_callback() {
	// Ellenőrizze a nonce-t
	check_ajax_referer('generate_ai_content', 'ai_content_nonce');
	
	$post_id = intval($_POST['post_id']);
	// Lekérjük a mentett system prompt sablont (ez nem módosul automatikusan)
	$basic_prompt = get_option('basic_system_prompt', '');
	// Lekérjük a post meta adatokat
	$writing_style   = get_post_meta($post_id, 'ai_writing_style', true);
	$writing_tone    = get_post_meta($post_id, 'ai_writing_tone', true);
	$output_language = get_post_meta($post_id, 'ai_output_language', true);
	
	// Cseréljük ki a placeholder-eket a basic_prompt sablonban
	$processed_prompt = str_replace(
		array('<Írási stílus>', '<Írási hangnem>', '<Nyelv>'),
		array($writing_style, $writing_tone, $output_language),
			$basic_prompt
	);
	
	// Extra adatok hozzáadása: a POST-ból kapott 'ai_extra_data' értékkel
	$extra_data = isset($_POST['ai_extra_data']) ? $_POST['ai_extra_data'] : '';
	if(!empty($extra_data)) {
		$processed_prompt .= "\n\n" . $extra_data;
	}
	
	// Itt kell az OpenAI API hívást végrehajtani a $processed_prompt értékkel…
	// Példa eredmény:
	$response_data = array(
		'content' => "Generált tartalom a következő prompttal: " . $processed_prompt,
		'message' => 'Sikeres generálás!'
	);
	
	wp_send_json_success($response_data);
}
add_action('wp_ajax_generate_ai_content', 'generate_ai_content_callback');
