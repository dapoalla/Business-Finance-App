<?php
require_once 'db.php';
include 'header.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    echo '<div class="container"><p>Invalid invoice.</p></div>';
    require_once 'footer.php';
    exit;
}

// Update invoice fields
$updateMsg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $status = $_POST['status'] ?? 'Open';
    $payment_status = $_POST['payment_status'] ?? 'Unpaid';
    $tithe_status = $_POST['tithe_status'] ?? 'Unpaid';
    $description = $_POST['description'] ?? null;

    $stmt = $conn->prepare("UPDATE invoices SET status=?, payment_status=?, tithe_status=?, description=? WHERE id=?");
    $stmt->bind_param('ssssi', $status, $payment_status, $tithe_status, $description, $id);
    if ($stmt->execute()) {
        $updateMsg = 'Invoice updated.';
    } else {
        $updateMsg = 'Update failed: ' . $conn->error;
    }
    $stmt->close();
}

// Fetch invoice
$stmt = $conn->prepare("SELECT invoices.*, clients.name AS client_name FROM invoices LEFT JOIN clients ON invoices.client_id = clients.id WHERE invoices.id = ?");
$stmt->bind_param('i', $id);
$stmt->execute();
$invoice = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$invoice) {
    echo '<div class="container"><p>Invoice not found.</p></div>';
    require_once 'footer.php';
    exit;
}

// Fetch transactions tied to this invoice
$tres = $conn->prepare("SELECT * FROM transactions WHERE invoice_id = ? ORDER BY created_at DESC");
$tres->bind_param('i', $id);
$tres->execute();
$transactions = $tres->get_result();
$tres->close();

// Compute totals and profit
$inflow = 0; $outflow = 0;
if ($transactions && $transactions->num_rows > 0) {
    while ($t = $transactions->fetch_assoc()) {
        if ($t['type'] === 'inflow') $inflow += (float)$t['amount'];
        else $outflow += (float)$t['amount'];
    }
    // need to refetch result set for display
    $tres = $conn->prepare("SELECT * FROM transactions WHERE invoice_id = ? ORDER BY created_at DESC");
    $tres->bind_param('i', $id);
    $tres->execute();
    $transactions = $tres->get_result();
    $tres->close();
}
$profit = $inflow - $outflow;

$config = include __DIR__ . '/config.php';
$currency = $config['app']['currency'] ?? 'NGN';
?>
<div class="container">
    <h2>Invoice: <?= htmlspecialchars($invoice['invoice_id']) ?> — <?= htmlspecialchars($invoice['client_name'] ?? 'Unknown') ?></h2>
    <?php if ($updateMsg): ?><p style="color:#b7ffb7;"><?= htmlspecialchars($updateMsg) ?></p><?php endif; ?>

    <div class="cards">
        <div class="card">
            <h3>Status</h3>
            <p>Invoice: <?= htmlspecialchars($invoice['status']) ?></p>
            <p>Payment: <?= htmlspecialchars($invoice['payment_status']) ?></p>
            <p>Tithe: <?= htmlspecialchars($invoice['tithe_status']) ?></p>
        </div>
        <div class="card">
            <h3>Totals (<?= htmlspecialchars($currency) ?>)</h3>
            <p>Inflow: <?= number_format($inflow, 2) ?></p>
            <p>Outflow: <?= number_format($outflow, 2) ?></p>
            <p>Profit: <?= number_format($profit, 2) ?></p>
        </div>
        <div class="card">
            <h3>Description</h3>
            <p><?= nl2br(htmlspecialchars($invoice['description'])) ?></p>
        </div>
    </div>

    <h3>Edit Invoice</h3>
    <form method="post">
        <label>Status
            <select name="status">
                <?php $s = $invoice['status']; ?>
                <option value="Open" <?= $s==='Open'? 'selected':'' ?>>Open</option>
                <option value="Closed" <?= $s==='Closed'? 'selected':'' ?>>Closed</option>
            </select>
        </label>
        <label>Payment Status
            <select name="payment_status">
                <?php $ps = $invoice['payment_status']; ?>
                <option value="Paid" <?= $ps==='Paid'? 'selected':'' ?>>Paid</option>
                <option value="Unpaid" <?= $ps==='Unpaid'? 'selected':'' ?>>Unpaid</option>
            </select>
        </label>
        <label>Tithe Status
            <select name="tithe_status">
                <?php $ts = $invoice['tithe_status']; ?>
                <option value="Paid" <?= $ts==='Paid'? 'selected':'' ?>>Paid</option>
                <option value="Unpaid" <?= $ts==='Unpaid'? 'selected':'' ?>>Unpaid</option>
            </select>
        </label>
        <label>Description
            <textarea name="description" rows="3"><?= htmlspecialchars($invoice['description']) ?></textarea>
        </label>
        <button type="submit">Update</button>
        <a class="btn" href="transactions.php?invoice_id=<?= (int)$invoice['id'] ?>">Manage Transactions</a>
    </form>

    <h3>Transactions</h3>
    <table>
        <thead>
            <tr>
                <th>Type</th>
                <th>Amount (<?= htmlspecialchars($currency) ?>)</th>
                <th>Description</th>
                <th>Date</th>
            </tr>
        </thead>
        <tbody>
        <?php if ($transactions && $transactions->num_rows > 0): ?>
            <?php while ($t = $transactions->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($t['type']) ?></td>
                    <td><?= number_format((float)$t['amount'], 2) ?></td>
                    <td><?= htmlspecialchars($t['description']) ?></td>
                    <td><?= htmlspecialchars($t['created_at']) ?></td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr><td colspan="4">No transactions for this invoice.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>
<?php require_once 'footer.php'; ?>