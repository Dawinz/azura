<?php

defined('BASEPATH') OR exit('no direct script access allowed');

date_default_timezone_set('Africa/Dar_Es_Salaam');

require_once(APPPATH . 'libraries/UmemeAPI.php');
require_once(APPPATH . 'libraries/SMSAPI.php');
date_default_timezone_set('Africa/Dar_Es_Salaam');


class PesaSend extends CI_Model {
    
    private $FEE_RATE = .1;
    
    private $SENDER_ID = 'Rahisi';
    // private $SENDER_ID = 'E-Mazao';
    // private $SENDER_ID = 'LEZA';
    
    private $MESSAGES = array(
         // "swahili" => "Hongera. Umefanikiwa kununua %s kWh kwa kiasi TZS%s kwa ajili ya mita %s. Tarakimu maalumu za kuwekea umeme wako ni %s. Kumbukumbu namba yako ni %s. Asante Kwa kutumia Rahisi Recharge.",
        // "english" => "Congratulation. You have successfully bought %s kWh for TZS%s for the meter %s. Your electricity token is %s. Your reference number is %s. Thank you for using Rahisi Recharge.",
        "swahili" => "Kumb: %s.\n Kiasi: TZS%s.\n Nishati: %sKWH.\n Mita: %s.\n Tarikimu: %s.%s\n Asante kwa kutumia Rahisi Recharge.",
        "english" => "Ref: %s.\n Amount: TZS%s.\n Power: %sKWH.\n Meter: %s.\n Token: %s.%s\n Thank you for using Rahisi Recharge."
    );
    
    private $FOOTER_MESSAGES = array(
        "swahili" => "\n Kwanza, tumia namba ishara mbili za mwanzo.",
        "english" => "\n Firstly, use the first two tokens."
    );

    public function transData($first_name, $last_name, $calling_code, $phone, $date, $amount, $meter_number, $pesapal_transaction_tracking_id, $pesapal_merchant_reference, $status) {
        
        echo "<br/><p>PesaSend transData: $first_name, $last_name, $calling_code, $phone, $date, $amount, $meter_number, $pesapal_transaction_tracking_id, $pesapal_merchant_reference, $status</p>";
        log_message('debug', "PesaSend transData: $first_name, $last_name, $calling_code, $phone, $date, $amount, $meter_number, $pesapal_transaction_tracking_id, $pesapal_merchant_reference, $status");
        
        // Get original customer details
        $query = $this->db->get_where('customer', array('reference_number' => $pesapal_merchant_reference, "meter_number" => '00'.intval($meter_number)));
        $customer = $query->row();
        
        // Check if the customer exists
        if (isset($customer) && !empty($customer))
            // Call the UmemeAPI function with the meter number amount, phone and reference_number
            return $this->vend($meter_number, $amount, $customer->calling_code, $customer->phone, $customer->sts_account, $pesapal_merchant_reference, $pesapal_transaction_tracking_id, $status);
        else {
            log_message('debug', "No Customer details found for reference number: " . $pesapal_merchant_reference);
            return false;
        }
    }
    
    public function dispatchToken($calling_code, $phone, $token, $meter_number, $tarrif, $service_charge, $amount, $reference_number, $quantity) {
            
        // Prepare Message for dispatch
        $message = $this->prepare_message((object) array(
            'Token' => $token,
            'MeterCode' => $meter_number,
            'Tarrif' => $tarrif,
            'ServiceCharge' => $service_charge
        ), $amount, $reference_number, $this->session->userdata('site_lang'), $quantity);

        // Trim and sanitize the phone number
        $phone = intval(trim($phone));
        $calling_code = (isset($calling_code) && $calling_code)? $calling_code : "255";
        $_phone = $calling_code . $phone;
        if (strpos($_phone, $calling_code) != 0) {
            $_phone = substr($_phone, -9, 9);
            $_phone = $calling_code . ltrim($_phone, '0');
        }
        
        // Send the SMS using the updated phone number and message
        $text0 = null;
        $text0 = $this->sendMessage($_phone, $message);
        $text1 = null;
        if (!$text0) $text1 = $this->sendMessageFallback($_phone, $message);
        
        // Update send message status
        $this->db->where('reference_number', $reference_number);
        $this->db->update('payment', array( "message_status" => ($text0 || $text1)? 1 : 0 ));
        
        return ($text0 || $text1);
        
    }
    
