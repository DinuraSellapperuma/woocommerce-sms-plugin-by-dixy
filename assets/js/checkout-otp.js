jQuery('document').ready(function($) {

    var ajax_url = plugin_ajax_object.ajax_url;
    var checkoutValidateKeyUps = 0;
    var billingPhoneFieldContainer  = document.getElementById('billing_phone_field');
    var billingPhone = document.getElementById('billing_phone');
    var billingPhoneValue = billingPhone ? billingPhone.value : "";

    function showSendCheckoutOTP() {
      var billingPhoneVerifyContainer = '<p class="form-row form-row-wide validate-required validate-phone" id="billing_phone_field_verify_container" data-priority="100">'+
          '<button type="button" class="button alt full-width" name="sms_panel_checkout_send_otp" id="sms_panel_checkout_send_otp" value="Send OTP" data-value="Verify Phone And Place Order">'+
            'Verify'+
          '</button>'+
      '</p>';

      jQuery(billingPhoneVerifyContainer).insertAfter(billingPhoneFieldContainer);
    }

    function hideSendCheckoutOTP() {
      var billingPhoneVerifyContainer = document.getElementById('billing_phone_field_verify_container');
      jQuery(billingPhoneVerifyContainer).remove();
    }

    function showAddCheckoutOTP() {
      var addCheckoutOTPContainer = '<p class="form-row form-row-first validate-required validate-phone" id="sms_panel_add_checkout_otp_field" data-priority="100">'+
        '<label for="sms_panel_add_checkout_otp" class="">OTP&nbsp;<abbr class="required" title="required">*</abbr></label>'+
          '<span class="woocommerce-input-wrapper">'+
            '<input type="text" class="input-text " name="sms_panel_add_checkout_otp" id="sms_panel_add_checkout_otp" placeholder="">'+
          '</span>'+
      '</p>';

      var billingPhoneVerifyContainer = document.getElementById('billing_phone_field_verify_container');
      jQuery(addCheckoutOTPContainer).insertAfter(billingPhoneVerifyContainer);
    }

    function hideAddCheckoutOTP() {
      var addCheckoutOTPContainer = document.getElementById('sms_panel_add_checkout_otp_field');
      jQuery(addCheckoutOTPContainer).remove();
    }

    function showBtnVerifyCheckoutOTP() {
      var btnVerifyCheckoutOTPContainer = '<p class="form-row form-row-last validate-required validate-phone" id="sms_panel_btn_verify_checkout_otp_field" data-priority="100">'+
          '<button type="button" class="button alt full-width btn-checkout-verify" name="sms_panel_btn_verify_checkout_otp" id="sms_panel_btn_verify_checkout_otp" value="Verify OTP" data-value="Verify Phone And Place Order">'+
            'Verify OTP'+
          '</button>'+
      '</p>';
      var addCheckoutOTPContainer = document.getElementById('sms_panel_add_checkout_otp_field');
      jQuery(btnVerifyCheckoutOTPContainer).insertAfter(addCheckoutOTPContainer);
    }

    function hideBtnVerifyCheckoutOTP() {
      var btnVerifyCheckoutOTPContainer = document.getElementById('sms_panel_btn_verify_checkout_otp_field');
      jQuery(btnVerifyCheckoutOTPContainer).remove();
    }

    function validateBillingPhoneField () {
      billingPhoneValue = billingPhone ? billingPhone.value : "";

      if (billingPhoneValue != '' && billingPhoneValue.match(/^[0-9]+$/)) {
        if (billingPhoneValue.length == 10) {
          checkoutValidateKeyUps++;
          if(checkoutValidateKeyUps == 1) {
            showSendCheckoutOTP();
          }
        } else{
          checkoutValidateKeyUps=0;
          hideSendCheckoutOTP();
          hideAddCheckoutOTP();
        }
      } else {
        checkoutValidateKeyUps=0;
        hideSendCheckoutOTP();
        hideAddCheckoutOTP();
      }
    }

    validateBillingPhoneField();

    jQuery('body').on('keyup', '#billing_phone', function(e) {
      e.preventDefault();
      if (!jQuery(billingPhone).is('[readonly]')) {
        validateBillingPhoneField();
      }
    });

    jQuery('body').on('click', '#sms_panel_checkout_send_otp', function(e) {
      e.preventDefault();

      var btnSendOTP = document.getElementById('sms_panel_checkout_send_otp');

      jQuery(btnSendOTP).attr('disabled', true);
      
      var data = {
        'action': 'send_checkout_otp',
        'billing_phone': billingPhoneValue
      };

      jQuery.ajax({
        url: ajax_url,
        type: 'post',
        data: data,
        dataType: 'json',
        success: function(response) {
          if (response != null && response.status == 'otp-sent') {
            showAddCheckoutOTP();
            showBtnVerifyCheckoutOTP();

            var timeleft = 60;
            var resendOtpTimer = setInterval(function(){
              if(timeleft <= 0){
                clearInterval(resendOtpTimer);
              }

              if (timeleft == 0) {
                hideAddCheckoutOTP();
                hideBtnVerifyCheckoutOTP();
                jQuery(btnSendOTP).html('Re-send OTP');
                jQuery(btnSendOTP).attr('disabled', false);
              }  else if (timeleft > 0) {
                let btnSendOtpHtml = timeleft;
                jQuery(btnSendOTP).html('Re-send OTP ' + btnSendOtpHtml);
              }

              timeleft -= 1;
            }, 1000);
          } else if (response != null && response.status == 'otp-failed') {
            alert('OTP sending failed!');
            jQuery(btnSendOTP).attr('disabled', false);
          }
        },
        error: function(error) {
          jQuery(btnSendOTP).attr('disabled', false);
        }
      });
    });

    jQuery('body').on('click', '#sms_panel_btn_verify_checkout_otp', function(e) {
      e.preventDefault();

      var addCheckoutOTP = document.getElementById('sms_panel_add_checkout_otp');
      var btnVerifyCheckoutOTP = document.getElementById('sms_panel_btn_verify_checkout_otp');

      jQuery(btnVerifyCheckoutOTP).attr('disabled', true);

      var data = {
        'action': 'verify_checkout_otp',
        'billing_phone': billingPhoneValue,
        'add_checkout_otp': addCheckoutOTP.value
      };

      jQuery.ajax({
        url: ajax_url,
        type: 'post',
        data: data,
        dataType: 'json',
        success: function(response) {
          if (response.status == 'otp-verified') {
            hideSendCheckoutOTP();
            hideAddCheckoutOTP();
            hideBtnVerifyCheckoutOTP();
            jQuery(billingPhone).attr('readonly', true);
          }
        },
        error: function(error) {
          jQuery(btnVerifyCheckoutOTP).attr('disabled', false);
        }
      });

    });




});