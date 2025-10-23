<?php
// db.php
// Database connection details
$servername = "localhost";
$username = "cyberros_aiuser";
$password = "Admin4gpt*";
$dbname = "cyberros_bizcashapp";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Start session for user authentication
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
?>
