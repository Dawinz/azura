<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Selcom APIGW signing + HTTP (Checkout API).
 * @see https://developers.selcommobile.com/
 */

if (!function_exists('selcom_resolve_base_url')) {
    /**
     * @param object|null $selcom Row from payment_gateways (name_key selcom).
     */
    function selcom_resolve_base_url($selcom)
    {
        if (!empty($_ENV['SELCOM_BASE_URL'])) {
            return rtrim((string) $_ENV['SELCOM_BASE_URL'], '/');
        }
        if (!empty($selcom) && !empty($selcom->environment) && $selcom->environment === 'sandbox') {
            return 'https://apigwtest.selcommobile.com';
        }
        return 'https://apigw.selcommobile.com';
    }
}

if (!function_exists('selcom_digest')) {
    function selcom_digest($timestamp, $payload, $signedFields, $apiSecret)
    {
        $parts = array('timestamp=' . $timestamp);
        foreach ($signedFields as $field) {
            $value = isset($payload[$field]) ? $payload[$field] : '';
            if (is_array($value) || is_object($value)) {
                $value = json_encode($value);
            }
            $parts[] = $field . '=' . (string) $value;
        }
        $signingString = implode('&', $parts);
        return base64_encode(hash_hmac('sha256', $signingString, $apiSecret, true));
    }
}

if (!function_exists('selcom_api_request')) {
    /**
     * @param object      $selcom      payment_gateways row
     * @param string      $path        e.g. /v1/checkout/create-order-minimal
     * @param string      $method      GET or POST
     * @param array       $payload     Request body or query params
     * @param array       $signedFields Field names for Digest (order must match docs)
     * @return array Decoded JSON or empty array
     */
    function selcom_api_request($selcom, $path, $method, $payload, $signedFields)
    {
        $baseUrl = selcom_resolve_base_url($selcom);
        $timestamp = date('Y-m-d H:i:s');
        $body = '';
        $url = rtrim($baseUrl, '/') . $path;
        if (strtoupper($method) === 'GET') {
            $query = http_build_query($payload);
            if ($query !== '') {
                $url .= '?' . $query;
            }
        } else {
            $body = json_encode($payload);
        }

        $secret = trim((string) $selcom->secret_key);
        $digest = selcom_digest($timestamp, $payload, $signedFields, $secret);
        $headers = array(
            'Accept: application/json',
            'Content-Type: application/json',
            'Authorization: SELCOM ' . base64_encode(trim((string) $selcom->public_key)),
            'Digest-Method: HS256',
            'Digest: ' . $digest,
            'Timestamp: ' . $timestamp,
            'Signed-Fields: ' . implode(',', $signedFields),
        );

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 60,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => strtoupper($method),
            CURLOPT_HTTPHEADER => $headers,
        ));
        if (strtoupper($method) !== 'GET') {
            curl_setopt($curl, CURLOPT_POSTFIELDS, $body);
        }
        $response = curl_exec($curl);
        curl_close($curl);
        $decoded = json_decode((string) $response, true);
        return is_array($decoded) ? $decoded : array();
    }
}
