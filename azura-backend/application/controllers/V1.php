<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once APPPATH . 'controllers/Flutter_v1_trait.php';

/**
 * Simple API v1 controller for the Flutter app.
 * GET /v1/category/list returns categories as JSON.
 */
class V1 extends CI_Controller {

    use Flutter_v1_trait;

    /** @var object|null Cached general_settings from config (avoids dynamic property issues on PHP 8.2+). */
    public $general_settings;

    /** @var bool */
    public $auth_check = false;

    public function __construct() {
        parent::__construct();
        try {
            $this->load->database();
            $this->general_settings = $this->config->item('general_settings');
            // Keep constructor lightweight for API reliability; avoid non-essential DB queries here.
            $this->auth_check = false;
            $this->_set_cors_headers();
        } catch (Throwable $e) {
            $this->output->set_content_type('application/json');
            $this->output->set_status_header(500);
            $this->output->set_output(json_encode(array('success' => false, 'error' => 'Initialization error: ' . $e->getMessage())));
            exit;
        }
    }

    /**
     * Set CORS headers to allow Flutter app (localhost and Vercel) to call the API.
     * This ensures CORS headers are always set, even on error responses.
     */
    private function _set_cors_headers() {
        $origin = $this->input->server('HTTP_ORIGIN');
        $allowed_origins = array(
            'http://localhost:8080',
            'http://localhost:3000',
            'http://127.0.0.1:8080',
            'http://127.0.0.1:3000',
            'https://web-five-tau-70.vercel.app',
            'https://web-vi5fbwp80-dawson-s-projects.vercel.app',
            'https://azuramall.shop',
            'https://www.azuramall.shop',
            'https://azuramall.com',
            'https://www.azuramall.com',
        );
        
        // Allow any vercel.app subdomain or localhost
        $allow_origin = null;
        if ($origin) {
            if (preg_match('/^https:\/\/.*\.vercel\.app$/', $origin)) {
                $allow_origin = $origin;
            } elseif (in_array($origin, $allowed_origins)) {
                $allow_origin = $origin;
            } elseif (preg_match('/^http:\/\/(localhost|127\.0\.0\.1)(:\d+)?$/', $origin)) {
                // Allow any localhost port for development
                $allow_origin = $origin;
            }
        }
        
        // Always set CORS headers - prioritize exact match, then localhost pattern
        if ($allow_origin) {
            $this->output->set_header('Access-Control-Allow-Origin: ' . $allow_origin);
        } elseif ($origin && (strpos($origin, 'http://localhost') === 0 || strpos($origin, 'http://127.0.0.1') === 0)) {
            // Fallback: allow any localhost for development
            $this->output->set_header('Access-Control-Allow-Origin: ' . $origin);
        } elseif ($origin && preg_match('/\.vercel\.app$/', $origin)) {
            // Fallback: allow any vercel.app subdomain if regex didn't match
            $this->output->set_header('Access-Control-Allow-Origin: ' . $origin);
        }
        
        $this->output->set_header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS, PATCH');
        $this->output->set_header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Accept, Origin');
        $this->output->set_header('Access-Control-Allow-Credentials: true');
        $this->output->set_header('Access-Control-Max-Age: 86400');

        // Handle preflight OPTIONS request - must exit before any output
        if ($this->input->server('REQUEST_METHOD') === 'OPTIONS') {
            $this->output->set_status_header(200);
            $this->output->_display();
            exit;
        }
    }

    /** GET /v1/ping */
    public function ping() {
        $this->output->set_content_type('application/json');
        $this->_set_cors_headers();
        $this->output->set_output(json_encode(array('success' => true, 'message' => 'pong')));
    }

