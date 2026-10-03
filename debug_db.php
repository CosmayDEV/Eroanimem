<?php
header("Content-Type: text/plain");
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'db_config.php';

echo "--- Debugging Database Connection ---\n";
echo "Host: " . DB_HOST . "\n";
echo "DB: " . DB_NAME . "\n";
echo "User: " . DB_USER . "\n";

try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    $pdo = new PDO($dsn, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "Connection: SUCCESS!\n\n";

    echo "--- Checking Tables ---\n";
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    echo "Tables found: " . implode(", ", $tables) . "\n\n";

    $required = ['users', 'media', 'chapters', 'episodes', 'status_notes'];
    foreach ($required as $tbl) {
        if (in_array($tbl, $tables)) {
            echo "[OK] $tbl table exists.\n";
        } else {
            echo "[FAIL] $tbl table MISSING!\n";
        }
    }

    echo "\n--- Checking Users ---\n";
    $count = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    echo "Total Users: $count\n";

} catch (PDOException $e) {
    echo "Connection FAILED: " . $e->getMessage() . "\n";
}
?>