<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class Ebook_Post_Type {

    public function __construct() {
        add_action('init', array($this, 'register_ebook_post_type'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
    }

    public function register_ebook_post_type() {
        $labels = array(
            'name'                  => _x('Ebooks', 'Post Type General Name', 'ebook-sales'),
            'singular_name'         => _x('Ebook', 'Post Type Singular Name', 'ebook-sales'),
            'menu_name'             => __('Ebooks', 'ebook-sales'),
            'name_admin_bar'        => __('Ebook', 'ebook-sales'),
            'archives'              => __('Ebook Archives', 'ebook-sales'),
            'attributes'            => __('Ebook Attributes', 'ebook-sales'),
            'parent_item_colon'     => __('Parent Ebook:', 'ebook-sales'),
            'all_items'             => __('All Ebooks', 'ebook-sales'),
            'add_new_item'          => __('Add New Ebook', 'ebook-sales'),
            'add_new'               => __('Add New', 'ebook-sales'),
            'new_item'              => __('New Ebook', 'ebook-sales'),
            'edit_item'             => __('Edit Ebook', 'ebook-sales'),
            'update_item'           => __('Update Ebook', 'ebook-sales'),
            'view_item'             => __('View Ebook', 'ebook-sales'),
            'view_items'            => __('View Ebooks', 'ebook-sales'),
            'search_items'          => __('Search Ebook', 'ebook-sales'),
            'not_found'             => __('Not found', 'ebook-sales'),
            'not_found_in_trash'    => __('Not found in Trash', 'ebook-sales'),
            'featured_image'        => __('Featured Image', 'ebook-sales'),
            'set_featured_image'    => __('Set featured image', 'ebook-sales'),
            'remove_featured_image' => __('Remove featured image', 'ebook-sales'),
            'use_featured_image'    => __('Use as featured image', 'ebook-sales'),
            'insert_into_item'      => __('Insert into ebook', 'ebook-sales'),
            'uploaded_to_this_item' => __('Uploaded to this ebook', 'ebook-sales'),
            'items_list'            => __('Ebooks list', 'ebook-sales'),
            'items_list_navigation' => __('Ebooks list navigation', 'ebook-sales'),
            'filter_items_list'     => __('Filter ebooks list', 'ebook-sales'),
        );
        $args = array(
            'label'                 => __('Ebook', 'ebook-sales'),
            'description'           => __('Ebook post type for selling ebooks', 'ebook-sales'),
            'labels'                => $labels,
            'supports'              => array('title', 'editor', 'thumbnail'),
            'taxonomies'            => array('category', 'post_tag'),
            'hierarchical'          => false,
            'public'                => true,
            'show_ui'               => true,
            'show_in_menu'          => 'ebook-sales', // Fontos: a saját menünk alá kerüljön
            'menu_position'         => 5,
            'show_in_admin_bar'     => true,
            'show_in_nav_menus'     => true,
            'can_export'            => true,
            'has_archive'           => true,
            'exclude_from_search'   => false,
            'publicly_queryable'    => true,
            'capability_type'       => 'post',
        );
        register_post_type('ebook', $args);
    }

    // Enqueue-oljuk a pluginhoz tartozó admin JS fájlt
    public function enqueue_admin_scripts() {
        // Csak az Ebook poszt szerkesztése oldalain töltsük be
        $screen = get_current_screen();
        if (isset($screen->post_type) && 'ebook' === $screen->post_type) {
            wp_enqueue_script(
                'ebook-file-upload',
                plugin_dir_url(__FILE__) . '../assets/js/ebook-file-upload.js',
                array('jquery'),
                '1.0',
                true
            );

            wp_localize_script('ebook-file-upload', 'ebook_post_data', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce'    => wp_create_nonce('save_ebook_file'),
                // A post_id itt később dinamikusan kerül beállításra is, ha szükséges
                'post_id'  => get_the_ID(),
            ));
        }
    }
}

new Ebook_Post_Type();

// Metabox regisztrálása az ebook posztokhoz
add_action('add_meta_boxes', 'ebook_add_meta_box');
function ebook_add_meta_box() {
    add_meta_box(
        'ebook_file_metabox',
        __('Ebook fájl feltöltése', 'ebook-sales'),
        'ebook_file_meta_box_callback',
        'ebook',
        'normal',
        'default'
    );
}
/** ==========================
 *  AUTOMATIKUS KÉPKEZELÉS
 * ========================== */

// 📌 Automatikusan beállítja a kiemelt képet a borító képből, ha nincs beállítva
function set_featured_image_if_not_set($post_id) {
    if (!has_post_thumbnail($post_id)) {
        $cover = get_post_meta($post_id, '_cover_image', true);
        if ($cover) {
            $attachment_id = attachment_url_to_postid($cover);
            if (!$attachment_id) {
                global $wpdb;
                $attachment_id = $wpdb->get_var($wpdb->prepare(
                    "SELECT ID FROM $wpdb->posts WHERE guid=%s AND post_type='attachment'", 
                    esc_url($cover)
                ));
            }
            if ($attachment_id) {
                set_post_thumbnail($post_id, $attachment_id);
            }
        }
    }
}
add_action('save_post_ebook', 'set_featured_image_if_not_set');

function ebook_file_meta_box_callback($post) {
    wp_nonce_field('save_ebook_file', 'ebook_file_nonce');
    $ebook_file   = get_post_meta($post->ID, '_ebook_file', true);
    $cover_image  = get_post_meta($post->ID, '_cover_image', true);
    $ebook_price  = get_post_meta($post->ID, '_ebook_price', true);
    $ebook_currency = get_post_meta($post->ID, '_ebook_currency', true);
    if ( empty($ebook_currency) ) {
        $ebook_currency = 'USD';
    }
    ?>
    <p>
        <label for="ebook_file"><?php _e('Válassza ki az ebook fájlt (PDF, EPUB, MOBI):', 'ebook-sales'); ?></label><br>
        <input type="file" id="ebook_file" name="ebook_file" accept=".pdf,.epub,.mobi" />
    </p>
    <p>
        <label for="cover_image"><?php _e('Válassza ki a borító képet (JPG, JPEG, PNG, GIF):', 'ebook-sales'); ?></label><br>
        <input type="file" id="cover_image" name="cover_image" accept=".jpg,.jpeg,.png,.gif" />
    </p>
    <!-- Új mezők: Ebook ára és devizanem -->
    <p>
        <label for="ebook_price"><?php _e('Ebook ára:', 'ebook-sales'); ?></label><br>
        <input type="number" min="0" step="0.01" id="ebook_price" name="ebook_price" value="<?php echo esc_attr($ebook_price); ?>" required />
    </p>
    <p>
        <label for="ebook_currency"><?php _e('Devizanem:', 'ebook-sales'); ?></label><br>
        <select id="ebook_currency" name="ebook_currency">
            <option value="USD" <?php selected($ebook_currency, 'USD'); ?>>USD</option>
            <option value="EUR" <?php selected($ebook_currency, 'EUR'); ?>>Euro</option>
            <option value="GBP" <?php selected($ebook_currency, 'GBP'); ?>>GBP</option>
        </select>
    </p>
    <p>
        <button type="button" id="ebook_file_save" class="button"><?php _e('Mentés', 'ebook-sales'); ?></button>
    </p>
    <div id="ebook_file_message"></div>
    <?php if ($ebook_file) : ?>
        <p>
            <?php _e('Jelenlegi ebook fájl:', 'ebook-sales'); ?>
            <a href="<?php echo esc_url($ebook_file); ?>" target="_blank"><?php echo esc_html(basename($ebook_file)); ?></a>
        </p>
    <?php endif; ?>
    <?php if ($cover_image) : ?>
        <p>
            <?php _e('Jelenlegi borító kép:', 'ebook-sales'); ?>
            <a href="<?php echo esc_url($cover_image); ?>" target="_blank"><?php echo esc_html(basename($cover_image)); ?></a>
        </p>
    <?php endif; ?>
    <script type="text/javascript" src="<?php echo plugin_dir_url(__FILE__); ?>../assets/js/ebook-file-upload.js"></script>
    <?php
}

add_action('save_post', 'save_ebook_file_meta_box');
function save_ebook_file_meta_box($post_id) {
    // Ellenőrizd a nonce-t, autosave-t és jogosultságot
    if (!isset($_POST['ebook_file_nonce']) || !wp_verify_nonce($_POST['ebook_file_nonce'], 'save_ebook_file')) {
        return;
    }
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }
    
    // Ebook ár és devizanem mentése
    if (isset($_POST['ebook_price'])) {
        $price_input = sanitize_text_field($_POST['ebook_price']);
        if ( $price_input === '' || floatval($price_input) === 0 ) {
            // Ha üres vagy 0, akkor mentsük 0-ként (adatbázisban 0)
            update_post_meta($post_id, '_ebook_price', 0);
        } elseif ( floatval($price_input) < 0 ) {
            // Negatív érték esetén hiba: ne engedje menteni
            set_transient("ebook_file_error_$post_id", __('Az ebook ára nem lehet negatív érték!', 'ebook-sales'), 45);
            wp_update_post(array('ID' => $post_id, 'post_status' => 'draft'));
            return;
        } else {
            update_post_meta($post_id, '_ebook_price', floatval($price_input));
        }
    } else {
        set_transient("ebook_file_error_$post_id", __('Az ebook ára kötelező!', 'ebook-sales'), 45);
        wp_update_post(array('ID' => $post_id, 'post_status' => 'draft'));
        return;
    }

    if (isset($_POST['ebook_currency'])) {
        $allowed_currencies = array('USD', 'EUR', 'GBP');
        $currency = sanitize_text_field($_POST['ebook_currency']);
        if (!in_array($currency, $allowed_currencies)) {
            $currency = 'USD';
        }
        update_post_meta($post_id, '_ebook_currency', $currency);
    }
    
    // Ha új fájl lett kiválasztva
    if (isset($_FILES['ebook_file']) && !empty($_FILES['ebook_file']['name'])) {
        // Engedélyezett kiterjesztések ellenőrzése
        $allowed_exts = array('pdf', 'epub', 'mobi');
        $filename = sanitize_file_name($_FILES['ebook_file']['name']);
        $file_ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        if (!in_array($file_ext, $allowed_exts)) {
            set_transient("ebook_file_error_$post_id", __('Kérjük, tölts fel PDF, EPUB vagy MOBI típusú fájlt!', 'ebook-sales'), 45);
            wp_update_post(array('ID' => $post_id, 'post_status' => 'draft'));
            return;
        }

        // Prostocollált feltöltési mappa meghatározása
        $upload = wp_upload_dir();
        $target_dir = $upload['basedir'] . '/protected_ebooks';
        if (!file_exists($target_dir)) {
            wp_mkdir_p($target_dir);
        }
        // Biztos, hogy egyedi a fájlnév
        $filename = wp_unique_filename($target_dir, $filename);
        $target_file = $target_dir . '/' . $filename;

        // Fájl feltöltése
        if (move_uploaded_file($_FILES['ebook_file']['tmp_name'], $target_file)) {
            // Állítsuk be a fájl URL-jét
            $file_url = $upload['baseurl'] . '/protected_ebooks/' . $filename;
            update_post_meta($post_id, '_ebook_file', esc_url_raw($file_url));
        } else {
            set_transient("ebook_file_error_$post_id", __('Fájl feltöltési hiba történt!', 'ebook-sales'), 45);
            wp_update_post(array('ID' => $post_id, 'post_status' => 'draft'));
            return;
        }
    }
    // Ha nincs fájl URL és a poszt publikus, állítsuk vissza a posztot vázlatba.
    $post = get_post($post_id);
    if ($post->post_status == 'publish') {
        $ebook_file = get_post_meta($post_id, '_ebook_file', true);
        $cover_image = get_post_meta($post_id, '_cover_image', true);
        if (empty($ebook_file) || empty($cover_image)) {
            set_transient("ebook_file_error_$post_id", __('Az ebook fájl és a borító kép feltöltése kötelező a publikáláshoz!', 'ebook-sales'), 45);
            wp_update_post(array('ID' => $post_id, 'post_status' => 'draft'));
            return;
        }
    }
}

