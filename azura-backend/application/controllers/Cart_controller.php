<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Cart_controller extends Home_Core_Controller
{
    /*
     * Payment Types
     *
     * 1. sale: Product purchases
     * 2. membership: Membership purchases
     * 3. promote: Promote purchases
     *
     */

    public function __construct()
    {
        parent::__construct();
        $this->session_cart_items = $this->cart_model->get_sess_cart_items();
        $this->cart_model->calculate_cart_total($this->session_cart_items);
    }

    /**
     * Cart
     */
    public function cart()
    {
        $data['title'] = trans("shopping_cart");
        $data['description'] = trans("shopping_cart") . " - " . $this->app_name;
        $data['keywords'] = trans("shopping_cart") . "," . $this->app_name;

        $data['cart_items'] = $this->session_cart_items;
        $data['cart_total'] = $this->cart_model->get_sess_cart_total();
        $data['cart_has_physical_product'] = $this->cart_model->check_cart_has_physical_product();

        $this->load->view('partials/_header', $data);
        $this->load->view('cart/cart', $data);
        $this->load->view('partials/_footer');
    }

    /**
     * Add to Cart
     */
    public function add_to_cart()
    {
        $product_id = $this->input->post('product_id', true);
        $is_ajax = $this->input->post('is_ajax', true);
        $product = $this->product_model->get_active_product($product_id);
        if (!empty($product)) {
            if ($product->status != 1) {
                $this->session->set_flashdata('product_details_error', trans("msg_error_cart_unapproved_products"));
            } else {
                $this->cart_model->add_to_cart($product);
                if (empty($is_ajax)) {
                    redirect(generate_url("cart"));
                }
            }
        }
        if (empty($is_ajax)) {
            redirect($this->agent->referrer());
        }
    }

    /**
     * Add to Cart qQuote
     */
    public function add_to_cart_quote()
    {
        $quote_request_id = $this->input->post('id', true);
        if (!empty($this->cart_model->add_to_cart_quote($quote_request_id))) {
            redirect(generate_url("cart"));
        }
        redirect($this->agent->referrer());
    }

    /**
     * Remove from Cart
     */
    public function remove_from_cart()
    {
        $cart_item_id = $this->input->post('cart_item_id', true);
        $this->cart_model->remove_from_cart($cart_item_id);
    }

    /**
     * Update Cart Product Quantity
     */
    public function update_cart_product_quantity()
    {
        $product_id = $this->input->post('product_id', true);
        $cart_item_id = $this->input->post('cart_item_id', true);
        $quantity = $this->input->post('quantity', true);
        $this->cart_model->update_cart_product_quantity($product_id, $cart_item_id, $quantity);
    }

    /**
     * Shipping
     */
    public function shipping()
    {
        $this->cart_model->validate_cart();
        $data['title'] = trans("shopping_cart");
        $data['description'] = trans("shopping_cart") . " - " . $this->app_name;
        $data['keywords'] = trans("shopping_cart") . "," . $this->app_name;
        $data['cart_items'] = $this->cart_model->get_sess_cart_items();
        $data['mds_payment_type'] = 'sale';

        if (empty($data['cart_items'])) {
            redirect(generate_url("cart"));
        }
        //check shipping status
        if ($this->product_settings->marketplace_shipping != 1) {
            redirect(generate_url("cart"));
            exit();
        }
        //check guest checkout
        if (empty($this->auth_check) && $this->general_settings->guest_checkout != 1) {
            redirect(generate_url("cart"));
            exit();
        }
        //check auth for digital products
        if (!$this->auth_check && $this->cart_model->check_cart_has_digital_product() == true) {
            $this->session->set_flashdata('error', trans("msg_digital_product_register_error"));
            redirect(generate_url("register"));
            exit();
        }
        //check physical products
        if ($this->cart_model->check_cart_has_physical_product() == false) {
            redirect(generate_url("cart"));
            exit();
        }
        $data['cart_total'] = $this->cart_model->get_sess_cart_total();
        if ($data['cart_total']->is_stock_available != 1) {
            redirect(generate_url("cart"));
            exit();
        }

        $state_id = 0;
        if ($this->auth_check) {
            $data["shipping_addresses"] = $this->profile_model->get_shipping_addresses($this->auth_user->id);
            $first_id = 0;
            if (!empty($data["shipping_addresses"]) && !empty($data["shipping_addresses"][0])) {
                $first_id = $data["shipping_addresses"][0]->id;
            }
            $data['selected_shipping_address_id'] = $first_id;
            $data['selected_billing_address_id'] = $first_id;
            $data['selected_same_address_for_billing'] = 1;
            if (!empty($data["shipping_addresses"][0]->state_id)) {
                $state_id = $data["shipping_addresses"][0]->state_id;
            }
            if (!empty($this->session->userdata('mds_cart_shipping'))) {
                $selected_shipping = $this->session->userdata('mds_cart_shipping');
                if (!empty($selected_shipping->user_id) && $selected_shipping->user_id == $this->auth_user->id) {
                    if (!empty($selected_shipping->shipping_address_id)) {
                        $data['selected_shipping_address_id'] = $selected_shipping->shipping_address_id;
                    }
                    if (!empty($selected_shipping->billing_address_id)) {
                        $data['selected_billing_address_id'] = $selected_shipping->billing_address_id;
                    }
                    if (!empty($selected_shipping->use_same_address_for_billing)) {
                        $data['selected_same_address_for_billing'] = $selected_shipping->use_same_address_for_billing;
                    }
                    $selected_address = $this->profile_model->get_shipping_address_by_id($data['selected_shipping_address_id']);
                    if (!empty($selected_address)) {
                        $state_id = $selected_address->state_id;
                    }
                }
            }
        } else {
            $mds_cart_shipping = get_sess_data('mds_cart_shipping');
            if (!empty($mds_cart_shipping)) {
                if (!empty($mds_cart_shipping->guest_shipping_address) && item_count($mds_cart_shipping->guest_shipping_address) > 0) {
                    if (!empty($mds_cart_shipping->guest_shipping_address['state_id'])) {
                        $state_id = $mds_cart_shipping->guest_shipping_address['state_id'];
                    }
                }
            }
        }
        if (!empty($state_id)) {
            $data["shipping_methods"] = $this->shipping_model->get_seller_shipping_methods_array($data['cart_items'], $state_id);
        }
        $data['selected_shipping_method_ids'] = array();
        if (!empty($this->session->userdata('mds_selected_shipping_method_ids'))) {
            $data['selected_shipping_method_ids'] = $this->session->userdata('mds_selected_shipping_method_ids');
        }

        //cart seller ids
        $data['cart_seller_ids'] = null;
        if (!empty($this->session->userdata('mds_array_cart_seller_ids'))) {
            $data['cart_seller_ids'] = $this->session->userdata('mds_array_cart_seller_ids');
        }

        if ($this->auth_check) {
            $this->prime_mds_payment_cart_session_sale($data['cart_items'], $data['cart_total']);
        }

        $this->load->view('partials/_header', $data);
        if ($this->auth_check) {
            $this->load->view('cart/shipping_information', $data);
        } else {
            $this->load->view('cart/shipping_information_guest', $data);
        }
        $this->load->view('partials/_footer');
    }

    /**
     * Shipping Post
     */
    public function shipping_post()
    {
        $cart_shipping = new stdClass();
        $cart_shipping->total_cost = 0;
        $cart_shipping->use_same_address_for_billing = $this->input->post('use_same_address_for_billing', true);
        if ($this->auth_check) {
            $cart_shipping->user_id = $this->auth_user->id;
            $cart_shipping->shipping_address_id = $this->input->post('shipping_address_id', true);
            $cart_shipping->billing_address_id = $this->input->post('billing_address_id', true);
            $cart_shipping->guest_shipping_address = null;
            $cart_shipping->guest_billing_address = null;
            if ($cart_shipping->use_same_address_for_billing == 1) {
                $cart_shipping->billing_address_id = $cart_shipping->shipping_address_id;
            }
            $cart_shipping->is_guest = false;
        } else {
            $cart_shipping->user_id = 0;
            $cart_shipping->guest_shipping_address = $this->cart_model->set_guest_shipping_address();
            $cart_shipping->guest_billing_address = $this->cart_model->set_guest_billing_address();
            if ($cart_shipping->use_same_address_for_billing == 1) {
                $cart_shipping->guest_billing_address = $cart_shipping->guest_shipping_address;
            }
            $cart_shipping->is_guest = true;
        }

        $result = $this->shipping_model->calculate_cart_shipping_total_cost();
        if (!empty($result) && $result['is_valid'] != 1) {
            $this->session->set_flashdata('error', trans("msg_error"));
            redirect($this->agent->referrer());
            exit();
        }
        $data['cart_total'] = $this->cart_model->get_sess_cart_total();
        if (!empty($data['cart_total']) && !empty($result['total_cost'])) {
            $data['cart_total']->shipping_cost = $result['total_cost'];
            $cart_shipping->total_cost = $result['total_cost'];
            $this->session->set_userdata('mds_shopping_cart_total', $data['cart_total']);
        }
        $this->session->set_userdata('mds_cart_shipping', $cart_shipping);
        redirect(generate_url("cart", "payment_method"));
        exit();
    }

    /**
     * Payment Method
     */
    public function payment_method()
    {
        $data['title'] = trans("shopping_cart");
        $data['description'] = trans("shopping_cart") . " - " . $this->app_name;
        $data['keywords'] = trans("shopping_cart") . "," . $this->app_name;

        $payment_type = input_get('payment_type');
        if ($payment_type != "membership" && $payment_type != "promote") {
            $payment_type = "sale";
        }

        if ($payment_type == "sale") {
            $this->cart_model->validate_cart();
            //sale payment
            $data['cart_items'] = $this->cart_model->get_sess_cart_items();
            $data['mds_payment_type'] = "sale";
            if ($data['cart_items'] == null) {
                redirect(generate_url("cart"));
            }
            //check auth for digital products
            if (!$this->auth_check && $this->cart_model->check_cart_has_digital_product() == true) {
                $this->session->set_flashdata('error', trans("msg_digital_product_register_error"));
                redirect(generate_url("register"));
                exit();
            }
            $data['cart_total'] = $this->cart_model->get_sess_cart_total();
            $user_id = null;
            if ($this->auth_check) {
                $user_id = $this->auth_user->id;
            }

            $data['cart_has_physical_product'] = $this->cart_model->check_cart_has_physical_product();
            $data['cart_has_digital_product'] = $this->cart_model->check_cart_has_digital_product();
            $this->cart_model->unset_sess_cart_payment_method();
            $data['show_shipping_cost'] = 1;
        } elseif ($payment_type == 'membership') {
            //membership payment
            if ($this->general_settings->membership_plans_system != 1) {
                redirect(lang_base_url());
                exit();
            }
            $data['mds_payment_type'] = 'membership';
            $plan_id = $this->session->userdata('modesy_selected_membership_plan_id');
            if (empty($plan_id)) {
                redirect(lang_base_url());
                exit();
            }
            $data['plan'] = $this->membership_model->get_plan($plan_id);
            if (empty($data['plan'])) {
                redirect(lang_base_url());
                exit();
            }
        } elseif ($payment_type == 'promote') {
            //promote payment
            if ($this->general_settings->promoted_products != 1) {
                redirect(lang_base_url());
            }
            $data['mds_payment_type'] = 'promote';
            $data['promoted_plan'] = $this->session->userdata('modesy_selected_promoted_plan');
            if (empty($data['promoted_plan'])) {
                redirect(lang_base_url());
            }
        }

        $this->load->view('partials/_header', $data);
        $this->load->view('cart/payment_method', $data);
        $this->load->view('partials/_footer');
    }

    /**
     * Payment Method Post
     */
    public function payment_method_post()
    {
        $this->cart_model->set_sess_cart_payment_method();

        $mds_payment_type = $this->input->post('mds_payment_type', true);
        $redirect = lang_base_url();
        if ($mds_payment_type == "sale") {
            $redirect = generate_url("cart", "payment");
        } elseif ($mds_payment_type == 'membership') {
            $transaction_number = 'bank-' . generate_transaction_number();
            $this->session->set_userdata('mds_membership_bank_transaction_number', $transaction_number);
            $redirect = generate_url("cart", "payment") . "?payment_type=membership";
        } elseif ($mds_payment_type == 'promote') {
            $transaction_number = 'bank-' . generate_transaction_number();
            $this->session->set_userdata('mds_promote_bank_transaction_number', $transaction_number);
            $redirect = generate_url("cart", "payment") . "?payment_type=promote";
        }
        redirect($redirect);
    }

    /**
     * Payment
     */
    public function payment()
    {
        $data['title'] = trans("shopping_cart");
        $data['description'] = trans("shopping_cart") . " - " . $this->app_name;
        $data['keywords'] = trans("shopping_cart") . "," . $this->app_name;
        $data['mds_payment_type'] = "sale";

        //check guest checkout
        if (empty($this->auth_check) && $this->general_settings->guest_checkout != 1) {
            redirect(generate_url("cart"));
            exit();
        }

        //check is set cart payment method
        $data['cart_payment_method'] = $this->cart_model->get_sess_cart_payment_method();
        if (empty($data['cart_payment_method'])) {
            redirect(generate_url("cart", "payment_method"));
        }

        $payment_type = input_get('payment_type');
        if ($payment_type != "membership" && $payment_type != "promote") {
            $payment_type = "sale";
        }

        if ($payment_type == "sale") {
            $this->cart_model->validate_cart();
            //sale payment
            $data['cart_items'] = $this->cart_model->get_sess_cart_items();
            if ($data['cart_items'] == null) {
                redirect(generate_url("cart"));
            }
            $data['cart_total'] = $this->cart_model->get_sess_cart_total();
            $data['cart_has_physical_product'] = $this->cart_model->check_cart_has_physical_product();

            $obj_amount = $this->cart_model->convert_currency_by_payment_gateway($data['cart_total']->total, "sale");
            $data['total_amount'] = $obj_amount->total;
            $data['currency'] = $obj_amount->currency;
            if (filter_var($data['total_amount'], FILTER_VALIDATE_INT) === false) {
                $data['total_amount'] = number_format($data['total_amount'], 2, ".", "");
            }
            //set payment session
            if (!empty($data['cart_items'])) {
                $this->session->set_userdata('mds_shopping_cart_final', $data['cart_items']);
            }
            if (!empty($data['cart_total'])) {
                $this->session->set_userdata('mds_shopping_cart_total_final', $data['cart_total']);
            }
            $data['show_shipping_cost'] = 1;
        } elseif ($payment_type == 'membership') {
            //membership payment
            if ($this->general_settings->membership_plans_system != 1) {
                redirect(lang_base_url());
                exit();
            }
            $data['mds_payment_type'] = 'membership';
            $plan_id = $this->session->userdata('modesy_selected_membership_plan_id');
            if (empty($plan_id)) {
                redirect(lang_base_url());
                exit();
            }
            $data['plan'] = $this->membership_model->get_plan($plan_id);
            if (empty($data['plan'])) {
                redirect(lang_base_url());
                exit();
            }
            //total amount
            $price = $data['plan']->price;
            if ($this->payment_settings->currency_converter != 1) {
                $price = get_price($price, 'decimal');
            }
            $obj_amount = $this->cart_model->convert_currency_by_payment_gateway($price, "membership");
            $data['total_amount'] = $obj_amount->total;
            $data['currency'] = $obj_amount->currency;
            $data['transaction_number'] = $this->session->userdata('mds_membership_bank_transaction_number');
            $data['cart_total'] = null;
        } elseif ($payment_type == 'promote') {
            //promote payment
            if ($this->general_settings->promoted_products != 1) {
                redirect(lang_base_url());
            }
            $data['mds_payment_type'] = 'promote';
            $data['promoted_plan'] = $this->session->userdata('modesy_selected_promoted_plan');
            if (empty($data['promoted_plan'])) {
                redirect(lang_base_url());
            }
            //total amount
            $obj_amount = $this->cart_model->convert_currency_by_payment_gateway($data['promoted_plan']->total_amount, "promote");
            $data['total_amount'] = $obj_amount->total;
            $data['currency'] = $obj_amount->currency;
            $data['transaction_number'] = $this->session->userdata('mds_promote_bank_transaction_number');
            $data['cart_total'] = null;
        }

        $this->load->view('partials/_header', $data);
        $this->load->view('cart/payment', $data);
        $this->load->view('partials/_footer');
    }

    /**
     * Payment with Paypal
     */
    public function paypal_payment_post()
    {
        $payment_id = $this->input->post('payment_id', true);
        $this->load->library('paypal');

        //validate the order
        if ($this->paypal->get_order($payment_id)) {
            $data_transaction = array(
                'payment_method' => "PayPal",
                'payment_id' => $payment_id,
                'currency' => $this->input->post('currency', true),
                'payment_amount' => $this->input->post('payment_amount', true),
                'payment_status' => $this->input->post('payment_status', true),
            );
            $mds_payment_type = $this->input->post('mds_payment_type', true);

            //add order
            $response = $this->execute_payment($data_transaction, $mds_payment_type, lang_base_url());
            if ($response->result == 1) {
                $this->session->set_flashdata('success', $response->message);
                echo json_encode([
                    'result' => 1,
                    'redirect_url' => $response->redirect_url
                ]);
            } else {
                $this->session->set_flashdata('error', $response->message);
                echo json_encode([
                    'result' => 0
                ]);
            }
        } else {
            $this->session->set_flashdata('error', trans("msg_error"));
            echo json_encode([
                'result' => 0
            ]);
        }
    }

    /**
     * Payment with Stripe
     */
    public function stripe_payment_post()
    {
        $stripe = get_payment_gateway('stripe');
        if (empty($stripe)) {
            $this->session->set_flashdata('error', "Payment method not found!");
            echo json_encode([
                'result' => 0
            ]);
            exit();
        }
        $payment_session = $this->session->userdata('mds_payment_cart_data');
        if (empty($payment_session)) {
            $this->session->set_flashdata('error', trans("invalid_attempt"));
            echo json_encode([
                'result' => 0
            ]);
            exit();
        }

        $paymentObject = $this->input->post('paymentObject', true);
        if (!empty($paymentObject)) {
            $paymentObject = json_decode($paymentObject);
        }
        $clientSecret = $this->session->userdata('mds_stripe_client_secret');

        if (!empty($paymentObject) && $paymentObject->client_secret == $clientSecret) {
            $data_transaction = array(
                'payment_method' => $stripe->name,
                'payment_id' => $paymentObject->id,
                'currency' => strtoupper($paymentObject->currency),
                'payment_amount' => get_price($paymentObject->amount, 'decimal'),
                'payment_status' => "Succeeded"
            );
            //add order
            $response = $this->execute_payment($data_transaction, $payment_session->payment_type, lang_base_url());
            if ($response->result == 1) {
                $this->session->set_flashdata('success', $response->message);
                echo json_encode([
                    'result' => 1,
                    'redirect_url' => $response->redirect_url
                ]);
            } else {
                $this->session->set_flashdata('error', $response->message);
                echo json_encode([
                    'result' => 0
                ]);
            }
        } else {
            $this->session->set_flashdata('error', trans("msg_error"));
            echo json_encode([
                'result' => 0
            ]);
        }
        @$this->session->unset_userdata('mds_stripe_client_secret');
    }

    /**
     * Payment with PayStack
     */
    public function paystack_payment_post()
    {
        $this->load->library('paystack');

        $data_transaction = array(
            'payment_method' => "PayStack",
            'payment_id' => $this->input->post('payment_id', true),
            'currency' => $this->input->post('currency', true),
            'payment_amount' => get_price($this->input->post('payment_amount', true), 'decimal'),
            'payment_status' => $this->input->post('payment_status', true),
        );

        if (empty($this->paystack->verify_transaction($data_transaction['payment_id']))) {
            $this->session->set_flashdata('error', 'Invalid transaction code!');
            echo json_encode([
                'result' => 0
            ]);
        } else {
            $mds_payment_type = $this->input->post('mds_payment_type', true);

            //add order
            $response = $this->execute_payment($data_transaction, $mds_payment_type, lang_base_url());
            if ($response->result == 1) {
                $this->session->set_flashdata('success', $response->message);
                echo json_encode([
                    'result' => 1,
                    'redirect_url' => $response->redirect_url
                ]);
            } else {
                $this->session->set_flashdata('error', $response->message);
                echo json_encode([
                    'result' => 0
                ]);
            }
        }
    }

    /**
     * Payment with Razorpay
     */
    public function razorpay_payment_post()
    {
        $this->load->library('razorpay');

        $data_transaction = array(
            'payment_method' => "Razorpay",
            'payment_id' => $this->input->post('payment_id', true),
            'razorpay_order_id' => $this->input->post('razorpay_order_id', true),
            'razorpay_signature' => $this->input->post('razorpay_signature', true),
            'currency' => $this->input->post('currency', true),
            'payment_amount' => get_price($this->input->post('payment_amount', true), 'decimal'),
            'payment_status' => 'Succeeded',
        );

        if (empty($this->razorpay->verify_payment_signature($data_transaction))) {
            $this->session->set_flashdata('error', 'Invalid signature passed!');
            echo json_encode([
                'result' => 0
            ]);
        } else {
            $mds_payment_type = $this->input->post('mds_payment_type', true);
            //add order
            $response = $this->execute_payment($data_transaction, $mds_payment_type, lang_base_url());
            if ($response->result == 1) {
                $this->session->set_flashdata('success', $response->message);
                echo json_encode([
                    'result' => 1,
                    'redirect_url' => $response->redirect_url
                ]);
            } else {
                $this->session->set_flashdata('error', $response->message);
                echo json_encode([
                    'result' => 0
                ]);
            }
        }
    }

    /**
     * Payment with Flutterwave
     */
    public function flutterwave_payment_post()
    {
        $flutterwave = get_payment_gateway('flutterwave');
        if (empty($flutterwave)) {
            $this->session->set_flashdata('error', "Payment method not found!");
            redirect($response->redirect_url);
            exit();
        }
        $payment_session = $this->session->userdata('mds_payment_cart_data');
        if (empty($payment_session)) {
            $this->session->set_flashdata('error', trans("invalid_attempt"));
            redirect($response->redirect_url);
            exit();
        }
        $transaction_id = input_get('transaction_id');
        $tx_ref = input_get('tx_ref');
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://api.flutterwave.com/v3/transactions/" . $transaction_id . "/verify",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_HTTPHEADER => array(
                "Content-Type: application/json",
                "Authorization: Bearer " . $flutterwave->secret_key
            ),
        ));
        $curlResponse = curl_exec($curl);
        curl_close($curl);
        $responseObj = json_decode($curlResponse);
        if (!empty($responseObj) && isset($responseObj->status) && $responseObj->status == 'success' && $payment_session->mds_payment_token == $tx_ref) {
            $data_transaction = array(
                'payment_method' => $flutterwave->name,
                'payment_id' => $transaction_id,
                'currency' => isset($responseObj->data->currency) ? $responseObj->data->currency : 'unset',
                'payment_amount' => isset($responseObj->data->amount) ? $responseObj->data->amount : 0,
                'payment_status' => "Succeeded"
            );
            //add order
            $response = $this->execute_payment($data_transaction, $payment_session->payment_type, lang_base_url());
            if ($response->result == 1) {
                $this->session->set_flashdata('success', $response->message);
                redirect($response->redirect_url);
            } else {
                $this->session->set_flashdata('error', $response->message);
                redirect($response->redirect_url);
            }
        } else {
            $this->session->set_flashdata('error', trans("msg_error"));
            redirect($lang_base_url . get_route("cart", true) . get_route("payment"));
        }
    }

    /**
     * Payment with Iyzico
     */
    public function iyzico_payment_post()
    {
        $iyzico = get_payment_gateway('iyzico');
        if (empty($iyzico)) {
            $this->session->set_flashdata('error', "Payment method not found!");
            redirect($response->redirect_url);
            exit();
        }
        require_once(APPPATH . 'third_party/iyzipay/vendor/autoload.php');
        require_once(APPPATH . 'third_party/iyzipay/vendor/iyzico/iyzipay-php/IyzipayBootstrap.php');

        $token = $this->input->post('token', true);
        $conversation_id = $this->input->get('conversation_id', true);
        $lang = $this->input->get('lang', true);
        $payment_type = $this->input->get('payment_type', true);

        $lang_base_url = lang_base_url();
        if ($lang != $this->selected_lang->short_form) {
            $lang_base_url = base_url() . $lang . "/";
        }

        IyzipayBootstrap::init();
        $options = new \Iyzipay\Options();
        $options->setApiKey($iyzico->public_key);
        $options->setSecretKey($iyzico->secret_key);
        if ($iyzico->environment == "sandbox") {
            $options->setBaseUrl("https://sandbox-api.iyzipay.com");
        } else {
            $options->setBaseUrl("https://api.iyzipay.com");
        }

        $request = new \Iyzipay\Request\RetrieveCheckoutFormRequest();
        $request->setLocale(\Iyzipay\Model\Locale::TR);
        $request->setConversationId($conversation_id);
        $request->setToken($token);

        $checkoutForm = \Iyzipay\Model\CheckoutForm::retrieve($request, $options);
        if ($checkoutForm->getPaymentStatus() == "SUCCESS") {
            $data_transaction = array(
                'payment_method' => "Iyzico",
                'payment_id' => $checkoutForm->getPaymentId(),
                'currency' => $checkoutForm->getCurrency(),
                'payment_amount' => $checkoutForm->getPrice(),
                'payment_status' => "Succeeded"
            );
            //add order
            $response = $this->execute_payment($data_transaction, $payment_type, $lang_base_url);
            if ($response->result == 1) {
                $this->session->set_flashdata('success', $response->message);
                redirect($response->redirect_url);
            } else {
                $this->session->set_flashdata('error', $response->message);
                redirect($response->redirect_url);
            }
        } else {
            $this->session->set_flashdata('error', trans("msg_error"));
            redirect($lang_base_url . get_route("cart", true) . get_route("payment"));
        }
    }

    /**
     * Payment with Midtrans
     */
    public function midtrans_payment_post()
    {
        $midtrans = get_payment_gateway('midtrans');
        if (empty($midtrans)) {
            $this->session->set_flashdata('error', "Payment method not found!");
            echo json_encode([
                'result' => 0
            ]);
            exit();
        }
        $payment_session = $this->session->userdata('mds_payment_cart_data');
        if (empty($payment_session)) {
            $this->session->set_flashdata('error', trans("invalid_attempt"));
            echo json_encode([
                'result' => 0
            ]);
            exit();
        }
        $transaction_id = $this->input->post('transaction_id', true);
        $curl = curl_init();
        $curlURL = "https://api.sandbox.midtrans.com/v2/" . $transaction_id . "/status";
        if ($midtrans->environment == "production") {
            $curlURL = "https://api.midtrans.com/v2/" . $transaction_id . "/status";
        }
        curl_setopt_array($curl, array(
            CURLOPT_URL => $curlURL,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                "Accept: application/json",
                "Content-Type: application/json",
                "Authorization: Basic " . base64_encode($midtrans->secret_key)
            ],
        ));
        $curlResponse = curl_exec($curl);
        curl_close($curl);
        $responseObj = json_decode($curlResponse);
        if (!empty($responseObj) && $responseObj->status_code == 200 && $responseObj->order_id == $payment_session->mds_payment_token) {
            $data_transaction = array(
                'payment_method' => $midtrans->name,
                'payment_id' => $transaction_id,
                'currency' => "IDR",
                'payment_amount' => isset($responseObj->gross_amount) ? $responseObj->gross_amount : 0,
                'payment_status' => "Succeeded"
            );
            //add order
            $response = $this->execute_payment($data_transaction, $payment_session->payment_type, lang_base_url());
            if ($response->result == 1) {
                $this->session->set_flashdata('success', $response->message);
                echo json_encode([
                    'result' => 1,
                    'redirect_url' => $response->redirect_url
                ]);
            } else {
                $this->session->set_flashdata('error', $response->message);
                echo json_encode([
                    'result' => 0
                ]);
            }
        } else {
            $this->session->set_flashdata('error', trans("msg_error"));
            echo json_encode([
                'result' => 0
            ]);
        }
    }

    /**
     * Payment with Selcom
     */
    public function selcom_payment_post()
    {
        $selcom = get_payment_gateway('selcom');
        if (empty($selcom) || empty($selcom->public_key) || empty($selcom->secret_key) || empty($selcom->locale)) {
            $this->session->set_flashdata('error', 'Selcom settings are incomplete. Please configure Vendor ID, API Key and API Secret.');
            redirect(generate_url("cart", "payment"));
            exit();
        }
        $payment_session = $this->session->userdata('mds_payment_cart_data');
        if (empty($payment_session)) {
            $this->session->set_flashdata('error', trans("invalid_attempt"));
            redirect(generate_url("cart", "payment"));
            exit();
        }

        $customer = get_cart_customer_data();
        $orderId = $payment_session->mds_payment_token;
        $paymentType = $this->input->post('mds_payment_type', true);
        if (empty($paymentType)) {
            $paymentType = $payment_session->payment_type;
        }
        $returnUrl = base_url() . "selcom-payment-return?payment_type=" . rawurlencode($paymentType) . "&order_id=" . rawurlencode($orderId);
        $webhookUrl = base_url() . "selcom-payment-webhook?order_id=" . rawurlencode($orderId);

        $buyerName = !empty($customer) ? trim($customer->first_name . ' ' . $customer->last_name) : '';
        if ($buyerName == '') {
            $buyerName = 'Guest Customer';
        }
        $buyerPhone = !empty($customer->phone_number) ? preg_replace('/\s+/', '', $customer->phone_number) : '255700000000';
        $postCalling = $this->input->post('calling_code', true);
        $postPhone = $this->input->post('phone', true);
        if ($postCalling !== null && $postCalling !== '' && $postPhone !== null && trim((string) $postPhone) !== '') {
            $cc = preg_replace('/\D+/', '', (string) $postCalling);
            $digits = preg_replace('/\D+/', '', (string) $postPhone);
            if ($cc !== '' && $digits !== '') {
                $digits = ltrim($digits, '0');
                $buyerPhone = $cc . $digits;
            }
        }
        $payload = array(
            'vendor' => trim($selcom->locale),
            'order_id' => $orderId,
            'buyer_email' => !empty($customer->email) ? $customer->email : 'guest@azuramall.local',
            'buyer_name' => $buyerName,
            'buyer_phone' => $buyerPhone,
            'amount' => (float)$payment_session->total_amount,
            'currency' => strtoupper($payment_session->currency),
            'payment_methods' => 'ALL',
            'redirect_url' => base64_encode($returnUrl),
            'cancel_url' => base64_encode($returnUrl . "&cancelled=1"),
            'webhook' => base64_encode($webhookUrl),
            'buyer_remarks' => 'Order ' . $orderId,
            'merchant_remarks' => 'Azura Mall checkout',
            'no_of_items' => 1
        );

        $this->load->helper('selcom');
        $response = selcom_api_request($selcom, '/v1/checkout/create-order-minimal', 'POST', $payload, array(
            'vendor', 'order_id', 'buyer_email', 'buyer_name', 'buyer_phone', 'amount', 'currency', 'payment_methods',
            'redirect_url', 'cancel_url', 'webhook', 'buyer_remarks', 'merchant_remarks', 'no_of_items'
        ));

        if (empty($response) || !isset($response['result']) || strtoupper((string)$response['result']) != 'SUCCESS') {
            $this->session->set_flashdata('error', !empty($response['message']) ? $response['message'] : trans("msg_error"));
            redirect(generate_url("cart", "payment"));
            exit();
        }

        $gatewayUrl = '';
        if (!empty($response['data'][0]['payment_gateway_url'])) {
            $rawUrl = $response['data'][0]['payment_gateway_url'];
            $decoded = base64_decode($rawUrl, true);
            $gatewayUrl = !empty($decoded) ? $decoded : $rawUrl;
        }
        if (empty($gatewayUrl)) {
            $this->session->set_flashdata('error', 'Selcom did not return a checkout URL.');
            redirect(generate_url("cart", "payment"));
            exit();
        }

        $this->session->set_userdata('mds_selcom_order_id', $orderId);
        redirect($gatewayUrl);
        exit();
    }

    /**
     * Selcom return callback (browser redirect)
     */
    public function selcom_payment_return()
    {
        $selcom = get_payment_gateway('selcom');
        $payment_session = $this->session->userdata('mds_payment_cart_data');
        if (empty($selcom) || empty($payment_session)) {
            $this->session->set_flashdata('error', trans("invalid_attempt"));
            redirect(generate_url("cart", "payment"));
            exit();
        }

        $cancelled = input_get('cancelled');
        if (!empty($cancelled)) {
            $this->session->set_flashdata('error', 'Payment cancelled by user.');
            redirect(generate_url("cart", "payment"));
            exit();
        }

        $orderId = input_get('order_id');
        if (empty($orderId)) {
            $orderId = $this->session->userdata('mds_selcom_order_id');
        }
        if (empty($orderId)) {
            $this->session->set_flashdata('error', trans("msg_error"));
            redirect(generate_url("cart", "payment"));
            exit();
        }

        $this->load->helper('selcom');
        $statusResponse = selcom_api_request($selcom, '/v1/checkout/order-status', 'GET', array('order_id' => $orderId), array('order_id'));
        if (empty($statusResponse) || strtoupper((string)($statusResponse['result'] ?? '')) != 'SUCCESS') {
            $this->session->set_flashdata('error', !empty($statusResponse['message']) ? $statusResponse['message'] : trans("msg_error"));
            redirect(generate_url("cart", "payment"));
            exit();
        }

        $statusData = !empty($statusResponse['data'][0]) ? $statusResponse['data'][0] : array();
        $paymentStatus = strtoupper((string)($statusData['payment_status'] ?? ''));
        if ($paymentStatus != 'COMPLETED') {
            $this->session->set_flashdata('error', 'Payment is not completed yet. Current status: ' . ($paymentStatus != '' ? $paymentStatus : 'UNKNOWN'));
            redirect(generate_url("cart", "payment"));
            exit();
        }

        $txRef = !empty($statusData['reference']) ? $statusData['reference'] : (!empty($statusData['transid']) ? $statusData['transid'] : $orderId);
        $data_transaction = array(
            'payment_method' => $selcom->name,
            'payment_id' => $txRef,
            'currency' => !empty($statusData['currency']) ? strtoupper($statusData['currency']) : strtoupper($payment_session->currency),
            'payment_amount' => !empty($statusData['amount']) ? (float)$statusData['amount'] : (float)$payment_session->total_amount,
            'payment_status' => "Succeeded"
        );

        $response = $this->execute_payment($data_transaction, $payment_session->payment_type, lang_base_url());
        if ($response->result == 1) {
            $this->session->set_flashdata('success', $response->message);
            redirect($response->redirect_url);
        } else {
            $this->session->set_flashdata('error', $response->message);
            redirect($response->redirect_url);
        }
    }

    /**
     * Selcom server-to-server callback.
     * Keeps ACK contract and finalizes app checkout orders when possible.
     */
    public function selcom_payment_webhook()
    {
        $payload = json_decode(file_get_contents('php://input'), true);
        if (!is_array($payload)) {
            $payload = array();
        }
        log_message('info', 'Selcom webhook: ' . json_encode($payload));

        $orderId = '';
        if (!empty($payload['order_id'])) {
            $orderId = (string) $payload['order_id'];
        } elseif (!empty($payload['data'][0]['order_id'])) {
            $orderId = (string) $payload['data'][0]['order_id'];
        } else {
            $orderId = input_get('order_id');
        }

        if ($orderId !== '') {
            $this->load->model('app_selcom_checkout_model');
            $row = $this->app_selcom_checkout_model->get_by_token($orderId);
            if (!empty($row) && $row->status !== 'completed') {
                $selcom = get_payment_gateway('selcom');
                if (!empty($selcom)) {
                    $this->load->helper('selcom');
                    $statusResponse = selcom_api_request($selcom, '/v1/checkout/order-status', 'GET', array('order_id' => $orderId), array('order_id'));
                    $statusData = !empty($statusResponse['data'][0]) ? $statusResponse['data'][0] : array();
                    $paymentStatus = strtoupper((string) ($statusData['payment_status'] ?? ''));

                    if (strtoupper((string) ($statusResponse['result'] ?? '')) === 'SUCCESS' && $paymentStatus === 'COMPLETED') {
                        $cartFinal = json_decode($row->cart_final_json, false);
                        $cartTotal = json_decode($row->cart_total_json, false);
                        if (!empty($cartFinal) && !empty($cartTotal)) {
                            $savedAuth = $this->auth_check;
                            $savedUser = isset($this->auth_user) ? $this->auth_user : null;
                            if (!empty($row->user_id)) {
                                $u = $this->auth_model->get_user((int) $row->user_id);
                                if (!empty($u)) {
                                    $this->auth_check = true;
                                    $this->auth_user = $u;
                                }
                            }

                            $this->restore_app_selcom_shipping_session($row);

                            $this->session->set_userdata('mds_shopping_cart_final', $cartFinal);
                            $this->session->set_userdata('mds_shopping_cart_total_final', $cartTotal);
                            $txRef = !empty($statusData['reference']) ? $statusData['reference'] : (!empty($statusData['transid']) ? $statusData['transid'] : $orderId);
                            $data_transaction = array(
                                'payment_method' => $selcom->name,
                                'payment_id' => $txRef,
                                'currency' => !empty($statusData['currency']) ? strtoupper((string) $statusData['currency']) : strtoupper((string) $row->currency),
                                'payment_amount' => !empty($statusData['amount']) ? (float) $statusData['amount'] : (float) $row->total_amount,
                                'payment_status' => 'Succeeded',
                            );
                            $response = $this->execute_payment($data_transaction, 'sale', lang_base_url());
                            $this->auth_check = $savedAuth;
                            $this->auth_user = $savedUser;
                            if ($response->result == 1 && !empty($response->order_primary_id)) {
                                $this->app_selcom_checkout_model->mark_completed($orderId, (int) $response->order_primary_id);
                            } else {
                                $this->app_selcom_checkout_model->mark_failed($orderId);
                            }
                        } else {
                            $this->app_selcom_checkout_model->mark_failed($orderId);
                        }
                    } elseif (in_array($paymentStatus, array('CANCELLED', 'FAILED', 'DECLINED', 'EXPIRED'), true)) {
                        $this->app_selcom_checkout_model->mark_failed($orderId);
                    }
                }
            }
        }

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode(array(
                'result' => 'SUCCESS',
                'resultcode' => '000',
                'message' => 'ACK'
            )));
    }

    /**
     * Execute Sale Payment
     */
    public function execute_payment($data_transaction, $payment_type, $base_url)
    {
        //response object
        $response = new stdClass();
        $response->result = 0;
        $response->message = "";
        $response->redirect_url = "";
        $data_transaction["payment_status"] = "payment_received";
        if ($payment_type == 'sale') {
            //add order
            $order_id = $this->order_model->add_order($data_transaction);
            $order = $this->order_model->get_order($order_id);
            if (!empty($order)) {
                $response->order_primary_id = (int) $order->id;
                //decrease product quantity after sale
                $this->order_model->decrease_product_stock_after_sale($order->id);
                //send email
                if ($this->general_settings->send_email_buyer_purchase == 1) {
                    $email_data = array(
                        'email_type' => 'new_order',
                        'order_id' => $order_id
                    );
                    $this->session->set_userdata('mds_send_email_data', json_encode($email_data));
                }
                //set response and redirect URLs
                $response->result = 1;
                $response->redirect_url = $base_url . get_route("order_details", true) . $order->order_number;
                if ($order->buyer_id == 0) {
                    $this->session->set_userdata('mds_show_order_completed_page', 1);
                    $response->redirect_url = $base_url . get_route("order_completed", true) . $order->order_number;
                } else {
                    $response->message = trans("msg_order_completed");
                }
            } else {
                //could not added to the database
                $response->message = trans("msg_payment_database_error");
                $response->result = 0;
                $response->redirect_url = $base_url . get_route("cart", true) . get_route("payment");
            }
        } elseif ($payment_type == 'membership') {
            $plan_id = $this->session->userdata('modesy_selected_membership_plan_id');
            $plan = null;
            if (!empty($plan_id)) {
                $plan = $this->membership_model->get_plan($plan_id);
            }
            if (!empty($plan)) {
                //add user membership plan
                $this->membership_model->add_user_plan($data_transaction, $plan, $this->auth_user->id);
                //add transaction
                $this->membership_model->add_membership_transaction($data_transaction, $plan);
                //set response and redirect URLs
                $response->result = 1;
                $response->redirect_url = $base_url . get_route("membership_payment_completed") . "?method=gtw";
            } else {
                //could not added to the database
                $response->message = trans("msg_payment_database_error");
                $response->result = 0;
                $response->redirect_url = $base_url . get_route("cart", true) . get_route("payment") . "?payment_type=membership";
            }
        } elseif ($payment_type == 'promote') {
            $promoted_plan = $this->session->userdata('modesy_selected_promoted_plan');
            if (!empty($promoted_plan)) {
                //add to promoted products
                $this->promote_model->add_to_promoted_products($promoted_plan);
                //add transaction
                $this->promote_model->add_promote_transaction($data_transaction);
                //reset cache
                reset_cache_data_on_change();
                reset_user_cache_data($this->auth_user->id);
                //set response and redirect URLs
                $response->result = 1;
                $response->redirect_url = $base_url . get_route("promote_payment_completed") . "?method=gtw&product_id=" . $promoted_plan->product_id;
            } else {
                //could not added to the database
                $response->message = trans("msg_payment_database_error");
                $response->result = 0;
                $response->redirect_url = $base_url . get_route("cart", true) . get_route("payment") . "?payment_type=promote";
            }
        }
        //reset session for the payment
        @$this->session->unset_userdata('mds_payment_cart_data');
        //return response
        return $response;
    }

    /**
     * Payment with Bank Transfer
     */
    public function bank_transfer_payment_post()
    {
        $mds_payment_type = $this->input->post('mds_payment_type', true);

        if ($mds_payment_type == 'membership') {
            $plan_id = $this->session->userdata('modesy_selected_membership_plan_id');
            $plan = null;
            if (!empty($plan_id)) {
                $plan = $this->membership_model->get_plan($plan_id);
            }
            if (!empty($plan)) {
                $data_transaction = array(
                    'payment_method' => 'Bank Transfer',
                    'payment_status' => 'awaiting_payment',
                    'payment_id' => $this->session->userdata('mds_membership_bank_transaction_number')
                );
                //add user membership plan
                $this->membership_model->add_user_plan($data_transaction, $plan, $this->auth_user->id);
                //add transaction
                $this->membership_model->add_membership_transaction_bank($data_transaction, $plan);
                redirect(generate_url("membership_payment_completed") . "?method=bank_transfer&transaction_number=" . $data_transaction['payment_id']);
            }
            $this->session->set_flashdata('error', trans("msg_error"));
            redirect(generate_url("cart", "payment") . "?payment_type=membership");
        } elseif ($mds_payment_type == 'promote') {
            $promoted_plan = $this->session->userdata('modesy_selected_promoted_plan');
            if (!empty($promoted_plan)) {
                $transaction_number = $this->session->userdata('mds_promote_bank_transaction_number');
                //add transaction
                $this->promote_model->add_promote_transaction_bank($promoted_plan, $transaction_number);

                $type = $this->session->userdata('mds_promote_product_type');

                if (empty($type)) {
                    $type = "new";
                }
                redirect(generate_url("promote_payment_completed") . "?method=bank_transfer&transaction_number=" . $transaction_number . "&product_id=" . $promoted_plan->product_id);
            }
            $this->session->set_flashdata('error', trans("msg_error"));
            redirect(generate_url("cart", "payment") . "?payment_type=promote");
        } else {
            //add order
            $order_id = $this->order_model->add_order_offline_payment("Bank Transfer");
            $order = $this->order_model->get_order($order_id);
            if (!empty($order)) {
                //decrease product quantity after sale
                $this->order_model->decrease_product_stock_after_sale($order->id);
                //send email
                if ($this->general_settings->send_email_buyer_purchase == 1) {
                    $email_data = array(
                        'email_type' => 'new_order',
                        'order_id' => $order_id
                    );
                    $this->session->set_userdata('mds_send_email_data', json_encode($email_data));
                }

                if ($order->buyer_id == 0) {
                    $this->session->set_userdata('mds_show_order_completed_page', 1);
                    redirect(generate_url("order_completed") . "/" . $order->order_number);
                } else {
                    $this->session->set_flashdata('success', trans("msg_order_completed"));
                    redirect(generate_url("order_details") . "/" . $order->order_number);
                }
            }

            $this->session->set_flashdata('error', trans("msg_error"));
            redirect(generate_url("cart", "payment"));
        }
    }

    /**
     * Cash on Delivery
     */
    public function cash_on_delivery_payment_post()
    {
        //add order
        $order_id = $this->order_model->add_order_offline_payment("Cash On Delivery");
        $order = $this->order_model->get_order($order_id);
        if (!empty($order)) {
            //decrease product quantity after sale
            $this->order_model->decrease_product_stock_after_sale($order->id);
            //send email
            if ($this->general_settings->send_email_buyer_purchase == 1) {
                $email_data = array(
                    'email_type' => 'new_order',
                    'order_id' => $order_id
                );
                $this->session->set_userdata('mds_send_email_data', json_encode($email_data));
            }

            if ($order->buyer_id == 0) {
                $this->session->set_userdata('mds_show_order_completed_page', 1);
                redirect(generate_url("order_completed") . "/" . $order->order_number);
            } else {
                $this->session->set_flashdata('success', trans("msg_order_completed"));
                redirect(generate_url("order_details") . "/" . $order->order_number);
            }
        }

        $this->session->set_flashdata('error', trans("msg_error"));
        redirect(generate_url("cart", "payment"));
    }

    /**
     * Order Completed
     */
    public function order_completed($order_number)
    {
        $data['title'] = trans("msg_order_completed");
        $data['description'] = trans("msg_order_completed") . " - " . $this->app_name;
        $data['keywords'] = trans("msg_order_completed") . "," . $this->app_name;

        $data['order'] = $this->order_model->get_order_by_order_number($order_number);

        if (empty($data['order'])) {
            redirect(lang_base_url());
        }

        if (empty($this->session->userdata('mds_show_order_completed_page'))) {
            redirect(lang_base_url());
        }

        $this->load->view('partials/_header', $data);
        $this->load->view('cart/order_completed', $data);
        $this->load->view('partials/_footer');
    }

    /**
     * Membership Payment Completed
     */
    public function membership_payment_completed()
    {
        $data['title'] = trans("msg_payment_completed");
        $data['description'] = trans("msg_payment_completed") . " - " . $this->app_name;
        $data['keywords'] = trans("payment") . "," . $this->app_name;
        $transaction_insert_id = $this->session->userdata('mds_membership_transaction_insert_id');
        if (empty($transaction_insert_id)) {
            redirect(lang_base_url());
        }
        $data["transaction"] = $this->membership_model->get_membership_transaction($transaction_insert_id);
        if (empty($data["transaction"])) {
            redirect(lang_base_url());
            exit();
        }

        $data["method"] = $this->input->get('method');
        $data["transaction_number"] = $this->input->get('transaction_number');


        $this->load->view('partials/_header', $data);
        $this->load->view('cart/membership_payment_completed', $data);
        $this->load->view('partials/_footer');
    }

    /**
     * Promote Payment Completed
     */
    public function promote_payment_completed()
    {
        $data['title'] = trans("msg_payment_completed");
        $data['description'] = trans("msg_payment_completed") . " - " . $this->app_name;
        $data['keywords'] = trans("payment") . "," . $this->app_name;
        $transaction_insert_id = $this->session->userdata('mds_promoted_transaction_insert_id');
        if (empty($transaction_insert_id)) {
            redirect(lang_base_url());
        }
        $data["transaction"] = $this->promote_model->get_promotion_transaction($transaction_insert_id);
        if (empty($data["transaction"])) {
            redirect(lang_base_url());
            exit();
        }
        $data["method"] = $this->input->get('method');
        $data["transaction_number"] = $this->input->get('transaction_number');

        $this->load->view('partials/_header', $data);
        $this->load->view('cart/promote_payment_completed', $data);
        $this->load->view('partials/_footer');
    }

    /**
     * POST /v1/checkout/selcom/init (JSON) — Flutter / API clients.
     * Body: { "lines": [{"product_id":1,"quantity":1}], "buyer_name", "buyer_email", "buyer_phone" }
     * Header (optional): Authorization: Bearer <users.token from /v1/auth/login>
     */
    public function api_selcom_checkout_init()
    {
        $this->api_flutter_set_cors();
        $this->output->set_content_type('application/json');
        if ($this->input->server('REQUEST_METHOD') === 'OPTIONS') {
            $this->output->set_status_header(200);
            return;
        }
        if ($this->input->method() !== 'post') {
            $this->output->set_status_header(405);
            $this->output->set_output(json_encode(array('success' => false, 'error' => 'Method not allowed')));
            return;
        }

        $raw = $this->input->raw_input_stream;
        if ($raw === '' || $raw === null) {
            $raw = file_get_contents('php://input');
        }
        $input = json_decode((string) $raw, true);
        if (!is_array($input)) {
            $input = array();
        }

        $lines = isset($input['lines']) ? $input['lines'] : array();
        $shipping_address = null;
        if (!empty($input['shipping_address']) && is_array($input['shipping_address'])) {
            $shipping_address = $input['shipping_address'];
        }
        $auth_header = $this->input->get_request_header('Authorization', true);
        $user = null;
        if (!empty($auth_header) && preg_match('/Bearer\s+(\S+)/i', $auth_header, $m)) {
            $this->load->model('auth_model');
            $user = $this->auth_model->get_user_by_token(trim($m[1]));
        }

        $buyer_name = isset($input['buyer_name']) ? trim((string) $input['buyer_name']) : '';
        $buyer_email = isset($input['buyer_email']) ? trim((string) $input['buyer_email']) : '';
        $buyer_phone = isset($input['buyer_phone']) ? preg_replace('/\s+/', '', (string) $input['buyer_phone']) : '';

        if (!empty($user)) {
            if ($buyer_name === '') {
                $buyer_name = trim(((string) ($user->first_name ?? '')) . ' ' . ((string) ($user->last_name ?? '')));
            }
            if ($buyer_name === '') {
                $buyer_name = (string) ($user->username ?? 'Customer');
            }
            if ($buyer_email === '' && !empty($user->email)) {
                $buyer_email = (string) $user->email;
            }
            if ($buyer_phone === '' && !empty($user->phone_number)) {
                $buyer_phone = preg_replace('/\s+/', '', (string) $user->phone_number);
            }
        }

        if ($buyer_email === '' || !filter_var($buyer_email, FILTER_VALIDATE_EMAIL)) {
            $this->output->set_status_header(400);
            $this->output->set_output(json_encode(array('success' => false, 'error' => 'Valid buyer_email is required')));
            return;
        }
        if ($buyer_name === '') {
            $this->output->set_status_header(400);
            $this->output->set_output(json_encode(array('success' => false, 'error' => 'buyer_name is required')));
            return;
        }
        if ($buyer_phone === '') {
            $buyer_phone = '255700000000';
        }

        $selcom = get_payment_gateway('selcom');
        if (empty($selcom)) {
            $this->output->set_status_header(503);
            $this->output->set_output(json_encode(array('success' => false, 'error' => 'Selcom is not configured. Add it in Admin → Payment Settings.')));
            return;
        }
        if (isset($selcom->status) && (int) $selcom->status !== 1) {
            $this->output->set_status_header(503);
            $this->output->set_output(json_encode(array('success' => false, 'error' => 'Selcom is disabled. Enable it in Admin → Payment Settings.')));
            return;
        }
        if (empty($selcom->public_key) || empty($selcom->secret_key) || empty($selcom->locale)) {
            $this->output->set_status_header(503);
            $this->output->set_output(json_encode(array('success' => false, 'error' => 'Selcom credentials incomplete (Vendor ID, API Key, Secret).')));
            return;
        }

        if (!empty($shipping_address) && empty($shipping_address['email'])) {
            $shipping_address['email'] = $buyer_email;
        }

        $prepared = $this->cart_model->prepare_checkout_from_api_lines($lines, $shipping_address);
        if (!empty($prepared['error'])) {
            $this->output->set_status_header(400);
            $this->output->set_output(json_encode(array('success' => false, 'error' => $prepared['error'])));
            return;
        }

        /** @var array $cartFinal */
        $cartFinal = $prepared['cart_final'];
        $cartTotal = $prepared['cart_total'];
        $payAmt = $prepared['payment_amount'];
        $orderToken = generate_token();

        $this->load->model('app_selcom_checkout_model');
        $pending_row = array(
            'order_token' => $orderToken,
            'user_id' => !empty($user) ? (int) $user->id : null,
            'buyer_email' => $buyer_email,
            'buyer_name' => $buyer_name,
            'buyer_phone' => $buyer_phone,
            'cart_final_json' => json_encode($cartFinal),
            'cart_total_json' => json_encode($cartTotal),
            'currency' => (string) $payAmt->currency,
            'total_amount' => (float) $payAmt->total,
            'status' => 'pending',
        );
        if (!empty($prepared['shipping_snapshot'])) {
            $pending_row['shipping_json'] = json_encode($prepared['shipping_snapshot']);
        }
        $this->app_selcom_checkout_model->insert_pending($pending_row);

        $returnUrl = base_url() . 'selcom-app-payment-return?payment_type=sale&order_id=' . rawurlencode($orderToken);
        $webhookUrl = base_url() . 'selcom-payment-webhook?order_id=' . rawurlencode($orderToken);

        $amount = (float) $payAmt->total;
        if (filter_var($amount, FILTER_VALIDATE_INT) === false) {
            $amount = (float) number_format($amount, 2, '.', '');
        }

        $payload = array(
            'vendor' => trim($selcom->locale),
            'order_id' => $orderToken,
            'buyer_email' => $buyer_email,
            'buyer_name' => $buyer_name,
            'buyer_phone' => $buyer_phone,
            'amount' => $amount,
            'currency' => strtoupper((string) $payAmt->currency),
            'payment_methods' => 'ALL',
            'redirect_url' => base64_encode($returnUrl),
            'cancel_url' => base64_encode($returnUrl . '&cancelled=1'),
            'webhook' => base64_encode($webhookUrl),
            'buyer_remarks' => 'App order ' . $orderToken,
            'merchant_remarks' => 'Azura Mall app checkout',
            'no_of_items' => count($cartFinal),
        );

        $this->load->helper('selcom');
        $response = selcom_api_request($selcom, '/v1/checkout/create-order-minimal', 'POST', $payload, array(
            'vendor', 'order_id', 'buyer_email', 'buyer_name', 'buyer_phone', 'amount', 'currency', 'payment_methods',
            'redirect_url', 'cancel_url', 'webhook', 'buyer_remarks', 'merchant_remarks', 'no_of_items',
        ));

        if (empty($response) || !isset($response['result']) || strtoupper((string) $response['result']) !== 'SUCCESS') {
            $this->app_selcom_checkout_model->mark_failed($orderToken);
            $this->output->set_status_header(502);
            $this->output->set_output(json_encode(array(
                'success' => false,
                'error' => !empty($response['message']) ? (string) $response['message'] : 'Selcom create-order failed',
            )));
            return;
        }

        $gatewayUrl = '';
        if (!empty($response['data'][0]['payment_gateway_url'])) {
            $rawUrl = $response['data'][0]['payment_gateway_url'];
            $decoded = base64_decode($rawUrl, true);
            $gatewayUrl = !empty($decoded) ? $decoded : $rawUrl;
        }
        if ($gatewayUrl === '') {
            $this->app_selcom_checkout_model->mark_failed($orderToken);
            $this->output->set_status_header(502);
            $this->output->set_output(json_encode(array('success' => false, 'error' => 'Selcom did not return a checkout URL')));
            return;
        }

        $this->output->set_output(json_encode(array(
            'success' => true,
            'order_token' => $orderToken,
            'payment_gateway_url' => $gatewayUrl,
            'amount' => $amount,
            'currency' => strtoupper((string) $payAmt->currency),
        )));
    }

    /**
     * Payment session for embedded Selcom on cart/shipping (logged-in combined checkout).
     * Mirrors cart/payment so selcom-payment-post has token, amount, and final cart snapshot.
     */
    private function prime_mds_payment_cart_session_sale($cart_items, $cart_total)
    {
        if (empty($cart_items) || empty($cart_total)) {
            return;
        }
        $obj_amount = $this->cart_model->convert_currency_by_payment_gateway($cart_total->total, 'sale');
        $total_amount = $obj_amount->total;
        $currency = $obj_amount->currency;
        if (filter_var($total_amount, FILTER_VALIDATE_INT) === false) {
            $total_amount = number_format((float) $total_amount, 2, '.', '');
        }
        $sess_data = new stdClass();
        $sess_data->mds_payment_token = generate_token();
        $sess_data->currency = $currency;
        $sess_data->total_amount = $total_amount;
        $sess_data->payment_type = 'sale';
        $this->session->set_userdata('mds_payment_cart_data', $sess_data);
        $this->session->set_userdata('mds_shopping_cart_final', $cart_items);
        $this->session->set_userdata('mds_shopping_cart_total_final', $cart_total);
    }

    /**
     * Restore shipping session saved during app Selcom init (required for order_shipping rows).
     */
    private function restore_app_selcom_shipping_session($row)
    {
        if (empty($row) || empty($row->shipping_json)) {
            return;
        }
        $snap = json_decode($row->shipping_json, true);
        if (!is_array($snap)) {
            return;
        }
        if (!empty($snap['cart_shipping'])) {
            $cs = json_decode(json_encode($snap['cart_shipping']), false);
            $this->session->set_userdata('mds_cart_shipping', $cs);
        }
        if (!empty($snap['mds_seller_shipping_costs']) && is_array($snap['mds_seller_shipping_costs'])) {
            $costs = array();
            foreach ($snap['mds_seller_shipping_costs'] as $sid => $data) {
                if (!is_array($data)) {
                    continue;
                }
                $o = new stdClass();
                $o->shipping_method_id = (int) (isset($data['shipping_method_id']) ? $data['shipping_method_id'] : 0);
                $o->cost = isset($data['cost']) ? (float) $data['cost'] : 0;
                $costs[(int) $sid] = $o;
            }
            if (item_count($costs) > 0) {
                $this->session->set_userdata('mds_seller_shipping_costs', $costs);
            }
        }
        if (!empty($snap['mds_selected_shipping_method_ids'])) {
            $this->session->set_userdata('mds_selected_shipping_method_ids', $snap['mds_selected_shipping_method_ids']);
        }
    }

    /**
     * Browser return after Selcom hosted checkout (mobile app WebView / external browser).
     */
    public function selcom_app_payment_return()
    {
        $this->load->helper('selcom');
        $this->load->model('app_selcom_checkout_model');
        $orderId = input_get('order_id');
        $cancelled = input_get('cancelled');
        if (!empty($cancelled)) {
            $this->session->set_flashdata('error', 'Payment cancelled.');
            redirect(generate_url('cart', 'cart'));
            return;
        }
        if ($orderId === '') {
            $this->session->set_flashdata('error', trans('invalid_attempt'));
            redirect(lang_base_url());
            return;
        }

        $row = $this->app_selcom_checkout_model->get_by_token($orderId);
        if (empty($row)) {
            $this->session->set_flashdata('error', trans('msg_error'));
            redirect(lang_base_url());
            return;
        }

        if ($row->status === 'completed' && !empty($row->internal_order_id)) {
            $existing = $this->order_model->get_order((int) $row->internal_order_id);
            if (!empty($existing)) {
                if ((int) $existing->buyer_id === 0) {
                    $this->session->set_userdata('mds_show_order_completed_page', 1);
                    redirect(lang_base_url() . get_route('order_completed', true) . $existing->order_number);
                } else {
                    redirect(lang_base_url() . get_route('order_details', true) . $existing->order_number);
                }
                return;
            }
        }

        $selcom = get_payment_gateway('selcom');
        if (empty($selcom)) {
            $this->session->set_flashdata('error', 'Selcom not configured');
            redirect(lang_base_url());
            return;
        }

        $statusResponse = selcom_api_request($selcom, '/v1/checkout/order-status', 'GET', array('order_id' => $orderId), array('order_id'));
        if (empty($statusResponse) || strtoupper((string) ($statusResponse['result'] ?? '')) !== 'SUCCESS') {
            $this->session->set_flashdata('error', !empty($statusResponse['message']) ? (string) $statusResponse['message'] : trans('msg_error'));
            redirect(generate_url('cart', 'cart'));
            return;
        }

        $statusData = !empty($statusResponse['data'][0]) ? $statusResponse['data'][0] : array();
        $paymentStatus = strtoupper((string) ($statusData['payment_status'] ?? ''));
        if ($paymentStatus !== 'COMPLETED') {
            $this->session->set_flashdata('error', 'Payment not completed. Status: ' . ($paymentStatus !== '' ? $paymentStatus : 'UNKNOWN'));
            redirect(generate_url('cart', 'cart'));
            return;
        }

        $cartFinal = json_decode($row->cart_final_json, false);
        $cartTotal = json_decode($row->cart_total_json, false);
        if (empty($cartFinal) || empty($cartTotal)) {
            $this->session->set_flashdata('error', trans('msg_error'));
            redirect(lang_base_url());
            return;
        }

        $this->restore_app_selcom_shipping_session($row);

        $savedAuth = $this->auth_check;
        $savedUser = isset($this->auth_user) ? $this->auth_user : null;
        if (!empty($row->user_id)) {
            $u = $this->auth_model->get_user((int) $row->user_id);
            if (!empty($u)) {
                $this->auth_check = true;
                $this->auth_user = $u;
            }
        }

        $this->session->set_userdata('mds_shopping_cart_final', $cartFinal);
        $this->session->set_userdata('mds_shopping_cart_total_final', $cartTotal);

        $txRef = !empty($statusData['reference']) ? $statusData['reference'] : (!empty($statusData['transid']) ? $statusData['transid'] : $orderId);
        $data_transaction = array(
            'payment_method' => $selcom->name,
            'payment_id' => $txRef,
            'currency' => !empty($statusData['currency']) ? strtoupper((string) $statusData['currency']) : strtoupper((string) $row->currency),
            'payment_amount' => !empty($statusData['amount']) ? (float) $statusData['amount'] : (float) $row->total_amount,
            'payment_status' => 'Succeeded',
        );

        $response = $this->execute_payment($data_transaction, 'sale', lang_base_url());
        $this->auth_check = $savedAuth;
        $this->auth_user = $savedUser;

        if ($response->result == 1) {
            if (!empty($response->order_primary_id)) {
                $this->app_selcom_checkout_model->mark_completed($orderId, (int) $response->order_primary_id);
            }
            $this->session->set_flashdata('success', $response->message);
            redirect($response->redirect_url);
        } else {
            $this->session->set_flashdata('error', $response->message);
            redirect($response->redirect_url);
        }
    }

    //get shipping method by location
    public function get_shipping_methods_by_location()
    {
        $data = array(
            'result' => 0,
            'html_content' => ""
        );
        $state_id = $this->input->post('state_id', true);
        $cart_items = $this->session_cart_items;
        if (!empty($state_id)) {
            $vars = array(
                "shipping_methods" => $this->shipping_model->get_seller_shipping_methods_array($cart_items, $state_id)
            );
            $html_content = $this->load->view('cart/_shipping_methods', $vars, true);
            $data['result'] = 1;
            $data['html_content'] = $html_content;
        }
        echo json_encode($data);
    }
}
