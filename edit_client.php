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

$client_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($client_id <= 0) {
    echo "Invalid client ID";
    exit();
}

// Fetch current client data
$stmt = $conn->prepare("SELECT name, email FROM clients WHERE id = ?");
$stmt->bind_param("i", $client_id);
$stmt->execute();
$result = $stmt->get_result();
$client = $result->fetch_assoc();
$stmt->close();

if (!$client) {
    echo "Client not found.";
    exit();
}

// Handle form submission to update client
if (isset($_POST['update_client'])) {
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';

    if (!empty($name) && !empty($email)) {
        $update_stmt = $conn->prepare("UPDATE clients SET name = ?, email = ? WHERE id = ?");
        $update_stmt->bind_param("ssi", $name, $email, $client_id);
        if ($update_stmt->execute()) {
            header("Location: client_management.php?msg=Client updated successfully");
            exit();
        } else {
            $error = "Failed to update client.";
        }
        $update_stmt->close();
    } else {
        $error = "Name and Email cannot be empty.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Edit Client</title>
</head>
<body>
    <h1>Edit Client</h1>
    <?php if (isset($error)) echo "<p style='color:red;'>$error</p>"; ?>
    <form method="POST">
        <label>Name</label><br />
        <input type="text" name="name" value="<?= htmlspecialchars($client['name']) ?>" required /><br /><br />
        <label>Email</label><br />
        <input type="email" name="email" value="<?= htmlspecialchars($client['email']) ?>" required /><br /><br />
        <button type="submit" name="update_client">Update Client</button>
    </form>
    <p><a href="client_management.php">&larr; Back to Client List</a></p>
</body>
</html>
