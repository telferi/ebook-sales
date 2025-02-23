<?php
if (!defined('ABSPATH')) {
    exit;
}

class User_Subscription_Menu {

    public function __construct() {
        add_shortcode('subscription_menu', [$this, 'render_subscription_menu']);
        add_action('init', [$this, 'handle_form_submission']);
    }

    public function render_subscription_menu() {
        if (!is_user_logged_in()) {
            return '<p>Kérjük, jelentkezz be a további funkciók eléréséhez.</p>';
        }

        $current_user = wp_get_current_user();
        $bio  = get_user_meta($current_user->ID, 'bio', true);
        $rank = get_user_meta($current_user->ID, 'user_rank', true);
        ob_start();
        ?>
        <form method="post">
            <?php wp_nonce_field('subscription_menu_update', 'subscription_menu_nonce'); ?>
            <p>
                <label for="bio">Bio:</label><br>
                <textarea name="bio" id="bio" rows="4" cols="50"><?php echo esc_textarea($bio); ?></textarea>
            </p>
            <p>
                <label for="rank">Rank:</label><br>
                <select name="rank" id="rank">
                    <option value="bronze" <?php selected($rank, 'bronze'); ?>>Bronze</option>
                    <option value="silver" <?php selected($rank, 'silver'); ?>>Silver</option>
                    <option value="gold" <?php selected($rank, 'gold'); ?>>Gold</option>
                </select>
            </p>
            <input type="submit" value="Mentés">
        </form>
        <?php
        return ob_get_clean();
    }

    public function handle_form_submission() {
        if (isset($_POST['subscription_menu_nonce']) && wp_verify_nonce($_POST['subscription_menu_nonce'], 'subscription_menu_update')) {
            if (is_user_logged_in()) {
                $current_user = wp_get_current_user();

                if (isset($_POST['bio'])) {
                    update_user_meta($current_user->ID, 'bio', sanitize_textarea_field($_POST['bio']));
                }

                if (isset($_POST['rank'])) {
                    update_user_meta($current_user->ID, 'user_rank', sanitize_text_field($_POST['rank']));
                }

                // Opcionális: üzenet megjelenítése vagy átirányítás
            }
        }
    }
}

new User_Subscription_Menu();