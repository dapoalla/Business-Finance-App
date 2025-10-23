<?php
// invoices.php
require_once 'header.php';

// --- Helper function to generate client abbreviation ---
function generate_client_abbr($client_name) {
    $client_name = trim(strtoupper($client_name));
    $words = preg_split("/[\s,-]+/", $client_name);
    $abbr = '';
    if (count($words) >= 3) {
        $abbr = substr($words[0], 0, 1) . substr($words[1], 0, 1) . substr($words[2], 0, 1);
    } elseif (strlen($client_name) >= 3) {
        $abbr = substr($client_name, 0, 3);
    } else {
        $abbr = str_pad($client_name, 3, 'X');
    }
    return preg_replace("/[^A-Z]/", "", $abbr); // Sanitize
}

// Handle Add Invoice
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_invoice'])) {
    $client_id = intval($_POST['client_id']);
    $invoice_date_str = $conn->real_escape_string($_POST['invoice_date']);
    $description = $conn->real_escape_string($_POST['description']);
    $invoice_date = new DateTime($invoice_date_str);

    // --- Generate New Invoice ID ---
    $client_res = $conn->query("SELECT name FROM clients WHERE id = $client_id");
    $client_name = $client_res->fetch_assoc()['name'];
    $client_abbr = generate_client_abbr($client_name);
    $date_part = $invoice_date->format('dmy');
    $seq_res = $conn->query("SELECT COUNT(id) as count FROM invoices WHERE client_id = $client_id");
    $client_invoice_count = $seq_res->fetch_assoc()['count'] + 1;
    $sequence_part = str_pad($client_invoice_count, 3, '0', STR_PAD_LEFT);
    $invoice_display_id = "{$date_part}-INV-{$client_abbr}-{$sequence_part}";
    if (!empty($description)) {
        $desc_part = preg_replace('/[^a-zA-Z0-9]/', '', $description);
        $invoice_display_id .= "_" . substr($desc_part, 0, 15);
    }
    // --- End Invoice ID Generation ---

    $sql = "INSERT INTO invoices (client_id, invoice_date, description, status, payment_status, tithe_status, invoice_display_id) VALUES (?, ?, ?, 'Open', 'Unpaid', 'Untithed', ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("isss", $client_id, $invoice_date_str, $description, $invoice_display_id);

    if ($stmt->execute()) {
        echo "<script>alert('Invoice created successfully'); window.location.href='invoices.php';</script>";
    } else {
        echo "<script>alert('Error: " . $stmt->error . "');</script>";
    }
    exit;
}

// Handle Status Toggle
if (isset($_GET['toggle_status'])) {
    $id = intval($_GET['id']);
    $status_type = $_GET['toggle_status'];
    $result = $conn->query("SELECT status, payment_status, tithe_status FROM invoices WHERE id=$id");
    $invoice = $result->fetch_assoc();
    $new_status = '';
    $column = '';

    switch ($status_type) {
        case 'status':
            $column = 'status';
            $new_status = ($invoice['status'] == 'Open') ? 'Completed' : 'Open';
            break;
        case 'payment':
            $column = 'payment_status';
            $new_status = ($invoice['payment_status'] == 'Unpaid') ? 'Paid' : 'Unpaid';
            break;
        case 'tithe':
            $column = 'tithe_status';
            $new_status = ($invoice['tithe_status'] == 'Untithed') ? 'Tithed' : 'Untithed';
            break;
    }

    if ($column) {
        $stmt = $conn->prepare("UPDATE invoices SET $column = ? WHERE id = ?");
        $stmt->bind_param("si", $new_status, $id);
        $stmt->execute();
        header("Location: invoices.php");
        exit();
    }
}

// Handle Delete Invoice
if (isset($_GET['delete_id'])) {
    $id_to_delete = intval($_GET['delete_id']);
    
    // Use a transaction to ensure both operations succeed or fail together
    $conn->begin_transaction();
    try {
        // First, delete associated transactions
        $stmt1 = $conn->prepare("DELETE FROM transactions WHERE invoice_id = ?");
        $stmt1->bind_param("i", $id_to_delete);
        $stmt1->execute();
        
        // Second, delete the invoice itself
        $stmt2 = $conn->prepare("DELETE FROM invoices WHERE id = ?");
        $stmt2->bind_param("i", $id_to_delete);
        $stmt2->execute();
        
        // If both are successful, commit the changes
        $conn->commit();
        echo "<script>alert('Invoice and all its transactions have been deleted.'); window.location.href='invoices.php';</script>";
    } catch (mysqli_sql_exception $exception) {
        $conn->rollback();
        echo "<script>alert('Error deleting invoice: " . $exception->getMessage() . "'); window.location.href='invoices.php';</script>";
    }
    exit;
}

?>

