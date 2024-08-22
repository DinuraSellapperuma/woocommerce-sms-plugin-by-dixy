<?php
/**
 * Login form
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/global/form-login.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see         https://docs.woocommerce.com/document/template-structure/
 * @package     WooCommerce\Templates
 * @version     3.6.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( is_user_logged_in() ) {
	return;
}
?>

<form class="woocommerce-form woocommerce-form-login login" method="post" <?php echo ( $hidden ) ? 'style="display:none;"' : ''; ?>>

  <?php echo ( $message ) ? wpautop( wptexturize( $message ) ) : ''; // @codingStandardsIgnoreLine ?>

  <?php if (get_option('sms_panel_enable_login_with_phone_number') && get_option('sms_panel_enable_login_with_phone_number')[0]) : ?>
    <p class="form-row form-row-first">
      <label for="sms_panel_login_option">Login With</label>
      <select name="sms_panel_login_option" id="sms_panel_login_option" class="select-login-option">
        <option value="email">Email</option>
        <option value="phone">Phone</option>
      </select>
    </p>
  <?php endif; ?>

  <span id="sms_panel_email_login_form">
    <?php do_action( 'woocommerce_login_form_start' ); ?>

    <p class="form-row form-row-first">
      <label for="username"><?php esc_html_e( 'Username or email', 'woocommerce' ); ?>&nbsp;<span class="required">*</span></label>
      <input type="text" class="input-text" name="username" id="username" autocomplete="username" />
    </p>
    <p class="form-row form-row-last">
      <label for="password"><?php esc_html_e( 'Password', 'woocommerce' ); ?>&nbsp;<span class="required">*</span></label>
      <input class="input-text" type="password" name="password" id="password" autocomplete="current-password" />
    </p>
    <div class="clear"></div>

    <?php do_action( 'woocommerce_login_form' ); ?>

    <p class="form-row">
      <label class="woocommerce-form__label woocommerce-form__label-for-checkbox woocommerce-form-login__rememberme">
        <input class="woocommerce-form__input woocommerce-form__input-checkbox" name="rememberme" type="checkbox" id="rememberme" value="forever" /> <span><?php esc_html_e( 'Remember me', 'woocommerce' ); ?></span>
      </label>
      <?php wp_nonce_field( 'woocommerce-login', 'woocommerce-login-nonce' ); ?>
      <input type="hidden" name="redirect" value="<?php echo esc_url( $redirect ); ?>" />
      <button type="submit" class="woocommerce-button button woocommerce-form-login__submit" name="login" value="<?php esc_attr_e( 'Login', 'woocommerce' ); ?>"><?php esc_html_e( 'Login', 'woocommerce' ); ?></button>
    </p>
    <p class="lost_password">
      <a href="<?php echo esc_url( wp_lostpassword_url() ); ?>"><?php esc_html_e( 'Lost your password?', 'woocommerce' ); ?></a>
    </p>

    <div class="clear"></div>

    <?php do_action( 'woocommerce_login_form_end' ); ?>
  </span>
  
  <?php if (get_option('sms_panel_enable_login_with_phone_number') && get_option('sms_panel_enable_login_with_phone_number')[0]) : ?>
    <span id="sms_panel_phone_login_form" class="woocommerce-form woocommerce-form-login login w-47 d-block mb-3">

      <span id="sms_panel_login_phone_error" class="sms-panel-error d-none"></span>
      <span id="sms_panel_login_phone_success" class="sms-panel-success d-none"></span>
      <p id="sms_panel_login_phone_field" class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
        <label for="sms_panel_login_phone"><?php esc_html_e( 'Phone', 'sms_panel' ); ?>&nbsp;<span class="required">*</span></label>
        <input type="tel" class="woocommerce-Input woocommerce-Input--text input-text" name="sms_panel_login_phone" id="sms_panel_login_phone" />
      </p>

      <?php wp_nonce_field( 'login_user_otp', 'sms-panel-login-nonce' ); ?>
  </span>
  <?php endif; ?>

</form>
