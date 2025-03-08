<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Ebook_Post_File {

    /**
     * Ellenőrzi és feltölti a fájlt a megadott mappába.
     *
     * @param array  $file         A $_FILES['...'] elem.
     * @param array  $allowed_exts Megengedett kiterjesztések.
     * @param array  $allowed_mimes Megengedett MIME típusok.
     * @param string $target_dir   A cél mappa elérési útja.
     *
     * @return array|WP_Error Array('filename' => ..., 'file_url' => ..., 'target_file' => ...) vagy WP_Error hiba esetén.
     */
    public static function upload_file( $file, $allowed_exts, $allowed_mimes, $target_dir ) {
        $filename = sanitize_file_name( $file['name'] );
        $file_ext = strtolower( pathinfo( $filename, PATHINFO_EXTENSION ) );
        $mime     = mime_content_type( $file['tmp_name'] );
        if ( ! in_array( $file_ext, $allowed_exts, true ) || ! in_array( $mime, $allowed_mimes, true ) ) {
            return new WP_Error( 'invalid_file', __( 'Érvénytelen fájltípus!', 'ebook-sales' ) );
        }
        if ( ! file_exists( $target_dir ) ) {
            wp_mkdir_p( $target_dir );
        }
        $unique_name = wp_unique_filename( $target_dir, $filename );
        $target_file = trailingslashit( $target_dir ) . $unique_name;
        if ( ! move_uploaded_file( $file['tmp_name'], $target_file ) ) {
            return new WP_Error( 'upload_error', __( 'Feltöltési hiba történt!', 'ebook-sales' ) );
        }
        $upload = wp_upload_dir();
        $file_url = trailingslashit( $upload['baseurl'] ) . str_replace( $upload['basedir'], '', $target_file );
        return array(
            'filename'    => $unique_name,
            'file_url'    => esc_url_raw( $file_url ),
            'target_file' => $target_file
        );
    }

    /**
     * Átméretezi a borító képet 16:9 arányra.
     *
     * @param string $cover_target_file A borító kép elérési útja.
     *
     * @return array|WP_Error Array('cover_target_file' => ..., 'cover_file_url' => ...) vagy WP_Error.
     */
    public static function process_cover_image( $cover_target_file ) {
        $editor = wp_get_image_editor( $cover_target_file );
        if ( is_wp_error( $editor ) ) {
            return $editor;
        }
        $size         = $editor->get_size();
        $orig_width   = $size['width'];
        $orig_height  = $size['height'];
        $desired_width = round( $orig_height * ( 16 / 9 ) );
        if ( $orig_width > $desired_width ) {
            // Ha a kép túl széles, cropoljuk középre.
            $src_x = round( ( $orig_width - $desired_width ) / 2 );
            $editor->crop( $src_x, 0, $desired_width, $orig_height );
        } elseif ( $orig_width < $desired_width ) {
            // Ha a kép keskenyebb, GD vagy Imagick alapján állítsuk be a desired_width-et.
            if ( method_exists( $editor, 'set_canvas_size' ) ) {
                $editor->set_canvas_size(
                    $desired_width,
                    $orig_height,
                    'center',
                    array( 'r' => 0, 'g' => 0, 'b' => 0, 'a' => 127 )
                );
            } else {
                if ( method_exists( $editor, 'get_image_object' ) ) {
                    $im = $editor->get_image_object();
                } else {
                    $reflection = new ReflectionClass( $editor );
                    $property   = $reflection->getProperty( 'image' );
                    $property->setAccessible( true );
                    $im = $property->getValue( $editor );
                }
                $x_offset = floor( ( $desired_width - $orig_width ) / 2 );
                $new      = new Imagick();
                $new->newImage( (int) $desired_width, (int) $orig_height, new ImagickPixel( 'transparent' ) );
                $new->setImageFormat( $im->getImageFormat() );
                $new->compositeImage( $im, Imagick::COMPOSITE_OVER, (int) $x_offset, 0 );
                $reflection = new ReflectionClass( $editor );
                $property   = $reflection->getProperty( 'image' );
                $property->setAccessible( true );
                $property->setValue( $editor, $new );
            }
        }
        $saved = $editor->save( $cover_target_file );
        if ( is_wp_error( $saved ) ) {
            return $saved;
        }
        $upload = wp_upload_dir();
        $cover_file_url = trailingslashit( $upload['baseurl'] ) . '/ebook_covers/' . basename( $saved['path'] );
        return array(
            'cover_target_file' => $saved['path'],
            'cover_file_url'    => esc_url_raw( $cover_file_url )
        );
    }

    /**
     * Létrehozza a borító kép attachment-ját és visszaállítja a featured image-t.
     *
     * @param int    $post_id           A poszt azonosítója.
     * @param string $cover_target_file A borító kép elérési útja.
     * @param string $cover_file_url    A borító kép URL-je.
     *
     * @return int|WP_Error Attachment ID vagy hiba esetén.
     */
    public static function create_cover_attachment( $post_id, $cover_target_file, $cover_file_url ) {
        require_once( ABSPATH . 'wp-admin/includes/file.php' );
        require_once( ABSPATH . 'wp-admin/includes/media.php' );
        $attachment = array(
            'guid'           => $cover_file_url,
            'post_mime_type' => wp_check_filetype( $cover_target_file )['type'],
            'post_title'     => sanitize_file_name( basename( $cover_target_file ) ),
            'post_content'   => '',
            'post_status'    => 'inherit'
        );
        $attachment_id = wp_insert_attachment( $attachment, $cover_target_file, $post_id );
        if ( ! is_wp_error( $attachment_id ) ) {
            $attach_data = wp_generate_attachment_metadata( $attachment_id, $cover_target_file );
            wp_update_attachment_metadata( $attachment_id, $attach_data );
            // Beállítjuk a featured image-t, ha még nincs
            if ( ! has_post_thumbnail( $post_id ) ) {
                set_post_thumbnail( $post_id, $attachment_id );
            }
        }
        return $attachment_id;
    }
}