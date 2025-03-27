<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// CSS stílus lap betöltése a gombhoz
function ebook_pay_button_enqueue_styles() {
    wp_enqueue_style( 'ebook-pay-button', EBOOK_SALES_PLUGIN_URL . 'assets/css/ebook-pay-button.css' );
}
add_action( 'wp_enqueue_scripts', 'ebook_pay_button_enqueue_styles' );

// Gomb hozzáfűzése a poszt tartalmához
function ebook_pay_button_append_to_content( $content ) {
    if ( is_singular('ebook') && in_the_loop() && is_main_query() ) {
        $button_markup = '<div class="ebook-pay-button-container" style="text-align: center; margin:20px 0;">
            <button class="ebook-pay-button">' . __( 'Buy Ebook', 'ebook-sales' ) . '</button>
        </div>';
        $content .= $button_markup;
    }
    return $content;
}
add_filter( 'the_content', 'ebook_pay_button_append_to_content' );
