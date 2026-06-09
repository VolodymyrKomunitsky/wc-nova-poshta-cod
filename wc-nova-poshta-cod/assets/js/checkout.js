/* global wcnpc, jQuery */
( function ( $ ) {
    'use strict';

    const gateway_id = 'wcnpc_cod';

    /**
     * Повертає true, якщо обрано наш метод оплати.
     */
    function isActive() {
        return $( 'input[name="payment_method"]:checked' ).val() === gateway_id;
    }

    /**
     * Відображає inline-помилку під полем.
     */
    function showError( $row, message ) {
        $row.addClass( 'woocommerce-invalid' ).removeClass( 'wcnpc-valid' );
        let $err = $row.find( '.wcnpc-inline-error' );
        if ( ! $err.length ) {
            $err = $( '<span class="wcnpc-inline-error"></span>' );
            $row.find( 'input' ).after( $err );
        }
        $err.text( message );
    }

    /**
     * Знімає inline-помилку.
     */
    function clearError( $row ) {
        $row.removeClass( 'woocommerce-invalid' ).addClass( 'wcnpc-valid' );
        $row.find( '.wcnpc-inline-error' ).remove();
    }

    /**
     * Клієнтська валідація — швидка, без мережевого запиту.
     */
    function validateLocally() {
        if ( ! isActive() ) return true;

        let valid = true;

        const $cityRow   = $( '#wcnpc_np_city_field' );
        const $branchRow = $( '#wcnpc_np_branch_field' );
        const city       = $( '#wcnpc_np_city' ).val().trim();
        const branch     = $( '#wcnpc_np_branch' ).val().trim();

        if ( city.length < 2 ) {
            showError( $cityRow, wcnpc.i18n.city_required );
            valid = false;
        } else {
            clearError( $cityRow );
        }

        if ( branch.length === 0 ) {
            showError( $branchRow, wcnpc.i18n.branch_required );
            valid = false;
        } else if ( ! /^\d{1,5}$/.test( branch ) ) {
            showError( $branchRow, wcnpc.i18n.branch_invalid );
            valid = false;
        } else {
            clearError( $branchRow );
        }

        return valid;
    }

    // -------------------------------------------------------------------------
    // Обробники подій
    // -------------------------------------------------------------------------

    // Валідація у реальному часі при введенні
    $( document.body ).on( 'input', '#wcnpc_np_city, #wcnpc_np_branch', function () {
        if ( ! isActive() ) return;
        validateLocally();
    } );

    // Перехоплення сабміту форми замовлення
    $( document.body ).on( 'checkout_place_order', function () {
        return validateLocally();
    } );

    // Очищення помилок при зміні методу оплати
    $( document.body ).on( 'payment_method_selected', function () {
        if ( ! isActive() ) {
            $( '#wcnpc_np_city_field, #wcnpc_np_branch_field' )
                .removeClass( 'woocommerce-invalid wcnpc-valid' );
            $( '.wcnpc-inline-error' ).remove();
        }
    } );

} )( jQuery );
