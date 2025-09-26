<?php
session_start();

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: client_management.php");
    exit();
}

$error_message = "";

// Database connection
$conn = new mysqli("localhost", "root", "", "crm_db");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle login
if (isset($_POST['login'])) {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    // Fetch id, password, role_id, and username
    $stmt = $conn->prepare("SELECT id, email, password, role_id, username FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();
    $stmt->bind_result($user_id, $user_email, $hashed_password, $role_id, $username);
    $stmt->fetch();

    if ($stmt->num_rows > 0 && password_verify($password, $hashed_password)) {
        // Store all required info in session
        $_SESSION['user_id'] = $user_id;
        $_SESSION['username'] = $username;
        $_SESSION['user_email'] = $user_email;

        // Get role name
        $role_stmt = $conn->prepare("SELECT role_name FROM roles WHERE id = ?");
        $role_stmt->bind_param("i", $role_id);
        $role_stmt->execute();
        $role_stmt->bind_result($role_name);
        $role_stmt->fetch();
        $_SESSION['role_name'] = $role_name;
        $role_stmt->close();

        header("Location: client_management.php");
        exit();
    } else {
        $error_message = "Invalid email or password.";
    }

    $stmt->close();
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>CRM Login</title>
    <link rel="stylesheet" href="login.css">
</head>
<body>
<div class="container">
    <h1 class="title">Login to CRM</h1>

    <?php if (!empty($error_message)): ?>
        <p class="error"><?= htmlspecialchars($error_message) ?></p>
    <?php endif; ?>

    <div class="card">
        <form method="POST" action="">
            <div class="input-group">
                <label for="email">Email</label>
                <input type="email" name="email" id="email" required>
            </div>
            <div class="input-group">
                <label for="password">Password</label>
                <input type="password" name="password" id="password" required>
            </div>
            <button type="submit" name="login">Login</button>
        </form>
    </div>

    <p style="text-align: center;">Don't have an account? <a href="register.php">Register here</a></p>
</div>
</body>
</html>
