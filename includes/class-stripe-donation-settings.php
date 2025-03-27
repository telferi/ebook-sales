<?php
if (!defined('ABSPATH')) {
    exit;
}

class Stripe_Donation_Settings {

    public function __construct() {
        add_action('admin_init', array($this, 'register_stripe_donation_settings'));
    }

    public function register_stripe_donation_settings() {
        register_setting('stripe_donation_settings_group', 'stripe_publishable_key');
        register_setting('stripe_donation_settings_group', 'stripe_secret_key');
        register_setting('stripe_donation_settings_group', 'stripe_webhook_secret');

        add_settings_section(
            'stripe_donation_section',
            __('Stripe API Settings', 'ebook-sales'),
            array($this, 'stripe_donation_section_callback'),
            'stripe-donation-settings'
        );

        add_settings_field(
            'stripe_publishable_key',
            __('Publishable Key', 'ebook-sales'),
            array($this, 'stripe_publishable_key_callback'),
            'stripe-donation-settings',
            'stripe_donation_section'
        );

        add_settings_field(
            'stripe_secret_key',
            __('Secret Key', 'ebook-sales'),
            array($this, 'stripe_secret_key_callback'),
            'stripe-donation-settings',
            'stripe_donation_section'
        );

        add_settings_field(
            'stripe_webhook_secret',
            __('Webhook Secret', 'ebook-sales'),
            array($this, 'stripe_webhook_secret_callback'),
            'stripe-donation-settings',
            'stripe_donation_section'
        );
    }

    public function stripe_donation_section_callback() {
        echo esc_html__('Enter your Stripe API keys and webhook secret.', 'ebook-sales');
    }

    public function stripe_publishable_key_callback() {
        $value = get_option('stripe_publishable_key');
        echo '<input type="text" name="stripe_publishable_key" value="' . esc_attr($value) . '" class="regular-text">';
    }

    public function stripe_secret_key_callback() {
        $value = get_option('stripe_secret_key');
        echo '<input type="password" name="stripe_secret_key" value="' . esc_attr($value) . '" class="regular-text">';
    }

    public function stripe_webhook_secret_callback() {
        $value = get_option('stripe_webhook_secret');
        echo '<input type="password" name="stripe_webhook_secret" value="' . esc_attr($value) . '" class="regular-text">
<p class="description">' . nl2br(esc_html__(
    '"Get this from Stripe Dashboard → Developers → Webhooks."' . "\n" .
    '"Endpoint URL: https://your website/?ebook_stripe_webhook=1."' . "\n" .
    '"Events: charge.refunded, charge.succeeded, checkout.session.completed, invoice.payment_succeeded, payment_intent.succeeded."',
    'ebook-sales'
)) . '</p>';
    }
}

new Stripe_Donation_Settings();