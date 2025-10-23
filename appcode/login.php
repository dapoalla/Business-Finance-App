<?php
// login.php
require_once 'db.php';

$error = '';

// Hardcoded password for simplicity. In a real application, use hashed passwords from a database.
define('MASTER_PASSWORD', 'BizW!z2024');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['password']) && $_POST['password'] === MASTER_PASSWORD) {
        $_SESSION['loggedin'] = true;
        header("location: index.php");
        exit;
    } else {
        $error = "Invalid password.";
    }
}

if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
    header("location: index.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - BizCashApp</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="login-container">
        <div class="login-form">
            <h2>BizCashApp Login</h2>
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" name="password" id="password" required>
                </div>
                <?php if($error): ?>
                    <p style="color: var(--primary-accent); text-align: center;"><?php echo $error; ?></p>
                <?php endif; ?>
                <button type="submit" class="btn">Login</button>
            </form>
        </div>
    </div>
</body>
</html>
