<?php defined('BASEPATH') or exit('No direct script access allowed');

date_default_timezone_set('Africa/Dar_Es_Salaam');

// Umeme API class
class UmemeAPI {
    
    //private static $API_URL = 'http://60.205.216.142:8086/api/EKPower/EKPower';
    // private static $API_URL = 'http://120.26.4.119:9110';
    private static $API_URL = 'http://120.26.4.119:9094';
    
    
    public static function decode_response($response) {
        if (is_string($response)) {
            $response = json_decode($response, true);
            if (is_string($response)) $response = json_decode($response, true);
            return (object) $response;
        } else return (object) $response;
    }
    
    public static function verify($meter, $username, $password) {
        
        echo "<br/><p>verify meter: $meter, $username, $password </p>";
        log_message('debug', "verify meter: $meter, $username, $password");
        
        // Build the API URL with parameters
        $data = http_build_query([
            'U' => $username,
            'K' => $password,
            'type' => 'e',
            'meter' => $meter,
            'OP' => 'verify',
        ]);
        // $data = http_build_query([
        //     'UserId' => $username,
        //     'Password' => $password,
        //     'MeterType' => 1,
        //     'MeterCode' => $meter
        // ]);
        
        // Initialize cURL
        $curl = curl_init();
        curl_setopt_array($curl, [
            // CURLOPT_URL => self::$API_URL,
            CURLOPT_URL => self::$API_URL."/api/Power/GetContractInfo?UserId=$username&Password=$password&MeterType=1&MeterCode=$meter",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            // CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_CUSTOMREQUEST => "GET",
            // CURLOPT_POSTFIELDS => $data,
            CURLOPT_HTTPHEADER => [
                "Content-Type: application/x-www-form-urlencoded",
                "Accept: application/json",
                "Cache-Control: no-cache",
            ],
        ]);
        
        echo "<br/><p> verify meter: $data </p>";
        log_message('debug', "verify meter: $data");
        
        // Execute the cURL request
        $response = curl_exec($curl);
        $err = curl_error($curl);
        
        // Close cURL
        curl_close($curl);
        
        // Check for error or success
        if ($err) {
            // Set error message and load appropriate view
            echo "<b/><p>Error! Something went wrong. Could not process electricity purchase. " . json_encode($err) . "</p>";
            log_message('error', json_encode($err));
            return false;
        } else {
            // Decode response
            $_response = self::decode_response($response);
            
            // Log the response
            echo "<br/><p>".json_encode($response)."</p>";
            log_message('debug', json_encode($response));
            
            // Check the return code
            if ($_response->Code != 200) {
                // Set error message and load appropriate view
                log_message('error', $_response->Message);
                echo "<br/><p>$_response->Message</p>";
                return false;
            } else {
                // Check if the meter exists
                if (isset($_response->Data) && !empty($_response->Data)) {
                    // Meter exists. Return the response data
                    return (object) array_merge($_response->Data, array("Code" => $_response->Code, "Message" => $_response->Message));
                } else return false; // Meter does not exists
                
            }
            
        }
        
    }
    
    public static function vend($meter, $amount, $username, $password) {
        
        echo "<br/><p> vend electricity: $meter, $amount, $username, $password </p>";
        log_message('debug', "vend electricity: $meter, $amount, $username, $password");
        
        // Prepare data for the second API
        $data = http_build_query([
            'U' => $username,
            'K' => $password,
            'type' => 'e',
            'meter' => $meter,
            'OP' => 'vend',
            'Amount' => $amount
        ]);
        // $data = http_build_query([
        //     'UserId' => $username,
        //     'Password' => $password,
        //     'MeterType' => 1,
        //     'MeterCode' => $meter,
        //     'AmountOrQuantity' => $amount,
        //     'VendingType' => 0
        // ]);
        
        echo "<br/><p> vend electricity: $data </p>";
        log_message('debug', "vend electricity: $data");

        // Send request to the second API
        $curl = curl_init();
        curl_setopt_array($curl, [
            // CURLOPT_URL => self::$API_URL,
            CURLOPT_URL => self::$API_URL."/api/Power/GetVendingToken?UserId=$username&Password=$password&MeterType=1&MeterCode=$meter&AmountOrQuantity=$amount&VendingType=0",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            // CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_CUSTOMREQUEST => 'GET',
            // CURLOPT_POSTFIELDS => $data,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/x-www-form-urlencoded',
                "Accept: application/json",
                "Cache-Control: no-cache",
            ],
        ]);
        
        // Execute the cURL request
        $response = curl_exec($curl);
        $err = curl_error($curl);
        
        // Close cURL
        curl_close($curl);
        
        // Check for error or success
        if ($err) {
            // Set error message and load appropriate view
            echo "<br/><p>Error! Something went wrong. Could not process electricity purchase.\n".json_encode($err)."</p>";
            // Log the error
            log_message('error', json_encode($err));
            // Return false
            return false;
        } else {
            // Decode response
            $_response = self::decode_response($response);
            
            // Log the response
            echo "<br/><p>".json_encode($response)."</p>";
            log_message('debug', json_encode($response));
            
            // Check the return code
            if ($_response->Code != 200) {
                // Set error message and load appropriate view
                log_message('error', $_response->Message);
                echo "<br/><p>$_response->Message</p>";
                return false;
            } else {
                // Check if vending was successful
                if (isset($_response->Data) && !empty($_response->Data)) {
                    // Return the response data
                    return (object) array_merge($_response->Data, array("Code" => $_response->Code, "Message" => $_response->Message));
                } else return false; // Vending unsuccessful
            }
            
        }
        
    }
    
}