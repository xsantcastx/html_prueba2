<?php
$db_host = "localhost"; // e.g., "localhost" or "127.0.0.1"
$db_user = "root";
$db_pass = "";
$db_name = "html_prueba2"; // Database name


$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);


if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}


if (!$conn->set_charset("utf8mb4")) {

}

?>
