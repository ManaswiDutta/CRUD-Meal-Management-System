<?php
session_start();
include '../backend/config/db_connect.php';

// Only allow students
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 1) {
    header("Location: blocked.php");
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
    <title>Student Dashboard — <?= htmlspecialchars($_SESSION['username'] ?? 'Student') ?></title>
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .dashboard-container { max-width:1100px; margin:32px auto; padding:18px; }
        .dash-header { display:flex; align-items:center; justify-content:space-between; gap:12px; margin-bottom:18px; }
        .profile { display:flex; gap:14px; align-items:center; }
        .avatar {
            width:72px; height:72px; border-radius:12px;
            display:flex; align-items:center; justify-content:center;
            font-weight:800; color:#fff; font-size:1.4rem;
            background: linear-gradient(135deg,#4f46e5,#4f46e5);
            box-shadow: 0 8px 24px rgba(2,6,23,0.06);
        }
        .meta h1 { margin:0; font-size:1.25rem; color:#0f172a; }
        .meta p { margin:6px 0 0 0; color:#64748b; font-size:0.95rem; }

        /* Header container narrower and centered (override inline styles) */
        header .container {
            max-width: 980px;
            margin: 0 auto;
            padding: 12px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .actions { display:flex; gap:10px; align-items:center; }
        .btn { padding:9px 14px; border-radius:10px; border:0; cursor:pointer; font-weight:700; color:#fff; background:#4f46e5; box-shadow:0 8px 22px rgba(15,23,42,0.06); }
        .btn.ghost { background:transparent; color:#0f172a; border:1px solid #e6e9ef; box-shadow:none; }
        .btn.secondary { background:#10b981; }

        .grid { display:grid; grid-template-columns: repeat(3,1fr); gap:18px; margin-top:12px; }
        .card { background:#fff; border-radius:12px; padding:16px; box-shadow: 0 10px 28px rgba(15,23,42,0.04); }
        .card h3 { margin:0 0 12px 0; color:#0f172a; font-size:1rem; }

        /* table layout: separate rows with rounded cells and white th text */
        .card table {
            width:100%;
            border-collapse:separate;
            border-spacing:0 10px;
        }
        .card tr {
            display:flex;
            gap:12px;
            align-items:center;
        }

        /* make the left-side header cell visually prominent with white text */
        .card th {
            width:160px;
            color:#ffffff;                          /* white text */
            background: linear-gradient(90deg,#f28c28,#f28c28); /* colored pill */
            padding:10px 14px;
            border-radius:10px;
            font-weight:800;
            text-align:left;
            box-shadow: 0 6px 18px rgba(79,70,229,0.08);
        }

        /* data cells appear as light rounded boxes */
        .card td {
            background:#f8fafc;
            padding:10px 14px;
            color:#0f172a;
            border-radius:10px;
            flex:1;
        }

        /* ensure last row corners look rounded inside the card */
        .card table tr:last-child th { border-bottom-left-radius:10px; }
        .card table tr:last-child td { border-bottom-right-radius:10px; }

        @media (max-width:980px) { .grid { grid-template-columns: repeat(2,1fr); } }
        @media (max-width:640px) { .grid { grid-template-columns: 1fr; } .dash-header{flex-direction:column;align-items:flex-start} .avatar{width:64px;height:64px} }
    </style>
</head>
<body>
<header>
  <div class="container" style="display:flex;align-items:center;justify-content:space-between;">
    <h1 style="margin:0;font-size:1.05rem;">Reynold System</h1>
    <nav>
      <a href="notifications.php" style="margin-right:12px;">Notifications</a>
      <a href="edit_user.php?edit_id=<?= (int)$user_id ?>" style="margin-right:12px;">Edit</a>

      <!-- replaced anchor-wrapped button with a POST form to ensure logout reliably clears session -->
      <form method="POST" action="logout.php" style="display:inline-block;margin:0;padding:0;">
        <button type="submit" class="btn ghost" style="margin:0;">Logout</button>
      </form>
    </nav>
  </div>
</header>

<div class="dashboard-container">
    <div class="dash-header">
        <div class="profile">
            <div class="avatar"><?= strtoupper(substr(htmlspecialchars($_SESSION['username'] ?? 'S'), 0, 1)) ?></div>
            <div class="meta">
                <h1>Welcome, <?= htmlspecialchars($_SESSION['username'] ?? 'Student') ?></h1>
                <p>Student Dashboard — View and update your details</p>
            </div>
        </div>

        <div class="actions">
            <a href="edit_user.php?edit_id=<?= (int)$user_id ?>"><button class="btn secondary">Edit My Info</button></a>
            <a href="leave_apply.php"><button class="btn" style="background: linear-gradient(90deg,#f28c28,#c46516);">Apply for Leave</button></a>
        </div>
    </div>

    <div class="grid">
        <div class="card">
            <h3>Account Info</h3>
            <table>
                <tr><th>Username</th><td><?= htmlspecialchars($user['username'] ?? '-') ?></td></tr>
                <tr><th>Email</th><td><?= htmlspecialchars($user['email'] ?? '-') ?></td></tr>
            </table>
        </div>

        <div class="card">
            <h3>Student Info</h3>
            <table>
                <tr><th>Roll No</th><td><?= htmlspecialchars($student['roll_no'] ?? '-') ?></td></tr>
                <tr><th>Department</th><td><?= htmlspecialchars($student['department'] ?? '-') ?></td></tr>
                <tr><th>Year</th><td><?= htmlspecialchars($student['year'] ?? '-') ?></td></tr>
            </table>
        </div>

        <div class="card">
            <h3>Meal Preferences</h3>
            <table>
                <tr><th>Lunch</th><td><?= htmlspecialchars($meal['lunch_preference'] ?? '-') ?></td></tr>
                <tr><th>Dinner 1</th><td><?= htmlspecialchars($meal['dinner_preference1'] ?? '-') ?></td></tr>
                <tr><th>Dinner 2</th><td><?= htmlspecialchars($meal['dinner_preference2'] ?? '-') ?></td></tr>
            </table>
        </div>
    </div>
</div>
</body>
</html>