    /**
     * POST /v1/user/register
     * Register a new user. Expects JSON: {username, email, password, phone_number}
     */
    public function user_register() {
        $this->output->set_content_type('application/json');
        
        // Ensure CORS headers are set before any output
        $this->_set_cors_headers();
        
        try {
            $this->load->model('auth_model');
            $this->load->helper(array('custom', 'string'));
        } catch (Throwable $e) {
            $this->output->set_status_header(500);
            $this->output->set_output(json_encode(array('success' => false, 'error' => 'Failed to load dependencies: ' . $e->getMessage())));
            return;
        }

        try {
            // Get raw input stream - CodeIgniter handles php://input caching
            $raw_input = $this->input->raw_input_stream;
            
            // If empty, try reading directly (fallback)
            if (empty($raw_input)) {
                $raw_input = file_get_contents('php://input');
            }
            
            // If still empty, try POST data (for form-encoded requests)
            if (empty($raw_input)) {
                $post_data = $this->input->post();
                if (!empty($post_data)) {
                    $raw_input = json_encode($post_data);
                }
            }
            
            if (empty($raw_input)) {
                // Debug: log what we're receiving
                $debug_info = array(
                    'raw_input_stream' => !empty($this->input->raw_input_stream) ? 'has_data' : 'empty',
                    'php_input' => !empty(file_get_contents('php://input')) ? 'has_data' : 'empty',
                    'post_data' => $this->input->post(),
                    'content_type' => $this->input->server('CONTENT_TYPE'),
                    'request_method' => $this->input->server('REQUEST_METHOD'),
                );
                $this->output->set_status_header(400);
                $this->output->set_output(json_encode(array('success' => false, 'error' => 'No input data received', 'debug' => $debug_info)));
                return;
            }
            
            $input = json_decode($raw_input, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->output->set_status_header(400);
                $this->output->set_output(json_encode(array('success' => false, 'error' => 'Invalid JSON: ' . json_last_error_msg())));
                return;
            }
            if (empty($input) || !is_array($input)) {
                $this->output->set_status_header(400);
                $this->output->set_output(json_encode(array('success' => false, 'error' => 'Invalid input format')));
                return;
            }

            $username = isset($input['username']) ? remove_special_characters($input['username']) : '';
            $email = isset($input['email']) ? trim($input['email']) : '';
            $password = isset($input['password']) ? $input['password'] : '';
            $phone_number = isset($input['phone_number']) ? $input['phone_number'] : '';

            if (empty($username) || empty($email) || empty($password)) {
                $this->output->set_status_header(400);
                $this->output->set_output(json_encode(array('success' => false, 'error' => 'Missing required fields')));
                return;
            }

            // Check if email exists
            $existing = $this->db->get_where('users', array('email' => $email))->row();
            if ($existing) {
                $this->output->set_status_header(400);
                $this->output->set_output(json_encode(array('success' => false, 'error' => 'Email already registered')));
                return;
            }

            // Load bcrypt library
            try {
                $this->load->library('bcrypt');
            } catch (Throwable $e) {
                $this->output->set_status_header(500);
                $this->output->set_output(json_encode(array('success' => false, 'error' => 'Failed to load bcrypt library: ' . $e->getMessage())));
                return;
            }
            
            // Generate slug and token
            try {
                $slug = $this->auth_model->generate_uniqe_slug($username);
                $token = generate_token();
            } catch (Throwable $e) {
                $this->output->set_status_header(500);
                $this->output->set_output(json_encode(array('success' => false, 'error' => 'Failed to generate slug/token: ' . $e->getMessage())));
                return;
            }
            
            $email_status = 1; // Auto-verify for now

            $data = array(
                'username' => $username,
                'slug' => $slug,
                'email' => $email,
                'phone_number' => $phone_number,
                'password' => $this->bcrypt->hash_password($password),
                'role' => 'member',
                'user_type' => 'registered',
                'token' => $token,
                'email_status' => $email_status,
                'banned' => 0,
                'last_seen' => date('Y-m-d H:i:s'),
                'created_at' => date('Y-m-d H:i:s'),
            );

            if ($this->db->insert('users', $data)) {
                $user_id = $this->db->insert_id();
                $this->output->set_output(json_encode(array('success' => true, 'message' => 'User registered successfully', 'user_id' => $user_id)));
            } else {
                $error = $this->db->error();
                $this->output->set_status_header(500);
                $this->output->set_output(json_encode(array('success' => false, 'error' => 'Registration failed: ' . ($error['message'] ?? 'Database error'))));
            }
        } catch (Throwable $e) {
            // Catch both Exception and Error (PHP 7+)
            $this->output->set_content_type('application/json');
            $this->output->set_status_header(500);
            $error_message = 'Server error';
            if (ENVIRONMENT === 'development') {
                $error_message .= ': ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine();
            } else {
                $error_message .= ': ' . $e->getMessage();
            }
            $this->output->set_output(json_encode(array('success' => false, 'error' => $error_message)));
        }
    }