add_action('admin_notices', 'ebook_file_admin_notice');
function ebook_file_admin_notice() {
    global $post;
    if (isset($post->ID)) {
        if ($error = get_transient("ebook_file_error_{$post->ID}")) {
            echo '<div class="error notice"><p>' . esc_html($error) . '</p></div>';
            delete_transient("ebook_file_error_{$post->ID}");
        }
    }
}

// Egyedi oszlop hozzáadása a 'ebook' post listához
add_filter('manage_ebook_posts_columns', 'set_custom_ebook_columns');
function set_custom_ebook_columns($columns) {
    // Megtartjuk a meglévő oszlopokat, majd hozzáadjuk az ebook fájl, borító kép, ár és devizanem oszlopokat.
    $columns['ebook_file']    = __('Ebook fájl', 'ebook-sales');
    $columns['cover_image']   = __('Borító kép', 'ebook-sales');
    $columns['ebook_price']   = __('Ár', 'ebook-sales');
    $columns['ebook_currency'] = __('Devizanem', 'ebook-sales');
    return $columns;
}

add_action('manage_ebook_posts_custom_column', 'custom_ebook_column', 10, 2);
function custom_ebook_column($column, $post_id) {
    if ($column == 'ebook_file') {
        $ebook_file = get_post_meta($post_id, '_ebook_file', true);
        if ($ebook_file) {
            echo '<a href="' . esc_url($ebook_file) . '" target="_blank">' . __('Megtekint', 'ebook-sales') . '</a>';
        } else {
            _e('Nincs fájl', 'ebook-sales');
        }
    } elseif ($column == 'cover_image') {
        $cover_image = get_post_meta($post_id, '_cover_image', true);
        if ($cover_image) {
            echo '<a href="' . esc_url($cover_image) . '" target="_blank">' . __('Megtekint', 'ebook-sales') . '</a>';
        } else {
            _e('Nincs kép', 'ebook-sales');
        }
    } elseif ($column == 'ebook_price') {
        $price = get_post_meta($post_id, '_ebook_price', true);
        // Ha a mentett érték 0, akkor "Free" jelenjen meg
        echo ($price === '0' || $price === 0) ? __('Free', 'ebook-sales') : esc_html($price);
    } elseif ($column == 'ebook_currency') {
        $currency = get_post_meta($post_id, '_ebook_currency', true);
        echo $currency ? esc_html($currency) : __('Nincs megadva', 'ebook-sales');
    }
}

