<?php

// KiliSMS API class
class KiliSMSAPI {
    
    private static $API_KEY = '3664A2519D7C41';
    private static $API_URL = 'http://smsportal.imartgroup.co.tz/app/smsapi/index.php';
    private static $DEFAULT_SENDER_ID = 'Rahisi';
    // private static $DEFAULT_SENDER_ID = 'LEZA';
    private static $CAMPAIGN_ID = 775;
    private static $ROUTE_ID = 8;

    public static function send($contacts, $message, $senderid = null) {
        
        
        // Set default sender ID
        if (!$senderid) $senderid = self::$DEFAULT_SENDER_ID;
        
        echo "<br/><p>send message: $contacts, $message, $senderid</p>";
        log_message('debug', "send message: $contacts, $message, $senderid");
        
        // Encode message
        $sms_text = urlencode($message);
        
        // Prepare Post Fields
        $postfields = "key=" . self::$API_KEY . "&campaign=".self::$CAMPAIGN_ID."&routeid=".self::$ROUTE_ID."&type=text&contacts=" . str_replace('+', '', $contacts) . "&senderid=" . $senderid . "&msg=" . $sms_text;
        
        // Intialize cURL request
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => self::$API_URL,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_HTTPHEADER => [
                "ContentType: application/x-www-form-urlencoded",
                "Accept: application/json",
                "Cache-Control: no-cache"
            ],
            CURLOPT_POSTFIELDS => $postfields
        ));
        
        // Execute cURL request
        $response = curl_exec($curl);
        $err = curl_error($curl);
        
        // Close cURL request
        curl_close($curl);
        
        if ($err) {
            // Set error message and load appropriate view
            echo "<br/><p>Error! Something went wrong. Could not send message.\n".json_encode($err)."</p>";
            // Log the error
            log_message('error', json_encode($err));
        }
        
        // Modify response if successful
        if (isset($response)) {
            $response = (object)["id" => $response];
            log_message('debug', json_encode($response));
        }
        
        // Return response
        return array('response' => $response, 'error' => $err);
        
    }
    
}