<?php
/**
 * Plugin Name: Eros Checkout Popup
 * Description: Displays a dismissible popup message on the WooCommerce checkout page.
 * Version: 1.0.0
 * Author: Eros
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action( 'wp_footer', 'eros_checkout_popup' );

function eros_checkout_popup() {
    if ( ! function_exists( 'is_checkout' ) || ! is_checkout() ) {
        return;
    }
    ?>
    <div id="eros-popup-overlay" style="
        display: flex;
        position: fixed;
        inset: 0;
        background: rgba(0,0,0,0.55);
        z-index: 99999;
        align-items: center;
        justify-content: center;
    ">
        <div style="
            background: #fff;
            border-radius: 8px;
            padding: 36px 32px 28px;
            max-width: 480px;
            width: 90%;
            position: relative;
            box-shadow: 0 8px 32px rgba(0,0,0,0.18);
            text-align: center;
            font-family: inherit;
        ">
            <button
                onclick="document.getElementById('eros-popup-overlay').style.display='none';"
                aria-label="Close"
                style="
                    position: absolute;
                    top: 12px;
                    right: 14px;
                    background: none;
                    border: none;
                    font-size: 22px;
                    line-height: 1;
                    cursor: pointer;
                    color: #555;
                    padding: 0;
                "
            >&times;</button>

            <h2 style="margin: 0 0 14px; font-size: 1.4em;">Important Notice</h2>
            <p style="margin: 0; font-size: 1em; line-height: 1.6; color: #333;">
                <!-- ✏️  Edit your message here -->
                Thank you for your order! Please note that all sales are final.
                If you have any questions, contact us before completing your purchase.
            </p>
        </div>
    </div>
    <?php
}
