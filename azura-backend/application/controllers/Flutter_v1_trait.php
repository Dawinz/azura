<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Flutter mobile API methods used by V1 controller.
 */
trait Flutter_v1_trait {

    protected function _flutter_json_input() {
        $raw = $this->input->raw_input_stream;
        if ($raw === null || $raw === '') {
            $raw = file_get_contents('php://input');
        }
        if ($raw === null || $raw === '') {
            return array();
        }
        $j = json_decode($raw, true);
        return is_array($j) ? $j : array();
    }

    protected function _flutter_user_by_id($user_id) {
        $user_id = (int) $user_id;
        if ($user_id < 1) {
            return null;
        }
        return $this->db->get_where('users', array('id' => $user_id))->row();
    }

    protected function _flutter_user_by_slug($slug) {
        $slug = trim((string) $slug);
        if ($slug === '') {
            return null;
        }
        $this->db->reset_query();
        $this->db->group_start();
        $this->db->where('slug', $slug);
        $this->db->or_where('username', $slug);
        $this->db->group_end();
        return $this->db->get('users')->row();
    }

    /** GET /v1/banner/list */
    public function banner_list() {
        $this->output->set_content_type('application/json');
        $this->_set_cors_headers();
        $lang_id = max(1, (int) $this->input->get('lang_id') ?: 1);
        $this->db->where('lang_id', $lang_id)->order_by('item_order', 'ASC');
        $rows = $this->db->get('slider')->result();
        $base = $this->config->item('base_url');
        $out = array();
        foreach ($rows as $r) {
            $out[] = array(
                'id' => (int) $r->id,
                'title' => $r->title,
                'description' => $r->description,
                'link' => $r->link,
                'image' => !empty($r->image) ? $base . $r->image : null,
                'image_mobile' => !empty($r->image_mobile) ? $base . $r->image_mobile : null,
            );
        }
        $this->output->set_output(json_encode($out));
    }

    /** GET /v1/product/detail_get */
    public function product_detail_get() {
        $this->output->set_content_type('application/json');
        $this->_set_cors_headers();
        $slug = trim((string) $this->input->get('slug', true));
        if ($slug === '') {
            $this->output->set_status_header(400);
            $this->output->set_output(json_encode(array('success' => false, 'error' => 'slug required')));
            return;
        }
        $db_host = getenv('MYSQLHOST') ?: getenv('DATABASE_HOST') ?: 'localhost';
        $db_user = getenv('MYSQLUSER') ?: getenv('DATABASE_USER') ?: 'root';
        $db_pass = getenv('MYSQLPASSWORD') ?: getenv('DATABASE_PASSWORD') ?: '';
        $db_name = getenv('MYSQLDATABASE') ?: getenv('DATABASE_NAME') ?: 'railway';
        $db_port = (int) (getenv('MYSQLPORT') ?: getenv('DATABASE_PORT') ?: 3306);
        $base = $this->config->item('base_url');
        mysqli_report(MYSQLI_REPORT_OFF);

        $mysqli = @new mysqli($db_host, $db_user, $db_pass, $db_name, $db_port);
        if ($mysqli->connect_errno) {
            $this->output->set_status_header(503);
            $this->output->set_output(json_encode(array('success' => false, 'error' => 'Service temporarily unavailable')));
            return;
        }
        try {
            $mysqli->set_charset('utf8mb4');
            $safe_slug = $mysqli->real_escape_string($slug);
            $sql = "SELECT p.*,
                           COALESCE((SELECT title FROM product_details pd WHERE pd.product_id = p.id AND pd.lang_id = 1 LIMIT 1), '') AS title,
                           COALESCE((SELECT description FROM product_details pd WHERE pd.product_id = p.id AND pd.lang_id = 1 LIMIT 1), '') AS description,
                           COALESCE(u.username, '') AS user_username,
                           COALESCE(u.shop_name, '') AS shop_name,
                           COALESCE(u.role, '') AS user_role,
                           COALESCE(u.slug, '') AS user_slug
                    FROM products p
                    LEFT JOIN users u ON p.user_id = u.id
                    WHERE p.slug = '$safe_slug'
                      AND p.is_deleted = 0
                    LIMIT 1";
            $res = $mysqli->query($sql);
            $p = $res ? $res->fetch_assoc() : null;
            if (empty($p)) {
                $mysqli->close();
                $this->output->set_status_header(404);
                $this->output->set_output(json_encode(array('success' => false, 'error' => 'Product not found')));
                return;
            }
            if (isset($p['product_type']) && (string) $p['product_type'] === 'digital') {
                $mysqli->close();
                $this->output->set_status_header(404);
                $this->output->set_output(json_encode(array(
                    'success' => false,
                    'error' => 'This product is not available in the app. Please visit azuramall.shop in your browser.',
                )));
                return;
            }

            $pid = (int) ($p['id'] ?? 0);
            $main_image = '';
            $files = array();
            if ($pid > 0) {
                $img_res = $mysqli->query("SELECT image_default, image_small, is_main FROM images WHERE product_id = $pid ORDER BY is_main DESC, id ASC");
                while ($img_res && ($im = $img_res->fetch_assoc())) {
                    $path = !empty($im['image_small']) ? (string) $im['image_small'] : (string) ($im['image_default'] ?? '');
                    if ($path === '') {
                        continue;
                    }
                    $url = (strpos($path, 'http://') === 0 || strpos($path, 'https://') === 0)
                        ? $path
                        : ($base . 'uploads/images/' . ltrim($path, '/'));
                    $files[] = $url;
                    if ($main_image === '' && (int) ($im['is_main'] ?? 0) === 1) {
                        $main_image = $url;
                    }
                }
            }
            if ($main_image === '' && !empty($files)) {
                $main_image = $files[0];
            }

            $price = (float) ($p['price'] ?? 0);
            $disc = (int) ($p['discount_rate'] ?? 0);
            $price_after = $disc > 0 ? $price * (1 - $disc / 100) : null;
            $payload = array(
                'id' => (string) ($p['id'] ?? ''),
                'title' => (string) ($p['title'] ?? ''),
                'slug' => (string) ($p['slug'] ?? ''),
                'image' => $main_image,
                'files' => $files,
                'product_type' => (string) ($p['product_type'] ?? ''),
                'listing_type' => (string) ($p['listing_type'] ?? ''),
                'category_id' => (string) ($p['category_id'] ?? ''),
                'subcategory_id' => isset($p['subcategory_id']) ? (string) $p['subcategory_id'] : null,
                'third_category_id' => isset($p['third_category_id']) ? (string) $p['third_category_id'] : null,
                'price' => $price,
                'price_afet_discount' => $price_after,
                'dicountpercent' => $disc > 0 ? $disc : null,
                'currency' => (string) ($p['currency'] ?? ''),
                'description' => (string) ($p['description'] ?? ''),
                'product_condition' => isset($p['product_condition']) ? (string) $p['product_condition'] : '',
                'country_id' => isset($p['country_id']) ? (string) $p['country_id'] : '',
                'state_id' => isset($p['state_id']) ? (string) $p['state_id'] : '',
                'city_id' => isset($p['city_id']) ? (string) $p['city_id'] : '',
                'address' => isset($p['address']) ? (string) $p['address'] : '',
                'zip_code' => isset($p['zip_code']) ? (string) $p['zip_code'] : '',
                'user_id' => (string) ($p['user_id'] ?? ''),
                'status' => (string) ($p['status'] ?? ''),
                'is_promoted' => (string) ($p['is_promoted'] ?? '0'),
                'promote_start_date' => (string) ($p['promote_start_date'] ?? ''),
                'promote_end_date' => (string) ($p['promote_end_date'] ?? ''),
                'promote_plan' => (string) ($p['promote_plan'] ?? ''),
                'promote_day' => (string) ($p['promote_day'] ?? ''),
                'visibility' => (string) ($p['visibility'] ?? ''),
                'rating' => (string) ($p['rating'] ?? '0'),
                'hit' => isset($p['pageviews']) ? (string) $p['pageviews'] : '0',
                'external_link' => (string) ($p['external_link'] ?? ''),
                'files_included' => (string) ($p['files_included'] ?? ''),
                'shipping_time' => isset($p['shipping_delivery_time_id']) ? (string) $p['shipping_delivery_time_id'] : '',
                'shipping_cost_type' => null,
                'shipping_cost' => null,
                'is_sold' => (string) ($p['is_sold'] ?? '0'),
                'is_deleted' => (string) ($p['is_deleted'] ?? '0'),
                'is_draft' => (string) ($p['is_draft'] ?? '0'),
                'created_at' => (string) ($p['created_at'] ?? ''),
                'user_username' => (string) ($p['user_username'] ?? ''),
                'shop_name' => (string) ($p['shop_name'] ?? ''),
                'user_role' => (string) ($p['user_role'] ?? ''),
                'user_slug' => (string) ($p['user_slug'] ?? ''),
                'product_url' => $base . (string) ($p['slug'] ?? ''),
            );
            $mysqli->close();
            $this->output->set_output(json_encode($payload));
        } catch (Throwable $e) {
            $mysqli->close();
            $this->output->set_status_header(503);
            $this->output->set_output(json_encode(array('success' => false, 'error' => 'Service temporarily unavailable')));
        }
    }

