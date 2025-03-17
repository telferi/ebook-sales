<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Mentés a "save_post" hook segítségével
function ebook_sales_save_ai_meta( $post_id ) {
    // Ellenőrizze a nonce-t és az autosave állapotot, illetve a jogosultságot
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
    }
    if ( ! current_user_can( 'edit_post', $post_id ) ) {
        return;
    }

    if ( isset($_POST['ai_writing_style']) ) {
        update_post_meta( $post_id, 'ai_writing_style', sanitize_text_field( $_POST['ai_writing_style'] ) );
    }
    if ( isset($_POST['ai_writing_tone']) ) {
        update_post_meta( $post_id, 'ai_writing_tone', sanitize_text_field( $_POST['ai_writing_tone'] ) );
    }
    if ( isset($_POST['ai_output_language']) ) {
        update_post_meta( $post_id, 'ai_output_language', sanitize_text_field( $_POST['ai_output_language'] ) );
    }
}
add_action( 'save_post', 'ebook_sales_save_ai_meta' );
