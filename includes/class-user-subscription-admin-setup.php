<?php
// filepath: /home/telferenc/GitMunkamenetek/ebook-sales/includes/class-user-subscription-admin-setup.php
if (!defined('ABSPATH')) {
    exit;
}

class User_Subscription_Admin_Setup {

    public function __construct() {
        add_action('admin_menu', [$this, 'admin_menu']);
    }

    public function admin_menu() {
        // Hozzáad egy almenüt a Users menühöz
        add_submenu_page(
            'users.php', 
            __('Előfizetés Setup', 'ebook-sales'), 
            __('Előfizetés Setup', 'ebook-sales'), 
            'manage_options', 
            'user-subscription-setup', 
            [$this, 'setup_page']
        );
    }

    public function setup_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('Nincs jogosultságod ehhez az oldalhoz!', 'ebook-sales'));
        }

        if (isset($_POST['submit'])) {
            check_admin_referer('user_subscription_setup_save', 'user_subscription_setup_nonce');

            // Adatok mentése, a mezők értékét itt tetszés szerint lehet konfigurálni
            $login_page        = sanitize_text_field($_POST['login_page']);
            $register_page     = sanitize_text_field($_POST['register_page']);
            $account_page      = sanitize_text_field($_POST['account_page']);
            $profile_view_page = sanitize_text_field($_POST['profile_view_page']);
            $rank_options      = sanitize_text_field($_POST['rank_options']);

            update_option('user_subscription_login_page', $login_page);
            update_option('user_subscription_register_page', $register_page);
            update_option('user_subscription_account_page', $account_page);
            update_option('user_subscription_profile_view_page', $profile_view_page);
            update_option('user_subscription_rank_options', $rank_options);

            echo '<div class="updated"><p>' . __('Beállítások mentve!', 'ebook-sales') . '</p></div>';
        }

        // Korábbi értékek betöltése
        $login_page        = get_option('user_subscription_login_page', '');
        $register_page     = get_option('user_subscription_register_page', '');
        $account_page      = get_option('user_subscription_account_page', '');
        $profile_view_page = get_option('user_subscription_profile_view_page', '');
        $rank_options      = get_option('user_subscription_rank_options', 'bronze,silver,gold');
        ?>
        <div class="wrap">
            <h1><?php _e('Előfizetés Setup', 'ebook-sales'); ?></h1>
            <form method="POST">
                <?php wp_nonce_field('user_subscription_setup_save', 'user_subscription_setup_nonce'); ?>
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="login_page"><?php _e('Bejelentkező oldal', 'ebook-sales'); ?></label>
                        </th>
                        <td>
                            <input type="text" name="login_page" id="login_page" value="<?php echo esc_attr($login_page); ?>" class="regular-text" />
                            <p class="description"><?php _e('Adj meg egy oldalcímet vagy URL-t a bejelentkező oldalhoz.', 'ebook-sales'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="register_page"><?php _e('Regisztrációs oldal', 'ebook-sales'); ?></label>
                        </th>
                        <td>
                            <input type="text" name="register_page" id="register_page" value="<?php echo esc_attr($register_page); ?>" class="regular-text" />
                            <p class="description"><?php _e('Adj meg egy oldalcímet vagy URL-t a regisztrációhoz.', 'ebook-sales'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="account_page"><?php _e('Fiók szerkesztése oldal', 'ebook-sales'); ?></label>
                        </th>
                        <td>
                            <input type="text" name="account_page" id="account_page" value="<?php echo esc_attr($account_page); ?>" class="regular-text" />
                            <p class="description"><?php _e('Adj meg egy oldalcímet vagy URL-t a fiók szerkesztéséhez.', 'ebook-sales'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="profile_view_page"><?php _e('Profil megtekintése oldal', 'ebook-sales'); ?></label>
                        </th>
                        <td>
                            <input type="text" name="profile_view_page" id="profile_view_page" value="<?php echo esc_attr($profile_view_page); ?>" class="regular-text" />
                            <p class="description"><?php _e('Adj meg egy oldalcímet vagy URL-t a profil megtekintéséhez.', 'ebook-sales'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="rank_options"><?php _e('Rank opciók (vesszővel elválasztva)', 'ebook-sales'); ?></label>
                        </th>
                        <td>
                            <input type="text" name="rank_options" id="rank_options" value="<?php echo esc_attr($rank_options); ?>" class="regular-text" />
                            <p class="description"><?php _e('Pl.: bronze,silver,gold', 'ebook-sales'); ?></p>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }
}

new User_Subscription_Admin_Setup();