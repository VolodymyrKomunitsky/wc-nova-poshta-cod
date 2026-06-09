<?php
defined( 'ABSPATH' ) || exit;

/**
 * Відображення збережених даних Нової Пошти у адмін-панелі замовлення.
 */
class WCNPC_Order_Meta {

    public function __construct() {
        // Виводимо мета-дані у блоці деталей замовлення
        add_action( 'woocommerce_admin_order_data_after_billing_address', [ $this, 'display_order_meta' ], 10, 1 );

        // Показуємо дані в листах (після таблиці товарів)
        add_action( 'woocommerce_email_after_order_table', [ $this, 'email_order_meta' ], 10, 1 );
    }

    /**
     * @param WC_Order $order
     */
    public function display_order_meta( WC_Order $order ): void {
        if ( 'wcnpc_cod' !== $order->get_payment_method() ) {
            return;
        }

        $city   = $order->get_meta( '_wcnpc_np_city' );
        $branch = $order->get_meta( '_wcnpc_np_branch' );

        if ( ! $city && ! $branch ) {
            return;
        }

        echo '<div class="wcnpc-admin-meta" style="margin-top:12px;padding:10px 12px;background:#f8f9fa;border-left:3px solid #e63946;border-radius:2px;">';
        echo '<p style="margin:0 0 4px;font-weight:600;">'
            . esc_html__( '🟡 Нова Пошта — Накладений платіж', 'wc-nova-poshta-cod' )
            . '</p>';

        if ( $city ) {
            echo '<p style="margin:2px 0;"><strong>'
                . esc_html__( 'Місто:', 'wc-nova-poshta-cod' )
                . '</strong> ' . esc_html( $city ) . '</p>';
        }

        if ( $branch ) {
            echo '<p style="margin:2px 0;"><strong>'
                . esc_html__( 'Відділення №:', 'wc-nova-poshta-cod' )
                . '</strong> ' . esc_html( $branch ) . '</p>';
        }

        echo '</div>';
    }

    /**
     * @param WC_Order $order
     */
    public function email_order_meta( WC_Order $order ): void {
        if ( 'wcnpc_cod' !== $order->get_payment_method() ) {
            return;
        }

        $city   = $order->get_meta( '_wcnpc_np_city' );
        $branch = $order->get_meta( '_wcnpc_np_branch' );

        if ( ! $city && ! $branch ) {
            return;
        }

        echo '<h2>' . esc_html__( 'Дані доставки Нова Пошта', 'wc-nova-poshta-cod' ) . '</h2>';
        echo '<table cellspacing="0" cellpadding="6" style="width:100%;border-collapse:collapse;">';

        if ( $city ) {
            echo '<tr><th style="text-align:left;border-bottom:1px solid #eee;padding:6px;">'
                . esc_html__( 'Місто', 'wc-nova-poshta-cod' )
                . '</th><td style="border-bottom:1px solid #eee;padding:6px;">'
                . esc_html( $city ) . '</td></tr>';
        }

        if ( $branch ) {
            echo '<tr><th style="text-align:left;border-bottom:1px solid #eee;padding:6px;">'
                . esc_html__( 'Відділення №', 'wc-nova-poshta-cod' )
                . '</th><td style="border-bottom:1px solid #eee;padding:6px;">'
                . esc_html( $branch ) . '</td></tr>';
        }

        echo '</table>';
    }
}
