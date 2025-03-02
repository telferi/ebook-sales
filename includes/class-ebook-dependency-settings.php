<?php
// filepath: /home/telferenc/GitMunkamenetek/ebook-sales/includes/class-ebook-dependency-settings.php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Ebook_Dependency_Settings {

    public function __construct() {
        add_action( 'admin_menu', [ $this, 'add_dependency_submenu' ] );
        add_action( 'admin_init', [ $this, 'register_dependency_settings' ] );
    }

    public function add_dependency_submenu() {
        add_submenu_page(
            'ebook-sales', // a főmenü slugja – módosítsd ha szükséges
            __( 'Függőségi beállítások', 'ebook-sales' ),
            __( 'Dependency Settings', 'ebook-sales' ),
            'manage_options',
            'ebook-dependency-settings',
            [ $this, 'dependency_settings_page' ]
        );
    }

    public function register_dependency_settings() {
        register_setting( 'ebook_dependency_options', 'ebook_dependency_settings' );
    }

    public function dependency_settings_page() {
        ?>
        <div class="wrap">
            <h1><?php _e( 'Függőségi beállítások', 'ebook-sales' ); ?></h1>
            <form method="post" action="options.php">
                <?php 
                    settings_fields( 'ebook_dependency_options' );
                    do_settings_sections( 'ebook-dependency-settings' );
                ?>
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="ebook_dependency_settings"><?php _e( 'Dependency Settings', 'ebook-sales' ); ?></label>
                        </th>
                        <td>
                            <input type="text" name="ebook_dependency_settings" id="ebook_dependency_settings" class="regular-text" value="<?php echo esc_attr( get_option( 'ebook_dependency_settings' ) ); ?>">
                            <p class="description"><?php _e( 'Add meg a szükséges függőségi beállításokat itt.', 'ebook-sales' ); ?></p>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }
}

new Ebook_Dependency_Settings();