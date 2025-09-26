<?php
// DB connection
$conn = new mysqli("localhost", "root", "", "crm_db");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (isset($_GET['client_id'])) {
    $client_id = $_GET['client_id'];

    // Fetch communication logs
    $result = $conn->query("SELECT * FROM client_communications WHERE client_id = $client_id ORDER BY communication_date DESC");

    $logs = [];
    while ($row = $result->fetch_assoc()) {
        $logs[] = $row;
    }

    echo json_encode($logs); // Return logs as JSON
}
?>
