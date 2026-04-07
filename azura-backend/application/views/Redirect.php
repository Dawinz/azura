<?php
defined('BASEPATH') OR exit('No direct script access allowed');

date_default_timezone_set('Africa/Dar_Es_Salaam');

class Redirect extends CI_Controller {
    
    private $API_KEY = "aYFAkyz3411ESieE0Ff10nP0Hv/FG75PDb9OjmNgBm96-aMD2qitEJeF8FFV98Fv9FDbRXCY94Dy9NZxY414c1FtsaENOC13UkG9ASlu5/4Gf4Ad3L254-55h69Efa4U";

    public function __construct() {
        parent::__construct();
        $this->load->model('donor_model');
        $this->load->model('pesaSend');
    }

    public function index() {
        
        $this->load->helper('url');
        
        $data = $this->input->get();
            
        $this->form_validation->set_data($data);
        $this->form_validation->set_rules("api_key", "API KEY", "required");
        $this->form_validation->set_rules("order_id", "Order ID", "required");
        $this->form_validation->set_rules("transid", "Transaction ID", "required");
        $this->form_validation->set_rules("reference_id", "Reference ID", "required");
        $this->form_validation->set_rules("reference_number", "Reference Number", "required");
        
        // Log response
        log_message('debug', 'Selcom Redirect : ' . json_encode($data));
        
        if ($this->form_validation->run()) {
            
            $api_key = urldecode($this->input->get('api_key'));
            if ($api_key == $this->API_KEY) {
                 
                // Retrieve order
                $query = $this->db->get_where('selcom_orders', $data);
                $order = $query->row();
                
                // Log response
                log_message('debug', 'Selcom Redirect: Transaction order :: ' . json_encode($order));
                
                // Check whether payment has already been done for similar refernce number and reference id
                // Retrieve payment details from the database
                $query = $this->db->get_where('selcom_payments', array( 'order_id' => $data->order_id, '_transid' => $data->transid, 'reference_id' => $reference_id, 'reference_number' => $reference_number));
                $result = $query->row();
                
                if ($order && isset($order) && !empty($order)) {
                    
                    // Retrive customer
                    $donorI = $this->donor_model->getDonors($order->reference_number);
                    if (isset($donorI) && count($donorI)) {
                        
                        $donorInfo = $donorI[0];
                        
                        // Log response
                        log_message('debug', 'Selcom Redirect: Transaction customer :: ' . json_encode($donorInfo));
                        
                        // Perform Electricity Purchase Transaction
                        $transData = $this->pesaSend->transData($donorInfo->first_name, $donorInfo->last_name, $donorInfo->calling_code, $donorInfo->phone, $donorInfo->created_at, $donorInfo->amount, $donorInfo->meter_number, ($result && isset($result) && !empty($result) && $result->reference)? $result->reference : $order->reference, $order->reference_number, $data->payment_status);
                        
                        if ($transData) {
                            
                            // Set flash data for view
                            $this->session->set_flashdata('order_id', $order->order_id);
                            $this->session->set_flashdata('transid', $order->transid);
                            $this->session->set_flashdata('reference', $order->reference);
                            $this->session->set_flashdata('reference_id', $order->reference_id);
                            $this->session->set_flashdata('reference_number', $order->reference_number);
                            $this->session->set_flashdata('meter_number', $donorInfo->meter_number);
                            $this->session->set_flashdata('calling_code', $donorInfo->calling_code);
                            $this->session->set_flashdata('phone', $donorInfo->phone);
                            
                            redirect(base_url("thankyou?order_id=$order->order_id&transid=$order->transid&reference=$order->reference&reference_id=$order->reference_id&reference_number=$order->reference_number&meter_number=$donorInfo->meter_number&calling_code=$donorInfo->calling_code&phone=$donorInfo->phone"));
                            
                        } else redirect(base_url('error'));
                        
                    } else redirect(base_url('error'));
                    
                } else redirect(base_url('error'));
            
                
            } else redirect(base_url('error'));
            
        } else redirect(base_url('error'));
        
    }
    
}