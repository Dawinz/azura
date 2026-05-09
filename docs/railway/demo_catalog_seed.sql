-- Azura Mall — demo catalog seed for Railway / MySQL
-- Backup DB first. Inserts 31 physical demo SKUs (AZ-DEMO-###) with HTTPS gallery URLs,
-- then adds placeholder images for any other products still missing an images row.
--
-- Git deploy does NOT run this file. Either execute this SQL in MySQL, or after deploying
-- the backend set Railway env DEMO_SEED_KEY and POST JSON {"key":"..."} to:
--   {your_storefront}/index.php/v1/demo/seed-catalog
-- (endpoint is disabled when DEMO_SEED_KEY is unset). Then pull-to-refresh the app.

SET @demo_seller := COALESCE(
  (SELECT id FROM users WHERE banned = 0 AND role = 'vendor' ORDER BY id LIMIT 1),
  (SELECT id FROM users WHERE banned = 0 ORDER BY id LIMIT 1),
  1
);
SET @demo_cat := COALESCE((SELECT MIN(id) FROM categories WHERE visibility = 1), 1);
SET @demo_cur := COALESCE((SELECT default_currency FROM payment_settings WHERE id = 1 LIMIT 1), 'TZS');

-- Remove prior demo batch (re-runnable)
DELETE FROM images WHERE product_id IN (SELECT id FROM (SELECT id FROM products WHERE sku LIKE 'AZ-DEMO-%') t);
DELETE FROM product_details WHERE product_id IN (SELECT id FROM (SELECT id FROM products WHERE sku LIKE 'AZ-DEMO-%') t);
DELETE FROM products WHERE sku LIKE 'AZ-DEMO-%';

INSERT INTO products (slug, product_type, listing_type, sku, category_id, price, currency, discount_rate, vat_rate, user_id, status, is_promoted, promote_start_date, promote_end_date, promote_plan, promote_day, is_special_offer, visibility, rating, stock, shipping_delivery_time_id, multiple_sale, is_deleted, is_draft, created_at) VALUES ('demo-azura-001-linen-blend-shirt', 'physical', 'sell_on_site', 'AZ-DEMO-001', @demo_cat, 1490000, @demo_cur, 0, 0, @demo_seller, 1, 0, NOW(), NOW(), 'none', 0, 0, 1, '0', 40, 0, 1, 0, 0, NOW());
SET @pid := LAST_INSERT_ID();
INSERT INTO product_details (product_id, lang_id, title, description, seo_title, seo_description, seo_keywords) VALUES (@pid, 1, 'Linen blend shirt — demo', 'Breathable linen-blend shirt for everyday wear. Demo listing for Azura Mall.', 'Linen blend shirt — demo', 'Breathable linen-blend shirt for everyday wear. Demo listing for Azura Mall.', 'demo, azura, marketplace');
INSERT INTO images (product_id, image_default, image_big, image_small, is_main, storage) VALUES (@pid, 'https://picsum.photos/id/11/800/800', 'https://picsum.photos/id/11/1200/1200', 'https://picsum.photos/id/11/600/600', 1, 'local');

INSERT INTO products (slug, product_type, listing_type, sku, category_id, price, currency, discount_rate, vat_rate, user_id, status, is_promoted, promote_start_date, promote_end_date, promote_plan, promote_day, is_special_offer, visibility, rating, stock, shipping_delivery_time_id, multiple_sale, is_deleted, is_draft, created_at) VALUES ('demo-azura-002-canvas-sneakers', 'physical', 'sell_on_site', 'AZ-DEMO-002', @demo_cat, 8900000, @demo_cur, 0, 0, @demo_seller, 1, 0, NOW(), NOW(), 'none', 0, 0, 1, '0', 40, 0, 1, 0, 0, NOW());
SET @pid := LAST_INSERT_ID();
INSERT INTO product_details (product_id, lang_id, title, description, seo_title, seo_description, seo_keywords) VALUES (@pid, 1, 'Canvas sneakers — demo', 'Comfortable canvas sneakers with rubber sole. Demo SKU for catalog testing.', 'Canvas sneakers — demo', 'Comfortable canvas sneakers with rubber sole. Demo SKU for catalog testing.', 'demo, azura, marketplace');
INSERT INTO images (product_id, image_default, image_big, image_small, is_main, storage) VALUES (@pid, 'https://picsum.photos/id/13/800/800', 'https://picsum.photos/id/13/1200/1200', 'https://picsum.photos/id/13/600/600', 1, 'local');

INSERT INTO products (slug, product_type, listing_type, sku, category_id, price, currency, discount_rate, vat_rate, user_id, status, is_promoted, promote_start_date, promote_end_date, promote_plan, promote_day, is_special_offer, visibility, rating, stock, shipping_delivery_time_id, multiple_sale, is_deleted, is_draft, created_at) VALUES ('demo-azura-003-ceramic-dinner-plate-set', 'physical', 'sell_on_site', 'AZ-DEMO-003', @demo_cat, 3200000, @demo_cur, 0, 0, @demo_seller, 1, 0, NOW(), NOW(), 'none', 0, 0, 1, '0', 40, 0, 1, 0, 0, NOW());
SET @pid := LAST_INSERT_ID();
INSERT INTO product_details (product_id, lang_id, title, description, seo_title, seo_description, seo_keywords) VALUES (@pid, 1, 'Ceramic dinner plate set — demo', 'Set of four ceramic plates for dining. Demo product imagery via CDN.', 'Ceramic dinner plate set — demo', 'Set of four ceramic plates for dining. Demo product imagery via CDN.', 'demo, azura, marketplace');
INSERT INTO images (product_id, image_default, image_big, image_small, is_main, storage) VALUES (@pid, 'https://picsum.photos/id/21/800/800', 'https://picsum.photos/id/21/1200/1200', 'https://picsum.photos/id/21/600/600', 1, 'local');

INSERT INTO products (slug, product_type, listing_type, sku, category_id, price, currency, discount_rate, vat_rate, user_id, status, is_promoted, promote_start_date, promote_end_date, promote_plan, promote_day, is_special_offer, visibility, rating, stock, shipping_delivery_time_id, multiple_sale, is_deleted, is_draft, created_at) VALUES ('demo-azura-004-stainless-steel-water-bottle', 'physical', 'sell_on_site', 'AZ-DEMO-004', @demo_cat, 4500000, @demo_cur, 0, 0, @demo_seller, 1, 0, NOW(), NOW(), 'none', 0, 0, 1, '0', 40, 0, 1, 0, 0, NOW());
SET @pid := LAST_INSERT_ID();
INSERT INTO product_details (product_id, lang_id, title, description, seo_title, seo_description, seo_keywords) VALUES (@pid, 1, 'Stainless steel water bottle — demo', 'Insulated bottle keeps drinks cold or warm. Demo listing.', 'Stainless steel water bottle — demo', 'Insulated bottle keeps drinks cold or warm. Demo listing.', 'demo, azura, marketplace');
INSERT INTO images (product_id, image_default, image_big, image_small, is_main, storage) VALUES (@pid, 'https://picsum.photos/id/29/800/800', 'https://picsum.photos/id/29/1200/1200', 'https://picsum.photos/id/29/600/600', 1, 'local');

INSERT INTO products (slug, product_type, listing_type, sku, category_id, price, currency, discount_rate, vat_rate, user_id, status, is_promoted, promote_start_date, promote_end_date, promote_plan, promote_day, is_special_offer, visibility, rating, stock, shipping_delivery_time_id, multiple_sale, is_deleted, is_draft, created_at) VALUES ('demo-azura-005-wireless-earbuds-case', 'physical', 'sell_on_site', 'AZ-DEMO-005', @demo_cat, 2200000, @demo_cur, 0, 0, @demo_seller, 1, 0, NOW(), NOW(), 'none', 0, 0, 1, '0', 40, 0, 1, 0, 0, NOW());
SET @pid := LAST_INSERT_ID();
INSERT INTO product_details (product_id, lang_id, title, description, seo_title, seo_description, seo_keywords) VALUES (@pid, 1, 'Wireless earbuds case — demo', 'Protective silicone case for earbuds. Demo marketplace item.', 'Wireless earbuds case — demo', 'Protective silicone case for earbuds. Demo marketplace item.', 'demo, azura, marketplace');
INSERT INTO images (product_id, image_default, image_big, image_small, is_main, storage) VALUES (@pid, 'https://picsum.photos/id/31/800/800', 'https://picsum.photos/id/31/1200/1200', 'https://picsum.photos/id/31/600/600', 1, 'local');

INSERT INTO products (slug, product_type, listing_type, sku, category_id, price, currency, discount_rate, vat_rate, user_id, status, is_promoted, promote_start_date, promote_end_date, promote_plan, promote_day, is_special_offer, visibility, rating, stock, shipping_delivery_time_id, multiple_sale, is_deleted, is_draft, created_at) VALUES ('demo-azura-006-woven-storage-basket', 'physical', 'sell_on_site', 'AZ-DEMO-006', @demo_cat, 1890000, @demo_cur, 0, 0, @demo_seller, 1, 0, NOW(), NOW(), 'none', 0, 0, 1, '0', 40, 0, 1, 0, 0, NOW());
SET @pid := LAST_INSERT_ID();
INSERT INTO product_details (product_id, lang_id, title, description, seo_title, seo_description, seo_keywords) VALUES (@pid, 1, 'Woven storage basket — demo', 'Handwoven basket for home organisation. Demo SKU.', 'Woven storage basket — demo', 'Handwoven basket for home organisation. Demo SKU.', 'demo, azura, marketplace');
INSERT INTO images (product_id, image_default, image_big, image_small, is_main, storage) VALUES (@pid, 'https://picsum.photos/id/36/800/800', 'https://picsum.photos/id/36/1200/1200', 'https://picsum.photos/id/36/600/600', 1, 'local');

INSERT INTO products (slug, product_type, listing_type, sku, category_id, price, currency, discount_rate, vat_rate, user_id, status, is_promoted, promote_start_date, promote_end_date, promote_plan, promote_day, is_special_offer, visibility, rating, stock, shipping_delivery_time_id, multiple_sale, is_deleted, is_draft, created_at) VALUES ('demo-azura-007-cotton-throw-blanket', 'physical', 'sell_on_site', 'AZ-DEMO-007', @demo_cat, 2790000, @demo_cur, 0, 0, @demo_seller, 1, 0, NOW(), NOW(), 'none', 0, 0, 1, '0', 40, 0, 1, 0, 0, NOW());
SET @pid := LAST_INSERT_ID();
INSERT INTO product_details (product_id, lang_id, title, description, seo_title, seo_description, seo_keywords) VALUES (@pid, 1, 'Cotton throw blanket — demo', 'Soft cotton throw for sofas and beds. Demo listing.', 'Cotton throw blanket — demo', 'Soft cotton throw for sofas and beds. Demo listing.', 'demo, azura, marketplace');
INSERT INTO images (product_id, image_default, image_big, image_small, is_main, storage) VALUES (@pid, 'https://picsum.photos/id/42/800/800', 'https://picsum.photos/id/42/1200/1200', 'https://picsum.photos/id/42/600/600', 1, 'local');

INSERT INTO products (slug, product_type, listing_type, sku, category_id, price, currency, discount_rate, vat_rate, user_id, status, is_promoted, promote_start_date, promote_end_date, promote_plan, promote_day, is_special_offer, visibility, rating, stock, shipping_delivery_time_id, multiple_sale, is_deleted, is_draft, created_at) VALUES ('demo-azura-008-desk-led-lamp', 'physical', 'sell_on_site', 'AZ-DEMO-008', @demo_cat, 3500000, @demo_cur, 0, 0, @demo_seller, 1, 0, NOW(), NOW(), 'none', 0, 0, 1, '0', 40, 0, 1, 0, 0, NOW());
SET @pid := LAST_INSERT_ID();
INSERT INTO product_details (product_id, lang_id, title, description, seo_title, seo_description, seo_keywords) VALUES (@pid, 1, 'Desk LED lamp — demo', 'Adjustable LED desk lamp with warm light. Demo product.', 'Desk LED lamp — demo', 'Adjustable LED desk lamp with warm light. Demo product.', 'demo, azura, marketplace');
INSERT INTO images (product_id, image_default, image_big, image_small, is_main, storage) VALUES (@pid, 'https://picsum.photos/id/48/800/800', 'https://picsum.photos/id/48/1200/1200', 'https://picsum.photos/id/48/600/600', 1, 'local');

INSERT INTO products (slug, product_type, listing_type, sku, category_id, price, currency, discount_rate, vat_rate, user_id, status, is_promoted, promote_start_date, promote_end_date, promote_plan, promote_day, is_special_offer, visibility, rating, stock, shipping_delivery_time_id, multiple_sale, is_deleted, is_draft, created_at) VALUES ('demo-azura-009-leather-card-holder', 'physical', 'sell_on_site', 'AZ-DEMO-009', @demo_cat, 950000, @demo_cur, 0, 0, @demo_seller, 1, 0, NOW(), NOW(), 'none', 0, 0, 1, '0', 40, 0, 1, 0, 0, NOW());
SET @pid := LAST_INSERT_ID();
INSERT INTO product_details (product_id, lang_id, title, description, seo_title, seo_description, seo_keywords) VALUES (@pid, 1, 'Leather card holder — demo', 'Slim leather holder for cards and cash. Demo SKU.', 'Leather card holder — demo', 'Slim leather holder for cards and cash. Demo SKU.', 'demo, azura, marketplace');
INSERT INTO images (product_id, image_default, image_big, image_small, is_main, storage) VALUES (@pid, 'https://picsum.photos/id/52/800/800', 'https://picsum.photos/id/52/1200/1200', 'https://picsum.photos/id/52/600/600', 1, 'local');

INSERT INTO products (slug, product_type, listing_type, sku, category_id, price, currency, discount_rate, vat_rate, user_id, status, is_promoted, promote_start_date, promote_end_date, promote_plan, promote_day, is_special_offer, visibility, rating, stock, shipping_delivery_time_id, multiple_sale, is_deleted, is_draft, created_at) VALUES ('demo-azura-010-kids-cotton-tee', 'physical', 'sell_on_site', 'AZ-DEMO-010', @demo_cat, 1290000, @demo_cur, 0, 0, @demo_seller, 1, 0, NOW(), NOW(), 'none', 0, 0, 1, '0', 40, 0, 1, 0, 0, NOW());
SET @pid := LAST_INSERT_ID();
INSERT INTO product_details (product_id, lang_id, title, description, seo_title, seo_description, seo_keywords) VALUES (@pid, 1, 'Kids cotton tee — demo', 'Breathable cotton t-shirt for kids. Demo listing.', 'Kids cotton tee — demo', 'Breathable cotton t-shirt for kids. Demo listing.', 'demo, azura, marketplace');
INSERT INTO images (product_id, image_default, image_big, image_small, is_main, storage) VALUES (@pid, 'https://picsum.photos/id/57/800/800', 'https://picsum.photos/id/57/1200/1200', 'https://picsum.photos/id/57/600/600', 1, 'local');

INSERT INTO products (slug, product_type, listing_type, sku, category_id, price, currency, discount_rate, vat_rate, user_id, status, is_promoted, promote_start_date, promote_end_date, promote_plan, promote_day, is_special_offer, visibility, rating, stock, shipping_delivery_time_id, multiple_sale, is_deleted, is_draft, created_at) VALUES ('demo-azura-011-glass-meal-prep-containers', 'physical', 'sell_on_site', 'AZ-DEMO-011', @demo_cat, 4100000, @demo_cur, 0, 0, @demo_seller, 1, 0, NOW(), NOW(), 'none', 0, 0, 1, '0', 40, 0, 1, 0, 0, NOW());
SET @pid := LAST_INSERT_ID();
INSERT INTO product_details (product_id, lang_id, title, description, seo_title, seo_description, seo_keywords) VALUES (@pid, 1, 'Glass meal prep containers — demo', 'Stackable glass containers with lids. Demo item.', 'Glass meal prep containers — demo', 'Stackable glass containers with lids. Demo item.', 'demo, azura, marketplace');
INSERT INTO images (product_id, image_default, image_big, image_small, is_main, storage) VALUES (@pid, 'https://picsum.photos/id/63/800/800', 'https://picsum.photos/id/63/1200/1200', 'https://picsum.photos/id/63/600/600', 1, 'local');

INSERT INTO products (slug, product_type, listing_type, sku, category_id, price, currency, discount_rate, vat_rate, user_id, status, is_promoted, promote_start_date, promote_end_date, promote_plan, promote_day, is_special_offer, visibility, rating, stock, shipping_delivery_time_id, multiple_sale, is_deleted, is_draft, created_at) VALUES ('demo-azura-012-yoga-mat-strap', 'physical', 'sell_on_site', 'AZ-DEMO-012', @demo_cat, 550000, @demo_cur, 0, 0, @demo_seller, 1, 0, NOW(), NOW(), 'none', 0, 0, 1, '0', 40, 0, 1, 0, 0, NOW());
SET @pid := LAST_INSERT_ID();
INSERT INTO product_details (product_id, lang_id, title, description, seo_title, seo_description, seo_keywords) VALUES (@pid, 1, 'Yoga mat strap — demo', 'Adjustable strap for carrying yoga mats. Demo SKU.', 'Yoga mat strap — demo', 'Adjustable strap for carrying yoga mats. Demo SKU.', 'demo, azura, marketplace');
INSERT INTO images (product_id, image_default, image_big, image_small, is_main, storage) VALUES (@pid, 'https://picsum.photos/id/67/800/800', 'https://picsum.photos/id/67/1200/1200', 'https://picsum.photos/id/67/600/600', 1, 'local');

INSERT INTO products (slug, product_type, listing_type, sku, category_id, price, currency, discount_rate, vat_rate, user_id, status, is_promoted, promote_start_date, promote_end_date, promote_plan, promote_day, is_special_offer, visibility, rating, stock, shipping_delivery_time_id, multiple_sale, is_deleted, is_draft, created_at) VALUES ('demo-azura-013-bamboo-cutting-board', 'physical', 'sell_on_site', 'AZ-DEMO-013', @demo_cat, 1750000, @demo_cur, 0, 0, @demo_seller, 1, 0, NOW(), NOW(), 'none', 0, 0, 1, '0', 40, 0, 1, 0, 0, NOW());
SET @pid := LAST_INSERT_ID();
INSERT INTO product_details (product_id, lang_id, title, description, seo_title, seo_description, seo_keywords) VALUES (@pid, 1, 'Bamboo cutting board — demo', 'Durable bamboo board for food prep. Demo listing.', 'Bamboo cutting board — demo', 'Durable bamboo board for food prep. Demo listing.', 'demo, azura, marketplace');
INSERT INTO images (product_id, image_default, image_big, image_small, is_main, storage) VALUES (@pid, 'https://picsum.photos/id/71/800/800', 'https://picsum.photos/id/71/1200/1200', 'https://picsum.photos/id/71/600/600', 1, 'local');

INSERT INTO products (slug, product_type, listing_type, sku, category_id, price, currency, discount_rate, vat_rate, user_id, status, is_promoted, promote_start_date, promote_end_date, promote_plan, promote_day, is_special_offer, visibility, rating, stock, shipping_delivery_time_id, multiple_sale, is_deleted, is_draft, created_at) VALUES ('demo-azura-014-travel-neck-pillow', 'physical', 'sell_on_site', 'AZ-DEMO-014', @demo_cat, 990000, @demo_cur, 0, 0, @demo_seller, 1, 0, NOW(), NOW(), 'none', 0, 0, 1, '0', 40, 0, 1, 0, 0, NOW());
SET @pid := LAST_INSERT_ID();
INSERT INTO product_details (product_id, lang_id, title, description, seo_title, seo_description, seo_keywords) VALUES (@pid, 1, 'Travel neck pillow — demo', 'Memory foam neck pillow for travel. Demo product.', 'Travel neck pillow — demo', 'Memory foam neck pillow for travel. Demo product.', 'demo, azura, marketplace');
INSERT INTO images (product_id, image_default, image_big, image_small, is_main, storage) VALUES (@pid, 'https://picsum.photos/id/74/800/800', 'https://picsum.photos/id/74/1200/1200', 'https://picsum.photos/id/74/600/600', 1, 'local');

INSERT INTO products (slug, product_type, listing_type, sku, category_id, price, currency, discount_rate, vat_rate, user_id, status, is_promoted, promote_start_date, promote_end_date, promote_plan, promote_day, is_special_offer, visibility, rating, stock, shipping_delivery_time_id, multiple_sale, is_deleted, is_draft, created_at) VALUES ('demo-azura-015-usb-c-charging-cable', 'physical', 'sell_on_site', 'AZ-DEMO-015', @demo_cat, 650000, @demo_cur, 0, 0, @demo_seller, 1, 0, NOW(), NOW(), 'none', 0, 0, 1, '0', 40, 0, 1, 0, 0, NOW());
SET @pid := LAST_INSERT_ID();
INSERT INTO product_details (product_id, lang_id, title, description, seo_title, seo_description, seo_keywords) VALUES (@pid, 1, 'USB-C charging cable — demo', 'Braided USB-C cable for phones and tablets. Demo SKU.', 'USB-C charging cable — demo', 'Braided USB-C cable for phones and tablets. Demo SKU.', 'demo, azura, marketplace');
INSERT INTO images (product_id, image_default, image_big, image_small, is_main, storage) VALUES (@pid, 'https://picsum.photos/id/78/800/800', 'https://picsum.photos/id/78/1200/1200', 'https://picsum.photos/id/78/600/600', 1, 'local');

INSERT INTO products (slug, product_type, listing_type, sku, category_id, price, currency, discount_rate, vat_rate, user_id, status, is_promoted, promote_start_date, promote_end_date, promote_plan, promote_day, is_special_offer, visibility, rating, stock, shipping_delivery_time_id, multiple_sale, is_deleted, is_draft, created_at) VALUES ('demo-azura-016-ceramic-mug-pair', 'physical', 'sell_on_site', 'AZ-DEMO-016', @demo_cat, 1150000, @demo_cur, 0, 0, @demo_seller, 1, 0, NOW(), NOW(), 'none', 0, 0, 1, '0', 40, 0, 1, 0, 0, NOW());
SET @pid := LAST_INSERT_ID();
INSERT INTO product_details (product_id, lang_id, title, description, seo_title, seo_description, seo_keywords) VALUES (@pid, 1, 'Ceramic mug pair — demo', 'Two minimalist ceramic mugs. Demo marketplace listing.', 'Ceramic mug pair — demo', 'Two minimalist ceramic mugs. Demo marketplace listing.', 'demo, azura, marketplace');
INSERT INTO images (product_id, image_default, image_big, image_small, is_main, storage) VALUES (@pid, 'https://picsum.photos/id/82/800/800', 'https://picsum.photos/id/82/1200/1200', 'https://picsum.photos/id/82/600/600', 1, 'local');

INSERT INTO products (slug, product_type, listing_type, sku, category_id, price, currency, discount_rate, vat_rate, user_id, status, is_promoted, promote_start_date, promote_end_date, promote_plan, promote_day, is_special_offer, visibility, rating, stock, shipping_delivery_time_id, multiple_sale, is_deleted, is_draft, created_at) VALUES ('demo-azura-017-running-shorts', 'physical', 'sell_on_site', 'AZ-DEMO-017', @demo_cat, 2100000, @demo_cur, 0, 0, @demo_seller, 1, 0, NOW(), NOW(), 'none', 0, 0, 1, '0', 40, 0, 1, 0, 0, NOW());
SET @pid := LAST_INSERT_ID();
INSERT INTO product_details (product_id, lang_id, title, description, seo_title, seo_description, seo_keywords) VALUES (@pid, 1, 'Running shorts — demo', 'Lightweight shorts for training. Demo item.', 'Running shorts — demo', 'Lightweight shorts for training. Demo item.', 'demo, azura, marketplace');
INSERT INTO images (product_id, image_default, image_big, image_small, is_main, storage) VALUES (@pid, 'https://picsum.photos/id/85/800/800', 'https://picsum.photos/id/85/1200/1200', 'https://picsum.photos/id/85/600/600', 1, 'local');

INSERT INTO products (slug, product_type, listing_type, sku, category_id, price, currency, discount_rate, vat_rate, user_id, status, is_promoted, promote_start_date, promote_end_date, promote_plan, promote_day, is_special_offer, visibility, rating, stock, shipping_delivery_time_id, multiple_sale, is_deleted, is_draft, created_at) VALUES ('demo-azura-018-wall-clock-silent', 'physical', 'sell_on_site', 'AZ-DEMO-018', @demo_cat, 3200000, @demo_cur, 0, 0, @demo_seller, 1, 0, NOW(), NOW(), 'none', 0, 0, 1, '0', 40, 0, 1, 0, 0, NOW());
SET @pid := LAST_INSERT_ID();
INSERT INTO product_details (product_id, lang_id, title, description, seo_title, seo_description, seo_keywords) VALUES (@pid, 1, 'Wall clock silent — demo', 'Silent sweeping wall clock. Demo SKU.', 'Wall clock silent — demo', 'Silent sweeping wall clock. Demo SKU.', 'demo, azura, marketplace');
INSERT INTO images (product_id, image_default, image_big, image_small, is_main, storage) VALUES (@pid, 'https://picsum.photos/id/88/800/800', 'https://picsum.photos/id/88/1200/1200', 'https://picsum.photos/id/88/600/600', 1, 'local');

INSERT INTO products (slug, product_type, listing_type, sku, category_id, price, currency, discount_rate, vat_rate, user_id, status, is_promoted, promote_start_date, promote_end_date, promote_plan, promote_day, is_special_offer, visibility, rating, stock, shipping_delivery_time_id, multiple_sale, is_deleted, is_draft, created_at) VALUES ('demo-azura-019-plant-stand-wooden', 'physical', 'sell_on_site', 'AZ-DEMO-019', @demo_cat, 1550000, @demo_cur, 0, 0, @demo_seller, 1, 0, NOW(), NOW(), 'none', 0, 0, 1, '0', 40, 0, 1, 0, 0, NOW());
SET @pid := LAST_INSERT_ID();
INSERT INTO product_details (product_id, lang_id, title, description, seo_title, seo_description, seo_keywords) VALUES (@pid, 1, 'Plant stand wooden — demo', 'Wooden stand for indoor plants. Demo listing.', 'Plant stand wooden — demo', 'Wooden stand for indoor plants. Demo listing.', 'demo, azura, marketplace');
INSERT INTO images (product_id, image_default, image_big, image_small, is_main, storage) VALUES (@pid, 'https://picsum.photos/id/91/800/800', 'https://picsum.photos/id/91/1200/1200', 'https://picsum.photos/id/91/600/600', 1, 'local');

INSERT INTO products (slug, product_type, listing_type, sku, category_id, price, currency, discount_rate, vat_rate, user_id, status, is_promoted, promote_start_date, promote_end_date, promote_plan, promote_day, is_special_offer, visibility, rating, stock, shipping_delivery_time_id, multiple_sale, is_deleted, is_draft, created_at) VALUES ('demo-azura-020-scented-candle-tin', 'physical', 'sell_on_site', 'AZ-DEMO-020', @demo_cat, 780000, @demo_cur, 0, 0, @demo_seller, 1, 0, NOW(), NOW(), 'none', 0, 0, 1, '0', 40, 0, 1, 0, 0, NOW());
SET @pid := LAST_INSERT_ID();
INSERT INTO product_details (product_id, lang_id, title, description, seo_title, seo_description, seo_keywords) VALUES (@pid, 1, 'Scented candle tin — demo', 'Small scented candle in travel tin. Demo product.', 'Scented candle tin — demo', 'Small scented candle in travel tin. Demo product.', 'demo, azura, marketplace');
INSERT INTO images (product_id, image_default, image_big, image_small, is_main, storage) VALUES (@pid, 'https://picsum.photos/id/93/800/800', 'https://picsum.photos/id/93/1200/1200', 'https://picsum.photos/id/93/600/600', 1, 'local');

INSERT INTO products (slug, product_type, listing_type, sku, category_id, price, currency, discount_rate, vat_rate, user_id, status, is_promoted, promote_start_date, promote_end_date, promote_plan, promote_day, is_special_offer, visibility, rating, stock, shipping_delivery_time_id, multiple_sale, is_deleted, is_draft, created_at) VALUES ('demo-azura-021-backpack-daypack', 'physical', 'sell_on_site', 'AZ-DEMO-021', @demo_cat, 6200000, @demo_cur, 0, 0, @demo_seller, 1, 0, NOW(), NOW(), 'none', 0, 0, 1, '0', 40, 0, 1, 0, 0, NOW());
SET @pid := LAST_INSERT_ID();
INSERT INTO product_details (product_id, lang_id, title, description, seo_title, seo_description, seo_keywords) VALUES (@pid, 1, 'Backpack daypack — demo', 'Compact backpack for daily commute. Demo SKU.', 'Backpack daypack — demo', 'Compact backpack for daily commute. Demo SKU.', 'demo, azura, marketplace');
INSERT INTO images (product_id, image_default, image_big, image_small, is_main, storage) VALUES (@pid, 'https://picsum.photos/id/95/800/800', 'https://picsum.photos/id/95/1200/1200', 'https://picsum.photos/id/95/600/600', 1, 'local');

INSERT INTO products (slug, product_type, listing_type, sku, category_id, price, currency, discount_rate, vat_rate, user_id, status, is_promoted, promote_start_date, promote_end_date, promote_plan, promote_day, is_special_offer, visibility, rating, stock, shipping_delivery_time_id, multiple_sale, is_deleted, is_draft, created_at) VALUES ('demo-azura-022-beach-towel-quick-dry', 'physical', 'sell_on_site', 'AZ-DEMO-022', @demo_cat, 990000, @demo_cur, 0, 0, @demo_seller, 1, 0, NOW(), NOW(), 'none', 0, 0, 1, '0', 40, 0, 1, 0, 0, NOW());
SET @pid := LAST_INSERT_ID();
INSERT INTO product_details (product_id, lang_id, title, description, seo_title, seo_description, seo_keywords) VALUES (@pid, 1, 'Beach towel quick-dry — demo', 'Quick-dry microfibre beach towel. Demo listing.', 'Beach towel quick-dry — demo', 'Quick-dry microfibre beach towel. Demo listing.', 'demo, azura, marketplace');
INSERT INTO images (product_id, image_default, image_big, image_small, is_main, storage) VALUES (@pid, 'https://picsum.photos/id/101/800/800', 'https://picsum.photos/id/101/1200/1200', 'https://picsum.photos/id/101/600/600', 1, 'local');

INSERT INTO products (slug, product_type, listing_type, sku, category_id, price, currency, discount_rate, vat_rate, user_id, status, is_promoted, promote_start_date, promote_end_date, promote_plan, promote_day, is_special_offer, visibility, rating, stock, shipping_delivery_time_id, multiple_sale, is_deleted, is_draft, created_at) VALUES ('demo-azura-023-kitchen-spatula-set', 'physical', 'sell_on_site', 'AZ-DEMO-023', @demo_cat, 480000, @demo_cur, 0, 0, @demo_seller, 1, 0, NOW(), NOW(), 'none', 0, 0, 1, '0', 40, 0, 1, 0, 0, NOW());
SET @pid := LAST_INSERT_ID();
INSERT INTO product_details (product_id, lang_id, title, description, seo_title, seo_description, seo_keywords) VALUES (@pid, 1, 'Kitchen spatula set — demo', 'Heat-resistant silicone spatulas. Demo item.', 'Kitchen spatula set — demo', 'Heat-resistant silicone spatulas. Demo item.', 'demo, azura, marketplace');
INSERT INTO images (product_id, image_default, image_big, image_small, is_main, storage) VALUES (@pid, 'https://picsum.photos/id/103/800/800', 'https://picsum.photos/id/103/1200/1200', 'https://picsum.photos/id/103/600/600', 1, 'local');

INSERT INTO products (slug, product_type, listing_type, sku, category_id, price, currency, discount_rate, vat_rate, user_id, status, is_promoted, promote_start_date, promote_end_date, promote_plan, promote_day, is_special_offer, visibility, rating, stock, shipping_delivery_time_id, multiple_sale, is_deleted, is_draft, created_at) VALUES ('demo-azura-024-phone-grip-stand', 'physical', 'sell_on_site', 'AZ-DEMO-024', @demo_cat, 340000, @demo_cur, 0, 0, @demo_seller, 1, 0, NOW(), NOW(), 'none', 0, 0, 1, '0', 40, 0, 1, 0, 0, NOW());
SET @pid := LAST_INSERT_ID();
INSERT INTO product_details (product_id, lang_id, title, description, seo_title, seo_description, seo_keywords) VALUES (@pid, 1, 'Phone grip stand — demo', 'Collapsible grip and stand for phones. Demo SKU.', 'Phone grip stand — demo', 'Collapsible grip and stand for phones. Demo SKU.', 'demo, azura, marketplace');
INSERT INTO images (product_id, image_default, image_big, image_small, is_main, storage) VALUES (@pid, 'https://picsum.photos/id/107/800/800', 'https://picsum.photos/id/107/1200/1200', 'https://picsum.photos/id/107/600/600', 1, 'local');

INSERT INTO products (slug, product_type, listing_type, sku, category_id, price, currency, discount_rate, vat_rate, user_id, status, is_promoted, promote_start_date, promote_end_date, promote_plan, promote_day, is_special_offer, visibility, rating, stock, shipping_delivery_time_id, multiple_sale, is_deleted, is_draft, created_at) VALUES ('demo-azura-025-facial-cotton-pads', 'physical', 'sell_on_site', 'AZ-DEMO-025', @demo_cat, 120000, @demo_cur, 0, 0, @demo_seller, 1, 0, NOW(), NOW(), 'none', 0, 0, 1, '0', 40, 0, 1, 0, 0, NOW());
SET @pid := LAST_INSERT_ID();
INSERT INTO product_details (product_id, lang_id, title, description, seo_title, seo_description, seo_keywords) VALUES (@pid, 1, 'Facial cotton pads — demo', 'Soft cotton pads for skincare. Demo listing.', 'Facial cotton pads — demo', 'Soft cotton pads for skincare. Demo listing.', 'demo, azura, marketplace');
INSERT INTO images (product_id, image_default, image_big, image_small, is_main, storage) VALUES (@pid, 'https://picsum.photos/id/111/800/800', 'https://picsum.photos/id/111/1200/1200', 'https://picsum.photos/id/111/600/600', 1, 'local');

INSERT INTO products (slug, product_type, listing_type, sku, category_id, price, currency, discount_rate, vat_rate, user_id, status, is_promoted, promote_start_date, promote_end_date, promote_plan, promote_day, is_special_offer, visibility, rating, stock, shipping_delivery_time_id, multiple_sale, is_deleted, is_draft, created_at) VALUES ('demo-azura-026-dog-leash-nylon', 'physical', 'sell_on_site', 'AZ-DEMO-026', @demo_cat, 450000, @demo_cur, 0, 0, @demo_seller, 1, 0, NOW(), NOW(), 'none', 0, 0, 1, '0', 40, 0, 1, 0, 0, NOW());
SET @pid := LAST_INSERT_ID();
INSERT INTO product_details (product_id, lang_id, title, description, seo_title, seo_description, seo_keywords) VALUES (@pid, 1, 'Dog leash nylon — demo', 'Strong nylon leash with padded handle. Demo product.', 'Dog leash nylon — demo', 'Strong nylon leash with padded handle. Demo product.', 'demo, azura, marketplace');
INSERT INTO images (product_id, image_default, image_big, image_small, is_main, storage) VALUES (@pid, 'https://picsum.photos/id/119/800/800', 'https://picsum.photos/id/119/1200/1200', 'https://picsum.photos/id/119/600/600', 1, 'local');

INSERT INTO products (slug, product_type, listing_type, sku, category_id, price, currency, discount_rate, vat_rate, user_id, status, is_promoted, promote_start_date, promote_end_date, promote_plan, promote_day, is_special_offer, visibility, rating, stock, shipping_delivery_time_id, multiple_sale, is_deleted, is_draft, created_at) VALUES ('demo-azura-027-notebook-dotted-a5', 'physical', 'sell_on_site', 'AZ-DEMO-027', @demo_cat, 890000, @demo_cur, 0, 0, @demo_seller, 1, 0, NOW(), NOW(), 'none', 0, 0, 1, '0', 40, 0, 1, 0, 0, NOW());
SET @pid := LAST_INSERT_ID();
INSERT INTO product_details (product_id, lang_id, title, description, seo_title, seo_description, seo_keywords) VALUES (@pid, 1, 'Notebook dotted A5 — demo', 'A5 dotted notebook for notes. Demo SKU.', 'Notebook dotted A5 — demo', 'A5 dotted notebook for notes. Demo SKU.', 'demo, azura, marketplace');
INSERT INTO images (product_id, image_default, image_big, image_small, is_main, storage) VALUES (@pid, 'https://picsum.photos/id/124/800/800', 'https://picsum.photos/id/124/1200/1200', 'https://picsum.photos/id/124/600/600', 1, 'local');

INSERT INTO products (slug, product_type, listing_type, sku, category_id, price, currency, discount_rate, vat_rate, user_id, status, is_promoted, promote_start_date, promote_end_date, promote_plan, promote_day, is_special_offer, visibility, rating, stock, shipping_delivery_time_id, multiple_sale, is_deleted, is_draft, created_at) VALUES ('demo-azura-028-electric-kettle-1l', 'physical', 'sell_on_site', 'AZ-DEMO-028', @demo_cat, 1250000, @demo_cur, 0, 0, @demo_seller, 1, 0, NOW(), NOW(), 'none', 0, 0, 1, '0', 40, 0, 1, 0, 0, NOW());
SET @pid := LAST_INSERT_ID();
INSERT INTO product_details (product_id, lang_id, title, description, seo_title, seo_description, seo_keywords) VALUES (@pid, 1, 'Electric kettle 1L — demo', 'Stainless kettle with auto shut-off. Demo listing.', 'Electric kettle 1L — demo', 'Stainless kettle with auto shut-off. Demo listing.', 'demo, azura, marketplace');
INSERT INTO images (product_id, image_default, image_big, image_small, is_main, storage) VALUES (@pid, 'https://picsum.photos/id/129/800/800', 'https://picsum.photos/id/129/1200/1200', 'https://picsum.photos/id/129/600/600', 1, 'local');

INSERT INTO products (slug, product_type, listing_type, sku, category_id, price, currency, discount_rate, vat_rate, user_id, status, is_promoted, promote_start_date, promote_end_date, promote_plan, promote_day, is_special_offer, visibility, rating, stock, shipping_delivery_time_id, multiple_sale, is_deleted, is_draft, created_at) VALUES ('demo-azura-029-sunglasses-polarized', 'physical', 'sell_on_site', 'AZ-DEMO-029', @demo_cat, 6200000, @demo_cur, 0, 0, @demo_seller, 1, 0, NOW(), NOW(), 'none', 0, 0, 1, '0', 40, 0, 1, 0, 0, NOW());
SET @pid := LAST_INSERT_ID();
INSERT INTO product_details (product_id, lang_id, title, description, seo_title, seo_description, seo_keywords) VALUES (@pid, 1, 'Sunglasses polarized — demo', 'Polarized lenses with UV protection. Demo item.', 'Sunglasses polarized — demo', 'Polarized lenses with UV protection. Demo item.', 'demo, azura, marketplace');
INSERT INTO images (product_id, image_default, image_big, image_small, is_main, storage) VALUES (@pid, 'https://picsum.photos/id/133/800/800', 'https://picsum.photos/id/133/1200/1200', 'https://picsum.photos/id/133/600/600', 1, 'local');

INSERT INTO products (slug, product_type, listing_type, sku, category_id, price, currency, discount_rate, vat_rate, user_id, status, is_promoted, promote_start_date, promote_end_date, promote_plan, promote_day, is_special_offer, visibility, rating, stock, shipping_delivery_time_id, multiple_sale, is_deleted, is_draft, created_at) VALUES ('demo-azura-030-hiking-socks-merino', 'physical', 'sell_on_site', 'AZ-DEMO-030', @demo_cat, 1750000, @demo_cur, 0, 0, @demo_seller, 1, 0, NOW(), NOW(), 'none', 0, 0, 1, '0', 40, 0, 1, 0, 0, NOW());
SET @pid := LAST_INSERT_ID();
INSERT INTO product_details (product_id, lang_id, title, description, seo_title, seo_description, seo_keywords) VALUES (@pid, 1, 'Hiking socks merino — demo', 'Merino blend socks for hiking. Demo SKU.', 'Hiking socks merino — demo', 'Merino blend socks for hiking. Demo SKU.', 'demo, azura, marketplace');
INSERT INTO images (product_id, image_default, image_big, image_small, is_main, storage) VALUES (@pid, 'https://picsum.photos/id/137/800/800', 'https://picsum.photos/id/137/1200/1200', 'https://picsum.photos/id/137/600/600', 1, 'local');

INSERT INTO products (slug, product_type, listing_type, sku, category_id, price, currency, discount_rate, vat_rate, user_id, status, is_promoted, promote_start_date, promote_end_date, promote_plan, promote_day, is_special_offer, visibility, rating, stock, shipping_delivery_time_id, multiple_sale, is_deleted, is_draft, created_at) VALUES ('demo-azura-031-bluetooth-speaker-mini', 'physical', 'sell_on_site', 'AZ-DEMO-031', @demo_cat, 2100000, @demo_cur, 0, 0, @demo_seller, 1, 0, NOW(), NOW(), 'none', 0, 0, 1, '0', 40, 0, 1, 0, 0, NOW());
SET @pid := LAST_INSERT_ID();
INSERT INTO product_details (product_id, lang_id, title, description, seo_title, seo_description, seo_keywords) VALUES (@pid, 1, 'Bluetooth speaker mini — demo', 'Portable mini speaker with clear sound. Demo listing.', 'Bluetooth speaker mini — demo', 'Portable mini speaker with clear sound. Demo listing.', 'demo, azura, marketplace');
INSERT INTO images (product_id, image_default, image_big, image_small, is_main, storage) VALUES (@pid, 'https://picsum.photos/id/140/800/800', 'https://picsum.photos/id/140/1200/1200', 'https://picsum.photos/id/140/600/600', 1, 'local');

-- Legacy rows: ensure every remaining product has at least one gallery image
INSERT INTO images (product_id, image_default, image_big, image_small, is_main, storage)
SELECT p.id,
  CONCAT('https://picsum.photos/id/', 30 + MOD(p.id, 170), '/800/800'),
  CONCAT('https://picsum.photos/id/', 30 + MOD(p.id, 170), '/1200/1200'),
  CONCAT('https://picsum.photos/id/', 30 + MOD(p.id, 170), '/600/600'),
  1,
  'local'
FROM products p
WHERE p.is_deleted = 0
  AND NOT EXISTS (
    SELECT 1 FROM images i WHERE i.product_id = p.id AND i.image_small IS NOT NULL AND TRIM(i.image_small) <> ''
  );