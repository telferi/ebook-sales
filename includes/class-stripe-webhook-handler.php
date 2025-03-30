<?php
if (!defined('ABSPATH')) {
    exit;
}

class Stripe_Webhook_Handler {

    public function __construct() {
        add_action('init', array($this, 'handle_stripe_webhook'));
    }

    public function handle_stripe_webhook() {
        if (!isset($_GET['ebook_stripe_webhook'])) {
            return;
        }

        $payload = @file_get_contents('php://input');
        $sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'];
        $webhook_secret = get_option('stripe_webhook_secret');
        
        // Adatok mentése tesztelési célból
        $this->save_webhook_data_to_file($payload, $sig_header);

        try {
            $event = \Stripe\Webhook::constructEvent($payload, $sig_header, $webhook_secret);
        } catch (\Exception $e) {
            error_log('Stripe Webhook Error: ' . $e->getMessage());
            
            // A hiba adatainak mentése is
            $this->save_webhook_data_to_file('HIBA: ' . $e->getMessage(), '', 'error');
            
            http_response_code(403);
            exit;
        }

        if ($event->type === 'charge.succeeded') {
            $this->save_payment_data($event->data->object, 'test_payments');
        }

        if ($event->type === 'invoice.payment_succeeded') {
            $this->save_subscription_data($event->data->object);
        }

        http_response_code(200);
        exit;
    }
    
    /**
     * Menti a webhook adatokat egy szövegfájlba tesztelési célból
     * 
     * @param string $payload A webhook által küldött adatok
     * @param string $signature A webhook aláírás
     * @param string $prefix Fájlnév előtag (opcionális)
     * @return bool Sikeres mentés esetén true, egyébként false
     */
    private function save_webhook_data_to_file($payload, $signature = '', $prefix = '') {
        try {
            // Fájl elérési útja
            $uploads_dir = wp_upload_dir();
            $log_dir = $uploads_dir['basedir'] . '/stripe-logs';
            
            // Létrehozzuk a mappát, ha nem létezik
            if (!file_exists($log_dir)) {
                mkdir($log_dir, 0755, true);
            }
            
            // Fájl neve időbélyeggel
            $timestamp = date('Y-m-d_H-i-s');
            if (!empty($prefix)) {
                $timestamp = $prefix . '_' . $timestamp;
            }
            $filename = $log_dir . '/ment-stripe_' . $timestamp . '.txt';
            
            // Összeállítjuk a mentendő adatokat
            $data = "=== STRIPE WEBHOOK ADAT ===\n";
            $data .= "Időpont: " . date('Y-m-d H:i:s') . "\n";
            $data .= "IP-cím: " . $_SERVER['REMOTE_ADDR'] . "\n\n";
            
            if (!empty($signature)) {
                $data .= "=== SIGNATURE ===\n" . $signature . "\n\n";
            }
            
            $data .= "=== PAYLOAD ===\n" . $payload . "\n";
            
            // Fájl írása
            file_put_contents($filename, $data);
            
            // Sikeres mentés naplózása
            error_log('Stripe webhook adatok mentése sikeres: ' . $filename);
            
            return true;
        } catch (\Exception $e) {
            error_log('Hiba a Stripe webhook adatok mentése során: ' . $e->getMessage());
            return false;
        }
    }

    private function save_payment_data($charge, $table) {
        global $wpdb;
        $wpdb->insert(
            $table,
            array(
                'customer_name' => sanitize_text_field($charge->billing_details->name ?? 'N/A'),
                'customer_email' => sanitize_email($charge->billing_details->email ?? 'N/A'),
                'geo_location' => $this->get_geo_location($charge->billing_details->address),
                'amount' => intval($charge->amount / 100),
                'currency' => strtolower(sanitize_text_field($charge->currency)),
                'payment_status' => 'succeeded',
                'receipt_url' => esc_url_raw($charge->receipt_url)
            ),
            array('%s', '%s', '%s', '%d', '%s', '%s', '%s')
        );
    }

    private function save_subscription_data($invoice) {
        global $wpdb;
        $subscription = \Stripe\Subscription::retrieve($invoice->subscription);

        $wpdb->insert(
            'test_paysubs',
            array(
                'customer_name' => sanitize_text_field($invoice->customer_name ?? 'N/A'),
                'customer_email' => sanitize_email($invoice->customer_email ?? 'N/A'),
                'geo_location' => $this->get_geo_location($invoice->customer_address),
                'amount' => intval($invoice->amount_paid / 100),
                'currency' => strtolower(sanitize_text_field($invoice->currency)),
                'payment_status' => 'succeeded',
                'receipt_url' => esc_url_raw($invoice->hosted_invoice_url),
                'subscription_id' => sanitize_text_field($invoice->subscription),
                'interval' => sanitize_text_field($subscription->items->data[0]->plan->interval),
                'product' => sanitize_text_field($subscription->items->data[0]->plan->product)
            ),
            array('%s', '%s', '%s', '%d', '%s', '%s', '%s', '%s', '%s', '%s')
        );
    }

    private function get_geo_location($address) {
        if (!$address) return 'N/A';
        return implode(', ', array_filter([
            sanitize_text_field($address->city),
            sanitize_text_field($address->country)
        ]));
    }
}

new Stripe_Webhook_Handler();