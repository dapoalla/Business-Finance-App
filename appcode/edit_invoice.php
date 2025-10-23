<?php
// edit_invoice.php
require_once 'header.php';

// Check if an ID is provided
if (!isset($_GET['id'])) {
    header("Location: invoices.php");
    exit();
}

$invoice_id = intval($_GET['id']);

// Handle form submission for update
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_invoice'])) {
    $client_id = intval($_POST['client_id']);
    $invoice_date = $conn->real_escape_string($_POST['invoice_date']);
    $description = $conn->real_escape_string($_POST['description']);
    
    // Note: We are NOT regenerating the invoice_display_id to maintain consistency.
    // If client or date changes, the original ID based on creation date/client is kept.
    
    $sql = "UPDATE invoices SET client_id = ?, invoice_date = ?, description = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("issi", $client_id, $invoice_date, $description, $invoice_id);

    if ($stmt->execute()) {
        echo "<script>alert('Invoice updated successfully'); window.location.href='invoices.php';</script>";
    } else {
        echo "<script>alert('Error updating invoice: " . $stmt->error . "');</script>";
    }
    exit;
}


// Fetch the current invoice data
$result = $conn->query("SELECT * FROM invoices WHERE id = $invoice_id");
if ($result->num_rows != 1) {
    // Invoice not found
    header("Location: invoices.php");
    exit();
}
$invoice = $result->fetch_assoc();

?>

<div class="container">
    <div class="page-header">
        <h1>Edit Invoice</h1>
        <a href="invoices.php" class="btn btn-secondary">Back to Invoices</a>
    </div>

    <div class="form-container">
        <h2>Editing Invoice: <?php echo htmlspecialchars($invoice['invoice_display_id'] ?: '#' . $invoice['id']); ?></h2>
        <form action="edit_invoice.php?id=<?php echo $invoice_id; ?>" method="post">
            <div class="form-row">
                <div class="form-group">
                    <label for="client_id">Client</label>
                    <select name="client_id" id="client_id" required>
                        <option value="">Select a Client</option>
                        <?php
                        $client_result = $conn->query("SELECT id, name FROM clients ORDER BY name");
                        while($client = $client_result->fetch_assoc()) {
                            $selected = ($client['id'] == $invoice['client_id']) ? 'selected' : '';
                            echo "<option value='" . $client['id'] . "' $selected>" . htmlspecialchars($client['name']) . "</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="invoice_date">Invoice Date</label>
                    <input type="date" name="invoice_date" id="invoice_date" value="<?php echo htmlspecialchars($invoice['invoice_date']); ?>" required>
                </div>
            </div>
            <div class="form-group">
                <label for="description">Description (Used in ID)</label>
                <input type="text" name="description" id="description" value="<?php echo htmlspecialchars($invoice['description']); ?>">
            </div>
            <button type="submit" name="update_invoice" class="btn">Update Invoice</button>
        </form>
    </div>
</div>

<?php require_once 'footer.php'; ?>