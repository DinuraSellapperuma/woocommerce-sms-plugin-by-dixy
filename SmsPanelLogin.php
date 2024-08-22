<?php

class SmsPanelLogin {
    public function login_user($phoneNumber) {
        $response = [];

        $phoneNumberExist = $this->phone_number_exists($phoneNumber);

        if ($phoneNumberExist) {
          wp_clear_auth_cookie();
          wp_set_current_user($phoneNumberExist);
          wp_set_auth_cookie($phoneNumberExist);

          $response['status'] = 'user-loggedin';
        } else {
          $response['status'] = 'user-not-found';
        }

        return $response;
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
}