    /**
     * POST /v1/auth/login
     * Login user. Expects form data: email, password, device_id
     */
    public function auth_login() {
        $this->output->set_content_type('application/json');
        $this->_set_cors_headers();
        try {
            // Try multiple methods to get POST data (CodeIgniter might not parse form-urlencoded correctly)
            $email = trim((string) $this->input->post('email'));
            $password = (string) $this->input->post('password');

            if ($email === '' || $password === '') {
                $raw_input = $this->input->raw_input_stream;
                if (empty($raw_input)) {
                    $raw_input = file_get_contents('php://input');
                }
                if (!empty($raw_input)) {
                    parse_str($raw_input, $post_data);
                    if ($email === '' && isset($post_data['email'])) {
                        $email = trim((string) $post_data['email']);
                    }
                    if ($password === '' && isset($post_data['password'])) {
                        $password = (string) $post_data['password'];
                    }
                }
                if (($email === '' || $password === '') && !empty($_POST)) {
                    if ($email === '' && isset($_POST['email'])) {
                        $email = trim((string) $_POST['email']);
                    }
                    if ($password === '' && isset($_POST['password'])) {
                        $password = (string) $_POST['password'];
                    }
                }
            }

            if ($email === '' || $password === '') {
                $this->output->set_status_header(400);
                $this->output->set_output(json_encode(array('success' => false, 'error' => 'Email and password required')));
                return;
            }

            // Query directly to avoid model/library compatibility issues on production runtimes.
            $this->db->where('email', $email);
            $this->db->limit(1);
            $user = $this->db->get('users')->row();
            if (empty($user)) {
                $this->output->set_status_header(401);
                $this->output->set_output(json_encode(array('success' => false, 'error' => 'Invalid email or password')));
                return;
            }

            $valid_password = false;
            if (!empty($user->password)) {
                $valid_password = password_verify($password, (string) $user->password);
                if (!$valid_password) {
                    // Backward compatibility for legacy hashes/checker.
                    try {
                        $this->load->library('bcrypt');
                        $valid_password = (bool) $this->bcrypt->check_password($password, $user->password);
                    } catch (Throwable $e) {
                        $valid_password = false;
                    }
                }
            }
            if (!$valid_password) {
                $this->output->set_status_header(401);
                $this->output->set_output(json_encode(array('success' => false, 'error' => 'Invalid email or password')));
                return;
            }

            if ((int) $user->email_status !== 1) {
                $this->output->set_status_header(403);
                $this->output->set_output(json_encode(array('success' => false, 'error' => 'Email not verified')));
                return;
            }
            if ((int) $user->banned === 1) {
                $this->output->set_status_header(403);
                $this->output->set_output(json_encode(array('success' => false, 'error' => 'Account banned')));
                return;
            }

            $display_name = trim(((string) ($user->first_name ?? '')) . ' ' . ((string) ($user->last_name ?? '')));
            if ($display_name === '') {
                $display_name = (string) ($user->username ?? '');
            }
            $this->output->set_output(json_encode(array(
                'success' => true,
                'id' => (int) $user->id,
                'name' => $display_name,
                'username' => (string) ($user->username ?? ''),
                'email' => (string) ($user->email ?? ''),
                'first_name' => (string) ($user->first_name ?? ''),
                'last_name' => (string) ($user->last_name ?? ''),
                'role' => (string) ($user->role ?? ''),
                'avatar' => (string) ($user->avatar ?? ''),
                'token' => !empty($user->token) ? (string) $user->token : '',
            )));
        } catch (Throwable $e) {
            log_message('error', 'V1 auth_login: ' . $e->getMessage());
            $this->output->set_status_header(500);
            $detail = (defined('ENVIRONMENT') && ENVIRONMENT !== 'production')
                ? $e->getMessage()
                : 'Login failed due to server error';
            $this->output->set_output(json_encode(array('success' => false, 'error' => $detail)));
        }
    }

