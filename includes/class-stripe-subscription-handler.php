<?php
if (!defined('ABSPATH')) {
    exit;
}

class Stripe_Subscription_Handler {

    public function __construct() {
        add_shortcode('StripeSubPay', array($this, 'handle_subscription_shortcode'));
        add_action('wp_ajax_process_stripe_subscription', array($this, 'process_subscription'));
        add_action('wp_ajax_nopriv_process_stripe_subscription', array($this, 'process_subscription'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
    }

    public function enqueue_scripts() {
        wp_enqueue_script('stripe-js', 'https://js.stripe.com/v3/');
        wp_enqueue_script(
            'ebook-sales-subscriptions',
            EBOOK_SALES_PLUGIN_URL . 'assets/js/stripe-subscriptions.js',
            array('jquery', 'stripe-js'),
            '2.1.0',
            true
        );

        wp_localize_script('ebook-sales-subscriptions', 'ebookStripeVars', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'publicKey' => get_option('stripe_publishable_key'),
            'nonce' => wp_create_nonce('stripe_payment_nonce')
        ));
    }

    public function handle_subscription_shortcode($atts) {
        $atts = shortcode_atts(array(
            'amount' => '29.99',
            'currency' => 'eur',
            'interval' => 'month',
            'product' => 'Premium Subscription'
        ), $atts);

        ob_start();
        ?>
        <button class="stripe-sub-pay-btn" 
                data-amount="<?php echo esc_attr($atts['amount']); ?>"
                data-currency="<?php echo esc_attr(strtolower($atts['currency'])); ?>"
                data-interval="<?php echo esc_attr($atts['interval']); ?>"
                data-product="<?php echo esc_attr($atts['product']); ?>">
            <?php esc_html_e('Subscribe Now', 'ebook-sales'); ?>
        </button>
        <?php
        return ob_get_clean();
    }

    public function process_subscription() {
        check_ajax_referer('stripe_payment_nonce', 'nonce');

        try {
            $amount = floatval($_POST['amount']);
            $currency = strtolower(sanitize_text_field($_POST['currency']));
            $interval = sanitize_text_field($_POST['interval']);
            $product_name = sanitize_text_field($_POST['product']);

            $product = \Stripe\Product::create(['name' => $product_name]);
            $price = \Stripe\Price::create([
                'product' => $product->id,
                'unit_amount' => $amount * 100,
                'currency' => $currency,
                'recurring' => ['interval' => $interval],
            ]);

            $session = \Stripe\Checkout\Session::create([
                'payment_method_types' => ['card'],
                'line_items' => [[
                    'price' => $price->id,
                    'quantity' => 1,
                ]],
                'mode' => 'subscription',
                'success_url' => home_url('/subscription-success'),
                'cancel_url' => home_url('/subscription-cancel'),
            ]);

            wp_send_json_success(['id' => $session->id]);
        } catch (\Exception $e) {
            wp_send_json_error(['error' => $e->getMessage()]);
        }
    }
}

new Stripe_Subscription_Handler();