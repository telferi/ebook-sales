<?php
if (!defined('ABSPATH')) {
    exit;
}

class Stripe_Payment_Handler {

    public function __construct() {
        add_shortcode('StripeFixPay', array($this, 'handle_fix_pay_shortcode'));
        add_shortcode('StripeFlexPay', array($this, 'handle_flex_pay_shortcode'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_process_stripe_payment', array($this, 'process_payment'));
        add_action('wp_ajax_nopriv_process_stripe_payment', array($this, 'process_payment'));
    }

    public function enqueue_scripts() {
        wp_enqueue_script('stripe-js', 'https://js.stripe.com/v3/');
        wp_enqueue_script(
            'ebook-sales-stripe',
            EBOOK_SALES_PLUGIN_URL . 'assets/js/stripe-payments.js',
            array('jquery', 'stripe-js'),
            '1.3.0',
            true
        );

        wp_localize_script('ebook-sales-stripe', 'ebookStripeVars', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'publicKey' => get_option('stripe_publishable_key'),
            'nonce' => wp_create_nonce('stripe_payment_nonce')
        ));
    }

    // Fix összegű shortcode
    public function handle_fix_pay_shortcode($atts) {
        $atts = shortcode_atts(array(
            'amount' => 10,
            'currency' => 'USD',
            'product' => 'Ebook Purchase'
        ), $atts);

        ob_start();
        ?>
        <button class="stripe-fix-pay" 
                data-amount="<?php echo esc_attr($atts['amount']); ?>" 
                data-currency="<?php echo esc_attr($atts['currency']); ?>"
                data-product="<?php echo esc_attr($atts['product']); ?>">
            Pay <?php echo esc_html($atts['amount']); ?> <?php echo esc_html($atts['currency']); ?>
        </button>
        <?php
        return ob_get_clean();
    }

    // Flexibilis összegű shortcode
    public function handle_flex_pay_shortcode($atts) {
        $atts = shortcode_atts(array(
            'product' => 'Ebook Purchase'
        ), $atts);

        ob_start();
        ?>
        <div class="stripe-flex-pay">
            <input type="number" class="stripe-amount" placeholder="Amount" min="1" step="1">
            <select class="stripe-currency">
                <option value="USD">USD</option>
                <option value="EUR">EUR</option>
                <option value="GBP">GBP</option>
            </select>
            <button class="stripe-flex-pay-btn" data-product="<?php echo esc_attr($atts['product']); ?>">
                Pay Now
            </button>
        </div>
        <?php
        return ob_get_clean();
    }

    // Fizetés feldolgozása
    public function process_payment() {
        check_ajax_referer('stripe_payment_nonce', 'nonce');

        try {
            $product_name = sanitize_text_field($_POST['product'] ?? 'Ebook Purchase');

            $session = \Stripe\Checkout\Session::create([
                'payment_method_types' => ['card'],
                'line_items' => [[
                    'price_data' => [
                        'currency' => sanitize_text_field($_POST['currency']),
                        'product_data' => [
                            'name' => $product_name
                        ],
                        'unit_amount' => sanitize_text_field($_POST['amount']) * 100,
                    ],
                    'quantity' => 1,
                ]],
                'mode' => 'payment',
                'success_url' => home_url('/success'),
                'cancel_url' => home_url('/cancel'),
            ]);

            wp_send_json_success(['id' => $session->id]);
        } catch (\Exception $e) {
            wp_send_json_error(['error' => $e->getMessage()]);
        }
    }
}

new Stripe_Payment_Handler();