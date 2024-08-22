<?php

class SMSPanelAPI 
{
    private $apiKey, $smsMask;

    public function __construct() {
        $this->apiKey = get_option('sms_panel_key');
        $this->smsMask = get_option('sms_panel_mask');
    }

    public function sendMessage($phoneNumber, $message) {

        $url = 'https://portal.richmo.lk/api/sms/send/';
          
        $dataArray = array(
            'dst' => $phoneNumber,
            'from' => $this->smsMask,
            'msg' => $message
        );

        $ch = curl_init();
        $data = http_build_query($dataArray);
        $getUrl = $url."?".$data;

        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_URL, $getUrl);
        curl_setopt($ch, CURLOPT_TIMEOUT, 80);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Authorization: Bearer '.$this->apiKey));
        
        $curlResponse = curl_exec($ch);
        $curlInfo = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        curl_close($ch);

        return $curlInfo;
    }
}