<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_email = $_SESSION['user_email'];

// DB Connection
$conn = new mysqli("localhost", "root", "", "crm_db");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Add Client
if (isset($_POST['add_client'])) {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $status = $_POST['status'];
    if (!empty($name) && !empty($email) && !empty($status)) {
        $stmt = $conn->prepare("INSERT INTO clients (name, email, status) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $name, $email, $status);
        $stmt->execute();
        $stmt->close();
    }
}

// Delete Client
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $conn->query("DELETE FROM clients WHERE id = $id");
}

// Search Query Initialization
$search_query = "SELECT * FROM clients WHERE 1";

if (isset($_POST['search_client'])) {
    if (!empty($_POST['search_name'])) {
        $search_name = "%" . $_POST['search_name'] . "%";
        $search_query .= " AND name LIKE '$search_name'";
    }
    if (!empty($_POST['search_email'])) {
        $search_email = "%" . $_POST['search_email'] . "%";
        $search_query .= " AND email LIKE '$search_email'";
    }
    if (!empty($_POST['search_status'])) {
        $search_status = $_POST['search_status'];
        $search_query .= " AND status = '$search_status'";
    }
    if (!empty($_POST['search_date_from'])) {
        $search_date_from = $_POST['search_date_from'];
        $search_query .= " AND date_added >= '$search_date_from'";
    }
    if (!empty($_POST['search_date_to'])) {
        $search_date_to = $_POST['search_date_to'];
        $search_query .= " AND date_added <= '$search_date_to'";
    }

    $result = $conn->query($search_query);
} else {
    $result = $conn->query("SELECT * FROM clients");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>CRM - Client Management</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>

<!-- Header -->
<header class="top-navbar">
  <div class="container">
    <div class="user-details">
      <a href="profile.php" class="user-link">
        <?= htmlspecialchars($_SESSION['username']) ?> (<?= htmlspecialchars($_SESSION['role_name']) ?>)
      </a>
      <span class="user-link">Login ID: <?= htmlspecialchars($user_email) ?></span>
      <a href="logout.php" class="user-link">Logout</a>
    </div>
    <div class="logo">CRM System</div>
    <nav>
      <a href="dashboard.php">Dashboard</a>
      <a href="client_management.php">Clients</a>
      <a href="tasks.php">Tasks</a>
	<a href="profile.php">Profile</a>
    </nav>
  </div>
</header>


<!-- Main Container -->
<div class="container">
    <h1 class="title">Welcome to CRM System</h1>

    <!-- Add Client -->
    <div class="card">
        <h2>Add New Client</h2>
        <form method="POST" action="">
            <div class="input-group">
                <label for="name">Client Name</label>
                <input type="text" name="name" id="name" required>
            </div>
            <div class="input-group">
                <label for="email">Client Email</label>
                <input type="email" name="email" id="email" required>
            </div>
            <div class="input-group">
                <label for="status">Status</label>
                <select name="status" id="status" required>
                    <option value="">Select Status</option>
                    <option value="Active">Active</option>
                    <option value="Inactive">Inactive</option>
                </select>
            </div>
            <button type="submit" name="add_client">Add Client</button>
        </form>
    </div>

    <!-- Search -->
    <div class="card">
        <h2>Advanced Search</h2>
        <form method="POST" action="">
            <div class="input-group"><label for="search_name">Name</label><input type="text" name="search_name"></div>
            <div class="input-group"><label for="search_email">Email</label><input type="email" name="search_email"></div>
            <div class="input-group">
                <label for="search_status">Status</label>
                <select name="search_status">
                    <option value="">Select Status</option>
                    <option value="Active">Active</option>
                    <option value="Inactive">Inactive</option>
                </select>
            </div>
            <div class="input-group"><label for="search_date_from">From</label><input type="date" name="search_date_from"></div>
            <div class="input-group"><label for="search_date_to">To</label><input type="date" name="search_date_to"></div>
            <button type="submit" name="search_client">Search</button>
        </form>
    </div>

    <!-- Client List -->
    <div class="card" id="client-table-section">
        <h2>Client List</h2>
        <table>
            <thead>
                <tr>
                    <th>Name</th><th>Email</th><th>Status</th><th>Date Added</th><th>Delete</th>
                    <th>Tasks</th><th>Logs</th><th>Files</th><th>Invoices</th><th>Reports</th><th>Edit</th>
                </tr>
            </thead>
            <tbody>
            <?php
            if ($result->num_rows > 0) {
                while($row = $result->fetch_assoc()) {
                    echo "<tr>
                        <td>{$row['name']}</td>
                        <td>{$row['email']}</td>
                        <td>{$row['status']}</td>
                        <td>{$row['date_added']}</td>
                        <td><a href='?delete={$row['id']}' onclick=\"return confirm('Are you sure?')\">Delete</a></td>
                        <td><a href='tasks.php?client_id={$row['id']}'>Manage Tasks</a></td>
                        <td><a href='logs.php?client_id={$row['id']}'>View Logs</a></td>
                        <td><a href='files.php?client_id={$row['id']}'>Upload Files</a></td>
                        <td><a href='invoices.php?client_id={$row['id']}'>Manage Invoices</a></td>
                        <td><a href='reports.php?client_id={$row['id']}'>View Reports</a></td>
                        <td><a href='edit_client.php?id={$row['id']}'>Edit</a></td>
                    </tr>";
                }
            } else {
                echo "<tr><td colspan='11'>No clients found</td></tr>";
            }
            ?>
            </tbody>
        </table>
    </div>

    <!-- Download Button -->
    <div style="text-align: center; margin-top: 20px;">
        <button onclick="downloadPDF()">Download Client Report as PDF</button>
    </div>
</div>

<!-- PDF Scripts -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script>
async function downloadPDF() {
    const { jsPDF } = window.jspdf;
    const pdf = new jsPDF();
    const section = document.getElementById("client-table-section");

    await html2canvas(section, { scale: 2 }).then(canvas => {
        const imgData = canvas.toDataURL("image/png");
        const imgProps = pdf.getImageProperties(imgData);
        const pdfWidth = pdf.internal.pageSize.getWidth();
        const pdfHeight = (imgProps.height * pdfWidth) / imgProps.width;

        pdf.addImage(imgData, "PNG", 0, 0, pdfWidth, pdfHeight);
        pdf.save("client_list_report.pdf");
    });
}
</script>

</body>
</html>
