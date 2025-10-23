<?php
// tithes.php
require_once 'header.php';
?>

<div class="container">
    <div class="page-header">
        <h1>Tithes Due</h1>
    </div>
    <p>This page shows 10% of the profit from all <strong>Completed</strong> invoices. You can mark a tithe as paid by toggling its status on the <a href="invoices.php" style="color: var(--primary-accent);">Invoices page</a>.</p>

    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>Invoice ID</th>
                    <th>Client</th>
                    <th>Invoice Profit</th>
                    <th>Tithe Amount (10%)</th>
                    <th>Tithe Status</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $sql = "
                    SELECT 
                        i.id,
                        i.invoice_display_id,
                        c.name as client_name,
                        i.tithe_status,
                        (
                            (SELECT COALESCE(SUM(amount), 0) FROM transactions WHERE invoice_id = i.id AND type = 'Inflow') - 
                            (SELECT COALESCE(SUM(amount), 0) FROM transactions WHERE invoice_id = i.id AND type = 'Outflow')
                        ) as profit
                    FROM invoices i
                    JOIN clients c ON i.client_id = c.id
                    WHERE i.status = 'Completed'
                    HAVING profit > 0
                    ORDER BY i.invoice_date DESC
                ";
                $result = $conn->query($sql);

                if ($result && $result->num_rows > 0) {
                    while($row = $result->fetch_assoc()) {
                        $tithe_amount = $row['profit'] * 0.10;
                        $display_id = htmlspecialchars($row['invoice_display_id'] ?: '#' . $row['id']);
                        echo "<tr>";
                        echo "<td>" . $display_id . "</td>";
                        echo "<td>" . htmlspecialchars($row["client_name"]) . "</td>";
                        echo "<td>₦" . number_format($row["profit"], 2) . "</td>";
                        echo "<td><strong>₦" . number_format($tithe_amount, 2) . "</strong></td>";
                        echo "<td><span class='status status-" . strtolower($row['tithe_status']) . "'>" . $row['tithe_status'] . "</span></td>";
                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='5'>No tithes due from completed invoices.</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once 'footer.php'; ?>
