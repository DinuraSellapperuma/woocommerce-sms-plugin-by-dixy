jQuery('document').ready(function($) {
  var ajax_url = plugin_ajax_object.ajax_url;
  var loginPhoneValidateKeyUps = 0;
  
  var smsPanelEmailLoginForm = document.getElementsByClassName('login');
  var wooLoginToggle = document.getElementsByClassName('woocommerce-form-login-toggle');

  var smsPanelLoginOption;
  var smsPanelPhoneLoginForm;
  var smsPanelLoginPhone;
  var smsPanelLoginError;
  var smsPanelLoginSuccess;

  function setLoginOptions() {
    var loginOptionContainer = '<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">'+
      '<label for="sms_panel_login_option" class="label-login-option">Login With</label>'+
      '<select name="sms_panel_login_option" id="sms_panel_login_option" class="select-login-option">'+
        '<option value="email">Email</option>'+
        '<option value="phone">Phone</option>'+
      '</select>'+
    '</p>';
    jQuery(loginOptionContainer).insertBefore(smsPanelEmailLoginForm);
  }

  function setPhoneLoginForm() {
    smsPanelPhoneLoginForm = '<form id="sms_panel_phone_login_form" class="woocommerce-form woocommerce-form-login login">'+
      '<span id="sms_panel_login_phone_error" class="sms-panel-error d-none"></span>'+
      '<span id="sms_panel_login_phone_success" class="sms-panel-success d-none"></span>'+
      '<p id="sms_panel_login_phone_field" class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">'+
        '<label for="sms_panel_login_phone">Phone&nbsp;<span class="required">*</span></label>'+
        '<input type="tel" class="woocommerce-Input woocommerce-Input--text input-text" name="sms_panel_login_phone" id="sms_panel_login_phone" />'+
      '</p>'+
    '</form>';
    jQuery(smsPanelPhoneLoginForm).insertAfter(smsPanelEmailLoginForm);
    smsPanelPhoneLoginForm = document.getElementById('sms_panel_phone_login_form');
  }

  function unsetPhoneLoginForm() {
    jQuery(smsPanelPhoneLoginForm).remove();
  }

  if (smsPanelEmailLoginForm) {
    jQuery(smsPanelEmailLoginForm).attr('id', 'sms_panel_email_login_form');
    smsPanelEmailLoginForm = document.getElementById('sms_panel_email_login_form');

    jQuery(smsPanelEmailLoginForm).find('h2').remove();
    jQuery(smsPanelEmailLoginForm).find('h3').remove();

    if (wooLoginToggle) {
      jQuery(wooLoginToggle).remove();
      jQuery(smsPanelEmailLoginForm).attr('style', '');
    }
    setLoginOptions();
  }

  function showHideLoginform() {
    smsPanelLoginOption = document.getElementById('sms_panel_login_option');
    var smsPanelLoginOptionValue = smsPanelLoginOption ? smsPanelLoginOption.value : "";
      
    if (smsPanelLoginOptionValue == 'phone') {
      setPhoneLoginForm();
      smsPanelLoginPhone =  document.getElementById('sms_panel_login_phone');
      smsPanelLoginError = document.getElementById('sms_panel_login_phone_error');
      smsPanelLoginSuccess = document.getElementById('sms_panel_login_phone_success')
      jQuery(smsPanelEmailLoginForm).addClass('d-none');
    } else if(smsPanelLoginOptionValue == 'email') {
      unsetPhoneLoginForm();
      jQuery(smsPanelEmailLoginForm).removeClass('d-none');
    }
  }

  jQuery('body').on('change', '#sms_panel_login_option', function () {
    showHideLoginform();
  });

  function showBtnSendLoginOTP() {
    var loginPhoneSendOtpContainer = '<p id="sms_panel_btn_login_phone_send_otp_field" class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">'+
      '<button type="button" class="woocommerce-Button woocommerce-button button sms-panel-btn-login-phone full-width" name="sms_panel_btn_login_phone_send_otp" id="sms_panel_btn_login_phone_send_otp" value="Verify">'+
        'Verify'+
      '</button>'+
    '</p>';
    jQuery(loginPhoneSendOtpContainer).insertAfter(smsPanelLoginPhone);
  }

  function hideSendLoginOTP() {
    var loginPhoneSendOtpContainer = document.getElementById('sms_panel_btn_login_phone_send_otp_field');
    jQuery(loginPhoneSendOtpContainer).remove();
  }

  function showAddLoginOTP() {
    var addLoginOTPContainer = '<p id="sms_panel_login_phone_otp_field" class="woocommerce-form-row form-row form-row-first">'+
      '<label for="sms_panel_login_phone_otp">OTP&nbsp;<span class="required">*</span></label>'+
      '<input type="text" class="woocommerce-Input woocommerce-Input--text input-text" name="sms_panel_login_phone_otp" id="sms_panel_login_phone_otp" />'+
    '</p>';
    var loginPhoneSendOtpContainer = document.getElementById('sms_panel_btn_login_phone_send_otp_field');
    jQuery(addLoginOTPContainer).insertAfter(loginPhoneSendOtpContainer);
  }

  function hideAddLoginOTP() {
    var addLoginOTPContainer = document.getElementById('sms_panel_login_phone_otp_field');
    jQuery(addLoginOTPContainer).remove();
  }

  function showBtnVerifyLoginOTP() {
    var loginPhoneVerifyOtpContainer = '<p id="sms_panel_btn_login_phone_verify_otp_field" class="woocommerce-form-row form-row form-row-last">'+
      '<button type="button" class="woocommerce-Button woocommerce-button button sms-panel-btn-login-phone-verify full-width" name="sms_panel_btn_login_phone_verify_otp" id="sms_panel_btn_login_phone_verify_otp" value="Verify">'+
        'Verify'+
      '</button>'+
    '</p>';
    addLoginOTPContainer = document.getElementById('sms_panel_login_phone_otp_field');
    jQuery(loginPhoneVerifyOtpContainer).insertAfter(addLoginOTPContainer);
  }

  function hideBtnVerifyLoginOTP() {
    var loginPhoneVerifyOtpContainer = document.getElementById('sms_panel_btn_login_phone_verify_otp_field');
    jQuery(loginPhoneVerifyOtpContainer).remove();
  }

  function validateLoginPhoneField () {
    jQuery(smsPanelLoginError).addClass('d-none');
    jQuery(smsPanelLoginError).html('');

    smsPanelLoginPhoneValue = smsPanelLoginPhone ? smsPanelLoginPhone.value : "";

    if (smsPanelLoginPhoneValue != '' && smsPanelLoginPhoneValue.match(/^[0-9]+$/)) {
      if (smsPanelLoginPhoneValue.length == 10) {
        loginPhoneValidateKeyUps++;
        if (loginPhoneValidateKeyUps == 1) {
          showBtnSendLoginOTP();
        }
      } else{
        loginPhoneValidateKeyUps = 0;
        hideSendLoginOTP();
        hideAddLoginOTP();
        hideBtnVerifyLoginOTP();
      }
    } else {
      loginPhoneValidateKeyUps = 0;
      jQuery(smsPanelLoginError).removeClass('d-none');
      jQuery(smsPanelLoginError).html('Enter valid phone number');
      hideSendLoginOTP();
      hideAddLoginOTP();
      hideBtnVerifyLoginOTP();
    }
  }

  jQuery('body').on('keyup', '#sms_panel_login_phone', function(e) {
    if (e.keyCode === 13 || e.which === 13) {
      e.preventDefault();
      return false;
    }
    e.preventDefault();
    if (!jQuery(smsPanelLoginPhone).is('[readonly]')) {
      validateLoginPhoneField();
    }
  });

  jQuery('body').on('click', '#sms_panel_btn_login_phone_send_otp', function(e) {
    e.preventDefault();

    jQuery(smsPanelLoginError).addClass('d-none');
    jQuery(smsPanelLoginError).html('');

    jQuery(smsPanelLoginSuccess).addClass('d-none');
    jQuery(smsPanelLoginSuccess).html('');

    var btnLoginPhoneSendOTP = document.getElementById('sms_panel_btn_login_phone_send_otp');

    jQuery(btnLoginPhoneSendOTP).attr('disabled', true);

    var data = {
      'action': 'send_login_user_otp',
      'login_phone': smsPanelLoginPhoneValue
    }

    jQuery.ajax({
      url: ajax_url,
      type: 'post',
      data: data,
      dataType: 'json',
      success: function(response) {
        if (response != null && response.status == 'otp-sent') {
          showAddLoginOTP();
          showBtnVerifyLoginOTP();

          var timeleft = 60;
          var resendOtpTimer = setInterval(function(){
            if(timeleft <= 0){
              clearInterval(resendOtpTimer);
            }

            if (timeleft == 0) {
              hideAddLoginOTP();
              hideBtnVerifyLoginOTP();
              jQuery(btnLoginPhoneSendOTP).html('Re-send OTP');
              jQuery(btnLoginPhoneSendOTP).attr('disabled', false);
            }  else if (timeleft > 0) {
              let btnSendOtpHtml = timeleft;
              jQuery(btnLoginPhoneSendOTP).html('Re-send OTP ' + btnSendOtpHtml);
            }

            timeleft -= 1;
          }, 1000);
        } else if (response != null && response.status == 'otp-failed') {
          jQuery(smsPanelLoginError).removeClass('d-none');
          jQuery(smsPanelLoginError).html('OTP sending failed! Contact the admin!');
          jQuery(btnLoginPhoneSendOTP).attr('disabled', false);
        } else if (response != null && response.status == 'user-not-found') {
          jQuery(smsPanelLoginError).removeClass('d-none');
          jQuery(smsPanelLoginError).html('User not found!');
          jQuery(btnLoginPhoneSendOTP).attr('disabled', false);
        }
      },
      error: function(error) {
        jQuery(btnLoginPhoneSendOTP).attr('disabled', false);
      }
    });

  });

  jQuery('body').on('click', '#sms_panel_btn_login_phone_verify_otp', function(e) {
    e.preventDefault();

    jQuery(smsPanelLoginError).addClass('d-none');
    jQuery(smsPanelLoginError).html('');

    jQuery(smsPanelLoginSuccess).addClass('d-none');
    jQuery(smsPanelLoginSuccess).html('');

    var addLoginOTP = document.getElementById('sms_panel_login_phone_otp');
    var btnVerifyLoginOTP = document.getElementById('sms_panel_btn_login_phone_verify_otp');

    var data = {
      'action': 'verify_login_user_otp',
      'login_phone': smsPanelLoginPhoneValue,
      'add_login_otp': addLoginOTP ? addLoginOTP.value : ""
    }

    jQuery.ajax({
      url: ajax_url,
      type: 'post',
      data: data,
      dataType: 'json',
      success: function(response) {
        if (response != null && response.status == 'otp-verified') {
          hideSendLoginOTP();
          hideAddLoginOTP();
          hideBtnVerifyLoginOTP();
          jQuery(smsPanelLoginPhone).attr('readonly', true);

          jQuery(smsPanelLoginSuccess).removeClass('d-none');
          jQuery(smsPanelLoginSuccess).html('Your phone number is verified! Login processing.');
        } else if (response != null && response.status == 'user-loggedin') {
          hideSendLoginOTP();
          hideAddLoginOTP();
          hideBtnVerifyLoginOTP();
          jQuery(smsPanelLoginPhone).attr('readonly', true);

          jQuery(smsPanelLoginSuccess).removeClass('d-none');
          jQuery(smsPanelLoginSuccess).html('You are succsfully logged-in.');
          window.location.reload();
        } else if (response != null && response.status == 'otp-failed') {
          jQuery(smsPanelLoginError).removeClass('d-none');
          jQuery(smsPanelLoginError).html('OTP did not match!');
        } else if (response != null && response.status == 'user-not-found') {
          jQuery(smsPanelLoginError).removeClass('d-none');
          jQuery(smsPanelLoginError).html('User not found!');
        }
      },
      error: function(error) {
        jQuery(btnVerifyLoginOTP).attr('disabled', false);
      }
    });
  });


});