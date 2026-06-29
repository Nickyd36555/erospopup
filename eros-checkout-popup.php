<?php
/**
 * Plugin Name: Eros Popup Suite
 * Description: Age gate on the home page + dismissible notice on the WooCommerce checkout page. Configure under Settings → Eros Popups.
 * Version: 4.0.0
 * Author: Eros
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// ── Admin menu ─────────────────────────────────────────────────────────────

add_action( 'admin_menu', 'eros_popup_add_menu' );
function eros_popup_add_menu() {
    add_options_page( 'Eros Popup Settings', 'Eros Popups', 'manage_options', 'eros-checkout-popup', 'eros_popup_settings_page' );
}

// ── Register settings ──────────────────────────────────────────────────────

add_action( 'admin_init', 'eros_popup_register_settings' );
function eros_popup_register_settings() {
    $s = 'sanitize_text_field';
    $b = 'rest_sanitize_boolean';
    $c = 'sanitize_hex_color';

    register_setting( 'eros_popup_group', 'eros_popup_enabled',    [ 'type' => 'boolean', 'default' => true,             'sanitize_callback' => $b ] );
    register_setting( 'eros_popup_group', 'eros_popup_title',      [ 'type' => 'string',  'default' => 'Important Notice','sanitize_callback' => $s ] );
    register_setting( 'eros_popup_group', 'eros_popup_message',    [ 'type' => 'string',  'default' => '',               'sanitize_callback' => 'wp_kses_post' ] );
    register_setting( 'eros_popup_group', 'eros_popup_bg_color',   [ 'type' => 'string',  'default' => '#ffffff',        'sanitize_callback' => $c ] );
    register_setting( 'eros_popup_group', 'eros_popup_text_color', [ 'type' => 'string',  'default' => '#333333',        'sanitize_callback' => $c ] );

    register_setting( 'eros_popup_group', 'eros_age_enabled',      [ 'type' => 'boolean', 'default' => true,             'sanitize_callback' => $b ] );
    register_setting( 'eros_popup_group', 'eros_age_title',        [ 'type' => 'string',  'default' => 'Age Verification','sanitize_callback' => $s ] );
    register_setting( 'eros_popup_group', 'eros_age_message',      [ 'type' => 'string',  'default' => 'You must be 18 years of age or older to enter this site.', 'sanitize_callback' => 'wp_kses_post' ] );
    register_setting( 'eros_popup_group', 'eros_age_redirect',     [ 'type' => 'string',  'default' => 'https://www.google.com', 'sanitize_callback' => 'esc_url_raw' ] );
    register_setting( 'eros_popup_group', 'eros_age_image',        [ 'type' => 'integer', 'default' => 0,                'sanitize_callback' => 'absint' ] );
    register_setting( 'eros_popup_group', 'eros_age_bg_color',     [ 'type' => 'string',  'default' => '#1a1a1a',        'sanitize_callback' => $c ] );
    register_setting( 'eros_popup_group', 'eros_age_text_color',   [ 'type' => 'string',  'default' => '#ffffff',        'sanitize_callback' => $c ] );
    register_setting( 'eros_popup_group', 'eros_age_yes_color',    [ 'type' => 'string',  'default' => '#28a745',        'sanitize_callback' => $c ] );
    register_setting( 'eros_popup_group', 'eros_age_no_color',     [ 'type' => 'string',  'default' => '#dc3545',        'sanitize_callback' => $c ] );
}

// ── Media uploader (admin settings page only) ──────────────────────────────

add_action( 'admin_enqueue_scripts', 'eros_popup_admin_scripts' );
function eros_popup_admin_scripts( $hook ) {
    if ( $hook !== 'settings_page_eros-checkout-popup' ) return;
    wp_enqueue_media();
    wp_enqueue_script( 'jquery' );
    add_action( 'admin_footer', 'eros_popup_admin_footer_script' );
}
function eros_popup_admin_footer_script() {
    ?>
    <script>
    jQuery(function($){
        $('#eros-age-upload-btn').on('click',function(e){
            e.preventDefault();
            var frame=wp.media({title:'Select Image',button:{text:'Use this image'},multiple:false});
            frame.on('select',function(){
                var a=frame.state().get('selection').first().toJSON();
                $('#eros_age_image').val(a.id);
                $('#eros-age-image-preview').attr('src',a.url).css('display','block');
                $('#eros-age-remove-btn').css('display','inline-block');
            });
            frame.open();
        });
        $('#eros-age-remove-btn').on('click',function(e){
            e.preventDefault();
            $('#eros_age_image').val('0');
            $('#eros-age-image-preview').css('display','none');
            $(this).css('display','none');
        });
    });
    </script>
    <?php
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

            <h2>Age Gate <span style="font-size:.75em;font-weight:normal;">(Home page)</span>
                &nbsp;<a href="<?php echo esc_url( home_url( '/?eros_reset_age=1' ) ); ?>" target="_blank" class="button button-small">Preview / Reset Cookie</a>
            </h2>
            <table class="form-table" role="presentation">
                <tr>
                    <th><label for="eros_age_enabled">Enable Age Gate</label></th>
                    <td>
                        <input type="checkbox" id="eros_age_enabled" name="eros_age_enabled" value="1" <?php checked( 1, get_option( 'eros_age_enabled', 1 ) ); ?> />
                        <p class="description">Shows on the home page until the visitor confirms their age (cookie lasts 30 days).</p>
                    </td>
                </tr>
                <tr>
                    <th><label for="eros_age_title">Title</label></th>
                    <td><input type="text" id="eros_age_title" name="eros_age_title" class="regular-text" value="<?php echo esc_attr( get_option( 'eros_age_title', 'Age Verification' ) ); ?>" /></td>
                </tr>
                <tr>
                    <th><label>Popup Image</label></th>
                    <td>
                        <?php
                        $img_id  = (int) get_option( 'eros_age_image', 0 );
                        $img_url = $img_id ? wp_get_attachment_image_url( $img_id, 'medium' ) : '';
                        ?>
                        <input type="hidden" id="eros_age_image" name="eros_age_image" value="<?php echo $img_id; ?>" />
                        <img id="eros-age-image-preview" src="<?php echo esc_url( $img_url ); ?>" style="max-width:160px;max-height:120px;display:<?php echo $img_url ? 'block' : 'none'; ?>;margin-bottom:8px;border-radius:4px;" />
                        <button type="button" id="eros-age-upload-btn" class="button">Choose Image</button>
                        <button type="button" id="eros-age-remove-btn" class="button" style="margin-left:6px;<?php echo $img_url ? '' : 'display:none;'; ?>">Remove</button>
                        <p class="description">Displays at the top of the age gate popup (logo, badge, etc.).</p>
                    </td>
                </tr>
                <tr>
                    <th><label for="eros_age_message">Message</label></th>
                    <td>
                        <?php wp_editor( get_option( 'eros_age_message', '' ), 'eros_age_message', [ 'textarea_name' => 'eros_age_message', 'media_buttons' => false, 'textarea_rows' => 4, 'teeny' => true ] ); ?>
                    </td>
                </tr>
                <tr>
                    <th><label for="eros_age_redirect">Redirect URL (No button)</label></th>
                    <td>
                        <input type="url" id="eros_age_redirect" name="eros_age_redirect" class="regular-text" value="<?php echo esc_attr( get_option( 'eros_age_redirect', 'https://www.google.com' ) ); ?>" />
                        <p class="description">Where to send visitors who click "No".</p>
                    </td>
                </tr>
                <tr>
                    <th>Colors</th>
                    <td style="display:flex;gap:24px;align-items:center;flex-wrap:wrap;">
                        <label>Background <input type="color" name="eros_age_bg_color"   value="<?php echo esc_attr( get_option( 'eros_age_bg_color',   '#1a1a1a' ) ); ?>" /></label>
                        <label>Text       <input type="color" name="eros_age_text_color" value="<?php echo esc_attr( get_option( 'eros_age_text_color', '#ffffff' ) ); ?>" /></label>
                        <label>Yes button <input type="color" name="eros_age_yes_color"  value="<?php echo esc_attr( get_option( 'eros_age_yes_color',  '#28a745' ) ); ?>" /></label>
                        <label>No button  <input type="color" name="eros_age_no_color"   value="<?php echo esc_attr( get_option( 'eros_age_no_color',   '#dc3545' ) ); ?>" /></label>
                    </td>
                </tr>
            </table>

            <hr>

            <h2>Checkout Notice <span style="font-size:.75em;font-weight:normal;">(WooCommerce checkout page)</span></h2>
            <table class="form-table" role="presentation">
                <tr>
                    <th><label for="eros_popup_enabled">Enable Checkout Popup</label></th>
                    <td><input type="checkbox" id="eros_popup_enabled" name="eros_popup_enabled" value="1" <?php checked( 1, get_option( 'eros_popup_enabled', 1 ) ); ?> /></td>
                </tr>
                <tr>
                    <th><label for="eros_popup_title">Title</label></th>
                    <td><input type="text" id="eros_popup_title" name="eros_popup_title" class="regular-text" value="<?php echo esc_attr( get_option( 'eros_popup_title', 'Important Notice' ) ); ?>" /></td>
                </tr>
                <tr>
                    <th><label for="eros_popup_message">Message</label></th>
                    <td>
                        <?php wp_editor( get_option( 'eros_popup_message', '' ), 'eros_popup_message', [ 'textarea_name' => 'eros_popup_message', 'media_buttons' => false, 'textarea_rows' => 6, 'teeny' => true ] ); ?>
                        <p class="description">Basic HTML allowed (bold, italic, links, etc.).</p>
                    </td>
                </tr>
                <tr>
                    <th>Colors</th>
                    <td style="display:flex;gap:24px;align-items:center;flex-wrap:wrap;">
                        <label>Background <input type="color" name="eros_popup_bg_color"   value="<?php echo esc_attr( get_option( 'eros_popup_bg_color',   '#ffffff' ) ); ?>" /></label>
                        <label>Text       <input type="color" name="eros_popup_text_color" value="<?php echo esc_attr( get_option( 'eros_popup_text_color', '#333333' ) ); ?>" /></label>
                    </td>
                </tr>
            </table>

            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}

// ── Reset cookie (for admin previewing) ───────────────────────────────────

add_action( 'template_redirect', 'eros_age_reset_cookie' );
function eros_age_reset_cookie() {
    if ( ! isset( $_GET['eros_reset_age'] ) || ! current_user_can( 'manage_options' ) ) return;
    setcookie( 'eros_age', '', time() - 3600, '/' );
    wp_safe_redirect( home_url( '/' ) );
    exit;
}

// ── Age gate (home page) ───────────────────────────────────────────────────

add_action( 'wp_footer', 'eros_age_gate', 20 );
function eros_age_gate() {
    if ( ! is_front_page() && ! is_home() ) return;
    if ( ! get_option( 'eros_age_enabled', 1 ) ) return;

    $title    = esc_html( get_option( 'eros_age_title', 'Age Verification' ) );
    $message  = wp_kses_post( get_option( 'eros_age_message', '' ) );
    $redirect = esc_url( get_option( 'eros_age_redirect', 'https://www.google.com' ) );
    $bg       = sanitize_hex_color( get_option( 'eros_age_bg_color',   '#1a1a1a' ) );
    $fg       = sanitize_hex_color( get_option( 'eros_age_text_color', '#ffffff' ) );
    $yes      = sanitize_hex_color( get_option( 'eros_age_yes_color',  '#28a745' ) );
    $no       = sanitize_hex_color( get_option( 'eros_age_no_color',   '#dc3545' ) );
    $img_id   = (int) get_option( 'eros_age_image', 0 );
    $img_url  = $img_id ? wp_get_attachment_image_url( $img_id, 'medium' ) : '';

    $btn = 'padding:11px 34px;font-size:1em;font-weight:bold;cursor:pointer;color:#fff;border:none;border-radius:6px;';
    $box_style = "background:{$bg};color:{$fg};border-radius:10px;padding:36px 32px 28px;max-width:480px;width:100%;max-height:90vh;overflow-y:auto;box-shadow:0 8px 40px rgba(0,0,0,.5);text-align:center;color-scheme:only light;forced-color-adjust:none;";
    $box_json = json_encode( $box_style );
    $img_html = $img_url ? '<img src="'.esc_url($img_url).'" alt="" style="max-width:120px;max-height:90px;margin:0 auto 18px;display:block;border-radius:4px">' : '';
    $ttl_html = $title   ? '<h2 style="margin:0 0 14px;font-size:1.5em;color:'.$fg.'">'.$title.'</h2>' : '';
    $msg_html = $message ? '<div style="margin:0 0 20px;line-height:1.7;color:'.$fg.'">'.$message.'</div>' : '';
    ?>
<script>
(function(){
    function getCookie(n){var m=document.cookie.match('(^|;)\\s*'+n+'\\s*=\\s*([^;]+)');return m?m.pop():'';}
    if(getCookie('eros_age')==='1') return;

    // Build overlay and append directly to body so no theme element can trap it
    var ov=document.createElement('div');
    ov.id='eag';
    ov.style.cssText='position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.85);z-index:2147483647;display:flex;align-items:center;justify-content:center;padding:16px;box-sizing:border-box';

    var box=document.createElement('div');
    box.style.cssText=<?php echo $box_json ?>;
    box.innerHTML=
        <?php echo json_encode( $img_html.$ttl_html.$msg_html ); ?>+
        '<label style="display:inline-flex;align-items:center;gap:8px;margin-bottom:20px;font-size:.9em;cursor:pointer;color:<?php echo $fg ?>">'
        +'<input type="checkbox" id="eag-remember" style="width:16px;height:16px;cursor:pointer;accent-color:<?php echo $yes ?>"> Remember me for 30 days</label>'
        +'<div style="display:flex;gap:14px;justify-content:center">'
        +'<button onclick="eagOk()" style="<?php echo addslashes($btn) ?>background:<?php echo $yes ?>">Yes, I am 18+</button>'
        +'<button onclick="location.href=\'<?php echo $redirect ?>\'" style="<?php echo addslashes($btn) ?>background:<?php echo $no ?>">No, Exit</button>'
        +'</div>';

    ov.appendChild(box);
    document.body.appendChild(ov);
})();

function eagOk(){
    if(document.getElementById('eag-remember').checked){
        var d=new Date();d.setDate(d.getDate()+30);
        document.cookie='eros_age=1;expires='+d.toUTCString()+';path=/';
    } else {
        document.cookie='eros_age=1;path=/';
    }
    document.getElementById('eag').style.display='none';
}
</script>
    <?php
}

// ── Checkout popup ─────────────────────────────────────────────────────────

add_action( 'wp_footer', 'eros_checkout_popup', 20 );
function eros_checkout_popup() {
    if ( ! function_exists( 'is_checkout' ) || ! is_checkout() ) return;
    if ( ! get_option( 'eros_popup_enabled', 1 ) ) return;

    $title   = esc_html( get_option( 'eros_popup_title', 'Important Notice' ) );
    $message = wp_kses_post( get_option( 'eros_popup_message', '' ) );
    if ( empty( $message ) ) return;

    $bg    = sanitize_hex_color( get_option( 'eros_popup_bg_color',   '#ffffff' ) );
    $fg    = sanitize_hex_color( get_option( 'eros_popup_text_color', '#333333' ) );
    $close     = 'document.getElementById(\'ecp\').style.display=\'none\'';
    $box_style = "background:{$bg};color:{$fg};border-radius:8px;padding:36px 32px 28px;max-width:480px;width:90%;position:relative;box-shadow:0 8px 32px rgba(0,0,0,.18);text-align:center;color-scheme:only light;forced-color-adjust:none;box-sizing:border-box;";
    $ttl_html  = $title ? '<h2 style="margin:0 0 12px;font-size:1.4em;color:'.$fg.'">'.$title.'</h2>' : '';
    $msg_html  = '<div style="color:'.$fg.'">'.$message.'</div>';
    ?>
<script>
(function(){
    var ov=document.createElement('div');
    ov.id='ecp';
    ov.style.cssText='position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.55);z-index:2147483647;display:flex;align-items:center;justify-content:center;box-sizing:border-box';
    var box=document.createElement('div');
    box.style.cssText=<?php echo json_encode($box_style); ?>;
    box.innerHTML=
        '<button onclick="document.getElementById(\'ecp\').style.display=\'none\'" aria-label="Close" style="position:absolute;top:10px;right:13px;background:none;border:none;font-size:22px;line-height:1;cursor:pointer;color:<?php echo $fg ?>">&times;</button>'
        +<?php echo json_encode($ttl_html.$msg_html); ?>
        +'<br><button onclick="document.getElementById(\'ecp\').style.display=\'none\'" style="margin-top:20px;padding:10px 32px;font-size:1em;cursor:pointer;background:#333;color:#fff;border:none;border-radius:5px;font-weight:bold">OK</button>';
    ov.appendChild(box);
    document.body.appendChild(ov);
})();
</script>
    <?php
}

// ── Clean up on uninstall ──────────────────────────────────────────────────

register_uninstall_hook( __FILE__, 'eros_popup_uninstall' );
function eros_popup_uninstall() {
    foreach ( [ 'eros_popup_enabled','eros_popup_title','eros_popup_message','eros_popup_bg_color','eros_popup_text_color',
                'eros_age_enabled','eros_age_title','eros_age_message','eros_age_redirect','eros_age_image',
                'eros_age_bg_color','eros_age_text_color','eros_age_yes_color','eros_age_no_color' ] as $k ) {
        delete_option( $k );
    }
}
