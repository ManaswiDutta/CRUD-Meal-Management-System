<?php
session_start();
include '../backend/config/db_connect.php';

// Only admin can access
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 3 ) {
    header("Location: login.php");
    exit;
}

$username = $email = $role_id = "";
$edit_mode = false;

// Check if we are editing an existing user
if (isset($_GET['edit_id'])) {
    $edit_id = (int)$_GET['edit_id'];
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->bind_param("i", $edit_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        $username = $user['username'];
        $email = $user['email'];
        $role_id = $user['role_id'];
        $edit_mode = true;
    } else {
        $error = "User not found.";
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $role_id = (int)$_POST['role_id'];
    $password = trim($_POST['password']);

    if ($edit_mode) {
        // Update existing user
        if (!empty($password)) {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $sql = "UPDATE users SET username=?, email=?, role_id=?, password_hash=? WHERE id=?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssisi", $username, $email, $role_id, $hashed_password, $edit_id);
        } else {
            // If no password entered, keep old one
            $sql = "UPDATE users SET username=?, email=?, role_id=? WHERE id=?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssii", $username, $email, $role_id, $edit_id);
        }

        if ($stmt->execute()) {
            $success = "User updated successfully! Redirecting...";
            header("Refresh: 2; url=manage_users.php");
        } else {
            $error = "Error updating user: " . $conn->error;
        }

    } else {
        // Create new user
        if (!empty($password)) {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $sql = "INSERT INTO users (username, email, password_hash, role_id) VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssi", $username, $email, $hashed_password, $role_id);
            if ($stmt->execute()) {
                $success = "User created successfully! Redirecting...";
                $username = $email = "";
                header("Refresh: 2; url=manage_users.php");
            } else {
                $error = "Error creating user: " . $conn->error;
            }
        } else {
            $error = "Password is required when creating a new user.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= $edit_mode ? "Edit User" : "Create User" ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="form-container">
        <h2><?= $edit_mode ? "Edit User" : "Create New User" ?></h2>

        <?php if (isset($error)) echo "<p style='color:red;'>$error</p>"; ?>
        <?php if (isset($success)) echo "<p style='color:green;'>$success</p>"; ?>

        <form method="POST" action="">
            <label>Username:</label><br>
            <input type="text" name="username" value="<?= htmlspecialchars($username) ?>" required><br><br>

            <label>Email:</label><br>
            <input type="email" name="email" value="<?= htmlspecialchars($email) ?>" required><br><br>

            <label>Password <?= $edit_mode ? "(leave blank to keep current)" : "" ?>:</label><br>
            <input type="password" name="password"><br><br>

            <label>Role:</label><br>
            <select name="role_id" required>
                <option value="">-- Select Role --</option>
                <option value="1" <?= ($role_id == 1) ? "selected" : "" ?>>Student</option>
                <option value="2" <?= ($role_id == 2) ? "selected" : "" ?>>Superintendent</option>
                <option value="3" <?= ($role_id == 3) ? "selected" : "" ?>>Admin</option>
            </select><br><br>

            <button type="submit"><?= "Create User" ?></button>
        </form>

        <p><a href="admin_dashboard.php">← Back to Dashboard</a></p>
    </div>
</body>
</html>
