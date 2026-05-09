# One-off generator: run from repo root
# python docs/railway/_gen_demo_seed.py
import re

titles = [
    ("Linen blend shirt — demo", "Breathable linen-blend shirt for everyday wear. Demo listing for Azura Mall."),
    ("Canvas sneakers — demo", "Comfortable canvas sneakers with rubber sole. Demo SKU for catalog testing."),
    ("Ceramic dinner plate set — demo", "Set of four ceramic plates for dining. Demo product imagery via CDN."),
    ("Stainless steel water bottle — demo", "Insulated bottle keeps drinks cold or warm. Demo listing."),
    ("Wireless earbuds case — demo", "Protective silicone case for earbuds. Demo marketplace item."),
    ("Woven storage basket — demo", "Handwoven basket for home organisation. Demo SKU."),
    ("Cotton throw blanket — demo", "Soft cotton throw for sofas and beds. Demo listing."),
    ("Desk LED lamp — demo", "Adjustable LED desk lamp with warm light. Demo product."),
    ("Leather card holder — demo", "Slim leather holder for cards and cash. Demo SKU."),
    ("Kids cotton tee — demo", "Breathable cotton t-shirt for kids. Demo listing."),
    ("Glass meal prep containers — demo", "Stackable glass containers with lids. Demo item."),
    ("Yoga mat strap — demo", "Adjustable strap for carrying yoga mats. Demo SKU."),
    ("Bamboo cutting board — demo", "Durable bamboo board for food prep. Demo listing."),
    ("Travel neck pillow — demo", "Memory foam neck pillow for travel. Demo product."),
    ("USB-C charging cable — demo", "Braided USB-C cable for phones and tablets. Demo SKU."),
    ("Ceramic mug pair — demo", "Two minimalist ceramic mugs. Demo marketplace listing."),
    ("Running shorts — demo", "Lightweight shorts for training. Demo item."),
    ("Wall clock silent — demo", "Silent sweeping wall clock. Demo SKU."),
    ("Plant stand wooden — demo", "Wooden stand for indoor plants. Demo listing."),
    ("Scented candle tin — demo", "Small scented candle in travel tin. Demo product."),
    ("Backpack daypack — demo", "Compact backpack for daily commute. Demo SKU."),
    ("Beach towel quick-dry — demo", "Quick-dry microfibre beach towel. Demo listing."),
    ("Kitchen spatula set — demo", "Heat-resistant silicone spatulas. Demo item."),
    ("Phone grip stand — demo", "Collapsible grip and stand for phones. Demo SKU."),
    ("Facial cotton pads — demo", "Soft cotton pads for skincare. Demo listing."),
    ("Dog leash nylon — demo", "Strong nylon leash with padded handle. Demo product."),
    ("Notebook dotted A5 — demo", "A5 dotted notebook for notes. Demo SKU."),
    ("Electric kettle 1L — demo", "Stainless kettle with auto shut-off. Demo listing."),
    ("Sunglasses polarized — demo", "Polarized lenses with UV protection. Demo item."),
    ("Hiking socks merino — demo", "Merino blend socks for hiking. Demo SKU."),
    ("Bluetooth speaker mini — demo", "Portable mini speaker with clear sound. Demo listing."),
]

picsum_ids = [11, 13, 21, 29, 31, 36, 42, 48, 52, 57, 63, 67, 71, 74, 78, 82, 85, 88, 91, 93, 95, 101, 103, 107, 111, 119, 124, 129, 133, 137, 140]
prices = [
    1490000, 8900000, 3200000, 4500000, 2200000, 1890000, 2790000, 3500000, 950000, 1290000, 4100000, 550000, 1750000,
    990000, 650000, 1150000, 2100000, 3200000, 1550000, 780000, 6200000, 990000, 480000, 340000, 120000, 450000,
    890000, 1250000, 6200000, 1750000, 2100000,
]


def slugify(title: str, idx: int) -> str:
    base = title.lower().replace(" — demo", "").strip()
    base = re.sub(r"[^a-z0-9]+", "-", base)
    base = re.sub(r"-+", "-", base).strip("-")
    return f"demo-azura-{idx:03d}-{base}"[:180]


def esc_sql(s: str) -> str:
    return s.replace("\\", "\\\\").replace("'", "''")


