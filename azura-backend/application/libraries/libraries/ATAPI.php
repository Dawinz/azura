<?php

// AfricasTalking API class
class ATAPI {
    
    // Auth Parameters
    private static $BASE_URL = "https://api.africastalking.com";
    private static $USERNAME = "e-mazao";
	private static $API_KEY = "ab515ce9a3f9ba20b6b065138bedb8620f47a3b5f312252f58cd0a24a5ed2e09";
	private static $DEFAULT_SENDER_ID = 'E-Mazao';

    // Get Method
    public static function get($route, $params = []) {

        $_params = '?usename=' . self::$USERNAME . '&';
        if (isset($params) && !empty($params)) {
            if (!$params['from']) $params['from'] = self::$DEFAULT_SENDER_ID;
            foreach($params as $key=>$value)
                $_params .= $key . "=" . urlencode($value) . "&";
        }
        
        echo "<br/><p>ATAPI get: $_params</p>";
        log_message('debug', "ATAPI get: $_params");
        
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => self::$BASE_URL . '/version1/' . $route . $_params,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_HTTPHEADER => array(
                "apiKey: ".self::$API_KEY,
                "ContentType: application/x-www-form-urlencoded",
                "Accept: application/json",
                "Cache-Control: no-cache",
            ),
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);
        
        if ($err) {
            // Set error message and load appropriate view
            echo "<br/><p>Error! Something went wrong. Could not send message.\n".json_encode($err)."</p>";
            // Log the error
            log_message('error', json_encode($err));
        }
        
        if (isset($response)) log_message('debug', json_encode($response));

        return array('response' => $response, 'error' => $err);
    }
    
    // Post Method
    public static function post($route, $data) {
        
        $postfields = "username=" . self::$USERNAME . "&";
        if (isset($params) && !empty($params)) {
            if (!$params['from']) $params['from'] = self::$DEFAULT_SENDER_ID;
            foreach($data as $key=>$value)
                $postfields .= $key . "=" . urlencode($value) . "&";
        }
        
        echo "<br/><p>ATAPI post: $postfields</p>";
        log_message('debug', "ATAPI post: $postfields");

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => self::$BASE_URL . '/version1/' . $route,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_HTTPHEADER => array(
                "apiKey: ".self::$API_KEY,
                "ContentType: application/x-www-form-urlencoded",
                "Accept: application/json",
                "Cache-Control: no-cache",
            ),
            CURLOPT_POSTFIELDS => $postfields,
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);
        
        if ($err) {
            // Set error message and load appropriate view
            echo "<br/><p>Error! Something went wrong. Could not send message.\n".json_encode($err)."</p>";
            // Log the error
            log_message('error', json_encode($err));
        }
        
        if (isset($response)) log_message('debug', json_encode($response));

        return array('response' => $response, 'error' => $err);
    }
    
}