<?php
/**
 * Plugin Name:       WC Nova Poshta COD
 * Plugin URI:        https://github.com/your-username/wc-nova-poshta-cod
 * Description:       Метод оплати «Накладений платіж (Нова Пошта)» для WooCommerce з валідацією телефону та збереженням даних відділення у замовленні.
 * Version:           1.0.0
 * Requires at least: 6.0
 * Requires PHP:      8.0
 * Author:            Vok
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       wc-nova-poshta-cod
 * Domain Path:       /languages
 */

defined( 'ABSPATH' ) || exit;

define( 'WCNPC_VERSION', '1.0.0' );
define( 'WCNPC_FILE',    __FILE__ );
define( 'WCNPC_DIR',     plugin_dir_path( __FILE__ ) );
define( 'WCNPC_URL',     plugin_dir_url( __FILE__ ) );

/**
 * Перевірка активності WooCommerce перед завантаженням плагіну.
 */
function wcnpc_check_woocommerce(): void {
    if ( ! class_exists( 'WooCommerce' ) ) {
        add_action( 'admin_notices', 'wcnpc_missing_wc_notice' );
        return;
    }
    wcnpc_init();
}
add_action( 'plugins_loaded', 'wcnpc_check_woocommerce' );

function wcnpc_missing_wc_notice(): void {
    echo '<div class="notice notice-error"><p>'
        . esc_html__( 'WC Nova Poshta COD потребує активного WooCommerce.', 'wc-nova-poshta-cod' )
        . '</p></div>';
}

/**
 * Основна ініціалізація після того, як WooCommerce підтверджено.
 */
function wcnpc_init(): void {
    require_once WCNPC_DIR . 'includes/class-wcnpc-payment-gateway.php';
    require_once WCNPC_DIR . 'includes/class-wcnpc-order-meta.php';
    require_once WCNPC_DIR . 'includes/class-wcnpc-checkout-fields.php';
    require_once WCNPC_DIR . 'includes/class-wcnpc-assets.php';

    // Реєстрація методу оплати
    add_filter( 'woocommerce_payment_gateways', 'wcnpc_register_gateway' );

    // Ініціалізація решти класів
    new WCNPC_Order_Meta();
    new WCNPC_Checkout_Fields();
    new WCNPC_Assets();
}

function wcnpc_register_gateway( array $gateways ): array {
    $gateways[] = 'WCNPC_Payment_Gateway';
    return $gateways;
}

/**
 * Оголошення сумісності з HPOS (High-Performance Order Storage).
 */
add_action( 'before_woocommerce_init', function () {
    if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
            'custom_order_tables',
            WCNPC_FILE,
            true
        );
    }
} );
