<?php
// filepath: c:\xampp\htdocs\Reynold\public\view_user.php
session_start();
include '../backend/config/db_connect.php';

// Only allow admin or superintendent
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role_id'], [2, 3])) {
    header("Location: blocked.php");
    exit;
}

$user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
if (!$user_id) {
    die("Invalid user ID.");
}

// Fetch account info
$stmt = $conn->prepare("SELECT username, email, role_id FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_result = $stmt->get_result();
$user = $user_result->fetch_assoc();
if (!$user) die("User not found.");

// Fetch student info if student
$student = null;
$meal = null;
if ($user['role_id'] == 1) {
    $stmt = $conn->prepare("SELECT roll_no, department, year FROM student_details WHERE student_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $student_result = $stmt->get_result();
    $student = $student_result->fetch_assoc();

    $stmt = $conn->prepare("SELECT lunch_preference, dinner_preference1, dinner_preference2 FROM meal_preference WHERE student_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $meal_result = $stmt->get_result();
    $meal = $meal_result->fetch_assoc();
}

// Role name
$role_names = [1 => 'Student', 2 => 'Superintendent', 3 => 'Admin'];
$role_name = $role_names[$user['role_id']] ?? 'Unknown';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>View User</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="dashboard-container">
    <h2>User Details</h2>
    <table border="1" cellpadding="8">
        <tr><th>Username</th><td><?= htmlspecialchars($user['username']) ?></td></tr>
        <tr><th>Email</th><td><?= htmlspecialchars($user['email']) ?></td></tr>
        <tr><th>Role</th><td><?= htmlspecialchars($role_name) ?></td></tr>
    </table>

    <?php if ($user['role_id'] == 1): ?>
        <h3>Student Info</h3>
        <table border="1" cellpadding="8">
            <tr><th>Roll No</th><td><?= htmlspecialchars($student['roll_no'] ?? '-') ?></td></tr>
            <tr><th>Department</th><td><?= htmlspecialchars($student['department'] ?? '-') ?></td></tr>
            <tr><th>Year</th><td><?= htmlspecialchars($student['year'] ?? '-') ?></td></tr>
        </table>
        <h3>Meal Preferences</h3>
        <table border="1" cellpadding="8">
            <tr><th>Lunch</th><td><?= htmlspecialchars($meal['lunch_preference'] ?? '-') ?></td></tr>
            <tr><th>Dinner 1</th><td><?= htmlspecialchars($meal['dinner_preference1'] ?? '-') ?></td></tr>
            <tr><th>Dinner 2</th><td><?= htmlspecialchars($meal['dinner_preference2'] ?? '-') ?></td></tr>
        </table>
    <?php endif; ?>

    <a href="<?= ($_SESSION['role_id'] == 3) ? 'manage_users.php' : 'super_dashboard.php' ?>"><button>Back</button></a>
</div>
</body>
</html>