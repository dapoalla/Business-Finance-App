<?php
// transactions.php
require_once 'header.php';

// Handle Delete Transaction
if (isset($_GET['delete_id'])) {
    $id_to_delete = intval($_GET['delete_id']);
    $stmt = $conn->prepare("DELETE FROM transactions WHERE id = ?");
    $stmt->bind_param("i", $id_to_delete);
    if ($stmt->execute()) {
        echo "<script>alert('Transaction deleted successfully'); window.location.href='transactions.php';</script>";
    } else {
        echo "<script>alert('Error deleting transaction: " . $conn->error . "'); window.location.href='transactions.php';</script>";
    }
    exit;
}

// Handle Add/Update Transaction
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Shared variables
    $invoice_id = intval($_POST['invoice_id']);
    $type = $conn->real_escape_string($_POST['type']);
    $amount = floatval($_POST['amount']);
    $description = $conn->real_escape_string($_POST['description']);
    $transaction_date = $conn->real_escape_string($_POST['transaction_date']);

    // Check if it's an update or a new addition
    if (isset($_POST['update_transaction'])) {
        // Handle Update
        $transaction_id = intval($_POST['transaction_id']);
        $sql = "UPDATE transactions SET invoice_id=?, type=?, amount=?, description=?, transaction_date=? WHERE id=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("isdssi", $invoice_id, $type, $amount, $description, $transaction_date, $transaction_id);
        if ($stmt->execute()) {
            echo "<script>alert('Transaction updated successfully'); window.location.href='transactions.php';</script>";
        } else {
            echo "<script>alert('Error updating transaction: " . $stmt->error . "');</script>";
        }
    } else {
        // Handle Add
        $sql = "INSERT INTO transactions (invoice_id, type, amount, description, transaction_date) VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("isdss", $invoice_id, $type, $amount, $description, $transaction_date);
        if ($stmt->execute()) {
            echo "<script>alert('Transaction added successfully'); window.location.href='transactions.php?invoice_id=$invoice_id';</script>";
        } else {
            echo "<script>alert('Error adding transaction: " . $stmt->error . "');</script>";
        }
    }
    exit;
}

// Check if we are in edit mode
$edit_mode = false;
$edit_transaction = null;
if (isset($_GET['edit_id'])) {
    $id_to_edit = intval($_GET['edit_id']);
    $result = $conn->query("SELECT * FROM transactions WHERE id = $id_to_edit");
    if ($result->num_rows == 1) {
        $edit_mode = true;
        $edit_transaction = $result->fetch_assoc();
    }
}


// Check if we are viewing a specific invoice
$invoice_filter_id = isset($_GET['invoice_id']) ? intval($_GET['invoice_id']) : 0;
$sql_where = $invoice_filter_id ? "WHERE t.invoice_id = $invoice_filter_id" : "";

?>

