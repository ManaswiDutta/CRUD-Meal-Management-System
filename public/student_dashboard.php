<?php

session_start();
include '../backend/config/db_connect.php';

// Only allow students
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 1) {
    header("Location: blocked.php");
    exit;
}

// Logout
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Fetch account info
$stmt = $conn->prepare("SELECT username, email FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_result = $stmt->get_result();
$user = $user_result->fetch_assoc();

// Fetch student info
$stmt = $conn->prepare("SELECT roll_no, department, year FROM student_details WHERE student_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$student_result = $stmt->get_result();
$student = $student_result->fetch_assoc();

// Fetch meal preferences
$stmt = $conn->prepare("SELECT lunch_preference, dinner_preference1, dinner_preference2 FROM meal_preference WHERE student_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$meal_result = $stmt->get_result();
$meal = $meal_result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Student Dashboard</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="dashboard-container">
        <h1>Welcome, <?= htmlspecialchars($_SESSION['username']); ?>!</h1>
        <p>You are logged in as <strong>Student</strong>.</p>

        <h2>Account Info</h2>
        <table border="1" cellpadding="8">
            <tr><th>Username</th><td><?= htmlspecialchars($user['username']) ?></td></tr>
            <tr><th>Email</th><td><?= htmlspecialchars($user['email']) ?></td></tr>
        </table>

        <h2>Student Info</h2>
        <table border="1" cellpadding="8">
            <tr><th>Roll No</th><td><?= htmlspecialchars($student['roll_no'] ?? '-') ?></td></tr>
            <tr><th>Department</th><td><?= htmlspecialchars($student['department'] ?? '-') ?></td></tr>
            <tr><th>Year</th><td><?= htmlspecialchars($student['year'] ?? '-') ?></td></tr>
        </table>

        <h2>Meal Preferences</h2>
        <table border="1" cellpadding="8">
            <tr><th>Lunch</th><td><?= htmlspecialchars($meal['lunch_preference'] ?? '-') ?></td></tr>
            <tr><th>Dinner 1</th><td><?= htmlspecialchars($meal['dinner_preference1'] ?? '-') ?></td></tr>
            <tr><th>Dinner 2</th><td><?= htmlspecialchars($meal['dinner_preference2'] ?? '-') ?></td></tr>
        </table>

        <br>
        <a href="edit_user.php?edit_id=<?= $user_id ?>"><button>Edit My Info</button></a>
        <a href="student_dashboard.php?logout=true"><button>Logout</button></a>
    </div>
</body>
</html>