<?php

/**
 * CodeIgniter
 *
 * An open source application development framework for PHP
 *
 * This content is released under the MIT License (MIT)
 *
 * Copyright (c) 2014 - 2019, British Columbia Institute of Technology
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 *
 * @package	CodeIgniter
 * @author	EllisLab Dev Team
 * @copyright	Copyright (c) 2008 - 2014, EllisLab, Inc. (https://ellislab.com/)
 * @copyright	Copyright (c) 2014 - 2019, British Columbia Institute of Technology (https://bcit.ca/)
 * @license	https://opensource.org/licenses/MIT	MIT License
 * @link	https://codeigniter.com
 * @since	Version 1.0.0
 * @filesource
 */
/*
 *---------------------------------------------------------------
 * HANDLE STANDALONE IMPORT SCRIPT
 *---------------------------------------------------------------
 * 
 * Allow direct access to import_db.php before CodeIgniter loads
 */
if (isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], '/import_db.php') !== false) {
    require_once __DIR__ . '/import_db.php';
    exit;
}

/*
 *---------------------------------------------------------------
 * CORS HEADERS FOR API REQUESTS
 *---------------------------------------------------------------
 * Handle CORS preflight and set headers for all /v1/* API requests
 * This function ensures CORS headers are set even on errors
 */
function set_cors_headers_for_api() {
    $origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '';
    $allowed_origins = array(
        'http://localhost:8080',
        'http://localhost:3000',
        'http://127.0.0.1:8080',
        'http://127.0.0.1:3000',
        'https://web-five-tau-70.vercel.app',
        'https://web-vi5fbwp80-dawson-s-projects.vercel.app',
        'https://azuramall.com',
        'https://www.azuramall.com',
        'https://azuramall.shop',
        'https://www.azuramall.shop',
    );
    $extra = getenv('ALLOWED_ORIGINS');
    if ($extra !== false && $extra !== '') {
        foreach (array_map('trim', explode(',', $extra)) as $o) {
            if ($o !== '') {
                $allowed_origins[] = $o;
            }
        }
    }
    
    // Allow any vercel.app subdomain or localhost
    $allow_origin = null;
    if ($origin) {
        if (preg_match('/^https:\/\/.*\.vercel\.app$/', $origin)) {
            $allow_origin = $origin;
        } elseif (in_array($origin, $allowed_origins)) {
            $allow_origin = $origin;
        } elseif (preg_match('/^http:\/\/(localhost|127\.0\.0\.1)(:\d+)?$/', $origin)) {
            $allow_origin = $origin;
        }
    }
    
    // Always set CORS headers for API requests
    if ($allow_origin) {
        header('Access-Control-Allow-Origin: ' . $allow_origin);
    } elseif ($origin && (strpos($origin, 'http://localhost') === 0 || strpos($origin, 'http://127.0.0.1') === 0)) {
        // Allow any localhost port for development
        header('Access-Control-Allow-Origin: ' . $origin);
    } elseif ($origin && preg_match('/\.vercel\.app$/', $origin)) {
        // Allow any vercel.app subdomain (fallback if regex didn't match)
        header('Access-Control-Allow-Origin: ' . $origin);
    }
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS, PATCH');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Accept, Origin');
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Max-Age: 86400');
}

function open_api_mysqli_connection($db_host, $db_user, $db_pass, $db_name, $db_port, $attempts = 3) {
    $last_exception = null;
    for ($i = 0; $i < $attempts; $i++) {
        try {
            $mysqli = @new mysqli($db_host, $db_user, $db_pass, $db_name, $db_port);
            if (!$mysqli->connect_errno) {
                return $mysqli;
            }
        } catch (Throwable $e) {
            $last_exception = $e;
        }
        usleep(200000); // brief retry for transient DB connection issues
    }
    if ($last_exception !== null) {
        throw $last_exception;
    }
    return null;
}

/**
 * Categories hidden from GET /v1/category/list (slug or display name).
 * Must stay in sync with V1::_v1_category_is_hidden (this route is handled in index.php before CI loads).
 */
function azura_api_v1_category_is_hidden($slug, $name) {
    $slug = strtolower(trim((string) $slug));
    $name = strtolower(trim((string) $name));
    $pat = '(shoes|abaya|shirts|khanzu|kanzu)';
    if ($slug !== '' && preg_match('/(^|-)' . $pat . '(-|$)/', $slug)) {
        return true;
    }
    if ($name !== '' && preg_match('/^' . $pat . '(\s|[-_.\/,]|$)/u', $name)) {
        return true;
    }
    return false;
}

// Check if this is an API request
$is_api_request = (isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], '/v1/') !== false) || isset($_GET['demo_action']);

