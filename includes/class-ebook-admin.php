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
                        ?>
                        <h3><?php _e('Beállítás', 'ebook-sales'); ?></h3>
                        <p><?php _e('Itt tudod konfigurálni a levelezési beállításokat.', 'ebook-sales'); ?></p>
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
