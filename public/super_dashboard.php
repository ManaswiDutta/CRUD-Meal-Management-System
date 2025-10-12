<?php
session_start();
include '../backend/config/db_connect.php';

// Protect page: only superintendents
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 2) {
    header("Location: login.php");
    exit;
}

// logout
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
    <title>Superintendent Dashboard</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="dashboard-container">
        <h1>Welcome, <?= htmlspecialchars($_SESSION['username']); ?>!</h1>
        <p>You are logged in as <strong>Superintendent</strong>.</p>

        <div class="dashboard-buttons">
            <!-- Add super-specific features here -->
            <a href="super_dashboard.php?logout=true"><button>Logout</button></a>
        </div>
    </div>
</body>
</html>