    public function getFeeRate() {
        return $this->FEE_RATE;
    }
    
    //---------- Function to vend electricity -------------------
    private function vend($meter, $amount, $calling_code, $phone, $sts_account, $reference_number, $transaction_code, $status) {
        
        echo "<br/><p>PesaSend vend: $meter, $amount, $calling_code, $phone, $sts_account, $reference_number, $transaction_code, $status</p>";
        log_message('debug', "PesaSend vend: $meter, $amount, $calling_code, $phone, $sts_account, $reference_number, $transaction_code, $status");
        
        // Check database to ensure no repetition of purchase
        $query = $this->db->get_where('payment', array('reference_number' => $reference_number));
        $result = $query->row();
        
        // There should be no records of the transaction when buying electricity to avoid double purchase
        if (!isset($result) || empty($result)) {
            
            // Get STS Account for Meter
            // Load models
            $CI =& get_instance();
            $CI->load->model('stsAccount');
            // Get sts account
            $sts_account = $this->stsAccount->getAccount($sts_account);
            if (isset($sts_account) && !empty($sts_account)) {
                
                // Adjust the amount
                $adjusted_amount = $amount * (1 - $this->FEE_RATE);
                
                // Vend electricity
                $_response = (object) UmemeAPI::vend($meter, $adjusted_amount, $sts_account->username, $sts_account->password);
                
                // Check if vending was successful
                if ($_response) {
                    
                    // Prepare Message for dispatch
                    $message = $this->prepare_message($_response, $amount, $reference_number, $this->session->userdata('site_lang'), $_response->VendingQuantity);
            
                    // Trim and sanitize the phone number
                    $phone = intval(trim($phone));
                    $calling_code = (isset($calling_code) && $calling_code)? $calling_code : "255";
                    $_phone = $calling_code . $phone;
                    if (strpos($_phone, $calling_code) != 0) {
                        $_phone = substr($_phone, -9, 9);
                        $_phone = $calling_code . ltrim($_phone, '0');
                    }
                    
                    // Send the SMS using the updated phone number and message
                    $text0 = null;
                    $text0 = $this->sendMessage($_phone, $message);
                    $text1 = null;
                    if (!$text0) $text1 = $this->sendMessageFallback($_phone, $message);
                    
                    // Define the data to be inserted into the 'payment' table
                    $data = array(
                        'reference_number' => $reference_number,
                        'meter_number' => $_response->MeterCode,
                        'token' => $_response->Token,
                        'tarrif' => $_response->Tarrif,
                        'service_charge' => $_response->ServiceCharge,
                        'calling_code' => $calling_code,
                        'phone' => $phone,
                        'amount' => $amount,
                        'adjusted_amount' => $adjusted_amount,
                        'quantity' => $_response->VendingQuantity,
                        'return_code' => $_response->Code,
                        'return_message' => $_response->Message,
                        "message_status" => ($text0 || $text1)? 1 : 0,
                        "transaction_code" => $transaction_code,
                        "status" => $status
                    );
                    
                    // Insert the data into the 'payment' table
                    $this->db->insert('payment', $data);
                    
                    return true;
                    
                } else {
                    log_message('debug', "Electricity vending failed!");
                    return false;
                }
                
            } else {
                log_message('debug', "Customer Meter $meter not verified!");
                return false;
            }
            
        } else {
            
            log_message('debug', "Duplicate Transaction. Transaction with reference number $reference_number already processed!");
            
            // Check whether transaction message was sent successfully
            if (!$result->message_status)
                $this->dispatchToken($calling_code, $phone, $result->token, $result->meter_number, $result->tarrif, $result->service_charge, $amount, $reference_number, $result->quantity);
            
            return true;
            
        }
        
    }
    
