<?php defined('BASEPATH') or exit('No direct script access allowed');

date_default_timezone_set('Africa/Dar_Es_Salaam');

require_once('ATAPI.php');
require_once('KiliSMSAPI.php');
require_once('BeemAfricaAPI.php');


// SMS API class
class SMSAPI {
    
    /** Main send method */
    public static function send($to, $message, $from = null) {
        return KiliSMSAPI::send($to, $message, $from);
        // return ATAPI::post('messaging', array('to' => $to, 'message' => $message, 'from' => $from));
    }
    
    /** Fallback send method */
    public static function sendFallback($to, $message, $from = null) {
        return BeemAfricaAPI::send($to, $message, $from);
    }

}