    /**
     * GET /v1/create_admin?key=railway_import_2026_temp_key_change_me
     * One-time: creates default admin (admin@azura.local / Admin123!). Uses app Bcrypt so login works.
     */
    public function create_admin() {
        $this->output->set_content_type('application/json');
        $secret = 'railway_import_2026_temp_key_change_me';
        if ($this->input->get('key') !== $secret) {
            $this->output->set_status_header(403);
            $this->output->set_output(json_encode(array('success' => false, 'error' => 'Forbidden')));
            return;
        }

        $q = $this->db->query("SELECT id FROM users WHERE role = 'admin' LIMIT 1");
        if ($q && $q->num_rows() > 0) {
            $this->output->set_output(json_encode(array(
                'success' => true,
                'message' => 'An admin user already exists. Use Admin → Administrators to manage or reset password.',
                'login_url' => $this->config->item('base_url') . 'admin/login',
            )));
            return;
        }

        $this->load->library('bcrypt');
        $username = 'admin';
        $email    = 'admin@azura.local';
        $password = 'Admin123!';
        $slug     = 'admin';
        $token    = bin2hex(function_exists('random_bytes') ? random_bytes(16) : openssl_random_pseudo_bytes(16)) . '-' . rand(10000000, 99999999);
        $hash     = $this->bcrypt->hash_password($password);

        $ok = $this->db->insert('users', array(
            'username' => $username,
            'slug' => $slug,
            'email' => $email,
            'email_status' => 1,
            'token' => $token,
            'password' => $hash,
            'role' => 'admin',
            'user_type' => 'registered',
        ));
        if (!$ok) {
            $this->output->set_status_header(500);
            $this->output->set_output(json_encode(array('success' => false, 'error' => 'Insert failed')));
            return;
        }

        $this->output->set_output(json_encode(array(
            'success'  => true,
            'message'  => 'Admin user created. Use the credentials below to log in at the login_url.',
            'login_url' => $this->config->item('base_url') . 'admin/login',
            'email'    => $email,
            'password' => $password,
        )));
    }

