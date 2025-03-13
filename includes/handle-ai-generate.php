<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function generate_ai_content_callback() {
	// Ellenőrizze a nonce-t
	check_ajax_referer('generate_ai_content', 'ai_content_nonce');
	
	$post_id = intval($_POST['post_id']);
	// Lekérjük a mentett system prompt sablont
	$system_prompt = get_option('system_prompt', '');
	// Lekérjük a post meta adatokat
	$writing_style   = get_post_meta($post_id, 'ai_writing_style', true);
	$writing_tone    = get_post_meta($post_id, 'ai_writing_tone', true);
	$output_language = get_post_meta($post_id, 'ai_output_language', true);
	
	// Cseréljük ki a placeholder-eket
	$final_prompt = str_replace(
		array('<Írási stílus>', '<Írási hangnem>', '<Nyelv>'),
		array($writing_style, $writing_tone, $output_language),
		$system_prompt
	);
	
	// Itt kell az OpenAI API hívást végrehajtani a $final_prompt értékkel…
	// Példa eredmény:
	$response_data = array(
		'content' => "Generált tartalom a következő prompttal: " . $final_prompt,
		'message' => 'Sikeres generálás!'
	);
	
	wp_send_json_success($response_data);
}
add_action('wp_ajax_generate_ai_content', 'generate_ai_content_callback');
