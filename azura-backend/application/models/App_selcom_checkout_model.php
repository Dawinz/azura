<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Persists mobile/app Selcom checkout sessions (stateless clients — no PHP session on init).
 */
class App_selcom_checkout_model extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->ensure_schema();
    }

    public function ensure_schema()
    {
        if ($this->db->table_exists('app_selcom_checkouts')) {
            if (!$this->db->field_exists('shipping_json', 'app_selcom_checkouts')) {
                $this->db->query('ALTER TABLE `app_selcom_checkouts` ADD `shipping_json` LONGTEXT NULL');
            }
            return;
        }
        $sql = "CREATE TABLE IF NOT EXISTS `app_selcom_checkouts` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `order_token` varchar(191) NOT NULL,
            `user_id` int(11) DEFAULT NULL,
            `buyer_email` varchar(255) NOT NULL,
            `buyer_name` varchar(255) NOT NULL,
            `buyer_phone` varchar(64) NOT NULL,
            `cart_final_json` longtext NOT NULL,
            `cart_total_json` longtext NOT NULL,
            `currency` varchar(16) NOT NULL,
            `total_amount` decimal(24,4) NOT NULL DEFAULT 0.0000,
            `status` varchar(32) NOT NULL DEFAULT 'pending',
            `internal_order_id` int(11) DEFAULT NULL,
            `created_at` datetime NOT NULL,
            `updated_at` datetime NOT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `order_token` (`order_token`),
            KEY `status_created` (`status`,`created_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
        $this->db->query($sql);
    }

    public function insert_pending($data)
    {
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = $data['created_at'];
        return $this->db->insert('app_selcom_checkouts', $data);
    }

    public function get_by_token($order_token)
    {
        $order_token = trim((string) $order_token);
        if ($order_token === '') {
            return null;
        }
        return $this->db->get_where('app_selcom_checkouts', array('order_token' => $order_token))->row();
    }

    public function mark_completed($order_token, $internal_order_id)
    {
        $this->db->where('order_token', $order_token);
        return $this->db->update('app_selcom_checkouts', array(
            'status' => 'completed',
            'internal_order_id' => (int) $internal_order_id,
            'updated_at' => date('Y-m-d H:i:s'),
        ));
    }

    public function mark_failed($order_token)
    {
        $this->db->where('order_token', $order_token);
        return $this->db->update('app_selcom_checkouts', array(
            'status' => 'failed',
            'updated_at' => date('Y-m-d H:i:s'),
        ));
    }
}
