<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Pay extends CI_Controller {

    // private $baseUrl;
    private $apiKey;
    private $apiSecret;
    private $vendorId;
    private $API_KEY;
    
    // private $apiKey = "TILL61101307-622d47ebdfbc44fbb3f82dd5a13e3844";
    // private $apiSecret = "5295a9-366a08-4ee2b3-177787-0c3b93-9c";
    // private $baseUrl = "https://apigw.selcommobile.com/v1";
    // private $vendorId = "TILL61101307";
    // private $API_KEY = "aYFAkyz3411ESieE0Ff10nP0Hv/FG75PDb9OjmNgBm96-aMD2qitEJeF8FFV98Fv9FDbRXCY94Dy9NZxY414c1FtsaENOC13UkG9ASlu5/4Gf4Ad3L254-55h69Efa4U";


    public function __construct() {
        parent::__construct();
        $this->load->library('form_validation');
        $this->load->database();
        $this->load->helper('url');
        
        // // Initialize Selcom API credentials
        $this->baseUrl = 'https://apigw.selcommobile.com/v1'; // Update with actual Selcom API URL
        $this->apiKey = 'TILL61101307-622d47ebdfbc44fbb3f82dd5a13e3844';
        $this->apiSecret = '5295a9-366a08-4ee2b3-177787-0c3b93-9c';
        $this->vendorId = 'TILL61101307';
        $this->API_KEY = 'aYFAkyz3411ESieE0Ff10nP0Hv/FG75PDb9OjmNgBm96-aMD2qitEJeF8FFV98Fv9FDbRXCY94Dy9NZxY414c1FtsaENOC13UkG9ASlu5/4Gf4Ad3L254-55h69Efa4U'; // For internal use
    }

    public function index() {
        $this->form_validation->set_rules("payment_methods", "Payment Methods", "required");
        $this->form_validation->set_rules("calling_code", "Calling Code", "required");
        $this->form_validation->set_rules("phone", "Phone Number", "required");

        $reference_number = $this->input->post('reference_number');

        if (!$this->form_validation->run()) {
            log_message('debug', 'Transaction failed: validation errors');
            redirect(base_url("frame?reference_number={$reference_number}"));
            return;
        }

        // Get data
        $payment_methods = $this->input->post('payment_methods');
        $calling_code = $this->input->post('calling_code') ?: "255";
        $phone = $this->input->post('phone');
        $order_id = time();
        $transid = time();
        $amount = $this->input->post('amount');
        $reference_id = $this->input->post('reference_id');
        $reference_number = $this->input->post('reference_number');
        $meter_number = $this->input->post('meter_number');

        // Flash data
        $this->session->set_flashdata([
            'payment_methods' => $payment_methods,
            'calling_code' => $calling_code,
            'phone' => $phone,
            'amount' => $amount,
            'reference_id' => $reference_id,
            'reference_number' => $reference_number,
            'meter_number' => $meter_number,
        ]);

        // Check existing payment
        $query = $this->db->get_where('selcom_payments', [
            'reference_id' => $reference_id,
            'reference_number' => $reference_number
        ]);
        $result = $query->row();

        if (!$result) {
            // Normalize phone
            $phone = intval(trim($phone));
            $_phone = $calling_code . $phone;
            if (strpos($_phone, $calling_code) !== 0) {
                $_phone = substr($_phone, -9);
                $_phone = $calling_code . ltrim($_phone, '0');
            }

            // Client init
            $client = new Client($this->baseUrl, $this->apiKey, $this->apiSecret);
            $callback = base64_encode(base_url("callback?_transid={$transid}&reference_id={$reference_id}&reference_number={$reference_number}&api_key=" . urlencode($this->API_KEY)));
            $cancel = base64_encode(base_url("cancel?order_id={$order_id}&transid={$transid}&reference_id={$reference_id}&reference_number={$reference_number}&api_key=" . urlencode($this->API_KEY)));

            if ($payment_methods === 'MOBILE') {
                try {
                    $response = $client->postFunc("/v1/checkout/create-order-minimal", [
                        "vendor" => $this->vendorId,
                        "order_id" => $order_id,
                        "buyer_email" => 'info@azuramall.co.tz',
                        "buyer_name" => 'Azura Mall',
                        "buyer_phone" => $_phone,
                        "amount" => $amount,
                        "webhook" => $callback,
                        "currency" => "TZS",
                        "buyer_remarks" => $order_id,
                        "merchant_remarks" => $order_id,
                        "no_of_items" => 1
                    ]);

                    if (empty($response) || $response['result'] !== 'SUCCESS') {
                        log_message('debug', 'Payment API Error (create-order-minimal): ' . json_encode($response));
                        $this->session->set_flashdata('payment_error', 'Transaction failed. Please try again.');
                        redirect(base_url("frame?reference_number={$reference_number}"));
                        return;
                    }

                    // Record order
                    $this->db->insert('selcom_orders', [
                        'order_id' => $order_id,
                        'transid' => $transid,
                        'reference' => $response['reference'],
                        'payment_methods' => $payment_methods,
                        "buyer_phone" => $_phone,
                        'resultcode' => $response['resultcode'],
                        'result' => $response['result'],
                        'message' => $response['message'],
                        'payment_token' => $response['data'][0]['payment_token'],
                        'payment_gateway_url' => $response['data'][0]['payment_gateway_url'],
                        'reference_id' => $reference_id,
                        'reference_number' => $reference_number
                    ]);

                    // Push wallet payment
                    $pushResp = $client->postFunc("/v1/checkout/wallet-payment", [
                        "transid" => $transid,
                        'order_id' => $order_id,
                        'msisdn' => $phone
                    ]);

                    if (empty($pushResp) || $pushResp['result'] !== 'SUCCESS') {
                        log_message('debug', 'Payment API Error (wallet-payment): ' . json_encode($pushResp));
                        $this->session->set_flashdata('payment_error', 'Transaction failed. Please try again.');
                        redirect(base_url("frame?reference_number={$reference_number}"));
                        return;
                    }

                    // Record push request
                    $this->db->insert('selcom_push', [
                        'order_id' => $order_id,
                        'reference' => $pushResp['reference'],
                        'transid' => $pushResp['transid'],
                        'resultcode' => $pushResp['resultcode'],
                        'result' => $pushResp['result'],
                        'message' => $pushResp['message'],
                        'reference_id' => $reference_id,
                        'reference_number' => $reference_number
                    ]);

                    // Flash and load view
                    $this->session->set_flashdata([
                        'order_id' => $order_id,
                        'reference' => $pushResp['reference'],
                        'transid' => $pushResp['transid']
                    ]);

                    $this->load->view('header');
                    $this->load->view('selcom-pay');
                    $this->load->view('footer');
                    return;

                } catch (Exception $e) {
                    log_message('debug', 'Payment API Exception: ' . $e->getMessage());
                    $this->session->set_flashdata('payment_error', 'Transaction failed. Please try again.');
                    redirect(base_url("frame?reference_number={$reference_number}"));
                    return;
                }

            } elseif ($payment_methods === 'CARD') {
                // Additional validation for card
                $this->form_validation->set_rules("name", "Name", "required");
                $this->form_validation->set_rules("country", "Country", "required");
                $this->form_validation->set_rules("state_or_region", "State or Region", "required");
                $this->form_validation->set_rules("email", "Email", "required");
                $this->form_validation->set_rules("postcode_or_pobox", "Postal Code or P.O Box", "required");
                $this->form_validation->set_rules("city", "City or Town", "required");
                $this->form_validation->set_rules("address", "Address", "required");

                if (!$this->form_validation->run()) {
                    log_message('debug', 'CARD payment validation failed');
                    $this->session->set_flashdata('payment_error', 'Please fill all required fields.');
                    redirect(base_url("frame?reference_number={$reference_number}"));
                    return;
                }

                $name = $this->input->post('name');
                $country = $this->input->post('country');
                $state_or_region = $this->input->post('state_or_region');
                $email = $this->input->post('email');
                $postcode_or_pobox = $this->input->post('postcode_or_pobox');
                $city = $this->input->post('city');
                $address = $this->input->post('address');

                $this->session->set_flashdata([
                    'name' => $name,
                    'country' => $country,
                    'state_or_region' => $state_or_region,
                    'email' => $email,
                    'postcode_or_pobox' => $postcode_or_pobox,
                    'city' => $city,
                    'address' => $address
                ]);

                $redirect = base64_encode(base_url("redirect?order_id={$order_id}&transid={$transid}&reference_id={$reference_id}&reference_number={$reference_number}&api_key=" . urlencode($this->API_KEY)));

                try {
                    // Prepare name parts
                    $name = trim(preg_replace('/\s+/', ' ', $name));
                    $nameParts = explode(' ', $name);
                    $firstName = $nameParts[0];
                    $lastName = end($nameParts);

                    $response = $client->postFunc("/v1/checkout/create-order", [
                        "vendor" => $this->vendorId,
                        "order_id" => $order_id,
                        "buyer_email" => $email,
                        "buyer_name" => $name,
                        "buyer_phone" => $_phone,
                        "amount" => $amount,
                        "currency" => "TZS",
                        "payment_methods" => "CARD",
                        "webhook" => $callback,
                        "cancel_url" => $cancel,
                        "redirect_url" => $redirect,
                        "billing.firstname" => $firstName,
                        "billing.lastname" => $lastName,
                        "billing.address_1" => $address,
                        "billing.city" => $city,
                        "billing.state_or_region" => $state_or_region,
                        "billing.postcode_or_pobox" => $postcode_or_pobox,
                        "billing.country" => $country,
                        "billing.phone" => $phone,
                        "buyer_remarks" => $order_id,
                        "merchant_remarks" => $order_id,
                        "no_of_items" => 1
                    ]);

                    if (empty($response) || $response['result'] !== 'SUCCESS') {
                        log_message('debug', 'Payment API Error (create-order CARD): ' . json_encode($response));
                        $this->session->set_flashdata('payment_error', 'Transaction failed. Please try again.');
                        redirect(base_url("frame?reference_number={$reference_number}"));
                        return;
                    }

                    // Record order
                    $this->db->insert('selcom_orders', [
                        'order_id' => $order_id,
                        'transid' => $transid,
                        'reference' => $response['reference'],
                        'payment_methods' => $payment_methods,
                        "buyer_name" => $name,
                        "buyer_email" => $email,
                        "buyer_phone" => $_phone,
                        'resultcode' => $response['resultcode'],
                        'result' => $response['result'],
                        'message' => $response['message'],
                        'gateway_buyer_uuid' => $response['data'][0]['gateway_buyer_uuid'],
                        'payment_token' => $response['data'][0]['payment_token'],
                        'qr' => $response['data'][0]['qr'],
                        'payment_gateway_url' => $response['data'][0]['payment_gateway_url'],
                        'reference_id' => $reference_id,
                        'reference_number' => $reference_number
                    ]);

                    // Redirect to gateway
                    redirect(base64_decode($response['data'][0]['payment_gateway_url']));
                    return;

                } catch (Exception $e) {
                    log_message('debug', 'Payment API Exception (CARD): ' . $e->getMessage());
                    $this->session->set_flashdata('payment_error', 'Transaction failed. Please try again.');
                    redirect(base_url("frame?reference_number={$reference_number}"));
                    return;
                }
            }

        } else {
            // Existing payment path
            if (!$result) {
                log_message('debug', 'Unexpected logic path: result should have been handled earlier.');
                redirect(base_url('error'));
                return;
            }

            // Get donor info directly from database
            $donorQuery = $this->db->get_where('donors', ['reference_number' => $reference_number]);
            $donorInfo = $donorQuery->row();

            if (empty($donorInfo)) {
                redirect(base_url('error'));
                return;
            }

            // Process transaction data
            $transData = $this->processTransactionData(
                $donorInfo->first_name,
                $donorInfo->last_name,
                $donorInfo->calling_code,
                $donorInfo->phone,
                $donorInfo->created_at,
                $donorInfo->amount,
                $donorInfo->meter_number,
                $result->reference,
                $reference_number,
                $result->payment_status
            );

            if ($transData) {
                $this->session->set_flashdata([
                    'order_id' => $result->order_id,
                    'transid' => $result->_transid,
                    'reference' => $result->reference,
                    'reference_id' => $result->reference_id,
                    'reference_number' => $result->reference_number,
                    'meter_number' => $donorInfo->meter_number,
                    'calling_code' => $donorInfo->calling_code,
                    'phone' => $donorInfo->phone
                ]);

                redirect(base_url("thankyou?order_id={$result->order_id}&transid={$result->_transid}&reference={$result->reference}&reference_id={$result->reference_id}&reference_number={$result->reference_number}&meter_number={$donorInfo->meter_number}&calling_code={$donorInfo->calling_code}&phone={$donorInfo->phone}"));
                return;
            } else {
                redirect(base_url('error'));
                return;
            }
        }
    }

    public function check() {
        $data = $this->input->get();

        // Return JSON response header
        header('Content-Type: application/json; charset=utf-8');

        $this->form_validation->set_data($data);
        $this->form_validation->set_rules("order_id", "Order ID", "required");
        $this->form_validation->set_rules("reference", "Reference", "required");
        $this->form_validation->set_rules("transid", "Transaction ID", "required");
        $this->form_validation->set_rules("reference_id", "Reference ID", "required");
        $this->form_validation->set_rules("reference_number", "Reference Number", "required");

        if (!$this->form_validation->run()) {
            echo json_encode([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $this->form_validation->error_array()
            ]);
            return;
        }

        $query = $this->db->get_where('selcom_payments', [
            'order_id' => $data['order_id'],
            '_transid' => $data['transid'],
            'reference_id' => $data['reference_id'],
            'reference_number' => $data['reference_number']
        ]);
        $result = $query->row();

        if (!empty($result)) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Record not found']);
        }
    }

    public function callback() {
        $transid = $this->input->get('_transid');
        $reference_id = $this->input->get('reference_id');
        $reference_number = $this->input->get('reference_number');
        $api_key = $this->input->get('api_key');

        // Verify API key
        if ($api_key !== $this->API_KEY) {
            log_message('error', 'Invalid API key in callback');
            show_error('Invalid request', 403);
            return;
        }

        // Get payment data from Selcom (this would typically come in the request body)
        $paymentData = json_decode(file_get_contents('php://input'), true);

        if (empty($paymentData)) {
            log_message('error', 'Empty payment data in callback');
            show_error('Invalid request', 400);
            return;
        }

        // Update payment status in database
        $this->db->where('transid', $transid);
        $this->db->where('reference_id', $reference_id);
        $this->db->where('reference_number', $reference_number);
        $this->db->update('selcom_payments', [
            'payment_status' => $paymentData['payment_status'],
            'channel' => $paymentData['channel'] ?? null,
            'msisdn' => $paymentData['msisdn'] ?? null,
            'updated_at' => date('Y-m-d H:i:s')
        ]);

        // You might want to update other related tables here as well

        // Return success response
        header('Content-Type: application/json');
        echo json_encode(['result' => 'SUCCESS', 'message' => 'Callback processed']);
    }

    public function cancel() {
        $order_id = $this->input->get('order_id');
        $transid = $this->input->get('transid');
        $reference_id = $this->input->get('reference_id');
        $reference_number = $this->input->get('reference_number');
        $api_key = $this->input->get('api_key');

        // Verify API key
        if ($api_key !== $this->API_KEY) {
            log_message('error', 'Invalid API key in cancel');
            show_error('Invalid request', 403);
            return;
        }

        // Initialize Selcom client
        $client = new Client($this->baseUrl, $this->apiKey, $this->apiSecret);

        try {
            // Cancel order with Selcom
            $response = $client->deleteFunc("/v1/checkout/cancel-order", ["order_id" => $order_id]);

            if ($response['result'] === 'SUCCESS') {
                // Update database
                $this->db->where('order_id', $order_id);
                $this->db->where('transid', $transid);
                $this->db->update('selcom_orders', [
                    'result' => 'CANCELLED',
                    'message' => 'Order cancelled by user',
                    'updated_at' => date('Y-m-d H:i:s')
                ]);

                $this->session->set_flashdata('payment_error', 'Payment was cancelled');
                redirect(base_url("frame?reference_number={$reference_number}"));
            } else {
                log_message('error', 'Failed to cancel order: ' . json_encode($response));
                $this->session->set_flashdata('payment_error', 'Failed to cancel payment');
                redirect(base_url("frame?reference_number={$reference_number}"));
            }
        } catch (Exception $e) {
            log_message('error', 'Exception while cancelling order: ' . $e->getMessage());
            $this->session->set_flashdata('payment_error', 'Error cancelling payment');
            redirect(base_url("frame?reference_number={$reference_number}"));
        }
    }

    public function order_status($order_id) {
        // Initialize Selcom client
        $client = new Client($this->baseUrl, $this->apiKey, $this->apiSecret);

        try {
            $response = $client->getFunc("/v1/checkout/order-status", ["order_id" => $order_id]);

            header('Content-Type: application/json');
            echo json_encode($response);
        } catch (Exception $e) {
            log_message('error', 'Exception while checking order status: ' . $e->getMessage());
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'Failed to check order status'
            ]);
        }
    }

    public function list_orders($from_date, $to_date) {
        // Initialize Selcom client
        $client = new Client($this->baseUrl, $this->apiKey, $this->apiSecret);

        try {
            $response = $client->getFunc("/v1/checkout/list-orders", [
                "fromdate" => $from_date,
                "todate" => $to_date
            ]);

            header('Content-Type: application/json');
            echo json_encode($response);
        } catch (Exception $e) {
            log_message('error', 'Exception while listing orders: ' . $e->getMessage());
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'Failed to list orders'
            ]);
        }
    }

    private function processTransactionData($firstName, $lastName, $callingCode, $phone, $createdAt, $amount, $meterNumber, $reference, $referenceNumber, $paymentStatus) {
        // This function replaces the model method
        // Process the transaction data as needed
        
        // For example, you might want to insert into a transactions table
        $transactionData = [
            'first_name' => $firstName,
            'last_name' => $lastName,
            'calling_code' => $callingCode,
            'phone' => $phone,
            'created_at' => $createdAt,
            'amount' => $amount,
            'meter_number' => $meterNumber,
            'reference' => $reference,
            'reference_number' => $referenceNumber,
            'payment_status' => $paymentStatus,
            'processed_at' => date('Y-m-d H:i:s')
        ];

        return $this->db->insert('transactions', $transactionData);
    }
}

