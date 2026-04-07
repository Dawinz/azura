<?php

defined('BASEPATH') OR exit('No direct script access allowed');

date_default_timezone_set('Africa/Dar_Es_Salaam');


class Callback extends CI_Controller {
    
    private $API_KEY = "aYFAkyz3411ESieE0Ff10nP0Hv/FG75PDb9OjmNgBm96-aMD2qitEJeF8FFV98Fv9FDbRXCY94Dy9NZxY414c1FtsaENOC13UkG9ASlu5/4Gf4Ad3L254-55h69Efa4U";

    public function __construct() {
        parent::__construct();
        
        $this->load->model('donor_model');
        $this->load->model('pesaSend');
    }

    public function index() {
        
        // Retrieve the POST data
        $post_data = file_get_contents("php://input");
        $data = json_decode($post_data);
        $api_key = urldecode($this->input->get('api_key'));
        $transid = $this->input->get('_transid');
        $reference_id = $this->input->get('reference_id');
        $reference_number = $this->input->get('reference_number');
        
        // Log response
        log_message('debug', 'Selcom Callback: ' . $post_data . ' ' . json_encode($this->input->get()));
        
        if ($api_key == $this->API_KEY) {

            if ($data && ($data->result === "SUCCESS" || $data->resultcode === "000")) {
                
                // Insert data into the MySQL transaction table
                $transaction_data = array(
                    'order_id' => $data->order_id,
                    'transid' => $data->transid,
                    '_transid' => $transid,
                    'reference' => $data->reference,
                    'reference_id' => $reference_id,
                    'reference_number' => $reference_number,
                    'channel' => $data->channel,
                    'amount' => $data->amount,
                    'phone' => $data->phone,
                    'payment_status' => $data->payment_status
                );
                
                // Log response
                log_message('debug', 'Selcom Callback: Transaction status success: ' . $post_data . ' ' . json_encode($this->input->get()) . ' ' . json_encode($transaction_data) );
    
                //  $this->Transaction_model->insert_transaction($transaction_data);
    			$tableName = 'selcom_payments';
                $this->db->insert($tableName, $transaction_data);
                
                // update transactrion status
                if ($data->payment_status == 'SUCCESS' || $data->payment_status == 'COMPLETED') {
                
                    // Retrieve order
                    $query = $this->db->get_where('selcom_orders', array('order_id' => $data->order_id, 'transid' => $transid, 'reference_id' => $reference_id, 'reference_number' => $reference_number));
                    $order = $query->row();
                    
                    // Log response
                    log_message('debug', 'Selcom Callback: Transaction order : ' . json_encode($order));
                    
                    if ($order && isset($order) && !empty($order)) {
                        
                        // Retrive customer
                        $donorI = $this->donor_model->getDonors($order->reference_number);
                        if (isset($donorI) && count($donorI)) {
                            
                            $donorInfo = $donorI[0];
                            
                            // Log response
                            log_message('debug', 'Selcom Callback: Transaction customer :: ' . json_encode($donorInfo));
                            
                            // Perform Electricity Purchase Transaction
                            $transData = $this->pesaSend->transData($donorInfo->first_name, $donorInfo->last_name, $donorInfo->calling_code, $donorInfo->phone, $donorInfo->created_at, $donorInfo->amount, $donorInfo->meter_number, $data->reference, $order->reference_number, $data->payment_status);
                            
                        }
                        
                    }
                    
                }
                
            } else {
                // Handle the callback failure
                log_message('error', 'Selcom Callback: Transaction status error :: ' . json_encode($data));
            }
            
        }
        
    }
}