    /** GET /v1/profile */
    public function profile_public() {
        $this->output->set_content_type('application/json');
        $this->_set_cors_headers();
        $slug = $this->input->get('profile_slug', true);
        $u = $this->_flutter_user_by_slug($slug);
        if (empty($u)) {
            $this->output->set_status_header(404);
            $this->output->set_output(json_encode(array('success' => false, 'error' => 'User not found')));
            return;
        }
        $base = $this->config->item('base_url');
        $this->output->set_output(json_encode(array(
            'success' => true,
            'id' => (int) $u->id,
            'username' => $u->username,
            'slug' => $u->slug,
            'avatar' => !empty($u->avatar) ? $base . $u->avatar : '',
            'shop_name' => $u->shop_name,
            'about_me' => $u->about_me,
            'created_at' => $u->created_at,
        )));
    }

    /** GET /v1/profile/products — same list shape as /v1/product/list */
    public function profile_products() {
        $this->output->set_content_type('application/json');
        $this->_set_cors_headers();
        $slug = $this->input->get('slug', true);
        $u = $this->_flutter_user_by_slug($slug);
        if (empty($u)) {
            $this->output->set_status_header(404);
            $this->output->set_output(json_encode(array('success' => false, 'error' => 'User not found')));
            return;
        }
        $page = max(1, (int) $this->input->get('page'));
        $per_page = min(50, max(1, (int) $this->input->get('per_page') ?: 20));
        $lang_id = max(1, (int) $this->input->get('lang_id') ?: 1);
        $seller_id = (int) $u->id;

        $this->db->from('products p');
        $this->db->where('p.user_id', $seller_id);
        $this->db->where('p.status', 1);
        $this->db->where('p.visibility', 1);
        $this->db->where('p.is_deleted', 0);
        $this->db->where('p.is_draft', 0);
        $this->db->where('p.product_type', 'physical');
        $this->db->where('EXISTS (SELECT 1 FROM images img WHERE img.product_id = p.id AND img.image_small IS NOT NULL AND img.image_small != \'\')', null, false);
        $total = $this->db->count_all_results();

        $this->db->select('p.id, p.slug, p.price, p.currency, p.discount_rate, p.user_id, p.rating, p.is_promoted, p.is_sold, p.created_at, p.product_type');
        $this->db->select('(SELECT title FROM product_details WHERE product_id = p.id AND lang_id = ' . $lang_id . ' LIMIT 1) AS title');
        $this->db->select('(SELECT image_small FROM images WHERE product_id = p.id AND is_main = 1 LIMIT 1) AS image');
        $this->db->from('products p');
        $this->db->where('p.user_id', $seller_id);
        $this->db->where('p.status', 1);
        $this->db->where('p.visibility', 1);
        $this->db->where('p.is_deleted', 0);
        $this->db->where('p.is_draft', 0);
        $this->db->where('p.product_type', 'physical');
        $this->db->where('EXISTS (SELECT 1 FROM images img WHERE img.product_id = p.id AND img.image_small IS NOT NULL AND img.image_small != \'\')', null, false);
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
                $image_url = preg_match('#^https?://#i', $img) ? $img : $base_url . $img;
            }
            $list[] = array(
                'id' => (int) $row->id,
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
    }

    /** POST /v1/messages/addnew */
    public function messages_addnew() {
        $this->output->set_content_type('application/json');
        $this->_set_cors_headers();
        $in = $this->_flutter_json_input();
        $sender = (int) (isset($in['user_id']) ? $in['user_id'] : 0);
        $receiver = (int) (isset($in['receiver_id']) ? $in['receiver_id'] : 0);
        $subject = isset($in['subject']) ? trim($in['subject']) : '';
        $body = isset($in['body_message']) ? trim($in['body_message']) : '';
        if ($sender < 1 || $receiver < 1 || $subject === '' || $body === '') {
            $this->output->set_status_header(400);
            $this->output->set_output(json_encode(array('success' => false, 'error' => 'Invalid payload')));
            return;
        }
        $this->db->where('sender_id', $sender)->where('receiver_id', $receiver)->where('subject', $subject);
        $existing = $this->db->get('conversations')->row();
        if (!empty($existing)) {
            $cid = (int) $existing->id;
        } else {
            $this->db->insert('conversations', array(
                'sender_id' => $sender,
                'receiver_id' => $receiver,
                'subject' => $subject,
                'created_at' => date('Y-m-d H:i:s'),
            ));
            $cid = (int) $this->db->insert_id();
        }
        $this->db->insert('conversation_messages', array(
            'conversation_id' => $cid,
            'sender_id' => $sender,
            'receiver_id' => $receiver,
            'message' => $body,
            'is_read' => 0,
            'deleted_user_id' => 0,
            'created_at' => date('Y-m-d H:i:s'),
        ));
        $this->output->set_output(json_encode(array('success' => true, 'conversation_id' => $cid)));
    }

