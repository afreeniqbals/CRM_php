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

$client_id = isset($_GET['client_id']) ? intval($_GET['client_id']) : 0;

// Get client info
$client = null;
if ($client_id > 0) {
    $stmt = $conn->prepare("SELECT name, email FROM clients WHERE id = ?");
    $stmt->bind_param("i", $client_id);
    $stmt->execute();
    $client_result = $stmt->get_result();
    $client = $client_result->fetch_assoc();
    $stmt->close();
}

// Handle log addition
if (isset($_POST['add_log'])) {
    $note = trim($_POST['note']);
    if (!empty($note)) {
        $stmt = $conn->prepare("INSERT INTO communication_logs (client_id, note) VALUES (?, ?)");
        $stmt->bind_param("is", $client_id, $note);
        $stmt->execute();
        $stmt->close();
    }
}

// Get logs
$logs = [];
$stmt = $conn->prepare("SELECT id, note, created_at FROM communication_logs WHERE client_id = ? ORDER BY created_at DESC");
$stmt->bind_param("i", $client_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $logs[] = $row;
}
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Communication Logs</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
<div class="container">
    <h1>Communication Logs for <?= htmlspecialchars($client['name']) ?> (<?= htmlspecialchars($client['email']) ?>)</h1>

    <div class="card">
        <h2>Add New Log</h2>
        <form method="POST" action="">
            <div class="input-group">
                <label for="note">Note</label>
                <textarea name="note" id="note" rows="4" required></textarea>
            </div>
            <button type="submit" name="add_log">Add Log</button>
        </form>
    </div>

    <div class="card">
        <h2>Previous Logs</h2>
        <?php if (count($logs) > 0): ?>
            <ul>
                <?php foreach ($logs as $log): ?>
                    <li><strong><?= date("Y-m-d H:i", strtotime($log['created_at'])) ?>:</strong> <?= htmlspecialchars($log['note']) ?></li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p>No communication logs found.</p>
        <?php endif; ?>
    </div>

    <p><a href="client_management.php">&larr; Back to Dashboard</a></p>
</div>
</body>
</html>
