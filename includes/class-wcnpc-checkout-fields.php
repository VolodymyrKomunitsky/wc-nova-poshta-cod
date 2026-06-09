<?php
defined( 'ABSPATH' ) || exit;

/**
 * Додаткова клієнтська валідація полів чекауту.
 * Серверна валідація залишається основною — JS лише покращує UX.
 */
class WCNPC_Checkout_Fields {

    public function __construct() {
        // Відображення помилок валідації у реальному часі через AJAX
        add_action( 'woocommerce_review_order_before_submit', [ $this, 'add_nonce_field' ] );
        add_action( 'wp_ajax_nopriv_wcnpc_validate_fields',   [ $this, 'ajax_validate' ] );
        add_action( 'wp_ajax_wcnpc_validate_fields',          [ $this, 'ajax_validate' ] );
    }

    /**
     * Nonce для AJAX-запиту.
     */
    public function add_nonce_field(): void {
        wp_nonce_field( 'wcnpc_validate', 'wcnpc_nonce' );
    }

    /**
     * AJAX: валідація полів без перезавантаження сторінки.
     */
    public function ajax_validate(): void {
        check_ajax_referer( 'wcnpc_validate', 'nonce' );

        $errors = [];

        $city = isset( $_POST['city'] )
            ? sanitize_text_field( wp_unslash( $_POST['city'] ) )
            : '';

        $branch = isset( $_POST['branch'] )
            ? sanitize_text_field( wp_unslash( $_POST['branch'] ) )
            : '';

        if ( mb_strlen( $city ) < 2 ) {
            $errors['city'] = __( 'Мінімум 2 символи', 'wc-nova-poshta-cod' );
        }

        if ( $branch && ! preg_match( '/^\d{1,5}$/', $branch ) ) {
            $errors['branch'] = __( 'Лише цифри (1–99999)', 'wc-nova-poshta-cod' );
        }

        wp_send_json( [
            'valid'  => empty( $errors ),
            'errors' => $errors,
        ] );
    }
}
