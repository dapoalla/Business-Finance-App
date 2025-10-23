<?php
require_once __DIR__ . '/db.php';

// If config exists and a basic table exists, skip setup
$configPath = __DIR__ . '/config.php';
if (file_exists($configPath)) {
    // check if core tables exist
    $res = $conn->query("SHOW TABLES LIKE 'clients'");
    if ($res && $res->num_rows > 0) {
        header('Location: index.php');
        exit;
    }
}

$setupErrors = [];
$setupSuccess = null;

function createUsersTable($conn) {
    $sql = "CREATE TABLE IF NOT EXISTS users (\n        id INT AUTO_INCREMENT PRIMARY KEY,\n        username VARCHAR(100) UNIQUE NOT NULL,\n        name VARCHAR(150) DEFAULT NULL,\n        email VARCHAR(150) DEFAULT NULL,\n        role ENUM('admin','user') NOT NULL DEFAULT 'admin',\n        password_hash VARCHAR(255) NOT NULL,\n        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP\n    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    return $conn->query($sql);
}

function writeConfig($data) {
    $config = [
        'db' => [
            'host' => $data['db_host'],
            'user' => $data['db_user'],
            'pass' => $data['db_pass'],
            'name' => $data['db_name'],
        ],
        'app' => [
            'version' => '1.2',
            'currency' => $data['currency'],
            'country' => $data['country'],
            'tax_enabled' => isset($data['tax_enabled']),
            'tax_rate' => (float)($data['tax_rate'] ?? 0),
            'admin_user' => [
                'username' => $data['admin_username'],
                'password_hash' => password_hash($data['admin_password'], PASSWORD_BCRYPT),
                'role' => 'admin'
            ],
        ],
    ];

    $content = "<?php\nreturn " . var_export($config, true) . ";\n?>";
    return file_put_contents(__DIR__ . '/config.php', $content) !== false;
}

