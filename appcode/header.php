<?php
require_once 'db.php';

// Access control
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
    exit;
}

$config = include __DIR__ . '/config.php';
$appVersion = $config['app']['version'] ?? '1.2';
$currency = $config['app']['currency'] ?? 'NGN';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Business Finance App</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header>
        <div class="logo">
            <h1>BizCashApp <span style="font-size:0.8em;color:#bbb;">v<?= htmlspecialchars($appVersion) ?></span></h1>
        </div>
        <nav class="nav">
            <a href="index.php" title="Dashboard">🏠</a>
            <a href="invoices.php" title="Invoices">🧾</a>
            <a href="transactions.php" title="Transactions">💳</a>
            <a href="clients.php" title="Clients">👥</a>
            <a href="trial_balance.php" title="Trial Balance">📊</a>
            <a href="users.php" title="Users">👤</a>
            <a href="settings.php" title="Settings">⚙️</a>
        </nav>
        <div class="logout">
            <a href="logout.php">Logout</a>
        </div>
    </header>
