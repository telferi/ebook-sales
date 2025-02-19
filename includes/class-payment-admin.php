<?php
if (!defined('ABSPATH')) {
    exit;
}

class Payment_Admin {

    public function __construct() {
        add_action('admin_menu', array($this, 'add_payment_menu'));
    }

    public function add_payment_menu() {
        add_submenu_page(
            'ebook-sales',
            __('Payment Records', 'ebook-sales'),
            __('Payment Records', 'ebook-sales'),
            'manage_options',
            'ebook-payments',
            array($this, 'render_payment_list')
        );
    }

    public function render_payment_list() {
        global $wpdb;
        $table_name = 'test_payments';
        $payments = $wpdb->get_results("SELECT * FROM $table_name ORDER BY created_at DESC");

        echo '<div class="wrap">';
        echo '<h1>' . esc_html__('Payment Records', 'ebook-sales') . '</h1>';
        echo '<table class="wp-list-table widefat fixed striped">';
        echo '<thead><tr>
                <th>ID</th>
                <th>Name</th>
                <th>Email</th>
                <th>Location</th>
                <th>Amount</th>
                <th>Currency</th>
                <th>Status</th>
                <th>Receipt</th>
                <th>Date</th>
              </tr></thead>';
        echo '<tbody>';

        foreach ($payments as $payment) {
            echo '<tr>
                <td>' . $payment->id . '</td>
                <td>' . esc_html($payment->customer_name) . '</td>
                <td>' . esc_html($payment->customer_email) . '</td>
                <td>' . esc_html($payment->geo_location) . '</td>
                <td>' . number_format($payment->amount, 2) . '</td>
                <td>' . esc_html($payment->currency) . '</td>
                <td>' . esc_html($payment->payment_status) . '</td>
                <td><a href="' . esc_url($payment->receipt_url) . '" target="_blank">View Receipt</a></td>
                <td>' . date_i18n(get_option('date_format'), strtotime($payment->created_at)) . '</td>
              </tr>';
        }

        echo '</tbody></table>';
        echo '</div>';
    }
}

new Payment_Admin();