if ($is_api_request) {
    set_cors_headers_for_api();
    
    // Handle preflight OPTIONS request
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(200);
        exit;
    }

    // Lightweight API handlers — respond before CodeIgniter (avoids empty 500 on some PHP/FrankenPHP stacks)
    if (isset($_SERVER['REQUEST_METHOD']) && strtoupper($_SERVER['REQUEST_METHOD']) === 'GET') {
        $uri = isset($_SERVER['REQUEST_URI']) ? (string) $_SERVER['REQUEST_URI'] : '';
        if ($uri !== '' && preg_match('#/v1/ping(?:\\?.*)?$#', $uri)) {
            header('Content-Type: application/json; charset=UTF-8');
            http_response_code(200);
            echo json_encode(array('success' => true, 'message' => 'pong'));
            exit(0);
        }
        if ($uri !== '' && preg_match('#/v1/promote/plan(?:\\?.*)?$#', $uri)) {
            header('Content-Type: application/json; charset=UTF-8');
            http_response_code(200);
            $plans = array(
                array('plan_key' => 'day_7', 'day_count' => 7, 'price' => 9.99, 'currency' => 'USD'),
                array('plan_key' => 'day_14', 'day_count' => 14, 'price' => 17.99, 'currency' => 'USD'),
                array('plan_key' => 'day_30', 'day_count' => 30, 'price' => 29.99, 'currency' => 'USD'),
            );
            echo json_encode(array('success' => true, 'data' => $plans));
            exit(0);
        }
        if ($uri !== '' && preg_match('#/v1/category/list(?:\\?.*)?$#', $uri)) {
            header('Content-Type: application/json; charset=UTF-8');
            $db_host = getenv('MYSQLHOST') ?: getenv('DATABASE_HOST') ?: 'localhost';
            $db_user = getenv('MYSQLUSER') ?: getenv('DATABASE_USER') ?: 'root';
            $db_pass = getenv('MYSQLPASSWORD') ?: getenv('DATABASE_PASSWORD') ?: '';
            $db_name = getenv('MYSQLDATABASE') ?: getenv('DATABASE_NAME') ?: 'railway';
            $db_port = (int) (getenv('MYSQLPORT') ?: getenv('DATABASE_PORT') ?: 3306);
            $lang_id = isset($_GET['lang_id']) ? max(1, (int) $_GET['lang_id']) : 1;

            $mysqli = null;
            try {
                $mysqli = open_api_mysqli_connection($db_host, $db_user, $db_pass, $db_name, $db_port);
            } catch (Throwable $e) {
                $mysqli = null;
            }
            if (!$mysqli || $mysqli->connect_errno) {
                http_response_code(500);
                echo json_encode(array('success' => false, 'error' => 'Category query failed'));
                exit(0);
            }

            $sql = "SELECT c.id, c.slug, c.parent_id, c.category_order, c.image,
                           COALESCE((SELECT name FROM categories_lang cl WHERE cl.category_id = c.id AND cl.lang_id = ? LIMIT 1), '') AS name
                    FROM categories c
                    WHERE c.visibility = 1
                    ORDER BY c.parent_id ASC, c.category_order ASC";
            $stmt = $mysqli->prepare($sql);
            if (!$stmt) {
                $mysqli->close();
                http_response_code(500);
                echo json_encode(array('success' => false, 'error' => 'Category query failed'));
                exit(0);
            }
            $stmt->bind_param('i', $lang_id);
            $stmt->execute();
            $res = $stmt->get_result();
            $rows = array();
            while ($res && ($r = $res->fetch_assoc())) {
                if (azura_api_v1_category_is_hidden((string) ($r['slug'] ?? ''), (string) ($r['name'] ?? ''))) {
                    continue;
                }
                $rows[] = array(
                    'id' => (int) ($r['id'] ?? 0),
                    'name' => (string) ($r['name'] ?? ''),
                    'slug' => (string) ($r['slug'] ?? ''),
                    'parent_id' => (int) ($r['parent_id'] ?? 0),
                    'category_order' => (int) ($r['category_order'] ?? 0),
                    'image' => !empty($r['image'])
                        ? ((strpos((string) $r['image'], 'http://') === 0 || strpos((string) $r['image'], 'https://') === 0)
                            ? (string) $r['image']
                            : ('https://azuramall.shop/' . ltrim((string) $r['image'], '/')))
                        : null,
                );
            }
            $stmt->close();
            $mysqli->close();

            http_response_code(200);
            echo json_encode(array('success' => true, 'data' => $rows));
            exit(0);
        }
        if ($uri !== '' && preg_match('#/v1/product/list(?:\\?.*)?$#', $uri)) {
            header('Content-Type: application/json; charset=UTF-8');
            $db_host = getenv('MYSQLHOST') ?: getenv('DATABASE_HOST') ?: 'localhost';
            $db_user = getenv('MYSQLUSER') ?: getenv('DATABASE_USER') ?: 'root';
            $db_pass = getenv('MYSQLPASSWORD') ?: getenv('DATABASE_PASSWORD') ?: '';
            $db_name = getenv('MYSQLDATABASE') ?: getenv('DATABASE_NAME') ?: 'railway';
            $db_port = (int) (getenv('MYSQLPORT') ?: getenv('DATABASE_PORT') ?: 3306);
            $page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
            $per_page = isset($_GET['per_page']) ? max(1, min(50, (int) $_GET['per_page'])) : 20;
            $category_id = isset($_GET['category_id']) ? (int) $_GET['category_id'] : 0;
            $lang_id = isset($_GET['lang_id']) ? max(1, (int) $_GET['lang_id']) : 1;
            $offset = ($page - 1) * $per_page;

            $mysqli = null;
            try {
                $mysqli = open_api_mysqli_connection($db_host, $db_user, $db_pass, $db_name, $db_port);
            } catch (Throwable $e) {
                $mysqli = null;
            }
            if (!$mysqli || $mysqli->connect_errno) {
                http_response_code(500);
                echo json_encode(array('success' => false, 'error' => 'Product query failed'));
                exit(0);
            }

            if ($category_id > 0) {
                $count_sql = "SELECT COUNT(*) AS total FROM products p
                              WHERE p.is_deleted = 0
                                AND (p.status = 1 OR p.status = 2 OR p.status IS NULL)
                                AND (p.visibility = 1 OR p.visibility IS NULL)
                                AND (p.is_draft = 0 OR p.is_draft IS NULL)
                                AND p.category_id = ?";
                $count_stmt = $mysqli->prepare($count_sql);
                $count_stmt->bind_param('i', $category_id);
            } else {
                $count_sql = "SELECT COUNT(*) AS total FROM products p
                              WHERE p.is_deleted = 0
                                AND (p.status = 1 OR p.status = 2 OR p.status IS NULL)
                                AND (p.visibility = 1 OR p.visibility IS NULL)
                                AND (p.is_draft = 0 OR p.is_draft IS NULL)";
                $count_stmt = $mysqli->prepare($count_sql);
            }
            $total = 0;
            if ($count_stmt) {
                $count_stmt->execute();
                $count_res = $count_stmt->get_result();
                $count_row = $count_res ? $count_res->fetch_assoc() : null;
                $total = (int) ($count_row['total'] ?? 0);
                $count_stmt->close();
            }

            if ($category_id > 0) {
                $sql = "SELECT p.id, p.category_id, p.slug, p.price, p.currency, p.discount_rate, p.user_id, p.rating, p.is_promoted, p.is_sold, p.created_at,
                               (SELECT title FROM product_details pd WHERE pd.product_id = p.id AND pd.lang_id = ? LIMIT 1) AS title,
                               (SELECT image_small FROM images i WHERE i.product_id = p.id AND i.is_main = 1 LIMIT 1) AS image
                        FROM products p
                        WHERE p.is_deleted = 0
                          AND (p.status = 1 OR p.status = 2 OR p.status IS NULL)
                          AND (p.visibility = 1 OR p.visibility IS NULL)
                          AND (p.is_draft = 0 OR p.is_draft IS NULL)
                          AND p.category_id = ?
                        ORDER BY p.created_at DESC
                        LIMIT ? OFFSET ?";
                $stmt = $mysqli->prepare($sql);
                $stmt->bind_param('iiii', $lang_id, $category_id, $per_page, $offset);
            } else {
                $sql = "SELECT p.id, p.category_id, p.slug, p.price, p.currency, p.discount_rate, p.user_id, p.rating, p.is_promoted, p.is_sold, p.created_at,
                               (SELECT title FROM product_details pd WHERE pd.product_id = p.id AND pd.lang_id = ? LIMIT 1) AS title,
                               (SELECT image_small FROM images i WHERE i.product_id = p.id AND i.is_main = 1 LIMIT 1) AS image
                        FROM products p
                        WHERE p.is_deleted = 0
                          AND (p.status = 1 OR p.status = 2 OR p.status IS NULL)
                          AND (p.visibility = 1 OR p.visibility IS NULL)
                          AND (p.is_draft = 0 OR p.is_draft IS NULL)
                        ORDER BY p.created_at DESC
                        LIMIT ? OFFSET ?";
                $stmt = $mysqli->prepare($sql);
                $stmt->bind_param('iii', $lang_id, $per_page, $offset);
            }

            if (!$stmt) {
                $mysqli->close();
                http_response_code(500);
                echo json_encode(array('success' => false, 'error' => 'Product query failed'));
                exit(0);
            }
            $stmt->execute();
            $res = $stmt->get_result();
            $rows = array();
            while ($res && ($r = $res->fetch_assoc())) {
                $rows[] = array(
                    'id' => (int) ($r['id'] ?? 0),
                    'category_id' => (int) ($r['category_id'] ?? 0),
                    'title' => (string) ($r['title'] ?? ''),
                    'slug' => (string) ($r['slug'] ?? ''),
                    'price' => (int) ($r['price'] ?? 0),
                    'currency' => (string) ($r['currency'] ?? ''),
                    'discount_rate' => (int) ($r['discount_rate'] ?? 0),
                    'user_id' => (int) ($r['user_id'] ?? 0),
                    'rating' => (string) ($r['rating'] ?? '0'),
                    'is_promoted' => (int) ($r['is_promoted'] ?? 0),
                    'is_sold' => (int) ($r['is_sold'] ?? 0),
                    'image' => !empty($r['image'])
                        ? ((strpos((string) $r['image'], 'http://') === 0 || strpos((string) $r['image'], 'https://') === 0)
                            ? (string) $r['image']
                            : ('https://azuramall.shop/' . ltrim((string) $r['image'], '/')))
                        : null,
                    'created_at' => (string) ($r['created_at'] ?? ''),
                );
            }
            $stmt->close();
            $mysqli->close();

            http_response_code(200);
            echo json_encode(array(
                'success' => true,
                'data' => $rows,
                'pagination' => array(
                    'current_page' => $page,
                    'per_page' => $per_page,
                    'total' => $total,
                ),
            ));
            exit(0);
        }
        if ($uri !== '' && preg_match('#/v1/product/detail_get(?:\\?.*)?$#', $uri)) {
            header('Content-Type: application/json; charset=UTF-8');
            $db_host = getenv('MYSQLHOST') ?: getenv('DATABASE_HOST') ?: 'localhost';
            $db_user = getenv('MYSQLUSER') ?: getenv('DATABASE_USER') ?: 'root';
            $db_pass = getenv('MYSQLPASSWORD') ?: getenv('DATABASE_PASSWORD') ?: '';
            $db_name = getenv('MYSQLDATABASE') ?: getenv('DATABASE_NAME') ?: 'railway';
            $db_port = (int) (getenv('MYSQLPORT') ?: getenv('DATABASE_PORT') ?: 3306);
            $slug = isset($_GET['slug']) ? trim((string) $_GET['slug']) : '';
            if ($slug === '') {
                http_response_code(400);
                echo json_encode(array('success' => false, 'error' => 'slug is required'));
                exit(0);
            }

            $mysqli = null;
            try {
                $mysqli = open_api_mysqli_connection($db_host, $db_user, $db_pass, $db_name, $db_port);
            } catch (Throwable $e) {
                $mysqli = null;
            }
            if (!$mysqli || $mysqli->connect_errno) {
                http_response_code(500);
                echo json_encode(array('success' => false, 'error' => 'Product details query failed'));
                exit(0);
            }

            try {
                $safe_slug = $mysqli->real_escape_string($slug);
                $sql = "SELECT p.*,
                               COALESCE((SELECT title FROM product_details pd WHERE pd.product_id = p.id AND pd.lang_id = 1 LIMIT 1), '') AS title,
                               COALESCE((SELECT description FROM product_details pd WHERE pd.product_id = p.id AND pd.lang_id = 1 LIMIT 1), '') AS description,
                               COALESCE(u.username, '') AS user_username,
                               COALESCE(u.shop_name, '') AS shop_name,
                               COALESCE(u.role, '') AS user_role,
                               COALESCE(u.slug, '') AS user_slug
                        FROM products p
                        LEFT JOIN users u ON u.id = p.user_id
                        WHERE p.slug = '$safe_slug'
                          AND p.is_deleted = 0
                          AND (p.status = 1 OR p.status = 2 OR p.status IS NULL)
                          AND (p.visibility = 1 OR p.visibility IS NULL)
                        LIMIT 1";
                $res = $mysqli->query($sql);
                $row = $res ? $res->fetch_assoc() : null;
                if (empty($row)) {
                    $mysqli->close();
                    http_response_code(404);
                    echo json_encode(array('success' => false, 'error' => 'Product not found'));
                    exit(0);
                }

                $pid = (int) ($row['id'] ?? 0);
                $files = array();
                $main_image = '';
                if ($pid > 0) {
                    $img_res = $mysqli->query("SELECT image_default, image_small, is_main FROM images WHERE product_id = $pid ORDER BY is_main DESC, id ASC");
                    while ($img_res && ($img = $img_res->fetch_assoc())) {
                        $img_path = (string) (!empty($img['image_default']) ? $img['image_default'] : ($img['image_small'] ?? ''));
                        if ($img_path === '') {
                            continue;
                        }
                        $img_url = ((strpos($img_path, 'http://') === 0 || strpos($img_path, 'https://') === 0)
                            ? $img_path
                            : ('https://azuramall.shop/uploads/images/' . ltrim($img_path, '/')));
                        $files[] = $img_url;
                        if ($main_image === '' && ((int) ($img['is_main'] ?? 0) === 1)) {
                            $main_image = $img_url;
                        }
                    }
                }
                if ($main_image === '' && !empty($files)) {
                    $main_image = $files[0];
                }

                $mysqli->close();
                http_response_code(200);
                echo json_encode(array(
                    'id' => (string) ($row['id'] ?? ''),
                    'title' => (string) ($row['title'] ?? ''),
                    'slug' => (string) ($row['slug'] ?? ''),
                    'image' => $main_image,
                    'files' => $files,
                    'product_type' => (string) ($row['product_type'] ?? ''),
                    'listing_type' => (string) ($row['listing_type'] ?? ''),
                    'category_id' => (string) ($row['category_id'] ?? ''),
                    'price' => (int) ($row['price'] ?? 0),
                    'currency' => (string) ($row['currency'] ?? ''),
                    'description' => (string) ($row['description'] ?? ''),
                    'user_id' => (string) ($row['user_id'] ?? ''),
                    'status' => (string) ($row['status'] ?? ''),
                    'is_promoted' => (string) ($row['is_promoted'] ?? '0'),
                    'promote_start_date' => (string) ($row['promote_start_date'] ?? ''),
                    'promote_end_date' => (string) ($row['promote_end_date'] ?? ''),
                    'promote_plan' => (string) ($row['promote_plan'] ?? ''),
                    'promote_day' => (string) ($row['promote_day'] ?? ''),
                    'visibility' => (string) ($row['visibility'] ?? '1'),
                    'rating' => (string) ($row['rating'] ?? '0'),
                    'external_link' => (string) ($row['external_link'] ?? ''),
                    'files_included' => (string) ($row['files_included'] ?? ''),
                    'shipping_time' => '',
                    'shipping_cost_type' => null,
                    'shipping_cost' => null,
                    'is_sold' => (string) ($row['is_sold'] ?? '0'),
                    'is_deleted' => (string) ($row['is_deleted'] ?? '0'),
                    'is_draft' => (string) ($row['is_draft'] ?? '0'),
                    'created_at' => (string) ($row['created_at'] ?? ''),
                    'user_username' => (string) ($row['user_username'] ?? ''),
                    'shop_name' => (string) ($row['shop_name'] ?? ''),
                    'user_role' => (string) ($row['user_role'] ?? ''),
                    'user_slug' => (string) ($row['user_slug'] ?? ''),
                    'product_url' => 'https://azuramall.shop/' . ltrim((string) ($row['slug'] ?? ''), '/'),
                ));
                exit(0);
            } catch (Throwable $e) {
                $mysqli->close();
                http_response_code(500);
                echo json_encode(array('success' => false, 'error' => 'Product details query failed'));
                exit(0);
            }
        }
        if ($uri !== '' && preg_match('#/v1/auth/login(?:\\?.*)?$#', $uri)) {
            header('Content-Type: application/json; charset=UTF-8');
            http_response_code(405);
            echo json_encode(array('success' => false, 'error' => 'Method not allowed'));
            exit(0);
        }
        $demo_action = isset($_GET['demo_action']) ? (string) $_GET['demo_action'] : '';
        if (($uri !== '' && preg_match('#/v1/demo/seed-catalog(?:\\?.*)?$#', $uri)) || $demo_action === 'seed_catalog') {
            header('Content-Type: application/json; charset=UTF-8');
            $secret = 'railway_demo_catalog_2026';
            $key = isset($_GET['key']) ? (string) $_GET['key'] : '';
            if ($key !== $secret) {
                http_response_code(401);
                echo json_encode(array('success' => false, 'error' => 'Unauthorized'));
                exit(0);
            }

            $db_host = getenv('MYSQLHOST') ?: getenv('DATABASE_HOST') ?: 'localhost';
            $db_user = getenv('MYSQLUSER') ?: getenv('DATABASE_USER') ?: 'root';
            $db_pass = getenv('MYSQLPASSWORD') ?: getenv('DATABASE_PASSWORD') ?: '';
            $db_name = getenv('MYSQLDATABASE') ?: getenv('DATABASE_NAME') ?: 'railway';
            $db_port = (int) (getenv('MYSQLPORT') ?: getenv('DATABASE_PORT') ?: 3306);
            $mysqli = null;
            try {
                $mysqli = open_api_mysqli_connection($db_host, $db_user, $db_pass, $db_name, $db_port);
            } catch (Throwable $e) {
                $mysqli = null;
            }
            if (!$mysqli || $mysqli->connect_errno) {
                http_response_code(500);
                echo json_encode(array('success' => false, 'error' => 'Database connection failed'));
                exit(0);
            }
            $mysqli->set_charset('utf8mb4');
            $debug_mode = ((getenv('CI_ENV') ?: getenv('APP_ENV')) === 'development');

            try {
                // pick any existing user as product owner (schema-safe)
                $owner_id = 0;
                $owner_rs = $mysqli->query("SELECT id FROM users ORDER BY id ASC LIMIT 1");
                if ($owner_rs && ($owner_row = $owner_rs->fetch_assoc())) {
                    $owner_id = (int) $owner_row['id'];
                }
                if ($owner_id < 1) {
                    $mysqli->close();
                    http_response_code(500);
                    echo json_encode(array('success' => false, 'error' => 'No users found to own demo products'));
                    exit(0);
                }

                $categories = array(
                    array('slug' => 'fashion-demo', 'name' => 'Fashion', 'image' => 'https://images.unsplash.com/photo-1445205170230-053b83016050?auto=format&fit=crop&w=1200&q=80'),
                    array('slug' => 'electronics-demo', 'name' => 'Electronics', 'image' => 'https://images.unsplash.com/photo-1519389950473-47ba0277781c?auto=format&fit=crop&w=1200&q=80'),
                    array('slug' => 'home-decor-demo', 'name' => 'Home Decor', 'image' => 'https://images.unsplash.com/photo-1616486338812-3dadae4b4ace?auto=format&fit=crop&w=1200&q=80'),
                );
                $category_ids = array();
                foreach ($categories as $idx => $cat) {
                    $slug = $mysqli->real_escape_string($cat['slug']);
                    $name = $mysqli->real_escape_string($cat['name']);
                    $cat_image = $mysqli->real_escape_string($cat['image']);
                    $check = $mysqli->query("SELECT id, image FROM categories WHERE slug='$slug' LIMIT 1");
                    if ($check && ($row = $check->fetch_assoc())) {
                        $cid = (int) $row['id'];
                        $existing_image = isset($row['image']) ? (string)$row['image'] : '';
                        if ($existing_image === '' || $existing_image === null || trim($existing_image, '/') === '') {
                            $mysqli->query("UPDATE categories SET image='$cat_image' WHERE id=$cid");
                        }
                        // Keep demo categories visually rich and consistent.
                        $mysqli->query("UPDATE categories SET image='$cat_image' WHERE id=$cid");
                    } else {
                        $order = $idx + 1;
                        $mysqli->query("INSERT INTO categories (slug,parent_id,category_order,featured_order,homepage_order,visibility,is_featured,show_products_on_index,show_subcategory_products,storage,image,show_image_on_navigation,created_at) VALUES ('$slug',0,$order,$order,$order,1,1,1,0,'local','$cat_image',0,NOW())");
                        $cid = (int) $mysqli->insert_id;
                    }
                    $category_ids[] = $cid;
                    $lang_check = $mysqli->query("SELECT id FROM categories_lang WHERE category_id=$cid AND lang_id=1 LIMIT 1");
                    if (!($lang_check && $lang_check->fetch_assoc())) {
                        $mysqli->query("INSERT INTO categories_lang (category_id,lang_id,name) VALUES ($cid,1,'$name')");
                    }
                }

                $products = array(
                    array('title' => 'Premium Leather Sneakers', 'slug' => 'premium-leather-sneakers-demo', 'price' => 129000, 'category' => 0, 'image' => 'https://images.unsplash.com/photo-1542291026-7eec264c27ff?auto=format&fit=crop&w=800&q=80', 'desc' => 'Comfortable premium sneakers for everyday style.'),
                    array('title' => 'Minimalist Smart Watch', 'slug' => 'minimalist-smart-watch-demo', 'price' => 189000, 'category' => 1, 'image' => 'https://images.unsplash.com/photo-1523275335684-37898b6baf30?auto=format&fit=crop&w=800&q=80', 'desc' => 'Track fitness and notifications with elegant design.'),
                    array('title' => 'Modern Desk Lamp', 'slug' => 'modern-desk-lamp-demo', 'price' => 59000, 'category' => 2, 'image' => 'https://images.unsplash.com/photo-1507473885765-e6ed057f782c?auto=format&fit=crop&w=800&q=80', 'desc' => 'Warm ambient light for work and reading spaces.'),
                    array('title' => 'Wireless Noise-Cancel Headphones', 'slug' => 'wireless-noise-cancel-headphones-demo', 'price' => 249000, 'category' => 1, 'image' => 'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?auto=format&fit=crop&w=800&q=80', 'desc' => 'Immersive sound and all-day battery life.'),
                    array('title' => 'Classic Linen Shirt', 'slug' => 'classic-linen-shirt-demo', 'price' => 79000, 'category' => 0, 'image' => 'https://images.unsplash.com/photo-1602810318383-e386cc2a3ccf?auto=format&fit=crop&w=800&q=80', 'desc' => 'Breathable linen shirt with timeless look.'),
                    array('title' => 'Ceramic Vase Set', 'slug' => 'ceramic-vase-set-demo', 'price' => 69000, 'category' => 2, 'image' => 'https://images.unsplash.com/photo-1616628182509-6f39fd8f95ad?auto=format&fit=crop&w=800&q=80', 'desc' => 'Beautiful hand-finished vases for home styling.'),
                );

                $created_products = 0;
                $gallery_images_added = 0;
                foreach ($products as $p) {
                    $slug = $mysqli->real_escape_string($p['slug']);
                    $exists = $mysqli->query("SELECT id FROM products WHERE slug='$slug' LIMIT 1");
                    $title = $mysqli->real_escape_string($p['title']);
                    $description = $mysqli->real_escape_string($p['desc']);
                    $img = $mysqli->real_escape_string($p['image']);
                    $cat_id = isset($category_ids[$p['category']]) ? (int) $category_ids[$p['category']] : (int) $category_ids[0];
                    $price = (int) $p['price'];
                    $pid = 0;

                    if ($exists && ($existing_row = $exists->fetch_assoc())) {
                        $pid = (int) ($existing_row['id'] ?? 0);
                    } else {
                        $mysqli->query("INSERT INTO products (slug,product_type,listing_type,sku,category_id,price,currency,discount_rate,vat_rate,user_id,status,is_promoted,visibility,rating,pageviews,stock,multiple_sale,is_sold,is_deleted,is_draft,is_free_product,created_at) VALUES ('$slug','physical','sell_on_site',NULL,$cat_id,$price,'TZS',0,0,$owner_id,1,0,1,'5.0',0,20,1,0,0,0,0,NOW())");
                        $pid = (int) $mysqli->insert_id;
                        if ($pid > 0) {
                            $mysqli->query("INSERT INTO product_details (product_id,lang_id,title,description,seo_title,seo_description,seo_keywords) VALUES ($pid,1,'$title','$description','$title','$description','demo,shopping,azuramall')");
                            $created_products++;
                        }
                    }

                    if ($pid < 1) {
                        continue;
                    }
                    $main_exists = $mysqli->query("SELECT id FROM images WHERE product_id=$pid AND is_main=1 LIMIT 1");
                    if (!($main_exists && $main_exists->fetch_assoc())) {
                        $mysqli->query("INSERT INTO images (product_id,image_default,image_big,image_small,is_main,storage) VALUES ($pid,'$img','$img','$img',1,'local')");
                        $gallery_images_added++;
                    }
                    $img_count_rs = $mysqli->query("SELECT COUNT(*) AS c FROM images WHERE product_id=$pid");
                    $img_count_row = $img_count_rs ? $img_count_rs->fetch_assoc() : null;
                    $img_count = (int) ($img_count_row['c'] ?? 0);
                    if ($img_count < 2) {
                        $mysqli->query("INSERT INTO images (product_id,image_default,image_big,image_small,is_main,storage) VALUES ($pid,'$img&v=2','$img&v=2','$img&v=2',0,'local')");
                        $gallery_images_added++;
                    }
                    if ($img_count < 3) {
                        $mysqli->query("INSERT INTO images (product_id,image_default,image_big,image_small,is_main,storage) VALUES ($pid,'$img&v=3','$img&v=3','$img&v=3',0,'local')");
                        $gallery_images_added++;
                    }
                }

                // Ensure every non-deleted product has rich imagery (at least 3 images).
                $global_gallery = array(
                    'https://images.unsplash.com/photo-1542291026-7eec264c27ff?auto=format&fit=crop&w=900&q=80',
                    'https://images.unsplash.com/photo-1523275335684-37898b6baf30?auto=format&fit=crop&w=900&q=80',
                    'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?auto=format&fit=crop&w=900&q=80',
                    'https://images.unsplash.com/photo-1507473885765-e6ed057f782c?auto=format&fit=crop&w=900&q=80',
                    'https://images.unsplash.com/photo-1519741497674-611481863552?auto=format&fit=crop&w=900&q=80',
                    'https://images.unsplash.com/photo-1616628182509-6f39fd8f95ad?auto=format&fit=crop&w=900&q=80',
                    'https://images.unsplash.com/photo-1483985988355-763728e1935b?auto=format&fit=crop&w=900&q=80',
                    'https://images.unsplash.com/photo-1473448912268-2022ce9509d8?auto=format&fit=crop&w=900&q=80'
                );
                $global_processed = 0;
                $global_query_failed = 0;
                $all_products = $mysqli->query("SELECT id, slug FROM products WHERE is_deleted=0");
                if (!$all_products) {
                    $global_query_failed = 1;
                }
                while ($all_products && ($ap = $all_products->fetch_assoc())) {
                    $ap_id = (int) ($ap['id'] ?? 0);
                    if ($ap_id < 1) {
                        continue;
                    }
                    $global_processed++;
                    $ap_slug = isset($ap['slug']) ? (string) $ap['slug'] : '';
                    $h = abs(crc32((string) $ap_id . '-' . $ap_slug));
                    $g1 = $mysqli->real_escape_string($global_gallery[$h % count($global_gallery)]);
                    $g2 = $mysqli->real_escape_string($global_gallery[($h + 3) % count($global_gallery)]);
                    $g3 = $mysqli->real_escape_string($global_gallery[($h + 5) % count($global_gallery)]);

                    $has_main = $mysqli->query("SELECT id FROM images WHERE product_id=$ap_id AND is_main=1 LIMIT 1");
                    if (!($has_main && $has_main->fetch_assoc())) {
                        $mysqli->query("INSERT INTO images (product_id,image_default,image_big,image_small,is_main,storage) VALUES ($ap_id,'$g1','$g1','$g1',1,'local')");
                        $gallery_images_added++;
                    }
                    $ap_count_rs = $mysqli->query("SELECT COUNT(*) AS c FROM images WHERE product_id=$ap_id");
                    $ap_count_row = $ap_count_rs ? $ap_count_rs->fetch_assoc() : null;
                    $ap_count = (int) ($ap_count_row['c'] ?? 0);
                    if ($ap_count < 2) {
                        $mysqli->query("INSERT INTO images (product_id,image_default,image_big,image_small,is_main,storage) VALUES ($ap_id,'$g2','$g2','$g2',0,'local')");
                        $gallery_images_added++;
                    }
                    if ($ap_count < 3) {
                        $mysqli->query("INSERT INTO images (product_id,image_default,image_big,image_small,is_main,storage) VALUES ($ap_id,'$g3','$g3','$g3',0,'local')");
                        $gallery_images_added++;
                    }
                }

                $total_products = 0;
                $tp = $mysqli->query("SELECT COUNT(*) AS n FROM products WHERE is_deleted=0");
                if ($tp && ($tp_row = $tp->fetch_assoc())) {
                    $total_products = (int) $tp_row['n'];
                }
                $mysqli->close();

                http_response_code(200);
                echo json_encode(array(
                    'success' => true,
                    'message' => 'Demo catalog seeded v2',
                    'created_products' => $created_products,
                    'gallery_images_added' => $gallery_images_added,
                    'global_processed' => $global_processed,
                    'global_query_failed' => $global_query_failed,
                    'total_products' => $total_products,
                ));
                exit(0);
            } catch (Throwable $e) {
                $mysqli->close();
                http_response_code(500);
                echo json_encode(array(
                    'success' => false,
                    'error' => $debug_mode ? $e->getMessage() : 'Seeder failed',
                ));
                exit(0);
            }
        }
        if (($uri !== '' && preg_match('#/v1/demo/fill-product-images(?:\\?.*)?$#', $uri)) || $demo_action === 'fill_product_images') {
            header('Content-Type: application/json; charset=UTF-8');
            $secret = 'railway_demo_catalog_2026';
            $key = isset($_GET['key']) ? (string) $_GET['key'] : '';
            if ($key !== $secret) {
                http_response_code(401);
                echo json_encode(array('success' => false, 'error' => 'Unauthorized'));
                exit(0);
            }

            $db_host = getenv('MYSQLHOST') ?: getenv('DATABASE_HOST') ?: 'localhost';
            $db_user = getenv('MYSQLUSER') ?: getenv('DATABASE_USER') ?: 'root';
            $db_pass = getenv('MYSQLPASSWORD') ?: getenv('DATABASE_PASSWORD') ?: '';
            $db_name = getenv('MYSQLDATABASE') ?: getenv('DATABASE_NAME') ?: 'railway';
            $db_port = (int) (getenv('MYSQLPORT') ?: getenv('DATABASE_PORT') ?: 3306);

            $mysqli = null;
            try {
                $mysqli = open_api_mysqli_connection($db_host, $db_user, $db_pass, $db_name, $db_port);
            } catch (Throwable $e) {
                $mysqli = null;
            }
            if (!$mysqli || $mysqli->connect_errno) {
                http_response_code(500);
                echo json_encode(array('success' => false, 'error' => 'Database connection failed'));
                exit(0);
            }
            $mysqli->set_charset('utf8mb4');

            try {
                $gallery = array(
                    'https://images.unsplash.com/photo-1542291026-7eec264c27ff?auto=format&fit=crop&w=900&q=80',
                    'https://images.unsplash.com/photo-1523275335684-37898b6baf30?auto=format&fit=crop&w=900&q=80',
                    'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?auto=format&fit=crop&w=900&q=80',
                    'https://images.unsplash.com/photo-1507473885765-e6ed057f782c?auto=format&fit=crop&w=900&q=80',
                    'https://images.unsplash.com/photo-1519741497674-611481863552?auto=format&fit=crop&w=900&q=80',
                    'https://images.unsplash.com/photo-1616628182509-6f39fd8f95ad?auto=format&fit=crop&w=900&q=80',
                    'https://images.unsplash.com/photo-1483985988355-763728e1935b?auto=format&fit=crop&w=900&q=80',
                    'https://images.unsplash.com/photo-1473448912268-2022ce9509d8?auto=format&fit=crop&w=900&q=80'
                );

                $result = $mysqli->query("SELECT id, slug FROM products WHERE is_deleted=0");
                $updated = 0;
                $created = 0;

                while ($result && ($row = $result->fetch_assoc())) {
                    $pid = (int) ($row['id'] ?? 0);
                    if ($pid < 1) {
                        continue;
                    }
                    $slug = isset($row['slug']) ? (string) $row['slug'] : '';
                    $hash = abs(crc32((string) $pid . '-' . $slug));
                    $base1 = $gallery[$hash % count($gallery)];
                    $base2 = $gallery[($hash + 3) % count($gallery)];
                    $base3 = $gallery[($hash + 5) % count($gallery)];

                    $esc1 = $mysqli->real_escape_string($base1);
                    $esc2 = $mysqli->real_escape_string($base2);
                    $esc3 = $mysqli->real_escape_string($base3);

                    $count_rs = $mysqli->query("SELECT COUNT(*) AS c FROM images WHERE product_id=$pid");
                    $count_row = $count_rs ? $count_rs->fetch_assoc() : null;
                    $img_count = (int) ($count_row['c'] ?? 0);

                    if ($img_count < 1) {
                        $mysqli->query("INSERT INTO images (product_id,image_default,image_big,image_small,is_main,storage) VALUES ($pid,'$esc1','$esc1','$esc1',1,'local')");
                        $created++;
                    } else {
                        $main_rs = $mysqli->query("SELECT id FROM images WHERE product_id=$pid AND is_main=1 LIMIT 1");
                        if (!($main_rs && $main_rs->fetch_assoc())) {
                            $first_rs = $mysqli->query("SELECT id FROM images WHERE product_id=$pid ORDER BY id ASC LIMIT 1");
                            $first_row = $first_rs ? $first_rs->fetch_assoc() : null;
                            if (!empty($first_row['id'])) {
                                $first_id = (int) $first_row['id'];
                                $mysqli->query("UPDATE images SET is_main=1 WHERE id=$first_id");
                                $updated++;
                            }
                        }
                    }

                    $count_rs2 = $mysqli->query("SELECT COUNT(*) AS c FROM images WHERE product_id=$pid");
                    $count_row2 = $count_rs2 ? $count_rs2->fetch_assoc() : null;
                    $img_count2 = (int) ($count_row2['c'] ?? 0);

                    if ($img_count2 < 3) {
                        if ($img_count2 < 2) {
                            $mysqli->query("INSERT INTO images (product_id,image_default,image_big,image_small,is_main,storage) VALUES ($pid,'$esc2','$esc2','$esc2',0,'local')");
                            $created++;
                        }
                        $mysqli->query("INSERT INTO images (product_id,image_default,image_big,image_small,is_main,storage) VALUES ($pid,'$esc3','$esc3','$esc3',0,'local')");
                        $created++;
                    }
                }

                $total_with_images = 0;
                $tw = $mysqli->query("SELECT COUNT(DISTINCT p.id) AS n FROM products p INNER JOIN images i ON i.product_id=p.id WHERE p.is_deleted=0");
                if ($tw && ($tw_row = $tw->fetch_assoc())) {
                    $total_with_images = (int) $tw_row['n'];
                }

                $total_products = 0;
                $tp = $mysqli->query("SELECT COUNT(*) AS n FROM products WHERE is_deleted=0");
                if ($tp && ($tp_row = $tp->fetch_assoc())) {
                    $total_products = (int) $tp_row['n'];
                }

                $mysqli->close();
                http_response_code(200);
                echo json_encode(array(
                    'success' => true,
                    'message' => 'Product images enriched',
                    'images_created_or_appended' => $created,
                    'records_updated' => $updated,
                    'products_with_images' => $total_with_images,
                    'total_products' => $total_products,
                ));
                exit(0);
            } catch (Throwable $e) {
                $mysqli->close();
                http_response_code(500);
                echo json_encode(array('success' => false, 'error' => 'Image enrichment failed'));
                exit(0);
            }
        }
        if (($uri !== '' && preg_match('#/v1/demo/image-stats(?:\\?.*)?$#', $uri)) || $demo_action === 'image_stats') {
            header('Content-Type: application/json; charset=UTF-8');
            $secret = 'railway_demo_catalog_2026';
            $key = isset($_GET['key']) ? (string) $_GET['key'] : '';
            if ($key !== $secret) {
                http_response_code(401);
                echo json_encode(array('success' => false, 'error' => 'Unauthorized'));
                exit(0);
            }

            $db_host = getenv('MYSQLHOST') ?: getenv('DATABASE_HOST') ?: 'localhost';
            $db_user = getenv('MYSQLUSER') ?: getenv('DATABASE_USER') ?: 'root';
            $db_pass = getenv('MYSQLPASSWORD') ?: getenv('DATABASE_PASSWORD') ?: '';
            $db_name = getenv('MYSQLDATABASE') ?: getenv('DATABASE_NAME') ?: 'railway';
            $db_port = (int) (getenv('MYSQLPORT') ?: getenv('DATABASE_PORT') ?: 3306);

            $mysqli = null;
            try {
                $mysqli = open_api_mysqli_connection($db_host, $db_user, $db_pass, $db_name, $db_port);
            } catch (Throwable $e) {
                $mysqli = null;
            }
            if (!$mysqli || $mysqli->connect_errno) {
                http_response_code(500);
                echo json_encode(array('success' => false, 'error' => 'Database connection failed'));
                exit(0);
            }

            try {
                $result = $mysqli->query("SELECT id FROM products WHERE is_deleted=0");
                $total_products = 0;
                $without_images = 0;
                $with_less_than_3 = 0;
                $min_images = null;
                $max_images = 0;

                while ($result && ($row = $result->fetch_assoc())) {
                    $pid = (int) ($row['id'] ?? 0);
                    if ($pid < 1) {
                        continue;
                    }
                    $total_products++;
                    $count_rs = $mysqli->query("SELECT COUNT(*) AS c FROM images WHERE product_id=$pid");
                    $count_row = $count_rs ? $count_rs->fetch_assoc() : null;
                    $c = (int) ($count_row['c'] ?? 0);
                    if ($c === 0) {
                        $without_images++;
                    }
                    if ($c < 3) {
                        $with_less_than_3++;
                    }
                    if ($min_images === null || $c < $min_images) {
                        $min_images = $c;
                    }
                    if ($c > $max_images) {
                        $max_images = $c;
                    }
                }
                $mysqli->close();

                http_response_code(200);
                echo json_encode(array(
                    'success' => true,
                    'stats' => array(
                        'total_products' => $total_products,
                        'without_images' => $without_images,
                        'with_less_than_3' => $with_less_than_3,
                        'min_images_per_product' => (int) ($min_images ?? 0),
                        'max_images_per_product' => $max_images,
                    )
                ));
                exit(0);
            } catch (Throwable $e) {
                $mysqli->close();
                http_response_code(500);
                echo json_encode(array('success' => false, 'error' => 'Image stats failed'));
                exit(0);
            }
        }
    }

    // Fallback login handler to keep mobile auth alive even when route rewriting drops URI segments.
    if (isset($_SERVER['REQUEST_METHOD']) && strtoupper($_SERVER['REQUEST_METHOD']) === 'POST') {
        $uri = isset($_SERVER['REQUEST_URI']) ? (string) $_SERVER['REQUEST_URI'] : '';
        if ($uri !== '' && preg_match('#/v1/auth/login(?:\\?.*)?$#', $uri)) {
            header('Content-Type: application/json; charset=UTF-8');

            $email = '';
            $password = '';
            if (isset($_POST['email'])) {
                $email = trim((string) $_POST['email']);
            }
            if (isset($_POST['password'])) {
                $password = (string) $_POST['password'];
            }
            if ($email === '' || $password === '') {
                $raw = file_get_contents('php://input');
                if (!empty($raw)) {
                    $post_data = array();
                    parse_str($raw, $post_data);
                    if ($email === '' && isset($post_data['email'])) {
                        $email = trim((string) $post_data['email']);
                    }
                    if ($password === '' && isset($post_data['password'])) {
                        $password = (string) $post_data['password'];
                    }
                }
            }
            if ($email === '' || $password === '') {
                http_response_code(400);
                echo json_encode(array('success' => false, 'error' => 'Email and password required'));
                exit(0);
            }

            $db_host = getenv('MYSQLHOST') ?: getenv('DATABASE_HOST') ?: 'localhost';
            $db_user = getenv('MYSQLUSER') ?: getenv('DATABASE_USER') ?: 'root';
            $db_pass = getenv('MYSQLPASSWORD') ?: getenv('DATABASE_PASSWORD') ?: '';
            $db_name = getenv('MYSQLDATABASE') ?: getenv('DATABASE_NAME') ?: 'railway';
            $db_port = (int) (getenv('MYSQLPORT') ?: getenv('DATABASE_PORT') ?: 3306);

            $mysqli = null;
            try {
                $mysqli = open_api_mysqli_connection($db_host, $db_user, $db_pass, $db_name, $db_port);
            } catch (Throwable $e) {
                $mysqli = null;
            }
            if (!$mysqli || $mysqli->connect_errno) {
                http_response_code(500);
                echo json_encode(array('success' => false, 'error' => 'Login failed due to server error'));
                exit(0);
            }

            $stmt = $mysqli->prepare('SELECT id, username, email, first_name, last_name, role, avatar, token, password, email_status, banned FROM users WHERE email = ? LIMIT 1');
            if (!$stmt) {
                $mysqli->close();
                http_response_code(500);
                echo json_encode(array('success' => false, 'error' => 'Login failed due to server error'));
                exit(0);
            }
            $stmt->bind_param('s', $email);
            $stmt->execute();
            $res = $stmt->get_result();
            $user = $res ? $res->fetch_assoc() : null;
            $stmt->close();
            $mysqli->close();

            if (empty($user)) {
                http_response_code(401);
                echo json_encode(array('success' => false, 'error' => 'Invalid email or password'));
                exit(0);
            }
            $hash = isset($user['password']) ? (string) $user['password'] : '';
            if ($hash === '' || !password_verify($password, $hash)) {
                http_response_code(401);
                echo json_encode(array('success' => false, 'error' => 'Invalid email or password'));
                exit(0);
            }
            if ((int) $user['email_status'] !== 1) {
                http_response_code(403);
                echo json_encode(array('success' => false, 'error' => 'Email not verified'));
                exit(0);
            }
            if ((int) $user['banned'] === 1) {
                http_response_code(403);
                echo json_encode(array('success' => false, 'error' => 'Account banned'));
                exit(0);
            }

            $display_name = trim(((string) ($user['first_name'] ?? '')) . ' ' . ((string) ($user['last_name'] ?? '')));
            if ($display_name === '') {
                $display_name = (string) ($user['username'] ?? '');
            }
            http_response_code(200);
            echo json_encode(array(
                'success' => true,
                'id' => (int) ($user['id'] ?? 0),
                'name' => $display_name,
                'username' => (string) ($user['username'] ?? ''),
                'email' => (string) ($user['email'] ?? ''),
                'first_name' => (string) ($user['first_name'] ?? ''),
                'last_name' => (string) ($user['last_name'] ?? ''),
                'role' => (string) ($user['role'] ?? ''),
                'avatar' => (string) ($user['avatar'] ?? ''),
                'token' => (string) ($user['token'] ?? ''),
            ));
            exit(0);
        }
    }
    
    // Register shutdown function: CORS on fatal + optional JSON error details (set CI_API_VERBOSE_ERRORS=1 on Railway to debug)
    register_shutdown_function(function() {
        $is_api = isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], '/v1/') !== false;
        if (!$is_api) {
            return;
        }
        $err = error_get_last();
        if ($err !== null && in_array($err['type'], array(E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR), true)) {
            error_log('V1/API fatal: ' . $err['message'] . ' @ ' . $err['file'] . ':' . $err['line']);
            if (getenv('CI_API_VERBOSE_ERRORS') && !headers_sent()) {
                set_cors_headers_for_api();
                header('Content-Type: application/json; charset=UTF-8');
                http_response_code(500);
                echo json_encode(array(
                    'success' => false,
                    'error' => 'Fatal: ' . $err['message'],
                    'file' => $err['file'],
                    'line' => $err['line'],
                ));
                return;
            }
        }
        if (!headers_sent()) {
            set_cors_headers_for_api();
        }
    });
}

