<?php defined('BASEPATH') OR exit('no direct script access allowed');

 class Transactions_model extends CI_Model{
     
     function trans($payment_status){
         $this->db->insert('pesapal_track', $payment_status);
     }
     
 }

