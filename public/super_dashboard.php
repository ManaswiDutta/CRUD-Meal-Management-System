<?php
session_start();
include '../backend/config/db_connect.php';

// Access control: only superintendents (role_id = 2)
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 2) {
    header("Location: blocked.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Superintendent Dashboard</title>
  <link rel="stylesheet" href="assets/css/style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body>
<?php include 'includes/super_header.php'; ?>


  <div class="container dashboard">
    <div class="dashboard-actions">
      <h2>Welcome, <?= htmlspecialchars($_SESSION['username']); ?> 👋</h2>

      <!-- Search box -->
      <!-- <div class="search-box">
        <input type="text" id="searchInput" placeholder="Search students...">
        <button id="searchButton"><i class="fa fa-search"></i></button>
      </div> -->
    </div>

    <!-- Cards Section -->
    <div class="card-list">
      <div class="card">
        <h3>Student Management</h3>
        <p>View and edit student details, update their meal preferences, and monitor their profiles.</p>
        <a href="manage_users.php" class="btn">Open</a>
      </div>

      <div class="card">
        <h3>Meal Preferences</h3>
        <p>Access and adjust student meal preferences for lunch and dinner.</p>
        <a href="meal_count.php" class="btn">Open</a>
      </div>

      <div class="card">
        <h3>Leave Management</h3>
        <p>manage student leaves.</p>
        <a href="manage_leave.php" class="btn">Open</a>
      </div>
    </div>
  </div>

  <footer>
    &copy; <?= date('Y'); ?> Ramakrishna Mission Vidyamandira 
  </footer>

  <script src="assets/js/search.js"></script>
</body>
</html>