/*
 *---------------------------------------------------------------
 * APPLICATION ENVIRONMENT
 *---------------------------------------------------------------
 *
 * You can load different configurations depending on your
 * current environment. Setting the environment also influences
 * things like logging and error reporting.
 *
 * This can be set to anything, but default usage is:
 *
 *     development
 *     testing
 *     production
 *
 * NOTE: If you change these, also change the error_reporting() code below
 */

    if (!file_exists(".htaccess")) {
        // Set CORS headers before error output for API requests
        if ($is_api_request) {
            set_cors_headers_for_api();
            header('Content-Type: application/json');
            http_response_code(500);
            echo json_encode(array('success' => false, 'error' => '.htaccess file missing'));
        } else {
            echo "The .htaccess file does not exist on main directory of your site. Modesy has a .htaccess file in the main directory of script files. You should upload this file to your site.<br>";
            echo "Depending on the operating system you are using, such setting files may be hidden. In this case, you may not see this file.<br><br>";
            echo "If you can't see this file, you can create a new file named \".htaccess\" in the main directory of your site and you can add these codes to inside this file:<br>";
            echo "<pre>RewriteEngine On<br>";
            echo "RewriteCond %{REQUEST_FILENAME} !-f<br>";
            echo "RewriteCond %{REQUEST_FILENAME} !-d<br>";
            echo "RewriteRule ^(.*)$ index.php?/$1 [L]</pre>";
        }
        exit();
    }

	// Production on Railway: suppress errors so API returns pure JSON
	$env = getenv('ENVIRONMENT') ?: (getenv('RAILWAY_PUBLIC_DOMAIN') ? 'production' : 'development');
	define('ENVIRONMENT', $env);
