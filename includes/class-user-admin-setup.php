<?php
// filepath: /home/telferenc/GitMunkamenetek/ebook-sales/includes/class-user-admin-setup.php
if (!defined('ABSPATH')) {
    exit;
}

class User_Admin_Setup {

    public function __construct() {
        add_action('admin_menu', [$this, 'register_submenus']);
        add_action('admin_init', [$this, 'register_settings']);
    }

    public function register_submenus() {
        // Regisztrációs oldal szerkesztése
        add_submenu_page(
            'users.php',
            __('Regisztrációs oldal szerkesztése', 'ebook-sales'),
            __('Regisztrációs oldal', 'ebook-sales'),
            'edit_users',
            'user-registration-edit',
            [$this, 'registration_page']
        );

        // Login oldal szerkesztése
        add_submenu_page(
            'users.php',
            __('Login oldal szerkesztése', 'ebook-sales'),
            __('Login oldal', 'ebook-sales'),
            'edit_users',
            'user-login-edit',
            [$this, 'login_page']
        );

        // User oldal szerkesztése
        add_submenu_page(
            'users.php',
            __('User oldal szerkesztése', 'ebook-sales'),
            __('User oldal', 'ebook-sales'),
            'edit_users',
            'user-account-edit',
            [$this, 'user_page']
        );

        // Extra User beállítások
        add_submenu_page(
            'users.php',
            __('Extra User beállítások', 'ebook-sales'),
            __('Extra beállítások', 'ebook-sales'),
            'edit_users',
            'user-extra-settings',
            [$this, 'extra_settings_page']
        );
    }

    public function register_settings() {
        // Példa opciók regisztrálása
        register_setting('user_registration_options', 'user_registration_page');
        register_setting('user_login_options', 'user_login_page');
        register_setting('user_account_options', 'user_account_page');
        register_setting('user_extra_options', 'user_extra_settings');
    }

    public function registration_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('Regisztrációs oldal szerkesztése', 'ebook-sales'); ?></h1>
            <form method="post" action="options.php">
                <?php
                    settings_fields('user_registration_options');
                    do_settings_sections('user-registration-settings');
                ?>
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="user_registration_page"><?php _e('Oldal URL / Slug', 'ebook-sales'); ?></label>
                        </th>
                        <td>
                            <input type="text" name="user_registration_page" id="user_registration_page" class="regular-text" value="<?php echo esc_attr(get_option('user_registration_page')); ?>">
                            <p class="description"><?php _e('Add meg az URL-t vagy slug-ot, ahol a regisztrációs oldal elérhető lesz.', 'ebook-sales'); ?></p>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    public function login_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('Login oldal szerkesztése', 'ebook-sales'); ?></h1>
            <form method="post" action="options.php">
                <?php
                    settings_fields('user_login_options');
                    do_settings_sections('user-login-settings');
                ?>
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="user_login_page"><?php _e('Oldal URL / Slug', 'ebook-sales'); ?></label>
                        </th>
                        <td>
                            <input type="text" name="user_login_page" id="user_login_page" class="regular-text" value="<?php echo esc_attr(get_option('user_login_page')); ?>">
                            <p class="description"><?php _e('Add meg az URL-t vagy slug-ot, ahol a login oldal elérhető lesz.', 'ebook-sales'); ?></p>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    public function user_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('User oldal szerkesztése', 'ebook-sales'); ?></h1>
            <form method="post" action="options.php">
                <?php
                    settings_fields('user_account_options');
                    do_settings_sections('user-account-settings');
                ?>
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="user_account_page"><?php _e('Oldal URL / Slug', 'ebook-sales'); ?></label>
                        </th>
                        <td>
                            <input type="text" name="user_account_page" id="user_account_page" class="regular-text" value="<?php echo esc_attr(get_option('user_account_page')); ?>">
                            <p class="description"><?php _e('Add meg az URL-t vagy slug-ot, ahol a user oldal elérhető lesz.', 'ebook-sales'); ?></p>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    public function extra_settings_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('Extra User beállítások', 'ebook-sales'); ?></h1>
            <form method="post" action="options.php">
                <?php
                    settings_fields('user_extra_options');
                    do_settings_sections('user-extra-settings');
                ?>
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="user_extra_settings"><?php _e('Extra opciók', 'ebook-sales'); ?></label>
                        </th>
                        <td>
                            <input type="text" name="user_extra_settings" id="user_extra_settings" class="regular-text" value="<?php echo esc_attr(get_option('user_extra_settings')); ?>">
                            <p class="description"><?php _e('Add meg a további konfigurációs beállításokat.', 'ebook-sales'); ?></p>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }
}

new User_Admin_Setup();