<div class="container">
    <div class="page-header">
        <h1>Manage Transactions</h1>
        <?php if ($invoice_filter_id): ?>
            <a href="transactions.php" class="btn btn-secondary">Show All Transactions</a>
        <?php endif; ?>
    </div>

    <div class="form-container">
        <h2><?php echo $edit_mode ? 'Edit Transaction' : 'Add New Transaction'; ?></h2>
        <form action="transactions.php" method="post">
            <?php if ($edit_mode): ?>
                <input type="hidden" name="transaction_id" value="<?php echo $edit_transaction['id']; ?>">
            <?php endif; ?>
            <div class="form-row">
                <div class="form-group">
                    <label for="invoice_id">For Invoice</label>
                    <select name="invoice_id" id="invoice_id" required>
                        <option value="">Select an Invoice</option>
                        <?php
                        $inv_sql = "SELECT id, invoice_display_id FROM invoices ORDER BY id DESC";
                        $inv_result = $conn->query($inv_sql);
                        while($inv = $inv_result->fetch_assoc()) {
                            $current_invoice_id = $edit_mode ? $edit_transaction['invoice_id'] : $invoice_filter_id;
                            $selected = ($current_invoice_id == $inv['id']) ? 'selected' : '';
                            $display_id = htmlspecialchars($inv['invoice_display_id'] ?: '#' . $inv['id']);
                            echo "<option value='" . $inv['id'] . "' $selected>" . $display_id . "</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="type">Type</label>
                    <select name="type" id="type" required>
                        <option value="Inflow" <?php echo ($edit_mode && $edit_transaction['type'] == 'Inflow') ? 'selected' : ''; ?>>Inflow</option>
                        <option value="Outflow" <?php echo ($edit_mode && $edit_transaction['type'] == 'Outflow') ? 'selected' : ''; ?>>Outflow</option>
                    </select>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label for="amount">Amount (₦)</label>
                    <input type="number" name="amount" id="amount" step="0.01" value="<?php echo $edit_mode ? $edit_transaction['amount'] : ''; ?>" required>
                </div>
                <div class="form-group">
                    <label for="transaction_date">Date</label>
                    <input type="date" name="transaction_date" id="transaction_date" value="<?php echo $edit_mode ? $edit_transaction['transaction_date'] : date('Y-m-d'); ?>" required>
                </div>
            </div>
            <div class="form-group">
                <label for="description">Description</label>
                <input type="text" name="description" id="description" value="<?php echo $edit_mode ? htmlspecialchars($edit_transaction['description']) : ''; ?>" required>
            </div>
            
            <?php if ($edit_mode): ?>
                <button type="submit" name="update_transaction" class="btn">Update Transaction</button>
                <a href="transactions.php" class="btn btn-secondary">Cancel Edit</a>
            <?php else: ?>
                <button type="submit" name="add_transaction" class="btn">Add Transaction</button>
            <?php endif; ?>
        </form>
    </div>

    <div class="table-wrapper">
        <?php 
        if ($invoice_filter_id && !$edit_mode) {
            $inv_res = $conn->query("SELECT invoice_display_id FROM invoices WHERE id = $invoice_filter_id");
            $inv_row = $inv_res->fetch_assoc();
            $display_id = htmlspecialchars($inv_row['invoice_display_id'] ?: '#' . $invoice_filter_id);
            echo "<h2>Transactions for Invoice $display_id</h2>";
        }
        ?>
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Invoice ID</th>
                    <th>Type</th>
                    <th>Description</th>
                    <th>Amount</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $sql = "
                    SELECT t.id, t.transaction_date, t.invoice_id, i.invoice_display_id, t.type, t.description, t.amount 
                    FROM transactions t
                    JOIN invoices i ON t.invoice_id = i.id
                    $sql_where
                    ORDER BY t.transaction_date DESC, t.id DESC
                ";
                $result = $conn->query($sql);

                if ($result->num_rows > 0) {
                    while($row = $result->fetch_assoc()) {
                        $amount_style = $row['type'] == 'Inflow' ? 'color: var(--secondary-accent);' : 'color: var(--primary-accent);';
                        $display_id = htmlspecialchars($row['invoice_display_id'] ?: '#' . $row['invoice_id']);
                        echo "<tr>";
                        echo "<td>" . $row["transaction_date"] . "</td>";
                        echo "<td><a href='invoice_detail.php?id=" . $row["invoice_id"] . "' target='_blank'>" . $display_id . "</a></td>";
                        echo "<td>" . htmlspecialchars($row["type"]) . "</td>";
                        echo "<td>" . htmlspecialchars($row["description"]) . "</td>";
                        echo "<td style='$amount_style'>₦" . number_format($row["amount"], 2) . "</td>";
                        echo "<td>";
                        echo "<a href='transactions.php?edit_id=" . $row["id"] . "' class='btn btn-small'>Edit</a>";
                        echo "<a href='transactions.php?delete_id=" . $row["id"] . "' class='btn btn-small' onclick='return confirm(\"Are you sure you want to delete this transaction?\");'>Delete</a>";
                        echo "</td>";
                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='6'>No transactions found.</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once 'footer.php'; ?>