/*
 *---------------------------------------------------------------
 * ERROR REPORTING
 *---------------------------------------------------------------
 *
 * Different environments will require different levels of error reporting.
 * By default development will show errors but testing and live will hide them.
 */
switch (ENVIRONMENT)
{
	case 'development':
		error_reporting(-1);
		ini_set('display_errors', 1);
	break;

	case 'testing':
	case 'production':
		ini_set('display_errors', 0);
		if (version_compare(PHP_VERSION, '5.6', '>='))
		{
			error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_STRICT & ~E_USER_NOTICE & ~E_USER_DEPRECATED);
		}
		else
		{
			error_reporting(E_ALL & ~E_NOTICE & ~E_STRICT & ~E_USER_NOTICE);
		}
	break;

	default:
		if ($is_api_request) {
			set_cors_headers_for_api();
			header('Content-Type: application/json');
			http_response_code(503);
			echo json_encode(array('success' => false, 'error' => 'Application environment not set correctly'));
		} else {
			header('HTTP/1.1 503 Service Unavailable.', TRUE, 503);
			echo 'The application environment is not set correctly.';
		}
		exit(1); // EXIT_ERROR
}

/*
 *---------------------------------------------------------------
 * SYSTEM DIRECTORY NAME
 *---------------------------------------------------------------
 *
 * This variable must contain the name of your "system" directory.
 * Set the path if it is not in the same directory as this file.
 */
	$system_path = 'system';

