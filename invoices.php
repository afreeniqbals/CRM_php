<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$conn = new mysqli("localhost", "root", "", "crm_db");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$client_id = $_GET['client_id'] ?? null;
if (!$client_id) {
    echo "Client ID is missing!";
    exit();
}

// Add Invoice
if (isset($_POST['add_invoice'])) {
    $amount = $_POST['amount'];
    $invoice_date = $_POST['invoice_date'];
    $due_date = $_POST['due_date'];
    $description = $_POST['description'];

    $stmt = $conn->prepare("INSERT INTO invoices (client_id, amount, invoice_date, due_date, description) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("idsss", $client_id, $amount, $invoice_date, $due_date, $description);
    $stmt->execute();
    $stmt->close();
}

// Record Payment
if (isset($_POST['record_payment'])) {
    $invoice_id = $_POST['invoice_id'];
    $payment_date = $_POST['payment_date'];
    $amount_paid = $_POST['amount_paid'];
    $payment_method = $_POST['payment_method'];

    // Check if invoice ID belongs to this client
    $check = $conn->prepare("SELECT id FROM invoices WHERE id = ? AND client_id = ?");
    $check->bind_param("ii", $invoice_id, $client_id);
    $check->execute();
    $check_result = $check->get_result();
    if ($check_result->num_rows === 0) {
        $payment_error = "Invalid invoice ID for this client.";
    } else {
        $stmt = $conn->prepare("INSERT INTO payments (invoice_id, payment_date, amount_paid, payment_method) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("isds", $invoice_id, $payment_date, $amount_paid, $payment_method);
        $stmt->execute();
        $stmt->close();
    }
}

// Fetch Invoices
$invoices = $conn->query("SELECT * FROM invoices WHERE client_id = $client_id");

// Fetch Summary
$total_invoices = $conn->query("SELECT COUNT(*) AS total FROM invoices WHERE client_id = $client_id")->fetch_assoc()['total'];
$total_paid = $conn->query("
    SELECT SUM(p.amount_paid) AS paid 
    FROM payments p 
    INNER JOIN invoices i ON p.invoice_id = i.id 
    WHERE i.client_id = $client_id
")->fetch_assoc()['paid'] ?? 0;
?>
<!DOCTYPE html>
<html>
<head>
    <title>Invoices for Client</title>
    <link rel="stylesheet" href="invoices.css">
</head>
<body>
<div class="container">
    <a href="client_management.php" class="back-button">← Back to Clients</a>
    <h1>Invoices for Client ID: <?= $client_id ?></h1>

    <!-- Summary -->
    <div class="card">
        <h3>Invoice Summary</h3>
        <p>Total Invoices: <?= $total_invoices ?></p>
        <p>Total Paid: ₹<?= number_format($total_paid, 2) ?></p>
    </div>

    <!-- Add Invoice -->
    <div class="card">
        <h2>Add New Invoice</h2>
        <form method="POST">
            <label>Amount</label>
            <input type="number" step="0.01" name="amount" required>
            <label>Invoice Date</label>
            <input type="date" name="invoice_date" required>
            <label>Due Date</label>
            <input type="date" name="due_date" required>
            <label>Description</label>
            <textarea name="description" required></textarea>
            <button type="submit" name="add_invoice">Add Invoice</button>
        </form>
    </div>

    <!-- Record Payment -->
    <div class="card">
        <h2>Record Payment</h2>
        <?php if (isset($payment_error)) echo "<p style='color:red;'>$payment_error</p>"; ?>
        <form method="POST">
            <label>Invoice ID</label>
            <select name="invoice_id" required>
                <option value="">Select Invoice</option>
                <?php
                $invoice_list = $conn->query("SELECT id FROM invoices WHERE client_id = $client_id");
                while ($inv = $invoice_list->fetch_assoc()) {
                    echo "<option value='{$inv['id']}'>Invoice #{$inv['id']}</option>";
                }
                ?>
            </select>
            <label>Payment Date</label>
            <input type="date" name="payment_date" required>
            <label>Amount Paid</label>
            <input type="number" step="0.01" name="amount_paid" required>
            <label>Payment Method</label>
            <input type="text" name="payment_method">
            <button type="submit" name="record_payment">Record Payment</button>
        </form>
    </div>

    <!-- Invoice List -->
    <div class="card">
        <h2>All Invoices</h2>
        <table>
            <thead>
                <tr>
                    <th>ID</th><th>Amount</th><th>Invoice Date</th><th>Due Date</th><th>Description</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if ($invoices->num_rows > 0) {
                    while ($row = $invoices->fetch_assoc()) {
                        echo "<tr>
                                <td>{$row['id']}</td>
                                <td>₹" . number_format($row['amount'], 2) . "</td>
                                <td>{$row['invoice_date']}</td>
                                <td>{$row['due_date']}</td>
                                <td>{$row['description']}</td>
                              </tr>";
                    }
                } else {
                    echo "<tr><td colspan='5'>No invoices found.</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>
