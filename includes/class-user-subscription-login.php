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
        
        // wp-login.php letiltása és átirányítása
        add_action('login_init', [$this, 'disable_wp_login']);

        // Módosítjuk a login_url generálódását is
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
    
    // A bejelentkező oldal sablonja
    public function login_page_template() {
        if (is_user_logged_in()) {
            wp_redirect(home_url());
            exit;
        }
        
        $errors = [];
        // Űrlap feldolgozása
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_login'])) {
            if (!isset($_POST['user_subscription_login_nonce']) || 
                !wp_verify_nonce($_POST['user_subscription_login_nonce'], 'user_subscription_login_action')) {
                $errors[] = __('Hibás űrlap ellenőrzés.', 'ebook-sales');
            } else {
                $credentials = [
                    'user_login'    => sanitize_user($_POST['user_login']),
                    'user_password' => $_POST['user_pass'],
                    'remember'      => isset($_POST['remember']) ? true : false,
                ];
                $user = wp_signon($credentials, false);
                if (is_wp_error($user)) {
                    $errors[] = __('Hibás felhasználónév vagy jelszó.', 'ebook-sales');
                } else {
                    wp_redirect(home_url());
                    exit;
                }
            }
        }
        
        get_header();
        echo '<div class="user-login">';
        echo '<h1>' . __('Bejelentkezés', 'ebook-sales') . '</h1>';
        if (!empty($errors)) {
            foreach ($errors as $error) {
                echo '<p style="color:red;">' . esc_html($error) . '</p>';
            }
        }
        ?>
        <form method="post">
            <?php wp_nonce_field('user_subscription_login_action', 'user_subscription_login_nonce'); ?>
            <p>
                <label for="user_login"><?php _e('Felhasználónév:', 'ebook-sales'); ?></label>
                <input type="text" name="user_login" id="user_login" required>
            </p>
            <p>
                <label for="user_pass"><?php _e('Jelszó:', 'ebook-sales'); ?></label>
                <input type="password" name="user_pass" id="user_pass" required>
            </p>
            <p>
                <label for="remember">
                    <input type="checkbox" name="remember" id="remember"> <?php _e('Emlékezz rám', 'ebook-sales'); ?>
                </label>
            </p>
            <p>
                <input type="submit" value="<?php _e('Bejelentkezés', 'ebook-sales'); ?>">
            </p>
        </form>
        <?php
        echo '</div>';
        get_footer();
    }
    
    // wp-login.php letiltása: átirányítás a saját login oldalra
    public function disable_wp_login() {
        // DOING_AJAX esetén ne irányítsuk át
        if (defined('DOING_AJAX') && DOING_AJAX) {
            return;
        }
        // Átirányítás a saját login oldalra
        $login_slug = get_option('user_subscription_login_page', 'login');
        wp_redirect(home_url('/' . $login_slug));
        exit;
    }
    
    // Módosítja a login_url szűrőn keresztül az URL-t a saját login oldalra
    public function filter_login_url($login_url, $redirect, $force_reauth) {
        $login_slug = get_option('user_subscription_login_page', 'login');
        return home_url('/' . $login_slug);
    }
}

new User_Subscription_Login();