/*
 *---------------------------------------------------------------
 * APPLICATION DIRECTORY NAME
 *---------------------------------------------------------------
 *
 * If you want this front controller to use a different "application"
 * directory than the default one you can set its name here. The directory
 * can also be renamed or relocated anywhere on your server. If you do,
 * use an absolute (full) server path.
 * For more info please see the user guide:
 *
 * https://codeigniter.com/user_guide/general/managing_apps.html
 *
 * NO TRAILING SLASH!
 */
	$application_folder = 'application';

/*
 *---------------------------------------------------------------
 * VIEW DIRECTORY NAME
 *---------------------------------------------------------------
 *
 * If you want to move the view directory out of the application
 * directory, set the path to it here. The directory can be renamed
 * and relocated anywhere on your server. If blank, it will default
 * to the standard location inside your application directory.
 * If you do move this, use an absolute (full) server path.
 *
 * NO TRAILING SLASH!
 */
	$view_folder = '';


/*
 * --------------------------------------------------------------------
 * DEFAULT CONTROLLER
 * --------------------------------------------------------------------
 *
 * Normally you will set your default controller in the routes.php file.
 * You can, however, force a custom routing by hard-coding a
 * specific controller class/function here. For most applications, you
 * WILL NOT set your routing here, but it's an option for those
 * special instances where you might want to override the standard
 * routing in a specific front controller that shares a common CI installation.
 *
 * IMPORTANT: If you set the routing here, NO OTHER controller will be
 * callable. In essence, this preference limits your application to ONE
 * specific controller. Leave the function name blank if you need
 * to call functions dynamically via the URI.
 *
 * Un-comment the $routing array below to use this feature
 */
	// The directory name, relative to the "controllers" directory.  Leave blank
	// if your controller is not in a sub-directory within the "controllers" one
	// $routing['directory'] = '';

	// The controller class file name.  Example:  mycontroller
	// $routing['controller'] = '';

	// The controller function you wish to be called.
	// $routing['function']	= '';