<div class="container">
    <div class="page-header">
        <h1>Manage Invoices</h1>
    </div>

    <div class="form-container">
        <h2>Create New Invoice</h2>
        <form action="invoices.php" method="post">
            <div class="form-row">
                <div class="form-group">
                    <label for="client_id">Client</label>
                    <select name="client_id" id="client_id" required>
                        <option value="">Select a Client</option>
                        <?php
                        $client_result = $conn->query("SELECT id, name FROM clients ORDER BY name");
                        while($client = $client_result->fetch_assoc()) {
                            echo "<option value='" . $client['id'] . "'>" . htmlspecialchars($client['name']) . "</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="invoice_date">Invoice Date</label>
                    <input type="date" name="invoice_date" id="invoice_date" value="<?php echo date('Y-m-d'); ?>" required>
                </div>
            </div>
            <div class="form-group">
                <label for="description">Description (Used in ID)</label>
                <input type="text" name="description" id="description">
            </div>
            <button type="submit" name="add_invoice" class="btn">Create Invoice</button>
        </form>
    </div>

    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>Invoice ID</th>
                    <th>Client</th>
                    <th>P/L</th>
                    <th>Status</th>
                    <th>Payment</th>
                    <th>Tithe</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $sql = "
                    SELECT 
                        i.id, 
                        i.invoice_display_id,
                        c.name as client_name, 
                        i.status, 
                        i.payment_status, 
                        i.tithe_status,
                        (SELECT COALESCE(SUM(CASE WHEN type = 'Inflow' THEN amount ELSE -amount END), 0) FROM transactions WHERE invoice_id = i.id) as profit
                    FROM invoices i
                    JOIN clients c ON i.client_id = c.id
                    ORDER BY i.invoice_date DESC, i.id DESC
                ";
                $result = $conn->query($sql);

                if ($result->num_rows > 0) {
                    while($row = $result->fetch_assoc()) {
                        echo "<tr>";
                        echo "<td>" . htmlspecialchars($row['invoice_display_id'] ?: '#' . $row['id']) . "</td>";
                        echo "<td>" . htmlspecialchars($row["client_name"]) . "</td>";
                        echo "<td>₦" . number_format($row["profit"], 2) . "</td>";
                        echo "<td><span class='status status-" . strtolower($row['status']) . "'>" . $row['status'] . "</span></td>";
                        echo "<td><span class='status status-" . strtolower($row['payment_status']) . "'>" . $row['payment_status'] . "</span></td>";
                        echo "<td><span class='status status-" . strtolower($row['tithe_status']) . "'>" . $row['tithe_status'] . "</span></td>";
                        echo "<td>";
                        // Status Toggles
                        echo "<a href='invoices.php?id=" . $row["id"] . "&toggle_status=status' class='btn btn-small'>Status</a>";
                        echo "<a href='invoices.php?id=" . $row["id"] . "&toggle_status=payment' class='btn btn-small'>Payment</a>";
                        if ($row['status'] == 'Completed' && $row['profit'] > 0) {
                            echo "<a href='invoices.php?id=" . $row["id"] . "&toggle_status=tithe' class='btn btn-small'>Tithe</a>";
                        }
                        // View Transactions
                        echo "<a href='transactions.php?invoice_id=" . $row["id"] . "' class='btn btn-small btn-secondary'>Txns</a>";
                        // Edit and Delete
                        echo "<a href='edit_invoice.php?id=" . $row["id"] . "' class='btn btn-small'>Edit</a>";
                        echo "<a href='invoices.php?delete_id=" . $row["id"] . "' class='btn btn-small' onclick='return confirm(\"Are you sure? Deleting an invoice will also delete ALL associated transactions.\");'>Delete</a>";
                        echo "</td>";
                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='7'>No invoices found.</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once 'footer.php'; ?>

<?php
require_once 'db.php';
include 'header.php';

// Handle invoice creation and actions (existing logic remains)

// Update: Link each invoice to a detail page in a new tab
// Render invoices table (existing code adapted)

// Fetch invoices (existing fetch block retained)
?>
<div class="container">
    <h2>Invoices</h2>
    <?php
    // Fetch invoices with client names
    $sql = "SELECT invoices.*, clients.name AS client_name FROM invoices LEFT JOIN clients ON invoices.client_id = clients.id ORDER BY invoices.created_at DESC";
    $result = $conn->query($sql);
    ?>
    <table>
        <thead>
            <tr>
                <th>Invoice</th>
                <th>Client</th>
                <th>Description</th>
                <th>Amount</th>
                <th>Status</th>
                <th>Payment</th>
                <th>Tithe</th>
                <th>Created</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php if ($result && $result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td>
                        <a href="invoice_detail.php?id=<?= (int)$row['id'] ?>" target="_blank" title="Open invoice in new tab">
                            <?= htmlspecialchars($row['invoice_id']) ?>
                        </a>
                    </td>
                    <td><?= htmlspecialchars($row['client_name'] ?? 'Unknown') ?></td>
                    <td><?= htmlspecialchars($row['description']) ?></td>
                    <td><?= number_format((float)$row['amount'], 2) ?></td>
                    <td><?= htmlspecialchars($row['status']) ?></td>
                    <td><?= htmlspecialchars($row['payment_status']) ?></td>
                    <td><?= htmlspecialchars($row['tithe_status']) ?></td>
                    <td><?= htmlspecialchars($row['created_at']) ?></td>
                    <td>
                        <!-- Keep existing actions if any -->
                        <a href="invoice_detail.php?id=<?= (int)$row['id'] ?>" target="_blank">View</a>
                    </td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr><td colspan="9">No invoices found.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>