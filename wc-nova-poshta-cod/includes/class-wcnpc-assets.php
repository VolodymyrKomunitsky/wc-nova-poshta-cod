<?php
defined( 'ABSPATH' ) || exit;

/**
 * Підключення CSS і JS тільки на потрібних сторінках.
 * Жодних глобальних підключень — продуктивність перш за все.
 */
class WCNPC_Assets {

    public function __construct() {
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_frontend' ] );
    }

    public function enqueue_frontend(): void {
        if ( ! is_checkout() ) {
            return;
        }

        wp_enqueue_style(
            'wcnpc-checkout',
            WCNPC_URL . 'assets/css/checkout.css',
            [],
            WCNPC_VERSION
        );

        wp_enqueue_script(
            'wcnpc-checkout',
            WCNPC_URL . 'assets/js/checkout.js',
            [ 'jquery', 'wc-checkout' ],
            WCNPC_VERSION,
            true // У футері
        );

        wp_localize_script( 'wcnpc-checkout', 'wcnpc', [
            'ajaxUrl'      => admin_url( 'admin-ajax.php' ),
            'nonce'        => wp_create_nonce( 'wcnpc_validate' ),
            'i18n'         => [
                'city_required'   => __( 'Вкажіть місто', 'wc-nova-poshta-cod' ),
                'branch_required' => __( 'Вкажіть номер відділення', 'wc-nova-poshta-cod' ),
                'branch_invalid'  => __( 'Лише цифри (1–99999)', 'wc-nova-poshta-cod' ),
            ],
        ] );
    }
}
