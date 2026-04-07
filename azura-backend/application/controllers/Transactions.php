<?php defined('BASEPATH') OR exit('No direct script access allowed');
//require_once __DIR__ . '.././vendor/autoload.php';
//require(APPPATH.'/libraries/ApigwClient.php');
use chriskacerguis\RestServer\RestController;
use Selcom\ApigwClient\Client;
error_reporting(E_ALL & ~E_NOTICE & ~E_STRICT & ~E_DEPRECATED);
class Transactions extends CI_Controller {

	public function __construct() {
        parent::__construct();
		date_default_timezone_set('Africa/Dar_Es_Salaam');
        error_reporting(0);
        $this->load->database();        
        $apiKey = "VIDO-WsGHweDFyW5OOiAs";
        $apiSecret = "886j88k6-khfd-36fa-bb63-8de8a9eb69b6";
        $baseURL = "https://apigw.selcommobile.com/v1";
        $vendorId = "TILL60874609";
		$this->load->model('Common_model', 'common');
		$this->load->library('AES',128);
        $this->load->library('ciqrcode');
                 
        $this->load->library('qrcode');
    	$key = $this->config->item('encryption_key');
	// Use Google Charts API for QR codes (no local files needed)
        $qr_url = "https://chart.googleapis.com/chart?chs=300x300&cht=qr&chl=".urlencode($data);
        echo '<img src="'.$qr_url.'">';
		//if (empty($this->session->userdata())) {
		//	$error_info = array(
		//		'heading' => 'Error',
		//		'message' => $this->config->item('default_error_page_message')
		//	);
		//	$this->session->set_flashdata('error_info', $error_info);
		//	redirect(base_url('home/custom_error_page'));
        //}
			// Set CORS headers
        $this->set_cors_headers();
        
        // Handle preflight
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            $this->handle_preflight();
            return;
        }
        
        // Load necessary libraries and models
        $this->load->database();
        $this->load->library('form_validation');
    
	}
	
    /**
     * Set CORS headers
     */
    private function set_cors_headers() {
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Credentials: true');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS, PATCH');
        header('Access-Control-Allow-Headers: Origin, Content-Type, Accept, Authorization, X-Request-With, X-Requested-With, Access-Control-Request-Method, Access-Control-Request-Headers, X-HTTP-Method-Override');
        header('Access-Control-Expose-Headers: Content-Length, X-JSON');
        header('Access-Control-Max-Age: 86400');
    }

    /**
     * Handle preflight OPTIONS request
     */
    private function handle_preflight() {
        http_response_code(200);
        echo json_encode(['status' => 'ok', 'message' => 'CORS preflight handled']);
        exit(0);
    }
  
	public function index() {
      	$head =  $this->settings->site();
    	$wallet_balance = $this->balance->walletbalance();
		$data['wallet_balance'] = $wallet_balance['data'];
      	$transactions = $this->balance->transactions();
      	$data['transactions'] = $transactions['data'];
	   //	$this->load->view('common/header', $head);
	   	$this->load->view('home/transaction', $data);
        $this->load->view('common/footer');
	}

    public function selcom_card_payments(){
    $order_id = time();
    $apiEndpoint = "/checkout/create-order";

    $buyer_email = $this->input->post('buyer_email');
    $firstname = $this->input->post('firstname');
    $lastname = $this->input->post('lastname');
    $buyer_userid = $this->input->post('buyer_userid');
    $buyer_phone = $this->input->post('buyer_phone');
    $amount = $this->input->post('amount');

    $req = array(
        "vendor" => $this->vendorId, // Replace with your Vendor TILL No.
        "order_id" => $order_id, //  or generate your own unique order_id
        "buyer_email" => $buyer_email,
        "buyer_name" => $firstname . " " . $lastname,
        "buyer_userid" => $buyer_userid,   // optional
        "buyer_phone" => $buyer_phone,
        "amount" => $amount,
        "currency" => "TZS",
        "payment_methods" => "CARD", // Choose one preferred method or ALL
        "redirect_url" => base64_encode(site_url('home')), // Optional
        "cancel_url" => base64_encode(site_url('home')), // Optional
        "webhook" => base64_encode(site_url('api/webhoock/selcom/') . $buyer_userid),
        "billing.firstname" => $firstname,
        "billing.lastname" => $lastname,
        "billing.address_1" => "Mwenge Dar es Salaam",
        "billing.city" => "Dar es Salaam",
        "billing.state_or_region" => "Dar es Salaam",
        "billing.postcode_or_pobox" => "11530",
        "billing.country" => "TZ",
        "billing.phone" => $buyer_phone,
        "no_of_items" => 1,
        // "expiry" => 60, // Optional
    );

    $response = $this->runRequest($req, $apiEndpoint, 'POST');

    if ($response['result'] == 'SUCCESS') {
        $data = array(
            'order_id' => $order_id,
            'user_id' => $buyer_userid,
            'reference' => $response['reference'],
            'resultcode' => $response['resultcode'],
            'result' => $response['result'],
            'currency' => 'TSH',
            'message' => $response['message'],
            'gateway_buyer_uuid' => $response['data'][0]['gateway_buyer_uuid'],
            'payment_token' => $response['data'][0]['payment_token'],
            'qr' => $response['data'][0]['qr'],
            'payment_gateway_url' => base64_decode($response['data'][0]['payment_gateway_url'])
        );

        $this->db->insert('payments', $data);

        $response_data = array('done' => base64_decode($response['data'][0]['payment_gateway_url']));
        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($response_data));
    } else {
        $response_data = array('error' => $response['result'] . ' ' . $response['message']);
        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($response_data));
    }
}

}