/*
 * -------------------------------------------------------------------
 *  CUSTOM CONFIG VALUES
 * -------------------------------------------------------------------
 *
 * The $assign_to_config array below will be passed dynamically to the
 * config class when initialized. This allows you to set custom config
 * items or override any default config values found in the config.php file.
 * This can be handy as it permits you to share one application between
 * multiple front controller files, with each file containing different
 * config values.
 *
 * Un-comment the $assign_to_config array below to use this feature
 */
	// $assign_to_config['name_of_config_item'] = 'value of config item';



// --------------------------------------------------------------------
// END OF USER CONFIGURABLE SETTINGS.  DO NOT EDIT BELOW THIS LINE
// --------------------------------------------------------------------

/*
 * ---------------------------------------------------------------
 *  Resolve the system path for increased reliability
 * ---------------------------------------------------------------
 */

	// Set the current directory correctly for CLI requests
	if (defined('STDIN'))
	{
		chdir(dirname(__FILE__));
	}

	if (($_temp = realpath($system_path)) !== FALSE)
	{
		$system_path = $_temp.DIRECTORY_SEPARATOR;
	}
	else
	{
		// Ensure there's a trailing slash
		$system_path = strtr(
			rtrim($system_path, '/\\'),
			'/\\',
			DIRECTORY_SEPARATOR.DIRECTORY_SEPARATOR
		).DIRECTORY_SEPARATOR;
	}

	// Is the system path correct?
	if ( ! is_dir($system_path))
	{
		if ($is_api_request) {
			set_cors_headers_for_api();
			header('Content-Type: application/json');
			http_response_code(503);
			echo json_encode(array('success' => false, 'error' => 'System folder path incorrect'));
		} else {
			header('HTTP/1.1 503 Service Unavailable.', TRUE, 503);
			echo 'Your system folder path does not appear to be set correctly. Please open the following file and correct this: '.pathinfo(__FILE__, PATHINFO_BASENAME);
		}
		exit(3); // EXIT_CONFIG
	}

