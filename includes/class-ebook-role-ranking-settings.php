<?php
// filepath: /home/telferenc/GitMunkamenetek/ebook-sales/includes/class-ebook-role-ranking-settings.php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Ebook_Role_Ranking_Settings {

    public function __construct() {
        add_action( 'admin_menu', [ $this, 'add_role_ranking_submenu' ] );
        add_action( 'admin_init', [ $this, 'register_role_ranking_settings' ] );
    }

    public function add_role_ranking_submenu() {
        add_submenu_page(
            'ebook-sales', // Főmenü slugja
            __( 'Szerepkör rang beállítások', 'ebook-sales' ),
            __( 'Role Ranking', 'ebook-sales' ),
            'manage_options',
            'ebook-role-ranking-settings',
            [ $this, 'role_ranking_settings_page' ]
        );
    }

    public function register_role_ranking_settings() {
        register_setting(
            'ebook_role_ranking_options',
            'ebook_role_ranking_settings',
            [
                'sanitize_callback' => [ $this, 'sanitize_role_ranking' ]
            ]
        );
    }

    public function sanitize_role_ranking( $input ) {
        $output = [];
        for ( $i = 1; $i <= 5; $i++ ) {
            if ( isset( $input[ "role_rank_$i" ] ) ) {
                $output[ "role_rank_$i" ] = sanitize_text_field( $input[ "role_rank_$i" ] );
            }
        }
        return $output;
    }

    public function role_ranking_settings_page() {
        ?>
        <div class="wrap">
            <h1><?php _e( 'Szerepkör rang beállítások', 'ebook-sales' ); ?></h1>
            <form method="post" action="options.php">
                <?php settings_fields( 'ebook_role_ranking_options' ); ?>
                <?php do_settings_sections( 'ebook_role_ranking_options' ); ?>
                <?php
                $options = get_option( 'ebook_role_ranking_settings' );
                $editable_roles = get_editable_roles();
                $role_options = '<option value="">' . esc_html__( 'Válassz egy szerepkört', 'ebook-sales' ) . '</option>';
                foreach ( $editable_roles as $role_key => $role_info ) {
                    if ( 'administrator' === $role_key ) {
                        continue;
                    }
                    $role_options .= '<option value="' . esc_attr( $role_key ) . '">' . esc_html( $role_info['name'] ) . '</option>';
                }
                ?>
                <table class="form-table">
                    <?php for ( $i = 1; $i <= 5; $i++ ) : ?>
                        <tr>
                            <th scope="row">
                                <label for="role_rank_<?php echo $i; ?>">
                                    <?php echo sprintf(
                                        __( 'Szerepkör rang %d%s', 'ebook-sales' ),
                                        $i,
                                        ( $i === 1 ? ' (' . __( 'legkisebb', 'ebook-sales' ) . ')' : ( $i === 5 ? ' (' . __( 'legnagyobb', 'ebook-sales' ) . ')' : '' ) )
                                    ); ?>
                                </label>
                            </th>
                            <td>
                                <select name="ebook_role_ranking_settings[role_rank_<?php echo $i; ?>]" id="role_rank_<?php echo $i; ?>">
                                    <?php echo $role_options; ?>
                                </select>
                            </td>
                        </tr>
                    <?php endfor; ?>
                </table>
                <?php submit_button( __( 'Mentés', 'ebook-sales' ) ); ?>
            </form>
        </div>
        <?php
    }
}

new Ebook_Role_Ranking_Settings();