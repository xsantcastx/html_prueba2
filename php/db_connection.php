<?php
$db_host = "your_db_host"; // e.g., "localhost" or "127.0.0.1"
$db_user = "your_db_username";
$db_pass = "your_db_password";
$db_name = "your_db_name";

// Create connection
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset to utf8mb4 for full UTF-8 support
if (!$conn->set_charset("utf8mb4")) {
    // printf("Error loading character set utf8mb4: %s\n", $conn->error);
    // Fallback or die, depending on requirements
    // For now, we'll proceed, but this should be handled in a real app
}

// The connection object $conn can now be used by other PHP scripts
// Example: require_once 'db_connection.php';
?>
