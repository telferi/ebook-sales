<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Levél sablon kezelő osztály
 */
class Ebook_Mail_Templates {
    
    /**
     * Osztály inicializálása
     */
    public static function init() {
        self::create_tables();
        
        // AJAX kezelők hozzáadása
        add_action('wp_ajax_delete_mail_template', array(__CLASS__, 'ajax_delete_template'));
    }
    
    /**
     * Szükséges adatbázis táblák létrehozása
     */
    public static function create_tables() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'ebook_mail_templates';
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            subject varchar(255) NOT NULL,
            content longtext NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    /**
     * Új levél sablon létrehozása
     */
    public static function create_mail_template($template_data) {
        global $wpdb;
        
        $data = array(
            'name' => $template_data['name'],
            'subject' => $template_data['subject'],
            'content' => $template_data['content'],
            'created_at' => current_time('mysql')
        );
        
        $wpdb->insert(
            $wpdb->prefix . 'ebook_mail_templates',
            $data
        );
        
        return $wpdb->insert_id;
    }
    
    /**
     * Sablon frissítése
     */
    public static function update_mail_template($template_id, $template_data) {
        global $wpdb;
        
        $data = array(
            'name' => $template_data['name'],
            'subject' => $template_data['subject'],
            'content' => $template_data['content']
        );
        
        $wpdb->update(
            $wpdb->prefix . 'ebook_mail_templates',
            $data,
            array('id' => $template_id)
        );
        
        return true;
    }
    
    /**
     * Sablon törlése
     */
    public static function delete_mail_template($template_id) {
        global $wpdb;
        
        return $wpdb->delete(
            $wpdb->prefix . 'ebook_mail_templates',
            array('id' => $template_id)
        );
    }
    
    /**
     * Sablon lekérése azonosító alapján
     */
    public static function get_mail_template_by_id($template_id) {
        global $wpdb;
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}ebook_mail_templates WHERE id = %d",
            $template_id
        ));
    }
    
    /**
     * Minden sablon lekérése
     */
    public static function get_mail_templates() {
        global $wpdb;
        
        return $wpdb->get_results(
            "SELECT * FROM {$wpdb->prefix}ebook_mail_templates ORDER BY id DESC"
        );
    }
    
    /**
     * Sablon törlés AJAX handler
     */
    public static function ajax_delete_template() {
        // Nonce ellenőrzés
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'ebook_mail_nonce')) {
            wp_send_json_error(__('Biztonsági ellenőrzés sikertelen.', 'ebook-sales'));
        }
        
        // Jogosultság ellenőrzés
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Nincs megfelelő jogosultságod ehhez a művelethez.', 'ebook-sales'));
        }
        
        // Template ID ellenőrzés
        $template_id = isset($_POST['template_id']) ? intval($_POST['template_id']) : 0;
        if ($template_id <= 0) {
            wp_send_json_error(__('Érvénytelen sablon azonosító.', 'ebook-sales'));
        }
        
        // Sablon törlése
        $result = self::delete_mail_template($template_id);
        
        if ($result) {
            wp_send_json_success(__('A sablon sikeresen törölve lett!', 'ebook-sales'));
        } else {
            wp_send_json_error(__('A sablon törlése sikertelen!', 'ebook-sales'));
        }
    }
}

// Inicializálás
Ebook_Mail_Templates::init();
