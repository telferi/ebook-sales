<?php
// filepath: /home/telferenc/GitMunkamenetek/ebook-sales/includes/handler-dependency-setting.php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action( 'admin_post_save_dependency_condition', 'handle_save_dependency_condition' );
add_action( 'admin_post_delete_dependency_condition', 'handle_delete_dependency_condition' );

function handle_save_dependency_condition() {
    // Ellenőrizd a nonce-t
    if ( ! isset( $_POST['dependency_condition_nonce'] ) || ! wp_verify_nonce( $_POST['dependency_condition_nonce'], 'save_dependency_condition' ) ) {
        wp_die( __( 'Érvénytelen nonce érték!', 'ebook-sales' ) );
    }
    // Jogosultság ellenőrzése
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( __( 'Nincs jogosultságod ehhez a művelethez!', 'ebook-sales' ) );
    }

    // Lekérjük a meglévő feltételeket
    $conditions = get_option( 'ebook_dependency_conditions', array() );

    // Összeállítjuk az új feltétel tömböt
    $new_condition = array(
        'id'                  => time(), // egyszerű egyedi azonosító, mellőzhető egy fejlettebb megoldás
        'user_type'           => sanitize_text_field( $_POST['user_type'] ),
        'test_condition'      => sanitize_text_field( $_POST['test_condition'] ),
        'comparison_operator' => isset( $_POST['comparison_operator'] ) ? sanitize_text_field( $_POST['comparison_operator'] ) : '',
        'comparison_amount'   => isset( $_POST['comparison_amount'] ) ? floatval( $_POST['comparison_amount'] ) : 0,
        'changed_result'      => sanitize_text_field( $_POST['changed_result'] )
    );

    // Hozzáadjuk az új feltételt a feltételek tömbhöz
    $conditions[] = $new_condition;

    // Mentjük az opciót a wp_options táblában
    update_option( 'ebook_dependency_conditions', $conditions );

    // Átirányítás vissza a Dependency Settings oldalra
    wp_redirect( admin_url( 'admin.php?page=ebook-dependency-settings' ) );
    exit;
}

function handle_delete_dependency_condition() {
    // Ellenőrizzük, hogy van-e id
    if ( ! isset( $_GET['id'] ) ) {
        wp_die( __( 'Nincs megadva azonosító', 'ebook-sales' ) );
    }
    $id = intval( $_GET['id'] );

    // Nonce ellenőrzés
    if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( $_GET['_wpnonce'], 'delete_dependency_condition_' . $id ) ) {
        wp_die( __( 'Érvénytelen nonce', 'ebook-sales' ) );
    }

    // Lekérjük a mentett feltételeket
    $conditions = get_option( 'ebook_dependency_conditions', array() );
    foreach ( $conditions as $key => $condition ) {
        if ( intval( $condition['id'] ) === $id ) {
            unset( $conditions[ $key ] );
            break;
        }
    }
    // Újraszámozzuk a tömböt, és mentjük
    update_option( 'ebook_dependency_conditions', array_values( $conditions ) );

    wp_redirect( admin_url( 'admin.php?page=ebook-dependency-settings' ) );
    exit;
}