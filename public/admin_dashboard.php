<?php
session_start();
include '../backend/config/db_connect.php';

// Access control: only admins
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 3) {
    header("Location: blocked.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Dashboard</title>
  <link rel="stylesheet" href="assets/css/style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body>
  <!-- Header -->
  <header>
    <h1>Admin Dashboard</h1>
    <nav>
      <a href="admin_dashboard.php">Home</a>
      <a href="create_user.php">Create User</a>
      <a href="manage_users.php">Manage Users</a>
      <a href="reports.php">Reports</a>
      <a href="logout.php" class="btn">Logout</a>
    </nav>
  </header>

  <!-- Main Content -->
  <div class="container dashboard">
    <div class="dashboard-actions">
      <h2>Welcome, <?= htmlspecialchars($_SESSION['username']); ?> 👋</h2>

      <!-- Search box -->
      <!-- <div class="search-box">
        <input type="text" id="searchInput" placeholder="Search users...">
        <button id="searchButton"><i class="fa fa-search"></i></button>
      </div> -->
    </div>

    <!-- Cards Section -->
    <div class="card-list">
      <div class="card">
        <h3>User Management</h3>
        <p>Create, view, and manage all users including students and superintendents.</p>
        <a href="manage_users.php" class="btn">Open</a>
      </div>

      <div class="card">
        <h3>Create New User</h3>
        <p>Add new users to the system with appropriate roles and credentials.</p>
        <a href="create_user.php" class="btn">Open</a>
      </div>

      <div class="card">
        <h3>Attendence & Leave history</h3>
        <p>View monthly attendence and leave report</p>
        <a href="reports.php" class="btn">Open</a>
      </div>

      <div class="card">
        <h3>Mess Overview</h3>
        <p>Monitor mess statistics, expences and trends</p>
        <a href="system_overview.php" class="btn">Open</a>
      </div>
    </div>
  </div>

  <!-- Footer -->
  <footer>
    &copy; <?= date('Y'); ?> Ramakrishna Mission Vidyamandira
  </footer>

  <script src="assets/js/search.js"></script>
</body>
</html>
