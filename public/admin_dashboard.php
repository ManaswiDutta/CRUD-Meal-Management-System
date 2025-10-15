<?php
session_start();
include '../backend/config/db_connect.php';

// Protect page: only admins
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 3) {
    header("Location: blocked.php");
    exit;
}

// Optional: handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: login.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="dashboard-container">
        <h1>Welcome, <?= htmlspecialchars($_SESSION['username']); ?>!</h1>
        <p>You are logged in as <strong>Admin</strong>.</p>

        <div class="dashboard-buttons">
            <a href="create_user.php"><button>Create New User</button></a>
            <a href="manage_users.php"><button>View / Manage Users</button></a>
            <a href="admin_dashboard.php?logout=true"><button>Logout</button></a>
        </div>
    </div>
</body>
</html>