add_action('wp_ajax_save_ebook_file_ajax', 'handle_save_ebook_file_ajax');

require_once plugin_dir_path( __FILE__ ) . 'class-ebook-post-file.php';

function handle_save_ebook_file_ajax() {
    // Ellenőrzés: nonce és post ID
    if (!isset($_POST['ebook_file_nonce']) || !wp_verify_nonce($_POST['ebook_file_nonce'], 'save_ebook_file')) {
        wp_send_json_error(array('message' => __('Érvénytelen nonce!', 'ebook-sales')));
    }
    if (!isset($_POST['post_id'])) {
        wp_send_json_error(array('message' => __('Hiányzó post ID!', 'ebook-sales')));
    }
    $post_id = intval($_POST['post_id']);

    // Ellenőrizzük, hogy mindkét fájl megfelelően ki van-e választva
    if (
        !isset($_FILES['ebook_file']) || $_FILES['ebook_file']['error'] !== UPLOAD_ERR_OK ||
        !isset($_FILES['cover_image']) || $_FILES['cover_image']['error'] !== UPLOAD_ERR_OK
    ) {
        wp_send_json_error(array('message' => __('Kérjük, válassza ki mind az ebook fájlt, mind a borító képet!', 'ebook-sales')));
    }

    // Engedélyezett kiterjesztések és MIME típusok
    $ebook_allowed_exts  = array('pdf', 'epub', 'mobi');
    $cover_allowed_exts  = array('jpg', 'jpeg', 'png', 'gif');
    $ebook_allowed_mimes = array('application/pdf', 'application/epub+zip', 'application/x-mobipocket-ebook');
    $cover_allowed_mimes = array('image/jpeg', 'image/png', 'image/gif');

    // Ebook fájl ellenőrzése
    $ebook_filename = sanitize_file_name($_FILES['ebook_file']['name']);
    $ebook_file_ext = strtolower(pathinfo($ebook_filename, PATHINFO_EXTENSION));
    $ebook_mime     = mime_content_type($_FILES['ebook_file']['tmp_name']);
    if (!in_array($ebook_file_ext, $ebook_allowed_exts) || !in_array($ebook_mime, $ebook_allowed_mimes)) {
        wp_send_json_error(array('message' => __('Kérjük, töltsön fel érvényes ebook fájlt (PDF, EPUB, MOBI)!', 'ebook-sales')));
    }

    // Borító kép ellenőrzése
    $cover_filename = sanitize_file_name($_FILES['cover_image']['name']);
    $cover_file_ext = strtolower(pathinfo($cover_filename, PATHINFO_EXTENSION));
    $cover_mime     = mime_content_type($_FILES['cover_image']['tmp_name']);
    if (!in_array($cover_file_ext, $cover_allowed_exts) || !in_array($cover_mime, $cover_allowed_mimes)) {
        wp_send_json_error(array('message' => __('Kérjük, töltsön fel érvényes borító képfájlt (JPG, JPEG, PNG, GIF)!', 'ebook-sales')));
    }

    // Feltöltési mappák létrehozása
    $upload = wp_upload_dir();
    $protected_dir  = $upload['basedir'] . '/protected_ebooks';
    $covers_dir     = $upload['basedir'] . '/ebook_covers';
    if (!file_exists($protected_dir) && !wp_mkdir_p($protected_dir)) {
        wp_send_json_error(array('message' => __('Nem sikerült létrehozni a protected_ebooks mappát.', 'ebook-sales')));
    }
    if (!file_exists($covers_dir) && !wp_mkdir_p($covers_dir)) {
        wp_send_json_error(array('message' => __('Nem sikerült létrehozni az ebook_covers mappát.', 'ebook-sales')));
    }

    // Ebook fájl mentése
    $ebook_upload = Ebook_Post_File::upload_file($_FILES['ebook_file'], array('pdf','epub','mobi'), array('application/pdf', 'application/epub+zip', 'application/x-mobipocket-ebook'), $protected_dir);

    if (is_wp_error($ebook_upload)) {
        wp_send_json_error(array('message' => $ebook_upload->get_error_message()));
    }
    update_post_meta($post_id, '_ebook_file', $ebook_upload['file_url']);

    // Borító kép mentése
    $cover_upload = Ebook_Post_File::upload_file($_FILES['cover_image'], array('jpg','jpeg','png','gif'), array('image/jpeg','image/png','image/gif'), $covers_dir);
    if (is_wp_error($cover_upload)) {
        wp_send_json_error(array('message' => $cover_upload->get_error_message()));
    }

    // Borító kép átméretezése
    $cover_result = Ebook_Post_File::process_cover_image($cover_upload['target_file']);
    if (is_wp_error($cover_result)) {
        wp_send_json_error(array('message' => $cover_result->get_error_message()));
    }
    update_post_meta($post_id, '_cover_image', $cover_result['cover_file_url']);

    // Attachment létrehozása és featured image beállítása
    $attachment_id = Ebook_Post_File::create_cover_attachment($post_id, $cover_upload['target_file'], $cover_result['cover_file_url']);

    wp_send_json_success(array(
        'message' => sprintf(
            __('Feltöltés sikeres: Ebook: %s; Borító: %s', 'ebook-sales'),
            esc_html($ebook_upload['file_url']),
            esc_html($cover_result['cover_file_url'])
        )
    ));
}

