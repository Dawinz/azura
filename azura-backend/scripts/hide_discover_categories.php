<?php
/**
 * One-off: set visibility=0 for Shoes, Abaya, Shirts, Khanzu/Kanzu (slug or name, lang_id=1).
 * Run on Railway:  cd azura-backend && railway run php scripts/hide_discover_categories.php
 */
declare(strict_types=1);

$h = getenv('MYSQLHOST') ?: getenv('DATABASE_HOST');
$u = getenv('MYSQLUSER') ?: getenv('DATABASE_USER');
$p = getenv('MYSQLPASSWORD') ?: getenv('DATABASE_PASSWORD');
$d = getenv('MYSQLDATABASE') ?: getenv('DATABASE_NAME');
$port = (int) (getenv('MYSQLPORT') ?: getenv('DATABASE_PORT') ?: 3306);

if (empty($h) || empty($u) || $d === false || $d === '') {
    fwrite(STDERR, "Missing database env.\n");
    exit(1);
}

$m = @new mysqli($h, $u, $p, $d, $port);
if ($m->connect_errno) {
    fwrite(STDERR, 'DB connect failed: ' . $m->connect_error . "\n");
    exit(1);
}
$m->set_charset('utf8mb4');

$sqlMatch = "
SELECT DISTINCT c.id, c.slug, cl.name
FROM categories c
LEFT JOIN categories_lang cl ON cl.category_id = c.id AND cl.lang_id = 1
WHERE LOWER(TRIM(COALESCE(c.slug, ''))) REGEXP '(^|-)(shoes|abaya|shirts|khanzu|kanzu)(-|$)'
   OR LOWER(TRIM(COALESCE(cl.name, ''))) REGEXP '^(shoes|abaya|shirts|khanzu|kanzu)([[:space:]]|[-_./,]|$)'
";

$res = $m->query($sqlMatch);
if (!$res) {
    fwrite(STDERR, 'Query failed: ' . $m->error . "\n");
    exit(1);
}

$ids = array();
echo "Matching categories (before update):\n";
while ($row = $res->fetch_assoc()) {
    $ids[] = (int) $row['id'];
    echo sprintf("  id=%d slug=%s name=%s\n", (int) $row['id'], $row['slug'] ?? '', $row['name'] ?? '');
}
$res->free();

if (empty($ids)) {
    echo "No rows matched. Nothing to update.\n";
    exit(0);
}

$idList = implode(',', array_map('intval', $ids));
$upd = 'UPDATE categories SET visibility = 0 WHERE id IN (' . $idList . ')';
if (!$m->query($upd)) {
    fwrite(STDERR, 'Update failed: ' . $m->error . "\n");
    exit(1);
}

echo 'Updated visibility=0 for ' . $m->affected_rows . " row(s).\n";
$m->close();
