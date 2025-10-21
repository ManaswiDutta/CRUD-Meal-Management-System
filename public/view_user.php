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
if ((int)$user['role_id'] === 1) {
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
$role_name = $role_names[(int)$user['role_id']] ?? 'Unknown';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>View User — <?= htmlspecialchars($user['username']) ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        /* header / page wrapper */
        body { background:#f3f6fb; }
        .page-shell { max-width:1100px; margin:36px auto; padding:18px; }
        .page-header {
            display:flex;
            align-items:center;
            justify-content:space-between;
            gap:16px;
            margin-bottom:18px;
        }
        .profile { display:flex; gap:16px; align-items:center; }
        .avatar {
            width:88px; height:88px; border-radius:12px;
            display:flex; align-items:center; justify-content:center;
            font-weight:700; color:#fff; font-size:1.6rem;
            background: linear-gradient(135deg,#4f46e5,#10b981);
            box-shadow: 0 8px 24px rgba(15,23,42,0.08);
        }
        .meta h1 { margin:0; font-size:1.3rem; color:#0f172a; }
        .meta p { margin:6px 0 0 0; color:#64748b; font-size:0.95rem; }
        .badge {
            display:inline-block; padding:6px 10px; border-radius:999px;
            font-size:0.85rem; font-weight:700; color:#fff; margin-top:8px;
        }
        .badge.student { background:#10b981; }
        .badge.super { background:#f59e0b; }
        .badge.admin { background:#4f46e5; }

        .buttons { display:flex; gap:10px; align-items:center; }
        .buttons a { text-decoration:none; }
        .buttons .btn {
            padding:10px 14px; border-radius:8px; border:0; cursor:pointer;
            font-weight:700; color:#fff; background:#4f46e5;
            box-shadow: 0 6px 18px rgba(15,23,42,0.06);
        }
        .buttons .btn.secondary { background:#10b981; }
        .buttons .btn.ghost { background:transparent; color:#0f172a; border:1px solid #e6e9ef; }

        .detail-grid {
            display:grid;
            grid-template-columns: repeat(3, 1fr);
            gap:18px;
        }
        .card {
            background:#fff; border-radius:12px; padding:18px;
            box-shadow: 0 8px 28px rgba(15,23,42,0.04);
            min-height:120px;
        }
        .card h3 { margin:0 0 12px 0; font-size:1rem; color:#0f172a; }
        .card table { width:100%; border-collapse:separate; border-spacing:0 8px; }
        .card tr { background:transparent; }
        .card th, .card td {
            text-align:left; padding:10px 12px; vertical-align:middle;
        }
        .card th {
            width:140px;
            color:#ffffff;               /* make header text white */
            font-weight:700;
            font-size:0.95rem;
            padding:10px 12px;
        }
        /* optional: give th a subtle dark bg so white text is readable */
        /* uncomment if you want a background for the th cells */
        /*
        .card th {
            background: rgba(15,23,42,0.08);
            color: #ffffff;
        }
        */
        .card td {
            color:#0f172a; background:#f8fafc; border-radius:8px;
        }

        /* responsive */
        @media (max-width:980px) { .detail-grid { grid-template-columns: repeat(2, 1fr); } }
        @media (max-width:640px) { .detail-grid { grid-template-columns: 1fr; } .page-header{flex-direction:column;align-items:flex-start} }
    </style>
</head>
<body>
<div class="page-shell dashboard-container" style="padding-top:8px;">
    <div class="page-header">
        <div class="profile">
            <div class="avatar"><?= strtoupper(substr(htmlspecialchars($user['username']),0,1)) ?></div>
            <div class="meta">
                <h1><?= htmlspecialchars($user['username']) ?></h1>
                <p><?= htmlspecialchars($user['email']) ?></p>
                <div>
                    <?php
                        $cls = ((int)$user['role_id'] === 1) ? 'student' : (((int)$user['role_id'] === 2) ? 'super' : 'admin');
                    ?>
                    <span class="badge <?= $cls ?>"><?= htmlspecialchars($role_name) ?></span>
                </div>
            </div>
        </div>

        <div class="buttons">
            <a href="manage_users.php"><button class="btn ghost">← Back</button></a>
            <a href="edit_user.php?edit_id=<?= (int)$user_id ?>"><button class="btn secondary">Edit</button></a>
        </div>
    </div>

    <div class="detail-grid">
        <div class="card">
            <h3>Account Info</h3>
            <table>
                <tr><th>Username</th><td><?= htmlspecialchars($user['username']) ?></td></tr>
                <tr><th>Email</th><td><?= htmlspecialchars($user['email']) ?></td></tr>
                <tr><th>Role</th><td><?= htmlspecialchars($role_name) ?></td></tr>
            </table>
        </div>

        <div class="card">
            <h3>Student Info</h3>
            <?php if ((int)$user['role_id'] === 1): ?>
                <table>
                    <tr><th>Roll No</th><td><?= htmlspecialchars($student['roll_no'] ?? '-') ?></td></tr>
                    <tr><th>Department</th><td><?= htmlspecialchars($student['department'] ?? '-') ?></td></tr>
                    <tr><th>Year</th><td><?= htmlspecialchars($student['year'] ?? '-') ?></td></tr>
                </table>
            <?php else: ?>
                <p style="color:#64748b; font-style:italic; margin:6px 0 0 0;">This account is not a student.</p>
            <?php endif; ?>
        </div>

        <div class="card">
            <h3>Meal Preferences</h3>
            <?php if ((int)$user['role_id'] === 1): ?>
                <table>
                    <tr><th>Lunch</th><td><?= htmlspecialchars($meal['lunch_preference'] ?? '-') ?></td></tr>
                    <tr><th>Dinner 1</th><td><?= htmlspecialchars($meal['dinner_preference1'] ?? '-') ?></td></tr>
                    <tr><th>Dinner 2</th><td><?= htmlspecialchars($meal['dinner_preference2'] ?? '-') ?></td></tr>
                </table>
            <?php else: ?>
                <p style="color:#64748b; font-style:italic; margin:6px 0 0 0;">No meal preferences available.</p>
            <?php endif; ?>
        </div>
    </div>
</div>
</body>
</html>