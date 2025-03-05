<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class Ebook_Post_Type {

    public function __construct() {
        add_action('init', array($this, 'register_ebook_post_type'));
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

function ebook_file_meta_box_callback($post) {
    wp_nonce_field('save_ebook_file', 'ebook_file_nonce');
    $ebook_file = get_post_meta($post->ID, '_ebook_file', true);
    ?>
    <p>
        <label for="ebook_file"><?php _e('Válassza ki az ebook fájlt (PDF, EPUB, MOBI):', 'ebook-sales'); ?></label><br>
        <input type="file" id="ebook_file" name="ebook_file" accept=".pdf,.epub,.mobi" />
    </p>
    <?php if ($ebook_file) : ?>
        <p>
            <?php _e('Jelenlegi fájl:', 'ebook-sales'); ?>
            <a href="<?php echo esc_url($ebook_file); ?>" target="_blank"><?php echo esc_html(basename($ebook_file)); ?></a>
        </p>
    <?php endif;
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
            // Állítsuk be a fájl URL-jét (a protected_ebooks mappa URL-je is elérhető lehet, de biztonsági beállításokat alkalmazhatsz .htaccess-ben)
            $file_url = $upload['baseurl'] . '/protected_ebooks/' . $filename;
            update_post_meta($post_id, '_ebook_file', esc_url_raw($file_url));
        } else {
            set_transient("ebook_file_error_$post_id", __('Fájl feltöltési hiba történt!', 'ebook-sales'), 45);
            wp_update_post(array('ID' => $post_id, 'post_status' => 'draft'));
            return;
        }
    } else {
        // Ha nincs fájl URL és a poszt publikus, hibaüzenettel állítjuk vissza a posztot vázlatba.
        $post = get_post($post_id);
        if ($post->post_status == 'publish') {
            set_transient("ebook_file_error_$post_id", __('Ebook fájl kötelező!', 'ebook-sales'), 45);
            wp_update_post(array('ID' => $post_id, 'post_status' => 'draft'));
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
    $columns['ebook_file'] = __('Ebook fájl', 'ebook-sales');
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
    }
}
