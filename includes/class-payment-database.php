<?php
if (!defined('ABSPATH')) {
    exit;
}

class Payment_Database {

    public static function create_table() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        // 1. Normál fizetések táblája
        $sql_payments = "CREATE TABLE test_payments (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            customer_name varchar(100) NOT NULL,
            customer_email varchar(100) NOT NULL,
            geo_location varchar(100),
            amount int(11) NOT NULL,
            currency varchar(10) NOT NULL,
            payment_status varchar(50) NOT NULL,
            receipt_url varchar(255),
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY (id)
        ) $charset_collate;";

        // 2. Előfizetések táblája (JAVÍTVA: `interval` escape-elése)
        $sql_subs = "CREATE TABLE test_paysubs (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            customer_name varchar(100) NOT NULL,
            customer_email varchar(100) NOT NULL,
            geo_location varchar(100),
            amount int(11) NOT NULL,
            currency varchar(10) NOT NULL,
            payment_status varchar(50) NOT NULL,
            receipt_url varchar(255),
            subscription_id varchar(255) NOT NULL,
            `interval` varchar(20) NOT NULL, 
            product varchar(255) NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY (id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_payments);
        dbDelta($sql_subs);
    }
}