    /** POST /v1/messages/send */
    public function messages_send() {
        $this->output->set_content_type('application/json');
        $this->_set_cors_headers();
        $in = $this->_flutter_json_input();
        $sender = (int) (isset($in['user_id']) ? $in['user_id'] : 0);
        $receiver = (int) (isset($in['receiver_id']) ? $in['receiver_id'] : 0);
        $cid = (int) (isset($in['id_message']) ? $in['id_message'] : 0);
        $body = isset($in['body_message']) ? trim($in['body_message']) : '';
        if ($sender < 1 || $receiver < 1 || $cid < 1 || $body === '') {
            $this->output->set_status_header(400);
            $this->output->set_output(json_encode(array('success' => false, 'error' => 'Invalid payload')));
            return;
        }
        $this->db->insert('conversation_messages', array(
            'conversation_id' => $cid,
            'sender_id' => $sender,
            'receiver_id' => $receiver,
            'message' => $body,
            'is_read' => 0,
            'deleted_user_id' => 0,
            'created_at' => date('Y-m-d H:i:s'),
        ));
        $this->output->set_output(json_encode(array('success' => true)));
    }

    /** GET /v1/messages/list */
    public function messages_list() {
        $this->output->set_content_type('application/json');
        $this->_set_cors_headers();
        $uid = (int) $this->input->get('user_id');
        if ($uid < 1) {
            $this->output->set_status_header(400);
            $this->output->set_output(json_encode(array()));
            return;
        }
        $this->db->where('sender_id', $uid)->or_where('receiver_id', $uid);
        $this->db->order_by('created_at', 'DESC');
        $convs = $this->db->get('conversations')->result();
        $list = array();
        foreach ($convs as $c) {
            $list[] = array(
                'id' => (int) $c->id,
                'sender_id' => (int) $c->sender_id,
                'receiver_id' => (int) $c->receiver_id,
                'subject' => $c->subject,
                'created_at' => $c->created_at,
            );
        }
        $this->output->set_output(json_encode($list));
    }

    /** GET /v1/messages/conversation */
    public function messages_conversation() {
        $this->output->set_content_type('application/json');
        $this->_set_cors_headers();
        $cid = (int) $this->input->get('conversation_id');
        $uid = (int) $this->input->get('user_id');
        if ($cid < 1 || $uid < 1) {
            $this->output->set_status_header(400);
            $this->output->set_output(json_encode(array('success' => false)));
            return;
        }
        $this->db->where('id', $cid);
        $conv = $this->db->get('conversations')->row();
        if (empty($conv) || ((int) $conv->sender_id !== $uid && (int) $conv->receiver_id !== $uid)) {
            $this->output->set_status_header(404);
            $this->output->set_output(json_encode(array('success' => false, 'error' => 'Not found')));
            return;
        }
        $this->db->where('conversation_id', $cid)->where('deleted_user_id !=', $uid)->order_by('id', 'ASC');
        $msgs = $this->db->get('conversation_messages')->result();
        $this->output->set_output(json_encode(array(
            'conversation' => $conv,
            'messages' => $msgs,
        )));
    }

    /** GET /v1/messages/unread-count */
    public function messages_unread_count() {
        $this->output->set_content_type('application/json');
        $this->_set_cors_headers();
        $uid = (int) $this->input->get('user_id');
        $n = 0;
        if ($uid > 0) {
            $this->db->where('receiver_id', $uid)->where('is_read', 0)->where('deleted_user_id !=', $uid);
            $n = (int) $this->db->count_all_results('conversation_messages');
        }
        $this->output->set_output(json_encode(array('unread_count' => $n)));
    }

    /** POST /api/v1/messages/delete */
    public function api_messages_delete() {
        $this->output->set_content_type('application/json');
        $this->_set_cors_headers();
        $in = $this->_flutter_json_input();
        $cid = (int) (isset($in['id_message']) ? $in['id_message'] : 0);
        $uid = (int) (isset($in['user_id']) ? $in['user_id'] : 0);
        if ($cid < 1 || $uid < 1) {
            $this->output->set_status_header(400);
            $this->output->set_output(json_encode(array('success' => false)));
            return;
        }
        $this->db->where('conversation_id', $cid)->where('deleted_user_id', 0);
        $msgs = $this->db->get('conversation_messages')->result();
        foreach ($msgs as $m) {
            if ((int) $m->sender_id === $uid || (int) $m->receiver_id === $uid) {
                if ((int) $m->deleted_user_id === 0) {
                    $this->db->where('id', $m->id)->update('conversation_messages', array('deleted_user_id' => $uid));
                } else {
                    $this->db->where('id', $m->id)->delete('conversation_messages');
                }
            }
        }
        $this->db->reset_query();
        $this->db->where('conversation_id', $cid);
        $left = $this->db->count_all_results('conversation_messages');
        if ($left === 0) {
            $this->db->where('id', $cid)->delete('conversations');
        }
        $this->output->set_output(json_encode(array('success' => true)));
    }

    /** GET /api/v1/profile/favorites */
    public function api_profile_favorites() {
        $this->output->set_content_type('application/json');
        $this->_set_cors_headers();
        $uid = (int) $this->input->get('user_id');
        if ($uid < 1) {
            $this->output->set_output(json_encode(array()));
            return;
        }
        $this->db->select('p.id');
        $this->db->from('wishlist w');
        $this->db->join('products p', 'p.id = w.product_id');
        $this->db->where('w.user_id', $uid)->where('p.is_deleted', 0);
        $ids = array();
        foreach ($this->db->get()->result() as $r) {
            $ids[] = (int) $r->id;
        }
        $this->output->set_output(json_encode($ids));
    }

    /** GET /api/v1/profile/followers */
    public function api_profile_followers() {
        $this->output->set_content_type('application/json');
        $this->_set_cors_headers();
        $uid = (int) $this->input->get('user_id');
        $list = array();
        if ($uid > 0) {
            $this->db->select('u.id, u.username, u.slug, u.avatar');
            $this->db->from('followers f');
            $this->db->join('users u', 'u.id = f.follower_id');
            $this->db->where('f.following_id', $uid);
            $list = $this->db->get()->result();
        }
        $this->output->set_output(json_encode($list));
    }

    /** GET /api/v1/profile/following */
    public function api_profile_following() {
        $this->output->set_content_type('application/json');
        $this->_set_cors_headers();
        $uid = (int) $this->input->get('user_id');
        $list = array();
        if ($uid > 0) {
            $this->db->select('u.id, u.username, u.slug, u.avatar');
            $this->db->from('followers f');
            $this->db->join('users u', 'u.id = f.following_id');
            $this->db->where('f.follower_id', $uid);
            $list = $this->db->get()->result();
        }
        $this->output->set_output(json_encode($list));
    }

    /** GET /api/v1/profile/reviews */
    public function api_profile_reviews() {
        $this->output->set_content_type('application/json');
        $this->_set_cors_headers();
        $uid = (int) $this->input->get('user_id');
        $list = array();
        if ($uid > 0) {
            $this->db->select('r.*, p.slug AS product_slug');
            $this->db->from('reviews r');
            $this->db->join('products p', 'p.id = r.product_id');
            $this->db->where('r.user_id', $uid);
            $this->db->order_by('r.id', 'DESC');
            $list = $this->db->get()->result();
        }
        $this->output->set_output(json_encode($list));
    }

