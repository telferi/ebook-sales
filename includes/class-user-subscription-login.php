<?php
// filepath: /home/telferenc/GitMunkamenetek/ebook-sales/includes/class-user-subscription-login.php
if (!defined('ABSPATH')) {
    exit;
}

class User_Subscription_Login {

    public function __construct() {
        // Rewrite szabály a saját login oldalhoz
        add_action('init', [$this, 'add_rewrite_rules']);
        add_filter('query_vars', [$this, 'add_query_vars']);
        add_action('template_redirect', [$this, 'template_redirect']);
        
        // wp-login.php letiltása: ha közvetlenül hívják, átirányítunk a saját URL-re
        add_action('login_init', [$this, 'disable_wp_login']);

        // Generált login_url módosítása
        add_filter('login_url', [$this, 'filter_login_url'], 10, 3);
    }
    
    public function add_rewrite_rules() {
        $login_slug = get_option('user_subscription_login_page', 'login');
        add_rewrite_rule('^' . preg_quote($login_slug, '/') . '/?$', 'index.php?user_subscription_login=1', 'top');
    }
    
    public function add_query_vars($vars) {
        $vars[] = 'user_subscription_login';
        return $vars;
    }
    
    public function template_redirect() {
        if (intval(get_query_var('user_subscription_login')) === 1) {
            $this->login_page_template();
            exit;
        }
    }
    
    // Az a metódus, amely meghívja a wp-login.php-t úgy, hogy a URL a /login maradjon
    public function login_page_template() {
        if (is_user_logged_in()) {
            wp_redirect(home_url());
            exit;
        }
        
        // Ha nincs explicit action, tűzzük be a "login" műveletet
        if (!isset($_GET['action'])) {
            $_GET['action'] = 'login';
        }
        
        // Állítsuk be úgy a SERVER változókat, hogy a wp-login.php "maskolva" legyen.
        $login_slug = get_option('user_subscription_login_page', 'login');
        // Például kényszerítsük a REQUEST_URI-t a /login/ URL-re
        $_SERVER['REQUEST_URI'] = '/' . trailingslashit($login_slug);
        // Ha szükséges, módosíthatjuk a SCRIPT_NAME-t is
        $_SERVER['SCRIPT_NAME'] = '/' . trailingslashit($login_slug);
        
        // Most include-oljuk a WP beépített login fájlját.
        require_once(ABSPATH . 'wp-login.php');
        exit;
    }
    
    // Ha valaki közvetlenül a wp-login.php-t hívja meg (pl. /wp-login.php), átirányítjuk a /login URL-re 
    public function disable_wp_login() {
        // DOING_AJAX esetén nem irányítjuk át
        if (defined('DOING_AJAX') && DOING_AJAX) {
            return;
        }
        
        // Ha a query var, amelyet a rewrite használ, nincs beállítva (tehát nem a /login URL-ről jön a kérés), irányítsuk át.
        if (!isset($_GET['user_subscription_login'])) {
            $login_slug = get_option('user_subscription_login_page', 'login');
            wp_redirect(home_url('/' . trailingslashit($login_slug)));
            exit;
        }
    }
    
    // A beépített login_url módosítása, hogy mindig a saját URL-re mutasson
    public function filter_login_url($login_url, $redirect, $force_reauth) {
        $login_slug = get_option('user_subscription_login_page', 'login');
        return home_url('/' . trailingslashit($login_slug));
    }
}

new User_Subscription_Login();