-- Optional: run manually if automatic CREATE TABLE is disabled on your host.
CREATE TABLE IF NOT EXISTS `app_selcom_checkouts` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