/*
 * -------------------------------------------------------------------
 *  Now that we know the path, set the main path constants
 * -------------------------------------------------------------------
 */
	// The name of THIS file
	define('SELF', pathinfo(__FILE__, PATHINFO_BASENAME));

	// Path to the system directory
	define('BASEPATH', $system_path);

	// Path to the front controller (this file) directory
	define('FCPATH', dirname(__FILE__).DIRECTORY_SEPARATOR);

	// Name of the "system" directory
	define('SYSDIR', basename(BASEPATH));

	// The path to the "application" directory
	if (is_dir($application_folder))
	{
		if (($_temp = realpath($application_folder)) !== FALSE)
		{
			$application_folder = $_temp;
		}
		else
		{
			$application_folder = strtr(
				rtrim($application_folder, '/\\'),
				'/\\',
				DIRECTORY_SEPARATOR.DIRECTORY_SEPARATOR
			);
		}
	}
	elseif (is_dir(BASEPATH.$application_folder.DIRECTORY_SEPARATOR))
	{
		$application_folder = BASEPATH.strtr(
			trim($application_folder, '/\\'),
			'/\\',
			DIRECTORY_SEPARATOR.DIRECTORY_SEPARATOR
		);
	}
	else
	{
		if ($is_api_request) {
			set_cors_headers_for_api();
			header('Content-Type: application/json');
			http_response_code(503);
			echo json_encode(array('success' => false, 'error' => 'Application folder path incorrect'));
		} else {
			header('HTTP/1.1 503 Service Unavailable.', TRUE, 503);
			echo 'Your application folder path does not appear to be set correctly. Please open the following file and correct this: '.SELF;
		}
		exit(3); // EXIT_CONFIG
	}

	define('APPPATH', $application_folder.DIRECTORY_SEPARATOR);

	// The path to the "views" directory
	if ( ! isset($view_folder[0]) && is_dir(APPPATH.'views'.DIRECTORY_SEPARATOR))
	{
		$view_folder = APPPATH.'views';
	}
	elseif (is_dir($view_folder))
	{
		if (($_temp = realpath($view_folder)) !== FALSE)
		{
			$view_folder = $_temp;
		}
		else
		{
			$view_folder = strtr(
				rtrim($view_folder, '/\\'),
				'/\\',
				DIRECTORY_SEPARATOR.DIRECTORY_SEPARATOR
			);
		}
	}
	elseif (is_dir(APPPATH.$view_folder.DIRECTORY_SEPARATOR))
	{
		$view_folder = APPPATH.strtr(
			trim($view_folder, '/\\'),
			'/\\',
			DIRECTORY_SEPARATOR.DIRECTORY_SEPARATOR
		);
	}
	else
	{
		if ($is_api_request) {
			set_cors_headers_for_api();
			header('Content-Type: application/json');
			http_response_code(503);
			echo json_encode(array('success' => false, 'error' => 'View folder path incorrect'));
		} else {
			header('HTTP/1.1 503 Service Unavailable.', TRUE, 503);
			echo 'Your view folder path does not appear to be set correctly. Please open the following file and correct this: '.SELF;
		}
		exit(3); // EXIT_CONFIG
	}

	define('VIEWPATH', $view_folder.DIRECTORY_SEPARATOR);

/*
 * --------------------------------------------------------------------
 * LOAD THE BOOTSTRAP FILE
 * --------------------------------------------------------------------
 *
 * And away we go...
 */
require_once BASEPATH.'core/CodeIgniter.php';
