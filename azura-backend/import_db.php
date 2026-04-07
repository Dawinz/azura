<?php
/**
 * One-shot database import via multi_query (no chunking, no split errors).
 * Call: import_db.php?key=railway_import_2026_temp_key_change_me
 *
 * IMPORTANT: Delete this file after importing the schema!
 */

// Disable import in production (set ENABLE_IMPORT=1 only when running import)
if (getenv('ENABLE_IMPORT') !== '1' && getenv('ENABLE_IMPORT') !== 'true') {
    http_response_code(404);
    die('Not found');
}

$secret_key = 'railway_import_2026_temp_key_change_me';
$provided_key = $_GET['key'] ?? '';

if ($provided_key !== $secret_key) {
    http_response_code(401);
    die('Unauthorized');
}

header('Content-Type: text/plain; charset=utf-8');
set_time_limit(600);

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$db_host = getenv('MYSQLHOST') ?: 'localhost';
$db_user = getenv('MYSQLUSER') ?: 'root';
$db_pass = getenv('MYSQLPASSWORD') ?: '';
$db_name = getenv('MYSQLDATABASE') ?: 'railway';
$db_port = (int)(getenv('MYSQLPORT') ?: 3306);

echo "Connecting... ";
$mysqli = mysqli_init();
$mysqli->options(MYSQLI_OPT_CONNECT_TIMEOUT, 10);
$connected = @$mysqli->real_connect($db_host, $db_user, $db_pass, $db_name, $db_port);
if (!$connected || !$mysqli->thread_id) {
    die("Failed: " . $mysqli->connect_error . "\n");
}
echo "OK\n";

$sql_file = __DIR__ . '/vvv/sql/install_modesy.sql';
if (!file_exists($sql_file)) {
    die("SQL file not found.\n");
}

$sql = file_get_contents($sql_file);
$sql = preg_replace('/CREATE DATABASE\s+[^;]+;/i', '', $sql);
$sql = preg_replace('/USE\s+[^;]+;/i', '', $sql);

echo "Running full import (multi_query, ~2 min)...\n";
flush();

$mysqli->autocommit(true);
$ok = false;
try {
    $ok = $mysqli->multi_query($sql);
} catch (mysqli_sql_exception $e) {
    $ok = false;
}

// Drain result sets
do {
    if ($result = $mysqli->store_result()) {
        $result->free();
    }
} while ($mysqli->next_result());

$errno = $mysqli->errno;
$errmsg = $mysqli->error;
$mysqli->close();

$base = 'https://' . (getenv('RAILWAY_PUBLIC_DOMAIN') ?: 'azura-backend-production.up.railway.app');

if ($ok && !$errno) {
    echo "========================================\n";
    echo "Import complete.\n";
    echo "========================================\n";
    echo "Test API: $base/v1/category/list\n";
    echo "Then delete import_db.php for security.\n";
} else {
    echo "Error ($errno): $errmsg\n";
    if ($errno == 1050 || strpos($errmsg, 'already exists') !== false) {
        echo "Tables already exist. Drop all tables in the DB and run again for a clean import.\n";
    }
}
