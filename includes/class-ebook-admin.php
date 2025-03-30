<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class Ebook_Admin {

    public function __construct() {
        add_action('admin_menu', array($this, 'add_ebook_menu'));
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
                <a href="<?php echo admin_url('admin.php?page=ebook-mailing&tab=egyeb'); ?>" class="nav-tab <?php echo ($current_tab === 'egyeb') ? 'nav-tab-active' : ''; ?>">
                    <?php _e('Egyébb', 'ebook-sales'); ?>
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
                        ?>
                        <h3><?php _e('Sablonok szerkesztése', 'ebook-sales'); ?></h3>
                        <p><?php _e('Itt tudod szerkeszteni a levelezési sablonokat.', 'ebook-sales'); ?></p>
                        <?php
                        break;
                    case 'egyeb':
                        ?>
                        <h3><?php _e('Egyébb', 'ebook-sales'); ?></h3>
                        <p><?php _e('Itt találhatóak egyéb levelezési opciók.', 'ebook-sales'); ?></p>
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
}

new Ebook_Admin();
