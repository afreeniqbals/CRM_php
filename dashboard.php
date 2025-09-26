<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "crm_db";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Add Client
if (isset($_POST['add_client'])) {
    $client_name = $_POST['client_name'];
    $client_email = $_POST['client_email'];
    $client_status = $_POST['client_status'];

    if (!empty($client_name) && !empty($client_email) && !empty($client_status)) {
        $stmt = $conn->prepare("INSERT INTO clients (name, email, status) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $client_name, $client_email, $client_status);
        $stmt->execute();
        $stmt->close();
    }
}

// Delete Client
if (isset($_GET['delete_id'])) {
    $client_id = $_GET['delete_id'];
    $conn->query("DELETE FROM clients WHERE id = $client_id");
}

// Search Clients
$search_query = "";
if (isset($_POST['search'])) {
    $search_name = $_POST['search_name'];
    $search_email = $_POST['search_email'];
    $search_status = $_POST['search_status'];
    
    $search_query = "WHERE name LIKE '%$search_name%' AND email LIKE '%$search_email%' AND status LIKE '%$search_status%'";
}

$sql = "SELECT * FROM clients $search_query";
$result = $conn->query($sql);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Client Dashboard</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>

<div class="container">
    <h2>Welcome to Client Management</h2>

    <!-- Add Client Form -->
    <div class="section-add">
        <h3>Add Client</h3>
        <form method="POST" action="dashboard.php">
            <label for="client_name">Name:</label>
            <input type="text" name="client_name" id="client_name" required>

            <label for="client_email">Email:</label>
            <input type="email" name="client_email" id="client_email" required>

            <label for="client_status">Status:</label>
            <select name="client_status" id="client_status">
                <option value="Active">Active</option>
                <option value="Inactive">Inactive</option>
            </select>

            <button type="submit" name="add_client">Add Client</button>
        </form>
    </div>

    <!-- Search Client Form -->
    <div class="section-search">
        <h3>Search Clients</h3>
        <form method="POST" action="dashboard.php">
            <input type="text" name="search_name" placeholder="Search by name">
            <input type="email" name="search_email" placeholder="Search by email">
            <select name="search_status">
                <option value="">Select Status</option>
                <option value="Active">Active</option>
                <option value="Inactive">Inactive</option>
            </select>
            <button type="submit" name="search">Search</button>
        </form>
    </div>

    <!-- Client List -->
    <div class="section-list">
        <h3>Client List</h3>
        <table>
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        echo "<tr>
                                <td>{$row['name']}</td>
                                <td>{$row['email']}</td>
                                <td>{$row['status']}</td>
                                <td>
                                    <a href='dashboard.php?delete_id={$row['id']}'>Delete</a>
                                </td>
                              </tr>";
                    }
                } else {
                    echo "<tr><td colspan='4'>No clients found</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>
