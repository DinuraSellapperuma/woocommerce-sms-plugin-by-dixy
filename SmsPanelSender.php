<?php

require 'SMSPanelAPI.php';

class SmsPanelSender
{   

    private $adminPhoneNumbers;
    
    private $isSendAdminSMS = false;
    private $isSendCustomerNoteSMS = false;
    
    private $defaultStatusMessage;
    private $defaultAdminMessage;
    private $defaultCustomerMessage;
    private $defaultCheckoutMessage;

    private $isRegisterUserSMS;
    private $defaultRegisterUserMessage;

    public function __construct() {

        // Admin notifications
        $this->isSendAdminSMS =  get_option('sms_panel_send_admin_notification') && get_option('sms_panel_send_admin_notification')[0] ? true : false;
        $this->defaultAdminMessage = get_option('sms_panel_new_order_sms_template');
        $this->adminPhoneNumbers = get_option('sms_panel_admin_phone');

        // Customer notes
        $this->isSendCustomerNoteSMS = get_option('sms_panel_send_customer_note') && get_option('sms_panel_send_customer_note')[0] ? true : false;
        $this->defaultCustomerMessage = get_option('sms_panel_new_customer_note_sms_template');

        // Woo statuschange
        $this->defaultStatusMessage = get_option('sms_panel_woo_status_default_sms');

        // Checkout OTP
        $this->defaultCheckoutMessage = get_option('sms_panel_woo_user_verification_otp_sms_template');

        // Register OTP
        $this->isRegisterUserSMS = get_option('sms_panel_enable_register_with_phone_number') && get_option('sms_panel_enable_register_with_phone_number')[0] ? true : false;
        $this->defaultRegisterUserMessage = get_option('sms_panel_user_register_otp_sms_template');

        add_action('woocommerce_order_status_changed', array($this, 'woo_status_change_sms'), 11, 3);
        add_action('woocommerce_new_customer_note', array($this, 'woo_customer_note_message'));
    }

    public function woo_status_change_sms($orderId, $statusFrom, $statusTo) {

        if (!get_option('sms_panel_woo_order_status_'.$statusTo)[0]) {
            return false;
        }

        $data['order_id'] = $orderId;
        $data['status'] = $statusTo;

        $this->initSms($data);
    }

    public function woo_send_admin_sms($orderId) {
        if ($this->isSendAdminSMS) {

            $data['order_id'] = $orderId;
            $data['status'] = 'admin-sms';

            $this->initSms($data);
        }
    }

    public function woo_customer_note_message($orderData) {
        if ($this->isSendCustomerNoteSMS) {

            $data['order_id'] = $orderData['order_id'];
            $data['status'] = 'customer-note';
            $data['message'] = $orderData['customer_note'];

            $this->initSms($data);
        }
    }

    public function initSms($data = []) {
        $response = [];
        $orderDetails = null;

        $phoneNumbers = isset($data['phone_numbers']) ? $data['phone_numbers'] : "";
        $status = isset($data['status']) ? $data['status'] : "";
        $otp = isset($data['sent_otp']) ? $data['sent_otp'] : "";
        $message = isset($data['message']) ? $data['message'] : "";
        $orderId = isset($data['order_id']) ? $data['order_id'] : "";

        if ($orderId != "") $orderDetails = new WC_Order($orderId);

        if ($status == 'admin-sms') {
            $message = $this->defaultAdminMessage;
        } elseif ($status == 'customer-note') {
            $message = (empty($message) ? $this->defaultCustomerMessage : $this->defaultCustomerMessage.' '.$message);
        } elseif ($status == 'checkout') {
            $message = $this->defaultCheckoutMessage;
            $message = (empty($message) ? $otp : $this->defaultCheckoutMessage);
        } elseif ($status == 'register') {
            $message = $this->defaultRegisterUserMessage;
            $message = (empty($message) ? $otp : $this->defaultRegisterUserMessage);
        } else {
            $message = get_option('sms_panel_woo_status_'.$status.'_sms_template');
            $message = (empty($message) ? $this->defaultStatusMessage : $message);
        }

        $message = self::shortCodes($orderDetails, $message, $otp);

        $adminPhoneNumbers = $this->adminPhoneNumbers;
        $billingPhone = $orderDetails ? $orderDetails->billing_phone : "";

        switch ($status) {
          case 'admin-sms':
            $phoneNumbers =  $adminPhoneNumbers;
            break;
          case 'checkout':
            $phoneNumbers = $phoneNumbers;
            break;
          case 'register':
            $phoneNumbers = $phoneNumbers;
            break;
          default:
            $phoneNumbers = $billingPhone;
            break;
        }

        $response = $this->checkAndSend($phoneNumbers, $message);

        return $response;
        
    }

    public function checkAndSend($phoneNumbers, $message)
    {
        $response = [];
        $smsAPI = new SMSPanelAPI();

        $phoneNumbersArray = null;

        if ($phoneNumbers) {
            $phoneNumbers = preg_replace('#[ -]+#', '', $phoneNumbers);

            if (preg_match('/[\/,]/', $phoneNumbers, $matched) && strlen($phoneNumbers) > 10) {
                $phoneNumbersArray = explode($matched[0], $phoneNumbers);
                
                for ($i=0; $i < count($phoneNumbersArray); $i++) { 
                    $formatedPhoneNumber = $this->formatPhoneNumber($phoneNumbersArray[$i]);
                    $smsResponse = $smsAPI->sendMessage($formatedPhoneNumber, $message);
                }
                $response = $smsResponse;
            } else {
                $formatedPhoneNumber =  $this->formatPhoneNumber($phoneNumbers);
                $response = $smsAPI->sendMessage($formatedPhoneNumber, $message);
            }
        }

        return $response;
    }

    public function formatPhoneNumber($phoneNumber)
    {
        $countryCode = '94';

        if (strlen($phoneNumber) == 9) $phoneNumber = $countryCode.$phoneNumber;

        if (strlen($phoneNumber) > 9)  $phoneNumber = $countryCode.substr($phoneNumber, -9);

        return strlen($phoneNumber) == 11 ? $phoneNumber : null;
    }

    public static function shortCodes($orderDetails = null, $message = '', $otp = "") {

        $replacements_string = array(
          '{{shop_name}}' => get_bloginfo('name'),
          '{{order_id}}' => $orderDetails ? $orderDetails->get_order_number() : "",
          '{{order_amount}}' => $orderDetails ? $orderDetails->get_total() : "",
          '{{order_status}}' => $orderDetails ? ucfirst($orderDetails->get_status()) : "",
          '{{first_name}}' => $orderDetails ? ucfirst($orderDetails->billing_first_name) : "",
          '{{last_name}}' => $orderDetails ? ucfirst($orderDetails->billing_last_name) : "",
          '{{billing_city}}' => $orderDetails ? ucfirst($orderDetails->billing_city) : "",
          '{{customer_phone}}' => $orderDetails ? $orderDetails->billing_phone : "",
          '{{otp_number}}' => $otp != "" ? $otp : ""
        );
        return str_replace(array_keys($replacements_string), $replacements_string, $message);
    }
}