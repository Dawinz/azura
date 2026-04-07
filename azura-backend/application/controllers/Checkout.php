<?php defined('BASEPATH') or exit('No direct script access allowed');

class Checkout extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('cart_model');
        $this->load->model('payment_model');
        $this->load->library('form_validation');
    }

    public function index()
    {
        // Check if cart is not empty
        if ($this->cart_model->is_sale_cart_empty()) {
            redirect(lang_base_url() . "cart");
        }
        
        $data['title'] = trans("checkout");
        $data['description'] = trans("checkout") . " - " . $this->settings->site_title;
        $data['keywords'] = trans("checkout") . "," . $this->settings->application_name;
        
        $data['cart_items'] = $this->cart_model->get_sess_cart_items();
        $data['cart_total'] = $this->cart_model->get_sess_cart_total();
        
        // Initialize checkout steps
        $data['step'] = 1;
        $data['selected_payment_method'] = $this->session->userdata('selected_payment_method');
        
        $this->load->view('partials/_header', $data);
        $this->load->view('cart/selcom_checkout', $data);
        $this->load->view('partials/_footer');
    }
    
    public function payment_method_post()
    {
        // Validate form
        $this->form_validation->set_rules('payment_method', trans("payment_method"), 'required');
        
        if ($this->form_validation->run() === false) {
            $this->session->set_flashdata('errors', validation_errors());
            redirect($this->agent->referrer());
        } else {
            $payment_method = $this->input->post('payment_method');
            $this->session->set_userdata('selected_payment_method', $payment_method);
            
            // Redirect to next step
            redirect(lang_base_url() . "checkout/payment-details");
        }
    }
    
    public function payment_details()
    {
        // Check if payment method is selected
        $payment_method = $this->session->userdata('selected_payment_method');
        if (empty($payment_method)) {
            redirect(lang_base_url() . "checkout");
        }
        
        $data['title'] = trans("payment_details");
        $data['description'] = trans("payment_details") . " - " . $this->settings->site_title;
        $data['keywords'] = trans("payment_details") . "," . $this->settings->application_name;
        
        $data['cart_items'] = $this->cart_model->get_sess_cart_items();
        $data['cart_total'] = $this->cart_model->get_sess_cart_total();
        
        // Set checkout step
        $data['step'] = 2;
        $data['selected_payment_method'] = $payment_method;
        
        // For Selcom payment
        if ($payment_method == 'selcom') {
            $data['selcom_payment_option'] = $this->session->userdata('selcom_payment_option');
            $data['calling_code'] = $this->session->userdata('calling_code');
            $data['phone'] = $this->session->userdata('phone');
            $data['name'] = $this->session->userdata('name');
            $data['email'] = $this->session->userdata('email');
        }
        
        $this->load->view('partials/_header', $data);
        $this->load->view('cart/selcom_checkout', $data);
        $this->load->view('partials/_footer');
    }
    
    public function selcom_payment_post()
    {
        // Validate form
        $this->form_validation->set_rules('selcom_payment_option', trans("payment_option"), 'required');
        $this->form_validation->set_rules('calling_code', trans("calling_code"), 'required');
        $this->form_validation->set_rules('phone', trans("phone_number"), 'required');
        
        if ($this->input->post('selcom_payment_option') == 'CARD') {
            $this->form_validation->set_rules('name', trans("name"), 'required');
            $this->form_validation->set_rules('email', trans("email"), 'required|valid_email');
        }
        
        if ($this->form_validation->run() === false) {
            $this->session->set_flashdata('errors', validation_errors());
            redirect($this->agent->referrer());
        } else {
            // Save payment details to session
            $payment_data = array(
                'selcom_payment_option' => $this->input->post('selcom_payment_option'),
                'calling_code' => $this->input->post('calling_code'),
                'phone' => $this->input->post('phone'),
                'name' => $this->input->post('name'),
                'email' => $this->input->post('email')
            );
            $this->session->set_userdata($payment_data);
            
            // Process payment based on option
            if ($this->input->post('selcom_payment_option') == 'MOBILE') {
                // Process mobile payment
                $this->process_selcom_mobile_payment();
            } else {
                // Process card payment
                $this->process_selcom_card_payment();
            }
        }
    }
    
    private function process_selcom_mobile_payment()
    {
        // Get cart total
        $cart_total = $this->cart_model->get_sess_cart_total();
        $amount = $cart_total->total;
        
        // Generate order ID and transaction ID
        $order_id = time();
        $transid = time();
        
        // Get phone number
        $calling_code = $this->session->userdata('calling_code');
        $phone = $this->session->userdata('phone');
        $phone = $calling_code . $phone;
        
        // Initialize Selcom client
        $client = new Client($this->config->item('selcom_base_url'), $this->config->item('selcom_api_key'), $this->config->item('selcom_api_secret'));
        
        // Create callback URL
        $callback = base64_encode(base_url("checkout/selcom-callback?_transid=$transid&order_id=$order_id"));
        
        try {
            // Create order
            $response = $client->postFunc("/checkout/create-order-minimal",
                array(
                    "vendor" => $this->config->item('selcom_vendor_id'),
                    "order_id" => $order_id,
                    "buyer_email" => 'customer@example.com',
                    "buyer_name" => 'Customer',
                    "buyer_phone" => $phone,
                    "amount" => $amount,
                    "webhook" => $callback,
                    "currency" => "TZS",
                    "buyer_remarks" => $order_id,
                    "merchant_remarks" => $order_id,
                    "no_of_items" => 1
                )
            );
            
            if ($response && $response['result'] == 'SUCCESS') {
                // Save order to database
                $order_data = array(
                    'order_id' => $order_id,
                    'transid' => $transid,
                    'reference' => $response['reference'],
                    'payment_method' => 'selcom_mobile',
                    'buyer_phone' => $phone,
                    'amount' => $amount,
                    'resultcode' => $response['resultcode'],
                    'result' => $response['result'],
                    'message' => $response['message'],
                    'payment_token' => $response['data'][0]['payment_token'],
                    'payment_gateway_url' => $response['data'][0]['payment_gateway_url'],
                    'status' => 'pending'
                );
                $this->payment_model->add_selcom_order($order_data);
                
                // Send push request
                $push_response = $client->postFunc("/checkout/wallet-payment", array(
                        "transid" => $transid,
                        'order_id' => $order_id,
                        'msisdn' => $phone
                    )
                );
                
                if ($push_response && $push_response['result'] == 'SUCCESS') {
                    // Save push request to database
                    $push_data = array(
                        'order_id' => $order_id,
                        'reference' => $push_response['reference'],
                        'transid' => $push_response['transid'],
                        'resultcode' => $push_response['resultcode'],
                        'result' => $push_response['result'],
                        'message' => $push_response['message'],
                        'status' => 'pending'
                    );
                    $this->payment_model->add_selcom_push($push_data);
                    
                    // Redirect to payment processing page
                    redirect(lang_base_url() . "checkout/payment-processing");
                } else {
                    $error_message = isset($push_response['message']) ? $push_response['message'] : 'Unknown error';
                    $this->session->set_flashdata('error', $error_message);
                    redirect(lang_base_url() . "checkout/payment-details");
                }
            } else {
                $error_message = isset($response['message']) ? $response['message'] : 'Unknown error';
                $this->session->set_flashdata('error', $error_message);
                redirect(lang_base_url() . "checkout/payment-details");
            }
        } catch (Exception $e) {
            $this->session->set_flashdata('error', $e->getMessage());
            redirect(lang_base_url() . "checkout/payment-details");
        }
    }
    
    private function process_selcom_card_payment()
    {
        // Similar implementation for card payment
        // Would include redirect to Selcom payment gateway
    }
    
    public function payment_processing()
    {
        // Show payment processing page
        $data['title'] = trans("payment_processing");
        $data['description'] = trans("payment_processing") . " - " . $this->settings->site_title;
        $data['keywords'] = trans("payment_processing") . "," . $this->settings->application_name;
        
        $data['cart_items'] = $this->cart_model->get_sess_cart_items();
        $data['cart_total'] = $this->cart_model->get_sess_cart_total();
        
        // Set checkout step
        $data['step'] = 3;
        $data['selected_payment_method'] = $this->session->userdata('selected_payment_method');
        $data['selcom_payment_option'] = $this->session->userdata('selcom_payment_option');
        
        // For Selcom card payment, get gateway URL from session
        if ($data['selected_payment_method'] == 'selcom' && $data['selcom_payment_option'] == 'CARD') {
            $data['payment_gateway_url'] = $this->session->userdata('payment_gateway_url');
        }
        
        $this->load->view('partials/_header', $data);
        $this->load->view('cart/selcom_checkout', $data);
        $this->load->view('partials/_footer');
    }
    
    public function selcom_callback()
    {
        // Handle Selcom callback
        $transid = $this->input->get('_transid');
        $order_id = $this->input->get('order_id');
        
        // Verify payment status with Selcom
        $client = new Client($this->config->item('selcom_base_url'), $this->config->item('selcom_api_key'), $this->config->item('selcom_api_secret'));
        
        try {
            $response = $client->getFunc("/checkout/order-status?order_id=" . $order_id);
            
            if ($response && $response['result'] == 'SUCCESS') {
                // Update order status in database
                $this->payment_model->update_selcom_order($order_id, array(
                    'status' => strtolower($response['data'][0]['payment_status'])
                ));
                
                if (strtolower($response['data'][0]['payment_status']) == 'completed') {
                    // Create sale and redirect to thank you page
                    $this->create_sale($order_id);
                    redirect(lang_base_url() . "order-completed/" . $order_id);
                } else {
                    // Payment failed
                    redirect(lang_base_url() . "checkout/payment-failed");
                }
            } else {
                // Error checking payment status
                redirect(lang_base_url() . "checkout/payment-failed");
            }
        } catch (Exception $e) {
            redirect(lang_base_url() . "checkout/payment-failed");
        }
    }
    
    public function check_payment_status()
    {
        // AJAX endpoint for checking payment status
        $order_id = $this->input->post('order_id');
        
        // Get order from database
        $order = $this->payment_model->get_selcom_order($order_id);
        
        if ($order) {
            if ($order->status == 'completed') {
                echo json_encode(array('status' => 'success'));
                return;
            } elseif ($order->status == 'failed') {
                echo json_encode(array('status' => 'failed'));
                return;
            }
        }
        
        echo json_encode(array('status' => 'pending'));
    }
    
    // Other methods for YoqPay and Cash on Delivery would go here
}