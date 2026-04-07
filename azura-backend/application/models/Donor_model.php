<?php defined('BASEPATH') OR exit('no direct script access allowed');

class Donor_model extends CI_Model{
    
    function donors($data){
        $this->db->insert('customer', $data);
        return $this->db->insert_id();
    }
    
    function getDonors($pesapal_merchant_reference){
      return  $this->db->get_where('customer', array('reference_number'=>$pesapal_merchant_reference))->result();
    }
    
    function getDonorByMeterNumber($meter_number) {
        return  $this->db->order_by("id", "meter_number")->limit(1)->get_where('customer', array('meter_number' => $meter_number))->row();
    }
    
    function parameters($arr){
      return  $this->db->insert('param', $arr);
    }
    
    function getParameters(){
        return $this->db->get('param')->row();
    }
    
    function track($data){
        log_message('debug', "Pesapal track: ".json_encode($data));
        echo "Pesapal track: ".json_encode($data)."<br/>";
        $track = $this->db->get_where('pesapal_track', $data)->result();
        log_message('debug', "Pesapal track: ".json_encode($track));
         echo "Pesapal track: ".json_encode($track)."<br/>";
        if (!isset($track) || empty($track)) $this->db->insert('pesapal_track', $data);
    }
    
    function insertParam($data){
        $this->db->where(array('id'=>1));
        $this->db->update('param',$data);
    }
    
}

