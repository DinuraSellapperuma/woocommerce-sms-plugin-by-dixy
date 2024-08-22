jQuery('document').ready(function($) {
    var ajax_url = plugin_ajax_object.ajax_url;
    var registerPhoneValidateKeyUps = 0;

    var smsPanelEmailRegisterForm = document.getElementsByClassName('woocommerce-form-register');
    
    var smsPanelRegisterOption;
    var smsPanelPhoneRegisterForm;
    var smsPanelRegisterPhone;
    var smsPanelRegisterError;
    var smsPanelRegisterSuccess;

    function setRegisterOptions() {
      var registerOptionContainer = '<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">'+
        '<label for="sms_panel_register_option" class="label-register-option">Register With</label>'+
        '<select name="sms_panel_register_option" id="sms_panel_register_option" class="select-register-option">'+
          '<option value="email">Email</option>'+
          '<option value="phone">Phone</option>'+
        '</select>'+
      '</p>';
      jQuery(registerOptionContainer).insertBefore(smsPanelEmailRegisterForm);
    }

    function setPhoneRegisterForm() {
      smsPanelPhoneRegisterForm = '<form id="sms_panel_phone_register_form" class="woocommerce-form woocommerce-form-register register">'+
        '<span id="sms_panel_register_phone_error" class="sms-panel-error d-none"></span>'+
        '<span id="sms_panel_register_phone_success" class="sms-panel-success d-none"></span>'+
        '<p id="sms_panel_register_phone_field" class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">'+
          '<label for="sms_panel_register_phone">Phone&nbsp;<span class="required">*</span></label>'+
          '<input type="tel" class="woocommerce-Input woocommerce-Input--text input-text" name="sms_panel_register_phone" id="sms_panel_register_phone" />'+
        '</p>'+
      '</form>';
      jQuery(smsPanelPhoneRegisterForm).insertAfter(smsPanelEmailRegisterForm);
      smsPanelPhoneRegisterForm = document.getElementById('sms_panel_phone_register_form');
    }

    function unsetPhoneRegisterForm() {
      jQuery(smsPanelPhoneRegisterForm).remove();
    }

    if (smsPanelEmailRegisterForm) {
      jQuery(smsPanelEmailRegisterForm).attr('id', 'sms_panel_email_register_form');
      smsPanelEmailRegisterForm = document.getElementById('sms_panel_email_register_form');

      jQuery(smsPanelEmailRegisterForm).find('h2').remove();
      jQuery(smsPanelEmailRegisterForm).find('h3').remove();
      jQuery(smsPanelEmailRegisterForm).removeClass('pl-lg-4');

      setRegisterOptions();
    }

    function showHideRegisterForms () {
      smsPanelRegisterOption = document.getElementById('sms_panel_register_option');
      var smsPanelRegisterOptionValue = smsPanelRegisterOption ? smsPanelRegisterOption.value : "";

      if (smsPanelRegisterOptionValue == 'phone') {
        setPhoneRegisterForm();
        smsPanelRegisterPhone =  document.getElementById('sms_panel_register_phone');
        smsPanelRegisterError = document.getElementById('sms_panel_register_phone_error');
        smsPanelRegisterSuccess = document.getElementById('sms_panel_register_phone_success');
        jQuery(smsPanelEmailRegisterForm).addClass('d-none');
      } else if(smsPanelRegisterOptionValue == 'email') {
        unsetPhoneRegisterForm();
        jQuery(smsPanelEmailRegisterForm).removeClass('d-none');
      }
    }

    jQuery('body').on('change', '#sms_panel_register_option', function () {
      showHideRegisterForms();
    });

    function showBtnSendRegisterOTP() {
      var registerPhoneSendOtpContainer = '<p id="sms_panel_btn_register_phone_send_otp_field" class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">'+
        '<button type="button" class="woocommerce-Button woocommerce-button button sms-panel-btn-register-phone full-width" name="sms_panel_btn_register_phone_send_otp" id="sms_panel_btn_register_phone_send_otp" value="Verify">'+
          'Verify'+
        '</button>'+
      '</p>';
      jQuery(registerPhoneSendOtpContainer).insertAfter(smsPanelRegisterPhone);
    }

    function hideSendRegisterOTP() {
      var registerPhoneSendOtpContainer = document.getElementById('sms_panel_btn_register_phone_send_otp_field');
      jQuery(registerPhoneSendOtpContainer).remove();
    }

    function showAddRegisterOTP() {
      var addRegisterOTPContainer = '<p id="sms_panel_register_phone_otp_field" class="woocommerce-form-row form-row form-row-first">'+
        '<label for="sms_panel_register_phone_otp">OTP&nbsp;<span class="required">*</span></label>'+
        '<input type="text" class="woocommerce-Input woocommerce-Input--text input-text" name="sms_panel_register_phone_otp" id="sms_panel_register_phone_otp" />'+
      '</p>';
      var registerPhoneSendOtpContainer = document.getElementById('sms_panel_btn_register_phone_send_otp_field');
      jQuery(addRegisterOTPContainer).insertAfter(registerPhoneSendOtpContainer);
    }

    function hideAddRegisterOTP() {
      var addRegisterOTPContainer = document.getElementById('sms_panel_register_phone_otp_field');
      jQuery(addRegisterOTPContainer).remove();
    }

    function showBtnVerifyRegisterOTP() {
      var registerPhoneVerifyOtpContainer = '<p id="sms_panel_btn_register_phone_verify_otp_field" class="woocommerce-form-row form-row form-row-last">'+
        '<button type="button" class="woocommerce-Button woocommerce-button button sms-panel-btn-register-phone-verify full-width" name="sms_panel_btn_register_phone_verify_otp" id="sms_panel_btn_register_phone_verify_otp" value="Verify">'+
          'Verify'+
        '</button>'+
      '</p>';
      addRegisterOTPContainer = document.getElementById('sms_panel_register_phone_otp_field');
      jQuery(registerPhoneVerifyOtpContainer).insertAfter(addRegisterOTPContainer);
    }

    function hideBtnVerifyRegisterOTP() {
      var registerPhoneVerifyOtpContainer = document.getElementById('sms_panel_btn_register_phone_verify_otp_field');
      jQuery(registerPhoneVerifyOtpContainer).remove();
    }

    function validateRegisterPhoneField () {
      jQuery(smsPanelRegisterError).addClass('d-none');
      jQuery(smsPanelRegisterError).html('');

      smsPanelRegisterPhoneValue = smsPanelRegisterPhone ? smsPanelRegisterPhone.value : "";

      if (smsPanelRegisterPhoneValue != '' && smsPanelRegisterPhoneValue.match(/^[0-9]+$/)) {
        if (smsPanelRegisterPhoneValue.length == 10) {
          registerPhoneValidateKeyUps++;
          if (registerPhoneValidateKeyUps == 1) {
            showBtnSendRegisterOTP();
          }
        } else{
          registerPhoneValidateKeyUps = 0;
          hideSendRegisterOTP();
          hideAddRegisterOTP();
          hideBtnVerifyRegisterOTP();
        }
      } else {
        registerPhoneValidateKeyUps = 0;
        jQuery(smsPanelRegisterError).removeClass('d-none');
        jQuery(smsPanelRegisterError).html('Enter valid phone number');
        hideSendRegisterOTP();
        hideAddRegisterOTP();
        hideBtnVerifyRegisterOTP();
      }
    }

    jQuery('body').on('keyup', '#sms_panel_register_phone', function(e) {
      if (e.keyCode === 13 || e.which === 13) {
        e.preventDefault();
        return false;
      }
      e.preventDefault();
      if (!jQuery(smsPanelRegisterPhone).is('[readonly]')) {
        validateRegisterPhoneField();
      }
    });

    jQuery('body').on('click', '#sms_panel_btn_register_phone_send_otp', function(e) {
      e.preventDefault();

      jQuery(smsPanelRegisterError).addClass('d-none');
      jQuery(smsPanelRegisterError).html('');

      jQuery(smsPanelRegisterSuccess).addClass('d-none');
      jQuery(smsPanelRegisterSuccess).html('');

      var btnRegPhoneSendOTP = document.getElementById('sms_panel_btn_register_phone_send_otp');

      jQuery(btnRegPhoneSendOTP).attr('disabled', true);

      var data = {
        'action': 'send_register_user_otp',
        'register_phone': smsPanelRegisterPhoneValue
      }

      jQuery.ajax({
        url: ajax_url,
        type: 'post',
        data: data,
        dataType: 'json',
        success: function(response) {
          if (response != null && response.status == 'otp-sent') {

            jQuery(smsPanelRegisterSuccess).removeClass('d-none');
            jQuery(smsPanelRegisterSuccess).html('OTP sent to your phone number. Insert OTP and verify!');

            showAddRegisterOTP();
            showBtnVerifyRegisterOTP();

            var timeleft = 60;
            var resendOtpTimer = setInterval(function(){
              if(timeleft <= 0){
                clearInterval(resendOtpTimer);
              }

              if (timeleft == 0) {
                hideAddRegisterOTP();
                hideBtnVerifyRegisterOTP();
                jQuery(btnRegPhoneSendOTP).html('Re-send OTP');
                jQuery(btnRegPhoneSendOTP).attr('disabled', false);
              }  else if (timeleft > 0) {
                let btnSendOtpHtml = timeleft;
                jQuery(btnRegPhoneSendOTP).html('Re-send OTP ' + btnSendOtpHtml);
              }

              timeleft -= 1;
            }, 1000);
          } else if (response != null && response.status == 'otp-failed') {
            jQuery(smsPanelRegisterError).removeClass('d-none');
            jQuery(smsPanelRegisterError).html('OTP sending failed! Contact the admin!');
            jQuery(btnRegPhoneSendOTP).attr('disabled', false);
          } else if (response != null && response.status == 'number-exist') {
            jQuery(smsPanelRegisterError).removeClass('d-none');
            jQuery(smsPanelRegisterError).html('Your phone number is already registerd.');
            jQuery(btnRegPhoneSendOTP).attr('disabled', false);
          }
        },
        error: function(error) {
          jQuery(btnRegPhoneSendOTP).attr('disabled', false);
        }
      });

    });

    jQuery('body').on('click', '#sms_panel_btn_register_phone_verify_otp', function(e) {
      e.preventDefault();

      jQuery(smsPanelRegisterError).addClass('d-none');
      jQuery(smsPanelRegisterError).html('');

      jQuery(smsPanelRegisterSuccess).addClass('d-none');
      jQuery(smsPanelRegisterSuccess).html('');

      var addRegisterOTP = document.getElementById('sms_panel_register_phone_otp');
      var btnVerifyRegisterOTP = document.getElementById('sms_panel_btn_register_phone_verify_otp');

      var data = {
        'action': 'verify_register_user_otp',
        'register_phone': smsPanelRegisterPhoneValue,
        'add_register_otp': addRegisterOTP ? addRegisterOTP.value : ""
      }

      jQuery.ajax({
        url: ajax_url,
        type: 'post',
        data: data,
        dataType: 'json',
        success: function(response) {
          if (response != null && response.status == 'otp-verified') {
            hideSendRegisterOTP();
            hideAddRegisterOTP();
            hideBtnVerifyRegisterOTP();
            jQuery(smsPanelRegisterPhone).attr('readonly', true);

            jQuery(smsPanelRegisterSuccess).removeClass('d-none');
            jQuery(smsPanelRegisterSuccess).html('Your phone number is verified! Registration processing.');
          } else if (response != null && response.status == 'number-exist') {
            hideSendRegisterOTP();
            hideAddRegisterOTP();
            hideBtnVerifyRegisterOTP();

            jQuery(smsPanelRegisterError).removeClass('d-none');
            jQuery(smsPanelRegisterError).html('Your phone number is already registerd.');
          } else if (response != null && response.status == 'user-registerd') {
            hideSendRegisterOTP();
            hideAddRegisterOTP();
            hideBtnVerifyRegisterOTP();
            jQuery(smsPanelRegisterPhone).attr('readonly', true);

            jQuery(smsPanelRegisterSuccess).removeClass('d-none');
            jQuery(smsPanelRegisterSuccess).html('Your phone number is verifie and registerd.');
            window.location.reload();
          } else if (response != null && response.status == 'otp-failed') {
            jQuery(smsPanelRegisterError).removeClass('d-none');
            jQuery(smsPanelRegisterError).html('OTP did not match!');
          }
        },
        error: function(error) {
          jQuery(btnVerifyRegisterOTP).attr('disabled', false);
        }
      });
    });


});