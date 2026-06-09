<?php
defined( 'ABSPATH' ) || exit;

/**
 * Клас методу оплати «Накладений платіж (Нова Пошта)».
 *
 * Успадковує WC_Payment_Gateway — стандартний базовий клас WooCommerce.
 * Ядро WordPress / WooCommerce не змінюється, лише розширюється.
 */
class WCNPC_Payment_Gateway extends WC_Payment_Gateway {

    public function __construct() {
        $this->id                 = 'wcnpc_cod';
        $this->icon               = WCNPC_URL . 'assets/img/np-icon.svg';
        $this->has_fields         = true; // Власні поля на чекауті
        $this->method_title       = __( 'Накладений платіж (Нова Пошта)', 'wc-nova-poshta-cod' );
        $this->method_description = __( 'Оплата при отриманні на відділенні або кур\'єром Нової Пошти.', 'wc-nova-poshta-cod' );

        // Завантаження налаштувань із сторінки адміна
        $this->init_form_fields();
        $this->init_settings();

        // Прив'язка властивостей із збережених налаштувань
        $this->title            = $this->get_option( 'title' );
        $this->description      = $this->get_option( 'description' );
        $this->enabled          = $this->get_option( 'enabled' );
        $this->max_order_amount = (float) $this->get_option( 'max_order_amount', 50000 );
        $this->instructions     = $this->get_option( 'instructions' );

        // Збереження налаштувань із адмін-форми
        add_action(
            'woocommerce_update_options_payment_gateways_' . $this->id,
            [ $this, 'process_admin_options' ]
        );

        // Вивід інструкцій у листах і на сторінці подяки
        add_action( 'woocommerce_email_before_order_table', [ $this, 'email_instructions' ], 10, 3 );
        add_action( 'woocommerce_thankyou_' . $this->id,    [ $this, 'thankyou_page' ] );
    }

    // -------------------------------------------------------------------------
    // Поля сторінки налаштувань (WooCommerce > Payments > Накладений платіж)
    // -------------------------------------------------------------------------

    public function init_form_fields(): void {
        $this->form_fields = [

            'enabled' => [
                'title'   => __( 'Увімкнено', 'wc-nova-poshta-cod' ),
                'type'    => 'checkbox',
                'label'   => __( 'Активувати метод оплати', 'wc-nova-poshta-cod' ),
                'default' => 'yes',
            ],

            'title' => [
                'title'       => __( 'Назва', 'wc-nova-poshta-cod' ),
                'type'        => 'text',
                'description' => __( 'Відображається покупцю під час оформлення замовлення.', 'wc-nova-poshta-cod' ),
                'default'     => __( 'Накладений платіж (Нова Пошта)', 'wc-nova-poshta-cod' ),
                'desc_tip'    => true,
            ],

            'description' => [
                'title'       => __( 'Опис', 'wc-nova-poshta-cod' ),
                'type'        => 'textarea',
                'description' => __( 'Короткий опис методу на сторінці чекауту.', 'wc-nova-poshta-cod' ),
                'default'     => __( 'Оплачуйте замовлення при отриманні на відділенні Нової Пошти.', 'wc-nova-poshta-cod' ),
            ],

            'instructions' => [
                'title'       => __( 'Інструкції', 'wc-nova-poshta-cod' ),
                'type'        => 'textarea',
                'description' => __( 'Відображаються на сторінці подяки та у листі-підтвердженні.', 'wc-nova-poshta-cod' ),
                'default'     => __( 'Ваше замовлення буде відправлено Новою Поштою. Оплата при отриманні.', 'wc-nova-poshta-cod' ),
            ],

            'max_order_amount' => [
                'title'             => __( 'Максимальна сума замовлення (грн)', 'wc-nova-poshta-cod' ),
                'type'              => 'number',
                'description'       => __( 'Метод оплати буде недоступний при перевищенні цієї суми. 0 — без обмежень.', 'wc-nova-poshta-cod' ),
                'default'           => '50000',
                'desc_tip'          => true,
                'custom_attributes' => [
                    'min'  => '0',
                    'step' => '100',
                ],
            ],

            'commission_section' => [
                'title' => __( 'Комісія', 'wc-nova-poshta-cod' ),
                'type'  => 'title',
            ],

            'commission_type' => [
                'title'   => __( 'Тип комісії', 'wc-nova-poshta-cod' ),
                'type'    => 'select',
                'options' => [
                    'none'    => __( 'Без комісії', 'wc-nova-poshta-cod' ),
                    'fixed'   => __( 'Фіксована (грн)', 'wc-nova-poshta-cod' ),
                    'percent' => __( 'Відсоток від суми (%)', 'wc-nova-poshta-cod' ),
                ],
                'default' => 'none',
            ],

            'commission_value' => [
                'title'             => __( 'Розмір комісії', 'wc-nova-poshta-cod' ),
                'type'              => 'number',
                'default'           => '0',
                'custom_attributes' => [
                    'min'  => '0',
                    'step' => '0.01',
                ],
            ],
        ];
    }

    // -------------------------------------------------------------------------
    // Валідація налаштувань перед збереженням
    // -------------------------------------------------------------------------

    /**
     * @param string $key
     * @param mixed  $value
     */
    public function validate_max_order_amount_field( string $key, $value ): string {
        $value = (float) $value;
        if ( $value < 0 ) {
            WC_Admin_Settings::add_error(
                __( 'Максимальна сума замовлення не може бути від\'ємною.', 'wc-nova-poshta-cod' )
            );
            return '0';
        }
        return (string) $value;
    }