    /** GET /api/v1/profile/drafts */
    public function api_profile_drafts() {
        $this->output->set_content_type('application/json');
        $this->_set_cors_headers();
        $uid = (int) $this->input->get('user_id');
        $list = array();
        if ($uid > 0) {
            $this->db->where('user_id', $uid)->where('is_draft', 1)->where('is_deleted', 0);
            $this->db->order_by('created_at', 'DESC');
            $list = $this->db->get('products')->result();
        }
        $this->output->set_output(json_encode($list));
    }

    /** GET /v1/setting/profile */
    public function setting_profile_get() {
        $this->output->set_content_type('application/json');
        $this->_set_cors_headers();
        $uid = (int) $this->input->get('user_id');
        $u = $this->_flutter_user_by_id($uid);
        if (empty($u)) {
            $this->output->set_status_header(404);
            $this->output->set_output(json_encode(array('success' => false)));
            return;
        }
        $this->output->set_output(json_encode(array(
            'success' => true,
            'username' => $u->username,
            'email' => $u->email,
            'slug' => $u->slug,
            'send_email_new_message' => (int) $u->send_email_new_message,
            'first_name' => $u->first_name,
            'last_name' => $u->last_name,
        )));
    }

    /** POST /api/v1/setting/profile */
    public function api_setting_profile_post() {
        $this->output->set_content_type('application/json');
        $this->_set_cors_headers();
        $in = $this->_flutter_json_input();
        $uid = (int) (isset($in['user_id']) ? $in['user_id'] : 0);
        if ($uid < 1) {
            $this->output->set_status_header(400);
            $this->output->set_output(json_encode(array('success' => false)));
            return;
        }
        $data = array(
            'username' => isset($in['username']) ? $in['username'] : null,
            'email' => isset($in['email']) ? $in['email'] : null,
            'slug' => isset($in['slug']) ? $in['slug'] : null,
            'send_email_new_message' => isset($in['send_email_new_message']) ? (int) $in['send_email_new_message'] : 0,
        );
        $data = array_filter($data, function ($v) { return $v !== null; });
        $this->db->where('id', $uid)->update('users', $data);
        $this->output->set_output(json_encode(array('success' => true)));
    }

    /** GET /api/v1/setting/contact */
    public function api_setting_contact_get() {
        $this->output->set_content_type('application/json');
        $this->_set_cors_headers();
        $uid = (int) $this->input->get('user_id');
        $u = $this->_flutter_user_by_id($uid);
        if (empty($u)) {
            $this->output->set_status_header(404);
            $this->output->set_output(json_encode(array('success' => false)));
            return;
        }
        $this->output->set_output(json_encode(array(
            'phone' => $u->phone_number,
            'address' => $u->address,
            'country_id' => (int) $u->country_id,
            'state_id' => (int) $u->state_id,
            'city_id' => (int) $u->city_id,
        )));
    }

    /** POST /api/v1/setting/contact */
    public function api_setting_contact_post() {
        $this->output->set_content_type('application/json');
        $this->_set_cors_headers();
        $in = $this->_flutter_json_input();
        $uid = (int) (isset($in['user_id']) ? $in['user_id'] : 0);
        if ($uid < 1) {
            $this->output->set_status_header(400);
            $this->output->set_output(json_encode(array('success' => false)));
            return;
        }
        $this->db->where('id', $uid)->update('users', array(
            'phone_number' => isset($in['phone']) ? $in['phone'] : '',
            'address' => isset($in['address']) ? $in['address'] : '',
            'country_id' => isset($in['country_id']) ? (int) $in['country_id'] : 0,
            'state_id' => isset($in['state_id']) ? (int) $in['state_id'] : 0,
            'city_id' => isset($in['city_id']) ? (int) $in['city_id'] : 0,
        ));
        $this->output->set_output(json_encode(array('success' => true)));
    }

    /** GET /v1/setting/shop */
    public function setting_shop_get() {
        $this->output->set_content_type('application/json');
        $this->_set_cors_headers();
        $uid = (int) $this->input->get('user_id');
        $u = $this->_flutter_user_by_id($uid);
        if (empty($u)) {
            $this->output->set_status_header(404);
            $this->output->set_output(json_encode(array('success' => false)));
            return;
        }
        $this->output->set_output(json_encode(array(
            'shop_name' => $u->shop_name,
            'about' => $u->about_me,
            'show_rss_feeds' => (int) $u->show_rss_feeds,
        )));
    }

    /** POST /api/v1/setting/shop */
    public function api_setting_shop_post() {
        $this->output->set_content_type('application/json');
        $this->_set_cors_headers();
        $in = $this->_flutter_json_input();
        $uid = (int) (isset($in['user_id']) ? $in['user_id'] : 0);
        if ($uid < 1) {
            $this->output->set_status_header(400);
            $this->output->set_output(json_encode(array('success' => false)));
            return;
        }
        $this->db->where('id', $uid)->update('users', array(
            'shop_name' => isset($in['shop_name']) ? $in['shop_name'] : '',
            'about_me' => isset($in['about']) ? $in['about'] : '',
            'show_rss_feeds' => isset($in['show_rss_feeds']) ? (int) $in['show_rss_feeds'] : 0,
        ));
        $this->output->set_output(json_encode(array('success' => true)));
    }

    /** GET /api/v1/setting/shipping */
    public function api_setting_shipping_get() {
        $this->output->set_content_type('application/json');
        $this->_set_cors_headers();
        $uid = (int) $this->input->get('user_id');
        $row = null;
        if ($uid > 0) {
            $this->db->where('user_id', $uid)->order_by('id', 'DESC')->limit(1);
            $row = $this->db->get('shipping_addresses')->row();
        }
        $this->output->set_output(json_encode($row ? $row : new stdClass()));
    }

    /** POST /api/v1/setting/shipping */
    public function api_setting_shipping_post() {
        $this->output->set_content_type('application/json');
        $this->_set_cors_headers();
        $in = $this->_flutter_json_input();
        $uid = (int) (isset($in['user_id']) ? $in['user_id'] : 0);
        if ($uid < 1) {
            $this->output->set_status_header(400);
            $this->output->set_output(json_encode(array('success' => false)));
            return;
        }
        $data = array(
            'user_id' => $uid,
            'first_name' => isset($in['shipping_first_name']) ? $in['shipping_first_name'] : '',
            'last_name' => isset($in['shipping_last_name']) ? $in['shipping_last_name'] : '',
            'email' => isset($in['shipping_email']) ? $in['shipping_email'] : '',
            'phone_number' => isset($in['shipping_phone_number']) ? $in['shipping_phone_number'] : '',
            'address' => isset($in['shipping_address_1']) ? $in['shipping_address_1'] : '',
            'country_id' => isset($in['shipping_country_id']) ? (string) $in['shipping_country_id'] : '',
            'state_id' => isset($in['shipping_state']) ? (int) $in['shipping_state'] : 0,
            'city' => isset($in['shipping_city']) ? $in['shipping_city'] : '',
            'zip_code' => isset($in['shipping_zip_code']) ? $in['shipping_zip_code'] : '',
            'title' => 'Default',
            'created_at' => date('Y-m-d H:i:s'),
        );
        $this->db->insert('shipping_addresses', $data);
        $this->output->set_output(json_encode(array('success' => true)));
    }

