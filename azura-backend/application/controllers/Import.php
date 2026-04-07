<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Temporary Database Import Controller
 * Access: /import/schema?key=YOUR_SECRET_KEY
 * 
 * IMPORTANT: Delete this file after importing the schema!
 */
class Import extends CI_Controller {

    private $secret_key = 'railway_import_2026_temp_key_change_me';

    public function schema() {
        // Check secret key
        $provided_key = $this->input->get('key');
        if ($provided_key !== $this->secret_key) {
            show_error('Unauthorized', 401);
            return;
        }

        // Get Railway MySQL environment variables
        $db_host = getenv('MYSQLHOST') ?: 'localhost';
        $db_user = getenv('MYSQLUSER') ?: 'root';
        $db_pass = getenv('MYSQLPASSWORD') ?: '';
        $db_name = getenv('MYSQLDATABASE') ?: 'railway';
        $db_port = getenv('MYSQLPORT') ?: 3306;

        header('Content-Type: text/plain');
        echo "Connecting to MySQL...\n";
        echo "Host: $db_host:$db_port\n";
        echo "Database: $db_name\n\n";

        // Connect to MySQL
        $mysqli = new mysqli($db_host, $db_user, $db_pass, $db_name, $db_port);

        // Check connection
        if ($mysqli->connect_error) {
            die("Connection failed: " . $mysqli->connect_error . "\n");
        }

        echo "Connected successfully!\n\n";

        // Read SQL file
        $sql_file = APPPATH . '../vvv/sql/install_modesy.sql';
        if (!file_exists($sql_file)) {
            die("SQL file not found: $sql_file\n");
        }

        echo "Reading SQL file...\n";
        $sql = file_get_contents($sql_file);

        if ($sql === false) {
            die("Failed to read SQL file\n");
        }

        echo "SQL file loaded (" . number_format(strlen($sql)) . " bytes)\n\n";

        // Remove database creation/use statements
        $sql = preg_replace('/CREATE DATABASE.*?;/i', '', $sql);
        $sql = preg_replace('/USE.*?;/i', '', $sql);

        // Split SQL into individual statements
        $sql = preg_replace('/--.*$/m', '', $sql);
        $sql = preg_replace('/\/\*.*?\*\//s', '', $sql);
        $statements = array_filter(
            array_map('trim', explode(';', $sql)),
            function($stmt) {
                return !empty($stmt) && !preg_match('/^(SET|START|COMMIT)/i', $stmt);
            }
        );

        echo "Executing " . count($statements) . " SQL statements...\n\n";

        $success = 0;
        $errors = 0;
        $error_messages = [];

        foreach ($statements as $index => $statement) {
            if (empty(trim($statement))) {
                continue;
            }
            
            if (preg_match('/^(SET|START|COMMIT|ROLLBACK)/i', trim($statement))) {
                continue;
            }
            
            if ($mysqli->query($statement)) {
                $success++;
            } else {
                $errors++;
                $error_msg = $mysqli->error;
                // Only collect first 10 errors
                if ($errors <= 10) {
                    $error_messages[] = "Statement " . ($index + 1) . ": " . substr($statement, 0, 100) . "... Error: $error_msg";
                }
            }
        }

        echo "\n";
        echo "Import completed!\n";
        echo "Successful statements: $success\n";
        echo "Errors: $errors\n\n";

        if (!empty($error_messages)) {
            echo "Error details:\n";
            foreach ($error_messages as $msg) {
                echo "- $msg\n";
            }
        }

        $mysqli->close();

        if ($success > 100) {
            echo "\n✓ Database schema imported successfully!\n";
            echo "Please delete this controller file (application/controllers/Import.php) for security.\n";
        }
    }
}