    /**
     * GET /v1/category/list
     * Returns categories with id, name, slug, parent_id. Uses default lang_id 1.
     */
    public function category_list() {
        $this->output->set_content_type('application/json');
        $this->_set_cors_headers();
        try {
            $lang_id = (int) $this->input->get('lang_id');
            if ($lang_id < 1) {
                $lang_id = 1;
            }

            $this->db->select('c.id, c.slug, c.parent_id, c.category_order, c.image');
            $this->db->select('(SELECT name FROM categories_lang WHERE category_id = c.id AND lang_id = ' . $lang_id . ' LIMIT 1) AS name');
            $this->db->from('categories c');
            $this->db->where('c.visibility', 1);
            $this->db->order_by('c.parent_id', 'ASC');
            $this->db->order_by('c.category_order', 'ASC');
            $query = $this->db->get();
            if ($query->num_rows() == 0) {
                // Fallback: return all categories (visibility any) in case seed data uses 0
                $this->db->select('c.id, c.slug, c.parent_id, c.category_order, c.image');
                $this->db->select('(SELECT name FROM categories_lang WHERE category_id = c.id LIMIT 1) AS name');
                $this->db->from('categories c');
                $this->db->order_by('c.parent_id', 'ASC');
                $this->db->order_by('c.category_order', 'ASC');
                $query = $this->db->get();
            }
            $rows = $query->result();

            $base_url = $this->config->item('base_url');
            $list = array();
            foreach ($rows as $row) {
                $img = isset($row->image) ? trim((string) $row->image) : '';
                $image_out = null;
                if ($img !== '') {
                    $image_out = preg_match('#^https?://#i', $img) ? $img : $base_url . $img;
                }
                if ($image_out === null || $image_out === '') {
                    $this->db->reset_query();
                    $this->db->select('img.image_small');
                    $this->db->from('images img');
                    $this->db->join('products p', 'p.id = img.product_id');
                    $this->db->where('p.category_id', (int) $row->id);
                    $this->db->where('p.status', 1);
                    $this->db->where('p.visibility', 1);
                    $this->db->where('p.is_deleted', 0);
                    $this->db->where('p.product_type', 'physical');
                    $this->db->order_by('img.is_main', 'DESC');
                    $this->db->limit(1);
                    $ir = $this->db->get()->row();
                    if (!empty($ir) && !empty($ir->image_small)) {
                        $p = trim((string) $ir->image_small);
                        $image_out = preg_match('#^https?://#i', $p) ? $p : $base_url . $p;
                    }
                }
                $list[] = array(
                    'id' => (int) $row->id,
                    'name' => $row->name ?: '',
                    'slug' => $row->slug ?: '',
                    'parent_id' => (int) $row->parent_id,
                    'category_order' => (int) $row->category_order,
                    'image' => $image_out,
                );
            }
            $this->output->set_output(json_encode(array('success' => true, 'data' => $list)));
        } catch (Throwable $e) {
            log_message('error', 'V1 category_list: ' . $e->getMessage());
            $this->output->set_status_header(500);
            $detail = (defined('ENVIRONMENT') && ENVIRONMENT !== 'production') ? $e->getMessage() : 'Category query failed';
            $this->output->set_output(json_encode(array('success' => false, 'error' => $detail)));
        }
    }

