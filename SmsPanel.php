<?php
/**
 *
 * @package   Woocommerce SMS Alerts by dixy
 * @author    Dinura Sellapperuma
 * @copyright 2024 Dinura Sellapperuma
 * @license   GPL-2.0-or-later
 *
 * Plugin Name: D I X Y Woocommerce SMS Alerts / OTP 
 * Description: Send SMS Alert / OTP using D I X Y Woocommerce add-on
 * Plugin URI:  https://dev.dinurasellapperuma.com/
 * Author:      Dinura Sellapperuma
 * Author URI:  https://dinurasellapperuma.com/
 * Created:     18.05.2024
 * Version:     1.6
 * Text Domain: woocommerce-sms-alerts-by-dixy
 * Domain Path: /lang
 * License:     GPL v2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 *
 * Copyright (C) 2024 Dinura Sellapperuma
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, see <http://www.gnu.org/licenses/>.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

// Include Plugin Update Checker library
require_once plugin_dir_path(__FILE__) . 'includes/plugin-update-checker/plugin-update-checker.php';

use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

// Set up the update checker
$myUpdateChecker = PucFactory::buildUpdateChecker(
    'https://update.dinurasellapperuma.com/plugins/woo-sms/meta-data/update-check.json',
    __FILE__,
    'woocommerce-sms-alerts-by-dixy'
);

require 'SmsPanelSender.php';
require 'SmsPanelRegister.php';
require 'SmsPanelLogin.php';

class SmsPanel {

    private $triggerAPI;
    private $smsPanelRegister;

    private $isVerifyCheckoutOTP;
    private $isRegisterWithPhone;
    private $isLoginWithPhone;

    private $smsPanelLogin;

    public function __construct() {

      $this->triggerAPI = new SmsPanelSender();
      $this->smsPanelRegister = new SmsPanelRegister();
      $this->smsPanelLogin = new SmsPanelLogin();

      $this->isVerifyCheckoutOTP = get_option('sms_panel_woo_user_verification') && get_option('sms_panel_woo_user_verification')[0] ? true : false;
      $this->isRegisterWithPhone = get_option('sms_panel_enable_register_with_phone_number') && get_option('sms_panel_enable_register_with_phone_number')[0] ? true : false;
      $this->isLoginWithPhone = get_option('sms_panel_enable_login_with_phone_number') && get_option('sms_panel_enable_login_with_phone_number')[0] ? true : false;

    	// Hook into the admin menu
    	add_action( 'admin_menu', array( $this, 'create_plugin_settings_page' ) );

      // Add Settings and Fields
    	add_action( 'admin_init', array( $this, 'setup_sections' ) );
    	add_action( 'admin_init', array( $this, 'setup_fields' ) );

      add_action( 'woocommerce_order_status_processing', array($this->triggerAPI, 'woo_send_admin_sms'), 10, 1);

      // Update users table 
      add_filter('user_contactmethods', array(&$this->smsPanelRegister, 'new_contact_methods'), 10, 1 );
      add_filter('manage_users_columns', array(&$this->smsPanelRegister, 'new_modify_user_table'));
      add_filter('manage_users_custom_column', array(&$this->smsPanelRegister, 'new_modify_user_table_row'), 10, 3);

      // add_filter('woocommerce_locate_template', array(&$this, 'sms_panel_woocommerce_login'), 1, 3);

      if ($this->isVerifyCheckoutOTP || $this->isRegisterWithPhone || $this->isLoginWithPhone) {
          add_action( 'wp_enqueue_scripts', array($this, 'load_sms_panel_scripts' ));
      }

      // Woo checkout OTP
      if ($this->isVerifyCheckoutOTP) {
          add_action( 'wp_enqueue_scripts', array($this, 'load_sms_panel_checkout_scripts' ));

          add_action( 'woocommerce_checkout_process', array($this, 'sms_panel_validate_new_checkout_field' ));

          add_action( 'wp_ajax_send_checkout_otp', array($this, 'send_checkout_otp_callback'));
          add_action( 'wp_ajax_nopriv_send_checkout_otp', array($this, 'send_checkout_otp_callback'));

          add_action( 'wp_ajax_verify_checkout_otp', array($this, 'verify_checkout_otp_callback'));
          add_action( 'wp_ajax_nopriv_verify_checkout_otp', array($this, 'verify_checkout_otp_callback'));
      }

      // Register with phone
      if ($this->isRegisterWithPhone) {
          add_action( 'wp_enqueue_scripts', array($this, 'load_sms_panel_register_scripts' ));

          add_action( 'wp_ajax_send_register_user_otp', array($this, 'send_register_user_otp_callback'));
          add_action( 'wp_ajax_nopriv_send_register_user_otp', array($this, 'send_register_user_otp_callback'));

          add_action( 'wp_ajax_verify_register_user_otp', array($this, 'verify_register_user_otp_callback'));
          add_action( 'wp_ajax_nopriv_verify_register_user_otp', array($this, 'verify_register_user_otp_callback'));
      }

      // Login with phone
      if ($this->isLoginWithPhone) {
          add_action( 'wp_enqueue_scripts', array($this, 'load_sms_panel_login_scripts' ));

          add_action( 'wp_ajax_send_login_user_otp', array($this, 'send_login_user_otp_callback'));
          add_action( 'wp_ajax_nopriv_send_login_user_otp', array($this, 'send_login_user_otp_callback'));

          add_action( 'wp_ajax_verify_login_user_otp', array($this, 'verify_login_user_otp_callback'));
          add_action( 'wp_ajax_nopriv_verify_login_user_otp', array($this, 'verify_login_user_otp_callback'));
      }

    }

    public function create_plugin_settings_page() {
    	// Add the menu item and page
      $parent_slug = 'woocommerce';
    	$page_title = 'SMS Panel Settings Page';
    	$menu_title = 'DIXY SMS';
    	$capability = 'manage_options';
    	$slug = 'sms_panel';
    	$callback = array( $this, 'plugin_settings_page_content' );
    	$icon = plugins_url('/assets/images/sms.png', __FILE__);
    	$position = 100;

      add_menu_page( $page_title, $menu_title, $capability, $slug, $callback, $icon, $position );

    	add_submenu_page( $parent_slug, $page_title, $menu_title, $capability, $slug, $callback);
    }

    public function plugin_settings_page_content() {
      ?>
    	  <div class="wrap">
          <img src="<?php echo esc_url(plugins_url('/assets/images/logo.png', __FILE__)); ?>" width="400px">
          <h1>SMS Settings Page</h1>
          <?php
            if ( isset( $_GET['settings-updated'] ) && $_GET['settings-updated'] ){
                $this->admin_notice();
            } 

            $default_tab = null;
            $tab = isset($_GET['tab']) ? $_GET['tab'] : $default_tab;
          ?>

          <div class="wrap">
            <nav class="nav-tab-wrapper">
              <a href="?page=sms_panel" class="nav-tab <?php if($tab===null):?>nav-tab-active<?php endif; ?>">Credentials</a>
              <a href="?page=sms_panel&tab=notification" class="nav-tab <?php if($tab==='notification'):?>nav-tab-active<?php endif; ?>">Notifications</a>
              <a href="?page=sms_panel&tab=otp" class="nav-tab <?php if($tab==='otp'):?>nav-tab-active<?php endif; ?>">OTP</a>
              <a href="?page=sms_panel&tab=shortcode" class="nav-tab <?php if($tab==='shortcode'):?>nav-tab-active<?php endif; ?>">Short Codes</a>
            </nav>

            <div class="tab-content">
              <?php switch($tab) :
                case 'notification':
                  $this->notificationsTab();
                  break;
                case 'otp':
                  $this->otpTab();
                  break;
                case 'shortcode':
                  $this->shortcodeTab();
                  break;
                default:
                  $this->credentialsTab();
                  break;
              endswitch; ?>
            </div>
          </div>
    	  </div>
      <?php
    }

    public function credentialsTab() {
      ?>
      <form method="POST" action="options.php">
      <?php
          settings_fields( 'sms_panel_credentials_tab' );
          do_settings_sections( 'sms_panel_credentials_tab' );
          submit_button();
        ?>
      </form>
      <?php
    }

    public function notificationsTab() {
      ?>
      <form method="POST" action="options.php">
      <?php
          settings_fields( 'sms_panel_woo_notification_tab' );
          do_settings_sections( 'sms_panel_woo_notification_tab' );
          submit_button();
        ?>
      </form>
      <?php
    }

    public function otpTab() {
      ?>
      <form method="POST" action="options.php">
      <?php
          settings_fields( 'sms_panel_otp_tab' );
          do_settings_sections( 'sms_panel_otp_tab' );
          submit_button();
        ?>
      </form>
      <?php
    }

    public function shortcodeTab() {
      ?>
      <form method="POST" action="options.php">
      <?php
          settings_fields( 'sms_panel_short_code_tab' );
          do_settings_sections( 'sms_panel_short_code_tab' );
          submit_button();
        ?>
      </form>
      <?php
    }
    
    public function admin_notice() { 
      ?>
          <div class="notice notice-success is-dismissible">
              <p>Your settings have been updated!</p>
          </div>
      <?php
    }

    public function setup_sections() {
        add_settings_section( 'sms_panel_credentials', 'D I X Y SMS Panel Credentials', array( $this, 'section_callback' ), 'sms_panel_credentials_tab' );

        add_settings_section( 'sms_panel_admin_settings', 'Woocommerce Admin Notification', array( $this, 'section_callback' ), 'sms_panel_woo_notification_tab' );

        add_settings_section( 'sms_panel_woo_status', 'Woocommerce Status', array( $this, 'section_callback' ), 'sms_panel_woo_notification_tab' );

        add_settings_section( 'sms_panel_woo_customer_note', 'Woocommerce Customer Notes', array( $this, 'section_callback' ), 'sms_panel_woo_notification_tab' );

        add_settings_section( 'sms_panel_woo_customer_verification', 'Woocommerce checkout OTP', array( $this, 'section_callback' ), 'sms_panel_otp_tab' );
        
        add_settings_section( 'sms_panel_user_login_otp', 'Login with phone number', array( $this, 'section_callback' ), 'sms_panel_otp_tab' );

        add_settings_section( 'sms_panel_user_register_otp', 'Register with phone number', array( $this, 'section_callback' ), 'sms_panel_otp_tab' );

        add_settings_section( 'sms_panel_sms_short_codes', 'SMS Short Codes', array( $this, 'section_callback' ), 'sms_panel_short_code_tab' );
    }

    public function section_callback( $arguments ) {
    	switch( $arguments['id'] ){
        case 'sms_panel_credentials':
    			  echo 'Add D I X Y SMS Panel credentials';
    			  break;
        case 'sms_panel_admin_settings':
            echo 'Configure woocommerce new order notification for admin';
            break;
        case 'sms_panel_woo_status':
            echo 'Configure woocommerce status SMS';
            break;
        case 'sms_panel_woo_customer_verification':
            echo 'Configure woocommerce customer checkout verification SMS';
            break;
        case 'sms_panel_user_login_otp':
            echo 'Configure customer login verification SMS';
            break;
        case 'sms_panel_user_register_otp':
            echo 'Configure customer register verification SMS';
            break;
        case 'sms_panel_woo_customer_note':
            echo 'Configure woocommerce customer note SMS';
            break;
        case 'sms_panel_sms_short_codes':
            echo 'Available short codes for SMS templates';
            break;

    	}
    }

    public function setup_fields() {
        $fields = array(
          // Credentials
          array(
              'uid' => 'sms_panel_key',
              'label' => 'API Key',
              'section' => 'sms_panel_credentials',
              'page' => 'sms_panel_credentials_tab',
              'type' => 'text',
              'placeholder' => 'Enter API Key',
             // 'supplimental' => 'Enter SMS Panel API key',
              'style' => 'border-radius:0;width:40%',
             // 'default' => 'xxxxxxxx'
        	),
          array(
              'uid' => 'sms_panel_mask',
              'label' => 'Mask Name (Sender Id)',
              'section' => 'sms_panel_credentials',
              'page' => 'sms_panel_credentials_tab',
              'type' => 'text',
              'placeholder' => 'Enter Sender Mask Name',
              'supplimental' => '<p>Don&#39;t have an account? <a href="https://textit.biz/signup_1.php" target="_blank">click here</a> to contact me and register for an SMS account<br>If you already have an account,<br> please log in to view API details on the
<a href="https://sms.dinurasellapperuma.com/campaigns/apis" target="_blank">D I X Y SMS Panel.</a>&nbsp;&nbsp;<br>For further assistance, contact me on <a href="https://wa.me/94784991063">WhatsApp</a> at +94 78 499 1063.</p>',
              'style' => 'border-radius:0;width:40%',
             // 'default' => 'xxxxxxxx'
        	),

          // Admin settings
          array(
              'uid' => 'sms_panel_send_admin_notification',
              'label' => 'Admin notification',
              'section' => 'sms_panel_admin_settings',
              'page' => 'sms_panel_woo_notification_tab',
              'type' => 'checkbox',
              'options' => array('new_order_sms' => 'Enable new order SMS alert for admin'),
              'default' => array('new_order_sms' => 'Enable new order SMS alert for admin'),
              'style' => 'border-radius:0;'
        	),
          array(
              'uid' => 'sms_panel_new_order_sms_template',
              'label' => 'SMS Template',
              'placeholder' => 'SMS Template',
              'section' => 'sms_panel_admin_settings',
              'page' => 'sms_panel_woo_notification_tab',
              'type' => 'textarea',
              'style' => 'border-radius:0;width:40%',
              'default' => 'You have a new customer order for {{shop_name}}. Order #{{order_id}}, Total Value: {{order_amount}}'
        	),
          array(
              'uid' => 'sms_panel_admin_phone',
              'label' => 'Admin Phone',
              'section' => 'sms_panel_admin_settings',
              'page' => 'sms_panel_woo_notification_tab',
              'type' => 'text',
              'placeholder' => '07xxxxxxxx',
              'supplimental' => 'You can add more admin numbers, e.g., 07xxxxxxxx, 07xxxxxxxx.',
              'style' => 'border-radius:0;width:40%',
            //  'default' => '07xxxxxxxx'
        	),

          // User login OTP
          array(
              'uid' => 'sms_panel_enable_login_with_phone_number',
              'label' => 'Enable login with phone number',
              'section' => 'sms_panel_user_login_otp',
              'page' => 'sms_panel_otp_tab',
              'type' => 'checkbox',
              'options' => array('enable_login_with_phone' => ''),
              'default' => array('enable_login_with_phone' => ''),
              'style' => 'border-radius:0;'
          ),
          array(
              'uid' => 'sms_panel_user_login_otp_sms_template',
              'label' => 'SMS Template for OTP message',
              'placeholder' => 'OTP SMS Template',
              'section' => 'sms_panel_user_login_otp',
              'page' => 'sms_panel_otp_tab',
              'type' => 'textarea',
              'style' => 'border-radius:0;width:40%',
              'default' => 'You OTP number for {{shop_name}}. {{otp_number}}'
          ),
          array(
              'uid' => 'sms_panel_user_login_verification_attempts',
              'label' => 'Verification Attempts',
              'section' => 'sms_panel_user_login_otp',
              'page' => 'sms_panel_otp_tab',
              'placeholder' => '',
              'type' => 'number',
              'default' => 3,
              'style' => 'border-radius:0; width: 10%;'
          ),

          // User Register OTP
          array(
              'uid' => 'sms_panel_enable_register_with_phone_number',
              'label' => 'Enable login with phone number',
              'section' => 'sms_panel_user_register_otp',
              'page' => 'sms_panel_otp_tab',
              'type' => 'checkbox',
              'options' => array('enable_login_with_phone' => ''),
              'default' => array('enable_login_with_phone' => ''),
              'style' => 'border-radius:0;'
          ),
          array(
              'uid' => 'sms_panel_user_register_otp_sms_template',
              'label' => 'SMS Template for OTP message',
              'placeholder' => 'OTP SMS Template',
              'section' => 'sms_panel_user_register_otp',
              'page' => 'sms_panel_otp_tab',
              'type' => 'textarea',
              'style' => 'border-radius:0;width:40%',
              'default' => 'You OTP number for {{shop_name}}. {{otp_number}}'
          ),
          array(
              'uid' => 'sms_panel_user_register_verification_attempts',
              'label' => 'Verification Attempts',
              'section' => 'sms_panel_user_register_otp',
              'page' => 'sms_panel_otp_tab',
              'placeholder' => '',
              'type' => 'number',
              'default' => 3,
              'style' => 'border-radius:0; width: 10%;'
          ),

        );

        // woo checkout verification
        $wooUserVerficationFields = array(
            'uid' => 'sms_panel_woo_user_verification',
            'label' => 'Checkout Verification',
            'section' => 'sms_panel_woo_customer_verification',
            'page' => 'sms_panel_otp_tab',
            'type' => 'checkbox',
            'options' => array('user_verification' => 'Enable woo checkout verification OTP'),
            'default' => array('user_verification' => 'Enable woo checkout verification OTP'),
            'style' => 'border-radius:0;'
        );

        $wooCheckOutOtpTemplate = array(
            'uid' => 'sms_panel_woo_user_verification_otp_sms_template',
            'label' => 'SMS Template for OTP message',
            'placeholder' => 'OTP SMS Template',
            'section' => 'sms_panel_woo_customer_verification',
            'page' => 'sms_panel_otp_tab',
            'type' => 'textarea',
            'style' => 'border-radius:0;width:40%',
            'default' => 'You OTP number for {{shop_name}}. {{otp_number}}'
        );

        $wooUserVerficationAttempsFields = array(
            'uid' => 'sms_panel_woo_user_verification_attempts',
            'label' => 'Verification Attempts',
            'section' => 'sms_panel_woo_customer_verification',
            'page' => 'sms_panel_otp_tab',
            'placeholder' => '',
            'type' => 'number',
            'default' => 3,
            'style' => 'border-radius:0; width: 10%;'
        );

        // woo status
        $wooStatusDefaultSMS = array(
            'uid' => 'sms_panel_woo_status_default_sms',
            'label' => 'Default SMS Template',
            'placeholder' => 'Default SMS Template',
            'section' => 'sms_panel_woo_status',
            'page' => 'sms_panel_woo_notification_tab',
            'type' => 'textarea',
            'style' => 'border-radius:0;width:40%',
            'default' => 'Your order #{{order_id}} is now {{order_status}}. Thank you for shopping at {{shop_name}}.'
        );

        array_push($fields, $wooStatusDefaultSMS);

        $woo_status_array = wc_get_order_statuses();

        foreach ($woo_status_array as $key => $val) {
            $key = str_replace("wc-", "", $key);

            $wooFieldCheckBox = array(
                'uid' => 'sms_panel_woo_order_status_'.$key,
                'label' => 'Status '.$val,
                'section' => 'sms_panel_woo_status',
                'page' => 'sms_panel_woo_notification_tab',
                'type' => 'checkbox',
                'options' => array('sms_panel_woo_status_'.$key => 'Enable "'.$val.'" status sms alert'),
                'default' => array('sms_panel_woo_status_'.$key => 'Enable "'.$val.'" status sms alert'),
                'style' => 'border-radius:0;'
            );

            $wooFieldTextArea = array(
                'uid' => 'sms_panel_woo_status_'.$key.'_sms_template',
                'label' => '',
                'placeholder' => 'SMS Template '.$val,
                'section' => 'sms_panel_woo_status',
                'page' => 'sms_panel_woo_notification_tab',
                'type' => 'textarea',
                'style' => 'border-radius:0;width:40%',
                'default' => 'Your order #{{order_id}} is now {{order_status}}. Thank you for shopping at {{shop_name}}.'
            );

            array_push($fields, $wooFieldCheckBox, $wooFieldTextArea);

        }

        // woo customer notes
        $wooCustomerNotesCheckBox = array(
            'uid' => 'sms_panel_send_customer_note',
            'label' => 'Enable customer notes SMS',
            'section' => 'sms_panel_woo_customer_note',
            'page' => 'sms_panel_woo_notification_tab',
            'type' => 'checkbox',
            'options' => array('new_customer_note_sms' => 'New Customer Note SMS'),
            'default' => array('new_customer_note_sms' => 'New Customer Note SMS'),
            'style' => 'border-radius:0;'
        );
        $wooCustomerNotesTextArea = array(
            'uid' => 'sms_panel_new_customer_note_sms_template',
            'label' => 'SMS Template',
            'placeholder' => 'SMS Template',
            'section' => 'sms_panel_woo_customer_note',
            'page' => 'sms_panel_woo_notification_tab',
            'type' => 'textarea',
            'style' => 'border-radius:0;width:40%',
            'default' => 'Your order #{{order_id}} has new note: '
        );

        // woo short-codes
        $shortCodes = array(
            '{{first_name}}' => "First name of the customer.",
            '{{last_name}}' => "Last name of the customer.",
            '{{shop_name}}' => 'Your shop name (' . get_bloginfo('name') . ').',
            '{{order_id}}' => 'Ther order ID.',
            '{{order_amount}}' => "Current order amount.",
            '{{order_status}}' => 'Current order status (Pending, Failed, Processing, etc...).',
            '{{billing_city}}' => 'The city in the customer billing address (If available).',
            '{{customer_phone}}' => 'Customer mobile number (If given).',
            '{{otp_code}}' => 'Login / Register / Checkout OTP'
        );

        $shortCodeDescription = "";
        foreach($shortCodes as $key => $value) {
            $shortCodeDescription .= '<b>' . $key . '</b> - ' . $value . '<br>';
        }

        $shortCodeField =  array(
            'uid' => 'sms_panel_short_codes',
            'label' => 'Available Shortcodes',
            'section' => 'sms_panel_sms_short_codes',
            'page' => 'sms_panel_short_code_tab',
            'type' => 'supplimental',
            'supplimental' => 'These shortcodes can be used in your message body contents. <br><br>'.'<span style="color: black;">'.$shortCodeDescription.'</span>',
            'default' => ''
        );

        array_push(
            $fields, 
            $wooUserVerficationFields, 
            $wooCheckOutOtpTemplate, 
            $wooUserVerficationAttempsFields, 
            $wooCustomerNotesCheckBox, 
            $wooCustomerNotesTextArea, 
            $shortCodeField
        );
        
      	foreach( $fields as $field ){

            $page = isset($field['page']) ? $field['page'] : 'sms_panel';

          	add_settings_field( 
                $field['uid'], 
                $field['label'], 
                array( $this, 'field_callback' ), 
                $page, 
                $field['section'], 
                $field 
            );
            register_setting( 
                $page, 
                $field['uid'] 
            );
      	}
    }

    public function field_callback( $arguments ) {

        $value = get_option( $arguments['uid'] );

        if( ! $value ) {
            $value = $arguments['default'];
        }

        switch( $arguments['type'] ){
            case 'text':
            case 'password':
            case 'number':
                printf( '<input name="%1$s" id="%1$s" type="%2$s" placeholder="%3$s" value="%4$s" style="%5$s" />', $arguments['uid'], $arguments['type'], $arguments['placeholder'], $value , $arguments['style']);
                break;
            case 'textarea':
                printf( '<textarea name="%1$s" id="%1$s" placeholder="%2$s" rows="3" cols="50" style="%4$s">%3$s</textarea>', $arguments['uid'], $arguments['placeholder'], $value, $arguments['style']);
                break;
            case 'select':
            case 'multiselect':
                if( ! empty ( $arguments['options'] ) && is_array( $arguments['options'] ) ){
                    $attributes = '';
                    $options_markup = '';
                    foreach( $arguments['options'] as $key => $label ){
                        $options_markup .= sprintf( '<option value="%s" %s>%s</option>', $key, selected( $value[ array_search( $key, $value, true ) ], $key, false ), $label );
                    }
                    if( $arguments['type'] === 'multiselect' ){
                        $attributes = ' multiple="multiple" ';
                    }
                    printf( '<select name="%1$s[]" id="%1$s" %2$s>%3$s</select>', $arguments['uid'], $attributes, $options_markup );
                }
                break;
            case 'radio':
            case 'checkbox':
                if( ! empty ( $arguments['options'] ) && is_array( $arguments['options'] ) ){
                    $options_markup = '';
                    $iterator = 0;

                    foreach( $arguments['options'] as $key => $label ){
                        $iterator++;

                        if (array_key_exists($key, $value)) {
                            $checked = null;
                        } else {
                            $checked = $value[array_search( $key, $value, true )];
                        }

                        $options_markup .= sprintf( 
                            '<label for="%1$s_%6$s"><input id="%1$s_%6$s" name="%1$s[]" type="%2$s" value="%3$s" %4$s style="%7$s"/> %5$s</label><br/>', 
                            $arguments['uid'], 
                            $arguments['type'], 
                            $key, 
                            checked( $checked, $key, false ), 
                            $label, 
                            $iterator, 
                            $arguments['style']
                        );
                    }
                    printf( '<fieldset>%s</fieldset>', $options_markup );
                }
                break;
        }

        if( isset($arguments['helper']) ){
            printf( '<span class="helper"> %s</span>', $arguments['helper'] );
        }

        if( isset($arguments['supplimental']) ){
            printf( '<p class="description">%s</p>', $arguments['supplimental'] );
        }

    }

    // sms-panel scripts
    public function load_sms_panel_scripts() {
        wp_enqueue_style( 'sms-panel-style', plugin_dir_url( __FILE__ ) . 'assets/css/otp.css');

        $this->register_new_session();
        $_SESSION['sms_panel_billing_phone_verified'] = 'false';
        $_SESSION['sms_panel_sent_otp'] = null;
        $_SESSION['sms_panel_billing_phone'] = null;
        $_SESSION['sms_panel_otp_attempts'] = null;
        
        $_SESSION['sms_panel_register_phone_verified'] = 'false';
        $_SESSION['sms_panel_register_phone'] = null;
        $_SESSION['sms_panel_register_sent_otp'] = null;
        $_SESSION['sms_panel_register_otp_attempts'] = null;
    }

    public function load_sms_panel_checkout_scripts () {
        wp_enqueue_script( 'sms-panel-checkout-js', plugin_dir_url( __FILE__ ) . 'assets/js/checkout-otp.js', array( 'jquery' ), '', true );
        wp_localize_script( 'sms-panel-checkout-js', 'plugin_ajax_object', array( 'ajax_url' => admin_url( 'admin-ajax.php' ) ) );
    }

    public function load_sms_panel_register_scripts () {
        wp_enqueue_script( 'sms-panel-register-js', plugin_dir_url( __FILE__ ) . 'assets/js/register-otp.js', array( 'jquery' ), '', true );
        wp_localize_script( 'sms-panel-register-js', 'plugin_ajax_object', array( 'ajax_url' => admin_url( 'admin-ajax.php' ) ) );
    }

    public function load_sms_panel_login_scripts () {
        wp_enqueue_script( 'sms-panel-login-js', plugin_dir_url( __FILE__ ) . 'assets/js/login-otp.js', array( 'jquery' ), '', true );
        wp_localize_script( 'sms-panel-login-js', 'plugin_ajax_object', array( 'ajax_url' => admin_url( 'admin-ajax.php' ) ) );
    }

    public function register_new_session(){
        if (!session_id()) {
            session_start();
        } else {
            session_destroy();
            session_start();
        }
    }

    public function sms_panel_validate_new_checkout_field() {

        $this->register_new_session();

        $billingPhone = isset($_POST['billing_phone']) ? $_POST['billing_phone'] : "";
        $sentBillingPhone = isset($_SESSION['sms_panel_billing_phone']) ? $_SESSION['sms_panel_billing_phone'] : "";
        $isVerified = isset($_SESSION['sms_panel_billing_phone_verified']) ? $_SESSION['sms_panel_billing_phone_verified'] : "";

        if ($sentBillingPhone == $billingPhone && $isVerified == 'true' ) {
            $_SESSION['sms_panel_billing_phone_verified'] = 'false';
            $_SESSION['sms_panel_sent_otp'] = null;
            $_SESSION['sms_panel_billing_phone'] = null;
            $_SESSION['sms_panel_otp_attempts'] = null;
        } else {
          $msg = 'Please verifiy your phone number!';
          wc_add_notice($msg, 'error');
        }
    }

    public function send_checkout_otp_callback() {
        $response = [];
        $response = $this->otp_call($_POST, 'checkout');
        echo json_encode($response);
        wp_die();
    }

    public function otp_call($post, $status= "") {
      $response = [];

      if (is_array($post)) {
          switch ($status) {
            case 'checkout':
                $phoneNumber = array_key_exists('billing_phone', $post) ?  $post['billing_phone'] : "";
                $response = $phoneNumber != "" ? $this->sendOTP($phoneNumber, $status) : '';
                break;
            case 'register':
                $phoneNumber = array_key_exists('register_phone', $post) ? $post['register_phone'] : "";
                $phoneNumberExist = $this->smsPanelRegister->phone_number_exists($phoneNumber);

                if (!$phoneNumberExist) {
                    $response = $this->sendOTP($phoneNumber, $status);
                } else {
                    $response['status'] = "number-exist";
                }
                break;
            case 'login':
                $phoneNumber = array_key_exists('login_phone', $post) ? $post['login_phone'] : "";
                $phoneNumberExist = $this->smsPanelRegister->phone_number_exists($phoneNumber);

                if ($phoneNumberExist) {
                    $response = $this->sendOTP($phoneNumber, $status);
                } else {
                    $response['status'] = "user-not-found";
                }
                break;
          }
      }

      return $response;
    }

    public function sendOTP($phoneNumber, $status= "") {
        $response = [];
        $otpNumber = mt_rand(100000, 999999);

        $this->register_new_session();

        switch ($status) {
          case 'checkout':
              $_SESSION['sms_panel_billing_phone_verified'] = 'false';
              $_SESSION['sms_panel_sent_otp'] = $otpNumber;
              $_SESSION['sms_panel_billing_phone'] = $phoneNumber;
              $otpAttempts = $_SESSION['sms_panel_otp_attempts'] = 10;
              break;
          case 'register':
              $_SESSION['sms_panel_register_phone_verified'] = 'false';
              $_SESSION['sms_panel_register_phone'] = $phoneNumber;
              $_SESSION['sms_panel_register_sent_otp'] = $otpNumber;
              $otpAttempts = $_SESSION['sms_panel_register_otp_attempts'] = 10;
              break;
          case 'login':
              $_SESSION['sms_panel_login_phone_verified'] = 'false';
              $_SESSION['sms_panel_login_phone'] = $phoneNumber;
              $_SESSION['sms_panel_login_sent_otp'] = $otpNumber;
              $otpAttempts = $_SESSION['sms_panel_login_otp_attempts'] = 10;
              break;
        }

        if ($otpAttempts > 0) {

            $data['status'] = $status;
            $data['sent_otp'] = $otpNumber;
            $data['phone_numbers'] = $phoneNumber;

            $smsResult = $this->triggerAPI->initSms($data);

            $smsResponse = json_decode($smsResult);

            if ($smsResponse == 200) {
                $response['status'] = 'otp-sent';
            } else {
                $response['status'] = 'otp-failed';
            }

        } else {
            switch ($status) {
              case 'checkout':
                wc_add_notice('You have reached maximim attempts. Check the phone number or contact the website admin.', 'error' );
                break;
              case 'register':
                $response['attempts-over'];
                break;
            }
        }

        return $response;
    }

    public function verify_checkout_otp_callback() {
        $response = [];

        $this->register_new_session();

        $sentOTP = isset($_SESSION['sms_panel_sent_otp']) ? $_SESSION['sms_panel_sent_otp'] : "";
        $sentBillingPhone = isset($_SESSION['sms_panel_billing_phone']) ? $_SESSION['sms_panel_billing_phone'] : "";

        if (is_array($_POST) && array_key_exists('billing_phone', $_POST) && array_key_exists('add_checkout_otp', $_POST)) {
            $billingPhone = $_POST['billing_phone'];
            $addCheckoutOTP = $_POST['add_checkout_otp'];

            if ($sentBillingPhone == $billingPhone && $sentOTP == $addCheckoutOTP) {
              $_SESSION['sms_panel_billing_phone_verified'] = 'true';
              $response['status'] = 'otp-verified';
            } else {
              $_SESSION['sms_panel_billing_phone_verified'] = 'false';
              $response['status'] = 'otp-failed';
            }
        }

        echo json_encode($response);
        wp_die();
    }

    public function sms_panel_woocommerce_login($template, $template_name, $template_path) {
        global $woocommerce;

        $_template = $template;

        if (!$template_path) $template_path = $woocommerce->template_url;

        $plugin_path = untrailingslashit(plugin_dir_path(__FILE__)) . '/template/woocommerce/';

        $template = locate_template(array($template_path . $template_name, $template_name));

        if (!$template && file_exists($plugin_path . $template_name)) $template = $plugin_path . $template_name;

        if (!$template) $template = $_template;

        return $template;
    }

    public function send_register_user_otp_callback() {
        $response = [];
        $response = $this->otp_call($_POST, 'register');
        echo json_encode($response);
        wp_die();
    }

    public function verify_register_user_otp_callback() {
        $response = [];

        $this->register_new_session();

        $sentOTP = isset($_SESSION['sms_panel_register_sent_otp']) ? $_SESSION['sms_panel_register_sent_otp'] : "";
        $sentRegisterPhone = isset($_SESSION['sms_panel_register_phone']) ? $_SESSION['sms_panel_register_phone'] : "";

        if (is_array($_POST) && array_key_exists('register_phone', $_POST) && array_key_exists('add_register_otp', $_POST)) {
            $registerPhone = $_POST['register_phone'];
            $addRegisterOTP = $_POST['add_register_otp'];

            if ($sentRegisterPhone == $registerPhone && $sentOTP == $addRegisterOTP) {
              $_SESSION['sms_panel_register_phone_verified'] == 'true';
              $response['status'] = 'otp-verified';
              $response = $this->smsPanelRegister->register_user($registerPhone);
            } else {
              $_SESSION['sms_panel_register_phone_verified'] == 'false';
              $response['status'] = 'otp-failed';
            }
        }

        echo json_encode($response);
        wp_die();
    }

    public function send_login_user_otp_callback() {
        $response = [];
        $response = $this->otp_call($_POST, 'login');
        echo json_encode($response);
        wp_die();
    }

    public function verify_login_user_otp_callback() {
      $response = [];

      $this->register_new_session();

      $sentOTP = isset($_SESSION['sms_panel_login_sent_otp']) ? $_SESSION['sms_panel_login_sent_otp'] : "";
      $sentLoginPhone = isset($_SESSION['sms_panel_login_phone']) ? $_SESSION['sms_panel_login_phone'] : "";

      if (is_array($_POST) && array_key_exists('login_phone', $_POST) && array_key_exists('add_login_otp', $_POST)) {
          $loginPhone = $_POST['login_phone'];
          $addLoginOTP = $_POST['add_login_otp'];

          if ($sentLoginPhone == $loginPhone && $sentOTP == $addLoginOTP) {
              $_SESSION['sms_panel_login_phone_verified'] == 'true';
              $response['status'] = 'otp-verified';
              $response = $this->smsPanelLogin->login_user($loginPhone);
          } else {
              $_SESSION['sms_panel_login_phone_verified'] == 'false';
              $response['status'] = 'otp-failed';
          }
      }

      echo json_encode($response);
      wp_die();
  }

}

if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
    new SmsPanel();
}