    /** GET /api/v1/setting/sosmed */
    public function api_setting_sosmed_get() {
        $this->output->set_content_type('application/json');
        $this->_set_cors_headers();
        $uid = (int) $this->input->get('user_id');
        $u = $this->_flutter_user_by_id($uid);
        if (empty($u)) {
            $this->output->set_status_header(404);
            $this->output->set_output(json_encode(array('success' => false)));
            return;
        }
        $this->output->set_output(json_encode(array(
            'facebook_url' => $u->facebook_url,
            'instagram_url' => $u->instagram_url,
        )));
    }

    /** POST /api/v1/setting/sosmed */
    public function api_setting_sosmed_post() {
        $this->output->set_content_type('application/json');
        $this->_set_cors_headers();
        $in = $this->_flutter_json_input();
        $uid = (int) (isset($in['user_id']) ? $in['user_id'] : 0);
        if ($uid < 1) {
            $this->output->set_status_header(400);
            $this->output->set_output(json_encode(array('success' => false)));
            return;
        }
        $this->db->where('id', $uid)->update('users', array(
            'facebook_url' => isset($in['facebook_url']) ? $in['facebook_url'] : '',
            'instagram_url' => isset($in['instagram_url']) ? $in['instagram_url'] : '',
        ));
        $this->output->set_output(json_encode(array('success' => true)));
    }

    /** POST /api/v1/setting/password */
    public function api_setting_password_post() {
        $this->output->set_content_type('application/json');
        $this->_set_cors_headers();
        $in = $this->_flutter_json_input();
        $uid = (int) (isset($in['user_id']) ? $in['user_id'] : 0);
        $cur = isset($in['current_password']) ? $in['current_password'] : '';
        $new = isset($in['new_password']) ? $in['new_password'] : '';
        if ($uid < 1 || $new === '') {
            $this->output->set_status_header(400);
            $this->output->set_output(json_encode(array('success' => false, 'error' => 'Invalid')));
            return;
        }
        $this->load->model('auth_model');
        $this->load->library('bcrypt');
        $u = $this->auth_model->get_user($uid);
        if (empty($u) || !$this->bcrypt->check_password($cur, $u->password)) {
            $this->output->set_status_header(403);
            $this->output->set_output(json_encode(array('success' => false, 'error' => 'Invalid password')));
            return;
        }
        $this->db->where('id', $uid)->update('users', array('password' => $this->bcrypt->hash_password($new)));
        $this->output->set_output(json_encode(array('success' => true)));
    }

    /** POST /api/v1/setting/resend-activation */
    public function api_setting_resend_activation_post() {
        $this->output->set_content_type('application/json');
        $this->_set_cors_headers();
        $in = $this->_flutter_json_input();
        $uid = (int) (isset($in['user_id']) ? $in['user_id'] : 0);
        if ($uid < 1) {
            $this->output->set_status_header(400);
            $this->output->set_output(json_encode(array('success' => false)));
            return;
        }
        try {
            $this->load->model('email_model');
            $this->load->model('auth_model');
            $u = $this->auth_model->get_user($uid);
            if (!empty($u) && !empty($u->token)) {
                $this->auth_model->send_email_activation_ajax($uid, $u->token);
            }
        } catch (Throwable $e) {
            // ignore mail errors
        }
        $this->output->set_output(json_encode(array('success' => true)));
    }

    /** POST /v1/sell */
    public function sell_create() {
        $this->output->set_content_type('application/json');
        $this->_set_cors_headers();
        $in = $this->_flutter_json_input();
        $uid = (int) (isset($in['user_id']) ? $in['user_id'] : 0);
        $title = isset($in['title']) ? trim($in['title']) : '';
        $cat = (int) (isset($in['category_id']) ? $in['category_id'] : 0);
        $desc = isset($in['description']) ? $in['description'] : '';
        if ($uid < 1 || $title === '' || $cat < 1) {
            $this->output->set_status_header(400);
            $this->output->set_output(json_encode(array('success' => false, 'error' => 'Invalid')));
            return;
        }
        $this->load->helper('custom');
        $slug = str_slug($title);
        if ($slug === '') {
            $slug = 'item-' . time();
        }
        $slug = substr($slug, 0, 180) . '-' . rand(1000, 9999);
        $now = date('Y-m-d H:i:s');
        $pdata = array(
            'slug' => $slug,
            'product_type' => 'physical',
            'listing_type' => 'sell_on_site',
            'sku' => '',
            'price' => 0,
            'currency' => 'USD',
            'discount_rate' => 0,
            'vat_rate' => 0,
            'user_id' => $uid,
            'category_id' => $cat,
            'status' => 0,
            'is_promoted' => 0,
            'promote_start_date' => $now,
            'promote_end_date' => $now,
            'promote_plan' => 'none',
            'promote_day' => 0,
            'visibility' => 1,
            'rating' => 0,
            'pageviews' => 0,
            'demo_url' => '',
            'external_link' => '',
            'files_included' => '',
            'stock' => 1,
            'shipping_delivery_time_id' => 0,
            'multiple_sale' => 1,
            'is_deleted' => 0,
            'is_draft' => 1,
            'is_free_product' => 0,
            'created_at' => $now,
        );
        $this->db->insert('products', $pdata);
        $pid = (int) $this->db->insert_id();
        $this->db->insert('product_details', array(
            'product_id' => $pid,
            'lang_id' => 1,
            'title' => $title,
            'description' => $desc,
            'seo_title' => $title,
            'seo_description' => '',
            'seo_keywords' => '',
        ));
        $this->output->set_output(json_encode(array('success' => true, 'product_id' => $pid, 'slug' => $slug)));
    }

    /** POST /v1/sell/detail */
    public function sell_detail() {
        $this->output->set_content_type('application/json');
        $this->_set_cors_headers();
        $in = $this->_flutter_json_input();
        $pid = (int) (isset($in['product_id']) ? $in['product_id'] : 0);
        $uid_check = isset($in['user_id']) ? (int) $in['user_id'] : 0;
        if ($pid < 1) {
            $this->output->set_status_header(400);
            $this->output->set_output(json_encode(array('success' => false)));
            return;
        }
        $p = $this->db->get_where('products', array('id' => $pid))->row();
        if (empty($p) || ($uid_check > 0 && (int) $p->user_id !== $uid_check)) {
            $this->output->set_status_header(403);
            $this->output->set_output(json_encode(array('success' => false)));
            return;
        }
        $upd = array(
            'price' => isset($in['price']) ? (int) $in['price'] : 0,
            'stock' => isset($in['quantity']) ? (int) $in['quantity'] : 1,
        );
        $this->db->where('id', $pid)->update('products', $upd);
        $this->output->set_output(json_encode(array('success' => true)));
    }