    //---------- Function to prepare sms -------------------
    private function prepare_message($response, $amount, $reference_number, $language = 'english', $quantity = 0) {
        // Set default $language
        $language = ($language != "") ? $language : "english";
        // Prepare SMS for sending
        // Get data
        $data = UmemeAPI::decode_response($response);
        // Get price
        $price = floatval(trim(substr($data->Tarrif, 0, strpos($data->Tarrif, 'per unit'))));
        // Prepare token
        $token = null;
        if (strpos($data->Token, ';')) {
            $token = explode(';', $data->Token);
            $data->Token = implode('; ', $token);
        }
        // Concat message
        return sprintf($this->MESSAGES[$language], $reference_number, $amount, !$quantity? round(($amount * (1 - $this->FEE_RATE)) / $price, 1) : $quantity, $data->MeterCode, $data->Token, ($token && isset($token) && !empty($token))? $this->FOOTER_MESSAGES[$language] : '');
    }
    
    //---------- Function to send sms -------------------
    private function sendMessage($phone, $message) {
        
        echo "<br/><p>PesaSend sendMessage: $phone, $message</p>";
        log_message('debug', "PesaSend sendMessage: $phone, $message");
        
	    // Format phone number
	    // $phone = '+255' . substr($phone, -9, 9);
	    $phone = str_replace("++", "+", "+".$phone);
	    $phone = str_replace("++", "+", $phone);
	    // Send token to phone number
	    $_sendToken = SMSAPI::send($phone, $message, $this->SENDER_ID);
	    
	    echo "<br/><p>PesaSend sendMessage response: ". json_encode($_sendToken) . "</p>";
        log_message('debug', "PesaSend sendMessage response: " . json_encode($_sendToken));
            
	    // Check if message was sent successfully
    	if (isset($_sendToken['response'])) {
    		$sendToken = is_string($_sendToken['response'])? json_decode($_sendToken['response']) : $_sendToken['response'];
    		
    		echo "<br/><p>PesaSend sendMessage response: ". json_encode($sendToken) . "</p>";
            log_message('debug', "PesaSend sendMessage response: " . json_encode($sendToken));
    		
    		if ($sendToken && isset($sendToken) && !empty($sendToken) && ($sendToken->id || ($sendToken->SMSMessageData && $sendToken->SMSMessageData->Recipients[0]->statusCode == 101))) return true;
    		else {
    			log_message('debug', "Oops, No messages were sent. ErrorMessage: ".json_encode($sendToken));
    			return false;
    		}
    		
    	} else {
			log_message('debug', "Oops, No messages were sent. ErrorMessage: ".json_encode($_sendToken['error']));
			return false;
		}
		
	}
	
	//---------- Fallback Function to send sms -------------------
	private function sendMessageFallback($phone, $message) {
	    
	    echo "<br/><p>PesaSend sendMessageFallback: $phone, $message</p>";
        log_message('debug', "PesaSend sendMessageFallback: $phone, $message");
        
        // Send token to phone number
	    $_sendToken = SMSAPI::sendFallback($phone, $message/**, $this->SENDER_ID*/);
	    
	    echo "<br/><p>PesaSend sendMessageFallback response: ". json_encode($_sendToken) . "</p>";
        log_message('debug', "PesaSend sendMessageFallback response: " . json_encode($_sendToken));
        
	    // Check if message was sent successfully
	    if (isset($_sendToken['response'])) {
	        $sendToken = is_string($_sendToken['response'])? json_decode($_sendToken['response']) : $_sendToken['response'];
	        
	        echo "<br/><p>PesaSend sendMessageFallback response: ". json_encode($_sendToken) . "</p>";
            log_message('debug', "PesaSend sendMessageFallback response: " . json_encode($_sendToken));
	        
            if ($sendToken && isset($sendToken) && !empty($sendToken) && $sendToken->code == 100) return true;
            else {
                log_message('debug', "Oops, No messages were sent. ErrorMessage: ".json_encode($sendToken));
    			return false;
            }
	    } else {
		    log_message('debug', "Oops, No messages were sent. ErrorMessage: ".json_encode($_sendToken['error']));
			return false;
		}
		
    }

}
