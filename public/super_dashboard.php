<?php
session_start();
include '../backend/config/db_connect.php';

// Only allow superintendents
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 2) {
    header("Location: blocked.php");
    exit;
}

// Logout
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: login.php");
    exit;
}

// Fetch all students
$sql = "SELECT id, username, email FROM users WHERE role_id = 1 ORDER BY id ASC";
$result = $conn->query($sql);
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
        <p>You are logged in as a <strong>Superintendent</strong>.</p>

        <h2>Manage Students</h2>
        <table border="1" cellpadding="10">
            <tr>
                <th>ID</th>
                <th>Username</th>
                <th>Email</th>
                <th>Action</th>
            </tr>

            <?php if ($result->num_rows > 0): ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?= $row['id'] ?></td>
                        <td><?= htmlspecialchars($row['username']) ?></td>
                        <td><?= htmlspecialchars($row['email']) ?></td>
                        <td>
                            <a href="view_user.php?user_id=<?= $row['id'] ?>"><button>View</button></a>
                            <a href="edit_user.php?edit_id=<?= $row['id'] ?>"><button>Edit</button></a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="4">No students found.</td></tr>
            <?php endif; ?>
        </table>

        <a href="super_dashboard.php?logout=true">
            <button class="logout-btn">Logout</button>
        </a>
    </div>
</body>
</html>