    /** POST /api/v1/sell/images */
    public function api_sell_images() {
        $this->output->set_content_type('application/json');
        $this->_set_cors_headers();
        $pid = (int) $this->input->post('product_id', true);
        if ($pid < 1) {
            $this->output->set_status_header(400);
            $this->output->set_output(json_encode(array('success' => false)));
            return;
        }
        $file_field = null;
        if (!empty($_FILES)) {
            $keys = array_keys($_FILES);
            $file_field = $keys[0];
        }
        if ($file_field === null) {
            $this->output->set_status_header(400);
            $this->output->set_output(json_encode(array('success' => false, 'error' => 'No file')));
            return;
        }
        $this->load->model('upload_model');
        $temp_path = $this->upload_model->upload_temp_image($file_field);
        if (empty($temp_path)) {
            $this->output->set_status_header(400);
            $this->output->set_output(json_encode(array('success' => false, 'error' => 'Upload failed')));
            return;
        }
        $data = array(
            'product_id' => $pid,
            'image_default' => $this->upload_model->product_default_image_upload($temp_path, 'images'),
            'image_big' => $this->upload_model->product_big_image_upload($temp_path, 'images'),
            'image_small' => $this->upload_model->product_small_image_upload($temp_path, 'images'),
            'is_main' => 0,
            'storage' => 'local',
        );
        $this->upload_model->delete_temp_image($temp_path);
        $this->db->insert('images', $data);
        $this->output->set_output(json_encode(array('success' => true, 'image_id' => (int) $this->db->insert_id())));
    }

    /** POST /api/v1/sell/image/delete */
    public function api_sell_image_delete() {
        $this->output->set_content_type('application/json');
        $this->_set_cors_headers();
        $in = $this->_flutter_json_input();
        $iid = (int) (isset($in['image_id']) ? $in['image_id'] : 0);
        $pid = (int) (isset($in['product_id']) ? $in['product_id'] : 0);
        if ($iid < 1 || $pid < 1) {
            $this->output->set_status_header(400);
            $this->output->set_output(json_encode(array('success' => false)));
            return;
        }
        $this->db->where('id', $iid)->where('product_id', $pid)->delete('images');
        $this->output->set_output(json_encode(array('success' => true)));
    }

    /** POST /api/v1/sell/image/set-main */
    public function api_sell_image_set_main() {
        $this->output->set_content_type('application/json');
        $this->_set_cors_headers();
        $in = $this->_flutter_json_input();
        $iid = (int) (isset($in['image_id']) ? $in['image_id'] : 0);
        $pid = (int) (isset($in['product_id']) ? $in['product_id'] : 0);
        if ($iid < 1 || $pid < 1) {
            $this->output->set_status_header(400);
            $this->output->set_output(json_encode(array('success' => false)));
            return;
        }
        $this->db->where('product_id', $pid)->update('images', array('is_main' => 0));
        $this->db->where('id', $iid)->where('product_id', $pid)->update('images', array('is_main' => 1));
        $this->output->set_output(json_encode(array('success' => true)));
    }

    /** GET /api/v1/sell/edit */
    public function api_sell_edit_get() {
        $this->output->set_content_type('application/json');
        $this->_set_cors_headers();
        $uid = (int) $this->input->get('user_id');
        $pid = (int) $this->input->get('product_id');
        if ($uid < 1 || $pid < 1) {
            $this->output->set_status_header(400);
            $this->output->set_output(json_encode(array('success' => false)));
            return;
        }
        $p = $this->db->get_where('products', array('id' => $pid, 'user_id' => $uid))->row();
        if (empty($p)) {
            $this->output->set_status_header(404);
            $this->output->set_output(json_encode(array('success' => false)));
            return;
        }
        $this->db->where('product_id', $pid)->limit(1);
        $pd = $this->db->get('product_details')->row();
        $this->db->where('product_id', $pid);
        $imgs = $this->db->get('images')->result();
        $this->output->set_output(json_encode(array(
            'product' => $p,
            'details' => $pd,
            'images' => $imgs,
        )));
    }

    /** POST /v1/product/favorite */
    public function product_favorite() {
        $this->output->set_content_type('application/json');
        $this->_set_cors_headers();
        $in = $this->_flutter_json_input();
        $uid = (int) (isset($in['user_id']) ? $in['user_id'] : 0);
        $pid = (int) (isset($in['product_id']) ? $in['product_id'] : 0);
        if ($uid < 1 || $pid < 1) {
            $this->output->set_status_header(400);
            $this->output->set_output(json_encode(array('success' => false)));
            return;
        }
        $this->db->where('user_id', $uid)->where('product_id', $pid);
        if ($this->db->count_all_results('wishlist') > 0) {
            $this->db->where('user_id', $uid)->where('product_id', $pid)->delete('wishlist');
        } else {
            $this->db->insert('wishlist', array('user_id' => $uid, 'product_id' => $pid));
        }
        $this->output->set_output(json_encode(array('success' => true)));
    }

    /** POST /v1/product/sold */
    public function product_sold() {
        $this->output->set_content_type('application/json');
        $this->_set_cors_headers();
        $in = $this->_flutter_json_input();
        $uid = (int) (isset($in['user_id']) ? $in['user_id'] : 0);
        $pid = (int) (isset($in['product_id']) ? $in['product_id'] : 0);
        if ($uid < 1 || $pid < 1) {
            $this->output->set_status_header(400);
            $this->output->set_output(json_encode(array('success' => false)));
            return;
        }
        $this->db->where('id', $pid)->where('user_id', $uid)->update('products', array('is_sold' => 1));
        $this->output->set_output(json_encode(array('success' => true)));
    }

    /** POST /v1/product/review */
    public function product_review() {
        $this->output->set_content_type('application/json');
        $this->_set_cors_headers();
        $in = $this->_flutter_json_input();
        $uid = (int) (isset($in['user_id']) ? $in['user_id'] : 0);
        $pid = (int) (isset($in['product_id']) ? $in['product_id'] : 0);
        $rating = (int) (isset($in['rating']) ? $in['rating'] : 0);
        $text = isset($in['review']) ? $in['review'] : '';
        if ($uid < 1 || $pid < 1 || $rating < 1) {
            $this->output->set_status_header(400);
            $this->output->set_output(json_encode(array('success' => false)));
            return;
        }
        $this->db->insert('reviews', array(
            'product_id' => $pid,
            'user_id' => $uid,
            'rating' => $rating,
            'review' => $text,
            'ip_address' => $this->input->ip_address(),
            'created_at' => date('Y-m-d H:i:s'),
        ));
        $this->output->set_output(json_encode(array('success' => true)));
    }

    /** POST /v1/product/review/delete */
    public function product_review_delete() {
        $this->output->set_content_type('application/json');
        $this->_set_cors_headers();
        $in = $this->_flutter_json_input();
        $uid = (int) (isset($in['user_id']) ? $in['user_id'] : 0);
        $pid = (int) (isset($in['product_id']) ? $in['product_id'] : 0);
        $rid = (int) (isset($in['review_id']) ? $in['review_id'] : 0);
        if ($uid < 1 || $rid < 1) {
            $this->output->set_status_header(400);
            $this->output->set_output(json_encode(array('success' => false)));
            return;
        }
        $this->db->where('id', $rid)->where('user_id', $uid);
        if ($pid > 0) {
            $this->db->where('product_id', $pid);
        }
        $this->db->delete('reviews');
        $this->output->set_output(json_encode(array('success' => true)));
    }

