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
}

new Ebook_Admin();