add_filter('post_thumbnail_html', 'auto_set_post_thumbnail', 10, 5);
function auto_set_post_thumbnail( $html, $post_id, $post_thumbnail_id, $size, $attr ) {
    // DOING_AUTOSAVE ellenőrzése: autosave vagy AJAX mentés esetén ne módosítsuk a featured image-t
    if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) {
        return $html;
    }

    if ( has_post_thumbnail( $post_id ) ) {
        return get_the_post_thumbnail( $post_id, $size, $attr );
    }
    return '<img src="' . plugin_dir_url(__FILE__) . '../assets/images/default-thumbnail.jpg" alt="Alapértelmezett kép">';
}

/**
 * Segédfüggvény, amely beállítja a featured image-t, ha még nincs.
 */
function maybe_set_featured_image( $post_id ) {
    if ( has_post_thumbnail( $post_id ) ) {
        return;
    }
    $cover = get_post_meta( $post_id, '_cover_image', true );
    if ( ! $cover ) {
        return;
    }
    $attachment_id = attachment_url_to_postid( $cover );
    if ( ! $attachment_id ) {
        global $wpdb;
        $attachment_id = $wpdb->get_var( $wpdb->prepare(
            "SELECT ID FROM $wpdb->posts WHERE guid = %s AND post_type = 'attachment'",
            esc_url($cover)
        ));
    }
    if ( $attachment_id ) {
        set_post_thumbnail( $post_id, $attachment_id );
    }
}