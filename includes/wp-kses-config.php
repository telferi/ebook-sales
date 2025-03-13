<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Bővíti a wp_kses által engedélyezett HTML tageket egyedi placeholder-ekkel,
 * melyeket a system promptban használunk.
 *
 * Használat: írd be a placeholder-eket a promptban az alábbi formában:
 * <írási-stílus>, <írási-hangnem> és <nyelv>.
 */
function ebook_sales_allow_placeholders( $allowed_tags, $context ) {
	// Csak a rendszer prompt specifikus kontextusban bővítjük az engedélyezett tageket.
	if ( $context === 'system_prompt' ) {
		$allowed_tags['Írási-stílus'] = array();
		$allowed_tags['Írási-hangnem'] = array();
		$allowed_tags['Nyelv']          = array();
	}
	return $allowed_tags;
}
add_filter( 'wp_kses_allowed_html', 'ebook_sales_allow_placeholders', 10, 2 );
