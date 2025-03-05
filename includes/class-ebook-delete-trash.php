<?php
// filepath: /home/telferenc/GitMunkamenetek/ebook-sales/includes/class-ebook-delete-trash.php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Ebook_Delete_Trash {

    public function __construct() {
        add_action('before_delete_post', array( $this, 'delete_ebook_files' ));
    }

    public function delete_ebook_files( $post_id ) {
        $post = get_post( $post_id );
        if ( ! $post || $post->post_type !== 'ebook' ) {
            return;
        }

        // Lekérjük a meta adatokat
        $ebook_file_url = get_post_meta( $post_id, '_ebook_file', true );
        $cover_file_url = get_post_meta( $post_id, '_cover_image', true );

        if ( $ebook_file_url ) {
            $this->delete_file_by_url( $ebook_file_url );
        }

        if ( $cover_file_url ) {
            $this->delete_file_by_url( $cover_file_url );
        }
    }

    private function delete_file_by_url( $file_url ) {
        $upload_dir = wp_upload_dir();
        $upload_baseurl = $upload_dir['baseurl'];
        $upload_basedir = $upload_dir['basedir'];
        if ( strpos( $file_url, $upload_baseurl ) !== false ) {
            $relative_path = str_replace( $upload_baseurl, '', $file_url );
            $file_path = $upload_basedir . $relative_path;
            if ( file_exists( $file_path ) ) {
                unlink( $file_path );
            }
        }
    }
}

new Ebook_Delete_Trash();