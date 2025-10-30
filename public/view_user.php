<?php
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
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link rel="stylesheet" href="assets/css/style.css">
  <style>
    body { background:#f7f6f2; font-family:'Segoe UI',sans-serif; }
    .container { max-width:1100px; margin:40px auto; padding:20px; }

    /* header */
    .header { display:flex; align-items:center; justify-content:space-between; margin-bottom:24px; }
    .profile { display:flex; align-items:center; gap:16px; }
    .avatar {
        width:80px; height:80px; border-radius:14px;
        background:linear-gradient(135deg,#f28c28,#ffba49);
        display:flex; align-items:center; justify-content:center;
        font-size:1.6rem; font-weight:700; color:#fff;
        box-shadow:0 10px 25px rgba(15,23,42,0.08);
    }
    .meta h1 { margin:0; font-size:1.3rem; color:#0f172a; }
    .meta p { margin:6px 0 0 0; color:#6b7280; }

    .badge {
        display:inline-block;
        margin-top:6px;
        padding:6px 12px;
        font-size:0.85rem;
        border-radius:999px;
        font-weight:700;
        color:#fff;
    }
    .student { background:#10b981; }
    .super { background:#f59e0b; }
    .admin { background:#4f46e5; }

    .actions { display:flex; gap:10px; }
    .btn {
        padding:10px 16px;
        border:none;
        border-radius:10px;
        font-weight:600;
        cursor:pointer;
        color:#fff;
        background:#f28c28;
        box-shadow:0 6px 20px rgba(15,23,42,0.06);
        transition:0.2s ease;
    }
    .btn:hover { transform:translateY(-1px); }
    .btn.secondary { background:#10b981; }
    .btn.ghost { background:transparent; color:#0f172a; border:1px solid #e6e9ef; box-shadow:none; }

    /* grid of cards */
    .grid { display:grid; grid-template-columns:repeat(3,1fr); gap:20px; margin-top:20px; }
    .card {
        background:#fff;
        border-radius:14px;
        padding:18px;
        box-shadow:0 10px 28px rgba(15,23,42,0.05);
    }
    .card h3 {
        margin:0 0 12px 0;
        font-size:1.05rem;
        color:#0f172a;
        border-bottom:2px solid rgba(242,140,40,0.3);
        padding-bottom:6px;
    }

    /* styled table */
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
    .card th {
        width:160px;
        color:#fff;
        background:linear-gradient(90deg,#f28c28,#f59e0b);
        padding:10px 14px;
        border-radius:10px;
        font-weight:700;
        font-size:0.95rem;
        text-align:left;
        box-shadow:0 4px 12px rgba(15,23,42,0.08);
    }
    .card td {
        background:#f8fafc;
        padding:10px 14px;
        color:#111827;
        border-radius:10px;
        flex:1;
    }

    /* responsiveness */
    @media (max-width:980px) { .grid { grid-template-columns:repeat(2,1fr); } }
    @media (max-width:640px) { .grid { grid-template-columns:1fr; } .header{flex-direction:column;align-items:flex-start} }
  </style>
</head>

<body>

<?php include 'includes/headers.php'; ?>

  <div class="container">
    <div class="header">
      <div class="profile">
        <div class="avatar"><?= strtoupper(substr(htmlspecialchars($user['username']),0,1)) ?></div>
        <div class="meta">
          <h1><?= htmlspecialchars($user['username']) ?></h1>
          <p><?= htmlspecialchars($user['email']) ?></p>
          <?php
            $cls = ((int)$user['role_id'] === 1) ? 'student' : (((int)$user['role_id'] === 2) ? 'super' : 'admin');
          ?>
          <span class="badge <?= $cls ?>"><?= htmlspecialchars($role_name) ?></span>
        </div>
      </div>

      <div class="actions">
        <a href="manage_users.php"><button class="btn ghost">← Back</button></a>
        <a href="edit_user.php?edit_id=<?= (int)$user_id ?>"><button class="btn secondary">Edit</button></a>
      </div>
    </div>

    <div class="grid">
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
          <p style="color:#6b7280;font-style:italic;">This account is not a student.</p>
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
          <p style="color:#6b7280;font-style:italic;">No meal preferences available.</p>
        <?php endif; ?>
      </div>
    </div>
  </div>
</body>
</html>