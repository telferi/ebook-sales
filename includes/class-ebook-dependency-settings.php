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
            <h1><?php _e('Függőségi beállítások', 'ebook-sales'); ?></h1>
            <?php
            // Új feltétel hozzáadásának űrlapja
            if ( isset($_GET['action']) && $_GET['action'] == 'new' ) {
                ?>
                <h2><?php _e('Új feltétel hozzáadása', 'ebook-sales'); ?></h2>
                <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
                    <?php 
                        wp_nonce_field('save_dependency_condition', 'dependency_condition_nonce');
                    ?>
                    <input type="hidden" name="action" value="save_dependency_condition">
                    <table class="form-table">
                        <!-- Felhasználó típusa -->
                        <tr>
                            <th scope="row">
                                <label for="user_type"><?php _e('Felhasználó típusa', 'ebook-sales'); ?></label>
                            </th>
                            <td>
                                <select name="user_type" id="user_type">
                                    <option value="registered"><?php _e('Regisztrált Látogató', 'ebook-sales'); ?></option>
                                    <option value="guest"><?php _e('Vendég', 'ebook-sales'); ?></option>
                                </select>
                            </td>
                        </tr>
                        <!-- Vizsgált feltétel -->
                        <tr>
                            <th scope="row">
                                <label for="test_condition"><?php _e('Vizsgált feltétel', 'ebook-sales'); ?></label>
                            </th>
                            <td>
                                <select name="test_condition" id="test_condition" required>
                                    <option value="social_share"><?php _e('Közösségi megosztás', 'ebook-sales'); ?></option>
                                    <option value="support_donation"><?php _e('Támogatás', 'ebook-sales'); ?></option>
                                    <option value="ebook_purchase"><?php _e('Ebook vásárlás', 'ebook-sales'); ?></option>
                                </select>
                                <p class="description" id="test_condition_desc">
                                    <?php 
                                    // Default: regisztrált felhasználó esetén
                                    _e('Válaszd ki, hogy melyik esemény esetén történjen szerepkör hozzárendelés.', 'ebook-sales'); 
                                    ?>
                                </p>
                            </td>
                        </tr>
                        <!-- Extra mezők támogatás vagy ebook vásárlás esetén -->
                        <tr id="comparison_fields" style="display:none;">
                            <th scope="row">
                                <label for="comparison_operator"><?php _e('Összehasonlító operátor', 'ebook-sales'); ?></label>
                            </th>
                            <td>
                                <select name="comparison_operator" id="comparison_operator">
                                    <option value="less"><?php _e('Kisebb', 'ebook-sales'); ?></option>
                                    <option value="greater"><?php _e('Nagyobb', 'ebook-sales'); ?></option>
                                    <option value="equal"><?php _e('Egyenlő', 'ebook-sales'); ?></option>
                                    <option value="ge"><?php _e('Nagyobb vagy egyenlő', 'ebook-sales'); ?></option>
                                    <option value="le"><?php _e('Kisebb vagy egyenlő', 'ebook-sales'); ?></option>
                                    <option value="neq"><?php _e('Nem egyenlő', 'ebook-sales'); ?></option>
                                </select>
                            </td>
                        </tr>
                        <tr id="amount_field" style="display:none;">
                            <th scope="row">
                                <label for="comparison_amount"><?php _e('Összeg (USD)', 'ebook-sales'); ?></label>
                            </th>
                            <td>
                                <input type="number" name="comparison_amount" id="comparison_amount" class="regular-text" min="1" step="0.01">
                            </td>
                        </tr>
                        <?php
                        // Szerepkör opciók elkészítése
                        $editable_roles = get_editable_roles();
                        $role_options = '<option value="">' . esc_html__('Válassz egy szerepkört', 'ebook-sales') . '</option>';
                        foreach ( $editable_roles as $role_key => $role_info ) {
                            if ( 'administrator' === $role_key ) {
                                continue;
                            }
                            $role_options .= '<option value="' . esc_attr($role_key) . '">' . esc_html($role_info['name']) . '</option>';
                        }
                        ?>
                        <!-- Kívánt eredmény (a mező, ahol szerepkörök választhatók) -->
                        <tr>
                            <th scope="row">
                                <label for="changed_result"><?php _e('Kívánt eredmény', 'ebook-sales'); ?></label>
                            </th>
                            <td>
                                <select name="changed_result" id="changed_result" required>
                                    <?php echo $role_options; ?>
                                </select>
                            </td>
                        </tr>
                    </table>
                    <?php submit_button(__('Mentés', 'ebook-sales')); ?>
                </form>
                <p>
                    <a href="<?php echo admin_url('admin.php?page=ebook-dependency-settings'); ?>">
                        &laquo; <?php _e('Vissza a listához', 'ebook-sales'); ?>
                    </a>
                </p>
                <script type="text/javascript">
                    (function($){
                        function updateTestConditionDesc() {
                            var userType = $('#user_type').val();
                            var testCondition = $('#test_condition').val();
                            var desc = '';
                            if(userType === 'guest') {
                                desc = '<?php _e('Válaszd ki, hogy melyik esemény esetén történjen automatikus regisztráció és szerepkör hozzárendelés.', 'ebook-sales'); ?>';
                            } else {
                                desc = '<?php _e('Válaszd ki, hogy melyik esemény esetén történjen szerepkör hozzárendelés.', 'ebook-sales'); ?>';
                            }
                            $('#test_condition_desc').text(desc);
                            
                            // Ha támogatás vagy ebook vásárlás, jelenjenek meg az extra mezők
                            if(testCondition === 'support_donation' || testCondition === 'ebook_purchase'){
                                $('#comparison_fields, #amount_field').show();
                                $('#comparison_amount').attr('required', 'required');
                            } else {
                                $('#comparison_fields, #amount_field').hide();
                                $('#comparison_amount').removeAttr('required');
                            }
                        }
                        $('#user_type, #test_condition').on('change', updateTestConditionDesc);
                        updateTestConditionDesc();
                    })(jQuery);
                </script>
                <?php
            } else { 
                // Felső bal oldali "Add New" gomb és a feltétel lista
                ?>
                <p>
                    <a href="<?php echo admin_url('admin.php?page=ebook-dependency-settings&action=new'); ?>" class="button button-primary">
                        <?php _e('Add New', 'ebook-sales'); ?>
                    </a>
                </p>
                <?php
                $role_ranking = get_option( 'ebook_role_ranking_settings' );
                if ( $role_ranking && isset( $role_ranking['role_ranking_count'] ) ) {
                    $count = intval( $role_ranking['role_ranking_count'] );
                    $editable_roles = get_editable_roles();
                    echo '<div class="role-ranking-summary" style="margin-bottom:15px;">';
                    echo '<strong>' . __( 'Szerepkör rang sorrend:', 'ebook-sales' ) . '</strong> ';
                    for ( $i = 1; $i <= $count; $i++ ) {
                        if ( ! empty( $role_ranking[ "role_rank_$i" ] ) ) {
                            $role_key = $role_ranking[ "role_rank_$i" ];
                            $role_name = isset( $editable_roles[ $role_key ]['name'] ) ? $editable_roles[ $role_key ]['name'] : $role_key;
                            echo sprintf( __( '%d: %s', 'ebook-sales' ), $i, esc_html( $role_name ) ) . ' ';
                        }
                    }
                    echo '</div>';
                }
                ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php _e('ID', 'ebook-sales'); ?></th>
                            <th><?php _e('Vizsgált feltétel', 'ebook-sales'); ?></th>
                            <th><?php _e('Felhasználó típusa', 'ebook-sales'); ?></th>
                            <th><?php _e('Kívánt eredmény', 'ebook-sales'); ?></th>
                            <th><?php _e('Műveletek', 'ebook-sales'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $conditions = get_option('ebook_dependency_conditions', array());
                        if ( ! empty( $conditions ) ) {
                            foreach ( $conditions as $condition ) {
                                echo '<tr>';
                                echo '<td>' . esc_html( $condition['id'] ) . '</td>';
                                echo '<td>' . esc_html( $condition['test_condition'] ) . '</td>';
                                $user_type = '';
                                if ( isset($condition['user_type']) ) {
                                    $user_type = ($condition['user_type'] === 'registered') ? __('Regisztrált Látogató', 'ebook-sales') : __('Vendég', 'ebook-sales');
                                }
                                echo '<td>' . esc_html( $user_type ) . '</td>';
                                echo '<td>' . esc_html( $condition['changed_result'] ) . '</td>';
                                echo '<td>';
                                $edit_url = admin_url('admin.php?page=ebook-dependency-settings&action=edit&id=' . intval($condition['id']));
                                $delete_url = wp_nonce_url(admin_url('admin-post.php?action=delete_dependency_condition&id=' . intval($condition['id'])), 'delete_dependency_condition_' . $condition['id']);
                                echo '<a href="'. esc_url($edit_url) .'">'. __('Edit', 'ebook-sales') .'</a> | ';
                                echo '<a href="'. esc_url($delete_url) .'" onclick="return confirm(\''. __('Biztosan törlöd?', 'ebook-sales') .'\');">'. __('Delete', 'ebook-sales') .'</a>';
                                echo '</td>';
                                echo '</tr>';
                            }
                        } else {
                            echo '<tr><td colspan="5">' . __('Nincs feltétel hozzáadva.', 'ebook-sales') . '</td></tr>';
                        }
                        ?>
                    </tbody>
                </table>
                <?php
            }
            ?>
        </div>
        <?php
    }
}

new Ebook_Dependency_Settings();