<?php
/*
Plugin Name: Ebook Sales
Plugin URI: https://metaliverail.hu/
Description: A plugin to manage and sell ebooks/subscriptions on your WordPress site.
Version: 2.1.0
Author: Frank Smith
Author URI: https://metaliverail.hu/
Text Domain: ebook-sales
Domain Path: /languages
*/

if (!defined('ABSPATH')) {
    exit;
}

define('EBOOK_SALES_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('EBOOK_SALES_PLUGIN_URL', plugin_dir_url(__FILE__));

// Stripe SDK betöltése
require_once EBOOK_SALES_PLUGIN_DIR . 'vendor/autoload.php';

// Stripe inicializálás
add_action('init', function() {
    \Stripe\Stripe::setApiKey(get_option('stripe_secret_key'));
});

// Fő fájlok betöltése
require_once EBOOK_SALES_PLUGIN_DIR . 'includes/class-ebook-post-type.php';
require_once EBOOK_SALES_PLUGIN_DIR . 'includes/class-ebook-admin.php';
require_once EBOOK_SALES_PLUGIN_DIR . 'includes/class-stripe-donation-settings.php';
require_once EBOOK_SALES_PLUGIN_DIR . 'includes/class-payment-database.php';
require_once EBOOK_SALES_PLUGIN_DIR . 'includes/class-stripe-payment-handler.php';
require_once EBOOK_SALES_PLUGIN_DIR . 'includes/class-stripe-webhook-handler.php';
require_once EBOOK_SALES_PLUGIN_DIR . 'includes/class-payment-admin.php';
require_once EBOOK_SALES_PLUGIN_DIR . 'includes/class-stripe-subscription-handler.php';
require_once EBOOK_SALES_PLUGIN_DIR . 'includes/class-ebook-dependency-settings.php'; 
require_once EBOOK_SALES_PLUGIN_DIR . 'includes/class-ebook-role-ranking-settings.php';// Új
require_once EBOOK_SALES_PLUGIN_DIR . 'includes/handler-dependency-setting.php';
require_once EBOOK_SALES_PLUGIN_DIR . 'includes/class-ebook-delete-trash.php';
require_once EBOOK_SALES_PLUGIN_DIR . 'includes/ebook-featured-image.php';
require_once EBOOK_SALES_PLUGIN_DIR . 'includes/handle-ai-generate.php';
require_once EBOOK_SALES_PLUGIN_DIR . 'includes/wp-kses-config.php';
require_once EBOOK_SALES_PLUGIN_DIR . 'includes/save-ai-meta.php';
// require_once EBOOK_SALES_PLUGIN_DIR . 'includes/insert-ebook-cover.php';
require_once EBOOK_SALES_PLUGIN_DIR . 'includes/meta-box-cover-image.php';
require_once EBOOK_SALES_PLUGIN_DIR . 'includes/insert-ebook-cover-frontend.php';
require_once EBOOK_SALES_PLUGIN_DIR . 'includes/ebook-pay-button.php';
require_once EBOOK_SALES_PLUGIN_DIR . 'includes/class-ai-setup.php';
require_once EBOOK_SALES_PLUGIN_DIR . 'includes/class-ebook-mail-templates.php';
require_once EBOOK_SALES_PLUGIN_DIR . 'includes/class-ebook-workflow.php';
// Aktiválás/deaktiválás
register_activation_hook(__FILE__, 'ebook_sales_activate');
function ebook_sales_activate() {
    // Betöltjük a custom post típus regisztrációját végző fájlt és példányosítjuk az osztályt.
    require_once EBOOK_SALES_PLUGIN_DIR . 'includes/class-ebook-post-type.php';
    $ebook_post_type = new Ebook_Post_Type();
    
    // Regisztráljuk a post típust, így a rewrite szabályok helyesek lesznek.
    $ebook_post_type->register_ebook_post_type();
    
    flush_rewrite_rules();
    Payment_Database::create_table();
    
    // Workflow tábla létrehozása aktiváláskor
    require_once EBOOK_SALES_PLUGIN_DIR . 'includes/class-ebook-workflow.php';
    $workflow = new Ebook_Workflow();
    $workflow->create_workflow_table();
}

register_deactivation_hook(__FILE__, 'ebook_sales_deactivate');
function ebook_sales_deactivate(){
    flush_rewrite_rules();
}

// Fordítások
add_action('plugins_loaded', function() {
    load_plugin_textdomain('ebook-sales', false, dirname(plugin_basename(__FILE__)) . '/languages/');
});