function runSqlFile($conn, $filePath, &$errors) {
    if (!file_exists($filePath)) {
        $errors[] = 'SQL file not found: ' . htmlspecialchars($filePath);
        return false;
    }
    $sql = file_get_contents($filePath);
    if ($sql === false) {
        $errors[] = 'Unable to read SQL file.';
        return false;
    }
    if ($conn->multi_query($sql)) {
        do {
            if ($result = $conn->store_result()) {
                $result->free();
            }
        } while ($conn->more_results() && $conn->next_result());
        return true;
    } else {
        $errors[] = 'SQL import error: ' . $conn->error;
        return false;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Try connecting to provided DB
    $db_host = $_POST['db_host'] ?? 'localhost';
    $db_user = $_POST['db_user'] ?? '';
    $db_pass = $_POST['db_pass'] ?? '';
    $db_name = $_POST['db_name'] ?? '';

    $testConn = @new mysqli($db_host, $db_user, $db_pass);
    if ($testConn->connect_error) {
        $setupErrors[] = 'Connection failed: ' . $testConn->connect_error;
    } else {
        // Create DB if not exists
        if (!$testConn->query("CREATE DATABASE IF NOT EXISTS `" . $testConn->real_escape_string($db_name) . "`")) {
            $setupErrors[] = 'Failed to create database: ' . $testConn->error;
        } else {
            $testConn->select_db($db_name);

            // Optionally import schema from provided SQL dump
            $importSchema = isset($_POST['import_schema']);
            if ($importSchema) {
                $defaultSqlPath = __DIR__ . DIRECTORY_SEPARATOR . 'cyberros_bizcashapp (1).sql';
                runSqlFile($testConn, $defaultSqlPath, $setupErrors);
            } else {
                // Minimal schema to get started
                $minimal = "CREATE TABLE IF NOT EXISTS clients (id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(255) NOT NULL, email VARCHAR(255) DEFAULT NULL, phone VARCHAR(50) DEFAULT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;\n" .
                    "CREATE TABLE IF NOT EXISTS invoices (id INT AUTO_INCREMENT PRIMARY KEY, client_id INT NOT NULL, invoice_id VARCHAR(50) UNIQUE NOT NULL, description TEXT DEFAULT NULL, amount DECIMAL(12,2) NOT NULL DEFAULT 0, status ENUM('Open','Closed') NOT NULL DEFAULT 'Open', payment_status ENUM('Paid','Unpaid') NOT NULL DEFAULT 'Unpaid', tithe_status ENUM('Paid','Unpaid') NOT NULL DEFAULT 'Unpaid', created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;\n" .
                    "CREATE TABLE IF NOT EXISTS transactions (id INT AUTO_INCREMENT PRIMARY KEY, invoice_id INT DEFAULT NULL, type ENUM('inflow','outflow') NOT NULL, amount DECIMAL(12,2) NOT NULL, description TEXT DEFAULT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE SET NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
                if (!$testConn->multi_query($minimal)) {
                    $setupErrors[] = 'Failed to create minimal schema: ' . $testConn->error;
                } else {
                    while ($testConn->more_results() && $testConn->next_result()) { /* flush */ }
                }
            }

            // Users table
            if (!createUsersTable($testConn)) {
                $setupErrors[] = 'Failed to create users table: ' . $testConn->error;
            } else {
                // Create admin user will be inserted on first login or explicitly here
            }

            // Write config
            $data = [
                'db_host' => $db_host,
                'db_user' => $db_user,
                'db_pass' => $db_pass,
                'db_name' => $db_name,
                'currency' => $_POST['currency'] ?? 'NGN',
                'country' => $_POST['country'] ?? 'Nigeria',
                'tax_enabled' => $_POST['tax_enabled'] ?? null,
                'tax_rate' => $_POST['tax_rate'] ?? '7.5',
                'admin_username' => $_POST['admin_username'] ?? 'admin',
                'admin_password' => $_POST['admin_password'] ?? '',
            ];
            if (!writeConfig($data)) {
                $setupErrors[] = 'Failed to write config.php. Ensure file permissions allow writing.';
            }
        }
        $testConn->close();
    }

    if (empty($setupErrors)) {
        $setupSuccess = 'Setup completed. You can now log in.';
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BizCashApp Setup Wizard</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .setup-container { max-width: 800px; margin: 24px auto; background: #1f1f1f; padding: 24px; border-radius: 8px; }
        .setup-container h1 { margin-top: 0; }
        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
        .note { font-size: 0.9em; color: #aaa; }
        .error { background: #3b1f1f; color: #ff9e9e; padding: 8px; border-radius: 6px; margin-bottom: 8px; }
        .success { background: #1f3b24; color: #b7ffb7; padding: 8px; border-radius: 6px; margin-bottom: 8px; }
        label { display: block; margin-bottom: 6px; }
        input[type="text"], input[type="password"], input[type="number"], select { width: 100%; padding: 8px; border-radius: 6px; border: 1px solid #333; background: #252525; color: #eee; }
        .btn { background: #2a5bd7; color: #fff; border: none; padding: 10px 16px; border-radius: 6px; cursor: pointer; }
        .btn-secondary { background: #444; }
        .section { margin-bottom: 16px; }
    </style>
</head>
<body>
<div class="setup-container">
    <h1>BizCashApp Setup</h1>
    <p class="note">Provide your database credentials, admin user, and app settings. You may import the full schema from the provided SQL dump.</p>

    <?php if (!empty($setupErrors)): ?>
        <?php foreach ($setupErrors as $e): ?>
            <div class="error"><?= htmlspecialchars($e) ?></div>
        <?php endforeach; ?>
    <?php endif; ?>
    <?php if ($setupSuccess): ?>
        <div class="success"><?= htmlspecialchars($setupSuccess) ?></div>
        <p><a class="btn" href="login.php">Proceed to Login</a></p>
    <?php endif; ?>

    <form method="post">
        <div class="section">
            <h2>Database</h2>
            <div class="grid-2">
                <div>
                    <label>Host</label>
                    <input type="text" name="db_host" value="<?= htmlspecialchars($_POST['db_host'] ?? 'localhost') ?>" required>
                </div>
                <div>
                    <label>User</label>
                    <input type="text" name="db_user" value="<?= htmlspecialchars($_POST['db_user'] ?? '') ?>" required>
                </div>
                <div>
                    <label>Password</label>
                    <input type="password" name="db_pass" value="<?= htmlspecialchars($_POST['db_pass'] ?? '') ?>" required>
                </div>
                <div>
                    <label>Database Name</label>
                    <input type="text" name="db_name" value="<?= htmlspecialchars($_POST['db_name'] ?? '') ?>" required>
                </div>
            </div>
            <label><input type="checkbox" name="import_schema" <?= isset($_POST['import_schema']) ? 'checked' : '' ?>> Import full schema from "cyberros_bizcashapp (1).sql"</label>
        </div>

        <div class="section">
            <h2>Admin Account</h2>
            <div class="grid-2">
                <div>
                    <label>Admin Username</label>
                    <input type="text" name="admin_username" value="<?= htmlspecialchars($_POST['admin_username'] ?? 'admin') ?>" required>
                </div>
                <div>
                    <label>Admin Password</label>
                    <input type="password" name="admin_password" value="<?= htmlspecialchars($_POST['admin_password'] ?? '') ?>" required>
                </div>
            </div>
        </div>

        <div class="section">
            <h2>App Settings</h2>
            <div class="grid-2">
                <div>
                    <label>Currency</label>
                    <select name="currency">
                        <?php $cur = $_POST['currency'] ?? 'NGN'; ?>
                        <option value="NGN" <?= $cur==='NGN'?'selected':'' ?>>NGN (₦)</option>
                        <option value="USD" <?= $cur==='USD'?'selected':'' ?>>USD ($)</option>
                        <option value="EUR" <?= $cur==='EUR'?'selected':'' ?>>EUR (€)</option>
                        <option value="GBP" <?= $cur==='GBP'?'selected':'' ?>>GBP (£)</option>
                    </select>
                </div>
                <div>
                    <label>Country</label>
                    <select name="country">
                        <?php $country = $_POST['country'] ?? 'Nigeria'; ?>
                        <option value="Nigeria" <?= $country==='Nigeria'?'selected':'' ?>>Nigeria</option>
                        <option value="Ghana" <?= $country==='Ghana'?'selected':'' ?>>Ghana</option>
                        <option value="Kenya" <?= $country==='Kenya'?'selected':'' ?>>Kenya</option>
                        <option value="Other" <?= $country==='Other'?'selected':'' ?>>Other</option>
                    </select>
                </div>
                <div>
                    <label><input type="checkbox" name="tax_enabled" <?= isset($_POST['tax_enabled']) ? 'checked' : '' ?>> Enable VAT/Tax</label>
                </div>
                <div>
                    <label>Tax Rate (%)</label>
                    <input type="number" step="0.1" name="tax_rate" value="<?= htmlspecialchars($_POST['tax_rate'] ?? '7.5') ?>">
                    <div class="note">Nigeria VAT defaults to 7.5%. If country is not Nigeria and tax is enabled, set your percentage.</div>
                </div>
            </div>
        </div>

        <button class="btn" type="submit">Run Setup</button>
        <a class="btn btn-secondary" href="index.php">Cancel</a>
    </form>
</div>
</body>
</html>