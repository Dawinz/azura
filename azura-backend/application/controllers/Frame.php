<?php

defined('BASEPATH') OR exit('no direct script access allowed');

date_default_timezone_set('Africa/Dar_Es_Salaam');

class Frame extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('donor_model');
    }

    function index() {
        
        $this->load->helper('url');
        
        $data = $this->input->get();
        
        $this->form_validation->set_data($data);
        $this->form_validation->set_rules("reference_number", "Reference Number", "required");
        
        if ($this->form_validation->run()) {
            
            // Retrive customer
            $donorI = $this->donor_model->getDonors($data['reference_number']);
            if (isset($donorI) && count($donorI)) {
                
                $donorInfo = $donorI[0];
                
                if (isset($donorInfo) && !empty($donorInfo)) {
                    
                    // Set flash data for view
                    $this->session->set_flashdata('reference_id', $donorInfo->id);
                    $this->session->set_flashdata('reference_number', $donorInfo->reference_number);
                    $name = $this->session->flashdata('name');
                    $this->session->set_flashdata('name', $name);
                    $this->session->set_flashdata('calling_code', $donorInfo->calling_code);
                    $this->session->set_flashdata('phone', $donorInfo->phone);
                    $this->session->set_flashdata('amount', $donorInfo->amount);
                    $this->session->set_flashdata('meter_number', $donorInfo->meter_number);
                    
                    $data = (array) $donorInfo;
                    $data['name'] = $name;
                    $data['description'] = $donorInfo->meter_number;
                    $data['param']= $this->donor_model->getParameters();
            
                    $this->load->view('header');
                    // $this->load->view('pesapal-iframe', $data);
                    $this->load->view('selcom-iframe', $data);
                    $this->load->view('footer');
                    
                } else redirect(base_url());
                
            } else redirect(base_url());
            
        } else redirect(base_url());
        
    }

}
