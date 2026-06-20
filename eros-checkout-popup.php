<?php
/**
 * Plugin Name: Eros Popup Suite
 * Description: Age gate on the home page + dismissible notice on the WooCommerce checkout page. Configure under Settings → Eros Popups.
 * Version: 3.0.0
 * Author: Eros
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// ── Admin menu ─────────────────────────────────────────────────────────────

add_action( 'admin_menu', 'eros_popup_add_menu' );
function eros_popup_add_menu() {
    add_options_page(
        'Eros Popup Settings',
        'Eros Popups',
        'manage_options',
        'eros-checkout-popup',
        'eros_popup_settings_page'
    );
}

// ── Register settings ──────────────────────────────────────────────────────

add_action( 'admin_init', 'eros_popup_register_settings' );
function eros_popup_register_settings() {

    // --- Checkout popup ---
    register_setting( 'eros_popup_group', 'eros_popup_enabled',    [ 'type' => 'boolean', 'default' => true,          'sanitize_callback' => 'rest_sanitize_boolean' ] );
    register_setting( 'eros_popup_group', 'eros_popup_title',      [ 'type' => 'string',  'default' => 'Important Notice', 'sanitize_callback' => 'sanitize_text_field' ] );
    register_setting( 'eros_popup_group', 'eros_popup_message',    [ 'type' => 'string',  'default' => '',            'sanitize_callback' => 'wp_kses_post' ] );
    register_setting( 'eros_popup_group', 'eros_popup_bg_color',   [ 'type' => 'string',  'default' => '#ffffff',     'sanitize_callback' => 'sanitize_hex_color' ] );
    register_setting( 'eros_popup_group', 'eros_popup_text_color', [ 'type' => 'string',  'default' => '#333333',     'sanitize_callback' => 'sanitize_hex_color' ] );

    // --- Age gate ---
    register_setting( 'eros_popup_group', 'eros_age_enabled',      [ 'type' => 'boolean', 'default' => true,          'sanitize_callback' => 'rest_sanitize_boolean' ] );
    register_setting( 'eros_popup_group', 'eros_age_title',        [ 'type' => 'string',  'default' => 'Age Verification', 'sanitize_callback' => 'sanitize_text_field' ] );
    register_setting( 'eros_popup_group', 'eros_age_message',      [ 'type' => 'string',  'default' => 'You must be 18 years of age or older to enter this site.', 'sanitize_callback' => 'wp_kses_post' ] );
    register_setting( 'eros_popup_group', 'eros_age_redirect',     [ 'type' => 'string',  'default' => 'https://www.google.com', 'sanitize_callback' => 'esc_url_raw' ] );
    register_setting( 'eros_popup_group', 'eros_age_bg_color',     [ 'type' => 'string',  'default' => '#1a1a1a',     'sanitize_callback' => 'sanitize_hex_color' ] );
    register_setting( 'eros_popup_group', 'eros_age_text_color',   [ 'type' => 'string',  'default' => '#ffffff',     'sanitize_callback' => 'sanitize_hex_color' ] );
    register_setting( 'eros_popup_group', 'eros_age_yes_color',    [ 'type' => 'string',  'default' => '#28a745',     'sanitize_callback' => 'sanitize_hex_color' ] );
    register_setting( 'eros_popup_group', 'eros_age_no_color',     [ 'type' => 'string',  'default' => '#dc3545',     'sanitize_callback' => 'sanitize_hex_color' ] );
}

// ── Settings page UI ───────────────────────────────────────────────────────

function eros_popup_settings_page() {
    if ( ! current_user_can( 'manage_options' ) ) return;
    $saved = isset( $_GET['settings-updated'] );
    ?>
    <div class="wrap">
        <h1>Eros Popup Settings</h1>
        <?php if ( $saved ) : ?>
            <div class="notice notice-success is-dismissible"><p>Settings saved.</p></div>
        <?php endif; ?>
        <form method="post" action="options.php">
            <?php settings_fields( 'eros_popup_group' ); ?>

            <h2>Age Gate <span style="font-size:.75em;font-weight:normal;">(Home page)</span></h2>
            <table class="form-table" role="presentation">
                <tr>
                    <th><label for="eros_age_enabled">Enable Age Gate</label></th>
                    <td>
                        <input type="checkbox" id="eros_age_enabled" name="eros_age_enabled" value="1"
                            <?php checked( 1, get_option( 'eros_age_enabled', 1 ) ); ?> />
                        <p class="description">Shows on the home page until the visitor confirms their age (cookie lasts 30 days).</p>
                    </td>
                </tr>
                <tr>
                    <th><label for="eros_age_title">Title</label></th>
                    <td><input type="text" id="eros_age_title" name="eros_age_title" class="regular-text"
                        value="<?php echo esc_attr( get_option( 'eros_age_title', 'Age Verification' ) ); ?>" /></td>
                </tr>
                <tr>
                    <th><label for="eros_age_message">Message</label></th>
                    <td>
                        <?php wp_editor( get_option( 'eros_age_message', '' ), 'eros_age_message', [
                            'textarea_name' => 'eros_age_message',
                            'media_buttons' => false,
                            'textarea_rows' => 4,
                            'teeny'         => true,
                        ] ); ?>
                    </td>
                </tr>
                <tr>
                    <th><label for="eros_age_redirect">Redirect URL (No button)</label></th>
                    <td>
                        <input type="url" id="eros_age_redirect" name="eros_age_redirect" class="regular-text"
                            value="<?php echo esc_attr( get_option( 'eros_age_redirect', 'https://www.google.com' ) ); ?>" />
                        <p class="description">Where to send visitors who click "No".</p>
                    </td>
                </tr>
                <tr>
                    <th>Colors</th>
                    <td style="display:flex;gap:24px;align-items:center;flex-wrap:wrap;">
                        <label>Background
                            <input type="color" name="eros_age_bg_color"
                                value="<?php echo esc_attr( get_option( 'eros_age_bg_color', '#1a1a1a' ) ); ?>" />
                        </label>
                        <label>Text
                            <input type="color" name="eros_age_text_color"
                                value="<?php echo esc_attr( get_option( 'eros_age_text_color', '#ffffff' ) ); ?>" />
                        </label>
                        <label>Yes button
                            <input type="color" name="eros_age_yes_color"
                                value="<?php echo esc_attr( get_option( 'eros_age_yes_color', '#28a745' ) ); ?>" />
                        </label>
                        <label>No button
                            <input type="color" name="eros_age_no_color"
                                value="<?php echo esc_attr( get_option( 'eros_age_no_color', '#dc3545' ) ); ?>" />
                        </label>
                    </td>
                </tr>
            </table>

            <hr>

            <h2>Checkout Notice <span style="font-size:.75em;font-weight:normal;">(WooCommerce checkout page)</span></h2>
            <table class="form-table" role="presentation">
                <tr>
                    <th><label for="eros_popup_enabled">Enable Checkout Popup</label></th>
                    <td>
                        <input type="checkbox" id="eros_popup_enabled" name="eros_popup_enabled" value="1"
                            <?php checked( 1, get_option( 'eros_popup_enabled', 1 ) ); ?> />
                    </td>
                </tr>
                <tr>
                    <th><label for="eros_popup_title">Title</label></th>
                    <td><input type="text" id="eros_popup_title" name="eros_popup_title" class="regular-text"
                        value="<?php echo esc_attr( get_option( 'eros_popup_title', 'Important Notice' ) ); ?>" /></td>
                </tr>
                <tr>
                    <th><label for="eros_popup_message">Message</label></th>
                    <td>
                        <?php wp_editor( get_option( 'eros_popup_message', '' ), 'eros_popup_message', [
                            'textarea_name' => 'eros_popup_message',
                            'media_buttons' => false,
                            'textarea_rows' => 6,
                            'teeny'         => true,
                        ] ); ?>
                        <p class="description">Basic HTML allowed (bold, italic, links, etc.).</p>
                    </td>
                </tr>
                <tr>
                    <th>Colors</th>
                    <td style="display:flex;gap:24px;align-items:center;flex-wrap:wrap;">
                        <label>Background
                            <input type="color" name="eros_popup_bg_color"
                                value="<?php echo esc_attr( get_option( 'eros_popup_bg_color', '#ffffff' ) ); ?>" />
                        </label>
                        <label>Text
                            <input type="color" name="eros_popup_text_color"
                                value="<?php echo esc_attr( get_option( 'eros_popup_text_color', '#333333' ) ); ?>" />
                        </label>
                    </td>
                </tr>
            </table>

            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}

// ── Age gate (home page) ───────────────────────────────────────────────────

add_action( 'wp_footer', 'eros_age_gate' );
function eros_age_gate() {
    if ( ! is_front_page() && ! is_home() ) return;
    if ( ! get_option( 'eros_age_enabled', 1 ) ) return;

    $title    = esc_html( get_option( 'eros_age_title', 'Age Verification' ) );
    $message  = wp_kses_post( get_option( 'eros_age_message', '' ) );
    $redirect = esc_url( get_option( 'eros_age_redirect', 'https://www.google.com' ) );
    $bg       = sanitize_hex_color( get_option( 'eros_age_bg_color',   '#1a1a1a' ) );
    $color    = sanitize_hex_color( get_option( 'eros_age_text_color', '#ffffff' ) );
    $yes_bg   = sanitize_hex_color( get_option( 'eros_age_yes_color',  '#28a745' ) );
    $no_bg    = sanitize_hex_color( get_option( 'eros_age_no_color',   '#dc3545' ) );
    ?>
    <div id="eros-age-overlay" style="
        display:flex;position:fixed;inset:0;
        background:rgba(0,0,0,0.85);
        z-index:99999;align-items:center;justify-content:center;
    ">
        <div style="
            background:<?php echo $bg; ?>;
            color:<?php echo $color; ?>;
            border-radius:10px;padding:44px 36px 36px;
            max-width:460px;width:90%;
            box-shadow:0 8px 40px rgba(0,0,0,0.5);
            text-align:center;font-family:inherit;
        ">
            <?php if ( $title ) : ?>
                <h2 style="margin:0 0 16px;font-size:1.6em;"><?php echo $title; ?></h2>
            <?php endif; ?>

            <?php if ( $message ) : ?>
                <div style="margin:0 0 28px;font-size:1em;line-height:1.7;opacity:.9;">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <div style="display:flex;gap:16px;justify-content:center;">
                <button
                    onclick="eros_age_confirm()"
                    style="padding:12px 36px;font-size:1em;font-weight:bold;cursor:pointer;
                           background:<?php echo $yes_bg; ?>;color:#fff;border:none;border-radius:6px;"
                >Yes, I am 18+</button>
                <button
                    onclick="window.location.href='<?php echo $redirect; ?>'"
                    style="padding:12px 36px;font-size:1em;font-weight:bold;cursor:pointer;
                           background:<?php echo $no_bg; ?>;color:#fff;border:none;border-radius:6px;"
                >No, Exit</button>
            </div>
        </div>
    </div>
    <script>
    (function(){
        function getCookie(n){var m=document.cookie.match('(^|;)\\s*'+n+'\\s*=\\s*([^;]+)');return m?m.pop():'';}
        if(getCookie('eros_age_verified')==='1'){
            var el=document.getElementById('eros-age-overlay');
            if(el)el.style.display='none';
        }
    })();
    function eros_age_confirm(){
        var d=new Date();d.setDate(d.getDate()+30);
        document.cookie='eros_age_verified=1;expires='+d.toUTCString()+';path=/';
        document.getElementById('eros-age-overlay').style.display='none';
    }
    </script>
    <?php
}

// ── Checkout popup ─────────────────────────────────────────────────────────

add_action( 'wp_footer', 'eros_checkout_popup' );
function eros_checkout_popup() {
    if ( ! function_exists( 'is_checkout' ) || ! is_checkout() ) return;
    if ( ! get_option( 'eros_popup_enabled', 1 ) ) return;

    $title   = esc_html( get_option( 'eros_popup_title', 'Important Notice' ) );
    $message = wp_kses_post( get_option( 'eros_popup_message', '' ) );
    $bg      = sanitize_hex_color( get_option( 'eros_popup_bg_color',   '#ffffff' ) );
    $color   = sanitize_hex_color( get_option( 'eros_popup_text_color', '#333333' ) );

    if ( empty( $message ) ) return;
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

// ── Clean up on uninstall ──────────────────────────────────────────────────

register_uninstall_hook( __FILE__, 'eros_popup_uninstall' );
function eros_popup_uninstall() {
    $keys = [
        'eros_popup_enabled','eros_popup_title','eros_popup_message','eros_popup_bg_color','eros_popup_text_color',
        'eros_age_enabled','eros_age_title','eros_age_message','eros_age_redirect',
        'eros_age_bg_color','eros_age_text_color','eros_age_yes_color','eros_age_no_color',
    ];
    foreach ( $keys as $k ) delete_option( $k );
}
