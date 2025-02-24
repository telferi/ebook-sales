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
        
        // wp-login.php letiltása: átirányítás a saját login oldalra
        add_action('login_init', [$this, 'disable_wp_login']);

        // Generált login_url módosítása
        add_filter('login_url', [$this, 'filter_login_url'], 10, 3);
    }
    
    // Rewrite szabály hozzáadása a login oldalhoz
    public function add_rewrite_rules() {
        $login_slug = get_option('user_subscription_login_page', 'login');
        add_rewrite_rule('^' . preg_quote($login_slug, '/') . '/?$', 'index.php?user_subscription_login=1', 'top');
    }
    
    // Egyéni query var engedélyezése
    public function add_query_vars($vars) {
        $vars[] = 'user_subscription_login';
        return $vars;
    }
    
    // Ha a query var jelen van, akkor megjelenítjük a login oldalt
    public function template_redirect() {
        if (intval(get_query_var('user_subscription_login')) === 1) {
            $this->login_page_template();
            exit;
        }
    }
    
    // Saját login oldal – ugyanaz a funkcionalitás, mint wp-login.php
    public function login_page_template() {
        if (is_user_logged_in()) {
            wp_redirect(home_url());
            exit;
        }

        // A wp_login_form() függvény alapértelmezett űrlapját jelenítjük meg
        // Amennyiben szükséges, testreszabhatod az argumentumokat
        get_header();
        echo '<div class="user-login">';
        echo '<h1>' . __('Bejelentkezés', 'ebook-sales') . '</h1>';
        wp_login_form( [
            'redirect' => home_url(), // Átirányítás bejelentkezés után
        ]);
        // Megjeleníthetjük a lost password link-et
        echo '<p><a href="' . esc_url(wp_lostpassword_url()) . '">' . __('Elfelejtetted a jelszavad?', 'ebook-sales') . '</a></p>';
        echo '</div>';
        get_footer();
    }
    
    // Ha a wp-login.php-ra jön a kérés, átirányítjuk a saját login oldalra
    public function disable_wp_login() {
        // DOING_AJAX esetén nem irányítjuk át
        if (defined('DOING_AJAX') && DOING_AJAX) {
            return;
        }
        
        // Ha a kérés nem a saját login slug-ot tartalmazza, átirányítjuk
        $login_slug = get_option('user_subscription_login_page', 'login');
        $current_url = $_SERVER['REQUEST_URI'];
        if (strpos($current_url, '/' . $login_slug) === false) {
            wp_redirect(home_url('/' . $login_slug));
            exit;
        }
    }
    
    // A beépített login_url módosítása, hogy mindig a saját URL-re mutasson
    public function filter_login_url($login_url, $redirect, $force_reauth) {
        $login_slug = get_option('user_subscription_login_page', 'login');
        return home_url('/' . $login_slug . '/');
    }
}

new User_Subscription_Login();