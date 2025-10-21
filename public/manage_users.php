<?php
session_start();
include '../backend/config/db_connect.php';

// Access control
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role_id'], [2, 3])) {
    header("Location: blocked.php");
    exit;
}

$is_admin = $_SESSION['role_id'] == 3;
$is_super = $_SESSION['role_id'] == 2;

// Build query based on role
if ($is_admin) {
    $sql = "SELECT u.id, u.username, u.email, r.name AS role_name
            FROM users u
            JOIN roles r ON u.role_id = r.id
            ORDER BY u.id ASC";
} else {
    // Super can only see students
    $sql = "SELECT u.id, u.username, u.email, r.name AS role_name
            FROM users u
            JOIN roles r ON u.role_id = r.id
            WHERE u.role_id = 1
            ORDER BY u.id ASC";
}

$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Manage Users</title>
  <link rel="stylesheet" href="assets/css/style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body>
<header>
  <h1><?= $is_admin ? "Admin Dashboard" : "Superintendent Dashboard" ?></h1>
  <nav>
    <a href="<?= $is_admin ? 'admin_dashboard.php' : 'super_dashboard.php' ?>">Home</a>
    <a href="manage_users.php" class="active">Manage Users</a>
    <a href="logout.php" class="btn">Logout</a>
  </nav>
</header>

<div class="container">

  <div class="dashboard-actions">
    <h2>Manage Users</h2>
    <div style="display:flex; gap:10px; align-items:center;">
        <div class="search-box">
        <input type="text" id="searchInput" placeholder="Search users...">
        <button id="searchButton"><i class="fa fa-search"></i></button>
        </div>

        <?php if ($is_admin): ?>
        <a href="create_user.php" class="btn"><i class="fa fa-plus"></i> Create User</a>
        <?php endif; ?>
    </div>
    </div>


  <div class="card">
    <table>
      <thead>
        <tr>
          <th>ID</th>
          <th>Username</th>
          <th>Email</th>
          <th>Role</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody id="usersTableBody">
        <?php if ($result->num_rows > 0): ?>
          <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
              <td><?= $row['id'] ?></td>
              <td><?= htmlspecialchars($row['username']) ?></td>
              <td><?= htmlspecialchars($row['email']) ?></td>
              <td><?= htmlspecialchars($row['role_name']) ?></td>
              <td>
                <a href="view_user.php?user_id=<?= $row['id'] ?>" class="btn" style="background:#10b981;"><i class="fa fa-eye"></i> View</a>
                <a href="edit_user.php?edit_id=<?= $row['id'] ?>" class="btn"><i class="fa fa-edit"></i> Edit</a>
                <?php if ($is_admin): ?>
                  <a href="delete_user.php?id=<?= $row['id'] ?>" class="btn" style="background:#c0392b;"><i class="fa fa-trash"></i> Delete</a>
                <?php endif; ?>
              </td>
            </tr>
          <?php endwhile; ?>
        <?php else: ?>
          <tr><td colspan="5" style="text-align:center;">No users found.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>


<script src="assets/js/search.js"></script>


<footer>
  &copy; <?= date('Y'); ?> Ramakrishna Mission Vidyamandira 
</footer>
</body>
</html>
