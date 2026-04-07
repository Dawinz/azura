<?php
/**
 * Database Schema Import Script for Railway
 * This script imports the database schema from install_modesy.sql
 */

// Get Railway MySQL environment variables
$db_host = getenv('MYSQLHOST') ?: 'localhost';
$db_user = getenv('MYSQLUSER') ?: 'root';
$db_pass = getenv('MYSQLPASSWORD') ?: '';
$db_name = getenv('MYSQLDATABASE') ?: 'railway';
$db_port = getenv('MYSQLPORT') ?: 3306;

echo "Connecting to MySQL...\n";
echo "Host: $db_host:$db_port\n";
echo "Database: $db_name\n";
echo "User: $db_user\n\n";

// Connect to MySQL
$mysqli = new mysqli($db_host, $db_user, $db_pass, $db_name, $db_port);

// Check connection
if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error . "\n");
}

echo "Connected successfully!\n\n";

// Read SQL file
$sql_file = __DIR__ . '/vvv/sql/install_modesy.sql';
if (!file_exists($sql_file)) {
    die("SQL file not found: $sql_file\n");
}

echo "Reading SQL file: $sql_file\n";
$sql = file_get_contents($sql_file);

if ($sql === false) {
    die("Failed to read SQL file\n");
}

echo "SQL file loaded (" . number_format(strlen($sql)) . " bytes)\n\n";

// Remove database creation statement if present (we're already connected to the database)
$sql = preg_replace('/CREATE DATABASE.*?;/i', '', $sql);
$sql = preg_replace('/USE.*?;/i', '', $sql);

// Split SQL into individual statements
// Remove comments and empty lines, then split by semicolons
$sql = preg_replace('/--.*$/m', '', $sql); // Remove single-line comments
$sql = preg_replace('/\/\*.*?\*\//s', '', $sql); // Remove multi-line comments
$statements = array_filter(
    array_map('trim', explode(';', $sql)),
    function($stmt) {
        return !empty($stmt) && !preg_match('/^(SET|START|COMMIT)/i', $stmt);
    }
);

echo "Executing " . count($statements) . " SQL statements...\n\n";

$success = 0;
$errors = 0;

foreach ($statements as $index => $statement) {
    if (empty(trim($statement))) {
        continue;
    }
    
    // Skip transaction control statements
    if (preg_match('/^(SET|START|COMMIT|ROLLBACK)/i', trim($statement))) {
        continue;
    }
    
    if ($mysqli->query($statement)) {
        $success++;
        if (($success % 50) == 0) {
            echo "Progress: $success statements executed...\n";
        }
    } else {
        $errors++;
        $error_msg = $mysqli->error;
        // Only show first few errors to avoid spam
        if ($errors <= 5) {
            echo "Error in statement " . ($index + 1) . ": $error_msg\n";
            echo "Statement: " . substr($statement, 0, 100) . "...\n\n";
        }
    }
}

echo "\n";
echo "Import completed!\n";
echo "Successful statements: $success\n";
echo "Errors: $errors\n";

$mysqli->close();

if ($errors > 0 && $errors < 10) {
    echo "\nNote: Some errors may be expected (e.g., table already exists).\n";
    echo "If many tables were created successfully, the import was likely successful.\n";
}
