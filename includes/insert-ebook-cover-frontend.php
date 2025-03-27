<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function ebook_insert_cover_frontend( $content ) {
    if ( is_singular('ebook') && in_the_loop() && is_main_query() ) {
        $cover_image = get_post_meta( get_the_ID(), '_cover_image', true );
        if ( $cover_image ) {
            $cover_html = '<div style="text-align: center; margin: 20px 0;">
                <img src="' . esc_url( $cover_image ) . '" alt="' . esc_attr__('Borítókép', 'ebook-sales') . '" style="max-width:100%; height:auto;" />
            </div>';
            $content = $cover_html . $content;
        }
    }
    return $content;
}
add_filter( 'the_content', 'ebook_insert_cover_frontend' );