    /**
     * @param string $key
     * @param mixed  $value
     */
    public function validate_commission_value_field( string $key, $value ): string {
        $commission_type = isset( $_POST['woocommerce_wcnpc_cod_commission_type'] )
            ? sanitize_text_field( wp_unslash( $_POST['woocommerce_wcnpc_cod_commission_type'] ) )
            : 'none';

        $value = (float) $value;

        if ( 'percent' === $commission_type && $value > 100 ) {
            WC_Admin_Settings::add_error(
                __( 'Відсоток комісії не може перевищувати 100%.', 'wc-nova-poshta-cod' )
            );
            return '0';
        }

        if ( $value < 0 ) {
            WC_Admin_Settings::add_error(
                __( 'Комісія не може бути від\'ємною.', 'wc-nova-poshta-cod' )
            );
            return '0';
        }

        return (string) $value;
    }

    // -------------------------------------------------------------------------
    // Перевірка доступності методу оплати
    // -------------------------------------------------------------------------

    public function is_available(): bool {
        if ( ! parent::is_available() ) {
            return false;
        }

        // Обмеження за максимальною сумою
        if ( $this->max_order_amount > 0 && WC()->cart ) {
            $cart_total = (float) WC()->cart->get_total( 'edit' );
            if ( $cart_total > $this->max_order_amount ) {
                return false;
            }
        }

        return true;
    }

    // -------------------------------------------------------------------------
    // Власні поля на сторінці чекауту
    // -------------------------------------------------------------------------

    public function payment_fields(): void {
        if ( $this->description ) {
            echo '<p>' . wp_kses_post( $this->description ) . '</p>';
        }

        echo '<div class="wcnpc-fields">';

        woocommerce_form_field( 'wcnpc_np_city', [
            'type'        => 'text',
            'label'       => __( 'Місто', 'wc-nova-poshta-cod' ),
            'placeholder' => __( 'Наприклад: Київ', 'wc-nova-poshta-cod' ),
            'required'    => true,
            'class'       => [ 'wcnpc-field' ],
        ], '' );

        woocommerce_form_field( 'wcnpc_np_branch', [
            'type'        => 'text',
            'label'       => __( 'Номер відділення', 'wc-nova-poshta-cod' ),
            'placeholder' => __( 'Наприклад: 12', 'wc-nova-poshta-cod' ),
            'required'    => true,
            'class'       => [ 'wcnpc-field' ],
        ], '' );

        echo '</div>';
    }

    // -------------------------------------------------------------------------
    // Валідація полів чекауту (серверна)
    // -------------------------------------------------------------------------

    public function validate_fields(): bool {
        $valid = true;

        $city = isset( $_POST['wcnpc_np_city'] )
            ? sanitize_text_field( wp_unslash( $_POST['wcnpc_np_city'] ) )
            : '';

        $branch = isset( $_POST['wcnpc_np_branch'] )
            ? sanitize_text_field( wp_unslash( $_POST['wcnpc_np_branch'] ) )
            : '';

        if ( empty( $city ) ) {
            wc_add_notice(
                __( 'Будь ласка, вкажіть місто для доставки Новою Поштою.', 'wc-nova-poshta-cod' ),
                'error'
            );
            $valid = false;
        } elseif ( mb_strlen( $city ) < 2 || mb_strlen( $city ) > 100 ) {
            wc_add_notice(
                __( 'Назва міста має містити від 2 до 100 символів.', 'wc-nova-poshta-cod' ),
                'error'
            );
            $valid = false;
        }

        if ( empty( $branch ) ) {
            wc_add_notice(
                __( 'Будь ласка, вкажіть номер відділення Нової Пошти.', 'wc-nova-poshta-cod' ),
                'error'
            );
            $valid = false;
        } elseif ( ! preg_match( '/^\d{1,5}$/', $branch ) ) {
            wc_add_notice(
                __( 'Номер відділення має бути числом від 1 до 99999.', 'wc-nova-poshta-cod' ),
                'error'
            );
            $valid = false;
        }

        return $valid;
    }

    // -------------------------------------------------------------------------
    // Обробка замовлення — збереження даних
    // -------------------------------------------------------------------------

    /**
     * @param int $order_id
     */
    public function process_payment( $order_id ): array {
        $order = wc_get_order( $order_id );

        $city   = sanitize_text_field( wp_unslash( $_POST['wcnpc_np_city']   ?? '' ) );
        $branch = sanitize_text_field( wp_unslash( $_POST['wcnpc_np_branch'] ?? '' ) );

        // Зберігаємо через HPOS-сумісний API
        $order->update_meta_data( '_wcnpc_np_city',   $city );
        $order->update_meta_data( '_wcnpc_np_branch', $branch );
        $order->set_payment_method( $this->id );
        $order->set_payment_method_title( $this->title );
        $order->update_status(
            'on-hold',
            __( 'Очікуємо підтвердження замовлення (накладений платіж).', 'wc-nova-poshta-cod' )
        );
        $order->save();

        // Спустошення кошика
        WC()->cart->empty_cart();

        return [
            'result'   => 'success',
            'redirect' => $this->get_return_url( $order ),
        ];
    }

    // -------------------------------------------------------------------------
    // Сторінка подяки та email
    // -------------------------------------------------------------------------

    public function thankyou_page(): void {
        if ( $this->instructions ) {
            echo '<p>' . wp_kses_post( $this->instructions ) . '</p>';
        }
    }

    /**
     * @param WC_Order $order
     * @param bool     $sent_to_admin
     * @param bool     $plain_text
     */
    public function email_instructions( WC_Order $order, bool $sent_to_admin, bool $plain_text ): void {
        if (
            $this->instructions
            && ! $sent_to_admin
            && $this->id === $order->get_payment_method()
            && $order->has_status( 'on-hold' )
        ) {
            echo wp_kses_post( wpautop( wptexturize( $this->instructions ) ) ) . PHP_EOL;
        }
    }
}
