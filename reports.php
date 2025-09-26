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

// Fetch counts
$total_clients = $conn->query("SELECT COUNT(*) AS total FROM clients")->fetch_assoc()['total'];
$active_clients = $conn->query("SELECT COUNT(*) AS total FROM clients WHERE status='Active'")->fetch_assoc()['total'];
$inactive_clients = $conn->query("SELECT COUNT(*) AS total FROM clients WHERE status='Inactive'")->fetch_assoc()['total'];

// Clients per month (last 6 months)
$monthly_data = [];
for ($i = 5; $i >= 0; $i--) {
    $month = date('Y-m', strtotime("-$i months"));
    $start = $month . "-01";
    $end = date('Y-m-t', strtotime($start));
    $stmt = $conn->prepare("SELECT COUNT(*) AS total FROM clients WHERE created_at BETWEEN ? AND ?");
    $stmt->bind_param("ss", $start, $end);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    $monthly_data[$month] = $res['total'];
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>CRM - Reports</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
<div class="container">
    <h1>Reports & Analytics</h1>
	<p><a href="client_management.php">&larr; Back to Dashboard</a></p>

    <div class="card">
        <h2>Client Summary</h2>
        <p>Total Clients: <?= $total_clients ?></p>
        <p>Active Clients: <?= $active_clients ?></p>
        <p>Inactive Clients: <?= $inactive_clients ?></p>
    </div>

    <div class="card">
        <h2>Clients Per Month</h2>
        <canvas id="monthlyChart"></canvas>
    </div>

    <div class="card">
        <h2>Status Breakdown</h2>
        <canvas id="statusChart"></canvas>
    </div>
</div>

<script>
const monthlyChart = document.getElementById('monthlyChart').getContext('2d');
new Chart(monthlyChart, {
    type: 'bar',
    data: {
        labels: <?= json_encode(array_keys($monthly_data)) ?>,
        datasets: [{
            label: 'Clients Added',
            data: <?= json_encode(array_values($monthly_data)) ?>,
            backgroundColor: 'rgba(54, 162, 235, 0.6)'
        }]
    }
});

const statusChart = document.getElementById('statusChart').getContext('2d');
new Chart(statusChart, {
    type: 'pie',
    data: {
        labels: ['Active', 'Inactive'],
        datasets: [{
            label: 'Client Status',
            data: [<?= $active_clients ?>, <?= $inactive_clients ?>],
            backgroundColor: ['#4CAF50', '#F44336']
        }]
    }
});
</script>
</body>
</html>
