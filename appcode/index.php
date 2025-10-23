<?php
// index.php
require_once 'header.php';

// --- CALCULATIONS ---

// Total Inflow
$inflow_result = $conn->query("SELECT SUM(amount) as total_inflow FROM transactions WHERE type = 'Inflow'");
$total_inflow = $inflow_result->fetch_assoc()['total_inflow'] ?? 0;

// Total Outflow
$outflow_result = $conn->query("SELECT SUM(amount) as total_outflow FROM transactions WHERE type = 'Outflow'");
$total_outflow = $outflow_result->fetch_assoc()['total_outflow'] ?? 0;

// Total Profit
$total_profit = $total_inflow - $total_outflow;

// Unpaid Invoices
$unpaid_invoices_result = $conn->query("SELECT COUNT(id) as unpaid_count FROM invoices WHERE payment_status = 'Unpaid'");
$unpaid_invoices_count = $unpaid_invoices_result->fetch_assoc()['unpaid_count'] ?? 0;

// Unpaid Tithes
$sql_tithe = "
    SELECT SUM(t.profit * 0.10) as total_unpaid_tithe
    FROM (
        SELECT 
            i.id,
            (SELECT COALESCE(SUM(amount), 0) FROM transactions WHERE invoice_id = i.id AND type = 'Inflow') - 
            (SELECT COALESCE(SUM(amount), 0) FROM transactions WHERE invoice_id = i.id AND type = 'Outflow') as profit
        FROM invoices i
        WHERE i.status = 'Completed' AND i.tithe_status = 'Untithed'
    ) as t
    WHERE t.profit > 0
";
$unpaid_tithe_result = $conn->query($sql_tithe);
$total_unpaid_tithe = $unpaid_tithe_result->fetch_assoc()['total_unpaid_tithe'] ?? 0;

?>

<div class="container">
    <div class="page-header">
        <h1>Dashboard</h1>
    </div>

    <div class="dashboard-grid">
        <div class="card">
            <h3>Total Inflow</h3>
            <p>₦<?php echo number_format($total_inflow, 2); ?></p>
        </div>
        <div class="card">
            <h3>Total Outflow</h3>
            <p>₦<?php echo number_format($total_outflow, 2); ?></p>
        </div>
        <div class="card">
            <h3>Total Profit</h3>
            <p>₦<?php echo number_format($total_profit, 2); ?></p>
        </div>
        <div class="card">
            <h3>Unpaid Invoices</h3>
            <p><?php echo $unpaid_invoices_count; ?></p>
        </div>
        <div class="card">
            <h3>Unpaid Tithes</h3>
            <p>₦<?php echo number_format($total_unpaid_tithe, 2); ?></p>
        </div>
    </div>

    <div class="page-header">
        <h2>Recent Invoices</h2>
        <a href="invoices.php" class="btn btn-secondary">View All Invoices</a>
    </div>

    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>Invoice ID</th>
                    <th>Client</th>
                    <th>Profit</th>
                    <th>Payment Status</th>
                    <th>Invoice Status</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $sql = "
                    SELECT 
                        i.id, 
                        i.invoice_display_id,
                        c.name as client_name, 
                        i.payment_status, 
                        i.status,
                        (SELECT COALESCE(SUM(CASE WHEN type = 'Inflow' THEN amount ELSE -amount END), 0) FROM transactions WHERE invoice_id = i.id) as profit
                    FROM invoices i
                    JOIN clients c ON i.client_id = c.id
                    ORDER BY i.invoice_date DESC
                    LIMIT 5
                ";
                $result = $conn->query($sql);
                if ($result->num_rows > 0) {
                    while($row = $result->fetch_assoc()) {
                        echo "<tr>";
                        echo "<td><a href='invoice_detail.php?id=" . (int)$row['id'] . "' target='_blank'>" . htmlspecialchars($row['invoice_display_id'] ?: '#' . $row['id']) . "</a></td>";
                        echo "<td>" . htmlspecialchars($row['client_name']) . "</td>";
                        echo "<td>₦" . number_format($row['profit'], 2) . "</td>";
                        echo "<td><span class='status status-" . strtolower(htmlspecialchars($row['payment_status'])) . "'>" . htmlspecialchars($row['payment_status']) . "</span></td>";
                        echo "<td><span class='status status-" . strtolower(htmlspecialchars($row['status'])) . "'>" . htmlspecialchars($row['status']) . "</span></td>";
                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='5'>No recent invoices found.</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once 'footer.php'; ?>

// Update: ensure recent invoices list links to invoice_detail in new tab
// Find rendering of recent invoices and change links
// The following replacement ensures the invoice ID links to invoice_detail
