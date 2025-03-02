<?php
/*
Plugin Name: Ebook Sales
Plugin URI: https://metaliverail.hu/
Description: A plugin to manage and sell ebooks/subscriptions on your WordPress site.
Version: 2.0.0
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

// Aktiválás/deaktiválás
register_activation_hook(__FILE__, function() {
    flush_rewrite_rules();
    Payment_Database::create_table();
});

register_deactivation_hook(__FILE__, function() {
    flush_rewrite_rules();
});

// Fordítások
add_action('plugins_loaded', function() {
    load_plugin_textdomain('ebook-sales', false, dirname(plugin_basename(__FILE__)) . '/languages/');
});