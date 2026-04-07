<?php
/**
 * Direct user registration script
 * Usage: Visit https://azura-backend-production.up.railway.app/register_user.php?key=railway_register_2026_temp_key
 */

$SECRET_KEY = 'railway_register_2026_temp_key';

if (!isset($_GET['key']) || $_GET['key'] !== $SECRET_KEY) {
    http_response_code(403);
    header('Content-Type: application/json');
    echo json_encode(array('success' => false, 'error' => 'Forbidden'));
    exit;
}

// Set CORS
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if ($origin && (strpos($origin, 'http://localhost') === 0 || strpos($origin, 'http://127.0.0.1') === 0 || preg_match('/^https:\/\/.*\.vercel\.app$/', $origin))) {
    header('Access-Control-Allow-Origin: ' . $origin);
}
header('Content-Type: application/json');

// User data
$username = 'dawin';
$email = 'dawinibra@gmail.com';
$password = 'dawinibra@gmail.com';
$phone_number = '0750285659';

// Bootstrap CodeIgniter
$_SERVER['REQUEST_URI'] = '/register_user.php';
$_SERVER['SCRIPT_NAME'] = '/register_user.php';
$_SERVER['PATH_INFO'] = '';

// Set environment
$env = getenv('ENVIRONMENT') ?: (getenv('RAILWAY_PUBLIC_DOMAIN') ? 'production' : 'development');
define('ENVIRONMENT', $env);

// Load CodeIgniter
require_once __DIR__ . '/system/core/CodeIgniter.php';

// Get CI instance after bootstrap
$CI =& get_instance();
$CI->load->database();
$CI->load->library('bcrypt');
$CI->load->model('auth_model');
$CI->load->helper(array('custom', 'string'));

try {
    // Check existing
    $existing = $CI->db->get_where('users', array('email' => $email))->row();
    if ($existing) {
        echo json_encode(array(
            'success' => true,
            'message' => 'User already exists',
            'user_id' => $existing->id,
            'username' => $existing->username,
            'email' => $existing->email
        ));
        exit;
    }

    // Prepare data
    $username_clean = remove_special_characters($username);
    $slug = $CI->auth_model->generate_uniqe_slug($username_clean);
    $token = generate_token();
    $hashed_password = $CI->bcrypt->hash_password($password);

    $data = array(
        'username' => $username_clean,
        'slug' => $slug,
        'email' => $email,
        'phone_number' => $phone_number,
        'password' => $hashed_password,
        'role' => 'member',
        'user_type' => 'registered',
        'token' => $token,
        'email_status' => 1,
        'banned' => 0,
        'last_seen' => date('Y-m-d H:i:s'),
        'created_at' => date('Y-m-d H:i:s'),
    );

    if ($CI->db->insert('users', $data)) {
        $user_id = $CI->db->insert_id();
        $user = $CI->db->get_where('users', array('id' => $user_id))->row();
        
        echo json_encode(array(
            'success' => true,
            'message' => 'User registered successfully',
            'user' => array(
                'id' => $user_id,
                'username' => $user->username,
                'email' => $user->email,
                'phone_number' => $user->phone_number ?? '',
                'role' => $user->role
            ),
            'login' => array('email' => $email, 'password' => $password)
        ));
    } else {
        $error = $CI->db->error();
        http_response_code(500);
        echo json_encode(array('success' => false, 'error' => 'Insert failed', 'db_error' => $error));
    }
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(array(
        'success' => false,
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ));
}