    /** POST /api/v1/product/comment */
    public function api_product_comment() {
        $this->output->set_content_type('application/json');
        $this->_set_cors_headers();
        $in = $this->_flutter_json_input();
        $uid = (int) (isset($in['user_id']) ? $in['user_id'] : 0);
        $pid = (int) (isset($in['product_id']) ? $in['product_id'] : 0);
        $comment = isset($in['comment']) ? $in['comment'] : '';
        if ($pid < 1 || $comment === '') {
            $this->output->set_status_header(400);
            $this->output->set_output(json_encode(array('success' => false)));
            return;
        }
        $u = $this->_flutter_user_by_id($uid);
        $this->db->insert('comments', array(
            'product_id' => $pid,
            'user_id' => $uid > 0 ? $uid : null,
            'name' => $u ? $u->username : 'Guest',
            'comment' => $comment,
            'ip_address' => $this->input->ip_address(),
            'status' => 1,
            'created_at' => date('Y-m-d H:i:s'),
        ));
        $this->output->set_output(json_encode(array('success' => true, 'id' => (int) $this->db->insert_id())));
    }

    /** POST /api/v1/product/comment/delete */
    public function api_product_comment_delete() {
        $this->output->set_content_type('application/json');
        $this->_set_cors_headers();
        $in = $this->_flutter_json_input();
        $cid = (int) (isset($in['comment_id']) ? $in['comment_id'] : 0);
        $uid = (int) (isset($in['user_id']) ? $in['user_id'] : 0);
        if ($cid < 1 || $uid < 1) {
            $this->output->set_status_header(400);
            $this->output->set_output(json_encode(array('success' => false)));
            return;
        }
        $this->db->where('id', $cid)->where('user_id', $uid)->delete('comments');
        $this->output->set_output(json_encode(array('success' => true)));
    }

    /** POST /v1/auth/forgetpass */
    public function auth_forgetpass() {
        $this->output->set_content_type('application/json');
        $this->_set_cors_headers();
        $in = $this->_flutter_json_input();
        $email = isset($in['email']) ? trim($in['email']) : '';
        if ($email === '') {
            $this->output->set_status_header(400);
            $this->output->set_output(json_encode(array('success' => false)));
            return;
        }
        $this->load->model('auth_model');
        $user = $this->auth_model->get_user_by_email($email);
        if (!empty($user)) {
            $this->load->model('email_model');
            $this->email_model->send_email_reset_password($user->id);
        }
        $this->output->set_output(json_encode(array('success' => true)));
    }

    /** POST /v1/auth/connect/google */
    public function auth_connect_google() {
        $this->output->set_content_type('application/json');
        $this->_set_cors_headers();
        $this->output->set_output(json_encode(array(
            'success' => false,
            'error' => 'Google sign-in requires the web OAuth flow. Use email/password or complete setup in Admin → Social Login.',
        )));
    }

    /** GET /v1/auth/connect/facebook */
    public function auth_connect_facebook() {
        $this->output->set_content_type('application/json');
        $this->_set_cors_headers();
        $this->output->set_output(json_encode(array(
            'success' => false,
            'error' => 'Facebook sign-in requires the web OAuth flow. Use email/password or complete setup in Admin → Social Login.',
        )));
    }

    /** GET /v1/promote/plan */
    public function promote_plan_get() {
        $this->output->set_content_type('application/json');
        $this->_set_cors_headers();
        $plans = array(
            array('plan_key' => 'day_7', 'day_count' => 7, 'price' => 9.99, 'currency' => 'USD'),
            array('plan_key' => 'day_14', 'day_count' => 14, 'price' => 17.99, 'currency' => 'USD'),
            array('plan_key' => 'day_30', 'day_count' => 30, 'price' => 29.99, 'currency' => 'USD'),
        );
        $this->output->set_output(json_encode(array('success' => true, 'data' => $plans)));
    }

    /** POST /api/v1/promote/plan */
    public function api_promote_plan_post() {
        $this->output->set_content_type('application/json');
        $this->_set_cors_headers();
        $this->output->set_output(json_encode(array(
            'success' => true,
            'message' => 'Promotion checkout is not wired for mobile API yet; use the website or admin.',
        )));
    }

