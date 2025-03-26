<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Ez a funkció beszúrja a mentett borítóképet a poszt tartalom elejére,
 * így a poszt title után, de a szöveg előtt jelenik meg.
 */
function insert_ebook_cover_into_content( $post_id ) {
    // Ne futtassuk autosave vagy revision esetén
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
    }
    if ( wp_is_post_revision( $post_id ) ) {
        return;
    }
    // Csak ebook típusú posztokra alkalmazzuk
    if ( get_post_type( $post_id ) !== 'ebook' ) {
        return;
    }
    
    // Lekérjük a mentett borító kép URL-jét
    $cover_url = get_post_meta( $post_id, '_cover_image', true );
    if ( empty( $cover_url ) ) {
        return;
    }

    $post = get_post( $post_id );
    // Ha már beszúrva van, ne illesszük be újra (marker: <!-- EBOOK COVER -->)
    if ( strpos( $post->post_content, '<!-- EBOOK COVER -->' ) !== false ) {
        return;
    }
    
    // Borító kép HTML blokk létrehozása
    $cover_html = '<!-- EBOOK COVER --><div class="ebook-cover"><p><img src="' . esc_url( $cover_url ) . '" alt="Borítókép" /></p></div>' . "\n";
    
    // Beszúrjuk a borító képet a post_content elejére
    $new_content = $cover_html . $post->post_content;
    
    // Frissítjük a posztot az új tartalommal
    remove_action('save_post', 'insert_ebook_cover_into_content');
    wp_update_post( array(
        'ID'           => $post_id,
        'post_content' => $new_content
    ) );
    add_action('save_post', 'insert_ebook_cover_into_content', 25);
}
add_action('save_post', 'insert_ebook_cover_into_content', 25);
