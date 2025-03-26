<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Megjeleníti a borítóképet egy meta dobozban a title mező és a szerkesztő között.
 */
function ebook_render_cover_meta_box() {
    global $post;
    if ( get_post_type( $post ) !== 'ebook' ) {
        return;
    }
    $cover_image = get_post_meta( $post->ID, '_cover_image', true );
    ?>
    <div id="ebook-cover-meta-box" style="margin: 15px 0; border: 1px solid #ccc; padding: 10px;">
        <h2><?php _e('Borítókép', 'ebook-sales'); ?></h2>
        <?php if ( $cover_image ) : ?>
            <div class="ebook-cover-image">
                <img src="<?php echo esc_url( $cover_image ); ?>" alt="<?php _e('Borítókép', 'ebook-sales'); ?>" style="max-width: 100%; height: auto;">
            </div>
        <?php else: ?>
            <p><?php _e('Nincs borítókép beállítva.', 'ebook-sales'); ?></p>
        <?php endif; ?>
    </div>
    <?php
}
add_action('edit_form_after_title', 'ebook_render_cover_meta_box');
