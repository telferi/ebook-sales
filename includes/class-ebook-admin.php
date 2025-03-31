<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class Ebook_Admin {

    public function __construct() {
        add_action('admin_menu', array($this, 'add_ebook_menu'));
        
        // Betöltjük a template kezelő osztályt
        require_once plugin_dir_path(__FILE__) . 'class-ebook-mail-templates.php';
        
        // Admin asset-ek betöltése
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
    }
    
    public function enqueue_admin_scripts($hook) {
        // Csak a levelezés oldalon töltjük be a szkripteket
        if (strpos($hook, 'ebook-mailing') !== false) {
            wp_enqueue_script('ebook-mail-templates', plugin_dir_url(__FILE__) . '../assets/js/ebook-mail-templates.js', array('jquery'), '1.0.0', true);
            wp_localize_script('ebook-mail-templates', 'ebook_mail_vars', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('ebook_mail_nonce'),
                'delete_confirm' => __('Biztosan törölni szeretnéd ezt a sablont?', 'ebook-sales')
            ));
        }
    }

    public function add_ebook_menu() {
        // Főmenüpont létrehozása
        add_menu_page(
            __('Ebooks', 'ebook-sales'), // Page title
            __('Ebooks', 'ebook-sales'), // Menu title
            'manage_options',            // Capability
            'ebook-sales',               // Menu slug
            null,                        // Callback function (NINCS, mert a WordPress kezeli)
            'dashicons-book',            // Icon URL
            6                            // Position
        );

        // Almenüpontok hozzáadása
        // 1. "Add New Ebook"
        add_submenu_page(
            'ebook-sales',               
            __('Add New Ebook', 'ebook-sales'), 
            __('Add New', 'ebook-sales'),       
            'manage_options',            
            'post-new.php?post_type=ebook', 
            null                         
        );

        // 2. "Categories"
        add_submenu_page(
            'ebook-sales',               
            __('Categories', 'ebook-sales'), 
            __('Categories', 'ebook-sales'), 
            'manage_options',            
            'edit-tags.php?taxonomy=category&post_type=ebook', 
            null                         
        );

        // 3. "Tags"
        add_submenu_page(
            'ebook-sales',               
            __('Tags', 'ebook-sales'),   
            __('Tags', 'ebook-sales'),   
            'manage_options',            
            'edit-tags.php?taxonomy=post_tag&post_type=ebook', 
            null                         
        );

        // 4. "Stripe Settings"
        add_submenu_page(
            'ebook-sales',               
            __('Stripe Settings', 'ebook-sales'), 
            __('Stripe', 'ebook-sales'), 
            'manage_options',            
            'stripe-donation-settings',  
            array($this, 'stripe_donation_settings_page') 
        );

        // 5. "Levelezés"
        add_submenu_page(
            'ebook-sales',
            __('Levelezés', 'ebook-sales'),
            __('Levelezés', 'ebook-sales'),
            'manage_options',
            'ebook-mailing',
            array($this, 'render_mailing_page')
        );
    }

    public function stripe_donation_settings_page() {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('Stripe Settings', 'ebook-sales'); ?></h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('stripe_donation_settings_group');
                do_settings_sections('stripe-donation-settings');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    public function render_mailing_page() {
        $current_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'beallitas';
        
        // Form adatok feldolgozása
        if (isset($_POST['ebook_mail_settings_submit']) && $current_tab === 'beallitas') {
            if (check_admin_referer('ebook_mail_settings_nonce', 'ebook_mail_settings_nonce')) {
                // Ellenőrizzük, hogy minden kötelező mező ki van-e töltve
                $required_fields = [
                    'ebook_mail_hostname' => __('Host name', 'ebook-sales'),
                    'ebook_mail_sender_email' => __('Sender email address', 'ebook-sales'),
                    'ebook_mail_imap_port' => __('IMAP Port', 'ebook-sales'),
                    'ebook_mail_pop3_port' => __('POP3 Port', 'ebook-sales'),
                    'ebook_mail_smtp_port' => __('SMTP Port', 'ebook-sales')
                ];
                
                $errors = [];
                foreach ($required_fields as $field => $label) {
                    if (empty($_POST[$field])) {
                        $errors[] = sprintf(__('A(z) %s mező kitöltése kötelező.', 'ebook-sales'), $label);
                    }
                }
                
                // Ellenőrizzük, hogy a port értékek nem negatívak
                if (isset($_POST['ebook_mail_imap_port']) && intval($_POST['ebook_mail_imap_port']) < 0) {
                    $errors[] = __('Az IMAP Port nem lehet negatív szám.', 'ebook-sales');
                }
                if (isset($_POST['ebook_mail_pop3_port']) && intval($_POST['ebook_mail_pop3_port']) < 0) {
                    $errors[] = __('A POP3 Port nem lehet negatív szám.', 'ebook-sales');
                }
                if (isset($_POST['ebook_mail_smtp_port']) && intval($_POST['ebook_mail_smtp_port']) < 0) {
                    $errors[] = __('Az SMTP Port nem lehet negatív szám.', 'ebook-sales');
                }
                
                if (empty($errors)) {
                    // Mentés, ha nincs hiba
                    update_option('ebook_mail_hostname', sanitize_text_field($_POST['ebook_mail_hostname']));
                    update_option('ebook_mail_sender_email', sanitize_email($_POST['ebook_mail_sender_email']));
                    update_option('ebook_mail_imap_port', absint($_POST['ebook_mail_imap_port']));
                    update_option('ebook_mail_pop3_port', absint($_POST['ebook_mail_pop3_port']));
                    update_option('ebook_mail_smtp_port', absint($_POST['ebook_mail_smtp_port']));
                    
                    echo '<div class="notice notice-success is-dismissible"><p>' . __('Beállítások sikeresen mentve!', 'ebook-sales') . '</p></div>';
                } else {
                    // Hibaüzenetek megjelenítése
                    echo '<div class="notice notice-error is-dismissible"><p>' . implode('<br>', $errors) . '</p></div>';
                }
            }
        }
        
        ?>
        <div class="wrap">
            <h1><?php _e('Levelezés', 'ebook-sales'); ?></h1>
            <h2 class="nav-tab-wrapper">
                <a href="<?php echo admin_url('admin.php?page=ebook-mailing&tab=beallitas'); ?>" class="nav-tab <?php echo ($current_tab === 'beallitas') ? 'nav-tab-active' : ''; ?>">
                    <?php _e('Beállítás', 'ebook-sales'); ?>
                </a>
                <a href="<?php echo admin_url('admin.php?page=ebook-mailing&tab=sablonok'); ?>" class="nav-tab <?php echo ($current_tab === 'sablonok') ? 'nav-tab-active' : ''; ?>">
                    <?php _e('Sablonok szerkesztése', 'ebook-sales'); ?>
                </a>
                <a href="<?php echo admin_url('admin.php?page=ebook-mailing&tab=workflow'); ?>" class="nav-tab <?php echo ($current_tab === 'workflow') ? 'nav-tab-active' : ''; ?>">
                    <?php _e('Workflow', 'ebook-sales'); ?>
                </a>
            </h2>
            <div class="tab-content">
                <?php
                switch($current_tab) {
                    case 'beallitas':
                        $hostname = get_option('ebook_mail_hostname', '');
                        $sender_email = get_option('ebook_mail_sender_email', '');
                        $imap_port = get_option('ebook_mail_imap_port', '993');
                        $pop3_port = get_option('ebook_mail_pop3_port', '995');
                        $smtp_port = get_option('ebook_mail_smtp_port', '456');
                        ?>
                        <h3><?php _e('Beállítás', 'ebook-sales'); ?></h3>
                        <form method="post" action="">
                            <?php wp_nonce_field('ebook_mail_settings_nonce', 'ebook_mail_settings_nonce'); ?>
                            
                            <table class="form-table">
                                <tr>
                                    <th scope="row"><label for="ebook_mail_hostname"><?php _e('Host name', 'ebook-sales'); ?> <span class="required">*</span></label></th>
                                    <td>
                                        <input type="text" id="ebook_mail_hostname" name="ebook_mail_hostname" value="<?php echo esc_attr($hostname); ?>" class="regular-text" required>
                                        <p class="description"><?php _e('A levelező szerver host neve.', 'ebook-sales'); ?></p>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><label for="ebook_mail_sender_email"><?php _e('Sender email address', 'ebook-sales'); ?> <span class="required">*</span></label></th>
                                    <td>
                                        <input type="email" id="ebook_mail_sender_email" name="ebook_mail_sender_email" value="<?php echo esc_attr($sender_email); ?>" class="regular-text" required>
                                        <p class="description"><?php _e('A kiküldött leveleken ez az email cím szerepel majd.', 'ebook-sales'); ?></p>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><label for="ebook_mail_imap_port"><?php _e('IMAP Port', 'ebook-sales'); ?> <span class="required">*</span></label></th>
                                    <td>
                                        <input type="number" id="ebook_mail_imap_port" name="ebook_mail_imap_port" value="<?php echo esc_attr($imap_port); ?>" class="small-text" required>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><label for="ebook_mail_pop3_port"><?php _e('POP3 Port', 'ebook-sales'); ?> <span class="required">*</span></label></th>
                                    <td>
                                        <input type="number" id="ebook_mail_pop3_port" name="ebook_mail_pop3_port" value="<?php echo esc_attr($pop3_port); ?>" class="small-text" required>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><label for="ebook_mail_smtp_port"><?php _e('SMTP Port', 'ebook-sales'); ?> <span class="required">*</span></label></th>
                                    <td>
                                        <input type="number" id="ebook_mail_smtp_port" name="ebook_mail_smtp_port" value="<?php echo esc_attr($smtp_port); ?>" class="small-text" required>
                                    </td>
                                </tr>
                            </table>

                            <style>
                                .required { color: red; }
                            </style>
                            
                            <?php submit_button(__('Mentés', 'ebook-sales'), 'primary', 'ebook_mail_settings_submit'); ?>
                        </form>
                        <?php
                        break;
                    case 'sablonok':
                        $template_id = isset($_GET['template_id']) ? intval($_GET['template_id']) : 0;
                        $action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : '';
                        
                        if ($action === 'edit' && $template_id > 0) {
                            // Sablon szerkesztése
                            $this->render_edit_template_form($template_id);
                        } elseif ($action === 'new') {
                            // Új sablon létrehozása
                            $this->render_edit_template_form(0);
                        } elseif ($action === 'view' && $template_id > 0) {
                            // Sablon megtekintése
                            $this->render_view_template($template_id);
                        } else {
                            // Sablonok listázása
                            ?>
                            <h3><?php _e('Sablonok szerkesztése', 'ebook-sales'); ?></h3>
                            <div class="tablenav top">
                                <div class="alignleft actions">
                                    <a href="<?php echo admin_url('admin.php?page=ebook-mailing&tab=sablonok&action=new'); ?>" class="button button-primary">
                                        <?php _e('Új levél sablon', 'ebook-sales'); ?>
                                    </a>
                                </div>
                                <br class="clear">
                            </div>
                            
                            <?php
                            // Template-ek listázása
                            $mail_templates = Ebook_Mail_Templates::get_mail_templates();
                            
                            if (empty($mail_templates)) {
                                echo '<p>' . __('Még nincsenek létrehozott levélsablonok.', 'ebook-sales') . '</p>';
                            } else {
                                ?>
                                <table class="wp-list-table widefat fixed striped">
                                    <thead>
                                        <tr>
                                            <th><?php _e('ID', 'ebook-sales'); ?></th>
                                            <th><?php _e('Neve', 'ebook-sales'); ?></th>
                                            <th><?php _e('Subject', 'ebook-sales'); ?></th>
                                            <th><?php _e('Létrehozás dátuma', 'ebook-sales'); ?></th>
                                            <th><?php _e('Műveletek', 'ebook-sales'); ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    <?php foreach ($mail_templates as $template) : ?>
                                        <tr>
                                            <td><?php echo esc_html($template->id); ?></td>
                                            <td><?php echo esc_html($template->name); ?></td>
                                            <td><?php echo esc_html($template->subject); ?></td>
                                            <td><?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($template->created_at))); ?></td>
                                            <td>
                                                <a href="<?php echo admin_url('admin.php?page=ebook-mailing&tab=sablonok&action=edit&template_id=' . $template->id); ?>" class="button button-small">
                                                    <?php _e('Szerkesztés', 'ebook-sales'); ?>
                                                </a>
                                                <a href="<?php echo admin_url('admin.php?page=ebook-mailing&tab=sablonok&action=view&template_id=' . $template->id); ?>" class="button button-small">
                                                    <?php _e('Megtekintés', 'ebook-sales'); ?>
                                                </a>
                                                <a href="#" class="button button-small delete-template" data-id="<?php echo $template->id; ?>">
                                                    <?php _e('Törlés', 'ebook-sales'); ?>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                    </tbody>
                                </table>
                                <?php
                            }
                        }
                        break;
                    case 'workflow':
                        ?>
                        <h3><?php _e('Workflow', 'ebook-sales'); ?></h3>
                        <p><?php _e('Itt állíthatók be az automatizált munkafolyamatok.', 'ebook-sales'); ?></p>
                        <?php
                        break;
                    default:
                        echo '<p>' . __('Érvénytelen fül.', 'ebook-sales') . '</p>';
                }
                ?>
            </div>
        </div>
        <?php
    }
    
    /**
     * Sablon szerkesztő űrlap megjelenítése
     */
    private function render_edit_template_form($template_id = 0) {
        $template = new stdClass();
        $title = __('Új levél sablon létrehozása', 'ebook-sales');
        $button_text = __('Létrehozás', 'ebook-sales');
        
        if ($template_id > 0) {
            $template = Ebook_Mail_Templates::get_mail_template_by_id($template_id);
            if (!$template) {
                echo '<div class="notice notice-error"><p>' . __('A sablon nem található!', 'ebook-sales') . '</p></div>';
                return;
            }
            $title = __('Levél sablon szerkesztése', 'ebook-sales');
            $button_text = __('Frissítés', 'ebook-sales');
        } else {
            $template->id = 0;
            $template->name = '';
            $template->subject = '';
            $template->content = '';
        }
    
        // Form feldolgozása
        if (isset($_POST['ebook_mail_template_submit'])) {
            if (check_admin_referer('ebook_mail_template_nonce', 'ebook_mail_template_nonce')) {
                $template_data = array(
                    'name' => sanitize_text_field($_POST['template_name']),
                    'subject' => sanitize_text_field($_POST['template_subject']),
                    'content' => wp_kses_post($_POST['template_content'])
                );
                
                $errors = array();
                if (empty($template_data['name'])) {
                    $errors[] = __('A sablon neve nem lehet üres!', 'ebook-sales');
                }
                if (empty($template_data['subject'])) {
                    $errors[] = __('A tárgy mező nem lehet üres!', 'ebook-sales');
                }
                
                if (empty($errors)) {
                    if ($template_id > 0) {
                        Ebook_Mail_Templates::update_mail_template($template_id, $template_data);
                        $message = __('A sablon sikeresen frissítve!', 'ebook-sales');
                    } else {
                        $template_id = Ebook_Mail_Templates::create_mail_template($template_data);
                        $message = __('Az új sablon sikeresen létrehozva!', 'ebook-sales');
                    }
                    
                    echo '<div class="notice notice-success is-dismissible"><p>' . $message . '</p></div>';
                    $template = Ebook_Mail_Templates::get_mail_template_by_id($template_id);
                } else {
                    echo '<div class="notice notice-error"><p>' . implode('<br>', $errors) . '</p></div>';
                }
            }
        }
        
        ?>
        <h3><?php echo $title; ?></h3>
        <form method="post" action="">
            <?php wp_nonce_field('ebook_mail_template_nonce', 'ebook_mail_template_nonce'); ?>
            <input type="hidden" name="template_id" value="<?php echo esc_attr($template_id); ?>">
            
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="template_name"><?php _e('Sablon neve', 'ebook-sales'); ?> <span style="color: red;">*</span></label></th>
                    <td>
                        <input type="text" id="template_name" name="template_name" value="<?php echo esc_attr($template->name); ?>" class="regular-text" required>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="template_subject"><?php _e('Tárgy', 'ebook-sales'); ?> <span style="color: red;">*</span></label></th>
                    <td>
                        <input type="text" id="template_subject" name="template_subject" value="<?php echo esc_attr($template->subject); ?>" class="regular-text" required>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="template_content"><?php _e('Tartalom', 'ebook-sales'); ?></label></th>
                    <td>
                        <?php
                        wp_editor($template->content, 'template_content', array(
                            'textarea_name' => 'template_content',
                            'media_buttons' => true,
                            'textarea_rows' => 15
                        ));
                        ?>
                    </td>
                </tr>
            </table>
            
            <p>
                <input type="submit" name="ebook_mail_template_submit" class="button button-primary" value="<?php echo $button_text; ?>">
                <a href="<?php echo admin_url('admin.php?page=ebook-mailing&tab=sablonok'); ?>" class="button"><?php _e('Vissza', 'ebook-sales'); ?></a>
            </p>
        </form>
        <?php
    }
    
    /**
     * Sablon megtekintése
     */
    private function render_view_template($template_id) {
        $template = Ebook_Mail_Templates::get_mail_template_by_id($template_id);
        if (!$template) {
            echo '<div class="notice notice-error"><p>' . __('A sablon nem található!', 'ebook-sales') . '</p></div>';
            return;
        }
        
        ?>
        <h3><?php echo esc_html($template->name); ?> <?php _e('megtekintése', 'ebook-sales'); ?></h3>
        
        <div class="template-view-container">
            <div class="template-meta">
                <p><strong><?php _e('ID', 'ebook-sales'); ?>:</strong> <?php echo esc_html($template->id); ?></p>
                <p><strong><?php _e('Név', 'ebook-sales'); ?>:</strong> <?php echo esc_html($template->name); ?></p>
                <p><strong><?php _e('Tárgy', 'ebook-sales'); ?>:</strong> <?php echo esc_html($template->subject); ?></p>
                <p><strong><?php _e('Létrehozva', 'ebook-sales'); ?>:</strong> <?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($template->created_at))); ?></p>
            </div>
            
            <div class="template-content">
                <h4><?php _e('Sablon tartalma', 'ebook-sales'); ?>:</h4>
                <div class="template-content-preview">
                    <?php echo wpautop($template->content); ?>
                </div>
            </div>
            
            <p>
                <a href="<?php echo admin_url('admin.php?page=ebook-mailing&tab=sablonok&action=edit&template_id=' . $template->id); ?>" class="button button-primary">
                    <?php _e('Szerkesztés', 'ebook-sales'); ?>
                </a>
                <a href="<?php echo admin_url('admin.php?page=ebook-mailing&tab=sablonok'); ?>" class="button">
                    <?php _e('Vissza a listához', 'ebook-sales'); ?>
                </a>
            </p>
        </div>
        
        <style>
            .template-content-preview {
                background: #fff;
                border: 1px solid #ccd0d4;
                padding: 20px;
                margin-top: 10px;
            }
            
            .template-meta {
                margin-bottom: 20px;
            }
        </style>
        <?php
    }
}

new Ebook_Admin();
