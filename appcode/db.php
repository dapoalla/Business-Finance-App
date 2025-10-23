<?php
// db.php
// Central database bootstrap and session

$configPath = __DIR__ . '/config.php';
if (!file_exists($configPath)) {
    // Redirect to setup wizard if config missing
    header('Location: setup.php');
    exit;
}

$config = include $configPath;
$servername = $config['db']['host'] ?? 'localhost';
$username = $config['db']['user'] ?? 'root';
$password = $config['db']['pass'] ?? '';
$dbname = $config['db']['name'] ?? '';

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
?>
