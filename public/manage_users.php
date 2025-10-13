<?php
session_start();
include '../backend/config/db_connect.php';

// Protect page: only admins
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 3) {
    header("Location: login.php");
    exit;
}

// Handle delete action
if (isset($_GET['delete_id'])) {
    $delete_id = (int)$_GET['delete_id'];

    // Prevent admin from deleting themselves
    if ($delete_id == $_SESSION['user_id']) {
        $error = "You cannot delete your own account.";
    } else {
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt->bind_param("i", $delete_id);
        if ($stmt->execute()) {
            $success = "User deleted successfully.";
        } else {
            $error = "Error deleting user: " . $conn->error;
        }
    }
}

// Fetch all users
$sql = "SELECT u.id, u.username, u.email, r.name AS role_name
        FROM users u
        JOIN roles r ON u.role_id = r.id
        ORDER BY u.id ASC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Users</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="table-container">
        <h2>Manage Users</h2>

        <?php if (isset($error)) echo "<p style='color:red;'>$error</p>"; ?>
        <?php if (isset($success)) echo "<p style='color:green;'>$success</p>"; ?>

        <table border="1" cellpadding="10">
            <tr>
                <th>ID</th>
                <th>Username</th>
                <th>Email</th>
                <th>Role</th>
                <th>Actions</th>
            </tr>
            <?php if ($result->num_rows > 0): ?>
                <?php while($user = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?= $user['id'] ?></td>
                        <td><?= htmlspecialchars($user['username']) ?></td>
                        <td><?= htmlspecialchars($user['email']) ?></td>
                        <td><?= htmlspecialchars($user['role_name']) ?></td>
                        <td>
                            <a href="view_user.php?user_id=<?= $user['id'] ?>"><button>View</button></a>
                            <a href="edit_user.php?edit_id=<?= $user['id'] ?>"><button>Edit</button></a>
                            <a href="manage_users.php?delete_id=<?= $user['id'] ?>" onclick="return confirm('Are you sure?')"><button>Delete</button></a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="5">No users found.</td></tr>
            <?php endif; ?>
        </table>

        <p><a href="admin_dashboard.php">← Back to Dashboard</a></p>
    </div>
</body>
</html>