// Client class implementation (should be in a separate file, but included here for completeness)
class Client {
    private $baseUrl;
    private $apiKey;
    private $apiSecret;
    
    public function __construct($baseUrl, $apiKey, $apiSecret) {
        $this->baseUrl = $baseUrl;
        $this->apiKey = $apiKey;
        $this->apiSecret = $apiSecret;
    }
    
    public function postFunc($path, $data) {
        return $this->makeRequest('POST', $path, $data);
    }
    
    public function getFunc($path, $params = []) {
        return $this->makeRequest('GET', $path, $params);
    }
    
    public function deleteFunc($path, $params = []) {
        return $this->makeRequest('DELETE', $path, $params);
    }
    
    private function makeRequest($method, $path, $data = []) {
        $url = $this->baseUrl . $path;
        
        $ch = curl_init();
        
        $headers = [
            'Authorization: Basic ' . base64_encode($this->apiKey . ':' . $this->apiSecret),
            'Content-Type: application/json'
        ];
        
        if ($method === 'GET' || $method === 'DELETE') {
            if (!empty($data)) {
                $url .= '?' . http_build_query($data);
            }
        } else {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
        
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if (curl_errno($ch)) {
            throw new Exception('Curl error: ' . curl_error($ch));
        }
        
        curl_close($ch);
        
        $decodedResponse = json_decode($response, true);
        
        if ($httpCode >= 400) {
            throw new Exception('API request failed with HTTP code ' . $httpCode . ': ' . $response);
        }
        
        return $decodedResponse;
    }
}