    /**
     * GET /v1/product/list
     * Returns products list for Flutter app (paginated). Optional: category_id, page, per_page.
     */
    public function product_list() {
        $this->output->set_content_type('application/json');
        $this->_set_cors_headers();
        try {
            $page = max(1, (int) $this->input->get('page'));
            $per_page = min(50, max(1, (int) $this->input->get('per_page') ?: 20));
            $category_id = $this->input->get('category_id') ? (int) $this->input->get('category_id') : null;
            $lang_id = max(1, (int) $this->input->get('lang_id') ?: 1);

            $this->db->from('products p');
            $this->db->where('p.status', 1);
            $this->db->where('p.visibility', 1);
            $this->db->where('p.is_deleted', 0);
            $this->db->where('p.is_draft', 0);
            $this->db->where('p.product_type', 'physical');
            $this->db->where('EXISTS (SELECT 1 FROM images img WHERE img.product_id = p.id AND img.image_small IS NOT NULL AND img.image_small != \'\')', null, false);
            if ($category_id > 0) {
                $this->db->where('p.category_id', $category_id);
            }
            $total = $this->db->count_all_results();

            $this->db->select('p.id, p.category_id, p.slug, p.price, p.currency, p.discount_rate, p.user_id, p.rating, p.is_promoted, p.is_sold, p.created_at, p.product_type');
            $this->db->select('(SELECT title FROM product_details WHERE product_id = p.id AND lang_id = ' . $lang_id . ' LIMIT 1) AS title');
            $this->db->select('(SELECT image_small FROM images WHERE product_id = p.id AND is_main = 1 LIMIT 1) AS image');
            $this->db->from('products p');
            $this->db->where('p.status', 1);
            $this->db->where('p.visibility', 1);
            $this->db->where('p.is_deleted', 0);
            $this->db->where('p.is_draft', 0);
            $this->db->where('p.product_type', 'physical');
            $this->db->where('EXISTS (SELECT 1 FROM images img WHERE img.product_id = p.id AND img.image_small IS NOT NULL AND img.image_small != \'\')', null, false);
            if ($category_id > 0) {
                $this->db->where('p.category_id', $category_id);
            }
            $this->db->order_by('p.created_at', 'DESC');
            $this->db->limit($per_page, ($page - 1) * $per_page);
            $query = $this->db->get();

            $base_url = $this->config->item('base_url');
            $list = array();
            foreach ($query->result() as $row) {
                $img = isset($row->image) ? $row->image : null;
                $image_url = null;
                if (!empty($img)) {
                    $img = trim((string) $img);
                    if (preg_match('#^https?://#i', $img)) {
                        $image_url = $img;
                    } else {
                        $image_url = $base_url . $img;
                    }
                }
                $list[] = array(
                    'id' => (int) $row->id,
                    'category_id' => isset($row->category_id) ? (int) $row->category_id : 0,
                    'title' => $row->title ?: '',
                    'slug' => $row->slug ?: '',
                    'price' => (int) $row->price,
                    'currency' => $row->currency ?: '',
                    'discount_rate' => (int) $row->discount_rate,
                    'user_id' => (int) $row->user_id,
                    'rating' => $row->rating ?: '0',
                    'is_promoted' => (int) $row->is_promoted,
                    'is_sold' => (int) $row->is_sold,
                    'product_type' => isset($row->product_type) ? (string) $row->product_type : 'physical',
                    'image' => $image_url,
                    'created_at' => $row->created_at,
                );
            }

            $this->output->set_output(json_encode(array(
                'success' => true,
                'data' => $list,
                'pagination' => array(
                    'current_page' => $page,
                    'per_page' => $per_page,
                    'total' => $total,
                ),
            )));
        } catch (Throwable $e) {
            log_message('error', 'V1 product_list: ' . $e->getMessage());
            $this->output->set_status_header(500);
            $detail = (defined('ENVIRONMENT') && ENVIRONMENT !== 'production') ? $e->getMessage() : 'Product query failed';
            $this->output->set_output(json_encode(array('success' => false, 'error' => $detail)));
        }
    }

    /**
     * GET /v1/create_user?key=railway_register_2026_temp_key&username=dawin&email=dawinibra@gmail.com&password=dawinibra@gmail.com&phone=0750285659
     * Direct user creation endpoint for testing/initial setup
     */
    public function create_user() {
        $this->output->set_content_type('application/json');
        $secret = 'railway_register_2026_temp_key';
        if ($this->input->get('key') !== $secret) {
            $this->output->set_status_header(403);
            $this->output->set_output(json_encode(array('success' => false, 'error' => 'Forbidden')));
            return;
        }

        $username = $this->input->get('username') ?: 'dawin';
        $email = $this->input->get('email') ?: 'dawinibra@gmail.com';
        $password = $this->input->get('password') ?: 'dawinibra@gmail.com';
        $phone_number = $this->input->get('phone') ?: '0750285659';

        try {
            $this->load->model('auth_model');
            $this->load->library('bcrypt');
            $this->load->helper(array('custom', 'string'));

            // Check if user exists
            $existing = $this->db->get_where('users', array('email' => $email))->row();
            if ($existing) {
                $this->output->set_output(json_encode(array(
                    'success' => true,
                    'message' => 'User already exists',
                    'user' => array(
                        'id' => $existing->id,
                        'username' => $existing->username,
                        'email' => $existing->email,
                        'role' => $existing->role
                    )
                )));
                return;
            }

            // Clean and prepare
            $username_clean = remove_special_characters($username);
            $slug = $this->auth_model->generate_uniqe_slug($username_clean);
            $token = generate_token();
            $hashed_password = $this->bcrypt->hash_password($password);

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

            if ($this->db->insert('users', $data)) {
                $user_id = $this->db->insert_id();
                $user = $this->db->get_where('users', array('id' => $user_id))->row();
                
                $this->output->set_output(json_encode(array(
                    'success' => true,
                    'message' => 'User created successfully',
                    'user' => array(
                        'id' => $user_id,
                        'username' => $user->username,
                        'email' => $user->email,
                        'phone_number' => $user->phone_number ?? '',
                        'role' => $user->role,
                        'slug' => $user->slug ?? ''
                    ),
                    'login' => array('email' => $email, 'password' => $password)
                )));
            } else {
                $error = $this->db->error();
                $this->output->set_status_header(500);
                $this->output->set_output(json_encode(array('success' => false, 'error' => 'Insert failed', 'db_error' => $error)));
            }
        } catch (Throwable $e) {
            $this->output->set_status_header(500);
            $this->output->set_output(json_encode(array('success' => false, 'error' => $e->getMessage())));
        }
    }

