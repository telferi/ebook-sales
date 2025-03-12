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
// Új: Regisztráljuk az AI tartalom generálás meta box-ot
add_action('add_meta_boxes', 'ai_content_add_meta_box');

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

function ai_content_add_meta_box() {
    add_meta_box(
        'ai_content_meta_box',                        // ID
        __('Ai tartalom generálás', 'ebook-sales'),     // Title
        'ai_content_meta_box_callback',                 // Callback
        'ebook',                                        // Screen
        'normal',                                       // Context
        'default'                                       // Priority
    );
}

function ebook_file_meta_box_callback($post) {
    wp_nonce_field('save_ebook_file', 'ebook_file_nonce');
    $ebook_file     = get_post_meta($post->ID, '_ebook_file', true);
    $cover_image    = get_post_meta($post->ID, '_cover_image', true);
    $ebook_price    = get_post_meta($post->ID, 'ebook_price', true);
    if ($ebook_price === '') {
        $ebook_price = 0;
    }
    $ebook_currency = get_post_meta($post->ID, 'ebook_currency', true);
    if ($ebook_currency === '') {
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
    <!-- Új mezők: Ár és Pénznem, step="0.01" -->
    <p>
        <label for="ebook_price"><?php _e('Ár', 'ebook-sales'); ?></label><br>
        <input type="number" id="ebook_price" name="ebook_price" value="<?php echo esc_attr($ebook_price); ?>" min="0" step="0.01" />
    </p>
    <p>
        <label for="ebook_currency"><?php _e('Pénznem', 'ebook-sales'); ?></label><br>
        <select id="ebook_currency" name="ebook_currency">
            <option value="USD" <?php selected($ebook_currency, 'USD'); ?>>USD</option>
            <option value="EURO" <?php selected($ebook_currency, 'EURO'); ?>>EURO</option>
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
    <script type="text/javascript">
    jQuery(document).ready(function($){
        <?php if ($ebook_file && $cover_image) : ?>
           $('#ebook_file_save, #ebook_file, #cover_image, label[for="ebook_file"], label[for="cover_image"]').hide();
        <?php endif; ?>
        
        $('#ebook_file_save').on('click', function(e) {
            e.preventDefault();
            
            var titleField = $('#title');
            var ebookInput = $('#ebook_file')[0];
            if ( ebookInput.files.length === 0 ) {
                alert('<?php _e('Kérjük, válassza ki az ebook fájlt!', 'ebook-sales'); ?>');
                return;
            }
            var file = ebookInput.files[0];
            if ($.trim(titleField.val()) === '') {
                var filename = file.name;
                var baseName = filename.replace(/\.[^/.]+$/, "");
                var newTitle = baseName.charAt(0).toUpperCase() + baseName.slice(1);
                titleField.val(newTitle);
            }
            
            var coverInput = $('#cover_image')[0];
            if (coverInput.files.length === 0) {
                alert('<?php _e('Kérjük, válassza ki a borító képet!', 'ebook-sales'); ?>');
                return;
            }
            var coverFile = coverInput.files[0];
            var formData = new FormData();
            formData.append('ebook_file', file);
            formData.append('cover_image', coverFile);
            // Új mezők elküldése AJAX kérésben
            formData.append('ebook_price', $('#ebook_price').val());
            formData.append('ebook_currency', $('#ebook_currency').val());
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
                        $('#ebook_file_save, #ebook_file, #cover_image, label[for="ebook_file"], label[for="cover_image"]').hide();
                    } else {
                        $('#ebook_file_message').html('<span style="color:red;">' + response.data.message + '</span>');
                    }
                },
                error: function(){
                    $('#ebook_file_message').html('<span style="color:red;"><?php _e("Fájl feltöltési hiba", "ebook-sales"); ?></span>');
                }
            });
        });
        $('#save-post, #publish').on('click', function(){
             $('#ebook_file_save, #ebook_file, #cover_image, label[for="ebook_file"], label[for="cover_image"]').hide();
        });
    });
    </script>
    <?php
}

