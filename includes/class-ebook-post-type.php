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
    $cover_image = get_post_meta($post->ID, '_cover_image', true);
    ?>
    <p>
        <label for="ebook_file"><?php _e('Válassza ki az ebook fájlt (PDF, EPUB, MOBI):', 'ebook-sales'); ?></label><br>
        <input type="file" id="ebook_file" name="ebook_file" accept=".pdf,.epub,.mobi" />
    </p>
    <p>
        <label for="cover_image"><?php _e('Válassza ki a borító képet (JPG, JPEG, PNG, GIF):', 'ebook-sales'); ?></label><br>
        <input type="file" id="cover_image" name="cover_image" accept=".jpg,.jpeg,.png,.gif" />
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
    <script type="text/javascript">
    jQuery(document).ready(function($){
        $('#ebook_file_save').on('click', function(e) {
            e.preventDefault();
            var ebookInput = $('#ebook_file')[0];
            var coverInput = $('#cover_image')[0];
            if (ebookInput.files.length === 0 || coverInput.files.length === 0) {
                alert('<?php _e('Kérjük, válassza ki mind az ebook fájlt, mind a borító képet!', 'ebook-sales'); ?>');
                return;
            }
            var ebookFile = ebookInput.files[0];
            var coverFile = coverInput.files[0];
            var formData = new FormData();
            formData.append('ebook_file', ebookFile);
            formData.append('cover_image', coverFile);
            formData.append('post_id', <?php echo $post->ID; ?>);
            formData.append('action', 'save_ebook_file_ajax');
            formData.append('ebook_file_nonce', $('#ebook_file_nonce').val());
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response){
                    if(response.success) {
                        $('#ebook_file_message').html('<span style="color:green;">' + response.data.message + '</span>');
                    } else {
                        $('#ebook_file_message').html('<span style="color:red;">' + response.data.message + '</span>');
                    }
                },
                error: function(){
                    $('#ebook_file_message').html('<span style="color:red;"><?php _e("Fájl feltöltési hiba", "ebook-sales"); ?></span>');
                }
            });
        });
    });
    </script>
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
    // Megtartjuk a meglévő oszlopokat, majd hozzáadjuk az ebook fájl és borító kép oszlopokat.
    $columns['ebook_file'] = __('Ebook fájl', 'ebook-sales');
    $columns['cover_image'] = __('Borító kép', 'ebook-sales');
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
    }
}

add_action('wp_ajax_save_ebook_file_ajax', 'handle_save_ebook_file_ajax');
function handle_save_ebook_file_ajax(){
    // Ellenőrzés: nonce és post ID
    if ( ! isset($_POST['ebook_file_nonce']) || ! wp_verify_nonce($_POST['ebook_file_nonce'], 'save_ebook_file') ) {
        wp_send_json_error(array('message' => __('Érvénytelen nonce!', 'ebook-sales')));
    }
    if (!isset($_POST['post_id'])) {
        wp_send_json_error(array('message' => __('Hiányzó post ID!', 'ebook-sales')));
    }
    $post_id = intval($_POST['post_id']);
    
    // Ellenőrizzük, hogy mindkét fájl ki van-e választva
    if (!isset($_FILES['ebook_file']) || empty($_FILES['ebook_file']['name']) ||
        !isset($_FILES['cover_image']) || empty($_FILES['cover_image']['name'])) {
        wp_send_json_error(array('message' => __('Kérjük, válassza ki mind az ebook fájlt, mind a borító képet!', 'ebook-sales')));
    }
    
    // Ellenőrzés: engedélyezett kiterjesztések
    $ebook_allowed_exts = array('pdf', 'epub', 'mobi');
    $cover_allowed_exts = array('jpg', 'jpeg', 'png', 'gif');
    
    // Ebook fájl ellenőrzése
    $ebook_filename = sanitize_file_name($_FILES['ebook_file']['name']);
    $ebook_file_ext = strtolower(pathinfo($ebook_filename, PATHINFO_EXTENSION));
    if (!in_array($ebook_file_ext, $ebook_allowed_exts)) {
        wp_send_json_error(array('message' => __('Kérjük, töltsön fel PDF, EPUB vagy MOBI típusú ebook fájlt!', 'ebook-sales')));
    }
    
    // Borító kép ellenőrzése
    $cover_filename = sanitize_file_name($_FILES['cover_image']['name']);
    $cover_file_ext = strtolower(pathinfo($cover_filename, PATHINFO_EXTENSION));
    if (!in_array($cover_file_ext, $cover_allowed_exts)) {
        wp_send_json_error(array('message' => __('Kérjük, töltsön fel érvényes képfájlt (JPG, JPEG, PNG, GIF)!', 'ebook-sales')));
    }
    
    // Célmappa: wp-content/uploads/protected_ebooks
    $upload = wp_upload_dir();
    $target_dir = $upload['basedir'] . '/protected_ebooks';
    if (!file_exists($target_dir)) {
        if (!wp_mkdir_p($target_dir)) {
            wp_send_json_error(array('message' => __('Nem sikerült létrehozni a célmappát.', 'ebook-sales')));
        }
    }
    
    // Ebook fájl: használjuk a wp_unique_filename eredményét (amely már tartalmazza a kiterjesztést)
    $ebook_unique_name = wp_unique_filename($target_dir, $ebook_filename);
    $ebook_target_file = $target_dir . '/' . $ebook_unique_name;
    
    // Borító kép: használjuk az ebook fájl base nevét (kiterjesztés nélkül) az új névhez,
    // majd fűzzük hozzá a cover kép saját kiterjesztését.
    $ebook_base = pathinfo($ebook_unique_name, PATHINFO_FILENAME);
    $cover_unique_name = $ebook_base . '.' . $cover_file_ext;
    $cover_target_file = $target_dir . '/' . $cover_unique_name;
    
    // Fájlok feltöltése – csak akkor, ha mindkettő sikeres
    if (move_uploaded_file($_FILES['ebook_file']['tmp_name'], $ebook_target_file) &&
        move_uploaded_file($_FILES['cover_image']['tmp_name'], $cover_target_file)) {
        
        // Állítsuk be a fájl URL-eket
        $ebook_file_url = $upload['baseurl'] . '/protected_ebooks/' . $ebook_unique_name;
        $cover_file_url = $upload['baseurl'] . '/protected_ebooks/' . $cover_unique_name;
        
        update_post_meta($post_id, '_ebook_file', esc_url_raw($ebook_file_url));
        update_post_meta($post_id, '_cover_image', esc_url_raw($cover_file_url));
        
        wp_send_json_success(array('message' => sprintf(__('Feltöltés sikeres: Ebook: %s; Borító: %s', 'ebook-sales'), $ebook_unique_name, $cover_unique_name)));
    } else {
        wp_send_json_error(array('message' => __('Fájl feltöltési hiba történt!', 'ebook-sales')));
    }
}