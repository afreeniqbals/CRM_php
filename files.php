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

// Handle file upload
if (isset($_POST['upload_file'])) {
    if (isset($_FILES['file']) && $_FILES['file']['error'] === 0) {
        $file_name = $_FILES['file']['name'];
        $file_tmp = $_FILES['file']['tmp_name'];
        $file_size = $_FILES['file']['size'];

        // Make sure uploads folder exists
        $upload_dir = 'uploads/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        // Check file size (max 5MB)
        if ($file_size <= 5000000) {
            $ext = pathinfo($file_name, PATHINFO_EXTENSION);
            $new_name = uniqid('', true) . '.' . $ext;
            $destination = $upload_dir . $new_name;

            if (move_uploaded_file($file_tmp, $destination)) {
                $stmt = $conn->prepare("INSERT INTO client_files (client_id, file_name, file_path) VALUES (?, ?, ?)");
                $stmt->bind_param("iss", $client_id, $new_name, $destination);
                $stmt->execute();
                $stmt->close();
            } else {
                echo "Failed to upload file.";
            }
        } else {
            echo "File too large. Max 5MB.";
        }
    } else {
        echo "No file uploaded or an error occurred.";
    }
}

// Fetch uploaded files
$files = [];
$stmt = $conn->prepare("SELECT id, file_name, file_path, uploaded_at FROM client_files WHERE client_id = ?");
$stmt->bind_param("i", $client_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $files[] = $row;
}
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>File Uploads</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
<div class="container">
    <h1>File Uploads for <?= htmlspecialchars($client['name']) ?> (<?= htmlspecialchars($client['email']) ?>)</h1>

    <div class="card">
        <h2>Upload File</h2>
        <form method="POST" enctype="multipart/form-data">
            <div class="input-group">
                <label for="file">Select File</label>
                <input type="file" name="file" id="file" required>
            </div>
            <button type="submit" name="upload_file">Upload</button>
        </form>
    </div>

    <div class="card">
        <h2>Uploaded Files</h2>
        <?php if (count($files) > 0): ?>
            <ul>
                <?php foreach ($files as $file): ?>
                    <li><a href="<?= htmlspecialchars($file['file_path']) ?>" target="_blank"><?= htmlspecialchars($file['file_name']) ?></a> - <?= $file['uploaded_at'] ?></li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p>No files uploaded.</p>
        <?php endif; ?>
    </div>

    <p><a href="dashboard.php">&larr; Back to Dashboard</a></p>
</div>
</body>
</html>