    /**
     * POST /v1/user/delete
     * JSON body: user_id, email, password — verifies credentials then permanently deletes the account.
     */
    public function user_delete_account() {
        $this->output->set_content_type('application/json');
        $this->_set_cors_headers();
        if (strtoupper((string) $this->input->server('REQUEST_METHOD')) !== 'POST') {
            $this->output->set_status_header(405);
            $this->output->set_output(json_encode(array('success' => false, 'error' => 'Method not allowed')));
            return;
        }
        try {
            $raw = $this->input->raw_input_stream;
            if ($raw === '' || $raw === null) {
                $raw = file_get_contents('php://input');
            }
            $body = json_decode((string) $raw, true);
            if (!is_array($body)) {
                $body = array();
            }
            $user_id = isset($body['user_id']) ? (int) $body['user_id'] : 0;
            $email = isset($body['email']) ? trim((string) $body['email']) : '';
            $password = isset($body['password']) ? (string) $body['password'] : '';

            if ($user_id < 1 || $email === '' || $password === '') {
                $this->output->set_status_header(400);
                $this->output->set_output(json_encode(array('success' => false, 'error' => 'user_id, email, and password are required')));
                return;
            }

            $this->db->where('id', $user_id);
            $this->db->where('email', $email);
            $user = $this->db->get('users')->row();
            if (empty($user)) {
                $this->output->set_status_header(401);
                $this->output->set_output(json_encode(array('success' => false, 'error' => 'Invalid credentials')));
                return;
            }

            $valid_password = false;
            if (!empty($user->password)) {
                $valid_password = password_verify($password, (string) $user->password);
                if (!$valid_password) {
                    $this->load->library('bcrypt');
                    $valid_password = (bool) $this->bcrypt->check_password($password, $user->password);
                }
            }
            if (!$valid_password) {
                $this->output->set_status_header(401);
                $this->output->set_output(json_encode(array('success' => false, 'error' => 'Invalid credentials')));
                return;
            }

            $this->load->model('product_admin_model');
            $this->load->model('auth_model');
            $ok = $this->auth_model->delete_user($user_id);
            if ($ok) {
                $this->output->set_output(json_encode(array('success' => true, 'message' => 'Account deleted')));
            } else {
                $this->output->set_status_header(500);
                $this->output->set_output(json_encode(array('success' => false, 'error' => 'Could not delete account')));
            }
        } catch (Throwable $e) {
            log_message('error', 'V1 user_delete_account: ' . $e->getMessage());
            $this->output->set_status_header(500);
            $this->output->set_output(json_encode(array('success' => false, 'error' => 'Server error')));
        }
    }

    /**
     * GET /v1/location/countries — active countries for shipping dropdowns.
     */
    public function location_countries() {
        $this->output->set_content_type('application/json');
        $this->_set_cors_headers();
        try {
            $this->load->model('location_model');
            $rows = $this->location_model->get_active_countries();
            $out = array();
            foreach ($rows as $r) {
                $out[] = array(
                    'id' => (int) $r->id,
                    'name' => $r->name ?: '',
                );
            }
            $this->output->set_output(json_encode(array('success' => true, 'data' => $out)));
        } catch (Throwable $e) {
            log_message('error', 'V1 location_countries: ' . $e->getMessage());
            $this->output->set_status_header(500);
            $this->output->set_output(json_encode(array('success' => false, 'error' => 'Failed to load countries')));
        }
    }

