<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'db_config.php';

echo "<h2>Database Connection Test</h2>";
echo "Host: " . htmlspecialchars($db_host) . "<br>";
echo "Database: " . htmlspecialchars($db_name) . "<br>";
echo "User: " . htmlspecialchars($db_user) . "<br>";
// Do not print password

echo "<hr>";

try {
    $dsn = "mysql:host=$db_host;dbname=$db_name;charset=utf8mb4";
    $conn = new PDO($dsn, $db_user, $db_pass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "<h3 style='color:green'>SUCCESS: Connected to database!</h3>";

    // Test Query
    $stmt = $conn->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "<b>Tables found:</b> " . implode(", ", $tables);

} catch (PDOException $e) {
    echo "<h3 style='color:red'>FAILURE: Could not connect.</h3>";
    echo "<strong>Error Message:</strong> " . $e->getMessage() . "<br>";
    echo "<strong>Error Code:</strong> " . $e->getCode() . "<br>";

    echo "<hr>";
    echo "<h4>Troubleshooting Tips:</h4>";
    echo "<ul>";
    echo "<li>Ensure the <b>User</b> is added to the <b>Database</b> in cPanel (MySQL Databases -> Add User to Database).</li>";
    echo "<li>Ensure the User has <b>ALL PRIVILEGES</b> checked.</li>";
    echo "<li>Check if the password copied has hidden spaces.</li>";
    echo "<li>If using a remote server, ensure 'localhost' is correct (sometimes providers use IPs).</li>";
    echo "</ul>";
}
?>