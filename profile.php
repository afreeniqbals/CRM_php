<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$conn = new mysqli("localhost", "root", "", "crm_db");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch current user info
$stmt = $conn->prepare("SELECT username, email FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($username, $email);
$stmt->fetch();
$stmt->close();

$success_msg = "";
$error_msg = "";

// Update Profile Handler
if (isset($_POST['update_profile'])) {
    $new_username = $_POST['username'];
    $new_password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if ($new_password !== $confirm_password) {
        $error_msg = "Passwords do not match!";
    } else {
        if (!empty($new_password)) {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET username = ?, password = ? WHERE id = ?");
            $stmt->bind_param("ssi", $new_username, $hashed_password, $user_id);
        } else {
            $stmt = $conn->prepare("UPDATE users SET username = ? WHERE id = ?");
            $stmt->bind_param("si", $new_username, $user_id);
        }
        if ($stmt->execute()) {
            $success_msg = "Profile updated successfully!";
            $_SESSION['username'] = $new_username;  // update session username
            $username = $new_username;
        } else {
            $error_msg = "Error updating profile!";
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>User Profile</title>
    <link rel="stylesheet" href="styles.css" />
</head>
<body>
    <h2>User Profile</h2>

    <?php if ($success_msg) echo "<p style='color: green;'>$success_msg</p>"; ?>
    <?php if ($error_msg) echo "<p style='color: red;'>$error_msg</p>"; ?>

    <form method="POST" action="">
        <label>Username:</label><br/>
        <input type="text" name="username" value="<?= htmlspecialchars($username) ?>" required /><br/><br/>

        <label>Email (read-only):</label><br/>
        <input type="email" value="<?= htmlspecialchars($email) ?>" readonly /><br/><br/>

        <label>New Password (leave blank to keep current):</label><br/>
        <input type="password" name="password" /><br/><br/>

        <label>Confirm Password:</label><br/>
        <input type="password" name="confirm_password" /><br/><br/>

        <button type="submit" name="update_profile">Update Profile</button>
    </form>

    <p><a href="dashboard.php">Back to Dashboard</a></p>
</body>
</html>
