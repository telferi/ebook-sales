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
TE EGY PRÉMIUM EBOOK MARKETING SZAKÉRTŐ VAGY, AKINEK FELADATA LENYŰGÖZŐ, ÉRDEKES ÉS MEGGYŐZŐ ISMERTETŐT ÍRNI A FELTÖLTÖTT EBOOKHOZ. A CÉL, HOGY AZ ISMERTETŐ FELKELTSE AZ OLVASÓ FIGYELMÉT ÉS ÖSZTÖNÖZZE A VÁSÁRLÁST.  
 
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
 🔹 \"Szeretnéd kihozni magadból a legtöbbet? Ez az eBook lépésről lépésre megmutatja, hogyan építs sikeres szokásokat, növeld a produktivitásod és érd el a céljaid. Kezdd el még ma!\"  
 
 **[Üzleti eBook esetén]**  
 💼 \"Ismerd meg a modern üzleti stratégiák titkait! Ez az útmutató segít növelni bevételeidet, hatékonyabbá tenni vállalkozásodat és megalapozni a hosszú távú sikert.\"  
 
 **[Regény esetén]**  
 📖 \"Egy lebilincselő történet tele izgalommal és fordulatokkal! Merülj el egy világban, ahol minden döntés számít, és fedezd fel a karakterek lenyűgöző történetét.\"  
 
 ### MIT NE TEGYÉL:  
 ❌ NE generálj túl hosszú vagy unalmas ismertetőt.  
 ❌ NE írj túl általánosan - emeld ki a konkrét értékajánlatot.  
 ❌ NE hagyd figyelmen kívül az Írási stílus, Írási hangnem és Nyelv beállításokat.  
 
 🔹 A generált szöveg mindig legyen *érdekes, figyelemfelkeltő és ösztönző*!" ;
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
	update_option('system_prompt', $processed_prompt);

	//error_log("Processed prompt: " . $processed_prompt);
	//error_log("Processed prompt: " . $system_prompt);

	// Itt kell az OpenAI API hívást végrehajtani a $processed_prompt értékkel…
	// Példa eredmény:
	$response_data = array(
		'content' => "Generált tartalom a következő prompttal: " . $processed_prompt,
		'message' => 'Sikeres generálás!'
	);

	// Ha szeretnéd a végleges promptot elmenteni a system_prompt opcióba, akkor:
	
	
	wp_send_json_success($response_data);
}
add_action('wp_ajax_generate_ai_content', 'generate_ai_content_callback');
