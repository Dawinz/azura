<?php

// BeemAfrica API class
class BeemAfricaAPI {
    
    // private static $API_KEY = 'dc882b4d52777d43';
    private static $API_KEY = '11e092d89ffa0a09';
    private static $SECRET_KEY = 'NzFkZTliMTkyZmJjNGE1MmViMTBjY2VhMmU0NzQ5YjVkZGJmNjUwNDBjMGZmZjZmNTcxOTk2M2RjYzZjNzg2MA==';
    // private static $SECRET_KEY = 'OWQ5YjAwYzkyYjE5YzRiZTNmOWU1MDRmMjRiNjM2M2ZhNjk3NDBiOWYyNjNlOGNjY2JjYmU0OGQ3YzAyOGJjMg==';
    private static $API_URL = 'https://apisms.beem.africa/v1/send';
    private static $DEFAULT_SENDER_ID = 'Vido App';

    public static function send($phone, $message, $senderid = null) {
        
        // Set default sender ID
        if (!$senderid) $senderid = self::$DEFAULT_SENDER_ID;
        
        echo "<br/><p>send message: $phone, $message, $senderid</p>";
        log_message('debug', "send message: $phone, $message, $senderid");
        
        // Prepare Post Data
        $postData = [
            'source_addr' => $senderid,
            'encoding' => 0,
            'schedule_time' => '',
            'message' => $message,
            'recipients' => [['recipient_id' => 1, 'dest_addr' => $phone]]
        ];
        
        // Initialize cURL request
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => self::$API_URL,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_HTTPHEADER => [
                'Authorization: Basic ' . base64_encode(self::$API_KEY.':'.self::$SECRET_KEY),
                "ContentType: application/x-www-form-urlencoded",
                "Accept: application/json",
                "Cache-Control: no-cache"
            ],
            CURLOPT_POSTFIELDS => json_encode($postData)
        ));
        
        // Execute cURL request
        $response = curl_exec($curl);
        $err = curl_error($curl);
        
        // Close request
        curl_close($curl);
        
        if ($err) {
            // Set error message and load appropriate view
            echo "<br/><p>Error! Something went wrong. Could not send message.\n".json_encode($err)."</p>";
            // Log the error
            log_message('error', json_encode($err));
        }
        
        if (isset($response)) log_message('debug', json_encode($response));
        
        // Return response
        return array('response' => $response, 'error' => $err);
        
    }
    
}