    /**
     * GET /v1/location/states?country_id=1
     */
    public function location_states() {
        $this->output->set_content_type('application/json');
        $this->_set_cors_headers();
        $country_id = (int) $this->input->get('country_id');
        if ($country_id < 1) {
            $this->output->set_status_header(400);
            $this->output->set_output(json_encode(array('success' => false, 'error' => 'country_id required')));
            return;
        }
        try {
            $this->load->model('location_model');
            $rows = $this->location_model->get_states_by_country($country_id);
            $out = array();
            foreach ($rows as $r) {
                $out[] = array(
                    'id' => (int) $r->id,
                    'name' => $r->name ?: '',
                    'country_id' => (int) $r->country_id,
                );
            }
            $this->output->set_output(json_encode(array('success' => true, 'data' => $out)));
        } catch (Throwable $e) {
            log_message('error', 'V1 location_states: ' . $e->getMessage());
            $this->output->set_status_header(500);
            $this->output->set_output(json_encode(array('success' => false, 'error' => 'Failed to load states')));
        }
    }

    /**
     * GET /v1/debug/categories?key=railway_import_2026_temp_key_change_me
     * Returns counts and sample rows from categories + categories_lang. Disabled when DISABLE_DEBUG=1.
     */
    public function debug_categories() {
        if (getenv('DISABLE_DEBUG') === '1' || getenv('DISABLE_DEBUG') === 'true') {
            $this->output->set_status_header(404);
            $this->output->set_content_type('application/json');
            $this->output->set_output(json_encode(array('success' => false, 'error' => 'Not found')));
            return;
        }
        $secret_key = 'railway_import_2026_temp_key_change_me';
        $provided_key = $this->input->get('key');
        if ($provided_key !== $secret_key) {
            $this->output->set_status_header(403);
            $this->output->set_content_type('application/json');
            $this->output->set_output(json_encode(array('success' => false, 'error' => 'Forbidden')));
            return;
        }

        $this->output->set_content_type('application/json');
        $out = array('success' => true, 'categories' => array(), 'categories_lang' => array(), 'summary' => array(), 'error' => null);

        try {
            $q = $this->db->query('SELECT COUNT(*) AS n FROM categories');
            $out['summary']['categories_count'] = $q && $q->num_rows() ? (int) $q->row()->n : 0;

            $q = $this->db->query('SELECT COUNT(*) AS n FROM categories_lang');
            $out['summary']['categories_lang_count'] = $q && $q->num_rows() ? (int) $q->row()->n : 0;

            $q = $this->db->query('SELECT id, slug, parent_id, visibility, category_order FROM categories ORDER BY id ASC LIMIT 20');
            if ($q && $q->num_rows() > 0) {
                foreach ($q->result() as $row) {
                    $out['categories'][] = array(
                        'id' => (int) $row->id,
                        'slug' => $row->slug,
                        'parent_id' => (int) $row->parent_id,
                        'visibility' => (int) $row->visibility,
                        'category_order' => (int) $row->category_order,
                    );
                }
            }

            $q = $this->db->query('SELECT category_id, lang_id, name FROM categories_lang ORDER BY category_id, lang_id LIMIT 30');
            if ($q && $q->num_rows() > 0) {
                foreach ($q->result() as $row) {
                    $out['categories_lang'][] = array(
                        'category_id' => (int) $row->category_id,
                        'lang_id' => (int) $row->lang_id,
                        'name' => $row->name,
                    );
                }
            }
        } catch (Throwable $e) {
            $out['success'] = false;
            $out['error'] = $e->getMessage();
        }

        $this->output->set_output(json_encode($out));
    }
}
