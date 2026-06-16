<?php
/**
 * Plugin Name: Eros Checkout Popup
 * Description: Displays a dismissible popup message on the WooCommerce checkout page. Configure it under Settings → Checkout Popup.
 * Version: 2.0.0
 * Author: Eros
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// ── Admin settings page ────────────────────────────────────────────────────

add_action( 'admin_menu', 'eros_popup_add_menu' );
function eros_popup_add_menu() {
    add_options_page(
        'Checkout Popup Settings',
        'Checkout Popup',
        'manage_options',
        'eros-checkout-popup',
        'eros_popup_settings_page'
    );
}

add_action( 'admin_init', 'eros_popup_register_settings' );
function eros_popup_register_settings() {
    register_setting( 'eros_popup_group', 'eros_popup_enabled', [
        'type'              => 'boolean',
        'default'           => true,
        'sanitize_callback' => 'rest_sanitize_boolean',
    ] );
    register_setting( 'eros_popup_group', 'eros_popup_title', [
        'type'              => 'string',
        'default'           => 'Important Notice',
        'sanitize_callback' => 'sanitize_text_field',
    ] );
    register_setting( 'eros_popup_group', 'eros_popup_message', [
        'type'              => 'string',
        'default'           => 'Thank you for your order! Please note that all sales are final. If you have any questions, contact us before completing your purchase.',
        'sanitize_callback' => 'wp_kses_post',
    ] );
    register_setting( 'eros_popup_group', 'eros_popup_bg_color', [
        'type'              => 'string',
        'default'           => '#ffffff',
        'sanitize_callback' => 'sanitize_hex_color',
    ] );
    register_setting( 'eros_popup_group', 'eros_popup_text_color', [
        'type'              => 'string',
        'default'           => '#333333',
        'sanitize_callback' => 'sanitize_hex_color',
    ] );
}

function eros_popup_settings_page() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }
    $saved = isset( $_GET['settings-updated'] );
    ?>
    <div class="wrap">
        <h1>Checkout Popup Settings</h1>
        <?php if ( $saved ) : ?>
            <div class="notice notice-success is-dismissible"><p>Settings saved.</p></div>
        <?php endif; ?>
        <form method="post" action="options.php">
            <?php settings_fields( 'eros_popup_group' ); ?>
            <table class="form-table" role="presentation">

                <tr>
                    <th scope="row"><label for="eros_popup_enabled">Enable Popup</label></th>
                    <td>
                        <input type="checkbox" id="eros_popup_enabled" name="eros_popup_enabled" value="1"
                            <?php checked( 1, get_option( 'eros_popup_enabled', 1 ) ); ?> />
                        <p class="description">Uncheck to hide the popup without deleting your settings.</p>
                    </td>
                </tr>

                <tr>
                    <th scope="row"><label for="eros_popup_title">Popup Title</label></th>
                    <td>
                        <input type="text" id="eros_popup_title" name="eros_popup_title" class="regular-text"
                            value="<?php echo esc_attr( get_option( 'eros_popup_title', 'Important Notice' ) ); ?>" />
                    </td>
                </tr>

                <tr>
                    <th scope="row"><label for="eros_popup_message">Popup Message</label></th>
                    <td>
                        <?php
                        wp_editor(
                            get_option( 'eros_popup_message', '' ),
                            'eros_popup_message',
                            [
                                'textarea_name' => 'eros_popup_message',
                                'media_buttons' => false,
                                'textarea_rows' => 6,
                                'teeny'         => true,
                            ]
                        );
                        ?>
                        <p class="description">Basic HTML is allowed (bold, italic, links, etc.).</p>
                    </td>
                </tr>

                <tr>
                    <th scope="row"><label for="eros_popup_bg_color">Popup Background Color</label></th>
                    <td>
                        <input type="color" id="eros_popup_bg_color" name="eros_popup_bg_color"
                            value="<?php echo esc_attr( get_option( 'eros_popup_bg_color', '#ffffff' ) ); ?>" />
                    </td>
                </tr>

                <tr>
                    <th scope="row"><label for="eros_popup_text_color">Popup Text Color</label></th>
                    <td>
                        <input type="color" id="eros_popup_text_color" name="eros_popup_text_color"
                            value="<?php echo esc_attr( get_option( 'eros_popup_text_color', '#333333' ) ); ?>" />
                    </td>
                </tr>

            </table>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}

// ── Frontend popup ─────────────────────────────────────────────────────────

add_action( 'wp_footer', 'eros_checkout_popup' );
function eros_checkout_popup() {
    if ( ! function_exists( 'is_checkout' ) || ! is_checkout() ) {
        return;
    }
    if ( ! get_option( 'eros_popup_enabled', 1 ) ) {
        return;
    }

    $title    = esc_html( get_option( 'eros_popup_title', 'Important Notice' ) );
    $message  = wp_kses_post( get_option( 'eros_popup_message', '' ) );
    $bg       = sanitize_hex_color( get_option( 'eros_popup_bg_color', '#ffffff' ) );
    $color    = sanitize_hex_color( get_option( 'eros_popup_text_color', '#333333' ) );

    if ( empty( $message ) ) {
        return;
    }
    ?>
    <div id="eros-popup-overlay" style="
        display:flex;position:fixed;inset:0;
        background:rgba(0,0,0,0.55);
        z-index:99999;align-items:center;justify-content:center;
    ">
        <div style="
            background:<?php echo $bg; ?>;
            color:<?php echo $color; ?>;
            border-radius:8px;padding:36px 32px 28px;
            max-width:480px;width:90%;position:relative;
            box-shadow:0 8px 32px rgba(0,0,0,0.18);
            text-align:center;font-family:inherit;
        ">
            <button
                onclick="document.getElementById('eros-popup-overlay').style.display='none';"
                aria-label="Close"
                style="position:absolute;top:12px;right:14px;background:none;border:none;
                       font-size:22px;line-height:1;cursor:pointer;color:<?php echo $color; ?>;padding:0;"
            >&times;</button>
            <?php if ( $title ) : ?>
                <h2 style="margin:0 0 14px;font-size:1.4em;"><?php echo $title; ?></h2>
            <?php endif; ?>
            <div style="margin:0;font-size:1em;line-height:1.6;">
                <?php echo $message; ?>
            </div>
            <button
                onclick="document.getElementById('eros-popup-overlay').style.display='none';"
                style="margin-top:22px;padding:10px 32px;font-size:1em;cursor:pointer;
                       background:#333;color:#fff;border:none;border-radius:5px;font-weight:bold;"
            >OK</button>
        </div>
    </div>
    <?php
}

// ── Clean up options on uninstall ──────────────────────────────────────────

register_uninstall_hook( __FILE__, 'eros_popup_uninstall' );
function eros_popup_uninstall() {
    foreach ( [ 'eros_popup_enabled', 'eros_popup_title', 'eros_popup_message', 'eros_popup_bg_color', 'eros_popup_text_color' ] as $key ) {
        delete_option( $key );
    }
}