function ai_content_meta_box_callback($post) {
    wp_nonce_field('save_ai_content', 'ai_content_nonce');
    $ai_content       = get_post_meta($post->ID, 'ai_content', true);
    $ai_writing_style = get_post_meta($post->ID, 'ai_writing_style', true);
    $ai_writing_tone  = get_post_meta($post->ID, 'ai_writing_tone', true);
    ?>
    <p>
        <input type="text" name="ai_content" id="ai_content" value="<?php echo esc_attr($ai_content); ?>" class="widefat" placeholder="<?php _e('Írja be a promptot...', 'ebook-sales'); ?>" />
    </p>
    <p>
        <label for="ai_writing_style"><?php _e('Írás stílusa (Writing Style):', 'ebook-sales'); ?></label>
        <select name="ai_writing_style" id="ai_writing_style" class="widefat">
            <option value="Tájékoztató" <?php selected($ai_writing_style, 'Tájékoztató'); ?>>Tájékoztató</option>
            <option value="Leíró" <?php selected($ai_writing_style, 'Leíró'); ?>>Leíró</option>
            <option value="Alkotó" <?php selected($ai_writing_style, 'Alkotó'); ?>>Alkotó</option>
            <option value="Elbeszélés" <?php selected($ai_writing_style, 'Elbeszélés'); ?>>Elbeszélés</option>
            <option value="Meggyőző" <?php selected($ai_writing_style, 'Meggyőző'); ?>>Meggyőző</option>
            <option value="Fényvisszaverő" <?php selected($ai_writing_style, 'Fényvisszaverő'); ?>>Fényvisszaverő</option>
            <option value="Érvelő" <?php selected($ai_writing_style, 'Érvelő'); ?>>Érvelő</option>
            <option value="Elemző" <?php selected($ai_writing_style, 'Elemző'); ?>>Elemző</option>
            <option value="Értékelő" <?php selected($ai_writing_style, 'Értékelő'); ?>>Értékelő</option>
            <option value="Újságírói" <?php selected($ai_writing_style, 'Újságírói'); ?>>Újságírói</option>
            <option value="Műszaki" <?php selected($ai_writing_style, 'Műszaki'); ?>>Műszaki</option>
        </select>
    </p>
    <p>
        <label for="ai_writing_tone"><?php _e('Írás hangja (Writing Tone):', 'ebook-sales'); ?></label>
        <select name="ai_writing_tone" id="ai_writing_tone" class="widefat">
            <option value="Semleges" <?php selected($ai_writing_tone, 'Semleges'); ?>>Semleges</option>
            <option value="Hivatalos" <?php selected($ai_writing_tone, 'Hivatalos'); ?>>Hivatalos</option>
            <option value="Magabiztos" <?php selected($ai_writing_tone, 'Magabiztos'); ?>>Magabiztos</option>
            <option value="Vidám" <?php selected($ai_writing_tone, 'Vidám'); ?>>Vidám</option>
            <option value="Tréfás" <?php selected($ai_writing_tone, 'Tréfás'); ?>>Tréfás</option>
            <option value="Informális" <?php selected($ai_writing_tone, 'Informális'); ?>>Informális</option>
            <option value="Inspiráló" <?php selected($ai_writing_tone, 'Inspiráló'); ?>>Inspiráló</option>
            <option value="Szakmai" <?php selected($ai_writing_tone, 'Szakmai'); ?>>Szakmai</option>
            <option value="Összefolyó" <?php selected($ai_writing_tone, 'Összefolyó'); ?>>Összefolyó</option>
            <option value="Érzelmi" <?php selected($ai_writing_tone, 'Érzelmi'); ?>>Érzelmi</option>
            <option value="Meggyőző" <?php selected($ai_writing_tone, 'Meggyőző'); ?>>Meggyőző</option>
            <option value="Támogató" <?php selected($ai_writing_tone, 'Támogató'); ?>>Támogató</option>
            <option value="Szarkasztikus" <?php selected($ai_writing_tone, 'Szarkasztikus'); ?>>Szarkasztikus</option>
            <option value="Leereszkedő" <?php selected($ai_writing_tone, 'Leereszkedő'); ?>>Leereszkedő</option>
            <option value="Szkeptikus" <?php selected($ai_writing_tone, 'Szkeptikus'); ?>>Szkeptikus</option>
            <option value="Elbeszélés" <?php selected($ai_writing_tone, 'Elbeszélés'); ?>>Elbeszélés</option>
            <option value="Újságírói" <?php selected($ai_writing_tone, 'Újságírói'); ?>>Újságírói</option>
        </select>
    </p>
    <p>
        <button type="button" id="generate_ai_content" class="button"><?php _e('Generál', 'ebook-sales'); ?></button>
    </p>
    <div id="ai_content_message"></div>
    <script type="text/javascript">
    jQuery(document).ready(function($){
        $('#generate_ai_content').on('click', function(e){
            e.preventDefault();
            var apiKey = '<?php echo esc_js(get_option("openai_api_key", "")); ?>';
            if(apiKey === ''){
                alert('<?php _e("Először adja meg az OpenAI API kulcsot az AI Setup oldalon!", "ebook-sales"); ?>');
                return;
            }
            var data = {
                action: 'generate_ai_content',
                post_id: <?php echo $post->ID; ?>,
                ai_content_nonce: '<?php echo wp_create_nonce("generate_ai_content"); ?>'
            };
            $.post(ajaxurl, data, function(response){
                if(response.success){
                    $('#ai_content').val(response.data.content);
                    $('#ai_content_message').html('<span style="color:green;">' + response.data.message + '</span>');
                } else {
                    $('#ai_content_message').html('<span style="color:red;">' + response.data.message + '</span>');
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
            remove_action('save_post', 'save_ebook_file_meta_box');
            wp_update_post(array('ID' => $post_id, 'post_status' => 'draft'));
            add_action('save_post', 'save_ebook_file_meta_box');
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
            remove_action('save_post', 'save_ebook_file_meta_box');
            wp_update_post(array('ID' => $post_id, 'post_status' => 'draft'));
            add_action('save_post', 'save_ebook_file_meta_box');
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
            remove_action('save_post', 'save_ebook_file_meta_box');
            wp_update_post(array('ID' => $post_id, 'post_status' => 'draft'));
            add_action('save_post', 'save_ebook_file_meta_box');
            return;
        }
    }
    
    // Ha feltöltés sikeres vagy nem is választottak fájlt, mentsük el az Ár és Pénznem értékeket
    if (isset($_POST['ebook_price'])) {
        $price = floatval($_POST['ebook_price']);
        if ($price < 0) {
            $price = 0;
        }
        update_post_meta($post_id, 'ebook_price', $price);
    }
    if (isset($_POST['ebook_currency'])) {
        $currency = sanitize_text_field($_POST['ebook_currency']);
        update_post_meta($post_id, 'ebook_currency', $currency);
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
    // Megtartjuk a meglévő oszlopokat, majd hozzáadjuk az ebook fájl, borító kép, ár és pénznem oszlopokat.
    $columns['ebook_file'] = __('Ebook fájl', 'ebook-sales');
    $columns['cover_image'] = __('Borító kép', 'ebook-sales');
    // Új oszlopok
    $columns['ebook_price'] = __('Ár', 'ebook-sales');
    $columns['ebook_currency'] = __('Pénznem', 'ebook-sales');
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
        $price = floatval(get_post_meta($post_id, 'ebook_price', true));
        if ($price == 0) {
            echo __('Free', 'ebook-sales');
        } else {
            echo esc_html(number_format($price, 2));
        }
    } elseif ($column == 'ebook_currency') {
        $currency = get_post_meta($post_id, 'ebook_currency', true);
        echo $currency !== '' ? esc_html($currency) : __('Nincs adat', 'ebook-sales');
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
    
    // Célmappa az ebook fájlhoz
    $upload = wp_upload_dir();
    $target_dir = $upload['basedir'] . '/protected_ebooks';
    if (!file_exists($target_dir)) {
        if (!wp_mkdir_p($target_dir)) {
            wp_send_json_error(array('message' => __('Nem sikerült létrehozni a célmappát a protected_ebooks számára.', 'ebook-sales')));
        }
    }
    // Új mappa a borító képeknek
    $target_cover_dir = $upload['basedir'] . '/ebook_covers';
    if (!file_exists($target_cover_dir)) {
        if (!wp_mkdir_p($target_cover_dir)) {
            wp_send_json_error(array('message' => __('Nem sikerült létrehozni a célmappát az ebook_covers számára.', 'ebook-sales')));
        }
    }
    
    // Ebook fájl: használjuk a wp_unique_filename eredményét (amely már tartalmazza a kiterjesztést)
    $ebook_unique_name = wp_unique_filename($target_dir, $ebook_filename);
    $ebook_target_file = $target_dir . '/' . $ebook_unique_name;
    
    // Borító kép: használjuk az ebook fájl base nevét (kiterjesztés nélkül) az új névhez,
    // majd fűzzük hozzá a cover kép saját kiterjesztését.
    $ebook_base = pathinfo($ebook_unique_name, PATHINFO_FILENAME);
    $cover_unique_name = $ebook_base . '.' . $cover_file_ext;
    $cover_target_file = $target_cover_dir . '/' . $cover_unique_name;
    
    // Fájlok feltöltése – csak akkor, ha mindkettő sikeres
    if ( move_uploaded_file($_FILES['ebook_file']['tmp_name'], $ebook_target_file) &&
         move_uploaded_file($_FILES['cover_image']['tmp_name'], $cover_target_file) ) {
        
        // Állítsuk be a fájl URL-eket
        $ebook_file_url = $upload['baseurl'] . '/protected_ebooks/' . $ebook_unique_name;
        $cover_file_url = $upload['baseurl'] . '/ebook_covers/' . $cover_unique_name;
        
        update_post_meta($post_id, '_ebook_file', esc_url_raw($ebook_file_url));
        update_post_meta($post_id, '_cover_image', esc_url_raw($cover_file_url));
        
        // Új kód: cover kép beszúrása a médiatárba
        if ( ! function_exists('wp_generate_attachment_metadata') ) {
            require_once( ABSPATH . 'wp-admin/includes/image.php' );
            require_once( ABSPATH . 'wp-admin/includes/file.php' );
            require_once( ABSPATH . 'wp-admin/includes/media.php' );
        }
        $attachment = array(
            'guid'           => $cover_file_url,
            'post_mime_type' => mime_content_type($cover_target_file),
            'post_title'     => preg_replace('/\.[^.]+$/', '', basename($cover_target_file)),
            'post_content'   => '',
            'post_status'    => 'inherit'
        );
        $attachment_id = wp_insert_attachment($attachment, $cover_target_file, $post_id);
        $attach_data   = wp_generate_attachment_metadata($attachment_id, $cover_target_file);
        wp_update_attachment_metadata($attachment_id, $attach_data);
        update_post_meta($post_id, '_cover_attachment', $attachment_id);
        
        // Ellenőrizzük a post címét
        $post = get_post($post_id);
        $current_title = trim($post->post_title);
        if ( empty($current_title) || strtolower($current_title) === 'auto draft' ) {
            // Ebook fájl eredeti neve kiterjesztés nélkül
            $new_title = pathinfo($ebook_filename, PATHINFO_FILENAME);
            // Az első betű nagybetűssé tétele (például "hólapát" -> "Hólapát")
            $new_title = mb_convert_case($new_title, MB_CASE_TITLE, "UTF-8");
            remove_action('save_post', 'save_ebook_file_meta_box');
            wp_update_post(array(
                'ID'        => $post_id,
                'post_title'=> $new_title,
                'post_name' => sanitize_title($new_title)
                // Ha nem szeretnéd változtatni a post_status-t, itt nem kell megadni.
            ));
            add_action('save_post', 'save_ebook_file_meta_box');
        }
        
        // Mentsük el az Ár és Pénznem értékeket az AJAX kérésből
        if (isset($_POST['ebook_price'])) {
            $price = floatval($_POST['ebook_price']);
            if ($price < 0) {
                $price = 0;
            }
            update_post_meta($post_id, 'ebook_price', $price);
        }
        if (isset($_POST['ebook_currency'])) {
            $currency = sanitize_text_field($_POST['ebook_currency']);
            update_post_meta($post_id, 'ebook_currency', $currency);
        }
        
        wp_send_json_success(array('message' => sprintf(__('Feltöltés sikeres: Ebook: %s; Borító: %s', 'ebook-sales'), $ebook_unique_name, $cover_unique_name)));
    } else {
        wp_send_json_error(array('message' => __('Fájl feltöltési hiba történt!', 'ebook-sales')));
    }
}

// Új funkció: a borító kép beállítása kiemelt képnek save draft és publish esetén
function set_featured_image_from_cover($post_id) {
    // Ne futtassuk revision vagy autosave esetén
    if ( wp_is_post_revision($post_id) || wp_is_post_autosave( $post_id ) ) {
        return;
    }
    // Ellenőrizzük, hogy ebook típusú posztról legyen szó
    if ( get_post_type($post_id) !== 'ebook' ) {
        return;
    }
    // Ha már van beállítva kiemelt kép, nem csinálunk semmit
    if ( has_post_thumbnail($post_id) ) {
        return;
    }
    // Megkeressük a borító kép attachment ID-t
    $cover_attachment = get_post_meta($post_id, '_cover_attachment', true);
    if ( $cover_attachment ) {
        set_post_thumbnail($post_id, $cover_attachment);
    }
}
add_action('save_post', 'set_featured_image_from_cover', 30);