    /**
     * GET /v1/wishlist/products?user_id=
     * Product rows in the same shape as /v1/product/list for wishlisted items.
     */
    public function wishlist_products() {
        $this->output->set_content_type('application/json');
        $this->_set_cors_headers();
        $uid = (int) $this->input->get('user_id');
        if ($uid < 1) {
            $this->output->set_output(json_encode(array('success' => true, 'data' => array())));
            return;
        }
        $this->db->select('p.id');
        $this->db->from('wishlist w');
        $this->db->join('products p', 'p.id = w.product_id');
        $this->db->where('w.user_id', $uid)->where('p.is_deleted', 0);
        $ids = array();
        foreach ($this->db->get()->result() as $r) {
            $ids[] = (int) $r->id;
        }
        if (empty($ids)) {
            $this->output->set_output(json_encode(array('success' => true, 'data' => array())));
            return;
        }
        $lang_id = max(1, (int) ($this->input->get('lang_id') ?: 1));
        $base_url = $this->config->item('base_url');
        $this->db->select('p.id, p.category_id, p.slug, p.price, p.currency, p.discount_rate, p.user_id, p.rating, p.is_promoted, p.is_sold, p.created_at, p.product_type');
        $this->db->select('(SELECT title FROM product_details WHERE product_id = p.id AND lang_id = ' . $lang_id . ' LIMIT 1) AS title');
        $this->db->select('(SELECT image_small FROM images WHERE product_id = p.id AND is_main = 1 LIMIT 1) AS image');
        $this->db->from('products p');
        $this->db->where_in('p.id', $ids);
        $this->db->where('p.is_deleted', 0);
        $this->db->where('p.product_type', 'physical');
        $query = $this->db->get();
        $list = array();
        foreach ($query->result() as $row) {
            $img = isset($row->image) ? $row->image : null;
            $image_url = null;
            if (!empty($img)) {
                $img = trim((string) $img);
                $image_url = preg_match('#^https?://#i', $img) ? $img : $base_url . $img;
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
        $this->output->set_output(json_encode(array('success' => true, 'data' => $list)));
    }

    /** GET /v1/buyer/orders?user_id=&limit= */
    public function buyer_orders() {
        $this->output->set_content_type('application/json');
        $this->_set_cors_headers();
        $uid = (int) $this->input->get('user_id');
        $limit = min(50, max(1, (int) ($this->input->get('limit') ?: 30)));
        if ($uid < 1) {
            $this->output->set_output(json_encode(array('success' => true, 'data' => array())));
            return;
        }
        $this->db->where('buyer_id', $uid)->order_by('created_at', 'DESC')->limit($limit);
        $orders = $this->db->get('orders')->result();
        $out = array();
        foreach ($orders as $o) {
            $ops = $this->db->get_where('order_products', array('order_id' => (int) $o->id))->result();
            $items = array();
            foreach ($ops as $op) {
                $items[] = array(
                    'product_id' => (int) $op->product_id,
                    'title' => $op->product_title,
                    'slug' => $op->product_slug,
                    'quantity' => (int) $op->product_quantity,
                    'line_total_cents' => (int) $op->product_total_price,
                    'currency' => $op->product_currency,
                    'line_status' => $op->order_status,
                );
            }
            $out[] = array(
                'order_number' => (string) $o->order_number,
                'created_at' => $o->created_at,
                'price_total' => $o->price_total,
                'price_currency' => $o->price_currency,
                'status' => (int) $o->status,
                'payment_status' => $o->payment_status,
                'items' => $items,
            );
        }
        $this->output->set_output(json_encode(array('success' => true, 'data' => $out)));
    }

    /** GET /v1/buyer/order?user_id=&order_number= */
    public function buyer_order() {
        $this->output->set_content_type('application/json');
        $this->_set_cors_headers();
        $uid = (int) $this->input->get('user_id');
        $on = trim((string) $this->input->get('order_number', true));
        if ($uid < 1 || $on === '') {
            $this->output->set_status_header(400);
            $this->output->set_output(json_encode(array('success' => false, 'error' => 'user_id and order_number required')));
            return;
        }
        $this->db->where('order_number', $on)->where('buyer_id', $uid)->limit(1);
        $order = $this->db->get('orders')->row();
        if (empty($order)) {
            $this->output->set_status_header(404);
            $this->output->set_output(json_encode(array('success' => false, 'error' => 'Order not found')));
            return;
        }
        $ops = $this->db->get_where('order_products', array('order_id' => (int) $order->id))->result();
        $items = array();
        foreach ($ops as $op) {
            $items[] = array(
                'product_id' => (int) $op->product_id,
                'title' => $op->product_title,
                'slug' => $op->product_slug,
                'quantity' => (int) $op->product_quantity,
                'unit_price_cents' => (int) $op->product_unit_price,
                'line_total_cents' => (int) $op->product_total_price,
                'currency' => $op->product_currency,
                'line_status' => $op->order_status,
            );
        }
        $ship = $this->db->get_where('order_shipping', array('order_id' => (int) $order->id), 1, 0)->row();
        $shipping = $ship ? array(
            'shipping_first_name' => $ship->shipping_first_name,
            'shipping_last_name' => $ship->shipping_last_name,
            'shipping_email' => $ship->shipping_email,
            'shipping_phone_number' => $ship->shipping_phone_number,
            'shipping_address' => $ship->shipping_address,
            'shipping_country' => $ship->shipping_country,
            'shipping_state' => $ship->shipping_state,
            'shipping_city' => $ship->shipping_city,
            'shipping_zip_code' => $ship->shipping_zip_code,
        ) : null;
        $this->output->set_output(json_encode(array(
            'success' => true,
            'order' => array(
                'order_number' => (string) $order->order_number,
                'created_at' => $order->created_at,
                'price_subtotal' => $order->price_subtotal,
                'price_vat' => (int) $order->price_vat,
                'price_shipping' => $order->price_shipping,
                'price_total' => $order->price_total,
                'price_currency' => $order->price_currency,
                'status' => (int) $order->status,
                'payment_status' => $order->payment_status,
                'payment_method' => $order->payment_method,
            ),
            'items' => $items,
            'shipping' => $shipping,
        )));
    }

    /** GET /v1/wallet/summary?user_id= — seller balance + recent earnings/payouts */
    public function wallet_summary() {
        $this->output->set_content_type('application/json');
        $this->_set_cors_headers();
        $uid = (int) $this->input->get('user_id');
        $u = $this->_flutter_user_by_id($uid);
        if (empty($u)) {
            $this->output->set_status_header(404);
            $this->output->set_output(json_encode(array('success' => false, 'error' => 'User not found')));
            return;
        }
        $balance = isset($u->balance) ? (int) $u->balance : 0;
        $history = array();
        $this->db->from('earnings');
        $this->db->where('user_id', $uid);
        $this->db->order_by('created_at', 'DESC');
        $this->db->limit(25);
        foreach ($this->db->get()->result() as $e) {
            $history[] = array(
                'type' => 'earning',
                'label' => 'Sale — order #' . (string) $e->order_number,
                'amount_cents' => (int) $e->earned_amount,
                'currency' => $e->currency,
                'created_at' => $e->created_at,
            );
        }
        $this->db->reset_query();
        $this->db->from('payouts');
        $this->db->where('user_id', $uid);
        $this->db->order_by('created_at', 'DESC');
        $this->db->limit(25);
        foreach ($this->db->get()->result() as $p) {
            $history[] = array(
                'type' => 'payout',
                'label' => 'Payout (' . ($p->payout_method ?: 'request') . ')',
                'amount_cents' => -1 * abs((int) $p->amount),
                'currency' => $p->currency,
                'created_at' => $p->created_at,
            );
        }
        $this->db->reset_query();
        usort($history, function ($a, $b) {
            return strcmp((string) $b['created_at'], (string) $a['created_at']);
        });
        $history = array_slice($history, 0, 40);
        $defCur = 'TZS';
        foreach ($history as $h) {
            if (!empty($h['currency'])) {
                $defCur = (string) $h['currency'];
                break;
            }
        }
        $this->output->set_output(json_encode(array(
            'success' => true,
            'balance_cents' => $balance,
            'currency' => $defCur,
            'history' => $history,
        )));
    }

    /** GET /v1/notifications?user_id= — lightweight feed from recent orders */
    public function notifications_feed() {
        $this->output->set_content_type('application/json');
        $this->_set_cors_headers();
        $uid = (int) $this->input->get('user_id');
        $items = array();
        if ($uid > 0) {
            $this->db->where('buyer_id', $uid)->order_by('created_at', 'DESC')->limit(12);
            foreach ($this->db->get('orders')->result() as $o) {
                $items[] = array(
                    'title' => 'Order #' . (string) $o->order_number,
                    'body' => 'Payment: ' . (string) $o->payment_status . ' · Total ' . (string) $o->price_total . ' ' . (string) $o->price_currency,
                    'time' => $o->created_at,
                    'kind' => 'order',
                );
            }
        }
        $this->output->set_output(json_encode(array('success' => true, 'data' => $items)));
    }

    /** GET /v1/product/reviews?product_id= */
    public function product_reviews_list() {
        $this->output->set_content_type('application/json');
        $this->_set_cors_headers();
        $pid = (int) $this->input->get('product_id');
        if ($pid < 1) {
            $this->output->set_status_header(400);
            $this->output->set_output(json_encode(array('success' => false, 'error' => 'product_id required')));
            return;
        }
        $this->db->select('r.id, r.rating, r.review, r.created_at, u.username');
        $this->db->from('reviews r');
        $this->db->join('users u', 'u.id = r.user_id', 'left');
        $this->db->where('r.product_id', $pid)->order_by('r.id', 'DESC')->limit(50);
        $rows = $this->db->get()->result();
        $list = array();
        foreach ($rows as $r) {
            $list[] = array(
                'id' => (int) $r->id,
                'rating' => (int) $r->rating,
                'review' => $r->review,
                'created_at' => $r->created_at,
                'username' => $r->username,
            );
        }
        $this->output->set_output(json_encode(array('success' => true, 'data' => $list)));
    }
}