lines = []
lines.append("-- Azura Mall — demo catalog seed for Railway / MySQL")
lines.append("-- Backup DB first. Inserts 31 physical demo SKUs (AZ-DEMO-###) with HTTPS gallery URLs,")
lines.append("-- then adds placeholder images for any other products still missing an images row.")
lines.append("")
lines.append("SET @demo_seller := COALESCE(")
lines.append("  (SELECT id FROM users WHERE banned = 0 AND role = 'vendor' ORDER BY id LIMIT 1),")
lines.append("  (SELECT id FROM users WHERE banned = 0 ORDER BY id LIMIT 1),")
lines.append("  1")
lines.append(");")
lines.append("SET @demo_cat := COALESCE((SELECT MIN(id) FROM categories WHERE visibility = 1), 1);")
lines.append("SET @demo_cur := COALESCE((SELECT default_currency FROM payment_settings WHERE id = 1 LIMIT 1), 'TZS');")
lines.append("")
lines.append("-- Remove prior demo batch (re-runnable)")
lines.append("DELETE FROM images WHERE product_id IN (SELECT id FROM (SELECT id FROM products WHERE sku LIKE 'AZ-DEMO-%') t);")
lines.append("DELETE FROM product_details WHERE product_id IN (SELECT id FROM (SELECT id FROM products WHERE sku LIKE 'AZ-DEMO-%') t);")
lines.append("DELETE FROM products WHERE sku LIKE 'AZ-DEMO-%';")
lines.append("")

for i, ((title, desc), pid, price) in enumerate(zip(titles, picsum_ids, prices), 1):
    sku = f"AZ-DEMO-{i:03d}"
    slug = slugify(title, i)
    img = f"https://picsum.photos/id/{pid}/"
    tsql = esc_sql(title)
    dsql = esc_sql(desc)
    lines.append(
        "INSERT INTO products (slug, product_type, listing_type, sku, category_id, price, currency, discount_rate, "
        "vat_rate, user_id, status, is_promoted, promote_start_date, promote_end_date, promote_plan, promote_day, "
        "is_special_offer, visibility, rating, stock, shipping_delivery_time_id, multiple_sale, is_deleted, is_draft, created_at) "
        f"VALUES ('{esc_sql(slug)}', 'physical', 'sell_on_site', '{sku}', @demo_cat, {price}, @demo_cur, 0, 0, @demo_seller, "
        "1, 0, NOW(), NOW(), 'none', 0, 0, 1, '0', 40, 0, 1, 0, 0, NOW());"
    )
    lines.append("SET @pid := LAST_INSERT_ID();")
    lines.append(
        f"INSERT INTO product_details (product_id, lang_id, title, description, seo_title, seo_description, seo_keywords) "
        f"VALUES (@pid, 1, '{tsql}', '{dsql}', '{tsql}', '{esc_sql(desc[:120])}', 'demo, azura, marketplace');"
    )
    lines.append(
        f"INSERT INTO images (product_id, image_default, image_big, image_small, is_main, storage) VALUES "
        f"(@pid, '{img}800/800', '{img}1200/1200', '{img}600/600', 1, 'local');"
    )
    lines.append("")

lines.append("-- Legacy rows: ensure every remaining product has at least one gallery image")
lines.append("INSERT INTO images (product_id, image_default, image_big, image_small, is_main, storage)")
lines.append("SELECT p.id,")
lines.append("  CONCAT('https://picsum.photos/id/', 30 + MOD(p.id, 170), '/800/800'),")
lines.append("  CONCAT('https://picsum.photos/id/', 30 + MOD(p.id, 170), '/1200/1200'),")
lines.append("  CONCAT('https://picsum.photos/id/', 30 + MOD(p.id, 170), '/600/600'),")
lines.append("  1,")
lines.append("  'local'")
lines.append("FROM products p")
lines.append("WHERE p.is_deleted = 0")
lines.append("  AND NOT EXISTS (")
lines.append("    SELECT 1 FROM images i WHERE i.product_id = p.id AND i.image_small IS NOT NULL AND TRIM(i.image_small) <> ''")
lines.append("  );")

out_path = __file__.replace("_gen_demo_seed.py", "demo_catalog_seed.sql")
with open(out_path, "w", encoding="utf-8") as f:
    f.write("\n".join(lines))
print("Wrote", out_path, len(lines), "lines")
