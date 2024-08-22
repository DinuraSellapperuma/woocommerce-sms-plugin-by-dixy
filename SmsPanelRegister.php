<?php

class SmsPanelRegister {
  
    public function new_contact_methods( $contactmethods ) {
        $contactmethods['phone_number'] = 'Phone Number';
        $contactmethods['is_verified'] = 'Is Verified';
        
        return $contactmethods;
    }

    // Add new fields to users table
    public function new_modify_user_table($column) {
        $column['phone_number'] = __('Phone number', 'sms_panel');
        $column['is_verified'] = __('Is Verified', 'sms_panel');

        return $column;
    }

    public function new_modify_user_table_row($val, $column_name, $user_id) {
        switch ($column_name) {
            case 'phone_number' :
                return get_the_author_meta('phone_number', $user_id);
            case 'is_verified' :
                return get_the_author_meta('is_verified', $user_id);
            default:
        }
        return $val;
    }

    // Check phone number is exists
    public function phone_number_exists($phone_number) {
        $args = array(
            'meta_query' => array(
                array(
                    'key' => 'phone_number',
                    'value' => $phone_number,
                    'compare' => '='
                )
            )
        );

        $member_arr = get_users($args);
        if ($member_arr && $member_arr[0]) {
            return $member_arr[0]->ID;
        } else {
            return 0;
        }
    }

    public function register_user($phoneNumber) {
        $response = [];
        $data = [];
        $data['user_login'] = $this->generate_username($phoneNumber);
        $data['user_nicename'] = $data['nickname'] = $data['display_name'] = $data['user_login'];
        $data['user_url'] = sanitize_text_field($_GET['website']);
        $new_user_register = wp_insert_user($data);

        if (is_wp_error($new_user_register)) {
            $errors = $new_user_register->get_error_codes();

            if (in_array('empty_user_login', $errors)) {
                return $response['status'] = 'empty-login';
            } elseif (in_array('existing_user_login', $errors)) {
                return $response['status'] = 'user-exisit';
            } elseif (in_array('existing_user_email', $errors)) {
                return $response['status'] = 'email-exist';
            }
        } else {
            add_user_meta($new_user_register, 'phone_number', sanitize_user($phoneNumber));
            add_user_meta($new_user_register, 'is_verified', sanitize_user('true'));
            update_user_meta($new_user_register, '_billing_phone', sanitize_user($phoneNumber));
            update_user_meta($new_user_register, 'billing_phone', sanitize_user($phoneNumber));

            wp_clear_auth_cookie();
            wp_set_current_user($new_user_register);
            wp_set_auth_cookie($new_user_register);

            $response['status'] = 'user-registerd';
        }

        return $response;
    }

    public function generate_username($phoneNumber) {
      
      $checkUserName = username_exists($phoneNumber);

      if (!empty($checkUserName)) {
        $addNewUserValue = 2;
        while(!empty($checkUserName)) {
            $new_login = $phoneNumber . '-' . $addNewUserValue;
            $checkUserName = username_exists($new_login);
            $addNewUserValue++;
        }
        $phoneNumber = $new_login;
      }

      return $phoneNumber;
    }
}