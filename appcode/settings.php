<?php
require_once 'db.php';
include 'header.php';

$configPath = __DIR__ . '/config.php';
$config = include $configPath;
$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $config['app']['currency'] = $_POST['currency'] ?? $config['app']['currency'];
    $config['app']['country'] = $_POST['country'] ?? $config['app']['country'];
    $config['app']['tax_enabled'] = isset($_POST['tax_enabled']);
    $config['app']['tax_rate'] = (float)($_POST['tax_rate'] ?? $config['app']['tax_rate']);

    // Write back to config.php
    $content = "<?php\nreturn " . var_export($config, true) . ";\n?>";
    if (file_put_contents($configPath, $content) !== false) {
        $msg = 'Settings updated.';
    } else {
        $msg = 'Failed to update settings. Check file permissions.';
    }
}

$currency = $config['app']['currency'] ?? 'NGN';
$country = $config['app']['country'] ?? 'Nigeria';
$taxEnabled = $config['app']['tax_enabled'] ?? false;
$taxRate = $config['app']['tax_rate'] ?? 7.5;
?>
<div class="container">
    <h2>Settings</h2>
    <?php if ($msg): ?><p style="color:#b7ffb7;"><?= htmlspecialchars($msg) ?></p><?php endif; ?>

    <form method="post">
        <label>Currency
            <select name="currency">
                <option value="NGN" <?= $currency==='NGN'?'selected':'' ?>>NGN (₦)</option>
                <option value="USD" <?= $currency==='USD'?'selected':'' ?>>USD ($)</option>
                <option value="EUR" <?= $currency==='EUR'?'selected':'' ?>>EUR (€)</option>
                <option value="GBP" <?= $currency==='GBP'?'selected':'' ?>>GBP (£)</option>
            </select>
        </label>
        <label>Country
            <select name="country">
                <option value="Nigeria" <?= $country==='Nigeria'?'selected':'' ?>>Nigeria</option>
                <option value="Ghana" <?= $country==='Ghana'?'selected':'' ?>>Ghana</option>
                <option value="Kenya" <?= $country==='Kenya'?'selected':'' ?>>Kenya</option>
                <option value="Other" <?= $country==='Other'?'selected':'' ?>>Other</option>
            </select>
        </label>
        <label><input type="checkbox" name="tax_enabled" <?= $taxEnabled ? 'checked' : '' ?>> Enable VAT/Tax</label>
        <label>Tax Rate (%)
            <input type="number" step="0.1" name="tax_rate" value="<?= htmlspecialchars($taxRate) ?>">
            <div class="note">Nigeria VAT defaults to 7.5%. If not Nigeria and tax is enabled, set your percentage.</div>
        </label>
        <button type="submit">Save Settings</button>
    </form>
</div>
<?php require_once 'footer.php'; ?>